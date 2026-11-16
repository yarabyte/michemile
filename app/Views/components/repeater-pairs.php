<?php
$items = $items ?? [];
$name = $name ?? 'pairs';
$label = $label ?? 'Informations';
$hint = $hint ?? '';
$leftLabel = $leftLabel ?? 'Libellé';
$rightLabel = $rightLabel ?? 'Valeur';
$initial = json_encode(array_values($items), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS);
?>
<div class="grid gap-3" x-data="pairRepeater(<?= e($initial) ?>)">
  <div>
    <div class="text-sm font-semibold text-ink"><?= e($label) ?></div>
    <?php if ($hint !== ''): ?><div class="ui-label-hint"><?= e($hint) ?></div><?php endif; ?>
  </div>
  <template x-for="(row, i) in rows" :key="i">
    <div class="grid gap-2 sm:grid-cols-[1fr_1fr_auto]">
      <input class="ui-input" type="text" :name="'<?= e($name) ?>[' + i + '][left]'" x-model="row.left" :placeholder="<?= e(json_encode($leftLabel)) ?>">
      <input class="ui-input" type="text" :name="'<?= e($name) ?>[' + i + '][right]'" x-model="row.right" :placeholder="<?= e(json_encode($rightLabel)) ?>">
      <button type="button" class="ui-btn-secondary" @click="remove(i)" title="Retirer">−</button>
    </div>
  </template>
  <button type="button" class="ui-btn-secondary w-fit" @click="add()">+ Ajouter une ligne</button>
</div>
