# Prompt AntiGravity — OpenData · Fase 1: Diagnóstico Honesto + Bootstrap de Metodología SDD

## Rol y regla dura
Vas a **resucitar** un proyecto Python de ~3 años (`C:\Users\Lisbeth\Downloads\opendata`) adoptando
la **metodología SDD** y las buenas prácticas del proyecto **datanestiq**. En esta Fase 1
**NO reescribes ni "modernizas" código de negocio.** Solo: (1) poner el repo a salvo, (2) producir
un **diagnóstico honesto**, (3) documentar lo que YA existe como **specs "as-built"**, y (4) montar
el **andamiaje SDD**. El stack se mantiene en **Python** (no migrar a otro lenguaje). Cualquier
mejora de código se **propone** como spec/tech-debt para una Fase 2, no se ejecuta ahora.

> Principio rector (heredado de datanestiq §2 — Honestidad Radical): **cero afirmaciones sin
> verificar**. Si no confirmaste que un script corre, di "no verificado". Si no sabes cuál de dos
> scripts duplicados es el canónico, dilo. Nada de "funciona 100%" sin evidencia.

---

## Paso 0 — Estudia el MÉTODO en datanestiq (no copies su stack, copia su forma de trabajar)
Lee estos archivos en `C:\xampp\htdocs\datanestiq\` para entender la metodología a transplantar:
- `.specify/memory/constitution.md` → la **constitución operativa** (honestidad §2, principios). Es la
  fuente de verdad del método. **Adáptala** a opendata (misma filosofía, principios re-escritos para
  un pipeline de datos/ML en Python).
- `CLAUDE.md` y `AGENTS.md` → cómo se define el rol del agente, convenciones, reglas de campo.
- `specs/026-analitica-blog-panel/{spec.md, data-model.md, plan.md, tech_debt.md}` → la **forma
  exacta de una spec** (4 artefactos). Cópiala como estructura.
- `planes/ESTADO-SPECS.md` + `src/data/specsStatus.json` + `scripts/build-specs-status.mjs` → el
  **build-gate antidrift** (matriz SSOT ↔ JSON verificado). Adapta el concepto (puede ser un JSON +
  un pequeño script Python que valide coherencia, no hace falta Node).
- `planes/Fases.md` → cómo se ve un **roadmap** por fases.

El objetivo: opendata termina con la **misma disciplina** (constitución, `specs/NNN-*/`, ESTADO-SPECS,
verificación honesta), pero **en Python y para su dominio** (datos abiertos CKAN + NLP).

---

## Paso A — Poner el repo a salvo (git + higiene), SIN perder nada
1. `git init` en `C:\Users\Lisbeth\Downloads\opendata`.
2. Crear `.gitignore` que **excluya el bloat y los datos pesados** (NO borrarlos del disco, solo no
   trackearlos): el venv commiteado `chatgpt/`, `modelos/`, `model_cache/`, `__pycache__/`, `*.pyc`,
   `*.sqlite`, `*.zip`, `.vscode/`, y cualquier caché de HuggingFace/torch.
3. **Primer commit "as-found"** con todo el código fuente (los `.py`, `querys/`, `tags.csv`,
   `database.html`) como línea base intacta. Mensaje: `chore: baseline as-found (pre-SDD)`.
   Esto congela el punto de partida antes de tocar nada.
4. Documentar aparte (en el inventario) que las bases `database/*.sqlite` (21 MB + 18 MB) y los
   modelos quedan **fuera de git** por peso, con una nota de dónde viven.

## Paso B — Diagnóstico honesto → `INVENTARIO.md`
Producir `INVENTARIO.md` en la raíz con:
1. **Mapa de scripts**: qué hace cada `.py`, y marcar **canónico vs duplicado/muerto**. Sabemos que
   hay familias duplicadas versionadas a mano: `textclassification.py` / `text_classification_improved.py`
   / `text_classificationv2.py` / `v3`; `preprocesador_texto.py` / `preprocesador_texto2.py`;
   `scraper.py` / `scrapergob.py` / `opendata.py`. Determina (leyendo imports reales y `main.py`)
   cuál está **en uso** y cuál es descarte. Si no puedes determinarlo, decláralo "ambiguo — requiere
   decisión humana".
2. **Dependencias reales**: si el venv `chatgpt/` arranca, haz `pip freeze` desde él para capturar
   las **versiones que funcionaban** (oro para reproducibilidad). Si no arranca, deriva las deps de
   los imports (`transformers, torch, tensorflow, spacy, huggingface_hub, datasets, pandas, numpy,
   scipy, bs4, requests, validators, sqlite3`). Anota el riesgo: torch + tensorflow + transformers
   sin fijar, 3 años, es la deuda #1 de reproducibilidad.
3. **Esquema de datos real**: tablas, columnas y **conteo de filas** de `database/pnda.sqlite`
   (tablas observadas: `datasets, resources, tags, groups, entidades, dt_recursos, dt_groups`…).
   Explica qué son las tablas `dt_*` (¿intento de tracking/delta?).
4. **Qué corre hoy vs qué está roto** (verificado, no supuesto): intenta ejecutar el flujo principal
   en modo seguro; reporta qué arranca y qué falla, con el error real.
5. **Defecto crítico ya detectado**: `main.py` hace `delete_all_data` de todas las tablas antes de
   insertar → **borra el histórico en cada corrida**. El objetivo del proyecto (trazar cómo cambian
   los datasets en el tiempo) **no está implementado**; solo hay snapshots manuales. Regístralo como
   hallazgo, no lo arregles aún.
6. **Barrido de seguridad**: secretos/keys hardcodeados (en el escaneo previo no aparecieron, confírmalo),
   y cualquier PII en la data.

## Paso C — Reverse-spec: documentar lo EXISTENTE como specs "as-built" (serie 000)
Crear `specs/000-*/` describiendo el comportamiento **actual** (con sus fallos, honestamente), usando
los 4 artefactos como en datanestiq. Sugerido:
- `specs/000-ingesta-scraping-ckan/` — scraping del portal CKAN → SQLite (incluye el defecto delete-all).
- `specs/001-clasificacion-nlp/` — la capa de clasificación (SDG/ODS, transformers) y sus versiones.
Cada `spec.md` = qué hace hoy; `data-model.md` = esquema SQLite real; `plan.md` = cómo está armado
hoy; `tech_debt.md` = deuda observada (duplicados, delete-all, deps sin fijar, sin tests).
**No inventes** funcionalidad que no exista; si algo está a medias, márcalo como tal.

## Paso D — Bootstrap del andamiaje SDD (adaptado a Python/datos)
Crear, adaptando las plantillas de datanestiq:
- `.specify/memory/constitution.md` — constitución de opendata (honestidad §2 sobre clasificaciones y
  métricas: nada de precisión inventada; `[EST]` con base; guards de "datos insuficientes"; DEMO
  marcado; privacidad de datos).
- `CLAUDE.md` — rol del agente + hechos del ecosistema opendata (no re-descubrir): stack Python,
  fuente CKAN `datosabiertos.gob.pe`, dónde vive la data, cómo se corre.
- `AGENTS.md` — reglas operativas/QA para este repo.
- `planes/ESTADO-SPECS.md` + un `estado_specs.json` (o `specsStatus.json`) + un pequeño validador
  Python que verifique que ambos coinciden (build-gate antidrift, equivalente al `.mjs`).
- `planes/Fases.md` — roadmap propuesto de modernización (ver abajo), sin ejecutarlo.
- `requirements.txt` (o `pyproject.toml`) con las versiones capturadas del `pip freeze` si fue posible,
  o marcadas como "a fijar" si no.
- `README.md` — qué es, cómo instalar/correr, estado honesto.

## Roadmap propuesto para Fase 2+ (solo **proponer** en `planes/Fases.md`, NO ejecutar)
Alinéalo con la visión del dueño (búsqueda semántica que entiende estructura, "cerebro" que sabe cómo
se cruzan los datasets, tracking temporal, clasificación amigable):
- Consolidar los scripts duplicados a un módulo canónico único.
- Rediseñar el esquema a **histórico/temporal** (versionar cambios de dataset en vez de delete-all).
- Capa de **búsqueda semántica** sobre metadatos (embeddings; principio 0-LLM-first donde se pueda,
  al estilo de las specs 024/026 de datanestiq, pero en Python).
- **Grafo de conocimiento** de datasets (cómo se cruzan/combinan) — inspirado en la spec 025
  (GraphRAG/taxonomía) de datanestiq.
- Suite de pruebas + fijar entorno reproducible.

---

## Entregables de esta Fase 1
1. Repo git inicializado con `.gitignore` correcto y commit baseline "as-found".
2. `INVENTARIO.md` honesto (scripts canónicos vs muertos, deps reales, esquema+conteos, qué corre,
   defectos, seguridad).
3. `specs/000-*` y `specs/001-*` "as-built".
4. Andamiaje SDD: constitución, CLAUDE.md, AGENTS.md, ESTADO-SPECS + JSON + validador, Fases.md,
   requirements, README.
5. **Cero cambios de comportamiento** en el código de negocio.

## Criterios de aceptación (los revisaré como auditor)
1. `git log` muestra el baseline; `git status` limpio tras los commits de andamiaje; venv/modelos/
   sqlite **fuera** de git.
2. `INVENTARIO.md` distingue explícitamente canónico vs duplicado y **no afirma** que algo corre sin
   evidencia (los que no se probaron van como "no verificado").
3. Las specs 000/001 describen el comportamiento **real** (incluido el defecto delete-all), no una
   versión idealizada.
4. La constitución y el CLAUDE.md están **adaptados a opendata** (no son copias literales de datanestiq).
5. El roadmap de Fase 2 está **propuesto, no ejecutado**; ningún script de negocio fue reescrito.
6. Reporte final honesto: qué se hizo, qué quedó "no verificado", y qué decisiones humanas faltan
   (p. ej. qué duplicado descartar).

> Tras tu entrega, el auditor (Claude/datanestiq) revisará el diagnóstico y las specs as-built antes
> de autorizar la **Fase 2 (modernización)**. No marques nada como "modernizado/terminado" en esta fase.
