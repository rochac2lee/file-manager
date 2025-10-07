<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InertiaFileManagerController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FileManagerController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PermissionPageController;
use App\Http\Controllers\TrashController;

// Rotas de autenticação
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware(['auth:web', 'inertia'])->group(function () {
    // Rotas principais do gerenciador de arquivos
    Route::get('/', [InertiaFileManagerController::class, 'index'])->name('home');
    Route::get('/files', [InertiaFileManagerController::class, 'index'])->name('files.index');
    Route::post('/files/navigate', [InertiaFileManagerController::class, 'navigate'])->name('files.navigate');
    
    // API para navegação instantânea
    Route::get('/api/folders/{folderId}/contents', [InertiaFileManagerController::class, 'getFolderContents'])->name('api.folders.contents');
    
    // Operações de arquivos
    Route::post('/files/upload', [InertiaFileManagerController::class, 'upload'])->name('files.upload');
    Route::patch('/files/{file}/rename', [InertiaFileManagerController::class, 'renameFile'])->name('files.rename');
    Route::delete('/files/{file}', [InertiaFileManagerController::class, 'deleteFile'])->name('files.delete');
    Route::get('/files/{file}/download', [InertiaFileManagerController::class, 'download'])->name('files.download');
    Route::get('/files/{file}/view', [InertiaFileManagerController::class, 'view'])->name('files.view');
    
    // Operações de pastas
    Route::post('/folders', [InertiaFileManagerController::class, 'createFolder'])->name('folders.create');
    Route::patch('/folders/{folder}/rename', [InertiaFileManagerController::class, 'renameFolder'])->name('folders.rename');
    Route::delete('/folders/{folder}', [InertiaFileManagerController::class, 'deleteFolder'])->name('folders.delete');
    
    // Gerenciamento de usuários
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    
    // Logs de atividade
    Route::get('/activity-logs', [UserController::class, 'getActivityLogs'])->name('activity-logs.index');
    
    // Página de permissões
    Route::get('/permissions', [PermissionPageController::class, 'index'])->name('permissions.index');
    
    // Lixeira
    Route::get('/trash', [TrashController::class, 'index'])->name('trash.index');
    Route::post('/trash/files/{file}/restore', [TrashController::class, 'restoreFile'])->name('trash.files.restore');
    Route::delete('/trash/files/{file}', [TrashController::class, 'destroyFile'])->name('trash.files.destroy');
    Route::post('/trash/folders/{folder}/restore', [TrashController::class, 'restoreFolder'])->name('trash.folders.restore');
    Route::delete('/trash/folders/{folder}', [TrashController::class, 'destroyFolder'])->name('trash.folders.destroy');
    Route::delete('/trash/empty', [TrashController::class, 'empty'])->name('trash.empty');
    
    // Sistema de permissões
    Route::prefix('permissions')->group(function () {
        // Permissões de arquivos
        Route::post('/files/{file}', [PermissionController::class, 'setFilePermissions'])->name('permissions.files.set');
        Route::delete('/files/{file}', [PermissionController::class, 'removeFilePermissions'])->name('permissions.files.remove');
        Route::get('/files/{file}', [PermissionController::class, 'getFilePermissions'])->name('permissions.files.get');
        
        // Permissões de pastas
        Route::post('/folders/{folder}', [PermissionController::class, 'setFolderPermissions'])->name('permissions.folders.set');
        Route::delete('/folders/{folder}', [PermissionController::class, 'removeFolderPermissions'])->name('permissions.folders.remove');
        Route::get('/folders/{folder}', [PermissionController::class, 'getFolderPermissions'])->name('permissions.folders.get');
    });
    
    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // Rota para pastas (deve ficar por último para não conflitar com outras rotas)
    // Só captura se não for uma rota que comece com /files, /users, /permissions, etc.
    Route::get('/{path}', [InertiaFileManagerController::class, 'showFolder'])
        ->where('path', '^(?!files|users|permissions|activity-logs|trash|folders).*')
        ->name('folder.show');
});
