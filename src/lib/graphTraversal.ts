import taxonomyGraphData from '../data/taxonomyGraph.json';

export interface GraphNode {
    id: string;
    type: 'pillar' | 'sector' | 'industry' | 'persona' | 'orgType';
    label: string;
    url?: string;
    attributes: {
        slug?: string;
        description?: string;
        features?: string[];
        techStack?: string[];
        proofPoints?: Array<{ client: string; result: string }>;
        problems?: Array<{ code: string; label: string; solution: string }>;
        kpis?: Array<{ metric: string; expectedRoi: string }>;
        roiCases?: Array<{ title: string; description?: string; metrics?: Array<{ value: string; label: string }> }>;
        regulations?: string[];
        roleEquivalents?: Record<string, string>;
        objectionResponses?: Array<{ objection: string; response: string }>;
        decisionCriteria?: string[];
    };
}

export interface GraphEdge {
    source: string;
    target: string;
    relation: string;
    weight: number;
    metadata?: Record<string, any>;
}

export interface TaxonomyGraphSSOT {
    version: string;
    generatedAt: string;
    stats: {
        totalNodes: number;
        totalEdges: number;
        nodesByType: Record<string, number>;
    };
    nodes: Record<string, GraphNode>;
    edges: GraphEdge[];
}

const graph = taxonomyGraphData as unknown as TaxonomyGraphSSOT;

export function getTaxonomyGraph(): TaxonomyGraphSSOT {
    return graph;
}

const nodeList = Object.values(graph.nodes);
const corpusTexts = nodeList.map(n => {
    let extra = '';
    if (n.type === 'persona' && n.attributes.slug) extra += ` ${n.attributes.slug}`;
    if (n.attributes.description) extra += ` ${n.attributes.description}`;
    return `[${n.type.toUpperCase()}] ${n.label}${extra}`;
});

export function getGraphCorpusTexts(): string[] {
    return corpusTexts;
}

export function getGraphNodeList(): GraphNode[] {
    return nodeList;
}

/**
 * Traversal BFS Enfocado a k saltos desde un conjunto de nodos ancla.
 * Garantiza que la expansión desde hubs persona NO arrastre sectores ajenos.
 */
export function traverseTaxonomyGraph(anchorNodeIds: string[], maxHops: number = 2) {
    const visitedNodes = new Set<string>();
    const activeEdges: GraphEdge[] = [];
    const queue: Array<{ nodeId: string; distance: number }> = [];

    const anchorSectors = new Set<string>();
    anchorNodeIds.forEach(id => {
        if (graph.nodes[id]) {
            visitedNodes.add(id);
            queue.push({ nodeId: id, distance: 0 });
            if (graph.nodes[id].type === 'sector') {
                anchorSectors.add(id);
            }
        }
    });

    while (queue.length > 0) {
        const { nodeId, distance } = queue.shift()!;
        const currentNode = graph.nodes[nodeId];

        if (distance >= maxHops) continue;

        graph.edges.forEach(edge => {
            let neighborId: string | null = null;
            if (edge.source === nodeId) {
                neighborId = edge.target;
            } else if (edge.target === nodeId) {
                neighborId = edge.source;
            }

            if (neighborId && graph.nodes[neighborId]) {
                const neighborNode = graph.nodes[neighborId];

                // FOCO DE NAVEGACIÓN (Defensa en profundidad):
                // 1. Si el vecino es un SECTOR: solo permitirlo si está entre los sectores explícitamente anclados.
                //    Si no hay sector anclado en la consulta, NO navegar hacia sectores desde hubs persona.
                if (neighborNode.type === 'sector') {
                    if (!anchorSectors.has(neighborId)) {
                        return; // Omitir sectores ajenos
                    }
                }

                // 2. Si el nodo actual es una persona (hub), limitar la expansión a pilares y al sector anclado
                if (currentNode.type === 'persona' && neighborNode.type === 'sector' && !anchorSectors.has(neighborId)) {
                    return;
                }

                activeEdges.push(edge);
                if (!visitedNodes.has(neighborId)) {
                    visitedNodes.add(neighborId);
                    queue.push({ nodeId: neighborId, distance: distance + 1 });
                }
            }
        });
    }

    const resultNodes = Array.from(visitedNodes).map(id => graph.nodes[id]);
    return {
        anchors: anchorNodeIds,
        nodes: resultNodes,
        edges: activeEdges
    };
}

/**
 * Construye el subgrafo de contexto y extrae los hechos fundamentados (Grounded Facts)
 */
