<?php
$href = $href ?? null;
$type = $type ?? 'button';
$variant = $variant ?? 'primary';
$class = $class ?? '';
$attrs = $attrs ?? '';
$label = $label ?? '';
$map = [
    'primary' => 'ui-btn',
    'secondary' => 'ui-btn-secondary',
    'danger' => 'ui-btn-danger',
    'ghost' => 'ui-btn-ghost',
];
$classes = ui_classes($map[$variant] ?? $map['primary'], $class);
?>
<?php if ($href !== null): ?>
  <a class="<?= e($classes) ?>" href="<?= e($href) ?>" <?= $attrs ?>><?= e((string) $label) ?></a>
<?php else: ?>
  <button class="<?= e($classes) ?>" type="<?= e($type) ?>" <?= $attrs ?>><?= e((string) $label) ?></button>
<?php endif; ?>
