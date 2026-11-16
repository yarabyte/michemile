<?php
$name = $name ?? '';
$label = $label ?? '';
$value = $value ?? '';
$rows = (int) ($rows ?? 4);
$required = !empty($required);
$hint = $hint ?? '';
$class = $class ?? '';
$attrs = $attrs ?? '';
$id = $id ?? ($name !== '' ? 'f-' . preg_replace('/[^a-z0-9_-]+/i', '-', $name) : '');
?>
<label class="ui-label <?= e($class) ?>" <?php if ($id): ?>for="<?= e($id) ?>"<?php endif; ?>>
  <span><?= e($label) ?><?php if ($required): ?> <span class="text-danger">*</span><?php endif; ?></span>
  <?php if ($hint !== ''): ?><span class="ui-label-hint"><?= e($hint) ?></span><?php endif; ?>
  <textarea
    class="ui-textarea"
    name="<?= e($name) ?>"
    rows="<?= $rows ?>"
    <?php if ($id): ?>id="<?= e($id) ?>"<?php endif; ?>
    <?= $required ? 'required' : '' ?>
    <?= $attrs ?>
  ><?= e((string) $value) ?></textarea>
</label>
