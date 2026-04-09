<?php
// deposit.php — Record a deposit
require_once __DIR__ . '/auth.php';
requireLogin();

$db = getDB();
$msg     = '';
$msgType = 'success';
$preselect = (int)($_GET['student_id'] ?? 0);

// ── Handle deposit ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $studentId = (int)($_POST['student_id'] ?? 0);
    $amount    = trim($_POST['amount'] ?? '');
    $note      = trim($_POST['note'] ?? '');

    // Validate
    if ($studentId <= 0) {
        $msg = 'Please select a student.'; $msgType = 'error';
    } elseif (!is_numeric($amount) || (float)$amount <= 0) {
        $msg = 'Amount must be a positive number.'; $msgType = 'error';
    } else {
        $amount = round((float)$amount, 2);
        // Fetch student
        $stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
        $stmt->execute([$studentId]);
        $student = $stmt->fetch();
        if (!$student) {
            $msg = 'Student not found.'; $msgType = 'error';
        } else {
            $newBalance = $student['balance'] + $amount;
            // Begin transaction
            $db->beginTransaction();
            try {
                $db->prepare("UPDATE students SET balance = ? WHERE id = ?")->execute([$newBalance, $studentId]);
                $db->prepare("INSERT INTO transactions (student_id, type, amount, balance_after, note) VALUES (?,?,?,?,?)")
                   ->execute([$studentId, 'deposit', $amount, $newBalance, $note ?: null]);
                $db->commit();
                $msg = "Deposited UGX " . number_format($amount, 0) . " for {$student['name']}. New balance: UGX " . number_format($newBalance, 0);
                $preselect = 0;
            } catch (Exception $e) {
                $db->rollBack();
                $msg = 'Transaction failed. Please try again.'; $msgType = 'error';
            }
        }
    }
}

// ── Load students for dropdown ──
$students = $db->query("SELECT id, student_uid, name, class, balance FROM students ORDER BY name ASC")->fetchAll();

$pageTitle  = 'Record Deposit';
$activePage = 'deposit';
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
        <div class="card-title">💵 Record Deposit</div>
      </div>

      <?php if (empty($students)): ?>
        <div class="empty-state">
          <div class="empty-icon">👥</div>
          <p>No students yet. <a href="students.php?action=add" style="color:var(--accent)">Add a student</a> first.</p>
        </div>
      <?php else: ?>
      <form method="POST" action="deposit.php" id="depositForm">
        <?= csrfField() ?>
        <div class="form-group">
          <label for="student_id">Select Student *</label>
          <select name="student_id" id="student_id" class="form-control" required onchange="updateStudentInfo(this)">
            <option value="">— Choose student —</option>
            <?php foreach ($students as $s): ?>
              <option value="<?= $s['id'] ?>"
                data-balance="<?= $s['balance'] ?>"
                data-name="<?= htmlspecialchars($s['name']) ?>"
                data-class="<?= htmlspecialchars($s['class']) ?>"
                <?= ($preselect === $s['id'] || (int)($_POST['student_id']??0) === $s['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($s['student_uid'] . ' — ' . $s['name'] . ' (' . $s['class'] . ')') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div id="studentInfo" style="display:none;background:var(--bg);border-radius:10px;padding:1rem;border:1px solid var(--border);margin-bottom:1rem">
          <div style="font-size:.75rem;color:var(--muted);margin-bottom:.3rem">CURRENT BALANCE</div>
          <div id="currentBalance" style="font-family:'Syne',sans-serif;font-size:1.4rem;font-weight:800;color:var(--accent)"></div>
        </div>

        <div class="form-group">
          <label for="amount">Amount (UGX) *</label>
          <input type="number" id="amount" name="amount" class="form-control" placeholder="e.g. 10000" min="1" step="1" required value="<?= sanitize($_POST['amount'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="note">Note (optional)</label>
          <input type="text" id="note" name="note" class="form-control" placeholder="e.g. From parent via MTN Money" maxlength="255" value="<?= sanitize($_POST['note'] ?? '') ?>">
        </div>
        <div style="display:flex;gap:.75rem">
          <button type="submit" class="btn btn-primary">✅ Record Deposit</button>
          <a href="students.php" class="btn btn-secondary">Cancel</a>
        </div>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <div>
    <div class="card">
      <div class="card-header"><div class="card-title">Recent Deposits</div></div>
      <?php
        $recentDeps = $db->query("
          SELECT t.*, s.name as sname, s.student_uid
          FROM transactions t JOIN students s ON s.id = t.student_id
          WHERE t.type = 'deposit'
          ORDER BY t.created_at DESC LIMIT 8
        ")->fetchAll();
      ?>
      <?php if (empty($recentDeps)): ?>
        <div class="empty-state"><div class="empty-icon">📋</div><p>No deposits yet.</p></div>
      <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Student</th><th>Amount</th><th>Date</th></tr></thead>
          <tbody>
            <?php foreach ($recentDeps as $d): ?>
            <tr>
              <td>
                <div class="td-name"><?= htmlspecialchars($d['sname']) ?></div>
                <div style="font-size:.75rem;color:var(--muted)"><?= htmlspecialchars($d['student_uid']) ?></div>
              </td>
              <td style="color:var(--accent);font-weight:600">+UGX <?= number_format($d['amount'],0) ?></td>
              <td style="font-size:.8rem;color:var(--muted)"><?= date('d M Y', strtotime($d['created_at'])) ?></td>
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
function updateStudentInfo(sel) {
  const opt = sel.options[sel.selectedIndex];
  const infoBox = document.getElementById('studentInfo');
  if (opt.value) {
    document.getElementById('currentBalance').textContent = 'UGX ' + Number(opt.dataset.balance).toLocaleString();
    infoBox.style.display = 'block';
  } else {
    infoBox.style.display = 'none';
  }
}
// Auto-run on load if preselected
window.addEventListener('DOMContentLoaded', () => {
  const sel = document.getElementById('student_id');
  if (sel && sel.value) updateStudentInfo(sel);
});
</script>

<?php include 'layout_footer.php'; ?>
