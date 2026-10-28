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
    <h1>Borradores</h1>
    <p><?= count($drafts) ?> sin publicar en <strong><?= htmlspecialchars($activePodcast) ?></strong></p>
  </div>
</div>

<?php if (!empty($drafts)): ?>
<div class="ep-list fade">
  <?php foreach ($drafts as $i => $ep): 
    $draftNum = $ep['episode'] ?? $ep['number'] ?? $ep['episode_number'] ?? null;
    $draftSea = $ep['season'] ?? $ep['season_number'] ?? null;
  ?>
  <div class="ep-item" style="animation-delay:<?= $i * 0.025 ?>s">
    <span class="ep-num"><?= $draftNum !== null && $draftNum !== '' ? '#' . htmlspecialchars((string)$draftNum) : '–' ?></span>
    <?php if (!empty($podcastCover)): ?>
    <img src="<?= htmlspecialchars($podcastCover) ?>" class="ep-cover" alt="cover" loading="lazy">
    <?php else: ?>
    <div class="ep-cover ep-cover-placeholder">🎙</div>
    <?php endif; ?>
    <div class="ep-info">
      <div class="ep-title"><?= htmlspecialchars($ep['title'] ?? 'Sin titulo') ?></div>
      <div class="ep-meta">
        Creado <?= !empty($ep['created_at']) && strtotime($ep['created_at']) ? date('d/m/Y H:i', strtotime($ep['created_at'])) : '-' ?>
        <?php if (!empty($draftSea)): ?> &middot; T<?= (int)$draftSea ?><?php endif; ?>
      </div>
    </div>
    <span class="ep-draft">borrador</span>
    <?php if (!empty($ep['id'])): ?>
    <form method="POST" action="index.php?page=publish_draft&podcast=<?= urlencode($activePodcast) ?>" style="margin:0">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <input type="hidden" name="episode_id" value="<?= (int)$ep['id'] ?>">
      <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('¿Publicar este borrador ahora en KutPod?')">🚀 Publicar</button>
    </form>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>

<div style="margin-top:1.5rem;padding:.75rem 1rem;background:var(--bg2);border:1px solid var(--line);border-radius:var(--r);font-size:.8rem;color:var(--text3);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem">
  <span>Para editar archivos o borrar borradores remotamente, accede al panel de KutPod.</span>
  <?php if (defined('KUTPOD_URL')): ?>
  <a href="<?= htmlspecialchars(rtrim(KUTPOD_URL, '/')) ?>" target="_blank" rel="noopener" class="btn btn-ghost btn-sm" style="font-size:.72rem">Abrir KutPod &rarr;</a>
  <?php endif; ?>
</div>

<?php else: ?>
<div class="empty fade">
  <div class="empty-icon">&#128196;</div>
  <p>No hay borradores pendientes.</p>
</div>
<?php endif; ?>
