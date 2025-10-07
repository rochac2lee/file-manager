<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\File as FileModel;
use App\Models\Folder;
use App\Models\ActivityLog;

class FileManagerController extends Controller
{
    /**
     * Listar arquivos do usuário (por pasta opcional)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $folderId = $request->query('folder_id');

        // Cache key baseado no usuário e folder_id
        $cacheKey = "files_user_{$user->id}_folder_" . ($folderId ?? 'null');

        $files = cache()->remember($cacheKey, 60, function () use ($user, $folderId) {
            return FileModel::with('uploader')
                ->when($folderId, fn ($q) => $q->where('folder_id', $folderId))
                ->where(function ($q) use ($user) {
                    $q->where('uploaded_by', $user->id)
                        ->orWhere('is_shared', true);
                })
                ->orderByDesc('updated_at')
                ->get()
                ->map(function (FileModel $file) {
                    $file->formatted_size = $file->formatted_size; // accessor
                    return $file;
                });
        });

        return response()->json(['success' => true, 'data' => $files]);
    }

    /**
     * Upload de arquivo
     */
    public function upload(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:20480', // 20MB
            'folder_id' => 'nullable|exists:folders,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Dados inválidos', 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $file = $request->file('file');
        $folderId = $request->input('folder_id');

        // Usar o caminho físico da pasta para upload
        $directory = $this->getFolderPhysicalPath($folderId);

        // Salvar com nome original (sem caracteres especiais)
        $originalName = $file->getClientOriginalName();
        $safeName = $this->sanitizeFileName($originalName);
        $storedPath = $file->storeAs($directory, $safeName);

        $model = FileModel::create([
            'name' => basename($storedPath),
            'original_name' => $file->getClientOriginalName(),
            'path' => $storedPath,
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => $file->getSize(),
            'folder_id' => $folderId,
            'uploaded_by' => $user->id,
            'is_shared' => false,
        ]);

        // Limpar cache de arquivos
        cache()->forget("files_user_{$user->id}_folder_" . ($folderId ?? 'null'));

        ActivityLog::createLog($user, 'upload_file', 'file', $model->id, 'Upload de arquivo', [
            'original_name' => $model->original_name,
            'path' => $model->path,
        ]);

        $model->formatted_size = $model->formatted_size;
        return response()->json(['success' => true, 'message' => 'Arquivo enviado com sucesso', 'data' => $model]);
    }

    /**
     * Construir o caminho físico da pasta baseado na hierarquia
     */
    private function buildPhysicalPath($parentId, $folderName): string
    {
        if (!$parentId) {
            return 'uploads/' . $folderName;
        }

        $parent = Folder::find($parentId);
        if (!$parent) {
            return 'uploads/' . $folderName;
        }

        return $parent->path . '/' . $folderName;
    }

    /**
     * Obter o caminho físico da pasta para upload de arquivos
     */
    private function getFolderPhysicalPath($folderId): string
    {
        if (!$folderId) {
            return 'uploads/root';
        }

        $folder = Folder::find($folderId);
        return $folder ? $folder->path : 'uploads/root';
    }

    /**
     * Atualizar caminhos das pastas filhas recursivamente
     */
    private function updateChildrenPaths($folder): void
    {
        foreach ($folder->children as $child) {
            $oldChildPath = $child->path;
            $newChildPath = $folder->path . '/' . $child->name;

            // Mover pasta física
            if (Storage::exists($oldChildPath)) {
                Storage::move($oldChildPath, $newChildPath);
            }

            // Atualizar no banco
            $child->path = $newChildPath;
            $child->save();

            // Atualizar recursivamente as pastas filhas
            $this->updateChildrenPaths($child);
        }
    }

    /**
     * Sanitizar nome do arquivo para evitar problemas
     */
    private function sanitizeFileName($filename): string
    {
        // Remover apenas caracteres realmente perigosos, manter acentos e espaços
        $filename = preg_replace('/[<>:"/\\|?*]/', '_', $filename);

        // Remover múltiplos underscores
        $filename = preg_replace('/_+/', '_', $filename);

        // Remover underscores do início e fim
        $filename = trim($filename, '_');

        // Se ficou vazio, usar nome padrão
        if (empty($filename)) {
            $filename = 'arquivo_' . time();
        }

        return $filename;
    }

    /**
     * Download de arquivo
     */
    public function download(Request $request, FileModel $file)
    {
        $user = $request->user();
        if (!$file->canView($user)) {
            return response()->json(['success' => false, 'message' => 'Sem permissão para baixar este arquivo'], 403);
        }

        if (!Storage::exists($file->path)) {
            return response()->json(['success' => false, 'message' => 'Arquivo não encontrado'], 404);
        }

        ActivityLog::createLog($user, 'download_file', 'file', $file->id, 'Download de arquivo');
        return Storage::download($file->path, $file->original_name);
    }

