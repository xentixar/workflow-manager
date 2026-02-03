<?php

namespace Xentixar\WorkflowManager\Support;

use Illuminate\Database\Eloquent\Model;
use Xentixar\WorkflowManager\Models\Workflow;
use Xentixar\WorkflowManager\Models\WorkflowRule;
use Xentixar\WorkflowManager\Models\WorkflowRuleAction;
use Xentixar\WorkflowManager\Models\WorkflowRuleCondition;
use Xentixar\WorkflowManager\Models\WorkflowState;
use Xentixar\WorkflowManager\Models\WorkflowTransition;

final class RuleEvaluator
{
    /**
     * Get the list of state names (strings) that are allowed as "to" state by rules
     * for the given workflow, record, and current state.
     * Rules can be bound to each transition (workflow_transition_id) or legacy workflow-level (actions).
     *
     * @return array<int, string>
     */
    public static function getAllowedToStates(Workflow $workflow, Model $record, string $currentStateValue): array
    {
        if (! config('workflow-manager.rules_enabled', true)) {
            return [];
        }

        $currentState = WorkflowState::query()
            ->where('workflow_id', $workflow->id)
            ->where('state', $currentStateValue)
            ->first();

        if (! $currentState) {
            return [];
        }

        $transitionsFromCurrent = WorkflowTransition::query()
            ->where('workflow_id', $workflow->id)
            ->where('from_state_id', $currentState->id)
            ->with(['toState', 'conditions'])
            ->get();

        $hasTransitionConditions = $transitionsFromCurrent->contains(
            fn (WorkflowTransition $t) => $t->conditions->isNotEmpty()
        );

        if ($hasTransitionConditions) {
            return self::evaluatePerTransition($transitionsFromCurrent, $record);
        }

        $workflowLevelRules = $workflow->rules()
            ->whereNull('workflow_transition_id')
            ->where('is_active', true)
            ->orderBy('priority')
            ->with(['conditions', 'actions'])
            ->get();

        if ($workflowLevelRules->isNotEmpty()) {
            return self::evaluateWorkflowLevelRules($workflowLevelRules, $record, $currentStateValue);
        }

        return [];
    }

    /**
     * For each transition, allow its to_state if it has no conditions or its conditions pass.
     *
     * @param  \Illuminate\Support\Collection<int, WorkflowTransition>  $transitions
     * @return array<int, string>
     */
    private static function evaluatePerTransition($transitions, Model $record): array
    {
        $allowed = [];

        foreach ($transitions as $transition) {
            $toStateValue = $transition->toState?->state;
            if ($toStateValue === null) {
                continue;
            }

            $conditions = $transition->conditions;
            if ($conditions->isEmpty()) {
                $allowed[] = $toStateValue;
                continue;
            }

            if (self::evaluateConditionCollection($conditions, $record)) {
                $allowed[] = $toStateValue;
            }
        }

        return array_values(array_unique($allowed));
    }

    /**
     * Evaluate a collection of conditions (e.g. on a transition); combine with first condition's logical_group.
     */
    private static function evaluateConditionCollection($conditions, Model $record): bool
    {
        if ($conditions->isEmpty()) {
            return true;
        }

        $results = $conditions->map(fn (WorkflowRuleCondition $c) => self::evaluateCondition($c, $record));
        $logicalGroup = strtoupper($conditions->first()?->logical_group ?? 'AND');

        return $logicalGroup === 'OR'
            ? $results->contains(true)
            : $results->every(fn ($r) => $r === true);
    }

    /**
     * Legacy: first matching workflow-level rule returns its action to_states for current state.
     *
     * @param  \Illuminate\Support\Collection<int, WorkflowRule>  $rules
     * @return array<int, string>
     */
    private static function evaluateWorkflowLevelRules($rules, Model $record, string $currentStateValue): array
    {
        foreach ($rules as $rule) {
            if (self::evaluateConditions($rule, $record)) {
                return self::getToStatesForCurrentState($rule, $currentStateValue);
            }
        }

        return [];
    }

