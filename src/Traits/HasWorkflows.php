<?php

namespace Xentixar\WorkflowManager\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Xentixar\WorkflowManager\Models\Workflow;

trait HasWorkflows
{
    /**
     * Get the workflows for the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function workflows(): HasMany
    {
        return $this->hasMany(Workflow::class, 'model_class', get_class($this));
    }
}