    /**
     * Visualizar arquivo (para imagens e documentos)
     */
    public function view(Request $request, FileModel $file)
    {
        $user = $request->user();
        if (!$file->canView($user)) {
            return response()->json(['success' => false, 'message' => 'Sem permissão para visualizar este arquivo'], 403);
        }

        if (!Storage::exists($file->path)) {
            return response()->json(['success' => false, 'message' => 'Arquivo não encontrado'], 404);
        }

        $fileContent = Storage::get($file->path);

        return response($fileContent)
            ->header('Content-Type', $file->mime_type)
            ->header('Content-Disposition', 'inline; filename="' . $file->original_name . '"')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    /** Atualizar (renomear/compartilhar toggle) */
    public function update(Request $request, FileModel $file): JsonResponse
    {
        $user = $request->user();
        if (!$file->canEdit($user)) {
            return response()->json(['success' => false, 'message' => 'Sem permissão'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'is_shared' => 'sometimes|boolean',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Dados inválidos', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        if (isset($data['name'])) {
            $file->original_name = $data['name'];
        }
        if (isset($data['is_shared'])) {
            $file->is_shared = $data['is_shared'];
        }
        $file->save();

        ActivityLog::createLog($user, 'update_file', 'file', $file->id, 'Atualização de arquivo', $data);
        $file->formatted_size = $file->formatted_size;
        return response()->json(['success' => true, 'message' => 'Arquivo atualizado', 'data' => $file]);
    }

    /** Excluir arquivo */
    public function delete(Request $request, FileModel $file): JsonResponse
    {
        $user = $request->user();
        if (!$file->canDelete($user)) {
            return response()->json(['success' => false, 'message' => 'Sem permissão'], 403);
        }

        Storage::delete($file->path);
        $file->delete();
        ActivityLog::createLog($user, 'delete_file', 'file', $file->id, 'Exclusão de arquivo');

        return response()->json(['success' => true, 'message' => 'Arquivo excluído']);
    }

    /** Listar pastas */
    public function getFolders(Request $request): JsonResponse
    {
        $user = $request->user();
        $parentId = $request->query('parent_id');

        // Cache key baseado no usuário e parent_id
        $cacheKey = "folders_user_{$user->id}_parent_" . ($parentId ?? 'null');

        $folders = cache()->remember($cacheKey, 60, function () use ($user, $parentId) {
            return Folder::with('creator')
                ->when($parentId, fn ($q) => $q->where('parent_id', $parentId))
                ->where(function ($q) use ($user) {
                    $q->where('created_by', $user->id)->orWhere('is_shared', true);
                })
                ->orderBy('name')
                ->get();
        });

        return response()->json(['success' => true, 'data' => $folders]);
    }

    /** Criar pasta */
    public function createFolder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:folders,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Dados inválidos', 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $data = $validator->validated();

        // Construir o caminho físico da pasta
        $parentId = $data['parent_id'] ?? null;
        $physicalPath = $this->buildPhysicalPath($parentId, $data['name']);

        // Criar a pasta física no storage
        if (!Storage::exists($physicalPath)) {
            Storage::makeDirectory($physicalPath);
        }

        $folder = Folder::create([
            'name' => $data['name'],
            'path' => $physicalPath,
            'parent_id' => $data['parent_id'] ?? null,
            'created_by' => $user->id,
        ]);

        ActivityLog::createLog($user, 'create_folder', 'folder', $folder->id, 'Criação de pasta', $data);
        return response()->json(['success' => true, 'message' => 'Pasta criada', 'data' => $folder]);
    }

    /** Atualizar pasta */
    public function updateFolder(Request $request, Folder $folder): JsonResponse
    {
        $user = $request->user();
        if (!$folder->canEdit($user)) {
            return response()->json(['success' => false, 'message' => 'Sem permissão'], 403);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Dados inválidos', 'errors' => $validator->errors()], 422);
        }

        $oldName = $folder->name;
        $newName = $validator->validated()['name'];
        $oldPath = $folder->path;

        // Construir novo caminho físico
        $newPath = $this->buildPhysicalPath($folder->parent_id, $newName);

        // Renomear a pasta física no storage
        if (Storage::exists($oldPath)) {
            Storage::move($oldPath, $newPath);
        }

        // Atualizar no banco de dados
        $folder->name = $newName;
        $folder->path = $newPath;
        $folder->save();

        // Atualizar caminhos das pastas filhas
        $this->updateChildrenPaths($folder);

        ActivityLog::createLog($user, 'rename_folder', 'folder', $folder->id, 'Renomeou pasta', ['from' => $oldName, 'to' => $newName]);
        return response()->json(['success' => true, 'message' => 'Pasta atualizada', 'data' => $folder]);
    }

    /** Excluir pasta */
    public function deleteFolder(Request $request, Folder $folder): JsonResponse
    {
        $user = $request->user();
        if (!$folder->canDelete($user)) {
            return response()->json(['success' => false, 'message' => 'Sem permissão'], 403);
        }

        // Remover arquivos físicos da pasta
        foreach ($folder->files as $file) {
            Storage::delete($file->path);
        }

        // Remover a pasta física do storage
        if (Storage::exists($folder->path)) {
            Storage::deleteDirectory($folder->path);
        }

        $folder->delete();
        ActivityLog::createLog($user, 'delete_folder', 'folder', $folder->id, 'Exclusão de pasta');
        return response()->json(['success' => true, 'message' => 'Pasta excluída']);
    }

    /** Compartilhar pasta (toggle) */
    public function shareFolder(Request $request, Folder $folder): JsonResponse
    {
        $user = $request->user();
        if (!$folder->canEdit($user)) {
            return response()->json(['success' => false, 'message' => 'Sem permissão'], 403);
        }
        $folder->is_shared = !$folder->is_shared;
        $folder->save();
        ActivityLog::createLog($user, 'share_folder', 'folder', $folder->id, 'Atualizou compartilhamento', ['is_shared' => $folder->is_shared]);
        return response()->json(['success' => true, 'message' => 'Compartilhamento atualizado', 'data' => $folder]);
    }

    /** Listar itens da lixeira */
    public function getTrash(Request $request): JsonResponse
    {
        $user = $request->user();

        // Buscar arquivos deletados (soft deletes desabilitado)
        $deletedFiles = collect([]); // Arquivos deletados não estão sendo rastreados

        // Buscar pastas deletadas (soft deletes desabilitado)
        $deletedFolders = collect([]); // Pastas deletadas não estão sendo rastreados

        $trashItems = $deletedFiles->concat($deletedFolders)
            ->values();

        return response()->json(['success' => true, 'data' => $trashItems]);
    }

    /** Restaurar arquivo */
    public function restoreFile(Request $request, $fileId): JsonResponse
    {
        $user = $request->user();
        $file = FileModel::onlyTrashed()->where('id', $fileId)->where('uploaded_by', $user->id)->first();

        if (!$file) {
            return response()->json(['success' => false, 'message' => 'Arquivo não encontrado'], 404);
        }

        $file->restore();
        ActivityLog::createLog($user, 'restore_file', 'file', $file->id, 'Arquivo restaurado da lixeira');

        return response()->json(['success' => true, 'message' => 'Arquivo restaurado']);
    }

    /** Restaurar pasta */
    public function restoreFolder(Request $request, $folderId): JsonResponse
    {
        $user = $request->user();
        $folder = Folder::onlyTrashed()->where('id', $folderId)->where('created_by', $user->id)->first();

        if (!$folder) {
            return response()->json(['success' => false, 'message' => 'Pasta não encontrada'], 404);
        }

        $folder->restore();
        ActivityLog::createLog($user, 'restore_folder', 'folder', $folder->id, 'Pasta restaurada da lixeira');

        return response()->json(['success' => true, 'message' => 'Pasta restaurada']);
    }

    /** Exclusão permanente de arquivo */
    public function permanentDeleteFile(Request $request, $fileId): JsonResponse
    {
        $user = $request->user();
        $file = FileModel::onlyTrashed()->where('id', $fileId)->where('uploaded_by', $user->id)->first();

        if (!$file) {
            return response()->json(['success' => false, 'message' => 'Arquivo não encontrado'], 404);
        }

        // Deletar arquivo físico
        if (Storage::exists($file->path)) {
            Storage::delete($file->path);
        }

        $file->forceDelete();
        ActivityLog::createLog($user, 'permanent_delete_file', 'file', $fileId, 'Arquivo excluído permanentemente');

        return response()->json(['success' => true, 'message' => 'Arquivo excluído permanentemente']);
    }

    /** Exclusão permanente de pasta */
    public function permanentDeleteFolder(Request $request, $folderId): JsonResponse
    {
        $user = $request->user();
        $folder = Folder::onlyTrashed()->where('id', $folderId)->where('created_by', $user->id)->first();

        if (!$folder) {
            return response()->json(['success' => false, 'message' => 'Pasta não encontrada'], 404);
        }

        $folder->forceDelete();
        ActivityLog::createLog($user, 'permanent_delete_folder', 'folder', $folderId, 'Pasta excluída permanentemente');

        return response()->json(['success' => true, 'message' => 'Pasta excluída permanentemente']);
    }
}
