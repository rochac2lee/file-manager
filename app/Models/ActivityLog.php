<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'description',
        'metadata',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Criar log de atividade
     */
    public static function createLog($user, $action, $modelType, $modelId, $description, $metadata = null)
    {
        return self::create([
            'user_id' => $user->id,
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'description' => $description,
            'metadata' => $metadata ? json_encode($metadata) : null,
        ]);
    }

    /**
     * Accessor para metadata
     */
    public function getMetadataAttribute($value)
    {
        return $value ? json_decode($value, true) : null;
    }
}