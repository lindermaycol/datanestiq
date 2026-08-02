---
title: "Documentación Técnica de Astro Wiki"
description: "Generación de documentación técnica para la colección Astro 'wiki' utilizando OpenWiki"
author: "AI Documenter"
lastUpdated: 2026-08-02
tags: ["astro wiki","openwiki","documentación técnica"]
seoScore: 100
---
## Introducción
OpenWiki es el motor de 'Documentación Viva' de Datanestiq, diseñado para generar o actualizar documentación técnica en formato Markdown para la colección Astro 'wiki'.

## Funcionamiento
OpenWiki utiliza un conjunto de proveedores de inteligencia artificial para generar contenido de alta calidad. Los proveedores están configurados en el archivo `.env` y se pueden personalizar según las necesidades del proyecto.

## Características
* Generación de documentación técnica en formato Markdown
* Utiliza proveedores de inteligencia artificial para generar contenido de alta calidad
* Personalizable mediante el archivo `.env`
* Compatible con la colección Astro 'wiki'

## Uso
Para utilizar OpenWiki, simplemente ejecute el comando `node scripts/docs-generator.mjs --target=wiki` en la terminal. Puede personalizar el comportamiento del script mediante los argumentos de línea de comandos.

## Argumentos de línea de comandos
* `--target`: especifica el tipo de documentación a generar (wiki, agents, skills, blog, page)
* `--dry-run`: simula la generación de documentación sin guardar los cambios
* `--seed`: genera documentación para todos los archivos en el repositorio
* `--since`: especifica el rango de commits para generar documentación
* `--brief`: especifica el nivel de detalle para la documentación
* `--slug`: especifica el slug para la documentación generada

## Ejemplo de uso
`node scripts/docs-generator.mjs --target=wiki --dry-run`

%%IGNORE_BLOCK_0%%