<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'permission_group_id',
        'use_custom_permissions',
        'is_active',
        'theme_color',
        'menu_permissions',
        'editable_menus',
        'deletable_menus',
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
            'use_custom_permissions' => 'boolean',
            'is_active' => 'boolean',
            'menu_permissions' => 'array',
            'editable_menus' => 'array',
            'deletable_menus' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (! static::query()->exists()) {
                $user->role = 'super_admin';
                $user->is_active = true;
            }
        });

        static::saving(function (User $user): void {
            if ($user->role !== 'super_admin' && blank($user->permission_group_id)) {
                $user->permission_group_id = PermissionGroup::query()
                    ->where('name', '商务 BD')
                    ->value('id');
            }
        });
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function canAccessMenu(string $menu): bool
    {
        return $this->canUseMenuAction($menu, 'view');
    }

    public function canEditMenu(string $menu): bool
    {
        return $this->canUseMenuAction($menu, 'edit');
    }

    public function canDeleteMenu(string $menu): bool
    {
        return $this->canUseMenuAction($menu, 'delete');
    }

    private function canUseMenuAction(string $menu, string $action): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $permissions = $this->permissionsForAction($action);

        if ($permissions === null) {
            return match ($action) {
                'view' => $this->menu_permissions === null,
                'edit' => $this->canAccessMenu($menu),
                'delete' => false,
                default => false,
            };
        }

        return in_array($menu, $permissions, true);
    }

    private function permissionsForAction(string $action): ?array
    {
        $group = $this->permissionGroup;

        if (! $this->use_custom_permissions && $group?->is_active) {
            return match ($action) {
                'edit' => $group->editable_menus,
                'delete' => $group->deletable_menus,
                default => $group->menu_permissions,
            };
        }

        return match ($action) {
            'edit' => $this->editable_menus,
            'delete' => $this->deletable_menus,
            default => $this->menu_permissions,
        };
    }

    public static function menuPermissionOptions(): array
    {
        return [
            'creators' => __('Creator Profiles'),
            'follow-ups' => __('Follow-ups'),
            'products' => __('Products'),
            'sample-items' => __('Sample Items'),
            'samples' => __('Sample Shipments'),
            'live-sessions' => __('Live Sessions'),
            'gmv-records' => __('GMV Records'),
            'ai-reports' => __('AI Reports'),
        ];
    }

    public function creators(): HasMany
    {
        return $this->hasMany(Creator::class, 'owner_id');
    }

    public function permissionGroup(): BelongsTo
    {
        return $this->belongsTo(PermissionGroup::class);
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
