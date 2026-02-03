<?php

namespace Xentixar\WorkflowManager\Forms\Components;

use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Xentixar\WorkflowManager\Contracts\WorkflowsContract;
use Xentixar\WorkflowManager\Models\Workflow;
use Xentixar\WorkflowManager\Models\WorkflowState;
use Xentixar\WorkflowManager\Models\WorkflowTransition;
use Xentixar\WorkflowManager\Support\Helper;
use Xentixar\WorkflowManager\Support\RuleEvaluator;

class StateSelect extends Select
{
    protected ?string $workflowModel = null;

    protected ?string $role = null;

    protected array $ignoredActions = [];

    protected bool $hasManuallySetIgnoredActions = false;

    public static function make(?string $name = 'status'): static
    {
        $static = parent::make($name)->label('Status');

        return $static
            ->selectablePlaceholder(false)
            ->options(fn ($get) => self::resolveOptions($static->getWorkflowModel(), $static->getRole()))
            ->disableOptionWhen(function (string $value, Get $get, ?Model $record, $operation) use ($static, $name) {
                if (in_array($operation, $static->hasManuallySetIgnoredActions ? $static->getIgnoredActions() : config('workflow-manager.ignored_actions', []))) {
                    return false;
                }

                $workflowModel = $static->getWorkflowModel();
                $role = $static->getRole();
                $currentState = $name ? $record?->getAttribute($name) : null;
                $currentState = $currentState ?? $static->getDefaultState();
                if (! $currentState || ! $workflowModel || ! $role) {
                    return false;
                }
                if (is_object($currentState) && enum_exists(get_class($currentState))) {
                    $currentState = $currentState->value; // @phpstan-ignore-line
                }

                return self::shouldDisableOption($value, $currentState, $workflowModel, $role, $record);
            }, true);
    }

    /**
     * Set the model class that uses the Workflows trait.
     *
     * @param  class-string  $model
     *
     * @throws InvalidArgumentException
     */
    public function setWorkflowForModel(string $model): static
    {
        if (! ((new $model) instanceof WorkflowsContract)) {
            throw new InvalidArgumentException('The model class must implement the Workflows contract.');
        }

        $this->workflowModel = $model;

        return $this;
    }

    public function setRole(string $role): static
    {
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule(function (Get $get, ?Model $record, $operation) {
            if (in_array($operation, $this->hasManuallySetIgnoredActions ? $this->getIgnoredActions() : config('workflow-manager.ignored_actions', []))) {
                return [];
            }

            $workflowModel = $this->getWorkflowModel();
            $role = $this->getRole();
            $name = $this->getName();
            $currentState = $record?->getAttribute($name) ?? $this->getDefaultState();

            if (! $workflowModel || ! $role || ! $currentState) {
                return [];
            }

            if (is_object($currentState) && enum_exists(get_class($currentState))) {
                $currentState = $currentState->value; // @phpstan-ignore-line
            }

            $options = self::resolveOptions($workflowModel, $role);

            $enabledOptions = array_filter(
                $options,
                function ($label, $value) use ($workflowModel, $role, $currentState, $record) {
                    $workflowDisabled = self::shouldDisableOption($value, $currentState, $workflowModel, $role, $record);

                    return ! ($workflowDisabled);
                },
                ARRAY_FILTER_USE_BOTH
            );

            return [Rule::in(array_keys($enabledOptions))];
        });
    }

    /**
     * Returns the available options for the given model and role.
     *
     * @return array<string, string>
     */
    private static function resolveOptions(?string $model, ?string $role): array
    {
        if (! $model || ! $role) {
            return [];
        }

        $morphClass = (new $model)->getMorphClass(); // @phpstan-ignore-line

        $workflow = Workflow::query()
            ->where('model_class', $morphClass)
            ->where('role', $role)
            ->with('states')
            ->first();

        $enumClass = $model::getStates();
        $defaultStates = Helper::getStatesFromEnum($enumClass);

        return $workflow?->states->pluck('label', 'state')->toArray() ?? $defaultStates;
    }

    /**
     * @param  class-string|null  $model
     */
    private static function shouldDisableOption(string $value, string $currentStateValue, ?string $model, ?string $role, ?Model $record = null): bool
    {
        if (! $currentStateValue || ! $model || ! $role) {
            return false;
        }

        $morphClass = (new $model)->getMorphClass(); // @phpstan-ignore-line

        $workflow = Workflow::query()
            ->where('model_class', $morphClass)
            ->where('role', $role)
            ->first();

        if (! $workflow) {
            return false;
        }

        $currentState = WorkflowState::query()
            ->where('workflow_id', $workflow->id)
            ->where('state', $currentStateValue)
            ->first();

        if (! $currentState) {
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

        if (! $targetState || ! in_array($targetState->id, $acceptedStateIds)) {
            return true;
        }

        if ($record && config('workflow-manager.rules_enabled', true)) {
            $transitionsWithConditionsFromCurrent = WorkflowTransition::query()
                ->where('workflow_id', $workflow->id)
                ->where('from_state_id', $currentState->id)
                ->whereHas('conditions')
                ->with('toState')
                ->get();

            if ($transitionsWithConditionsFromCurrent->isNotEmpty()) {
                if ($value === $currentStateValue) {
                    return false;
                }

                $ruleAllowedToStates = RuleEvaluator::getAllowedToStates($workflow, $record, $currentStateValue);
                $targetStateValuesWithConditions = $transitionsWithConditionsFromCurrent
                    ->map(fn ($t) => $t->toState?->state)
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                if ($ruleAllowedToStates !== []) {
                    return ! in_array($value, $ruleAllowedToStates, true);
                }

                return in_array($value, $targetStateValuesWithConditions, true);
            }
        }

        return false;
    }

    public function setIgnoredActions(array $actions, bool $override = false): static
    {
        $this->ignoredActions = $override ? $actions : array_unique(array_merge($this->ignoredActions, $actions));
        $this->hasManuallySetIgnoredActions = true;

        return $this;
    }

    public function getIgnoredActions(): array
    {
        return $this->ignoredActions;
    }
}
