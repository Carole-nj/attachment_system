<?php
require 'includes/init.php';
$u = require_login(['student']);
$today = date('Y-m-d');

$pl = row(placement_sql() . " WHERE p.student_id = ? ORDER BY p.start_date DESC LIMIT 1", 'i', $u['id']);

if ($pl && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $rec = row("SELECT * FROM attendance WHERE placement_id = ? AND att_date = ?", 'is', $pl['id'], $today);

    if ($today < $pl['start_date'] || $today > $pl['end_date']) {
        flash('Your attachment period is not active today (' . fmt_date($pl['start_date']) . ' to ' . fmt_date($pl['end_date']) . ').', 'error');
    } elseif ($action === 'in') {
        if ($rec) {
            flash('You have already signed in today.', 'error');
        } else {
            q("INSERT INTO attendance (placement_id, att_date, time_in) VALUES (?, ?, ?)",
              'iss', $pl['id'], $today, date('Y-m-d H:i:s'));
            flash('Signed in at ' . date('H:i') . '. Have a productive day!');
        }
    } elseif ($action === 'out') {
        $activity = trim($_POST['activity'] ?? '');
        if (!$rec) {
            flash('You need to sign in first.', 'error');
        } elseif ($rec['time_out']) {
            flash('You have already signed out today.', 'error');
        } elseif (strlen($activity) < 5) {
            flash('Please describe what you did today before signing out.', 'error');
        } else {
            q("UPDATE attendance SET time_out = ?, activity = ? WHERE id = ?",
              'ssi', date('Y-m-d H:i:s'), $activity, $rec['id']);
            flash('Signed out at ' . date('H:i') . '. See you tomorrow!');
        }
    }
    redirect('attendance.php');
}

$pageTitle = 'Sign In / Out';
require 'includes/header.php';

if (!$pl): ?>
  <div class="card"><h2>No placement yet</h2><p>You have not been assigned to a placement. Please contact your coordinator.</p></div>
<?php else:
    $pr   = placement_progress($pl);
    $st   = attendance_stats($pl['id']);
    $rec  = row("SELECT * FROM attendance WHERE placement_id = ? AND att_date = ?", 'is', $pl['id'], $today);
    $log  = rows("SELECT * FROM attendance WHERE placement_id = ? ORDER BY att_date DESC", 'i', $pl['id']);
?>
  <div class="page-head"><h1>Sign In / Out</h1><?= state_badge($pr['state']) ?></div>

  <div class="card signpanel">
    <div class="clock" id="clock"><?= date('H:i:s') ?></div>
    <div class="date"><?= date('l, d F Y') ?> · <?= e($pl['company_name']) ?></div>

    <?php if ($pr['state'] !== 'Ongoing'): ?>
      <p class="muted">Your attachment period is <?= $pr['state'] === 'Completed' ? 'complete' : 'not active yet' ?>. Sign in is only available between <?= fmt_date($pl['start_date']) ?> and <?= fmt_date($pl['end_date']) ?>.</p>
    <?php elseif (!$rec): ?>
      <p>You have not signed in today.</p>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="in">
        <button class="btn big" type="submit">Sign In</button>
      </form>
    <?php elseif (!$rec['time_out']): ?>
      <p>Signed in at <strong><?= fmt_time($rec['time_in']) ?></strong>. Describe your work and sign out when you leave.</p>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="out">
        <textarea name="activity" placeholder="What did you work on today?" required></textarea>
        <button class="btn big danger" type="submit">Sign Out</button>
      </form>
    <?php else: ?>
      <p>Signed in <strong><?= fmt_time($rec['time_in']) ?></strong> · signed out <strong><?= fmt_time($rec['time_out']) ?></strong>
         (<?= hours_between($rec['time_in'], $rec['time_out']) ?>). Done for today.</p>
    <?php endif; ?>
  </div>

  <div class="stats">
    <?php
    stat_card('Weeks done', $pr['weeks_done'], 'of ' . $pr['total_weeks']);
    stat_card('Weeks remaining', $pr['weeks_left'], $pr['days_left'] . ' days left');
    stat_card('Days attended', $st['days']);
    stat_card('Hours logged', $st['hours']);
    ?>
  </div>
  <div class="card">
    <div class="bar big"><span style="width:<?= $pr['percent'] ?>%"></span></div>
    <p class="small muted" style="margin-bottom:0"><?= $pr['percent'] ?>% of your attachment is complete</p>
  </div>

  <div class="card">
    <h2>My attendance log</h2>
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
          <td><?= $r['time_out'] ? fmt_time($r['time_out']) : '<span class="badge warn">Not signed out</span>' ?></td>
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

  <script>
    (function () {
      var el = document.getElementById('clock');
      if (!el) return;
      setInterval(function () {
        var d = new Date(), p = function (n) { return (n < 10 ? '0' : '') + n; };
        el.textContent = p(d.getHours()) + ':' + p(d.getMinutes()) + ':' + p(d.getSeconds());
      }, 1000);
    })();
  </script>
<?php endif;

require 'includes/footer.php';
