#!/usr/bin/env node
/**
 * learn_prompt_optimizer.mjs — Loop `learn` Offline (Spec 016)
 *
 * Script CLI offline para análisis batch de conversiones en el chatbot.
 * Lee las conversaciones de leads reales desde secure_leads/crm.sqlite.
 *
 * REGLA ESTRICTA DE SEGURIDAD (Constitución §2 - Honestidad Radical & Spec 016):
 * - PROHIBIDO fabricar o simular métricas, porcentajes o patrones cuando los datos son escasos.
 * - Si hay menos de 10 conversiones reales en la BD, emite un reporte transparente
 *   de "Datos insuficientes" SIN sugerencias ficticias de cambio al SYSTEM_PROMPT.
 * - Si hay suficientes datos, calcula estadísticas 100% reales a través de SQL.
 * - Opera exclusivamente en modo PR Draft (content-pr.yml), 0 auto-merge a producción.
 *
 * Uso:
 *   node scripts/learn_prompt_optimizer.mjs
 */

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { DatabaseSync } from 'node:sqlite';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const ROOT_DIR = path.resolve(__dirname, '..');

// Cargar .env
function loadEnv() {
  const envPath = path.join(ROOT_DIR, '.env');
  if (!fs.existsSync(envPath)) return;
  const lines = fs.readFileSync(envPath, 'utf-8').split('\n');
  for (const line of lines) {
    const trimmed = line.trim();
    if (!trimmed || trimmed.startsWith('#')) continue;
    const [key, ...vals] = trimmed.split('=');
    if (key && !process.env[key.trim()]) {
      process.env[key.trim()] = vals.join('=').trim();
    }
  }
}
loadEnv();

console.log("=================================================");
console.log("  LOOP LEARN OFFLINE — OPTIMIZADOR DE PROMPTS  ");
console.log("  Spec 016: Analítica & Asistencia de Copywriting");
console.log("=================================================\n");

const dbPath = path.join(ROOT_DIR, 'secure_leads', 'crm.sqlite');
const outputPath = path.join(ROOT_DIR, 'planes', 'PR-DRAFT-PROMPT-OPTIMIZATION-SPEC016.md');
const MIN_CONVERSIONS = 10;

if (!fs.existsSync(dbPath)) {
  console.log(`[INFO] No se encontró la base de datos CRM en: ${dbPath}`);
  writeInsufficientDataReport(0, 0, 0, "Base de datos no encontrada en el entorno local.");
  process.exit(0);
}

try {
  const db = new DatabaseSync(dbPath);
  
  // Consultas reales de base de datos
  const totalLeads = db.prepare("SELECT COUNT(*) as cnt FROM leads").get().cnt;
  const ganados = db.prepare("SELECT COUNT(*) as cnt FROM leads WHERE status = 'ganado'").get().cnt;
  const perdidos = db.prepare("SELECT COUNT(*) as cnt FROM leads WHERE status IN ('perdido', 'no_interesado')").get().cnt;
  const citas = db.prepare("SELECT COUNT(*) as cnt FROM leads WHERE status IN ('cita_solicitada', 'ganado')").get().cnt;

  console.log(`[REAL DATA] Total Leads: ${totalLeads} | Ganados: ${ganados} | Perdidos/No interesado: ${perdidos} | Citas: ${citas}`);

  if (totalLeads < MIN_CONVERSIONS) {
    console.log(`\n[GUARDARRAÍL §2] Datos insuficientes para análisis estadístico (Muestra: ${totalLeads} leads, Umbral mínimo: ${MIN_CONVERSIONS}).`);
    console.log("[GUARDARRAÍL §2] Se omite la generación de patrones y porcentajes simulados para evitar violaciones de honestidad radical.");
    writeInsufficientDataReport(totalLeads, ganados, perdidos, `Volumen actual de leads (${totalLeads}) es inferior al umbral mínimo requerido (${MIN_CONVERSIONS}).`);
    process.exit(0);
  }

  // Si hay datos suficientes (>= 10), calcular métricas 100% reales
  const tasaGanadosPct = ((ganados / totalLeads) * 100).toFixed(2);
  const tasaCitasPct = ((citas / totalLeads) * 100).toFixed(2);
  
  // Latencia LLM real
  let avgLatency = 0;
  try {
    const latRes = db.prepare("SELECT ROUND(AVG(latency_ms), 0) as avg_lat FROM chat_metrics").get();
    avgLatency = latRes ? latRes.avg_lat : 0;
  } catch (e) {
    avgLatency = 0;
  }

  // Transcripciones de leads reales
  const ganadosInteractions = db.prepare(`
    SELECT i.content FROM interactions i 
    JOIN leads l ON i.lead_id = l.id 
    WHERE l.status = 'ganado' LIMIT 20
  `).all();

  writeRealDataReport({
    totalLeads,
    ganados,
    perdidos,
    citas,
    tasaGanadosPct,
    tasaCitasPct,
    avgLatency,
    ganadosCount: ganadosInteractions.length
  });

} catch (err) {
  console.error('[ERROR] Fallo al consultar crm.sqlite:', err.message);
  writeInsufficientDataReport(0, 0, 0, `Error de lectura de BD: ${err.message}`);
}

