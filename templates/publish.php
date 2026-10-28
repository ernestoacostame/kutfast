<?php
// Load values from local draft if editing one
$v = $editDraft ?? $_POST;
$draftId = $editDraft['draft_id'] ?? '';
$podcastCover = $podcastInfo['cover_url'] ?? $podcastInfo['cover']['url'] ?? $podcastInfo['cover'] ?? '';
if (!empty($podcastCover) && !str_starts_with($podcastCover, 'http')) {
    $podcastCover = rtrim(KUTPOD_URL, '/') . '/' . ltrim($podcastCover, '/');
}
?>
<div class="page-head fade">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem">
    <div style="display:flex;align-items:center;gap:.75rem">
      <?php if (!empty($podcastCover)): ?>
      <img src="<?= htmlspecialchars($podcastCover) ?>" style="width:42px;height:42px;border-radius:8px;object-fit:cover;border:1px solid var(--line);box-shadow:0 2px 8px rgba(0,0,0,.2)" alt="cover">
      <?php endif; ?>
      <div>
        <h1><?= $draftId ? 'Editar borrador' : 'Nuevo episodio' ?></h1>
        <p>Publicando en <strong><?= htmlspecialchars($activePodcast) ?></strong></p>
      </div>
    </div>
    <div style="display:flex;gap:.4rem;align-items:center">
      <button type="button" class="btn btn-ghost btn-sm" id="btnQuickTitle" title="Rellenar título con fecha de hoy">
        ⚡ + Fecha de hoy
      </button>
      <?php if (!empty($localDrafts)): ?>
      <a href="index.php?page=local_drafts&podcast=<?= urlencode($activePodcast) ?>" class="btn btn-ghost btn-sm" title="Ver borradores guardados">
        📁 Borradores (<?= count($localDrafts) ?>)
      </a>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- LOCAL AUTOSAVE RESTORE BANNER -->
<div class="autosave-banner fade" id="autosaveBanner">
  <div style="display:flex;align-items:center;gap:.5rem">
    <span>💾</span>
    <span>Tienes un borrador no guardado de tu sesión anterior en este dispositivo.</span>
  </div>
  <div style="display:flex;gap:.4rem">
    <button type="button" class="btn btn-primary btn-sm" id="btnRestoreAutosave" style="padding:.2rem .6rem;font-size:.75rem">Restaurar</button>
    <button type="button" class="btn btn-ghost btn-sm" id="btnDiscardAutosave" style="padding:.2rem .5rem;font-size:.75rem">Descartar</button>
  </div>
</div>

<?php
$ffmpegAvailable = !empty(trim((string) shell_exec('which ffmpeg 2>/dev/null')));
if (!$ffmpegAvailable): ?>
<div class="alert alert-err fade" style="border-color:rgba(248,113,113,.4)">
  ⚠ FFmpeg no encontrado. La conversión y normalización de audio no funcionará.
  Instala con: <code style="background:var(--bg3);padding:.15rem .4rem;border-radius:3px">sudo apt install ffmpeg</code>
</div>
<?php endif; ?>

