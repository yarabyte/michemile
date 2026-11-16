<?php
use App\Core\I18n;
use App\Models\HomeSection;
use App\Services\Settings;

$byKey = HomeSection::mapByKey($sections ?? []);
$heroSec = $byKey['hero'] ?? null;
$intro = $byKey['intro'] ?? null;
$amenitiesSec = $byKey['amenities'] ?? null;
$servicesSec = $byKey['services'] ?? null;
$roomsSec = $byKey['rooms'] ?? null;
$diningSec = $byKey['restaurants'] ?? null;
$spaSec = $byKey['spa'] ?? null;
$ctaSec = $byKey['cta'] ?? null;

$heroExtra = HomeSection::extra($heroSec);
$introExtra = HomeSection::extra($intro);
$amenitiesExtra = HomeSection::extra($amenitiesSec);
$servicesExtra = HomeSection::extra($servicesSec);
$ctaExtra = HomeSection::extra($ctaSec);

$heroFallback = Settings::get('hero_image', '/assets/img/content/JDL1.jpeg');
$heroSlides = [];
if (!empty($heroExtra['slides']) && is_array($heroExtra['slides'])) {
    foreach ($heroExtra['slides'] as $slide) {
        $slide = trim((string) $slide);
        if ($slide === '' || in_array($slide, $heroSlides, true)) {
            continue;
        }
        $heroSlides[] = $slide;
        if (count($heroSlides) >= 3) {
            break;
        }
    }
}
if ($heroSlides === [] && $heroFallback !== '') {
    $heroSlides = [$heroFallback];
}
$introMedia = null;
if (!empty($intro['media_id'])) {
    $introMedia = \App\Core\Database::fetch('SELECT public_path FROM media WHERE id=?', [(int) $intro['media_id']]);
}
$introImage = $introMedia['public_path']
    ?? default_intro_image();

$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$icons = ui_icon_paths();

$heroTitle = $heroSec ? (I18n::pick($heroSec, 'title') ?: 'Micheline & Emile') : 'Micheline & Emile';
$heroLead = $heroSec ? (I18n::pick($heroSec, 'subtitle') ?: '') : __('home.hero_lead');

$amenities = $amenitiesExtra['items'] ?? [
    ['icon' => 'pool', 'label_fr' => 'Piscine 24h/24', 'label_en' => '24/7 pool'],
    ['icon' => 'leaf', 'label_fr' => 'Spa', 'label_en' => 'Spa'],
    ['icon' => 'wifi', 'label_fr' => 'Wi-Fi illimité', 'label_en' => 'Unlimited Wi-Fi'],
    ['icon' => 'snow', 'label_fr' => 'Climatisation', 'label_en' => 'Air conditioning'],
    ['icon' => 'tv', 'label_fr' => 'CANAL+', 'label_en' => 'CANAL+'],
];

$services = $servicesExtra['items'] ?? [
    ['icon' => 'wifi', 'title_fr' => 'Internet haut débit', 'text_fr' => 'Wifi illimité partout, câble Ethernet pour les plus exigeants.', 'title_en' => 'High-speed internet', 'text_en' => 'Unlimited Wi-Fi everywhere.'],
    ['icon' => 'leaf', 'title_fr' => 'Spa & Bien-être', 'text_fr' => 'Soins 7j/7. Piscine et cascades accessibles 24h/24.', 'title_en' => 'Spa & wellness', 'text_en' => 'Treatments 7 days a week.'],
    ['icon' => 'food', 'title_fr' => 'Restauration', 'text_fr' => 'Restaurants, bars, snack et coffee club sur place.', 'title_en' => 'Dining', 'text_en' => 'Restaurants and bars on site.'],
    ['icon' => 'car', 'title_fr' => 'Transport', 'text_fr' => 'Navette aéroport et véhicules avec chauffeur sur demande.', 'title_en' => 'Transport', 'text_en' => 'Airport shuttle on request.'],
];

