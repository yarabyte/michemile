<p><?= e(__('booking.mail_hello', ['name' => $booking['guest_name']])) ?></p>
<p><?= e(__('booking.mail_received', ['ref' => $booking['reference']])) ?></p>
<p><?= e(__('booking.mail_dates', ['in' => $booking['check_in'], 'out' => $booking['check_out']])) ?></p>
<p><?= e(__('booking.mail_reply')) ?></p>
<p><?= e(__('booking.mail_sign')) ?></p>
