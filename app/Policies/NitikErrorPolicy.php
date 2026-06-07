<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Kholil\Nitik\Models\NitikError;
use Illuminate\Auth\Access\HandlesAuthorization;

class NitikErrorPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:NitikError');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:NitikError');
    }

    public function update(AuthUser $authUser, NitikError $nitikError): bool
    {
        return $authUser->can('Update:NitikError');
    }

    public function delete(AuthUser $authUser, NitikError $nitikError): bool
    {
        return $authUser->can('Delete:NitikError');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:NitikError');
    }

    public function restore(AuthUser $authUser, NitikError $nitikError): bool
    {
        return $authUser->can('Restore:NitikError');
    }

    public function forceDelete(AuthUser $authUser, NitikError $nitikError): bool
    {
        return $authUser->can('ForceDelete:NitikError');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:NitikError');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:NitikError');
    }

    public function replicate(AuthUser $authUser, NitikError $nitikError): bool
    {
        return $authUser->can('Replicate:NitikError');
    }

    public function view(AuthUser $authUser, NitikError $nitikError): bool
    {
        return $authUser->can('View:NitikError');
    }

}