$pickExtra = static function (array $extra, string $field): string {
    $locale = I18n::locale();
    $key = $field . '_' . $locale;
    if (!empty($extra[$key])) {
        return (string) $extra[$key];
    }
    return (string) ($extra[$field . '_fr'] ?? '');
};

$show = static function (?array $sec): bool {
    // Section absente = fallback affiché ; section présente mais désactivée = masquée
    if ($sec === null) {
        return true;
    }
    return (int) ($sec['is_enabled'] ?? 1) === 1;
};
?>
<?php if ($show($heroSec)): ?>
<section class="hero" data-hero-slider<?= count($heroSlides) > 1 ? ' data-autoplay="6500"' : '' ?>>
  <div class="hero-media" aria-hidden="true">
    <?php foreach ($heroSlides as $i => $slide): ?>
      <div class="hero-slide<?= $i === 0 ? ' is-active' : '' ?>" data-hero-slide>
        <img
          src="<?= e($slide) ?>"
          alt=""
          width="1600"
          height="1067"
          <?= $i === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?>
        >
      </div>
    <?php endforeach; ?>
  </div>
  <div class="hero-shade" aria-hidden="true"></div>
  <?php if (count($heroSlides) > 1): ?>
    <div class="hero-slider-ui" aria-label="<?= e(__('home.slideshow')) ?>">
      <button type="button" class="hero-nav hero-nav-prev" data-hero-prev aria-label="<?= e(__('home.prev_image')) ?>">‹</button>
      <div class="hero-dots" role="tablist" aria-label="<?= e(__('home.choose_image')) ?>">
        <?php foreach ($heroSlides as $i => $_): ?>
          <button
            type="button"
            class="hero-dot<?= $i === 0 ? ' is-active' : '' ?>"
            data-hero-dot="<?= $i ?>"
            role="tab"
            aria-selected="<?= $i === 0 ? 'true' : 'false' ?>"
            aria-label="<?= e(__('home.image_n', ['n' => (string) ($i + 1)])) ?>"
          ></button>
        <?php endforeach; ?>
      </div>
      <button type="button" class="hero-nav hero-nav-next" data-hero-next aria-label="<?= e(__('home.next_image')) ?>">›</button>
    </div>
  <?php endif; ?>
  <div class="hero-inner">
    <h1><?php
      // Keep brand typography: split on & if present
      if (str_contains($heroTitle, '&')) {
          $parts = preg_split('/\s*&\s*/', $heroTitle, 2);
          echo e(trim($parts[0] ?? 'Micheline')) . ' <em>&</em> ' . e(trim($parts[1] ?? 'Emile'));
      } else {
          echo e($heroTitle);
      }
    ?></h1>
    <?php if ($heroLead !== ''): ?><p class="hero-lead"><?= e($heroLead) ?></p><?php endif; ?>

    <form class="book-strip" method="get" action="<?= e(url('/reservation/')) ?>">
      <label class="book-field">
        <span class="book-label"><?= e(__('home.arrival')) ?></span>
        <span class="book-value">
          <input type="date" name="check_in" value="<?= e($today) ?>" required>
        </span>
      </label>
      <label class="book-field">
        <span class="book-label"><?= e(__('home.departure')) ?></span>
        <span class="book-value">
          <input type="date" name="check_out" value="<?= e($tomorrow) ?>" required>
        </span>
      </label>
      <label class="book-field">
        <span class="book-label"><?= e(__('home.guests')) ?></span>
        <span class="book-value">
          <select name="guests">
            <?php for ($i = 1; $i <= 6; $i++): ?>
              <option value="<?= $i ?>" <?= $i === 2 ? 'selected' : '' ?>><?= $i ?></option>
            <?php endfor; ?>
          </select>
        </span>
      </label>
      <label class="book-field">
        <span class="book-label"><?= e(__('home.accommodation')) ?></span>
        <span class="book-value">
          <select name="room">
            <option value=""><?= e(__('home.all')) ?></option>
            <?php foreach ($rooms as $room): ?>
              <option value="<?= e($room['slug']) ?>"><?= e(I18n::pick($room, 'name')) ?></option>
            <?php endforeach; ?>
          </select>
        </span>
      </label>
      <div class="book-submit">
        <button class="book-cta" type="submit"><?= e(__('nav.booking')) ?></button>
      </div>
    </form>
  </div>
