<?php
declare(strict_types=1);

namespace App\Controllers\Front;

use App\Core\Csrf;
use App\Core\Env;
use App\Core\I18n;
use App\Core\View;
use App\Models\Message;
use App\Services\FormGuard;
use App\Services\Mailer;
use App\Services\Settings;

final class ContactController
{
    public function form(): void
    {
        View::render('public/contact', [
            'title' => __('contact.title') . ' — Micheline & Emile',
            'metaDescription' => __('seo.contact_desc'),
            'errors' => $_SESSION['_errors'] ?? [],
            'sent' => flash('contact_sent'),
            'canonical' => url('/contact/'),
            'ogImage' => asset('img/og-default.jpg'),
            'phone' => Settings::get('phone', '+237 653 29 02 58 / 222 20 86 71'),
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
            redirect('/contact/');
        }

        $errors = [];
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $body = trim((string) ($_POST['body'] ?? ''));
        if ($name === '') $errors['name'] = __('contact.err_name');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = __('contact.err_email');
        if ($body === '' || mb_strlen($body) < 10) $errors['body'] = __('contact.err_body');
        if ($errors) {
            $_SESSION['_errors'] = $errors;
            $_SESSION['_old'] = $_POST;
            redirect('/contact/');
        }

        Message::create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
            'subject' => $subject !== '' ? $subject : null,
            'body' => $body,
            'locale' => I18n::locale(),
            'ip_address' => client_ip(),
        ]);

        $html = '<p>Nouveau message de ' . e($name) . ' &lt;' . e($email) . '&gt;</p><p>' . nl2br(e($body)) . '</p>';
        Mailer::send(Mailer::bookingAddress(), 'Contact site — ' . ($subject ?: $name), $html);

        flash('contact_sent', '1');
        redirect('/contact/');
    }
}
