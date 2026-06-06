<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermissionGroup extends Model
{
    protected $fillable = [
        'name',
        'description',
        'menu_permissions',
        'editable_menus',
        'deletable_menus',
        'notify_sample_shipments',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'menu_permissions' => 'array',
            'editable_menus' => 'array',
            'deletable_menus' => 'array',
            'notify_sample_shipments' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