</section>
<?php endif; ?>

<?php if ($show($amenitiesSec) && $amenities): ?>
<div class="amenities" aria-label="<?= e(__('home.amenities')) ?>">
  <?php foreach ($amenities as $am):
    $iconKey = $am['icon'] ?? 'check';
    $label = I18n::locale() === 'en'
        ? (($am['label_en'] ?? '') ?: ($am['label_fr'] ?? ''))
        : ($am['label_fr'] ?? '');
    if ($label === '') continue;
  ?>
    <span>
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="<?= e($icons[$iconKey] ?? $icons['check']) ?>" stroke-linecap="round" stroke-linejoin="round"/></svg>
      <?= e($label) ?>
    </span>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($show($intro) || $show($servicesSec)): ?>
<section class="section section-residence" id="residence">
  <div class="container">
    <?php if ($show($intro)): ?>
    <div class="residence-grid">
      <div class="residence-copy" data-reveal>
        <h2><?= e($intro ? I18n::pick($intro, 'title') : __('home.welcome')) ?></h2>
        <div class="residence-body"><?= purify($intro ? I18n::pick($intro, 'body') : '') ?></div>
        <?php $quote = $pickExtra($introExtra, 'quote'); if ($quote !== ''): ?>
          <blockquote class="residence-quote">
            <p>« <?= e($quote) ?> »</p>
          </blockquote>
        <?php endif; ?>
        <a class="btn btn-sm" href="#hebergements"><?= e(__('home.see_rooms')) ?></a>
      </div>

      <figure class="residence-media" data-reveal>
        <img
          src="<?= e($introImage) ?>"
          alt="<?= e($intro ? I18n::pick($intro, 'title') : 'Micheline & Emile') ?>"
          loading="lazy"
          width="960"
          height="720"
        >
      </figure>
    </div>
    <?php endif; ?>

    <?php if ($show($servicesSec) && $services): ?>
    <div class="grid-services">
      <?php foreach ($services as $i => $svc):
        $icon = $svc['icon'] ?? 'check';
        $svcTitle = I18n::locale() === 'en'
            ? (($svc['title_en'] ?? '') ?: ($svc['title_fr'] ?? ''))
            : ($svc['title_fr'] ?? '');
        $svcText = I18n::locale() === 'en'
            ? (($svc['text_en'] ?? '') ?: ($svc['text_fr'] ?? ''))
            : ($svc['text_fr'] ?? '');
        if ($svcTitle === '') continue;
      ?>
        <article class="service" data-reveal style="transition-delay: <?= $i * 70 ?>ms">
          <div class="service-head">
            <span class="service-index" aria-hidden="true"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
            <svg class="service-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="<?= e($icons[$icon] ?? $icons['check']) ?>" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </div>
          <h3><?= e($svcTitle) ?></h3>
          <p><?= e($svcText) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($show($roomsSec)): ?>
