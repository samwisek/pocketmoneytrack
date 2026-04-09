<?php
// transactions.php — All transactions with delete
require_once __DIR__ . '/auth.php';
requireLogin();

$db  = getDB();
$msg = '';

// ── Handle delete ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_txn'])) {
    verifyCsrf();
    $txnId = (int)($_POST['txn_id'] ?? 0);
    if ($txnId > 0) {
        // Rebuild balance: delete transaction and recalculate running balances
        // Simple approach: delete and note balance may be off; or recalculate
        $txn = $db->prepare("SELECT * FROM transactions WHERE id = ?");
        $txn->execute([$txnId]);
        $txn = $txn->fetch();
        if ($txn) {
            $db->beginTransaction();
            try {
                // Reverse the amount from student balance
                $reversal = $txn['type'] === 'deposit' ? -$txn['amount'] : +$txn['amount'];
                $db->prepare("UPDATE students SET balance = GREATEST(0, balance + ?) WHERE id = ?")
                   ->execute([$reversal, $txn['student_id']]);
                $db->prepare("DELETE FROM transactions WHERE id = ?")->execute([$txnId]);
                $db->commit();
                $msg = 'Transaction deleted and balance adjusted.';
            } catch (Exception $e) {
                $db->rollBack();
                $msg = 'Failed to delete transaction.';
            }
        }
    }
    // Redirect to referrer or self
    $redirect = $_POST['redirect'] ?? 'transactions.php';
    if (!preg_match('/^[a-zA-Z0-9_.?=&\-]+$/', $redirect)) $redirect = 'transactions.php';
    header("Location: $redirect" . ($msg ? '' : ''));
    exit;
}

// ── Filters ──
$filterType    = $_GET['type'] ?? '';
$filterStudent = (int)($_GET['student_id'] ?? 0);
$page          = max(1, (int)($_GET['p'] ?? 1));
$perPage       = 20;
$offset        = ($page - 1) * $perPage;

$where  = [];
$params = [];
if ($filterType === 'deposit' || $filterType === 'withdraw') {
    $where[]  = "t.type = ?";
    $params[] = $filterType;
}
if ($filterStudent > 0) {
    $where[]  = "t.student_id = ?";
    $params[] = $filterStudent;
}
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $db->prepare("SELECT COUNT(*) FROM transactions t $whereSQL");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

$stmt = $db->prepare("
    SELECT t.*, s.name as student_name, s.student_uid
    FROM transactions t
    JOIN students s ON s.id = t.student_id
    $whereSQL
    ORDER BY t.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$txns = $stmt->fetchAll();

$students = $db->query("SELECT id, student_uid, name FROM students ORDER BY name ASC")->fetchAll();

$pageTitle  = 'Transaction History';
$activePage = 'transactions';
include 'layout_header.php';
?>

<?php if ($msg): ?>
  <div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="card">
  <div class="card-header">
    <div class="card-title">All Transactions <span style="color:var(--muted);font-size:.85rem;font-weight:400">(<?= number_format($totalRows) ?> records)</span></div>
  </div>

  <!-- Filters -->
  <form method="GET" action="transactions.php" style="display:flex;gap:.75rem;flex-wrap:wrap;margin-bottom:1.25rem;align-items:flex-end">
    <div>
      <label style="display:block;font-size:.72rem;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:.3rem">Type</label>
      <select name="type" class="form-control" style="width:140px">
        <option value="" <?= $filterType==='' ? 'selected' : '' ?>>All Types</option>
        <option value="deposit" <?= $filterType==='deposit' ? 'selected' : '' ?>>Deposits</option>
        <option value="withdraw" <?= $filterType==='withdraw' ? 'selected' : '' ?>>Withdrawals</option>
      </select>
    </div>
    <div>
      <label style="display:block;font-size:.72rem;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:.3rem">Student</label>
      <select name="student_id" class="form-control" style="width:220px">
        <option value="">All Students</option>
        <?php foreach ($students as $s): ?>
          <option value="<?= $s['id'] ?>" <?= $filterStudent === $s['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($s['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="display:flex;gap:.5rem;align-items:center;margin-top:1.2rem">
      <button type="submit" class="btn btn-secondary btn-sm">🔍 Filter</button>
      <a href="transactions.php" class="btn btn-secondary btn-sm">✕ Clear</a>
    </div>
  </form>

  <?php if (empty($txns)): ?>
    <div class="empty-state"><div class="empty-icon">📋</div><p>No transactions found.</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Student</th>
          <th>Type</th>
          <th>Amount</th>
          <th>Balance After</th>
          <th>Note</th>
          <th>Date</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($txns as $t): ?>
        <tr>
          <td style="color:var(--muted);font-size:.8rem"><?= $t['id'] ?></td>
          <td>
            <div class="td-name">
              <a href="students.php?action=view&id=<?= $t['student_id'] ?>" style="color:var(--text);text-decoration:none">
                <?= htmlspecialchars($t['student_name']) ?>
              </a>
            </div>
            <div style="font-size:.75rem;color:var(--muted)"><?= htmlspecialchars($t['student_uid']) ?></div>
          </td>
          <td>
            <?php if ($t['type'] === 'deposit'): ?>
              <span class="chip chip-green">↑ Deposit</span>
            <?php else: ?>
              <span class="chip chip-red">↓ Withdraw</span>
            <?php endif; ?>
          </td>
          <td style="font-weight:600;<?= $t['type']==='deposit' ? 'color:var(--accent)' : 'color:var(--danger)' ?>">
            <?= $t['type'] === 'deposit' ? '+' : '-' ?>UGX <?= number_format($t['amount'], 0) ?>
          </td>
          <td class="balance-pill">UGX <?= number_format($t['balance_after'], 0) ?></td>
          <td style="color:var(--muted);font-size:.8rem;max-width:160px">
            <?= htmlspecialchars($t['note'] ?: '—') ?>
          </td>
          <td style="font-size:.8rem;color:var(--muted);white-space:nowrap"><?= date('d M Y, H:i', strtotime($t['created_at'])) ?></td>
          <td>
            <form method="POST" action="transactions.php" onsubmit="return confirm('Delete this transaction? The balance will be adjusted.')">
              <?= csrfField() ?>
              <input type="hidden" name="delete_txn" value="1">
              <input type="hidden" name="txn_id" value="<?= $t['id'] ?>">
              <button type="submit" class="btn btn-danger btn-icon btn-sm" title="Delete transaction">🗑</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
  <div style="display:flex;justify-content:center;gap:.5rem;margin-top:1.25rem;flex-wrap:wrap">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
      <a href="?type=<?= urlencode($filterType) ?>&student_id=<?= $filterStudent ?>&p=<?= $i ?>"
         class="btn btn-sm <?= $i === $page ? 'btn-primary' : 'btn-secondary' ?>">
        <?= $i ?>
      </a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>

  <?php endif; ?>
</div>

<?php include 'layout_footer.php'; ?>
