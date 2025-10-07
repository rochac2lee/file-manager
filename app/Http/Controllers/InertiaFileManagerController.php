<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\File as FileModel;
use App\Models\Folder;
use App\Models\ActivityLog;

class InertiaFileManagerController extends Controller
{
    /**
     * Exibir o gerenciador de arquivos
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $folderId = $request->query('folder_id');

        // Buscar dados
        $data = $this->getFolderData($user, $folderId);

        return Inertia::render('FileManager', [
            'folders' => $data['folders'],
            'files' => $data['files'],
            'currentFolderId' => $folderId,
            'currentPath' => $data['currentPath'],
            'viewMode' => $request->query('view', 'list'),
            'searchQuery' => $request->query('search', ''),
            'loading' => false,
        ]);
    }

    /**
     * Navegar para uma pasta
     */
    public function navigate(Request $request): Response
    {
        $user = $request->user();
        $folderId = $request->input('folder_id');

        // Buscar dados
        $data = $this->getFolderData($user, $folderId);

        return Inertia::render('FileManager', [
            'folders' => $data['folders'],
            'files' => $data['files'],
            'currentFolderId' => $folderId,
            'currentPath' => $data['currentPath'],
            'viewMode' => $request->query('view', 'list'),
            'searchQuery' => $request->query('search', ''),
            'loading' => false,
        ]);
    }