<section class="section section-rooms" id="hebergements">
  <div class="container">
    <div class="section-head-row">
      <div class="section-head" data-reveal>
        <h2><?= e(($roomsSec && I18n::pick($roomsSec, 'title')) ? I18n::pick($roomsSec, 'title') : __('home.rooms')) ?></h2>
        <p><?= e(($roomsSec && I18n::pick($roomsSec, 'subtitle')) ? I18n::pick($roomsSec, 'subtitle') : __('home.rooms_lead')) ?></p>
      </div>
      <a class="btn btn-sm" href="<?= e(url('/nos-suites/')) ?>"><?= e(__('home.see_rooms')) ?></a>
    </div>

    <div class="filters" data-filters role="tablist" aria-label="<?= e(__('home.filter_rooms')) ?>">
      <button type="button" class="filter-btn is-active" data-filter="all" role="tab" aria-selected="true"><?= e(__('home.filter_all')) ?></button>
      <button type="button" class="filter-btn" data-filter="suites" role="tab" aria-selected="false"><?= e(__('home.filter_suites')) ?></button>
      <button type="button" class="filter-btn" data-filter="apparts" role="tab" aria-selected="false"><?= e(__('home.filter_appartments')) ?></button>
      <button type="button" class="filter-btn" data-filter="chambres" role="tab" aria-selected="false"><?= e(__('home.filter_rooms_cat')) ?></button>
    </div>

    <div class="grid-rooms">
      <?php foreach ($rooms as $i => $room):
        $gallery = json_decode((string) ($room['gallery_json'] ?? '[]'), true) ?: [];
        $img = $gallery[0] ?? '/assets/img/content/WhatsApp-Image-2026-06-23-at-11.33.48-600x450.jpeg';
        $catLabel = match ($room['category']) {
            'suites' => __('room.cat_suite'),
            'apparts' => __('room.cat_apartment'),
            default => __('room.cat_room'),
        };
        $roomUrl = url('/chambres/' . $room['slug'] . '/');
      ?>
        <article class="room-card" data-reveal data-category="<?= e($room['category']) ?>" style="transition-delay: <?= ($i % 3) * 80 ?>ms">
          <a class="media" href="<?= e($roomUrl) ?>">
            <img src="<?= e($img) ?>" alt="<?= e(I18n::pick($room, 'name')) ?>" loading="lazy" width="600" height="480">
            <span class="room-media-tag" aria-hidden="true"><?= e($catLabel) ?></span>
          </a>
          <div class="room-card-body">
            <h3><a href="<?= e($roomUrl) ?>"><?= e(I18n::pick($room, 'name')) ?></a></h3>
            <div class="room-meta">
              <span><?= e(__('room.persons_short', ['n' => (string) (int) $room['capacity']])) ?></span>
              <span><?= (int) $room['area_m2'] ?> m²</span>
            </div>
            <p class="excerpt"><?= e(I18n::pick($room, 'short')) ?></p>
            <div class="room-foot">
              <div class="price">
                <?php if ((int) $room['price_from']): ?><small><?= e(__('room.from')) ?></small><?php endif; ?>
                <?= e(format_fcfa((int) $room['price_fcfa'])) ?>
              </div>
              <div class="room-foot-actions">
                <a class="room-more" href="<?= e($roomUrl) ?>"><?= e(__('home.learn_more')) ?></a>
                <a class="btn btn-sm" href="<?= e(url('/reservation/?room=' . $room['slug'])) ?>"><?= e(__('nav.booking')) ?></a>
              </div>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($show($diningSec)): ?>
<section class="section section-dining" id="restaurants">
  <div class="container">
    <div class="section-head-row">
      <div class="section-head" data-reveal>
        <h2><?= e(($diningSec && I18n::pick($diningSec, 'title')) ? I18n::pick($diningSec, 'title') : __('home.restaurants')) ?></h2>
        <p><?= e(($diningSec && I18n::pick($diningSec, 'subtitle')) ? I18n::pick($diningSec, 'subtitle') : __('home.dining_lead')) ?></p>
      </div>
      <a class="btn btn-sm" href="<?= e(url('/restaurants/')) ?>"><?= e(__('home.discover')) ?></a>
    </div>

    <div class="grid-restos">
      <?php foreach ($restaurants as $i => $resto): ?>
        <article class="resto-card" data-reveal style="transition-delay: <?= $i * 90 ?>ms">
          <a class="resto-card-link" href="<?= e(url('/restaurants/' . $resto['slug'] . '/')) ?>">
            <span class="media" aria-hidden="true">
              <img
                src="<?= e($resto['image_url'] ?: '/assets/img/content/WhatsApp-Image-2026-06-30-at-00.00.54.jpeg') ?>"
                alt=""
                loading="lazy"
                width="640"
                height="800"
              >
            </span>
            <?php if (!empty($resto['abbr'])): ?>
              <span class="resto-mark" aria-hidden="true"><?= e($resto['abbr']) ?></span>
            <?php endif; ?>
            <div class="resto-card-body">
              <?php if (!empty($resto['abbr'])): ?><span class="tag"><?= e($resto['abbr']) ?></span><?php endif; ?>
              <h3><?= e(I18n::pick($resto, 'name')) ?></h3>
              <p><?= e(I18n::pick($resto, 'tagline')) ?></p>
              <div class="resto-card-meta">
                <p class="resto-hours"><?= e(I18n::pick($resto, 'hours')) ?></p>
                <span class="resto-more"><?= e(__('home.discover')) ?></span>
              </div>
            </div>
          </a>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($show($spaSec) && !empty($spaPages)): ?>
