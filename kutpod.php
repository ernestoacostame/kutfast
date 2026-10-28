<?php
/**
 * KutPod REST API client
 *
 * Auth:     POST /api/auth/login
 * Base URL: https://your-kutpod.com/api
 */
class KutPodAPI {
    private string $baseUrl;
    private ?string $token = null;
    private array  $tmpFiles = [];

    public function __construct(string $instanceUrl, ?string $token = null) {
        $this->baseUrl = rtrim($instanceUrl, '/') . '/api';
        $this->token   = $token;
    }
    public function __destruct() {
        foreach ($this->tmpFiles as $f) {
            if (file_exists($f)) unlink($f);
        }
    }

    public function authenticate(string $user, string $password): string {
        $url = $this->baseUrl . '/auth/login';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['user' => $user, 'password' => $password]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $raw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 400) {
            throw new Exception("KutPod Auth HTTP {$httpCode}: " . $raw);
        }
        $data = json_decode($raw, true);
        if (empty($data['token'])) {
            throw new Exception("KutPod login falló: " . $raw);
        }
        $this->token = $data['token'];
        return $this->token;
    }
    // -------------------------------------------------------
    // Public API
    // -------------------------------------------------------

    /** Returns all podcasts as array. Cached in session for 5 minutes. */
    public function getPodcasts(bool $forceRefresh = false): array {
        if (!$forceRefresh && !empty($_SESSION['kutpod_podcasts_cache']) && !empty($_SESSION['kutpod_podcasts_time'])) {
            if (time() - $_SESSION['kutpod_podcasts_time'] < 300) {
                return $_SESSION['kutpod_podcasts_cache'];
            }
        }
        $list = $this->request('GET', '/podcasts');
        if (is_array($list)) {
            $_SESSION['kutpod_podcasts_cache'] = $list;
            $_SESSION['kutpod_podcasts_time']  = time();
        }
        return $list;
    }

    /** Clear podcast cache */
    public function clearPodcastCache(): void {
        unset($_SESSION['kutpod_podcasts_cache'], $_SESSION['kutpod_podcasts_time']);
    }

    /** Resolve a slug like "my-podcast" to its numeric podcast id. */
    public function getPodcastIdByHandle(string $slug, bool $forceRefresh = false): int {
        $podcasts = $this->getPodcasts($forceRefresh);
        foreach ($podcasts as $p) {
            if (($p['slug'] ?? '') === $slug) {
                return (int) $p['id'];
            }
        }
        // If not found and not yet refreshed, try one force refresh before throwing
        if (!$forceRefresh) {
            return $this->getPodcastIdByHandle($slug, true);
        }
        throw new Exception(
            "No se encontró ningún podcast con el slug '{$slug}'. " .
            "Comprueba el valor de KUTPOD_PODCAST_SLUG en config.php. " .
            "Slugs disponibles: " . implode(', ', array_column($podcasts, 'slug'))
        );
    }
    
    // El frontend pide getPodcastById para info extra. Lo simulamos buscando en la lista.
    public function getPodcastById(int $id): array {
        $podcasts = $this->getPodcasts();
        foreach ($podcasts as $p) {
            if (($p['id'] ?? 0) == $id) {
                if (empty($p['title']) && !empty($p['name'])) {
                    $p['title'] = $p['name'];
                }
                if (empty($p['cover_url']) && !empty($p['cover'])) {
                    $p['cover_url'] = $p['cover'];
                }
                if (empty($p['host']) && !empty($p['author'])) {
                    $p['host'] = $p['author'];
                }
                return $p;
            }
        }
        return [];
    }

    /** Publish an existing draft episode in KutPod */
    public function publishDraft(int $podcastId, int $episodeId): array {
        return $this->request('PUT', "/podcasts/{$podcastId}/episodes/{$episodeId}", [], [
            'status'       => 'published',
            'published_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /** Returns only the most recent N episodes. */
    public function getRecentEpisodes(int $podcastId, int $limit = 20): array {
        $data = $this->request('GET', "/podcasts/{$podcastId}/episodes", [
            'per_page'   => $limit,
        ]);
        $episodes = $data['items'] ?? [];
        return $episodes;
    }

    /** Returns ALL episodes for a podcast including drafts, fetching all pages. */
    public function getAllEpisodes(int $podcastId, int $pageSize = 100): array {
        $all    = [];
        $page   = 1;
        while (true) {
            $data = $this->request('GET', "/podcasts/{$podcastId}/episodes", [
                'per_page'   => $pageSize,
                'page'       => $page,
            ]);

            $items = $data['items'] ?? [];
            if (empty($items)) break;
            $all = array_merge($all, $items);

            if ($page >= ($data['pages'] ?? 1)) break;
            $page++;
        }
        return $all;
    }

    /** Returns episodes for a podcast (by numeric id). */
    public function getEpisodesByPodcastId(int $podcastId): array {
        return $this->getAllEpisodes($podcastId);
    }

    /**
     * Full publish flow in KutPod:
     *   1. Resolve slug -> podcast_id
     *   2. POST /api/podcasts/{id}/episodes (creates and publishes/schedules)
     */
    public function publishEpisode(
        string  $podcastSlug,
        array   $fields,
        ?array  $audioFile,
        ?array  $coverFile
    ): array {
        // Step 1 - resolve podcast id
        $podcastId = $this->getPodcastIdByHandle($podcastSlug);
        
        // Step 2 - obtener nivel LUFS (por defecto -16)
        $lufsLevel = -16.0;
        if (!empty($fields['lufs_level'])) {
            $lufsLevel = (float) $fields['lufs_level'];
        } elseif (!empty($_POST['lufs_level'])) {
            $lufsLevel = (float) $_POST['lufs_level'];
        }
        
        // Convertir a MP3 con normalización LUFS
        if ($audioFile && !empty($audioFile['tmp_name'])) {
            $audioFile = $this->ensureMp3($audioFile, $lufsLevel);
            $this->validateAudioFile($audioFile);
        } elseif (empty($fields['audio_url'] ?? '')) {
            throw new Exception('Debes subir un archivo de audio o proporcionar una URL.');
        }

        // Field names for KutPod API (handlers/episode_create.php)
        $body = [
            'title'        => $fields['title'],
        ];

        if (!empty($fields['description'])) {
            $body['description'] = $fields['description'];
        }
        if (!empty($fields['episode_number'])) {
            $body['episode'] = (int) $fields['episode_number'];
        }
        if (!empty($fields['season_number'])) {
            $body['season'] = (int) $fields['season_number'];
        }
        if (!empty($fields['type'])) {
            $body['episode_type'] = $fields['type'];
        }
        if (!empty($fields['explicit'])) {
            $body['explicit'] = 1;
        }

        if (!empty($fields['published_at'])) {
            $ts = strtotime($fields['published_at']);
            if ($ts && $ts > time() + 60) {
                // Scheduled
                $body['publish_at'] = date('Y-m-d H:i:s', $ts);
            }
        }

        if ($audioFile && !empty($audioFile['tmp_name'])) {
            $body['audio'] = new CURLFile(
                $audioFile['tmp_name'], 'audio/mpeg', $audioFile['name']
            );
        } elseif (!empty($fields['audio_url'])) {
            throw new Exception("KutPod API requiere subir un archivo de audio local, URLs no permitidas.");
        }

        if ($coverFile && !empty($coverFile['tmp_name'])) {
            $body['cover'] = new CURLFile(
                $coverFile['tmp_name'],
                $coverFile['type'] ?: 'image/jpeg',
                $coverFile['name']
            );
        }

        $created = $this->request('POST', "/podcasts/{$podcastId}/episodes", [], $body, true);

        if (empty($created['id'])) {
            throw new Exception('KutPod creó el episodio pero no devolvió su ID. Respuesta: ' . json_encode($created));
        }

        return $created;
    }

    // -------------------------------------------------------
    // Audio processing: convert to MP3 + normalize to -16 LUFS
    // Uses two-pass EBU R128 loudnorm (same standard as Apple/Spotify)
    // Target: -16 LUFS integrated, -1.5 dBTP true peak, 11 LU range
    // Applied to ALL audio uploads, not just WebM recordings
    // -------------------------------------------------------
    private function ensureMp3(array $file, float $lufsLevel = -16.0): array {
        $debug = defined('KUTPOD_DEBUG') && KUTPOD_DEBUG;
        
        $ffmpeg = trim((string) shell_exec('which ffmpeg 2>/dev/null'));
        if (empty($ffmpeg)) {
            throw new Exception(
                'FFmpeg no está instalado. Instala con: sudo apt install ffmpeg'
            );
        }
        
        if (!is_dir(UPLOAD_TMP_DIR)) {
            mkdir(UPLOAD_TMP_DIR, 0750, true);
        }
        
        $input = $file['tmp_name'];
        if (!file_exists($input)) {
            throw new Exception('El archivo de audio no existe en la ubicación temporal: ' . $input);
        }
        if (filesize($input) === 0) {
            throw new Exception('El archivo de audio está vacío: ' . $input);
        }
        $normalizedName = preg_replace('/\.(webm|opus|ogg|mp3|m4a|wav|flac|aac)$/i', '.mp3', $file['name']);
        $out = UPLOAD_TMP_DIR . '/norm_' . uniqid() . '.mp3';
        
        // Validar nivel LUFS (entre -70 y 0)
        $lufsLevel = max(-70, min(0, $lufsLevel));
        $lufsStr = number_format($lufsLevel, 1, '.', '');
        if ($debug) error_log("[KutPodAPI] ensureMp3: input={$input}, LUFS={$lufsStr}");
        
        // Pass 1: medir estadísticas de loudness
        $measureFilter = "loudnorm=I={$lufsStr}:TP=-1.5:LRA=11:print_format=json";
        $measureCmd = sprintf(
            '%s -y -i %s -vn -af %s -f null /dev/null',
            escapeshellarg($ffmpeg),
            escapeshellarg($input),
            escapeshellarg($measureFilter)
        );
        exec($measureCmd . ' 2>&1', $measureLines, $measureExitCode);
        $measureOutput = implode("\n", $measureLines);
        if ($debug) error_log("[KutPodAPI] Pass 1 exit: {$measureExitCode}");
        if ($measureExitCode !== 0) {
            throw new Exception('FFmpeg falló al medir loudness. Log: ' . $measureOutput);
        }
        
        // Extraer JSON de la salida de FFmpeg
        $stats = null;
        if ($measureOutput && preg_match('/\{[^}]+\}/s', $measureOutput, $m)) {
            $stats = json_decode($m[0], true);
        }
        
        // Pass 2: aplicar normalización con valores medidos
        if ($stats &&
            isset($stats['input_i'], $stats['input_tp'],
                  $stats['input_lra'], $stats['input_thresh'],
                  $stats['target_offset'])) {
            // Normalización lineal de dos pasos - más precisa
            $filter = sprintf(
                'loudnorm=I=%s:TP=-1.5:LRA=11:measured_I=%s:measured_TP=%s:measured_LRA=%s:measured_thresh=%s:offset=%s:linear=true',
                $lufsStr,
                $stats['input_i'],
                $stats['input_tp'],
                $stats['input_lra'],
                $stats['input_thresh'],
                $stats['target_offset']
            );
        } else {
            // Fallback: un solo paso si falla la medición
            $filter = "loudnorm=I={$lufsStr}:TP=-1.5:LRA=11";
            if ($debug) error_log('[KutPodAPI] Using single-pass fallback filter');
        }
        
        $convertCmd = sprintf(
            '%s -y -i %s -vn -af %s -acodec libmp3lame -ab 192k -ar 44100 %s',
            escapeshellarg($ffmpeg),
            escapeshellarg($input),
            escapeshellarg($filter),
            escapeshellarg($out)
        );
        exec($convertCmd . ' 2>&1', $convertLines, $convertExitCode);
        $convertLog = implode("\n", $convertLines);
        if ($debug) error_log("[KutPodAPI] Pass 2 exit: {$convertExitCode}, size: " . (file_exists($out) ? filesize($out) : 'N/A'));
        
        if ($convertExitCode !== 0 || !file_exists($out) || filesize($out) < 1024) {
            throw new Exception(
                'FFmpeg no pudo procesar el audio.' . "\n" .
                'Código de salida: ' . $convertExitCode . "\n" .
                'Log completo de FFmpeg:' . "\n" . $convertLog
            );
        }
        
        $this->tmpFiles[] = $out;
        
        return [
            'tmp_name' => $out,
            'name'     => $normalizedName,
            'type'     => 'audio/mpeg',
            'size'     => filesize($out),
        ];
    }

    // -------------------------------------------------------
    // HTTP
    // -------------------------------------------------------
    private function request(
        string  $method,
        string  $endpoint,
        array   $query        = [],
        mixed   $body         = null,
        bool    $multipart    = false,
        bool    $formEncoded  = false
    ): array {
        if (empty($this->token)) {
            throw new Exception("Token de API no proporcionado.");
        }
        
        $url = $this->baseUrl . $endpoint;
        if ($query) $url .= '?' . http_build_query($query);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 600);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $headers = [
            'Accept: application/json',
            'Authorization: Bearer ' . $this->token,
        ];

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($body !== null) {
                if ($multipart) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                } elseif ($formEncoded) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($body));
                } else {
                    $headers[] = 'Content-Type: application/json';
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
                }
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, '');
            }
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($body !== null) {
                $headers[] = 'Content-Type: application/json';
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            }
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $raw      = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            throw new Exception("Error de conexión: {$curlErr}");
        }

        $decoded = json_decode($raw, true);

        if (defined('KUTPOD_DEBUG') && KUTPOD_DEBUG) {
            error_log("[KutPodAPI] {$method} {$url} → HTTP {$httpCode}: " . substr($raw, 0, 500));
        }

        if ($httpCode >= 400) {
            $msg = $decoded['messages']['error']
                ?? $decoded['message']
                ?? $decoded['error']
                ?? substr($raw, 0, 400);
            throw new Exception("KutPod HTTP {$httpCode} [{$method} {$endpoint}]: {$msg}");
        }

        return $decoded ?? [];
    }

    // -------------------------------------------------------
    // Helpers
    // -------------------------------------------------------
    private function validateAudioFile(array $file): void {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['mp3','ogg','wav','aac','flac','m4a','mp4','opus'])) {
            throw new Exception("Formato de audio no soportado: .{$ext}");
        }
        if ($file['size'] > MAX_AUDIO_SIZE) {
            throw new Exception('El archivo supera el límite de ' . round(MAX_AUDIO_SIZE/1024/1024) . ' MB.');
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $allowedMime = ['audio/mpeg', 'audio/ogg', 'audio/wav', 'audio/x-wav', 'audio/aac', 'audio/flac', 'audio/mp4', 'audio/x-m4a', 'audio/opus', 'audio/webm'];
        if (!$mime || !in_array($mime, $allowedMime)) {
            throw new Exception("Tipo MIME de audio no reconocido: {$mime}.");
        }
    }
}
