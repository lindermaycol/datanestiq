import React, { useState, useEffect, useRef } from 'react';
import { useStore } from '@nanostores/react';
import { lastUserQuery, chatbotOpen, userContext, userChallenge } from '../../store/index';
import { CHAT_API, SAVE_WIZARD_API } from '../../lib/endpoints';
import AppointmentPicker from './AppointmentPicker.jsx';
import sectorsCorpus from '../../data/sectorsCorpus.json';
import personas from '../../data/personasCorpus.json';
import { mapSectorSlugToId, getLocalizedRoleTitle } from '../../lib/roleLocalization';
import { getSessionId, trackEvent } from '../../lib/session';
// Spec 019 — Router 0-LLM
import intentsConfig from '../../data/intents.json';
import {
  setupWorkerListener,
  initIntentClassifier,
  onWorkerReady,
  classifyIntent,
  matchFAQ,
  isClassifierReady,
} from '../../lib/intentClassifier';

// Corpus FAQ plano desde personasCorpus (fuente de verdad §2)
const FAQ_CORPUS = personas.roles.flatMap(r =>
  (r.objectionResponses || []).map(or => ({ question: or.objection, answer: or.response }))
);

export default function Chatbot() {
  const query = useStore(lastUserQuery);
  const isOpen = useStore(chatbotOpen);
  const ctxState = useStore(userContext);
  
  const [messages, setMessages] = useState([]);
  const [displayMessages, setDisplayMessages] = useState([]);
  const [inputText, setInputText] = useState('');
  const [isTyping, setIsTyping] = useState(false);
  const messagesEndRef = useRef(null);
  const initialized = useRef(false);

  // Spec 019 — Router 0-LLM: worker ref + worker state
  const workerRef = useRef(null);
  const [workerReady, setWorkerReady] = useState(false);
  // Estado para respuesta FAQ 0-LLM (escape a LLM incluido)
  const [faqResponse, setFaqResponse] = useState(null); // { answer, originalText }

  // State Machine (FR-015)
  const [chatState, setChatState] = useState({ step: 'intro', sector: null, role: null, problem: null });
  
  // Lead tracking
  const sessionIdRef = useRef(getSessionId());
  const [leadData, setLeadData] = useState({ email: '', telefono: '', organizacion: '', reto: '', stack: '' });
  const [leadConfirmed, setLeadConfirmed] = useState(false);
  const [showLeadCard, setShowLeadCard] = useState(false);

  // Journey tracking (Spec 014) — acumula pasos sin fetch
  const journeyRef = useRef([]);

  const extractLeadSignals = (text) => {
    const emailMatch = text.match(/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/);
    const phoneMatch = text.match(/(\+?\d[\d\s-]{7,14}\d)/);

    setLeadData((prev) => ({
      ...prev,
      email: emailMatch ? emailMatch[0] : prev.email,
      telefono: phoneMatch ? phoneMatch[0] : prev.telefono,
    }));
  };

  const leadPrefilledRef = useRef(false);
  useEffect(() => {
    // Si entramos en modo semántico y obtenemos lead, mostramos la tarjeta
    if ((leadData.email || leadData.telefono) && !leadConfirmed && !showLeadCard && chatState.step === 'semantic') {
      setShowLeadCard(true);
    }
    if (showLeadCard && !leadPrefilledRef.current) {
      leadPrefilledRef.current = true;
      const initialReto = userChallenge.get() || (chatState.problem ? `Atender desafío de ${chatState.problem}` : '') || query || '';
      if (initialReto) {
        setLeadData(prev => ({ ...prev, reto: prev.reto || initialReto }));
      }
    }
  }, [leadData, leadConfirmed, showLeadCard, chatState.step, chatState.problem, query]);

  const saveLead = async (leadInfo) => {
    try {
        await fetch(SAVE_WIZARD_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                session_id: sessionIdRef.current,
                email: leadInfo.email,
                telefono: leadInfo.telefono,
                organizacion: leadInfo.organizacion,
                reto: leadInfo.reto,
                stack: leadInfo.stack,
                source: 'chatbot',
                sector: chatState.sector || ctxState.sector || '',
                rol: chatState.role || ctxState.rol || '',
                journey: journeyRef.current,
            })
        });
    } catch (e) {
        console.error('Error saving lead:', e);
    }
  };

  const confirmLead = async () => {
    await saveLead(leadData);
    setLeadConfirmed(true);
    setShowLeadCard(false);
    setChatState(prev => ({ ...prev, step: 'done' }));
    
    setDisplayMessages(prev => [
        ...prev, 
        { role: 'assistant', content: '¡Perfecto! Hemos recibido tus datos. Un arquitecto de datos de Datanestiq se pondrá en contacto contigo muy pronto. 🎯 ¿Te gustaría agendar una cita directamente?' }
    ]);
    setShowScheduler(true);
  };

  const [showScheduler, setShowScheduler] = useState(false);

  // Initialize intro message or inherit active context chip upon FIRST opening of chatbot (Refix F-03 / F-11 - 0-LLM)
  // Spec 019 — Inicializar el worker Xenova y el clasificador de intención (una sola vez)
  useEffect(() => {
    if (workerRef.current) return; // ya inicializado
    const w = new Worker('/worker.js', { type: 'module' });
    workerRef.current = w;
    // P2: setupWorkerListener instala el handler de respuestas por `id` (cross-wiring cero)
    setupWorkerListener(w);
    // Listener de progreso/ready (no gestionado por setupWorkerListener)
    const handleProgress = async (event) => {
      const { status } = event.data ?? {};
      if (status === 'ready') {
        setWorkerReady(true);
        // P1: el modelo acaba de cargarse → inicializar el clasificador ahora
        await onWorkerReady(intentsConfig, FAQ_CORPUS);
      }
    };
    w.addEventListener('message', handleProgress);
    // Calentar el modelo (puede tardar; si el chatbot se abre antes, P1 garantiza LLM inmediato)
    w.postMessage({ type: 'warmup' });
    return () => {
      w.removeEventListener('message', handleProgress);
      // No terminar el worker al desmontar — puede ser reutilizado si el chatbot vuelve a abrirse
    };
  }, []); // eslint-disable-line react-hooks/exhaustive-deps

  // Spec 019 — Cuando el worker ya estaba listo al montar el chatbot, inicializar de inmediato
  useEffect(() => {
    if (workerReady) {
      initIntentClassifier(intentsConfig, FAQ_CORPUS, true);
    }
  }, [workerReady]); // eslint-disable-line react-hooks/exhaustive-deps

  useEffect(() => {
    if (isOpen && !initialized.current) {
        initialized.current = true;

        const matchedSectorId = mapSectorSlugToId(ctxState.sector, sectorsCorpus);
        const matchedRoleObj = personas.roles.find(r => r.id === ctxState.rol);
        const matchedSectorObj = matchedSectorId ? sectorsCorpus.find(s => s.id === matchedSectorId) : null;

        if (matchedSectorObj && matchedRoleObj) {
            setChatState({ step: 'problem', sector: matchedSectorObj.id, role: matchedRoleObj.id, problem: null });
            journeyRef.current.push({ type: 'context_inherited', sector: matchedSectorObj.id, role: matchedRoleObj.id });

            const kpisStr = matchedSectorObj.kpis ? matchedSectorObj.kpis.map(k => k.metric).join(', ') : 'eficiencia';
            const regStr = matchedSectorObj.regulations ? matchedSectorObj.regulations.join(', ') : 'normativas vigentes';
            const roleTitleDisplay = getLocalizedRoleTitle(matchedRoleObj, matchedSectorObj);
            
            setDisplayMessages([
                { role: 'assistant', content: `¡Hola! Veo que estás explorando como ${roleTitleDisplay} en ${matchedSectorObj.title}. Sabemos que buscas "${matchedRoleObj.decisionCriteria?.[0] || 'ROI'}" y optimizar KPIs como ${kpisStr} cumpliendo con ${regStr}.\n\n¿Qué proceso operativo específico te genera más cuellos de botella hoy?` }
            ]);
        } else if (matchedSectorObj) {
            setChatState({ step: 'role', sector: matchedSectorObj.id, role: null, problem: null });
            journeyRef.current.push({ type: 'context_inherited', sector: matchedSectorObj.id });
            setDisplayMessages([
                { role: 'assistant', content: `¡Hola! Veo que exploras el sector ${matchedSectorObj.title}. Para darte la recomendación adecuada, ¿cuál es tu rol principal en la organización?` }
            ]);
        } else if (matchedRoleObj) {
            setChatState({ step: 'sector', sector: null, role: matchedRoleObj.id, problem: null });
            journeyRef.current.push({ type: 'context_inherited', role: matchedRoleObj.id });
            setDisplayMessages([
                { role: 'assistant', content: `¡Hola! Veo que estás explorando como ${matchedRoleObj.title}. ¿A qué sector pertenece tu organización principalmente?` }
            ]);
        } else {
            setDisplayMessages([
                { role: 'assistant', content: '¡Hola! Soy el AI Concierge de Datanestiq. Estoy aquí para entender tus desafíos operativos y sugerirte soluciones de IA o Datos. ¿A qué sector perteneces?' }
            ]);
        }
    }
  }, [isOpen, ctxState]);

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  };

  useEffect(() => {
    scrollToBottom();
  }, [displayMessages, isTyping, chatState.step, showLeadCard]);

  // Precisión 1: Respetar herencia de contexto de Spec 007
  useEffect(() => {
    if (query) {
      setChatState(prev => ({ ...prev, step: 'semantic' }));
      handleUserSubmit(`Represento al sector de interés. Me gustaría saber cómo pueden ayudarme con: ${query}`);
      chatbotOpen.set(true);
      lastUserQuery.set(''); // clear it
    }
  }, [query]);

  // Spec 019 — Ruta LLM (fallback, igual que antes)
  const _callLLM = async (text, newDisplay, newHistory) => {
    try {
      const response = await fetch(CHAT_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          session_id: sessionIdRef.current,
          messages: newHistory,
          context: { step: chatState.step, sector: chatState.sector, role: chatState.role, problem: chatState.problem }
        })
      });
      if (!response.ok) throw new Error('Network error');
      const data = await response.json();
      const aiContent = data.choices[0].message.content;
      setMessages([...newHistory, { role: 'assistant', content: aiContent }]);
      setDisplayMessages([...newDisplay, { role: 'assistant', content: aiContent }]);
    } catch (e) {
      console.error('Chatbot API Error:', e);
      setDisplayMessages([...newDisplay, { role: 'assistant', content: 'Ups, tuve un problema para procesar tu mensaje. Por favor intenta de nuevo en un momento o déjame tu correo y un arquitecto de Datanestiq te contactará.' }]);
    } finally {
      setIsTyping(false);
    }
  };

  const handleUserSubmit = async (forcedText = null, skipRouter = false) => {
    const text = forcedText || inputText;
    if (!text.trim()) return;

    // Spec 018 Telemetría
    trackEvent('chatbot_step', 'chatbot-text-submit', text);

    // Escapar hacia semántico si usuario escribe texto libre
    if (chatState.step !== 'semantic') {
        setChatState(prev => ({ ...prev, step: 'semantic' }));
    }

    // Journey: registrar mensaje de texto libre (sin fetch adicional)
    journeyRef.current.push({ type: 'chat_message', content: text, step: chatState.step });

    extractLeadSignals(text);
    setFaqResponse(null); // Limpiar respuesta FAQ previa
    setInputText('');
    const newDisplay = [...displayMessages, { role: 'user', content: text }];
    const newHistory = [...messages, { role: 'user', content: text }];
    setDisplayMessages(newDisplay);
    setMessages(newHistory);
    setIsTyping(true);

    // ── Spec 019: Router 0-LLM ─────────────────────────────────────────────────
    // P1: si el clasificador no está listo (modelo aún descargando) → LLM inmediato, sin bloquear.
    // skipRouter=true se usa desde el botón de escape FAQ para ir directo al LLM.
    if (!skipRouter && isClassifierReady()) {
      try {
        const { intent, confidence } = await classifyIntent(text);

        // Instrumentación: registrar decisión de ruteo (Spec 018 + 019)
        const routingResolved = ['faq', 'cita', 'guiado'].includes(intent) ? '0llm' : 'llm';
        trackEvent('intent_routing', 'chatbot-router', JSON.stringify({ intent, confidence: confidence.toFixed(3), resolved: routingResolved }));

        // ── Ruta cita (0-LLM) ──────────────────────────────────────────────────
        if (intent === 'cita') {
          const citaMsg = '¡Perfecto! Te muestro la disponibilidad de nuestros arquitectos de datos. Elige el horario que mejor te venga.';
          setDisplayMessages([...newDisplay, { role: 'assistant', content: citaMsg }]);
          setMessages([...newHistory, { role: 'assistant', content: citaMsg }]);
          setShowScheduler(true);
          setIsTyping(false);
          return;
        }

        // ── Ruta guiado (0-LLM) ────────────────────────────────────────────────
        if (intent === 'guiado') {
          const guidedMsg = 'Déjame guiarte por los servicios que aplican a tu situación. ¿A qué sector pertenece tu organización?';
          setChatState(prev => ({ ...prev, step: 'intro' }));
          setDisplayMessages([...newDisplay, { role: 'assistant', content: guidedMsg }]);
          setMessages([...newHistory, { role: 'assistant', content: guidedMsg }]);
          setIsTyping(false);
          return;
        }

        // ── Ruta faq (0-LLM, doble umbral §2) ─────────────────────────────────
        if (intent === 'faq') {
          const faqMatch = await matchFAQ(text, FAQ_CORPUS);
          if (faqMatch) {
            // Respuesta verbatim de la taxonomía real (§2 — jamás inventada)
            setFaqResponse({ answer: faqMatch.answer, originalText: text });
            setDisplayMessages([...newDisplay, { role: 'assistant', content: faqMatch.answer, isFAQ: true }]);
            setMessages([...newHistory, { role: 'assistant', content: faqMatch.answer }]);
            setIsTyping(false);
            return;
          }
          // match < faq_match_threshold → fallback a LLM (§2: no forzar FAQ errónea)
        }

        // ── Ruta complejo / unknown / confianza baja → LLM ─────────────────────
      } catch (classifyErr) {
        // Error en el clasificador → LLM inmediato (P1: nunca bloquear)
        console.warn('[Router 019] classifyIntent error, falling back to LLM:', classifyErr);
      }
    }
    // ─────────────────────────────────────────────────────────────────────────

    // Ruta LLM (exactamente igual que antes)
    await _callLLM(text, newDisplay, newHistory);
  };

  // Bloque 1: Ruteo Híbrido Determinista (Cero Latencia, Cero LLM)
  // Bloque 1: Ruteo Híbrido Determinista (Cero Latencia, Cero LLM)
  const selectSector = (sectorId) => {
      const sectorObj = sectorsCorpus.find(s => s.id === sectorId);
      
      // Spec 018 Telemetría
      trackEvent('chatbot_step', 'chatbot-sector-select', sectorId);
      
      setChatState(prev => ({ ...prev, sector: sectorId, step: 'role' }));
      journeyRef.current.push({ type: 'flow_step', step: 'sector', value: sectorId, label: sectorObj.title });
      
      const newDisplay = [
          ...displayMessages,
          { role: 'user', content: sectorObj.title },
          { role: 'assistant', content: `Entendido, trabajas en ${sectorObj.title}. Para darte la mejor recomendación, ¿cuál es tu rol principal en la organización?` }
      ];
      setDisplayMessages(newDisplay);
      setMessages([...messages, { role: 'user', content: sectorObj.title }]);
  };

  const selectRole = (roleId) => {
      const roleObj = personas.roles.find(r => r.id === roleId);
      const sectorObj = sectorsCorpus.find(s => s.id === chatState.sector);
      const displayRole = getLocalizedRoleTitle(roleObj, sectorObj);
      
      // Spec 018 Telemetría
      trackEvent('chatbot_step', 'chatbot-role-select', roleId);
      
      setChatState(prev => ({ ...prev, role: roleId, step: 'problem' }));
      journeyRef.current.push({ type: 'flow_step', step: 'role', value: roleId, label: displayRole });
      
      const objectionsStr = roleObj.objections ? roleObj.objections[0] : 'implementación riesgosa';
      const kpisStr = sectorObj.kpis ? sectorObj.kpis.map(k => k.metric).join(', ') : 'eficiencia';
      const regStr = sectorObj.regulations ? sectorObj.regulations[0] : 'normativas vigentes';
      
      const newDisplay = [
          ...displayMessages,
          { role: 'user', content: displayRole },
          { role: 'assistant', content: `Perfecto. Como ${displayRole}, sabemos que buscas "${roleObj.decisionCriteria?.[0] || 'ROI'}" y debes mitigar preocupaciones como "${objectionsStr}". En ${sectorObj.title}, ayudamos a optimizar KPIs clave como ${kpisStr} cumpliendo con ${regStr}.\n\n¿Qué proceso operativo específico te genera más cuellos de botella hoy?` }
      ];
      setDisplayMessages(newDisplay);
      setMessages([...messages, { role: 'user', content: displayRole }]);
  };

  const selectProblem = (problemCode, problemLabel) => {
      // Spec 018 Telemetría
      trackEvent('chatbot_step', 'chatbot-problem-select', problemCode);
      
      setChatState(prev => ({ ...prev, problem: problemCode, step: 'solution' }));
      journeyRef.current.push({ type: 'flow_step', step: 'problem', value: problemCode, label: problemLabel });
      
      const solutionMessage = getSolutionMessage(chatState.sector, problemCode);
      
      const newDisplay = [
          ...displayMessages,
          { role: 'user', content: problemLabel },
          { role: 'assistant', content: `${solutionMessage}\n\n¿Te gustaría agendar un diagnóstico rápido con un arquitecto de datos? Por favor déjanos tu correo y un teléfono.` }
      ];
      setDisplayMessages(newDisplay);
      setMessages([...messages, { role: 'user', content: problemLabel }]);
      
      // Mostrar tarjeta de lead nativa de inmediato
      setShowLeadCard(true);
      setChatState(prev => ({ ...prev, step: 'lead' }));
  };

  const getSolutionMessage = (sectorId, problemCode) => {
      const sector = sectorsCorpus.find(s => s.id === sectorId);
      if (sector && sector.problems) {
          const problem = sector.problems.find(p => p.code === problemCode);
          if (problem) return problem.solution;
      }
      return 'Podemos diseñar una solución a medida para ese desafío. Nuestro equipo técnico te propone una hoja de ruta con resultados medibles desde la primera semana.';
  };

  const escapeToSemantic = () => {
      // Spec 018 Telemetría
      trackEvent('chatbot_step', 'chatbot-escape-semantic', 'escape');
      
      setChatState(prev => ({ ...prev, step: 'semantic' }));
      const newDisplay = [
          ...displayMessages,
          { role: 'user', content: 'Prefiero detallar mi caso específicamente.' },
          { role: 'assistant', content: 'Por supuesto, explícame con tus palabras cuál es tu necesidad principal o el ecosistema tecnológico que manejas actualmente.' }
      ];
      setDisplayMessages(newDisplay);
      setMessages([...messages, { role: 'user', content: 'Prefiero detallar mi caso específicamente.' }]);
  };

  // Format AI text (bold tags, italic tags, markdown links, newlines, lists)
  const formatText = (text) => {
    const safeText = text.replace(/</g, '&lt;').replace(/>/g, '&gt;');
    
    return safeText.split('\n').map((line, i) => {
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
        .replace(/\*\*(.*?)\*\*/g, '<b class="text-white">$1</b>')
        .replace(/\*(.*?)\*/g, '<i>$1</i>')
        .replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" class="text-brandCyan underline hover:text-white transition-colors">$1</a>');
      
      return (
        <React.Fragment key={i}>
          <span dangerouslySetInnerHTML={{ __html: formattedLine }} />
          {(!numMatch && !bulletMatch && i < safeText.split('\n').length - 1) && <br />}
        </React.Fragment>
      );
    });
  };

  if (!isOpen) {
    return (
      <button 
        onClick={() => chatbotOpen.set(true)}
        className="fixed bottom-6 right-6 w-14 h-14 bg-brandCyan text-white rounded-full shadow-lg flex items-center justify-center hover:bg-cyan-600 transition-colors z-50 ring-2 ring-brandCyan/50"
      >
        <i className="ph ph-robot text-2xl text-black"></i>
      </button>
    );
  }

  return (
    <div className="fixed bottom-6 right-6 w-80 sm:w-96 bg-darker border border-white/20 rounded-2xl shadow-2xl flex flex-col z-50 overflow-hidden animate-in slide-in-from-bottom-10">
      <div className="bg-brand/20 p-4 flex justify-between items-center border-b border-brand/30">
        <h3 className="font-bold text-white flex items-center gap-2">
            <i className="ph ph-robot text-brandCyan text-xl"></i> 
            AI Concierge
        </h3>
        <button onClick={() => chatbotOpen.set(false)} className="text-gray-400 hover:text-white transition-colors">
            <i className="ph ph-x text-xl"></i>
        </button>
      </div>
      
      <div className="p-4 h-80 overflow-y-auto space-y-4 bg-black/40">
        {displayMessages.map((m, i) => (
          <div key={i} className={`flex gap-3 ${m.role === 'assistant' ? 'flex-row' : 'flex-row-reverse'}`}>
            {m.role === 'assistant' && (
                <div className="w-8 h-8 rounded bg-brand/20 flex-shrink-0 flex items-center justify-center text-brandCyan">
                    <i className="ph ph-robot"></i>
                </div>
            )}
            <div className={`p-3 rounded-2xl text-sm border max-w-[85%] ${
                m.role === 'assistant' 
                    ? 'bg-white/5 border-white/10 text-gray-200 rounded-tl-sm' 
                    : 'bg-brand/20 border-brand/30 text-white rounded-tr-sm'
            }`}>
              {formatText(m.content)}
            </div>
          </div>
        ))}

        {/* State Machine: Intro Buttons */}
        {chatState.step === 'intro' && !isTyping && (
            <div className="flex flex-col gap-2 mt-2 animate-in fade-in max-h-48 overflow-y-auto pr-2 custom-scrollbar">
                {sectorsCorpus.map(s => (
                    <button key={s.id} onClick={() => selectSector(s.id)} className="w-full text-left p-2 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 text-sm transition-colors text-gray-300">
                        {s.title}
                    </button>
                ))}
                <button onClick={escapeToSemantic} className="w-full text-left p-2 rounded-lg bg-brand/10 hover:bg-brand/20 border border-brand/30 text-brandCyan text-sm transition-colors font-medium text-center">Otro / Escribir libremente</button>
            </div>
        )}

        {/* State Machine: Role Buttons */}
        {chatState.step === 'role' && !isTyping && (() => {
            const currentSectorObj = sectorsCorpus.find(s => s.id === chatState.sector);
            return (
                <div className="flex flex-col gap-2 mt-2 animate-in fade-in">
                    {currentSectorObj?.relevantPersonas?.map(roleId => {
                        const role = personas.roles.find(r => r.id === roleId);
                        if (!role) return null;
                        const localizedTitle = getLocalizedRoleTitle(role, currentSectorObj);
                        return (
                            <button key={role.id} onClick={() => selectRole(role.id)} className="w-full text-left p-2 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 text-sm transition-colors text-gray-300">
                                {localizedTitle}
                            </button>
                        );
                    })}
                    <button onClick={escapeToSemantic} className="w-full text-left p-2 rounded-lg bg-brand/10 hover:bg-brand/20 border border-brand/30 text-brandCyan text-sm transition-colors font-medium text-center">Otro / Detallar</button>
                </div>
            );
        })()}

        {/* State Machine: Problem Buttons */}
        {chatState.step === 'problem' && !isTyping && (
            <div className="flex flex-col gap-2 mt-2 animate-in fade-in">
                {sectorsCorpus.find(s => s.id === chatState.sector)?.problems?.map(p => (
                    <button key={p.code} onClick={() => selectProblem(p.code, p.label)} className="w-full text-left p-2 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 text-sm transition-colors text-gray-300">
                        {p.label}
                    </button>
                ))}
                <button onClick={escapeToSemantic} className="w-full text-left p-2 rounded-lg bg-brand/10 hover:bg-brand/20 border border-brand/30 text-brandCyan text-sm transition-colors font-medium text-center">Otro problema / Detallar</button>
            </div>
        )}

        {/* Lead Capture Card */}
        {showLeadCard && (
          <div className="bg-brand/10 border border-brand/30 rounded-2xl p-4 mt-2 animate-in fade-in">
            <p className="text-xs text-gray-300 mb-2">Confirma tus datos para que un arquitecto de datos de Datanestiq te contacte:</p>
            <p className="text-xs text-brandCyan mb-3 italic">Un arquitecto revisará tu caso y te enviará un diagnóstico inicial en 48 horas. Sin compromiso.</p>
            <input
              type="text"
              value={leadData.organizacion || ''}
              onChange={(e) => setLeadData(prev => ({ ...prev, organizacion: e.target.value }))}
              placeholder="Empresa / Entidad (ej: Banco X, Minsur, etc.)"
              className="w-full bg-darker border border-white/10 rounded-lg p-2 text-sm text-white mb-2 focus:outline-none focus:border-brandCyan"
            />
            <input
              type="email"
              value={leadData.email || ''}
              onChange={(e) => setLeadData(prev => ({ ...prev, email: e.target.value }))}
              placeholder="Correo institucional"
              className="w-full bg-darker border border-white/10 rounded-lg p-2 text-sm text-white mb-2 focus:outline-none focus:border-brandCyan"
            />
            <input
              type="tel"
              value={leadData.telefono || ''}
              onChange={(e) => setLeadData(prev => ({ ...prev, telefono: e.target.value }))}
              placeholder="Celular o fijo"
              className="w-full bg-darker border border-white/10 rounded-lg p-2 text-sm text-white mb-2 focus:outline-none focus:border-brandCyan"
            />
            <input
              type="text"
              value={leadData.reto || ''}
              onChange={(e) => setLeadData(prev => ({ ...prev, reto: e.target.value }))}
              placeholder="Reto principal"
              className="w-full bg-darker border border-white/10 rounded-lg p-2 text-sm text-white mb-2 focus:outline-none focus:border-brandCyan"
            />
            <input
              type="text"
              value={leadData.stack || ''}
              onChange={(e) => setLeadData(prev => ({ ...prev, stack: e.target.value }))}
              placeholder="Sistemas actuales / situación de datos"
              className="w-full bg-darker border border-white/10 rounded-lg p-2 text-sm text-white mb-3 focus:outline-none focus:border-brandCyan"
            />
            <button onClick={confirmLead} className="w-full btn-primary text-sm py-2">
              Confirmar y agendar diagnóstico
            </button>
          </div>
        )}

        {/* Spec 019 — FAQ 0-LLM: botón de escape siempre visible en respuestas FAQ */}
        {faqResponse && (
          <div className="mt-1 animate-in fade-in">
            <button
              onClick={() => {
                setFaqResponse(null);
                handleUserSubmit(faqResponse.originalText, true);
              }}
              className="w-full text-left p-2 rounded-lg bg-brand/10 hover:bg-brand/20 border border-brand/30 text-brandCyan text-xs transition-colors font-medium"
            >
              <i className="ph ph-chat-dots mr-1"></i>
              ¿No era exactamente lo que buscabas? Escríbeme más detalles →
            </button>
          </div>
        )}

        {/* Spec 019 — AppointmentPicker para ruta cita 0-LLM */}
        {showScheduler && !leadConfirmed && (
          <AppointmentPicker
            sessionId={sessionIdRef.current}
            email={leadData.email}
            onBooked={() => setShowScheduler(false)}
          />
        )}

        {/* Appointment Picker — tras captura exitosa del lead */}
        {showScheduler && leadConfirmed && (
          <AppointmentPicker
            sessionId={sessionIdRef.current}
            email={leadData.email}
            onBooked={() => setShowScheduler(false)}
          />
        )}

        {isTyping && (
            <div className="flex gap-3 mt-2">
                <div className="w-8 h-8 rounded bg-brand/20 flex-shrink-0 flex items-center justify-center text-brandCyan">
                    <i className="ph ph-robot"></i>
                </div>
                <div className="bg-white/5 px-4 py-3 rounded-2xl rounded-tl-sm border border-white/10 flex items-center gap-1 h-[42px]">
                    <span className="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style={{animationDelay: '0ms'}}></span>
                    <span className="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style={{animationDelay: '150ms'}}></span>
                    <span className="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style={{animationDelay: '300ms'}}></span>
                </div>
            </div>
        )}
        <div ref={messagesEndRef} />
      </div>

      <div className="p-3 bg-black/60 border-t border-white/10">
        <div className="relative">
            <input 
                type="text" 
                value={inputText}
                onChange={(e) => setInputText(e.target.value)}
                onKeyDown={(e) => e.key === 'Enter' && handleUserSubmit()}
                disabled={isTyping || chatState.step === 'done'}
                placeholder={chatState.step === 'done' ? "Conversación finalizada" : "Escribe tu necesidad aquí..."}
                className="w-full bg-darker border border-white/20 rounded-lg py-2 pl-3 pr-10 text-sm text-white focus:outline-none focus:border-brandCyan disabled:opacity-50" 
            />
            <button 
                onClick={() => handleUserSubmit()}
                disabled={isTyping || chatState.step === 'done'}
                className="absolute right-2 top-1/2 -translate-y-1/2 text-brandCyan hover:text-white transition-colors"
            >
                <i className="ph ph-paper-plane-right text-lg"></i>
            </button>
        </div>
      </div>
    </div>
  );
}
