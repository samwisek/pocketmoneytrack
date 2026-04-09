<?php
// layout_header.php — Shared HTML header & navigation
// Variables expected: $pageTitle (string), $activePage (string)
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'PocketTrack') ?> — PocketTrack</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,400&display=swap" rel="stylesheet">
<style>
:root {
  --bg: #0f1117;
  --surface: #1a1d27;
  --surface2: #222638;
  --border: #2a2d3e;
  --accent: #4ade80;
  --accent2: #22c55e;
  --accent-dim: rgba(74,222,128,0.12);
  --text: #f0f2f8;
  --muted: #8b8fa8;
  --danger: #f87171;
  --warning: #fbbf24;
  --info: #60a5fa;
  --radius: 12px;
  --sidebar-w: 240px;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
body {
  font-family: 'DM Sans', sans-serif;
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
  display: flex;
  background-image:
    radial-gradient(ellipse at 0% 0%, rgba(74,222,128,0.05) 0%, transparent 50%);
}

/* ── Sidebar ── */
.sidebar {
  width: var(--sidebar-w);
  min-height: 100vh;
  background: var(--surface);
  border-right: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  position: fixed;
  top: 0; left: 0;
  z-index: 100;
  transition: transform 0.3s;
}
.sidebar-brand {
  padding: 1.5rem 1.25rem 1rem;
  border-bottom: 1px solid var(--border);
  display: flex;
  align-items: center;
  gap: 0.65rem;
}
.brand-icon {
  width: 36px; height: 36px;
  background: linear-gradient(135deg, var(--accent), var(--accent2));
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem;
  flex-shrink: 0;
  box-shadow: 0 4px 12px rgba(74,222,128,0.3);
}
.brand-text {
  font-family: 'Syne', sans-serif;
  font-weight: 800;
  font-size: 1.1rem;
  letter-spacing: -0.02em;
  line-height: 1;
}
.brand-text span { display: block; font-size: 0.65rem; font-weight: 400; color: var(--muted); font-family: 'DM Sans', sans-serif; letter-spacing: 0; margin-top: 2px; }

.sidebar-nav {
  flex: 1;
  padding: 1rem 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}
.nav-label {
  font-size: 0.65rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: var(--muted);
  padding: 0.5rem 0.5rem 0.25rem;
  margin-top: 0.5rem;
}
.nav-link {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0.6rem 0.75rem;
  border-radius: 8px;
  color: var(--muted);
  text-decoration: none;
  font-size: 0.875rem;
  font-weight: 500;
  transition: all 0.15s;
}
.nav-link .nav-icon { font-size: 1rem; width: 1.25rem; text-align: center; }
.nav-link:hover { background: var(--surface2); color: var(--text); }
.nav-link.active { background: var(--accent-dim); color: var(--accent); }

.sidebar-footer {
  padding: 1rem 0.75rem;
  border-top: 1px solid var(--border);
}
.user-info {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  margin-bottom: 0.75rem;
}
.avatar {
  width: 32px; height: 32px;
  background: var(--surface2);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.8rem;
  border: 1px solid var(--border);
}
.user-name { font-size: 0.8rem; font-weight: 500; }
.user-role { font-size: 0.7rem; color: var(--muted); }
.btn-logout {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  width: 100%;
  padding: 0.5rem 0.75rem;
  background: transparent;
  border: 1px solid var(--border);
  border-radius: 8px;
  color: var(--muted);
  font-family: inherit;
  font-size: 0.8rem;
  cursor: pointer;
  transition: all 0.15s;
  text-decoration: none;
}
.btn-logout:hover { border-color: var(--danger); color: var(--danger); background: rgba(248,113,113,0.05); }

/* ── Main content ── */
.main {
  margin-left: var(--sidebar-w);
  flex: 1;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}
.topbar {
  padding: 1.25rem 1.75rem;
  border-bottom: 1px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: rgba(26,29,39,0.8);
  backdrop-filter: blur(8px);
  position: sticky; top: 0; z-index: 50;
}
.topbar-title {
  font-family: 'Syne', sans-serif;
  font-weight: 700;
  font-size: 1.15rem;
}
.topbar-right { display: flex; align-items: center; gap: 0.75rem; }
.badge {
  font-size: 0.7rem;
  font-weight: 600;
  padding: 0.2rem 0.6rem;
  border-radius: 20px;
  background: var(--accent-dim);
  color: var(--accent);
  letter-spacing: 0.03em;
}
.content { padding: 1.75rem; flex: 1; }

/* ── Hamburger (mobile) ── */
.hamburger {
  display: none;
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: 8px;
  color: var(--text);
  padding: 0.4rem 0.6rem;
  cursor: pointer;
  font-size: 1.1rem;
  line-height: 1;
}
.sidebar-overlay {
  display: none;
  position: fixed; inset: 0;
  background: rgba(0,0,0,0.6);
  z-index: 99;
}

/* ── Cards & components ── */
.card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 1.5rem;
}
.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.25rem;
  gap: 1rem;
}
.card-title {
  font-family: 'Syne', sans-serif;
  font-weight: 700;
  font-size: 1rem;
}
.stat-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}
.stat-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 1.25rem;
}
.stat-label {
  font-size: 0.75rem;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: 0.06em;
  margin-bottom: 0.4rem;
}
.stat-value {
  font-family: 'Syne', sans-serif;
  font-size: 1.6rem;
  font-weight: 800;
  letter-spacing: -0.02em;
  color: var(--text);
}
.stat-value.green { color: var(--accent); }
.stat-sub { font-size: 0.75rem; color: var(--muted); margin-top: 0.2rem; }

