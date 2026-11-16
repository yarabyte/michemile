<?php
$name = $name ?? '';
$label = $label ?? '';
$type = $type ?? 'text';
$value = $value ?? '';
$required = !empty($required);
$hint = $hint ?? '';
$class = $class ?? '';
$attrs = $attrs ?? '';
$id = $id ?? ($name !== '' ? 'f-' . preg_replace('/[^a-z0-9_-]+/i', '-', $name) : '');
?>
<label class="ui-label <?= e($class) ?>" <?php if ($id): ?>for="<?= e($id) ?>"<?php endif; ?>>
  <span><?= e($label) ?><?php if ($required): ?> <span class="text-danger">*</span><?php endif; ?></span>
  <?php if ($hint !== ''): ?><span class="ui-label-hint"><?= e($hint) ?></span><?php endif; ?>
  <input
    class="ui-input"
    type="<?= e($type) ?>"
    name="<?= e($name) ?>"
    <?php if ($id): ?>id="<?= e($id) ?>"<?php endif; ?>
    value="<?= e((string) $value) ?>"
    <?= $required ? 'required' : '' ?>
    <?= $attrs ?>
  >
</label>
