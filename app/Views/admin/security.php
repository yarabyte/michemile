<?php component('page-header', [
    'title' => $title ?? 'Sécurité 2FA',
    'subtitle' => 'Authentification à deux facteurs TOTP',
]); ?>

<div class="ui-card max-w-xl">
  <p class="mt-0 text-sm text-muted">
    Statut 2FA :
    <?php component('badge', [
        'text' => !empty($enabled) ? 'activée' : 'désactivée',
        'variant' => !empty($enabled) ? 'ok' : 'warn',
    ]); ?>
  </p>

  <?php if (empty($enabled)): ?>
    <p class="text-sm text-ink">Scannez ce QR code avec votre application TOTP, puis saisissez un code.</p>
    <p><img class="rounded-md border border-line bg-white p-2" src="<?= e($qr) ?>" alt="QR 2FA" width="180" height="180"></p>
    <p class="text-sm">Secret : <code class="rounded bg-cream px-1.5 py-0.5 text-xs"><?= e($secret) ?></code></p>
    <form class="mt-4 grid max-w-sm gap-4" method="post" action="<?= e(admin_url('securite/activer')) ?>">
      <?= csrf_field() ?>
      <?php component('input', ['name' => 'totp', 'label' => 'Code', 'required' => true]); ?>
      <?php component('button', ['type' => 'submit', 'label' => 'Activer la 2FA']); ?>
    </form>
  <?php else: ?>
    <form method="post" action="<?= e(admin_url('securite/desactiver')) ?>">
      <?= csrf_field() ?>
      <?php component('button', ['type' => 'submit', 'variant' => 'danger', 'label' => 'Désactiver la 2FA']); ?>
    </form>
  <?php endif; ?>
</div>
