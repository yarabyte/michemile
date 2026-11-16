<?php
$items = $items ?? [];
$name = $name ?? 'gallery';
$label = $label ?? 'Photos';
$hint = $hint ?? 'La première photo est utilisée en couverture sur le site.';
$max = isset($max) ? (int) $max : 0;
$firstBadge = $firstBadge ?? 'Couverture';
$addLabel = $addLabel ?? 'Ajouter des photos';
$initial = json_encode(array_values($items), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS);
$maxArg = $max > 0 ? (string) $max : '0';
?>
<div class="grid gap-3" x-data="galleryField(<?= e($initial) ?>, <?= e($maxArg) ?>)">
  <div>
    <div class="text-sm font-semibold text-ink"><?= e($label) ?></div>
    <?php if ($hint !== ''): ?><div class="ui-label-hint"><?= e($hint) ?></div><?php endif; ?>
  </div>
  <div class="flex flex-wrap gap-3" x-show="items.length">
    <template x-for="(url, i) in items" :key="url + '-' + i">
      <div class="gallery-thumb">
        <img :src="url" alt="" class="gallery-thumb-img">
        <div class="gallery-thumb-actions">
          <button type="button" class="richtext-btn" @click="move(i, -1)" title="Monter">↑</button>
          <button type="button" class="richtext-btn" @click="move(i, 1)" title="Descendre">↓</button>
          <button type="button" class="richtext-btn" @click="remove(i)" title="Retirer">✕</button>
        </div>
        <input type="hidden" :name="'<?= e($name) ?>[]'" :value="url">
        <span class="gallery-thumb-badge" x-show="i === 0"><?= e($firstBadge) ?></span>
      </div>
    </template>
  </div>
  <p class="m-0 text-sm text-muted" x-show="!items.length">Aucune photo pour l’instant.</p>
  <p class="m-0 text-sm text-muted" x-show="max && items.length >= max">Maximum <?= (int) $max ?> images atteint.</p>
  <button
    type="button"
    class="ui-btn-secondary w-fit"
    @click="addFromPicker()"
    x-show="!max || items.length < max"
  ><?= e($addLabel) ?></button>
</div>
