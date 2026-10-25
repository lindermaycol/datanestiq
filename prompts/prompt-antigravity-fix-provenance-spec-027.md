# Prompt AntiGravity — Fix §2: distintivo de provenance en respuestas del Experience Bank (Spec 027)

## Contexto (hallazgo de auditoría en vivo, 2026-08-10)
El build de la 027 está desplegado y funcional, **pero incumple un requisito §2 del propio spec**.

`spec.md` US-027.1 exige que toda respuesta reusada 0-LLM lleve *"un distintivo transparente:
**'Respuesta de Experiencia Curada Datanestiq'**"* (Provenance visual §2). El walkthrough afirmó
que se entrega "con distintivo transparente", pero en el código **no es cierto**:

- En `src/components/islands/Chatbot.jsx:496` el mensaje de experiencia se marca con
  `isExperience: true`.
- Pero el render de mensajes en `src/components/islands/Chatbot.jsx:668`
  (`displayMessages.map((m, i) => ...)`) **solo usa `m.role` y `m.content`** — nunca lee
  `m.isExperience`. El flag es **metadata muerta**.

Resultado: una respuesta curada se serviría **indistinguible** de una respuesta en vivo, sin
etiqueta de origen. Es un incumplimiento §2 de transparencia. Hoy es **latente** (banco vacío,
`total:0`), pero se activa en cuanto se apruebe el primer candidato. **Debe cerrarse antes de
aprobar experiencias.**

---

## Fix 1 (principal) — Renderizar el distintivo de provenance
En `src/components/islands/Chatbot.jsx`, dentro de `displayMessages.map(...)` (~línea 668),
cuando `m.isExperience` sea `true`, mostrar **sobre o junto** al contenido del mensaje un
distintivo visible y transparente con el texto **verbatim**:

> **Respuesta de Experiencia Curada Datanestiq**

Requisitos:
- Texto **exacto** (no parafrasear): `Respuesta de Experiencia Curada Datanestiq`.
- Estilo discreto pero legible (p. ej. una etiqueta/badge pequeña sobre la burbuja del asistente),
  coherente con el diseño actual del chat (usa las clases utilitarias ya presentes, p. ej.
  `text-brandCyan`, `border-brand/30`, `bg-brand/10`). Un icono opcional (`ph ph-seal-check` o
  similar) es aceptable, pero el **texto** es obligatorio.
- Solo debe aparecer en mensajes con `m.isExperience` (no en respuestas normales, FAQ ni LLM).
- No inventes provenance donde no la hay: no lo pongas en mensajes sin ese flag.

**Alcance mínimo:** solo tocar el render del `.map`. No cambies la lógica del router
(`matchExperience`, umbral 0.75, `experience_hit`), ni el backend, ni el flujo FAQ/LLM.
Reutiliza el `expMsg`/`cite_url` tal como ya se construyen (líneas 488-491); el `cite_url` ya se
muestra como referencia y no debe duplicarse.

## Fix 2 (secundario, menor) — Endurecer `experience_hit.php` a POST
`public/api/experience_hit.php` acepta **GET** y lee `$_GET['id']`, así que cualquiera puede
inflar `usage_count` golpeando la URL. Restringir a **POST** únicamente:
- Quitar `GET` de `Access-Control-Allow-Methods` y dejar de leer `$_GET['id']` (usar solo
  `$_POST['id']`).
- Si `REQUEST_METHOD !== 'POST'` → `405 Method Not Allowed`.
- El cliente ya lo llama por POST (`Chatbot.jsx:482`), así que no rompe nada.
Esto no vuelve la métrica "dura" (sigue sin sesión), pero elimina el inflado trivial por GET.

---

## §2 / Restricciones
- No re-servir respuestas sin provenance visible: el distintivo es la garantía de transparencia.
- No tocar otras specs, ni el SSOT salvo lo indicado abajo.
- Cambio de frontend ⇒ **obligatorio** recompilar: `npm run build` **antes** de
  `python scripts/deploy/deploy_ionos.py --confirm` (el PHP/JS se sirve desde `dist/`; sin
  recompilar se sube la versión vieja).

## Criterios de aceptación (los verificaré EN VIVO)
1. En `Chatbot.jsx`, el `.map` de `displayMessages` **consume `m.isExperience`** y renderiza el
   texto exacto `Respuesta de Experiencia Curada Datanestiq`.
2. **Prueba funcional real**: inserta temporalmente una experiencia `aprobado` y vigente en
   `experience_bank` (o aprueba un candidato de prueba en `/admin/`), lanza en el chatbot una
   consulta que supere 0.75, y **confirma visualmente** que aparece el distintivo (captura/screenshot).
   Limpia la fila de prueba al terminar (deja el banco como estaba).
3. `experience_hit.php` responde `405` a GET y `200`/`400` a POST según corresponda.
4. Consola del navegador **sin** `ReferenceError`/errores; el fallback a FAQ/LLM sigue intacto para
   mensajes sin el flag.
5. Recompilado y desplegado; el bundle nuevo en prod contiene el render del distintivo (marcador
   verificable en vivo, no solo local).
6. Solo tras verde real, si decides marcar la 027 como cerrada/LIVE en `planes/ESTADO-SPECS.md` y
   `src/data/specsStatus.json`, hazlo con `isHonest: true` y fecha real. Si no, mantenla como está.

> Nota: reporta lo que **realmente** se ve en vivo (con captura del distintivo), no "hecho". En este
> proyecto el "verde" del reporte ya falló antes justo en este punto.
