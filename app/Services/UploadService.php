<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class UploadService
{
    private const ALLOWED = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];

    public function store(array $file, ?int $userId = null, ?string $altFr = null): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Échec de l’upload.');
        }
        if (($file['size'] ?? 0) > 8 * 1024 * 1024) {
            throw new \RuntimeException('Fichier trop volumineux (max 8 Mo).');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']) ?: '';
        if (!isset(self::ALLOWED[$mime])) {
            throw new \RuntimeException('Type de fichier non autorisé.');
        }

        $ext = self::ALLOWED[$mime];
        $random = bin2hex(random_bytes(16));
        $subdir = date('Y/m');
        $root = dirname(__DIR__, 2) . '/public/uploads/' . $subdir;
        if (!is_dir($root) && !mkdir($root, 0750, true) && !is_dir($root)) {
            throw new \RuntimeException('Impossible de créer le dossier uploads.');
        }

        $width = null;
        $height = null;
        $finalName = $random . '.' . $ext;
        $dest = $root . '/' . $finalName;

        if (str_starts_with($mime, 'image/')) {
            $finalName = $random . '.webp';
            $dest = $root . '/' . $finalName;
            $this->reencodeImage($file['tmp_name'], $dest, $mime, $width, $height);
            $mime = 'image/webp';
            $ext = 'webp';
        } else {
            if (!move_uploaded_file($file['tmp_name'], $dest)) {
                throw new \RuntimeException('Impossible d’enregistrer le fichier.');
            }
        }

        $public = '/uploads/' . $subdir . '/' . $finalName;
        $id = Database::insert(
            'INSERT INTO media (disk_path, public_path, original_name, mime, width, height, size_bytes, alt_fr, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $dest,
                $public,
                (string) ($file['name'] ?? $finalName),
                $mime,
                $width,
                $height,
                filesize($dest) ?: 0,
                $altFr,
                $userId,
            ]
        );

        return Database::fetch('SELECT * FROM media WHERE id = ?', [(int) $id]) ?? [];
    }

    private function reencodeImage(string $src, string $dest, string $mime, ?int &$width, ?int &$height): void
    {
        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($src),
            'image/png' => @imagecreatefrompng($src),
            'image/webp' => @imagecreatefromwebp($src),
            default => false,
        };
        if (!$image) {
            throw new \RuntimeException('Image illisible.');
        }
        $width = imagesx($image);
        $height = imagesy($image);
        if (!imagewebp($image, $dest, 82)) {
            imagedestroy($image);
            throw new \RuntimeException('Ré-encodage WebP impossible.');
        }
        imagedestroy($image);
    }
}