<!-- MAIN PUBLISH FORM (GRID) -->
<form method="POST" action="index.php?page=publish&podcast=<?= urlencode($activePodcast) ?>" enctype="multipart/form-data" id="publishForm">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
  <input type="hidden" name="podcast_handle" value="<?= htmlspecialchars($activePodcast) ?>">
  <input type="hidden" name="draft_id" value="<?= htmlspecialchars($draftId) ?>">

  <div class="publish-grid">
    <!-- LEFT COLUMN: AUDIO & DETAILS -->
    <div class="publish-main-col">
      <!-- AUDIO SECTION -->
      <div class="group-label">1. Audio del episodio</div>

      <div class="recorder fade" id="recorderBox">
        <div class="rec-head">
          <div class="rec-badge">
            <span class="rec-dot" id="recLiveDot"></span>
            <span id="recStatusBadge">Micrófono</span>
          </div>
          <span style="font-size:.7rem;font-family:'Noto Sans Mono',monospace;color:var(--text3)" id="recMimeBadge">listo</span>
        </div>

        <canvas class="rec-canvas" id="recCanvas"></canvas>
        <div class="rec-timer" id="recTimer">00:00:00</div>
        <div class="rec-status" id="recStatus">Pulsa para iniciar la grabación</div>

        <div class="rec-controls-row">
          <button type="button" class="rec-btn-sub" id="btnDiscard" style="display:none" title="Descartar grabación">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
          </button>
          
          <button type="button" class="rec-btn-circle" id="btnRec" title="Grabar">
            <svg id="recIconMic" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="22"/></svg>
            <svg id="recIconStop" width="26" height="26" viewBox="0 0 24 24" fill="currentColor" style="display:none"><rect x="5" y="5" width="14" height="14" rx="2"/></svg>
          </button>

          <button type="button" class="rec-btn-sub" id="btnPause" disabled title="Pausar / Reanudar">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
          </button>
        </div>

        <audio id="recPlayback" controls style="display:none;margin-top:1rem"></audio>
        <input type="hidden" name="recorded_audio_name" id="recName">
        <input type="hidden" name="recorded_audio_mime" id="recMime">
      </div>

      <div class="or-line" style="margin:1rem 0">o sube un archivo de audio</div>

      <div class="field">
        <div class="upload-zone" id="audioUploadZone">
          <input type="file" name="audio_file" id="audioFileInput" accept="audio/*,.mp3,.ogg,.wav,.aac,.flac,.m4a,.opus,.webm">
          <div class="upload-icon">&#127911;</div>
          <div class="upload-main">Arrastra aquí tu audio o pulsa para seleccionar</div>
          <div class="upload-sub">MP3, M4A, WAV, FLAC, OGG, WebM &middot; máx <?= round(MAX_AUDIO_SIZE/1024/1024) ?> MB</div>
          <div class="upload-name" id="audioFileName"></div>
        </div>
      </div>

      <details style="margin-bottom:1.25rem">
        <summary style="font-size:.78rem;color:var(--text3);cursor:pointer;margin-bottom:.5rem">O usar URL remota de audio</summary>
        <div class="field" style="margin-top:.4rem">
          <input type="url" id="audio_url" name="audio_url" placeholder="https://example.com/audio.mp3" value="<?= htmlspecialchars($v['audio_url'] ?? '') ?>">
        </div>
      </details>

      <div class="divider"></div>

      <!-- METADATA SECTION -->
      <div class="group-label">2. Detalles de publicación</div>

      <div class="field">
        <label for="ep-title">Título del episodio <span class="req">*</span></label>
        <input type="text" id="ep-title" name="title" required placeholder="Ej: Episodio 42: Novedades semanales" value="<?= htmlspecialchars($v['title'] ?? '') ?>">
      </div>

      <div class="field">
        <label for="ep-slug">Slug <span class="sub">(identificador URL auto-generado)</span></label>
        <input type="text" id="ep-slug" name="slug" placeholder="episodio-42-novedades" value="<?= htmlspecialchars($v['slug'] ?? '') ?>">
      </div>

      <div class="field-row">
        <div class="field">
          <label for="ep-season">Temporada</label>
          <input type="number" id="ep-season" name="season_number" min="1" placeholder="1" 
                 value="<?= htmlspecialchars($v['season_number'] ?? $defaultSeason ?? '') ?>">
        </div>
        <div class="field">
          <label for="ep-num">Episodio <span class="sub">(auto según temporada)</span></label>
          <input type="number" id="ep-num" name="episode_number" min="0"
                 placeholder="Auto"
                 value="<?= htmlspecialchars($v['episode_number'] ?? $defaultEpisode ?? '') ?>">
        </div>
      </div>

      <div class="field-row">
        <div class="field">
          <label for="ep-type">Tipo</label>
          <select id="ep-type" name="episode_type">
            <option value="full"    <?= ($v['episode_type']??'full')==='full'   ?'selected':'' ?>>Full (Completo)</option>
            <option value="trailer" <?= ($v['episode_type']??'')==='trailer'    ?'selected':'' ?>>Trailer</option>
            <option value="bonus"   <?= ($v['episode_type']??'')==='bonus'      ?'selected':'' ?>>Bonus</option>
          </select>
        </div>
        <div class="field">
          <label for="ep-date">Fecha y hora de publicación</label>
          <input type="datetime-local" id="ep-date" name="published_at" value="<?= htmlspecialchars($v['published_at'] ?? date('Y-m-d\TH:i')) ?>">
        </div>
      </div>

      <div class="field">
        <label class="check-row">
          <input type="checkbox" name="explicit" <?= !empty($v['explicit'])?'checked':'' ?>>
          Contenido explícito (Parental Advisory)
        </label>
      </div>

      <div class="field">
        <label>Portada específica del episodio <span class="sub">(opcional)</span></label>
        <div class="upload-zone" style="padding:1rem">
          <input type="file" name="cover_image" accept="image/jpeg,image/png,image/webp">
          <div class="upload-icon">&#128444;</div>
          <div class="upload-main" style="font-size:.78rem">Imagen de portada</div>
          <div class="upload-sub">JPG, PNG &middot; mín 1400x1400 px</div>
          <div class="upload-name"></div>
        </div>
      </div>

      <!-- LUFS NORMALIZATION -->
      <div class="divider"></div>
      <div class="group-label">Normalización de audio (LUFS)</div>
      <div class="field">
        <p style="margin-top:0;margin-bottom:.5rem;font-size:.8rem;color:var(--text3)">
          EBU R128 estándar: Podcasts (-16), Música (-14), Radio (-18)
        </p>
        
        <div class="lufs-buttons" style="display:flex;gap:.4rem;margin-bottom:.6rem;flex-wrap:wrap">
          <button type="button" class="btn btn-ghost btn-sm lufs-btn" data-lufs="-14">-14 LUFS</button>
          <button type="button" class="btn btn-ghost btn-sm lufs-btn active" data-lufs="-16">-16 (Estándar)</button>
          <button type="button" class="btn btn-ghost btn-sm lufs-btn" data-lufs="-18">-18 LUFS</button>
          <button type="button" class="btn btn-ghost btn-sm lufs-btn" data-lufs="-24">-24 LUFS</button>
        </div>
        
        <div style="display:flex;align-items:center;gap:.5rem">
          <label for="lufs_custom" style="font-size:.8rem">Personalizado:</label>
          <input type="number" id="lufs_custom" 
                 name="lufs_custom" 
                 min="-70" max="0" step="0.1"
                 placeholder="-16.0"
                 style="width:110px;padding:.3rem .5rem;font-size:.85rem">
          <span style="font-size:.75rem;color:var(--text3)">LUFS</span>
        </div>
      </div>
      <input type="hidden" name="lufs_level" id="lufs_level" value="-16">
    </div>

    <!-- RIGHT COLUMN: STICKY PREVIEW, DESCRIPTION & PUBLISH CTA -->
    <div class="publish-sticky-col">
      <!-- DESCRIPTION with templates -->
      <div class="group-label">3. Notas y descripción</div>
      <div class="field">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.35rem">
          <label for="ep-desc" style="margin:0">Descripción</label>
          <?php if (!empty($templates)): ?>
          <div style="display:flex;align-items:center;gap:.35rem">
            <select id="tplPicker" style="padding:.2rem .5rem;font-size:.72rem;background:var(--bg3);border-color:var(--line2);color:var(--text2);width:auto">
              <option value="">Plantilla...</option>
              <?php foreach ($templates as $tpl): ?>
              <option value="<?= htmlspecialchars($tpl['description'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($tpl['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <a href="index.php?page=templates" class="btn btn-ghost btn-sm" style="padding:.18rem .45rem;font-size:.7rem" target="_blank">Editar</a>
          </div>
          <?php endif; ?>
        </div>
        <textarea id="ep-desc" name="description" placeholder="Notas del episodio, enlaces, créditos... (Si lo dejas vacío, se genera una nota automática con fecha)"><?= htmlspecialchars($v['description'] ?? '') ?></textarea>
      </div>

      <!-- LIVE PREVIEW CARD -->
      <div class="group-label">Vista previa en tiempo real</div>
      <div class="preview-box fade" id="previewBox" style="box-shadow:0 6px 20px rgba(0,0,0,.15);border-radius:12px;margin-bottom:1rem">
        <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.65rem">
          <div style="width:40px;height:40px;border-radius:8px;background:var(--bg3);display:flex;align-items:center;justify-content:center;color:var(--accent);font-size:1.2rem;flex-shrink:0">
            &#127911;
          </div>
          <div style="min-width:0;flex:1">
            <div class="preview-title" id="prevTitle" style="font-size:.95rem;line-height:1.2">--</div>
            <div class="preview-meta" id="prevMeta" style="font-size:.68rem;margin:0">--</div>
          </div>
        </div>
        <div class="preview-desc" id="prevDesc" style="font-size:.78rem;max-height:140px;overflow-y:auto;line-height:1.5"></div>
      </div>

      <!-- ACTIONS -->
      <div style="display:flex;flex-direction:column;gap:.6rem">
        <button type="submit" class="btn btn-primary" id="btnPublish" style="padding:.75rem 1.4rem;font-size:.95rem;font-weight:600">
          🚀 Publicar episodio
        </button>
        <div style="display:flex;gap:.5rem">
          <button type="button" class="btn btn-ghost" id="btnSaveDraft" style="flex:1">
            💾 Guardar borrador
          </button>
          <a href="index.php?page=dashboard&podcast=<?= urlencode($activePodcast) ?>" class="btn btn-ghost" style="padding:.6rem .9rem">
            Cancelar
          </a>
        </div>
      </div>

      <!-- Upload progress overlay -->
      <div id="uploadOverlay" style="display:none;margin-top:.75rem;padding:1rem;background:var(--bg2);border:1px solid var(--line2);border-radius:var(--r);text-align:center">
        <div id="uploadStatus" style="font-size:.85rem;color:var(--text2);margin-bottom:.5rem">Subiendo audio...</div>
        <div style="width:100%;height:6px;background:var(--bg3);border-radius:3px;overflow:hidden">
          <div id="uploadBar" style="width:0%;height:100%;background:var(--accent);border-radius:3px;transition:width .2s"></div>
        </div>
        <div id="uploadPercent" style="font-size:.72rem;color:var(--text3);margin-top:.3rem;font-family:'Noto Sans Mono',monospace">0%</div>
      </div>

      <?php if ($draftId): ?>
      <p style="font-size:.75rem;color:var(--green);margin-top:.4rem;text-align:center">Editando borrador guardado el <?= htmlspecialchars($editDraft['saved_at'] ?? '') ?></p>
      <?php endif; ?>
    </div>
  </div>
