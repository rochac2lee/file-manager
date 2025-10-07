<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\User;
use App\Models\File;
use App\Models\Folder;

class PermissionPageController extends Controller
{
    /**
     * Exibir página de permissões
     */
    public function index(): Response
    {
        $user = auth()->user();
        
        // Buscar arquivos e pastas do usuário
        $files = File::where('uploaded_by', $user->id)
            ->with('uploader')
            ->orderBy('created_at', 'desc')
            ->get();

        $folders = Folder::where('created_by', $user->id)
            ->with('creator')
            ->orderBy('created_at', 'desc')
            ->get();

        // Buscar todos os usuários para seleção
        $users = User::select('id', 'name', 'email')
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->get();

        return Inertia::render('Permissions/Index', [
            'files' => $files,
            'folders' => $folders,
            'users' => $users,
        ]);
    }
}
