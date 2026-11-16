<?php use App\Core\I18n; ?>
<section class="section">
  <div class="container">
    <div class="section-head" data-reveal>
      <p class="eyebrow"><?= e(__('room.list_eyebrow')) ?></p>
      <h1><?= e($heading) ?></h1>
      <p><?= e(__('room.list_intro')) ?></p>
    </div>
    <div class="grid-rooms">
      <?php foreach ($rooms as $room):
        $gallery = json_decode((string) ($room['gallery_json'] ?? '[]'), true) ?: [];
        $img = $gallery[0] ?? '';
      ?>
        <article class="room-card" data-reveal>
          <a class="media" href="<?= e(url('/chambres/' . $room['slug'] . '/')) ?>">
            <?php if ($img): ?><img src="<?= e($img) ?>" alt="<?= e(I18n::pick($room, 'name')) ?>" loading="lazy"><?php endif; ?>
          </a>
          <div class="room-card-body">
            <h2 class="room-card-title">
              <a href="<?= e(url('/chambres/' . $room['slug'] . '/')) ?>"><?= e(I18n::pick($room, 'name')) ?></a>
            </h2>
            <div class="room-meta">
              <span><?= e(__('room.persons_short', ['n' => (string) (int) $room['capacity']])) ?></span>
              <span><?= (int) $room['area_m2'] ?> m²</span>
            </div>
            <p class="excerpt"><?= e(I18n::pick($room, 'short')) ?></p>
            <div class="room-foot">
              <div class="price"><?= e(format_fcfa((int) $room['price_fcfa'])) ?></div>
              <a class="btn btn-sm" href="<?= e(url('/reservation/?room=' . $room['slug'])) ?>"><?= e(__('nav.booking')) ?></a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
