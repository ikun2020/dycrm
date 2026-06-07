<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AiReport;
use Illuminate\Auth\Access\HandlesAuthorization;

class AiReportPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AiReport');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AiReport');
    }

    public function update(AuthUser $authUser, AiReport $aiReport): bool
    {
        return $authUser->can('Update:AiReport');
    }

    public function delete(AuthUser $authUser, AiReport $aiReport): bool
    {
        return $authUser->can('Delete:AiReport');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:AiReport');
    }

    public function restore(AuthUser $authUser, AiReport $aiReport): bool
    {
        return $authUser->can('Restore:AiReport');
    }

    public function forceDelete(AuthUser $authUser, AiReport $aiReport): bool
    {
        return $authUser->can('ForceDelete:AiReport');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AiReport');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AiReport');
    }

    public function replicate(AuthUser $authUser, AiReport $aiReport): bool
    {
        return $authUser->can('Replicate:AiReport');
    }

}