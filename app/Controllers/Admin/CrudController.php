<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\Accommodation;
use App\Models\Booking;
use App\Models\HomeSection;
use App\Models\Message;
use App\Models\Restaurant;
use App\Models\SpaPage;
use App\Services\ActivityLogger;
use App\Services\Mailer;
use App\Services\Settings;
use App\Services\UploadService;
use App\Core\Database;

final class CrudController
{
    public function accommodations(): void
    {
        $user = Auth::requireLogin();
        $items = Accommodation::allAdmin();
        $category = trim((string) ($_GET['categorie'] ?? ''));
        $published = trim((string) ($_GET['statut'] ?? ''));
        if ($category !== '') {
            $items = array_values(array_filter($items, static fn ($r) => ($r['category'] ?? '') === $category));
        }
        if ($published === 'publie') {
            $items = array_values(array_filter($items, static fn ($r) => (int) ($r['is_published'] ?? 0) === 1));
        } elseif ($published === 'brouillon') {
            $items = array_values(array_filter($items, static fn ($r) => (int) ($r['is_published'] ?? 0) === 0));
        }
        View::renderAdmin('admin/accommodations/index', [
            'title' => 'Hébergements',
            'user' => $user,
            'items' => $items,
            'filterCategory' => $category,
            'filterStatus' => $published,
        ]);
    }

    public function accommodationEdit(?string $id = null): void
    {
        $user = Auth::requireLogin();
        $item = $id ? Accommodation::find((int) $id) : null;
        View::renderAdmin('admin/accommodations/form', [
            'title' => $item ? 'Modifier hébergement' : 'Nouvel hébergement',
            'user' => $user,
            'item' => $item,
        ]);
    }

    public function accommodationSave(): void
    {
        $user = Auth::requireLogin();
        Csrf::requireValid($_POST['_csrf'] ?? null);
        $id = (int) ($_POST['id'] ?? 0) ?: null;
        $data = $this->roomPayload($_POST);
        if ($data['name_fr'] === '' || $data['short_fr'] === '') {
            flash('error', 'Le nom et le résumé en français sont obligatoires.');
            redirect($id ? admin_url('hebergements/' . $id) : admin_url('hebergements/nouveau'));
        }
        $newId = Accommodation::save($data, $id);
        ActivityLogger::log((int) $user['id'], $id ? 'accommodation.update' : 'accommodation.create', 'accommodation', $newId);
        flash('ok', 'C’est enregistré.');
        redirect(admin_url('hebergements/' . $newId));
    }

    public function accommodationDelete(): void
    {
        $user = Auth::requireLogin();
        Csrf::requireValid($_POST['_csrf'] ?? null);
        $id = (int) ($_POST['id'] ?? 0);
        Accommodation::delete($id);
        ActivityLogger::log((int) $user['id'], 'accommodation.delete', 'accommodation', $id);
        flash('ok', 'Hébergement supprimé.');
        redirect(admin_url('hebergements'));
    }

    public function accommodationReorder(): void
    {
        $user = Auth::requireLogin();
        $raw = (string) file_get_contents('php://input');
        $json = json_decode($raw, true);
        $payload = is_array($json) ? $json : $_POST;
        Csrf::requireValid(
            isset($payload['_csrf']) && is_string($payload['_csrf'])
                ? $payload['_csrf']
                : (isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? (string) $_SERVER['HTTP_X_CSRF_TOKEN'] : null)
        );
        $ids = $payload['ids'] ?? [];
        if (!is_array($ids) || $ids === []) {
            $this->json(['ok' => false, 'error' => 'Liste d’identifiants manquante.']);
        }
        $ids = array_values(array_filter(array_map('intval', $ids), static fn (int $id) => $id > 0));
        if ($ids === []) {
            $this->json(['ok' => false, 'error' => 'Aucun hébergement à réordonner.']);
        }
        Accommodation::reorder($ids);
        ActivityLogger::log((int) $user['id'], 'accommodation.reorder', 'accommodation', null);
        $this->json(['ok' => true]);
    }

    public function mediaListApi(): void
    {
        Auth::requireLogin();
        $accept = (string) ($_GET['accept'] ?? 'image');
        $sql = 'SELECT id, public_path, original_name, mime, alt_fr, size_bytes, created_at FROM media';
        $params = [];
        if ($accept === 'image') {
            $sql .= ' WHERE mime LIKE ?';
            $params[] = 'image/%';
        } elseif ($accept === 'pdf') {
            $sql .= ' WHERE mime = ?';
            $params[] = 'application/pdf';
        }
        $sql .= ' ORDER BY created_at DESC LIMIT 200';
        $items = Database::fetchAll($sql, $params);
        $this->json(['items' => $items]);
    }

