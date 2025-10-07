<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
// use Illuminate\Database\Eloquent\SoftDeletes;

class Folder extends Model
{
    // use SoftDeletes;
    protected $fillable = [
        'name',
        'path',
        'parent_id',
        'created_by',
        'is_shared',
    ];

    protected $casts = [
        'is_shared' => 'boolean',
    ];

    /**
     * Relacionamentos
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Folder::class, 'parent_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function files(): HasMany
    {
        return $this->hasMany(File::class);
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(FolderPermission::class);
    }

    /**
     * Verificar se o usuário tem permissão para visualizar
     */
    public function canView(User $user): bool
    {
        if ($this->created_by === $user->id || $user->isAdmin()) {
            return true;
        }

        $permission = $this->permissions()->where('user_id', $user->id)->first();
        return $permission && $permission->can_view;
    }

    /**
     * Verificar se o usuário tem permissão para editar
     */
    public function canEdit(User $user): bool
    {
        if ($this->created_by === $user->id || $user->isAdmin()) {
            return true;
        }

        $permission = $this->permissions()->where('user_id', $user->id)->first();
        return $permission && $permission->can_edit;
    }

    /**
     * Verificar se o usuário tem permissão para excluir
     */
    public function canDelete(User $user): bool
    {
        if ($this->created_by === $user->id || $user->isAdmin()) {
            return true;
        }

        $permission = $this->permissions()->where('user_id', $user->id)->first();
        return $permission && $permission->can_delete;
    }

    /**
     * Verificar se o usuário tem permissão para renomear
     */
    public function canRename(User $user): bool
    {
        if ($this->created_by === $user->id || $user->isAdmin()) {
            return true;
        }

        $permission = $this->permissions()->where('user_id', $user->id)->first();
        return $permission && $permission->can_rename;
    }
}
