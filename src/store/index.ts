import { atom } from 'nanostores';

// Compartimos la última interacción del usuario entre las islas interactivos
export const lastUserQuery = atom('');
export const chatbotOpen = atom(false);

// Spec 002: Context-Aware Chaining and Semantic Highlighting
export const userChallenge = atom('');
export const semanticHighlight = atom({});

// Spec 013: Conversión Consultiva
export const userContext = atom({ rol: null, sector: null, dismissed: false });
if (typeof window !== 'undefined') {
  const stored = localStorage.getItem('datanestiq_context');
  if (stored) {
    try {
      userContext.set(JSON.parse(stored));
    } catch(e) {}
  }
  userContext.listen(val => {
    localStorage.setItem('datanestiq_context', JSON.stringify(val));
  });
}
