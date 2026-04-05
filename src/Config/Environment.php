<?php

declare(strict_types=1);

namespace App\Config;

class Environment
{
    public static function load(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);

            if (str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);

            if ($key !== '' && !array_key_exists($key, $_ENV)) {
                $_ENV[$key] = trim($value);
            }
        }
    }

    private function __construct() {}
}
