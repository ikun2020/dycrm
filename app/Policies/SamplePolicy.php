<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Sample;
use Illuminate\Auth\Access\HandlesAuthorization;

class SamplePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Sample');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Sample');
    }

    public function update(AuthUser $authUser, Sample $sample): bool
    {
        return $authUser->can('Update:Sample');
    }

    public function delete(AuthUser $authUser, Sample $sample): bool
    {
        return $authUser->can('Delete:Sample');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Sample');
    }

    public function restore(AuthUser $authUser, Sample $sample): bool
    {
        return $authUser->can('Restore:Sample');
    }

    public function forceDelete(AuthUser $authUser, Sample $sample): bool
    {
        return $authUser->can('ForceDelete:Sample');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Sample');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Sample');
    }

    public function replicate(AuthUser $authUser, Sample $sample): bool
    {
        return $authUser->can('Replicate:Sample');
    }

}