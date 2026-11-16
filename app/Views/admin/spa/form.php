<?php
$item = $item ?? null;
$blocks = json_decode((string) ($item['blocks_json'] ?? '[]'), true) ?: [];
$coverPath = $item['image_url'] ?? '';
$viewHref = $item ? url('/spa/' . ($item['slug'] ?? '')) : null;
?>
<?php component('page-header', [
    'title' => $title ?? ($item ? 'Modifier page spa' : 'Nouvelle page spa'),
    'subtitle' => 'Prestations et textes du spa',
    'actionHref' => admin_url('spa'),
    'actionLabel' => 'Retour à la liste',
    'actionVariant' => 'secondary',
]); ?>

<form
  class="grid gap-4 pb-8"
  method="post"
  action="<?= e(admin_url('spa/sauver')) ?>"
  data-auto-slug
  x-data="langTabs('fr')"
>
  <?= csrf_field() ?>
  <?php if ($item): ?><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><?php endif; ?>
  <input type="hidden" name="legacy_path" value="<?= e((string) ($item['legacy_path'] ?? '')) ?>">

  <div class="ui-card grid gap-4 md:grid-cols-2">
    <h2 class="md:col-span-2 mb-0 mt-0 text-base font-semibold text-forest">Identité</h2>
    <?php component('input', [
        'name' => 'title_fr',
        'label' => 'Titre (français)',
        'required' => true,
        'value' => $item['title_fr'] ?? '',
        'attrs' => 'data-slug-source',
        'class' => 'md:col-span-2',
    ]); ?>
    <label class="ui-label grid-cols-[auto_1fr] items-center self-end font-medium">
      <input type="checkbox" name="is_published" class="rounded border-line" <?= !isset($item['is_published']) || (int) $item['is_published'] ? 'checked' : '' ?>>
      Visible sur le site
    </label>
  </div>

  <div class="ui-card">
    <?php component('media-field', [
        'name' => 'cover_media_id',
        'pathName' => 'image_url',
        'label' => 'Photo',
        'mediaId' => (int) ($item['cover_media_id'] ?? 0),
        'path' => (string) $coverPath,
        'previewUrl' => (string) $coverPath,
    ]); ?>
  </div>

  <div class="ui-card">
    <div class="mb-4 flex gap-2 border-b border-line pb-2">
      <button type="button" class="rounded-md px-3 py-1.5 text-sm font-semibold" :class="tab==='fr' ? 'bg-forest text-cream-2' : 'text-muted'" @click="tab='fr'">Français</button>
      <button type="button" class="rounded-md px-3 py-1.5 text-sm font-semibold" :class="tab==='en' ? 'bg-forest text-cream-2' : 'text-muted'" @click="tab='en'">English</button>
    </div>
    <div class="grid gap-4" x-show="tab==='fr'">
      <?php component('textarea', ['name' => 'lead_fr', 'label' => 'Chapô', 'required' => true, 'rows' => 3, 'value' => $item['lead_fr'] ?? '']); ?>
      <?php component('richtext', ['name' => 'body_fr', 'label' => 'Description', 'value' => $item['body_fr'] ?? '']); ?>
      <?php component('input', ['name' => 'cta_label_fr', 'label' => 'Texte du bouton', 'value' => $item['cta_label_fr'] ?? '']); ?>
    </div>
    <div class="grid gap-4" x-show="tab==='en'" style="display:none">
      <?php component('input', ['name' => 'title_en', 'label' => 'Title (English)', 'value' => $item['title_en'] ?? '']); ?>
      <?php component('textarea', ['name' => 'lead_en', 'label' => 'Lead', 'rows' => 3, 'value' => $item['lead_en'] ?? '']); ?>
      <?php component('richtext', ['name' => 'body_en', 'label' => 'Description', 'value' => $item['body_en'] ?? '']); ?>
      <?php component('input', ['name' => 'cta_label_en', 'label' => 'Button label', 'value' => $item['cta_label_en'] ?? '']); ?>
    </div>
  </div>

  <div class="ui-card">
    <?php component('repeater-blocks', ['items' => $blocks, 'name' => 'blocks']); ?>
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
      'backHref' => admin_url('spa'),
  ]); ?>
</form>
