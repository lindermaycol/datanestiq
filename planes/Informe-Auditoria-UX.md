# Informe de Auditoría UX — Datanestiq (navegación real por roles)

**Método:** navegación real en navegador (`astro preview --host` sobre el build de producción) con clics reales, inspección de **red**, **consola** y **responsive** (móvil 375px / desktop). Se interpretaron roles del comité de compra y se recorrieron home, chatbot, páginas de sector, soluciones e institucionales.

---

## Resumen ejecutivo
El sitio está **sólido y genuinamente personalizado**: la taxonomía impulsa de verdad las microinteracciones (no es decorativo). El chatbot ofrece las **personas correctas según el sector** y arma mensajes con los **KPIs, objeciones y criterios de decisión reales** del rol+industria, **sin costo (0 llamadas LLM)** en el flujo guiado. Las páginas de sector muestran **regulaciones y KPIs `[EST]` creíbles y específicos**. **Cero errores de consola** en las páginas probadas; **responsive** correcto.

**Los 3 hallazgos más importantes:**
1. Las **páginas de sector están sub-enlazadas** (no hay enlaces `<a href>` rastreables; solo botones JS) → problema de SEO/descubrimiento.
2. El enlace **"Metodología" del menú apunta al FAQ** (`/#faq`), no a la sección de metodología (que sí existe).
3. Existe un **enlace a `#roi` sin destino** (ancla muerta) en el home.

Veredicto como cliente: **sí convence** a agendar una auditoría; la personalización por rol es un diferenciador real. Las mejoras son de enlazado/SEO y detalles de navegación, no de fondo.

---

## Aciertos verificados EN VIVO
- **Chatbot data-driven (Spec 002↔011):** las personas cambian por sector —
  - *Finanzas* → CFO / CISO / CDO
  - *Manufactura* → COO / CTO
  Y el mensaje es hiperpersonalizado. Ejemplos reales capturados:
  - **CFO/Finanzas:** *"…optimizar KPIs clave como Reducción de falsos positivos en fraude, Tiempo de evaluación crediticia cumpliendo con Basilea III/IV."*
  - **COO/Manufactura:** *"…buscas 'Aumento de eficiencia (OEE, Tiempos)' y debes mitigar 'Mi equipo de operaciones se resistirá a usar un nuevo sistema'. En Manufactura, optimizar KPIs como Mejora del OEE…"*
- **0 llamadas LLM** en el flujo guiado (verificado en la pestaña de Red: solo assets estáticos + `sectorsCorpus.js`/`personas.js`). El texto libre sí llama al backend (correcto).
- **Páginas de sector** (`/sectores/*`): subsectores, KPIs con `[EST]`, y compliance real (Basilea/PCI-DSS/**SBS**, HIPAA/HL7-FHIR).
- **Páginas de solución** (`/soluciones/*`): stack tecnológico, proof points y posicionamiento (anti vendor lock-in / deuda técnica).
- **Home data-driven** con **paridad visual** (headline con gradiente y salto de línea).
- **Responsive móvil (375px):** menú hamburguesa, hero y CTAs sin ruptura.
- **Sin errores de consola** en home, sector y solución.
- Rutas institucionales OK: `/nosotros`, `/casos-de-exito`, `/blog` → 200.

---

## Bugs y hallazgos (priorizados)
| Sev | Hallazgo | Detalle / URL | Cómo reproducir |
|---|---|---|---|
| **P2** | Sectores **sub-enlazados** (SEO/descubrimiento) | Las 10 `/sectores/*` solo se alcanzan por `<button onClick>` de la isla "Soluciones por Industria"; **no hay `<a href>`** ni ítem "Sectores" en el menú. | Inspección de enlaces del home: `sectores: []` |
| **P2** | "Metodología" apunta al FAQ | Menú "Metodología" → `/#faq` (existe una sección de metodología en el home, pero el link va al FAQ). | Clic en "Metodología" → baja a FAQ |
| **P2** | Ancla muerta `#roi` | Un enlace del home apunta a `#roi`, pero no existe `id="roi"`. | El clic no hace scroll |
| **P3** | Rutas `/servicios` y `/metodologia` → 404 | El menú usa anclas (`/#servicios`, `/#faq`), así que **no** rompe la navegación; pero esas rutas directas no existen. | `fetch('/servicios')` → 404 |

> No se detectaron: errores de consola, páginas draft filtradas, `/home` duplicada, imágenes rotas ni contenido "undefined" en lo recorrido.

---

## Evaluación por rol (rúbrica, navegación real)
| Rol / Sector | Relevancia | Propuesta valor | Manejo objeciones | UX | Confianza |
|---|---|---|---|---|---|
| **CFO / Finanzas** | 5 — el chatbot cita Payback/TCO, objeción "ROI abstracto", KPIs de fraude y Basilea/PCI-DSS/SBS | 4 | 4 — la objeción se nombra explícitamente en la interacción | 4 | 4 |
| **COO / Manufactura** | 5 — OEE, "mi equipo se resistirá", KPIs de mantenimiento | 4 | 4 | 4 | 4 |

*(La personalización varía correctamente por rol+sector — verificado en 2 combinaciones; el patrón aplica a las 7 personas / 10 sectores por diseño.)*

---

## Recomendaciones (backlog priorizado)
1. **[P2 · alto impacto] Enlazar las páginas de sector con `<a href>` reales** (una grilla de sectores enlazada y/o un ítem "Sectores"/"Industrias" en el menú) → mejora SEO, indexación y descubrimiento de las landings ricas.
2. **[P2] Corregir "Metodología"** para que apunte a la sección de metodología (o renombrar el ítem si su destino real es el FAQ).
3. **[P2/P3] Arreglar o quitar el ancla `#roi`** (apuntarla a la sección/página de casos ROI).
4. **[Mejora de contenido, válida del review previo] Subir 1–2 KPIs/objeciones por rol al home** (bajo cada tarjeta de rol) para enganche inmediato de CFO/CEO sin tener que navegar.

---

## Pendientes del usuario (fuera del código)
- 🔴 Rotar la contraseña SSH de IONOS (historial de git).
- Configurar los secrets `GROQ`/`DASHSCOPE`/`GEMINI` en GitHub para el workflow `content-pr.yml`.
