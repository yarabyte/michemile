<?php
use App\Core\I18n;
use App\Services\Settings;
$locale = I18n::locale();
$phone = Settings::get('phone', '+237 653 29 02 58 / 222 20 86 71');
$whatsapp = preg_replace('/\D+/', '', (string) Settings::get('whatsapp', '237653290258'));
$title = $title ?? 'Micheline & Emile';
$metaDescription = $metaDescription ?? __('layout.meta_default');
$canonical = $canonical ?? url('/');
$ogImage = absolute_url(!empty($ogImage) ? (string) $ogImage : asset('img/og-default.jpg'));
$ogType = $ogType ?? 'website';
$ogImageAlt = $ogImageAlt ?? $title;
$ogLocale = $locale === 'en' ? 'en_US' : 'fr_FR';
$siteName = 'Micheline & Emile';
$noindex = !empty($noindex);
$isDefaultOg = str_ends_with(parse_url($ogImage, PHP_URL_PATH) ?: '', '/og-default.jpg');
$phoneTel = preg_replace('/\s+/', '', explode('/', $phone)[0] ?? '');
$path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
$navActive = static function (string $prefix) use ($path): bool {
    $prefix = rtrim($prefix, '/') . '/';
    if ($prefix === '/') {
        return $path === '/' || $path === '';
    }
    return str_starts_with(rtrim($path, '/') . '/', $prefix);
};
?><!DOCTYPE html>
<html lang="<?= e($locale) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?></title>
  <meta name="description" content="<?= e($metaDescription) ?>">
  <?php if ($noindex): ?>
  <meta name="robots" content="noindex,nofollow">
  <?php endif; ?>
  <link rel="canonical" href="<?= e($canonical) ?>">
  <meta property="og:type" content="<?= e($ogType) ?>">
  <meta property="og:site_name" content="<?= e($siteName) ?>">
  <meta property="og:locale" content="<?= e($ogLocale) ?>">
  <meta property="og:title" content="<?= e($title) ?>">
  <meta property="og:description" content="<?= e($metaDescription) ?>">
  <meta property="og:url" content="<?= e($canonical) ?>">
  <meta property="og:image" content="<?= e($ogImage) ?>">
  <meta property="og:image:alt" content="<?= e($ogImageAlt) ?>">
  <?php if ($isDefaultOg): ?>
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <?php endif; ?>
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= e($title) ?>">
  <meta name="twitter:description" content="<?= e($metaDescription) ?>">
  <meta name="twitter:image" content="<?= e($ogImage) ?>">
  <link rel="icon" href="<?= e(asset('img/favicon.png')) ?>" type="image/png">
  <link rel="apple-touch-icon" href="<?= e(asset('img/logo.png')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>?v=22">
  <?php if (!empty($schema)): ?>
  <script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?></script>
  <?php endif; ?>
