<?php
use App\Core\I18n;
$errors = $errors ?? [];
$old = $_SESSION['_old'] ?? [];
$checkIn = (string) ($old['check_in'] ?? $_GET['check_in'] ?? '');
$checkOut = (string) ($old['check_out'] ?? $_GET['check_out'] ?? '');
$guests = (string) ($old['guests'] ?? $_GET['guests'] ?? '2');
$preselect = $preselect ?: (string) ($_GET['room'] ?? '');
$selectedRoomId = (string) ($old['accommodation_id'] ?? '');
?>
<section class="section">
  <div class="container form-shell">
    <div class="form-intro" data-reveal>
      <p class="eyebrow"><?= e(__('booking.eyebrow')) ?></p>
      <h1><?= e(__('booking.title')) ?></h1>
      <p><?= e(__('booking.intro')) ?></p>
      <ul>
        <li><?= e(__('booking.bullet_1')) ?></li>
        <li><?= e(__('booking.bullet_2')) ?></li>
        <li><?= e(__('booking.bullet_3')) ?></li>
      </ul>
    </div>

    <form class="form" method="post" action="<?= e(url('/reservation/')) ?>" novalidate data-reveal>
      <?= csrf_field() ?>
      <div class="hp" aria-hidden="true">
        <label><input type="text" name="fax_number_hp" value="" tabindex="-1" autocomplete="new-password"></label>
        <label><input type="text" name="url_confirm_hp" value="" tabindex="-1" autocomplete="new-password"></label>
      </div>

      <?php if (!empty($errors['_form'])): ?><div class="error"><?= e($errors['_form']) ?></div><?php endif; ?>

      <label><?= e(__('booking.name')) ?>
        <input name="guest_name" required autocomplete="name" value="<?= e((string) ($old['guest_name'] ?? '')) ?>">
        <?php if (!empty($errors['guest_name'])): ?><span class="error"><?= e($errors['guest_name']) ?></span><?php endif; ?>
      </label>

      <div class="form-row">
        <label><?= e(__('booking.email')) ?>
          <input type="email" name="guest_email" required autocomplete="email" value="<?= e((string) ($old['guest_email'] ?? '')) ?>">
        </label>
        <label><?= e(__('booking.phone')) ?>
          <input name="guest_phone" required autocomplete="tel" value="<?= e((string) ($old['guest_phone'] ?? '')) ?>">
        </label>
      </div>

      <label><?= e(__('booking.accommodation')) ?>
        <select name="accommodation_id">
          <option value=""><?= e(__('booking.any')) ?></option>
          <?php foreach ($rooms as $room):
            $sel = ($selectedRoomId !== '' && $selectedRoomId === (string) $room['id'])
              || ($selectedRoomId === '' && $preselect && $preselect === $room['slug']);
          ?>
            <option value="<?= (int) $room['id'] ?>" <?= $sel ? 'selected' : '' ?>>
              <?= e(I18n::pick($room, 'name')) ?> — <?= e(format_fcfa((int) $room['price_fcfa'])) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>

      <div class="form-row">
        <label><?= e(__('booking.check_in')) ?>
          <input type="date" name="check_in" required value="<?= e($checkIn) ?>">
        </label>
        <label><?= e(__('booking.check_out')) ?>
          <input type="date" name="check_out" required value="<?= e($checkOut) ?>">
        </label>
      </div>

      <label><?= e(__('booking.guests')) ?>
        <input type="number" name="guests" min="1" max="12" value="<?= e($guests) ?>">
      </label>

      <label><?= e(__('booking.message')) ?>
        <textarea name="message" placeholder="<?= e(__('booking.message_ph')) ?>"><?= e((string) ($old['message'] ?? '')) ?></textarea>
      </label>

      <?php if (!empty($recaptchaSiteKey)): ?>
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        <div class="g-recaptcha" data-sitekey="<?= e((string) $recaptchaSiteKey) ?>"></div>
      <?php endif; ?>

      <button class="btn btn-gold btn-block" type="submit"><?= e(__('booking.submit')) ?></button>
    </form>
  </div>
</section>
