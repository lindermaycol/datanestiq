// Datanestiq AI Concierge Logic

const chatbotState = {
    isOpen: false,
    step: 'intro',
    sector: null,
    problem: null,
    leadData: {}
};

// Toggle Chatbot Visibility and FAB
function toggleChatbot() {
    const chatbot = document.getElementById('ai-concierge');
    const fab = document.getElementById('chatbot-fab');
    chatbotState.isOpen = !chatbotState.isOpen;
    
    if (chatbotState.isOpen) {
        chatbot.classList.remove('hidden');
        if (fab) fab.classList.add('hidden');
        setTimeout(() => {
            chatbot.classList.remove('opacity-0', 'translate-y-4', 'scale-95');
        }, 10);
    } else {
        chatbot.classList.add('opacity-0', 'translate-y-4', 'scale-95');
        if (fab) fab.classList.remove('hidden');
        setTimeout(() => {
            chatbot.classList.add('hidden');
        }, 300);
    }
}

// Helper para agregar mensajes sin borrar el historial
function appendMessage(html) {
    const contentBody = document.getElementById('chat-content');
    const div = document.createElement('div');
    div.innerHTML = html;
    contentBody.appendChild(div);
    // Auto-scroll al último mensaje
    contentBody.scrollTop = contentBody.scrollHeight;
}

// Remover opciones anteriores
function clearOptions() {
    const optionsContainer = document.getElementById('chatbot-options');
    if (optionsContainer) {
        optionsContainer.remove();
    }
}

// Proceed to specific sector route
function selectSector(sector) {
    clearOptions();
    chatbotState.sector = sector;
    chatbotState.step = 'problem';
    
    // Append User Choice
    appendMessage(`
        <div class="flex justify-end mt-2">
            <div class="bg-brand/20 p-3 rounded-2xl rounded-tr-sm text-sm border border-brand/30 text-white">
                ${sector}
            </div>
        </div>
    `);

    setTimeout(renderChatbotUI, 500); // delay for realism
}

function selectProblem(problem, label) {
    clearOptions();
    chatbotState.problem = problem;
    chatbotState.step = 'solution';

    // Append User Choice
    appendMessage(`
        <div class="flex justify-end mt-2">
            <div class="bg-brand/20 p-3 rounded-2xl rounded-tr-sm text-sm border border-brand/30 text-white">
                ${label}
            </div>
        </div>
    `);

    setTimeout(renderChatbotUI, 800); // delay for realism
}

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

