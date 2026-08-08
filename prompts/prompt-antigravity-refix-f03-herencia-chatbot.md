# Re-fix — F-03: la herencia de contexto del chatbot no funciona en el flujo intra-sesión

Auditoría en navegador: **5 de 6 fixes OK**. Solo **F-03 (herencia del chip en el chatbot) está roto** en el caso principal. Corrección puntual; sigue extendiendo Spec 002 / Spec 013. Guardarraíles intactos.

## Diagnóstico (verificado en vivo)
`Chatbot.jsx` monta con `client:idle` y ejecuta su `useEffect` de init **al hidratar**, cuando el `userContext` todavía está **vacío**. Ahí fija `initialized.current = true` y muestra el saludo genérico. Cuando el usuario elige un chip **después** (que es el flujo real: aterriza → elige chip → abre el chatbot), el `useEffect` re-corre por el cambio de `ctxState` pero el guard `if (!initialized.current)` ya es `false` → **nunca hereda**.

Evidencia:
- **Elegir chip CDO → abrir chatbot:** saludo genérico *"¿A qué sector perteneces?"* ❌ (debería heredar).
- **Contexto ya en localStorage al recargar → abrir:** saludo heredado *"Veo que estás explorando como CDO… en Finanzas y Banca…"* ✅.

O sea, la lógica de herencia (incluido el mapeo slug→id) **está bien**; el problema es **cuándo** corre la init.

## Fix
Que el saludo se inicialice **cuando el panel se ABRE por primera vez** (leyendo el `userContext` vigente en ese momento), no en el mount `idle`:
- Cambia el guard para que la init corra en la **primera apertura**: efecto que dependa de `isOpen` (y de `ctxState`) y ejecute la inicialización cuando `isOpen === true && !initialized.current`. Al abrir, se lee el contexto activo (chip recién elegido) y se compone el saludo heredado / se saltan los pasos conocidos, igual que hoy.
- **Idempotencia:** tras la primera apertura, `initialized.current = true`; cerrar y reabrir **no** debe re-inicializar ni pisar una conversación en curso.
- **Compatibilidad:** el caso de contexto persistido de sesión previa debe seguir funcionando (se lee igual al abrir). El caso "abrir desde el CTA del buscador" (setea `lastUserQuery` + `chatbotOpen`) también debe seguir OK.
- **0-LLM intacto:** sigue siendo lectura de store + estado local, **sin `fetch`**.

## Verificación (repite mi prueba)
1. `localStorage.clear()` + recarga. **Elige el chip "Datos / CDO"**, luego **abre el chatbot** → debe saludar *"Veo que estás explorando como CDO / Chief Data Officer en Finanzas y Banca…"* y arrancar en el paso **problema** (no preguntar sector). 
2. Repite con **"Sector Público" (CIO)** y **"Finanzas / CFO"** → saludo heredado correcto por rol/sector.
3. **Sin chip** (contexto vacío) → saludo genérico *"¿A qué sector perteneces?"* (comportamiento actual).
4. Abrir → cerrar → reabrir → **no** se reinicia el saludo ni se pierde el avance.
5. Network del flujo guiado = **cero `POST /api/chat.php`**.
6. `npm run build` verde.

## Doc-sync
Actualiza `planes/ESTADO-SPECS.md`: hoy afirma la herencia del chip como "auditada" pero solo aplica al contexto persistido. Corrige la redacción para reflejar que la herencia funciona **al abrir el panel con el chip activo** (flujo intra-sesión), tras este fix.

---
**Nota:** Claude (Opus 4.8) reauditará exactamente los 5 pasos de arriba en navegador (chip elegido en sesión → saludo heredado, idempotencia al reabrir, y cero `chat.php`).
