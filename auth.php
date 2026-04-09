<?php
// auth.php — Session, Authentication & CSRF

require_once __DIR__ . '/config.php';

// ── Secure session cookie settings (before session_start) ──
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => isset($_SERVER['HTTPS']),   // true on HTTPS
    'httponly' => true,
    'samesite' => 'Strict',
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Session helpers ──────────────────────────────────────────

function isLoggedIn(): bool {
    if (!isset($_SESSION['admin_id'])) return false;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

function login(string $username, string $password): bool {
    $db   = getDB();
    $stmt = $db->prepare("SELECT id, username, password FROM admin WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($password, $admin['password'])) {
        // Regenerate session ID on privilege escalation
        session_regenerate_id(true);
        $_SESSION['admin_id']       = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['last_activity']  = time();
        return true;
    }
    return false;
}

function logout(): void {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}

// ── CSRF helpers ─────────────────────────────────────────────

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Renders a hidden CSRF input — call inside every <form> */
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES) . '">';
}

/** Validates CSRF token on POST requests; dies with 403 on failure */
function verifyCsrf(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        die('403 Forbidden — invalid CSRF token. Please go back and try again.');
    }
}

// ── Sanitize helper ──────────────────────────────────────────

function sanitize(string $val): string {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}
