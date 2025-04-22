@php
    $modelClass = $workflow->model_class;
    $states = $modelClass::getStates();

    $transitions = $workflow->transitions()->with('parent')->get();

    $nodeDefinitions = [];
    $transitionDefinitions = [];

    foreach ($transitions as $transition) {
        $toState = $states[$transition->state] ?? $transition->state;
        $toStateKey = str_replace([' ', '-'], '_', $transition->state);

        if (!in_array($toStateKey, $nodeDefinitions)) {
            $nodeDefinitions[] = $toStateKey;
        }

        if ($transition->parent) {
            $fromState = $states[$transition->parent->state] ?? $transition->parent->state;
            $fromStateKey = str_replace([' ', '-'], '_', $transition->parent->state);

            if (!in_array($fromStateKey, $nodeDefinitions)) {
                $nodeDefinitions[] = $fromStateKey;
            }

            $transitionDefinitions[] = "$fromStateKey --> $toStateKey: \"$fromState to $toState\"";
        } else {
            $transitionDefinitions[] = "[*] --> $toStateKey: \"Initial State\"";
        }
    }

    $mermaidCode = "stateDiagram-v2\n";
    foreach ($nodeDefinitions as $node) {
        $mermaidCode .= $node . ': ' . str_replace('state_', '', str_replace('_', ' ', $node)) . "\n";
    }

    foreach ($transitionDefinitions as $transition) {
        $mermaidCode .= $transition . "\n";
    }
@endphp

<div x-data="{
    diagramCode: '',
    isRendered: false,
    init(code) {
        this.diagramCode = code;
        this.renderDiagram();
    },
    renderDiagram() {
        if (this.isRendered) return;

        if (typeof mermaid !== 'undefined') {
            mermaid.initialize({
                startOnLoad: false,
                theme: 'neutral',
                securityLevel: 'loose'
            });

            mermaid.render('workflow-diagram', this.diagramCode)
                .then(({ svg }) => {
                    this.$refs.diagramContainer.innerHTML = svg;
                    this.isRendered = true;
                })
                .catch(error => {
                    console.error('Failed to render diagram:', error);
                    this.$refs.diagramContainer.innerHTML = '<div class=\'p-4 text-red-500 font-medium\'>Failed to render workflow diagram</div>';
                });
        } else {
            console.error('Mermaid library not loaded');
            this.$refs.diagramContainer.innerHTML = '<div class=\'p-4 text-red-500 font-medium\'>Mermaid library not loaded</div>';
        }
    }
}" x-init="init(@js($mermaidCode))" class="flex flex-col items-center justify-center p-4">
    <div x-ref="diagramContainer" class="w-full flex justify-center"></div>
</div>
