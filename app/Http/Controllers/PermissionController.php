<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\File;
use App\Models\Folder;
use App\Models\FilePermission;
use App\Models\FolderPermission;
use App\Models\User;
use App\Models\ActivityLog;

class PermissionController extends Controller
{
    /**
     * Definir permissões para um arquivo
     */
    public function setFilePermissions(Request $request, File $file)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'can_view' => 'boolean',
            'can_edit' => 'boolean',
            'can_delete' => 'boolean',
            'can_rename' => 'boolean',
        ]);

        $user = auth()->user();

        // Verificar se o usuário tem permissão para gerenciar este arquivo
        if (!$file->canEdit($user) && !$user->isAdmin()) {
            abort(403, 'Você não tem permissão para gerenciar este arquivo.');
        }

        $permission = FilePermission::updateOrCreate(
            [
                'file_id' => $file->id,
                'user_id' => $request->user_id,
            ],
            [
                'can_view' => $request->boolean('can_view', false),
                'can_edit' => $request->boolean('can_edit', false),
                'can_delete' => $request->boolean('can_delete', false),
                'can_rename' => $request->boolean('can_rename', false),
            ]
        );

        $targetUser = User::find($request->user_id);

        ActivityLog::createLog(
            $user,
            'set_file_permissions',
            'file',
            $file->id,
            "Definiu permissões do arquivo '{$file->name}' para usuário '{$targetUser->name}'",
            [
                'target_user_id' => $targetUser->id,
                'permissions' => $permission->toArray()
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Permissões definidas com sucesso!'
        ]);
    }

    /**
     * Definir permissões para uma pasta
     */
    public function setFolderPermissions(Request $request, Folder $folder)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'can_view' => 'boolean',
            'can_edit' => 'boolean',
            'can_delete' => 'boolean',
            'can_rename' => 'boolean',
        ]);

        $user = auth()->user();

        // Verificar se o usuário tem permissão para gerenciar esta pasta
        if (!$folder->canEdit($user) && !$user->isAdmin()) {
            abort(403, 'Você não tem permissão para gerenciar esta pasta.');
        }

        $permission = FolderPermission::updateOrCreate(
            [
                'folder_id' => $folder->id,
                'user_id' => $request->user_id,
            ],
            [
                'can_view' => $request->boolean('can_view', false),
                'can_edit' => $request->boolean('can_edit', false),
                'can_delete' => $request->boolean('can_delete', false),
                'can_rename' => $request->boolean('can_rename', false),
            ]
        );

        $targetUser = User::find($request->user_id);

        ActivityLog::createLog(
            $user,
            'set_folder_permissions',
            'folder',
            $folder->id,
            "Definiu permissões da pasta '{$folder->name}' para usuário '{$targetUser->name}'",
            [
                'target_user_id' => $targetUser->id,
                'permissions' => $permission->toArray()
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Permissões definidas com sucesso!'
        ]);
    }

    /**
     * Remover permissões de um arquivo
     */
    public function removeFilePermissions(Request $request, File $file)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = auth()->user();

        // Verificar se o usuário tem permissão para gerenciar este arquivo
        if (!$file->canEdit($user) && !$user->isAdmin()) {
            abort(403, 'Você não tem permissão para gerenciar este arquivo.');
        }

        $permission = FilePermission::where('file_id', $file->id)
            ->where('user_id', $request->user_id)
            ->first();

        if ($permission) {
            $targetUser = User::find($request->user_id);
            
            ActivityLog::createLog(
                $user,
                'remove_file_permissions',
                'file',
                $file->id,
                "Removeu permissões do arquivo '{$file->name}' do usuário '{$targetUser->name}'"
            );

            $permission->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Permissões removidas com sucesso!'
        ]);
    }

    /**
     * Remover permissões de uma pasta
     */
    public function removeFolderPermissions(Request $request, Folder $folder)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = auth()->user();

        // Verificar se o usuário tem permissão para gerenciar esta pasta
        if (!$folder->canEdit($user) && !$user->isAdmin()) {
            abort(403, 'Você não tem permissão para gerenciar esta pasta.');
        }

        $permission = FolderPermission::where('folder_id', $folder->id)
            ->where('user_id', $request->user_id)
            ->first();

        if ($permission) {
            $targetUser = User::find($request->user_id);
            
            ActivityLog::createLog(
                $user,
                'remove_folder_permissions',
                'folder',
                $folder->id,
                "Removeu permissões da pasta '{$folder->name}' do usuário '{$targetUser->name}'"
            );

            $permission->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Permissões removidas com sucesso!'
        ]);
    }

    /**
     * Obter permissões de um arquivo
     */
    public function getFilePermissions(File $file)
    {
        $user = auth()->user();

        // Verificar se o usuário tem permissão para visualizar este arquivo
        if (!$file->canView($user) && !$user->isAdmin()) {
            abort(403, 'Você não tem permissão para visualizar este arquivo.');
        }

        $permissions = FilePermission::with('user')
            ->where('file_id', $file->id)
            ->get();

        return response()->json($permissions);
    }

    /**
     * Obter permissões de uma pasta
     */
    public function getFolderPermissions(Folder $folder)
    {
        $user = auth()->user();

        // Verificar se o usuário tem permissão para visualizar esta pasta
        if (!$folder->canView($user) && !$user->isAdmin()) {
            abort(403, 'Você não tem permissão para visualizar esta pasta.');
        }

        $permissions = FolderPermission::with('user')
            ->where('folder_id', $folder->id)
            ->get();

        return response()->json($permissions);
    }
}
