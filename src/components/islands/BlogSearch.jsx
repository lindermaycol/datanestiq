import React, { useState, useEffect, useRef } from 'react';
import blogCorpus from '../../data/blogCorpus.json';
import { WORKER_URL } from '../../lib/endpoints';
import { trackEvent } from '../../lib/session';

const SIMILARITY_THRESHOLD = 0.35; // Calibrado §2

export default function BlogSearch() {
    const [query, setQuery] = useState('');
    const [status, setStatus] = useState('idle'); // idle, loading_model, searching, done, error
    const [results, setResults] = useState([]);
    const workerRef = useRef(null);
    const hasWarmedUp = useRef(false);
    const reqCounterRef = useRef(0);
    const activeReqIdRef = useRef('');
    const activeQueryRef = useRef('');

    // Preparar array plano de strings (Contrato Real del Worker: corpusTexts)
    const corpusTextsRef = useRef(blogCorpus.map(b => b.text || `${b.title} ${b.excerpt} ${b.body_head}`));

    useEffect(() => {
        workerRef.current = new Worker(WORKER_URL, { type: 'module' });

        workerRef.current.addEventListener('message', (e) => {
            const { status: wStatus, results: wResults, id: responseId, error } = e.data;

            if (wStatus === 'initiate' || wStatus === 'progress' || wStatus === 'download') {
                setStatus('loading_model');
            } else if (wStatus === 'ready' || wStatus === 'indexed') {
                if (status === 'loading_model') setStatus('idle');
            } else if (wStatus === 'complete' || wStatus === 'results') {
                // Guard de Concurrencia: Ignorar respuestas si el ID no coincide con la búsqueda activa
                if (responseId && responseId !== activeReqIdRef.current) {
                    return;
                }

                if (wResults) {
                    // Filtrar por umbral calibrado (>= 0.35) y mapear por ÍNDICE NUMÉRICO (contrato real del worker)
                    const filtered = wResults
                        .filter(r => r.score >= SIMILARITY_THRESHOLD)
                        .slice(0, 5)
                        .map(r => {
                            const meta = blogCorpus[r.index];
                            return meta ? { ...meta, score: r.score } : null;
                        })
                        .filter(Boolean);

                    setResults(filtered);
                    setStatus('done');

                    // Spec 026: Telemetría de búsqueda con resultados reales (Timing correcto post-Worker)
                    if (activeQueryRef.current && activeQueryRef.current.trim().length > 0) {
                        trackEvent('search_query', 'blog-search', JSON.stringify({
                            q: activeQueryRef.current.trim(),
                            n: filtered.length,
                            top: filtered[0]?.score || 0
                        }));
                    }
                }
            } else if (wStatus === 'error') {
                console.error('BlogSearch Worker error:', error);
                setStatus('error');
            }
        });

        return () => {
            if (workerRef.current) {
                workerRef.current.terminate();
            }
        };
    }, []);

    const handleIntent = () => {
        if (!hasWarmedUp.current && workerRef.current) {
            hasWarmedUp.current = true;
            // Pre-indexar el corpus del blog enviando corpusTexts al worker
            workerRef.current.postMessage({
                type: 'index',
                corpusTexts: corpusTextsRef.current
            });
        }
    };

    const handleSearch = (e) => {
        const val = e.target.value;
        setQuery(val);
        activeQueryRef.current = val;

        if (!val.trim()) {
            setResults([]);
            setStatus('idle');
            return;
        }

        // Si es el primer toque, forzar indexación previa antes de la consulta
        if (!hasWarmedUp.current && workerRef.current) {
            hasWarmedUp.current = true;
            workerRef.current.postMessage({
                type: 'index',
                corpusTexts: corpusTextsRef.current
            });
        }

        setStatus('searching');

        // ID Dinámico Único por Petición (Corrección de Concurrencia)
        const reqId = `blog-search-${++reqCounterRef.current}-${Date.now()}`;
        activeReqIdRef.current = reqId;

        // Enviar búsqueda con contrato REAL del worker (corpusTexts) e ID dinámico
        workerRef.current.postMessage({
            type: 'search',
            query: val,
            corpusTexts: corpusTextsRef.current,
            id: reqId
        });
    };

    return (
        <div style={{
            background: 'rgba(18, 18, 26, 0.75)',
            backdropFilter: 'blur(12px)',
            border: '1px solid rgba(255, 255, 255, 0.1)',
            borderRadius: '16px',
            padding: '1.5rem',
            marginBottom: '2.5rem',
            boxShadow: '0 8px 32px rgba(0, 0, 0, 0.3)'
        }}>
            <div style={{ display: 'flex', gap: '0.75rem', marginBottom: results.length > 0 || status === 'done' ? '1.25rem' : '0' }}>
                <div style={{ position: 'relative', flex: 1 }}>
                    <input
                        type="text"
                        value={query}
                        onChange={handleSearch}
                        onFocus={handleIntent}
                        onMouseEnter={handleIntent}
                        placeholder="🔍 Busca artículos en lenguaje natural (ej. 'almacenamiento de datos retail', 'ciberseguridad')..."
                        style={{
                            width: '100%',
                            padding: '0.85rem 1.25rem',
                            background: 'rgba(255, 255, 255, 0.05)',
                            border: '1px solid rgba(34, 211, 238, 0.3)',
                            borderRadius: '10px',
                            color: '#fff',
                            fontSize: '0.95rem',
                            outline: 'none',
                            transition: 'border-color 0.2s, background 0.2s'
                        }}
                    />
                </div>
            </div>

            {status === 'searching' && (
                <div style={{ color: '#22d3ee', fontSize: '0.85rem', padding: '0.5rem 0' }}>
                    ⌛ Analizando publicaciones con IA local en tu navegador (0-LLM)...
                </div>
            )}

            {status === 'done' && results.length === 0 && query.trim() !== '' && (
                <div style={{
                    background: 'rgba(255, 255, 255, 0.03)',
                    border: '1px dashed rgba(255, 255, 255, 0.15)',
                    padding: '1.25rem',
                    borderRadius: '10px',
                    color: 'rgba(255, 255, 255, 0.7)',
                    fontSize: '0.9rem',
                    textAlign: 'center',
                    lineHeight: '1.5'
                }}>
                    💡 No encontramos artículos relevantes para tu búsqueda. Prueba con otros términos o consulta directamente con nuestro <strong>AI Concierge</strong>.
                </div>
            )}

            {results.length > 0 && (
                <div style={{ display: 'flex', flexDirection: 'column', gap: '0.85rem' }}>
                    <div style={{ fontSize: '0.75rem', textTransform: 'uppercase', tracking: '0.05em', color: '#888', fontWeight: 600 }}>
                        Artículos Afines ({results.length}):
                    </div>
                    {results.map(r => (
                        <a
                            key={r.slug}
                            href={r.url}
                            style={{
                                display: 'block',
                                background: 'rgba(255, 255, 255, 0.04)',
                                border: '1px solid rgba(255, 255, 255, 0.08)',
                                padding: '1rem',
                                borderRadius: '10px',
                                textDecoration: 'none',
                                transition: 'all 0.2s'
                            }}
                        >
                            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '0.35rem' }}>
                                <h4 style={{ color: '#22d3ee', margin: 0, fontSize: '1rem', fontWeight: 600 }}>
                                    {r.title}
                                </h4>
                                <span style={{ background: 'rgba(34, 211, 238, 0.12)', color: '#22d3ee', fontSize: '0.7rem', padding: '0.15rem 0.5rem', borderRadius: '4px', fontWeight: 600 }}>
                                    Match: {Math.round(r.score * 100)}%
                                </span>
                            </div>
                            <p style={{ color: '#aaa', fontSize: '0.85rem', margin: '0 0 0.5rem 0', lineHeight: '1.4' }}>
                                {r.excerpt}
                            </p>
                            <div style={{ display: 'flex', gap: '0.4rem', flexWrap: 'wrap' }}>
                                {r.tags && r.tags.map(t => (
                                    <span key={t} style={{ background: 'rgba(255, 255, 255, 0.06)', color: '#888', fontSize: '0.7rem', padding: '0.1rem 0.4rem', borderRadius: '3px' }}>
                                        #{t}
                                    </span>
                                ))}
                            </div>
                        </a>
                    ))}
                </div>
            )}
        </div>
    );
}
