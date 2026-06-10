<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\KnowledgePost;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class KnowledgePostPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:KnowledgePost');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:KnowledgePost');
    }

    public function update(AuthUser $authUser, KnowledgePost $knowledgePost): bool
    {
        return $authUser->can('Update:KnowledgePost');
    }

    public function delete(AuthUser $authUser, KnowledgePost $knowledgePost): bool
    {
        return $authUser->can('Delete:KnowledgePost');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:KnowledgePost');
    }

    public function restore(AuthUser $authUser, KnowledgePost $knowledgePost): bool
    {
        return $authUser->can('Restore:KnowledgePost');
    }

    public function forceDelete(AuthUser $authUser, KnowledgePost $knowledgePost): bool
    {
        return $authUser->can('ForceDelete:KnowledgePost');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:KnowledgePost');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:KnowledgePost');
    }

    public function replicate(AuthUser $authUser, KnowledgePost $knowledgePost): bool
    {
        return $authUser->can('Replicate:KnowledgePost');
    }

    public function publish(AuthUser $authUser, KnowledgePost $knowledgePost): bool
    {
        return $authUser->can('Publish:KnowledgePost');
    }
}
