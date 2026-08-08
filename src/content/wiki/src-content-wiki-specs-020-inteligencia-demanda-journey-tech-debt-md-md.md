---
title: "Tech Debt — Spec 020: Inteligencia de Demanda y Journey Reconstructor"
description: "Este documento detalla la deuda técnica anticipada y las simplificaciones asumidas para la v1 de la Spec 020, incluyendo la gestión de corpus, crecimiento de"
author: "AI Documenter"
lastUpdated: 2026-08-06
tags: ["tech-debt","spec-020","inteligencia-demanda","chatbot","xenova","sqlite","clustering"]
seoScore: 100
---
# Tech Debt — Spec 020: Inteligencia de Demanda y Journey Reconstructor
## TD-020-01 — Sobrecarga en la inicialización de múltiples corpus (Xenova)
## TD-020-02 — Crecimiento lineal de la tabla `demand_signals`
## TD-020-03 — Agrupación semántica simple en base a matched_service
## TD-020-04 — Umbral de coincidencia de catálogo bajo (0.40)
### Resumen de Deuda Técnica