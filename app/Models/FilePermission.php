<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FilePermission extends Model
{
    protected $fillable = [
        'file_id',
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
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
