# Feature Specification: OpenWiki y Documentación Viva (Spec 005)

> [!NOTE]
> **ESTADO: ✅ CONSOLIDADA / SUPERSEDED por la Spec 010.** Esta spec creó nuestro motor propio de "documentación viva" (originalmente `openwiki-sync.mjs`). Ese motor **evolucionó y fue absorbido por la Spec 010 (Generador Multi-Destino, `docs-generator.mjs`)**, donde vive como el `--target=wiki`. No está *archivada como fallida* (a diferencia de la 009): cumplió su objetivo y fue generalizada. Se conserva por trazabilidad histórica.

**Feature Branch**: `[005-openwiki-agentes]`

**Created**: 2026-07-05
**Status**: ✅ Consolidada en Spec 010 (motor `docs-generator.mjs`, target `wiki`)
**Input**: Reemplazo del "espejismo" CLI de OpenWiki por un motor propio en Node con balanceo Groq ⇄ DashScope y compatibilidad estricta con Astro Collections.

## 1. Visión General
Esta especificación define la infraestructura técnica y los flujos de CI/CD para integrar el motor de "Documentación Viva" en Datanestiq. Su propósito es auditar continuamente el código frente a nuestras especificaciones (`/specs`) y generar/actualizar dinámicamente la colección `openwiki` en Astro (`src/content/openwiki`).
En lugar de depender de herramientas CLI externas inexistentes o SDKs costosos, el motor utiliza un script propio en Node (`scripts/docs-generator.mjs`) que implementa un balanceo de carga (Round-robin ponderado) y *failover atómico* entre dos proveedores OpenAI-compatibles: **Groq** y **Alibaba DashScope (Qwen)**, siguiendo exactamente el mismo patrón de gobernanza de costos ya validado en el Chatbot (`chat.php`).

---

## 2. Instrucciones de Sistema para `AGENTS.md` (Mejores Prácticas)
Para garantizar que la IA comprenda correctamente la jerarquía de nuestras especificaciones (001 a 006) y el contexto de Datanestiq, el motor inyectará las siguientes reglas exactas (System Instructions) al evaluar cambios:

```markdown
# Configuración Global de Agentes (Datanestiq)

## Jerarquía y Fuente de la Verdad (/specs)
Al auditar o generar documentación, DEBES consultar siempre los archivos en la carpeta `/specs` respetando estrictamente esta jerarquía:
- **Spec 001 (Elevación Premium):** Rige el tono, estilo y estándar estético de la marca y la interfaz de usuario. Ningún cambio debe contradecir el estándar premium.
- **Spec 002 (Microexperiencias IA):** Define las interacciones y microinteracciones de IA en la web.
- **Spec 003 (Taxonomía de Servicios):** ES LA ÚNICA FUENTE DE VERDAD para la oferta comercial (6 pilares y 10 sectores). Nunca inventes servicios que no estén aquí.
- **Spec 004 (Metodología y Agentes):** Define el flujo de trabajo de la Fábrica de Agentes, los 10 agentes de IA y los frameworks de copywriting (PAS, AIDA, StoryBrand).
- **Spec 005 (OpenWiki):** (Este documento) Regula el comportamiento del propio agente documentador y los flujos de CI/CD.
- **Spec 006 (Arquitectura WP):** (Próximamente) Reglas técnicas para el despliegue y desarrollo en WordPress.

## Reglas de Modificación
1. NUNCA alteres las decisiones estratégicas de negocio o los "core values" definidos en las specs. Tu rol es reflejar en la documentación los cambios descubiertos en el código, no redefinir el negocio.
2. Si detectas un nuevo servicio en el código que no está en la Spec 003, proponlo en una PR con un flag de advertencia `[HUMAN REVIEW REQUIRED]`.
3. Mantén un tono técnico, conciso y profesional, acorde al nivel de una consultora de élite.
```

---

## 3. Arquitectura del Motor (Groq ⇄ DashScope)

El CLI de `openwiki` se ha descartado en favor de un motor nativo (`scripts/docs-generator.mjs`) diseñado con las siguientes características:

### A. Compatibilidad Astro y Esquema Zod
Todos los archivos Markdown generados en `src/content/openwiki/` **deben** cumplir estrictamente con el esquema Zod de la colección. El script forzará este esquema en el frontmatter:
```yaml
title: "Título (máx 100 chars)"
description: "Descripción (máx 160 chars)"
author: "AI Documenter"
lastUpdated: 2026-07-08
tags: ["doc", "autogenerado"]
seoScore: 100
```

### B. Balanceador de Carga y Failover Atómico
Para optimizar costos, el motor reparte la generación de documentos entre dos APIs compatibles con OpenAI usando `fetch` nativo (sin dependencias SDK):
- **Groq**: Configurado vía `GROQ_API_KEY` y `GROQ_MODEL` (ej. `llama-3.3-70b-versatile`).
- **DashScope**: Configurado vía `DASHSCOPE_API_KEY`, `DASHSCOPE_URL` y `DASHSCOPE_MODEL` (ej. `qwen-plus`).

**Estrategia:**
1. **Round-robin Ponderado**: El script reparte la carga de los documentos entre ambos proveedores de forma equitativa (configurable).
2. **Failover Atómico**: Si un proveedor falla (HTTP != 2xx), se reintenta exactamente una vez con el otro proveedor.
3. Si ambos fallan, el script aborta sin corromper la documentación.

---

## 4. Flujo CI/CD: Automatización con GitHub Actions
El siguiente archivo YAML automatiza la ejecución diaria del motor, inyectando las claves necesarias y abriendo PRs automatizados.

**Archivo:** `.github/workflows/openwiki-audit.yml`

