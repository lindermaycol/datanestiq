import React, { useState, useEffect, useRef } from 'react';
import { useStore } from '@nanostores/react';
import { userChallenge, semanticHighlight, userContext, lastUserQuery, chatbotOpen } from '../../store/index';
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

    // DOM manipulation for semanticHighlight has been moved to HighlightSync.jsx

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
                // Solo si no estamos buscando o terminados activamente, para no ocultar la UI
                setStatus(prev => (prev === 'searching' || prev === 'done') ? prev : 'loading_model');
                if (wProg) setProgress(Math.round(wProg));
            } else if (wStatus === 'ready' || wStatus === 'indexed') {
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
            // Disparar indexación anticipada al detectar intención (hover/focus), sin forzar en mount (Precisión 2)
            workerRef.current.postMessage({
                type: 'index',
                corpusTexts: corpusRef.current.map(c => c.text)
            });
        }
    };

    const handleResults = (results) => {
        const highlightMap = {};
        const services = [];
        
        results.forEach(res => {
            const item = corpusRef.current[res.index];
            if (item.id.startsWith('service-')) {
                services.push({ id: item.id, score: res.score });
            } else {
                highlightMap[item.id] = res.score;
            }
        });
        
        services.sort((a, b) => b.score - a.score);
        const topServices = services.filter(s => s.score > 0.35).slice(0, 3);
        
        topServices.forEach(s => {
            highlightMap[s.id] = 1.0;
        });

        const topMatch = results[0];
        if (topMatch) {
            const topItem = corpusRef.current[topMatch.index];
            if (topItem.id.startsWith('industry-') && topMatch.score > 0.3) {
                const industryData = extendedIndustries.find(i => i.id === topItem.id);
                if (industryData) {
                    setIndustryMatch(industryData);
                    industryData.relatedPillars.slice(0, 3).forEach(pillarSlug => {
                        highlightMap[`service-${pillarSlug}`] = 1.0;
                    });
                } else {
                    setIndustryMatch(null);
                }
            } else {
                setIndustryMatch(null);
            }
        } else {
            setIndustryMatch(null);
        }

        semanticHighlight.set(highlightMap);
    };

    const handleSearch = () => {
        if (!query.trim()) return;
        
        // Save to context chaining nanostore
        userChallenge.set(query);
        setStatus('searching');

        // Enviar consulta + corpusTexts como fallback anti-race-condition (Precisión 1)
        workerRef.current.postMessage({
            type: 'search',
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
                <div className="mt-4 p-4 bg-darker/60 border border-brandCyan/20 rounded-xl text-center animate-in fade-in">
                    <div className="text-xs text-brandCyan font-mono flex items-center justify-center gap-2 mb-3">
                        <i className="ph ph-cpu animate-pulse text-sm"></i> 
                        Cargando el modelo de IA (solo la primera vez)... {progress}%
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                        {[1, 2, 3].map(i => (
                            <div key={i} className="p-3 bg-white/5 border border-white/10 rounded-lg animate-pulse">
                                <div className="h-4 bg-brandCyan/20 rounded w-3/4 mb-2"></div>
                                <div className="h-3 bg-white/10 rounded w-full mb-1"></div>
                                <div className="h-3 bg-white/10 rounded w-2/3"></div>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {status === 'searching' && (
                <div className="mt-4 p-4 bg-darker/60 border border-brandCyan/20 rounded-xl text-center animate-in fade-in">
                    <div className="text-xs text-brandCyan font-mono flex items-center justify-center gap-2 mb-3">
                        <i className="ph ph-spinner-gap animate-spin text-sm"></i> 
                        Analizando vectores semánticos...
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                        {[1, 2, 3].map(i => (
                            <div key={i} className="p-3 bg-white/5 border border-white/10 rounded-lg animate-pulse">
                                <div className="h-4 bg-brandCyan/20 rounded w-3/4 mb-2"></div>
                                <div className="h-3 bg-white/10 rounded w-full mb-1"></div>
                                <div className="h-3 bg-white/10 rounded w-2/3"></div>
                            </div>
                        ))}
                    </div>
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

            {status === 'done' && (() => {
                const serviceCount = Object.keys(highlightState).filter(k => k.startsWith('service-')).length;
                if (serviceCount === 0) return null;
                const scrollTo = (id) => { document.querySelector(id)?.scrollIntoView({ behavior: 'smooth' }); };
                const openConcierge = () => {
                    lastUserQuery.set(query);
                    chatbotOpen.set(true);
                };
                return (
                    <div className="mt-4 bg-brand/10 border border-brandCyan/20 rounded-xl p-4 text-center animate-in fade-in">
                        <p className="text-white text-sm mb-3">
                            Encontramos <strong className="text-brandCyan">{serviceCount}</strong> soluciones relevantes para tu búsqueda.
                        </p>
                        <div className="flex flex-wrap justify-center gap-3">
                            <button
                                onClick={() => scrollTo('#copilot-section')}
                                className="text-xs px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-300 hover:bg-white/10 hover:border-brandCyan/40 transition-all"
                            >
                                <i className="ph ph-terminal-window mr-1"></i> Ver cómo razona nuestra IA →
                            </button>
                            <button
                                onClick={() => scrollTo('#diagnostic-section')}
                                className="text-xs px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-gray-300 hover:bg-white/10 hover:border-brandCyan/40 transition-all"
                            >
                                <i className="ph ph-compass mr-1"></i> Iniciar Diagnóstico con este contexto →
                            </button>
                            <button
                                onClick={openConcierge}
                                className="text-xs px-4 py-2 rounded-lg bg-brandCyan/10 border border-brandCyan/30 text-brandCyan hover:bg-brandCyan/20 transition-all"
                            >
                                <i className="ph ph-robot mr-1"></i> Consultar con el AI Concierge →
                            </button>
                        </div>
                    </div>
                );
            })()}
        </div>
    );
}
