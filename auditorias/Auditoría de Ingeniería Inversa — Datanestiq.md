# Auditoría de Ingeniería Inversa — Datanestiq

Análisis exhaustivo de lo implementado vs. lo especificado, con plan de acción para formalizar en SDD.

---

## 1. Estado de las Correcciones Fase 0 (prompt-antigravity-fase0-correcciones-fase1.md)

### BLOQUE 1: Correcciones UX/UI en `index.html`

| # | Corrección | Estado | Evidencia |
|---|---|---|---|
| 1.1 | Botón flotante FAB del chatbot | ✅ Implementado | Líneas 258-266: `#chatbot-fab` con `fixed bottom-6 right-6 z-50` |
| 1.2 | Eliminar `<br>` frágil del H1 | ✅ Implementado | Línea 84: H1 limpio con `max-w-4xl` sin `<br>` |
| 1.3 | `color-scheme: dark` en `<head>` | ✅ Implementado | Línea 7: `<meta name="color-scheme" content="dark">` |
| 1.4 | Social Proof: badges texto en vez de íconos | ✅ Implementado | Líneas 109-113: badges tipo pill con sectores |
| 1.5 | Sección Metodología con 3 pasos | ✅ Implementado | Líneas 192-208: Diagnóstico → Diseño → Implementación ágil |
| 1.6 | CTA final: "Iniciar mi diagnóstico gratuito" | ✅ Implementado | Línea 211: texto correcto |
| 1.7 | Frase metodología actualizada | ✅ Implementado | Línea 190: "Implementamos en semanas porque no partimos de cero..." |

### BLOQUE 2: Correcciones en `app.js`

| # | Corrección | Estado | Evidencia |
|---|---|---|---|
| 2.1 | Coordinar FAB con apertura/cierre del chat | ✅ Implementado | Líneas 12-30: `toggleChatbot()` oculta/muestra FAB |
| 2.2 | `renderChatbotUI` acumula mensajes (no reemplaza) | ✅ Implementado | Líneas 33-40: `appendMessage()` usa `appendChild` |
| 2.3 | `collectLead()` con validación | ✅ Implementado | Líneas 170-191: valida nombre y email |
| 2.4 | `submitLead()` preparada para webhook | ✅ Implementado | Líneas 193-226: pero usa `YOUR_FORM_ID` placeholder |
| 2.5 | Mensajes de solución personalizados por sector | ✅ Implementado | Líneas 85-106: `getSolutionMessage()` con 4 sectores × 2 problemas |

### BLOQUE 3: Preparación WordPress

| # | Corrección | Estado | Detalle |
|---|---|---|---|
| 3.1 | Migrar Tailwind CDN a CLI | ❌ No implementado | Línea 14 sigue usando `cdn.tailwindcss.com` |
| 3.2 | Estructura del tema WordPress | ❌ No implementado | No existe `wp-content/themes/datanestiq-theme/` |
| 3.3 | `functions.php` recomendado | ❌ No implementado | No se creó el tema aún |
| 3.4 | Checklist pre-Fase 1 completo | 🟡 Parcial | Items de Fase 0 completos; items de Fase 1 pendientes |

---

## 2. Estado de los 8 Entregables del Prompt Maestro

| # | Entregable | Estado | Ubicación / Notas |
|---|---|---|---|
| 1 | **Diagnóstico del sitio actual** | ✅ Hecho | Documentado en los planes estratégicos |
| 2 | **Nueva estrategia de posicionamiento** | ✅ Hecho | En ambos planes: propuesta de valor, narrativa, audiencias, pilares, IA |
| 3 | **Nueva arquitectura del sitio** | ✅ Hecho | Mapa de páginas, flujo de navegación, embudo de conversión |
| 4 | **Copy premium completo** | ✅ Hecho | Hero, servicios, industrias, metodología, CTA — implementado en HTML |
| 5 | **Dirección visual premium** | ✅ Hecho | Dark mode, Inter, glassmorphism, paleta definida |
| 6 | **Propuesta funcionalidades IA en la web** | 🟡 Parcial | Chatbot implementado; formulario inteligente y demo copiloto NO |
| 7 | **Especificación técnica** | 🟡 Parcial | Arquitectura frontend definida; responsive, accesibilidad, performance sin validar |
| 8 | **Versión inicial implementable** | 🟡 Parcial | Home prototipo OK; chatbot funcional; tema WordPress NO |

