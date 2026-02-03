@php
    use Xentixar\WorkflowManager\Resources\WorkflowResource;
    $transitions = $workflow->transitions()->with(['fromState', 'toState', 'conditions'])->get();
    $transitionsUrl = WorkflowResource::getUrl('transitions', ['record' => $workflow]);
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
            <a href="{{ $transitionsUrl }}" class="workflow-diagram__btn workflow-diagram__btn--link">
                <svg class="workflow-diagram__btn-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                </svg>
                <span>Manage transitions</span>
            </a>
            <button type="button" @click="fit()" class="workflow-diagram__btn" title="Fit diagram in view">
                <svg class="workflow-diagram__btn-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" />
                </svg>
                <span>Fit</span>
            </button>
            <button type="button" @click="resetZoom()" class="workflow-diagram__btn" title="Reset zoom">
                <svg class="workflow-diagram__btn-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                <span>Reset zoom</span>
            </button>
            <button type="button" @click="downloadImage()" class="workflow-diagram__btn" title="Download as PNG">
                <svg class="workflow-diagram__btn-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                <span>Download image</span>
            </button>
        </div>
    </div>
    <div x-ref="container" class="workflow-diagram__canvas"></div>
    <div class="workflow-diagram__footer">
        States (blue) · Conditions (orange) · Edges show AND/OR · Drag to pan, scroll to zoom
    </div>
</div>