</form>

<!-- SAVE DRAFT FORM (hidden, submitted by JS) -->
<form method="POST" action="index.php?page=save_draft&podcast=<?= urlencode($activePodcast) ?>" id="draftForm" style="display:none">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
  <input type="hidden" name="draft_id" id="draftFormId" value="<?= htmlspecialchars($draftId) ?>">
  <input type="hidden" name="title"          id="df_title">
  <input type="hidden" name="description"    id="df_desc">
  <input type="hidden" name="slug"           id="df_slug">
  <input type="hidden" name="season_number"  id="df_season">
  <input type="hidden" name="episode_number" id="df_num">
  <input type="hidden" name="episode_type"   id="df_type">
  <input type="hidden" name="published_at"   id="df_date">
  <input type="hidden" name="explicit"       id="df_explicit">
  <input type="hidden" name="audio_url"      id="df_audiourl">
  <input type="hidden" name="lufs_level"     id="df_lufs" value="-16">
</form>

<script>
// Check for MediaRecorder support
const recorderBox = document.getElementById('recorderBox');
if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
  if (recorderBox) recorderBox.innerHTML = '<div style="color:var(--text3);padding:1rem;text-align:center">Microfono no disponible en este navegador o contexto inseguro (requiere HTTPS en producción).</div>';
}

