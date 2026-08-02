#!/usr/bin/env node
/**
 * build-specs-status.mjs — Build Gate Antidrift para el Estado de Specs (Spec 017)
 *
 * Valida en tiempo de compilación que `src/data/specsStatus.json` (SSOT) no diverja
 * de la tabla de seguimiento humana en `planes/ESTADO-SPECS.md`.
 * Si se detecta *drift*, interrumpe la compilación con código de salida 1 (`process.exit(1)`).
 *
 * Uso:
 *   node scripts/build-specs-status.mjs
 */

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const ROOT_DIR = path.resolve(__dirname, '..');

const ssotPath = path.join(ROOT_DIR, 'src', 'data', 'specsStatus.json');
const estadoSpecsPath = path.join(ROOT_DIR, 'planes', 'ESTADO-SPECS.md');
const publicApiPath = path.join(ROOT_DIR, 'public', 'api', 'specs-status.json');

console.log("=================================================");
console.log("  BUILD-GATE ANTIDRIFT — ESTADO DE SPECS (017)   ");
console.log("=================================================\n");

if (!fs.existsSync(ssotPath)) {
  console.error(`[ERROR CRÍTICO] No se encontró el SSOT en: ${ssotPath}`);
  process.exit(1);
}

if (!fs.existsSync(estadoSpecsPath)) {
  console.error(`[ERROR CRÍTICO] No se encontró la tabla de seguimiento en: ${estadoSpecsPath}`);
  process.exit(1);
}

const specsJson = JSON.parse(fs.readFileSync(ssotPath, 'utf-8'));
const estadoMd = fs.readFileSync(estadoSpecsPath, 'utf-8');

let hasDrift = false;

for (const spec of specsJson) {
  const specId = spec.id;
  const jsonStatus = spec.status; // 'LIVE', 'DESIGNED', 'IN_PROGRESS', 'SUPERSEDED'

  // Buscar la fila correspondiente al ID de la spec en ESTADO-SPECS.md
  // Ejemplo de fila: | **016 (Analítica...)** | ✅ **DESPLEGADA Y AUDITADA...** |
  const regex = new RegExp(`\\|\\s*\\*\\*${specId}\\s*\\([^\\)]+\\)\\*\\*\\s*\\|\\s*([^\\|]+)\\|`, 'i');
  const match = estadoMd.match(regex);

  if (!match) {
    console.error(`[DRIFT DETECTADO] Spec ID ${specId} declarada en JSON SSOT no fue encontrada en ESTADO-SPECS.md.`);
    hasDrift = true;
    continue;
  }

  const estadoLineText = match[1];
  let mdStatusEquivalent = 'UNKNOWN';

  if (estadoLineText.includes('✅') || estadoLineText.includes('🟢')) {
    mdStatusEquivalent = 'LIVE';
  } else if (estadoLineText.includes('🟠')) {
    mdStatusEquivalent = 'DESIGNED';
  } else if (estadoLineText.includes('🔴')) {
    mdStatusEquivalent = 'SUPERSEDED';
  } else if (estadoLineText.includes('⏸️')) {
    mdStatusEquivalent = 'IN_PROGRESS';
  }

  // Verificar congruencia entre SSOT y Markdown
  const isMatch = (jsonStatus === mdStatusEquivalent) ||
                  (jsonStatus === 'LIVE' && mdStatusEquivalent === 'LIVE') ||
                  ((jsonStatus === 'DESIGNED' || jsonStatus === 'IN_PROGRESS') && (mdStatusEquivalent === 'DESIGNED' || mdStatusEquivalent === 'IN_PROGRESS'));

  if (!isMatch) {
    console.error(`[DRIFT ERROR] Inconsistencia en Spec ${specId} ('${spec.name}'):`);
    console.error(`  - Estado en src/data/specsStatus.json (SSOT): ${jsonStatus}`);
    console.error(`  - Estado en planes/ESTADO-SPECS.md: ${mdStatusEquivalent} (Texto: "${estadoLineText.trim().substring(0, 40)}...")`);
    hasDrift = true;
  }
}

if (hasDrift) {
  console.error("\n🔴 [BUILD FAILED] Se detectó desincronización (drift) entre specsStatus.json y ESTADO-SPECS.md.");
  console.error("   Corrija las diferencias antes de continuar. Cumplimiento estricto de Honestidad Radical (§2).\n");
  process.exit(1);
}

// Asegurar que la carpeta public/api exista y copiar el JSON limpio
const publicApiDir = path.dirname(publicApiPath);
if (!fs.existsSync(publicApiDir)) {
  fs.mkdirSync(publicApiDir, { recursive: true });
}
fs.writeFileSync(publicApiPath, JSON.stringify(specsJson, null, 2), 'utf-8');

console.log("[SUCCESS] Build-gate Antidrift PASSED 100%.");
console.log(`[SUCCESS] ${specsJson.length} especificaciones verificadas en SSOT y sincronizadas con ESTADO-SPECS.md.`);
console.log(`[SUCCESS] Artefacto estático emitido en: ${publicApiPath}\n`);
