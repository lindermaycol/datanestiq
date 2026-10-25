# Prompt para Antigravity — Commit + push del pipeline (con pre-verificación de seguridad OBLIGATORIA)

Sube al repositorio el trabajo del pipeline SDD (Enriquecimiento F3 → Fundación → Spec 013 + refinamiento de la calculadora). **Un push publica de forma externa y difícil de revertir**, y este árbol contiene secretos y PII. Por eso, **NO uses `git add -A` a ciegas**: primero endereza el `.gitignore`, luego verifica que nada sensible se vaya a commitear, y solo entonces commiteas y empujas.

## PASO 0 — Arreglar `.gitignore` (antes de tocar el índice)
Edita `.gitignore` y añade/asegura:
1. **`.venv/`** — hoy NO está ignorado; sin esto subirías miles de archivos del entorno Python. Agrégalo.
2. **Excepción para el backend de la app** (hoy la regla `*.php` ignora también nuestro API PHP, que SÍ debe versionarse porque no contiene secretos —las claves viven en `.env` vía `getenv`):
   ```
   !public/api/chat.php
   !public/api/save_wizard.php
   ```
   (colócalas después de la línea `*.php`). Verifica con `git check-ignore -v public/api/chat.php` que **ya NO** queden ignorados.
3. **Basura/temporales** que no deben subir — agrégalos al `.gitignore`:
   ```
   .venv/
   appjs_extract.txt
   appjs_lines.txt
   appjs_lines2.txt
   indexhtml_extract.txt
   indexhtml_lines.txt
   openwiki-log.txt
   conversation_history/
   ```
   (`conversation_history/` puede contener datos sensibles de sesiones — NO subir.)

## PASO 1 — 🔴 Verificación de seguridad (BLOQUEANTE; aborta si algo falla)
Confirma que los archivos sensibles siguen ignorados y **fuera del índice**:
```
git check-ignore -v .env secure_leads/ chat_logs.jsonl wp-config.php .venv/
git ls-files | grep -E '\.env$|secure_leads/|\.jsonl$|wp-config\.php$'   # DEBE salir vacío
```
Si `git ls-files` devuelve **cualquier** coincidencia (`.env`, algo bajo `secure_leads/`, un `.jsonl`, o `wp-config.php`), **DETENTE y repórtalo** — no commitees. Nunca subas claves ni PII.

## PASO 2 — Preparar y revisar el staging
- Añade el trabajo real: `git add -A` **solo después** de que el PASO 0 dejó el `.gitignore` limpio (así los ignorados no entran).
- **Revisa manualmente** la lista staged antes de commitear:
  ```
  git status
  git diff --cached --name-only
  ```
  Escanea esa lista: no debe aparecer `.env`, `secure_leads/…`, `*.jsonl`, `.venv/…`, ni los `.txt` scratch. Si aparecen, corrige el `.gitignore`/`git rm --cached` y vuelve a revisar.

## PASO 3 — Commit
Rama actual: `007-multi-pagina` (no es `main`, así que commitear aquí es válido). Mensaje sugerido (Conventional Commits):
```
feat: pipeline conversión consultiva por rol × sector (Spec 013) + fundación + enriquecimiento taxonomía

- Spec 011 F3: enriquecimiento de taxonomía (techStack/arquitectura, KPIs [EST], objectionResponses 1:1, deployment/engagement models, roiCases) + schema
- Fundación: /soluciones/* y /sectores/* renderizan taxonomía + bloque "Resolvemos tus dudas"; hubs /soluciones y /sectores; nav; páginas legales; footer sin href="#"
- Spec 013: motor A1–A9 (userContext, ContextChips, ConsultativeCTA dinámico, IllustrativeRoiCase [EST], reuso semanticHighlight); Fases Público/CFO/CEO; intents 0-LLM en taxonomía; Business Case Estimator (client-side, modelo defendible); formulario vía chat.php (PII segura)
- Fixes: chat.php parse error corregido; blogs sector público
```
> Nota: la calculadora computa client-side y el formulario reusa `save_wizard.php`; ningún secreto ni PII va en el código.

## PASO 4 — Push
- `git push origin 007-multi-pagina` (empuja a la **rama actual**, NO a `main`).
- **No** hagas merge a `main` ni fuerces nada. Si el usuario quiere llevarlo a `main`, será vía Pull Request aparte.
- Si el remoto rechaza por historial divergente, **reporta** el error — no hagas `--force`.

## PASO 5 — Reporte (evidencia)
Reporta:
1. El `.gitignore` final (diff de lo que añadiste).
2. Salida de `git check-ignore -v` de los sensibles + confirmación de que `git ls-files | grep` sensible salió **vacío**.
3. `git diff --cached --name-only` (o el resumen del commit) — la lista de archivos realmente commiteados.
4. Confirmación del push (hash del commit + rama) y que **no** se incluyó `.venv/`, `.env`, `secure_leads/`, `*.jsonl` ni scratch `.txt`.
5. Sección "Hallazgos adicionales".

---
**Recordatorio:** No toques el core de WordPress (`wp-admin/`, `wp-includes/`, `wp-content/`). El objetivo es versionar el trabajo del pipeline **sin filtrar** secretos ni PII. Claude (Opus 4.8) revisará tu reporte y puede correr `git ls-files`/`git log` para confirmar que no se subió nada sensible.
