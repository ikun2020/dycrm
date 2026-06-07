<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AiSetting;
use Illuminate\Auth\Access\HandlesAuthorization;

class AiSettingPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AiSetting');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AiSetting');
    }

    public function update(AuthUser $authUser, AiSetting $aiSetting): bool
    {
        return $authUser->can('Update:AiSetting');
    }

    public function delete(AuthUser $authUser, AiSetting $aiSetting): bool
    {
        return $authUser->can('Delete:AiSetting');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:AiSetting');
    }

    public function restore(AuthUser $authUser, AiSetting $aiSetting): bool
    {
        return $authUser->can('Restore:AiSetting');
    }

    public function forceDelete(AuthUser $authUser, AiSetting $aiSetting): bool
    {
        return $authUser->can('ForceDelete:AiSetting');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AiSetting');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AiSetting');
    }

    public function replicate(AuthUser $authUser, AiSetting $aiSetting): bool
    {
        return $authUser->can('Replicate:AiSetting');
    }

}