# Datanestiq — Sitio web (AI & Data Consulting B2B)

Sitio corporativo de **Datanestiq**, consultora B2B de Ingeniería de Datos, Inteligencia Artificial y Sistemas Digitales. Construido con **Astro (SSG) + islas React**, con una **taxonomía como fuente única de verdad** y un pequeño backend **PHP** para el chatbot y la captura de leads. El proyecto se desarrolla con metodología **SDD (Spec-Driven Development / Spec-Kit)**.

> **Nota sobre WordPress:** el repositorio nació de un setup local de WordPress (de ahí los archivos `wp-*` en la raíz). **El sitio activo es el proyecto Astro en `src/`.** La ruta headless-WordPress (Spec 008) está **en pausa**; los archivos core de WP no forman parte del build de producción.

---

## Stack

- **Astro 7** — `output: 'static'` (SSG), `site: https://datanestiq.com`
- **React** (islas hidratadas) + **nanostores** (bus de contexto entre islas)
- **Tailwind CSS**
- **@xenova/transformers** — buscador semántico con embeddings **en el navegador** (Edge AI, sin servidor)
- **Content Collections** (Zod) con `loader: glob()`
- **PHP 8.2** (XAMPP) — `public/api/chat.php` (chatbot texto-libre con failover Groq→DashScope→Gemini) y `public/api/save_wizard.php` (captura de leads)

## Requisitos

- Node.js 18+ (recomendado 20+)
- Para probar el chatbot/formularios en local: **PHP 8.2** (XAMPP u otro) — Astro no ejecuta PHP.

---

## Cómo levantarlo en local

### Desarrollo (UI)
```bash
npm install
npm run dev      # corre build-taxonomy (predev) y luego astro dev
```
> El buscador semántico y el chatbot **guiado** funcionan en `astro dev`. El chatbot en **texto libre** y los formularios necesitan PHP (ver abajo).

### Probar con el backend PHP (chatbot texto-libre + leads)
Astro no ejecuta PHP, así que se sirve el build con PHP:
```bash
npm run build
# servir dist/ con PHP (XAMPP):
"C:/xampp/php/php.exe" -S localhost:8080 -t dist
# abrir http://localhost:8080/
```

### Variables de entorno (`.env` en la raíz, **NO** se commitea)
```
GROQ_API_KEY=...
DASHSCOPE_API_KEY=...
GEMINI_API_KEY=...
```
`chat.php` las lee vía `getenv`. Sin claves, el chatbot degrada a un mensaje amable de captura de lead.

---

## Scripts

| Comando | Qué hace |
|---|---|
| `npm run dev` | Dev server (corre `build-taxonomy` antes vía `predev`) |
| `npm run build` | Build estático (corre `build-taxonomy` antes vía `prebuild`) |
| `npm run preview` | Preview del build (Node — **sin PHP**) |
| `npm run lint` | ESLint (`.js/.jsx/.ts/.astro`) |
| `node scripts/build-taxonomy.mjs` | Regenera los JSON del corpus desde la taxonomía (valida Zod + integridad de aristas; **exit 1** ante inconsistencia) |
| `node scripts/docs-generator.mjs` | Generador multi-destino (wiki/agents/skills/blog/page) — Spec 010/012 |
| `bash scripts/test-specs.sh` | Suite de tests de specs contra `dist/` |

---

## Estructura del repositorio

```
src/
  components/    # islas React (islands/) + componentes UI (.astro)
  content/       # Content Collections: pillars, sectors, pages, blog, wiki, industries
  data/          # JSON GENERADOS por build-taxonomy (NO editar a mano)
  lib/           # schemas.js (Zod), endpoints.js
  pages/         # rutas Astro (/soluciones/[id], /sectores/[slug], hubs, legales, business-case…)
  store/         # nanostores (userContext, semanticHighlight, userChallenge…)
  styles/        # global.css (Tailwind)
  workers/       # web worker del buscador semántico
public/api/      # chat.php, save_wizard.php, services.json (backend PHP)
scripts/         # build-taxonomy.mjs, docs-generator.mjs, tests
specs/           # 001–013: especificaciones SDD (Spec-Kit)
planes/          # planes de implementación, insumos SDD, informes de auditoría, ESTADO-SPECS.md
prompts/         # prompts de trabajo con el agente implementador (Antigravity) y de auditoría
secure_leads/    # PII (CSVs de leads) — 403 + gitignored, NUNCA se commitea
```

### Taxonomía = fuente única de verdad
El contenido (pilares/servicios, sectores, personas del comité de compra) se autoría en la **fuente** (`src/content/pillars/*.yaml`, `src/content/sectors/*.yaml`, `src/data/personas.json`). `build-taxonomy.mjs` valida y **genera** los JSON de `src/data/*` que consumen las páginas y las islas. **No edites los JSON generados; edita la fuente y reconstruye.**

---

## Metodología SDD / Spec-Kit

El trabajo sigue el ciclo **spec → plan → tasks → implementación → auditoría**:
- Las **especificaciones** viven en `specs/` (001–013).
- El estado real y auditado de cada spec está en **`planes/ESTADO-SPECS.md`**.
- La hoja de ruta por fases está en **`planes/Fases.md`**.
- La implementación la ejecuta un agente (Antigravity) a partir de prompts en `prompts/`, y cada entrega se **audita en navegador** (servido por PHP) antes de aprobarse.

---

## Seguridad (reglas del proyecto)

- **Nunca** se commitean: `.env`, `secure_leads/`, `*.jsonl`, `wp-config.php`, claves de API. El `.gitignore` es la barrera.
- **PII redactada** en logs (`[EMAIL_REDACTED]`/`[PHONE_REDACTED]`); los leads reales van a `secure_leads/` (403 + gitignored).
- Las claves se leen por entorno (`getenv`/`import.meta.env`), nunca hardcodeadas.
- **Honestidad de contenido:** cero casos de éxito, testimonios o logos de clientes fabricados; toda métrica estimada va marcada **`[EST]`**.

---

## Estado del proyecto

Ver **`planes/Fases.md`** (hoja de ruta) y **`planes/ESTADO-SPECS.md`** (estado detallado por spec). En resumen: fundación de páginas + Spec 013 (motor consultivo por rol × sector, fases Público/CFO/CEO) implementadas y auditadas; pendiente el **despliegue a IONOS** y la decisión sobre el port headless a WordPress (Spec 008, en pausa).
