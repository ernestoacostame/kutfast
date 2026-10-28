<?php
if (session_status() === PHP_SESSION_NONE) {
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    session_set_cookie_params([
        'lifetime' => 28800,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}
require_once 'config.php';
require_once 'auth.php';
require_once 'kutpod.php';
require_once 'podcasts_store.php';
require_once 'drafts_store.php';
require_once 'templates_store.php';

// ── CSRF Token ────────────────────────────────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

function verify_csrf(): bool {
    $token = $_POST['csrf_token'] ?? '';
    return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

$auth      = new Auth();
$kutpod    = new KutPodAPI(KUTPOD_URL, $auth->getToken());
$store     = new PodcastsStore(PODCASTS_FILE);
$draftSt   = new LocalDraftsStore(DRAFTS_FILE);
$templateSt= new TemplatesStore(TEMPLATES_FILE);

$page    = $_GET['page'] ?? 'dashboard';
$error   = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

// ── Logout ────────────────────────────────────────────────
if ($page === 'logout') { $auth->logout(); header('Location: index.php'); exit; }

// ── Login ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page === 'login') {
    $user = $_POST['user'] ?? '';
    $pass = $_POST['password'] ?? '';
    if ($auth->login($user, $pass, $kutpod)) {
        header('Location: index.php?page=dashboard'); exit;
    }
    $error = 'Usuario, contraseña inválidos o conexión fallida.';
}

// ── Guard ─────────────────────────────────────────────────
if ($page !== 'login' && !$auth->isLoggedIn()) {
    header('Location: index.php?page=login'); exit;
}

// ── Active podcast ────────────────────────────────────────
$allPodcasts   = $store->all();
$activePodcast = $_GET['podcast'] ?? $_SESSION['active_podcast'] ?? KUTPOD_PODCAST_SLUG;
$_SESSION['active_podcast'] = $activePodcast;

// ── Podcast store actions ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page === 'podcasts') {
    if (!verify_csrf()) { $error = 'Token CSRF inválido.'; }
    else {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name   = trim($_POST['name'] ?? '');
        $handle = trim($_POST['handle'] ?? '');
        if ($name && $handle) {
            try {
                $kutpod->getPodcastIdByHandle($handle);
                $store->add($name, $handle);
                $success = "Podcast '{$name}' anadido.";
            } catch (Exception $e) { $error = $e->getMessage(); }
        } else { $error = 'Nombre y handle son obligatorios.'; }
    } elseif ($action === 'delete') {
        $store->remove($_POST['handle'] ?? '');
        $success = 'Podcast eliminado.';
    }
    }
}

// ── Template actions ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page === 'templates') {
    if (!verify_csrf()) { $error = 'Token CSRF inválido.'; }
    else {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name = trim($_POST['tpl_name'] ?? '');
        $body = $_POST['tpl_body'] ?? '';
        if ($name && $body) {
            $templateSt->add($name, $body);
            $success = "Plantilla '{$name}' guardada.";
        } else { $error = 'Nombre y contenido son obligatorios.'; }
    } elseif ($action === 'update') {
        $templateSt->update($_POST['tpl_id'], trim($_POST['tpl_name'] ?? ''), $_POST['tpl_body'] ?? '');
        $success = 'Plantilla actualizada.';
    } elseif ($action === 'delete') {
        $templateSt->delete($_POST['tpl_id'] ?? '');
        $success = 'Plantilla eliminada.';
    }
    }
}

// ── Local draft actions ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page === 'save_draft') {
    if (!verify_csrf()) { $error = 'Token CSRF inválido.'; }
    else {
    $fields = [
        'title'          => trim($_POST['title'] ?? ''),
        'description'    => trim($_POST['description'] ?? ''),
        'slug'           => trim($_POST['slug'] ?? ''),
        'season_number'  => $_POST['season_number']  ?: '',
        'episode_number' => $_POST['episode_number'] ?: '',
        'episode_type'   => $_POST['episode_type']   ?? 'full',
        'published_at'   => $_POST['published_at']   ?? '',
        'explicit'       => !empty($_POST['explicit']) ? '1' : '',
        'draft_id'       => $_POST['draft_id']       ?? '',
        'lufs_level'     => $_POST['lufs_level']     ?? '-16',
    ];
    $id = $draftSt->save_draft($activePodcast, $fields);
    $success = 'Borrador guardado.';
    header("Location: index.php?page=publish&podcast=" . urlencode($activePodcast) . "&draft_id={$id}");
    exit;
    }
}

