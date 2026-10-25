import React, { useState, useEffect } from 'react';
import { useStore } from '@nanostores/react';
import { userChallenge } from '../../store/index';
import { SAVE_WIZARD_API } from '../../lib/endpoints';
import taxonomyCorpus from '../../data/taxonomyCorpus.json';
export default function MultiStepWizard() {
    // Fase D: Context-Aware Chaining
    const challengeContext = useStore(userChallenge);
    
    // States: 1 (Reto), 2 (Stack/Tech), 3 (Lead Capture), 4 (Result)
    const [step, setStep] = useState(1);
    
    // Form data
    const [reto, setReto] = useState('');
    const [stack, setStack] = useState('');
    const [lead, setLead] = useState({ nombre: '', email: '', organizacion: '' });
    const [score, setScore] = useState(0);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [recommendedPillar, setRecommendedPillar] = useState(null);

    useEffect(() => {
        // Bypass step 1 if we have a context challenge from Semantic Search
        if (challengeContext && challengeContext.trim() !== '') {
            setReto(challengeContext);
            setStep(2);
        }
    }, [challengeContext]);

    const handleNextStep1 = () => {
        if (!reto.trim()) return;
        setStep(2);
    };

    const handleNextStep2 = () => {
        if (!stack.trim()) return;
        setStep(3);
    };

    const handleSubmit = async () => {
        if (!lead.email || !lead.nombre) return;
        setIsSubmitting(true);

        // Simple mock score based on length of inputs
        const calculatedScore = Math.min(100, 40 + (reto.length > 20 ? 30 : 10) + (stack.length > 20 ? 30 : 10));
        setScore(calculatedScore);

        // Calculate recommended pillar based on keywords
        const combinedText = `${reto} ${stack}`.toLowerCase();
        let bestMatch = taxonomyCorpus[5]; // Default: Estrategia Datos & IA
        let maxMatches = 0;

        for (const pillar of taxonomyCorpus) {
            let matches = 0;
            if (pillar.keywords) {
                pillar.keywords.forEach(kw => {
                    if (combinedText.includes(kw.toLowerCase())) matches++;
                });
            }
            if (matches > maxMatches) {
                maxMatches = matches;
                bestMatch = pillar;
            }
        }
        setRecommendedPillar(bestMatch);

        try {
            await fetch(SAVE_WIZARD_API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    session_id: 'wizard_diag_' + Date.now(),
                    email: lead.email,
                    nombre: lead.nombre,
                    organizacion: lead.organizacion,
                    reto: reto,
                    stack: stack,
                    score: calculatedScore
                })
            });
            setStep(4);
        } catch (e) {
            console.error(e);
            alert('Error guardando los datos. Por favor, intente de nuevo.');
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <div className="max-w-2xl mx-auto py-12 px-4">
            <div className="bg-darker border border-white/10 rounded-2xl p-8 shadow-2xl relative overflow-hidden">
                {/* Progress bar */}
                <div className="absolute top-0 left-0 h-1 bg-brand/20 w-full">
                    <div 
                        className="h-full bg-brandCyan transition-all duration-500" 
                        style={{ width: `${(step / 4) * 100}%` }}
                    ></div>
                </div>

                <div className="text-center mb-8">
                    <h3 className="text-2xl font-bold text-white mb-2">Diagnóstico de Madurez de Datos</h3>
                    <p className="text-gray-400 text-sm">Evalúa el nivel de tu infraestructura técnica actual.</p>
                </div>

                {/* Step 1: Reto */}
                {step === 1 && (
                    <div className="animate-in fade-in slide-in-from-right-4 duration-500 space-y-4">
                        <label className="block text-brandCyan text-sm font-semibold mb-2">1. ¿Cuál es el principal reto operativo o de negocio que buscas resolver con datos?</label>
                        <textarea 
                            value={reto}
                            onChange={(e) => setReto(e.target.value)}
                            placeholder="Ej: Altos tiempos de espera en la generación de reportes financieros..."
                            className="w-full bg-black/40 border border-white/20 rounded-lg p-4 text-white focus:border-brandCyan outline-none min-h-[120px]"
                        ></textarea>
                        <button 
                            onClick={handleNextStep1}
                            disabled={!reto.trim()}
                            className="w-full btn-primary py-3 disabled:opacity-50"
                        >
                            Siguiente Paso <i className="ph ph-arrow-right ml-2"></i>
                        </button>
                    </div>
                )}

                {/* Step 2: Stack */}
                {step === 2 && (
                    <div className="animate-in fade-in slide-in-from-right-4 duration-500 space-y-4">
                        {challengeContext && (
                            <div className="bg-brand/10 border border-brand/30 text-brandCyan text-xs py-2 px-3 rounded-md mb-4 inline-flex items-center gap-2">
                                <i className="ph ph-sparkle"></i> ✨ Contexto Heredado de IA: "{challengeContext.substring(0, 50)}..."
                            </div>
                        )}
                        <label className="block text-brandCyan text-sm font-semibold mb-2">2. ¿Dónde residen principalmente tus datos en este momento?</label>
                        <textarea 
                            value={stack}
                            onChange={(e) => setStack(e.target.value)}
                            placeholder="Ej: En hojas de cálculo aisladas, base de datos SQL on-premise, o un ERP heredado..."
                            className="w-full bg-black/40 border border-white/20 rounded-lg p-4 text-white focus:border-brandCyan outline-none min-h-[120px]"
                        ></textarea>
                        <div className="flex gap-4">
                            <button onClick={() => setStep(1)} className="px-6 py-3 border border-white/20 rounded-lg text-white hover:bg-white/5">Atrás</button>
                            <button 
                                onClick={handleNextStep2}
                                disabled={!stack.trim()}
                                className="flex-1 btn-primary py-3 disabled:opacity-50"
                            >
                                Siguiente Paso <i className="ph ph-arrow-right ml-2"></i>
                            </button>
                        </div>
                    </div>
                )}

                {/* Step 3: Lead */}
                {step === 3 && (
                    <div className="animate-in fade-in slide-in-from-right-4 duration-500 space-y-4">
                        <label className="block text-brandCyan text-sm font-semibold mb-4">3. ¿A dónde te enviamos el resultado del diagnóstico?</label>
                        <div className="space-y-3">
                            <input 
                                type="text"
                                placeholder="Nombre completo"
                                value={lead.nombre}
                                onChange={(e) => setLead({...lead, nombre: e.target.value})}
                                className="w-full bg-black/40 border border-white/20 rounded-lg p-3 text-white focus:border-brandCyan outline-none"
                            />
                            <input 
                                type="email"
                                placeholder="Correo institucional"
                                value={lead.email}
                                onChange={(e) => setLead({...lead, email: e.target.value})}
                                className="w-full bg-black/40 border border-white/20 rounded-lg p-3 text-white focus:border-brandCyan outline-none"
                            />
                            <input 
                                type="text"
                                placeholder="Organización (Opcional)"
                                value={lead.organizacion}
                                onChange={(e) => setLead({...lead, organizacion: e.target.value})}
                                className="w-full bg-black/40 border border-white/20 rounded-lg p-3 text-white focus:border-brandCyan outline-none"
                            />
                        </div>
                        <div className="flex gap-4 mt-6">
                            <button onClick={() => setStep(2)} className="px-6 py-3 border border-white/20 rounded-lg text-white hover:bg-white/5">Atrás</button>
                            <button 
                                onClick={handleSubmit}
                                disabled={!lead.email || !lead.nombre || isSubmitting}
                                className="flex-1 btn-primary py-3 disabled:opacity-50"
                            >
                                {isSubmitting ? 'Calculando...' : 'Ver Resultados'}
                            </button>
                        </div>
                    </div>
                )}

                {/* Step 4: Result */}
                {step === 4 && (
                    <div className="animate-in zoom-in duration-500 text-center space-y-6 py-6">
                        <div className="w-24 h-24 rounded-full border-4 border-brandCyan mx-auto flex items-center justify-center bg-brand/10">
                            <span className="text-3xl font-bold text-white">{score}%</span>
                        </div>
                        <div>
                            <h4 className="text-xl font-bold text-white mb-2">Puntaje de Madurez</h4>
                            <p className="text-gray-400 text-sm max-w-md mx-auto mb-4">Hemos recibido tu información de arquitectura. Un ingeniero de datos de Datanestiq analizará estos resultados para estructurar un roadmap de modernización y se contactará contigo a {lead.email}.</p>
                            
                            {recommendedPillar && (
                                <div className="bg-white/5 border border-white/10 rounded-xl p-4 text-left max-w-md mx-auto">
                                    <div className="text-xs text-brandCyan font-semibold mb-1 uppercase tracking-wider">Solución Recomendada</div>
                                    <h5 className="text-white font-bold mb-2">{recommendedPillar.name}</h5>
                                    <a href={`/soluciones/${recommendedPillar.slug}`} className="text-sm text-brandCyan hover:underline flex items-center gap-1">
                                        Explorar capacidades <i className="ph ph-arrow-right"></i>
                                    </a>
                                </div>
                            )}
                        </div>
                        <button onClick={() => {
                            setStep(1); setReto(''); setStack(''); setLead({nombre:'', email:'', organizacion:''}); userChallenge.set('');
                        }} className="text-brandCyan text-sm hover:underline">Reiniciar Diagnóstico</button>
                    </div>
                )}
            </div>
        </div>
    );
}
