# Spec 010: Generador Multi-Destino de Documentación

## 1. Introducción y Contexto
Debido a la imposibilidad de utilizar la solución `openwiki` basada en agentes (Spec 009) en tiers gratuitos por los límites de cuota (RateLimitQuotaExhaustedError), se pivota la estrategia hacia la construcción de un generador propio y controlado de documentación (Spec 010). Este generador evolucionará desde el script previamente creado (`scripts/docs-generator.mjs` de la Spec 005), transformándolo en una herramienta multi-destino capaz de generar documentación técnica, briefs para agentes, manuales de skills y artículos de blog.

El nuevo motor (`scripts/docs-generator.mjs`) mantendrá una estricta limitación de UNA llamada por documento, utilizando un balanceador de carga ponderado interno (Groq ⇄ DashScope ⇄ Gemini) que aproveche las capas compatibles con OpenAI de estos proveedores.

## 2. Destinos de Generación (Targets)

El generador debe soportar cuatro tipos de documentación (targets):

| Destino | Fuente | Salida | Esquema / Estilo |
|---|---|---|---|
| **wiki (técnica)** | código, specs, taxonomía (git diff o `--seed`) | `src/content/wiki/*.md` | Zod `wiki` (title≤100, description≤160, author, lastUpdated, tags, seoScore). Fundamentado en código real. Respeta bloques IGNORE. |
| **agentes** | estructura del repo, convenciones, specs | `AGENTS.md` (raíz) | Overview para agentes IA (arquitectura, convenciones, navegación). Sin frontmatter (no es colección). |
| **skills** | skills/comandos del proyecto | `src/content/wiki/skills-*.md` o `docs/` | Descripción de las skills/capacidades disponibles. |
| **blog** | Taxonomía (`src/data/*.json`) + brief del usuario | `src/content/blog/*.md` | Zod `blog` (title≤120, description≤200, pubDate, author=Datanestiq, tags[], draft). Editorial/on-brand. Evita frontmatter duplicado. |

## 3. Requisitos Técnicos

### 3.1. Balanceador y Conmutación por Error (Failover)
* **Reutilizar Pool Existente**: Se mantendrá la estructura actual de proveedores (Groq, DashScope, Gemini) configurada vía variables de entorno (`*_API_KEY`, `*_URL`, `*_MODEL`, `LLM_WEIGHT_*`).
* **Resiliencia ante 429**: Implementar un backoff exponencial si los tres proveedores responden con error 429. Si falla un proveedor, salta de inmediato al siguiente (round-robin + failover).
* **Reporte de Uso**: Al finalizar una ejecución (lote de N documentos), se debe mostrar en la consola un resumen de cuántas llamadas atendió cada proveedor.

### 3.2. Interfaz de Línea de Comandos (CLI)
Deberá aceptar, como mínimo, la siguiente sintaxis y parámetros:
* `--target=<wiki|agents|skills|blog>` (obligatorio).
* `--dry-run`: simulación de ejecución sin realizar llamadas de red ni sobrescribir archivos.
* `--since=<ref>` o `--seed`: para el target `wiki`.
* `--brief="<texto>"`: específico para el target `blog`.
* `--slug=<nombre>`: opcional para especificar el archivo de salida (útil en blog o agents).

(Para el manejo de lotes de documentos que provienen de múltiples orígenes diversos, se puede evaluar posteriormente la inclusión de un manifiesto `docs.manifest.json`).

### 3.3. Restricciones por Target
* **wiki/agents/skills**: Deben fundamentarse estrictamente en la estructura/código proveído en el prompt. El LLM **no debe inventar** funciones ni clases inexistentes. Se deben respetar los bloques protegidos (`<!-- OPENWIKI:IGNORE:START -->`).
* **blog**: Utilizar la taxonomía JSON (`src/data/`) para darle contexto editorial, sin depender de los commits del código. Incorporar un saneamiento preventivo mediante `stripFrontmatter` para asegurar que el modelo no inyecte un bloque de metadatos YAML redundante.

## 4. Requisitos de Seguridad Obligatorios
* **Filtrado de Secretos y Archivos Sensibles**: Por ningún motivo se enviarán a los proveedores LLM los contenidos de los siguientes archivos/rutas:
  * `.env` y derivados.
  * `secure_leads/` (donde reside PII).
  * `wp-config.php`.
  * Archivos de datos `*.jsonl` de logs y/o telemetría si contienen datos sensibles.
  * Archivos base de WordPress (`wp-admin`, `wp-includes`).
* **Filtro de Exclusión Explícito**: Las funciones de recolección de archivos (e.g. `getChangedFiles`) deben rechazar estos archivos utilizando expresiones regulares o una lista negra.

## 5. Criterios de Aceptación (Verificación E2E)
1. **Dry-Run Funcional**: `node scripts/docs-generator.mjs --target=<X> --dry-run` finaliza mostrando por pantalla el fragmento generado (simulado) sin efectuar llamadas a APIs de pago.
2. **Generación Real Balanceada**: La CLI termina con código 0 luego de generar un lote y muestra un registro explícito de que Groq, DashScope o Gemini respondieron (evidencia del balanceo en la consola).
3. **Esquemas Compatibles Zod**: Los archivos en `wiki` y `blog` compilan (vía `npm run build`) sin arrojar errores de validación Astro Zod (no hay propiedades requeridas faltantes, las fechas son correctas, etc.).
4. **Resistencia a Fallas (Failover)**: Al forzar una falla inyectando un token falso para el proveedor primario en `.env`, el script detecta el error (ej: 401) e instantáneamente procesa el documento con el proveedor secundario de la fila.
5. **Estado Actualizado**: `planes/ESTADO-SPECS.md` documenta a la Spec 010 como el generador base.
