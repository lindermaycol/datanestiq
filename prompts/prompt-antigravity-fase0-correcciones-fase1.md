# Prompt para Antigravity: Correcciones post-auditoría Fase 0 → preparación para Fase 1 (WordPress)

Actúa como un Senior Frontend Engineer, WordPress Theme Developer y especialista en UX/UI y conversión.

Acabas de completar la Fase 0 del prototipo estático de **Datanestiq** (consultora multi-industria de AI & Data), ubicado en:

```
c:\xampp\htdocs\datanestiq\prototype\
```

Archivos involucrados:
- `prototype/index.html` — Home premium con Tailwind CSS
- `prototype/app.js` — Lógica del chatbot "Datanestiq AI Concierge"
- `prototype/styles.css` — Overrides mínimos de CSS

El repositorio está en: `git@github.com:lindermaycol/datanestiq.git`

---

## Contexto de la auditoría

He auditado el código y he identificado **correcciones prioritarias** que debes aplicar antes de iniciar la Fase 1 (portado a WordPress). A continuación te detallo cada corrección con precisión quirúrgica. Aplícalas todas en el orden indicado.

> ⚠️ IMPORTANTE: La entidad reguladora de contrataciones públicas en Perú se llama **OECE** (Organismo Especializado de Contrataciones del Estado). Este nombre es correcto y ya está bien escrito en el código. No lo cambies. No lo confundas con OSCE (nombre anterior, ya en desuso). Cada vez que el código mencione OECE, es correcto.

---

## BLOQUE 1: Correcciones de UX/UI en `index.html`

### 1.1 — Agregar botón flotante del chatbot (crítico para mobile)

El chatbot actualmente no tiene botón flotante visible cuando está cerrado. En mobile es inaccesible. Agrega un botón circular fijo en la esquina inferior derecha, siempre visible, que active el chatbot:

```html
<!-- Botón flotante del chatbot (visible siempre, especialmente en mobile) -->
<button 
  onclick="toggleChatbot()" 
  id="chatbot-fab"
  class="fixed bottom-6 right-6 z-50 w-14 h-14 rounded-full bg-brand hover:bg-brandHover shadow-[0_0_30px_-5px_rgba(37,99,235,0.6)] flex items-center justify-center transition-all"
  aria-label="Abrir asistente de diagnóstico"
>
  <i class="ph ph-robot text-white text-2xl"></i>
</button>
```

Lógica adicional: cuando el chatbot esté abierto, oculta el FAB (`#chatbot-fab`). Cuando se cierre, muéstralo de nuevo. Coordina esto dentro de la función `toggleChatbot()` en `app.js`.

### 1.2 — Corregir el H1 con quiebre manual frágil

Elimina el `<br class="hidden md:block">` dentro del H1, que es frágil en mobile. Sustitúyelo por control de ancho con `max-w`:

```html
<!-- ANTES (frágil): -->
<h1 class="...">
  Inteligencia aplicada para <br class="hidden md:block" />
  <span ...>decisiones claras</span> y resultados medibles.
</h1>

<!-- DESPUÉS (robusto): -->
<h1 class="text-5xl md:text-7xl font-bold tracking-tight mb-8 leading-tight max-w-4xl mx-auto">
  Inteligencia aplicada para <span class="text-transparent bg-clip-text bg-gradient-to-r from-white to-gray-500">decisiones claras</span> y resultados medibles.
</h1>
```

### 1.3 — Agregar `color-scheme: dark` al `<head>`

Para evitar flashes de color al cargar en navegadores que soportan dark mode nativo, agrega dentro del `<head>`:

```html
<meta name="color-scheme" content="dark">
```

### 1.4 — Reemplazar íconos de Social Proof por placeholders de texto

Los íconos Phosphor en la sección de Social Proof no comunican autoridad. Mientras no haya logos reales de clientes o partners, reemplázalos por etiquetas de texto tipo "badge":

```html
<!-- Reemplaza los <i class="ph ph-..."> de la sección Social Proof por: -->
<div class="flex flex-wrap justify-center items-center gap-6 md:gap-10">
  <span class="text-xs font-semibold text-gray-500 tracking-widest uppercase border border-white/10 px-4 py-2 rounded-full">Sector Público</span>
  <span class="text-xs font-semibold text-gray-500 tracking-widest uppercase border border-white/10 px-4 py-2 rounded-full">Salud</span>
  <span class="text-xs font-semibold text-gray-500 tracking-widest uppercase border border-white/10 px-4 py-2 rounded-full">Finanzas y Banca</span>
  <span class="text-xs font-semibold text-gray-500 tracking-widest uppercase border border-white/10 px-4 py-2 rounded-full">Retail y B2B</span>
  <span class="text-xs font-semibold text-gray-500 tracking-widest uppercase border border-white/10 px-4 py-2 rounded-full">LATAM</span>
</div>
```

