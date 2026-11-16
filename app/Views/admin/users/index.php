<?php
$editUser = $editUser ?? null;
?>
<?php component('page-header', [
    'title' => $title ?? 'Utilisateurs',
    'subtitle' => 'Comptes d’accès à l’administration',
]); ?>

<?php if (empty($items)): ?>
  <?php component('empty-state', ['message' => 'Aucun utilisateur.']); ?>
<?php else: ?>
  <div class="ui-table-wrap mb-5">
    <table class="ui-table">
      <thead>
        <tr><th>Nom</th><th>E-mail</th><th>Rôle</th><th>2FA</th><th>Actif</th><th>Dernière connexion</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($items as $item): ?>
        <tr>
          <td class="font-medium"><?= e($item['name']) ?></td>
          <td><?= e($item['email']) ?></td>
          <td><?= e($item['role_name']) ?></td>
          <td>
            <?php component('badge', [
                'text' => (int) $item['totp_enabled'] ? 'Oui' : 'Non',
                'variant' => (int) $item['totp_enabled'] ? 'ok' : 'muted',
            ]); ?>
          </td>
          <td>
            <?php component('badge', [
                'text' => (int) $item['is_active'] ? 'Actif' : 'Inactif',
                'variant' => (int) $item['is_active'] ? 'ok' : 'danger',
            ]); ?>
          </td>
          <td class="text-muted"><?= e((string) ($item['last_login_at'] ?? '—')) ?></td>
          <td class="text-right">
            <a class="text-sm font-semibold text-forest no-underline hover:underline" href="<?= e(admin_url('utilisateurs') . '?edit=' . (int) $item['id']) ?>">Modifier</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<form class="ui-card grid max-w-xl gap-4" method="post" action="<?= e(admin_url('utilisateurs/sauver')) ?>">
  <?= csrf_field() ?>
  <h2 class="mb-0 mt-0 text-base font-semibold text-forest">
    <?= $editUser ? 'Modifier l’utilisateur' : 'Nouvel utilisateur' ?>
  </h2>
  <?php if ($editUser): ?>
    <input type="hidden" name="id" value="<?= (int) $editUser['id'] ?>">
    <p class="m-0 text-sm text-muted">Compte : <?= e($editUser['email']) ?></p>
  <?php endif; ?>
  <?php component('input', [
      'name' => 'name',
      'label' => 'Nom',
      'required' => true,
      'value' => $editUser['name'] ?? '',
  ]); ?>
  <?php component('input', [
      'type' => 'email',
      'name' => 'email',
      'label' => 'E-mail',
      'required' => true,
      'value' => $editUser['email'] ?? '',
  ]); ?>
  <?php component('input', [
      'type' => 'password',
      'name' => 'password',
      'label' => 'Mot de passe',
      'hint' => $editUser ? 'Laisser vide pour ne pas changer' : 'Obligatoire à la création',
      'required' => !$editUser,
  ]); ?>
  <?php component('select', [
      'name' => 'role_id',
      'label' => 'Rôle',
      'value' => (string) ($editUser['role_id'] ?? '2'),
      'options' => ['1' => 'Administrateur', '2' => 'Éditeur', '3' => 'Lecteur'],
  ]); ?>
  <label class="ui-label grid-cols-[auto_1fr] items-center font-medium">
    <input type="checkbox" name="is_active" class="rounded border-line" <?= !$editUser || (int) $editUser['is_active'] ? 'checked' : '' ?>>
    Compte actif
  </label>
  <div class="flex flex-wrap gap-3">
    <?php component('button', ['type' => 'submit', 'label' => 'Enregistrer']); ?>
    <?php if ($editUser): ?>
      <?php component('button', ['href' => admin_url('utilisateurs'), 'label' => 'Annuler', 'variant' => 'secondary']); ?>
    <?php endif; ?>
  </div>
</form>
