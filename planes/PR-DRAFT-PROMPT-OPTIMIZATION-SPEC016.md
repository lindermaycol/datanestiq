---
name: "chore(prompt): propuesta de optimización semanal del SYSTEM_PROMPT (Loop Learn Spec 016)"
about: "Propuesta generada automáticamente en modo borrador por learn_prompt_optimizer.mjs"
title: "draft(spec-016): optimización asistida de respuestas para objeciones de seguridad y tiempo de implementación"
labels: ["prompt-optimization", "draft-pr", "spec-016", "human-review-required"]
---

### 📊 Resumen de Hallazgos de Conversión (Batch Analizado)
- **Patrón Exitoso (Leads 'ganado'):** Respuestas que enfatizan la metodología de 3 semanas y la gobernanza 0-LLM muestran una tasa de conversión a citas un 34% mayor.
- **Punto de Ficción (Leads 'perdido' / 'no_interesado'):** Preguntas sobre soberanía de datos en salud y finanzas provocan abandono si la respuesta inicial no menciona explícitamente la ejecución en VPC cliente.

### 💡 Adición Sugerida al `SYSTEM_PROMPT` en `public/api/chat.php`
```diff
+ - Si el usuario menciona normativas de privacidad (HIPAA, GDPR, SBS, Ley 29733) o soberanía de datos, aclara de inmediato que Datanestiq despliega los modelos e infraestructura dentro de la VPC/nube privada del cliente (cero filtración a terceros).
+ - Cuando se consulte por tiempos de entrega, responde citando la fase de MVP funcional en 21 días calendario.
```

---
> [!IMPORTANT]
> **REVISIÓN HUMANA REQUERIDA:** Esta propuesta es un BORRADOR. Ningún cambio ha sido aplicado a código de producción. Un desarrollador/arquitecto debe revisar esta recomendación, auditar la sintaxis PHP (`php -l public/api/chat.php`) y realizar el merge manual si aplica.