#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Crée le premier compte administrateur.
 * Usage:
 *   php bin/seed-admin.php --email=admin@michemil.com --password='...' --name='Admin'
 */

$root = dirname(__DIR__);
require $root . '/app/Support/helpers.php';
spl_autoload_register(static function (string $class) use ($root): void {
    if (!str_starts_with($class, 'App\\')) return;
    $file = $root . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) require $file;
});
\App\Core\Env::load($root . '/.env');

$opts = getopt('', ['email:', 'password:', 'name::']);
$email = mb_strtolower(trim((string) ($opts['email'] ?? '')));
$password = (string) ($opts['password'] ?? '');
$name = trim((string) ($opts['name'] ?? 'Administrateur'));

if ($email === '' || $password === '') {
    fwrite(STDERR, "Usage: php bin/seed-admin.php --email=... --password=... [--name=...]\n");
    exit(1);
}
if (strlen($password) < 12) {
    fwrite(STDERR, "Mot de passe trop court (min. 12 caractères).\n");
    exit(1);
}

use App\Core\Database;

Database::query(
    "INSERT INTO roles (id, slug, name, permissions) VALUES
     (1,'admin','Administrateur', JSON_ARRAY('*')),
     (2,'editor','Éditeur', JSON_ARRAY('content.manage','media.manage','bookings.manage','messages.manage')),
     (3,'viewer','Lecteur', JSON_ARRAY('bookings.view','messages.view','content.view'))
     ON DUPLICATE KEY UPDATE name=VALUES(name)"
);

$hash = password_hash($password, password_algo());
$existing = Database::fetch('SELECT id FROM users WHERE email = ?', [$email]);
if ($existing) {
    Database::query('UPDATE users SET password_hash=?, name=?, role_id=1, is_active=1 WHERE id=?', [$hash, $name, $existing['id']]);
    echo "Admin mis à jour: {$email}\n";
} else {
    Database::insert(
        'INSERT INTO users (role_id, email, password_hash, name, is_active) VALUES (1, ?, ?, ?, 1)',
        [$email, $hash, $name]
    );
    echo "Admin créé: {$email}\n";
}

$adminPath = \App\Core\Env::get('ADMIN_PATH');
echo "URL admin: " . rtrim((string)\App\Core\Env::get('APP_URL'), '/') . '/' . trim((string)$adminPath, '/') . "/login/\n";
