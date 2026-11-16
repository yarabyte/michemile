<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;

final class DashboardController
{
    public function index(): void
    {
        $user = Auth::requireLogin();
        $stats = [
            'bookings_new' => (int) (Database::fetch("SELECT COUNT(*) c FROM bookings WHERE status='new'")['c'] ?? 0),
            'messages_new' => (int) (Database::fetch("SELECT COUNT(*) c FROM messages WHERE status='new'")['c'] ?? 0),
            'rooms' => (int) (Database::fetch('SELECT COUNT(*) c FROM accommodations')['c'] ?? 0),
            'media' => (int) (Database::fetch('SELECT COUNT(*) c FROM media')['c'] ?? 0),
        ];
        $recent = Database::fetchAll(
            'SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 20'
        );
        View::renderAdmin('admin/dashboard', [
            'title' => 'Tableau de bord',
            'user' => $user,
            'stats' => $stats,
            'recent' => $recent,
        ]);
    }
}
