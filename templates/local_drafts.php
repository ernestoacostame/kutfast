<?php
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
    <h1>Borradores locales</h1>
    <p><?= count($localDrafts) ?> guardados para <strong><?= htmlspecialchars($activePodcast) ?></strong></p>
  </div>
</div>

<?php if (!empty($localDrafts)): ?>
<div class="ep-list fade">
  <?php foreach ($localDrafts as $i => $ld): 
    $ldNum = $ld['episode_number'] ?? $ld['episode'] ?? $ld['number'] ?? null;
  ?>
  <div class="ep-item" style="animation-delay:<?= $i*0.025 ?>s">
    <span class="ep-num"><?= $ldNum !== null && $ldNum !== '' ? '#' . htmlspecialchars((string)$ldNum) : '–' ?></span>
    <?php if (!empty($podcastCover)): ?>
    <img src="<?= htmlspecialchars($podcastCover) ?>" class="ep-cover" alt="cover" loading="lazy">
    <?php else: ?>
    <div class="ep-cover ep-cover-placeholder">🎙</div>
    <?php endif; ?>
    <div class="ep-info">
      <div class="ep-title"><?= htmlspecialchars($ld['title'] ?: 'Sin titulo') ?></div>
      <div class="ep-meta">
        Guardado <?= htmlspecialchars($ld['saved_at'] ?? '') ?>
        <?php if(!empty($ld['episode_number'])): ?> &middot; Ep.<?= (int)$ld['episode_number'] ?><?php endif; ?>
      </div>
    </div>
    <span class="ep-draft">local</span>
    <a href="index.php?page=publish&podcast=<?= urlencode($activePodcast) ?>&draft_id=<?= urlencode($ld['draft_id']) ?>" class="btn btn-ghost btn-sm">Editar</a>
    <form method="POST" action="index.php?page=delete_draft&podcast=<?= urlencode($activePodcast) ?>" style="margin:0;display:inline">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <input type="hidden" name="draft_id" value="<?= htmlspecialchars($ld['draft_id']) ?>">
      <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Eliminar este borrador?')">x</button>
    </form>
  </div>
  <?php endforeach; ?>
</div>
<?php else: ?>
<div class="empty fade">
  <div class="empty-icon">&#128196;</div>
  <p>No tienes borradores locales guardados.</p>
  <a href="index.php?page=publish&podcast=<?= urlencode($activePodcast) ?>" class="btn btn-ghost btn-sm">Nuevo episodio</a>
</div>
<?php endif; ?>
