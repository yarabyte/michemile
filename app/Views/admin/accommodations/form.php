<?php
$item = $item ?? null;
$eq = json_decode((string) ($item['equipment_json'] ?? '[]'), true) ?: [];
$pr = json_decode((string) ($item['practical_json'] ?? '[]'), true) ?: [];
$g = json_decode((string) ($item['gallery_json'] ?? '[]'), true) ?: [];
$viewHref = $item ? url('/chambres/' . ($item['slug'] ?? '')) : null;
?>
<?php component('page-header', [
    'title' => $title ?? ($item ? 'Modifier hébergement' : 'Nouvel hébergement'),
    'subtitle' => 'Remplissez les infos visibles sur le site. Pas besoin de connaître le HTML.',
    'actionHref' => admin_url('hebergements'),
    'actionLabel' => 'Retour à la liste',
    'actionVariant' => 'secondary',
]); ?>

<form
  class="grid gap-4 pb-8"
  method="post"
  action="<?= e(admin_url('hebergements/sauver')) ?>"
  data-auto-slug
  x-data="langTabs('fr')"
>
  <?= csrf_field() ?>
  <?php if ($item): ?><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><?php endif; ?>

  <div class="ui-card grid gap-4 md:grid-cols-2">
    <h2 class="md:col-span-2 mb-0 mt-0 text-base font-semibold text-forest">Identité</h2>
    <?php component('input', [
        'name' => 'name_fr',
        'label' => 'Nom (français)',
        'required' => true,
        'value' => $item['name_fr'] ?? '',
        'hint' => 'Ex. : Suite Prestige',
        'attrs' => 'data-slug-source',
        'class' => 'md:col-span-2',
    ]); ?>
    <?php component('select', [
        'name' => 'category',
        'label' => 'Catégorie',
        'value' => $item['category'] ?? 'suites',
        'options' => [
            'suites' => 'Suites',
            'apparts' => 'Appartements',
            'chambres' => 'Chambres',
        ],
    ]); ?>
    <label class="ui-label grid-cols-[auto_1fr] items-center self-end font-medium">
      <input type="checkbox" name="is_published" class="rounded border-line" <?= !isset($item['is_published']) || (int) $item['is_published'] ? 'checked' : '' ?>>
      Visible sur le site
    </label>
  </div>

  <div class="ui-card">
    <?php component('gallery-field', [
        'items' => $g,
        'name' => 'gallery',
        'label' => 'Photos',
        'hint' => 'Ajoutez des photos. La première sert de couverture.',
    ]); ?>
  </div>

  <div class="ui-card">
    <div class="mb-4 flex gap-2 border-b border-line pb-2">
      <button type="button" class="rounded-md px-3 py-1.5 text-sm font-semibold" :class="tab==='fr' ? 'bg-forest text-cream-2' : 'text-muted'" @click="tab='fr'">Français</button>
      <button type="button" class="rounded-md px-3 py-1.5 text-sm font-semibold" :class="tab==='en' ? 'bg-forest text-cream-2' : 'text-muted'" @click="tab='en'">English</button>
    </div>
    <div class="grid gap-4" x-show="tab==='fr'">
      <?php component('textarea', [
          'name' => 'short_fr',
          'label' => 'Résumé court (français)',
          'required' => true,
          'rows' => 3,
          'hint' => '1 à 2 phrases affichées sur les listes.',
          'value' => $item['short_fr'] ?? '',
      ]); ?>
      <?php component('richtext', [
          'name' => 'body_fr',
          'label' => 'Description (français)',
          'value' => $item['body_fr'] ?? '',
      ]); ?>
    </div>
    <div class="grid gap-4" x-show="tab==='en'" style="display:none">
      <?php component('input', ['name' => 'name_en', 'label' => 'Name (English)', 'value' => $item['name_en'] ?? '']); ?>
      <?php component('textarea', ['name' => 'short_en', 'label' => 'Short summary', 'rows' => 3, 'value' => $item['short_en'] ?? '']); ?>
      <?php component('richtext', ['name' => 'body_en', 'label' => 'Description (English)', 'value' => $item['body_en'] ?? '']); ?>
    </div>
  </div>

  <div class="ui-card grid gap-4 md:grid-cols-2">
    <h2 class="md:col-span-2 mb-0 mt-0 text-base font-semibold text-forest">Caractéristiques & prix</h2>
    <?php component('input', ['type' => 'number', 'name' => 'capacity', 'label' => 'Capacité (personnes)', 'value' => (string) ($item['capacity'] ?? '2')]); ?>
    <?php component('input', ['type' => 'number', 'name' => 'area_m2', 'label' => 'Surface (m²)', 'value' => (string) ($item['area_m2'] ?? '0')]); ?>
    <?php component('input', ['type' => 'number', 'name' => 'bedrooms', 'label' => 'Nombre de chambres', 'value' => (string) ($item['bedrooms'] ?? '1')]); ?>
    <?php component('input', ['type' => 'number', 'name' => 'bathrooms', 'label' => 'Salles de bain', 'value' => (string) ($item['bathrooms'] ?? '1')]); ?>
    <?php component('input', ['name' => 'beds', 'label' => 'Lits', 'hint' => 'Ex. : 1 lit king 160×200', 'value' => $item['beds'] ?? '']); ?>
    <?php component('input', ['type' => 'number', 'name' => 'price_fcfa', 'label' => 'Prix (FCFA)', 'value' => (string) ($item['price_fcfa'] ?? '0')]); ?>
    <label class="ui-label md:col-span-2 grid-cols-[auto_1fr] items-center font-medium">
      <input type="checkbox" name="price_from" class="rounded border-line" <?= !empty($item['price_from']) ? 'checked' : '' ?>>
      Afficher « à partir de » devant le prix
    </label>
  </div>

  <div class="ui-card">
    <?php component('repeater-equip', ['items' => $eq, 'name' => 'equipment']); ?>
  </div>

  <div class="ui-card">
    <?php component('repeater-pairs', [
        'items' => $pr,
        'name' => 'practical',
        'label' => 'Informations pratiques',
        'hint' => 'Ex. : Check-in / 14h',
        'leftLabel' => 'Libellé',
        'rightLabel' => 'Valeur',
    ]); ?>
  </div>

  <?php
    ob_start();
    component('input', [
        'name' => 'slug',
        'label' => 'Adresse web',
        'hint' => 'Générée automatiquement à partir du nom. Modifiez seulement si nécessaire.',
        'required' => true,
        'value' => $item['slug'] ?? '',
        'attrs' => 'data-slug-target' . ($item ? ' data-slug-locked="1"' : ''),
    ]);
    component('input', ['type' => 'number', 'name' => 'sort_order', 'label' => 'Ordre d’affichage', 'hint' => 'Plus petit = plus haut dans la liste', 'value' => (string) ($item['sort_order'] ?? '0')]);
    component('input', ['name' => 'meta_title_fr', 'label' => 'Titre Google (FR)', 'value' => $item['meta_title_fr'] ?? '']);
    component('textarea', ['name' => 'meta_desc_fr', 'label' => 'Description Google (FR)', 'rows' => 2, 'value' => $item['meta_desc_fr'] ?? '']);
    component('input', ['name' => 'meta_title_en', 'label' => 'Google title (EN)', 'value' => $item['meta_title_en'] ?? '']);
    component('textarea', ['name' => 'meta_desc_en', 'label' => 'Google description (EN)', 'rows' => 2, 'value' => $item['meta_desc_en'] ?? '']);
    $advanced = ob_get_clean();
    component('accordion', ['title' => 'Référencement & options avancées', 'body' => $advanced]);
  ?>

  <?php component('form-actions', [
      'saveLabel' => 'Enregistrer',
      'viewHref' => $viewHref,
      'backHref' => admin_url('hebergements'),
  ]); ?>
</form>

<?php if ($item): ?>
  <form class="mt-2" method="post" action="<?= e(admin_url('hebergements/supprimer')) ?>" onsubmit="return confirm('Supprimer définitivement cet hébergement ? Cette action est irréversible.');">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
    <?php component('button', ['type' => 'submit', 'variant' => 'danger', 'label' => 'Supprimer cet hébergement']); ?>
  </form>
<?php endif; ?>
