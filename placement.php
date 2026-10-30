<?php
require 'includes/init.php';
$u = require_login();

$id = (int)($_GET['id'] ?? 0);
$pl = row(placement_sql() . " WHERE p.id = ?", 'i', $id);
if (!$pl) {
    http_response_code(404);
    die('Placement not found.');
}

$allowed = in_array($u['role'], ['coordinator', 'admin'], true)
    || ($u['role'] === 'student' && (int)$pl['student_id'] === (int)$u['id'])
    || ($u['role'] === 'supervisor' && (int)$pl['supervisor_id'] === (int)$u['id']);
if (!$allowed) {
    http_response_code(403);
    die('Access denied.');
}

$pr  = placement_progress($pl);
$st  = attendance_stats($pl['id']);
$log = rows("SELECT * FROM attendance WHERE placement_id = ? ORDER BY att_date DESC", 'i', $pl['id']);

$pageTitle = 'Placement – ' . $pl['student_name'];
require 'includes/header.php';
?>
<div class="page-head">
  <h1><?= e($pl['student_name']) ?> <span class="muted small"><?= e($pl['reg_no']) ?></span></h1>
  <div class="no-print">
    <button type="button" class="secondary" style="background:#64748b" onclick="window.print()">Print</button>
    <a class="btn secondary" href="dashboard.php">Back</a>
  </div>
</div>

<div class="stats">
  <?php
  stat_card('Weeks done', $pr['weeks_done'], 'of ' . $pr['total_weeks'] . ' weeks');
  stat_card('Weeks remaining', $pr['weeks_left'], $pr['days_left'] . ' days left');
  stat_card('Days attended', $st['days'], $st['approved'] . ' approved');
  stat_card('Hours logged', $st['hours']);
  ?>
</div>

<div class="card">
  <div class="bar big"><span style="width:<?= $pr['percent'] ?>%"></span></div>
  <p class="small muted"><?= $pr['percent'] ?>% complete · <?= state_badge($pr['state']) ?></p>
  <dl class="details">
    <dt>Company</dt><dd><?= e($pl['company_name']) ?></dd>
    <dt>Supervisor</dt><dd><?= $pl['supervisor_name'] ? e($pl['supervisor_name']) : 'Not assigned' ?></dd>
    <dt>Period</dt><dd><?= fmt_date($pl['start_date']) ?> to <?= fmt_date($pl['end_date']) ?></dd>
    <dt>Duration</dt><dd><?= $pr['total_weeks'] ?> weeks (<?= $pr['total_days'] ?> days)</dd>
  </dl>
</div>

<div class="card">
  <h2>Attendance log</h2>
  <?php if (!$log): ?>
    <p class="muted">No attendance recorded yet.</p>
  <?php else: ?>
  <div class="table-wrap"><table>
    <thead><tr><th>Date</th><th>In</th><th>Out</th><th>Hours</th><th>Activity</th><th>Status</th><th>Supervisor comment</th></tr></thead>
    <tbody>
    <?php foreach ($log as $r): ?>
      <tr>
        <td class="nowrap"><?= fmt_date($r['att_date']) ?></td>
        <td><?= fmt_time($r['time_in']) ?></td>
        <td><?= $r['time_out'] ? fmt_time($r['time_out']) : '—' ?></td>
        <td><?= hours_between($r['time_in'], $r['time_out']) ?></td>
        <td><?= nl2br(e($r['activity'])) ?></td>
        <td><?= status_badge($r['status']) ?></td>
        <td><?= e($r['supervisor_comment']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</div>
<?php require 'includes/footer.php'; ?>
