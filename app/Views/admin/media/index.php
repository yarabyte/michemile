<?php component('page-header', [
    'title' => $title ?? 'Médiathèque',
    'subtitle' => 'Photos et documents utilisables dans les fiches',
]); ?>

<?php if (!empty($error)): ?>
  <?php component('alert', ['type' => 'error', 'message' => (string) $error]); ?>
<?php endif; ?>

<form class="ui-card mb-5 grid gap-4 md:grid-cols-[1fr_1fr_auto] md:items-end" method="post" action="<?= e(admin_url('mediatheque/upload')) ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <label class="ui-label">
    <span>Envoyer un fichier</span>
    <span class="ui-label-hint">JPEG / PNG / WebP / PDF — max 8 Mo</span>
    <input class="ui-input" type="file" name="file" required>
  </label>
  <?php component('input', ['name' => 'alt_fr', 'label' => 'Texte alternatif', 'hint' => 'Description courte de l’image']); ?>
  <?php component('button', ['type' => 'submit', 'label' => 'Envoyer']); ?>
</form>

<?php if (empty($items)): ?>
  <?php component('empty-state', ['message' => 'Aucun média pour l’instant.']); ?>
<?php else: ?>
  <div class="media-grid">
    <?php foreach ($items as $item): ?>
      <article class="media-grid-card">
        <?php if (str_starts_with((string) $item['mime'], 'image/')): ?>
          <img src="<?= e($item['public_path']) ?>" alt="<?= e((string) ($item['alt_fr'] ?? '')) ?>">
        <?php else: ?>
          <div class="flex aspect-square items-center justify-center bg-cream text-sm font-semibold text-muted">PDF</div>
        <?php endif; ?>
        <div class="grid gap-1 p-2">
          <div class="truncate text-xs font-medium text-ink" title="<?= e((string) ($item['original_name'] ?? '')) ?>"><?= e((string) ($item['original_name'] ?? $item['public_path'])) ?></div>
          <div class="text-xs text-muted"><?= e(format_bytes((int) $item['size_bytes'])) ?></div>
          <form method="post" action="<?= e(admin_url('mediatheque/supprimer')) ?>" onsubmit="return confirm('Supprimer ce fichier de la médiathèque ?');">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
            <button type="submit" class="text-xs font-semibold text-danger">Supprimer</button>
          </form>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
