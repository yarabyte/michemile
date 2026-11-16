# Micheline & Emile — site PHP 8.2 + MySQL 8

Application PHP sans framework lourd, conçue pour un hébergement mutualisé OVH
(Apache + PHP 8.2 + MySQL 8). La maquette HTML/CSS fournie a été traduite en
templates serveur (FR/EN), avec back-office sécurisé.

## Architecture

```
.env                 # hors webroot (priorité sécurité)
public/              # document root Apache
  index.php          # front controller
  assets/            # CSS, JS, polices
  uploads/           # médias (PHP désactivé via .htaccess)
app/                 # code applicatif (MVC léger)
database/            # schema.sql + seeds
bin/                 # seed admin + import contenu
resources/content/   # JSON extrait de la maquette
vendor/              # HTML Purifier + TwoFactorAuth
```

Pas de Node en production, pas de process persistant.

## Prérequis OVH

- PHP **8.2+** avec extensions : `pdo_mysql`, `mbstring`, `fileinfo`, `gd` (ou `imagick`), `openssl`, `json`
- MySQL **8**
- `mod_rewrite` Apache
- Accès SSH recommandé (sinon : import SQL via phpMyAdmin + upload FTP)

## Déploiement OVH (mutualié)

### 1. Préparer la base

Dans l’espace client OVH → Hébergement → Bases de données :

1. Créer une base MySQL 8 et un utilisateur.
2. Importer `database/schema.sql` (phpMyAdmin ou CLI).
3. Importer `database/seeds/roles.sql`.

### 2. Déposer les fichiers

Option recommandée (document root = `public/`) :

1. Uploadez le projet dans `www/` (ou un sous-dossier).
2. Dans OVH → Multisite / Domaine → **modifier la racine** vers `/www/.../public`.
3. Placez `.env` **à la racine du projet** (parent de `public/`), jamais dans `public/`.

Si vous ne pouvez pas changer la racine : le `.htaccess` racine redirige vers `public/`
et refuse l’accès à `.env`, `app/`, `vendor/`, etc.

### 3. Configuration `.env`

```bash
cp .env.example .env
```

Renseignez au minimum :

- `APP_URL=https://michemil.com`
- `APP_KEY` (chaîne longue aléatoire)
- `ADMIN_PATH` (chemin admin non devinable, déjà prérempli — **changez-le**)
- `DB_*`
- `MAIL_BOOKING=booking@michemil.com`
- `SESSION_SECURE=true` (HTTPS)
- `RECAPTCHA_*` (optionnel ; honeypot actif sinon)

### 4. Permissions

```bash
chmod 750 storage storage/logs storage/cache storage/sessions storage/tmp public/uploads
chmod 640 .env
```

### 5. Compte admin

```bash
php bin/seed-admin.php --email=admin@michemil.com --password='MotDePasseLong!' --name='Admin'
```

Puis ouvrez : `https://michemil.com/{ADMIN_PATH}/login/`

Activez la **2FA TOTP** depuis Admin → Sécurité 2FA.

### 6. Import du contenu maquette / SEO

```bash
php bin/import-content.php
```

Importe :

- 6 hébergements (`/chambres/{slug}/`)
- 3 restaurants (slugs propres + 301 depuis les URLs WP fautives)
- 2 pages spa + 301 depuis les URLs legacy
- sections d’accueil, paramètres, redirections taxonomies

### 7. Couper WordPress

1. Vérifier les URLs clés (liste ci-dessous).
2. Basculer le DNS / la racine vers cette app.
3. Conserver une sauvegarde WP hors ligne.

## URLs conservées / 301

| URL | Comportement |
|-----|--------------|
| `/` | Accueil |
| `/nos-suites/`, `/nos-appartements/`, `/nos-chambres/` | Listes |
| `/chambres/suite-executive/` (etc.) | Fiches |
| `/restaurants/...` | Fiches restos |
| `/restaurants/le-bleubird-lounge/` | **301** → `/restaurants/le-bluebird-lounge/` |
| `/restaurants/demgne-cofffee-club/` | **301** → `/restaurants/demgne-coffee-club/` |
| `/le-babys-spa-beauty-and-care/` | **301** → `/spa/babys/` |
| `/la-piscine-des-jardins-de-lym/` | **301** → `/spa/piscine/` |
| `/reservation/` | Formulaire |
| `/type-de-chambre/*` | **301** vers listes |
| `/sitemap.xml`, `/robots.txt` | SEO |

## Sécurité (checklist)

- PDO + requêtes préparées uniquement
- `password_hash` / `password_verify` (**Argon2id**)
- Sessions : `HttpOnly`, `Secure`, `SameSite=Strict`, régénération à la connexion
- CSRF sur tous les formulaires
- `htmlspecialchars` en sortie + **HTML Purifier** pour le HTML riche
- Limitation des tentatives de login + verrouillage temporaire
- 2FA TOTP optionnelle
- Uploads : `finfo` MIME, ré-encodage WebP, nom aléatoire, `.htaccess` anti-PHP
- URL admin non devinable (`ADMIN_PATH`)
- En-têtes CSP / HSTS / X-Frame-Options / nosniff
- `.env` hors webroot
- Journal `activity_log`
- Honeypot (+ reCAPTCHA optionnel) sur réservation & contact

## Back-office

CRUD : hébergements, restaurants, spa, sections accueil, réservations (statuts),
messages, médiathèque, paramètres, utilisateurs/rôles, 2FA.

Les demandes de réservation :

1. Enregistrent un statut `new` + référence
2. Envoient un e-mail à `booking@michemil.com`
3. Envoient un accusé au client

## Développement local

```bash
# Document root = public/
php -S 127.0.0.1:8080 -t public
```

Créez la base, copiez `.env`, lancez seed + import.

### Assets CSS (Tailwind)

Node est requis **uniquement pour builder** les CSS (pas en production OVH).
Les fichiers générés dans `public/assets/css/` sont versionnés.

```bash
npm install
npm run build          # site.css + admin.css
npm run dev:site       # watch front
npm run dev:admin      # watch admin
```

Sources : `resources/css/` (`site.css`, `admin.css`, `tokens.css`, `site-legacy.css`).
Composants UI admin : `app/Views/components/`.

## Notes médias

Les images seed sont locales sous `public/assets/img/content/`
(chemins `/assets/img/content/...`). Pour re-télécharger / réécrire les
références à partir d’anciennes URLs WordPress :

```bash
php bin/localize-images.php
```

Pour de nouveaux visuels, uploadez-les dans la médiathèque admin
(ré-encodage WebP) et mettez à jour les galeries.

## Licence / stack

PHP 8.2, MySQL 8, HTML Purifier, RobThree TwoFactorAuth, Tailwind CSS 4 (build), Alpine.js (admin).
