<?php
// config.php — App configuration (reads from .env)

require_once __DIR__ . '/env.php';

// Load .env from project root (silently skipped if not found)
loadEnv(__DIR__ . '/.env');

// ── Database ──
define('DB_HOST',    env('DB_HOST',    'localhost'));
define('DB_NAME',    env('DB_NAME',    'pocket_money_db'));
define('DB_USER',    env('DB_USER',    'root'));
define('DB_PASS',    env('DB_PASS',    ''));
define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));

// ── App ──
define('APP_NAME',        env('APP_NAME',        'PocketTrack'));
define('APP_ENV',         env('APP_ENV',         'production'));
define('SESSION_TIMEOUT', (int) env('SESSION_TIMEOUT', 3600));

// ── PDO singleton ──
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn     = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            $msg = APP_ENV === 'development'
                ? 'Database connection failed: ' . $e->getMessage()
                : 'Database connection failed. Please contact the administrator.';
            die(json_encode(['success' => false, 'message' => $msg]));
        }
    }
    return $pdo;
}

// ── Probabilistic cleanup: delete transactions older than 1 year ──
function cleanupOldTransactions(): void {
    try {
        getDB()->exec("DELETE FROM transactions WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)");
    } catch (PDOException) { /* silent fail */ }
}

if (rand(1, 100) === 1) {
    cleanupOldTransactions();
}