<section class="section section-alt">
  <div class="container">
    <div class="section-head-row">
      <div class="section-head" data-reveal>
        <h2><?= e(($spaSec && I18n::pick($spaSec, 'title')) ? I18n::pick($spaSec, 'title') : __('nav.spa')) ?></h2>
        <p><?= e(($spaSec && I18n::pick($spaSec, 'subtitle')) ? I18n::pick($spaSec, 'subtitle') : __('home.spa_lead')) ?></p>
      </div>
      <a class="btn btn-sm" href="<?= e(url('/spa/')) ?>"><?= e(__('home.discover')) ?></a>
    </div>
    <div class="grid-rooms">
      <?php foreach ($spaPages as $page): ?>
        <article class="room-card" data-reveal>
          <a class="media" href="<?= e(url('/spa/' . $page['slug'] . '/')) ?>">
            <?php if (!empty($page['image_url'])): ?>
              <img src="<?= e($page['image_url']) ?>" alt="<?= e(I18n::pick($page, 'title')) ?>" loading="lazy" width="600" height="450">
            <?php endif; ?>
          </a>
          <div class="room-card-body">
            <h3><a href="<?= e(url('/spa/' . $page['slug'] . '/')) ?>"><?= e(I18n::pick($page, 'title')) ?></a></h3>
            <p class="excerpt"><?= e(I18n::pick($page, 'lead')) ?></p>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($show($ctaSec)): ?>
<section class="cta-band" aria-labelledby="cta-title">
  <div class="container cta-band-inner">
    <div class="cta-copy" data-reveal>
      <h2 id="cta-title"><?= e(($ctaSec && I18n::pick($ctaSec, 'title')) ? I18n::pick($ctaSec, 'title') : __('home.cta_title')) ?></h2>
      <p><?= e(($ctaSec && I18n::pick($ctaSec, 'subtitle')) ? I18n::pick($ctaSec, 'subtitle') : __('home.cta_lead')) ?></p>
      <?php
        $points = I18n::locale() === 'en'
            ? (($ctaExtra['points_en'] ?? []) ?: ($ctaExtra['points_fr'] ?? []))
            : ($ctaExtra['points_fr'] ?? []);
        if ($points === []) {
            $points = [__('home.cta_point_1'), __('home.cta_point_2'), __('home.cta_point_3')];
        }
      ?>
      <?php if ($points): ?>
      <ul class="cta-points">
        <?php foreach ($points as $point): ?>
          <li><?= e((string) $point) ?></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
    <div class="cta-actions" data-reveal>
      <div class="cta-btns">
        <a class="btn btn-gold cta-primary" href="<?= e(url('/reservation/')) ?>"><?= e(__('home.cta')) ?></a>
        <a class="btn btn-ghost cta-secondary" href="<?= e(url('/nos-suites/')) ?>"><?= e(__('home.learn_more')) ?></a>
      </div>
      <a class="cta-mail" href="mailto:booking@michemil.com">booking@michemil.com</a>
      <?php
        $ctaPhone = Settings::get('phone', '+237 653 29 02 58 / 222 20 86 71');
        $ctaTel = preg_replace('/\s+/', '', explode('/', $ctaPhone)[0] ?? '');
      ?>
      <p class="cta-note"><?= e(__('home.or_phone')) ?> <a href="tel:<?= e($ctaTel) ?>"><?= e(trim(explode('/', $ctaPhone)[0] ?? $ctaPhone)) ?></a></p>
    </div>
  </div>
</section>
<?php endif; ?>
