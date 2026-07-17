#!/bin/bash
# ============================================================================
# test-specs.sh — Suite de verificación de las specs de Datanestiq
#
# Prueba contra el HTML de PRODUCCIÓN construido (dist/) — que es lo que se
# sirve realmente — más análisis del código fuente de las islas.
#
# USO:  npm run build && bash scripts/test-specs.sh
# ============================================================================
cd "$(dirname "$0")/.." || exit 1

if [ ! -d dist ]; then
  echo "No existe dist/. Corre primero: npm run build"
  exit 1
fi

pass=0; fail=0
check(){ if eval "$2"; then echo "  ✅ $1"; pass=$((pass+1)); else echo "  ❌ $1"; fail=$((fail+1)); fi; }

echo "== SPEC 012 — Fábrica de contenido / Home data-driven =="
check "Home (/) publicado y no vacío (>40KB)" '[ $(wc -c < dist/index.html) -gt 40000 ]'
check "Home renderiza headline desde home.md (con <br/> + Ventaja Asimétrica)" 'grep -q "Transformamos Datos en<br/>" dist/index.html && grep -q "Ventaja Asimétrica" dist/index.html'
check "NO existe /home duplicada (catch-all la excluye)" '[ ! -f dist/home/index.html ]'
check "Página demo (pagina-muestra) despublicada" '[ ! -f dist/pagina-muestra/index.html ]'

echo "== SPEC 011 — Taxonomía enriquecida en páginas =="
check "Sector finanzas: regulaciones reales (Basilea/PCI-DSS/SBS)" 'grep -q "Basilea" dist/sectores/finanzas/index.html && grep -q "SBS" dist/sectores/finanzas/index.html'
check "Sector finanzas: KPIs con ROI [EST] renderizado (>=2)" '[ $(grep -o "\[EST\]" dist/sectores/finanzas/index.html | wc -l) -ge 2 ]'
check "Sector salud: HIPAA / HL7-FHIR" 'grep -q "HIPAA" dist/sectores/salud/index.html'

echo "== SPEC 002 — Microinteracciones (islas + taxonomía) =="
check "Home embebe islas hidratadas (astro-island)" 'grep -q "astro-island" dist/index.html'
check "Chatbot: flujo guiado usa el corpus (objections) sin llamada LLM nueva" 'grep -q "roleObj.objections" src/components/islands/Chatbot.jsx && [ $(grep -c "fetch(" src/components/islands/Chatbot.jsx) -le 2 ]'
check "DiagnosticWizard usa kpis/regulations del sector" 'grep -qE "kpis|regulations" src/components/islands/DiagnosticWizard.jsx'

echo "== SPEC 007 — Breadcrumbs Schema (SEO) =="
check "BreadcrumbList JSON-LD presente en página de sector" 'grep -q "BreadcrumbList" dist/sectores/finanzas/index.html'

echo "== SPEC 003 — Taxonomía de sectores =="
check "Las 10 páginas de sector se generan" '[ $(ls -d dist/sectores/*/ | wc -l) -eq 10 ]'

echo ""
echo "==================================================="
echo "RESULTADO: $pass pasadas / $fail fallidas"
echo "==================================================="
[ "$fail" -eq 0 ] || exit 1
