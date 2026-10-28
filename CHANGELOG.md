# Changelog

Todos los cambios notables realizados en **KutFast** se documentan en este archivo.

El formato se basa en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/).

---

## [1.2.0] - 2026-10-26

### Añadido
* **Tarjeta Principal del Podcast en el Panel (`dashboard.php`):**
  * Rediseño completo de la cabecera informativa con una tarjeta moderna que integra portada en alta resolución, banner, título en tipografía *Plus Jakarta Sans*, badge `@slug`, identificación del host/presentador, descripción formateada, etiquetas de categoría e idioma, y accesos directos al Feed RSS y a la página pública en KutPod.
* **Miniaturas de Portada del Podcast en Listas y Encabezados:**
  * Visualización de la portada del podcast activo junto al título en cada fila de episodios en `episodes.php`, en el panel `dashboard.php`, en los borradores remotos `drafts.php` y en los borradores locales `local_drafts.php`.
  * Inclusión de miniatura de portada en las cabeceras principales (`page-head`) de cada sección.
* **Soporte PWA (Progressive Web App):**
  * Manifiesto de aplicación web (`manifest.json`) que permite instalar KutFast en pantalla completa como una app nativa en Android e iOS.
  * Service Worker ligero (`sw.js`) para registro y precaché de recursos estáticos.
  * Nuevo icono vectorial de alta resolución (`assets/icon.svg`).
* **Web Share Target API:**
  * Capacidad para compartir archivos de audio grabados desde otras aplicaciones móviles (grabadora de voz, mensajería, etc.) directamente hacia KutFast.
* **Arrastrar y Soltar Global (Global Drag & Drop):**
  * Detección de archivos de audio arrastrados sobre cualquier zona de la ventana con un overlay visual moderno y asignación automática al formulario de publicación.
* **Grabador Táctil Modernizado:**
  * Botón circular táctil prominente con animación de pulso rojo durante la grabación activa.
  * Visualizador de audio por barras de frecuencia (VU meter) en Canvas, adaptado a los colores de acento de los temas de la aplicación.
  * Indicador de estado en vivo (`EN VIVO`), tiempo prominente y badge de formato de compresión (`opus`, `webm`, etc.).
  * Controles de pausa/reanudación y descarte rápido de toma.
* **Layout Responsive de Dos Columnas en Escritorio:**
  * En pantallas de escritorio (`≥ 860px`), la pantalla de publicación se divide en dos columnas: controles de audio y metadatos a la izquierda; vista previa en vivo, plantillas y botón principal a la derecha.
* **Modo "Voz Rápida" (Quick Voice):**
  * Botón de atajo `⚡ + Fecha de hoy` para autocompletar el título con la fecha actual en un solo clic.
  * Generación automática de nota con fecha/hora si la descripción se deja en blanco, permitiendo publicación de notas de voz en 1 solo paso.
* **Autoguardado Local en el Navegador (`localStorage`):**
  * Guardado silencioso de campos para evitar pérdida de datos por cierres accidentales o llamadas entrantes en el móvil.
  * Banner de recuperación inteligente para restaurar o descartar borradores previos.
* **Reproductores de Audio Integrados:**
  * Botones de reproducción inline (Play/Pause) con selector de estado y enlaces directos de descarga en el panel y en la lista de episodios.
* **Publicación de Borradores de KutPod:**
  * Método `publishDraft()` en la clase `KutPodAPI` para permitir publicar borradores remotos existentes directamente desde la interfaz.
* **Respuesta Asíncrona JSON en Publicación:**
  * Procesamiento vía AJAX con reporte de porcentaje de carga en tiempo real y redirección automática sin recargar el DOM completo.

### Cambiado
* **Modernización Tipográfica:**
  * Reemplazo de tipografías por una pila moderna de alto impacto: `Inter` para texto base y elementos de interfaz, `Plus Jakarta Sans` para titulares y logotipo, y `JetBrains Mono` para metadatos, numeración de episodios, temporizadores y badges.
* **Canal de Audio Directo (Binario Multipart vs Base64):**
  * El audio grabado ahora se transfiere como un archivo binario nativo (`Blob`) directamente en el `FormData`, erradicando el cuello de botella de memoria y truncamiento por el límite `post_max_size` de PHP.
* **Reemplazo de `document.write()`:**
  * Eliminado el reemplazo destructivo del DOM tras subir audio; ahora se comunica con el servidor vía JSON estructurado.
* **Espaciado y Ancho de Pantalla:**
  * Aumentado el ancho máximo (`max-width`) en escritorio según la página (hasta 980px en publicación y 840px en panel) para un mejor aprovechamiento del monitor sin alterar la ergonomía en móviles.
* **Diseño de Pantalla de Acceso (`login.php`):**
  * Tarjeta de acceso rediseñada con el icono oficial de micrófono de KutStudio y atributos de autocompletado para gestores de contraseñas.

### Corregido
* **Visualización del Número de Episodio y Temporada en Listas:**
  * Corregido el mapeo de claves de la API de KutPod (`episode` y `season`), permitiendo que el número de episodio (`#1`, `#2`, etc.) y la temporada se muestren correctamente en la lista de episodios (`episodes.php`), panel principal (`dashboard.php`), borradores remotos (`drafts.php`) y borradores locales (`local_drafts.php`).
  * Corregido el cálculo automático del próximo episodio (`$nextEpisodeNumber`) y el mapeo por temporada (`$episodesBySeason`) en `index.php`.
* **Inconsistencia de Marca:**
  * Corregido el nombre `CastoPOST` por `KUTFAST` en la pantalla de login.
* **Acción de Publicar Borradores:**
  * Corregido el formulario de `templates/drafts.php` que apuntaba a una ruta inexistente; ahora procesa la publicación a través de la API de KutPod y ofrece acceso directo al panel admin.
* **Escape en Selector de Plantillas:**
  * Eliminado `addslashes()` en el elemento `<option>` de plantillas que provocaba la inserción de barras invertidas (`\'`, `\"`) en el textarea.

### Rendimiento
* **Caché de Podcasts en Sesión:**
  * Almacenamiento en caché de la lista de podcasts e identificadores en `$_SESSION['kutpod_podcasts_cache']` con TTL de 5 minutos, eliminando llamadas de red redundantes en cada cambio de vista.
* **Optimización de Canvas:**
  * Reducción de la resolución FFT a 256 en el analizador de audio móvil para minimizar el consumo de batería y CPU durante la grabación.

### Seguridad
* **Permisos de Archivos Temporales:**
  * Modificados los permisos de creación del directorio `/tmp` a `0750` en lugar de `0777`.
* **Protección de Sesión:**
  * Incorporadas directivas `HttpOnly`, `SameSite=Lax` y validación condicional de `Secure` en cookies de sesión antes de `session_start()`.
