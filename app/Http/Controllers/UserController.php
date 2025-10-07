<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\User;
use App\Models\ActivityLog;

class UserController extends Controller
{
    /**
     * Exibir lista de usuários
     */
    public function index(): Response
    {
        $users = User::orderBy('created_at', 'desc')->get();
        
        return Inertia::render('Users/Index', [
            'users' => $users,
            'auth' => [
                'user' => auth()->user()
            ]
        ]);
    }

    /**
     * Criar novo usuário
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'sector' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'role' => 'nullable|string|in:usuario,gestor,administrador',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'sector' => $validated['sector'] ?? null,
            'position' => $validated['position'] ?? null,
            'role' => $validated['role'] ?? 'usuario',
        ]);

        // Log da atividade
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'created',
            'model_type' => 'User',
            'model_id' => $user->id,
            'description' => "Usuário '{$user->name}' foi criado"
        ]);

        return redirect()->back()->with('success', 'Usuário criado com sucesso!');
    }

    /**
     * Atualizar usuário
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'sector' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'role' => 'nullable|string|in:usuario,gestor,administrador',
            'password' => 'nullable|string|min:8',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'sector' => $validated['sector'],
            'position' => $validated['position'],
            'role' => $validated['role'],
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        // Log da atividade
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'updated',
            'model_type' => 'User',
            'model_id' => $user->id,
            'description' => "Usuário '{$user->name}' foi atualizado"
        ]);

        return redirect()->back()->with('success', 'Usuário atualizado com sucesso!');
    }

    /**
     * Excluir usuário
     */
    public function destroy(User $user)
    {
        // Não permitir exclusão do próprio usuário
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'Você não pode excluir sua própria conta.');
        }

        $userName = $user->name;
        $user->delete();

        // Log da atividade
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'deleted',
            'model_type' => 'User',
            'model_id' => $user->id,
            'description' => "Usuário '{$userName}' foi excluído"
        ]);

        return redirect()->back()->with('success', 'Usuário excluído com sucesso!');
    }

    /**
     * Obter logs de atividade
     */
    public function getActivityLogs(): Response
    {
        $logs = ActivityLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        return Inertia::render('ActivityLogs/Index', [
            'logs' => $logs
        ]);
    }
}