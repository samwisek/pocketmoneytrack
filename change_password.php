<?php
// change_password.php — Admin password change utility
require_once __DIR__ . '/auth.php';
requireLogin();

$msg     = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $current  = $_POST['current_password']  ?? '';
    $new      = $_POST['new_password']       ?? '';
    $confirm  = $_POST['confirm_password']   ?? '';

    if ($current === '' || $new === '' || $confirm === '') {
        $msg = 'All fields are required.';
        $msgType = 'error';
    } elseif (strlen($new) < 8) {
        $msg = 'New password must be at least 8 characters.';
        $msgType = 'error';
    } elseif ($new !== $confirm) {
        $msg = 'New passwords do not match.';
        $msgType = 'error';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT password FROM admin WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($current, $admin['password'])) {
            $msg = 'Current password is incorrect.';
            $msgType = 'error';
        } else {
            $hash = password_hash($new, PASSWORD_BCRYPT);
            $db->prepare("UPDATE admin SET password = ? WHERE id = ?")
               ->execute([$hash, $_SESSION['admin_id']]);
            $msg = 'Password changed successfully!';
        }
    }
}

$pageTitle  = 'Change Password';
$activePage = '';
include 'layout_header.php';
?>

<?php if ($msg): ?>
  <div class="alert alert-<?= $msgType === 'error' ? 'error' : 'success' ?>">
    <?= $msgType === 'error' ? '⚠️' : '✅' ?> <?= htmlspecialchars($msg) ?>
  </div>
<?php endif; ?>

<div style="max-width: 480px;">
  <div class="card">
    <div class="card-header">
      <div class="card-title">🔑 Change Admin Password</div>
      <a href="dashboard.php" class="btn btn-secondary btn-sm">← Back</a>
    </div>

    <form method="POST" action="change_password.php">
      <?= csrfField() ?>

      <div class="form-group">
        <label for="current_password">Current Password</label>
        <input type="password" id="current_password" name="current_password"
               class="form-control" placeholder="Enter current password"
               autocomplete="current-password" required>
      </div>

      <div class="form-group">
        <label for="new_password">New Password</label>
        <input type="password" id="new_password" name="new_password"
               class="form-control" placeholder="At least 8 characters"
               autocomplete="new-password" required minlength="8"
               oninput="checkStrength(this.value)">
        <!-- Password strength bar -->
        <div style="margin-top: 6px; height: 4px; border-radius: 4px; background: var(--border); overflow: hidden;">
          <div id="strengthBar" style="height: 100%; width: 0%; border-radius: 4px; transition: width .3s, background .3s;"></div>
        </div>
        <div id="strengthLabel" style="font-size: 0.72rem; color: var(--muted); margin-top: 4px;"></div>
      </div>

      <div class="form-group">
        <label for="confirm_password">Confirm New Password</label>
        <input type="password" id="confirm_password" name="confirm_password"
               class="form-control" placeholder="Repeat new password"
               autocomplete="new-password" required minlength="8"
               oninput="checkMatch()">
        <div id="matchLabel" style="font-size: 0.72rem; margin-top: 4px;"></div>
      </div>

      <div style="display: flex; gap: .75rem; margin-top: .5rem;">
        <button type="submit" class="btn btn-primary">💾 Save New Password</button>
        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
      </div>
    </form>
  </div>

  <div style="margin-top: 1rem; background: rgba(74,222,128,0.05); border: 1px solid rgba(74,222,128,0.15);
              border-radius: var(--radius); padding: .9rem 1rem; font-size: .8rem; color: var(--muted);">
    <strong style="color: var(--accent);">Tips for a strong password:</strong><br>
    Use at least 12 characters, mix uppercase, lowercase, numbers and symbols.
    Avoid using your name or common words.
  </div>
</div>

<script>
function checkStrength(val) {
  const bar   = document.getElementById('strengthBar');
  const label = document.getElementById('strengthLabel');
  let score   = 0;
  if (val.length >= 8)  score++;
  if (val.length >= 12) score++;
  if (/[A-Z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;

  const levels = [
    { pct: '20%', color: '#f87171', text: 'Very weak'  },
    { pct: '40%', color: '#fb923c', text: 'Weak'       },
    { pct: '60%', color: '#fbbf24', text: 'Fair'       },
    { pct: '80%', color: '#a3e635', text: 'Good'       },
    { pct: '100%',color: '#4ade80', text: 'Strong 💪'  },
  ];
  const lvl = levels[Math.min(score, 4)];
  bar.style.width      = lvl.pct;
  bar.style.background = lvl.color;
  label.textContent    = val.length ? lvl.text : '';
  label.style.color    = lvl.color;
}

function checkMatch() {
  const newP  = document.getElementById('new_password').value;
  const conf  = document.getElementById('confirm_password').value;
  const label = document.getElementById('matchLabel');
  if (!conf) { label.textContent = ''; return; }
  if (newP === conf) {
    label.textContent = '✓ Passwords match';
    label.style.color = 'var(--accent)';
  } else {
    label.textContent = '✗ Passwords do not match';
    label.style.color = 'var(--danger)';
  }
}
</script>

<?php include 'layout_footer.php'; ?>
