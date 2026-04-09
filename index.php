<?php
// index.php — Login Page
require_once __DIR__ . '/auth.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } elseif (login($username, $password)) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PocketTrack — Admin Login</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  :root {
    --bg: #0f1117;
    --card: #1a1d27;
    --border: #2a2d3e;
    --accent: #4ade80;
    --accent2: #22c55e;
    --text: #f0f2f8;
    --muted: #8b8fa8;
    --danger: #f87171;
    --radius: 12px;
  }
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    background-image: radial-gradient(ellipse at 20% 50%, rgba(74,222,128,0.06) 0%, transparent 60%),
                      radial-gradient(ellipse at 80% 20%, rgba(34,197,94,0.04) 0%, transparent 50%);
  }
  .login-wrap {
    width: 100%;
    max-width: 400px;
  }
  .brand {
    text-align: center;
    margin-bottom: 2rem;
  }
  .brand-icon {
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    border-radius: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.6rem;
    margin-bottom: 0.75rem;
    box-shadow: 0 8px 32px rgba(74,222,128,0.3);
  }
  .brand h1 {
    font-family: 'Syne', sans-serif;
    font-size: 1.8rem;
    font-weight: 800;
    letter-spacing: -0.02em;
    color: var(--text);
  }
  .brand p {
    color: var(--muted);
    font-size: 0.875rem;
    margin-top: 0.25rem;
  }
  .card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 2rem;
  }
  .form-group {
    margin-bottom: 1.25rem;
  }
  label {
    display: block;
    font-size: 0.8rem;
    font-weight: 500;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-bottom: 0.5rem;
  }
  input {
    width: 100%;
    padding: 0.75rem 1rem;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text);
    font-family: inherit;
    font-size: 0.95rem;
    transition: border-color 0.2s;
    outline: none;
  }
  input:focus { border-color: var(--accent); }
  .btn-primary {
    width: 100%;
    padding: 0.85rem;
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    color: #0a1a0f;
    font-family: 'Syne', sans-serif;
    font-weight: 700;
    font-size: 0.95rem;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    letter-spacing: 0.02em;
    transition: opacity 0.2s, transform 0.1s;
    margin-top: 0.5rem;
  }
  .btn-primary:hover { opacity: 0.9; transform: translateY(-1px); }
  .btn-primary:active { transform: translateY(0); }
  .error-msg {
    background: rgba(248,113,113,0.1);
    border: 1px solid rgba(248,113,113,0.3);
    border-radius: 8px;
    color: var(--danger);
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    margin-bottom: 1.25rem;
  }
  .hint {
    text-align: center;
    margin-top: 1.25rem;
    font-size: 0.8rem;
    color: var(--muted);
    background: rgba(74,222,128,0.05);
    border: 1px solid rgba(74,222,128,0.15);
    border-radius: 8px;
    padding: 0.6rem;
  }
  .hint strong { color: var(--accent); }
</style>
</head>
<body>
<div class="login-wrap">
  <div class="brand">
    <div class="brand-icon">💰</div>
    <h1>PocketTrack</h1>
    <p>Student Pocket Money Management</p>
  </div>
  <div class="card">
    <?php if ($error): ?>
      <div class="error-msg">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST" action="index.php">
      <?= csrfField() ?>
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" placeholder="Enter username" autocomplete="username" required value="<?= sanitize($_POST['username'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Enter password" autocomplete="current-password" required>
      </div>
      <button type="submit" class="btn-primary">Sign In →</button>
    </form>
  </div>
  <div class="hint">Default credentials: <strong>admin</strong> / <strong>admin123</strong></div>
</div>
</body>
</html>
