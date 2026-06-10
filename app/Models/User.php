<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasRoles;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'theme_color',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (! static::query()->exists()) {
                $user->is_active = true;
            }
        });

        static::created(function (User $user): void {
            if (
                static::query()->whereKeyNot($user->getKey())->exists()
                || ! Schema::hasTable('roles')
                || ! Schema::hasTable('model_has_roles')
            ) {
                return;
            }

            $user->assignRole(Role::firstOrCreate([
                'name' => config('filament-shield.super_admin.name', 'super_admin'),
                'guard_name' => 'web',
            ]));
        });
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(config('filament-shield.super_admin.name', 'super_admin'));
    }

    public function canAccessMenu(string $menu): bool
    {
        return $this->isSuperAdmin()
            || ($this->canUseShieldMenuPermission($menu, 'view') ?? false);
    }

    public function canCreateMenu(string $menu): bool
    {
        return $this->isSuperAdmin()
            || ($this->canUseShieldMenuPermission($menu, 'create') ?? false);
    }

    public function canEditMenu(string $menu): bool
    {
        return $this->isSuperAdmin()
            || ($this->canUseShieldMenuPermission($menu, 'update') ?? false);
    }

    public function canDeleteMenu(string $menu): bool
    {
        return $this->isSuperAdmin()
            || ($this->canUseShieldMenuPermission($menu, 'delete') ?? false);
    }

    private function canUseShieldMenuPermission(string $menu, string $action): ?bool
    {
        if (! $this->relationLoaded('roles')) {
            $this->load('roles.permissions');
        }

        if ($this->roles->isEmpty()) {
            return null;
        }

        $subject = self::shieldPermissionSubjects()[$menu] ?? null;

        if ($subject === null) {
            return null;
        }

        $prefix = match ($action) {
            'view' => 'ViewAny',
            'create' => 'Create',
            'update' => 'Update',
            'delete' => 'Delete',
            default => null,
        };

        if ($prefix === null) {
            return null;
        }

        return $this->can($prefix.':'.$subject);
    }

    public static function shieldPermissionSubjects(): array
    {
        return [
            'creators' => 'Creator',
            'follow-ups' => 'FollowUp',
            'products' => 'Product',
            'sample-items' => 'SampleItem',
            'samples' => 'Sample',
            'live-sessions' => 'LiveSession',
            'gmv-records' => 'GmvRecord',
            'ai-reports' => 'AiReport',
            'knowledge-posts' => 'KnowledgePost',
        ];
    }

    public function creators(): HasMany
    {
        return $this->hasMany(Creator::class, 'owner_id');
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUp::class);
    }

    public function liveSessions(): HasMany
    {
        return $this->hasMany(LiveSession::class, 'owner_id');
    }

    public function samples(): HasMany
    {
        return $this->hasMany(Sample::class, 'owner_id');
    }
}
