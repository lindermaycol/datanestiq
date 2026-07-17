# Prompt: Auditoría UX del sitio Datanestiq — navegar interpretando distintos roles de cliente

> **Para:** una IA con capacidad de **navegar un sitio web en un navegador real** (tomar screenshots, hacer clic, llenar campos, leer la consola y la red).
> **Objetivo:** recorrer el sitio de **Datanestiq** poniéndote en la piel de **distintos roles del comité de compra B2B** (como un cliente real evaluando si contratar), y producir un **informe de auditoría UX** con aciertos, bugs y mejoras.

---

## 1. Contexto del sitio
**Datanestiq** es una consultora B2B de élite en **Ingeniería de Datos, Inteligencia Artificial y Sistemas Digitales**. El sitio es estático (Astro), con **microinteracciones de IA** (chatbot, buscador semántico, wizard de diagnóstico, demo de copiloto) y su contenido está impulsado por una **taxonomía** (pilares de servicio, sectores/industrias y personas/roles de compra).

Puntos clave que debes saber:
- El **chatbot "AI Concierge"** (botón flotante abajo a la derecha) tiene un **flujo guiado** (sector → rol → problema) que es **100% determinista y NO llama a ningún LLM** (personaliza desde datos locales). Solo el modo de **texto libre** llama a un backend.
- Las **páginas de sector** muestran datos ricos por industria: subsectores, KPIs con ROI estimado (marcado `[EST]`), regulaciones/compliance.
- El sitio es la fuente de verdad de una consultora; el contenido debe sentirse **on-brand, específico y creíble**, no genérico.

## 2. Cómo levantar el sitio (entorno local)
```bash
# En la raíz del proyecto:
npm install            # si es la primera vez
npm run build
npx astro preview --host --port 4321
# Abre: http://localhost:4321/
```
> Nota: usa `astro preview` (sirve el build). Si `astro dev` no es alcanzable por tu navegador, `preview --host` sí lo es.

## 3. Mapa del sitio (URLs y qué esperar)
| Ruta | Qué es | Qué revisar |
|---|---|---|
| `/` | Home (data-driven) | Hero, propuesta de valor, islas (buscador semántico, copiloto, wizard, soluciones por rol/industria), chatbot flotante |
| `/sectores/<slug>` | Landing por industria | subsectores, **KPIs con `[EST]`**, regulaciones/compliance, retos y soluciones, cross-links a pilares |
| `/soluciones/<slug>` | Landing por pilar/servicio | stack tecnológico, proof points, posicionamiento competitivo, madurez |
| `/blog` y sus posts | Artículos | contenido on-brand, coherente con la taxonomía |
| `/nosotros`, `/metodologia`, `/casos-roi` (o similares del menú) | Institucional | claridad, consistencia |

**Sectores** (slugs): `finanzas`, `salud`, `publico`, `retail`, `manufactura`, `logistica`, `educacion`, `mineria`, `seguros`, `telecomunicaciones`.
**Pilares/soluciones** (slugs): `ai-data-science`, `business-intelligence`, `data-engineering`, `estrategia-datos-ia`, `hiperautomatizacion`, `sistemas-digitales`.

## 4. Los roles que debes interpretar (comité de compra B2B)
Para cada escenario, **actúa como este rol**: adopta sus objetivos, sus dolores, y sobre todo **sus objeciones** — y evalúa si el sitio te convence a TI, en ese rol.

| Rol | Tipo de comprador | Le importa (criterios) | Objeciones típicas (debe rebatirlas el sitio) | Encaja en sectores |
|---|---|---|---|---|
| **CFO / Director Financiero** | Económico | Payback, TCO, impacto en EBITDA | "El ROI de datos es abstracto / a muy largo plazo"; "cambiar el ERP cuesta demasiado" | finanzas, seguros, minería |
| **CEO / Director General** | Económico | Ventaja competitiva, crecimiento de ingresos | "La IA es una moda"; "no somos una empresa tecnológica" | retail, finanzas, educación, logística |
| **CIO** | Técnico | Seguridad certificada, integración con legacy, TCO | "Ya tenemos contrato con Microsoft"; "nuestros datos son muy sensibles para la nube" | público, salud, seguros, educación |
| **CTO** | Técnico | Flexibilidad de arquitectura, seguridad, APIs | "Tenemos mucha deuda técnica"; "no quiero vendor lock-in" | manufactura, logística, telecom |
| **CDO / Chief Data Officer** | Técnico | Gobernanza escalable, adopción, romper silos | "Nuestra calidad de datos es muy baja para IA"; "falta cultura del dato" | retail, telecom, público, educación |
| **CISO** | Técnico | ISO 27001/SOC2, encriptación, despliegue on-prem/VPC | "La IA pública expone datos confidenciales"; "amplía la superficie de ataque" | finanzas, público, salud |
| **COO / Director de Operaciones** | Usuario | Eficiencia (OEE, tiempos), facilidad de uso, soporte | "Mi equipo se resistirá al cambio"; "no podemos parar la producción" | manufactura, salud, retail, logística |

## 5. Escenarios de navegación (ejecútalos como recorridos completos)
Para **cada rol**, haz un recorrido realista de "cliente evaluando":

