<?php
$item = $item ?? null;
$specs = json_decode((string) ($item['specialties_json'] ?? '[]'), true) ?: [];
$feats = json_decode((string) ($item['features_json'] ?? '[]'), true) ?: [];
$coverPath = $item['image_url'] ?? '';
$menuPath = '';
if (!empty($item['menu_media_id'])) {
    $menu = \App\Core\Database::fetch('SELECT public_path FROM media WHERE id=?', [(int) $item['menu_media_id']]);
    $menuPath = $menu['public_path'] ?? '';
}
$viewHref = $item ? url('/restaurants/' . ($item['slug'] ?? '')) : null;
?>
<?php component('page-header', [
    'title' => $title ?? ($item ? 'Modifier restaurant' : 'Nouveau restaurant'),
    'subtitle' => 'Fiche visible sur le site',
    'actionHref' => admin_url('restaurants'),
    'actionLabel' => 'Retour à la liste',
    'actionVariant' => 'secondary',
]); ?>

<form
  class="grid gap-4 pb-8"
  method="post"
  action="<?= e(admin_url('restaurants/sauver')) ?>"
  data-auto-slug
  x-data="langTabs('fr')"
>
  <?= csrf_field() ?>
  <?php if ($item): ?><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><?php endif; ?>
  <input type="hidden" name="legacy_slug" value="<?= e((string) ($item['legacy_slug'] ?? '')) ?>">

  <div class="ui-card grid gap-4 md:grid-cols-2">
    <h2 class="md:col-span-2 mb-0 mt-0 text-base font-semibold text-forest">Identité</h2>
    <?php component('input', [
        'name' => 'name_fr',
        'label' => 'Nom (français)',
        'required' => true,
        'value' => $item['name_fr'] ?? '',
        'attrs' => 'data-slug-source',
        'class' => 'md:col-span-2',
    ]); ?>
    <?php component('input', ['name' => 'abbr', 'label' => 'Abréviation', 'hint' => 'Optionnel', 'value' => $item['abbr'] ?? '']); ?>
    <label class="ui-label grid-cols-[auto_1fr] items-center self-end font-medium">
      <input type="checkbox" name="is_published" class="rounded border-line" <?= !isset($item['is_published']) || (int) $item['is_published'] ? 'checked' : '' ?>>
      Visible sur le site
    </label>
  </div>

  <div class="ui-card grid gap-4 md:grid-cols-2">
    <h2 class="md:col-span-2 mb-0 mt-0 text-base font-semibold text-forest">Images</h2>
    <?php component('media-field', [
        'name' => 'cover_media_id',
        'pathName' => 'image_url',
        'label' => 'Photo de couverture',
        'mediaId' => (int) ($item['cover_media_id'] ?? 0),
        'path' => (string) $coverPath,
        'previewUrl' => (string) $coverPath,
    ]); ?>
    <?php component('media-field', [
        'name' => 'menu_media_id',
        'label' => 'Menu (PDF ou image)',
        'hint' => 'Document téléchargeable pour les clients.',
        'accept' => 'all',
        'mediaId' => (int) ($item['menu_media_id'] ?? 0),
        'path' => (string) $menuPath,
        'previewUrl' => (string) $menuPath,
    ]); ?>
  </div>

  <div class="ui-card">
    <div class="mb-4 flex gap-2 border-b border-line pb-2">
      <button type="button" class="rounded-md px-3 py-1.5 text-sm font-semibold" :class="tab==='fr' ? 'bg-forest text-cream-2' : 'text-muted'" @click="tab='fr'">Français</button>
      <button type="button" class="rounded-md px-3 py-1.5 text-sm font-semibold" :class="tab==='en' ? 'bg-forest text-cream-2' : 'text-muted'" @click="tab='en'">English</button>
    </div>
    <div class="grid gap-4" x-show="tab==='fr'">
      <?php component('input', ['name' => 'tagline_fr', 'label' => 'Accroche', 'value' => $item['tagline_fr'] ?? '']); ?>
      <?php component('input', ['name' => 'hours_fr', 'label' => 'Horaires', 'value' => $item['hours_fr'] ?? '']); ?>
      <?php component('richtext', ['name' => 'body_fr', 'label' => 'Description', 'value' => $item['body_fr'] ?? '']); ?>
    </div>
    <div class="grid gap-4" x-show="tab==='en'" style="display:none">
      <?php component('input', ['name' => 'name_en', 'label' => 'Name (English)', 'value' => $item['name_en'] ?? '']); ?>
      <?php component('input', ['name' => 'tagline_en', 'label' => 'Tagline', 'value' => $item['tagline_en'] ?? '']); ?>
      <?php component('input', ['name' => 'hours_en', 'label' => 'Hours', 'value' => $item['hours_en'] ?? '']); ?>
      <?php component('richtext', ['name' => 'body_en', 'label' => 'Description', 'value' => $item['body_en'] ?? '']); ?>
    </div>
  </div>

  <div class="ui-card grid gap-6">
    <?php component('repeater-lines', [
        'items' => $specs,
        'name' => 'specialties',
        'label' => 'Spécialités',
        'placeholder' => 'Ex. : Grillades',
    ]); ?>
    <?php component('repeater-lines', [
        'items' => $feats,
        'name' => 'features',
        'label' => 'Atouts',
        'placeholder' => 'Ex. : Vue piscine',
    ]); ?>
  </div>

  <?php
    ob_start();
    component('input', [
        'name' => 'slug',
        'label' => 'Adresse web',
        'required' => true,
        'value' => $item['slug'] ?? '',
        'attrs' => 'data-slug-target' . ($item ? ' data-slug-locked="1"' : ''),
    ]);
    component('input', ['type' => 'number', 'name' => 'sort_order', 'label' => 'Ordre d’affichage', 'value' => (string) ($item['sort_order'] ?? '0')]);
    component('input', ['name' => 'meta_title_fr', 'label' => 'Titre Google (FR)', 'value' => $item['meta_title_fr'] ?? '']);
    component('textarea', ['name' => 'meta_desc_fr', 'label' => 'Description Google (FR)', 'rows' => 2, 'value' => $item['meta_desc_fr'] ?? '']);
    component('input', ['name' => 'meta_title_en', 'label' => 'Google title (EN)', 'value' => $item['meta_title_en'] ?? '']);
    component('textarea', ['name' => 'meta_desc_en', 'label' => 'Google description (EN)', 'rows' => 2, 'value' => $item['meta_desc_en'] ?? '']);
    $advanced = ob_get_clean();
    component('accordion', ['title' => 'Référencement & options avancées', 'body' => $advanced]);
  ?>

  <?php component('form-actions', [
      'viewHref' => $viewHref,
      'backHref' => admin_url('restaurants'),
  ]); ?>
</form>
