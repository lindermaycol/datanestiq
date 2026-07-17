# Quickstart Validation: Expansión Multi-Página

Valida la generación estática (SSG) de las landing pages long-tail.

## 1. Validación de Rutas Dinámicas
```bash
# Compilar el proyecto en modo producción para verificar SSG
npm run build
```
1. Revisa la carpeta `dist/soluciones/`.
2. Deben existir carpetas o archivos HTML para cada id (ej. `dist/soluciones/ai-data-science/index.html`).

## 2. Validación SEO y Contexto
1. Ejecuta el preview de producción: `npm run preview`.
2. Navega a `http://localhost:4321/soluciones/ai-data-science`.
3. Abre las DevTools (F12) -> Elements -> Busca `<script type="application/ld+json">`. Verifica que el nombre del servicio coincida.
4. Haz clic en el botón principal "Inicia tu Diagnóstico".
5. Verifica que la URL del Wizard incluya el parámetro (ej. `/?servicio=ai-data-science` o `/diagnostico?servicio=ai-data-science`).
6. El Chatbot/Wizard debe abrirse y registrar automáticamente el interés inicial.
