@php
    $states = $workflow->states()->get()->keyBy('id');
    
    $transitions = $workflow->transitions()->with(['fromState', 'toState'])->get();

    $nodeDefinitions = [];
    $transitionDefinitions = [];

    foreach ($transitions as $transition) {
        $toState = $transition->toState;
        $fromState = $transition->fromState;

        if ($toState) {
            $toStateKey = str_replace([' ', '-'], '_', $toState->state);
            $toStateLabel = $toState->label ?? $toState->state;

            if (!array_key_exists($toStateKey, $nodeDefinitions)) {
                $nodeDefinitions[$toStateKey] = $toStateLabel;
            }

            if ($fromState) {
                $fromStateKey = str_replace([' ', '-'], '_', $fromState->state);
                $fromStateLabel = $fromState->label ?? $fromState->state;

                if (!array_key_exists($fromStateKey, $nodeDefinitions)) {
                    $nodeDefinitions[$fromStateKey] = $fromStateLabel;
                }

                $transitionDefinitions[] = "$fromStateKey --> $toStateKey: \"$fromStateLabel to $toStateLabel\"";
            } else {
                $transitionDefinitions[] = "[*] --> $toStateKey: \"Initial State\"";
            }
        }
    }

    $mermaidCode = "stateDiagram-v2\n";
    foreach ($nodeDefinitions as $key => $label) {
        $mermaidCode .= $key . ': ' . addslashes($label) . "\n";
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
