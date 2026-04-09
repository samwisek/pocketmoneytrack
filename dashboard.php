<?php
// dashboard.php
require_once __DIR__ . '/auth.php';
requireLogin();

$db = getDB();

// Stats
$totalStudents = $db->query("SELECT COUNT(*) FROM students")->fetchColumn();
$totalBalance  = $db->query("SELECT COALESCE(SUM(balance),0) FROM students")->fetchColumn();
$totalDeposits = $db->query("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE type='deposit'")->fetchColumn();
$totalWithdraw = $db->query("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE type='withdraw'")->fetchColumn();

// Recent 10 transactions
$recent = $db->query("
    SELECT t.*, s.name as student_name, s.student_uid
    FROM transactions t
    JOIN students s ON s.id = t.student_id
    ORDER BY t.created_at DESC
    LIMIT 10
")->fetchAll();

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
include 'layout_header.php';
?>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-label">👥 Total Students</div>
    <div class="stat-value"><?= number_format($totalStudents) ?></div>
    <div class="stat-sub">Registered in system</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">💰 Total Balance</div>
    <div class="stat-value green"><?= number_format($totalBalance, 0) ?></div>
    <div class="stat-sub">UGX across all students</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">📥 Total Deposits</div>
    <div class="stat-value"><?= number_format($totalDeposits, 0) ?></div>
    <div class="stat-sub">UGX all time</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">📤 Total Withdrawals</div>
    <div class="stat-value"><?= number_format($totalWithdraw, 0) ?></div>
    <div class="stat-sub">UGX all time</div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title">Recent Transactions</div>
    <a href="transactions.php" class="btn btn-secondary btn-sm">View All</a>
  </div>
  <?php if (empty($recent)): ?>
    <div class="empty-state">
      <div class="empty-icon">📋</div>
      <p>No transactions recorded yet.</p>
    </div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Student</th>
          <th>Type</th>
          <th>Amount</th>
          <th>Balance After</th>
          <th>Note</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($recent as $t): ?>
        <tr>
          <td>
            <div class="td-name"><?= htmlspecialchars($t['student_name']) ?></div>
            <div style="font-size:0.75rem;color:var(--muted)"><?= htmlspecialchars($t['student_uid']) ?></div>
          </td>
          <td>
            <?php if ($t['type'] === 'deposit'): ?>
              <span class="chip chip-green">↑ Deposit</span>
            <?php else: ?>
              <span class="chip chip-red">↓ Withdraw</span>
            <?php endif; ?>
          </td>
          <td style="font-weight:600">
            <?= $t['type'] === 'deposit' ? '+' : '-' ?>UGX <?= number_format($t['amount'], 0) ?>
          </td>
          <td class="balance-pill">UGX <?= number_format($t['balance_after'], 0) ?></td>
          <td style="color:var(--muted);font-size:0.8rem"><?= htmlspecialchars($t['note'] ?: '—') ?></td>
          <td style="color:var(--muted);font-size:0.8rem"><?= date('d M Y, H:i', strtotime($t['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php include 'layout_footer.php'; ?>
