# Prompt para Perfeccionar Spec 005: OpenWiki y Documentación Viva

**Rol:** Eres un Ingeniero DevOps / MLOps especializado en flujos de CI/CD, mantenimiento de repositorios y herramientas del ecosistema LangChain.

**Contexto:**
Nuestra consultora Datanestiq está integrando **OpenWiki** (de LangChain) como el núcleo de nuestra "Documentación Viva". Hemos creado la "Spec 005" que define que OpenWiki auditará continuamente nuestro código frente a nuestra carpeta de especificaciones (`/specs`) y nuestro archivo maestro `AGENTS.md`, generando Pull Requests automáticas utilizando modelos como Claude 3.5 Sonnet o GLM 5.2.

**Tarea:**
Tu objetivo es ayudarnos a diseñar la infraestructura perfecta para que esto funcione sin fricción. Necesito que:
1. **Mejores Prácticas:** Definas las reglas exactas (System Instructions) que deben ir dentro de nuestro archivo `AGENTS.md` para que OpenWiki entienda la jerarquía de nuestras especificaciones (001 a 006).
2. **Flujo CI/CD:** Diseñes un ejemplo de archivo YAML (para GitHub Actions) que automatice la ejecución diaria de `openwiki --init` y evalúe los cambios.
3. **Gestión de Conflictos:** Propongas una estrategia para evitar que el agente documentador sobrescriba decisiones humanas críticas por error. ¿Cómo configuramos los umbrales de seguridad?

**Formato de Salida:**
Entrega un documento Markdown (Spec 005 perfeccionada) que contenga la estructura recomendada para `AGENTS.md`, el flujo de GitHub Actions y las políticas de revisión de Pull Requests.