// ── MODERN RECORDER LOGIC (DIRECT BLOB MULTIPART) ─────────
(function(){
  let mr, chunks=[], stream, raf, secs=0, iv, paused=false, analyser, dataArr;
  window.recordedAudioBlob = null;

  const btnRec=document.getElementById('btnRec'),
        btnPause=document.getElementById('btnPause'),
        btnDisc=document.getElementById('btnDiscard'),
        timer=document.getElementById('recTimer'),
        status=document.getElementById('recStatus'),
        playback=document.getElementById('recPlayback'),
        recName=document.getElementById('recName'),
        recMime=document.getElementById('recMime'),
        liveDot=document.getElementById('recLiveDot'),
        mimeBadge=document.getElementById('recMimeBadge'),
        iconMic=document.getElementById('recIconMic'),
        iconStop=document.getElementById('recIconStop'),
        canvas=document.getElementById('recCanvas'),
        ctx=canvas.getContext('2d');

  function fmt(s){ return [Math.floor(s/3600),Math.floor((s%3600)/60),s%60].map(n=>String(n).padStart(2,'0')).join(':'); }

  function draw(){
    if(!analyser) return; 
    raf = requestAnimationFrame(draw);
    analyser.getByteFrequencyData(dataArr);
    const W = canvas.width, H = canvas.height;
    ctx.clearRect(0,0,W,H);

    const barWidth = 4;
    const gap = 3;
    const totalBars = Math.floor(W / (barWidth + gap));
    const step = Math.floor(dataArr.length / totalBars) || 1;
    const accent = window.getAccentColor ? window.getAccentColor() : '#3d9be9';

    for(let i = 0; i < totalBars; i++){
      const val = dataArr[i * step] || 0;
      const barH = Math.max(3, (val / 255) * H * 0.88);
      const x = i * (barWidth + gap);
      const y = (H - barH) / 2;
      
      ctx.fillStyle = paused ? 'rgba(150,150,160,0.3)' : accent;
      ctx.beginPath();
      ctx.roundRect ? ctx.roundRect(x, y, barWidth, barH, 2) : ctx.rect(x, y, barWidth, barH);
      ctx.fill();
    }
  }

  function resizeCanvas(){
    canvas.width  = canvas.offsetWidth || 300;
    canvas.height = 50;
  }
  window.addEventListener('resize', resizeCanvas);
  requestAnimationFrame(resizeCanvas);

  async function startRecording() {
    try {
      const constraints = {
        audio: { channelCount: 1, echoCancellation: true, noiseSuppression: true, autoGainControl: true }
      };
      stream = await navigator.mediaDevices.getUserMedia(constraints);
    } catch(e) {
      status.textContent = 'Sin acceso al micrófono: ' + e.message;
      return;
    }

    const aC = new (window.AudioContext || window.webkitAudioContext)();
    analyser = aC.createAnalyser();
    analyser.fftSize = 256;
    dataArr = new Uint8Array(analyser.frequencyBinCount);
    aC.createMediaStreamSource(stream).connect(analyser);
    draw();

    chunks = [];
    paused = false;
    secs = 0;

    const mimeTypes = [
      'audio/webm;codecs=opus',
      'audio/webm',
      'audio/ogg;codecs=opus',
      'audio/ogg',
      'audio/mp4'
    ];
    let selectedMime = '';
    for(const m of mimeTypes){
      if(MediaRecorder.isTypeSupported(m)){ selectedMime = m; break; }
    }

    let options = { audioBitsPerSecond: 128000 };
    if(selectedMime) options.mimeType = selectedMime;

    try {
      mr = new MediaRecorder(stream, options);
    } catch(e) {
      mr = new MediaRecorder(stream);
    }

    mr.ondataavailable = e => {
      if(e.data && e.data.size > 0) chunks.push(e.data);
    };

    mr.onstop = () => {
      cancelAnimationFrame(raf);
      ctx.clearRect(0,0,canvas.width,canvas.height);
      if(chunks.length === 0){
        status.textContent = 'No se capturó audio.';
        return;
      }

      const blob = new Blob(chunks, { type: selectedMime || mr.mimeType || 'audio/webm' });
      window.recordedAudioBlob = blob;

      let ext = 'webm';
      if(blob.type.includes('ogg')) ext = 'ogg';
      else if(blob.type.includes('mp4')) ext = 'mp4';

      recName.value = 'grabacion-' + Date.now() + '.' + ext;
      recMime.value = blob.type;

      playback.src = URL.createObjectURL(blob);
      playback.style.display = 'block';

      status.textContent = 'Audio listo (' + Math.round(blob.size/1024) + ' KB)';
      mimeBadge.textContent = (blob.size/1024/1024).toFixed(1) + ' MB';
      liveDot.classList.remove('active');

      // Auto-set title if empty
      const titleInput = document.getElementById('ep-title');
      if (titleInput && !titleInput.value.trim()) {
        const d = new Date();
        titleInput.value = 'Nota de voz ' + d.toLocaleDateString('es-ES', { day:'numeric', month:'short' });
        titleInput.dispatchEvent(new Event('input', { bubbles: true }));
      }

      if(stream) stream.getTracks().forEach(t => t.stop());
    };

    mr.start(1000);
    iv = setInterval(() => {
      if(!paused){
        secs++;
        timer.textContent = fmt(secs);
      }
    }, 1000);

    timer.classList.add('on');
    liveDot.classList.add('active');
    btnRec.classList.add('recording');
    iconMic.style.display = 'none';
    iconStop.style.display = 'block';
    btnPause.disabled = false;
    btnDisc.style.display = 'none';
    playback.style.display = 'none';
    status.textContent = 'Grabando...';
    mimeBadge.textContent = selectedMime ? selectedMime.split(';')[0] : 'en vivo';
  }

  function stopRecording() {
    if(!mr) return;
    clearInterval(iv);
    timer.classList.remove('on');
    liveDot.classList.remove('active');
    btnRec.classList.remove('recording');
    iconMic.style.display = 'block';
    iconStop.style.display = 'none';
    btnPause.disabled = true;
    btnDisc.style.display = 'inline-flex';
    mr.stop();
  }

  btnRec.addEventListener('click', () => {
    if(mr && mr.state === 'recording'){
      stopRecording();
    } else {
      startRecording();
    }
  });

  btnPause.addEventListener('click', () => {
    if(!mr) return;
    if(paused){
      mr.resume();
      paused = false;
      timer.classList.add('on');
      liveDot.classList.add('active');
      status.textContent = 'Grabando...';
    } else {
      mr.pause();
      paused = true;
      timer.classList.remove('on');
      liveDot.classList.remove('active');
      status.textContent = 'Pausado';
    }
  });

  btnDisc.addEventListener('click', () => {
    playback.style.display = 'none';
    window.recordedAudioBlob = null;
    recName.value = '';
    secs = 0;
    timer.textContent = '00:00:00';
    status.textContent = 'Pulsa para iniciar la grabación';
    mimeBadge.textContent = 'listo';
    btnDisc.style.display = 'none';
    ctx.clearRect(0,0,canvas.width,canvas.height);
  });
})();

