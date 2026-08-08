# Actualización de Especificaciones y Taxonomía de Servicios

Este plan detalla la actualización de la especificación técnica actual (002) para reflejar los logros reales obtenidos con la integración del proxy de IA (`chat.php` + Groq), así como la creación de un nuevo marco taxonómico (004) para incorporar los nuevos servicios solicitados respetando la reserva de la especificación 003 para el portado a WordPress.

## User Review Required

> [!IMPORTANT]
> El rediseño de los servicios implicará agregar nuevas tarjetas a la sección de "Capacidades tecnológicas" en la Landing Page. Pasaremos de 4 servicios (IA, RPA, Data, BI) a un número mayor (ej. 5 o 6). Necesitamos tu aprobación sobre cómo re-distribuir la cuadrícula visual (grid) para que no rompa la estética.

## Open Questions

> [!WARNING]
> ¿Deseas que los nuevos servicios de "Diseño Web Inteligente" y "Desarrollo de Sistemas con IA" se consoliden en una sola tarjeta nueva (ej. "Desarrollo Digital con IA") o prefieres mantenerlos como dos pilares separados?
> ¿Tienes alguna industria o sector adicional que desees añadir a la taxonomía base además de los 10 que ya tenemos ocultos?

## Proposed Changes

### Especificaciones Técnicas (Documentación)

#### [MODIFY] [002-microexperiencias-ia/spec.md](file:///c:/xampp/htdocs/datanestiq/specs/002-microexperiencias-ia/spec.md)
- Se actualizará el documento para cambiar las "Asunciones" de usar respuestas simuladas/mockeadas por la implementación real y viva que logramos a través de `api/chat.php` usando Llama 3.1.
- Se incluirá el mapeo y análisis de "Bridges" (puentes), detallando cómo `app.js` se comunica con el Worker local (`Transformers.js`) para la búsqueda semántica y con el backend (`chat.php`) para la generación de texto, enrutando los logs limpiamente.

#### [NEW] [004-taxonomia-servicios/spec.md](file:///c:/xampp/htdocs/datanestiq/specs/004-taxonomia-servicios/spec.md)
- Creación de un nuevo documento rector que establezca la taxonomía completa.
- Estructura jerárquica: Categorías Generales (ej. Ingeniería de Datos) y Servicios Específicos (ej. Pipelines ETL, Data Lakes).
- Inclusión del nuevo pilar de **Inteligencia Digital**: Diseño Web Inteligente, Aplicaciones Web Inteligentes y Sistemas Modulares con IA.

### Interfaz y Lógica (Frontend)

#### [MODIFY] [app.js](file:///c:/xampp/htdocs/datanestiq/prototype/app.js)
- Ampliar la variable `servicesCorpus` (alrededor de la línea 332) para incluir los nuevos objetos: "Diseño Web Inteligente" y "Sistemas Inteligentes".
- Actualizar las palabras clave (keywords) para que el buscador semántico pueda detectarlos cuando el usuario busque "crear una página web", "app con IA", etc.

#### [MODIFY] [index.html](file:///c:/xampp/htdocs/datanestiq/prototype/index.html)
- Agregar las nuevas tarjetas físicas (cards) correspondientes a los nuevos servicios en el contenedor `<div id="solutions-grid">`.
- Ajustar las clases de TailwindCSS de la cuadrícula (grid) para acomodar el nuevo número de tarjetas de manera balanceada (por ejemplo, pasar de 2 columnas a 3 columnas en pantallas grandes).

## Verification Plan

### Manual Verification
- Cargar la página y verificar que las nuevas tarjetas de servicios se renderizan correctamente sin romper la simetría del diseño premium.
- Probar el buscador semántico introduciendo frases como "quiero una aplicación web con IA" para confirmar que la tarjeta correspondiente es resaltada exitosamente por el worker local.
- Leer el archivo `004-taxonomia-servicios/spec.md` para confirmar que cubre todas tus líneas de negocio B2B.
