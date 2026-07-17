# Plan de Implementación: Spec 010 (Generador Multi-Destino)

## 1. Objetivo
Convertir el script de auditoría OpenWiki (Spec 005) en un **motor unificado de generación documental multi-destino** capaz de alimentar la Wiki, los manifiestos de Agentes, los resúmenes de Skills y los borradores del Blog, garantizando resiliencia operativa y alta disponibilidad a través de balanceo de carga LLM.

## 2. Arquitectura del Motor (`scripts/docs-generator.mjs`)
- **Routing por Target:** El motor procesa comandos con `--target=<wiki|agents|skills|blog>` y aplica diferentes system prompts, validaciones y destinos de guardado para cada uno.
- **Detección de Cambios Segura:** Utiliza `git ls-files` como lista blanca de seguridad para no procesar archivos ignorados o sensibles (contraseñas, `.env`, `wp-config.php`). El modo wiki obtiene su contexto diferencial con `git diff HEAD~1..HEAD`.
- **Parser Defensivo (IGNORE Blocks):** Extrae bloques delimitados por `<!-- OPENWIKI:IGNORE:START/END -->` antes de enviar el contenido al LLM, los reemplaza con placeholders seguros, y los inyecta idénticos tras el output para garantizar inmutabilidad.
- **Tipado Zod Frontmatter:** Genera bloques Frontmatter de Markdown asegurando la inclusión de campos requeridos (title, description, seoScore, tags) escapando comillas para evitar rupturas de build. Para el blog, `pubDate` se graba sin comillas de string.

## 3. Resiliencia y Balanceo LLM
- **Pool de Proveedores:** Configurado para Groq (primario/rápido), DashScope (Qwen, económico) y Gemini (Google). 
- **Selección Aleatoria (Round-Robin):** Inicia la iteración con un `poolIndex` aleatorio para balancear los costos y peticiones a lo largo del tiempo.
- **Atomic Failover:** Si un proveedor falla con HTTP 4xx o 5xx (ej. 429 Too Many Requests), el motor automáticamente reintenta en caliente con el siguiente proveedor de la lista.
- **Backoff Exponencial:** Si toda la lista se agota por Rate Limits (429), entra en un loop de backoff que duplica el tiempo de espera por cada reintento, evitando denegar el servicio permanentemente en el CI/CD.

## 4. Estrategia de Testing (Dry Run)
- Flag `--dry-run` para simular la ejecución de los algoritmos sin tocar APIs ni consumir tokens.
- Flag `--seed` para generar un documento fundacional (`arquitectura.md`) que lee el árbol del repositorio inicial.
