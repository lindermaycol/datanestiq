#!/bin/bash
# Local testing for OpenWiki (Dry Run)

echo "Iniciando validación local (Dry Run) de OpenWiki..."

# Execute native Node script with dry-run
node scripts/docs-generator.mjs --target=wiki --dry-run --seed

echo "Simulación de actualización de servicios completada."
