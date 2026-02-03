
document.addEventListener('alpine:init', () => {
    Alpine.data('workflowDiagram', (graphData) => ({
        graphData,
        cy: null,
        isRendered: false,

        mount() {
            if (this.isRendered) return;
            const container = this.$refs.container;
            if (!container || !this.graphData || !this.graphData.nodes.length) {
                if (container) container.innerHTML = '<div class="workflow-diagram__empty">No states or transitions yet. Add states and transitions to see the diagram.</div>';
                return;
            }

            if (typeof cytoscape === 'undefined') {
                container.innerHTML = '<div class="workflow-diagram__error">Cytoscape diagram library not loaded. Ensure the workflow-manager assets are registered.</div>';
                return;
            }

            // Defer so modal/container has layout (height) before Cytoscape measures it
            requestAnimationFrame(() => {
                if (this.isRendered) return;
                this.doRender(container);
            });
        },

        doRender(container) {
            if (this.isRendered || !container) return;
            const graphData = this.graphData;
            if (!graphData || !graphData.nodes.length) return;

            const elements = [];
            graphData.nodes.forEach(n => {
                let label = (typeof n.label === 'string' ? n.label : (n.label || ''));
                label = label.replace(/\u2028/g, '\n').replace(/\[\[NL\]\]/g, '\n').replace(/\\n/g, '\n');
                elements.push({
                    group: 'nodes',
                    data: { id: n.id, label, type: n.type || 'state' },
                });
            });
            graphData.edges.forEach((e, i) => {
                elements.push({
                    group: 'edges',
                    data: {
                        id: `e${i}`,
                        source: e.source,
                        target: e.target,
                        reverse: e.reverse || false,
                        label: e.edgeLabel || '',
                        hasConditions: e.hasConditions === true ? 'true' : 'false',
                    },
                });
            });

            this.cy = cytoscape({
                container,
                elements,
                style: [
                    {
                        selector: 'node[type="state"]',
                        style: {
                            'label': 'data(label)',
                            'text-valign': 'center',
                            'text-halign': 'center',
                            'background-color': '#2563eb',
                            'color': '#fff',
                            'shape': 'round-rectangle',
                            'width': 'label',
                            'height': 'label',
                            'padding': '14px',
                            'text-wrap': 'wrap',
                            'text-max-width': 200,
                            'font-size': '13px',
                            'font-family': 'system-ui, -apple-system, sans-serif',
                            'border-width': 2,
                            'border-color': '#1d4ed8',
                        },
                    },
                    {
                        selector: 'node[type="start"]',
                        style: {
                            'label': 'data(label)',
                            'text-valign': 'center',
                            'text-halign': 'center',
                            'background-color': '#059669',
                            'color': '#fff',
                            'shape': 'ellipse',
                            'width': 'label',
                            'height': 'label',
                            'padding': '12px',
                            'font-size': '13px',
                            'font-family': 'system-ui, -apple-system, sans-serif',
                            'border-width': 2,
                            'border-color': '#047857',
                        },
                    },
                    {
                        selector: 'node[type="condition"]',
                        style: {
                            'label': 'data(label)',
                            'text-valign': 'center',
                            'text-halign': 'center',
                            'background-color': '#d97706',
                            'color': '#fff',
                            'shape': 'round-rectangle',
                            'width': 'label',
                            'height': 'label',
                            'padding': '14px',
                            'text-wrap': 'wrap',
                            'text-max-width': 160,
                            'font-size': '11px',
                            'line-height': 1.5,
                            'font-family': 'system-ui, -apple-system, sans-serif',
                            'border-width': 2,
                            'border-color': '#b45309',
                        },
                    },
                    {
                        selector: 'edge[reverse="true"]',
                        style: {
                            'width': 2,
                            'line-color': '#94a3b8',
                            'target-arrow-color': '#94a3b8',
                            'target-arrow-shape': 'triangle',
                            'curve-style': 'bezier',
                            'line-style': 'dashed',
                            'label': 'data(label)',
                            'font-size': '11px',
                            'text-margin-y': -8,
                            'color': '#64748b',
                            'text-background-color': '#fff',
                            'text-background-opacity': 1,
                            'text-background-padding': '6px',
                        },
                    },
                    {
                        selector: 'edge[hasConditions="true"]',
                        style: {
                            'width': 2.5,
                            'line-color': '#475569',
                            'target-arrow-color': '#475569',
                            'target-arrow-shape': 'triangle',
                            'curve-style': 'bezier',
                            'line-style': 'dashed',
                            'label': 'data(label)',
                            'font-size': '11px',
                            'text-margin-y': -8,
                            'color': '#475569',
                            'text-background-color': '#fff',
                            'text-background-opacity': 1,
                            'text-background-padding': '6px',
                        },
                    },
                    {
                        selector: 'edge[hasConditions="false"]',
                        style: {
                            'width': 2.5,
                            'line-color': '#475569',
                            'target-arrow-color': '#475569',
                            'target-arrow-shape': 'triangle',
                            'curve-style': 'bezier',
                            'line-style': 'solid',
                            'label': 'data(label)',
                            'font-size': '11px',
                            'text-margin-y': -8,
                            'color': '#475569',
                            'text-background-color': '#fff',
                            'text-background-opacity': 1,
                            'text-background-padding': '6px',
                        },
                    },
                    {
                        selector: 'edge',
                        style: {
                            'width': 2.5,
                            'line-color': '#475569',
                            'target-arrow-color': '#475569',
                            'target-arrow-shape': 'triangle',
                            'curve-style': 'bezier',
                            'label': 'data(label)',
                            'font-size': '11px',
                            'text-margin-y': -8,
                            'color': '#475569',
                            'text-background-color': '#fff',
                            'text-background-opacity': 1,
                            'text-background-padding': '6px',
                        },
                    },
                ],
                minZoom: 0.2,
                maxZoom: 3,
                wheelSensitivity: 0.3,
            });

            const layoutOpts = {
                name: 'cose',
                animate: false,
                fit: true,
                padding: 60,
                nodeRepulsion: 100000,
                idealEdgeLength: 30,
                edgeElasticity: 200,
                nestingFactor: 1.2,
                numIter: 1000,
            };
            const doFit = () => {
                if (this.cy) this.cy.fit(60);
            };
            this.cy.one('layoutstop', doFit);
            this.cy.layout(layoutOpts).run();
            setTimeout(doFit, 100);
            setTimeout(doFit, 400);

            this.isRendered = true;
        },

        fit() {
            if (this.cy) this.cy.fit(40);
        },

        resetZoom() {
            if (this.cy) {
                this.cy.zoom(1);
                this.cy.center();
            }
        },

        downloadImage() {
            if (!this.cy) return;
            try {
                const dataUri = this.cy.png({ full: true, scale: 1 });
                if (!dataUri) return;
                const link = document.createElement('a');
                link.href = dataUri;
                link.download = 'workflow-diagram.png';
                link.click();
            } catch (e) {
                console.error('Workflow diagram export failed:', e);
            }
        },
    }));
});
