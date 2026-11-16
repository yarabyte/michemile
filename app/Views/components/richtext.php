<?php
$name = $name ?? '';
$label = $label ?? '';
$value = $value ?? '';
$required = !empty($required);
$hint = $hint ?? 'Éditeur type Word : gras, listes, titres, liens, images…';
$rows = (int) ($rows ?? 8);
$class = $class ?? '';
$id = $id ?? ($name !== '' ? 'f-' . preg_replace('/[^a-z0-9_-]+/i', '-', $name) : '');
?>
<label class="ui-label <?= e($class) ?>">
  <span><?= e($label) ?><?php if ($required): ?> <span class="text-danger">*</span><?php endif; ?></span>
  <?php if ($hint !== ''): ?><span class="ui-label-hint"><?= e($hint) ?></span><?php endif; ?>
  <textarea
    class="ui-textarea"
    name="<?= e($name) ?>"
    <?php if ($id): ?>id="<?= e($id) ?>"<?php endif; ?>
    rows="<?= $rows ?>"
    data-richtext
    <?= $required ? 'required' : '' ?>
  ><?= e((string) $value) ?></textarea>
</label>
