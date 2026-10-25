import React from 'react';
import { useStore } from '@nanostores/react';
import { userContext } from '../../store/index';
import personas from '../../data/personasCorpus.json';
import sectorsCorpus from '../../data/sectorsCorpus.json';
import { mapSectorSlugToId, getLocalizedRoleTitle } from '../../lib/roleLocalization';

/**
 * HeroRoleLine — Propuesta de valor por rol activo (Spec 013 A9 / F-11)
 * Localizado según sector activo vía getLocalizedRoleTitle y mapSectorSlugToId.
 */
export default function HeroRoleLine() {
  const ctx = useStore(userContext);
  if (!ctx.rol) return null;

  const persona = personas.roles.find(r => r.id === ctx.rol);
  if (!persona) return null;

  // Precisión 1: Mapear slug de sector (ej. 'publico') a ID de sectorsCorpus ('sector-publico')
  const sectorId = mapSectorSlugToId(ctx.sector, sectorsCorpus);
  const sectorObj = sectorId ? sectorsCorpus.find(s => s.id === sectorId) : null;
  const displayRoleTitle = getLocalizedRoleTitle(persona, sectorObj);

  const goal = persona.goals?.[0] || '';
  const criterion = persona.decisionCriteria?.[0] || '';

  if (!goal) return null;

  return (
    <div className="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-brand/10 border border-brandCyan/30 text-xs md:text-sm text-gray-200 animate-in fade-in duration-500 max-w-2xl mx-auto shadow-lg">
      <span className="w-2 h-2 rounded-full bg-brandCyan animate-pulse flex-shrink-0"></span>
      <span className="text-white font-semibold">{displayRoleTitle}:</span>
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
