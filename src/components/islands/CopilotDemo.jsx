import React, { useState } from 'react';
import { useStore } from '@nanostores/react';
import { semanticHighlight, chatbotOpen } from '../../store/index';
import { CHAT_API } from '../../lib/endpoints';
import { trackEvent } from '../../lib/session';
const TypewriterText = ({ text }) => {
    const [displayedText, setDisplayedText] = useState('');
    
    React.useEffect(() => {
        let i = 0;
        let current = '';
        const interval = setInterval(() => {
            if (i >= text.length) {
                clearInterval(interval);
                return;
            }
            current += text[i];
            setDisplayedText(current);
            i++;
        }, 12);

        return () => clearInterval(interval);
    }, [text]);

    const formatMarkdown = (rawText) => {
        const safeText = rawText.replace(/</g, '&lt;').replace(/>/g, '&gt;');
        const lines = safeText.split('\n');
        return lines.map((line, i) => {
            let formattedLine = line;
            
            const numMatch = formattedLine.match(/^(\s*)(\d+)\.\s+(.*)$/);
            const bulletMatch = formattedLine.match(/^(\s*)([-*])\s+(.*)$/);
            
            if (numMatch) {
                formattedLine = `<div class="ml-4 flex gap-2 mt-1"><span class="text-brandCyan font-bold min-w-[1.2rem]">${numMatch[2]}.</span><span>${numMatch[3]}</span></div>`;
            } else if (bulletMatch) {
                formattedLine = `<div class="ml-4 flex gap-2 mt-1"><span class="text-brandCyan font-bold min-w-[1rem]">&bull;</span><span>${bulletMatch[3]}</span></div>`;
            } else {
                formattedLine = `<span>${formattedLine}</span>`;
            }
            
            formattedLine = formattedLine
                .replace(/\*\*(.*?)\*\*/g, '<b class="text-white font-semibold">$1</b>')
                .replace(/\*(.*?)\*/g, '<i>$1</i>')
                .replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank" rel="noopener noreferrer" class="text-brandCyan underline hover:text-white transition-colors">$1</a>');
            
            return (
                <React.Fragment key={i}>
                    <span dangerouslySetInnerHTML={{ __html: formattedLine }} />
                    {(!numMatch && !bulletMatch && i < lines.length - 1) && <br />}
                </React.Fragment>
            );
        });
    };

    return (
        <div className="text-sm text-gray-300 font-mono leading-relaxed">
            {formatMarkdown(displayedText)}
        </div>
    );
};

import taxonomyCorpus from '../../data/taxonomyCorpus.json';

