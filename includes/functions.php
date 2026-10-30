<?php
// Base query for placements with student, company and supervisor names
function placement_sql() {
    return "SELECT p.*, s.full_name AS student_name, s.reg_no, c.name AS company_name, v.full_name AS supervisor_name
            FROM placements p
            JOIN users s ON s.id = p.student_id
            JOIN companies c ON c.id = p.company_id
            LEFT JOIN users v ON v.id = p.supervisor_id";
}

// Weeks done / remaining / duration for a placement row
function placement_progress(array $p) {
    $start = new DateTime($p['start_date']);
    $end   = new DateTime($p['end_date']);
    $today = new DateTime('today');

    $totalDays  = $start->diff($end)->days + 1;
    $totalWeeks = (int)ceil($totalDays / 7);

    if ($today < $start) {
        $elapsed = 0;
        $state = 'Not started';
    } elseif ($today > $end) {
        $elapsed = $totalDays;
        $state = 'Completed';
    } else {
        $elapsed = $start->diff($today)->days + 1;
        $state = 'Ongoing';
    }
    $daysLeft = $totalDays - $elapsed;

    return [
        'total_days'   => $totalDays,
        'total_weeks'  => $totalWeeks,
        'elapsed_days' => $elapsed,
        'weeks_done'   => (int)floor($elapsed / 7),
        'weeks_left'   => (int)ceil($daysLeft / 7),
        'days_left'    => $daysLeft,
        'percent'      => (int)round($elapsed / $totalDays * 100),
        'current_week' => $elapsed > 0 ? min($totalWeeks, (int)floor(($elapsed - 1) / 7) + 1) : 0,
        'state'        => $state,
    ];
}

// Days logged, hours worked and pending logs for a placement
function attendance_stats($placementId) {
    $r = row("SELECT COUNT(*) AS days,
                     COALESCE(SUM(CASE WHEN time_out IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, time_in, time_out) ELSE 0 END), 0) AS mins,
                     COALESCE(SUM(status = 'pending' AND time_out IS NOT NULL), 0) AS pending,
                     COALESCE(SUM(status = 'approved'), 0) AS approved
              FROM attendance WHERE placement_id = ?", 'i', $placementId);
    return [
        'days'     => (int)$r['days'],
        'hours'    => round($r['mins'] / 60, 1),
        'pending'  => (int)$r['pending'],
        'approved' => (int)$r['approved'],
    ];
}

function progress_bar($pct) {
    $pct = max(0, min(100, (int)$pct));
    return '<div class="bar" title="' . $pct . '%"><span style="width:' . $pct . '%"></span></div>';
}

function state_badge($state) {
    $cls = ['Ongoing' => 'ok', 'Not started' => 'warn', 'Completed' => 'info'][$state] ?? '';
    return '<span class="badge ' . $cls . '">' . e($state) . '</span>';
}

function status_badge($s) {
    $cls = ['pending' => 'warn', 'approved' => 'ok', 'rejected' => 'bad'][$s] ?? '';
    return '<span class="badge ' . $cls . '">' . e(ucfirst($s)) . '</span>';
}

function stat_card($label, $value, $sub = '') {
    echo '<div class="stat"><div class="stat-label">' . e($label) . '</div><div class="stat-value">' . e($value) . '</div>';
    if ($sub !== '') {
        echo '<div class="stat-sub">' . e($sub) . '</div>';
    }
    echo '</div>';
}

function fmt_date($d) { return $d ? date('d M Y', strtotime($d)) : '—'; }
function fmt_time($d) { return $d ? date('H:i', strtotime($d)) : '—'; }
function hours_between($in, $out) {
    if (!$in || !$out) { return '—'; }
    return round((strtotime($out) - strtotime($in)) / 3600, 1) . ' h';
}

// Table of placements with progress. $manage adds Edit / Delete buttons.
function placement_table(array $list, $showStudent = true, $showSupervisor = true, $manage = false) {
    if (!$list) {
        echo '<p class="muted">No placements found.</p>';
        return;
    }
    echo '<div class="table-wrap"><table><thead><tr>';
    if ($showStudent)    { echo '<th>Student</th>'; }
    echo '<th>Company</th>';
    if ($showSupervisor) { echo '<th>Supervisor</th>'; }
    echo '<th>Period</th><th>Status</th><th style="min-width:130px">Progress</th><th>Weeks done</th><th>Weeks left</th><th>Days logged</th><th>Hours</th><th></th></tr></thead><tbody>';

    foreach ($list as $p) {
        $pr = placement_progress($p);
        $st = attendance_stats($p['id']);
        echo '<tr>';
        if ($showStudent) {
            echo '<td><strong>' . e($p['student_name']) . '</strong><br><span class="muted small">' . e($p['reg_no']) . '</span></td>';
        }
        echo '<td>' . e($p['company_name']) . '</td>';
        if ($showSupervisor) {
            echo '<td>' . ($p['supervisor_name'] ? e($p['supervisor_name']) : '<span class="muted">Not assigned</span>') . '</td>';
        }
        echo '<td class="nowrap">' . fmt_date($p['start_date']) . '<br>to ' . fmt_date($p['end_date']) . '<br><span class="muted small">' . $pr['total_weeks'] . ' weeks</span></td>';
        echo '<td>' . state_badge($pr['state']) . '</td>';
        echo '<td>' . progress_bar($pr['percent']) . '<span class="small muted">' . $pr['percent'] . '%</span></td>';
        echo '<td>' . $pr['weeks_done'] . ' / ' . $pr['total_weeks'] . '</td>';
        echo '<td>' . $pr['weeks_left'] . '</td>';
        echo '<td>' . $st['days'];
        if ($st['pending'] > 0) {
            echo ' <span class="badge warn">' . $st['pending'] . ' pending</span>';
        }
        echo '</td>';
        echo '<td>' . $st['hours'] . '</td>';
        echo '<td class="nowrap"><a class="btn small" href="placement.php?id=' . (int)$p['id'] . '">View</a>';
        if ($manage) {
            echo ' <a class="btn small secondary" href="placements.php?edit=' . (int)$p['id'] . '#form">Edit</a>';
            echo ' <form class="inline" method="post" onsubmit="return confirm(\'Delete this placement and all its attendance records?\')">'
               . csrf_field()
               . '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' . (int)$p['id'] . '">'
               . '<button class="btn small danger">Delete</button></form>';
        }
        echo '</td></tr>';
    }
    echo '</tbody></table></div>';
}
