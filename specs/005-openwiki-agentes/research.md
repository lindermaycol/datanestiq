# Research: OpenWiki Infrastructure

## Decisiones Técnicas

### 1. Framework CI/CD
- **Decision:** Usar GitHub Actions (`peter-evans/create-pull-request`)
- **Rationale:** Es el estándar en la industria para orquestar flujos de trabajo sobre repositorios de GitHub. La integración de `peter-evans` permite automatizar las PRs con metadatos, reviewers asignados y branches limpios (`bot/openwiki-sync`).
- **Alternatives considered:** GitLab CI (descartado porque Datanestiq usa GitHub), scripts de cron locales (muy frágil).

### 2. Gestión de Confidence Threshold
- **Decision:** Configurar `--threshold 0.85` en OpenWiki CLI.
- **Rationale:** Minimiza los falsos positivos y previene que el LLM proponga cambios destructivos por "alucinación" (Drift Documental negativo).
- **Alternatives considered:** Threshold dinámico o 0.95 (muy estricto, rechazaría refactors menores).

### 3. Modelo de Lenguaje
- **Decision:** Claude 3.5 Sonnet.
- **Rationale:** Alto nivel de razonamiento para código y documentación estructurada, muy superior analizando jerarquías complejas y manteniendo tono consultivo.
- **Alternatives considered:** GLM 5.2 (bueno, pero Claude 3.5 Sonnet tiene mejor benchmark en Context Windows grandes).
