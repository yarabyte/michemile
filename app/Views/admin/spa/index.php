<?php component('page-header', [
    'title' => $title ?? 'Spa',
    'subtitle' => 'Pages spa',
    'actionHref' => admin_url('spa/nouveau'),
    'actionLabel' => 'Ajouter',
]); ?>

<?php if (empty($items)): ?>
  <?php component('empty-state', ['message' => 'Aucune page spa.']); ?>
<?php else: ?>
  <div class="ui-table-wrap">
    <table class="ui-table">
      <thead>
        <tr><th>Titre</th><th>Statut</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($items as $item): ?>
        <tr>
          <td class="font-medium"><?= e($item['title_fr']) ?></td>
          <td>
            <?php component('badge', [
                'text' => (int) $item['is_published'] ? 'Publié' : 'Brouillon',
                'variant' => (int) $item['is_published'] ? 'ok' : 'muted',
            ]); ?>
          </td>
          <td class="text-right whitespace-nowrap">
            <a class="text-sm font-semibold text-forest no-underline hover:underline" href="<?= e(url('/spa/' . $item['slug'])) ?>" target="_blank" rel="noopener">Voir</a>
            <span class="text-muted"> · </span>
            <a class="text-sm font-semibold text-forest no-underline hover:underline" href="<?= e(admin_url('spa/' . $item['id'])) ?>">Modifier</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
