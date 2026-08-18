<?php

namespace App\Models;

use App\Models\Concerns\GeneratesUuid;
use App\Support\Portal;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'phone', 'password', 'status', 'customer_uuid'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use GeneratesUuid, HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $connection = 'isp_application';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_BLOCKED = 'blocked';

    /**
     * Rôles du personnel (accès administration), par opposition à « client ».
     */
    public const STAFF_ROLES = [
        'super_admin', 'admin', 'finance', 'commercial', 'support', 'network_admin', 'operator',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_uuid', 'uuid');
    }

    public function isClient(): bool
    {
        return $this->hasRole('client');
    }

    public function isStaff(): bool
    {
        return $this->hasAnyRole(self::STAFF_ROLES);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isStaff();
    }

    /**
     * Portail cible après connexion selon le rôle.
     */
    public function homeRoute(): string
    {
        $role = $this->getRoleNames()->first() ?? Portal::CLIENT_ROLE;

        return route(Portal::routeForRole($role));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
