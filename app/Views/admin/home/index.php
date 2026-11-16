<?php
/** @var array $byKey */
/** @var array $serviceIcons */
$byKey = $byKey ?? [];
$heroImage = $heroImage ?? '';
$serviceIcons = $serviceIcons ?? [];
$order = ['hero', 'amenities', 'intro', 'services', 'rooms', 'restaurants', 'spa', 'cta'];
?>
<?php component('page-header', [
    'title' => $title ?? 'Page d’accueil',
    'subtitle' => 'Modifiez les textes et images de la page d’accueil, dans l’ordre du site',
    'actionHref' => url('/'),
    'actionLabel' => 'Voir le site',
    'actionVariant' => 'secondary',
]); ?>

<nav class="filter-tabs mb-5" aria-label="Aller à une section">
  <?php foreach ($order as $key):
    if (empty($byKey[$key])) continue;
  ?>
    <a class="filter-tab" href="#<?= e($key) ?>"><?= e(home_section_label($key)) ?></a>
  <?php endforeach; ?>
</nav>

<div class="grid gap-5">
<?php foreach ($order as $key):
  $item = $byKey[$key] ?? null;
  if (!$item) continue;
  $extra = \App\Models\HomeSection::extra($item);
  $mediaPath = '';
  if (!empty($item['media_id'])) {
      $m = \App\Core\Database::fetch('SELECT public_path FROM media WHERE id=?', [(int) $item['media_id']]);
      $mediaPath = $m['public_path'] ?? '';
  }
  if ($key === 'hero' && $mediaPath === '' && $heroImage !== '') {
      $mediaPath = $heroImage;
  }