export default function CopilotDemo() {
    const [selectedPrompt, setSelectedPrompt] = useState(null);
    const [response, setResponse] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const highlights = useStore(semanticHighlight);

    // Filtrar a pilares que tengan copilotPrompts (Precisión 2: nunca reventar)
    const pillarsWithPrompts = taxonomyCorpus.filter(p => p.copilotPrompts && p.copilotPrompts.length > 0);

    // Mejora 1a: si hay contexto de búsqueda, seleccionar por relevancia
    const hasSearchContext = Object.keys(highlights).some(k => k.startsWith('service-'));
    const rankedPillars = hasSearchContext
        ? [...pillarsWithPrompts]
            .map(p => ({ ...p, _score: highlights[`service-${p.slug}`] || 0 }))
            .sort((a, b) => b._score - a._score)
            .slice(0, 3)
        : pillarsWithPrompts.slice(0, 3);

    const demoItems = rankedPillars.map(p => ({
        prompt: p.copilotPrompts[0],
        positioning: p.competitivePositioning || '',
        proofs: p.proofPoints ? p.proofPoints.map(pp => `${pp.client}: ${pp.result}`).join(' | ') : ''
    }));

    const runDemo = async (item) => {
        if (isLoading) return;
        
        // Spec 018 Telemetría
        trackEvent('copilot_click', 'copilot-demo-card', item.prompt);
        
        setSelectedPrompt(item.prompt);
        setResponse('');
        setIsLoading(true);

        try {
            // Using session_id 'copilot_demo' to route to heavy model in chat.php (Phase F)
            const res = await fetch(CHAT_API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    session_id: 'copilot_demo',
                    messages: [
                        { role: 'system', content: `Eres el Copiloto Ejecutivo de Datanestiq. Responde de forma muy técnica, analítica y concisa (máximo 4 líneas) como un consultor de IA de alto nivel. Da respuestas basadas en nuestro posicionamiento: "${item.positioning}". Casos de éxito: "${item.proofs}".` },
                        { role: 'user', content: item.prompt }
                    ]
                })
            });

            if (!res.ok) throw new Error('API Error');
            const data = await res.json();
            
            setResponse(data.choices[0].message.content.trim());
        } catch (e) {
            console.error('Copilot Demo Error:', e);
            setResponse('Error de conexión. El modelo no se encuentra disponible temporalmente.');
        } finally {
            setIsLoading(false);
        }
    };

    const openChatbot = () => {
        chatbotOpen.set(true);
    };

    return (
        <div className="max-w-4xl mx-auto py-12 px-4">
            <div className="text-center mb-8">
                <div className="inline-block px-3 py-1 bg-brand/20 border border-brand/30 text-brandCyan rounded-full text-xs font-semibold mb-4 tracking-wider">
                    <i className="ph ph-terminal-window mr-1"></i> DEMO TÉCNICA
                </div>
                <h3 className="text-2xl font-bold text-white mb-2">Copiloto Estratégico (C-Level)</h3>
                <p className="text-gray-400 text-sm">Una demostración del razonamiento que produce nuestra arquitectura de IA — distinto del AI Concierge, que resuelve tu consulta.</p>
            </div>

            {hasSearchContext && (
                <div className="text-center mb-6 text-sm text-brandCyan/80 bg-brandCyan/5 border border-brandCyan/20 rounded-lg py-2 px-4">
                    <i className="ph ph-link mr-1"></i> Basado en tu búsqueda, mira cómo razona nuestra IA sobre estos escenarios.
                </div>
            )}

            <div className="bg-darker border border-white/10 rounded-2xl overflow-hidden shadow-2xl">
                {/* Header Terminal */}
                <div className="bg-black/50 border-b border-white/10 px-4 py-3 flex items-center gap-2">
                    <div className="flex gap-1.5">
                        <div className="w-3 h-3 rounded-full bg-red-500/80"></div>
                        <div className="w-3 h-3 rounded-full bg-yellow-500/80"></div>
                        <div className="w-3 h-3 rounded-full bg-green-500/80"></div>
                    </div>
                    <div className="mx-auto text-xs font-mono text-gray-500">datanestiq_copilot_v2.sh</div>
                </div>

                {/* Body Terminal */}
                <div className="p-6 md:p-8 flex flex-col gap-6">
                    {/* Prompts list */}
                    <div className="flex flex-col gap-3">
                        <div className="text-xs text-brandCyan font-mono mb-2">Selecciona un escenario analítico:</div>
                        {demoItems.map((item, i) => (
                            <button
                                key={i}
                                onClick={() => runDemo(item)}
                                disabled={isLoading}
                                className={`text-left px-4 py-3 rounded-lg border text-sm transition-all font-mono ${
                                    selectedPrompt === item.prompt 
                                        ? 'bg-brand/20 border-brandCyan text-white'
                                        : 'bg-white/5 border-white/10 text-gray-400 hover:border-white/30 hover:bg-white/10'
                                }`}
                            >
                                <span className="text-brandCyan mr-2">&gt;</span> {item.prompt}
                            </button>
                        ))}
                    </div>

                    {/* Output area */}
                    <div className="bg-black/40 rounded-lg p-5 border border-white/5 min-h-[160px] font-mono flex flex-col">
                        {!selectedPrompt && !isLoading && (
                            <div className="text-gray-600 text-sm flex items-center justify-center flex-1">
                                Esperando ejecución...
                            </div>
                        )}

                        {isLoading && (
                            <div className="flex items-center gap-2 text-brandCyan text-sm">
                                <i className="ph ph-spinner-gap animate-spin"></i>
                                Procesando inferencia...
                            </div>
                        )}

                        {response && !isLoading && (
                            <div className="animate-in fade-in duration-500">
                                <div className="text-xs text-gray-500 mb-2">OUTPUT:</div>
                                <TypewriterText text={response} />
                            </div>
                        )}
                    </div>

                    {/* Puente al chatbot (Mejora 2) */}
                    <div className="text-center border-t border-white/5 pt-4">
                        <button
                            onClick={openChatbot}
                            className="text-sm text-gray-400 hover:text-brandCyan transition-colors"
                        >
                            ¿Tu caso no está en estos escenarios? <span className="text-brandCyan font-medium">Pregúntale al AI Concierge →</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}

