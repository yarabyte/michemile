<?php
declare(strict_types=1);

namespace App\Services;

use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\Providers\Qr\QRServerProvider;

final class TotpService
{
    public static function make(): TwoFactorAuth
    {
        return new TwoFactorAuth(new QRServerProvider(), 'Micheline & Emile');
    }
}
