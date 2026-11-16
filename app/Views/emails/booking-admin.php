<h2>Nouvelle réservation <?= e($booking['reference']) ?></h2>
<p><strong>Client :</strong> <?= e($booking['guest_name']) ?></p>
<p><strong>E-mail :</strong> <?= e($booking['guest_email']) ?></p>
<p><strong>Téléphone :</strong> <?= e($booking['guest_phone']) ?></p>
<p><strong>Hébergement :</strong> <?= e($booking['room_name'] ?? 'Non précisé') ?></p>
<p><strong>Arrivée :</strong> <?= e($booking['check_in']) ?> — <strong>Départ :</strong> <?= e($booking['check_out']) ?></p>
<p><strong>Personnes :</strong> <?= (int)$booking['guests'] ?></p>
<p><?= nl2br(e((string)($booking['message'] ?? ''))) ?></p>
