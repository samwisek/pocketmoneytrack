<?php
// env.php — Lightweight .env file parser
// Loads key=value pairs from .env into getenv() / $_ENV

function loadEnv(string $path): void {
    if (!file_exists($path)) return;

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        // Skip comments and blank lines
        if ($line === '' || str_starts_with($line, '#')) continue;

        if (!str_contains($line, '=')) continue;

        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);

        // Strip inline comments
        if (str_contains($value, ' #')) {
            $value = trim(explode(' #', $value, 2)[0]);
        }

        // Strip surrounding quotes
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        if ($key !== '' && !array_key_exists($key, $_ENV)) {
            $_ENV[$key]    = $value;
            putenv("$key=$value");
        }
    }
}

function env(string $key, mixed $default = null): mixed {
    $val = $_ENV[$key] ?? getenv($key);
    if ($val === false || $val === null || $val === '') return $default;
    return $val;
}