export function buildSubgraphContext(anchorNodeIds: string[], maxHops: number = 2) {
    const traversal = traverseTaxonomyGraph(anchorNodeIds, maxHops);
    const citedUrlsSet = new Set<string>();

    const solutions: string[] = [];
    const sectorContexts: string[] = [];
    const personaPainsAndObjections: Array<{ objection: string; response: string }> = [];
    const kpisAndRoi: Array<{ metric: string; expectedRoi: string; tagged: '[EST]' }> = [];

    traversal.nodes.forEach(node => {
        if (node.url) {
            citedUrlsSet.add(node.url);
        }

        if (node.type === 'pillar') {
            solutions.push(`Solución "${node.label}": ${node.attributes.description || ''}. Características: ${(node.attributes.features || []).join(', ')}.`);
        } else if (node.type === 'sector') {
            sectorContexts.push(`Sector "${node.label}": ${node.attributes.description || ''}.`);
            if (node.attributes.kpis) {
                node.attributes.kpis.forEach(k => {
                    kpisAndRoi.push({
                        metric: k.metric,
                        expectedRoi: k.expectedRoi,
                        tagged: '[EST]'
                    });
                });
            }
            if (node.attributes.roiCases) {
                node.attributes.roiCases.forEach(rc => {
                    if (rc.metrics) {
                        rc.metrics.forEach(m => {
                            kpisAndRoi.push({
                                metric: `${rc.title}: ${m.label}`,
                                expectedRoi: m.value,
                                tagged: '[EST]'
                            });
                        });
                    }
                });
            }
        } else if (node.type === 'persona') {
            if (node.attributes.objectionResponses) {
                node.attributes.objectionResponses.forEach(obj => {
                    personaPainsAndObjections.push({
                        objection: obj.objection,
                        response: obj.response
                    });
                });
            }
        }
    });

    const citedUrls = Array.from(citedUrlsSet);

    return {
        anchors: traversal.anchors,
        visitedNodes: traversal.nodes,
        activeEdges: traversal.edges,
        citedUrls,
        hasSufficientData: traversal.nodes.length > 0 && (solutions.length > 0 || sectorContexts.length > 0),
        groundedFacts: {
            solutions,
            sectorContexts,
            personaPainsAndObjections,
            kpisAndRoi
        }
    };
}

/**
 * Anclaje de Nodos Estructurado por SLOTS (Persona + Sector + Pilar)
 * Prioriza tokens de código de rol exacto (CFO, CIO, CEO) para evitar falsos positivos de roles secundarios.
 */
export function findAnchorNodesFallback(query: string): string[] {
    const qLower = query.toLowerCase();

    // Detección directa por token de código de rol exacto (máxima prioridad)
    const roleTokens: Record<string, string> = {
        'cfo': 'persona-cfo',
        'cio': 'persona-cio',
        'cdo': 'persona-cdo',
        'ceo': 'persona-ceo',
        'ciso': 'persona-ciso',
        'cto': 'persona-cto',
        'coo': 'persona-coo'
    };

    let exactRoleNodeId: string | null = null;
    const wordsInQuery = qLower.split(/\s+/).map(w => w.replace(/[^a-z0-9]/g, ''));
    for (const w of wordsInQuery) {
        if (roleTokens[w]) {
            exactRoleNodeId = roleTokens[w];
            break;
        }
    }

    // Diccionario léxico de alias para roles y sectores de la taxonomía real
    const aliases: Record<string, string[]> = {
        'persona-cfo': ['cfo', 'director financiero', 'gerente financiero', 'financiero'],
        'persona-cio': ['cio', 'director it', 'sistemas', 'director de sistemas'],
        'persona-ceo': ['ceo', 'director general', 'gerente general', 'ejecutivo'],
        'persona-cdo': ['cdo', 'director de datos', 'data officer'],
        'persona-ciso': ['ciso', 'ciberseguridad', 'director de seguridad'],
        'sector-finanzas': ['banco', 'banca', 'finanzas', 'financiero', 'crediticio', 'entidad crediticia'],
        'sector-salud': ['salud', 'clinica', 'hospital', 'medico'],
        'sector-retail': ['retail', 'comercio', 'tienda', 'supermercado'],
        'sector-mineria': ['mineria', 'minera', 'extractiva'],
        'sector-manufactura': ['manufactura', 'fabrica', 'industrial', 'planta'],
        'sector-educacion': ['educacion', 'universidad', 'colegio', 'educativo'],
        'sector-seguros': ['seguros', 'aseguradora'],
        'sector-telecomunicaciones': ['telecom', 'telecomunicaciones', 'telco']
    };

    let bestPersona: { id: string; score: number } | null = null;
    let bestSector: { id: string; score: number } | null = null;
    let bestPillar: { id: string; score: number } | null = null;

    // Si hay un token de rol exacto (ej. CFO), asignarlo inmediatamente con score máximo
    if (exactRoleNodeId && graph.nodes[exactRoleNodeId]) {
        bestPersona = { id: exactRoleNodeId, score: 2.0 };
    }

    nodeList.forEach(node => {
        let score = 0;
        const labelLower = node.label.toLowerCase();
        const descLower = (node.attributes.description || '').toLowerCase();
        const slugLower = (node.attributes.slug || '').toLowerCase();

        // 1. Alias exactos
        const nodeAliases = aliases[node.id] || [];
        nodeAliases.forEach(alias => {
            if (qLower.includes(alias)) {
                score += 0.85;
            }
        });

        // 2. Coincidencia por etiqueta / slug
        if (qLower.includes(labelLower)) {
            score += 0.75;
        } else if (slugLower && qLower.includes(slugLower)) {
            score += 0.65;
        }

        // 3. Coincidencia por palabras (ruido de descripción reducido para personas)
        const words = qLower.split(/\s+/).filter(w => w.length > 3);
        words.forEach(w => {
            if (labelLower.includes(w)) score += 0.2;
            if (descLower.includes(w)) {
                score += (node.type === 'persona') ? 0.01 : 0.05;
            }
        });

        if (score >= 0.35) {
            if (node.type === 'persona') {
                if (!bestPersona || score > bestPersona.score) {
                    bestPersona = { id: node.id, score };
                }
            } else if (node.type === 'sector') {
                if (!bestSector || score > bestSector.score) {
                    bestSector = { id: node.id, score };
                }
            } else if (node.type === 'pillar') {
                if (!bestPillar || score > bestPillar.score) {
                    bestPillar = { id: node.id, score };
                }
            }
        }
    });

    const anchors: string[] = [];
    if (bestPersona) anchors.push(bestPersona.id);
    if (bestSector) anchors.push(bestSector.id);
    if (bestPillar) anchors.push(bestPillar.id);

    return anchors;
}
