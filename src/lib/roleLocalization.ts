import sectorsCorpus from '../data/sectorsCorpus.json';

/**
 * Maps a sector slug (e.g. 'publico', 'finanzas') to its corresponding ID in sectorsCorpus (e.g. 'sector-publico').
 */
export function mapSectorSlugToId(sectorSlug: string | null, sectorsList: any[] = sectorsCorpus): string | null {
  if (!sectorSlug) return null;
  const found = sectorsList.find(
    s => s.id === sectorSlug || s.slug === sectorSlug || s.id === `sector-${sectorSlug}` || s.id.endsWith(`-${sectorSlug}`)
  );
  return found ? found.id : null;
}

/**
 * Resolves localized role title if sectorObj has a roleEquivalent for persona.id.
 * Output format: "<Etiqueta local> (<SIGLA>)" if equivalent exists, else persona.title.
 * Defensively prevents double parentheticals.
 */
export function getLocalizedRoleTitle(persona: any, sectorObj: any): string {
  if (!persona) return '';
  if (!sectorObj || !sectorObj.roleEquivalents || !sectorObj.roleEquivalents[persona.id]) {
    return persona.title || '';
  }

  const localTitle = sectorObj.roleEquivalents[persona.id];
  const acronym = persona.id.toUpperCase();

  // If localTitle already includes acronym (e.g. "(CIO)" or "(OTI)") or ends with any parenthesis, format cleanly
  const lowerLocal = localTitle.toLowerCase();
  if (lowerLocal.includes(`(${persona.id})`) || lowerLocal.includes(`(${acronym.toLowerCase()})`)) {
    return localTitle;
  }

  // Prevent double trailing parentheticals if localTitle ends with any ')'
  if (localTitle.trim().endsWith(')')) {
    return `${localTitle} ${acronym}`;
  }

  return `${localTitle} (${acronym})`;
}
