<?php
// Podcast metadata resolution
$podTitle   = $podcastInfo['name'] ?? $podcastInfo['title'] ?? $activePodcast;
$podSlug    = $podcastInfo['slug'] ?? $activePodcast;
$podAuthor  = $podcastInfo['author'] ?? $podcastInfo['host'] ?? '';
$podDesc    = $podcastInfo['description'] ?? $podcastInfo['description_html'] ?? '';
$podLang    = $podcastInfo['language'] ?? $podcastInfo['language_code'] ?? '';
$podCat     = $podcastInfo['category'] ?? '';

$podcastCover = $podcastInfo['cover_url'] ?? $podcastInfo['cover']['url'] ?? $podcastInfo['cover'] ?? '';
if (!empty($podcastCover) && !str_starts_with($podcastCover, 'http')) {
    $podcastCover = rtrim(KUTPOD_URL, '/') . '/' . ltrim($podcastCover, '/');
}

$podcastBanner = $podcastInfo['banner_url'] ?? $podcastInfo['banner']['url'] ?? $podcastInfo['banner'] ?? '';
if (!empty($podcastBanner) && !str_starts_with($podcastBanner, 'http')) {
    $podcastBanner = rtrim(KUTPOD_URL, '/') . '/' . ltrim($podcastBanner, '/');
}

$podFeed   = rtrim(KUTPOD_URL, '/') . '/@' . urlencode($podSlug) . '/feed.xml';
$podPublic = rtrim(KUTPOD_URL, '/') . '/@' . urlencode($podSlug);
?>

<div class="page-head fade" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem">
  <div>
    <h1>Panel</h1>
    <p>Resumen general de <strong><?= htmlspecialchars($podTitle) ?></strong></p>
  </div>
  <div style="display:flex;align-items:center;gap:.5rem">
    <a href="index.php?page=publish&podcast=<?= urlencode($activePodcast) ?>" class="btn btn-primary btn-sm">＋ Grabar / Publicar</a>
  </div>
</div>

<div class="podcast-card fade">
  <?php if (!empty($podcastBanner)): ?>
  <div class="podcast-card-banner" style="background-image:url('<?= htmlspecialchars($podcastBanner) ?>')"></div>
  <?php endif; ?>

  <div class="podcast-card-content">
    <div class="podcast-card-cover-wrap">
      <?php if (!empty($podcastCover)): ?>
      <img src="<?= htmlspecialchars($podcastCover) ?>" class="podcast-card-cover" alt="<?= htmlspecialchars($podTitle) ?>" loading="lazy">
      <?php else: ?>
      <div class="podcast-card-cover podcast-card-cover-placeholder">🎙️</div>
      <?php endif; ?>
    </div>

    <div class="podcast-card-body">
      <div class="podcast-card-header">
        <div class="podcast-card-title-row">
          <h2 class="podcast-card-title"><?= htmlspecialchars($podTitle) ?></h2>
          <span class="podcast-badge podcast-handle">@<?= htmlspecialchars($podSlug) ?></span>
        </div>

        <?php if (!empty($podAuthor)): ?>
        <div class="podcast-card-host">
          <span class="host-icon">🎙️</span>
          <span class="host-label">Host:</span>
          <strong><?= htmlspecialchars($podAuthor) ?></strong>
        </div>
        <?php endif; ?>
      </div>

      <?php if (!empty($podDesc)): ?>
      <div class="podcast-card-desc" title="<?= htmlspecialchars(strip_tags($podDesc)) ?>">
        <?= nl2br(htmlspecialchars(strip_tags($podDesc))) ?>
      </div>
      <?php endif; ?>

      <div class="podcast-card-meta-row">
        <?php if (!empty($podAuthor)): ?>
        <span class="podcast-chip" title="Presentador / Host">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <?= htmlspecialchars($podAuthor) ?>
        </span>
        <?php endif; ?>

        <?php if (!empty($podCat)): ?>
        <span class="podcast-chip" title="Categoría">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
          <?= htmlspecialchars($podCat) ?>
        </span>
        <?php endif; ?>

        <?php if (!empty($podLang)): ?>
        <span class="podcast-chip" title="Idioma">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
          <?= strtoupper(htmlspecialchars($podLang)) ?>
        </span>
        <?php endif; ?>

        <a href="<?= htmlspecialchars($podFeed) ?>" target="_blank" rel="noopener" class="podcast-link-chip" title="Feed RSS público">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 11a9 9 0 0 1 9 9"/><path d="M4 4a16 16 0 0 1 16 16"/><circle cx="5" cy="19" r="1"/></svg>
          Feed RSS
        </a>

        <a href="<?= htmlspecialchars($podPublic) ?>" target="_blank" rel="noopener" class="podcast-link-chip" title="Abrir página pública en KutPod">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
          Ver en KutPod
        </a>
      </div>
    </div>
  </div>