/* ── Form elements ── */
.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}
.form-group { margin-bottom: 1rem; }
.form-group label {
  display: block;
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: 0.06em;
  margin-bottom: 0.4rem;
}
.form-control {
  width: 100%;
  padding: 0.7rem 0.9rem;
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: 8px;
  color: var(--text);
  font-family: inherit;
  font-size: 0.9rem;
  transition: border-color 0.15s;
  outline: none;
}
.form-control:focus { border-color: var(--accent); }
.form-control::placeholder { color: var(--muted); }
select.form-control { cursor: pointer; }
select.form-control option { background: var(--surface); }

/* ── Buttons ── */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.6rem 1.1rem;
  border-radius: 8px;
  font-family: inherit;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  border: none;
  transition: all 0.15s;
  text-decoration: none;
  white-space: nowrap;
}
.btn-primary { background: linear-gradient(135deg, var(--accent), var(--accent2)); color: #0a1a0f; font-weight: 700; }
.btn-primary:hover { opacity: 0.9; transform: translateY(-1px); }
.btn-secondary { background: var(--surface2); color: var(--text); border: 1px solid var(--border); }
.btn-secondary:hover { border-color: var(--accent); color: var(--accent); }
.btn-danger { background: rgba(248,113,113,0.1); color: var(--danger); border: 1px solid rgba(248,113,113,0.2); }
.btn-danger:hover { background: rgba(248,113,113,0.2); }
.btn-sm { padding: 0.35rem 0.7rem; font-size: 0.8rem; }
.btn-icon { padding: 0.4rem; width: 30px; height: 30px; justify-content: center; }

/* ── Table ── */
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th {
  text-align: left;
  font-size: 0.72rem;
  font-weight: 600;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: 0.07em;
  padding: 0.65rem 0.9rem;
  border-bottom: 1px solid var(--border);
}
td {
  padding: 0.75rem 0.9rem;
  font-size: 0.875rem;
  border-bottom: 1px solid rgba(42,45,62,0.5);
  vertical-align: middle;
}
tr:last-child td { border-bottom: none; }
tr:hover td { background: rgba(255,255,255,0.02); }
.td-name { font-weight: 500; }

/* ── Chips / Tags ── */
.chip {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.2rem 0.55rem;
  border-radius: 20px;
  font-size: 0.72rem;
  font-weight: 600;
  letter-spacing: 0.03em;
}
.chip-green { background: rgba(74,222,128,0.12); color: var(--accent); }
.chip-red   { background: rgba(248,113,113,0.12); color: var(--danger); }
.chip-blue  { background: rgba(96,165,250,0.12); color: var(--info); }

/* ── Alerts ── */
.alert {
  padding: 0.75rem 1rem;
  border-radius: 8px;
  font-size: 0.875rem;
  margin-bottom: 1.25rem;
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
}
.alert-success { background: rgba(74,222,128,0.1); border: 1px solid rgba(74,222,128,0.25); color: var(--accent); }
.alert-error   { background: rgba(248,113,113,0.1); border: 1px solid rgba(248,113,113,0.25); color: var(--danger); }

/* ── Page grid ── */
.page-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
.page-grid .full { grid-column: 1 / -1; }

/* ── Search box ── */
.search-wrap { position: relative; }
.search-wrap input { padding-left: 2.25rem; }
.search-icon {
  position: absolute;
  left: 0.75rem; top: 50%;
  transform: translateY(-50%);
  color: var(--muted);
  font-size: 0.9rem;
  pointer-events: none;
}

/* ── Balance ── */
.balance-pill {
  font-family: 'Syne', sans-serif;
  font-weight: 700;
  font-size: 0.95rem;
  color: var(--accent);
}

/* ── Empty state ── */
.empty-state {
  text-align: center;
  padding: 3rem 1rem;
  color: var(--muted);
}
.empty-icon { font-size: 2.5rem; margin-bottom: 0.75rem; }

/* ── Responsive ── */
@media (max-width: 768px) {
  .sidebar { transform: translateX(-100%); }
  .sidebar.open { transform: translateX(0); }
  .sidebar-overlay.open { display: block; }
  .main { margin-left: 0; }
  .hamburger { display: block; }
  .content { padding: 1rem; }
  .page-grid { grid-template-columns: 1fr; }
  .stat-grid { grid-template-columns: repeat(2, 1fr); }
  .form-grid { grid-template-columns: 1fr; }
}
@media (max-width: 480px) {
  .stat-grid { grid-template-columns: 1fr 1fr; }
  .topbar { padding: 1rem; }
}
</style>
</head>
<body>

<div class="sidebar-overlay" id="overlay" onclick="toggleSidebar()"></div>

<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon">💰</div>
    <div class="brand-text">PocketTrack<span>School Money Manager</span></div>
  </div>
  <nav class="sidebar-nav">
    <span class="nav-label">Main</span>
    <a href="dashboard.php" class="nav-link <?= ($activePage??'') === 'dashboard' ? 'active' : '' ?>">
      <span class="nav-icon">📊</span> Dashboard
    </a>
    <span class="nav-label">Students</span>
    <a href="students.php" class="nav-link <?= ($activePage??'') === 'students' ? 'active' : '' ?>">
      <span class="nav-icon">👥</span> Students
    </a>
    <a href="students.php?action=add" class="nav-link <?= ($activePage??'') === 'add-student' ? 'active' : '' ?>">
      <span class="nav-icon">➕</span> Add Student
    </a>
    <span class="nav-label">Transactions</span>
    <a href="deposit.php" class="nav-link <?= ($activePage??'') === 'deposit' ? 'active' : '' ?>">
      <span class="nav-icon">💵</span> Deposit
    </a>
    <a href="withdraw.php" class="nav-link <?= ($activePage??'') === 'withdraw' ? 'active' : '' ?>">
      <span class="nav-icon">💸</span> Withdraw
    </a>
    <a href="transactions.php" class="nav-link <?= ($activePage??'') === 'transactions' ? 'active' : '' ?>">
      <span class="nav-icon">📋</span> All Transactions
    </a>
  </nav>
  <div class="sidebar-footer">
    <div class="user-info">
      <div class="avatar">👤</div>
      <div>
        <div class="user-name"><?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?></div>
        <div class="user-role">Administrator</div>
      </div>
    </div>
    <a href="change_password.php" class="btn-logout" style="margin-bottom:.4rem">🔑 Change Password</a>
    <a href="logout.php" class="btn-logout">🚪 Sign Out</a>
  </div>
</aside>

<div class="main">
  <div class="topbar">
    <div style="display:flex;align-items:center;gap:0.75rem;">
      <button class="hamburger" onclick="toggleSidebar()">☰</button>
      <div class="topbar-title"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></div>
    </div>
    <div class="topbar-right">
      <span class="badge">✓ Online</span>
    </div>
  </div>
  <div class="content">

<script>
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('overlay').classList.toggle('open');
}
</script>
