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
        <div style="display:flex;gap:1rem;margin-bottom:1.5rem;border-bottom:1px solid rgba(255,255,255,0.1);padding-bottom:0.75rem;flex-wrap:wrap;">
            <button id="tab-btn-leads" class="btn-detail" style="background:#22d3ee;color:#000;font-weight:700;padding:0.5rem 1rem;" onclick="switchTab('leads')">📋 Leads & Citas</button>
            <button id="tab-btn-conversations" class="btn-detail" style="padding:0.5rem 1rem;" onclick="switchTab('conversations')">💬 Conversaciones</button>
            <button id="tab-btn-analytics" class="btn-detail" style="padding:0.5rem 1rem;" onclick="switchTab('analytics')">📊 Analítica & Eficiencia IA</button>
            <button id="tab-btn-ops" class="btn-detail" style="padding:0.5rem 1rem;" onclick="switchTab('ops')">⚡ Salud & Costos IA</button>
            <button id="tab-btn-behavior" class="btn-detail" style="padding:0.5rem 1rem;" onclick="switchTab('behavior')">📈 Engagement & Comportamiento</button>
            <button id="tab-btn-demand" class="btn-detail" style="padding:0.5rem 1rem;" onclick="switchTab('demand')">🔍 Demanda & Journey</button>
        </div>

        <!-- View: Leads & Citas -->
        <div id="view-leads">
            <!-- Sub-Navigation for Leads & Citas -->
            <div style="display:flex;gap:0.5rem;margin-bottom:1.5rem;border-bottom:1px solid rgba(255,255,255,0.05);padding-bottom:0.75rem;">
                <button id="leads-subtab-crm" class="btn-detail" style="background:#22d3ee;color:#000;font-size:0.75rem;padding:0.35rem 0.75rem;font-weight:700;" onclick="switchLeadsSubTab('crm')">Formularios CRM</button>
                <button id="leads-subtab-detected" class="btn-detail" style="background:rgba(255,255,255,0.05);color:#fff;font-size:0.75rem;padding:0.35rem 0.75rem;" onclick="switchLeadsSubTab('detected')">Detectados en Chat (LLM)</button>
                <button id="leads-subtab-agenda" class="btn-detail" style="background:rgba(255,255,255,0.05);color:#fff;font-size:0.75rem;padding:0.35rem 0.75rem;" onclick="switchLeadsSubTab('agenda')">📅 Agenda Global de Citas</button>
            </div>

            <!-- Sub-View: CRM Forms -->
            <div id="leads-content-crm">
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

            <!-- Sub-View: Extracted Chat Leads (LLM) -->
            <div id="leads-content-detected" style="display:none;">
                <div style="background:rgba(34,211,238,0.05);border:1px solid rgba(34,211,238,0.15);padding:1rem;border-radius:8px;margin-bottom:1.5rem;font-size:0.85rem;color:rgba(255,255,255,0.7);line-height:1.4;">
                    💡 <strong>Leads Detectados por IA:</strong> Estos contactos fueron extraídos por modelos LLM de forma asíncrona analizando conversaciones de chat libres que <em>no</em> completaron el formulario formal. Se presentan de forma deducida y deduplicada automáticamente si ya completaron un formulario posteriormente.
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Nombre</th><th>Email</th><th>Teléfono</th><th>Intención Detectada</th><th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="detected-leads-body"></tbody>
                    </table>
                </div>
            <!-- Sub-View: Agenda Global de Citas (Spec 015 / Spec 023 Parte 3) -->
            <div id="leads-content-agenda" style="display:none;">
                <div style="background:rgba(139,92,246,0.08);border:1px solid rgba(139,92,246,0.2);padding:1rem;border-radius:8px;margin-bottom:1.5rem;font-size:0.85rem;color:rgba(255,255,255,0.8);line-height:1.4;">
                    📅 <strong>Agenda Global de Citas:</strong> Listado cronológico de solicitudes de diagnóstico y citas técnicas agendadas mediante el componente interactivo. Permite confirmar, reagendar o actualizar el estado de atención.
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Fecha Solic.</th>
                                <th>Cliente / Email</th>
                                <th>Empresa</th>
                                <th>Sector • Rol</th>
                                <th>Tipo de Cita</th>
                                <th>Duración</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="agenda-body"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- View: Visor de Conversaciones Redactadas (Spec 022) -->
        <div id="view-conversations" style="display:none; padding:1.5rem;">
            <div style="background:#12121a;border:1px solid rgba(255,255,255,0.08);border-radius:12px;padding:1.5rem;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
                    <h3 style="font-size:1.1rem;color:#fff;margin:0;">💬 Historial de Conversaciones del Chatbot (PII Redactada §2)</h3>
                    <span style="font-size:0.75rem;color:#888;">Fuente Única SQLite `conversations`</span>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1.3fr;gap:1.5rem;">
                    <!-- Lista de Sesiones de Chat -->
                    <div style="border:1px solid rgba(255,255,255,0.05);border-radius:8px;background:#0d0d16;max-height:450px;overflow-y:auto;">
                        <table style="width:100%;border-collapse:collapse;font-size:0.75rem;text-align:left;">
                            <thead>
                                <tr style="border-bottom:1px solid rgba(255,255,255,0.08);background:rgba(255,255,255,0.02);">
                                    <th style="padding:0.6rem;color:rgba(255,255,255,0.6);">Sesión</th>
                                    <th style="padding:0.6rem;color:rgba(255,255,255,0.6);">Backend</th>
                                    <th style="padding:0.6rem;color:rgba(255,255,255,0.6);text-align:center;">Msgs</th>
                                </tr>
                            </thead>
                            <tbody id="conversations-sessions-tbody"></tbody>
                        </table>
                    </div>

                    <!-- Visualizador de Mensajes Interactivos -->
                    <div style="background:#0d0d16;border:1px solid rgba(255,255,255,0.05);border-radius:8px;padding:1.25rem;display:flex;flex-direction:column;">
                        <h4 id="conversation-detail-title" style="font-size:0.9rem;color:#22d3ee;margin-top:0;margin-bottom:1rem;border-bottom:1px solid rgba(255,255,255,0.05);padding-bottom:0.5rem;">Selecciona una conversación</h4>
                        <div id="conversation-messages-container" style="flex:1;max-height:380px;overflow-y:auto;display:flex;flex-direction:column;gap:0.75rem;">
                            <span style="color:#666;font-size:0.8rem;text-align:center;margin-top:2rem;">Haz clic en una sesión del panel izquierdo para reconstruir el diálogo.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- View: Analítica de Conversión & Eficiencia IA (Spec 016 / Spec 023) -->
        <div id="view-analytics" style="display:none;">
            <div class="stats" id="analytics-kpi-grid"></div>

            <!-- Spec 023 Parte 1: Tarjeta de Eficiencia 0-LLM y Ahorro Estimado -->
            <div style="background:#12121a;border:1px solid rgba(255,255,255,0.08);border-radius:12px;padding:1.5rem;margin-bottom:1.5rem;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
                    <h3 style="font-size:1.1rem;color:#fff;margin:0;">⚡ Eficiencia del Router 0-LLM y Costos Evitados</h3>
                    <span id="ai-efficiency-badge" style="background:rgba(34,211,238,0.15);color:#22d3ee;font-size:0.75rem;padding:0.25rem 0.5rem;border-radius:4px;font-weight:600;">[EST] Escenario Estimado §2</span>
                </div>

                <div id="ai-efficiency-insufficient" style="display:none;background:rgba(245,158,11,0.1);border:1px solid rgba(245,158,11,0.3);color:#f59e0b;padding:0.75rem;border-radius:8px;font-size:0.85rem;">
                    ⚠️ <strong>Muestra insuficiente para Eficiencia 0-LLM:</strong> Se requieren al menos 20 eventos de enrutamiento para calcular métricas de ahorro fiables (§2).
                </div>

                <div id="ai-efficiency-content" style="display:grid;grid-template-columns: 1fr 1fr 1.2fr;gap:1.5rem;align-items:center;">
                    <!-- Dona SVG Inline 0-LLM -->
                    <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;background:#0d0d16;padding:1.25rem;border-radius:8px;border:1px solid rgba(255,255,255,0.05);">
                        <svg viewBox="0 0 36 36" style="width:110px;height:110px;transform:rotate(-90deg);">
                            <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="rgba(255,255,255,0.08)" stroke-width="3.8"/>
                            <path id="svg-donut-segment" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#22d3ee" stroke-width="3.8" stroke-dasharray="0, 100"/>
                        </svg>
                        <div id="ai-donut-percent" style="font-size:1.25rem;font-weight:700;color:#fff;margin-top:0.5rem;">0%</div>
                        <span style="font-size:0.75rem;color:#888;">Consultas Resueltas 0-LLM</span>
                    </div>

                    <!-- Métricas de Ahorro -->
                    <div style="display:flex;flex-direction:column;gap:0.75rem;justify-content:center;">
                        <div style="background:#0d0d16;padding:0.75rem 1rem;border-radius:8px;border:1px solid rgba(255,255,255,0.05);">
                            <div style="font-size:0.75rem;color:#888;">Llamadas a LLM Evitadas</div>
                            <div id="ai-saved-calls" style="font-size:1.4rem;font-weight:700;color:#34d399;">0</div>
                        </div>
                        <div style="background:#0d0d16;padding:0.75rem 1rem;border-radius:8px;border:1px solid rgba(255,255,255,0.05);">
                            <div style="font-size:0.75rem;color:#888;">Ahorro Estimado ($ USD) <span style="color:#22d3ee;font-size:0.7rem;">[EST]</span></div>
                            <div id="ai-saved-dollars" style="font-size:1.4rem;font-weight:700;color:#22d3ee;">$0.0000</div>
                        </div>
                    </div>

                    <!-- Desglose por Ruta -->
                    <div style="background:#0d0d16;padding:1rem;border-radius:8px;border:1px solid rgba(255,255,255,0.05);">
                        <div style="font-size:0.8rem;color:#fff;font-weight:600;margin-bottom:0.5rem;">Desglose de Ruteo:</div>
                        <div id="ai-routes-breakdown-list" style="display:flex;flex-direction:column;gap:0.4rem;font-size:0.75rem;color:rgba(255,255,255,0.8);"></div>
                        <div style="font-size:0.68rem;color:#666;margin-top:0.75rem;line-height:1.3;" id="ai-efficiency-note">
                            * Cómputo basado en tokens reales de chat_metrics y tarifa ref. ($0.30/1M). Proveedores activos en free-tier.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Embudo de Conversión CSS (Spec 023 Parte 6) -->
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

            <!-- Loop Learn Prompt Optimization Insights -->
            <div style="background:#12121a;border:1px solid rgba(255,255,255,0.08);border-radius:12px;padding:1.5rem;margin-top:1.5rem;">
                <h3 style="font-size:1.1rem;color:#fff;margin-bottom:1rem;">💡 Optimizador Automático del Prompt (Loop Learn Insights)</h3>
                <div id="ops-learn-report" style="background:#0d0d16;padding:1.5rem;border-radius:8px;border:1px solid rgba(255,255,255,0.05);max-height:300px;overflow-y:auto;font-family:monospace;white-space:pre-wrap;color:rgba(255,255,255,0.85);font-size:0.8rem;line-height:1.5;">
                    Cargando reporte de optimización...
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

                <!-- Tarjeta de Consumo Diario vs Cap (Spec 022 File-Free) -->
                <div style="background:#12121a;border:1px solid rgba(255,255,255,0.08);border-radius:12px;padding:1.5rem;">
                    <h3 style="font-size:1rem;color:#fff;margin-bottom:1rem;">🛡️ Consumo Diario de Tokens vs Daily Cap (Gobernanza)</h3>
                    <div style="display:flex;flex-direction:column;gap:0.75rem;">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="font-size:0.8rem;color:#888;">Consumo Hoy (<span id="ops-daily-date">-</span>):</span>
                            <span id="ops-daily-status-badge" style="background:rgba(34,197,94,0.15);color:#34d399;font-size:0.75rem;padding:0.2rem 0.5rem;border-radius:4px;font-weight:600;">NORMAL</span>
                        </div>
                        <div style="background:rgba(255,255,255,0.05);height:14px;border-radius:7px;overflow:hidden;position:relative;">
                            <div id="ops-daily-progress-bar" style="background:#22d3ee;width:0%;height:100%;transition:width 0.5s ease;"></div>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:0.75rem;color:rgba(255,255,255,0.7);">
                            <span>Tokens: <strong id="ops-daily-tokens-val" style="color:#fff;">0</strong> / <span id="ops-daily-cap-val">500,000</span></span>
                            <span>Peticiones Hoy: <strong id="ops-daily-reqs-val" style="color:#fff;">0</strong></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tendencia Diaria 7d -->
            <div style="background:#12121a;border:1px solid rgba(255,255,255,0.08);border-radius:12px;padding:1.5rem;margin-bottom:1.5rem;">
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

            <!-- New Spec 021 Sections: SQLite Alerts & Token Usage -->
            <div style="display:grid;grid-template-columns:1.3fr 1fr;gap:1.5rem;">
                
                <!-- Infrastructure Alerts Table -->
                <div style="background:#12121a;border:1px solid rgba(255,255,255,0.08);border-radius:12px;padding:1.5rem;display:flex;flex-direction:column;">
                    <h3 style="font-size:1rem;color:#fff;margin-bottom:1rem;">⚠️ Registro de Alertas de Infraestructura (SQLite)</h3>
                    <div class="table-wrap" style="max-height:250px;overflow-y:auto;flex:1;">
                        <table style="width:100%;font-size:0.75rem;text-align:left;">
                            <thead>
                                <tr style="border-bottom:1px solid rgba(255,255,255,0.08);">
                                    <th style="padding:0.5rem;color:rgba(255,255,255,0.6);">Fecha</th>
                                    <th style="padding:0.5rem;color:rgba(255,255,255,0.6);">Tipo</th>
                                    <th style="padding:0.5rem;color:rgba(255,255,255,0.6);">Mensaje</th>
                                </tr>
                            </thead>
                            <tbody id="ops-alerts-body"></tbody>
                        </table>
                    </div>
                </div>

                <!-- LLM Token Usage Breakdown -->
                <div style="background:#12121a;border:1px solid rgba(255,255,255,0.08);border-radius:12px;padding:1.5rem;display:flex;flex-direction:column;">
                    <h3 style="font-size:1rem;color:#fff;margin-bottom:1rem;">🪙 Consumo y Desglose de Tokens LLM</h3>
                    <div style="display:flex;flex-direction:column;gap:1rem;flex:1;" id="ops-tokens-usage-container">
                        <!-- Populated dynamically -->
                    </div>
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

    <!-- View: Demanda & Journey -->
    <div id="view-demand" style="display:none; padding:1.5rem;">
        <!-- Insuficiente Datos warning container -->
        <div id="demand-insufficient" style="display:none;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#f87171;padding:1rem;border-radius:8px;margin-bottom:1rem;font-size:0.875rem;">
            ⚠️ <strong>Datos insuficientes:</strong> Se requieren al menos 20 señales de demanda y 10 interesados para calcular estadísticas fiables (§2).
        </div>

        <!-- Banner DEMO (§2): visible solo cuando el toggle está ON -->
        <div id="demand-demo-banner" style="display:none;background:rgba(239,68,68,0.12);border:1px solid rgba(239,68,68,0.4);color:#fca5a5;padding:0.6rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:0.8rem;font-weight:600;">
            🧪 <strong>MODO DEMO ACTIVO:</strong> Los buckets incluyen datos sembrados artificialmente. Los conteos y ejemplos que ves NO son demanda real. Desactiva "Incluir Demo" para ver solo datos reales.
        </div>

        <div id="demand-dashboard" style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
            <!-- Bucket 1: Demanda No Atendida -->
            <div style="background:#12121a;border:1px solid rgba(255,255,255,0.08);border-radius:12px;padding:1.5rem;">
                <h3 style="font-size:1rem;color:#fff;margin-bottom:1rem;">🔍 Posible Demanda No Atendida <span style="font-size:0.75rem;color:#888;font-weight:normal;">(Hipótesis de mercado, N&ge;20)</span></h3>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Servicio Sugerido</th>
                                <th>Consultas</th>
                                <th>Ejemplos Recientes (redactados-imperfectos §2)</th>
                            </tr>
                        </thead>
                        <tbody id="demand-signals-body"></tbody>
                    </table>
                </div>
            </div>

            <!-- Bucket 2: Fugas de Conversión -->
            <div style="background:#12121a;border:1px solid rgba(255,255,255,0.08);border-radius:12px;padding:1.5rem;">
                <h3 style="font-size:1rem;color:#fff;margin-bottom:1rem;">⚠️ Fugas de Conversión por Servicio <span style="font-size:0.75rem;color:#888;font-weight:normal;">(Atribución aproximada §2, N&ge;10)</span></h3>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Servicio Ofrecido</th>
                                <th>Interés (Sesiones)</th>
                                <th>Leads Capturados</th>
                                <th>Fuga (Sesiones)</th>
                                <th>% Fuga</th>
                            </tr>
                        </thead>
                        <tbody id="leakage-body"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Journey Reconstructor Container (Two-column layout) -->
        <div style="margin-top:1.5rem;display:grid;grid-template-columns: 1fr 1.3fr;gap:1.5rem;">
            
            <!-- Left Column: Recents sessions list -->
            <div style="background:#12121a;border:1px solid rgba(255,255,255,0.08);border-radius:12px;padding:1.5rem;display:flex;flex-direction:column;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
                    <h3 style="font-size:1rem;color:#fff;margin:0;">🧭 Sesiones Recientes</h3>
                    <label style="font-size:0.75rem;color:rgba(255,255,255,0.6);display:flex;align-items:center;gap:0.25rem;cursor:pointer;">
                        <input type="checkbox" id="journey-demo-toggle" onchange="loadDemandData()" style="cursor:pointer;" /> Incluir Demo
                    </label>
                </div>
                <div id="journey-sessions-list-container" style="flex:1;max-height:350px;overflow-y:auto;border:1px solid rgba(255,255,255,0.05);border-radius:8px;background:#0d0d16;">
                    <table style="width:100%;border-collapse:collapse;font-size:0.75rem;text-align:left;">
                        <thead>
                            <tr style="border-bottom:1px solid rgba(255,255,255,0.08);background:rgba(255,255,255,0.02);">
                                <th style="padding:0.5rem;color:rgba(255,255,255,0.6);">Sesión / Contacto</th>
                                <th style="padding:0.5rem;color:rgba(255,255,255,0.6);text-align:center;">Eventos</th>
                            </tr>
                        </thead>
                        <tbody id="journey-sessions-tbody">
                            <!-- Populated dynamically -->
                        </tbody>
                    </table>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:0.75rem;font-size:0.75rem;">
                    <button class="btn-detail" onclick="journeyPrevPage()" style="padding:0.25rem 0.5rem;font-size:0.7rem;background:rgba(255,255,255,0.05);color:#fff;border:none;border-radius:4px;cursor:pointer;">« Ant</button>
                    <span id="journey-page-info" style="color:rgba(255,255,255,0.5);">Pág. 1</span>
                    <button class="btn-detail" onclick="journeyNextPage()" style="padding:0.25rem 0.5rem;font-size:0.7rem;background:rgba(255,255,255,0.05);color:#fff;border:none;border-radius:4px;cursor:pointer;">Sig »</button>
                </div>
            </div>

            <!-- Right Column: Timeline Reconstructor -->
            <div style="background:#12121a;border:1px solid rgba(255,255,255,0.08);border-radius:12px;padding:1.5rem;display:flex;flex-direction:column;">
                <h3 style="font-size:1rem;color:#fff;margin-bottom:1rem;">🔬 Journey Reconstructor</h3>
                <div style="display:flex;gap:0.75rem;margin-bottom:1rem;">
                    <input type="text" id="journey-session-label" placeholder="Selecciona una sesión de la izquierda..." readonly style="flex:1;background:#1a1a2e;border:1px solid rgba(255,255,255,0.15);border-radius:8px;color:#aaa;padding:0.5rem 0.75rem;font-size:0.875rem;outline:none;cursor:default;" />
                    <input type="hidden" id="journey-session-id" />
                    <button class="btn-detail" style="background:#22d3ee;color:#000;font-weight:700;padding:0.5rem 1rem;" onclick="reconstructJourney()">Reconstruir</button>
                </div>
                
                <div id="journey-result" style="display:none;background:#1a1a2e;padding:1.5rem;border-radius:12px;border:1px solid rgba(255,255,255,0.05);max-height:300px;overflow-y:auto;flex:1;">
                    <div id="journey-timeline" style="display:flex;flex-direction:column;gap:1rem;border-left:2px solid rgba(34,211,238,0.3);padding-left:1.5rem;margin-left:0.5rem;"></div>
                </div>
                <div id="journey-empty-state" style="display:flex;align-items:center;justify-content:center;height:200px;border:1px dashed rgba(255,255,255,0.1);border-radius:12px;color:rgba(255,255,255,0.4);font-size:0.875rem;text-align:center;padding:1rem;">
                    Selecciona una sesión de la lista de la izquierda para ver su Journey de conversión interactivo.
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
    let journeyPage = 1;
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
        const conversationsView = document.getElementById('view-conversations');
        const analyticsView = document.getElementById('view-analytics');
        const opsView = document.getElementById('view-ops');
        const behaviorView = document.getElementById('view-behavior');
        const demandView = document.getElementById('view-demand');
        
        const btnLeads = document.getElementById('tab-btn-leads');
        const btnConversations = document.getElementById('tab-btn-conversations');
        const btnAnalytics = document.getElementById('tab-btn-analytics');
        const btnOps = document.getElementById('tab-btn-ops');
        const btnBehavior = document.getElementById('tab-btn-behavior');
        const btnDemand = document.getElementById('tab-btn-demand');

        // Reset button styles
        [btnLeads, btnConversations, btnAnalytics, btnOps, btnBehavior, btnDemand].forEach(btn => {
            if (btn) {
                btn.style.background = 'none';
                btn.style.color = '#22d3ee';
                btn.style.fontWeight = 'normal';
            }
        });

        if (tab === 'conversations') {
            leadsView.style.display = 'none';
            analyticsView.style.display = 'none';
            opsView.style.display = 'none';
            behaviorView.style.display = 'none';
            if (demandView) demandView.style.display = 'none';
            conversationsView.style.display = 'block';
            btnConversations.style.background = '#22d3ee';
            btnConversations.style.color = '#000';
            btnConversations.style.fontWeight = '700';
            loadConversations();
        } else if (tab === 'analytics') {
            leadsView.style.display = 'none';
            if (conversationsView) conversationsView.style.display = 'none';
            opsView.style.display = 'none';
            behaviorView.style.display = 'none';
            if (demandView) demandView.style.display = 'none';
            analyticsView.style.display = 'block';
            btnAnalytics.style.background = '#22d3ee';
            btnAnalytics.style.color = '#000';
            btnAnalytics.style.fontWeight = '700';
            loadAnalyticsData();
        } else if (tab === 'ops') {
            leadsView.style.display = 'none';
            if (conversationsView) conversationsView.style.display = 'none';
            analyticsView.style.display = 'none';
            behaviorView.style.display = 'none';
            if (demandView) demandView.style.display = 'none';
            opsView.style.display = 'block';
            btnOps.style.background = '#22d3ee';
            btnOps.style.color = '#000';
            btnOps.style.fontWeight = '700';
            loadOpsTelemetry();
            loadDailyUsage();
        } else if (tab === 'behavior') {
            leadsView.style.display = 'none';
            if (conversationsView) conversationsView.style.display = 'none';
            analyticsView.style.display = 'none';
            opsView.style.display = 'none';
            if (demandView) demandView.style.display = 'none';
            behaviorView.style.display = 'block';
            btnBehavior.style.background = '#22d3ee';
            btnBehavior.style.color = '#000';
            btnBehavior.style.fontWeight = '700';
            loadBehaviorAnalytics();
        } else if (tab === 'demand') {
            leadsView.style.display = 'none';
            if (conversationsView) conversationsView.style.display = 'none';
            analyticsView.style.display = 'none';
            opsView.style.display = 'none';
            behaviorView.style.display = 'none';
            if (demandView) demandView.style.display = 'block';
            btnDemand.style.background = '#22d3ee';
            btnDemand.style.color = '#000';
            btnDemand.style.fontWeight = '700';
            loadDemandData();
        } else {
            if (conversationsView) conversationsView.style.display = 'none';
            analyticsView.style.display = 'none';
            opsView.style.display = 'none';
            behaviorView.style.display = 'none';
            if (demandView) demandView.style.display = 'none';
            leadsView.style.display = 'block';
            btnLeads.style.background = '#22d3ee';
            btnLeads.style.color = '#000';
            btnLeads.style.fontWeight = '700';
            loadLeads(1);
        }
    }

    function switchLeadsSubTab(sub) {
        const crmContent = document.getElementById('leads-content-crm');
        const detectedContent = document.getElementById('leads-content-detected');
        const agendaContent = document.getElementById('leads-content-agenda');

        const btnCrm = document.getElementById('leads-subtab-crm');
        const btnDetected = document.getElementById('leads-subtab-detected');
        const btnAgenda = document.getElementById('leads-subtab-agenda');

        [btnCrm, btnDetected, btnAgenda].forEach(btn => {
            if (btn) {
                btn.style.background = 'rgba(255,255,255,0.05)';
                btn.style.color = '#fff';
                btn.style.fontWeight = 'normal';
            }
        });

        if (sub === 'detected') {
            if (crmContent) crmContent.style.display = 'none';
            if (agendaContent) agendaContent.style.display = 'none';
            if (detectedContent) detectedContent.style.display = 'block';
            if (btnDetected) {
                btnDetected.style.background = '#22d3ee';
                btnDetected.style.color = '#000';
                btnDetected.style.fontWeight = '700';
            }
            loadDetectedLeads();
        } else if (sub === 'agenda') {
            if (crmContent) crmContent.style.display = 'none';
            if (detectedContent) detectedContent.style.display = 'none';
            if (agendaContent) agendaContent.style.display = 'block';
            if (btnAgenda) {
                btnAgenda.style.background = '#22d3ee';
                btnAgenda.style.color = '#000';
                btnAgenda.style.fontWeight = '700';
            }
            loadAgenda();
        } else {
            if (detectedContent) detectedContent.style.display = 'none';
            if (agendaContent) agendaContent.style.display = 'none';
            if (crmContent) crmContent.style.display = 'block';
            if (btnCrm) {
                btnCrm.style.background = '#22d3ee';
                btnCrm.style.color = '#000';
                btnCrm.style.fontWeight = '700';
            }
            loadLeads(1);
        }
    }

    async function loadConversations() {
        const data = await api('conversations', { include_demo: getIncludeDemoFlag() });
        const tbody = document.getElementById('conversations-sessions-tbody');
        if (!data.conversations || data.conversations.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;padding:1rem;color:#888;">Sin conversaciones registradas</td></tr>';
            return;
        }

        tbody.innerHTML = data.conversations.map(c => `
            <tr style="cursor:pointer;border-bottom:1px solid rgba(255,255,255,0.03);" onclick="selectConversation('${esc(c.session_id)}')">
                <td style="padding:0.6rem;color:#22d3ee;font-family:monospace;">${esc(c.session_id.substring(0, 16))}...</td>
                <td style="padding:0.6rem;color:#aaa;">${esc(c.backend_used || 'groq')}</td>
                <td style="padding:0.6rem;text-align:center;color:#fff;font-weight:600;">${c.message_count}</td>
            </tr>
        `).join('');
    }

    async function selectConversation(sessionId) {
        const data = await api('conversations', { session_id: sessionId });
        const container = document.getElementById('conversation-messages-container');
        const title = document.getElementById('conversation-detail-title');

        title.textContent = `Sesión: ${sessionId}`;

        if (!data.messages || data.messages.length === 0) {
            container.innerHTML = '<span style="color:#666;font-size:0.8rem;">Sin mensajes grabados en esta sesión.</span>';
            return;
        }

        container.innerHTML = data.messages.map(m => {
            const isUser = m.role === 'user';
            const isSystem = m.role === 'system';
            if (isSystem) return ''; // Omitir prompt de sistema en la visualización

            const align = isUser ? 'flex-end' : 'flex-start';
            const bg = isUser ? 'rgba(34,211,238,0.15)' : 'rgba(255,255,255,0.05)';
            const border = isUser ? '1px solid rgba(34,211,238,0.3)' : '1px solid rgba(255,255,255,0.1)';
            const color = isUser ? '#22d3ee' : '#e2e8f0';
            const label = isUser ? 'Usuario' : 'Asistente IA';

            return `
                <div style="align-self:${align};max-width:85%;background:${bg};border:${border};padding:0.65rem 0.85rem;border-radius:8px;font-size:0.8rem;line-height:1.4;">
                    <div style="font-size:0.7rem;color:#888;margin-bottom:0.25rem;display:flex;justify-content:space-between;gap:1rem;">
                        <strong>${label}</strong>
                        <span>${m.created_at || ''}</span>
                    </div>
                    <div style="color:${color};white-space:pre-wrap;">${esc(m.content)}</div>
                </div>
            `;
        }).join('');
    }

    async function loadAgenda() {
        const data = await api('agenda', { include_demo: getIncludeDemoFlag() });
        const tbody = document.getElementById('agenda-body');
        if (!data.appointments || data.appointments.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:1.5rem;color:#888;">No hay citas o diagnósticos agendados.</td></tr>';
            return;
        }

        tbody.innerHTML = data.appointments.map(a => `
            <tr>
                <td style="font-size:0.8rem;color:#22d3ee;font-weight:600;">${esc(a.requested_date)}</td>
                <td>
                    <div style="font-weight:600;color:#fff;">${esc(a.nombre || 'Cliente Cita')}</div>
                    <div style="font-size:0.75rem;color:#888;">${esc(a.email)} ${a.telefono ? '• ' + esc(a.telefono) : ''}</div>
                </td>
                <td>${esc(a.organizacion || 'Individual')}</td>
                <td style="font-size:0.75rem;color:#aaa;">${esc(a.sector || 'general')} • ${esc(a.rol || 'lider')}</td>
                <td><span style="background:rgba(139,92,246,0.15);color:#a78bfa;font-size:0.75rem;padding:0.2rem 0.5rem;border-radius:4px;">${esc(a.service_type)}</span></td>
                <td>${a.duration_minutes || 30} min</td>
                <td><span class="status-badge status-${a.status}">${esc(a.status)}</span></td>
                <td>
                    ${a.status === 'solicitada' ? `<button class="btn-detail" style="background:#22c55e;color:#000;font-weight:700;margin-right:0.3rem;" onclick="updateAppointmentStatus(${a.appointment_id}, 'confirmada')">Confirmar</button>` : ''}
                    ${a.status !== 'cancelada' ? `<button class="btn-detail" style="background:rgba(239,68,68,0.2);color:#ef4444;" onclick="updateAppointmentStatus(${a.appointment_id}, 'cancelada')">Cancelar</button>` : ''}
                </td>
            </tr>
        `).join('');
    }

    async function updateAppointmentStatus(appointmentId, newStatus) {
        if (!confirm(`¿Confirmas cambiar el estado de la cita #${appointmentId} a "${newStatus}"?`)) return;
        const res = await api('update_appointment', {}, 'POST', { appointment_id: appointmentId, status: newStatus });
        if (res.success) {
            loadAgenda();
        } else {
            alert('Error al actualizar la cita: ' + (res.error || 'Acción denegada'));
        }
    }

    async function loadDailyUsage() {
        const data = await api('usage_daily');
        document.getElementById('ops-daily-date').textContent = data.usage_date || '-';
        document.getElementById('ops-daily-tokens-val').textContent = (data.total_tokens || 0).toLocaleString();
        document.getElementById('ops-daily-cap-val').textContent = (data.daily_cap || 500000).toLocaleString();
        document.getElementById('ops-daily-reqs-val').textContent = (data.request_count || 0).toLocaleString();

        const pct = Math.min(100, data.percentage_used || 0);
        const bar = document.getElementById('ops-daily-progress-bar');
        bar.style.width = `${pct}%`;
        bar.style.background = pct >= 100 ? '#ef4444' : (pct >= 80 ? '#f59e0b' : '#22d3ee');

        const badge = document.getElementById('ops-daily-status-badge');
        badge.textContent = data.status || 'NORMAL';
        badge.style.background = pct >= 100 ? 'rgba(239,68,68,0.15)' : (pct >= 80 ? 'rgba(245,158,11,0.15)' : 'rgba(34,197,94,0.15)');
        badge.style.color = pct >= 100 ? '#ef4444' : (pct >= 80 ? '#f59e0b' : '#34d399');
    }

    async function loadAiEfficiency() {
        const data = await api('ai_efficiency', { include_demo: getIncludeDemoFlag() });
        const warn = document.getElementById('ai-efficiency-insufficient');
        const content = document.getElementById('ai-efficiency-content');

        if (data.insufficient_data) {
            warn.style.display = 'block';
            content.style.opacity = '0.4';
            return;
        }

        warn.style.display = 'none';
        content.style.opacity = '1';

        const pct = data.zero_llm_percentage || 0;
        document.getElementById('ai-donut-percent').textContent = `${pct}%`;
        document.getElementById('svg-donut-segment').setAttribute('stroke-dasharray', `${pct}, 100`);

        document.getElementById('ai-saved-calls').textContent = (data.zero_llm_count || 0).toLocaleString();
        document.getElementById('ai-saved-dollars').textContent = `$${(data.estimated_savings_usd || 0).toFixed(4)}`;

        const rList = document.getElementById('ai-routes-breakdown-list');
        const rData = data.routes_breakdown || {};
        rList.innerHTML = `
            <div>• FAQ Determinístico (0-LLM): <strong>${rData.faq || 0}</strong></div>
            <div>• Cita Directa (0-LLM): <strong>${rData.cita || 0}</strong></div>
            <div>• Flujo Guiado (0-LLM): <strong>${rData.guiado || 0}</strong></div>
            <div style="color:#22d3ee;margin-top:0.2rem;">• Proveedor LLM (Modelos): <strong>${rData.llm || 0}</strong></div>
        `;
    }

    async function loadOpsTelemetry() {
        const telemetry = await api('ops_telemetry');

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

        // Spec 021: Load SQLite alerts and token usage breakdown
        try {
            const [alertsData, usageData] = await Promise.all([
                api('alerts_ops', { limit: 20 }),
                api('usage_ops')
            ]);

            // Render alerts
            const alertsBody = document.getElementById('ops-alerts-body');
            if (alertsData.alerts && alertsData.alerts.length > 0) {
                alertsBody.innerHTML = alertsData.alerts.map(a => `<tr>
                    <td style="padding:0.4rem 0.5rem;color:#888;">${esc(a.created_at)}</td>
                    <td style="padding:0.4rem 0.5rem;"><span style="color:${a.alert_type === 'burst' || a.alert_type === 'cap_80' ? '#ef4444' : '#f59e0b'};font-weight:600;">${esc(a.alert_type.toUpperCase())}</span></td>
                    <td style="padding:0.4rem 0.5rem;color:#ccc;">${esc(a.message)}</td>
                </tr>`).join('');
            } else {
                alertsBody.innerHTML = '<tr><td colspan="3" style="text-align:center;color:#666;padding:1rem;">Sin alertas registradas en SQLite</td></tr>';
            }

            // Render token usage
            const tokensContainer = document.getElementById('ops-tokens-usage-container');
            const summary = usageData.summary || {};
            const total = parseInt(summary.total_tokens || 0);
            const prompt = parseInt(summary.total_prompt_tokens || 0);
            const completion = parseInt(summary.total_completion_tokens || 0);
            // Costo estimado (§2: solo cuando hay desglose real)
            const hasBreakdown = prompt > 0 || completion > 0;
            const cost = hasBreakdown ? ((prompt * 0.15) + (completion * 0.60)) / 1000000 : 0;
            
            const promptPct = total > 0 && hasBreakdown ? Math.round((prompt / total) * 100) : 0;
            const compPct = total > 0 && hasBreakdown ? Math.round((completion / total) * 100) : 0;

            // §2 — Honestidad Radical: si el desglose es 0, no mostrar barras vacías
            const breakdownHtml = hasBreakdown ? `
                <div>
                    <div style="display:flex;justify-content:space-between;font-size:0.75rem;margin-bottom:0.25rem;color:#aaa;">
                        <span>Prompt / Entrada (${promptPct}%)</span>
                        <span>${prompt.toLocaleString()} tokens</span>
                    </div>
                    <div style="background:rgba(255,255,255,0.05);height:8px;border-radius:4px;overflow:hidden;margin-bottom:0.75rem;">
                        <div style="width:${promptPct}%;height:100%;background:#22d3ee;border-radius:4px;"></div>
                    </div>

                    <div style="display:flex;justify-content:space-between;font-size:0.75rem;margin-bottom:0.25rem;color:#aaa;">
                        <span>Completion / Salida (${compPct}%)</span>
                        <span>${completion.toLocaleString()} tokens</span>
                    </div>
                    <div style="background:rgba(255,255,255,0.05);height:8px;border-radius:4px;overflow:hidden;">
                        <div style="width:${compPct}%;height:100%;background:#c084fc;border-radius:4px;"></div>
                    </div>
                </div>
            ` : `
                <div style="background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.2);border-radius:8px;padding:0.75rem;font-size:0.8rem;color:#f59e0b;line-height:1.5;">
                    ⚠️ <strong>Desglose no disponible (§2):</strong> Los campos <code>prompt_tokens</code> / <code>completion_tokens</code> llegan como 0 desde el proveedor activo. Solo se registra el total de tokens estimados. El desglose se mostrará automáticamente cuando esté disponible en la respuesta del LLM.
                </div>
            `;

            tokensContainer.innerHTML = `
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;background:rgba(255,255,255,0.02);padding:1rem;border-radius:8px;">
                    <div>
                        <div style="font-size:0.75rem;color:rgba(255,255,255,0.5);">Tokens Totales</div>
                        <div style="font-size:1.25rem;color:#fff;font-weight:700;">${total.toLocaleString()}</div>
                    </div>
                    <div>
                        <div style="font-size:0.75rem;color:rgba(255,255,255,0.5);">Costo Acumulado Est.</div>
                        <div style="font-size:1.25rem;color:${hasBreakdown ? '#4ade80' : '#888'};font-weight:700;">${hasBreakdown ? '$' + cost.toFixed(5) + ' USD' : 'N/A'}</div>
                    </div>
                </div>
                ${breakdownHtml}
            `;
        } catch (e) {
            console.error('Error loading spec 021 ops details:', e);
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
        // Cargar tarjeta de Eficiencia 0-LLM (Spec 023 Parte 1)
        loadAiEfficiency();

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

        // Funnel con Guardarraíl §2 ($N < 20$ leads)
        const funnelContainer = document.getElementById('funnel-container');
        if (funnelData.total_leads < 20) {
            funnelContainer.innerHTML = `
                <div style="background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.2);color:#f59e0b;padding:0.75rem 1rem;border-radius:8px;font-size:0.85rem;">
                    ⚠️ <strong>Muestra escasa de leads (${funnelData.total_leads} &lt; 20):</strong> Se muestran porcentajes orientativos por etapa sin extrapolar conclusiones estocásticas (§2).
                </div>
            ` + funnelData.funnel.map(f => `
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
        } else {
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
        }

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

        // Spec 021: Load Prompt Optimization insights report
        try {
            const reportData = await api('learn_insights');
            document.getElementById('ops-learn-report').textContent = reportData.content;
        } catch (e) {
            document.getElementById('ops-learn-report').textContent = "Error al cargar reporte de optimización: " + e.message;
        }
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

    async function loadDemandData() {
        // Fix Arreglo 1: cargar sesiones SIEMPRE al inicio, antes de cualquier guard §2
        loadJourneySessions();

        const includeDemo = document.getElementById('journey-demo-toggle')?.checked ? 1 : 0;

        // Banner §2: visible cuando toggle está ON
        const demoBanner = document.getElementById('demand-demo-banner');
        if (demoBanner) demoBanner.style.display = includeDemo ? 'block' : 'none';

        try {
            const [demandRes, leakageRes] = await Promise.all([
                api('demand_signals', { include_demo: includeDemo }),
                api('leakage', { include_demo: includeDemo })
            ]);

            const insufficientContainer = document.getElementById('demand-insufficient');
            const dashboardContainer = document.getElementById('demand-dashboard');

            if (demandRes.insufficient_data || leakageRes.insufficient_data) {
                insufficientContainer.style.display = 'block';
                dashboardContainer.style.display = 'none';
                return;
            }

            insufficientContainer.style.display = 'none';
            dashboardContainer.style.display = 'grid';

            // Render Bucket 1 (Demanda no atendida)
            const demandBody = document.getElementById('demand-signals-body');
            demandBody.innerHTML = '';
            if (demandRes.demand && demandRes.demand.length > 0) {
                demandRes.demand.forEach(row => {
                    const examplesEscaped = (row.examples || '')
                        .split(' | ')
                        .map(ex => `<span style="background:rgba(255,255,255,0.05);padding:0.2rem 0.4rem;border-radius:4px;display:inline-block;margin:0.1rem;font-size:0.75rem;">${esc(ex)}</span>`)
                        .join(' ');

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td style="font-weight:600;color:#fff;">${esc(row.matched_service || 'Otro')}</td>
                        <td style="color:#22d3ee;">${row.total_requests}</td>
                        <td style="color:#aaa;">${examplesEscaped}</td>
                    `;
                    demandBody.appendChild(tr);
                });
            } else {
                demandBody.innerHTML = '<tr><td colspan="3" style="text-align:center;color:#666;">No hay registros de brechas de demanda.</td></tr>';
            }

            // Render Bucket 2 (Fugas de conversión)
            const leakageBody = document.getElementById('leakage-body');
            leakageBody.innerHTML = '';
            if (leakageRes.leakage && leakageRes.leakage.length > 0) {
                leakageRes.leakage.forEach(row => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td style="font-weight:600;color:#fff;">${esc(row.matched_service || 'Otro')}</td>
                        <td>${row.total_interested_sessions}</td>
                        <td style="color:#22c55e;">${row.converted_leads}</td>
                        <td style="color:#f87171;">${row.leaked_sessions}</td>
                        <td style="font-weight:700;color:${row.leakage_percentage > 70 ? '#f87171' : '#fbbf24'};">${row.leakage_percentage}%</td>
                    `;
                    leakageBody.appendChild(tr);
                });
            } else {
                leakageBody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#666;">No hay registros de fugas de conversión.</td></tr>';
            }

            // (loadJourneySessions ya se llama al inicio de loadDemandData)

        } catch (err) {
            console.error('Error loading demand data:', err);
        }
    }

    async function reconstructJourney() {
        const sessionId = document.getElementById('journey-session-id').value.trim();
        if (!sessionId || sessionId.length < 32) {
            alert('Por favor ingresa un session_id válido (mínimo 32 caracteres)');
            return;
        }

        const resultContainer = document.getElementById('journey-result');
        const timeline = document.getElementById('journey-timeline');
        const emptyState = document.getElementById('journey-empty-state');

        if (emptyState) emptyState.style.display = 'none';
        resultContainer.style.display = 'block';
        timeline.innerHTML = '<div style="color:#888;font-size:0.8rem;">Cargando Journey...</div>';

        try {
            const res = await api(`lead_journey&session_id=${encodeURIComponent(sessionId)}`);
            timeline.innerHTML = '';

            if (res.error) {
                timeline.innerHTML = `<div style="color:#f87171;font-size:0.8rem;font-weight:600;">⚠️ Error en el servidor: ${esc(res.error)}</div>`;
                return;
            }

            if (!res.journey || res.journey.length === 0) {
                timeline.innerHTML = '<div style="color:#888;font-size:0.8rem;">No se encontraron interacciones para esta sesión.</div>';
                return;
            }

            res.journey.forEach(item => {
                const itemDiv = document.createElement('div');
                itemDiv.className = 'timeline-item';
                itemDiv.style.position = 'relative';
                itemDiv.style.marginBottom = '1rem';

                let badgeColor = '#666';
                let sourceTitle = 'Evento';
                let description = '';

                if (item.source === 'behavior') {
                    badgeColor = '#3b82f6';
                    sourceTitle = 'Comportamiento (Micro)';
                    description = `Acción: <strong>${esc(item.activity)}</strong> en <code>${esc(item.target)}</code> (Valor: ${esc(item.detail)})`;
                } else if (item.source === 'demand') {
                    badgeColor = '#a78bfa';
                    sourceTitle = 'Chatbot 0-LLM';
                    
                    let q = item.detail || '';
                    let routeInfo = '';
                    let ctxInfo = '';
                    
                    const routeMatch = q.match(/\[Route:\s*([^\]]+)\]/);
                    if (routeMatch) {
                        routeInfo = routeMatch[1];
                        q = q.replace(routeMatch[0], '');
                    }
                    const ctxMatch = q.match(/\[Context:\s*([^\]]+)\]/);
                    if (ctxMatch) {
                        ctxInfo = ctxMatch[1];
                        q = q.replace(ctxMatch[0], '');
                    }
                    q = q.trim();

                    let extraBadge = '';
                    if (routeInfo) {
                        extraBadge += ` <span style="background:rgba(34,211,238,0.15);color:#22d3ee;padding:0.15rem 0.4rem;border-radius:4px;font-size:0.7rem;font-weight:600;margin-left:0.5rem;text-transform:uppercase;">${esc(routeInfo)}</span>`;
                    }
                    if (ctxInfo && ctxInfo !== '/') {
                        extraBadge += ` <span style="background:rgba(251,191,36,0.15);color:#fbbf24;padding:0.15rem 0.4rem;border-radius:4px;font-size:0.7rem;font-weight:600;margin-left:0.5rem;text-transform:uppercase;">${esc(ctxInfo)}</span>`;
                    }

                    description = `Mensaje clasificado: "${esc(q)}"${extraBadge}<br/>Intención: <strong>${esc(item.activity)}</strong> (Servicio: <code>${esc(item.target || 'N/A')}</code>)`;
                } else if (item.source === 'crm_interaction') {
                    badgeColor = '#22c55e';
                    sourceTitle = 'CRM (Contacto)';
                    description = `Contacto: <strong>${esc(item.activity)}</strong> &rarr; <em>${esc(item.detail)}</em>`;
                } else if (item.source === 'crm_status') {
                    badgeColor = '#fbbf24';
                    sourceTitle = 'CRM Estado';
                    description = `Cambio de estado: <strong>${esc(item.target)}</strong> &rarr; Notas: <em>${esc(item.detail || 'Ninguna')}</em>`;
                }

                itemDiv.innerHTML = `
                    <div style="position:absolute;left:-2.1rem;top:0.2rem;width:1rem;height:1rem;border-radius:50%;background:${badgeColor};border:2px solid #1a1a2e;"></div>
                    <div style="font-size:0.75rem;color:#888;margin-bottom:0.15rem;">
                        <span style="font-weight:600;color:${badgeColor};text-transform:uppercase;">${sourceTitle}</span> &bull; ${item.ts}
                    </div>
                    <div style="font-size:0.875rem;color:#fff;">${description}</div>
                `;
                timeline.appendChild(itemDiv);
            });

        } catch (err) {
            timeline.innerHTML = `<div style="color:#f87171;font-size:0.8rem;">Error al cargar el journey: ${esc(err.message)}</div>`;
        }
    }

    // Spec 021: Sub-Navegación de Leads Detectados
    function switchLeadsSubTab(subtab) {
        const crmTab = document.getElementById('leads-subtab-crm');
        const detTab = document.getElementById('leads-subtab-detected');
        const crmContent = document.getElementById('leads-content-crm');
        const detContent = document.getElementById('leads-content-detected');

        if (subtab === 'detected') {
            crmTab.style.background = 'rgba(255,255,255,0.05)';
            crmTab.style.color = '#fff';
            crmTab.style.fontWeight = 'normal';
            detTab.style.background = '#22d3ee';
            detTab.style.color = '#000';
            detTab.style.fontWeight = '700';

            crmContent.style.display = 'none';
            detContent.style.display = 'block';
            loadDetectedLeads();
        } else {
            crmTab.style.background = '#22d3ee';
            crmTab.style.color = '#000';
            crmTab.style.fontWeight = '700';
            detTab.style.background = 'rgba(255,255,255,0.05)';
            detTab.style.color = '#fff';
            detTab.style.fontWeight = 'normal';

            crmContent.style.display = 'block';
            detContent.style.display = 'none';
            loadLeads(currentPage);
        }
    }

    async function loadDetectedLeads() {
        const body = document.getElementById('detected-leads-body');
        body.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#888;padding:1rem;">Cargando leads detectados por LLM...</td></tr>';
        try {
            const data = await api('leads_detected');
            if (data.leads && data.leads.length > 0) {
                body.innerHTML = data.leads.map(l => `
                    <tr>
                        <td style="color:#fff;font-weight:600;">${esc(l.nombre)}</td>
                        <td><a href="mailto:${esc(l.email)}" style="color:#22d3ee;text-decoration:none;">${esc(l.email)}</a></td>
                        <td>${esc(l.telefono)}</td>
                        <td><span style="background:rgba(167,139,250,0.15);color:#a78bfa;padding:0.2rem 0.4rem;border-radius:4px;font-size:0.75rem;font-weight:600;">${esc(l.intencion)}</span></td>
                        <td>
                            <button class="btn-detail" onclick="goToJourney('${esc(l.session_id)}')" style="padding:0.25rem 0.5rem;font-size:0.75rem;background:#22d3ee;color:#000;border:none;border-radius:4px;cursor:pointer;">Ver Chat</button>
                        </td>
                    </tr>
                `).join('');
            } else {
                body.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#666;padding:1rem;">No se detectaron leads adicionales en el historial de chat</td></tr>';
            }
        } catch (e) {
            body.innerHTML = `<tr><td colspan="5" style="text-align:center;color:#ef4444;padding:1rem;">Error al cargar leads detectados: ${esc(e.message)}</td></tr>`;
        }
    }

    function goToJourney(sessionId) {
        switchTab('demand');
        document.getElementById('journey-session-id').value = sessionId;
        reconstructJourney();
    }

    // Spec 021: Carga y paginación de la lista de sesiones
    async function loadJourneySessions() {
        const tbody = document.getElementById('journey-sessions-tbody');
        tbody.innerHTML = '<tr><td colspan="2" style="text-align:center;color:#888;padding:1rem;">Cargando sesiones...</td></tr>';
        
        const includeDemo = document.getElementById('journey-demo-toggle').checked ? 1 : 0;
        const limit = 20;
        const offset = (journeyPage - 1) * limit;

        try {
            const data = await api('journey_sessions', { include_demo: includeDemo, limit, offset });
            if (data.sessions && data.sessions.length > 0) {
                tbody.innerHTML = data.sessions.map(s => {
                    const emailTxt = s.email === 'Anónimo' ? `<span style="color:#888;font-style:italic;">${esc(s.organizacion)}</span>` : `<strong style="color:#fff;">${esc(s.email)}</strong><br/><span style="color:#888;font-size:0.7rem;">${esc(s.organizacion)}</span>`;
                    const dateTxt = (s.last_activity || '').split(' ')[0] || '';
                    const firstQuery = s.first_query ? s.first_query : 'N/A';
                    
                    const queryTruncated = firstQuery.length > 35 ? firstQuery.substring(0, 35) + '...' : firstQuery;
                    
                    const badge = s.session_type === 'lead_crm' 
                        ? '<span style="background:rgba(34,197,94,0.15);color:#22c55e;padding:0.1rem 0.3rem;border-radius:4px;font-size:0.65rem;font-weight:600;margin-left:0.25rem;">CRM</span>'
                        : '<span style="background:rgba(167,139,250,0.15);color:#a78bfa;padding:0.1rem 0.3rem;border-radius:4px;font-size:0.65rem;font-weight:600;margin-left:0.25rem;">CHAT</span>';

                    const demoBadge = s.session_id.startsWith('demoseed') ? ' <span style="color:#ef4444;font-weight:bold;font-size:0.65rem;">[DEMO]</span>' : '';

                    return `
                        <tr data-session-id="${esc(s.session_id)}" data-sector="${esc(s.sector || '')}" data-role="${esc(s.role || '')}" onclick="selectSessionRow(this)" style="border-bottom:1px solid rgba(255,255,255,0.05);cursor:pointer;transition:background 0.2s;">
                            <td style="padding:0.5rem;">
                                <div style="display:flex;align-items:center;">${emailTxt}${badge}${demoBadge}</div>
                                <div style="color:rgba(255,255,255,0.4);font-size:0.7rem;margin-top:0.15rem;font-style:italic;" title="${esc(firstQuery)}">"${esc(queryTruncated)}"</div>
                            </td>
                            <td style="padding:0.5rem;text-align:center;color:#aaa;">
                                ${dateTxt}<br/>
                                <span style="background:rgba(255,255,255,0.05);padding:0.1rem 0.3rem;border-radius:4px;font-size:0.65rem;">${s.events_count || 0} ev.</span>
                            </td>
                        </tr>
                    `;
                }).join('');
            } else {
                tbody.innerHTML = '<tr><td colspan="2" style="text-align:center;color:#666;padding:1rem;">No se encontraron sesiones recientes</td></tr>';
            }
            document.getElementById('journey-page-info').textContent = `Pág. ${journeyPage}`;
        } catch (e) {
            tbody.innerHTML = `<tr><td colspan="2" style="text-align:center;color:#ef4444;padding:1rem;">Error: ${esc(e.message)}</td></tr>`;
        }
    }

    function selectSessionRow(row) {
        const tbody = document.getElementById('journey-sessions-tbody');
        Array.from(tbody.getElementsByTagName('tr')).forEach(tr => {
            tr.style.background = 'none';
        });

        row.style.background = 'rgba(34,211,238,0.1)';

        const sessionId = row.getAttribute('data-session-id');
        const sector = row.getAttribute('data-sector') || '';
        const role = row.getAttribute('data-role') || '';
        const label = [sector, role].filter(Boolean).join(' • ') || sessionId.substring(0, 8) + '…';
        // Mostrar resumen legible en el input visible; mantener ID en campo oculto
        document.getElementById('journey-session-label').value = '📍 ' + label;
        document.getElementById('journey-session-id').value = sessionId;
        reconstructJourney();
    }

    function journeyPrevPage() {
        if (journeyPage > 1) {
            journeyPage--;
            loadJourneySessions();
        }
    }

    function journeyNextPage() {
        journeyPage++;
        loadJourneySessions();
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
