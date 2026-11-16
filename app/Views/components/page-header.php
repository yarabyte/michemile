<?php
$title = $title ?? '';
$subtitle = $subtitle ?? '';
$actionHref = $actionHref ?? null;
$actionLabel = $actionLabel ?? '';
$actionVariant = $actionVariant ?? 'primary';
?>
<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
  <div>
    <h1 class="m-0 text-2xl font-semibold tracking-tight text-forest"><?= e($title) ?></h1>
    <?php if ($subtitle !== ''): ?>
      <p class="mt-1 mb-0 text-sm text-muted"><?= e($subtitle) ?></p>
    <?php endif; ?>
  </div>
  <?php if ($actionHref && $actionLabel !== ''): ?>
    <?php component('button', ['href' => $actionHref, 'label' => $actionLabel, 'variant' => $actionVariant]); ?>
  <?php endif; ?>
</div>
