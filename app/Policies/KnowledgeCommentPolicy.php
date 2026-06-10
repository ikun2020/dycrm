<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\KnowledgeComment;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class KnowledgeCommentPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:KnowledgeComment');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:KnowledgeComment');
    }

    public function update(AuthUser $authUser, KnowledgeComment $knowledgeComment): bool
    {
        return $authUser->can('Update:KnowledgeComment');
    }

    public function delete(AuthUser $authUser, KnowledgeComment $knowledgeComment): bool
    {
        return $authUser->can('Delete:KnowledgeComment');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:KnowledgeComment');
    }

    public function restore(AuthUser $authUser, KnowledgeComment $knowledgeComment): bool
    {
        return $authUser->can('Restore:KnowledgeComment');
    }

    public function forceDelete(AuthUser $authUser, KnowledgeComment $knowledgeComment): bool
    {
        return $authUser->can('ForceDelete:KnowledgeComment');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:KnowledgeComment');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:KnowledgeComment');
    }

    public function replicate(AuthUser $authUser, KnowledgeComment $knowledgeComment): bool
    {
        return $authUser->can('Replicate:KnowledgeComment');
    }
}
