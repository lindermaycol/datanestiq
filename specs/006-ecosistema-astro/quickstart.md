# Quickstart Validation: Ecosistema Astro

Valida la instalación base y la colección de contenidos del proyecto.

## 1. Inicialización
```bash
# Asumiendo que has corrido el comando de inicialización de Astro
npm install
npm run dev
```

## 2. Validación de Carga sin JS Bloqueante
1. Abre el navegador en `http://localhost:4321`.
2. Abre las DevTools (F12) -> Pestaña **Network** -> Filtra por **JS**.
3. Asegúrate de que el documento HTML principal carga sin descargar un bundle masivo de JS de renderizado (0 FOUC).
4. Haz scroll hasta el "Diagnostic Wizard"; la red debe disparar la hidratación de ese componente específico solo cuando es visible.

## 3. Validación de Content Collections
1. Crea un archivo en `src/content/openwiki/test.md`:
```markdown
---
title: "Documentación de Prueba"
description: "Valida que el frontmatter de Zod funciona"
lastUpdated: 2026-07-06
tags: ["test"]
seoScore: 100
---
# Título de Prueba
Contenido estático generado.
```
2. Navega a `http://localhost:4321/wiki/test`.
3. El servidor debería renderizar la vista de artículo generada de forma estática.