</head>
<body>
  <a class="skip-link" href="#main"><?= e(__('layout.skip')) ?></a>
  <div class="topbar">
    <div class="container">
      <span><?= e(__('layout.topbar')) ?></span>
      <span class="topbar-right">
        <a href="mailto:booking@michemil.com">booking@michemil.com</a>
        <a href="tel:<?= e($phoneTel) ?>"><?= e($phone) ?></a>
      </span>
    </div>
  </div>

  <header class="site-header" data-header>
    <div class="container nav-row">
      <a class="brand" href="<?= e(url('/')) ?>">
        <img class="brand-logo" src="<?= e(asset('img/logo.png')) ?>" alt="Micheline & Emile" width="56" height="56">
        <span class="brand-text">
          <span class="brand-mark">Micheline <em>&</em> Emile</span>
          <span class="brand-sub"><?= e(__('layout.brand_sub')) ?></span>
        </span>
      </a>
      <button class="nav-toggle" type="button" data-nav-toggle aria-expanded="false" aria-controls="site-nav" aria-label="<?= e(__('layout.menu')) ?>">
        <span class="nav-toggle-bars" aria-hidden="true">
          <span></span><span></span><span></span>
        </span>
      </button>
      <nav class="nav" id="site-nav" data-nav>
        <div class="nav-links">
          <a href="<?= e(url('/nos-suites/')) ?>" class="<?= $navActive('/nos-suites') || $navActive('/nos-appartements') || $navActive('/nos-chambres') || $navActive('/chambres') ? 'is-active' : '' ?>"<?= ($navActive('/nos-suites') || $navActive('/chambres')) ? ' aria-current="page"' : '' ?>><?= e(__('nav.rooms')) ?></a>
          <a href="<?= e(url('/restaurants/')) ?>" class="<?= $navActive('/restaurants') ? 'is-active' : '' ?>"<?= $navActive('/restaurants') ? ' aria-current="page"' : '' ?>><?= e(__('nav.restaurants')) ?></a>
          <a href="<?= e(url('/spa/')) ?>" class="<?= $navActive('/spa') ? 'is-active' : '' ?>"<?= $navActive('/spa') ? ' aria-current="page"' : '' ?>><?= e(__('nav.spa')) ?></a>
          <a href="<?= e(url('/contact/')) ?>" class="<?= $navActive('/contact') ? 'is-active' : '' ?>"<?= $navActive('/contact') ? ' aria-current="page"' : '' ?>><?= e(__('nav.contact')) ?></a>
        </div>
        <div class="nav-actions">
          <span class="lang-switch" aria-label="<?= e(__('layout.lang')) ?>">
            <a class="<?= $locale === 'fr' ? 'active' : '' ?>" href="?lang=fr" hreflang="fr"<?= $locale === 'fr' ? ' aria-current="true"' : '' ?>>FR</a>
            <a class="<?= $locale === 'en' ? 'active' : '' ?>" href="?lang=en" hreflang="en"<?= $locale === 'en' ? ' aria-current="true"' : '' ?>>EN</a>
          </span>
          <a class="btn btn-sm nav-cta" href="<?= e(url('/reservation/')) ?>"><?= e(__('nav.booking')) ?></a>
        </div>
      </nav>
    </div>
  </header>
  <div class="nav-backdrop" data-nav-backdrop hidden></div>

  <main id="main">
    <?= $content ?>
  </main>

  <footer class="site-footer">
    <div class="container footer-grid">
      <div>
        <a class="footer-brand" href="<?= e(url('/')) ?>">
          <img class="footer-logo" src="<?= e(asset('img/logo.png')) ?>" alt="" width="64" height="64">
          <h3>Micheline <em>&</em> Emile</h3>
        </a>
        <p class="footer-lead"><?= e(__('layout.footer_lead')) ?></p>
      </div>
      <div class="footer-links">
        <strong><?= e(__('layout.explore')) ?></strong>
        <a href="<?= e(url('/nos-suites/')) ?>"><?= e(__('nav.rooms')) ?></a>
        <a href="<?= e(url('/restaurants/')) ?>"><?= e(__('nav.restaurants')) ?></a>
        <a href="<?= e(url('/spa/')) ?>"><?= e(__('nav.spa')) ?></a>
        <a href="<?= e(url('/reservation/')) ?>"><?= e(__('nav.booking')) ?></a>
      </div>
      <div class="footer-links">
        <strong><?= e(__('nav.contact')) ?></strong>
        <span>Yaoundé, Bastos</span>
        <a href="mailto:booking@michemil.com">booking@michemil.com</a>
        <a href="tel:<?= e($phoneTel) ?>"><?= e($phone) ?></a>
      </div>
    </div>
    <div class="container footer-copy">
      © <?= date('Y') ?> <?= e(__('layout.footer_copy')) ?> <?= e(__('footer.rights')) ?>
    </div>
  </footer>

  <a class="wa-fab" href="https://wa.me/<?= e($whatsapp) ?>" target="_blank" rel="noopener" aria-label="WhatsApp">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 3.5A11 11 0 0 0 3.4 17.8L2 22l4.3-1.3A11 11 0 1 0 20.5 3.5zm-8.5 17a9 9 0 0 1-4.6-1.3l-.3-.2-2.6.8.8-2.5-.2-.3A9 9 0 1 1 12 20.5zm5-6.7c-.3-.1-1.6-.8-1.9-.9s-.4-.1-.6.1-.7.9-.8 1-.3.2-.6.1a7.3 7.3 0 0 1-3.6-3.1c-.3-.5.3-.4.8-1.4.1-.2 0-.3 0-.5l-.9-2.1c-.2-.6-.5-.5-.6-.5h-.5c-.2 0-.5.1-.7.3s-1 1-1 2.4 1 2.8 1.2 3a10.4 10.4 0 0 0 4 3.5c.5.2 1 .4 1.3.5.6.2 1.1.2 1.5.1.5-.1 1.6-.7 1.8-1.3.2-.6.2-1.2.2-1.3s-.3-.2-.6-.3z"/></svg>
  </a>
  <script src="<?= e(asset('js/site.js')) ?>?v=13" defer></script>
</body>
</html>
