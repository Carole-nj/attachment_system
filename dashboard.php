<?php
require 'includes/init.php';
$u = require_login();
$pageTitle = 'Dashboard';
require 'includes/header.php';
$today = date('Y-m-d');

/* ======================= STUDENT ======================= */
if ($u['role'] === 'student'):
    $pl = row(placement_sql() . " WHERE p.student_id = ? ORDER BY p.start_date DESC LIMIT 1", 'i', $u['id']);
    if (!$pl): ?>
        <div class="card">
          <h2>Welcome, <?= e($u['name']) ?></h2>
          <p>You have not been assigned to a placement yet. Please contact your attachment coordinator.</p>
        </div>
    <?php else:
        $pr  = placement_progress($pl);
        $st  = attendance_stats($pl['id']);
        $rec = row("SELECT * FROM attendance WHERE placement_id = ? AND att_date = ?", 'is', $pl['id'], $today);
    ?>
        <div class="page-head">
          <h1>Welcome, <?= e($u['name']) ?></h1>
          <?= state_badge($pr['state']) ?>
        </div>

        <div class="stats">
          <?php
          stat_card('Weeks done', $pr['weeks_done'], 'of ' . $pr['total_weeks'] . ' weeks');
          stat_card('Weeks remaining', $pr['weeks_left'], $pr['days_left'] . ' days left');
          stat_card('Total duration', $pr['total_weeks'] . ' wks', $pr['total_days'] . ' days');
          stat_card('Days attended', $st['days'], $st['approved'] . ' approved');
          stat_card('Hours logged', $st['hours']);
          ?>
        </div>

        <div class="card">
          <h2>Attachment progress</h2>
          <div class="bar big"><span style="width:<?= $pr['percent'] ?>%"></span></div>
          <p class="small muted"><?= $pr['percent'] ?>% complete
            <?php if ($pr['current_week'] > 0): ?> · currently in week <?= $pr['current_week'] ?> of <?= $pr['total_weeks'] ?><?php endif; ?></p>
        </div>

        <div class="card">
          <h2>Today</h2>
          <p>
            <?php if (!$rec): ?>
              You have <strong>not signed in</strong> today.
            <?php elseif (!$rec['time_out']): ?>
              Signed in at <strong><?= fmt_time($rec['time_in']) ?></strong>. Remember to sign out when you leave.
            <?php else: ?>
              Signed in <strong><?= fmt_time($rec['time_in']) ?></strong> and signed out <strong><?= fmt_time($rec['time_out']) ?></strong>.
            <?php endif; ?>
          </p>
          <a class="btn" href="attendance.php">Go to Sign In / Out</a>
        </div>

        <div class="card">
          <h2>Placement details</h2>
          <dl class="details">
            <dt>Company</dt><dd><?= e($pl['company_name']) ?></dd>
            <dt>Supervisor</dt><dd><?= $pl['supervisor_name'] ? e($pl['supervisor_name']) : 'Not assigned yet' ?></dd>
            <dt>Start date</dt><dd><?= fmt_date($pl['start_date']) ?></dd>
            <dt>End date</dt><dd><?= fmt_date($pl['end_date']) ?></dd>
            <dt>Registration no.</dt><dd><?= e($pl['reg_no']) ?></dd>
          </dl>
        </div>
    <?php endif;

/* ======================= SUPERVISOR ======================= */
elseif ($u['role'] === 'supervisor'):
    $list = rows(placement_sql() . " WHERE p.supervisor_id = ? ORDER BY s.full_name", 'i', $u['id']);
    $pending = (int)row("SELECT COUNT(*) AS n FROM attendance a JOIN placements p ON p.id = a.placement_id
                         WHERE p.supervisor_id = ? AND a.status = 'pending' AND a.time_out IS NOT NULL", 'i', $u['id'])['n'];
    ?>
    <div class="page-head"><h1>Welcome, <?= e($u['name']) ?></h1></div>
    <div class="stats">
      <?php
      stat_card('Students assigned', count($list));
      stat_card('Logs awaiting review', $pending);
      ?>
    </div>
    <?php if ($pending > 0): ?>
      <div class="flash error" style="background:#fef3c7;border-color:#fcd34d;color:#78350f">
        You have <?= $pending ?> attendance log(s) waiting for your review. <a href="review.php">Review now</a>
      </div>
    <?php endif; ?>
    <div class="card">
      <h2>My students</h2>
      <?php placement_table($list, true, false); ?>
    </div>

<?php
/* ======================= COORDINATOR / ADMIN ======================= */
else:
    $c = row("SELECT
                (SELECT COUNT(*) FROM users WHERE role = 'student') AS students,
                (SELECT COUNT(*) FROM users WHERE role = 'supervisor') AS supervisors,
                (SELECT COUNT(*) FROM companies) AS companies,
                (SELECT COUNT(*) FROM placements WHERE ? BETWEEN start_date AND end_date) AS active", 's', $today);
    $unplaced = rows("SELECT id, full_name, reg_no FROM users
                      WHERE role = 'student' AND status = 'active' AND id NOT IN (SELECT student_id FROM placements)
                      ORDER BY full_name");
    $list = rows(placement_sql() . " ORDER BY p.end_date ASC, s.full_name LIMIT 50");
    ?>
    <div class="page-head">
      <h1>Welcome, <?= e($u['name']) ?></h1>
      <a class="btn" href="placements.php#form">+ New placement</a>
    </div>
    <div class="stats">
      <?php
      stat_card('Students', $c['students']);
      stat_card('Supervisors', $c['supervisors']);
      stat_card('Companies', $c['companies']);
      stat_card('Active placements', $c['active']);
      stat_card('Unplaced students', count($unplaced));
      ?>
    </div>

    <?php if ($unplaced): ?>
    <div class="card">
      <h2>Students without a placement</h2>
      <p class="small muted">
        <?php foreach ($unplaced as $s): ?>
          <span class="badge warn"><?= e($s['full_name']) ?> (<?= e($s['reg_no']) ?>)</span>
        <?php endforeach; ?>
      </p>
      <a class="btn small" href="placements.php#form">Assign placements</a>
    </div>
    <?php endif; ?>

    <div class="card">
      <h2>All placements</h2>
      <?php placement_table($list); ?>
    </div>
<?php endif;

require 'includes/footer.php';
