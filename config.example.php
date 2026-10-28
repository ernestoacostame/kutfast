<?php
// ============================================================
//  KutFast - Configuracion
// ============================================================

// Contrasena de acceso a esta aplicacion
define('APP_PASSWORD', 'mi_contrasena_segura');

// URL de tu instancia de KutPod (sin barra final)
define('KUTPOD_URL', 'https://tu-kutpod.com');

// Credenciales REST API (usuario y contraseña de la cuenta en KutPod)
define('KUTPOD_API_USER',     'tu_usuario_api');
define('KUTPOD_API_PASSWORD', 'tu_password_api');

// Slug del podcast principal (el del config.php actua como default)
// Los demas podcasts se gestionan desde el panel
define('KUTPOD_PODCAST_SLUG', 'my-podcast');

// ID de tu usuario en KutPod

// Archivo JSON donde se guardan los podcasts adicionales (persistente)
define('PODCASTS_FILE',   __DIR__ . '/podcasts.json');

// Archivo JSON para borradores locales de episodios
define('DRAFTS_FILE',     __DIR__ . '/local_drafts.json');

// Archivo JSON para plantillas de descripcion
define('TEMPLATES_FILE',  __DIR__ . '/templates.json');

// Directorio temporal para uploads y conversiones
define('UPLOAD_TMP_DIR', __DIR__ . '/tmp');

// Tamano maximo de audio en bytes (500 MB)
define('MAX_AUDIO_SIZE', 500 * 1024 * 1024);

// Zona horaria
date_default_timezone_set('UTC'); // Cambia a tu zona horaria: https://www.php.net/manual/en/timezones.php

// Debug: muestra respuestas raw de la API en pantalla y error_log
define('KUTPOD_DEBUG', false);
// ============================================================
