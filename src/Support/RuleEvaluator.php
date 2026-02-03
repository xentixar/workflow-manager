<?php

namespace Xentixar\WorkflowManager\Support;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Throwable;
use Xentixar\WorkflowManager\Models\Workflow;
use Xentixar\WorkflowManager\Models\WorkflowRuleCondition;
use Xentixar\WorkflowManager\Models\WorkflowState;
use Xentixar\WorkflowManager\Models\WorkflowTransition;

final class RuleEvaluator
{
    /**
     * Get the list of state names (strings) that are allowed as "to" state
     * for the given workflow, record, and current state.
     * Only transition-level conditions are evaluated.
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

        return self::evaluatePerTransition($transitionsFromCurrent, $record);
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
     * Evaluate a collection of conditions in order.
     * Sequence matters: result starts as the first condition's result; each next condition
     * is combined with the current result using that condition's logical_group (AND or OR).
     * E.g. c1 AND c2 OR c3 => (c1 AND c2) OR c3.
     */
    private static function evaluateConditionCollection($conditions, Model $record): bool
    {
        if ($conditions->isEmpty()) {
            return true;
        }

        $result = self::evaluateCondition($conditions->first(), $record);

        foreach ($conditions->slice(1) as $condition) {
            $nextResult = self::evaluateCondition($condition, $record);
            $op = strtoupper($condition->logical_group ?? 'AND');

            $result = $op === 'OR'
                ? ($result || $nextResult)
                : ($result && $nextResult);
        }

        return $result;
    }

    /**
     * Evaluate a single condition against the record.
     * Field can be a relationship path (e.g. user.department) via data_get().
     */
    private static function evaluateCondition(WorkflowRuleCondition $condition, Model $record): bool
    {
        $fieldValue = self::getFieldValue($record, $condition->field);

        $operator = $condition->operator;

        if ($operator === 'in') {
            $compareValue = $condition->value_type === 'dynamic'
                ? self::resolveDynamicValueForIn($condition, $record)
                : $condition->value;
            if ($condition->value_type === 'dynamic' && trim((string) $compareValue) === '') {
                return false;
            }
            return self::compareIn($fieldValue, $compareValue);
        }

        if ($operator === 'like' || $operator === 'regex') {
            $pattern = $condition->value_type === 'static'
                ? ($condition->value ?? '')
                : (filled($condition->base_field) ? self::getFieldValue($record, $condition->base_field) : null);
            if ($pattern === null || (is_string($pattern) && trim($pattern) === '')) {
                return false;
            }
            return $operator === 'like'
                ? self::compareLike($fieldValue, (string) $pattern)
                : self::compareRegex($fieldValue, (string) $pattern);
        }

        $compareValue = self::resolveCompareValue($condition, $record);
        if ($condition->value_type === 'dynamic') {
            if ($compareValue === null || (is_string($compareValue) && trim($compareValue) === '')) {
                return false;
            }
        } elseif ($compareValue === null) {
            return false;
        }

        if ($operator === '=' || $operator === '!=') {
            $a = self::normalizeForEquality($fieldValue);
            $b = self::normalizeForEquality($compareValue);
            return $operator === '=' ? ($a == $b) : ($a != $b);
        }

        return match ($operator) {
            '>' => self::compare($fieldValue, $compareValue, fn ($a, $b) => $a > $b),
            '<' => self::compare($fieldValue, $compareValue, fn ($a, $b) => $a < $b),
            '>=' => self::compare($fieldValue, $compareValue, fn ($a, $b) => $a >= $b),
            '<=' => self::compare($fieldValue, $compareValue, fn ($a, $b) => $a <= $b),
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
     * Resolve the value to compare against: static (literal) or dynamic (another column/relation).
     * Base field supports dot notation (e.g. user.department).
     */
    private static function resolveCompareValue(WorkflowRuleCondition $condition, Model $record): mixed
    {
        if ($condition->value_type === 'static') {
            return self::castValue($condition->value, self::getFieldValue($record, $condition->field));
        }

        if ($condition->value_type === 'dynamic' && filled($condition->base_field)) {
            return self::getFieldValue($record, $condition->base_field);
        }

        return $condition->value;
    }

    /**
     * For 'in' operator with dynamic: value list from another column (comma-separated string or array).
     */
    private static function resolveDynamicValueForIn(WorkflowRuleCondition $condition, Model $record): string
    {
        if (blank($condition->base_field)) {
            return $condition->value ?? '';
        }
        $v = self::getFieldValue($record, $condition->base_field);
        if (is_array($v)) {
            return implode(',', $v);
        }
        return (string) $v;
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
        if ($fieldValue instanceof DateTimeInterface || self::looksLikeDate($value)) {
            try {
                return Carbon::parse($value);
            } catch (Throwable) {
                return $value;
            }
        }
        return $value;
    }

    private static function looksLikeDate(string $value): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}/', $value);
    }

