<?php
declare(strict_types=1);

namespace App\Controllers\Front;

use App\Core\Csrf;
use App\Core\Env;
use App\Core\I18n;
use App\Core\View;
use App\Models\Accommodation;
use App\Models\Booking;
use App\Services\FormGuard;
use App\Services\Mailer;

final class BookingController
{
    public function form(): void
    {
        $preselect = isset($_GET['room']) ? (string) $_GET['room'] : '';
        View::render('public/booking', [
            'title' => __('booking.title') . ' — Micheline & Emile',
            'metaDescription' => __('seo.booking_desc'),
            'rooms' => Accommodation::published(),
            'preselect' => $preselect,
            'errors' => $_SESSION['_errors'] ?? [],
            'canonical' => url('/reservation/'),
            'ogImage' => asset('img/og-default.jpg'),
            'recaptchaSiteKey' => Env::get('RECAPTCHA_SITE_KEY', ''),
        ]);
        unset($_SESSION['_errors'], $_SESSION['_old']);
    }

    public function submit(): void
    {
        Csrf::requireValid($_POST['_csrf'] ?? null);
        try {
            FormGuard::assertHuman($_POST);
        } catch (\Throwable $e) {
            $_SESSION['_errors'] = ['_form' => $e->getMessage()];
            $_SESSION['_old'] = $_POST;
            redirect('/reservation/');
        }

        $errors = [];
        $name = trim((string) ($_POST['guest_name'] ?? ''));
        $email = trim((string) ($_POST['guest_email'] ?? ''));
        $phone = trim((string) ($_POST['guest_phone'] ?? ''));
        $checkIn = (string) ($_POST['check_in'] ?? '');
        $checkOut = (string) ($_POST['check_out'] ?? '');
        $guests = (int) ($_POST['guests'] ?? 1);
        $roomId = (int) ($_POST['accommodation_id'] ?? 0) ?: null;
        $message = trim((string) ($_POST['message'] ?? ''));

        if ($name === '' || mb_strlen($name) > 150) $errors['guest_name'] = __('booking.err_name');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['guest_email'] = __('booking.err_email');
        if ($phone === '') $errors['guest_phone'] = __('booking.err_phone');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $checkIn)) $errors['check_in'] = __('booking.err_check_in');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $checkOut)) $errors['check_out'] = __('booking.err_check_out');
        if ($checkIn && $checkOut && $checkOut <= $checkIn) $errors['check_out'] = __('booking.err_dates');
        if ($guests < 1 || $guests > 12) $errors['guests'] = __('booking.err_guests');
        if ($roomId) {
            $room = Accommodation::find($roomId);
            if (!$room || !(int) $room['is_published']) $errors['accommodation_id'] = __('booking.err_room');
        }

        if ($errors) {
            $_SESSION['_errors'] = $errors;
            $_SESSION['_old'] = $_POST;
            redirect('/reservation/');
        }

        $booking = Booking::create([
            'accommodation_id' => $roomId,
            'guest_name' => $name,
            'guest_email' => $email,
            'guest_phone' => $phone,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests' => $guests,
            'message' => $message !== '' ? $message : null,
            'locale' => I18n::locale(),
            'ip_address' => client_ip(),
            'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ]);

        $mail = $this->notify($booking);

        redirect('/reservation/confirmation/' . $booking['reference'] . '/?mail=' . ($mail['guest'] ? '1' : '0'));
    }

    public function confirm(string $reference): void
    {
        $booking = Booking::findByReference($reference);
        if (!$booking) {
            http_response_code(404);
            View::render('public/404', ['title' => __('error.booking_title')]);
            return;
        }
        View::render('public/booking-confirm', [
            'title' => __('booking.confirm_title') . ' — Micheline & Emile',
            'metaDescription' => __('seo.confirm_desc'),
            'booking' => $booking,
            'mailSent' => (string) ($_GET['mail'] ?? '1') !== '0',
            'canonical' => url('/reservation/confirmation/' . $reference . '/'),
            'ogImage' => asset('img/og-default.jpg'),
            'noindex' => true,
        ]);
    }

    /** @return array{admin:bool,guest:bool,admin_to:list<string>,guest_to:?string,errors:list<string>} */
    private function notify(array $booking): array
    {
        $mail = Mailer::sendBookingNotifications($booking);
        if (!empty($mail['admin']) || !empty($mail['guest'])) {
            \App\Core\Database::query('UPDATE bookings SET notified_at = NOW() WHERE id = ?', [$booking['id']]);
        }
        return $mail;
    }
}
