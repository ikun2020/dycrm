<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SampleItem;
use Illuminate\Auth\Access\HandlesAuthorization;

class SampleItemPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SampleItem');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SampleItem');
    }

    public function update(AuthUser $authUser, SampleItem $sampleItem): bool
    {
        return $authUser->can('Update:SampleItem');
    }

    public function delete(AuthUser $authUser, SampleItem $sampleItem): bool
    {
        return $authUser->can('Delete:SampleItem');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SampleItem');
    }

    public function restore(AuthUser $authUser, SampleItem $sampleItem): bool
    {
        return $authUser->can('Restore:SampleItem');
    }

    public function forceDelete(AuthUser $authUser, SampleItem $sampleItem): bool
    {
        return $authUser->can('ForceDelete:SampleItem');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SampleItem');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SampleItem');
    }

    public function replicate(AuthUser $authUser, SampleItem $sampleItem): bool
    {
        return $authUser->can('Replicate:SampleItem');
    }

}