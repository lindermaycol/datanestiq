---
title: "Modelo de Datos — Spec 016: Analítica de Conversión y Loop"
description: "Este documento especifica las extensiones al esquema SQLite de secure_leads/crm.sqlite, las consultas agregadas para el embudo de conversión y las uniones re"
author: "AI Documenter"
lastUpdated: 2026-08-02
tags: ["analítica de conversión","esquema SQLite","embudo de conversión","sesiones de chat","leads","citas","historial de estados"]
seoScore: 100
---
# Modelo de Datos — Spec 016: Analítica de Conversión y Loop
## Introducción
Este documento describe el modelo de datos para la analítica de conversión y loop, incluyendo las extensiones al esquema SQLite y las consultas agregadas necesarias para el análisis del embudo de conversión.
## Esquema SQLite Extendido
El esquema SQLite se extiende para incluir las siguientes tablas y campos:
*   leads: id, nombre, correo electrónico, teléfono, fecha de creación
*   sesiones de chat: id, id de lead, fecha de inicio, fecha de fin
*   citas: id, id de lead, fecha de cita, resultado
*   historial de estados: id, id de lead, estado, fecha de cambio
## Consultas Agregadas
Se definen las siguientes consultas agregadas para el análisis del embudo de conversión:
*   Conversión de leads a citas
*   Conversión de citas a clientes
*   Tasa de abandono en cada etapa del embudo
## Uniones Relacionadas
Se establecen uniones entre las tablas para facilitar el análisis y la generación de informes:
*   Leads y sesiones de chat
*   Leads y citas
*   Leads y historial de estados
## Conclusión
El modelo de datos presentado en este documento proporciona una base sólida para la analítica de conversión y loop, permitiendo a los usuarios analizar y optimizar el embudo de conversión para mejorar la eficiencia y el rendimiento.