<?php
// students.php — List, Add, Search, View students
require_once __DIR__ . '/auth.php';
requireLogin();

$db = getDB();
$action  = $_GET['action'] ?? 'list';
$msg     = '';
$msgType = 'success';

// ── Handle Add ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add') {
    verifyCsrf();
    $name  = trim($_POST['name'] ?? '');
    $class = trim($_POST['class'] ?? '');
    $uid   = strtoupper(trim($_POST['student_uid'] ?? ''));

    if ($name === '' || $class === '' || $uid === '') {
        $msg = 'All fields are required.';
        $msgType = 'error';
    } elseif (!preg_match('/^[A-Z0-9\-]{3,20}$/', $uid)) {
        $msg = 'Student ID must be 3–20 alphanumeric characters (A-Z, 0-9, dash).';
        $msgType = 'error';
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO students (student_uid, name, class) VALUES (?, ?, ?)");
            $stmt->execute([$uid, $name, $class]);
            $msg = "Student {$name} added successfully!";
            $action = 'list';
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $msg = 'Student ID already exists. Use a unique ID.';
            } else {
                $msg = 'Error adding student. Please try again.';
            }
            $msgType = 'error';
        }
    }
}

// ── Handle Delete ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_student'])) {
    verifyCsrf();
    $sid = (int)($_POST['student_id'] ?? 0);
    if ($sid > 0) {
        $db->prepare("DELETE FROM students WHERE id = ?")->execute([$sid]);
        $msg = 'Student deleted.';
    }
    $action = 'list';
}

// ── Search & list ──
$search   = trim($_GET['q'] ?? '');
$students = [];
if ($action === 'list') {
    if ($search !== '') {
        $stmt = $db->prepare("SELECT * FROM students WHERE name LIKE ? OR student_uid LIKE ? ORDER BY name ASC");
        $stmt->execute(["%$search%", "%$search%"]);
    } else {
        $stmt = $db->query("SELECT * FROM students ORDER BY name ASC");
    }
    $students = $stmt->fetchAll();
}

// ── View single ──
$student = null;
$studentTxns = [];
if ($action === 'view' && isset($_GET['id'])) {
    $sid = (int)$_GET['id'];
    $student = $db->prepare("SELECT * FROM students WHERE id = ?");
    $student->execute([$sid]);
    $student = $student->fetch();
    if ($student) {
        $stmt = $db->prepare("SELECT * FROM transactions WHERE student_id = ? ORDER BY created_at DESC");
        $stmt->execute([$sid]);
        $studentTxns = $stmt->fetchAll();
    }
}

$pageTitle  = $action === 'add' ? 'Add Student' : ($action === 'view' ? 'Student Profile' : 'Students');
$activePage = $action === 'add' ? 'add-student' : 'students';
include 'layout_header.php';
?>

<?php if ($msg): ?>
  <div class="alert alert-<?= $msgType === 'error' ? 'error' : 'success' ?>">
    <?= $msgType === 'error' ? '⚠️' : '✅' ?> <?= htmlspecialchars($msg) ?>
  </div>
<?php endif; ?>

<?php if ($action === 'add'): ?>
<!-- ── ADD STUDENT FORM ── -->
<div class="card" style="max-width:560px">
  <div class="card-header">
    <div class="card-title">Add New Student</div>
    <a href="students.php" class="btn btn-secondary btn-sm">← Back to List</a>
  </div>
  <form method="POST" action="students.php?action=add">
    <?= csrfField() ?>
    <div class="form-grid">
      <div class="form-group">
        <label for="uid">Student ID *</label>
        <input type="text" id="uid" name="student_uid" class="form-control" placeholder="e.g. STU001" required maxlength="20" value="<?= sanitize($_POST['student_uid'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="sname">Full Name *</label>
        <input type="text" id="sname" name="name" class="form-control" placeholder="e.g. Alice Namukasa" required maxlength="100" value="<?= sanitize($_POST['name'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="sclass">Class *</label>
        <input type="text" id="sclass" name="class" class="form-control" placeholder="e.g. Senior 1A" required maxlength="50" value="<?= sanitize($_POST['class'] ?? '') ?>">
      </div>
    </div>
    <button type="submit" class="btn btn-primary">➕ Add Student</button>
  </form>
</div>

