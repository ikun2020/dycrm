<?php

namespace App\Filament\Resources\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait ChecksMenuPermission
{
    public static function canViewAny(): bool
    {
        return auth()->user()?->canAccessMenu(static::getMenuPermissionKey()) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canEditMenu(static::getMenuPermissionKey()) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->canEditMenu(static::getMenuPermissionKey()) ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->canDeleteMenu(static::getMenuPermissionKey()) ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->canDeleteMenu(static::getMenuPermissionKey()) ?? false;
    }

    protected static function getMenuPermissionKey(): string
    {
        return static::$menuPermissionKey
            ?? Str::of(class_basename(static::class))
                ->beforeLast('Resource')
                ->kebab()
                ->plural()
                ->toString();
    }
}