function renderChatbotUI() {
    if (chatbotState.step === 'problem') {
        let problemsHTML = '';
        if (chatbotState.sector === 'Gobierno' || chatbotState.sector === 'Sector Público y Gobierno') {
            chatbotState.sector = 'Gobierno';
            problemsHTML = `
                <button onclick="selectProblem('expedientes', 'Revisión lenta de expedientes')" class="w-full text-left p-3 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 text-sm mb-2 transition-colors">Revisión lenta de expedientes o compras públicas</button>
                <button onclick="selectProblem('transparencia', 'Reportes e indicadores')" class="w-full text-left p-3 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 text-sm mb-2 transition-colors">Reportes e indicadores de gestión y transparencia</button>
            `;
        } else if (chatbotState.sector === 'Salud') {
            problemsHTML = `
                <button onclick="selectProblem('listas', 'Listas de espera y camas')" class="w-full text-left p-3 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 text-sm mb-2 transition-colors">Gestión de listas de espera y ocupación de camas</button>
                <button onclick="selectProblem('clinicas', 'Historias clínicas dispersas')" class="w-full text-left p-3 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 text-sm mb-2 transition-colors">Historias clínicas dispersas y reportes manuales</button>
            `;
        } else if (chatbotState.sector === 'Finanzas' || chatbotState.sector === 'Servicios Financieros y Banca') {
            chatbotState.sector = 'Finanzas';
            problemsHTML = `
                <button onclick="selectProblem('riesgo', 'Scoring y riesgo')" class="w-full text-left p-3 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 text-sm mb-2 transition-colors">Modelos de scoring, riesgo y prevención de fraude</button>
                <button onclick="selectProblem('creditos', 'Solicitudes de crédito')" class="w-full text-left p-3 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 text-sm mb-2 transition-colors">Evaluación manual de solicitudes de crédito</button>
            `;
        } else {
            chatbotState.sector = 'Retail';
            problemsHTML = `
                <button onclick="selectProblem('inventarios', 'Forecasting e inventarios')" class="w-full text-left p-3 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 text-sm mb-2 transition-colors">Problemas de forecasting e inventarios (Retail/B2B)</button>
                <button onclick="selectProblem('reportes', 'Generación de reportes lentos')" class="w-full text-left p-3 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 text-sm mb-2 transition-colors">Silos de datos y generación de reportes operativos lentos</button>
            `;
        }

        appendMessage(`
            <div class="flex gap-3 mt-4">
                <div class="w-8 h-8 rounded bg-brand/20 flex-shrink-0 flex items-center justify-center text-brandCyan"><i class="ph ph-robot"></i></div>
                <div class="bg-white/5 p-3 rounded-2xl rounded-tl-sm text-sm border border-white/10">
                    Entendido, trabajas en ${chatbotState.sector}. ¿Qué proceso operativo te genera más cuellos de botella actualmente?
                </div>
            </div>
            <div id="chatbot-options" class="flex flex-col mt-4">
                ${problemsHTML}
            </div>
        `);
    } else if (chatbotState.step === 'solution') {
        const solutionText = getSolutionMessage(chatbotState.sector, chatbotState.problem);
        
        appendMessage(`
            <div class="flex flex-col gap-4 mt-4">
                <div class="flex gap-3">
                    <div class="w-8 h-8 rounded bg-brand/20 flex-shrink-0 flex items-center justify-center text-brandCyan"><i class="ph ph-robot"></i></div>
                    <div class="bg-white/5 p-3 rounded-2xl rounded-tl-sm text-sm border border-white/10">
                        ${solutionText}<br><br>¿Te gustaría agendar un diagnóstico rápido con un arquitecto de datos?
                    </div>
                </div>
                <div id="chatbot-options" class="mt-2 flex flex-col gap-2">
                    <input type="text" id="lead-nombre" placeholder="Tu Nombre" class="w-full bg-darker border border-white/10 rounded-lg p-3 text-sm focus:border-brandCyan outline-none transition-colors">
                    <input type="email" id="lead-email" placeholder="Correo Institucional" class="w-full bg-darker border border-white/10 rounded-lg p-3 text-sm focus:border-brandCyan outline-none transition-colors">
                    <button id="btn-submit-lead" onclick="submitLead()" class="w-full py-3 rounded-lg bg-brand hover:bg-brandHover text-white text-sm font-medium transition-colors mt-2">
                        Agendar Diagnóstico Gratuito
                    </button>
                </div>
            </div>
        `);
    }
}

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

async function submitLead() {
    if (!collectLead()) return;

    const btn = document.getElementById('btn-submit-lead');
    btn.textContent = 'Enviando...';
    btn.disabled = true;

    try {
        // Mock webhook o Formspree
        const response = await fetch('https://formspree.io/f/YOUR_FORM_ID', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(chatbotState.leadData)
        });

        // Simulamos éxito incluso si falla el webhook por ser formspree mock
        clearOptions();
        
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
    } catch (error) {
        btn.textContent = 'Agendar Diagnóstico Gratuito';
        btn.disabled = false;
        alert('Hubo un problema al enviar tu solicitud. Por favor intenta nuevamente.');
    }
}

// Para la carga inicial (opciones de la intro en el html)
document.addEventListener("DOMContentLoaded", () => {
    // Add id to initial options so they can be cleared
    const initialOptions = document.querySelector('#chat-content .flex-col.gap-2');
    if (initialOptions) {
        initialOptions.id = 'chatbot-options';
    }
});
