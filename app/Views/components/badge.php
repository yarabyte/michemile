<?php
$text = $text ?? '';
$variant = $variant ?? 'muted';
$map = [
    'ok' => 'ui-badge ui-badge-ok',
    'muted' => 'ui-badge ui-badge-muted',
    'warn' => 'ui-badge ui-badge-warn',
    'danger' => 'ui-badge ui-badge-danger',
];
?>
<span class="<?= e($map[$variant] ?? $map['muted']) ?>"><?= e($text) ?></span>
