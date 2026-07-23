import React from 'react';
import { useStore } from '@nanostores/react';
import { userContext } from '../../store/index';
import personas from '../../data/personas.json';

/**
 * HeroRoleLine — Propuesta de valor por rol activo (Spec 013 A9)
 * Compone goals[0] y decisionCriteria[0] de personas.json (100% Taxonomía, 0 inventado)
 */
export default function HeroRoleLine() {
  const ctx = useStore(userContext);
  if (!ctx.rol) return null;

  const persona = personas.roles.find(r => r.id === ctx.rol);
  if (!persona) return null;

  const goal = persona.goals?.[0] || '';
  const criterion = persona.decisionCriteria?.[0] || '';

  if (!goal) return null;

  return (
    <div className="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-brand/10 border border-brandCyan/30 text-xs md:text-sm text-gray-200 animate-in fade-in duration-500 max-w-2xl mx-auto shadow-lg">
      <span className="w-2 h-2 rounded-full bg-brandCyan animate-pulse flex-shrink-0"></span>
      <span className="text-white font-semibold">{persona.title}:</span>
      <span className="text-brandCyan font-medium capitalize">{goal}</span>
      {criterion && (
        <>
          <span className="text-gray-500">·</span>
          <span className="text-gray-300">Criterio: {criterion}</span>
        </>
      )}
    </div>
  );
}
