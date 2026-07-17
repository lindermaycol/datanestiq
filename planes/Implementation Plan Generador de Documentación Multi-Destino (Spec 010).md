# Implementation Plan: Generador de Documentación Multi-Destino (Spec 010)

Esta implementación tiene como objetivo cumplir el requerimiento de desarrollar un generador documental de tipo Single-Shot, pivoteando de la aproximación fallida (basada en agentes) de la Spec 009, y basándose en el motor ligero creado durante la Spec 005. 

## User Review Required

> [!IMPORTANT]
> **Refactor Script**: Se renombrará el actual `scripts/docs-generator.mjs` a `scripts/docs-generator.mjs` y se expandirá agresivamente su comportamiento. La CLI cambiará para requerir el argumento `--target`.
> **Soporte de Blogs y Agentes**: Se inyectarán dependencias de los JSON taxonómicos y una lista de exclusión de seguridad robusta. 

## Open Questions

Ninguna por el momento. La especificación solicitada se encuentra completamente contenida en los requerimientos.

## Proposed Changes

### 1. Refactorización y Configuración de Rutas (scripts/docs-generator.mjs)
> Se creará a partir del código base de `docs-generator.mjs` y se eliminará este último.

#### [NEW] [docs-generator.mjs](file:///C:/xampp/htdocs/datanestiq/scripts/docs-generator.mjs)
- **CLI Argument Parsing**: Implementar soporte para `--target=wiki|agents|skills|blog`, `--since`, `--seed`, `--brief`, `--slug` y `--dry-run`.
- **Filtro de Seguridad**: Añadir una constante `SECURITY_EXCLUDES = ['.env', 'secure_leads', 'wp-config.php', '.jsonl', 'wp-admin', 'wp-includes']`. Todo archivo evaluado será filtrado contra esta lista antes de cargarlo a memoria.
- **Backoff Exponencial y Retry**: Expandir el módulo de failover del round-robin `balanceAndCall` actual. Añadir mecanismo para detectar cuando *todos* los proxies dieron 429, y si es el caso, aplicar un sleep exponencial de corto aliento antes de volver a intentar (hasta un máximo de reintentos).
- **Reporte de Uso**: Añadir un registro global `providerStats = { groq: 0, dashscope: 0, gemini: 0 }`. Sumar la estadística al resolver exitosamente y mostrarla con `console.table()` al finalizar.
- **Estrategias por Target (Generators)**:
  - `wiki`: Reutilizar el flow original basado en archivos modificados/diff o `SEED`.
  - `agents`: Buscar toda la estructura de `/specs` y el file tree para armar un `AGENTS.md` a inyectar en la raíz (sin frontmatter).
  - `skills`: Iterar la carpeta de habilidades o comandos para resumirlos en una lista `skills-overview.md`.
  - `blog`: Descartar el repo git. Leer `src/data/*.json`, consumir el argumento `--brief` para el LLM y emitir en `src/content/blog/` preservando el flag `draft: true` en su frontmatter. Uso intensivo de `stripFrontmatter` sobre el contenido emitido.

#### [DELETE] [docs-generator.mjs](file:///C:/xampp/htdocs/datanestiq/scripts/docs-generator.mjs)

### 2. Especificaciones y Documentación
#### [NEW] [spec.md](file:///C:/xampp/htdocs/datanestiq/specs/010-generador-multidestino/spec.md)
- Redactar y asentar formalmente la Spec 010. *(Completado)*

#### [MODIFY] [ESTADO-SPECS.md](file:///C:/xampp/htdocs/datanestiq/planes/ESTADO-SPECS.md)
- Añadir la entrada correspondiente a la Spec 010.
- Marcar la Spec 009 explícitamente como archivada/"bloqueada" por cuotas.
- Actualizar la 005 referenciando su integración a la 010.

#### [MODIFY] [.env.example](file:///C:/xampp/htdocs/datanestiq/.env.example)
- Actualizar `.env.example` para listar los modelos `GROQ_MODEL`, `DASHSCOPE_MODEL`, `GEMINI_MODEL`, y los pesos requeridos para un balanceo.

## Verification Plan

### Automated Tests
1. Ejecutar `node scripts/docs-generator.mjs --target=wiki --dry-run`
2. Ejecutar `node scripts/docs-generator.mjs --target=blog --brief="El poder de los datos" --slug=el-poder-datos --dry-run`
3. Comprobar que todos devuelven un status exitoso simulado y un recorte de texto.

### Manual Verification
1. El usuario puede correr un `npm run build` luego de generar el blog para comprobar que el frontmatter (y esquema de Astro) son conformes.
2. Comprobar el log final con la tabla de `providerStats`.