---

## 3. Análisis de Brechas del Chatbot "AI Concierge"

### Requisitos del chatbot (prompt maestro) vs. implementación

| Requisito | Estado | Detalle |
|---|---|---|
| Se ve premium y moderno | ✅ | Glassmorphism, dark, integrado con la UI |
| Integrado visualmente con la web | ✅ | Misma paleta, tipografía, componentes |
| Ayuda a convertir visitas en conversaciones | ✅ | Flujo: sector → problema → solución → lead |
| Responde dudas sobre servicios | 🟡 | Solo responde sobre 4 sectores × 2 problemas |
| Guía hacia contacto/demo/diagnóstico | ✅ | Finaliza con formulario de lead |
| No parece widget genérico | ✅ | UI custom, no plugin tercero |
| **Rutas diferenciadas por sector** | ✅ | 4 sectores: Gobierno, Salud, Finanzas, Retail |
| **Datos a capturar** | 🟡 | Solo captura nombre + email. Falta: organización, sector (auto), urgencia |
| **Modo "Otro" (genérico B2B)** | ❌ | El plan multi-industria incluía un 5to flujo genérico. No existe |
| **Formulario dinámico adaptativo** | ❌ | Microexperiencia propuesta pero no implementada |
| **Demo de copiloto de datos** | ❌ | Microexperiencia propuesta pero no implementada |
| **Recomendador de servicios por perfil** | ❌ | Widget interactivo propuesto pero no implementado |
| **Endpoint real de webhook** | ❌ | `YOUR_FORM_ID` sigue como placeholder |

---

## 4. Brechas de Diseño / UX / Motion

### Lo que falta según el prompt maestro y los prompts de Claude Code (imagen)

| Elemento | Estado | Impacto | Inspirado en prompt Claude Code |
|---|---|---|---|
| **Scroll animations** | ❌ No hay | Los elementos aparecen estáticos, sin reveal | #2 Motion Design |
| **Micro-interacciones** | 🟡 Solo hover en cards | Faltan transiciones de página, loading states | #5 Motion Design |
| **Parallax / efectos cinematográficos** | ❌ No hay | Hero estático sin profundidad visual | #1 Cinematic CSS |
| **Animación del grid de fondo** | ❌ Estático | El grid pattern no se mueve ni reacciona | #1 Cinematic CSS |
| **Counter animations** (números/métricas) | ❌ No hay | Oportunidad perdida en social proof | #6 Luxury Landing |
| **Cursor trail / particle effects** | ❌ No hay | No necesario, pero sería diferenciador sutil | #5 Motion Design |
| **Loading skeleton / shimmer** | ❌ No hay | El chatbot no muestra typing indicator realista | #2 Motion Design |
| **Transiciones entre secciones** | ❌ No hay | Transiciones abruptas al hacer scroll | #4 FLOWSTATE |
| **Meta description** | ❌ Falta | Sin `<meta name="description">` | #3 Architecture |
| **Datos estructurados** (JSON-LD) | ❌ Falta | Sin schema.org para SEO | #7 Performance/UX |
| **Lazy loading de assets** | ❌ No hay | Todo carga de golpe | #7 Performance/UX |
| **Performance / Web Vitals** | ❌ Sin validar | CDN de Tailwind genera ~3MB de CSS | #7 Performance/UX |
| **Accesibilidad ARIA** | 🟡 Mínima | Solo el FAB tiene `aria-label`; el resto no | #3 Architecture |
| **Footer completo** | 🟡 Mínimo | Solo `© 2026 Datanestiq`. Sin links, redes, legal | #6 Luxury Landing |

---

## 5. Brechas de WordPress (Fase 1)

