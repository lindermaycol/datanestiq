# Instrucciones de Migración y Entorno Local (WordPress)

> [!TIP]
> **Para el agente de la nueva sesión:** Este documento contiene las instrucciones completas y el plan de implementación para descargar y configurar el entorno local del sitio web WordPress (`datanestiq.com`), alojado en un servidor compartido IONOS.

## Contexto del Proyecto Nuevo
- **Directorio de Trabajo:** `C:\xampp\htdocs\datanestiq` para alojar los archivos del sitio web WordPress.
- **Entorno Local (XAMPP):** 
  - Se recomienda instalar **XAMPP con PHP 8.2**, ya que es la versión óptima, más segura y recomendada para las instalaciones de WordPress modernas, además de ser altamente compatible con servidores compartidos como IONOS.

> [!IMPORTANT]
> ## User Review Required / Open Questions
> Para poder ejecutar este plan de manera exitosa, necesito la siguiente información y confirmaciones de tu parte:
> 1. **Credenciales SSH/SFTP:** Necesito la contraseña o la llave privada para el usuario `a2533622` en el host `access-5017755440.webspace-host.com` para poder conectarme y extraer los archivos y la base de datos de IONOS.
> 2. **Estado de XAMPP:** ¿Ya tienes instalado XAMPP (con PHP 8.2) y están corriendo los servicios de Apache y MySQL en tu equipo local?
> 3. **WP-CLI (Opcional pero recomendado):** Para no romper datos serializados, lo ideal es usar WP-CLI para el reemplazo de URLs. ¿Te parece bien si lo instalo localmente o prefieres usar un script PHP de Search & Replace como "interconnect/it"?
> 4. **Control de Versiones (Git):** ¿Te gustaría que inicialice un repositorio Git en la carpeta de tu tema (`wp-content/themes/...`) para llevar un control estricto de las mejoras y cambios de código antes de enviarlos a producción?

## Mejoras Propuestas (Insights de Experto en WP, UX y SEO)
Al revisar tu plan original, propongo incorporar las siguientes mejoras para asegurar la calidad y estabilidad del proyecto:
1. **Manejo Seguro de URLs Serializadas:** WordPress guarda las configuraciones y datos de constructores visuales en formato serializado. Al cambiar `datanestiq.com` a `localhost/datanestiq`, un simple Query SQL corromperá el sitio. Utilizaremos WP-CLI o una herramienta especializada en WP para el reemplazo seguro.
2. **SEO y Entorno Local:** Aseguraremos que el entorno local esté configurado correctamente para no indexar (`WP_ENVIRONMENT_TYPE` en 'local'). 
3. **Control de Versiones:** Propondré crear un repositorio Git para el código personalizado (Tema hijo o plugins propios) en lugar de subir archivos sueltos "a ciegas". Esto evita pérdida de código y facilita deshacer errores.
4. **Permisos y Seguridad (Deploy):** Al subir archivos de vuelta a IONOS, nos aseguraremos de que los permisos (644 para archivos, 755 para carpetas) se mantengan intactos.
5. **Auditoría UX/SEO:** Una vez que el sitio esté corriendo en local, realizaré una pequeña auditoría de usabilidad (tiempos de carga, jerarquía HTML, navegación) y te daré sugerencias puntuales para mejorar.

## Proposed Changes (Instrucciones para el Agente)

### 1. Exploración y Respaldo vía SSH
Utilizando scripts o herramientas SSH, el agente deberá:
- Conectarse al servidor IONOS.
- Explorar el directorio de usuario para encontrar el directorio raíz de WordPress (buscar el archivo `wp-config.php`).
- Leer el `wp-config.php` del servidor para extraer las credenciales de la base de datos de producción (`DB_NAME`, `DB_USER`, `DB_PASSWORD`).
- Realizar un volcado (`mysqldump`) de la base de datos mediante SSH.
- Comprimir la carpeta de la instalación (o idealmente solo `wp-content` y archivos clave) y el archivo `.sql`, para descargarlos a la nueva carpeta local `C:\xampp\htdocs\datanestiq`.

### 2. Instalación en XAMPP (Local)
- Validar que Apache y MySQL estén corriendo en XAMPP local.
- Descargar el Core limpio de WordPress en su última versión y colocarlo en `C:\xampp\htdocs\datanestiq`.
- Reemplazar la carpeta `wp-content` del core por la carpeta extraída del servidor.
- Crear una nueva base de datos local y realizar la importación del archivo `.sql`.
- Generar el archivo `wp-config.php` local con las credenciales locales (usuario `root`, sin contraseña por defecto en XAMPP).
- Ejecutar WP-CLI (o la herramienta elegida) para cambiar todas las ocurrencias del dominio de producción (`https://datanestiq.com`) a la URL local (ej. `http://localhost/datanestiq`).

### 3. Desarrollo y Despliegue (Deploy)
- El usuario podrá visualizar el sitio en `http://localhost/datanestiq` y solicitar modificaciones (CSS, funciones del tema, usabilidad, etc.).
- Tras desarrollar y probar las mejoras, el agente subirá los cambios al servidor de IONOS vía SSH/SFTP.
- **Regla de Oro:** Solo se subirán los **archivos modificados** del código. Bajo ninguna circunstancia se sobreescribirá la base de datos de producción con datos del entorno local, para no perder información de los usuarios.

## Verification Plan

### Verificaciones Locales
- Comprobar que los puertos 80/443 (Apache) y 3306 (MySQL) están en operación.
- Asegurar que la carga de `http://localhost/datanestiq` sea exitosa, sin errores 500 ni problemas de conexión a la base de datos.
- Confirmar que la navegación interna y las imágenes apunten correctamente al dominio `localhost`.

### Verificaciones de Deploy a Producción
- Antes del primer despliegue real, subir un cambio menor (ej. un comentario de CSS) para verificar la correcta autenticación, permisos y rutas en IONOS.
- Comprobar visualmente el sitio en vivo para confirmar que el despliegue no generó incompatibilidades.
