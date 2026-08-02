# Feature Specification: Metodología de Desarrollo Digital Premium

> [!IMPORTANT]
> **STATUS: SUPERSEDED_BY: 010**  
> El pipeline basado en LangGraph nunca se construyó (decisión de arquitectura del usuario por simplicidad y costos). El objetivo original —agentes que redactan y mantienen la documentación y el sitio— se sirve formalmente con el patrón single-shot controlado y multi-destino de la Spec 010 (`docs-generator.mjs`).

**Feature Branch**: `[004-metodologia-desarrollo-digital]`

**Created**: 2026-07-05

**Status**: SUPERSEDED por Spec 010

**Input**: User provided 10 AI agent prompt concepts for building premium websites.

## Deuda Técnica y Backlog
- **[ABANDONADO] LangGraph**: La orquestación en Python/LangGraph ha sido oficialmente abandonada. La generación en vivo utiliza la máquina de estados React+PHP, y la generación offline usa scripts directos Node/Single-shot (Spec 010). No se construirá el pipeline en LangGraph.

<!-- OPENWIKI:IGNORE:START -->
## 1. Visión General y Orquestación Global

La Spec 004 nació originalmente como la Metodología Operativa para la redacción (copywriting) usando 10 agentes especializados y LangGraph. Sin embargo, este enfoque multi-agente en Python fue **abandonado** a favor de una arquitectura más ligera, determinista y "single-shot" definida en la **Spec 010**.
<!-- OPENWIKI:IGNORE:END -->

---

## 2. El Flujo de Orquestación (Pipeline de 5 Pasos)

La creación de Landing Pages y Páginas de Venta se orquesta siguiendo esta secuencia estricta de ejecución de prompts:

### **Step 0: Planificación Estratégica**
Se ejecuta el prompt del **Agente 1** (The Premium Website Builder) para definir la arquitectura, secciones y elementos de diseño macro del sitio.

### **Step 1: El Gancho (Hero Section)**
Se ejecuta el prompt del **Agente 2** (The First Impression Engineer) para redactar el titular (Headline), el subtítulo y el CTA principal que debe capturar la atención en menos de 3 segundos.

### **Step 2: Estructura Central (Core Page)**
Dependiendo de lo que se esté ofreciendo, se elige el agente adecuado:
- Para un producto/oferta: Se ejecuta el **Agente 3** (The Conversion Page Copywriter).
- Para un portafolio de profesional/agencia: Se ejecuta el **Agente 4** (The Portfolio Creator).
- Para un servicio de consultoría/agencia: Se ejecuta el **Agente 5** (The Service Page Copywriter).
- Para construir autoridad de marca: Se ejecuta el **Agente 6** (The About Page Storyteller).

### **Step 3: Capa de Confianza (Trust Layer)**
Se ejecuta el **Agente 9** (The Trust & Proof Architect) para integrar testimonios, logotipos, garantías y señales de confianza, eliminando la fricción de los visitantes.

### **Step 4: Manejo de Objeciones**
Se ejecuta el **Agente 10** (The Objection-Killing FAQ Writer) para convertir las 5-7 objeciones más comunes en razones irrefutables para comprar.

### **Step 5: Optimización Móvil y Pulido Final**
Se ejecutan los **Agentes 7 y 8** (The Mobile Optimizer y The Website Copy System) para refinar los botones en dispositivos móviles y pulir el copy global del sitio.

---

## 3. Catálogo de Agentes (Prompts Modulares)

Los *prompts* detallados de cada uno de los 10 agentes especializados no residen en este documento. Para mantener la modularidad y facilitar el mantenimiento (a través de OpenWiki - Spec 005), cada agente tiene su propio archivo Markdown en el subdirectorio `/prompts`:

- `01-premium-website-builder.md`
- `02-first-impression-engineer.md`
- `03-conversion-page-copywriter.md`
- `04-portfolio-creator.md`
- `05-service-page-copywriter.md`
- `06-about-page-storyteller.md`
- `07-mobile-optimizer.md`
- `08-website-copy-system.md`
- `09-trust-proof-architect.md`
- `10-objection-killing-faq.md`

---

## 4. Arquitectura del Master Orchestrator (LangGraph) - [ABANDONADO]

**Nota:** Toda esta sección queda obsoleta por el paso a Spec 010 (Single-shot Node.js workflow).
