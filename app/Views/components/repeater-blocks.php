<?php
$items = $items ?? [];
$name = $name ?? 'blocks';
$label = $label ?? 'Blocs de prestations';
$hint = $hint ?? 'Chaque bloc a un titre et une liste (une prestation par ligne).';
$initial = json_encode(array_values($items), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS);
?>
<div class="grid gap-4" x-data="blocksRepeater(<?= e($initial) ?>)">
  <div>
    <div class="text-sm font-semibold text-ink"><?= e($label) ?></div>
    <?php if ($hint !== ''): ?><div class="ui-label-hint"><?= e($hint) ?></div><?php endif; ?>
  </div>
  <template x-for="(block, i) in blocks" :key="i">
    <div class="rounded-md border border-line bg-cream/40 p-3 grid gap-3">
      <div class="flex items-start justify-between gap-2">
        <div class="text-sm font-semibold text-forest">Bloc <span x-text="i + 1"></span></div>
        <button type="button" class="ui-btn-secondary" @click="remove(i)">Retirer</button>
      </div>
      <input class="ui-input" type="text" :name="'<?= e($name) ?>[' + i + '][title]'" x-model="block.title" placeholder="Titre du bloc">
      <input class="ui-input" type="text" :name="'<?= e($name) ?>[' + i + '][note]'" x-model="block.note" placeholder="Note (optionnel)">
      <textarea class="ui-textarea" rows="4" :name="'<?= e($name) ?>[' + i + '][items]'" x-model="block.items" placeholder="Une prestation par ligne"></textarea>
    </div>
  </template>
  <button type="button" class="ui-btn-secondary w-fit" @click="add()">+ Ajouter un bloc</button>
</div>
