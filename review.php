<?php
require 'includes/init.php';
$u = require_login(['supervisor']);
$show = (($_GET['show'] ?? 'pending') === 'all') ? 'all' : 'pending';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = (int)($_POST['id'] ?? 0);
    $decision = $_POST['decision'] ?? '';
    $comment  = trim($_POST['comment'] ?? '');

    $rec = row("SELECT a.id FROM attendance a JOIN placements p ON p.id = a.placement_id
                WHERE a.id = ? AND p.supervisor_id = ? AND a.time_out IS NOT NULL", 'ii', $id, $u['id']);
    if (!$rec) {
        flash('That log was not found or is not yours to review.', 'error');
    } elseif (!in_array($decision, ['approved', 'rejected'], true)) {
        flash('Invalid decision.', 'error');
    } elseif ($decision === 'rejected' && $comment === '') {
        flash('Please give a reason when rejecting a log.', 'error');
    } else {
        q("UPDATE attendance SET status = ?, supervisor_comment = ? WHERE id = ?",
          'ssi', $decision, $comment !== '' ? $comment : null, $id);
        flash('Log ' . $decision . '.');
    }
    redirect('review.php?show=' . $show);
}

$sql = "SELECT a.*, s.full_name AS student_name, s.reg_no
        FROM attendance a
        JOIN placements p ON p.id = a.placement_id
        JOIN users s ON s.id = p.student_id
        WHERE p.supervisor_id = ?";
if ($show === 'pending') {
    $sql .= " AND a.status = 'pending' AND a.time_out IS NOT NULL";
}
$sql .= " ORDER BY a.att_date DESC, s.full_name LIMIT 200";
$logs = rows($sql, 'i', $u['id']);

$pageTitle = 'Review Logs';
require 'includes/header.php';
?>
<div class="page-head">
  <h1>Review attendance logs</h1>
  <div>
    <a class="btn small <?= $show === 'pending' ? '' : 'secondary' ?>" href="review.php?show=pending">Pending</a>
    <a class="btn small <?= $show === 'all' ? '' : 'secondary' ?>" href="review.php?show=all">All logs</a>
  </div>
</div>

<div class="card">
<?php if (!$logs): ?>
  <p class="muted"><?= $show === 'pending' ? 'Nothing waiting for review. Great job!' : 'No attendance logs yet.' ?></p>
<?php else: ?>
  <div class="table-wrap"><table>
    <thead><tr><th>Student</th><th>Date</th><th>In</th><th>Out</th><th>Hours</th><th>Activity</th><th>Status</th><th style="min-width:230px">Review</th></tr></thead>
    <tbody>
    <?php foreach ($logs as $r): ?>
      <tr>
        <td><strong><?= e($r['student_name']) ?></strong><br><span class="muted small"><?= e($r['reg_no']) ?></span></td>
        <td class="nowrap"><?= fmt_date($r['att_date']) ?></td>
        <td><?= fmt_time($r['time_in']) ?></td>
        <td><?= $r['time_out'] ? fmt_time($r['time_out']) : '<span class="badge warn">Still in</span>' ?></td>
        <td><?= hours_between($r['time_in'], $r['time_out']) ?></td>
        <td><?= nl2br(e($r['activity'])) ?></td>
        <td><?= status_badge($r['status']) ?></td>
        <td>
          <?php if ($r['time_out'] && $r['status'] === 'pending'): ?>
            <form method="post" action="review.php?show=<?= e($show) ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <input type="text" name="comment" placeholder="Comment (required to reject)" maxlength="250">
              <div style="margin-top:6px;display:flex;gap:6px">
                <button class="small" name="decision" value="approved">Approve</button>
                <button class="small danger" name="decision" value="rejected">Reject</button>
              </div>
            </form>
          <?php else: ?>
            <span class="small muted"><?= e($r['supervisor_comment']) ?></span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
<?php endif; ?>
</div>
<?php require 'includes/footer.php'; ?>
