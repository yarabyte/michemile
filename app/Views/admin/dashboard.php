<?php component('page-header', [
    'title' => $title ?? 'Tableau de bord',
    'subtitle' => 'Vue d’ensemble de l’activité',
]); ?>

<div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
  <a class="ui-card no-underline hover:border-forest" href="<?= e(admin_url('reservations') . '?statut=new') ?>">
    <div class="text-xs font-semibold uppercase tracking-wide text-muted">Réservations</div>
    <div class="mt-1 text-3xl font-semibold text-forest"><?= (int) $stats['bookings_new'] ?></div>
    <div class="mt-1 text-sm text-muted">nouvelles — voir</div>
  </a>
  <a class="ui-card no-underline hover:border-forest" href="<?= e(admin_url('messages') . '?statut=new') ?>">
    <div class="text-xs font-semibold uppercase tracking-wide text-muted">Messages</div>
    <div class="mt-1 text-3xl font-semibold text-forest"><?= (int) $stats['messages_new'] ?></div>
    <div class="mt-1 text-sm text-muted">nouveaux — voir</div>
  </a>
  <a class="ui-card no-underline hover:border-forest" href="<?= e(admin_url('hebergements')) ?>">
    <div class="text-xs font-semibold uppercase tracking-wide text-muted">Hébergements</div>
    <div class="mt-1 text-3xl font-semibold text-forest"><?= (int) $stats['rooms'] ?></div>
    <div class="mt-1 text-sm text-muted">fiches — gérer</div>
  </a>
  <a class="ui-card no-underline hover:border-forest" href="<?= e(admin_url('mediatheque')) ?>">
    <div class="text-xs font-semibold uppercase tracking-wide text-muted">Médias</div>
    <div class="mt-1 text-3xl font-semibold text-forest"><?= (int) $stats['media'] ?></div>
    <div class="mt-1 text-sm text-muted">fichiers — ouvrir</div>
  </a>
</div>

<section class="ui-card mt-5">
  <h2 class="mb-3 mt-0 text-base font-semibold text-forest">Activité récente</h2>
  <?php if (empty($recent)): ?>
    <?php component('empty-state', ['message' => 'Aucune activité récente.']); ?>
  <?php else: ?>
    <div class="ui-table-wrap border-0 shadow-none">
      <table class="ui-table">
        <thead>
          <tr><th>Date</th><th>Action</th></tr>
        </thead>
        <tbody>
        <?php foreach ($recent as $row): ?>
          <tr>
            <td class="whitespace-nowrap text-muted"><?= e($row['created_at']) ?></td>
            <td><?= e(activity_label((string) $row['action'], $row['entity_type'] ?? null, isset($row['entity_id']) ? (int) $row['entity_id'] : null)) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