// ── Delete draft (POST only) ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($page === 'delete_draft') && $auth->isLoggedIn()) {
    if (verify_csrf()) {
        $draftSt->delete_draft($activePodcast, $_POST['draft_id'] ?? '');
    }
    header('Location: index.php?page=local_drafts&podcast=' . urlencode($activePodcast)); exit;
}

// ── Publish draft from KutPod ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page === 'publish_draft' && $auth->isLoggedIn()) {
    if (!verify_csrf()) {
        $error = 'Token CSRF inválido.';
    } else {
        $epId = (int)($_POST['episode_id'] ?? 0);
        if ($epId > 0) {
            try {
                $podId = $kutpod->getPodcastIdByHandle($activePodcast);
                $kutpod->publishDraft($podId, $epId);
                $success = "Borrador #{$epId} publicado correctamente.";
            } catch (Exception $e) {
                $error = 'Error al publicar borrador: ' . $e->getMessage();
            }
        }
    }
    header('Location: index.php?page=drafts&podcast=' . urlencode($activePodcast) . ($error ? '&error=' . urlencode($error) : '&success=' . urlencode($success)));
    exit;
}

// ── Publish episode ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page === 'publish') {
    $isAjax = !empty($_POST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    if (!verify_csrf()) {
        $error = 'Token CSRF inválido.';
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $error]);
            exit;
        }
    }
    else {
    try {
        $desc = trim($_POST['description'] ?? '');
        if ($desc === '') {
            $desc = 'Episodio publicado desde KutFast el ' . date('d/m/Y H:i');
        }

        $data = [
            'title'          => trim($_POST['title'] ?? ''),
            'description'    => $desc,
            'season_number'  => $_POST['season_number']  ?: null,
            'episode_number' => $_POST['episode_number'] ?: null,
            'type'           => $_POST['episode_type']   ?? 'full',
            'explicit'       => !empty($_POST['explicit']),
            'slug'           => trim($_POST['slug'] ?? ''),
            'published_at'   => $_POST['published_at']   ?? date('Y-m-d\TH:i:s'),
            'lufs_level'     => $_POST['lufs_level']     ?? '-16',
        ];
        if (empty($data['title'])) throw new Exception('El titulo es obligatorio.');

        $audioFile = null;
        if (!empty($_FILES['audio_file']['name']) && !empty($_FILES['audio_file']['tmp_name'])) {
            $audioFile = $_FILES['audio_file'];
        } elseif (!empty($_POST['recorded_audio_data'])) {
            $rawData = $_POST['recorded_audio_data'];
            if (defined('KUTPOD_DEBUG') && KUTPOD_DEBUG) error_log('[DEBUG] recorded_audio_data length: ' . strlen($rawData));

            if (strlen($rawData) < 100) {
                throw new Exception('Los datos de audio grabado son demasiado cortos (menos de 100 bytes). Posiblemente no se grabó correctamente.');
            }

            $base64 = preg_replace('/^data:[^;]+;base64,/', '', $rawData);
            if (empty($base64)) {
                throw new Exception('El data URL no contiene parte base64 válida.');
            }

            $cleanBase64 = str_replace(["\r", "\n", ' '], '', $base64);
            $decoded = base64_decode($cleanBase64, true);
            if ($decoded === false) {
                throw new Exception('Error al decodificar base64: los datos no son base64 válidos.');
            }
            $decodedLen = strlen($decoded);
            if (defined('KUTPOD_DEBUG') && KUTPOD_DEBUG) error_log('[DEBUG] Decoded size: ' . $decodedLen . ' bytes');
            if ($decodedLen < 100) {
                throw new Exception('El audio decodificado es demasiado pequeño (' . $decodedLen . ' bytes).');
            }

            $tmpDir = UPLOAD_TMP_DIR;
            if (defined('KUTPOD_DEBUG') && KUTPOD_DEBUG) error_log('[DEBUG] UPLOAD_TMP_DIR = ' . $tmpDir);
            if (!is_dir($tmpDir)) {
                if (defined('KUTPOD_DEBUG') && KUTPOD_DEBUG) error_log('[DEBUG] Creando directorio temporal: ' . $tmpDir);
                if (!mkdir($tmpDir, 0750, true)) {
                    throw new Exception('No se pudo crear el directorio temporal: ' . $tmpDir);
                }
                chmod($tmpDir, 0750);
            }

            $tmpPath = $tmpDir . '/rec_' . uniqid() . '.webm';
            if (defined('KUTPOD_DEBUG') && KUTPOD_DEBUG) error_log('[DEBUG] Intentando escribir archivo: ' . $tmpPath);
            $written = file_put_contents($tmpPath, $decoded, LOCK_EX);
            if ($written === false) {
                throw new Exception('Error al escribir el archivo de audio en ' . $tmpPath);
            }
            if (defined('KUTPOD_DEBUG') && KUTPOD_DEBUG) error_log('[DEBUG] Archivo escrito: ' . $tmpPath . ' - ' . $written . ' bytes');

            clearstatcache(true, $tmpPath);
            $fileSize = filesize($tmpPath);
            if (defined('KUTPOD_DEBUG') && KUTPOD_DEBUG) error_log('[DEBUG] filesize después de clearstatcache: ' . ($fileSize === false ? 'false' : $fileSize));
            if ($fileSize === false || $fileSize === 0) {
                unlink($tmpPath);
                throw new Exception('El archivo grabado está vacío después de escribir.');
            }

            $mimeFromPost   = $_POST['recorded_audio_mime'] ?? '';
            $mimeFromDataUrl = '';
            if (preg_match('/^data:([a-z]+\\/[a-z0-9.+-]+);base64,/', $rawData, $m)) {
                $mimeFromDataUrl = $m[1];
            }

            $finalMime = '';
            if (str_starts_with($mimeFromPost, 'audio/')) {
                $finalMime = $mimeFromPost;
            } elseif (str_starts_with($mimeFromDataUrl, 'audio/')) {
                $finalMime = $mimeFromDataUrl;
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $detectedMime = $finfo->file($tmpPath);
                if (str_starts_with($detectedMime, 'audio/')) {
                    $finalMime = $detectedMime;
                } else {
                    $finalMime = 'audio/webm';
                    if (defined('KUTPOD_DEBUG') && KUTPOD_DEBUG) error_log('[DEBUG] MIME no detectado, asumiendo audio/webm. Detected: ' . ($detectedMime ?: 'none'));
                }
            }

            $extMap = [
                'audio/webm'  => 'webm',
                'audio/ogg'   => 'ogg',
                'audio/mp4'   => 'mp4',
                'audio/mpeg'  => 'mp3',
                'audio/aac'   => 'aac',
                'audio/wav'   => 'wav',
                'audio/flac'  => 'flac',
                'audio/opus'  => 'opus',
                'audio/x-m4a' => 'm4a',
            ];
            $ext = $extMap[$finalMime] ?? 'webm';

            $audioName = $_POST['recorded_audio_name'] ?? 'grabacion.' . pathinfo($tmpPath, PATHINFO_EXTENSION);
            $audioName = preg_replace('/\\.[^.]+$/', '', $audioName) . '.' . $ext;

            if ($ext !== 'webm') {
                $newPath = preg_replace('/\\.webm$/', '.' . $ext, $tmpPath);
                if (rename($tmpPath, $newPath)) {
                    $tmpPath = $newPath;
                    if (defined('KUTPOD_DEBUG') && KUTPOD_DEBUG) error_log('[DEBUG] Archivo renombrado a: ' . $tmpPath);
                }
            }

            $audioFile = [
                'tmp_name' => $tmpPath,
                'name'     => $audioName,
                'type'     => $finalMime,
                'size'     => filesize($tmpPath),
                '_cleanup' => true,
            ];
            if (defined('KUTPOD_DEBUG') && KUTPOD_DEBUG) error_log('[DEBUG] Audio file listo: ' . json_encode($audioFile));
        } elseif (empty($_POST['audio_url'])) {
            throw new Exception('Debes grabar, subir o enlazar un archivo de audio.');
        } else {
            $data['audio_url'] = $_POST['audio_url'];
        }

        $coverFile     = !empty($_FILES['cover_image']['name']) ? $_FILES['cover_image'] : null;
        $podcastHandle = !empty($_POST['podcast_handle']) ? trim($_POST['podcast_handle']) : KUTPOD_PODCAST_SLUG;

        if (defined('KUTPOD_DEBUG') && KUTPOD_DEBUG) error_log('[DEBUG] Antes de llamar a publishEpisode. audioFile: ' . json_encode($audioFile));
        $result = $kutpod->publishEpisode($podcastHandle, $data, $audioFile, $coverFile);
        if (defined('KUTPOD_DEBUG') && KUTPOD_DEBUG) error_log('[DEBUG] Después de publishEpisode. Resultado: ' . json_encode($result));

        if (!empty($audioFile['_cleanup']) && file_exists($audioFile['tmp_name'])) {
            if (defined('KUTPOD_DEBUG') && KUTPOD_DEBUG) error_log('[DEBUG] Limpiando archivo temporal: ' . $audioFile['tmp_name']);
            unlink($audioFile['tmp_name']);
        }

        // Delete local draft if it was used
        if (!empty($_POST['draft_id'])) {
            $draftSt->delete_draft($podcastHandle, $_POST['draft_id']);
        }

        $success = 'Episodio publicado correctamente.' . (KUTPOD_DEBUG ? ' [debug] ' . json_encode($result) : '');

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success'  => true,
                'message'  => 'Episodio publicado correctamente.',
                'redirect' => 'index.php?page=dashboard&podcast=' . urlencode($podcastHandle)
            ]);
            exit;
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
        if (defined('KUTPOD_DEBUG') && KUTPOD_DEBUG) {
            error_log('[KutPodAPI] Excepción en publish: ' . $e->getMessage());
        }
        if (!empty($audioFile['_cleanup']) && !empty($audioFile['tmp_name']) && file_exists($audioFile['tmp_name'])) {
            unlink($audioFile['tmp_name']);
        }

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error'   => $error
            ]);
            exit;
        }
    }
    }
}

