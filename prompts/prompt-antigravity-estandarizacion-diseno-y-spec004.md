# Prompt para Antigravity: Estandarización del Sistema de Diseño (vs. Prototipo Fase 0) + Ejecución de los prompts pendientes de la Spec 004

Actúa como un **Director de Diseño (Design Systems Lead), copywriter B2B senior (framework de los 10 agentes de la Spec 004) y Frontend Engineer de Astro/React**.

## Contexto de esta auditoría

Se restauró `prototype/index.html` y `prototype/app.js` (habían quedado en 0 bytes por un borrado accidental; se recuperaron desde el commit `334b0df` del historial de git — ya están de vuelta en el repo, íntegros). Al compararlos con el sitio Astro actual (`src/`), Claude (Sonnet 5) encontró que **el prototipo — que sí pasó por el proceso completo de la Spec 001 (Elevación Premium) y el dogfooding de los 10 agentes de la Spec 004 — es visualmente más sobrio y "premium" que lo que terminó viviendo en Astro tras la migración (Spec 006)**. La migración no portó fielmente ese refinamiento: introdujo un gradiente azul-morado (justo el cliché que la Constitution prohíbe), diluyó el flujo de captura de leads (que en el prototipo era un formulario visible, y en Astro es una extracción silenciosa por regex), y la migración multi-página (Spec 007) nunca volvió a pasar por el framework de copywriting de los 10 agentes.

Este documento resuelve ambos frentes: (1) estandarizar el sistema de diseño de Astro alineándolo al lenguaje visual más sobrio del prototipo, y (2) ejecutar sobre la arquitectura multi-página real los agentes de la Spec 004 que nunca se aplicaron (04, 05, 06, 10).

Trabaja sobre `c:\xampp\htdocs\datanestiq\`. Ejecuta los bloques en orden. Al final, corre `npm run build` y `npm run preview` para validar.

---

## BLOQUE 1: Consolidar el sistema de color (eliminar el gradiente azul-morado y la colisión de nombres)

**Problema de fondo:** hoy conviven dos esquemas de color con el mismo nombre y distinto significado. En `prototype/index.html`, `accent` = `#F3F4F6` (casi blanco, usado para detalles neutros). En `tailwind.config.mjs` actual, `accent` = `#8B5CF6` (morado). Además, `primary` (`#3B82F6`) y `brand` (`#3B82F6`) son literalmente el mismo color con dos nombres — quedó duplicado cuando se parchearon las clases rotas en la auditoría anterior. Esto genera confusión y es la causa directa del gradiente "azul → morado" que la Constitution prohíbe explícitamente (`Navbar.astro` y `Hero.astro` usan `bg-gradient-to-r from-primary to-accent`).

### 1.1 — Nueva paleta canónica en `tailwind.config.mjs`

Reemplaza el bloque `colors` completo por esta paleta única (basada en la del prototipo, que ya fue validada en la Spec 001):
```js
colors: {
  background: '#0A0A0B',
  surface: '#111827',
  darker: '#050505',
  brand: '#2563EB',        // Azul Acero (antes "primary" #3B82F6 — se reemplaza por el tono más sobrio del prototipo)
  brandHover: '#1D4ED8',
  brandCyan: '#0891B2',    // antes #22D3EE — se atenúa para que no compita como si fuera un segundo color de marca
  secondary: '#10B981',    // se mantiene, único uso actual: ícono de Business Intelligence en Services.astro
},
```
Elimina `primary` y `accent` (el morado) por completo de la config. No agregues un reemplazo morado — el objetivo es tener **una sola familia cromática** (azul acero + cian apagado) más blancos/grises, sin un segundo color de marca compitiendo.

### 1.2 — Reemplazar todos los usos de `primary`/`accent` en el código