</div>

<div class="stats fade">
  <div class="stat">
    <div class="stat-n">
      <a href="index.php?page=episodes&podcast=<?= urlencode($activePodcast) ?>"><?= count($episodes) ?>+</a>
    </div>
    <div class="stat-l">Publicados</div>
  </div>
  <div class="stat">
    <div class="stat-n">
      <a href="index.php?page=drafts&podcast=<?= urlencode($activePodcast) ?>" style="<?= !empty($drafts)?'color:var(--accent)':'' ?>"><?= count($drafts) ?></a>
    </div>
    <div class="stat-l">En KutPod</div>
  </div>
  <div class="stat">
    <div class="stat-n">
      <a href="index.php?page=local_drafts&podcast=<?= urlencode($activePodcast) ?>" style="<?= !empty($localDrafts)?'color:var(--green)':'' ?>"><?= count($localDrafts) ?></a>
    </div>
    <div class="stat-l">Borradores locales</div>
  </div>
  <div class="stat">
    <div class="stat-n" style="font-size:1.2rem;padding-top:.4rem">
      <?= $nextEpisodeNumber ?>
    </div>
    <div class="stat-l">Proximo ep.</div>
  </div>
</div>

<?php if (!empty($episodes)): 
  $last = $episodes[0];
  $lastNum = $last['episode'] ?? $last['number'] ?? $last['episode_number'] ?? null;
  $lastSea = $last['season'] ?? $last['season_number'] ?? null;
?>
<div class="group-label fade">Ultimo publicado</div>
<div class="ep-list fade" style="background:var(--bg2);border:1px solid var(--line);border-radius:var(--r);padding:.25rem .85rem;margin-bottom:1.75rem">
  <div class="ep-item" style="border:none">
    <span class="ep-num"><?= $lastNum !== null && $lastNum !== '' ? '#' . htmlspecialchars((string)$lastNum) : '–' ?></span>
    <?php if (!empty($podcastCover)): ?>
    <img src="<?= htmlspecialchars($podcastCover) ?>" class="ep-cover" alt="cover" loading="lazy">
    <?php else: ?>
    <div class="ep-cover ep-cover-placeholder">🎙</div>
    <?php endif; ?>
    <div class="ep-info">
      <div class="ep-title"><?= htmlspecialchars($last['title'] ?? 'Sin titulo') ?></div>
      <?php if (!empty($last['published_at']) && ($ts = strtotime($last['published_at']))): ?>
      <div class="ep-meta">
        <?= date('d/m/Y H:i', $ts) ?>
        <?php if (!empty($lastSea)): ?> &middot; T<?= (int)$lastSea ?><?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
    <?php if (!empty($last['audio_url'])): 
        $audioUrl = $last['audio_url'];
        if (str_starts_with($audioUrl, '/')) {
            $audioUrl = rtrim(KUTPOD_URL, '/') . $audioUrl;
        }
    ?>
    <div style="display:flex;align-items:center;gap:.4rem;flex-shrink:0">
      <button type="button" class="btn btn-ghost btn-sm dash-play-btn" data-url="<?= htmlspecialchars($audioUrl) ?>" title="Escuchar">&#9654; Reproducir</button>
      <a href="<?= htmlspecialchars($audioUrl) ?>" target="_blank" rel="noopener" class="btn btn-ghost btn-sm" style="padding:.35rem .55rem" title="Descargar / Abrir">&#8681;</a>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<?php if (!empty($localDrafts)): ?>
