<?php
// withdraw.php — Record a withdrawal
require_once __DIR__ . '/auth.php';
requireLogin();

$db = getDB();
$msg       = '';
$msgType   = 'success';
$preselect = (int)($_GET['student_id'] ?? 0);

// ── Handle withdrawal ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $studentId = (int)($_POST['student_id'] ?? 0);
    $amount    = trim($_POST['amount'] ?? '');
    $note      = trim($_POST['note'] ?? '');

    if ($studentId <= 0) {
        $msg = 'Please select a student.'; $msgType = 'error';
    } elseif (!is_numeric($amount) || (float)$amount <= 0) {
        $msg = 'Amount must be a positive number.'; $msgType = 'error';
    } else {
        $amount = round((float)$amount, 2);
        $stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
        $stmt->execute([$studentId]);
        $student = $stmt->fetch();

        if (!$student) {
            $msg = 'Student not found.'; $msgType = 'error';
        } elseif ($student['balance'] < $amount) {
            $msg = "Insufficient balance. {$student['name']} only has UGX " . number_format($student['balance'], 0) . ".";
            $msgType = 'error';
        } else {
            $newBalance = $student['balance'] - $amount;
            $db->beginTransaction();
            try {
                $db->prepare("UPDATE students SET balance = ? WHERE id = ?")->execute([$newBalance, $studentId]);
                $db->prepare("INSERT INTO transactions (student_id, type, amount, balance_after, note) VALUES (?,?,?,?,?)")
                   ->execute([$studentId, 'withdraw', $amount, $newBalance, $note ?: null]);
                $db->commit();
                $msg = "Withdrawn UGX " . number_format($amount, 0) . " from {$student['name']}. Remaining balance: UGX " . number_format($newBalance, 0);
                $preselect = 0;
            } catch (Exception $e) {
                $db->rollBack();
                $msg = 'Transaction failed. Please try again.'; $msgType = 'error';
            }
        }
    }
}

$students = $db->query("SELECT id, student_uid, name, class, balance FROM students ORDER BY name ASC")->fetchAll();

$pageTitle  = 'Record Withdrawal';
$activePage = 'withdraw';
include 'layout_header.php';
?>

<?php if ($msg): ?>
  <div class="alert alert-<?= $msgType === 'error' ? 'error' : 'success' ?>">
    <?= $msgType === 'error' ? '⚠️' : '✅' ?> <?= htmlspecialchars($msg) ?>
  </div>
<?php endif; ?>

<div class="page-grid">
  <div>
    <div class="card">
      <div class="card-header">
        <div class="card-title">💸 Record Withdrawal</div>
      </div>

      <?php if (empty($students)): ?>
        <div class="empty-state">
          <div class="empty-icon">👥</div>
          <p>No students yet. <a href="students.php?action=add" style="color:var(--accent)">Add a student</a> first.</p>
        </div>
      <?php else: ?>
      <form method="POST" action="withdraw.php" id="withdrawForm">
        <?= csrfField() ?>
        <div class="form-group">
          <label for="student_id">Select Student *</label>
          <select name="student_id" id="student_id" class="form-control" required onchange="updateStudentInfo(this)">
            <option value="">— Choose student —</option>
            <?php foreach ($students as $s): ?>
              <option value="<?= $s['id'] ?>"
                data-balance="<?= $s['balance'] ?>"
                data-name="<?= htmlspecialchars($s['name']) ?>"
                <?= ($preselect === $s['id'] || (int)($_POST['student_id']??0) === $s['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($s['student_uid'] . ' — ' . $s['name'] . ' (' . $s['class'] . ')') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div id="studentInfo" style="display:none;background:var(--bg);border-radius:10px;padding:1rem;border:1px solid var(--border);margin-bottom:1rem">
          <div style="font-size:.75rem;color:var(--muted);margin-bottom:.3rem">AVAILABLE BALANCE</div>
          <div id="currentBalance" style="font-family:'Syne',sans-serif;font-size:1.4rem;font-weight:800;color:var(--accent)"></div>
          <div id="balanceWarn" style="display:none;margin-top:.5rem;font-size:.8rem;color:var(--danger)">⚠️ Amount exceeds available balance</div>
        </div>

        <div class="form-group">
          <label for="amount">Amount (UGX) *</label>
          <input type="number" id="amount" name="amount" class="form-control" placeholder="e.g. 5000" min="1" step="1" required oninput="checkAmount(this)" value="<?= sanitize($_POST['amount'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="note">Note (optional)</label>
          <input type="text" id="note" name="note" class="form-control" placeholder="e.g. Tuck shop expenses" maxlength="255" value="<?= sanitize($_POST['note'] ?? '') ?>">
        </div>
        <div style="display:flex;gap:.75rem">
          <button type="submit" class="btn btn-primary" id="submitBtn">✅ Record Withdrawal</button>
          <a href="students.php" class="btn btn-secondary">Cancel</a>
        </div>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <div>
    <div class="card">
      <div class="card-header"><div class="card-title">Recent Withdrawals</div></div>
      <?php
        $recentWd = $db->query("
          SELECT t.*, s.name as sname, s.student_uid
          FROM transactions t JOIN students s ON s.id = t.student_id
          WHERE t.type = 'withdraw'
          ORDER BY t.created_at DESC LIMIT 8
        ")->fetchAll();
      ?>
      <?php if (empty($recentWd)): ?>
        <div class="empty-state"><div class="empty-icon">📋</div><p>No withdrawals yet.</p></div>
      <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Student</th><th>Amount</th><th>Date</th></tr></thead>
          <tbody>
            <?php foreach ($recentWd as $w): ?>
            <tr>
              <td>
                <div class="td-name"><?= htmlspecialchars($w['sname']) ?></div>
                <div style="font-size:.75rem;color:var(--muted)"><?= htmlspecialchars($w['student_uid']) ?></div>
              </td>
              <td style="color:var(--danger);font-weight:600">-UGX <?= number_format($w['amount'],0) ?></td>
              <td style="font-size:.8rem;color:var(--muted)"><?= date('d M Y', strtotime($w['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
let currentBalance = 0;
function updateStudentInfo(sel) {
  const opt = sel.options[sel.selectedIndex];
  const infoBox = document.getElementById('studentInfo');
  if (opt.value) {
    currentBalance = parseFloat(opt.dataset.balance);
    document.getElementById('currentBalance').textContent = 'UGX ' + currentBalance.toLocaleString();
    infoBox.style.display = 'block';
    checkAmount(document.getElementById('amount'));
  } else {
    infoBox.style.display = 'none';
    currentBalance = 0;
  }
}
function checkAmount(inp) {
  const val = parseFloat(inp.value);
  const warn = document.getElementById('balanceWarn');
  const btn  = document.getElementById('submitBtn');
  if (currentBalance > 0 && val > currentBalance) {
    warn.style.display = 'block';
    btn.disabled = true;
    btn.style.opacity = '0.5';
  } else {
    warn.style.display = 'none';
    btn.disabled = false;
    btn.style.opacity = '1';
  }
}
window.addEventListener('DOMContentLoaded', () => {
  const sel = document.getElementById('student_id');
  if (sel && sel.value) updateStudentInfo(sel);
});
</script>

<?php include 'layout_footer.php'; ?>
