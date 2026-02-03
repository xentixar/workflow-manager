@php
    $transitions = $workflow->transitions()->with(['fromState', 'toState', 'conditions'])->get();
    $includeParent = config('workflow-manager.include_parent', false);

    $conditionLine = function ($c) {
        $field = str_replace('_', ' ', $c->field);
        $valueType = $c->value_type ?? 'static';
        $valuePart = ($valueType === 'dynamic' && filled($c->base_field))
            ? 'value of ' . str_replace('_', ' ', $c->base_field)
            : $c->value;

        $operatorPhrase = match ($c->operator) {
            '=' => 'is equal to',
            '!=' => 'is not equal to',
            '>' => 'is greater than',
            '<' => 'is less than',
            '>=' => 'is greater than or equal to',
            '<=' => 'is less than or equal to',
            'in' => 'is one of',
            'like' => 'matches pattern',
            'regex' => 'matches regex',
            default => $c->operator,
        };

        return $field . ' ' . $operatorPhrase . ' ' . $valuePart;
    };

    $nodes = [];
    $edges = [];
    $seenStates = [];

    foreach ($transitions as $transition) {
        $toState = $transition->toState;
        $fromState = $transition->fromState;

        if (! $toState) {
            continue;
        }

        $toKey = 'state_' . $toState->id;
        $toLabel = $toState->label ?? $toState->state;
        if (! isset($seenStates[$toKey])) {
            $seenStates[$toKey] = true;
            $nodes[] = ['id' => $toKey, 'label' => $toLabel, 'type' => 'state'];
        }

        if ($fromState) {
            $fromKey = 'state_' . $fromState->id;
            $fromLabel = $fromState->label ?? $fromState->state;
            if (! isset($seenStates[$fromKey])) {
                $seenStates[$fromKey] = true;
                $nodes[] = ['id' => $fromKey, 'label' => $fromLabel, 'type' => 'state'];
            }

            $conditions = $transition->conditions;
            if ($conditions->isNotEmpty()) {
                $prevKey = $fromKey;
                foreach ($conditions as $i => $c) {
                    $edgeLabel = $i === 0 ? null : strtoupper($c->logical_group ?? 'AND');
                    $condLabel = $conditionLine($c);
                    $condKey = 'cond_' . $transition->id . '_' . $i;
                    $nodes[] = [
                        'id' => $condKey,
                        'label' => $condLabel,
                        'type' => 'condition',
                    ];
                    $edges[] = [
                        'source' => $prevKey,
                        'target' => $condKey,
                        'edgeLabel' => $edgeLabel,
                        'hasConditions' => true,
                    ];
                    $prevKey = $condKey;
                }
                $edges[] = ['source' => $prevKey, 'target' => $toKey, 'edgeLabel' => null, 'hasConditions' => true];
            } else {
                $edges[] = ['source' => $fromKey, 'target' => $toKey, 'edgeLabel' => null, 'hasConditions' => false];
            }

            if ($includeParent) {
                $edges[] = ['source' => $toKey, 'target' => $fromKey, 'reverse' => true, 'edgeLabel' => null, 'hasConditions' => false];
            }
        } else {
            $startId = 'start';
            if (! isset($seenStates[$startId])) {
                $seenStates[$startId] = true;
                $nodes[] = ['id' => $startId, 'label' => 'Start', 'type' => 'start'];
            }
            $edges[] = ['source' => $startId, 'target' => $toKey, 'edgeLabel' => null, 'hasConditions' => false];
        }
    }

    $graphData = ['nodes' => array_values($nodes), 'edges' => $edges];
@endphp

<div
    x-data="workflowDiagram(@js($graphData))"
    x-init="mount()"
    class="workflow-diagram"
>
    <div class="workflow-diagram__header">
        <span class="workflow-diagram__title">Workflow diagram</span>
        <div class="workflow-diagram__toolbar">
            <button type="button" @click="fit()" class="workflow-diagram__btn">Fit</button>
            <button type="button" @click="resetZoom()" class="workflow-diagram__btn">Reset zoom</button>
        </div>
    </div>
    <div x-ref="container" class="workflow-diagram__canvas"></div>
    <div class="workflow-diagram__footer">
        States (blue) · Conditions (orange) · Edges show AND/OR · Drag to pan, scroll to zoom
    </div>
</div>