// ── QUICK TITLE DATE SHORTCUT ─────────────────────────────
document.getElementById('btnQuickTitle')?.addEventListener('click', () => {
  const d = new Date();
  const dateStr = d.toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' });
  const titleInput = document.getElementById('ep-title');
  if (titleInput) {
    titleInput.value = 'Episodio ' + dateStr;
    titleInput.dispatchEvent(new Event('input', { bubbles: true }));
    titleInput.focus();
  }
});

// ── FAST XHR AJAX SUBMISSION WITH DIRECT BLOB MULTIPART ──
document.getElementById('publishForm').addEventListener('submit', function(e) {
  e.preventDefault();

  const fileInput = document.getElementById('audioFileInput');
  const hasFile = fileInput && fileInput.files.length > 0;
  const hasUrl  = document.getElementById('audio_url')?.value.trim() !== '';
  const hasRec  = !!window.recordedAudioBlob;

  if(!hasFile && !hasUrl && !hasRec) {
    alert('Necesitas grabar, subir o enlazar un archivo de audio.');
    return;
  }

  const titleVal = document.getElementById('ep-title')?.value.trim();
  if(!titleVal) {
    alert('Por favor introduce un título para el episodio.');
    document.getElementById('ep-title')?.focus();
    return;
  }

  const form     = this;
  const btn      = document.getElementById('btnPublish');
  const overlay  = document.getElementById('uploadOverlay');
  const bar      = document.getElementById('uploadBar');
  const pct      = document.getElementById('uploadPercent');
  const statusEl = document.getElementById('uploadStatus');

  btn.disabled = true;
  btn.textContent = 'Publicando en KutPod...';
  overlay.style.display = 'block';
  bar.style.width = '0%';
  pct.textContent = '0%';

  const formData = new FormData(form);
  formData.append('ajax', '1');

  // Direct binary Blob upload for recordings
  if (window.recordedAudioBlob && !hasFile) {
    const filename = document.getElementById('recName')?.value || 'grabacion.webm';
    formData.set('audio_file', window.recordedAudioBlob, filename);
  }

  const xhr = new XMLHttpRequest();
  xhr.open('POST', form.action, true);
  xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

  xhr.upload.addEventListener('progress', function(ev) {
    if (ev.lengthComputable) {
      const p = Math.round(ev.loaded / ev.total * 100);
      bar.style.width = p + '%';
      pct.textContent = p + '%';
      statusEl.textContent = (p < 100) ? 'Subiendo archivo de audio...' : 'Procesando en servidor y normalizando LUFS...';
    }
  });

  xhr.addEventListener('load', function() {
    try {
      const res = JSON.parse(xhr.responseText);
      if (res.success) {
        // Clear local autosave on success
        localStorage.removeItem('kutfast_draft_' + window.activePodcast);
        bar.style.width = '100%';
        bar.style.background = 'var(--green)';
        statusEl.textContent = '¡Episodio publicado correctamente!';
        setTimeout(() => {
          window.location.href = res.redirect || 'index.php?page=dashboard&podcast=' + encodeURIComponent(window.activePodcast);
        }, 500);
      } else {
        throw new Error(res.error || 'Respuesta inesperada.');
      }
    } catch(err) {
      btn.disabled = false;
      btn.innerHTML = '🚀 Publicar episodio';
      overlay.style.display = 'none';
      alert('Error al publicar: ' + (err.message || xhr.responseText));
    }
  });

  xhr.addEventListener('error', function() {
    btn.disabled = false;
    btn.innerHTML = '🚀 Publicar episodio';
    overlay.style.display = 'none';
    alert('Error de conexión de red al subir el audio.');
  });

  xhr.send(formData);
});

