<div class="grid min-h-screen place-items-center px-4 py-10">
  <div class="w-full max-w-md rounded-xl border border-line bg-white p-6 shadow-soft sm:p-8">
    <div class="mb-6 text-center">
      <img src="<?= e(asset('img/logo.png')) ?>" alt="Micheline & Emile" width="72" height="72" class="mx-auto mb-3 h-[4.5rem] w-[4.5rem] rounded-full object-contain bg-black">
      <div class="text-xs font-semibold uppercase tracking-[0.16em] text-gold">Micheline &amp; Emile</div>
      <h1 class="mt-2 mb-1 text-2xl font-semibold text-forest">Connexion admin</h1>
      <p class="m-0 text-sm text-muted">Accès réservé à l’équipe.</p>
    </div>
    <?php if (!empty($error)): ?>
      <?php component('alert', ['type' => 'error', 'message' => (string) $error]); ?>
    <?php endif; ?>
    <form method="post" action="<?= e(admin_url('login')) ?>" class="grid gap-4">
      <?= csrf_field() ?>
      <?php if (!empty($needs2fa)): ?>
        <?php component('input', [
            'name' => 'totp',
            'label' => 'Code 2FA',
            'required' => true,
            'attrs' => 'inputmode="numeric" autocomplete="one-time-code"',
        ]); ?>
      <?php else: ?>
        <?php component('input', [
            'type' => 'email',
            'name' => 'email',
            'label' => 'E-mail',
            'required' => true,
            'attrs' => 'autocomplete="username"',
        ]); ?>
        <?php component('input', [
            'type' => 'password',
            'name' => 'password',
            'label' => 'Mot de passe',
            'required' => true,
            'attrs' => 'autocomplete="current-password"',
        ]); ?>
      <?php endif; ?>
      <?php component('button', ['type' => 'submit', 'label' => 'Se connecter', 'class' => 'w-full']); ?>
    </form>
  </div>
</div>