    /**
     * Criar uma nova pasta
     */
    public function createFolder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:folders,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->with('error', 'Dados inválidos');
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
            'parent_id' => $parentId,
            'created_by' => $user->id,
        ]);

        ActivityLog::createLog($user, 'create_folder', 'folder', $folder->id, 'Criação de pasta', $data);

        // Limpar cache
        $this->clearCache($user, $parentId);

        return back()->with('success', 'Pasta criada com sucesso');
    }

    /**
     * Upload de arquivo
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:20480', // 20MB
            'folder_id' => 'nullable|exists:folders,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->with('error', 'Dados inválidos');
        }

        $user = $request->user();
        $file = $request->file('file');
        $folderId = $request->input('folder_id');

        // Usar o caminho físico da pasta para upload
        $directory = $this->getFolderPhysicalPath($folderId);

        // Salvar com nome original (sem caracteres especiais)
        $originalName = $file->getClientOriginalName();
        $safeName = $this->sanitizeFileName($originalName);
        $storedPath = $file->storeAs($directory, $safeName, 'public');

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

        ActivityLog::createLog($user, 'upload_file', 'file', $model->id, 'Upload de arquivo', [
            'original_name' => $model->original_name,
            'path' => $model->path,
        ]);

        // Limpar cache
        $this->clearCache($user, $folderId);

        return back()->with('success', 'Arquivo enviado com sucesso');
    }

    /**
     * Renomear arquivo
     */
    public function renameFile(Request $request, FileModel $file)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->with('error', 'Dados inválidos');
        }

        $user = $request->user();
        if (!$file->canEdit($user)) {
            return back()->with('error', 'Sem permissão');
        }

        $oldName = $file->original_name;
        $file->original_name = $request->input('name');
        $file->save();

        ActivityLog::createLog($user, 'rename_file', 'file', $file->id, 'Renomeou arquivo', [
            'from' => $oldName,
            'to' => $file->original_name,
        ]);

        // Limpar cache
        $this->clearCache($user, $file->folder_id);

        return back()->with('success', 'Arquivo renomeado com sucesso');
    }

    /**
     * Renomear pasta
     */
    public function renameFolder(Request $request, Folder $folder)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->with('error', 'Dados inválidos');
        }

        $user = $request->user();
        if (!$folder->canEdit($user)) {
            return back()->with('error', 'Sem permissão');
        }

        $oldName = $folder->name;
        $newName = $request->input('name');
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

        ActivityLog::createLog($user, 'rename_folder', 'folder', $folder->id, 'Renomeou pasta', [
            'from' => $oldName,
            'to' => $newName,
        ]);

        // Limpar cache
        $this->clearCache($user, $folder->parent_id);

        return back()->with('success', 'Pasta renomeada com sucesso');
    }

    /**
     * Excluir arquivo
     */
    public function deleteFile(Request $request, FileModel $file)
    {
        $user = $request->user();
        if (!$file->canDelete($user)) {
            return back()->with('error', 'Sem permissão');
        }

        Storage::delete($file->path);
        $folderId = $file->folder_id;
        $file->delete();

        ActivityLog::createLog($user, 'delete_file', 'file', $file->id, 'Exclusão de arquivo');

        // Limpar cache
        $this->clearCache($user, $folderId);

        return back()->with('success', 'Arquivo excluído com sucesso');
    }

    /**
     * Excluir pasta
     */
    public function deleteFolder(Request $request, Folder $folder)
    {
        $user = $request->user();
        if (!$folder->canDelete($user)) {
            return back()->with('error', 'Sem permissão');
        }

        // Remover arquivos físicos da pasta
        foreach ($folder->files as $file) {
            Storage::delete($file->path);
        }

        // Remover a pasta física do storage
        if (Storage::exists($folder->path)) {
            Storage::deleteDirectory($folder->path);
        }

        $parentId = $folder->parent_id;
        $folder->delete();

        ActivityLog::createLog($user, 'delete_folder', 'folder', $folder->id, 'Exclusão de pasta');

        // Limpar cache
        $this->clearCache($user, $parentId);

        return back()->with('success', 'Pasta excluída com sucesso');
    }

    /**
     * Download de arquivo
     */
    public function download(Request $request, FileModel $file)
    {
        $user = $request->user();
        if (!$file->canView($user)) {
            return back()->with('error', 'Sem permissão para baixar este arquivo');
        }

        if (!Storage::exists($file->path)) {
            return back()->with('error', 'Arquivo não encontrado');
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
            return back()->with('error', 'Sem permissão para visualizar este arquivo');
        }

        if (!Storage::exists($file->path)) {
            return back()->with('error', 'Arquivo não encontrado');
        }

        $fileContent = Storage::get($file->path);

        return response($fileContent)
            ->header('Content-Type', $file->mime_type)
            ->header('Content-Disposition', 'inline; filename="' . $file->original_name . '"')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    /**
     * Obter dados da pasta
     */
    private function getFolderData($user, $folderId = null): array
    {
        // Cache key baseado no usuário e folder_id
        $cacheKey = "inertia_data_user_{$user->id}_folder_" . ($folderId ?? 'null');
        
        // Limpar cache específico para forçar regeneração
        Cache::forget($cacheKey);

        return Cache::remember($cacheKey, 60, function () use ($user, $folderId) {
            // Buscar pastas
            $folders = Folder::with('creator')
                ->when($folderId, fn ($q) => $q->where('parent_id', $folderId))
                ->where(function ($q) use ($user) {
                    $q->where('created_by', $user->id)->orWhere('is_shared', true);
                })
                ->orderBy('name')
                ->get()
                ->map(function ($folder) {
                    return [
                        'id' => $folder->id,
                        'name' => $folder->name,
                        'path' => $folder->path,
                        'parent_id' => $folder->parent_id,
                        'created_by' => $folder->created_by,
                        'is_shared' => $folder->is_shared,
                        'created_at' => $folder->created_at,
                        'updated_at' => $folder->updated_at,
                    ];
                });

            // Buscar arquivos
            $files = FileModel::with('uploader')
                ->when($folderId, fn ($q) => $q->where('folder_id', $folderId))
                ->where(function ($q) use ($user) {
                    $q->where('uploaded_by', $user->id)
                        ->orWhere('is_shared', true);
                })
                ->orderByDesc('updated_at')
                ->get()
                ->map(function ($file) {
                    $fileUrl = url('/storage/' . rawurlencode($file->path));
                    \Log::info('Gerando URL para arquivo:', [
                        'id' => $file->id,
                        'name' => $file->name,
                        'path' => $file->path,
                        'url' => $fileUrl
                    ]);
                    
                    $fileData = [
                        'id' => $file->id,
                        'name' => $file->name,
                        'original_name' => $file->original_name,
                        'path' => $file->path,
                        'url' => $fileUrl,
                        'mime_type' => $file->mime_type,
                        'size' => $file->size,
                        'formatted_size' => $file->formatted_size,
                        'folder_id' => $file->folder_id,
                        'uploaded_by' => $file->uploaded_by,
                        'is_shared' => $file->is_shared,
                        'created_at' => $file->created_at,
                        'updated_at' => $file->updated_at,
                    ];
                    
                    \Log::info('Dados do arquivo sendo enviados:', $fileData);
                    
                    return $fileData;
                });

            // Construir caminho atual
            $currentPath = [];
            if ($folderId) {
                $currentPath = $this->buildCurrentPath($folderId);
            }

            return [
                'folders' => $folders,
                'files' => $files,
                'currentPath' => $currentPath,
            ];
        });
    }

    /**
     * Construir caminho atual baseado na hierarquia
     */
    private function buildCurrentPath($folderOrId): array
    {
        $path = [];
        
        // Se for um ID, buscar a pasta
        if (is_numeric($folderOrId)) {
            $folder = Folder::find($folderOrId);
        } else {
            // Se for um objeto, usar diretamente
            $folder = $folderOrId;
        }
        
        // Se não encontrou a pasta, retornar array vazio
        if (!$folder) {
            return [];
        }

        while ($folder) {
            array_unshift($path, [
                'id' => $folder->id,
                'name' => $folder->name,
            ]);
            $folder = $folder->parent;
        }

        return $path;
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
     * Mostrar pasta por caminho
     */
    public function showFolder(Request $request, string $path)
    {
        $user = $request->user();
        
        // Encontrar a pasta pelo caminho
        $folder = $this->findFolderByPath($path);
        
        if (!$folder) {
            abort(404, 'Pasta não encontrada');
        }
        
        // Verificar permissões
        if (!$this->canAccessFolder($user, $folder)) {
            abort(403, 'Acesso negado');
        }
        
        // Obter dados da pasta
        $folderData = $this->getFolderData($user, $folder->id);
        
        return Inertia::render('FileManager', [
            'folders' => $folderData['folders'],
            'files' => $folderData['files'],
            'currentFolderId' => $folder->id,
            'currentPath' => $this->buildCurrentPath($folder),
            'currentFolderPath' => $path,
            'breadcrumbs' => $this->buildBreadcrumbs($folder),
            'storageUsage' => $this->getStorageUsage($user),
        ]);
    }
    
    /**
     * Encontrar pasta pelo caminho
     */
    private function findFolderByPath(string $path): ?Folder
    {
        if ($path === '/' || empty($path)) {
            return null; // Pasta raiz
        }
        
        // Dividir o caminho em partes
        $pathParts = explode('/', trim($path, '/'));
        
        $currentFolder = null;
        foreach ($pathParts as $folderName) {
            $folder = Folder::where('name', $folderName)
                ->where('parent_id', $currentFolder?->id)
                ->first();
                
            if (!$folder) {
                return null;
            }
            
            $currentFolder = $folder;
        }
        
        return $currentFolder;
    }
    
    /**
     * Verificar se usuário pode acessar a pasta
     */
    private function canAccessFolder($user, $folder): bool
    {
        // Se a pasta não tem pai, é a raiz
        if (!$folder) {
            return true;
        }
        
        // Verificar permissões (implementar lógica de permissões aqui)
        return true; // Por enquanto, permitir acesso a todos
    }
    
    /**
     * Construir breadcrumbs para a pasta
     */
    private function buildBreadcrumbs($folder): array
    {
        $breadcrumbs = [
            [
                'title' => 'Início',
                'value' => null,
                'disabled' => false,
            ]
        ];
        
        if ($folder) {
            $path = [];
            $current = $folder;
            
            // Construir caminho da pasta atual até a raiz
            while ($current) {
                array_unshift($path, $current);
                $current = $current->parent;
            }
            
            // Adicionar cada pasta ao breadcrumb
            foreach ($path as $folder) {
                $breadcrumbs[] = [
                    'title' => $folder->name,
                    'value' => $folder->id,
                    'disabled' => false,
                ];
            }
        }
        
        return $breadcrumbs;
    }
    

    /**
     * Obter uso de storage do usuário
     */
    private function getStorageUsage($user): array
    {
        // Calcular tamanho total dos arquivos do usuário
        $totalSize = \App\Models\File::where('uploaded_by', $user->id)->sum('size');
        
        // Converter para MB
        $usedMB = round($totalSize / (1024 * 1024), 2);
        
        // Limite padrão de 1GB (1000MB)
        $limitMB = 1000;
        
        // Calcular percentual de uso
        $usagePercentage = $limitMB > 0 ? round(($usedMB / $limitMB) * 100, 2) : 0;
        
        return [
            'used' => $usedMB,
            'limit' => $limitMB,
            'percentage' => $usagePercentage,
            'used_formatted' => $this->formatBytes($totalSize),
            'limit_formatted' => $this->formatBytes($limitMB * 1024 * 1024),
        ];
    }
    
    /**
     * Formatar bytes em formato legível
     */
    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Limpar cache
     */
    private function clearCache($user, $folderId = null): void
    {
        $cacheKey = "inertia_data_user_{$user->id}_folder_" . ($folderId ?? 'null');
        Cache::forget($cacheKey);
    }
}
