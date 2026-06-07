<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\OperationLog;
use Illuminate\Auth\Access\HandlesAuthorization;

class OperationLogPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:OperationLog');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:OperationLog');
    }

    public function update(AuthUser $authUser, OperationLog $operationLog): bool
    {
        return $authUser->can('Update:OperationLog');
    }

    public function delete(AuthUser $authUser, OperationLog $operationLog): bool
    {
        return $authUser->can('Delete:OperationLog');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:OperationLog');
    }

    public function restore(AuthUser $authUser, OperationLog $operationLog): bool
    {
        return $authUser->can('Restore:OperationLog');
    }

    public function forceDelete(AuthUser $authUser, OperationLog $operationLog): bool
    {
        return $authUser->can('ForceDelete:OperationLog');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:OperationLog');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:OperationLog');
    }

    public function replicate(AuthUser $authUser, OperationLog $operationLog): bool
    {
        return $authUser->can('Replicate:OperationLog');
    }

}