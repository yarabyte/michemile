<?php
use App\Core\Env;
$settings = $settings ?? [];
$hero = (string) ($settings['hero_image'] ?? '');
$envDefaults = [
    'mail_driver' => (string) Env::get('MAIL_DRIVER', 'mail'),
    'mail_from' => (string) Env::get('MAIL_FROM', 'noreply@michemil.com'),
    'mail_from_name' => (string) Env::get('MAIL_FROM_NAME', 'Micheline & Emile'),
    'mail_booking' => (string) Env::get('MAIL_BOOKING', 'booking@michemil.com'),
    'smtp_host' => (string) Env::get('SMTP_HOST', ''),
    'smtp_port' => (string) Env::get('SMTP_PORT', '587'),
    'smtp_user' => (string) Env::get('SMTP_USER', ''),
    'smtp_secure' => (string) Env::get('SMTP_SECURE', 'tls'),
];
?>
<?php component('page-header', [
    'title' => $title ?? 'Paramètres',
    'subtitle' => 'Coordonnées, e-mail et réglages du site',
]); ?>

<form class="grid max-w-3xl gap-4" method="post" action="<?= e(admin_url('parametres/sauver')) ?>">
  <?= csrf_field() ?>

  <div class="ui-card grid gap-4">
    <h2 class="mb-0 mt-0 text-base font-semibold text-forest">Contact</h2>
    <?php component('input', ['name' => 'phone', 'label' => 'Téléphone', 'value' => (string) ($settings['phone'] ?? '')]); ?>
    <?php component('input', ['name' => 'whatsapp', 'label' => 'WhatsApp', 'hint' => 'Avec indicatif pays, ex. 2376…', 'value' => (string) ($settings['whatsapp'] ?? '')]); ?>
    <?php component('input', ['name' => 'address', 'label' => 'Adresse', 'value' => (string) ($settings['address'] ?? '')]); ?>
  </div>

  <div class="ui-card grid gap-4">
    <h2 class="mb-0 mt-0 text-base font-semibold text-forest">Image d’accueil</h2>
    <?php component('media-field', [
        'name' => 'hero_media_id',
        'pathName' => 'hero_image',
        'label' => 'Photo hero',
        'hint' => 'Grande image en haut de la page d’accueil.',
        'mediaId' => 0,
        'path' => $hero,
        'previewUrl' => $hero,
    ]); ?>
  </div>

  <div class="ui-card grid gap-4">
    <h2 class="mb-0 mt-0 text-base font-semibold text-forest">Référencement (Google)</h2>
    <?php component('input', ['name' => 'meta_title_fr', 'label' => 'Titre du site (FR)', 'value' => (string) ($settings['meta_title_fr'] ?? '')]); ?>
    <?php component('textarea', ['name' => 'meta_desc_fr', 'label' => 'Description (FR)', 'rows' => 2, 'value' => (string) ($settings['meta_desc_fr'] ?? '')]); ?>
    <?php component('input', ['name' => 'meta_title_en', 'label' => 'Site title (EN)', 'value' => (string) ($settings['meta_title_en'] ?? '')]); ?>
    <?php component('textarea', ['name' => 'meta_desc_en', 'label' => 'Description (EN)', 'rows' => 2, 'value' => (string) ($settings['meta_desc_en'] ?? '')]); ?>
  </div>

  <?php
    $mailDriver = (string) ($settings['mail_driver'] ?? $envDefaults['mail_driver'] ?? 'mail');
    $smtpSecure = (string) ($settings['smtp_secure'] ?? $envDefaults['smtp_secure'] ?? 'tls');
    $smtpPassSet = trim((string) ($settings['smtp_pass'] ?? '')) !== '';
  ?>
  <div class="ui-card grid gap-4">
    <h2 class="mb-0 mt-0 text-base font-semibold text-forest">E-mail &amp; SMTP</h2>
    <p class="m-0 text-sm text-muted">Ces réglages remplacent le fichier <code>.env</code> lorsqu’ils sont renseignés. Laissez un champ vide pour conserver la valeur du serveur.</p>

    <?php component('select', [
        'name' => 'mail_driver',
        'label' => 'Mode d’envoi',
        'value' => $mailDriver,
        'options' => [
            'mail' => 'PHP mail() (hébergeur)',
            'smtp' => 'SMTP (recommandé)',
        ],
    ]); ?>

    <?php component('input', [
        'name' => 'mail_from',
        'label' => 'Adresse d’expédition (From)',
        'hint' => 'Ex. noreply@michemil.com',
        'value' => (string) ($settings['mail_from'] ?? $envDefaults['mail_from'] ?? ''),
    ]); ?>
    <?php component('input', [
        'name' => 'mail_from_name',
        'label' => 'Nom d’expédition',
        'value' => (string) ($settings['mail_from_name'] ?? $envDefaults['mail_from_name'] ?? ''),
    ]); ?>
    <?php component('input', [
        'name' => 'mail_booking',
        'label' => 'Boîte réservations / contact',
        'hint' => 'Seule adresse qui reçoit les demandes du site (réservation + contact). Modifiable ici à tout moment.',
        'value' => (string) ($settings['mail_booking'] ?? $envDefaults['mail_booking'] ?? ''),
      ]); ?>

    <div class="grid gap-4 border-t border-line pt-4 sm:grid-cols-2">
      <?php component('input', [
          'name' => 'smtp_host',
          'label' => 'Serveur SMTP',
          'hint' => 'Ex. ssl0.ovh.net ou smtp.gmail.com',
          'value' => (string) ($settings['smtp_host'] ?? $envDefaults['smtp_host'] ?? ''),
          'class' => 'sm:col-span-2',
      ]); ?>
      <?php component('input', [
          'name' => 'smtp_port',
          'label' => 'Port',
          'hint' => '587 (TLS) ou 465 (SSL)',
          'value' => (string) ($settings['smtp_port'] ?? $envDefaults['smtp_port'] ?? '587'),
      ]); ?>
      <?php component('select', [
          'name' => 'smtp_secure',
          'label' => 'Sécurité',
          'value' => $smtpSecure,
          'options' => [
              'tls' => 'STARTTLS (587)',
              'ssl' => 'SSL (465)',
              'none' => 'Aucune',
          ],
      ]); ?>
      <?php component('input', [
          'name' => 'smtp_user',
          'label' => 'Utilisateur SMTP',
          'value' => (string) ($settings['smtp_user'] ?? $envDefaults['smtp_user'] ?? ''),
          'class' => 'sm:col-span-2',
      ]); ?>
      <?php component('input', [
          'name' => 'smtp_pass',
          'type' => 'password',
          'label' => 'Mot de passe SMTP',
          'hint' => $smtpPassSet
              ? 'Déjà enregistré — laisser vide pour ne pas changer'
              : 'Laisser vide pour garder la valeur du .env',
          'value' => '',
          'attrs' => 'autocomplete="new-password"',
          'class' => 'sm:col-span-2',
      ]); ?>
    </div>
    <?php if ((int) ($settings['smtp_port'] ?? 0) === 995 || (int) ($settings['smtp_port'] ?? 0) === 993): ?>
      <p class="m-0 rounded border border-danger/30 bg-danger/5 px-3 py-2 text-sm text-danger">
        Le port <?= e((string) $settings['smtp_port']) ?> n’est pas un port SMTP (995 = POP3, 993 = IMAP).
        Utilisez <strong>465</strong> avec SSL ou <strong>587</strong> avec STARTTLS.
      </p>
    <?php endif; ?>
    <?php if ($mailDriver !== 'smtp' && trim((string) ($settings['smtp_host'] ?? '')) !== ''): ?>
      <p class="m-0 rounded border border-line bg-cream-2 px-3 py-2 text-sm text-muted">
        Des paramètres SMTP sont renseignés, mais le mode d’envoi est encore <strong>PHP mail()</strong>.
        Passez en <strong>SMTP</strong> puis enregistrez pour les utiliser.
      </p>
    <?php endif; ?>
  </div>

  <?php component('button', ['type' => 'submit', 'label' => 'Enregistrer']); ?>
</form>

<form class="mt-6 grid max-w-3xl gap-4" method="post" action="<?= e(admin_url('parametres/test-email')) ?>">
  <?= csrf_field() ?>
  <div class="ui-card grid gap-4">
    <h2 class="mb-0 mt-0 text-base font-semibold text-forest">Tester l’envoi</h2>
    <p class="m-0 text-sm text-muted">Envoie un e-mail de test avec la configuration actuellement enregistrée (pensez à enregistrer avant).</p>
    <?php component('input', [
        'name' => 'test_email_to',
        'type' => 'email',
        'label' => 'Destinataire du test',
        'hint' => 'Par défaut : boîte réservations / contact',
        'value' => (string) ($settings['mail_booking'] ?? $envDefaults['mail_booking'] ?? ''),
    ]); ?>
    <?php component('button', ['type' => 'submit', 'label' => 'Envoyer un e-mail de test', 'variant' => 'secondary']); ?>
  </div>
</form>
