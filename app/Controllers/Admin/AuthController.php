<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;

final class AuthController
{
    public function loginForm(): void
    {
        if (Auth::check()) {
            redirect(admin_url());
        }
        View::render('admin/login', [
            'title' => 'Connexion',
            'error' => flash('login_error'),
            'needs2fa' => !empty($_SESSION['pending_2fa_user_id']),
        ], 'layouts/admin-guest');
    }

    public function login(): void
    {
        Csrf::requireValid($_POST['_csrf'] ?? null);
        if (!empty($_SESSION['pending_2fa_user_id'])) {
            $code = preg_replace('/\s+/', '', (string) ($_POST['totp'] ?? ''));
            if (Auth::complete2fa($code)) {
                redirect(admin_url());
            }
            flash('login_error', 'Code 2FA invalide.');
            redirect(admin_url('login'));
        }

        $email = (string) ($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $result = Auth::attempt($email, $password);
        if (!$result['ok']) {
            flash('login_error', $result['error']);
            redirect(admin_url('login'));
        }
        if (!empty($result['needs_2fa'])) {
            redirect(admin_url('login'));
        }
        redirect(admin_url());
    }

    public function logout(): void
    {
        Csrf::requireValid($_POST['_csrf'] ?? null);
        Auth::logout();
        redirect(admin_url('login'));
    }
}