Se detectaron usos de clases `*-primary` / `*-accent` en 9 archivos. Reemplaza sistemáticamente `primary` → `brand` y `accent` → `brandCyan` en cada uno, con dos excepciones específicas de gradiente que debes tratar distinto (ver abajo):
- `src/components/ui/Footer.astro`
- `src/components/ui/Services.astro`
- `src/components/ui/Navbar.astro`
- `src/styles/global.css`
- `src/components/ui/Hero.astro`
- `src/components/ui/Faq.astro`
- `src/pages/wiki/[slug].astro`
- `src/components/ui/SolutionContrast.astro`
- `src/components/ui/SolutionHero.astro`

**Excepción — gradientes de marca/headline (eliminar el gradiente de color, usar el monocromático del prototipo):**
- En `Navbar.astro`, el logo `DATANESTIQ` usa `bg-clip-text text-transparent bg-gradient-to-r from-primary to-accent`. Cámbialo a un tratamiento sólido (sin gradiente): `text-white`, o si quieres conservar algo de textura, `bg-gradient-to-r from-white to-gray-400` (igual que el H1 del prototipo).
- En `Hero.astro`, el `<span>` del H1 (`Ventaja Asimétrica`) usa el mismo gradiente azul-morado. Reemplázalo por el patrón exacto del prototipo: `text-transparent bg-clip-text bg-gradient-to-r from-white to-gray-500`.
- Revisa si `SolutionHero.astro` tiene algún tratamiento de headline similar y aplica el mismo criterio (monocromático, no azul-morado).

### 1.3 — Botones en forma de píldora (`rounded-full`), como en el prototipo

El prototipo usa `rounded-full` para todos los CTAs primarios (Hero, Navbar, Metodología, FAB del chatbot); el Astro actual usa `rounded-lg`. Actualiza `.btn-primary` en `src/styles/global.css`:
```css
.btn-primary {
  @apply bg-brand hover:bg-brandHover text-white font-medium py-3 px-6 rounded-full shadow-[0_0_20px_-5px_rgba(37,99,235,0.5)] transition-all duration-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 focus-visible:ring-offset-background;
}
```
No cambies el radio de `.glass-card` (los `rounded-2xl` de las tarjetas de servicio/FAQ se quedan igual) — la píldora es específicamente para botones de acción, tal como en el prototipo.

### 1.4 — Verificación
Ejecuta `npm run build` y confirma visualmente en `npm run preview` que ya no queda ningún gradiente azul→morado en el sitio (Home ni páginas de `/soluciones/`).

---

## BLOQUE 2: Captura de lead visible (recuperar el patrón del prototipo, sin perder el chat libre por IA)

**Problema:** el prototipo tenía un formulario de captura **visible** (nombre + email, con validación y botón "Agendar Diagnóstico Gratuito") al final del flujo del chat. La versión Astro actual (`Chatbot.jsx`) solo extrae el email/teléfono **en silencio**, vía regex sobre el texto libre — el usuario nunca ve un formulario ni una confirmación explícita de que sus datos quedaron registrados. Esto es peor para la conversión y la confianza (un usuario premium B2B quiere saber que su solicitud fue recibida formalmente, no que un regex le leyó el mensaje).

**Corrección — añade una tarjeta de confirmación visible dentro de `Chatbot.jsx`:**

Cuando `leadData.email` se detecte por primera vez (o el usuario lleve más de 4 mensajes intercambiados sin haber confirmado datos), inserta en el hilo de mensajes una tarjeta de confirmación con los campos ya pre-rellenados (editables) y un botón explícito, en vez de solo guardarlo en silencio:

```jsx
const [leadConfirmed, setLeadConfirmed] = useState(false);
const [showLeadCard, setShowLeadCard] = useState(false);

useEffect(() => {
  if ((leadData.email || leadData.telefono) && !leadConfirmed && !showLeadCard) {
    setShowLeadCard(true);
  }
}, [leadData, leadConfirmed, showLeadCard]);

const confirmLead = () => {
  const existing = JSON.parse(localStorage.getItem('datanestiq_leads') || '[]');
  const updated = [...existing.filter(l => l.session !== sessionIdRef.current), { ...leadData, session: sessionIdRef.current, timestamp: new Date().toISOString() }];
  localStorage.setItem('datanestiq_leads', JSON.stringify(updated));
  setLeadConfirmed(true);
  setShowLeadCard(false);
};
```