### 1.5 — Agregar sección de Metodología real entre Industrias y CTA final

La sección `#metodologia` actualmente solo tiene el CTA. Agrega antes del CTA un bloque de 3–4 pasos que muestre el proceso de trabajo:

```html
<!-- Bloque de metodología / proceso (agregar ANTES del CTA final en la sección #metodologia) -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-20">
  <div class="text-center">
    <div class="w-12 h-12 rounded-full bg-brand/10 border border-brand/20 flex items-center justify-center text-brandCyan font-bold text-lg mx-auto mb-4">01</div>
    <h4 class="font-semibold mb-2">Diagnóstico</h4>
    <p class="text-sm text-gray-400">Evaluamos tu infraestructura, procesos y madurez de datos en una sesión técnica sin costo.</p>
  </div>
  <div class="text-center">
    <div class="w-12 h-12 rounded-full bg-brand/10 border border-brand/20 flex items-center justify-center text-brandCyan font-bold text-lg mx-auto mb-4">02</div>
    <h4 class="font-semibold mb-2">Diseño de solución</h4>
    <p class="text-sm text-gray-400">Proponemos una hoja de ruta técnica con arquitectura, herramientas y KPIs de impacto medible.</p>
  </div>
  <div class="text-center">
    <div class="w-12 h-12 rounded-full bg-brand/10 border border-brand/20 flex items-center justify-center text-brandCyan font-bold text-lg mx-auto mb-4">03</div>
    <h4 class="font-semibold mb-2">Implementación ágil</h4>
    <p class="text-sm text-gray-400">Ejecutamos en sprints cortos con entregas visibles desde la primera semana. Sin disrupciones.</p>
  </div>
</div>
```

### 1.6 — Ajustar el CTA final

Reemplaza el copy del CTA final por uno más universal (válido para todos los sectores, no solo los técnicos):

```html
<!-- ANTES: -->
Evaluar mi infraestructura de datos

<!-- DESPUÉS: -->
Iniciar mi diagnóstico gratuito
```

### 1.7 — Ajustar la frase de la sección metodología

Reemplaza:
```
Del caos de los datos a la eficiencia operativa en semanas, no meses.
```
Por:
```
Implementamos en semanas porque no partimos de cero: nuestro método está calibrado para entornos regulados y complejos.
```

---

## BLOQUE 2: Correcciones críticas en `app.js`

### 2.1 — Coordinar el FAB con la apertura/cierre del chatbot

Dentro de `toggleChatbot()`, agrega lógica para ocultar el botón flotante cuando el chat está abierto y mostrarlo cuando está cerrado:

```javascript
function toggleChatbot() {
    const chatbot = document.getElementById('ai-concierge');
    const fab = document.getElementById('chatbot-fab');
    chatbotState.isOpen = !chatbotState.isOpen;

    if (chatbotState.isOpen) {
        chatbot.classList.remove('hidden');
        fab.classList.add('hidden');
        setTimeout(() => {
            chatbot.classList.remove('opacity-0', 'translate-y-4', 'scale-95');
        }, 10);
    } else {
        chatbot.classList.add('opacity-0', 'translate-y-4', 'scale-95');
        fab.classList.remove('hidden');
        setTimeout(() => {
            chatbot.classList.add('hidden');
        }, 300);
    }
}
```

### 2.2 — Corregir `renderChatbotUI` para acumular mensajes (no reemplazar)

Actualmente `renderChatbotUI()` hace `innerHTML = ''`, borrando el historial. Cambia la lógica para que **agregue** mensajes al hilo en lugar de reemplazar todo el contenido:

```javascript
function appendMessage(html) {
    const contentBody = document.getElementById('chat-content');
    const div = document.createElement('div');
    div.innerHTML = html;
    contentBody.appendChild(div);
    // Auto-scroll al último mensaje
    contentBody.scrollTop = contentBody.scrollHeight;
}
```

Refactoriza `renderChatbotUI()` para que use `appendMessage()` en lugar de reemplazar `innerHTML` directamente. Al cambiar de paso, limpia SOLO los botones anteriores (no el historial de mensajes del bot).

### 2.3 — Poblar `leadData` en el estado y agregar función `collectLead()`

El objeto `chatbotState.leadData` existe pero nunca se usa. Agrega esta función y llámala antes de cualquier submit:

```javascript
function collectLead() {
    const nombre = document.getElementById('lead-nombre')?.value?.trim();
    const email = document.getElementById('lead-email')?.value?.trim();

    if (!nombre || nombre.length < 2) {
        alert('Por favor ingresa tu nombre.');
        return false;
    }
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        alert('Por favor ingresa un correo electrónico válido.');
        return false;
    }

    chatbotState.leadData = {
        nombre,
        email,
        sector: chatbotState.sector,
        problema: chatbotState.problem,
        timestamp: new Date().toISOString()
    };
    return true;
}
```

Agrega `id="lead-nombre"` e `id="lead-email"` a los inputs del formulario en el paso `solution`.

### 2.4 — Agregar función de submit real (preparada para webhook)

Agrega una función `submitLead()` que conecte con un endpoint externo (Formspree, Make, n8n, o el que uses):

```javascript
async function submitLead() {
    if (!collectLead()) return;

    const btn = document.getElementById('btn-submit-lead');
    btn.textContent = 'Enviando...';
    btn.disabled = true;

    try {
        // Reemplaza la URL por tu endpoint real (Formspree, Make, n8n, etc.)
        const response = await fetch('https://formspree.io/f/YOUR_FORM_ID', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(chatbotState.leadData)
        });

        if (response.ok) {
            appendMessage(`
                <div class="flex gap-3 mt-4">
                    <div class="w-8 h-8 rounded bg-brand/20 flex-shrink-0 flex items-center justify-center text-brandCyan">
                        <i class="ph ph-robot"></i>
                    </div>
                    <div class="bg-white/5 p-3 rounded-2xl rounded-tl-sm text-sm border border-white/10">
                        ¡Perfecto! Hemos recibido tu solicitud. Un arquitecto de datos de Datanestiq se pondrá en contacto contigo en las próximas 24 horas. 🎯
                    </div>
                </div>
            `);
        } else {
            throw new Error('Error en el servidor');
        }
    } catch (error) {
        btn.textContent = 'Agendar Diagnóstico Gratuito';
        btn.disabled = false;
        alert('Hubo un problema al enviar tu solicitud. Por favor intenta nuevamente.');
    }
}
```

En el HTML del paso `solution`, agrega `id="btn-submit-lead"` al botón y cambia su `onclick` a `submitLead()`.

### 2.5 — Personalizar el mensaje de solución por sector y problema

El mensaje de solución actual es idéntico para todos los sectores. Reemplázalo por mensajes contextualizados:

```javascript
function getSolutionMessage(sector, problem) {
    const messages = {
        'Gobierno': {
            'expedientes': 'Podemos automatizar la revisión y trazabilidad de expedientes con IA, reduciendo tiempos operativos hasta un 60% y garantizando cumplimiento con estándares OECE.',
            'transparencia': 'Implementamos dashboards ejecutivos en tiempo real que generan reportes de indicadores de gestión y transparencia de forma automática.'
        },
        'Salud': {
            'listas': 'Desarrollamos modelos predictivos que optimizan la asignación de camas y listas de espera, mejorando tiempos de atención y reduciendo riesgo operativo.',
            'clinicas': 'Centralizamos historias clínicas dispersas en una plataforma unificada con generación automática de reportes clínicos y alertas tempranas.'
        },
        'Finanzas': {
            'riesgo': 'Desplegamos modelos de scoring y detección de fraude en tiempo real que mejoran la precisión de evaluación crediticia y reducen pérdidas operativas.',
            'creditos': 'Automatizamos el flujo de evaluación de solicitudes de crédito con RPA e IA, pasando de días a horas en la toma de decisión.'
        },
        'Retail': {
            'inventarios': 'Implementamos modelos de forecasting con IA que optimizan inventarios, reducen quiebres de stock y aumentan el margen operativo.',
            'reportes': 'Centralizamos tus silos de datos en una sola fuente de verdad con dashboards automáticos y alertas en tiempo real para equipos comerciales.'
        }
    };
    return messages[sector]?.[problem] ?? 
        'Podemos diseñar una solución a medida para ese desafío. Nuestro equipo técnico te propone una hoja de ruta con resultados medibles desde la primera semana.';
}
```

Usa `getSolutionMessage(chatbotState.sector, chatbotState.problem)` para renderizar el mensaje en el paso `solution`.

---

## BLOQUE 3: Preparación para portado a WordPress (Fase 1)

### 3.1 — Migrar Tailwind CDN a Tailwind CLI

El CDN de Tailwind genera ~3MB de CSS en runtime y no es apto para producción. Antes de iniciar el tema WordPress:

1. Instala Tailwind CLI:
```bash
npm install -D tailwindcss
npx tailwindcss init
```

2. Configura `tailwind.config.js` con los mismos valores custom del `<script>` inline en `index.html` (dark, darker, brand, brandHover, brandCyan, accent).