    public function mediaUploadApi(): void
    {
        $user = Auth::requireLogin();
        Csrf::requireValid($_POST['_csrf'] ?? null);
        try {
            $item = (new UploadService())->store($_FILES['file'] ?? [], (int) $user['id'], trim((string) ($_POST['alt_fr'] ?? '')) ?: null);
            $this->json(['item' => $item]);
        } catch (\Throwable $e) {
            http_response_code(422);
            $this->json(['error' => $e->getMessage()]);
        }
    }

    public function mediaDelete(): void
    {
        $user = Auth::requireLogin();
        Csrf::requireValid($_POST['_csrf'] ?? null);
        $id = (int) ($_POST['id'] ?? 0);
        $row = Database::fetch('SELECT * FROM media WHERE id = ?', [$id]);
        if ($row) {
            if (!empty($row['disk_path']) && is_file((string) $row['disk_path'])) {
                @unlink((string) $row['disk_path']);
            }
            Database::query('DELETE FROM media WHERE id = ?', [$id]);
            ActivityLogger::log((int) $user['id'], 'media.delete', 'media', $id);
            flash('ok', 'Fichier supprimé.');
        }
        redirect(admin_url('mediatheque'));
    }

    public function restaurants(): void
    {
        $user = Auth::requireLogin();
        View::renderAdmin('admin/restaurants/index', [
            'title' => 'Restaurants', 'user' => $user, 'items' => Restaurant::allAdmin(),
        ]);
    }

    public function restaurantEdit(?string $id = null): void
    {
        $user = Auth::requireLogin();
        $item = $id ? Database::fetch('SELECT * FROM restaurants WHERE id=?', [(int)$id]) : null;
        View::renderAdmin('admin/restaurants/form', [
            'title' => $item ? 'Modifier restaurant' : 'Nouveau restaurant',
            'user' => $user, 'item' => $item,
        ]);
    }

    public function restaurantSave(): void
    {
        $user = Auth::requireLogin();
        Csrf::requireValid($_POST['_csrf'] ?? null);
        $id = (int) ($_POST['id'] ?? 0) ?: null;
        $existing = $id ? Database::fetch('SELECT * FROM restaurants WHERE id=?', [$id]) : null;
        $nameFr = trim((string) ($_POST['name_fr'] ?? ''));
        $slug = trim((string) ($_POST['slug'] ?? ''));
        if ($slug === '' && $nameFr !== '') {
            $slug = slugify($nameFr);
        }
        $coverId = (int) ($_POST['cover_media_id'] ?? 0) ?: null;
        $menuId = (int) ($_POST['menu_media_id'] ?? 0) ?: null;
        $imageUrl = trim((string) ($_POST['image_url'] ?? '')) ?: null;
        if ($coverId) {
            $media = Database::fetch('SELECT public_path FROM media WHERE id=?', [$coverId]);
            if ($media) {
                $imageUrl = (string) $media['public_path'];
            }
        }
        $specialties = $_POST['specialties'] ?? [];
        if (!is_array($specialties)) {
            $specialties = array_filter(array_map('trim', explode("\n", (string) $specialties)));
        } else {
            $specialties = array_values(array_filter(array_map(static fn ($v) => trim((string) $v), $specialties)));
        }
        $features = $_POST['features'] ?? [];
        if (!is_array($features)) {
            $features = array_filter(array_map('trim', explode("\n", (string) $features)));
        } else {
            $features = array_values(array_filter(array_map(static fn ($v) => trim((string) $v), $features)));
        }
        $data = [
            'slug' => $slug,
            'legacy_slug' => trim((string) ($_POST['legacy_slug'] ?? ($existing['legacy_slug'] ?? ''))) ?: null,
            'name_fr' => $nameFr,
            'name_en' => trim((string) ($_POST['name_en'] ?? '')),
            'abbr' => trim((string) ($_POST['abbr'] ?? '')) ?: null,
            'tagline_fr' => trim((string) ($_POST['tagline_fr'] ?? '')),
            'tagline_en' => trim((string) ($_POST['tagline_en'] ?? '')),
            'body_fr' => purify((string) ($_POST['body_fr'] ?? '')),
            'body_en' => purify((string) ($_POST['body_en'] ?? '')),
            'hours_fr' => trim((string) ($_POST['hours_fr'] ?? '')),
            'hours_en' => trim((string) ($_POST['hours_en'] ?? '')),
            'specialties_json' => json_encode($specialties, JSON_UNESCAPED_UNICODE),
            'features_json' => json_encode($features, JSON_UNESCAPED_UNICODE),
            'cover_media_id' => $coverId,
            'menu_media_id' => $menuId,
            'image_url' => $imageUrl,
            'meta_title_fr' => trim((string) ($_POST['meta_title_fr'] ?? '')) ?: null,
            'meta_title_en' => trim((string) ($_POST['meta_title_en'] ?? '')) ?: null,
            'meta_desc_fr' => trim((string) ($_POST['meta_desc_fr'] ?? '')) ?: null,
            'meta_desc_en' => trim((string) ($_POST['meta_desc_en'] ?? '')) ?: null,
            'is_published' => isset($_POST['is_published']) ? 1 : 0,
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ];
        $newId = Restaurant::save($data, $id);
        ActivityLogger::log((int) $user['id'], $id ? 'restaurant.update' : 'restaurant.create', 'restaurant', $newId);
        flash('ok', 'C’est enregistré.');
        redirect(admin_url('restaurants/' . $newId));
    }

