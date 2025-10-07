<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'sector',
        'position',
        'role',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relacionamentos
     */
    public function folders()
    {
        return $this->hasMany(Folder::class, 'created_by');
    }

    public function files()
    {
        return $this->hasMany(File::class, 'uploaded_by');
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function folderPermissions()
    {
        return $this->hasMany(FolderPermission::class);
    }

    public function filePermissions()
    {
        return $this->hasMany(FilePermission::class);
    }

    /**
     * Verificar se o usuário é administrador
     */
    public function isAdmin()
    {
        return $this->role === 'administrador';
    }

    /**
     * Verificar se o usuário é gestor
     */
    public function isManager()
    {
        return $this->role === 'gestor';
    }

    /**
     * Verificar se o email é institucional
     */
    public function hasInstitutionalEmail()
    {
        return str_ends_with($this->email, '@expert-seg.com');
    }
}