3. Crea un archivo `src/input.css` con:
```css
@tailwind base;
@tailwind components;
@tailwind utilities;
```

4. Compila a `assets/css/output.css`:
```bash
npx tailwindcss -i ./src/input.css -o ./assets/css/output.css --minify
```

5. Reemplaza el `<script src="https://cdn.tailwindcss.com">` por `<link href="assets/css/output.css" rel="stylesheet">`.

### 3.2 — Estructura del tema WordPress a crear

Cuando empieces la Fase 1, crea el tema con esta estructura exacta en `wp-content/themes/datanestiq-theme/`:

```
datanestiq-theme/
├── style.css                    ← metadata del tema (Theme Name, Version, etc.)
├── functions.php                ← wp_enqueue_style/script, shortcodes, hooks
├── front-page.php               ← home principal (incluye template parts)
├── header.php                   ← navbar fija
├── footer.php                   ← footer + scripts al final del body
├── index.php                    ← fallback requerido por WP
├── template-parts/
│   ├── hero.php
│   ├── social-proof.php
│   ├── soluciones.php
│   ├── industrias.php
│   ├── metodologia.php
│   ├── cta-final.php
│   └── chatbot.php
└── assets/
    ├── css/
    │   ├── output.css           ← Tailwind compilado
    │   └── styles.css           ← overrides custom
    └── js/
        └── app.js               ← chatbot logic (desacoplado de WP)
```

### 3.3 — functions.php mínimo recomendado

```php
<?php
function datanestiq_enqueue_assets() {
    // Fuente Inter (local o desde Google)
    wp_enqueue_style('datanestiq-fonts', 
        'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap', 
        [], null);

    // Tailwind compilado
    wp_enqueue_style('datanestiq-tailwind', 
        get_template_directory_uri() . '/assets/css/output.css', 
        [], '1.0.0');

    // CSS overrides
    wp_enqueue_style('datanestiq-style', 
        get_template_directory_uri() . '/assets/css/styles.css', 
        ['datanestiq-tailwind'], '1.0.0');

    // Phosphor Icons
    wp_enqueue_script('phosphor-icons', 
        'https://unpkg.com/@phosphor-icons/web', 
        [], null, true);

    // Chatbot JS (defer, sin bloquear render)
    wp_enqueue_script('datanestiq-app', 
        get_template_directory_uri() . '/assets/js/app.js', 
        [], '1.0.0', true);
}
add_action('wp_enqueue_scripts', 'datanestiq_enqueue_assets');

// Shortcode para el chatbot (úsalo en cualquier página con [datanestiq_chatbot])
function datanestiq_chatbot_shortcode() {
    ob_start();
    get_template_part('template-parts/chatbot');
    return ob_get_clean();
}
add_shortcode('datanestiq_chatbot', 'datanestiq_chatbot_shortcode');
```

### 3.4 — Checklist pre-Fase 1

Antes de empezar a portarlo a WordPress, confirma que:

- [ ] El botón flotante FAB está visible en mobile y coordina correctamente con el chat
- [ ] El H1 no tiene `<br>` manuales frágiles
- [ ] `color-scheme: dark` está en el `<head>`
- [ ] La sección Social Proof usa badges de texto en lugar de íconos
- [ ] La sección Metodología tiene los 3 pasos definidos
- [ ] El CTA final dice "Iniciar mi diagnóstico gratuito"
- [ ] La frase de metodología está actualizada
- [ ] `renderChatbotUI` acumula mensajes (no reemplaza)
- [ ] `leadData` se puebla con `collectLead()`
- [ ] El formulario tiene validación mínima
- [ ] `submitLead()` apunta a un endpoint real o tiene placeholder listo para conectar
- [ ] Los mensajes de solución son diferentes por sector y problema
- [ ] Tailwind CDN está reemplazado por CSS compilado
- [ ] La estructura del tema WordPress está creada

---

## Instrucciones de ejecución

1. Aplica todas las correcciones del BLOQUE 1 sobre `index.html`.
2. Aplica todas las correcciones del BLOQUE 2 sobre `app.js`.
3. Una vez confirmados ambos bloques y probados en el prototipo estático (`c:\xampp\htdocs\datanestiq\prototype\`), avísame y procedemos con el BLOQUE 3 (Fase 1 WordPress).
4. Haz commit de los cambios al repositorio con el mensaje: `fix: post-audit corrections Fase 0 → ready for Fase 1`.

## Forma de respuesta

- Aplica cada corrección en orden.
- Muéstrame el código resultante final de `index.html` y `app.js` una vez aplicados todos los cambios.
- Confirma el checklist pre-Fase 1 cuando hayas terminado.
- NO hagas cambios que no estén listados en este prompt.
