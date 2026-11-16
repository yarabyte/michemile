<?php
$saveLabel = $saveLabel ?? 'Enregistrer';
$viewHref = $viewHref ?? null;
$backHref = $backHref ?? null;
?>
<div class="form-actions sticky bottom-0 z-20 -mx-4 mt-2 border-t border-line bg-cream-2/95 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
  <div class="flex flex-wrap items-center gap-3">
    <?php component('button', ['type' => 'submit', 'label' => $saveLabel]); ?>
    <?php if ($viewHref): ?>
      <?php component('button', ['href' => $viewHref, 'label' => 'Voir sur le site', 'variant' => 'secondary', 'attrs' => 'target="_blank" rel="noopener"']); ?>
    <?php endif; ?>
    <?php if ($backHref): ?>
      <?php component('button', ['href' => $backHref, 'label' => 'Retour à la liste', 'variant' => 'secondary']); ?>
    <?php endif; ?>
  </div>
</div>
