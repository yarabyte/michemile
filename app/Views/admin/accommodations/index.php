<?php
$filterCategory = $filterCategory ?? '';
$filterStatus = $filterStatus ?? '';
$canReorder = $filterCategory === '' && $filterStatus === '';
?>
<?php component('page-header', [
    'title' => $title ?? 'Hébergements',
    'subtitle' => 'Suites, appartements et chambres',
    'actionHref' => admin_url('hebergements/nouveau'),
    'actionLabel' => 'Ajouter',
]); ?>

<nav class="filter-tabs" aria-label="Filtres">
  <?php
    $tabs = [
      ['' , '', 'Tous'],
      ['suites', '', 'Suites'],
      ['apparts', '', 'Appartements'],
      ['chambres', '', 'Chambres'],
      ['', 'publie', 'Publiés'],
      ['', 'brouillon', 'Brouillons'],
    ];
    foreach ($tabs as [$cat, $st, $label]):
      $qs = [];
      if ($cat !== '') $qs['categorie'] = $cat;
      if ($st !== '') $qs['statut'] = $st;
      $href = admin_url('hebergements') . ($qs ? '?' . http_build_query($qs) : '');
      $active = ($filterCategory === $cat && $filterStatus === $st)
          || ($cat === '' && $st === '' && $filterCategory === '' && $filterStatus === '');
      if ($st !== '' && $filterStatus === $st && $filterCategory === '') $active = true;
      if ($cat !== '' && $filterCategory === $cat && $filterStatus === '') $active = true;
  ?>
    <a class="filter-tab <?= $active ? 'filter-tab-active' : '' ?>" href="<?= e($href) ?>"><?= e($label) ?></a>
  <?php endforeach; ?>
</nav>

<?php if (empty($items)): ?>
  <?php component('empty-state', ['message' => 'Aucun hébergement. Créez la première fiche.']); ?>
<?php else: ?>
  <?php if ($canReorder): ?>
    <p class="mb-3 text-sm text-muted">Glissez les lignes (ou utilisez ▲ ▼) pour définir l’ordre d’affichage sur le site.</p>
  <?php else: ?>
    <p class="mb-3 text-sm text-muted">Affichez le filtre « Tous » pour réorganiser l’ordre d’affichage.</p>
  <?php endif; ?>
  <div class="ui-table-wrap">
    <table class="ui-table">
      <thead>
        <tr>
          <?php if ($canReorder): ?><th class="w-20">Ordre</th><?php endif; ?>
          <th>Photo</th>
          <th>Nom</th>
          <th>Catégorie</th>
          <th>Prix</th>
          <th>Statut</th>
          <th></th>
        </tr>
      </thead>
      <tbody
        <?php if ($canReorder): ?>
          data-reorder-list
          data-reorder-url="<?= e(admin_url('hebergements/ordonner')) ?>"
        <?php endif; ?>
      >
      <?php foreach ($items as $index => $item):
        $gallery = json_decode((string) ($item['gallery_json'] ?? '[]'), true) ?: [];
        $thumb = $gallery[0] ?? '';
      ?>
        <tr data-id="<?= (int) $item['id'] ?>"<?= $canReorder ? ' draggable="true" class="reorder-row"' : '' ?>>
          <?php if ($canReorder): ?>
            <td class="reorder-cell">
              <div class="reorder-controls">
                <button type="button" class="reorder-handle" title="Glisser pour déplacer" aria-label="Glisser pour déplacer">⋮⋮</button>
                <button type="button" class="reorder-btn" data-reorder-up title="Monter" aria-label="Monter">▲</button>
                <button type="button" class="reorder-btn" data-reorder-down title="Descendre" aria-label="Descendre">▼</button>
                <span class="reorder-rank text-xs text-muted" data-reorder-rank><?= $index + 1 ?></span>
              </div>
            </td>
          <?php endif; ?>
          <td>
            <?php if ($thumb !== ''): ?>
              <img class="h-12 w-16 rounded object-cover" src="<?= e($thumb) ?>" alt="">
            <?php else: ?>
              <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
          <td class="font-medium"><?= e($item['name_fr']) ?></td>
          <td><?= e(category_label((string) $item['category'])) ?></td>
          <td><?= e(format_fcfa((int) $item['price_fcfa'])) ?></td>
          <td>
            <?php component('badge', [
                'text' => (int) $item['is_published'] ? 'Publié' : 'Brouillon',
                'variant' => (int) $item['is_published'] ? 'ok' : 'muted',
            ]); ?>
          </td>
          <td class="text-right whitespace-nowrap">
            <a class="text-sm font-semibold text-forest no-underline hover:underline" href="<?= e(url('/chambres/' . $item['slug'])) ?>" target="_blank" rel="noopener">Voir</a>
            <span class="text-muted"> · </span>
            <a class="text-sm font-semibold text-forest no-underline hover:underline" href="<?= e(admin_url('hebergements/' . $item['id'])) ?>">Modifier</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($canReorder): ?>
    <p class="mt-2 text-sm text-muted" data-reorder-status aria-live="polite"></p>
  <?php endif; ?>
<?php endif; ?>
