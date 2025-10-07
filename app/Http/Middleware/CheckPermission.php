<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;
use App\Models\File;
use App\Models\Folder;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return redirect('/login');
        }

        // Administradores têm acesso total
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Verificar permissão específica
        if (!$this->hasPermission($user, $permission, $request)) {
            abort(403, 'Acesso negado. Você não tem permissão para realizar esta ação.');
        }

        return $next($request);
    }

    /**
     * Verificar se o usuário tem a permissão necessária
     */
    private function hasPermission(User $user, string $permission, Request $request): bool
    {
        switch ($permission) {
            case 'view_file':
                return $this->canViewFile($user, $request);
            case 'edit_file':
                return $this->canEditFile($user, $request);
            case 'delete_file':
                return $this->canDeleteFile($user, $request);
            case 'view_folder':
                return $this->canViewFolder($user, $request);
            case 'edit_folder':
                return $this->canEditFolder($user, $request);
            case 'delete_folder':
                return $this->canDeleteFolder($user, $request);
            case 'manage_users':
                return $user->isAdmin() || $user->isManager();
            default:
                return false;
        }
    }

    /**
     * Verificar se pode visualizar arquivo
     */
    private function canViewFile(User $user, Request $request): bool
    {
        $fileId = $request->route('file');
        if (!$fileId) return false;

        $file = File::find($fileId);
        if (!$file) return false;

        // Dono do arquivo
        if ($file->uploaded_by === $user->id) return true;

        // Arquivo compartilhado
        if ($file->is_shared) return true;

        // Verificar permissão específica
        $permission = $file->permissions()->where('user_id', $user->id)->first();
        return $permission && $permission->can_view;
    }

    /**
     * Verificar se pode editar arquivo
     */
    private function canEditFile(User $user, Request $request): bool
    {
        $fileId = $request->route('file');
        if (!$fileId) return false;

        $file = File::find($fileId);
        if (!$file) return false;

        // Dono do arquivo
        if ($file->uploaded_by === $user->id) return true;

        // Verificar permissão específica
        $permission = $file->permissions()->where('user_id', $user->id)->first();
        return $permission && $permission->can_edit;
    }

    /**
     * Verificar se pode excluir arquivo
     */
    private function canDeleteFile(User $user, Request $request): bool
    {
        $fileId = $request->route('file');
        if (!$fileId) return false;

        $file = File::find($fileId);
        if (!$file) return false;

        // Dono do arquivo
        if ($file->uploaded_by === $user->id) return true;

        // Verificar permissão específica
        $permission = $file->permissions()->where('user_id', $user->id)->first();
        return $permission && $permission->can_delete;
    }

    /**
     * Verificar se pode visualizar pasta
     */
    private function canViewFolder(User $user, Request $request): bool
    {
        $folderId = $request->route('folder');
        if (!$folderId) return false;

        $folder = Folder::find($folderId);
        if (!$folder) return false;

        // Criador da pasta
        if ($folder->created_by === $user->id) return true;

        // Pasta compartilhada
        if ($folder->is_shared) return true;

        // Verificar permissão específica
        $permission = $folder->permissions()->where('user_id', $user->id)->first();
        return $permission && $permission->can_view;
    }

    /**
     * Verificar se pode editar pasta
     */
    private function canEditFolder(User $user, Request $request): bool
    {
        $folderId = $request->route('folder');
        if (!$folderId) return false;

        $folder = Folder::find($folderId);
        if (!$folder) return false;

        // Criador da pasta
        if ($folder->created_by === $user->id) return true;

        // Verificar permissão específica
        $permission = $folder->permissions()->where('user_id', $user->id)->first();
        return $permission && $permission->can_edit;
    }

    /**
     * Verificar se pode excluir pasta
     */
    private function canDeleteFolder(User $user, Request $request): bool
    {
        $folderId = $request->route('folder');
        if (!$folderId) return false;

        $folder = Folder::find($folderId);
        if (!$folder) return false;

        // Criador da pasta
        if ($folder->created_by === $user->id) return true;

        // Verificar permissão específica
        $permission = $folder->permissions()->where('user_id', $user->id)->first();
        return $permission && $permission->can_delete;
    }
}