<?php elseif ($action === 'view' && $student): ?>
<!-- ── STUDENT PROFILE ── -->
<div class="page-grid">
  <div>
    <div class="card">
      <div class="card-header">
        <div class="card-title">Student Profile</div>
        <a href="students.php" class="btn btn-secondary btn-sm">← Back</a>
      </div>
      <div style="margin-bottom:1.5rem">
        <div style="font-size:2.5rem;margin-bottom:0.5rem">🎒</div>
        <div style="font-family:'Syne',sans-serif;font-size:1.3rem;font-weight:800"><?= htmlspecialchars($student['name']) ?></div>
        <div style="color:var(--muted);font-size:0.875rem;margin-top:0.25rem">
          <span class="chip chip-blue"><?= htmlspecialchars($student['student_uid']) ?></span>
          &nbsp;<?= htmlspecialchars($student['class']) ?>
        </div>
      </div>
      <div style="background:var(--bg);border-radius:10px;padding:1.25rem;border:1px solid var(--border)">
        <div style="font-size:0.75rem;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:.4rem">Current Balance</div>
        <div style="font-family:'Syne',sans-serif;font-size:2rem;font-weight:800;color:var(--accent)">UGX <?= number_format($student['balance'], 0) ?></div>
      </div>
      <div style="display:flex;gap:.75rem;margin-top:1.25rem;flex-wrap:wrap">
        <a href="deposit.php?student_id=<?= $student['id'] ?>" class="btn btn-primary btn-sm">💵 Deposit</a>
        <a href="withdraw.php?student_id=<?= $student['id'] ?>" class="btn btn-secondary btn-sm">💸 Withdraw</a>
      </div>
    </div>
  </div>

  <div>
    <div class="card">
      <div class="card-header">
        <div class="card-title">Transaction History</div>
        <span style="font-size:.8rem;color:var(--muted)"><?= count($studentTxns) ?> records</span>
      </div>
      <?php if (empty($studentTxns)): ?>
        <div class="empty-state"><div class="empty-icon">📋</div><p>No transactions yet.</p></div>
      <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Type</th><th>Amount</th><th>Balance After</th><th>Date</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($studentTxns as $t): ?>
            <tr>
              <td><?= $t['type']==='deposit' ? '<span class="chip chip-green">↑ Deposit</span>' : '<span class="chip chip-red">↓ Withdraw</span>' ?></td>
              <td style="font-weight:600"><?= $t['type']==='deposit' ? '+' : '-' ?>UGX <?= number_format($t['amount'],0) ?></td>
              <td class="balance-pill">UGX <?= number_format($t['balance_after'],0) ?></td>
              <td style="font-size:.8rem;color:var(--muted)"><?= date('d M Y', strtotime($t['created_at'])) ?></td>
              <td>
                <form method="POST" action="transactions.php" onsubmit="return confirm('Delete this transaction? This cannot be undone.')">
                  <?= csrfField() ?>
                  <input type="hidden" name="delete_txn" value="1">
                  <input type="hidden" name="txn_id" value="<?= $t['id'] ?>">
                  <input type="hidden" name="redirect" value="students.php?action=view&id=<?= $student['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-icon btn-sm" title="Delete">🗑</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php else: ?>
<!-- ── STUDENT LIST ── -->
<div class="card">
  <div class="card-header">
    <div class="card-title">All Students</div>
    <a href="students.php?action=add" class="btn btn-primary btn-sm">➕ Add Student</a>
  </div>

  <form method="GET" action="students.php" style="margin-bottom:1.25rem">
    <div class="search-wrap" style="max-width:320px">
      <span class="search-icon">🔍</span>
      <input type="text" name="q" class="form-control" placeholder="Search by name or ID…" value="<?= htmlspecialchars($search) ?>">
    </div>
  </form>

  <?php if (empty($students)): ?>
    <div class="empty-state">
      <div class="empty-icon">👥</div>
      <p><?= $search ? 'No students match your search.' : 'No students added yet.' ?></p>
    </div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>ID</th><th>Name</th><th>Class</th><th>Balance</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($students as $s): ?>
        <tr>
          <td><span class="chip chip-blue"><?= htmlspecialchars($s['student_uid']) ?></span></td>
          <td class="td-name"><?= htmlspecialchars($s['name']) ?></td>
          <td style="color:var(--muted)"><?= htmlspecialchars($s['class']) ?></td>
          <td class="balance-pill">UGX <?= number_format($s['balance'], 0) ?></td>
          <td>
            <div style="display:flex;gap:.5rem;flex-wrap:wrap">
              <a href="students.php?action=view&id=<?= $s['id'] ?>" class="btn btn-secondary btn-sm">👁 View</a>
              <a href="deposit.php?student_id=<?= $s['id'] ?>" class="btn btn-secondary btn-sm">💵</a>
              <a href="withdraw.php?student_id=<?= $s['id'] ?>" class="btn btn-secondary btn-sm">💸</a>
              <form method="POST" action="students.php" style="display:inline" onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($s['name'])) ?>? All transactions will be removed.')">
                <?= csrfField() ?>
                <input type="hidden" name="delete_student" value="1">
                <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">🗑</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php endif; ?>

<?php include 'layout_footer.php'; ?>
