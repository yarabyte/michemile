<?php
$user = $user ?? \App\Core\Auth::user();
$navGroups = [
    'Contenu' => [
        ['' , 'Tableau de bord'],
        ['hebergements', 'Hébergements'],
        ['restaurants', 'Restaurants'],
        ['spa', 'Spa'],
        ['accueil', 'Page d’accueil'],
    ],
    'Ops' => [
        ['reservations', 'Réservations'],
        ['messages', 'Messages'],
        ['mediatheque', 'Médiathèque'],
    ],
    'Système' => [
        ['parametres', 'Paramètres'],
        ['utilisateurs', 'Utilisateurs'],
        ['securite', 'Sécurité 2FA'],
    ],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title><?= e($title ?? 'Admin') ?> — Micheline & Emile</title>
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <link rel="icon" href="<?= e(asset('img/favicon.png')) ?>" type="image/png">
  <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>?v=8">
  <script src="<?= e(asset('vendor/tinymce/tinymce.min.js')) ?>" defer></script>
  <script src="<?= e(asset('js/admin.js')) ?>?v=8" defer></script>
  <script src="<?= e(asset('js/alpine.min.js')) ?>" defer></script>
</head>
<body
  class="min-h-screen"
  x-data="{ navOpen: false }"
  data-admin-base="<?= e(rtrim(admin_url(), '/')) ?>"
>
<div class="min-h-screen lg:grid lg:grid-cols-[16.5rem_1fr]">
  <div
    class="fixed inset-0 z-40 bg-forest-dark/40 backdrop-blur-[1px] lg:hidden"
    x-show="navOpen"
    x-transition.opacity
    @click="navOpen = false"
    style="display:none"
  ></div>

  <aside
    class="fixed inset-y-0 left-0 z-50 flex w-[16.5rem] flex-col bg-forest text-cream-2 shadow-soft transition-transform duration-300 lg:static lg:translate-x-0"
    :class="navOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
  >
    <div class="flex items-center justify-between gap-2 border-b border-white/10 px-4 py-4">
      <a class="flex items-center gap-2.5 text-cream-2 no-underline" href="<?= e(admin_url()) ?>">
        <img src="<?= e(asset('img/logo.png')) ?>" alt="" width="36" height="36" class="h-9 w-9 rounded-full object-contain bg-black">
        <span class="text-base font-semibold tracking-tight">M&amp;E Admin</span>
      </a>
      <button type="button" class="ui-btn-ghost px-2 py-1 lg:hidden" @click="navOpen = false" aria-label="Fermer le menu">✕</button>
    </div>
    <nav class="flex-1 overflow-y-auto px-2 py-3">
      <?php foreach ($navGroups as $group => $links): ?>
        <div class="admin-nav-group"><?= e($group) ?></div>
        <?php foreach ($links as [$path, $label]): ?>
          <?php
            $active = admin_is_active($path);
            $href = $path === '' ? admin_url() : admin_url($path);
          ?>
          <a
            class="<?= e(ui_classes('admin-nav-link', $active ? 'admin-nav-link-active' : '')) ?>"
            href="<?= e($href) ?>"
            <?= $active ? 'aria-current="page"' : '' ?>
          ><?= e($label) ?></a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </nav>
    <div class="border-t border-white/10 p-3">
      <?php if ($user): ?>
        <div class="mb-2 px-2 text-xs text-cream-2/70">
          <div class="font-medium text-cream-2"><?= e((string) ($user['name'] ?? '')) ?></div>
          <div><?= e((string) ($user['email'] ?? '')) ?></div>
        </div>
      <?php endif; ?>
      <form method="post" action="<?= e(admin_url('logout')) ?>">
        <?= csrf_field() ?>
        <?php component('button', ['type' => 'submit', 'variant' => 'ghost', 'label' => 'Déconnexion', 'class' => 'w-full']); ?>
      </form>
    </div>
  </aside>

  <div class="flex min-w-0 flex-col">
    <header class="sticky top-0 z-30 flex items-center gap-3 border-b border-line bg-cream/90 px-4 py-3 backdrop-blur lg:hidden">
      <button type="button" class="ui-btn-secondary" @click="navOpen = true" aria-label="Ouvrir le menu">Menu</button>
      <div class="text-sm font-semibold text-forest"><?= e($title ?? 'Admin') ?></div>
    </header>
    <main class="flex-1 px-4 py-5 sm:px-6 lg:px-8">
      <?php component('alert', ['type' => 'ok', 'message' => (string) (flash('ok') ?? '')]); ?>
      <?php component('alert', ['type' => 'error', 'message' => (string) (flash('error') ?? '')]); ?>
      <?= $content ?>
    </main>
  </div>
</div>
<?php component('media-modal'); ?>
</body>
</html>
