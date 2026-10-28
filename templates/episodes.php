<?php
// Show total from API separately if we have it
$totalPublished = count($episodes);
$podcastCover = $podcastInfo['cover_url'] ?? $podcastInfo['cover']['url'] ?? $podcastInfo['cover'] ?? '';
if (!empty($podcastCover) && !str_starts_with($podcastCover, 'http')) {
    $podcastCover = rtrim(KUTPOD_URL, '/') . '/' . ltrim($podcastCover, '/');
}
?>
<div class="page-head fade" style="display:flex;align-items:center;gap:.85rem">
  <?php if (!empty($podcastCover)): ?>
  <img src="<?= htmlspecialchars($podcastCover) ?>" style="width:44px;height:44px;border-radius:8px;object-fit:cover;border:1px solid var(--line);box-shadow:0 2px 8px rgba(0,0,0,.2)" alt="cover">
  <?php endif; ?>
  <div>
    <h1>Episodios</h1>
    <p><?= $totalPublished ?> publicados en <strong><?= htmlspecialchars($activePodcast) ?></strong></p>
  </div>
</div>

<?php if (defined('KUTPOD_DEBUG') && KUTPOD_DEBUG && !empty($episodes)): ?>
<details style="margin-bottom:1rem;font-size:.7rem;color:var(--text3)">
  <summary style="cursor:pointer">Debug: campos del primer episodio</summary>
  <pre style="overflow:auto;padding:.6rem;background:var(--bg2);border-radius:6px;margin-top:.4rem;font-family:'JetBrains Mono',monospace"><?= htmlspecialchars(json_encode($episodes[0], JSON_PRETTY_PRINT)) ?></pre>
</details>
<?php endif; ?>

<?php if (!empty($episodes)): ?>
<div class="ep-list fade">
  <?php foreach ($episodes as $i => $ep):
    $ts  = !empty($ep['published_at']) && strtotime($ep['published_at']) ? strtotime($ep['published_at']) : null;
    $num = $ep['episode'] ?? $ep['number'] ?? $ep['episode_number'] ?? null;
    $sea = $ep['season'] ?? $ep['season_number'] ?? null;
    // title comes directly from Episode entity serialization
    $ttl = $ep['title'] ?? null;
    $dur = !empty($ep['duration']) ? (int)$ep['duration'] : 0;
  ?>
  <div class="ep-item" style="animation-delay:<?= min($i, 20) * 0.02 ?>s">
    <span class="ep-num"><?= $num !== null && $num !== '' ? '#' . htmlspecialchars((string)$num) : '–' ?></span>
    <?php if (!empty($podcastCover)): ?>
    <img src="<?= htmlspecialchars($podcastCover) ?>" class="ep-cover" alt="cover" loading="lazy">
    <?php else: ?>
    <div class="ep-cover ep-cover-placeholder">🎙</div>
    <?php endif; ?>
    <div class="ep-info">
      <div class="ep-title">
        <?php if ($ttl): ?>
          <?= htmlspecialchars($ttl) ?>
        <?php else: ?>
          <em style="color:var(--text3);font-style:normal">ID <?= (int)($ep['id'] ?? 0) ?></em>
        <?php endif; ?>
      </div>
      <div class="ep-meta">
        <?= $ts ? date('d/m/Y', $ts) : '' ?>
        <?php if (!empty($sea)): ?>&middot; T<?= (int)$sea ?><?php endif; ?>
        <?php if ($dur > 0): ?>&middot; <?= $dur >= 3600 ? gmdate('H:i:s', $dur) : gmdate('i:s', $dur) ?><?php endif; ?>
      </div>
    </div>
    <?php if (!empty($ep['type']) && $ep['type'] !== 'full'): ?>
    <span class="ep-type"><?= htmlspecialchars($ep['type']) ?></span>
    <?php endif; ?>
    <?php if (!empty($ep['audio_url'])): 
        $audioUrl = $ep['audio_url'];
        if (str_starts_with($audioUrl, '/')) {
            $audioUrl = rtrim(KUTPOD_URL, '/') . $audioUrl;
        }
    ?>
    <div style="display:flex;align-items:center;gap:.35rem">
      <button type="button" class="btn btn-ghost btn-sm inline-play-btn" data-url="<?= htmlspecialchars($audioUrl) ?>" title="Escuchar en el navegador">&#9654;</button>
      <a href="<?= htmlspecialchars($audioUrl) ?>" target="_blank" rel="noopener" class="btn btn-ghost btn-sm" style="padding:.35rem .55rem" title="Abrir / Descargar">&#8681;</a>
    </div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>
<?php else: ?>
<div class="empty fade">
  <div class="empty-icon">&#127911;</div>
  <p>No hay episodios publicados.</p>
  <a href="index.php?page=publish&podcast=<?= urlencode($activePodcast) ?>" class="btn btn-ghost btn-sm">Publicar el primero</a>
</div>
<?php endif; ?>

<div style="margin-top:1.25rem;text-align:right">
  <a href="index.php?page=publish&podcast=<?= urlencode($activePodcast) ?>" class="btn btn-primary btn-sm">+ Nuevo episodio</a>
</div>

<script>
(function() {
  let activeAudio = null;
  let activeBtn = null;
  document.querySelectorAll('.inline-play-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const url = this.getAttribute('data-url');
      if (activeBtn === this && activeAudio) {
        if (activeAudio.paused) {
          activeAudio.play();
          this.innerHTML = '&#10074;&#10074;';
        } else {
          activeAudio.pause();
          this.innerHTML = '&#9654;';
        }
        return;
      }
      if (activeAudio) {
        activeAudio.pause();
        if (activeBtn) activeBtn.innerHTML = '&#9654;';
      }
      activeAudio = new Audio(url);
      activeBtn = this;
      this.innerHTML = '&#10074;&#10074;';
      activeAudio.play().catch(() => {
        this.innerHTML = '&#9654;';
      });
      activeAudio.onended = () => {
        this.innerHTML = '&#9654;';
        activeAudio = null;
        activeBtn = null;
      };
    });
  });
})();
</script>
