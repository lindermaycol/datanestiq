---
title: "Evaluación de Demanda con Modelos de Lenguaje"
description: "Este script evalúa la capacidad de un modelo de lenguaje para clasificar demandas de servicios"
author: "AI Documenter"
lastUpdated: 2026-08-06
tags: ["NLP","Modelos de Lenguaje","Clasificación de Demanda"]
seoScore: 90
---

# Evaluación de Demanda con Modelos de Lenguaje
Este script utiliza el modelo de lenguaje 'Xenova/paraphrase-multilingual-MiniLM-L12-v2' para evaluar la capacidad de clasificar demandas de servicios.

## Requisitos
* Node.js
* @xenova/transformers
* fs

## Funcionamiento
1. Carga el corpus de taxonomía desde un archivo JSON.
2. Crea un conjunto de entradas de taxonomía con texto y ID.
3. Carga el modelo de lenguaje y crea un extractor de características.
4. Calcula las embeddings de las entradas de taxonomía y las consultas de prueba.
5. Compara las embeddings de las consultas con las de las entradas de taxonomía y determina la mejor coincidencia.
6. Evalúa si la coincidencia es correcta según los casos de prueba.

## Casos de Prueba
* Consultas de servicios ofrecidos: 'necesito dashboards para mi junta directiva', 'quiero entrenar modelos de machine learning y llm', 'crear un data lake y pipelines con python y aws'
* Consultas de servicios no ofrecidos: '¿venden repuestos de autos?', 'quiero pedir una pizza de pepperoni', 'necesito comprar repuestos de laptops'

## Resultados
El script imprime los resultados de la evaluación, incluyendo la coincidencia de cada consulta y el puntaje de similitud. El resultado final es el número de casos de prueba pasados sobre el total.
