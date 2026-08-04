<?php
/**
 * index.php — Dashboard principal del CRM interno
 * Seguridad: IP whitelist + sesión autenticada (via auth.php)
 */
require_once __DIR__ . '/auth.php';
requireAuth();

$csrf = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>CRM Datanestiq — Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', system-ui, sans-serif; background: #0a0a0f; color: #e0e0e0; }
        .topbar {
            background: #12121a; border-bottom: 1px solid rgba(255,255,255,0.1);
            padding: 0.75rem 1.5rem; display: flex; justify-content: space-between; align-items: center;
        }
        .topbar h1 { font-size: 1.125rem; color: #fff; }
        .topbar h1 span { color: #22d3ee; }
        .topbar a { color: #888; text-decoration: none; font-size: 0.875rem; }
        .topbar a:hover { color: #f87171; }
        .container { max-width: 1200px; margin: 0 auto; padding: 1.5rem; }

        /* Stats */
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .stat-card {
            background: #12121a; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px;
            padding: 1rem; text-align: center;
        }
        .stat-card .value { font-size: 1.75rem; font-weight: 700; color: #22d3ee; }
        .stat-card .label { font-size: 0.75rem; color: #888; margin-top: 0.25rem; text-transform: uppercase; }

        /* Filters */
        .filters { display: flex; gap: 0.75rem; margin-bottom: 1rem; flex-wrap: wrap; align-items: center; }
        .filters select, .filters input {
            background: #1a1a2e; border: 1px solid rgba(255,255,255,0.15); border-radius: 8px;
            color: #fff; padding: 0.5rem 0.75rem; font-size: 0.875rem; outline: none;
        }
        .filters select:focus, .filters input:focus { border-color: #22d3ee; }

        /* Table */
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        th { text-align: left; padding: 0.75rem; color: #888; border-bottom: 1px solid rgba(255,255,255,0.1); font-weight: 600; }
        td { padding: 0.75rem; border-bottom: 1px solid rgba(255,255,255,0.05); }
        tr:hover td { background: rgba(34,211,238,0.03); }
        .status-badge {
            display: inline-block; padding: 0.2rem 0.6rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600;
        }
        .status-nuevo { background: rgba(59,130,246,0.2); color: #60a5fa; }
        .status-contactado { background: rgba(245,158,11,0.2); color: #fbbf24; }
        .status-cita_solicitada { background: rgba(139,92,246,0.2); color: #a78bfa; }
        .status-ganado { background: rgba(34,197,94,0.2); color: #4ade80; }
        .status-perdido { background: rgba(239,68,68,0.2); color: #f87171; }
        .status-no_interesado { background: rgba(107,114,128,0.2); color: #9ca3af; }
        .btn-detail {
            color: #22d3ee; background: none; border: 1px solid rgba(34,211,238,0.3); border-radius: 6px;
            padding: 0.3rem 0.6rem; font-size: 0.75rem; cursor: pointer; transition: all 0.2s;
        }
        .btn-detail:hover { background: rgba(34,211,238,0.1); }

        /* Modal */
        .modal-overlay {
            display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 100;
            align-items: center; justify-content: center;
        }
        .modal-overlay.active { display: flex; }
        .modal {
            background: #12121a; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px;
            width: 95%; max-width: 700px; max-height: 85vh; overflow-y: auto; padding: 2rem;
        }
        .modal h2 { font-size: 1.25rem; margin-bottom: 1rem; color: #fff; }
        .modal .close-btn {
            float: right; background: none; border: none; color: #888; font-size: 1.5rem; cursor: pointer;
        }
        .modal .field { margin-bottom: 0.75rem; }
        .modal .field label { display: block; font-size: 0.75rem; color: #888; margin-bottom: 0.25rem; text-transform: uppercase; }
        .modal .field .val { color: #fff; font-size: 0.875rem; }
        .modal select, .modal textarea, .modal input[type="text"] {
            width: 100%; background: #1a1a2e; border: 1px solid rgba(255,255,255,0.15); border-radius: 8px;
            color: #fff; padding: 0.5rem; font-size: 0.875rem; outline: none; font-family: inherit;
        }
        .modal textarea { min-height: 80px; resize: vertical; }
        .modal .btn-save {
            background: #22d3ee; color: #000; border: none; border-radius: 8px;
            padding: 0.6rem 1.5rem; font-weight: 700; cursor: pointer; margin-top: 0.75rem;
        }
        .modal .btn-save:hover { background: #06b6d4; }
        .timeline { border-left: 2px solid rgba(34,211,238,0.3); padding-left: 1rem; margin-top: 0.75rem; }
        .timeline-item { margin-bottom: 0.75rem; font-size: 0.8rem; }
        .timeline-item .time { color: #666; font-size: 0.7rem; }
        .timeline-item .type { color: #22d3ee; font-weight: 600; }
        .appointment-card {
            background: rgba(139,92,246,0.1); border: 1px solid rgba(139,92,246,0.3);
            border-radius: 8px; padding: 0.75rem; margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="topbar">
        <h1>🗂️ <span>CRM</span> Datanestiq</h1>
        <a href="?logout=1" id="logout-link">Cerrar sesión</a>
    </div>

        <!-- Navigation Tabs -->
        <div style="display:flex;gap:1rem;margin-bottom:1.5rem;border-bottom:1px solid rgba(255,255,255,0.1);padding-bottom:0.75rem;">
            <button id="tab-btn-leads" class="btn-detail" style="background:#22d3ee;color:#000;font-weight:700;padding:0.5rem 1rem;" onclick="switchTab('leads')">📋 Leads & Citas</button>
            <button id="tab-btn-analytics" class="btn-detail" style="padding:0.5rem 1rem;" onclick="switchTab('analytics')">📊 Analítica de Conversión</button>
            <button id="tab-btn-ops" class="btn-detail" style="padding:0.5rem 1rem;" onclick="switchTab('ops')">🛠️ Observabilidad Ops</button>
            <button id="tab-btn-behavior" class="btn-detail" style="padding:0.5rem 1rem;" onclick="switchTab('behavior')">📈 Engagement & Comportamiento</button>
        </div>

        <!-- View: Leads & Citas -->
        <div id="view-leads">
            <div class="stats" id="stats-grid"></div>

            <div class="filters">
                <select id="filter-status">
                    <option value="">Todos los estados</option>
                    <option value="nuevo">Nuevo</option>
                    <option value="contactado">Contactado</option>
                    <option value="cita_solicitada">Cita Solicitada</option>
                    <option value="ganado">Ganado</option>
                    <option value="perdido">Perdido</option>
                    <option value="no_interesado">No Interesado</option>
                </select>
                <input type="text" id="filter-search" placeholder="Buscar email, empresa, reto...">
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th><th>Email</th><th>Organización</th><th>Estado</th><th>Sector</th><th>Rol</th><th>Citas</th><th>Fecha</th><th></th>
                        </tr>
                    </thead>
                    <tbody id="leads-body"></tbody>
                </table>
            </div>
            <div style="text-align:center; margin-top:1rem;">
                <button id="prev-page" class="btn-detail" style="margin-right:0.5rem;">← Anterior</button>
                <span id="page-info" style="color:#888; font-size:0.85rem;"></span>
                <button id="next-page" class="btn-detail" style="margin-left:0.5rem;">Siguiente →</button>
            </div>
        </div>

        <!-- View: Analítica de Conversión (Spec 016) -->
        <div id="view-analytics" style="display:none;">
            <div class="stats" id="analytics-kpi-grid"></div>

            <!-- Embudo de Conversión -->
            <div style="background:#12121a;border:1px solid rgba(255,255,255,0.08);border-radius:12px;padding:1.5rem;margin-bottom:1.5rem;">
                <h3 style="font-size:1.1rem;color:#fff;margin-bottom:1rem;">Embudo de Conversión por Etapa</h3>
                <div id="funnel-container" style="display:flex;flex-direction:column;gap:0.75rem;"></div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
                <!-- Desglose por Sector x Rol -->
                <div style="background:#12121a;border:1px solid rgba(255,255,255,0.08);border-radius:12px;padding:1.5rem;">
                    <h3 style="font-size:1rem;color:#fff;margin-bottom:1rem;">Conversión por Sector × Rol</h3>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr><th>Sector</th><th>Rol</th><th>Leads</th><th>Ganados</th><th>% Ganados</th></tr>
                            </thead>
                            <tbody id="dimensions-body"></tbody>
                        </table>
                    </div>
                </div>

                <!-- Métricas de Proveedores LLM -->
                <div style="background:#12121a;border:1px solid rgba(255,255,255,0.08);border-radius:12px;padding:1.5rem;">
                    <h3 style="font-size:1rem;color:#fff;margin-bottom:1rem;">Rendimiento de Proveedores LLM</h3>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr><th>Proveedor</th><th>Peticiones</th><th>Latencia Avg</th><th>% Éxito</th></tr>
                            </thead>
                            <tbody id="llm-body"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- View: Observabilidad Ops (Spec 017) -->
        <div id="view-ops" style="display:none;">
            <!-- KPI Cards -->
            <div class="stats" id="ops-kpi-grid"></div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem;">
                <!-- Salud y SLA de Proveedores LLM -->
                <div style="background:#12121a;border:1px solid rgba(255,255,255,0.08);border-radius:12px;padding:1.5rem;">
                    <h3 style="font-size:1rem;color:#fff;margin-bottom:1rem;">⚡ Telemetría SLA & Failover de LLM (30 días)</h3>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr><th>Proveedor</th><th>Llamadas</th><th>% Éxito</th><th>Latencia Prom.</th><th>p50 (ms)</th><th>p95 (ms)</th></tr>
                            </thead>
                            <tbody id="ops-providers-body"></tbody>
                        </table>
                    </div>
                </div>

                <!-- Estado del Sistema y Build -->
                <div style="background:#12121a;border:1px solid rgba(255,255,255,0.08);border-radius:12px;padding:1.5rem;">
                    <h3 style="font-size:1rem;color:#fff;margin-bottom:1rem;">🖥️ Estado del Sistema y Despliegue</h3>
                    <div id="ops-system-info" style="display:flex;flex-direction:column;gap:0.75rem;"></div>
                </div>
            </div>

            <!-- Portafolio de Specs (SSOT Antidrift) -->
            <div style="background:#12121a;border:1px solid rgba(255,255,255,0.08);border-radius:12px;padding:1.5rem;margin-bottom:1.5rem;">
                <h3 style="font-size:1.1rem;color:#fff;margin-bottom:1rem;">🏛️ Estado del Portafolio de Specs (SSOT specsStatus.json)</h3>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>ID</th><th>Nombre de la Especicificación</th><th>Estado</th><th>Fase Real</th><th>Deuda Técnica Abierta</th><th>Última Auditoría</th></tr>
                        </thead>
                        <tbody id="ops-specs-body"></tbody>
                    </table>
                </div>
            </div>

            <!-- Tendencia Diaria 7d -->
            <div style="background:#12121a;border:1px solid rgba(255,255,255,0.08);border-radius:12px;padding:1.5rem;">
                <h3 style="font-size:1rem;color:#fff;margin-bottom:1rem;">📈 Tendencia Diaria de Peticiones y Errores (Últimos 7 días)</h3>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Fecha</th><th>Invocaciones Totales</th><th>Latencia Promedio</th><th>Errores / Failovers</th></tr>
                        </thead>
                        <tbody id="ops-trend-body"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- View: Engagement & Comportamiento (Spec 018) -->
        <div id="view-behavior" style="display:none;">
            <!-- KPI Cards -->
            <div class="stats" id="behavior-kpi-grid"></div>

            <!-- Insuficiente Datos warning container -->
            <div id="behavior-insufficient-data-warn" style="display:none;background:rgba(245,158,11,0.1);border:1px solid rgba(245,158,11,0.3);color:#f59e0b;padding:1rem;border-radius:8px;margin-bottom:1.5rem;font-size:0.9rem;">
                ⚠️ <strong>Muestra escasa o insuficiente:</strong> Se requieren al menos 20 eventos de micro-interacciones en los últimos 180 días para formular estadísticas y embudos agregados (Constitución &sect;2).
            </div>

            <div id="behavior-charts-grid" style="display:grid;grid-template-columns:1fr 1.2fr;gap:1.5rem;margin-bottom:1.5rem;">
                <!-- Top Opciones y Categorías -->
                <div style="background:#12121a;border:1px solid rgba(255,255,255,0.08);border-radius:12px;padding:1.5rem;display:flex;flex-direction:column;gap:1.5rem;">
                    <div>
                        <h3 style="font-size:1rem;color:#fff;margin-bottom:0.75rem;">🎯 Top Sectores / Roles Seleccionados (Chips)</h3>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr><th>Valor / Mapeo</th><th>Selecciones</th></tr>
                                </thead>
                                <tbody id="behavior-chips-body"></tbody>
                            </table>
                        </div>
                    </div>

                    <div>
                        <h3 style="font-size:1rem;color:#fff;margin-bottom:0.75rem;">🤖 Top Claves de Selección del Chatbot</h3>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr><th>Problema / Hito</th><th>Selecciones</th></tr>
                                </thead>
                                <tbody id="behavior-chatbot-body"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Búsquedas y Copilot -->
                <div style="background:#12121a;border:1px solid rgba(255,255,255,0.08);border-radius:12px;padding:1.5rem;display:flex;flex-direction:column;gap:1.5rem;">
                    <div>
                        <h3 style="font-size:1rem;color:#fff;margin-bottom:0.75rem;">🔍 Top Consultas Semánticas (PII Redactada)</h3>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr><th>Consulta de Búsqueda</th><th>Frecuencia</th></tr>
                                </thead>
                                <tbody id="behavior-queries-body"></tbody>
                            </table>
                        </div>
                    </div>

                    <div>
                        <h3 style="font-size:1rem;color:#fff;margin-bottom:0.75rem;">⚡ Escenarios del Copiloto Más Consultados</h3>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr><th>Escenario</th><th>Clicks</th></tr>
                                </thead>
                                <tbody id="behavior-copilot-body"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Embudos de Conversión y Pasos -->
            <div style="background:#12121a;border:1px solid rgba(255,255,255,0.08);border-radius:12px;padding:1.5rem;margin-bottom:1.5rem;">
                <h3 style="font-size:1.1rem;color:#fff;margin-bottom:1rem;">📊 Embudos y Progresión por Componente (Sesiones Únicas)</h3>
                
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
                    <div>
                        <h4 style="font-size:0.9rem;color:#22d3ee;margin-bottom:0.75rem;">🤖 Avance en Pasos de Chatbot</h4>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr><th>Paso (Target)</th><th>Sesiones Únicas</th></tr>
                                </thead>
                                <tbody id="behavior-chatbot-funnel-body"></tbody>
                            </table>
                        </div>
                    </div>

                    <div>
                        <h4 style="font-size:0.9rem;color:#22d3ee;margin-bottom:0.75rem;">🪄 Avance en Asistente de Diagnóstico (Wizard)</h4>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr><th>Hito (Target)</th><th>Sesiones Únicas</th></tr>
                                </thead>
                                <tbody id="behavior-wizard-funnel-body"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de detalle -->
    <div class="modal-overlay" id="detail-modal">
        <div class="modal">
            <button class="close-btn" onclick="closeModal()">×</button>
            <h2 id="modal-title">Detalle del Lead</h2>
            <div id="modal-content"></div>
        </div>
    </div>

    <script>
    const CSRF = '<?= htmlspecialchars($csrf) ?>';
    let currentPage = 1;
    let currentCsrf = CSRF;

    // Logout
    document.getElementById('logout-link').addEventListener('click', (e) => {
        e.preventDefault();
        fetch('api.php?action=stats').then(() => { // just to ensure session
            window.location = 'login.php?logout=1';
        });
    });

    async function api(action, params = {}, method = 'GET', body = null) {
        let url = `api.php?action=${action}`;
        Object.keys(params).forEach(k => url += `&${k}=${encodeURIComponent(params[k])}`);
        const opts = { method };
        if (body) {
            opts.headers = { 'Content-Type': 'application/json' };
            body.csrf_token = currentCsrf;
            opts.body = JSON.stringify(body);
        }
        const res = await fetch(url, opts);
        const data = await res.json();
        if (data.csrf_token) currentCsrf = data.csrf_token;
        return data;
    }

    async function loadStats() {
        const data = await api('stats');
        const grid = document.getElementById('stats-grid');
        const labels = {
            nuevo: 'Nuevos', contactado: 'Contactados', cita_solicitada: 'Cita Solicitada',
            ganado: 'Ganados', perdido: 'Perdidos', no_interesado: 'No Interesado'
        };
        let html = `<div class="stat-card"><div class="value">${data.total_leads}</div><div class="label">Total Leads</div></div>`;
        html += `<div class="stat-card"><div class="value">${data.pending_appointments}</div><div class="label">Citas Pendientes</div></div>`;
        Object.keys(data.status_counts).forEach(s => {
            html += `<div class="stat-card"><div class="value">${data.status_counts[s]}</div><div class="label">${labels[s] || s}</div></div>`;
        });
        grid.innerHTML = html;
    }

    async function loadLeads(page = 1) {
        currentPage = page;
        const status = document.getElementById('filter-status').value;
        const search = document.getElementById('filter-search').value;
        const data = await api('leads', { page, status, search });
        const tbody = document.getElementById('leads-body');
        const totalPages = Math.ceil(data.total / data.per_page);

        tbody.innerHTML = data.leads.map(l => `
            <tr>
                <td>${l.id}</td>
                <td>${esc(l.email)}</td>
                <td>${esc(l.organizacion)}</td>
                <td><span class="status-badge status-${l.status}">${l.status}</span></td>
                <td><span style="font-size:0.8rem;color:#aaa">${esc(l.sector || 'no_especificado')}</span></td>
                <td><span style="font-size:0.8rem;color:#aaa">${esc(l.rol || 'no_especificado')}</span></td>
                <td>${l.appointment_count || 0}</td>
                <td style="font-size:0.8rem;color:#888">${l.created_at}</td>
                <td><button class="btn-detail" onclick="openDetail(${l.id})">Ver</button></td>
            </tr>
        `).join('');

        document.getElementById('page-info').textContent = `Página ${page} de ${totalPages || 1}`;
        document.getElementById('prev-page').disabled = page <= 1;
        document.getElementById('next-page').disabled = page >= totalPages;
    }

    function switchTab(tab) {
        const leadsView = document.getElementById('view-leads');
        const analyticsView = document.getElementById('view-analytics');
        const opsView = document.getElementById('view-ops');
        const behaviorView = document.getElementById('view-behavior');
        
        const btnLeads = document.getElementById('tab-btn-leads');
        const btnAnalytics = document.getElementById('tab-btn-analytics');
        const btnOps = document.getElementById('tab-btn-ops');
        const btnBehavior = document.getElementById('tab-btn-behavior');

        // Reset button styles
        [btnLeads, btnAnalytics, btnOps, btnBehavior].forEach(btn => {
            if (btn) {
                btn.style.background = 'none';
                btn.style.color = '#22d3ee';
                btn.style.fontWeight = 'normal';
            }
        });

        if (tab === 'analytics') {
            leadsView.style.display = 'none';
            opsView.style.display = 'none';
            behaviorView.style.display = 'none';
            analyticsView.style.display = 'block';
            btnAnalytics.style.background = '#22d3ee';
            btnAnalytics.style.color = '#000';
            btnAnalytics.style.fontWeight = '700';
            loadAnalyticsData();
        } else if (tab === 'ops') {
            leadsView.style.display = 'none';
            analyticsView.style.display = 'none';
            behaviorView.style.display = 'none';
            opsView.style.display = 'block';
            btnOps.style.background = '#22d3ee';
            btnOps.style.color = '#000';
            btnOps.style.fontWeight = '700';
            loadOpsTelemetry();
        } else if (tab === 'behavior') {
            leadsView.style.display = 'none';
            analyticsView.style.display = 'none';
            opsView.style.display = 'none';
            behaviorView.style.display = 'block';
            btnBehavior.style.background = '#22d3ee';
            btnBehavior.style.color = '#000';
            btnBehavior.style.fontWeight = '700';
            loadBehaviorAnalytics();
        } else {
            analyticsView.style.display = 'none';
            opsView.style.display = 'none';
            behaviorView.style.display = 'none';
            leadsView.style.display = 'block';
            btnLeads.style.background = '#22d3ee';
            btnLeads.style.color = '#000';
            btnLeads.style.fontWeight = '700';
            loadLeads(1);
        }
    }

    async function loadOpsTelemetry() {
        const [telemetry, specsData] = await Promise.all([
            api('ops_telemetry'),
            api('ops_specs_status')
        ]);

        // KPIs
        const sla = telemetry.sla_global || {};
        const sys = telemetry.system_info || {};
        document.getElementById('ops-kpi-grid').innerHTML = `
            <div class="stat-card"><div class="value">${sla.sla_success_rate_pct || 100}%</div><div class="label">SLA Éxito Global (30d)</div></div>
            <div class="stat-card"><div class="value">${sla.avg_latency_ms || 0} ms</div><div class="label">Latencia Promedio Global</div></div>
            <div class="stat-card"><div class="value">${sla.total_requests || 0}</div><div class="label">Invocaciones Totales (30d)</div></div>
            <div class="stat-card"><div class="value" style="color:#22c55e;">${sys.status || 'HEALTHY'}</div><div class="label">Estado Subdominio (63 págs)</div></div>
        `;

        // Telemetría de Proveedores LLM
        const providersBody = document.getElementById('ops-providers-body');
        if (telemetry.providers && telemetry.providers.length > 0) {
            providersBody.innerHTML = telemetry.providers.map(p => {
                const p50Text = p.insufficient_data ? '<span style="color:#f59e0b;font-size:0.75rem;">Muestra escasa (&lt;10)</span>' : `${p.p50_latency_ms} ms`;
                const p95Text = p.insufficient_data ? '<span style="color:#f59e0b;font-size:0.75rem;">Muestra escasa (&lt;10)</span>' : `${p.p95_latency_ms} ms`;
                return `<tr>
                    <td><strong style="color:#22d3ee;">${esc(p.backend_used)}</strong></td>
                    <td>${p.total_calls}</td>
                    <td>${p.success_pct}%</td>
                    <td>${p.avg_latency_ms} ms</td>
                    <td>${p50Text}</td>
                    <td>${p95Text}</td>
                </tr>`;
            }).join('');
        } else {
            providersBody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#888;">Sin datos de peticiones en los últimos 30 días</td></tr>';
        }

        // Estado del Sistema
        document.getElementById('ops-system-info').innerHTML = `
            <div style="font-size:0.85rem;color:#ccc;"><strong>Último Build Estático:</strong> <span style="color:#22d3ee;">${esc(sys.last_build)}</span></div>
            <div style="font-size:0.85rem;color:#ccc;"><strong>Páginas Estáticas Compiladas:</strong> ${sys.static_pages} páginas HTML5</div>
            <div style="font-size:0.85rem;color:#ccc;"><strong>Especificaciones Verificadas (SSOT):</strong> ${sys.total_specs} specs</div>
            <div style="font-size:0.85rem;color:#ccc;"><strong>Subdominio Producción IONOS:</strong> <code>https://${esc(sys.subdomain)}/</code> (200 OK)</div>
            <div style="font-size:0.85rem;color:#22c55e;margin-top:0.5rem;font-weight:600;">✓ Protección .htaccess activa | IP Dynamic Auth Password-Only</div>
        `;

        // Portafolio de Specs (SSOT)
        const specsBody = document.getElementById('ops-specs-body');
        if (specsData.specs && specsData.specs.length > 0) {
            specsBody.innerHTML = specsData.specs.map(s => {
                let badgeColor = '#22c55e';
                if (s.status === 'DESIGNED' || s.status === 'IN_PROGRESS') badgeColor = '#f59e0b';
                if (s.status === 'SUPERSEDED' || s.status === 'ARCHIVED') badgeColor = '#ef4444';
                return `<tr>
                    <td><strong>${esc(s.id)}</strong></td>
                    <td style="color:#fff;">${esc(s.name)}</td>
                    <td><span style="background:${badgeColor}22;color:${badgeColor};border:1px solid ${badgeColor};padding:0.2rem 0.5rem;border-radius:4px;font-size:0.75rem;font-weight:600;">${esc(s.status)}</span></td>
                    <td style="font-size:0.8rem;color:#aaa;">${esc(s.phase)}</td>
                    <td style="font-size:0.8rem;color:#888;">${esc(s.openTechDebt || 'Ninguna')}</td>
                    <td style="font-size:0.75rem;color:#666;">${esc(s.lastAuditDate)}</td>
                </tr>`;
            }).join('');
        } else {
            specsBody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#888;">No se pudo cargar el archivo SSOT specsStatus.json</td></tr>';
        }

        // Tendencia Diaria 7d
        const trendBody = document.getElementById('ops-trend-body');
        if (telemetry.daily_trend && telemetry.daily_trend.length > 0) {
            trendBody.innerHTML = telemetry.daily_trend.map(t => `<tr>
                <td>${esc(t.date_day)}</td>
                <td>${t.daily_calls}</td>
                <td>${t.avg_latency_ms} ms</td>
                <td style="color:${t.errors_count > 0 ? '#ef4444' : '#22c55e'};">${t.errors_count}</td>
            </tr>`).join('');
        } else {
            trendBody.innerHTML = '<tr><td colspan="4" style="text-align:center;color:#888;">Sin registros de actividad en los últimos 7 días</td></tr>';
        }
    }

    async function loadBehaviorAnalytics() {
        const data = await api('ops_behavior_analytics');
        const warnBlock = document.getElementById('behavior-insufficient-data-warn');
        const chartsGrid = document.getElementById('behavior-charts-grid');
        const kpiGrid = document.getElementById('behavior-kpi-grid');

        // Render KPI Cards
        kpiGrid.innerHTML = `
            <div class="stat-card"><div class="value">${data.total_events || 0}</div><div class="label">Eventos Totales Recibidos</div></div>
            <div class="stat-card"><div class="value">${data.insufficient_data ? 'Muestra Escasa' : 'Completa'}</div><div class="label">Consistencia Métricas (IP Limit)</div></div>
            <div class="stat-card"><div class="value">180 días</div><div class="label">Ventana de Retención Activa</div></div>
            <div class="stat-card"><div class="value" style="color:#22c55e;">Activa</div><div class="label" style="font-weight:600;">Telemetría Spec 018</div></div>
        `;

        if (data.insufficient_data) {
            warnBlock.style.display = 'block';
            chartsGrid.style.display = 'none';
            return;
        }

        warnBlock.style.display = 'none';
        chartsGrid.style.display = 'grid';

        // 1. Top Chips Table
        const chipsBody = document.getElementById('behavior-chips-body');
        if (data.chips && data.chips.length > 0) {
            chipsBody.innerHTML = data.chips.map(c => `
                <tr>
                    <td><strong style="color:#22d3ee;">${esc(c.event_value)}</strong></td>
                    <td>${c.qty} selections</td>
                </tr>
            `).join('');
        } else {
            chipsBody.innerHTML = '<tr><td colspan="2" style="text-align:center;color:#888;">Sin selecciones registradas</td></tr>';
        }

        // 2. Top Chatbot Selections Table
        const chatbotBody = document.getElementById('behavior-chatbot-body');
        if (data.chatbot_values && data.chatbot_values.length > 0) {
            chatbotBody.innerHTML = data.chatbot_values.map(c => `
                <tr>
                    <td><strong style="color:#22d3ee;">${esc(c.event_value)}</strong></td>
                    <td>${c.qty} hits</td>
                </tr>
            `).join('');
        } else {
            chatbotBody.innerHTML = '<tr><td colspan="2" style="text-align:center;color:#888;">Sin datos de clics de chatbot</td></tr>';
        }

        // 3. Top Semantic Search Table
        const queriesBody = document.getElementById('behavior-queries-body');
        if (data.queries && data.queries.length > 0) {
            queriesBody.innerHTML = data.queries.map(q => `
                <tr>
                    <td><em style="color:#aaa;">"${esc(q.event_value)}"</em></td>
                    <td>${q.qty} búsquedas</td>
                </tr>
            `).join('');
        } else {
            queriesBody.innerHTML = '<tr><td colspan="2" style="text-align:center;color:#888;">Sin consultas de búsqueda registradas</td></tr>';
        }

        // 4. Top Copilot Clicks Table
        const copilotBody = document.getElementById('behavior-copilot-body');
        if (data.copilot && data.copilot.length > 0) {
            copilotBody.innerHTML = data.copilot.map(c => `
                <tr>
                    <td><strong style="color:#a78bfa;">${esc(c.event_value)}</strong></td>
                    <td>${c.qty} clicks</td>
                </tr>
            `).join('');
        } else {
            copilotBody.innerHTML = '<tr><td colspan="2" style="text-align:center;color:#888;">Sin clics en Copiloto registrados</td></tr>';
        }

        // 5. Chatbot Funnel Table
        const chatbotFunnelBody = document.getElementById('behavior-chatbot-funnel-body');
        if (data.chatbot_funnel && data.chatbot_funnel.length > 0) {
            chatbotFunnelBody.innerHTML = data.chatbot_funnel.map(f => `
                <tr>
                    <td><strong style="color:#22d3ee;">${esc(f.event_target)}</strong></td>
                    <td>${f.unique_sessions} sesiones únicas</td>
                </tr>
            `).join('');
        } else {
            chatbotFunnelBody.innerHTML = '<tr><td colspan="2" style="text-align:center;color:#888;">Sin progresión de embudo chatbot</td></tr>';
        }

        // 6. Wizard Funnel Table
        const wizardFunnelBody = document.getElementById('behavior-wizard-funnel-body');
        if (data.wizard_funnel && data.wizard_funnel.length > 0) {
            wizardFunnelBody.innerHTML = data.wizard_funnel.map(f => `
                <tr>
                    <td><strong style="color:#22d3ee;">${esc(f.event_target)}</strong></td>
                    <td>${f.unique_sessions} sesiones únicas</td>
                </tr>
            `).join('');
        } else {
            wizardFunnelBody.innerHTML = '<tr><td colspan="2" style="text-align:center;color:#888;">Sin progresión de embudo de diagnóstico</td></tr>';
        }
    }

    async function loadAnalyticsData() {
        const [funnelData, dimData, llmData] = await Promise.all([
            api('analytics_funnel'),
            api('analytics_by_dimension'),
            api('analytics_llm_metrics')
        ]);

        // KPIs
        document.getElementById('analytics-kpi-grid').innerHTML = `
            <div class="stat-card"><div class="value">${funnelData.total_leads}</div><div class="label">Total Leads</div></div>
            <div class="stat-card"><div class="value">${funnelData.tasa_citas_pct}%</div><div class="label">Tasa Citas</div></div>
            <div class="stat-card"><div class="value">${funnelData.tasa_ganados_pct}%</div><div class="label">Tasa Ganados</div></div>
            <div class="stat-card"><div class="value">${llmData.avg_latency_ms || 0} ms</div><div class="label">Latencia Avg LLM</div></div>
        `;

        // Funnel
        const funnelContainer = document.getElementById('funnel-container');
        funnelContainer.innerHTML = funnelData.funnel.map(f => `
            <div>
                <div style="display:flex;justify-content:space-between;font-size:0.85rem;margin-bottom:0.25rem;">
                    <span style="color:#fff;"><span class="status-badge status-${f.status}">${f.status}</span></span>
                    <span style="color:#888;">${f.count} leads (${f.percentage}%)</span>
                </div>
                <div style="background:rgba(255,255,255,0.05);height:12px;border-radius:6px;overflow:hidden;">
                    <div style="width:${Math.max(f.percentage, 2)}%;height:100%;background:#22d3ee;border-radius:6px;transition:width 0.5s;"></div>
                </div>
            </div>
        `).join('');

        // Dimensions Table
        document.getElementById('dimensions-body').innerHTML = dimData.dimensions.map(d => `
            <tr>
                <td>${esc(d.sector_name)}</td>
                <td>${esc(d.rol_name)}</td>
                <td>${d.total_leads}</td>
                <td>${d.ganados}</td>
                <td><strong style="color:#4ade80;">${d.tasa_ganados_pct}%</strong></td>
            </tr>
        `).join('');

        // LLM Metrics Table
        document.getElementById('llm-body').innerHTML = llmData.backends.map(b => `
            <tr>
                <td><strong style="color:#22d3ee;">${esc(b.backend_used)}</strong></td>
                <td>${b.calls}</td>
                <td>${b.avg_latency_ms} ms</td>
                <td><span style="color:${b.success_rate_pct >= 90 ? '#4ade80' : '#f87171'}">${b.success_rate_pct}%</span></td>
            </tr>
        `).join('');
    }

    function esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

    async function openDetail(id) {
        const data = await api('lead_detail', { id });
        const l = data.lead;
        const modal = document.getElementById('detail-modal');
        const content = document.getElementById('modal-content');

        let html = `
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
                <div class="field"><label>Email</label><div class="val">${esc(l.email)}</div></div>
                <div class="field"><label>Teléfono</label><div class="val">${esc(l.telefono)}</div></div>
                <div class="field"><label>Organización</label><div class="val">${esc(l.organizacion)}</div></div>
                <div class="field"><label>Fuente</label><div class="val">${esc(l.source)}</div></div>
                <div class="field"><label>Sector</label><div class="val">${esc(l.sector || 'no_especificado')}</div></div>
                <div class="field"><label>Rol</label><div class="val">${esc(l.rol || 'no_especificado')}</div></div>
            </div>
            <div class="field"><label>Reto</label><div class="val">${esc(l.reto)}</div></div>
            <div class="field"><label>Stack</label><div class="val">${esc(l.stack)}</div></div>
            <div style="margin-top:0.5rem;"><button class="btn-detail" onclick="viewChatHistory('${esc(l.session_id)}')">💬 Ver Historial Chat de Sesión</button></div>
            <div id="chat-history-container" style="margin-top:0.5rem;display:none;"></div>
            <hr style="border-color:rgba(255,255,255,0.1);margin:1rem 0;">
            <h3 style="font-size:1rem;margin-bottom:0.5rem;">Gestión</h3>
            <div class="field">
                <label>Estado</label>
                <select id="edit-status">
                    ${['nuevo','contactado','cita_solicitada','ganado','perdido','no_interesado'].map(s => 
                        `<option value="${s}" ${l.status===s?'selected':''}>${s}</option>`
                    ).join('')}
                </select>
            </div>
            <div class="field"><label>Próxima Acción</label><input type="text" id="edit-next-action" value="${esc(l.next_action)}"></div>
            <div class="field"><label>Notas</label><textarea id="edit-notes">${esc(l.notes)}</textarea></div>
            <button class="btn-save" onclick="saveLead(${l.id})">Guardar Cambios</button>
        `;

        // Appointments
        if (data.appointments && data.appointments.length > 0) {
            html += `<hr style="border-color:rgba(255,255,255,0.1);margin:1rem 0;"><h3 style="font-size:1rem;margin-bottom:0.5rem;">Citas</h3>`;
            data.appointments.forEach(a => {
                html += `<div class="appointment-card">
                    <strong>${a.requested_date}</strong> — ${esc(a.type)} — 
                    <span class="status-badge status-${a.status === 'solicitada' ? 'cita_solicitada' : a.status === 'confirmada' ? 'ganado' : 'perdido'}">${a.status}</span>
                    ${a.status === 'solicitada' ? `
                        <button class="btn-detail" style="margin-left:0.5rem;" onclick="updateAppt(${a.id},'confirmada')">✓ Confirmar</button>
                        <button class="btn-detail" style="margin-left:0.25rem;border-color:rgba(239,68,68,0.3);color:#f87171;" onclick="updateAppt(${a.id},'cancelada')">✕ Cancelar</button>
                    ` : ''}
                </div>`;
            });
        }

        // Interactions timeline
        if (data.interactions && data.interactions.length > 0) {
            html += `<hr style="border-color:rgba(255,255,255,0.1);margin:1rem 0;"><h3 style="font-size:1rem;margin-bottom:0.5rem;">Recorrido</h3><div class="timeline">`;
            data.interactions.forEach(i => {
                let parsed = '';
                try { parsed = JSON.stringify(JSON.parse(i.content), null, 2); } catch { parsed = i.content; }
                html += `<div class="timeline-item">
                    <div class="time">${i.created_at}</div>
                    <div><span class="type">${esc(i.interaction_type)}</span></div>
                    <div style="white-space:pre-wrap;color:#aaa;margin-top:0.2rem;font-size:0.75rem;">${esc(parsed)}</div>
                </div>`;
            });
            html += '</div>';
        }

        // Status history
        if (data.status_history && data.status_history.length > 0) {
            html += `<hr style="border-color:rgba(255,255,255,0.1);margin:1rem 0;"><h3 style="font-size:1rem;margin-bottom:0.5rem;">Historial de Estado</h3><div class="timeline">`;
            data.status_history.forEach(h => {
                html += `<div class="timeline-item">
                    <div class="time">${h.created_at} — por ${esc(h.changed_by)}</div>
                    <div>${esc(h.old_status)} → <strong>${esc(h.new_status)}</strong></div>
                </div>`;
            });
            html += '</div>';
        }

        content.innerHTML = html;
        modal.classList.add('active');
    }

    async function viewChatHistory(sessionId) {
        const container = document.getElementById('chat-history-container');
        if (!container) return;
        if (container.style.display === 'block') {
            container.style.display = 'none';
            return;
        }
        container.style.display = 'block';
        container.innerHTML = '<div style="color:#888;font-size:0.8rem;">Cargando mensajes...</div>';

        const data = await api('lead_chat_history', { session_id: sessionId });
        if (!data.interactions || data.interactions.length === 0) {
            container.innerHTML = '<div style="color:#888;font-size:0.8rem;">No hay registros de chat para esta sesión.</div>';
            return;
        }

        let html = '<div style="background:#1a1a2e;border:1px solid rgba(255,255,255,0.1);border-radius:8px;padding:0.75rem;max-height:250px;overflow-y:auto;font-size:0.8rem;">';
        data.interactions.forEach(item => {
            let body = item.content;
            try {
                const parsed = JSON.parse(item.content);
                body = parsed.content || parsed.label || item.content;
            } catch (e) {}
            html += `<div style="margin-bottom:0.4rem;"><span style="color:#22d3ee;font-weight:600;">[${esc(item.interaction_type)}]</span> <span style="color:#ddd;">${esc(body)}</span> <span style="color:#666;font-size:0.7rem;">(${item.created_at})</span></div>`;
        });

        if (data.metrics && data.metrics.length > 0) {
            html += '<hr style="border-color:rgba(255,255,255,0.1);margin:0.5rem 0;"><div style="color:#888;font-size:0.75rem;">Métricas LLM:</div>';
            data.metrics.forEach(m => {
                html += `<div style="color:#aaa;font-size:0.75rem;">Backend: <strong style="color:#22d3ee;">${esc(m.backend_used)}</strong> | Latencia: ${m.latency_ms}ms | Éxito: ${m.success ? '1' : '0'}</div>`;
            });
        }
        html += '</div>';
        container.innerHTML = html;
    }

    function closeModal() { document.getElementById('detail-modal').classList.remove('active'); }

    async function saveLead(id) {
        await api('update_lead', {}, 'POST', {
            id,
            status: document.getElementById('edit-status').value,
            next_action: document.getElementById('edit-next-action').value,
            notes: document.getElementById('edit-notes').value,
        });
        closeModal();
        loadLeads(currentPage);
        loadStats();
    }

    async function updateAppt(id, status) {
        await api('update_appointment', {}, 'POST', { id, status });
        closeModal();
        loadLeads(currentPage);
        loadStats();
    }

    // Event listeners
    document.getElementById('filter-status').addEventListener('change', () => loadLeads(1));
    document.getElementById('filter-search').addEventListener('input', debounce(() => loadLeads(1), 400));
    document.getElementById('prev-page').addEventListener('click', () => { if (currentPage > 1) loadLeads(currentPage - 1); });
    document.getElementById('next-page').addEventListener('click', () => loadLeads(currentPage + 1));

    function debounce(fn, ms) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; }

    // Init
    loadStats();
    loadLeads(1);
    </script>
</body>
</html>
<?php
// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}
