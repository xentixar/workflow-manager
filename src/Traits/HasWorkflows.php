<?php

namespace Xentixar\WorkflowManager\Traits;

use Xentixar\WorkflowManager\Models\Workflow;

trait HasWorkflows
{
    public function workflows()
    {
        return Workflow::query()->where('model_class', get_class($this))->get();
    }

    public function getStates(): array
    {
        return [];
    }
}