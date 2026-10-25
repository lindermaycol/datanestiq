# Deuda Técnica: Spec 007 (Expansión Multi-Página)

## Estado
- **Fase actual:** Implementado (6 páginas SSG generadas, verificado)
- **Impacto:** N/A
- **Severidad:** N/A

## Lista de Deuda Técnica (Technical Debt)

### 1. Enrutamiento hacia Taxonomías Inexistentes (Corpus Incompleto)
- **Descripción:** Las tarjetas 4, 5 y 6 de la página de inicio enrutaban hacia slugs inexistentes o en formato hash (`#`) que no correspondían con las rutas dinámicas `/soluciones/[slug].astro`.
- **Riesgo:** Páginas 404 que rompen la experiencia de usuario y penalizan el SEO.
- **Solución Implementada:** Se expandió el archivo `taxonomyCorpus.json` para incluir los 4 pilares tecnológicos faltantes (Hiperautomatización, Business Intelligence, Sistemas Digitales, Estrategia), garantizando que Astro genere el total de páginas estáticas requeridas. Se ajustaron los enlaces en `Services.astro` para coincidir con la taxonomía oficial.
- **Estado:** ✅ [RESUELTO]

### 2. Metadatos SEO Inválidos (JSON-LD)
- **Descripción:** El bloque `application/ld+json` en `SEO.astro` invocaba a `serviceData.description`, provocando un error fatal al renderizar rutas cuando `serviceData` no existía o cuando las claves internas variaban.
- **Riesgo:** Error 500 en SSR (Server-Side Rendering) de Astro que tumbaba la construcción (build) de las rutas, así como esquemas Schema.org rotos.
- **Solución Implementada:** Se refactorizó la destructuración y fallback en `SEO.astro`, extrayendo la `description` general directamente para inyectarla en el `Service` schema sin depender de sub-objetos anidados.
- **Estado:** ✅ [RESUELTO]
