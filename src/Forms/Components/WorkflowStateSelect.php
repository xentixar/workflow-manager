<?php

namespace Xentixar\WorkflowManager\Forms\Components;

use Closure;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Model;
use Xentixar\WorkflowManager\Contracts\Workflows;
use Xentixar\WorkflowManager\Models\Workflow;
use Xentixar\WorkflowManager\Models\WorkflowTransition;
use Xentixar\WorkflowManager\Models\WorkflowState;

class WorkflowStateSelect extends Select
{
    protected bool|Closure $isSearchable = true;

    protected bool|Closure $isPreloaded = true;

    protected ?string $workflowModel = null;

    protected ?string $role = null;

    public static function make(string $name = 'status'): static
    {
        $static = parent::make($name)
            ->label('Status');

        return $static
            ->options(function () use ($static) {
                $model = $static->getWorkflowModel();
                $role = $static->getRole();

                if ($model === null || $role === null) {
                    throw new \InvalidArgumentException('Workflow model or role is not set.');
                }

                $workflow = Workflow::query()
                    ->where('model_class', $model)
                    ->where('role', $role)
                    ->with('states')
                    ->first();

                if (!$workflow) {
                    return [];
                }

                return $workflow->states->pluck('label', 'state')->toArray();
            })
            ->disableOptionWhen(function (string $value, $get, ?Model $record) use ($name, $static) {
                $model = $static->getWorkflowModel();
                $role = $static->getRole();

                if ($model === null || $role === null) {
                    throw new \InvalidArgumentException('Workflow model is not set.');
                }
                

                $workflow = Workflow::query()
                    ->where('model_class', $model)
                    ->where('role', $role)
                    ->first();

                if (!$workflow || !$record) {
                    return false;
                }

                $currentStateValue = $record->getAttribute($name);
                if (is_object($currentStateValue) && enum_exists(get_class($currentStateValue))) {
                    $currentStateValue = $currentStateValue->value;
                }

                $currentState = WorkflowState::query()
                    ->where('workflow_id', $workflow->id)
                    ->where('state', $currentStateValue)
                    ->first();

                if (!$currentState) {
                    return true;
                }

                $acceptedStateIds = [$currentState->id];

                $children = WorkflowTransition::query()
                    ->where('workflow_id', $workflow->id)
                    ->where('from_state_id', $currentState->id)
                    ->pluck('to_state_id')
                    ->toArray();

                $parents = [];
                if (config('workflow-manager.include_parent')) {
                    $parents = WorkflowTransition::query()
                        ->where('workflow_id', $workflow->id)
                        ->where('to_state_id', $currentState->id)
                        ->pluck('from_state_id')
                        ->toArray();
                }

                $acceptedStateIds = array_merge($acceptedStateIds, $children, $parents);

                $targetState = WorkflowState::query()
                    ->where('workflow_id', $workflow->id)
                    ->where('state', $value)
                    ->first();

                return !$targetState || !in_array($targetState->id, $acceptedStateIds);
            });
    }

    public function workflowModel(string $class, string $role): static
    {
        if (!((new $class) instanceof Workflows)) {
            throw new \InvalidArgumentException('The class must implement the Workflows interface.');
        }

        $this->workflowModel = $class;
        $this->role = $role;

        return $this;
    }

    public function getWorkflowModel(): ?string
    {
        return $this->workflowModel;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }
}