// ── TEMPLATE PICKER (CLEAN ESCAPING) ──────────────────────
const tplPicker = document.getElementById('tplPicker');
if(tplPicker){
  tplPicker.addEventListener('change', function(){
    const val = this.value;
    if(val){
      document.getElementById('ep-desc').value = val;
      this.value = '';
      updatePreview();
      triggerAutosave();
    }
  });
}

// ── LIVE PREVIEW CARD ─────────────────────────────────────
function updatePreview(){
  const title = document.getElementById('ep-title')?.value || 'Título del episodio';
  const num   = document.getElementById('ep-num')?.value;
  const season= document.getElementById('ep-season')?.value;
  const type  = document.getElementById('ep-type')?.value;
  const desc  = document.getElementById('ep-desc')?.value || 'Sin descripción aún.';
  const date  = document.getElementById('ep-date')?.value;

  document.getElementById('prevTitle').textContent = title;

  let meta = [];
  if(num) meta.push('Ep. ' + num);
  if(season) meta.push('T' + season);
  if(type && type !== 'full') meta.push(type);
  if(date) {
    try { meta.push(new Date(date).toLocaleDateString('es-ES')); } catch(e){}
  }
  document.getElementById('prevMeta').textContent = meta.join(' · ') || 'Borrador';

  const html = desc
    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>')
    .replace(/\*(.+?)\*/g,'<em>$1</em>')
    .replace(/^- (.+)/gm,'<li>$1</li>')
    .replace(/\n/g,'<br>');
  document.getElementById('prevDesc').innerHTML = html;
}

