<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @method static \Illuminate\Database\Eloquent\Builder|Admin newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Admin newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Admin query()
 * @mixin \Eloquent
 * @mixin IdeHelperAdmin
 */
class Admin extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_WRITER = 'writer';

    protected $fillable = [
        'name',
        'email',
        'role',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        // Always use the central DB (admins live with tenants/domains, not shop SQLite).
        $this->connection = config('tenancy.database.central_connection', config('database.default', 'mysql'));
    }

    public function isAdmin(): bool
    {
        return ($this->role ?? self::ROLE_ADMIN) === self::ROLE_ADMIN;
    }

    public function isWriter(): bool
    {
        return ($this->role ?? self::ROLE_ADMIN) === self::ROLE_WRITER;
    }

    public function canManageBlog(): bool
    {
        return $this->isAdmin() || $this->isWriter();
    }

    public function canManageSupport(): bool
    {
        return $this->isAdmin();
    }

    public function canManageAdmins(): bool
    {
        return $this->isAdmin();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin';
    }
}
