<?php
use App\Core\I18n;
$specs = json_decode((string) ($restaurant['specialties_json'] ?? '[]'), true) ?: [];
$feats = json_decode((string) ($restaurant['features_json'] ?? '[]'), true) ?: [];
$name = I18n::pick($restaurant, 'name');
$tagline = I18n::pick($restaurant, 'tagline');
$hours = I18n::pick($restaurant, 'hours');
$body = I18n::pick($restaurant, 'body');
$abbr = trim((string) ($restaurant['abbr'] ?? ''));
$image = trim((string) ($restaurant['image_url'] ?? ''));
if ($image === '') {
    $image = '/assets/img/content/WhatsApp-Image-2026-06-30-at-00.00.54.jpeg';
}
?>
<section class="resto-hero" aria-label="<?= e($name) ?>">
  <div class="resto-hero-media" data-reveal>
    <img
      src="<?= e($image) ?>"
      alt="<?= e($name) ?>"
      width="1600"
      height="900"
      fetchpriority="high"
    >
  </div>
</section>

<section class="section section-resto-detail">
  <div class="container resto-detail-shell">
    <nav class="room-breadcrumb" aria-label="<?= e(__('room.breadcrumb')) ?>" data-reveal>
      <a href="<?= e(url('/restaurants/')) ?>"><?= e(__('restaurant.back')) ?></a>
      <span aria-hidden="true">/</span>
      <span><?= e($name) ?></span>
    </nav>

    <header class="resto-intro" data-reveal>
      <?php if ($abbr !== ''): ?>
        <p class="resto-mark-line"><span><?= e($abbr) ?></span></p>
      <?php endif; ?>
      <h1><?= e($name) ?></h1>
      <?php if ($tagline !== ''): ?>
        <p class="resto-intro-lead"><?= e($tagline) ?></p>
      <?php endif; ?>
      <?php if ($hours !== ''): ?>
        <p class="resto-hours-line">
          <span class="resto-hours-label"><?= e(__('restaurant.hours')) ?></span>
          <span class="resto-hours-value"><?= e($hours) ?></span>
        </p>
      <?php endif; ?>
    </header>

    <div class="resto-content">
      <?php if ($body !== ''): ?>
        <article class="resto-story" data-reveal>
          <h2><?= e(__('restaurant.story')) ?></h2>
          <div class="resto-story-body"><?= purify($body) ?></div>
          <div class="resto-actions">
            <a class="btn btn-gold" href="<?= e(url('/contact/')) ?>"><?= e(__('restaurant.contact_cta')) ?></a>
            <a class="btn btn-outline" href="<?= e(url('/reservation/')) ?>"><?= e(__('restaurant.book_cta')) ?></a>
          </div>
        </article>
      <?php endif; ?>

      <?php if ($specs || $feats): ?>
        <aside class="resto-aside" data-reveal>
          <?php if ($specs): ?>
            <div class="resto-panel">
              <h2><?= e(__('restaurant.specialties')) ?></h2>
              <ul class="resto-list">
                <?php foreach ($specs as $s): ?>
                  <li><?= e($s) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>
          <?php if ($feats): ?>
            <div class="resto-panel">
              <h2><?= e(__('restaurant.features')) ?></h2>
              <ul class="resto-list">
                <?php foreach ($feats as $s): ?>
                  <li><?= e($s) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>
        </aside>
      <?php endif; ?>
    </div>
  </div>
</section>