Y en el JSX, justo antes del indicador de "escribiendo" (`isTyping`), renderiza la tarjeta si `showLeadCard` es `true`:
```jsx
{showLeadCard && (
  <div className="bg-brand/10 border border-brand/30 rounded-2xl p-4 mt-2">
    <p className="text-xs text-gray-300 mb-3">Confirma tus datos para que un arquitecto de datos de Datanestiq te contacte:</p>
    <input
      type="email"
      defaultValue={leadData.email || ''}
      onChange={(e) => setLeadData(prev => ({ ...prev, email: e.target.value }))}
      placeholder="Correo institucional"
      className="w-full bg-darker border border-white/10 rounded-lg p-2 text-sm text-white mb-2 focus:outline-none focus:border-brandCyan"
    />
    <input
      type="tel"
      defaultValue={leadData.telefono || ''}
      onChange={(e) => setLeadData(prev => ({ ...prev, telefono: e.target.value }))}
      placeholder="Celular o fijo"
      className="w-full bg-darker border border-white/10 rounded-lg p-2 text-sm text-white mb-3 focus:outline-none focus:border-brandCyan"
    />
    <button onClick={confirmLead} className="w-full btn-primary text-sm py-2">
      Confirmar y agendar diagnóstico
    </button>
  </div>
)}
```
Ajusta el `useEffect` existente que persistía en `localStorage` automáticamente (Bloque C de la auditoría global anterior) para que **ya no guarde en silencio apenas detecta el regex** — ahora la persistencia real ocurre en `confirmLead()`, tras confirmación explícita del usuario. Esto es más honesto con el usuario y da una señal de conversión mucho más clara para el negocio.

---

## BLOQUE 3: Ejecutar los agentes pendientes de la Spec 004 sobre la arquitectura multi-página real

Los 10 prompts de agentes viven en `specs/004-metodologia-desarrollo-digital/prompts/`. Solo se habían aplicado (dogfooding manual, una sola vez) al prototipo original de una sola página. Con la Spec 007 el sitio ya es multi-página — hay que volver a correr los agentes que nunca se ejecutaron, y hacerlo sobre la estructura real actual.

### 3.1 — Agente 06 (`06-about-page-storyteller.md`): crear la página "Nosotros"

Crea `src/pages/nosotros.astro` (usa `BaseLayout`, `Navbar`, `Footer` igual que `index.astro`). Aplica el framework del agente: apertura que genere confianza instantánea, una narrativa de por qué existe Datanestiq relevante para un C-Level, y un elemento humano (equipo, principios de trabajo) que invite a trabajar con la firma. Tono: consultivo, autoritario, cero clichés (regla de la Constitution sección 2). Añade el enlace en `Navbar.astro` (desktop y mobile) y en `Footer.astro`.

### 3.2 — Agente 04 (`04-portfolio-creator.md`): crear la página de Casos de Éxito

Crea `src/pages/casos-de-exito.astro`. **Importante — restricción ética:** Datanestiq aún no tiene casos de cliente reales y verificados en este repositorio (el testimonio de "RetailCorp / María Jiménez" en `TrustLayer.astro` y los logos "FINCORP/MEDITECH/etc." ya existentes están marcados como simulados en un comentario HTML). Para esta página nueva:
- **No inventes nombres de empresas reales ni cifras presentadas como datos verificados/auditados.** Usa arquetipos de industria sin nombre propio (ej. "Entidad financiera regional — Automatización de scoring crediticio") o, si describes un caso, enmárcalo explícitamente como *"caso ilustrativo"* / *"escenario representativo"* en el copy, para no exponer a Datanestiq a un reclamo por publicidad engañosa.
- Estructura: showcase de 3-4 "casos" (o "escenarios de aplicación" si prefieres el término más honesto), sección de confianza, y CTA de contacto.
- Añade el enlace en `Navbar.astro` y `Footer.astro`.
- Deja una nota en `specs/004-metodologia-desarrollo-digital/tech_debt.md` indicando que esta página usa contenido ilustrativo y debe reemplazarse por casos reales en cuanto Datanestiq tenga clientes dispuestos a ser citados.

