# Walkthrough: Implementación de Spec 005 (OpenWiki)

He completado el desarrollo e implementación de la infraestructura para OpenWiki siguiendo la metodología SDD.

## 1. El Cerebro Documental (`AGENTS.md`)
He creado el documento maestro en `.agents/AGENTS.md`. Este archivo es crucial porque le enseña a OpenWiki (y a cualquier otro agente IA futuro) la jerarquía de nuestras Specs (de la 001 a la 006) y establece la regla de oro: **Spec 003 es la única fuente de verdad para la oferta B2B**. Además, especifica la orden estricta de respetar los bloques `<!-- OPENWIKI:IGNORE:START -->`.

## 2. Protección de Core Values
Modifiqué los archivos de la **Spec 003** (Taxonomía) y **Spec 004** (Metodología), inyectando los bloques de comentarios HTML `OPENWIKI:IGNORE` en sus secciones estratégicas. Esto blinda las decisiones de negocio; la IA no podrá modificarlas accidentalmente.

## 3. GitHub Actions (CI/CD Automático)
Desplegué el Workflow de GitHub Actions en `.github/workflows/openwiki-audit.yml`.
*   **Cronjob:** Programado para ejecutarse diariamente a las 2 AM UTC.
*   **Threshold:** Configurado con `--threshold 0.85` utilizando Claude 3.5 Sonnet para garantizar cambios precisos.
*   **Aprobaciones:** Toda sugerencia de cambio llegará como una PR con etiqueta de validación y un flag explícito de `[HUMAN REVIEW REQUIRED]` si afecta a las specs estratégicas (001 o 003).

## 4. Script de Dry-Run Local
Implementé `scripts/openwiki-local-sync.sh` para que tú o tu equipo puedan simular las auditorías de OpenWiki localmente sin gastar tokens de la API, permitiendo validar cambios en la estructura de datos (`taxonomyCorpus.json`) antes de subir código.

*Con esta integración, la arquitectura documental de Datanestiq ahora se defiende y actualiza a sí misma de manera autónoma.*
