@php
    $states = $workflow->states()->get()->keyBy('id');
    $transitions = $workflow->transitions()->with(['fromState', 'toState'])->get();

    $nodeLabels = [];
    $transitionLines = [];

    foreach ($transitions as $transition) {
        $toState = $transition->toState;
        $fromState = $transition->fromState;

        if ($toState) {
            $toKey = 'state_' . str_replace([' ', '-'], '_', $toState->state);
            $toLabel = $toState->label ?? $toState->state;
            $nodeLabels[$toKey] = $toLabel;

            if ($fromState) {
                $fromKey = 'state_' . str_replace([' ', '-'], '_', $fromState->state);
                $fromLabel = $fromState->label ?? $fromState->state;
                $nodeLabels[$fromKey] = $fromLabel;

                $transitionLines[] = $fromKey . ' --> ' . $toKey;
            } else {
                $transitionLines[] = 'start((Start)) --> ' . $toKey;
            }
        }
    }

    $mermaidCode = "graph TD\n";
    foreach ($nodeLabels as $key => $label) {
        $labelEscaped = addslashes($label);
        $mermaidCode .= $key . '["' . $labelEscaped . "\"]\n";
    }
    foreach ($transitionLines as $line) {
        $mermaidCode .= $line . "\n";
    }
@endphp

<div 
    x-data="{
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
    }"
    x-init="init(@js($mermaidCode))"
    class="flex flex-col items-center justify-center p-4"
>
    <div x-ref="diagramContainer" class="w-full flex justify-center"></div>
</div>