['ep-title','ep-desc','ep-num','ep-season','ep-type','ep-date'].forEach(id => {
  const el = document.getElementById(id);
  if(el) el.addEventListener('input', () => {
    updatePreview();
    triggerAutosave();
  });
});
updatePreview();

// ── AUTOMATIC EPISODE NUMBER BY SEASON ────────────────────
(function(){
  const episodesBySeason = <?= json_encode($episodesBySeason ?? []) ?>;
  const seasonInput = document.getElementById('ep-season');
  const episodeInput = document.getElementById('ep-num');
  if(!seasonInput || !episodeInput) return;

  let episodeManuallyChanged = false;

  function updateEpisodeBySeason() {
    if (episodeManuallyChanged && episodeInput.value !== '') return;
    const s = seasonInput.value;
    if (s === '') return;
    if (episodesBySeason[s]) {
      episodeInput.value = parseInt(episodesBySeason[s]) + 1;
    } else {
      episodeInput.value = 1;
    }
    updatePreview();
  }

  seasonInput.addEventListener('input', updateEpisodeBySeason);
  episodeInput.addEventListener('input', () => {
    if(episodeInput.value !== '') episodeManuallyChanged = true;
  });
})();

// ── LUFS SELECTION PILLS ──────────────────────────────────
(function(){
  const lufsButtons = document.querySelectorAll('.lufs-btn');
  const lufsCustom  = document.getElementById('lufs_custom');
  const lufsHidden  = document.getElementById('lufs_level');
  
  lufsButtons.forEach(btn => {
    btn.addEventListener('click', function() {
      lufsButtons.forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      lufsHidden.value = this.getAttribute('data-lufs');
      if(lufsCustom) lufsCustom.value = '';
    });
  });
  
  if(lufsCustom) {
    lufsCustom.addEventListener('input', function() {
      lufsButtons.forEach(b => b.classList.remove('active'));
      if(this.value.trim() !== '') {
        lufsHidden.value = parseFloat(this.value).toFixed(1);
      }
    });
  }
})();

