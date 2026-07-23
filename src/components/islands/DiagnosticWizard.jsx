import React, { useState, useEffect } from 'react';
import { useStore } from '@nanostores/react';
import { lastUserQuery, chatbotOpen, semanticHighlight, userChallenge } from '../../store/index';
import { CHAT_API } from '../../lib/endpoints';
import corpus from '../../data/taxonomyCorpus.json';

import rawSectorsCorpus from '../../data/sectorsCorpus.json';

export default function DiagnosticWizard() {
  const [sectors, setSectors] = useState([...rawSectorsCorpus]);
  const [customSector, setCustomSector] = useState('');
  const [inputs, setInputs] = useState({});
  const [pitches, setPitches] = useState({});
  const [loadingIds, setLoadingIds] = useState({});
  
  const highlights = useStore(semanticHighlight);
  const challenge = useStore(userChallenge);
  
  // Calculate if we have any active highlights to dim non-matching cards
  const hasHighlights = Object.keys(highlights).length > 0;
  // Fix Mejora 1c: solo scores de sectores (excluir service-*) para calcular umbral
  const sectorScores = Object.entries(highlights)
      .filter(([k]) => !k.startsWith('service-'))
      .map(([, v]) => v);
  const maxScore = sectorScores.length > 0 ? Math.max(...sectorScores) : 0;
  const highlightThreshold = Math.max(0.2, maxScore * 0.75);

  // Precisión 3: pre-llenar inputs con userChallenge UNA vez, luego editable/borrable
  const challengeApplied = React.useRef(false);
  useEffect(() => {
    if (challenge && !challengeApplied.current) {
      challengeApplied.current = true;
      const prefilled = {};
      rawSectorsCorpus.forEach(s => { prefilled[s.id] = challenge; });
      setInputs(prev => {
        const merged = { ...prefilled };
        // Preservar inputs que el usuario ya haya editado
        Object.keys(prev).forEach(k => { if (prev[k]) merged[k] = prev[k]; });
        return merged;
      });
    }
  }, [challenge]);

  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const servicioSlug = params.get('servicio');
    if (!servicioSlug) return;

    const service = corpus.find((s) => s.slug === servicioSlug);
    if (!service) return;

    lastUserQuery.set(
      `Vengo desde la página de "${service.name}". Quiero entender cómo Datanestiq puede ayudarme concretamente con este servicio.`
    );
    chatbotOpen.set(true);

    // Limpia el query param de la URL sin recargar la página
    const url = new URL(window.location.href);
    url.searchParams.delete('servicio');
    window.history.replaceState({}, '', url);
  }, []);

  const handleCreateCustomSector = () => {
      const name = customSector.trim();
      if (!name) return;

      const newSector = {
          id: 'sector-' + Date.now(),
          title: name,
          description: 'Sector personalizado. Cuéntanos tu desafío para entender mejor tus necesidades operativas.',
          icon: 'ph-buildings'
      };

      setSectors([...sectors, newSector]);
      setCustomSector('');
  };

  const generatePitch = async (sector) => {
      const challenge = inputs[sector.id];
      if (!challenge || !challenge.trim()) return;

      setLoadingIds(prev => ({ ...prev, [sector.id]: true }));

      try {
          const kpisStr = sector.kpis ? sector.kpis.map(k => k.metric).join(', ') : 'eficiencia general';
          const regStr = sector.regulations ? sector.regulations.join(', ') : 'normativas vigentes';
          const prompt = `El usuario trabaja en el sector ${sector.title} y tiene este desafío: "${challenge}". Considera que en este sector importan KPIs como ${kpisStr} y regulaciones como ${regStr}. En un párrafo de máximo 30 palabras, explica cómo los servicios de datos e IA de Datanestiq resuelven esto. Sé persuasivo.`;
          
          const response = await fetch(CHAT_API, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({
                  session_id: 'wizard_' + Date.now(),
                  messages: [{ role: 'user', content: prompt }]
              })
          });

          if (!response.ok) throw new Error('API Error');
          const data = await response.json();
          let text = data.choices[0].message.content.trim();
          
          setPitches(prev => ({ ...prev, [sector.id]: text }));
      } catch (e) {
          console.error('Error generating pitch', e);
          setPitches(prev => ({ ...prev, [sector.id]: 'Hubo un error de conexión con la IA. Intenta de nuevo.' }));
      } finally {
          setLoadingIds(prev => ({ ...prev, [sector.id]: false }));
      }
  };

  const TypewriterText = ({ text }) => {
    const [displayedText, setDisplayedText] = useState('');
    
    React.useEffect(() => {
        let i = 0;
        let isTag = false;
        let current = '';
        
        // Very basic typewriter for string (we'll just use raw string since we format it later)
        const interval = setInterval(() => {
            if (i >= text.length) {
                clearInterval(interval);
                return;
            }
            current += text[i];
            setDisplayedText(current);
            i++;
        }, 20);

        return () => clearInterval(interval);
    }, [text]);

    // Parse simple bold tags
    const html = displayedText.replace(/\*\*(.*?)\*\*/g, '<b class="text-white">$1</b>');

    return (
        <div 
            className="text-xs text-brandCyan mt-2 font-medium bg-brandCyan/10 p-3 rounded-lg border border-brandCyan/20"
            style={{ minHeight: '80px', transition: 'height 0.3s ease' }}
            dangerouslySetInnerHTML={{ __html: html }}
        />
    );
  };

  return (
    <div className="max-w-6xl mx-auto py-12">
      <div className="text-center mb-10">
          <h2 className="text-3xl md:text-4xl font-bold text-white mb-4">Descubre el Impacto en tu Sector</h2>
          <p className="text-gray-400 max-w-2xl mx-auto">Selecciona tu industria o agrega una nueva, cuéntanos tu mayor desafío y nuestra IA formulará una solución al instante.</p>
      </div>

      <div className="flex flex-col md:flex-row justify-center items-center gap-4 mb-10 max-w-lg mx-auto">
          <input 
              type="text" 
              value={customSector}
              onChange={(e) => setCustomSector(e.target.value)}
              onKeyDown={(e) => e.key === 'Enter' && handleCreateCustomSector()}
              placeholder="¿No encuentras tu sector? Escríbelo aquí..." 
              className="flex-1 bg-darker border border-white/10 rounded-lg p-3 text-sm text-white focus:outline-none focus:border-brandCyan"
          />
          <button 
              onClick={handleCreateCustomSector}
              className="bg-brand/20 border border-brand/30 text-brandCyan px-6 py-3 rounded-lg hover:bg-brand/30 transition-colors whitespace-nowrap"
          >
              <i className="ph ph-plus mr-2"></i> Añadir Sector
          </button>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="sectores-grid">
          {sectors.map(sector => {
              const score = highlights[sector.id] || 0;
              const isHighlighted = hasHighlights && score >= highlightThreshold;
              const isDimmed = hasHighlights && !isHighlighted;
              
              return (
              <div 
                  key={sector.id} 
                  id={sector.id} 
                  className="p-6 bg-white/5 border rounded-2xl transition-all duration-500 min-h-[220px] flex flex-col relative overflow-hidden"
                  style={{
                      opacity: isDimmed ? 0.3 : 1,
                      transform: isHighlighted ? 'scale(1.02)' : (isDimmed ? 'scale(0.98)' : 'none'),
                      borderColor: isHighlighted ? 'var(--brand-cyan)' : 'rgba(255,255,255,0.1)',
                      boxShadow: isHighlighted ? '0 0 20px rgba(0, 240, 255, 0.1)' : 'none'
                  }}
              >
                  <h4 className="text-lg font-semibold mb-3 flex items-center gap-2 text-white">
                      <i className={`ph ${sector.icon} text-brandCyan text-2xl`}></i> {sector.title}
                  </h4>
                  <div className="text-sm text-gray-400 leading-relaxed border-l border-white/10 pl-4 py-1 mb-4 flex-1">
                      {sector.description}
                  </div>
                  
                  <div className="mt-auto relative z-10">
                      <div className="relative">
                          <input 
                              type="text" 
                              value={inputs[sector.id] || ''}
                              onChange={(e) => setInputs({...inputs, [sector.id]: e.target.value})}
                              onKeyDown={(e) => e.key === 'Enter' && generatePitch(sector)}
                              disabled={loadingIds[sector.id] || !!pitches[sector.id]}
                              placeholder={`¿Cuál es tu mayor desafío en ${sector.title}?`}
                              className={`w-full bg-darker border border-white/10 rounded-lg py-2 pl-3 pr-10 text-xs text-white outline-none transition-colors ${
                                  (loadingIds[sector.id] || pitches[sector.id]) ? 'opacity-50' : 'focus:border-brandCyan'
                              }`}
                          />
                          {loadingIds[sector.id] ? (
                              <i className="ph ph-spinner-gap absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 animate-spin"></i>
                          ) : !pitches[sector.id] && (
                              <button 
                                onClick={() => generatePitch(sector)}
                                className="absolute right-2 top-1/2 -translate-y-1/2 text-brandCyan hover:text-white"
                              >
                                  <i className="ph ph-magic-wand"></i>
                              </button>
                          )}
                      </div>

                      {pitches[sector.id] && (
                          <TypewriterText text={pitches[sector.id]} />
                      )}
                  </div>
              </div>
              );
          })}
      </div>
    </div>
  );
}
