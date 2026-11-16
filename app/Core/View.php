<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $template, array $data = [], string $layout = 'layouts/main'): void
    {
        $root = dirname(__DIR__) . '/Views/';
        $file = $root . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException('Vue introuvable: ' . $template);
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        $content = ob_get_clean();

        if ($layout) {
            $layoutFile = $root . $layout . '.php';
            require $layoutFile;
            return;
        }

        echo $content;
    }

    public static function renderAdmin(string $template, array $data = []): void
    {
        self::render($template, $data, 'layouts/admin');
    }

    public static function partial(string $template, array $data = []): void
    {
        $file = dirname(__DIR__) . '/Views/' . $template . '.php';
        extract($data, EXTR_SKIP);
        require $file;
    }
}