| Elemento | Estado |
|---|---|
| Tema WordPress `datanestiq-theme` | ❌ No creado |
| `style.css` con metadata del tema | ❌ |
| `functions.php` con enqueues | ❌ |
| `front-page.php` | ❌ |
| `header.php` / `footer.php` | ❌ |
| Template parts (hero, soluciones, etc.) | ❌ |
| Tailwind CLI compilado (no CDN) | ❌ |
| Shortcode del chatbot | ❌ |
| Reemplazo de URLs (producción → local) | ❌ Sin verificar |
| WP-CLI instalado | ❌ Sin verificar |

---

## 6. Elementos Rescatables de los Prompts de Claude Code (Imagen)

De la imagen compartida, estos prompts contienen técnicas directamente aplicables:

### Alta prioridad (mejoran significativamente Datanestiq)

| Prompt | Técnicas a incorporar |
|---|---|
| **#1 Cinematic CSS** | Parallax sutil en hero, tipografía con profundidad, transiciones cinematográficas, backdrop-filter, accesibilidad |
| **#2 Motion Design** | `IntersectionObserver` para reveal en scroll, efectos hover premium, transiciones de estado del chatbot |
| **#4 FLOWSTATE** | Interacciones ultra-premium: smooth scroll, micro-animations CSS-only, feedback visual en CTAs |
| **#6 Luxury Landing Page** | Estructura completa de landing premium: hero con video/particles, testimonials, pricing, FAQ, trust signals |
| **#7 Performance & UX/UI** | Web Vitals optimization, lazy loading, SEO completo, datos estructurados |

### Prioridad media (mejoras de diferenciación)

| Prompt | Técnicas a incorporar |
|---|---|
| **#3 Checkbox Architecture** | Blueprint técnico completo: SEO, responsive audit, deployment checklist |
| **#5 Motion Design (avanzado)** | Particle systems, cursor-aware animations (usarlos con moderación) |
| **#8 UI/UX Design Expert** | Sistema de diseño completo, component library, design tokens |

---

## 7. Resumen Ejecutivo de Brechas

```
┌──────────────────────────┬──────┬──────┬──────┐
│ Área                     │  ✅  │  🟡  │  ❌  │
├──────────────────────────┼──────┼──────┼──────┤
│ Correcciones Fase 0 (14) │  11  │   1  │   3  │
│ Entregables maestro (8)  │   5  │   3  │   0  │
│ Chatbot requisitos (13)  │   6  │   3  │   4  │
│ Diseño / Motion (14)     │   0  │   2  │  12  │
│ WordPress / Fase 1 (10)  │   0  │   0  │  10  │
├──────────────────────────┼──────┼──────┼──────┤
│ TOTAL (59)               │  22  │   9  │  29  │
└──────────────────────────┴──────┴──────┴──────┘
```

> [!IMPORTANT]
> **37% completado, 15% parcial, 49% pendiente.**
> La Fase 0 del prototipo está bien ejecutada (HTML/chatbot/copy), pero falta casi todo lo relacionado con: motion/animaciones premium, microexperiencias de IA adicionales, SEO/performance, y el portado completo a WordPress.

---

## 8. Plan de Acción Propuesto (SDD)

Propongo crear **3 specs formales** en el sistema SDD para cubrir todo el trabajo pendiente:

### Spec 001: Elevación Premium del Prototipo
- Scroll animations con IntersectionObserver
- Micro-interacciones y hover effects premium  
- Parallax sutil en hero
- Typing indicator en chatbot
- Footer completo
- SEO (meta description, datos estructurados)
- Accesibilidad ARIA completa
- Performance (lazy loading, optimización)
- 5to flujo genérico B2B en chatbot
- Campos adicionales de lead (organización, urgencia)

### Spec 002: Microexperiencias de IA
- Formulario de diagnóstico dinámico/adaptativo
- Recomendador de servicios por perfil
- Demo simulada de copiloto de datos

### Spec 003: Portado a WordPress (Fase 1)
- Migración de Tailwind CDN a CLI
- Creación del tema `datanestiq-theme`
- Template parts para cada sección
- `functions.php` con enqueues y shortcodes
- Integración del chatbot vía shortcode
- Configuración WP local y deploy a IONOS

> [!TIP]
> ¿Apruebas este plan de 3 specs? Una vez aprobado, ejecutaré el flujo SDD completo:
> `speckit-specify → speckit-plan → speckit-tasks → speckit-implement` para cada una.
