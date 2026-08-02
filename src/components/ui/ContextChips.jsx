import React from 'react';
import { useStore } from '@nanostores/react';
import { userContext, semanticHighlight } from '../../store/index.js';
import personas from '../../data/personasCorpus.json';

export default function ContextChips() {
  const context = useStore(userContext);

  const handleSelect = (sectorSlug, rolId) => {
    userContext.set({ rol: rolId, sector: sectorSlug, dismissed: false });
    
    // Buscar los pilares de interes de este rol y publicarlos en semanticHighlight
    const role = personas.roles.find(r => r.id === rolId);
    if (role && role.pillarsOfInterest) {
      const highlightMap = {};
      // Opt A: Slice top 2 para mantener la discriminación y utilidad visual
      role.pillarsOfInterest.slice(0, 2).forEach(pillar => {
        highlightMap[`service-${pillar}`] = 1.0;
      });
      semanticHighlight.set(highlightMap);
    }
  };

  const handleDismiss = () => {
    userContext.set({ ...context, dismissed: true });
  };

  if (context.dismissed) {
    return null; // Se descartó
  }

  if (context.rol || context.sector) {
    return (
      <div className="flex justify-center mt-8 animate-fade-in z-50 relative gap-2 flex-wrap items-center bg-darker/50 p-2 rounded-full border border-white/5">
        <span className="text-gray-400 text-xs mr-1 ml-2 font-medium">Viendo como:</span>
        <button 
          onClick={() => handleSelect('publico', 'cio')}
          className={`px-3 py-1.5 rounded-full text-xs font-medium transition-all flex items-center ${context.rol === 'cio' ? 'bg-brand/20 border border-brand/50 text-white' : 'bg-white/5 border border-white/10 text-gray-400 hover:text-white hover:bg-brand/10'}`}
        >
          <i className="ph ph-bank mr-1.5"></i> Público (CIO)
        </button>
        <button 
          onClick={() => handleSelect('finanzas', 'cfo')}
          className={`px-3 py-1.5 rounded-full text-xs font-medium transition-all flex items-center ${context.rol === 'cfo' ? 'bg-brand/20 border border-brand/50 text-white' : 'bg-white/5 border border-white/10 text-gray-400 hover:text-white hover:bg-brand/10'}`}
        >
          <i className="ph ph-chart-line-up mr-1.5"></i> Finanzas (CFO)
        </button>
        <button 
          onClick={() => handleSelect('finanzas', 'cdo')}
          className={`px-3 py-1.5 rounded-full text-xs font-medium transition-all flex items-center ${context.rol === 'cdo' ? 'bg-brand/20 border border-brand/50 text-white' : 'bg-white/5 border border-white/10 text-gray-400 hover:text-white hover:bg-brand/10'}`}
        >
          <i className="ph ph-database mr-1.5"></i> Datos (CDO)
        </button>
        <button 
          onClick={() => handleSelect('retail', 'ceo')}
          className={`px-3 py-1.5 rounded-full text-xs font-medium transition-all flex items-center ${context.rol === 'ceo' ? 'bg-brand/20 border border-brand/50 text-white' : 'bg-white/5 border border-white/10 text-gray-400 hover:text-white hover:bg-brand/10'}`}
        >
          <i className="ph ph-rocket mr-1.5"></i> Estrategia (CEO)
        </button>
        <button 
          onClick={() => {
            userContext.set({ rol: null, sector: null, dismissed: false });
            semanticHighlight.set({});
          }}
          className="ml-1 w-7 h-7 rounded-full flex items-center justify-center text-gray-500 hover:text-white transition-colors hover:bg-white/10"
          title="Resetear"
        >
          <i className="ph ph-x"></i>
        </button>
      </div>
    );
  }

  return (
    <div className="flex flex-wrap items-center justify-center gap-2 mt-8 animate-fade-in z-50 relative">
      <span className="text-gray-400 text-sm mr-2 font-medium">Personaliza tu experiencia:</span>
      <button 
        onClick={() => handleSelect('publico', 'cio')}
        className="px-4 py-2 rounded-full text-sm font-medium bg-white/5 border border-white/10 text-gray-300 hover:text-white hover:bg-brand/20 hover:border-brand/50 transition-all flex items-center"
      >
        <i className="ph ph-bank mr-2"></i> Sector Público
      </button>
      <button 
        onClick={() => handleSelect('finanzas', 'cfo')}
        className="px-4 py-2 rounded-full text-sm font-medium bg-white/5 border border-white/10 text-gray-300 hover:text-white hover:bg-brand/20 hover:border-brand/50 transition-all flex items-center"
      >
        <i className="ph ph-chart-line-up mr-2"></i> Finanzas / CFO
      </button>
      <button 
        onClick={() => handleSelect('finanzas', 'cdo')}
        className="px-4 py-2 rounded-full text-sm font-medium bg-white/5 border border-white/10 text-gray-300 hover:text-white hover:bg-brand/20 hover:border-brand/50 transition-all flex items-center"
      >
        <i className="ph ph-database mr-2"></i> Datos / CDO
      </button>
      <button 
        onClick={() => handleSelect('retail', 'ceo')}
        className="px-4 py-2 rounded-full text-sm font-medium bg-white/5 border border-white/10 text-gray-300 hover:text-white hover:bg-brand/20 hover:border-brand/50 transition-all flex items-center"
      >
        <i className="ph ph-rocket mr-2"></i> Estrategia / CEO
      </button>
      <button 
        onClick={handleDismiss}
        className="ml-2 w-8 h-8 rounded-full flex items-center justify-center text-gray-500 hover:text-white transition-colors hover:bg-white/5"
        title="Omitir"
      >
        <i className="ph ph-x"></i>
      </button>
    </div>
  );
}
