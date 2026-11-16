<?php
$title = $title ?? 'Avancé';
$open = !empty($open);
$body = $body ?? '';
?>
<details class="ui-accordion ui-card" <?= $open ? 'open' : '' ?>>
  <summary class="ui-accordion-summary"><?= e($title) ?></summary>
  <div class="ui-accordion-body grid gap-4">
    <?= $body ?>
  </div>
</details>
