<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FileManagerController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Rotas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rotas protegidas
Route::middleware('auth:sanctum')->group(function () {
    // Autenticação
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);

    // Gerenciamento de arquivos
    Route::prefix('files')->group(function () {
        Route::get('/', [FileManagerController::class, 'index']);
        Route::post('/upload', [FileManagerController::class, 'upload']);
        Route::get('/{file}/download', [FileManagerController::class, 'download']);
        Route::get('/{file}/view', [FileManagerController::class, 'view']);
        Route::put('/{file}', [FileManagerController::class, 'update']);
        Route::delete('/{file}', [FileManagerController::class, 'delete']);
    });

    // Gerenciamento de pastas
    Route::prefix('folders')->group(function () {
        Route::get('/', [FileManagerController::class, 'getFolders']);
        Route::post('/', [FileManagerController::class, 'createFolder']);
        Route::put('/{folder}', [FileManagerController::class, 'updateFolder']);
        Route::delete('/{folder}', [FileManagerController::class, 'deleteFolder']);
        Route::post('/{folder}/share', [FileManagerController::class, 'shareFolder']);
    });

    // Lixeira
    Route::prefix('trash')->group(function () {
        Route::get('/', [FileManagerController::class, 'getTrash']);
        Route::post('/files/{file}/restore', [FileManagerController::class, 'restoreFile']);
        Route::post('/folders/{folder}/restore', [FileManagerController::class, 'restoreFolder']);
        Route::delete('/files/{file}', [FileManagerController::class, 'permanentDeleteFile']);
        Route::delete('/folders/{folder}', [FileManagerController::class, 'permanentDeleteFolder']);
    });

    // Usuários (apenas para administradores)
    Route::middleware('admin')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::put('/users/{user}/role', [UserController::class, 'updateRole']);
        Route::delete('/users/{user}', [UserController::class, 'delete']);
    });

    // Logs de atividade
    Route::get('/activity-logs', [UserController::class, 'getActivityLogs']);
});