### 3.3 — Agente 05 (`05-service-page-copywriter.md`): refinar el copy de las 6 páginas de solución

Aplica el framework del agente (headline que hable directo al cliente ideal, descripción que haga obvio el valor, CTA que se sienta natural) para revisar y mejorar — no reescribir desde cero, ya está aprobado por la taxonomía — los campos `hero.headline`, `hero.subheadline`, `contrast.problem/solution` de los 6 pilares en `src/data/taxonomyCorpus.json`. Mantén los `id`/`slug`/`seo` y `features` intactos (son la fuente de verdad de la Spec 003); solo pule la prosa persuasiva si detectas que puede sonar más específica o más orientada a ROI medible.

### 3.4 — Agente 10 (`10-objection-killing-faq.md`): expandir el FAQ de 3 a 5-7 objeciones reales

`src/components/ui/Faq.astro` solo tiene 3 preguntas. El framework pide 5-7 objeciones reales que frenan la conversión de un comprador B2B high-ticket. Añade 2-4 preguntas nuevas siguiendo el mismo patrón de acordeón accesible ya implementado (`aria-expanded`, `aria-controls`, `role="region"`), cubriendo objeciones que hoy no están cubiertas — por ejemplo: costo/inversión típica de un proyecto, tiempo de contratación/onboarding del equipo, qué pasa si el proyecto no cumple el ROI proyectado, y confidencialidad de datos internos compartidos durante el diagnóstico. Cierra la sección con una línea que empuje al lector indeciso hacia el CTA principal (abrir el chatbot o ir al diagnóstico), tal como pide el framework del agente.

### 3.5 — Navegación

Una vez creadas `nosotros.astro` y `casos-de-exito.astro`, actualiza `Navbar.astro` (desktop + mobile) agregando los dos enlaces nuevos junto a los existentes (`Servicios`, `Metodología`, `Casos ROI`), y actualiza el sitemap implícitamente (ya se genera solo vía `@astrojs/sitemap` al hacer build).

---

## Instrucciones de ejecución

1. Aplica el BLOQUE 1 completo y verifica visualmente que no quede ningún gradiente azul-morado.
2. Aplica el BLOQUE 2 y verifica manualmente: escribe un email en el chat, confirma que aparece la tarjeta de confirmación (no un guardado silencioso), y que solo tras hacer clic en "Confirmar" aparece en `localStorage.getItem('datanestiq_leads')`.
3. Aplica el BLOQUE 3 completo (las 4 sub-tareas). Ejecuta `npm run build` y confirma que ahora se generan también `dist/nosotros/index.html` y `dist/casos-de-exito/index.html`.
4. Actualiza `specs/004-metodologia-desarrollo-digital/tasks.md` (créalo si no existe, con el mismo formato retroactivo de `specs/001` y `specs/002`) marcando qué agentes (01-10) están aplicados y dónde, para que quede trazable de ahora en adelante.
5. Haz commit con el mensaje: `feat: estandariza sistema de diseño con prototipo Fase 0 y ejecuta agentes pendientes de Spec 004 (Nosotros, Casos de Exito, FAQ, copy de soluciones)`.

## Forma de respuesta

- Reporta bloque por bloque, con capturas o descripciones de qué cambió visualmente.
- Sé explícito sobre la restricción ética del Bloque 3.2 (contenido ilustrativo, no casos reales) en tu resumen final.
- Sección final obligatoria "Hallazgos adicionales" para cualquier cosa fuera de alcance.
- No toques `specs/008-headless-wordpress/` en este ciclo — sigue en pausa hasta que el resto del sitio esté validado, como ya se acordó.

---

**Nota:** Claude (Sonnet 5) auditará este trabajo, incluyendo una revisión visual comparando contra `prototype/` como referencia, una vez completado.
