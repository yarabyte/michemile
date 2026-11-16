<?php
$items = $items ?? [];
$name = $name ?? 'items';
$label = $label ?? 'Liste';
$hint = $hint ?? '';
$placeholder = $placeholder ?? '';
$initial = json_encode(array_values($items), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS);
?>
<div class="grid gap-3" x-data="lineRepeater(<?= e($initial) ?>)">
  <div>
    <div class="text-sm font-semibold text-ink"><?= e($label) ?></div>
    <?php if ($hint !== ''): ?><div class="ui-label-hint"><?= e($hint) ?></div><?php endif; ?>
  </div>
  <template x-for="(row, i) in rows" :key="i">
    <div class="flex gap-2">
      <input class="ui-input" type="text" :name="'<?= e($name) ?>[]'" x-model="rows[i]" placeholder="<?= e($placeholder) ?>">
      <button type="button" class="ui-btn-secondary shrink-0" @click="remove(i)" title="Retirer">−</button>
    </div>
  </template>
  <button type="button" class="ui-btn-secondary w-fit" @click="add()">+ Ajouter</button>
</div>
