<?php
/**
 * pii.php — Helper de Sanitización y Redacción de PII (Constitución §6)
 * Compartido entre track_event.php, save_wizard.php, book_appointment.php y api.php
 */

if (!function_exists('redactPii')) {
    function redactPii($text) {
        if (empty($text)) return '';
        try {
            // Redactar Emails
            $text = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', '[EMAIL_REDACTED]', $text);
            // Redactar Teléfonos (patrón estándar internacional de 10+ dígitos)
            $text = preg_replace('/(\+?\d{1,3}[-.\s]?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4,}/', '[PHONE_REDACTED]', $text);
            // Móvil Perú: 9XX XXX XXX (con o sin +51 y separadores)
            $text = preg_replace('/(?<!\d)(\+?51[\s.\-]?)?9\d{2}[\s.\-]?\d{3}[\s.\-]?\d{3}(?!\d)/', '[PHONE_REDACTED]', $text);
            return $text;
        } catch (Exception $e) {
            // Guardarraíl fail-closed: Si la redacción falla, borramos el valor
            return '[REDACTION_ERROR]';
        }
    }
}
