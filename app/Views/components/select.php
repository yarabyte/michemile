<?php
$name = $name ?? '';
$label = $label ?? '';
$options = $options ?? [];
$value = $value ?? '';
$required = !empty($required);
$class = $class ?? '';
$attrs = $attrs ?? '';
$id = $id ?? ($name !== '' ? 'f-' . preg_replace('/[^a-z0-9_-]+/i', '-', $name) : '');
?>
<label class="ui-label <?= e($class) ?>" <?php if ($id): ?>for="<?= e($id) ?>"<?php endif; ?>>
  <span><?= e($label) ?><?php if ($required): ?> <span class="text-danger">*</span><?php endif; ?></span>
  <select
    class="ui-select"
    name="<?= e($name) ?>"
    <?php if ($id): ?>id="<?= e($id) ?>"<?php endif; ?>
    <?= $required ? 'required' : '' ?>
    <?= $attrs ?>
  >
    <?php foreach ($options as $optValue => $optLabel): ?>
      <option value="<?= e((string) $optValue) ?>" <?= (string) $value === (string) $optValue ? 'selected' : '' ?>>
        <?= e((string) $optLabel) ?>
      </option>
    <?php endforeach; ?>
  </select>
</label>