    public function spa(): void
    {
        $user = Auth::requireLogin();
        View::renderAdmin('admin/spa/index', [
            'title' => 'Pages Spa', 'user' => $user, 'items' => SpaPage::allAdmin(),
        ]);
    }

    public function spaEdit(?string $id = null): void
    {
        $user = Auth::requireLogin();
        $item = $id ? Database::fetch('SELECT * FROM spa_pages WHERE id=?', [(int)$id]) : null;
        View::renderAdmin('admin/spa/form', [
            'title' => $item ? 'Modifier page spa' : 'Nouvelle page spa',
            'user' => $user, 'item' => $item,
        ]);
    }

    public function spaSave(): void
    {
        $user = Auth::requireLogin();
        Csrf::requireValid($_POST['_csrf'] ?? null);
        $id = (int) ($_POST['id'] ?? 0) ?: null;
        $existing = $id ? Database::fetch('SELECT * FROM spa_pages WHERE id=?', [$id]) : null;
        $titleFr = trim((string) ($_POST['title_fr'] ?? ''));
        $slug = trim((string) ($_POST['slug'] ?? ''));
        if ($slug === '' && $titleFr !== '') {
            $slug = slugify($titleFr);
        }
        $blocksIn = $_POST['blocks'] ?? null;
        $blocks = [];
        if (is_array($blocksIn)) {
            foreach ($blocksIn as $block) {
                if (!is_array($block)) {
                    continue;
                }
                $title = trim((string) ($block['title'] ?? ''));
                $note = trim((string) ($block['note'] ?? ''));
                $itemsRaw = (string) ($block['items'] ?? '');
                $items = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $itemsRaw) ?: [])));
                if ($title === '' && $items === []) {
                    continue;
                }
                $row = ['title' => $title, 'items' => $items];
                if ($note !== '') {
                    $row['note'] = $note;
                }
                $blocks[] = $row;
            }
        } else {
            $decoded = json_decode((string) ($_POST['blocks_json'] ?? '[]'), true);
            $blocks = is_array($decoded) ? $decoded : [];
        }
        $coverId = (int) ($_POST['cover_media_id'] ?? 0) ?: null;
        $imageUrl = trim((string) ($_POST['image_url'] ?? '')) ?: null;
        if ($coverId) {
            $media = Database::fetch('SELECT public_path FROM media WHERE id=?', [$coverId]);
            if ($media) {
                $imageUrl = (string) $media['public_path'];
            }
        }
        $data = [
            'slug' => $slug,
            'legacy_path' => trim((string) ($_POST['legacy_path'] ?? ($existing['legacy_path'] ?? ''))) ?: null,
            'title_fr' => $titleFr,
            'title_en' => trim((string) ($_POST['title_en'] ?? '')),
            'lead_fr' => trim((string) ($_POST['lead_fr'] ?? '')),
            'lead_en' => trim((string) ($_POST['lead_en'] ?? '')),
            'body_fr' => purify((string) ($_POST['body_fr'] ?? '')),
            'body_en' => purify((string) ($_POST['body_en'] ?? '')),
            'blocks_json' => json_encode($blocks, JSON_UNESCAPED_UNICODE),
            'cta_label_fr' => trim((string) ($_POST['cta_label_fr'] ?? '')) ?: null,
            'cta_label_en' => trim((string) ($_POST['cta_label_en'] ?? '')) ?: null,
            'cover_media_id' => $coverId,
            'image_url' => $imageUrl,
            'meta_title_fr' => trim((string) ($_POST['meta_title_fr'] ?? '')) ?: null,
            'meta_title_en' => trim((string) ($_POST['meta_title_en'] ?? '')) ?: null,
            'meta_desc_fr' => trim((string) ($_POST['meta_desc_fr'] ?? '')) ?: null,
            'meta_desc_en' => trim((string) ($_POST['meta_desc_en'] ?? '')) ?: null,
            'is_published' => isset($_POST['is_published']) ? 1 : 0,
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ];
        $newId = SpaPage::save($data, $id);
        ActivityLogger::log((int) $user['id'], $id ? 'spa.update' : 'spa.create', 'spa_page', $newId);
        flash('ok', 'C’est enregistré.');
        redirect(admin_url('spa/' . $newId));
    }

    public function homeSections(): void
    {
        $user = Auth::requireLogin();
        HomeSection::ensureDefaults();
        $items = HomeSection::allAdmin();
        $byKey = [];
        foreach ($items as $item) {
            $byKey[(string) $item['section_key']] = $item;
        }
        View::renderAdmin('admin/home/index', [
            'title' => 'Page d’accueil',
            'user' => $user,
            'items' => $items,
            'byKey' => $byKey,
            'heroImage' => Settings::get('hero_image', ''),
            'serviceIcons' => [
                'wifi' => 'Wi‑Fi',
                'leaf' => 'Spa / nature',
                'food' => 'Restauration',
                'car' => 'Transport',
                'pool' => 'Piscine',
                'snow' => 'Climatisation',
                'tv' => 'TV / CANAL+',
                'check' => 'Autre',
            ],
        ]);
    }

    public function homeSectionSave(): void
    {
        $user = Auth::requireLogin();
        Csrf::requireValid($_POST['_csrf'] ?? null);
        $id = (int) ($_POST['id'] ?? 0);
        $existing = Database::fetch('SELECT * FROM home_sections WHERE id=?', [$id]);
        if (!$existing) {
            flash('error', 'Section introuvable.');
            redirect(admin_url('accueil'));
        }

        $key = (string) $existing['section_key'];
        $extra = HomeSection::extra($existing);

        unset($extra['eyebrow_fr'], $extra['eyebrow_en']);

        if ($key === 'intro') {
            $extra['quote_fr'] = trim((string) ($_POST['quote_fr'] ?? ''));
            $extra['quote_en'] = trim((string) ($_POST['quote_en'] ?? ''));
        }

        if ($key === 'amenities') {
            $extra['items'] = $this->parseLabeledItems($_POST['amenity'] ?? [], false);
        }

        if ($key === 'services') {
            $extra['items'] = $this->parseServiceItems($_POST['service'] ?? []);
        }

        if ($key === 'cta') {
            $pointsFr = $_POST['points_fr'] ?? [];
            $pointsEn = $_POST['points_en'] ?? [];
            if (!is_array($pointsFr)) {
                $pointsFr = preg_split('/\r\n|\r|\n/', (string) $pointsFr) ?: [];
            }
            if (!is_array($pointsEn)) {
                $pointsEn = preg_split('/\r\n|\r|\n/', (string) $pointsEn) ?: [];
            }
            $extra['points_fr'] = array_values(array_filter(array_map(static fn ($v) => trim((string) $v), $pointsFr)));
            $extra['points_en'] = array_values(array_filter(array_map(static fn ($v) => trim((string) $v), $pointsEn)));
        }

        $mediaId = (int) ($_POST['media_id'] ?? 0) ?: null;
        if ($key === 'hero') {
            $slidesIn = $_POST['slides'] ?? [];
            if (!is_array($slidesIn)) {
                $slidesIn = [];
            }
            $slides = [];
            foreach ($slidesIn as $url) {
                $url = trim((string) $url);
                if ($url === '' || in_array($url, $slides, true)) {
                    continue;
                }
                $slides[] = $url;
                if (count($slides) >= 3) {
                    break;
                }
            }
            $extra['slides'] = $slides;
            if ($slides !== []) {
                Settings::set('hero_image', $slides[0]);
            }
        }

        HomeSection::save([
            'title_fr' => trim((string) ($_POST['title_fr'] ?? '')),
            'title_en' => trim((string) ($_POST['title_en'] ?? '')),
            'subtitle_fr' => trim((string) ($_POST['subtitle_fr'] ?? '')),
            'subtitle_en' => trim((string) ($_POST['subtitle_en'] ?? '')),
            'body_fr' => purify((string) ($_POST['body_fr'] ?? '')),
            'body_en' => purify((string) ($_POST['body_en'] ?? '')),
            'media_id' => $mediaId,
            'extra_json' => json_encode($extra, JSON_UNESCAPED_UNICODE),
            'is_enabled' => isset($_POST['is_enabled']) ? 1 : 0,
            'sort_order' => (int) ($_POST['sort_order'] ?? ($existing['sort_order'] ?? 0)),
        ], $id);
        ActivityLogger::log((int) $user['id'], 'home.update', 'home_section', $id);
        flash('ok', 'C’est enregistré.');
        redirect(admin_url('accueil') . '#' . rawurlencode($key));
    }

    private function parseLabeledItems(mixed $rows, bool $withText = true): array
    {
        if (!is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $labelFr = trim((string) ($row['label_fr'] ?? $row['title_fr'] ?? ''));
            if ($labelFr === '') {
                continue;
            }
            $item = [
                'icon' => trim((string) ($row['icon'] ?? 'check')) ?: 'check',
                'label_fr' => $labelFr,
                'label_en' => trim((string) ($row['label_en'] ?? $row['title_en'] ?? '')),
            ];
            if ($withText) {
                $item['title_fr'] = $labelFr;
                $item['title_en'] = $item['label_en'];
                $item['text_fr'] = trim((string) ($row['text_fr'] ?? ''));
                $item['text_en'] = trim((string) ($row['text_en'] ?? ''));
            }
            $out[] = $item;
        }
        return $out;
    }

    private function parseServiceItems(mixed $rows): array
    {
        if (!is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $titleFr = trim((string) ($row['title_fr'] ?? ''));
            if ($titleFr === '') {
                continue;
            }
            $out[] = [
                'icon' => trim((string) ($row['icon'] ?? 'check')) ?: 'check',
                'title_fr' => $titleFr,
                'title_en' => trim((string) ($row['title_en'] ?? '')),
                'text_fr' => trim((string) ($row['text_fr'] ?? '')),
                'text_en' => trim((string) ($row['text_en'] ?? '')),
            ];
        }
        return $out;
    }

    public function bookings(): void
    {
        $user = Auth::requireLogin();
        $status = isset($_GET['statut']) ? (string) $_GET['statut'] : (isset($_GET['status']) ? (string) $_GET['status'] : null);
        if ($status === '') {
            $status = null;
        }
        if ($status !== null && !in_array($status, ['new', 'confirmed', 'cancelled', 'completed'], true)) {
            $status = null;
        }

        $items = Booking::allAdmin($status);
        $selectedId = (int) ($_GET['id'] ?? 0);
        $selected = $selectedId > 0 ? Booking::find($selectedId) : [];
        if (!$selected && $items) {
            $selected = $items[0];
            $selectedId = (int) $selected['id'];
        }

        View::renderAdmin('admin/bookings/index', [
            'title' => 'Réservations',
            'user' => $user,
            'items' => $items,
            'filterStatus' => $status,
            'counts' => Booking::counts(),
            'selectedId' => $selectedId,
            'selected' => $selected ?: null,
        ]);
    }

    public function bookingUpdate(): void
    {
        $user = Auth::requireLogin();
        Csrf::requireValid($_POST['_csrf'] ?? null);
        $id = (int) ($_POST['id'] ?? 0);
        $status = (string) ($_POST['status'] ?? 'new');
        if (!in_array($status, ['new', 'confirmed', 'cancelled', 'completed'], true)) {
            $status = 'new';
        }
        $notes = array_key_exists('admin_notes', $_POST)
            ? (trim((string) $_POST['admin_notes']) ?: null)
            : (Booking::find($id)['admin_notes'] ?? null);
        if (is_string($notes) && $notes === '') {
            $notes = null;
        }
        Booking::updateStatus($id, $status, $notes);
        ActivityLogger::log((int) $user['id'], 'booking.update', 'booking', $id, ['status' => $status]);
        flash('ok', 'Réservation mise à jour.');
        self::redirectBookings($id, trim((string) ($_POST['filter_status'] ?? '')));
    }

    public function bookingResendMail(): void
    {
        $user = Auth::requireLogin();
        Csrf::requireValid($_POST['_csrf'] ?? null);
        $id = (int) ($_POST['id'] ?? 0);
        $booking = Booking::find($id);
        if (!$booking) {
            flash('error', 'Réservation introuvable.');
            redirect(admin_url('reservations'));
        }

        $mail = Mailer::sendBookingNotifications($booking);
        if (!empty($mail['admin']) || !empty($mail['guest'])) {
            \App\Core\Database::query('UPDATE bookings SET notified_at = NOW() WHERE id = ?', [$id]);
            ActivityLogger::log((int) $user['id'], 'booking.mail_resend', 'booking', $id);
            $parts = [];
            if ($mail['admin']) {
                $parts[] = 'équipe (' . implode(', ', $mail['admin_to']) . ')';
            }
            if ($mail['guest']) {
                $parts[] = 'client (' . $mail['guest_to'] . ')';
            }
            flash('ok', 'E-mails renvoyés : ' . implode(' · ', $parts) . '.');
        } else {
            $detail = $mail['errors'][0] ?? 'Vérifiez les paramètres SMTP.';
            flash('error', 'Échec d’envoi des e-mails de réservation : ' . $detail);
        }
        self::redirectBookings($id, trim((string) ($_POST['filter_status'] ?? '')));
    }

    private static function redirectBookings(int $id, string $filter): never
    {
        $back = admin_url('reservations');
        $qs = [];
        if (in_array($filter, ['new', 'confirmed', 'cancelled', 'completed'], true)) {
            $qs['statut'] = $filter;
        }
        if ($id > 0) {
            $qs['id'] = (string) $id;
        }
        if ($qs) {
            $back .= '?' . http_build_query($qs);
        }
        redirect($back);
    }

    public function messages(): void
    {
        $user = Auth::requireLogin();
        $status = isset($_GET['statut']) ? (string) $_GET['statut'] : null;
        if ($status === '') {
            $status = null;
        }
        if ($status !== null && !in_array($status, ['new', 'read', 'archived'], true)) {
            $status = null;
        }

        $items = Message::allAdmin($status);
        $selectedId = (int) ($_GET['id'] ?? 0);
        $selected = $selectedId > 0 ? Message::find($selectedId) : null;
        if (!$selected && $items) {
            $selected = $items[0];
            $selectedId = (int) $selected['id'];
        }

        View::renderAdmin('admin/messages/index', [
            'title' => 'Messages',
            'user' => $user,
            'items' => $items,
            'filterStatus' => $status,
            'counts' => Message::counts(),
            'selectedId' => $selectedId,
            'selected' => $selected,
        ]);
    }

    public function messageUpdate(): void
    {
        $user = Auth::requireLogin();
        Csrf::requireValid($_POST['_csrf'] ?? null);
        $id = (int) ($_POST['id'] ?? 0);
        $status = (string) ($_POST['status'] ?? 'read');
        if (!in_array($status, ['new', 'read', 'archived'], true)) {
            $status = 'read';
        }
        Message::mark($id, $status);
        ActivityLogger::log((int) $user['id'], 'message.update', 'message', $id);
        flash('ok', 'Message mis à jour.');

        $back = admin_url('messages');
        $qs = [];
        $filter = trim((string) ($_POST['filter_status'] ?? ''));
        if (in_array($filter, ['new', 'read', 'archived'], true)) {
            $qs['statut'] = $filter;
        }
        if ($id > 0) {
            $qs['id'] = (string) $id;
        }
        if ($qs) {
            $back .= '?' . http_build_query($qs);
        }
        redirect($back);
    }

    public function media(): void
    {
        $user = Auth::requireLogin();
        $items = Database::fetchAll('SELECT * FROM media ORDER BY created_at DESC LIMIT 200');
        View::renderAdmin('admin/media/index', [
            'title' => 'Médiathèque', 'user' => $user, 'items' => $items, 'error' => flash('media_error'),
        ]);
    }

    public function mediaUpload(): void
    {
        $user = Auth::requireLogin();
        Csrf::requireValid($_POST['_csrf'] ?? null);
        try {
            (new UploadService())->store($_FILES['file'] ?? [], (int) $user['id'], trim((string) ($_POST['alt_fr'] ?? '')) ?: null);
            flash('ok', 'Fichier importé.');
        } catch (\Throwable $e) {
            flash('media_error', $e->getMessage());
        }
        redirect(admin_url('mediatheque'));
    }

    public function settings(): void
    {
        $user = Auth::requireLogin();
        View::renderAdmin('admin/settings', [
            'title' => 'Paramètres', 'user' => $user, 'settings' => Settings::all(),
        ]);
    }

    public function settingsSave(): void
    {
        $user = Auth::requireLogin();
        Csrf::requireValid($_POST['_csrf'] ?? null);
        $heroMediaId = (int) ($_POST['hero_media_id'] ?? 0);
        if ($heroMediaId > 0) {
            $media = Database::fetch('SELECT public_path FROM media WHERE id=?', [$heroMediaId]);
            if ($media) {
                $_POST['hero_image'] = (string) $media['public_path'];
            }
        }
        foreach ([
            'phone', 'whatsapp', 'address',
            'meta_title_fr', 'meta_desc_fr', 'meta_title_en', 'meta_desc_en',
            'hero_image',
            'mail_driver', 'mail_from', 'mail_from_name', 'mail_booking',
            'smtp_host', 'smtp_port', 'smtp_user', 'smtp_secure',
        ] as $key) {
            if (array_key_exists($key, $_POST)) {
                Settings::set($key, trim((string) $_POST[$key]));
            }
        }

        // SMTP password: empty means keep existing (do not wipe).
        if (array_key_exists('smtp_pass', $_POST)) {
            $smtpPass = (string) $_POST['smtp_pass'];
            if ($smtpPass !== '') {
                Settings::set('smtp_pass', $smtpPass);
            }
        }

        $driver = strtolower(trim((string) ($_POST['mail_driver'] ?? 'mail')));
        if (!in_array($driver, ['mail', 'smtp'], true)) {
            Settings::set('mail_driver', 'mail');
        }
        $secure = strtolower(trim((string) ($_POST['smtp_secure'] ?? 'tls')));
        if (!in_array($secure, ['tls', 'ssl', 'none'], true)) {
            Settings::set('smtp_secure', 'tls');
        }
        $port = (int) ($_POST['smtp_port'] ?? 587);
        if ($port < 1 || $port > 65535) {
            Settings::set('smtp_port', '587');
        }

        ActivityLogger::log((int) $user['id'], 'settings.update');
        flash('ok', 'C’est enregistré.');
        redirect(admin_url('parametres'));
    }

    public function settingsTestMail(): void
    {
        $user = Auth::requireLogin();
        Csrf::requireValid($_POST['_csrf'] ?? null);

        $to = trim((string) ($_POST['test_email_to'] ?? ''));
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $to = Mailer::bookingAddress();
        }
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Adresse de test invalide.');
            redirect(admin_url('parametres'));
        }

        $snap = Mailer::snapshot();
        $when = date('d/m/Y H:i:s');
        $html = '<p>Ceci est un e-mail de test depuis l’admin Micheline &amp; Emile.</p>'
            . '<ul>'
            . '<li>Date : ' . e($when) . '</li>'
            . '<li>Mode : ' . e($snap['driver']) . '</li>'
            . '<li>From : ' . e($snap['from_name'] . ' &lt;' . $snap['from'] . '&gt;') . '</li>'
            . ($snap['driver'] === 'smtp'
                ? '<li>SMTP : ' . e($snap['host'] . ':' . $snap['port'] . ' / ' . $snap['secure']) . '</li>'
                : '')
            . '</ul>'
            . '<p>Si vous lisez ceci, la configuration e-mail fonctionne.</p>';

        try {
            $result = Mailer::sendWithDetails(
                $to,
                '[Test] Micheline & Emile — ' . $when,
                $html,
                "Test e-mail Micheline & Emile — {$when}"
            );
            ActivityLogger::log((int) $user['id'], 'settings.mail_test', 'settings', null);
            flash('ok', $result['message']);
        } catch (\Throwable $e) {
            flash('error', 'Échec du test e-mail : ' . $e->getMessage());
        }
        redirect(admin_url('parametres'));
    }

    public function users(): void
    {
        $user = Auth::requireLogin();
        if (!Auth::can($user, '*') && $user['role_slug'] !== 'admin') {
            http_response_code(403); exit('Interdit');
        }
        $items = Database::fetchAll('SELECT u.id,u.email,u.name,u.role_id,u.is_active,u.totp_enabled,u.last_login_at,r.name AS role_name FROM users u JOIN roles r ON r.id=u.role_id ORDER BY u.id');
        $editId = (int) ($_GET['edit'] ?? 0);
        $editUser = null;
        if ($editId > 0) {
            foreach ($items as $row) {
                if ((int) $row['id'] === $editId) {
                    $editUser = $row;
                    break;
                }
            }
        }
        View::renderAdmin('admin/users/index', [
            'title' => 'Utilisateurs',
            'user' => $user,
            'items' => $items,
            'editUser' => $editUser,
        ]);
    }

    public function userSave(): void
    {
        $user = Auth::requireLogin();
        Csrf::requireValid($_POST['_csrf'] ?? null);
        if ($user['role_slug'] !== 'admin') { http_response_code(403); exit('Interdit'); }
        $id = (int) ($_POST['id'] ?? 0) ?: null;
        $email = mb_strtolower(trim((string) $_POST['email']));
        $name = trim((string) $_POST['name']);
        $roleId = (int) ($_POST['role_id'] ?? 2);
        $active = isset($_POST['is_active']) ? 1 : 0;
        if ($id) {
            Database::query('UPDATE users SET email=?, name=?, role_id=?, is_active=? WHERE id=?', [$email,$name,$roleId,$active,$id]);
            if (!empty($_POST['password'])) {
                $hash = password_hash((string) $_POST['password'], password_algo());
                Database::query('UPDATE users SET password_hash=? WHERE id=?', [$hash, $id]);
            }
        } else {
            $hash = password_hash((string) $_POST['password'], password_algo());
            Database::insert('INSERT INTO users (role_id,email,password_hash,name,is_active) VALUES (?,?,?,?,?)', [$roleId,$email,$hash,$name,$active]);
        }
        ActivityLogger::log((int) $user['id'], 'user.save');
        flash('ok', 'Utilisateur enregistré.');
        redirect(admin_url('utilisateurs'));
    }

    public function security2fa(): void
    {
        $user = Auth::requireLogin();
        $tfa = \App\Services\TotpService::make();
        if (empty($_SESSION['totp_setup_secret'])) {
            $_SESSION['totp_setup_secret'] = $tfa->createSecret();
        }
        $secret = $_SESSION['totp_setup_secret'];
        $qr = $tfa->getQRCodeImageAsDataUri($user['email'], $secret);
        View::renderAdmin('admin/security', [
            'title' => 'Sécurité 2FA',
            'user' => $user,
            'secret' => $secret,
            'qr' => $qr,
            'enabled' => (int) $user['totp_enabled'] === 1,
            'error' => flash('login_error'),
            'ok' => flash('ok'),
        ]);
    }

    public function security2faEnable(): void
    {
        $user = Auth::requireLogin();
        Csrf::requireValid($_POST['_csrf'] ?? null);
        $secret = $_SESSION['totp_setup_secret'] ?? '';
        $code = preg_replace('/\s+/', '', (string) ($_POST['totp'] ?? ''));
        $tfa = \App\Services\TotpService::make();
        if (!$secret || !$tfa->verifyCode($secret, $code)) {
            flash('login_error', 'Code invalide.');
            redirect(admin_url('securite'));
        }
        Database::query('UPDATE users SET totp_secret=?, totp_enabled=1 WHERE id=?', [$secret, $user['id']]);
        unset($_SESSION['totp_setup_secret']);
        ActivityLogger::log((int) $user['id'], 'auth.2fa_enable');
        flash('ok', '2FA activée.');
        redirect(admin_url('securite'));
    }

    public function security2faDisable(): void
    {
        $user = Auth::requireLogin();
        Csrf::requireValid($_POST['_csrf'] ?? null);
        Database::query('UPDATE users SET totp_secret=NULL, totp_enabled=0 WHERE id=?', [$user['id']]);
        ActivityLogger::log((int) $user['id'], 'auth.2fa_disable');
        flash('ok', '2FA désactivée.');
        redirect(admin_url('securite'));
    }

    private function roomPayload(array $post): array
    {
        $equip = [];
        if (isset($post['equipment']) && is_array($post['equipment'])) {
            foreach ($post['equipment'] as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $label = trim((string) ($row['label'] ?? ''));
                if ($label === '') {
                    continue;
                }
                $icon = trim((string) ($row['icon'] ?? 'check')) ?: 'check';
                $equip[] = [$icon, $label];
            }
        } else {
            foreach (array_filter(array_map('trim', explode("\n", (string) ($post['equipment'] ?? '')))) as $line) {
                $parts = array_map('trim', explode('|', $line, 2));
                $equip[] = count($parts) === 2 ? $parts : ['check', $parts[0]];
            }
        }

        $practical = [];
        if (isset($post['practical']) && is_array($post['practical'])) {
            foreach ($post['practical'] as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $left = trim((string) ($row['left'] ?? $row['label'] ?? ''));
                $right = trim((string) ($row['right'] ?? $row['value'] ?? ''));
                if ($left === '' && $right === '') {
                    continue;
                }
                $practical[] = [$left, $right];
            }
        } else {
            foreach (array_filter(array_map('trim', explode("\n", (string) ($post['practical'] ?? '')))) as $line) {
                $parts = array_map('trim', explode('|', $line, 2));
                if (count($parts) === 2) {
                    $practical[] = $parts;
                }
            }
        }

        if (isset($post['gallery']) && is_array($post['gallery'])) {
            $gallery = array_values(array_filter(array_map(static fn ($v) => trim((string) $v), $post['gallery'])));
        } else {
            $gallery = array_values(array_filter(array_map('trim', explode("\n", (string) ($post['gallery'] ?? '')))));
        }

        $nameFr = trim((string) ($post['name_fr'] ?? ''));
        $slug = trim((string) ($post['slug'] ?? ''));
        if ($slug === '' && $nameFr !== '') {
            $slug = slugify($nameFr);
        }

        $coverId = (int) ($post['cover_media_id'] ?? 0) ?: null;
        if (!$coverId && $gallery) {
            $media = Database::fetch('SELECT id FROM media WHERE public_path = ? LIMIT 1', [$gallery[0]]);
            if ($media) {
                $coverId = (int) $media['id'];
            }
        }

        return [
            'slug' => $slug,
            'category' => (string) ($post['category'] ?? 'suites'),
            'name_fr' => $nameFr,
            'name_en' => trim((string) ($post['name_en'] ?? '')),
            'short_fr' => trim((string) ($post['short_fr'] ?? '')),
            'short_en' => trim((string) ($post['short_en'] ?? '')),
            'body_fr' => purify((string) ($post['body_fr'] ?? '')),
            'body_en' => purify((string) ($post['body_en'] ?? '')),
            'capacity' => (int) ($post['capacity'] ?? 2),
            'area_m2' => (int) ($post['area_m2'] ?? 0),
            'bedrooms' => (int) ($post['bedrooms'] ?? 1),
            'bathrooms' => (int) ($post['bathrooms'] ?? 1),
            'beds' => trim((string) ($post['beds'] ?? '')) ?: null,
            'price_fcfa' => (int) ($post['price_fcfa'] ?? 0),
            'price_from' => isset($post['price_from']) ? 1 : 0,
            'cover_media_id' => $coverId,
            'practical_json' => json_encode($practical, JSON_UNESCAPED_UNICODE),
            'equipment_json' => json_encode($equip, JSON_UNESCAPED_UNICODE),
            'gallery_json' => json_encode($gallery, JSON_UNESCAPED_UNICODE),
            'meta_title_fr' => trim((string) ($post['meta_title_fr'] ?? '')) ?: null,
            'meta_title_en' => trim((string) ($post['meta_title_en'] ?? '')) ?: null,
            'meta_desc_fr' => trim((string) ($post['meta_desc_fr'] ?? '')) ?: null,
            'meta_desc_en' => trim((string) ($post['meta_desc_en'] ?? '')) ?: null,
            'is_published' => isset($post['is_published']) ? 1 : 0,
            'sort_order' => (int) ($post['sort_order'] ?? 0),
        ];
    }

    private function json(array $payload): never
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