// ── Tmp cleanup (POST only) ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['deltmp']) && $auth->isLoggedIn()) {
    if (verify_csrf()) {
        $t = UPLOAD_TMP_DIR . '/' . basename($_POST['deltmp']);
        if (is_file($t)) unlink($t);
    }
    header('Location: index.php?page=dashboard&podcast=' . urlencode($activePodcast)); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['deltmpall']) && $auth->isLoggedIn()) {
    if (verify_csrf()) {
        foreach (glob(UPLOAD_TMP_DIR . '/*') as $f) { if (is_file($f)) unlink($f); }
    }
    header('Location: index.php?page=dashboard&podcast=' . urlencode($activePodcast)); exit;
}

// ── Load episodes/podcast info ────────────────────────────
$episodes = $drafts = [];
$podcastId   = null;
$podcastInfo = null;

if ($auth->isLoggedIn() && in_array($page, ['dashboard', 'episodes', 'drafts', 'publish', 'local_drafts'])) {
    try {
        $podcastId   = $kutpod->getPodcastIdByHandle($activePodcast);
        $podcastInfo = $kutpod->getPodcastById($podcastId);

        if (in_array($page, ['dashboard'])) {
            $all = $kutpod->getRecentEpisodes($podcastId, 5);
        } else {
            $all = $kutpod->getAllEpisodes($podcastId);
        }

        foreach ($all as $ep) {
            if (isset($ep['status']) && $ep['status'] === 'draft') {
                $drafts[] = $ep;
            } else {
                $episodes[] = $ep;
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// ── Next episode number per season ───────────────────────────────────
$nextEpisodeNumber = 1;
$episodesBySeason = []; // Nuevo: para guardar el último episodio por temporada
$lastSeason = null;
$lastEpisode = null;

if ($page === 'publish' && $podcastId) {
    try {
        $recent = $kutpod->getRecentEpisodes($podcastId, 20); // Aumentar para tener suficiente datos
        $nums = array_filter(array_map(fn($e) => $e['episode'] ?? $e['number'] ?? $e['episode_number'] ?? null, $recent));
        if ($nums) $nextEpisodeNumber = max($nums) + 1;
        
        // Calcular el último episodio por temporada
        foreach ($recent as $ep) {
            $season = $ep['season'] ?? $ep['season_number'] ?? null;
            $epNum = $ep['episode'] ?? $ep['number'] ?? $ep['episode_number'] ?? null;
            if ($season !== null && $epNum !== null) {
                if (!isset($episodesBySeason[$season]) || $epNum > $episodesBySeason[$season]) {
                    $episodesBySeason[$season] = $epNum;
                }
                // Calcular la última temporada y episodio
                if ($lastSeason === null || $season > $lastSeason || 
                    ($season == $lastSeason && $epNum > $lastEpisode)) {
                    $lastSeason = $season;
                    $lastEpisode = $epNum;
                }
            }
        }
    } catch (Exception $e) { /* keep defaults */ }
} elseif (!empty($episodes)) {
    $nums = array_filter(array_map(fn($e) => $e['episode'] ?? $e['number'] ?? $e['episode_number'] ?? null, $episodes));
    if ($nums) $nextEpisodeNumber = max($nums) + 1;
    
    // Calcular el último episodio por temporada desde todos los episodios
    foreach ($episodes as $ep) {
        $season = $ep['season'] ?? $ep['season_number'] ?? null;
        $epNum = $ep['episode'] ?? $ep['number'] ?? $ep['episode_number'] ?? null;
        if ($season !== null && $epNum !== null) {
            if (!isset($episodesBySeason[$season]) || $epNum > $episodesBySeason[$season]) {
                $episodesBySeason[$season] = $epNum;
            }
            // Calcular la última temporada y episodio
            if ($lastSeason === null || $season > $lastSeason || 
                ($season == $lastSeason && $epNum > $lastEpisode)) {
                $lastSeason = $season;
                $lastEpisode = $epNum;
            }
        }
    }
}

// Si encontramos una última temporada, calcular el próximo episodio
$defaultSeason = null;
$defaultEpisode = null;
if ($lastSeason !== null && $lastEpisode !== null) {
    $defaultSeason = $lastSeason;
    $defaultEpisode = $lastEpisode + 1;
}

// ── Load draft to edit if requested ──────────────────────
$editDraft = null;
if ($page === 'publish' && !empty($_GET['draft_id'])) {
    $editDraft = $draftSt->get_draft($activePodcast, $_GET['draft_id']);
    if ($editDraft === null) {
        // No se encontró el borrador: redirigir con error
        $error = 'No se encontró el borrador solicitado (ID: ' . htmlspecialchars($_GET['draft_id']) . '). Puede que haya sido eliminado o que el podcast no coincida.';
        // Opcional: eliminar el draft_id de la URL para evitar que se muestre como edición
        unset($_GET['draft_id']);
    }
}

// ── Local drafts for sidebar ──────────────────────────────
$localDrafts = $draftSt->get_drafts($activePodcast);
$templates   = $templateSt->all();

// ── Build nav podcast list ────────────────────────────────
$allPodcasts = $store->all();

// Pasar episodios por temporada y valores por defecto al template
$episodesBySeason = $episodesBySeason ?? [];
$defaultSeason = $defaultSeason ?? null;
$defaultEpisode = $defaultEpisode ?? null;

include 'templates/layout.php';
