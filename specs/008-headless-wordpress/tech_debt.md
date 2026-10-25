# Deuda Técnica: Spec 008 (Headless WordPress)

## Estado
- **Fase actual:** ⏸️ En pausa por decisión del usuario hasta validar el frontend (ya validado E2E)
- **Impacto:** Crítico
- **Severidad:** Alta

## Lista de Deuda Técnica (Technical Debt)

### 1. Ausencia de Custom Post Types y API REST
- **Descripción:** La instancia local no posee endpoints expuestos ni el CPT `datanestiq_lead`. Temporalmente, se emplea CSV como "sink" de datos soberano.

### 2. Editor del Blog B2B (Nuevo Ángulo)
- **Descripción:** El headless WP podría servir como UI de edición robusta para la Parte C (Blog). Los editores no técnicos escriben en WP, y el contenido alimenta las colecciones de Astro. Requiere documentar el `plan.md` y `tasks.md`.

### 3. Credenciales SSH en Texto Plano [CRÍTICO]
- **Descripción:** El archivo `remote_extract.py` expuso credenciales críticas de la instancia productiva (`host`, `user`, `password`) en el historial de Git.
- **Acción Requerida:** **[RESUELTO — código migrado a `.env`] CRÍTICO: El usuario DEBE rotar la contraseña SSH de IONOS manualmente en su panel ya que quedó expuesta en commits anteriores de Git. Antigravity no puede tocar credenciales.**
