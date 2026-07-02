// Datanestiq AI Concierge Logic

const chatbotState = {
    isOpen: false,
    step: 'intro',
    sector: null,
    problem: null,
    leadData: {}
};

// Toggle Chatbot Visibility
function toggleChatbot() {
    const chatbot = document.getElementById('ai-concierge');
    chatbotState.isOpen = !chatbotState.isOpen;
    
    if (chatbotState.isOpen) {
        chatbot.classList.remove('hidden');
        setTimeout(() => {
            chatbot.classList.remove('opacity-0', 'translate-y-4', 'scale-95');
        }, 10);
    } else {
        chatbot.classList.add('opacity-0', 'translate-y-4', 'scale-95');
        setTimeout(() => {
            chatbot.classList.add('hidden');
        }, 300);
    }
}

// Proceed to specific sector route
function selectSector(sector) {
    chatbotState.sector = sector;
    chatbotState.step = 'problem';
    renderChatbotUI();
}

function selectProblem(problem) {
    chatbotState.problem = problem;
    chatbotState.step = 'solution';
    renderChatbotUI();
}

function renderChatbotUI() {
    const contentBody = document.getElementById('chat-content');
    contentBody.innerHTML = ''; // clear

    if (chatbotState.step === 'problem') {
        let problemsHTML = '';
        if (chatbotState.sector === 'Gobierno') {
            problemsHTML = `
                <button onclick="selectProblem('expedientes')" class="w-full text-left p-3 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 text-sm mb-2 transition-colors">Revisión lenta de expedientes o compras públicas</button>
                <button onclick="selectProblem('transparencia')" class="w-full text-left p-3 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 text-sm mb-2 transition-colors">Reportes e indicadores de gestión y transparencia</button>
            `;
        } else if (chatbotState.sector === 'Salud') {
            problemsHTML = `
                <button onclick="selectProblem('listas')" class="w-full text-left p-3 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 text-sm mb-2 transition-colors">Gestión de listas de espera y ocupación de camas</button>
                <button onclick="selectProblem('clinicas')" class="w-full text-left p-3 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 text-sm mb-2 transition-colors">Historias clínicas dispersas y reportes manuales</button>
            `;
        } else if (chatbotState.sector === 'Finanzas') {
            problemsHTML = `
                <button onclick="selectProblem('riesgo')" class="w-full text-left p-3 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 text-sm mb-2 transition-colors">Modelos de scoring, riesgo y prevención de fraude</button>
                <button onclick="selectProblem('creditos')" class="w-full text-left p-3 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 text-sm mb-2 transition-colors">Evaluación manual de solicitudes de crédito</button>
            `;
        } else {
            problemsHTML = `
                <button onclick="selectProblem('inventarios')" class="w-full text-left p-3 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 text-sm mb-2 transition-colors">Problemas de forecasting e inventarios (Retail/B2B)</button>
                <button onclick="selectProblem('reportes')" class="w-full text-left p-3 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 text-sm mb-2 transition-colors">Silos de datos y generación de reportes operativos lentos</button>
            `;
        }

        contentBody.innerHTML = `
            <div class="flex flex-col gap-4">
                <div class="flex gap-3">
                    <div class="w-8 h-8 rounded bg-brand/20 flex-shrink-0 flex items-center justify-center text-brandCyan"><i class="ph ph-robot"></i></div>
                    <div class="bg-white/5 p-3 rounded-2xl rounded-tl-sm text-sm border border-white/10">
                        Entendido, trabajas en ${chatbotState.sector}. ¿Qué proceso operativo te genera más cuellos de botella actualmente?
                    </div>
                </div>
                <div class="flex flex-col mt-2">
                    ${problemsHTML}
                </div>
            </div>
        `;
    } else if (chatbotState.step === 'solution') {
        contentBody.innerHTML = `
            <div class="flex flex-col gap-4">
                <div class="flex gap-3">
                    <div class="w-8 h-8 rounded bg-brand/20 flex-shrink-0 flex items-center justify-center text-brandCyan"><i class="ph ph-robot"></i></div>
                    <div class="bg-white/5 p-3 rounded-2xl rounded-tl-sm text-sm border border-white/10">
                        Podemos automatizar ese flujo y reducir los tiempos operativos en un 40% utilizando RPA e IA Predictiva.<br><br>¿Te gustaría agendar un diagnóstico rápido con un arquitecto de datos?
                    </div>
                </div>
                <div class="mt-4 flex flex-col gap-2">
                    <input type="text" placeholder="Tu Nombre" class="w-full bg-darker border border-white/10 rounded-lg p-3 text-sm focus:border-brandCyan outline-none transition-colors">
                    <input type="email" placeholder="Correo Institucional" class="w-full bg-darker border border-white/10 rounded-lg p-3 text-sm focus:border-brandCyan outline-none transition-colors">
                    <button class="w-full py-3 rounded-lg bg-brand hover:bg-brandHover text-white text-sm font-medium transition-colors mt-2">
                        Agendar Diagnóstico Gratuito
                    </button>
                </div>
            </div>
        `;
    }
}
