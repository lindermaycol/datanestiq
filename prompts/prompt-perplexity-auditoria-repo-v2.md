# Prompt para Perplexity — Auditoría del repositorio Datanestiq (rama de trabajo)

Eres un auditor de repositorios (arquitectura, disciplina SDD y postura de seguridad). Vas a revisar el repo de Datanestiq —consultora B2B de IA y Datos— en **GitHub**.

## 🔴 QUÉ mirar (crítico — no repitas el error anterior)
- **Repo:** `https://github.com/lindermaycol/datanestiq`
- **Revisa la rama `007-multi-pagina`**, NO `main`. Todo el trabajo real vive ahí:
  - Árbol: `https://github.com/lindermaycol/datanestiq/tree/007-multi-pagina`
  - Y el **Pull Request #1** (`007-multi-pagina` → `main`): `https://github.com/lindermaycol/datanestiq/pull/1`
- **`main` es una foto vieja (solo "Fase 0")** — si la auditas, verás un estado obsoleto. Ignórala salvo para comparar.
- Si el repo fuera privado y no puedes acceder, dilo y detente.

## Contexto (para que no malinterpretes la estructura)
- El **sitio activo es Astro (SSG) + islas React** en `src/`. El backend del chatbot/leads es **PHP** en `public/api/`.
- Los archivos `wp-*` de la raíz (`wp-admin`, `wp-includes`, `wp-config-sample.php`, `readme.html`) son **legado** de un setup inicial de WordPress; **no** son el sitio de producción. La ruta headless-WordPress (Spec 008) está **en pausa** (ver `planes/Fases.md`).
- Metodología **SDD / Spec-Kit**: specs en `specs/` (001–013), estado en `planes/ESTADO-SPECS.md`, hoja de ruta en `planes/Fases.md`, prompts de trabajo e insumos en `prompts/` y `planes/`.
- **Taxonomía = fuente única de verdad:** el contenido se autoría en `src/content/pillars/*.yaml`, `src/content/sectors/*.yaml`, `src/data/personas.json`; `scripts/build-taxonomy.mjs` valida (Zod + integridad de aristas) y **genera** los JSON de `src/data/*`.
- No ejecutas código: **verifica en el fuente**; marca como "no verificable" lo que requiera runtime.

## Qué auditar

### 1. Historia de commits y trazabilidad SDD (en `007-multi-pagina`)
- ¿Los commits recientes son limpios y **referencian el trabajo/spec** que implementan? (ej. Spec 013, fundación, honestidad, fixes de conversión).
- ¿La progresión refleja el ciclo spec → plan → implementación → auditoría?

### 2. Arquitectura y coherencia
- Confirma el stack en `astro.config.mjs` (`output: 'static'`), `src/content.config.ts` (Content Collections + Zod), `src/lib/schemas.js` (`pillarSchema`/`sectorSchema`/`personaSchema`, incluidos campos como `objectionResponses`, `deploymentModels`, `roiCases`, `institutionalContinuity`), y el bus de estado en `src/store/index.ts` (nanostores).
- ¿La taxonomía como fuente de verdad está bien implementada? (`build-taxonomy.mjs` + fuentes YAML/JSON → `src/data/*.json`). ¿Los JSON generados NO se editan a mano?

### 3. 🔴 Postura de seguridad (validación externa muy valiosa)
Verifica que el repositorio **NO exponga secretos ni PII** en la rama `007-multi-pagina`:
- ¿Hay algún `.env`, archivo bajo `secure_leads/`, `*.jsonl`, o `wp-config.php` (el real, no `-sample`) **commiteado**? (No debería.) Busca patrones de claves: `sk-`, `gsk_`, `AIza`, `*_API_KEY=` con valor.
- ¿El `.gitignore` excluye `.env`, `secure_leads/`, `*.jsonl`, `.venv/`, `wp-config.php`?
- ¿Las claves se leen por entorno (`getenv` en `public/api/chat.php`, `import.meta.env`) y no hardcodeadas?
- ¿`chat.php`/`save_wizard.php` redactan PII (`[EMAIL_REDACTED]`/`[PHONE_REDACTED]`) y escriben leads a `secure_leads/` (fuera de `public/`)?

### 4. 🔴 Honestidad en el código/contenido
- ¿El contenido evita **prueba social fabricada**? Revisa `src/components/ui/TrustLayer.astro` y `src/content/pages/home.md`: **no** debe haber logos de clientes inventados ni testimonios con nombre/empresa/cifra falsos (deberían ser "Tecnologías que dominamos" con stack real; el testimonio retirado).
- ¿Las métricas de resultado en la taxonomía (`proofPoints`, `kpis`, `roiCases`) van marcadas **`[EST]`** y no como casos reales?
- ¿El FAQ (`src/components/ui/Faq.astro`) evita afirmar "nuestros clientes reportan…" sin respaldo?

### 5. Documentación
- ¿El `README.md` (raíz) describe el proyecto, stack, cómo levantarlo (incluido servir con PHP), estructura y metodología? ¿`planes/Fases.md` cuenta la evolución por fases y los pendientes?
- ¿Hay algo que un colaborador nuevo no entendería?

## Formato de salida (Markdown)
1. **Resumen ejecutivo** (5–7 líneas): madurez del repo, disciplina SDD, y postura de seguridad.
2. **Tabla de hallazgos:** `ID · Prioridad (P0–P3) · Área (arquitectura/SDD/seguridad/honestidad/docs) · Hallazgo · Evidencia (ruta de archivo) · ¿Verificable o requiere runtime?`.
3. **Sección Seguridad:** veredicto claro — ¿se filtra algún secreto/PII? (idealmente "no se detectaron").
4. **Sección Honestidad:** ¿queda alguna prueba social fabricada o métrica sin `[EST]`?
5. **Recomendaciones priorizadas** (máx. 8), separando SDD/docs, arquitectura y seguridad.

Cita siempre **evidencia** (ruta de archivo o commit). Si algo no lo puedes verificar por tus límites (no ejecutas código, o no ves un archivo gitignored), **dilo**, no lo asumas como defecto. Y **confirma explícitamente qué rama auditaste** (debe ser `007-multi-pagina`).
