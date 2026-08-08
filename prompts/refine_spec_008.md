# Objetivo
Actuar como un Ingeniero Cloud/DevOps de Élite para refinar y detallar la "Spec 008: Headless WordPress (Backend CMS)".

# Instrucciones
1. Analizar el ecosistema: El frontend se ejecutará en Astro (Spec 006) pero necesita un backend robusto para la gestión de leads y publicación visual de contenido. El core de WordPress ya está instalado en local (`C:\xampp\htdocs\datanestiq`).
2. Leer el archivo `specs/008-headless-wordpress/spec.md` actual (si está vacío, crearlo usando SDD).
3. Asegurar que la Spec defina claramente:
   - Configuración de la API REST o WPGraphQL para conectar WordPress con Astro.
   - Seguridad: Bloqueo de vistas públicas en WP (`/wp-admin` restringido, frontend de WP redirigido a Astro).
   - Migración de los Leads del Wizard hacia un Custom Post Type en WordPress (eliminando el uso temporal de LocalStorage).
4. El output debe ser el archivo `spec.md` actualizado y listo para implementación.
