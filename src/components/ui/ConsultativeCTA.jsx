import React from 'react';
import { useStore } from '@nanostores/react';
import { userContext } from '../../store/index.js';

export default function ConsultativeCTA({ className = "btn-primary text-lg" }) {
  const context = useStore(userContext);

  let ctaText = "Diagnóstico estratégico gratuito";
  
  if (context.rol === 'cdo' || context.sector === 'publico') {
    ctaText = "Solicita diagnóstico de madurez en IA";
  } else if (context.rol === 'cfo' || context.sector === 'finanzas' || context.sector === 'seguros') {
    ctaText = "Auditoría de ROI y pérdidas evitables";
  } else if (context.rol === 'ceo') {
    ctaText = "Evalúa potencial estratégico de IA";
  }

  return (
    <button 
      className={className} 
      onClick={() => document.dispatchEvent(new CustomEvent('open-chatbot'))}
    >
      {ctaText}
    </button>
  );
}
