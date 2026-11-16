<?php
$items = $items ?? [];
$name = $name ?? 'equipment';
$label = $label ?? 'Équipements';
$hint = $hint ?? 'Ex. : Wi‑Fi, climatisation, cuisine équipée…';
$icons = equipment_icon_options();
$paths = ui_icon_paths();
$initial = json_encode(array_values($items), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS);
$pathsJson = json_encode($paths, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS);
?>
<div class="grid gap-3" x-data="equipRepeater(<?= e($initial) ?>, <?= e($pathsJson) ?>)">
  <div>
    <div class="text-sm font-semibold text-ink"><?= e($label) ?></div>
    <?php if ($hint !== ''): ?><div class="ui-label-hint"><?= e($hint) ?></div><?php endif; ?>
  </div>
  <template x-for="(row, i) in rows" :key="i">
    <div class="grid gap-2 sm:grid-cols-[auto_10rem_1fr_auto] sm:items-center">
      <span class="icon-preview" aria-hidden="true" x-html="iconSvg(row.icon)"></span>
      <select class="ui-select" :name="'<?= e($name) ?>[' + i + '][icon]'" x-model="row.icon">
        <?php foreach ($icons as $val => $lab): ?>
          <option value="<?= e($val) ?>"><?= e($lab) ?></option>
        <?php endforeach; ?>
      </select>
      <input class="ui-input" type="text" :name="'<?= e($name) ?>[' + i + '][label]'" x-model="row.label" placeholder="Libellé affiché">
      <button type="button" class="ui-btn-secondary" @click="remove(i)" title="Retirer">−</button>
    </div>
  </template>
  <button type="button" class="ui-btn-secondary w-fit" @click="add()">+ Ajouter un équipement</button>
</div>
