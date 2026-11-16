<?php
$name = $name ?? 'cover_media_id';
$pathName = $pathName ?? null;
$label = $label ?? 'Image';
$hint = $hint ?? 'Choisissez une image ou envoyez-en une nouvelle.';
$accept = $accept ?? 'image';
$mediaId = (int) ($mediaId ?? 0);
$path = $path ?? '';
$previewUrl = $previewUrl ?? $path;
$idBase = 'mf-' . preg_replace('/[^a-z0-9_-]+/i', '-', $name);
$isImage = $previewUrl !== '' && !preg_match('/\.pdf($|\?)/i', $previewUrl);
$pickArgs = json_encode($idBase . '-id') . ',' . json_encode($idBase . '-preview') . ',' . ($pathName ? json_encode($idBase . '-path') : 'null') . ',' . json_encode($accept);
?>
<div class="grid gap-2">
  <div class="text-sm font-semibold text-ink"><?= e($label) ?></div>
  <?php if ($hint !== ''): ?><div class="ui-label-hint"><?= e($hint) ?></div><?php endif; ?>
  <input type="hidden" name="<?= e($name) ?>" id="<?= e($idBase) ?>-id" value="<?= $mediaId ?: '' ?>">
  <?php if ($pathName): ?>
    <input type="hidden" name="<?= e($pathName) ?>" id="<?= e($idBase) ?>-path" value="<?= e($path) ?>">
  <?php endif; ?>
  <div class="flex flex-wrap items-start gap-3">
    <div id="<?= e($idBase) ?>-preview" class="media-preview-box">
      <?php if ($previewUrl !== '' && $isImage): ?>
        <img class="media-preview-img" src="<?= e($previewUrl) ?>" alt="">
      <?php elseif ($previewUrl !== ''): ?>
        <span class="px-2 text-center text-xs text-muted">Fichier sélectionné</span>
      <?php else: ?>
        <span class="text-xs text-muted">Aucun fichier</span>
      <?php endif; ?>
    </div>
    <div class="flex flex-col gap-2">
      <?php component('button', [
          'type' => 'button',
          'variant' => 'secondary',
          'label' => 'Choisir / envoyer',
          'attrs' => 'onclick="AdminMedia.pickInto(' . e($pickArgs) . ')"',
      ]); ?>
      <?php if ($mediaId || $path !== '' || $previewUrl !== ''): ?>
      <button
        type="button"
        class="text-left text-sm font-semibold text-danger"
        onclick="document.getElementById(<?= e(json_encode($idBase . '-id')) ?>).value=''; var p=document.getElementById(<?= e(json_encode($idBase . '-path')) ?>); if(p) p.value=''; document.getElementById(<?= e(json_encode($idBase . '-preview')) ?>).innerHTML='<span class=&quot;text-xs text-muted&quot;>Aucun fichier</span>'; this.remove();"
      >Retirer</button>
      <?php endif; ?>
    </div>
  </div>
</div>
