<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\File;
use App\Models\Folder;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Storage;

class TrashController extends Controller
{
    /**
     * Exibir lixeira
     */
    public function index(): Response
    {
        $user = auth()->user();
        
        // Buscar arquivos e pastas excluídos (soft deleted)
        $files = File::onlyTrashed()
            ->where('uploaded_by', $user->id)
            ->with('uploader')
            ->orderBy('deleted_at', 'desc')
            ->get();

        $folders = Folder::onlyTrashed()
            ->where('created_by', $user->id)
            ->with('creator')
            ->orderBy('deleted_at', 'desc')
            ->get();

        return Inertia::render('Trash/Index', [
            'files' => $files,
            'folders' => $folders,
        ]);
    }

    /**
     * Restaurar arquivo
     */
    public function restoreFile(File $file)
    {
        $user = auth()->user();

        // Verificar se o usuário tem permissão para restaurar este arquivo
        if ($file->uploaded_by !== $user->id && !$user->isAdmin()) {
            abort(403, 'Você não tem permissão para restaurar este arquivo.');
        }

        $file->restore();

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'restore',
            'model_type' => 'File',
            'model_id' => $file->id,
            'description' => "Arquivo '{$file->name}' foi restaurado da lixeira"
        ]);

        return redirect()->back()->with('success', 'Arquivo restaurado com sucesso!');
    }

    /**
     * Excluir arquivo permanentemente
     */
    public function destroyFile(File $file)
    {
        $user = auth()->user();

        // Verificar se o usuário tem permissão para excluir este arquivo
        if ($file->uploaded_by !== $user->id && !$user->isAdmin()) {
            abort(403, 'Você não tem permissão para excluir este arquivo.');
        }

        // Remover arquivo físico do storage
        if (Storage::exists($file->path)) {
            Storage::delete($file->path);
        }

        $fileName = $file->name;
        $file->forceDelete();

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'permanent_delete',
            'model_type' => 'File',
            'model_id' => $file->id,
            'description' => "Arquivo '{$fileName}' foi excluído permanentemente"
        ]);

        return redirect()->back()->with('success', 'Arquivo excluído permanentemente!');
    }

    /**
     * Restaurar pasta
     */
    public function restoreFolder(Folder $folder)
    {
        $user = auth()->user();

        // Verificar se o usuário tem permissão para restaurar esta pasta
        if ($folder->created_by !== $user->id && !$user->isAdmin()) {
            abort(403, 'Você não tem permissão para restaurar esta pasta.');
        }

        $folder->restore();

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'restore',
            'model_type' => 'Folder',
            'model_id' => $folder->id,
            'description' => "Pasta '{$folder->name}' foi restaurada da lixeira"
        ]);

        return redirect()->back()->with('success', 'Pasta restaurada com sucesso!');
    }

    /**
     * Excluir pasta permanentemente
     */
    public function destroyFolder(Folder $folder)
    {
        $user = auth()->user();

        // Verificar se o usuário tem permissão para excluir esta pasta
        if ($folder->created_by !== $user->id && !$user->isAdmin()) {
            abort(403, 'Você não tem permissão para excluir esta pasta.');
        }

        $folderName = $folder->name;
        $folder->forceDelete();

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'permanent_delete',
            'model_type' => 'Folder',
            'model_id' => $folder->id,
            'description' => "Pasta '{$folderName}' foi excluída permanentemente"
        ]);

        return redirect()->back()->with('success', 'Pasta excluída permanentemente!');
    }

    /**
     * Esvaziar lixeira
     */
    public function empty()
    {
        $user = auth()->user();

        $deletedFiles = File::onlyTrashed()
            ->where('uploaded_by', $user->id)
            ->get();

        $deletedFolders = Folder::onlyTrashed()
            ->where('created_by', $user->id)
            ->get();

        // Remover arquivos físicos
        foreach ($deletedFiles as $file) {
            if (Storage::exists($file->path)) {
                Storage::delete($file->path);
            }
            $file->forceDelete();
        }

        // Remover pastas
        foreach ($deletedFolders as $folder) {
            $folder->forceDelete();
        }

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'empty_trash',
            'model_type' => 'Trash',
            'model_id' => null,
            'description' => 'Lixeira foi esvaziada'
        ]);

        return redirect()->back()->with('success', 'Lixeira esvaziada com sucesso!');
    }
}
