---
title: "Spec 020: Inteligencia de Demanda y Journey Reconstructor"
description: "Implementación de la Spec 020 para mejorar la inteligencia de demanda y la reconstrucción del journey del cliente"
author: "AI Documenter"
lastUpdated: 2026-08-06
tags: ["inteligencia de demanda","journey reconstructor","Spec 020"]
seoScore: 100
---
# Implementación de la Spec 020
La Spec 020 se centra en mejorar la inteligencia de demanda y la reconstrucción del journey del cliente. A continuación, se presentan los cambios técnicos necesarios para implementar esta especificación.

## Cambios Propuestos
### 1.1 Modificaciones de Base de Datos y APIs Backend
Se deben realizar las siguientes modificaciones:
* Añadir la instrucción `CREATE TABLE IF NOT EXISTS demand_signals` y sus índices de rendimiento asociados en el script `init_crm_db.php`.
* Soportar el nuevo tipo de evento `demand_signal` en el archivo `track_event.php`.
* Crear endpoints seguros en el archivo `admin/api.php` para la administración de la demanda y el journey del cliente.

### 1.2 Modificaciones en Frontend e Islas Interactivas
Se deben realizar las siguientes modificaciones:
* Cargar e instanciar el catálogo de la taxonomía en el Web Worker de Xenova en el archivo `Chatbot.jsx`.
* Enviar el evento `demand_signal` a `track_event.php` vía `trackEvent()` en el archivo `Chatbot.jsx`.
* Agregar la pestaña "Demanda & Journey" en la barra de navegación lateral en el archivo `index.php`.

### 1.3 Registro de Especificaciones y OpenWiki
Se deben realizar las siguientes modificaciones:
* Añadir la fila correspondiente a la Spec 020 en el archivo `ESTADO-SPECS.md`.
* Registrar la Spec 020 en el archivo `specsStatus.json`.
* Añadir la Spec 020 dentro del roadmap de la fase de analítica/inteligencia de conversión en el archivo `Fases.md`.

## 2. Plan de Verificación
### 2.1 Pruebas de Clasificación
Se deben realizar pruebas de clasificación para garantizar que el clasificador del catálogo funcione correctamente.

### 2.2 Pruebas de Guardarraíl
Se deben realizar pruebas de guardarraíl para garantizar que la API de administración retorne "Datos insuficientes" cuando no haya suficientes registros en la tabla `demand_signals`.

### 2.3 Pruebas de Reconstrucción de Journey
Se deben realizar pruebas de reconstrucción de journey para garantizar que el timeline presente los eventos ordenados y sin duplicados.