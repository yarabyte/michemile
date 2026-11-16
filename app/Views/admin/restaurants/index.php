<?php component('page-header', [
    'title' => $title ?? 'Restaurants',
    'subtitle' => 'Fiches restaurants',
    'actionHref' => admin_url('restaurants/nouveau'),
    'actionLabel' => 'Ajouter',
]); ?>

<?php if (empty($items)): ?>
  <?php component('empty-state', ['message' => 'Aucun restaurant.']); ?>
<?php else: ?>
  <div class="ui-table-wrap">
    <table class="ui-table">
      <thead>
        <tr><th>Photo</th><th>Nom</th><th>Statut</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($items as $item): ?>
        <tr>
          <td>
            <?php if (!empty($item['image_url'])): ?>
              <img class="h-12 w-16 rounded object-cover" src="<?= e($item['image_url']) ?>" alt="">
            <?php else: ?>
              <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
          <td class="font-medium"><?= e($item['name_fr']) ?></td>
          <td>
            <?php component('badge', [
                'text' => (int) $item['is_published'] ? 'Publié' : 'Brouillon',
                'variant' => (int) $item['is_published'] ? 'ok' : 'muted',
            ]); ?>
          </td>
          <td class="text-right whitespace-nowrap">
            <a class="text-sm font-semibold text-forest no-underline hover:underline" href="<?= e(url('/restaurants/' . $item['slug'])) ?>" target="_blank" rel="noopener">Voir</a>
            <span class="text-muted"> · </span>
            <a class="text-sm font-semibold text-forest no-underline hover:underline" href="<?= e(admin_url('restaurants/' . $item['id'])) ?>">Modifier</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
