# Plan de Implementación: Spec 013 (Conversión Consultiva por Rol × Sector)

## 1. Overview y Entregables
**Objetivo:** Construir e instanciar un "Motor Consultivo" reutilizable (A1-A9) que adapte dinámicamente la experiencia del sitio web (Hero, CTAs, Chatbot, Casos ROI) según la tupla `(rol, sector)` detectada, eliminando la fricción y transformando el sitio en un asesor pre-venta especializado.
**Entregables:** 
- `spec.md` (Completado).
- Integración de `userContext` en el bus de Nanostores (`src/store/index.ts`).
- Chips de contexto (hero) y CTAs dinámicos en los layouts de Astro.
- Formulario de contacto consultivo con redacción de PII.
- Tres paquetes de fases (Público, CFO, CEO) que instancian este motor.

## 2. El Motor (A1-A9) — Implementación

- **Componentes y Store (A9):** Se añadirá `export const userContext = atom({ rol: null, sector: null })` en `src/store/index.ts`, persistido en `localStorage` (via `@nanostores/persistent` si aplica, o inicializador local). 
- **Chips en Hero:** Un nuevo componente `<ContextChips client:load />` se añadirá en las landings principales. Si el usuario cierra el chip, se guarda `{ dismissed: true }` en localStorage.
- **CTA Dinámico (A3):** Un componente de Isla o Web Component `<ConsultativeCTA client:load />` leerá `userContext` para mutar entre "Diagnóstico estratégico gratuito" (default) y variantes específicas.
- **Casos ROI / Evidencia (A6):** Un componente `<IllustrativeRoiCase />` que renderiza por diseño la insignia `[EST]` y el disclaimer *"Modelo ilustrativo"* permanentemente anclado, leyendo los casos desde un JSON derivado del corpus para garantizar honestidad.
- **Formulario (A5):** Un componente `<ConsultativeForm client:load />` conectado a `chat.php` o un endpoint equivalente (ej. `contact.php`), que valida campos y asegura que el payload carece de PII cruda si se va a loguear (usando `remote_extract` o similar).

## 3. Anexo por Fase

### Fase 1: Sector Público
- **Objetivos:** Reforzar seguridad, compliance y modalidades de adopción institucional.
- **Cambios Taxonomía:** Consumir los matices institucionales ya inyectados (F3).
- **Cambios Páginas:** `/sectores/publico.astro` añade el renderizado del FAQ institucional y destaca on-prem/VPC en la parte superior si el contexto es "Público".
- **Chatbot:** Extender el JSON de configuración para reconocer intents de "expedientes", "auditoría" y "trazabilidad" en `chat.php` system prompt.
- **Blog:** 4 artículos top-up generados con `docs-generator.mjs`.

### Fase 2: CFO Económico
- **Objetivos:** Pivotar la narrativa hacia el ROI cuantificable, TCO y el dolor de recambio de ERP.
- **Cambios Taxonomía:** Lectura estricta de las objeciones "ROI abstracto" en `/sectores/finanzas.astro` y `/sectores/seguros.astro`.
- **Cambios Páginas:** Integración de los modelos `[EST]` etiquetados claramente. Creación de una ruta o componente modal para el "Business Case Builder".
- **Chatbot:** Incorporar intents de "EBITDA", "TCO", "payback".
- **CTA:** "Auditoría de potencial de ROI".

### Fase 3: CEO Estratégico
- **Objetivos:** Posicionar a Datanestiq como sparring estratégico de disrupción prudente.
- **Cambios Taxonomía:** Lectura de objeciones sobre "IA como moda".
- **Cambios Páginas:** Si el `userContext.rol` == 'ceo', el hero global ajusta su subtítulo ("Construye una ventaja asimétrica") y re-prioriza la grilla de servicios para poner Estrategia de Datos de primero.
- **Chatbot:** Reconocimiento de intents "ventaja competitiva" y "crecimiento".

## 4. Riesgos y Mitigaciones

| Riesgo | Mitigación |
| --- | --- |
| **Falsificación de prueba social** (Inventar logos/métricas para rellenar). | **Mitigación Activa:** Uso de la etiqueta `[EST] MODELO ILUSTRATIVO` hardcodeada a nivel de componente (`<IllustrativeRoiCase>`). Requerirá tu confirmación explícita. |
| FOUC (Flash of Unstyled Content) al hidratar los CTAs y Chips. | Uso prudente de esqueletos iniciales neutros (el CTA "Diagnóstico estratégico gratuito" se muestra SSG, y en cliente se sustituye suavemente). |
| Bloqueo del Chatbot Guiado. | Todo mapeo de intents 0-LLM se hará inyectando nuevas llaves en la constante del árbol estático de la Isla, sin añadir peticiones fetch de red. |

## 5. Pregunta Abierta y Confirmación
> [!IMPORTANT]
> **Decisión Requerida sobre Casos ROI (CFO/CEO):** 
> Para cumplir con la honestidad radical, propongo crear un componente visual estricto para los Casos ROI que incluirá siempre un badge permanente: **"[EST] Escenario Ilustrativo"** y un tooltip o nota al pie: *"Esta proyección es un modelo basado en promedios de la industria y nuestras capacidades arquitectónicas, no representa un cliente real histórico"*. 
> ¿Estás de acuerdo con este enfoque y con el plan presentado?

## 6. Tareas (`tasks.md` preliminar)
**Fase 0 (Motor):**
- [ ] Definir `userContext` en `src/store/index.ts`.
- [ ] Construir `<ContextChips client:load />`.
- [ ] Refactorizar el `<button>` de auditoría a `<ConsultativeCTA client:load />`.
- [ ] Construir componente `<IllustrativeRoiCase />`.

**Fase 1 (Público):**
- [ ] Inyectar contexto institucional en Home (si contexto activo).
- [ ] Ajustar intents del chatbot público.
- [ ] Generar blogs adicionales de Sector Público.

**Fase 2 (CFO):**
- [ ] Cargar modelos ilustrativos de ROI en Finanzas/Seguros.
- [ ] Agregar bloque de "Coexistencia con Core/ERP".
- [ ] Ajustar chatbot para intents económicos.

**Fase 3 (CEO):**
- [ ] Ajustar highlight del Hero y grilla (si contexto == CEO).
- [ ] Incorporar FAQ estratégica (sparring).
- [ ] Adaptar CTA a "Evaluación de potencial estratégico".
