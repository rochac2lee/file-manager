<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FolderPermission extends Model
{
    protected $fillable = [
        'folder_id',
        'user_id',
        'can_view',
        'can_edit',
        'can_delete',
        'can_rename',
    ];

    protected $casts = [
        'can_view' => 'boolean',
        'can_edit' => 'boolean',
        'can_delete' => 'boolean',
        'can_rename' => 'boolean',
    ];

    /**
     * Relacionamentos
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
