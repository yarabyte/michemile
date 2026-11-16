<?php
use App\Core\I18n;
$blocks = json_decode((string) ($page['blocks_json'] ?? '[]'), true) ?: [];
$title = I18n::pick($page, 'title');
$lead = I18n::pick($page, 'lead');
$body = I18n::pick($page, 'body');
$cta = I18n::pick($page, 'cta_label');
$image = trim((string) ($page['image_url'] ?? ''));
if ($image === '') {
    $image = '/assets/img/content/Massage6.jpg';
}
?>
<section class="resto-hero spa-detail-hero" aria-label="<?= e($title) ?>">
  <div class="resto-hero-media" data-reveal>
    <img
      src="<?= e($image) ?>"
      alt="<?= e($title) ?>"
      width="1600"
      height="900"
      fetchpriority="high"
    >
  </div>
</section>

<section class="section section-spa-detail">
  <div class="container resto-detail-shell">
    <nav class="room-breadcrumb" aria-label="<?= e(__('room.breadcrumb')) ?>" data-reveal>
      <a href="<?= e(url('/spa/')) ?>"><?= e(__('spa.back')) ?></a>
      <span aria-hidden="true">/</span>
      <span><?= e($title) ?></span>
    </nav>

    <header class="resto-intro" data-reveal>
      <p class="resto-mark-line"><span><?= e(__('spa.eyebrow')) ?></span></p>
      <h1><?= e($title) ?></h1>
      <?php if ($lead !== ''): ?>
        <p class="resto-intro-lead"><?= e($lead) ?></p>
      <?php endif; ?>
    </header>

    <div class="resto-content<?= $blocks ? '' : ' resto-content-solo' ?>">
      <article class="resto-story" data-reveal>
        <?php if ($body !== ''): ?>
          <h2><?= e(__('spa.story')) ?></h2>
          <div class="resto-story-body"><?= purify($body) ?></div>
        <?php endif; ?>
        <div class="resto-actions">
          <a class="btn btn-gold" href="<?= e(url('/contact/')) ?>">
            <?= e($cta !== '' ? $cta : __('spa.contact_cta')) ?>
          </a>
          <a class="btn btn-outline" href="<?= e(url('/reservation/')) ?>"><?= e(__('spa.book_cta')) ?></a>
        </div>
      </article>

      <?php if ($blocks): ?>
        <aside class="resto-aside" data-reveal>
          <?php foreach ($blocks as $block):
            $blockTitle = trim((string) ($block['title'] ?? ''));
            $blockNote = trim((string) ($block['note'] ?? ''));
            $items = $block['items'] ?? [];
            if ($blockTitle === '' && !$items) {
                continue;
            }
          ?>
            <div class="resto-panel">
              <?php if ($blockTitle !== ''): ?>
                <h2>
                  <?= e($blockTitle) ?>
                  <?php if ($blockNote !== ''): ?>
                    <small class="spa-block-note"><?= e($blockNote) ?></small>
                  <?php endif; ?>
                </h2>
              <?php endif; ?>
              <?php if ($items): ?>
                <ul class="resto-list">
                  <?php foreach ($items as $item): ?>
                    <li><?= e($item) ?></li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </aside>
      <?php endif; ?>
    </div>
  </div>
</section>