    /**
     * Evaluate all conditions of a rule against the record.
     * Conditions are combined using the rule's logical_group (AND/OR).
     * Field supports dot notation for relationships (e.g. user.department, budget.amount).
     */
    private static function evaluateConditions(WorkflowRule $rule, Model $record): bool
    {
        $conditions = $rule->conditions;

        if ($conditions->isEmpty()) {
            return true;
        }

        $results = $conditions->map(fn (WorkflowRuleCondition $c) => self::evaluateCondition($c, $record));

        $logicalGroup = strtoupper($conditions->first()?->logical_group ?? 'AND');

        return $logicalGroup === 'OR'
            ? $results->contains(true)
            : $results->every(fn ($r) => $r === true);
    }

    /**
     * Evaluate a single condition against the record.
     * Field can be a relationship path (e.g. user.department) via data_get().
     */
    private static function evaluateCondition(WorkflowRuleCondition $condition, Model $record): bool
    {
        $fieldValue = self::getFieldValue($record, $condition->field);

        $compareValue = self::resolveCompareValue($condition, $record);
        if ($compareValue === null && $condition->value_type !== 'static') {
            return false;
        }

        $operator = $condition->operator;

        return match ($operator) {
            '>' => self::compare($fieldValue, $compareValue, fn ($a, $b) => $a > $b),
            '<' => self::compare($fieldValue, $compareValue, fn ($a, $b) => $a < $b),
            '>=' => self::compare($fieldValue, $compareValue, fn ($a, $b) => $a >= $b),
            '<=' => self::compare($fieldValue, $compareValue, fn ($a, $b) => $a <= $b),
            '=' => self::compare($fieldValue, $compareValue, fn ($a, $b) => $a == $b),
            '!=' => self::compare($fieldValue, $compareValue, fn ($a, $b) => $a != $b),
            'in' => self::compareIn($fieldValue, $condition->value),
            default => false,
        };
    }

    /**
     * Get attribute or relationship value; supports dot notation (e.g. user.department, budget.amount).
     */
    private static function getFieldValue(Model $record, string $field): mixed
    {
        return data_get($record, $field);
    }

    /**
     * Resolve the value to compare against (static or percentage of base field).
     * Base field supports dot notation.
     */
    private static function resolveCompareValue(WorkflowRuleCondition $condition, Model $record): mixed
    {
        if ($condition->value_type === 'static') {
            return self::castValue($condition->value, self::getFieldValue($record, $condition->field));
        }

        if ($condition->value_type === 'percentage') {
            $baseField = $condition->base_field ?? $condition->field;
            $baseValue = self::getFieldValue($record, $baseField);
            if ($baseValue === null || ! is_numeric($baseValue) || ! is_numeric($condition->value)) {
                return null;
            }
            return (float) $baseValue * ((float) $condition->value / 100);
        }

        return $condition->value;
    }

    /**
     * Cast string value to a type suitable for comparison with field value.
     */
    private static function castValue(string $value, mixed $fieldValue): mixed
    {
        if (is_numeric($fieldValue) || (is_string($fieldValue) && is_numeric($fieldValue))) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }
        if (is_bool($fieldValue)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }
        return $value;
    }

    private static function compare(mixed $fieldValue, mixed $compareValue, callable $op): bool
    {
        if ($fieldValue === null && $compareValue === null) {
            return $op(0, 0) || $op(null, null);
        }
        if ($fieldValue === null || $compareValue === null) {
            return false;
        }
        $a = is_numeric($fieldValue) ? (float) $fieldValue : $fieldValue;
        $b = is_numeric($compareValue) ? (float) $compareValue : $compareValue;
        return $op($a, $b);
    }

    private static function compareIn(mixed $fieldValue, string $valueList): bool
    {
        $allowed = array_map('trim', explode(',', $valueList));
        return in_array((string) $fieldValue, $allowed, true)
            || in_array($fieldValue, $allowed, false);
    }

    /**
     * Get to_state values from the rule's actions where from_state matches current state.
     *
     * @return array<int, string>
     */
    private static function getToStatesForCurrentState(WorkflowRule $rule, string $currentStateValue): array
    {
        return $rule->actions
            ->where('from_state', $currentStateValue)
            ->pluck('to_state')
            ->unique()
            ->values()
            ->all();
    }
}
