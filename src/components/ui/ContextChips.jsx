import React from 'react';
import { useStore } from '@nanostores/react';
import { userContext, semanticHighlight } from '../../store/index.js';
import personas from '../../data/personas.json';

export default function ContextChips() {
  const context = useStore(userContext);

  if (context.dismissed || context.rol || context.sector) {
    return null; // Ya se eligió o se descartó
  }

  const handleSelect = (sectorSlug, rolId) => {
    userContext.set({ rol: rolId, sector: sectorSlug, dismissed: false });
    
    // Buscar los pilares de interes de este rol y publicarlos en semanticHighlight
    const role = personas.roles.find(r => r.id === rolId);
    if (role && role.pillarsOfInterest) {
      const highlightMap = {};
      role.pillarsOfInterest.forEach(pillar => {
        highlightMap[`service-${pillar}`] = 1.0;
      });
      semanticHighlight.set(highlightMap);
    }
  };

  const handleDismiss = () => {
    userContext.set({ ...context, dismissed: true });
  };

  return (
    <div className="flex flex-wrap items-center justify-center gap-2 mt-8 animate-fade-in z-50 relative">
      <span className="text-gray-400 text-sm mr-2 font-medium">Personaliza tu experiencia:</span>
      <button 
        onClick={() => handleSelect('publico', 'cdo_publico')}
        className="px-4 py-2 rounded-full text-sm font-medium bg-white/5 border border-white/10 text-gray-300 hover:text-white hover:bg-brand/20 hover:border-brand/50 transition-all flex items-center"
      >
        <i className="ph ph-bank mr-2"></i> Sector Público
      </button>
      <button 
        onClick={() => handleSelect('finanzas', 'cfo_economico')}
        className="px-4 py-2 rounded-full text-sm font-medium bg-white/5 border border-white/10 text-gray-300 hover:text-white hover:bg-brand/20 hover:border-brand/50 transition-all flex items-center"
      >
        <i className="ph ph-chart-line-up mr-2"></i> Finanzas / CFO
      </button>
      <button 
        onClick={() => handleSelect('retail', 'ceo_estrategico')}
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
