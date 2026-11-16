<?php $mailSent = $mailSent ?? true; ?>
<section class="section"><div class="container" style="max-width:640px">
  <div class="notice">
    <h1><?= e(__('confirm.title')) ?></h1>
    <p><?= e(__($mailSent ? 'confirm.text' : 'confirm.text_no_mail', ['ref' => $booking['reference']])) ?></p>
  </div>
  <p><a class="btn" href="<?= e(url('/')) ?>"><?= e(__('confirm.home')) ?></a></p>
</div></section>
