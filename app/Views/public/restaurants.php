<?php use App\Core\I18n; ?>
<section class="section section-dining">
  <div class="container">
    <div class="section-head" data-reveal>
      <h1><?= e(__('nav.restaurants')) ?></h1>
      <p><?= e(__('restaurant.lead')) ?></p>
    </div>
    <div class="grid-restos">
      <?php foreach ($restaurants as $i => $restaurant): ?>
        <article class="resto-card" data-reveal style="transition-delay: <?= $i * 90 ?>ms">
          <a class="resto-card-link" href="<?= e(url('/restaurants/' . $restaurant['slug'] . '/')) ?>">
            <span class="media" aria-hidden="true">
              <img
                src="<?= e($restaurant['image_url'] ?: '/assets/img/content/WhatsApp-Image-2026-06-30-at-00.00.54.jpeg') ?>"
                alt=""
                loading="lazy"
                width="640"
                height="800"
              >
            </span>
            <?php if (!empty($restaurant['abbr'])): ?>
              <span class="resto-mark" aria-hidden="true"><?= e($restaurant['abbr']) ?></span>
            <?php endif; ?>
            <div class="resto-card-body">
              <?php if (!empty($restaurant['abbr'])): ?><span class="tag"><?= e($restaurant['abbr']) ?></span><?php endif; ?>
              <h2><?= e(I18n::pick($restaurant, 'name')) ?></h2>
              <p><?= e(I18n::pick($restaurant, 'tagline')) ?></p>
              <div class="resto-card-meta">
                <p class="resto-hours"><?= e(I18n::pick($restaurant, 'hours')) ?></p>
                <span class="resto-more"><?= e(__('restaurant.discover')) ?></span>
              </div>
            </div>
          </a>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
