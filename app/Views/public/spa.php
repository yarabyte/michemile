<?php
use App\Core\I18n;
$pages = $pages ?? [];
$heroImage = '';
foreach ($pages as $p) {
    if (!empty($p['image_url'])) {
        $heroImage = (string) $p['image_url'];
        break;
    }
}
if ($heroImage === '') {
    $heroImage = '/assets/img/content/Massage6.jpg';
}
?>
<section class="spa-index-hero" aria-label="<?= e(__('nav.spa')) ?>">
  <div class="spa-index-hero-media" data-reveal>
    <img
      src="<?= e($heroImage) ?>"
      alt=""
      width="1600"
      height="900"
      fetchpriority="high"
    >
  </div>
  <div class="spa-index-hero-copy">
    <div class="container" data-reveal>
      <p class="spa-eyebrow"><?= e(__('spa.eyebrow')) ?></p>
      <h1><?= e(__('nav.spa')) ?></h1>
      <p class="spa-index-lead"><?= e(__('spa.lead')) ?></p>
    </div>
  </div>
</section>

<section class="section section-spa-index">
  <div class="container">
    <div class="spa-grid">
      <?php foreach ($pages as $i => $page):
        $title = I18n::pick($page, 'title');
        $lead = I18n::pick($page, 'lead');
        $href = url('/spa/' . $page['slug'] . '/');
        $img = trim((string) ($page['image_url'] ?? ''));
        if ($img === '') {
            $img = '/assets/img/content/Massage6.jpg';
        }
      ?>
        <article class="spa-card" data-reveal style="transition-delay: <?= $i * 90 ?>ms">
          <a class="spa-card-link" href="<?= e($href) ?>">
            <span class="spa-card-media" aria-hidden="true">
              <img src="<?= e($img) ?>" alt="" loading="<?= $i === 0 ? 'eager' : 'lazy' ?>" width="800" height="600">
            </span>
            <span class="spa-card-body">
              <span class="spa-card-index" aria-hidden="true"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
              <h2><?= e($title) ?></h2>
              <?php if ($lead !== ''): ?>
                <p><?= e($lead) ?></p>
              <?php endif; ?>
              <span class="spa-card-more"><?= e(__('spa.discover')) ?></span>
            </span>
          </a>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