```yaml
name: OpenWiki Daily Documentation Audit

on:
  schedule:
    - cron: '0 2 * * *'
  workflow_dispatch:

jobs:
  audit-documentation:
    runs-on: ubuntu-latest
    permissions:
      contents: write
      pull-requests: write

    steps:
      - name: Checkout Repository
        uses: actions/checkout@v4
        with:
          fetch-depth: 0

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: '20'

      - name: Install Dependencies
        run: npm ci

      - name: Run OpenWiki Sync Motor
        env:
          GROQ_API_KEY: ${{ secrets.GROQ_API_KEY }}
          DASHSCOPE_API_KEY: ${{ secrets.DASHSCOPE_API_KEY }}
          GROQ_MODEL: llama-3.3-70b-versatile
          DASHSCOPE_MODEL: qwen-plus
        run: |
          node scripts/docs-generator.mjs

      - name: Add Human Review Required Flag for Critical Specs
        id: check
        run: |
          echo "Verificando modificaciones de confianza baja o specs críticas..."
          if git diff --name-only | grep -E "001-elevacion-premium|003-taxonomia-servicios"; then
            echo "human_review=[HUMAN REVIEW REQUIRED]" >> $GITHUB_OUTPUT
          fi

      - name: Create Pull Request
        uses: peter-evans/create-pull-request@v6
        with:
          token: ${{ secrets.GITHUB_TOKEN }}
          commit-message: "docs(openwiki): sincronización automatizada de documentación viva"
          branch: "bot/openwiki-sync"
          delete-branch: true
          title: "🤖 Actualización Automática de Documentación Viva"
          body: |
            OpenWiki Motor ha detectado divergencias o generado nueva documentación.
            
            ${{ steps.check.outputs.human_review }}
            
            **Por favor, revisa los cambios sugeridos para mantener la documentación actualizada.**
          labels: "documentation, automated pr"
          draft: false
```

---

## 5. Gestión de Conflictos y Umbrales de Seguridad

### A. Umbrales de Confianza (Confidence Thresholds)
- El motor solicitará a los LLMs un *score* de confianza (0.0 a 1.0) junto a la respuesta. Si la confianza es `< 0.85`, el script agregará el flag `[HUMAN REVIEW REQUIRED]` en lugar de procesarlo a ciegas.

### B. Bloqueo de Secciones Sensibles (Immutable Blocks)
El script respeta absolutamente los bloques inmutables:
```markdown
<!-- OPENWIKI:IGNORE:START -->
## Visión del Negocio (Inmutable)
Este texto no será tocado.
<!-- OPENWIKI:IGNORE:END -->
```
Al parsear, extraerá los bloques temporalmente y los reinsertará intactos en la salida final.

---

## 6. Grafo de Dependencias Maestro
OpenWiki opera como el "Guardián de la Verdad" del proyecto. Para que funcione correctamente, debe gobernar y auditar la relación e interacción entre las especificaciones a través del siguiente Grafo de Dependencias:

- **Spec 005 (Guardián/OpenWiki Motor):** Lee continuamente el archivo `AGENTS.md` (o la carpeta `specs/`) y supervisa el cumplimiento del resto de las Specs.
- **Spec 001 (UI/UX - Elevación Premium):** Verifica que los componentes Frontend mantengan la estética premium.
- **Spec 002 (Interacciones IA):** Verifica que la API y los endpoints implementen correctamente los flujos de IA descritos.
- **Spec 003 (Taxonomía de Servicios):** Garantiza que cualquier nuevo pilar agregado en el código quede documentado.
- **Spec 004 (Metodología):** Asegura que los *System Prompts* se alineen con los frameworks definidos.

---

## 7. User Stories & Acceptance Scenarios

### User Story 1: Actualización de la Taxonomía desde el Código
**Como** desarrollador backend,
**Quiero** añadir un nuevo "Pilar de Servicio" en el archivo de configuración JS o Base de Datos del proyecto,
**Para que** el sistema lo exponga en el Frontend sin tener que actualizar manualmente el documento Markdown (Spec 003).

- **Given** que un desarrollador hace merge a `main` añadiendo el pilar "Automatización Cuántica",
- **When** se ejecute la acción diaria del motor OpenWiki,
- **Then** el agente debe detectar la discrepancia y generar automáticamente una PR actualizando la Spec 003 para incluir este nuevo pilar.

### User Story 2: Revisión de PRs de Documentación
**Como** Technical Lead,
**Quiero** revisar las Pull Requests generadas automáticamente,
**Para** asegurar que los cambios propuestos no contienen alucinaciones.

- **Given** que OpenWiki ha generado una PR proponiendo cambios a la Spec 001,
- **When** el Technical Lead revise el *diff*,
- **Then** la PR requerirá aprobación obligatoria antes de ser *mergeada*.

### User Story 3: Prevención de Sobrescritura de Core Values
**Como** Product Manager,
**Quiero** que las secciones estratégicas permanezcan intactas,
**Para que** la IA nunca las reescriba por error.

- **Given** que la Spec 004 tiene una sección envuelta en etiquetas `<!-- OPENWIKI:IGNORE:START -->`,
- **When** OpenWiki audite ese documento,
- **Then** ignorará por completo esa sección.

---

## 8. Functional Requirements (FRs)
- **FR1 - Threshold de Confianza:** El script debe solicitar un umbral mínimo (`0.85`) para generar la documentación de forma limpia.
- **FR2 - Failover Atómico:** En caso de caída de un LLM (HTTP != 2xx), el motor conmuta automáticamente al otro.
- **FR3 - Dependencias Nativas:** Uso de `fetch` nativo de Node >= 18; cero uso de SDKs costosos de Anthropic u OpenAI.
- **FR4 - Dry-Run Mode:** Soporte para flag `--dry-run` para simulación de flujos y testeos locales.
