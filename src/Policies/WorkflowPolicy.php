<?php

namespace Xentixar\WorkflowManager\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Xentixar\WorkflowManager\Models\Workflow;

class WorkflowPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if (!$this->isPolicyEnabled()) {
            return true;
        }
        return $user->can(config('workflow-manager.permissions.view_any'));
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Workflow $workflow): bool
    {
        if (!$this->isPolicyEnabled()) {
            return true;
        }
        return $user->can(config('workflow-manager.permissions.view'));
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if (!$this->isPolicyEnabled()) {
            return true;
        }
        return $user->can(config('workflow-manager.permissions.create'));
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Workflow $workflow): bool
    {
        if (!$this->isPolicyEnabled()) {
            return true;
        }
        return $user->can(config('workflow-manager.permissions.update'));
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Workflow $workflow): bool
    {
        if (!$this->isPolicyEnabled()) {
            return true;
        }
        return $user->can(config('workflow-manager.permissions.delete'));
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Workflow $workflow): bool
    {
        if (!$this->isPolicyEnabled()) {
            return true;
        }
        return $user->can(config('workflow-manager.permissions.restore'));
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Workflow $workflow): bool
    {
        if (!$this->isPolicyEnabled()) {
            return true;
        }
        return $user->can(config('workflow-manager.permissions.force_delete'));
    }

    /**
     * Determine whether the user can reorder the model.
     */
    public function reorder(User $user): bool
    {
        if (!$this->isPolicyEnabled()) {
            return true;
        }
        return $user->can(config('workflow-manager.permissions.reorder'));
    }

    /**
     * Determine whether the user can replicate the model.
     */
    public function replicate(User $user): bool
    {
        if (!$this->isPolicyEnabled()) {
            return true;
        }
        return $user->can(config('workflow-manager.permissions.replicate'));
    }

    /**
     * Check if the policy is enabled.
     */
    private function isPolicyEnabled(): bool
    {
        return config('workflow-manager.enable_policy', false);
    }
}
