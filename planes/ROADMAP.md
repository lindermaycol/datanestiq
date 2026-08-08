# Datanestiq: Master Roadmap & Arquitectura

Este documento centraliza la visión arquitectónica y el estado de implementación de todas las Especificaciones (Specs) del proyecto Datanestiq. Su propósito es guiar la transición desde la fase de **Prototipo (Vanilla JS / HTML)** hacia la fase de **Escalamiento B2B (Astro / OpenWiki)**.

## Flujo Arquitectónico (Specs Pipeline)

```mermaid
graph TD
    %% Base de Datos y Motor Interno
    subgraph Capa de Datos y Orquestación
        S003[Spec 003<br/>Taxonomía/Corpus JSON]:::done
        S004[Spec 004<br/>LangGraph Master Orchestrator]:::done
        S005[Spec 005<br/>OpenWiki Documentación]:::pending
    end

    %% Frontend y UX
    subgraph Capa de Presentación (Frontend)
        S001[Spec 001<br/>UI Premium / Tailwind]:::done
        S002[Spec 002<br/>Microexperiencias IA]:::done
        S006[Spec 006<br/>Astro Islands Build]:::pending
        S007[Spec 007<br/>Astro SSG Multi-Page]:::pending
    end

    %% Backend y CMS
    subgraph Capa de Backend Segura
        S008[Spec 008<br/>Headless WordPress DB]:::pending
    end

    %% Dependencias de Datos (Data Flow)
    S003 -- Alimenta a --> S004
    S003 -- Configura Rutas --> S007
    
    S004 -- Orquesta Copys a --> S006
    S004 -- Inyecta Contenido en --> S007
    S004 -- Dispara Logs hacia --> S005
    
    S001 -- Componentiza en --> S006
    S002 -- Migra Lógica a --> S006
    
    S006 -- Habilita Ruteo para --> S007
    S006 -- Envía Leads (POST) a --> S008
    
    S005 -- Genera Artículos SSG en --> S006

    classDef done fill:#064e3b,stroke:#059669,stroke-width:2px,color:#fff
    classDef pending fill:#1f2937,stroke:#3b82f6,stroke-width:2px,color:#9ca3af,stroke-dasharray: 5 5
```

## Estado de Implementación

| Spec | Nombre | Dominio | Estado |
| :--- | :--- | :--- | :--- |
| **001** | Elevación Premium | UI / UX / Frontend | ✅ COMPLETADO |
| **002** | Microexperiencias IA | Lógica / Conversión | ✅ COMPLETADO |
| **003** | Taxonomía de Servicios | Base de Datos (JSON) | ✅ COMPLETADO |
| **004** | Metodología de Desarrollo | Agentes IA / Copywriting | ✅ COMPLETADO |
| **005** | OpenWiki Interna | Documentación / Conocimiento | ⏳ PENDIENTE |
| **006** | Ecosistema Astro | Arquitectura Web / SEO | ⏳ PENDIENTE |
| **007** | Expansión Multi-Página | Ruteo Dinámico | ⏳ PENDIENTE |
| **008** | Headless WordPress | Backend CMS / API | ⏳ PENDIENTE |

## Deuda Técnica (Technical Debt)
Al estar completando una fase de prototipado rápido, hemos acumulado "Deuda Técnica" de forma consciente (atajos temporales que permiten velocidad pero que no escalan). Cada Spec cuenta con su propio archivo `tech_debt.md` en su respectivo directorio documentando estas brechas que deberán saldarse durante la implementación de las Specs 006 y 007.
