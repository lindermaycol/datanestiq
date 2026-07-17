import React, { useState, useEffect, useRef } from 'react';
import { useStore } from '@nanostores/react';
import { userChallenge, semanticHighlight, userContext } from '../../store/index';
import taxonomyCorpus from '../../data/taxonomyCorpus.json';
import sectorsCorpus from '../../data/sectorsCorpus.json';
import extendedIndustries from '../../data/extendedIndustries.json';
import { WORKER_URL } from '../../lib/endpoints';

export default function SemanticSearch() {
    const [query, setQuery] = useState('');
    const [status, setStatus] = useState('idle'); // idle, loading_model, searching, done, error
    const [progress, setProgress] = useState(0);
    const workerRef = useRef(null);
    const corpusRef = useRef([]);
    const hasWarmedUp = useRef(false);
    const [industryMatch, setIndustryMatch] = useState(null);
    const highlightState = useStore(semanticHighlight);
    const contextState = useStore(userContext);

    // Apply highlight from store
    useEffect(() => {
        if (!highlightState || Object.keys(highlightState).length === 0) return;
        
        const serviceCards = document.querySelectorAll('.service-card');
        if (!serviceCards.length) return;
        
        const threshold = 0.2;
        serviceCards.forEach(card => {
            const sId = card.getAttribute('data-service-id');
            const score = highlightState[`service-${sId}`] || 0;
            
            if (score >= threshold) {
                card.style.opacity = '1';
                card.style.transform = 'scale(1.02)';
                card.style.borderColor = 'var(--brand-cyan)';
                card.style.boxShadow = '0 0 20px rgba(0, 240, 255, 0.1)';
            } else {
                card.style.opacity = '0.3';
                card.style.transform = 'scale(0.98)';
                card.style.borderColor = 'rgba(255,255,255,0.1)';
                card.style.boxShadow = 'none';
            }
        });
    }, [highlightState]);

    // Prepare corpus
    useEffect(() => {
        const buildCorpus = () => {
            const arr = [];
            taxonomyCorpus.forEach(item => {
                const techStr = item.techStack ? item.techStack.join(' ') : '';
                arr.push({
                    id: `service-${item.slug}`,
                    text: `${item.name} ${item.hero.headline} ${item.hero.subheadline} ${item.contrast.problem} ${item.contrast.solution} ${item.features.join(' ')} ${techStr}`
                });
            });
            sectorsCorpus.forEach(item => {
                const subStr = item.subSectors ? item.subSectors.join(' ') : '';
                const regStr = item.regulations ? item.regulations.join(' ') : '';
                arr.push({
                    id: item.id,
                    text: `${item.title} ${item.description} ${subStr} ${regStr}`
                });
            });
            extendedIndustries.forEach(item => {
                arr.push({
                    id: item.id,
                    text: `${item.title} ${item.keywords.join(' ')}`
                });
            });
            return arr;
        };
        corpusRef.current = buildCorpus();
    }, []);

    useEffect(() => {
        workerRef.current = new Worker(WORKER_URL, { type: 'module' });

        workerRef.current.addEventListener('message', (e) => {
            const { status: wStatus, progress: wProg, results, error } = e.data;

            if (wStatus === 'initiate' || wStatus === 'progress' || wStatus === 'download') {
                // Solo si no estamos buscando activamente, para no ocultar la UI
                setStatus(prev => prev === 'searching' ? 'searching' : 'loading_model');
                if (wProg) setProgress(Math.round(wProg));
            } else if (wStatus === 'ready') {
                setStatus('ready');
            } else if (wStatus === 'complete') {
                setStatus('done');
                handleResults(results);
            } else if (wStatus === 'error') {
                console.error('Semantic Search Error:', error);
                setStatus('error');
            }
        });



        return () => {
            if (workerRef.current) workerRef.current.terminate();
        };
    }, []);

    const handleIntent = () => {
        if (hasWarmedUp.current) return;
        
        if (typeof navigator !== 'undefined') {
            const conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
            const saveData = conn?.saveData === true;
            const et = conn?.effectiveType || '';
            const slow = conn && (et === 'slow-2g' || et === '2g' || et === '3g');
            
            if (saveData || slow) return; // Skip warmup on limited connections
        }

        hasWarmedUp.current = true;
        if (workerRef.current) {
            workerRef.current.postMessage({ type: 'warmup' });
        }
    };

    const handleResults = (results) => {
        const highlightMap = {};
        
        // results is array of { index, score }
        // The higher the score, the better the match. Typically score > 0.3 is relevant.
        results.forEach(res => {
            const item = corpusRef.current[res.index];
            highlightMap[item.id] = res.score;
        });

        // 1. Publish to store for React components (like DiagnosticWizard sectors)
        semanticHighlight.set(highlightMap);

        // 2. Manipulate DOM for static Astro components (Services)
        // Find the top 2 matching services to highlight, dim the rest
        const serviceMatches = results
            .map(r => ({ id: corpusRef.current[r.index].id, score: r.score }))
            .filter(r => r.id.startsWith('service-'));
        
        // Find if an extended industry is the top match
        const topMatch = results[0];
        if (topMatch) {
            const topItem = corpusRef.current[topMatch.index];
            if (topItem.id.startsWith('industry-') && topMatch.score > 0.3) {
                const industryData = extendedIndustries.find(i => i.id === topItem.id);
                if (industryData) {
                    setIndustryMatch(industryData);
                    // Override service matches with industry's related pillars
                    industryData.relatedPillars.forEach(pillarSlug => {
                        highlightMap[`service-${pillarSlug}`] = topMatch.score;
                        const existingMatch = serviceMatches.find(m => m.id === `service-${pillarSlug}`);
                        if (!existingMatch) {
                            serviceMatches.push({ id: `service-${pillarSlug}`, score: topMatch.score });
                        }
                    });
                }
            } else {
                setIndustryMatch(null);
            }
        } else {
            setIndustryMatch(null);
        }

        // Find max score among services
        const maxScore = Math.max(...serviceMatches.map(s => s.score));
        const threshold = Math.max(0.2, maxScore * 0.75); // Dynamic threshold
    };

    const handleSearch = () => {
        if (!query.trim()) return;
        
        // Save to context chaining nanostore
        userChallenge.set(query);
        setStatus('searching');

        workerRef.current.postMessage({
            query: query,
            corpusTexts: corpusRef.current.map(c => c.text),
            id: Date.now()
        });
    };

    const clearSearch = () => {
        setQuery('');
        setStatus('idle');
        semanticHighlight.set({});
        userChallenge.set('');
        setIndustryMatch(null);
        
        const serviceCards = document.querySelectorAll('.service-card');
        serviceCards.forEach(card => {
            card.style.opacity = '1';
            card.style.transform = 'none';
            card.style.borderColor = '';
            card.style.boxShadow = '';
        });
    };

    return (
        <div className="max-w-3xl mx-auto py-10 px-4" onMouseEnter={handleIntent}>
            <div className="text-center mb-6">
                <h2 className="text-2xl font-bold text-white mb-2">Buscador Semántico impulsado por IA</h2>
                <p className="text-gray-400 text-sm">Describe tu problema o desafío en lenguaje natural. Nuestra IA vectorizará tu petición y encontrará las soluciones y sectores exactos para ti.</p>
            </div>
            
            <div className="relative flex items-center">
                <div className="absolute left-4 text-brandCyan">
                    <i className="ph ph-magnifying-glass text-xl"></i>
                </div>
                <input 
                    type="text" 
                    value={query}
                    onChange={(e) => setQuery(e.target.value)}
                    onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
                    onFocus={handleIntent}
                    placeholder="Ej: Tengo problemas con la retención de clientes en mi e-commerce..."
                    className="w-full bg-darker border border-brand/40 focus:border-brandCyan rounded-xl py-4 pl-12 pr-24 text-white shadow-[0_0_15px_rgba(0,240,255,0.1)] focus:shadow-[0_0_25px_rgba(0,240,255,0.2)] transition-all outline-none"
                />
                
                {query && (
                    <button 
                        onClick={clearSearch}
                        className="absolute right-14 text-gray-400 hover:text-white"
                    >
                        <i className="ph ph-x"></i>
                    </button>
                )}

                <button 
                    onClick={handleSearch}
                    disabled={status === 'loading_model' || status === 'searching'}
                    className="absolute right-3 bg-brand/20 hover:bg-brand/40 text-brandCyan p-2 rounded-lg transition-colors disabled:opacity-50"
                >
                    {(status === 'loading_model' || status === 'searching') ? (
                        <i className="ph ph-spinner-gap animate-spin text-lg"></i>
                    ) : (
                        <i className="ph ph-arrow-right text-lg"></i>
                    )}
                </button>
            </div>

            {status === 'loading_model' && (
                <div className="text-center mt-3 text-xs text-gray-400 flex items-center justify-center gap-2">
                    <i className="ph ph-cpu animate-pulse"></i> 
                    Cargando modelo de embeddings (Edge AI)... {progress}%
                </div>
            )}

            {status === 'ready' && (
                <div className="text-center mt-3 text-xs text-brandCyan/70 flex items-center justify-center gap-2">
                    <i className="ph ph-check-circle"></i> IA lista para búsqueda instantánea
                </div>
            )}
            
            {status === 'done' && industryMatch && (
                <div className="text-center mt-3 text-sm text-white/90 bg-brand/20 py-2 px-4 rounded-lg inline-block mx-auto border border-brandCyan/30">
                    Para el sector <strong className="text-brandCyan">{industryMatch.title}</strong>, recomendamos estas soluciones:
                </div>
            )}
            
            {status === 'done' && !industryMatch && (
                <div className="text-center mt-3 text-xs text-brandCyan font-mono">
                    ✓ Resultados filtrados semánticamente
                </div>
            )}
        </div>
    );
}
