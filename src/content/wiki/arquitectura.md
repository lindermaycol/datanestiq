---
title: "Arquitectura Web de Datanestiq (Astro)"
description: "Documentación base de la arquitectura del ecosistema web de Datanestiq, centrada en Astro y su arquitectura de islas."
author: "AI Documenter"
lastUpdated: 2026-07-08
tags: ["arquitectura","astro","ssg","islas","web"]
seoScore: 100
---

# Arquitectura Web de Datanestiq

La arquitectura web de Datanestiq se fundamenta en **Astro** como framework principal, aprovechando su arquitectura de islas (Island Architecture). Esto permite la creación de sitios estáticos (SSG) de alto rendimiento, combinados con interactividad dinámica en el cliente para funcionalidades como el Chatbot y microexperiencias.

## Principios
<!-- OPENWIKI:IGNORE:START -->
1. **Rendimiento primero:** Zero JS por defecto en la carga inicial de las landings comerciales.
2. **Interactividad aislada:** Uso de directivas (ej. `client:load`) únicamente donde es estrictamente necesario.
<!-- OPENWIKI:IGNORE:END -->

## Componentes Clave
- **Colecciones (Content Collections):** Utilizamos colecciones fuertemente tipadas (Zod) para la gestión de contenido, incluyendo el blog (`/src/content/blog`) y la documentación técnica de OpenWiki (`/src/content/wiki`).
- **Integraciones:** Se emplean integraciones clave como React (para la implementación de islas), Tailwind CSS (actualmente bajo revisión para optimización), y soporte nativo para Markdown/MDX.
- **APIs Serverless:** Para flujos de trabajo seguros y dinámicos, como el Chatbot, se utilizan endpoints PHP o funciones Edge.
