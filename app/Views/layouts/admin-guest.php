<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title><?= e($title ?? 'Connexion') ?> — Micheline & Emile</title>
  <link rel="icon" href="<?= e(asset('img/favicon.png')) ?>" type="image/png">
  <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>?v=6">
</head>
<body class="min-h-screen bg-cream-2 font-sans text-ink antialiased">
<?= $content ?>
</body>
</html>
