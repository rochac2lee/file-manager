<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class File extends Model
{
    protected $fillable = [
        'name',
        'original_name',
        'path',
        'mime_type',
        'size',
        'folder_id',
        'uploaded_by',
        'is_shared',
    ];

    protected $casts = [
        'is_shared' => 'boolean',
        'size' => 'integer',
    ];

    /**
     * Relacionamentos
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(FilePermission::class);
    }

    /**
     * Verificar se o usuário tem permissão para visualizar
     */
    public function canView(User $user): bool
    {
        if ($this->uploaded_by === $user->id || $user->isAdmin()) {
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
        if ($this->uploaded_by === $user->id || $user->isAdmin()) {
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
        if ($this->uploaded_by === $user->id || $user->isAdmin()) {
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
        if ($this->uploaded_by === $user->id || $user->isAdmin()) {
            return true;
        }

        $permission = $this->permissions()->where('user_id', $user->id)->first();
        return $permission && $permission->can_rename;
    }

    /**
     * Formatar tamanho do arquivo
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
