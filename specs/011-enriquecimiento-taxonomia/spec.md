# Spec 011: Enriquecimiento Profundo de la Taxonomía

## 1. Visión General
Esta especificación define el estándar para enriquecer la taxonomía central de Datanestiq (actualmente validada en Astro Content Collections). La taxonomía es la **fuente única de verdad** y su profundidad y calidad estructurada determinarán la potencia del generador de contenido de IA (Spec 010) y la personalización de las interacciones.

## 2. Requisitos de Negocio
El equipo de Marketing y la Fábrica de Agentes necesitan una base ontológica mucho más densa para crear campañas hiper-personalizadas y material para el Blog. 
- Necesitamos sub-sectores, KPIs de ROI cuantificables por industria y regulaciones.
- Se debe caracterizar el comité de compras B2B (comprador técnico, económico, usuario) con sus objeciones y criterios de decisión.
- Expandir la información de los Pilares hacia stack tecnológico, casos y rutas de madurez.
- Establecer un benchmark competitivo vs consultoras corporativas.
- Proveer un eje de **"Content Angles" (Ángulos de Contenido)** por nodo, que alimente directamente a `docs-generator.mjs --target=blog` con briefs.

## 3. Limitaciones Técnicas Estrictas
- **Byte-Compatible (Aditivo):** Las modificaciones a los esquemas Zod (`src/lib/schemas.js`) deben realizarse mediante campos `.optional()`. Ningún esquema anterior o campo puede ser modificado o renombrado para no romper los consumidores actuales (islas React y endpoints).
- **Integridad Referencial:** Todas las relaciones cruzadas en la ontología deben validar y fallar limpiamente (`exit 1` en pre-build `build-taxonomy.mjs`) si un ID no existe.
- **Rendimiento:** Aunque se expande la información, se debe considerar el peso final del JSON (`taxonomyCorpus.json`). Los esquemas pesados deben modularizarse si alcanzan el megabyte (en este momento es seguro añadir campos de texto).
- **No-regresión:** `npm run build` debe ser 100% verde con las nuevas colecciones YAML hidratadas.

## 4. Formato de "Content Angles" (Sinergia Spec 010)
Se añadirá una estructura `contentAngles` que contendrá briefs preparados para la IA, por ejemplo:
```yaml
contentAngles:
  - id: "roi-gobierno-ia"
    title: "El ROI de la IA en Gobierno"
    brief: "Artículo de 1000 palabras dirigido a líderes del sector público (CIOs) detallando cómo la automatización de expedientes con modelos LLaMA locales reduce tiempos en un 60%, sin comprometer la soberanía del dato. Mencionar OECE."
```
Este nodo será consumido en un pipeline que dispare iterativamente `--target=blog --brief="{brief}"`.
