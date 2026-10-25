# Prompt IA (navegador) — Navegación detallada de las PÁGINAS INTERNAS (todo menos el home) + delays (reporte para Claude)

Eres un QA con **navegador real**. Recorre **todas las páginas internas** del sitio Datanestiq (el home se audita aparte), verifica contenido, enlaces, hidratación de islas y **mide latencias reales**. Emite un **reporte estructurado con evidencia concreta** para que Claude (Opus 4.8) lo audite. No generalices: cada afirmación con **valor concreto** (texto observado, código HTTP, ms, presencia/ausencia de elementos).

## Acceso al sitio
1. `npm run build`
2. `C:/xampp/php/php.exe -S localhost:8080 -t dist`
3. Base: `http://localhost:8080/`

## Reglas anti-falsos-positivos
- **`localStorage.clear()` + recarga** al empezar cada página (evita arrastrar contexto de rol).
- **Perplexity/crawlers no renderizan JS** → tú SÍ debes ejecutar JS; audita el DOM **renderizado**, no el HTML crudo.
- **Honestidad radical (Constitución §2):** el sitio **no debe** mostrar casos/testimonios/logos/certificaciones **fabricados**. Toda métrica de negocio debe llevar marca **`[EST]`**. Si ves una cifra o "caso de cliente" sin `[EST]` y sin fuente, **repórtalo como posible violación de honestidad** (no como acierto).
- **Páginas legales** llevan el marcador **`[BORRADOR LEGAL]`** (esperado, no es error).
- **0-LLM / seguridad:** ninguna página estática debe filtrar llamadas inesperadas; reporta cualquier `POST` a endpoints (`chat.php`, `save_wizard.php`) que no hayas provocado tú.

## Medición de delay (obligatorio, por página)
- **TTFB / carga:** usa `performance.getEntriesByType('navigation')[0]` → reporta `responseStart` y `loadEventEnd` (ms).
- **Islas `client:visible`:** al hacer scroll, mide el delay hasta que hidratan y son interactivas.
- Clasifica: **Instantáneo <100ms · Aceptable 100–1000ms · Lento 1–3s · Crítico >3s**.

---

## Inventario de páginas (recórrelas TODAS)

### G1 — Hubs
- `/soluciones/` — hub de soluciones (grilla dinámica).
- `/sectores/` — hub de sectores.
**Verifica:** cada tarjeta enlaza a su detalle (sin `href="#"`), imágenes/íconos cargan, breadcrumbs JSON-LD presentes.

### G2 — Detalle de Soluciones (6 páginas)
`/soluciones/ai-data-science/` · `/soluciones/business-intelligence/` · `/soluciones/data-engineering/` · `/soluciones/estrategia-datos-ia/` · `/soluciones/hiperautomatizacion/` · `/soluciones/sistemas-digitales/`
**En cada una verifica:** techStack, competitivePositioning, proofPoints, KPIs con **`[EST]`**, deploymentModels/engagementModels, y el bloque **"Resolvemos tus dudas"** (objeción→respuesta). CTA hacia diagnóstico/chatbot funciona.

### G3 — Detalle de Sectores (10 páginas)
`/sectores/publico/` · `/sectores/finanzas/` · `/sectores/salud/` · `/sectores/educacion/` · `/sectores/logistica/` · `/sectores/manufactura/` · `/sectores/mineria/` · `/sectores/retail/` · `/sectores/seguros/` · `/sectores/telecomunicaciones/`
**En cada una verifica:** contenido específico del sector (regulaciones/KPIs reales), personas relevantes, bloque de objeciones. **`/sectores/publico/`** además: continuidad institucional, seguridad On-Prem/VPC, mención OECE/normativa. Reporta cualquier página que se vea **genérica** (sin diferenciación sectorial).

### G4 — Business Case Estimator
`/business-case/` (isla `BusinessCaseEstimator`).
**Verifica:** el badge **`[EST] Escenario Ilustrativo`**; mueve los sliders (fracción direccionable, etc.) y confirma que el ROI recalcula **client-side** (sin `chat.php`). Prueba un caso pequeño → debe mostrar honestamente **"N/A (ROI Negativo)"** si aplica (no un ROI inflado). Mide el delay de recálculo. El opt-in de contacto usa `save_wizard.php` (solo si tú envías).

### G5 — Casos de éxito / Nosotros
- `/casos-de-exito/` — **CRÍTICO de honestidad:** ¿hay casos con nombres de clientes reales sin evidencia? ¿O están marcados como ilustrativos/`[EST]`? Reporta literal lo que muestra.
- `/nosotros/` — equipo/empresa. Reporta si hay datos de equipo reales o placeholders.

### G6 — Legales
`/privacidad/` · `/terminos/` — deben llevar **`[BORRADOR LEGAL]`**. Verifica que existen (no 404) y que el footer enlaza a ambas (sin `href="#"`).

### G7 — Wiki
`/wiki/arquitectura/` · `/wiki/skills-overview/` — contenido técnico generado. Verifica que renderizan y que los enlaces internos funcionan.

### G8 — Blog (12 posts)
`/blog/` (índice) + los 12 posts:
`analitica-contrataciones-estado` · `calidad-padrones-ia` · `cio-modernizacion-estado` · `dashboards-ejecutivos-gobierno` · `data-ia-ebitda` · `gobierno-dato-estado` · `ia-corporativa-produccion` · `ia-segura-estado` · `interoperabilidad-institucional` · `roi-ia-evaluacion-cfo` · `ventaja-competitiva-datos-ceo` (todos bajo `/blog/<slug>/`).
**Verifica:** el índice `/blog/` los lista, cada post abre (no 404), tiene título/cuerpo/fecha, y los CTAs internos funcionan. Reporta cualquier post vacío o con enlaces rotos.

---

## Chequeos transversales (en toda la navegación)
1. **Enlaces rotos:** registra cualquier `404` o `href="#"` (navbar, footer, tarjetas, CTAs). Da la URL exacta.
2. **Navbar/footer** consistentes en todas las páginas; menú mobile (hamburguesa) funciona.
3. **Consola:** errores JS literales por página.
4. **Responsive:** repite 2–3 páginas clave en viewport móvil (375px) y reporta roturas de layout.
5. **SEO básico:** `<title>` y `<meta description>` diferenciados por página (no todos iguales al home).

## Formato del reporte (para Claude)
1. **Tabla por página:** `URL · Código HTTP · Carga (ms) + clasificación · Contenido OK/Genérico/Vacío · Enlaces rotos · Errores consola`.
2. **Sección Honestidad:** lista literal de cualquier cifra sin `[EST]` o "caso/cliente/testimonio" que parezca fabricado (con la URL y el texto exacto).
3. **Sección Enlaces rotos / 404** (consolidada, con URLs).
4. **Tabla de latencias** (páginas ordenadas por tiempo de carga; marca las >3s).
5. **Qué NO pudiste verificar y por qué.**
6. **Veredicto:** ¿todas las páginas internas cargan, están diferenciadas y son honestas? ¿Cuáles requieren atención?

Sé **literal** y **conservador**: si no lo interactuaste/mediste, márcalo *no verificado*, no como defecto ni como acierto. **No inventes.**
