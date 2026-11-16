<?php
declare(strict_types=1);

$root = dirname(__DIR__);

require $root . '/app/Support/helpers.php';

spl_autoload_register(static function (string $class) use ($root): void {
    $prefixes = [
        'App\\' => $root . '/app/',
        'RobThree\\Auth\\' => $root . '/vendor/robthree/twofactorauth/lib/',
    ];
    foreach ($prefixes as $prefix => $base) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }
        $relative = str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        $file = $base . $relative;
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});

require $root . '/vendor/ezyang/htmlpurifier/library/HTMLPurifier.auto.php';

use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\CrudController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Front\BookingController;
use App\Controllers\Front\ContactController;
use App\Controllers\Front\HomeController;
use App\Controllers\Front\RestaurantController;
use App\Controllers\Front\RoomController;
use App\Controllers\Front\SeoController;
use App\Controllers\Front\SpaController;
use App\Core\App;
use App\Core\Env;
use App\Models\Redirect;

$router = App::bootstrap();

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
try {
    $redir = Redirect::find($path);
    if ($redir) {
        redirect($redir['to_path'], (int) $redir['status_code']);
    }
} catch (Throwable $e) {
    // DB may not be ready during first install
}

$admin = '/' . trim((string) Env::get('ADMIN_PATH', 'admin'), '/');

$router->get('/', [HomeController::class, 'index']);
$router->get('/nos-suites', static function () { (new RoomController())->listing('nos-suites'); });
$router->get('/nos-appartements', static function () { (new RoomController())->listing('nos-appartements'); });
$router->get('/nos-chambres', static function () { (new RoomController())->listing('nos-chambres'); });
$router->get('/chambres/{slug}', [RoomController::class, 'show']);
$router->get('/restaurants', [RestaurantController::class, 'index']);
$router->get('/restaurants/{slug}', [RestaurantController::class, 'show']);
$router->get('/spa', [SpaController::class, 'index']);
$router->get('/spa/{slug}', [SpaController::class, 'show']);
$router->get('/le-babys-spa-beauty-and-care', static function () { (new SpaController())->legacy('le-babys-spa-beauty-and-care'); });
$router->get('/la-piscine-des-jardins-de-lym', static function () { (new SpaController())->legacy('la-piscine-des-jardins-de-lym'); });
$router->get('/reservation', [BookingController::class, 'form']);
$router->post('/reservation', [BookingController::class, 'submit']);
$router->get('/reservation/confirmation/{reference}', [BookingController::class, 'confirm']);
$router->get('/contact', [ContactController::class, 'form']);
$router->post('/contact', [ContactController::class, 'submit']);
$router->get('/sitemap.xml', [SeoController::class, 'sitemap']);
$router->get('/robots.txt', [SeoController::class, 'robots']);
$router->get('/type-de-chambre/suite', static fn () => redirect('/nos-suites/', 301));
$router->get('/type-de-chambre/appartement', static fn () => redirect('/nos-appartements/', 301));
$router->get('/type-de-chambre/chambre', static fn () => redirect('/nos-chambres/', 301));
$router->get('/spa/spa', static fn () => redirect('/spa/', 301));

$router->get($admin . '/login', [AuthController::class, 'loginForm']);
$router->post($admin . '/login', [AuthController::class, 'login']);
$router->post($admin . '/logout', [AuthController::class, 'logout']);
$router->get($admin, [DashboardController::class, 'index']);
$router->get($admin . '/hebergements', [CrudController::class, 'accommodations']);
$router->get($admin . '/hebergements/nouveau', static fn () => (new CrudController())->accommodationEdit(null));
$router->get($admin . '/hebergements/{id}', [CrudController::class, 'accommodationEdit']);
$router->post($admin . '/hebergements/sauver', [CrudController::class, 'accommodationSave']);
$router->post($admin . '/hebergements/supprimer', [CrudController::class, 'accommodationDelete']);
$router->post($admin . '/hebergements/ordonner', [CrudController::class, 'accommodationReorder']);
$router->get($admin . '/restaurants', [CrudController::class, 'restaurants']);
$router->get($admin . '/restaurants/nouveau', static fn () => (new CrudController())->restaurantEdit(null));
$router->get($admin . '/restaurants/{id}', [CrudController::class, 'restaurantEdit']);
$router->post($admin . '/restaurants/sauver', [CrudController::class, 'restaurantSave']);
$router->get($admin . '/spa', [CrudController::class, 'spa']);
$router->get($admin . '/spa/nouveau', static fn () => (new CrudController())->spaEdit(null));
$router->get($admin . '/spa/{id}', [CrudController::class, 'spaEdit']);
$router->post($admin . '/spa/sauver', [CrudController::class, 'spaSave']);
$router->get($admin . '/accueil', [CrudController::class, 'homeSections']);
$router->post($admin . '/accueil/sauver', [CrudController::class, 'homeSectionSave']);
$router->get($admin . '/reservations', [CrudController::class, 'bookings']);
$router->post($admin . '/reservations/sauver', [CrudController::class, 'bookingUpdate']);
$router->post($admin . '/reservations/renvoyer-email', [CrudController::class, 'bookingResendMail']);
$router->get($admin . '/messages', [CrudController::class, 'messages']);
$router->post($admin . '/messages/sauver', [CrudController::class, 'messageUpdate']);
$router->get($admin . '/mediatheque', [CrudController::class, 'media']);
$router->post($admin . '/mediatheque/upload', [CrudController::class, 'mediaUpload']);
$router->post($admin . '/mediatheque/supprimer', [CrudController::class, 'mediaDelete']);
$router->get($admin . '/api/medias', [CrudController::class, 'mediaListApi']);
$router->post($admin . '/api/medias/upload', [CrudController::class, 'mediaUploadApi']);
$router->get($admin . '/parametres', [CrudController::class, 'settings']);
$router->post($admin . '/parametres/sauver', [CrudController::class, 'settingsSave']);
$router->post($admin . '/parametres/test-email', [CrudController::class, 'settingsTestMail']);
$router->get($admin . '/utilisateurs', [CrudController::class, 'users']);
$router->post($admin . '/utilisateurs/sauver', [CrudController::class, 'userSave']);
$router->get($admin . '/securite', [CrudController::class, 'security2fa']);
$router->post($admin . '/securite/activer', [CrudController::class, 'security2faEnable']);
$router->post($admin . '/securite/desactiver', [CrudController::class, 'security2faDisable']);

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
