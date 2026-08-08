# Prompt para Antigravity — Corregir la prueba social FABRICADA del home (honestidad)

Una auditoría detectó **prueba social inventada** en el home, incoherente con el estándar de honestidad que aplicamos en todo el resto del sitio (`[EST]`, cero casos/testimonios/logos falsos). Datanestiq es una **firma nueva**: no puede presentar clientes, logos ni testimonios que no existen. Hay que corregirlo en **3 lugares** (verificados):

1. **`src/content/pages/home.md`** — sección `logos` (FINCORP, MEDITECH, GLOBAL RETAIL, INDUX, SECURE NET) bajo *"Trusted by Visionary Organizations"* + sección `testimonials` (*"María Jiménez, Directora de Operaciones, RetailCorp"*, "+24% retención").
2. **`src/components/ui/TrustLayer.astro`** — los **valores por defecto** replican esos mismos datos falsos (incluido `testimonialLabel = 'MJ'`). Si solo arreglas el `.md`, el default falso vuelve a aparecer.
3. **`src/components/ui/Faq.astro`** (línea ~32) — *"Nuestros clientes empresariales reportan un ROI positivo entre los 3 y 6 meses…"* afirma una base de clientes que quizá no existe.

## Qué hacer (enfoque honesto)

### A. Fila de "logos" → **stack tecnológico real** (no clientes)
Reemplaza los logos de empresas-cliente inventadas por las **tecnologías que Datanestiq realmente domina** (son capacidades reales, no relaciones con clientes). Cambia también el `heading` para que **no implique clientela**:
- `heading`: algo como **"Construido sobre un stack de clase mundial"** o **"Tecnologías que dominamos"** (NO "Trusted by…").
- Ítems: tómalos del `techStack` real de la taxonomía (p. ej. **AWS, Databricks, Snowflake, Python, LangChain, Docker/Kubernetes, dbt**). Usa nombres/íconos verídicos.
- Actualiza el comentario `<!-- Logos (Simulated) -->` en `TrustLayer.astro`.

### B. Testimonio inventado → **quitar o reencuadrar** (DECISIÓN para el usuario)
No inventes una persona ni una empresa ni una cifra de cliente. Elige una vía y **déjala marcada como borrador para aprobación del usuario**:
- **Opción 1 (recomendada):** **eliminar** el bloque de testimonio hasta que existan casos reales.
- **Opción 2:** sustituirlo por una **declaración de propuesta de valor/metodología** atribuida a **Datanestiq como firma** (no a una persona ficticia) — p. ej. una frase sobre el compromiso de ROI/gobernanza, sin nombre ni empresa inventados.
- **Opción 3:** un bloque de **resultado modelo claramente etiquetado `[EST] Escenario Ilustrativo`** (consistente con la calculadora), sin atribuirlo a un cliente real.
> **Prohibido:** nombres de personas, empresas o cifras presentadas como cliente real.

### C. FAQ → reencuadrar la afirmación de "clientes"
Cambia *"Nuestros clientes empresariales reportan…"* por una formulación **defendible** que no afirme una base de clientes inexistente: p. ej. *"Diseñamos los proyectos para alcanzar un ROI positivo típicamente entre los 3 y 6 meses…"* o *"Según benchmarks del sector, el ROI suele observarse en 3–6 meses…"*. Revisa el resto del FAQ por afirmaciones similares y aplícales el mismo criterio.

## Guardarraíles (no negociables)
- **Nunca** inventes empresas, logos, personas, testimonios ni cifras de clientes. Si no se puede verificar como real, **no** va en el sitio como prueba social.
- Lo único que puedes afirmar sin `[EST]` son **capacidades reales** (tecnologías del stack, metodología). Toda proyección va como **`[EST]`**.
- No rompas el layout ni las animaciones de `TrustLayer`/FAQ; `npm run build` verde; consola limpia.
- No toques nada fuera de estos 3 archivos (+ el `.md`).

## Forma de respuesta
- Implementa A y C. Para **B**, aplica tu opción recomendada pero **marca el texto final como "borrador para aprobación"** y lista las 3 opciones para que el usuario elija.
- Reporta el **diff** de los 3 archivos y confirma con `grep` que **ya no aparecen** en `dist/`: `FINCORP`, `MEDITECH`, `María Jiménez`, `RetailCorp`, `Trusted by Visionary`, ni la cifra "24%" como testimonio.
- `npm run build` verde. Sección final "Hallazgos adicionales".

---
**Aparte (responde esto):** en el turno anterior el usuario dijo *"Antigravity ha culminado"*, pero no encontré cambios nuevos en el repositorio. **¿Qué tarea ejecutaste y por qué no produjo cambios en archivos?** Acláralo.

**Nota:** Claude (Opus 4.8) reauditará en `dist/` que la prueba social fabricada desapareció, que la fila de tecnologías sea veraz, y que el build siga verde.