**Recorrido tipo (repite por rol):**
1. **Aterriza en el Home** (`/`). Como este rol, ¿el mensaje principal te habla? ¿Entiendes en 5 segundos qué hace Datanestiq y por qué te conviene?
2. **Abre el chatbot** (botón flotante). Recorre el flujo guiado: elige **tu sector** → elige **tu rol** → elige un **problema**. Verifica:
   - ¿El chatbot te ofrece **tu rol** entre las opciones del sector? (debe, por la taxonomía).
   - ¿El mensaje que arma menciona **tus KPIs, regulaciones y criterios** reales del sector/rol? ¿Se siente personalizado o genérico?
   - **Técnico:** mientras haces clic en las opciones del flujo guiado, abre la pestaña de **Red (Network)**: **NO debe haber ninguna petición a `chat.php` ni a una API de IA**. Si aparece una, es un bug (el flujo guiado debe ser 0-LLM). Luego prueba el **modo texto libre** (escribe una pregunta): ahí sí puede llamar al backend.
3. **Visita tu página de sector** (`/sectores/<tu-sector>`). ¿Aparecen **subsectores**, **KPIs con `[EST]`** y **regulaciones/compliance** propias de tu industria (ej. finanzas → Basilea/PCI-DSS/SBS; salud → HIPAA/HL7-FHIR)? ¿Son creíbles y específicas?
4. **Explora una solución/pilar** relevante (`/soluciones/<slug>`). ¿El stack, los proof points y el posicionamiento **rebaten tus objeciones**? (ej. como CTO, ¿ves la promesa de "sin vendor lock-in"? como CISO, ¿ves despliegue on-prem/seguridad?).
5. **Lee un artículo del blog** relacionado con tu rol/sector. ¿Es on-brand, útil, sin relleno?
6. **Prueba las demás islas** del home: el **buscador semántico** (escribe una necesidad y mira si los resultados son relevantes), la **demo de copiloto**, el **wizard de diagnóstico** (recórrelo hasta el final), y **"Soluciones por Rol / por Industria"** (cambia de rol/industria y verifica que resalte lo correcto).
7. **Intenta el CTA principal** ("Auditoría Gratuita" / formularios). Si hay un formulario, **NO envíes datos reales**; si lo pruebas, usa datos claramente de prueba (ej. nombre "TEST QA") y evalúa la usabilidad, no generes un lead real.

Cubre **al menos 4-5 roles distintos** en sus sectores correspondientes (usa la tabla del §4), para tener variedad.

## 6. Qué evaluar (rúbrica)
Para cada recorrido, califica y comenta:
- **Relevancia/Personalización:** ¿el sitio (y el chatbot) habla el idioma de mi rol? ¿Menciona mis KPIs/regulaciones/objeciones? (1-5)
- **Claridad de propuesta de valor:** ¿entiendo qué ofrecen y por qué elegirlos? (1-5)
- **Manejo de objeciones:** ¿el contenido rebate mis dudas específicas? (1-5)
- **UX y navegación:** flujo, jerarquía, CTAs claros, coherencia visual. (1-5)
- **Responsive:** repite pasos clave en **móvil (≈375px)** y **desktop**; ¿se rompe algo?
- **Rendimiento percibido:** ¿carga rápido? ¿las islas hidratan bien?
- **Calidad de contenido:** on-brand, sin typos, sin lorem/placeholder, cifras coherentes (los `[EST]` deben estar marcados como estimación).
- **Confianza/credibilidad:** ¿parece una consultora seria?

## 7. Bugs y verificaciones técnicas a cazar
Reporta cualquiera de estos:
- **Errores de consola** (JS) en cada página (revísala).
- **Enlaces rotos / 404**, imágenes que no cargan, secciones vacías.
- **Layout roto** en móvil o desktop.
- **Chatbot:** que el flujo guiado **NO** dispare llamadas de red a IA (0-LLM); que ofrezca los roles correctos por sector; que el mensaje personalizado no tenga campos vacíos o "undefined".
- **Contenido faltante o "undefined"/placeholder** en cualquier página.
- **Draft leaking:** no debería existir `/pagina-muestra` ni páginas marcadas como borrador visibles en producción.
- **SEO técnico:** que las páginas tengan `<title>`/meta description, y breadcrumbs (JSON-LD `BreadcrumbList`).
- **Duplicados de ruta** (ej. que exista una `/home` además de `/`).

## 8. Formato del informe (entregable)
Estructura tu reporte así:

1. **Resumen ejecutivo** (5-8 líneas): impresión general del sitio como cliente, y los 3 hallazgos más importantes.
2. **Por rol** (una sección por cada rol interpretado): qué funcionó, qué falló, screenshots clave, y las 5 calificaciones de la rúbrica (§6) con 1 frase de justificación.
3. **Bugs consolidados** (tabla), priorizados:
   - `P0` (bloqueante/roto), `P1` (grave), `P2` (menor), `P3` (cosmético). Con: descripción, URL, pasos para reproducir, evidencia (screenshot/consola/red).
4. **Mejoras de UX/contenido** recomendadas (lista priorizada por impacto/esfuerzo).
5. **Aciertos** (qué está muy bien y conviene conservar).
6. **Evidencia:** adjunta screenshots numerados por recorrido.

## 9. Reglas
- **Solo navegar/leer.** No modifiques código, datos ni archivos del proyecto.
- **No generes leads reales:** si pruebas formularios, usa datos de prueba evidentes (p. ej. "TEST QA / no-reply@example.com") o solo evalúa la UX sin enviar.
- **Sé honesto y específico:** cita la URL y pega evidencia. No inventes hallazgos ni asumas que algo funciona sin verlo. Si algo no cargó, dilo.
- **Piensa como el rol**, no como un tester genérico: la pregunta central es *"¿este sitio me convencería a MÍ, en este puesto, de agendar una reunión con Datanestiq?"*.