    private static function compare(mixed $fieldValue, mixed $compareValue, callable $op): bool
    {
        if ($fieldValue === null && $compareValue === null) {
            return $op(0, 0) || $op(null, null);
        }
        if ($fieldValue === null || $compareValue === null) {
            return false;
        }
        $a = $fieldValue;
        $b = $compareValue;
        $aIsDate = $a instanceof DateTimeInterface || (is_string($a) && self::looksLikeDate($a));
        $bIsDate = $b instanceof DateTimeInterface || (is_string($b) && self::looksLikeDate((string) $b));
        if ($aIsDate || $bIsDate) {
            try {
                $a = $a instanceof DateTimeInterface ? $a : \Carbon\Carbon::parse($fieldValue);
                $b = $b instanceof DateTimeInterface ? $b : \Carbon\Carbon::parse($compareValue);
                return $op($a->getTimestamp(), $b->getTimestamp());
            } catch (\Throwable) {
                // fall through to default comparison
            }
        }
        $a = is_numeric($fieldValue) ? (float) $fieldValue : $fieldValue;
        $b = is_numeric($compareValue) ? (float) $compareValue : $compareValue;
        return $op($a, $b);
    }

    private static function normalizeForEquality(mixed $value): string|float|int
    {
        if (is_numeric($value)) {
            return is_float($value) || (is_string($value) && str_contains((string) $value, '.')) ? (float) $value : (int) $value;
        }
        if ($value instanceof \BackedEnum) {
            return $value->value;
        }
        if ($value instanceof \UnitEnum) {
            return $value->name;
        }
        return (string) $value;
    }

    private static function compareIn(mixed $fieldValue, string $valueList): bool
    {
        $allowed = array_map('trim', explode(',', $valueList));
        return in_array((string) $fieldValue, $allowed, true)
            || in_array($fieldValue, $allowed, false);
    }

    /**
     * Like: SQL-style pattern. % = any sequence, _ = single character.
     * Case-insensitive; leading/trailing whitespace in subject and pattern is trimmed.
     */
    private static function compareLike(mixed $fieldValue, string $pattern): bool
    {
        $subject = trim((string) $fieldValue);
        $pattern = trim($pattern);
        if ($pattern === '') {
            return false;
        }
        $pattern = str_replace(['%', '_'], ["\x02", "\x03"], $pattern);
        $pattern = preg_quote($pattern, '#');
        $pattern = str_replace(["\x02", "\x03"], ['.*', '.'], $pattern);
        return (bool) preg_match('#^' . $pattern . '\z#ui', $subject);
    }

    /**
     * Regex: value is a PCRE pattern. Matches if field value matches the pattern.
     */
    private static function compareRegex(mixed $fieldValue, string $pattern): bool
    {
        $subject = (string) $fieldValue;
        $delimiter = '#';
        if (str_contains($pattern, $delimiter)) {
            $delimiter = '~';
        }
        $regex = $delimiter . $pattern . $delimiter . 'u';
        $result = @preg_match($regex, $subject);
        return $result === 1;
    }
}
