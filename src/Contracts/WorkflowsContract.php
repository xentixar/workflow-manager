<?php

namespace Xentixar\WorkflowManager\Contracts;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Xentixar\WorkflowManager\Models\Workflow;

interface WorkflowsContract
{
    /**
     * Get the workflows for the model.
     *
     * @return HasMany<Workflow, $this>
     */
    public function workflows(): HasMany; // @phpstan-ignore-line

    /**
     * Get the enum class representing available states.
     */
    public static function getStates(): string;
}
