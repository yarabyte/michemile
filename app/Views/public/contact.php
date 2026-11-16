<?php $errors = $errors ?? []; ?>
<section class="section">
  <div class="container form-shell">
    <div class="form-intro" data-reveal>
      <p class="eyebrow"><?= e(__('contact.eyebrow')) ?></p>
      <h1><?= e(__('contact.title')) ?></h1>
      <p><?= e(__('contact.intro')) ?></p>
      <ul>
        <li><?= e($phone ?? '') ?></li>
        <li><a href="mailto:booking@michemil.com">booking@michemil.com</a></li>
        <li><?= e(__('contact.address')) ?></li>
      </ul>
    </div>

    <form class="form" method="post" action="<?= e(url('/contact/')) ?>" data-reveal>
      <?= csrf_field() ?>
      <div class="hp" aria-hidden="true">
        <label><input type="text" name="fax_number_hp" value="" tabindex="-1" autocomplete="new-password"></label>
        <label><input type="text" name="url_confirm_hp" value="" tabindex="-1" autocomplete="new-password"></label>
      </div>
      <?php if (!empty($sent)): ?><div class="notice"><?= e(__('contact.sent')) ?></div><?php endif; ?>
      <?php if (!empty($errors['_form'])): ?><div class="error"><?= e($errors['_form']) ?></div><?php endif; ?>

      <label><?= e(__('contact.name')) ?> <input name="name" required autocomplete="name" value="<?= old('name') ?>"></label>
      <div class="form-row">
        <label><?= e(__('contact.email')) ?> <input type="email" name="email" required autocomplete="email" value="<?= old('email') ?>"></label>
        <label><?= e(__('contact.phone')) ?> <input name="phone" autocomplete="tel" value="<?= old('phone') ?>"></label>
      </div>
      <label><?= e(__('contact.subject')) ?> <input name="subject" value="<?= old('subject') ?>"></label>
      <label><?= e(__('contact.message')) ?> <textarea name="body" required><?= old('body') ?></textarea></label>

      <?php if (!empty($recaptchaSiteKey)): ?>
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        <div class="g-recaptcha" data-sitekey="<?= e((string) $recaptchaSiteKey) ?>"></div>
      <?php endif; ?>

      <button class="btn btn-gold btn-block" type="submit"><?= e(__('contact.submit')) ?></button>
    </form>
  </div>
</section>