// ── SAVE DRAFT TO SERVER ──────────────────────────────────
document.getElementById('btnSaveDraft')?.addEventListener('click', function(){
  document.getElementById('df_title').value    = document.getElementById('ep-title')?.value||'';
  document.getElementById('df_desc').value     = document.getElementById('ep-desc')?.value||'';
  document.getElementById('df_slug').value     = document.getElementById('ep-slug')?.value||'';
  document.getElementById('df_season').value   = document.getElementById('ep-season')?.value||'';
  document.getElementById('df_num').value      = document.getElementById('ep-num')?.value||'';
  document.getElementById('df_type').value     = document.getElementById('ep-type')?.value||'full';
  document.getElementById('df_date').value     = document.getElementById('ep-date')?.value||'';
  document.getElementById('df_explicit').value = document.querySelector('input[name="explicit"]')?.checked?'1':'';
  document.getElementById('df_audiourl').value = document.getElementById('audio_url')?.value||'';
  document.getElementById('df_lufs').value     = document.getElementById('lufs_level')?.value || '-16';
  document.getElementById('draftForm').submit();
});

// ── LOCALSTORAGE AUTOSAVE (CLIENT-SIDE SAFETY) ────────────
let autosaveTimeout = null;
function triggerAutosave() {
  clearTimeout(autosaveTimeout);
  autosaveTimeout = setTimeout(() => {
    const draft = {
      title: document.getElementById('ep-title')?.value || '',
      description: document.getElementById('ep-desc')?.value || '',
      season: document.getElementById('ep-season')?.value || '',
      episode: document.getElementById('ep-num')?.value || '',
      time: Date.now()
    };
    if (draft.title || draft.description) {
      localStorage.setItem('kutfast_autosave_' + window.activePodcast, JSON.stringify(draft));
    }
  }, 500);
}

// Check autosave on page load if fields are empty
document.addEventListener('DOMContentLoaded', () => {
  const currentTitle = document.getElementById('ep-title')?.value.trim();
  const raw = localStorage.getItem('kutfast_autosave_' + window.activePodcast);
  if (!currentTitle && raw) {
    try {
      const saved = JSON.parse(raw);
      if (saved.title || saved.description) {
        const banner = document.getElementById('autosaveBanner');
        if (banner) banner.style.display = 'flex';

        document.getElementById('btnRestoreAutosave')?.addEventListener('click', () => {
          if (saved.title) document.getElementById('ep-title').value = saved.title;
          if (saved.description) document.getElementById('ep-desc').value = saved.description;
          if (saved.season) document.getElementById('ep-season').value = saved.season;
          if (saved.episode) document.getElementById('ep-num').value = saved.episode;
          banner.style.display = 'none';
          updatePreview();
        });

        document.getElementById('btnDiscardAutosave')?.addEventListener('click', () => {
          localStorage.removeItem('kutfast_autosave_' + window.activePodcast);
          banner.style.display = 'none';
        });
      }
    } catch(e){}
  }
});
</script>