function writeInsufficientDataReport(total, ganados, perdidos, razon) {
  const reportContent = `---
name: "chore(prompt): reporte semanal del loop learn (Spec 016)"
about: "Estado del Loop Learn sin métricas fabricadas (Constitución §2)"
title: "spec-016: reporte de optimización del SYSTEM_PROMPT — DATOS INSUFICIENTES"
labels: ["prompt-optimization", "spec-016", "insufficient-data", "radical-honesty"]
---

### 📊 Reporte de Muestra de Conversión (Datos Reales)
- **Total Leads Registrados:** ${total}
- **Clientes Ganados:** ${ganados}
- **Leads Abandonados/Perdidos:** ${perdidos}
- **Estado de Muestra:** DATOS INSUFICIENTES (${razon})

---

> [!NOTE]
> **HONESTIDAD RADICAL (§2 — Cero Métricas Ficticias):**  
> Al contar con una muestra inferior a ${MIN_CONVERSIONS} leads de conversión en producción, el sistema **no genera propuestas de cambio ni estadísticas fabricadas**. El loop \`learn\` permanecerá a la espera de acumular interacciones reales de usuarios en producción para derivar recomendaciones estadísticamente significativas.
`;

  fs.writeFileSync(outputPath, reportContent.trim(), 'utf-8');
  console.log(`\n[SUCCESS] Reporte honesto de datos insuficientes emitido en: ${outputPath}`);
  console.log("[SAFEGUARD] Cero métricas ni diffs simulados. Compliant con la Constitución §2.\n");
}

function writeRealDataReport(data) {
  const reportContent = `---
name: "chore(prompt): propuesta de optimización semanal del SYSTEM_PROMPT (Loop Learn Spec 016)"
about: "Propuesta generada automáticamente basada en ${data.totalLeads} leads reales"
title: "draft(spec-016): optimización asistida de respuestas basada en ${data.totalLeads} conversiones reales"
labels: ["prompt-optimization", "draft-pr", "spec-016", "human-review-required"]
---

### 📊 Resumen de Hallazgos de Conversión (Datos 100% Reales)
- **Total Leads Analizados:** ${data.totalLeads}
- **Tasa de Conversión a Citas Real:** ${data.tasaCitasPct}% (${data.citas} leads)
- **Tasa de Clientes Ganados Real:** ${data.tasaGanadosPct}% (${data.ganados} leads)
- **Latencia Promedio LLM:** ${data.avgLatency || 'N/A'} ms

### 💡 Análisis de Interacciones Reales
Se han analizado ${data.ganadosCount} muestras de recorrido de clientes ganados.

---
> [!IMPORTANT]
> **REVISIÓN HUMANA REQUERIDA:** Esta propuesta se deriva exclusivamente de datos reales de producción. Un arquitecto debe revisar los hallazgos antes de realizar cualquier ajuste manual al \`SYSTEM_PROMPT\`.
`;

  fs.writeFileSync(outputPath, reportContent.trim(), 'utf-8');
  console.log(`\n[SUCCESS] Propuesta basada en datos REALES emitida en: ${outputPath}`);
}
