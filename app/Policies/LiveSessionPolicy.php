<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\LiveSession;
use Illuminate\Auth\Access\HandlesAuthorization;

class LiveSessionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:LiveSession');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:LiveSession');
    }

    public function update(AuthUser $authUser, LiveSession $liveSession): bool
    {
        return $authUser->can('Update:LiveSession');
    }

    public function delete(AuthUser $authUser, LiveSession $liveSession): bool
    {
        return $authUser->can('Delete:LiveSession');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:LiveSession');
    }

    public function restore(AuthUser $authUser, LiveSession $liveSession): bool
    {
        return $authUser->can('Restore:LiveSession');
    }

    public function forceDelete(AuthUser $authUser, LiveSession $liveSession): bool
    {
        return $authUser->can('ForceDelete:LiveSession');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:LiveSession');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:LiveSession');
    }

    public function replicate(AuthUser $authUser, LiveSession $liveSession): bool
    {
        return $authUser->can('Replicate:LiveSession');
    }

}