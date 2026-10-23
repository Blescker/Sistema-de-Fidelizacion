<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use App\Support\DefaultRoles;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use BelongsToBusiness, HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'business_id',
        'branch_id',
        'name',
        'username',
        'phone',
        'password',
        'is_active',
        'last_login_at',
    ];

    /** Valores por defecto (igual que en la migración) para objetos recién creados */
    protected $attributes = [
        'is_super_admin' => false,
        'is_active' => true,
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    /** Nombre legible del rol principal: "Administrador", "Cajero"... */
    public function roleLabel(): string
    {
        if ($this->isSuperAdmin()) {
            return 'Super Admin';
        }

        $role = $this->roles->first()?->name;

        return DefaultRoles::LABELS[$role] ?? ($role ?? 'Sin rol');
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name));

        return mb_strtoupper(mb_substr($parts[0] ?? '', 0, 1).mb_substr($parts[1] ?? '', 0, 1));
    }
}
