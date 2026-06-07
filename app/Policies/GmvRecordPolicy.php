<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\GmvRecord;
use Illuminate\Auth\Access\HandlesAuthorization;

class GmvRecordPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:GmvRecord');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:GmvRecord');
    }

    public function update(AuthUser $authUser, GmvRecord $gmvRecord): bool
    {
        return $authUser->can('Update:GmvRecord');
    }

    public function delete(AuthUser $authUser, GmvRecord $gmvRecord): bool
    {
        return $authUser->can('Delete:GmvRecord');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:GmvRecord');
    }

    public function restore(AuthUser $authUser, GmvRecord $gmvRecord): bool
    {
        return $authUser->can('Restore:GmvRecord');
    }

    public function forceDelete(AuthUser $authUser, GmvRecord $gmvRecord): bool
    {
        return $authUser->can('ForceDelete:GmvRecord');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:GmvRecord');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:GmvRecord');
    }

    public function replicate(AuthUser $authUser, GmvRecord $gmvRecord): bool
    {
        return $authUser->can('Replicate:GmvRecord');
    }

}