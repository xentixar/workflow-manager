<?php

namespace Xentixar\WorkflowManager\Contracts;

use Illuminate\Database\Eloquent\Relations\HasMany;

interface Workflows
{
    /**
     * Get the workflows for the model.
     *
     * @return HasMany
     */
    public function workflows(): HasMany;

    /**
     * Get the available states for the workflow.
     * 
     * @return array
     */
    public static function getStates(): array;
}