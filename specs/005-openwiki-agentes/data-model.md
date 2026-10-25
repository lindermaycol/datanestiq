# Data Model: OpenWiki CI/CD

Dado que esta Spec 005 es una infraestructura de CI/CD, no requiere esquemas de base de datos tradicionales.

## Workflow Event Payload (GitHub Actions)
```json
{
  "event": "schedule",
  "cron": "0 2 * * *",
  "actor": "github-actions[bot]",
  "permissions": {
    "contents": "write",
    "pull-requests": "write"
  }
}
```

## Pull Request Data Contract
Cada PR generada por el agente debe cumplir con este esquema:
- **Title Prefix:** `🤖 Actualización Automática de Documentación Viva`
- **Labels:** `documentation`, `automated pr`
- **Branch Naming:** `bot/openwiki-sync-[timestamp]`
- **Required Reviewers:** Mínimo 1 humano.
- **Validation:** No debe contener secretos, contraseñas o PII en los diffs de Markdown.
