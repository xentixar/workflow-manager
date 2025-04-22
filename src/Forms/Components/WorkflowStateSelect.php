<?php

namespace Xentixar\WorkflowManager\Forms\Components;

use Closure;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Model;
use Xentixar\WorkflowManager\Contracts\Workflows;
use Xentixar\WorkflowManager\Models\Workflow;
use Xentixar\WorkflowManager\Models\WorkflowTransition;

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
                $model = $static->getworkflowModel();

                if ($model === null) {
                    throw new \InvalidArgumentException('Workflow model is not set.');
                }

                $states = $model::getStates();
                $states = array_combine($states, $states);

                return $states;
            })
            ->disableOptionWhen(function (string $value, $get, ?Model $record) use ($name, $static) {
                $model = $static->getworkflowModel();
                $role = $static->getRole();

                $workflow = Workflow::query()
                    ->where('model_class', $model)
                    ->where('role', $role)
                    ->first();

                if ($workflow === null) {
                    throw new \InvalidArgumentException('Workflow not found.');
                }

                if ($model === null) {
                    throw new \InvalidArgumentException('Workflow model is not set.');
                }

                if ($record === null) {
                    return false;
                }

                $currentState = $record->getAttribute($name);

                if (is_object($currentState) && enum_exists(get_class($currentState))) {
                    $currentState = $currentState->value;
                }

                $childrenStates = [];
                $parentStates = [];

                $currentTransitionStates = WorkflowTransition::query()
                    ->where('workflow_id', $workflow->id)
                    ->where('state', $currentState)
                    ->get();

                $childrenStates = $currentTransitionStates
                    ->flatMap(function ($state) {
                        return $state->children?->pluck('state') ?? [];
                    })
                    ->unique()
                    ->values()
                    ->toArray();

                if (config('workflow-manager.include_parent')) {
                    $parentStates = $currentTransitionStates
                        ->flatMap(function ($state) {
                            return $state->parent ? [$state->parent->state] : [];
                        })
                        ->unique()
                        ->values()
                        ->toArray();
                }

                $childrenStates = $currentTransitionStates
                    ->flatMap(function ($parentState) {
                        return $parentState->children?->pluck('state') ?? [];
                    })
                    ->unique()
                    ->values()
                    ->toArray();

                $childrenStates = array_combine($childrenStates, $childrenStates);
                $parentStates = array_combine($parentStates, $parentStates);

                $acceptedStates = array_merge($childrenStates, $parentStates, [$currentState => $currentState]);

                return !in_array($value, $acceptedStates);
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
