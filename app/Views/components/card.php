<?php
$title = $title ?? '';
$class = $class ?? '';
$body = $body ?? '';
?>
<section class="ui-card <?= e($class) ?>">
  <?php if ($title !== ''): ?>
    <h2 class="mb-3 text-base font-semibold text-forest"><?= e($title) ?></h2>
  <?php endif; ?>
  <?= $body ?>
</section>