<div class="group-label fade">Borradores locales</div>
<div class="ep-list fade" style="background:var(--bg2);border:1px solid var(--line);border-radius:var(--r);padding:.25rem .85rem;margin-bottom:1.75rem">
  <?php foreach (array_slice($localDrafts, 0, 3) as $ld):
    $draftUrl = 'index.php?page=publish&podcast=' . urlencode($activePodcast) . '&draft_id=' . urlencode($ld['draft_id']);
    $ldNum = $ld['episode_number'] ?? $ld['episode'] ?? $ld['number'] ?? null;
  ?>
  <a href="<?= $draftUrl ?>" class="ep-item" style="text-decoration:none;color:inherit">
    <span class="ep-num"><?= $ldNum !== null && $ldNum !== '' ? '#' . htmlspecialchars((string)$ldNum) : '–' ?></span>
    <?php if (!empty($podcastCover)): ?>
    <img src="<?= htmlspecialchars($podcastCover) ?>" class="ep-cover" alt="cover" loading="lazy">
    <?php else: ?>
    <div class="ep-cover ep-cover-placeholder">🎙</div>
    <?php endif; ?>
    <div class="ep-info">
      <div class="ep-title" style="color:var(--green)"><?= htmlspecialchars($ld['title'] ?: 'Sin titulo') ?></div>
      <div class="ep-meta">Guardado <?= htmlspecialchars($ld['saved_at'] ?? '') ?><?php if(!empty($ldNum)): ?> &middot; Ep.<?= (int)$ldNum ?><?php endif; ?></div>
    </div>
    <span class="ep-draft">retomar &rarr;</span>
  </a>
  <?php endforeach; ?>
  <?php if (count($localDrafts) > 3): ?>
  <div style="padding:.5rem 0;text-align:center">
    <a href="index.php?page=local_drafts&podcast=<?= urlencode($activePodcast) ?>" style="font-size:.78rem;color:var(--text3)">Ver todos (<?= count($localDrafts) ?>)</a>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php
// Tmp files
$tmpFiles = [];
if (is_dir(UPLOAD_TMP_DIR)) {
    foreach (glob(UPLOAD_TMP_DIR . '/*') as $f) {
        if (is_file($f)) $tmpFiles[] = ['name'=>basename($f),'size'=>round(filesize($f)/1024/1024,2),'age'=>round((time()-filemtime($f))/60)];
    }
}
?>
<?php if (!empty($tmpFiles)): ?>
<div class="group-label" style="margin-top:1.5rem">Temporales en /tmp</div>
<div style="background:var(--bg2);border:1px solid var(--line);border-radius:var(--r);padding:.25rem .85rem">
  <?php foreach ($tmpFiles as $tf): ?>
  <div class="tmp-item">
    <span class="tmp-name"><?= htmlspecialchars($tf['name']) ?></span>
    <span class="tmp-meta"><?= $tf['size'] ?> MB / <?= $tf['age'] ?> min</span>
    <form method="POST" action="index.php?page=dashboard" style="margin:0;display:inline">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <input type="hidden" name="deltmp" value="<?= htmlspecialchars($tf['name']) ?>">
      <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Eliminar?')">x</button>
    </form>
  </div>
  <?php endforeach; ?>
  <div style="padding:.6rem 0;text-align:right">
    <form method="POST" action="index.php?page=dashboard" style="margin:0;display:inline">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <input type="hidden" name="deltmpall" value="1">
      <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Eliminar todos?')">Limpiar todo</button>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
(function() {
  let audio = null;
  const btn = document.querySelector('.dash-play-btn');
  if (btn) {
    btn.addEventListener('click', function() {
      const url = this.getAttribute('data-url');
      if (audio) {
        if (audio.paused) {
          audio.play();
          this.innerHTML = '&#10074;&#10074; Pausar';
        } else {
          audio.pause();
          this.innerHTML = '&#9654; Reproducir';
        }
        return;
      }
      audio = new Audio(url);
      this.innerHTML = '&#10074;&#10074; Pausar';
      audio.play().catch(() => { this.innerHTML = '&#9654; Reproducir'; });
      audio.onended = () => {
        this.innerHTML = '&#9654; Reproducir';
        audio = null;
      };
    });
  }
})();
</script>
