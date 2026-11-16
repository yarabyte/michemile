<?php
use App\Core\I18n;
$gallery = json_decode((string) ($room['gallery_json'] ?? '[]'), true) ?: [];
$equip = json_decode((string) ($room['equipment_json'] ?? '[]'), true) ?: [];
$practical = json_decode((string) ($room['practical_json'] ?? '[]'), true) ?: [];
$name = I18n::pick($room, 'name');
$body = I18n::pick($room, 'body');
$short = I18n::pick($room, 'short');
$catLabel = match ($room['category']) {
    'suites' => __('room.cat_suite'),
    'apparts' => __('room.cat_apartment'),
    default => __('room.cat_room'),
};
$bookUrl = url('/reservation/?room=' . $room['slug']);
$listUrl = url('/nos-suites/');
?>
<section class="section section-room-detail">
  <div class="container">
    <nav class="room-breadcrumb" aria-label="<?= e(__('room.breadcrumb')) ?>" data-reveal>
      <a href="<?= e($listUrl) ?>"><?= e(__('home.rooms')) ?></a>
      <span aria-hidden="true">/</span>
      <span><?= e($name) ?></span>
    </nav>

    <div class="room-detail">
      <div class="room-gallery" data-reveal>
        <div class="gallery-main">
          <?php if ($gallery): ?>
            <img src="<?= e($gallery[0]) ?>" alt="<?= e($name) ?>" loading="eager" data-gallery-main width="960" height="720">
          <?php endif; ?>
          <span class="room-media-tag" aria-hidden="true"><?= e($catLabel) ?></span>
        </div>
        <?php if (count($gallery) > 1): ?>
          <div class="gallery-thumbs" role="list">
            <?php foreach (array_slice($gallery, 0, 6) as $ti => $g): ?>
              <button
                type="button"
                class="<?= $ti === 0 ? 'is-active' : '' ?>"
                data-gallery-thumb="<?= e($g) ?>"
                aria-label="<?= e(__('room.view_photo', ['n' => (string) ($ti + 1)])) ?>"
                aria-pressed="<?= $ti === 0 ? 'true' : 'false' ?>"
              >
                <img src="<?= e($g) ?>" alt="" loading="lazy" width="200" height="200">
              </button>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <aside class="room-summary" data-reveal>
        <p class="eyebrow"><?= e($catLabel) ?></p>
        <h1><?= e($name) ?></h1>
        <div class="room-meta">
          <span><?= e(__('room.persons_short', ['n' => (string) (int) $room['capacity']])) ?></span>
          <span><?= (int) $room['area_m2'] ?> m²</span>
          <?php if (!empty($room['beds'])): ?><span><?= e($room['beds']) ?></span><?php endif; ?>
        </div>
        <?php if ($short): ?>
          <p class="room-lead"><?= e($short) ?></p>
        <?php endif; ?>

        <div class="room-book-panel">
          <div class="price">
            <?php if ((int) $room['price_from']): ?><small><?= e(__('room.from')) ?></small><?php endif; ?>
            <?= e(format_fcfa((int) $room['price_fcfa'])) ?>
            <span class="price-unit"><?= e(__('room.per_night')) ?></span>
          </div>
          <div class="cta-btns">
            <a class="btn btn-gold cta-primary" href="<?= e($bookUrl) ?>"><?= e(__('room.book')) ?></a>
            <a class="btn btn-outline cta-secondary" href="<?= e($listUrl) ?>"><?= e(__('home.see_rooms')) ?></a>
          </div>
          <p class="room-book-note"><?= e(__('room.book_note')) ?></p>
        </div>
      </aside>
    </div>

    <div class="room-detail-grid">
      <?php if ($body): ?>
        <div class="room-description" data-reveal>
          <h2><?= e(__('room.presentation')) ?></h2>
          <div class="room-body"><?= purify($body) ?></div>
        </div>
      <?php endif; ?>

      <div class="room-sideblocks" data-reveal>
        <?php if ($practical): ?>
          <div class="room-block">
            <h2><?= e(__('room.practical')) ?></h2>
            <dl class="facts">
              <?php foreach ($practical as $row): ?>
                <div>
                  <dt><?= e($row[0] ?? '') ?></dt>
                  <dd><?= e($row[1] ?? '') ?></dd>
                </div>
              <?php endforeach; ?>
            </dl>
          </div>
        <?php endif; ?>

        <?php if ($equip): ?>
          <div class="room-block">
            <h2><?= e(__('room.equipment')) ?></h2>
            <ul class="equip">
              <?php foreach ($equip as $eq):
                $eqIcon = is_array($eq) ? (string) ($eq[0] ?? 'check') : 'check';
                $eqLabel = is_array($eq) ? (string) ($eq[1] ?? $eq[0] ?? '') : (string) $eq;
              ?>
                <li>
                  <?= ui_icon($eqIcon, 'equip-icon') ?>
                  <span><?= e($eqLabel) ?></span>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php if (!empty($similar)): ?>
<section class="section section-rooms">
  <div class="container">
    <div class="section-head" data-reveal>
      <p class="eyebrow"><?= e(__('room.continue')) ?></p>
      <h2><?= e(__('room.similar')) ?></h2>
    </div>
    <div class="grid-rooms">
      <?php foreach ($similar as $si => $s):
        $g = json_decode((string) ($s['gallery_json'] ?? '[]'), true) ?: [];
        $sCat = match ($s['category']) {
            'suites' => __('room.cat_suite'),
            'apparts' => __('room.cat_apartment'),
            default => __('room.cat_room'),
        };
        $sUrl = url('/chambres/' . $s['slug'] . '/');
      ?>
        <article class="room-card" data-reveal style="transition-delay: <?= $si * 80 ?>ms">
          <a class="media" href="<?= e($sUrl) ?>">
            <?php if ($g): ?>
              <img src="<?= e($g[0]) ?>" alt="<?= e(I18n::pick($s, 'name')) ?>" loading="lazy" width="600" height="480">
            <?php endif; ?>
            <span class="room-media-tag" aria-hidden="true"><?= e($sCat) ?></span>
          </a>
          <div class="room-card-body">
            <h3><a href="<?= e($sUrl) ?>"><?= e(I18n::pick($s, 'name')) ?></a></h3>
            <div class="room-meta">
              <span><?= e(__('room.persons_short', ['n' => (string) (int) $s['capacity']])) ?></span>
              <span><?= (int) $s['area_m2'] ?> m²</span>
            </div>
            <div class="room-foot">
              <div class="price"><?= e(format_fcfa((int) $s['price_fcfa'])) ?></div>
              <div class="room-foot-actions">
                <a class="room-more" href="<?= e($sUrl) ?>"><?= e(__('room.learn_more')) ?></a>
                <a class="btn btn-sm" href="<?= e(url('/reservation/?room=' . $s['slug'])) ?>"><?= e(__('nav.booking')) ?></a>
              </div>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