?>
  <form
    id="<?= e($key) ?>"
    class="ui-card grid gap-4 scroll-mt-24"
    method="post"
    action="<?= e(admin_url('accueil/sauver')) ?>"
    x-data="langTabs('fr')"
  >
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
    <input type="hidden" name="sort_order" value="<?= (int) $item['sort_order'] ?>">

    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-line pb-3">
      <div>
        <h2 class="mb-1 mt-0 text-base font-semibold text-forest"><?= e(home_section_label($key)) ?></h2>
        <p class="m-0 text-sm text-muted"><?= e(home_section_help($key)) ?></p>
      </div>
      <label class="ui-label grid-cols-[auto_1fr] items-center font-medium">
        <input type="checkbox" name="is_enabled" class="rounded border-line" <?= (int) $item['is_enabled'] ? 'checked' : '' ?>>
        Visible sur le site
      </label>
    </div>

    <?php if ($key === 'hero'):
      $slides = $extra['slides'] ?? [];
      if (!is_array($slides)) $slides = [];
      $slides = array_values(array_filter(array_map('strval', $slides)));
      if ($slides === [] && $mediaPath !== '') {
          $slides = [$mediaPath];
      }
      if ($slides === [] && $heroImage !== '') {
          $slides = [$heroImage];
      }
      $slides = array_slice($slides, 0, 3);
    ?>
      <?php component('gallery-field', [
          'items' => $slides,
          'name' => 'slides',
          'label' => 'Photos du slider (max 3)',
          'hint' => 'Ajoutez jusqu’à 3 images. Elles défilent en haut de la page d’accueil.',
          'max' => 3,
          'firstBadge' => '1re',
          'addLabel' => 'Ajouter une image',
      ]); ?>
      <div class="flex gap-2 border-b border-line pb-2">
        <button type="button" class="rounded-md px-3 py-1.5 text-sm font-semibold" :class="tab==='fr' ? 'bg-forest text-cream-2' : 'text-muted'" @click="tab='fr'">Français</button>
        <button type="button" class="rounded-md px-3 py-1.5 text-sm font-semibold" :class="tab==='en' ? 'bg-forest text-cream-2' : 'text-muted'" @click="tab='en'">English</button>
      </div>
      <div class="grid gap-4" x-show="tab==='fr'">
        <?php component('input', ['name' => 'title_fr', 'label' => 'Titre principal', 'hint' => 'Souvent le nom de la marque', 'value' => $item['title_fr'] ?? '']); ?>
        <?php component('textarea', ['name' => 'subtitle_fr', 'label' => 'Phrase d’accroche', 'rows' => 3, 'value' => $item['subtitle_fr'] ?? '']); ?>
      </div>
      <div class="grid gap-4" x-show="tab==='en'" style="display:none">
        <?php component('input', ['name' => 'title_en', 'label' => 'Main title', 'value' => $item['title_en'] ?? '']); ?>
        <?php component('textarea', ['name' => 'subtitle_en', 'label' => 'Lead text', 'rows' => 3, 'value' => $item['subtitle_en'] ?? '']); ?>
      </div>

    <?php elseif ($key === 'intro'):
      $introPreview = $mediaPath !== '' ? $mediaPath : default_intro_image();
    ?>
      <?php component('media-field', [
          'name' => 'media_id',
          'label' => 'Photo de la résidence',
          'hint' => 'Photo à droite du texte « La résidence ».',
          'mediaId' => (int) ($item['media_id'] ?? 0),
          'path' => $mediaPath,
          'previewUrl' => $introPreview,
      ]); ?>
      <div class="flex gap-2 border-b border-line pb-2">
        <button type="button" class="rounded-md px-3 py-1.5 text-sm font-semibold" :class="tab==='fr' ? 'bg-forest text-cream-2' : 'text-muted'" @click="tab='fr'">Français</button>
        <button type="button" class="rounded-md px-3 py-1.5 text-sm font-semibold" :class="tab==='en' ? 'bg-forest text-cream-2' : 'text-muted'" @click="tab='en'">English</button>
      </div>
      <div class="grid gap-4" x-show="tab==='fr'">
        <?php component('input', ['name' => 'title_fr', 'label' => 'Titre', 'value' => $item['title_fr'] ?? '']); ?>
        <?php component('richtext', ['name' => 'body_fr', 'label' => 'Texte de présentation', 'rows' => 6, 'value' => $item['body_fr'] ?? '']); ?>
        <?php component('textarea', ['name' => 'quote_fr', 'label' => 'Citation mise en avant', 'rows' => 2, 'hint' => 'Affichée en italique sous le texte', 'value' => $extra['quote_fr'] ?? '']); ?>
      </div>
      <div class="grid gap-4" x-show="tab==='en'" style="display:none">
        <?php component('input', ['name' => 'title_en', 'label' => 'Title', 'value' => $item['title_en'] ?? '']); ?>
        <?php component('richtext', ['name' => 'body_en', 'label' => 'Body', 'rows' => 6, 'value' => $item['body_en'] ?? '']); ?>
        <?php component('textarea', ['name' => 'quote_en', 'label' => 'Quote', 'rows' => 2, 'value' => $extra['quote_en'] ?? '']); ?>
      </div>

    <?php elseif ($key === 'amenities'):
      $itemsList = $extra['items'] ?? [];
      if (!$itemsList) {
          $itemsList = [['icon' => 'pool', 'label_fr' => '', 'label_en' => '']];
      }
      $initial = json_encode($itemsList, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS);
      $pathsJson = json_encode(ui_icon_paths(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS);
      $blankAmenity = json_encode(['icon' => 'check', 'label_fr' => '', 'label_en' => ''], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS);
    ?>
      <div class="grid gap-3" x-data="iconRows(<?= e($initial) ?>, <?= e($pathsJson) ?>, <?= e($blankAmenity) ?>)">
        <p class="m-0 text-sm text-muted">Une ligne = une pastille sous la bannière.</p>
        <template x-for="(row, i) in rows" :key="i">
          <div class="grid gap-2 rounded-md border border-line bg-cream/40 p-3 sm:grid-cols-[auto_9rem_1fr_1fr_auto] sm:items-center">
            <span class="icon-preview" aria-hidden="true" x-html="iconSvg(row.icon)"></span>
            <select class="ui-select" :name="'amenity['+i+'][icon]'" x-model="row.icon">
              <?php foreach ($serviceIcons as $val => $lab): ?>
                <option value="<?= e($val) ?>"><?= e($lab) ?></option>
              <?php endforeach; ?>
            </select>
            <input class="ui-input" type="text" :name="'amenity['+i+'][label_fr]'" x-model="row.label_fr" placeholder="Libellé FR">
            <input class="ui-input" type="text" :name="'amenity['+i+'][label_en]'" x-model="row.label_en" placeholder="Label EN">
            <button type="button" class="ui-btn-secondary" @click="remove(i)">−</button>
          </div>
        </template>
        <button type="button" class="ui-btn-secondary w-fit" @click="add()">+ Ajouter</button>
      </div>

    <?php elseif ($key === 'services'):
      $itemsList = $extra['items'] ?? [];
      if (!$itemsList) {
          $itemsList = [['icon' => 'wifi', 'title_fr' => '', 'title_en' => '', 'text_fr' => '', 'text_en' => '']];
      }
      $initial = json_encode($itemsList, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS);
      $pathsJson = json_encode(ui_icon_paths(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS);
      $blankService = json_encode(['icon' => 'check', 'title_fr' => '', 'title_en' => '', 'text_fr' => '', 'text_en' => ''], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS);
    ?>
      <div class="grid gap-3" x-data="iconRows(<?= e($initial) ?>, <?= e($pathsJson) ?>, <?= e($blankService) ?>)">
        <p class="m-0 text-sm text-muted">Cartes affichées sous « La résidence ».</p>
        <template x-for="(row, i) in rows" :key="i">
          <div class="grid gap-2 rounded-md border border-line bg-cream/40 p-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <div class="flex items-center gap-2">
                <span class="icon-preview" aria-hidden="true" x-html="iconSvg(row.icon)"></span>
                <select class="ui-select max-w-[12rem]" :name="'service['+i+'][icon]'" x-model="row.icon">
                  <?php foreach ($serviceIcons as $val => $lab): ?>
                    <option value="<?= e($val) ?>"><?= e($lab) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <button type="button" class="ui-btn-secondary" @click="remove(i)">Retirer</button>
            </div>
            <div class="grid gap-2 md:grid-cols-2">
              <input class="ui-input" type="text" :name="'service['+i+'][title_fr]'" x-model="row.title_fr" placeholder="Titre FR">
              <input class="ui-input" type="text" :name="'service['+i+'][title_en]'" x-model="row.title_en" placeholder="Title EN">
              <textarea class="ui-textarea" rows="2" :name="'service['+i+'][text_fr]'" x-model="row.text_fr" placeholder="Texte FR"></textarea>
              <textarea class="ui-textarea" rows="2" :name="'service['+i+'][text_en]'" x-model="row.text_en" placeholder="Text EN"></textarea>
            </div>
          </div>
        </template>
        <button type="button" class="ui-btn-secondary w-fit" @click="add()">+ Ajouter un service</button>
      </div>

    <?php elseif (in_array($key, ['rooms', 'restaurants', 'spa'], true)): ?>
      <div class="flex gap-2 border-b border-line pb-2">
        <button type="button" class="rounded-md px-3 py-1.5 text-sm font-semibold" :class="tab==='fr' ? 'bg-forest text-cream-2' : 'text-muted'" @click="tab='fr'">Français</button>
        <button type="button" class="rounded-md px-3 py-1.5 text-sm font-semibold" :class="tab==='en' ? 'bg-forest text-cream-2' : 'text-muted'" @click="tab='en'">English</button>
      </div>
      <div class="grid gap-4" x-show="tab==='fr'">
        <?php component('input', ['name' => 'title_fr', 'label' => 'Titre de section', 'value' => $item['title_fr'] ?? '']); ?>
        <?php component('textarea', ['name' => 'subtitle_fr', 'label' => 'Phrase d’introduction', 'rows' => 3, 'value' => $item['subtitle_fr'] ?? '']); ?>
      </div>
      <div class="grid gap-4" x-show="tab==='en'" style="display:none">
        <?php component('input', ['name' => 'title_en', 'label' => 'Section title', 'value' => $item['title_en'] ?? '']); ?>
        <?php component('textarea', ['name' => 'subtitle_en', 'label' => 'Intro sentence', 'rows' => 3, 'value' => $item['subtitle_en'] ?? '']); ?>
      </div>

    <?php elseif ($key === 'cta'):
      $pointsFr = $extra['points_fr'] ?? [];
      $pointsEn = $extra['points_en'] ?? [];
    ?>
      <div class="flex gap-2 border-b border-line pb-2">
        <button type="button" class="rounded-md px-3 py-1.5 text-sm font-semibold" :class="tab==='fr' ? 'bg-forest text-cream-2' : 'text-muted'" @click="tab='fr'">Français</button>
        <button type="button" class="rounded-md px-3 py-1.5 text-sm font-semibold" :class="tab==='en' ? 'bg-forest text-cream-2' : 'text-muted'" @click="tab='en'">English</button>
      </div>
      <div class="grid gap-4" x-show="tab==='fr'">
        <?php component('input', ['name' => 'title_fr', 'label' => 'Titre', 'value' => $item['title_fr'] ?? '']); ?>
        <?php component('textarea', ['name' => 'subtitle_fr', 'label' => 'Texte', 'rows' => 3, 'value' => $item['subtitle_fr'] ?? '']); ?>
        <?php component('repeater-lines', [
            'items' => $pointsFr,
            'name' => 'points_fr',
            'label' => 'Points forts',
            'placeholder' => 'Ex. : Réponse sous 24 h',
        ]); ?>
      </div>
      <div class="grid gap-4" x-show="tab==='en'" style="display:none">
        <?php component('input', ['name' => 'title_en', 'label' => 'Title', 'value' => $item['title_en'] ?? '']); ?>
        <?php component('textarea', ['name' => 'subtitle_en', 'label' => 'Text', 'rows' => 3, 'value' => $item['subtitle_en'] ?? '']); ?>
        <?php component('repeater-lines', [
            'items' => $pointsEn,
            'name' => 'points_en',
            'label' => 'Highlights',
            'placeholder' => 'e.g. Reply within 24 h',
        ]); ?>
      </div>
    <?php endif; ?>

    <div>
      <?php component('button', ['type' => 'submit', 'label' => 'Enregistrer cette section']); ?>
    </div>
  </form>
<?php endforeach; ?>
</div>
