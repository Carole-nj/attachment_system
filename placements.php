<?php
require 'includes/init.php';
$u = require_login(['coordinator', 'admin']);

function valid_date($d) {
    $x = DateTime::createFromFormat('Y-m-d', $d);
    return $x && $x->format('Y-m-d') === $d;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'delete') {
            q("DELETE FROM placements WHERE id = ?", 'i', (int)($_POST['id'] ?? 0));
            flash('Placement deleted.');
            redirect('placements.php');
        }

        if ($action === 'save') {
            $id      = (int)($_POST['id'] ?? 0);
            $student = (int)($_POST['student_id'] ?? 0);
            $company = (int)($_POST['company_id'] ?? 0);
            $sup     = (int)($_POST['supervisor_id'] ?? 0) ?: null;
            $start   = $_POST['start_date'] ?? '';
            $end     = $_POST['end_date'] ?? '';

            if ($id) {
                $existing = row("SELECT student_id FROM placements WHERE id = ?", 'i', $id);
                $student = $existing ? (int)$existing['student_id'] : 0;
            }

            if (!$student || !$company || !valid_date($start) || !valid_date($end)) {
                flash('Please choose a student, a company and valid start and end dates.', 'error');
            } elseif ($end <= $start) {
                flash('The end date must be after the start date.', 'error');
            } elseif (!row("SELECT id FROM users WHERE id = ? AND role = 'student'", 'i', $student)) {
                flash('Invalid student selected.', 'error');
            } elseif ($sup && !row("SELECT id FROM users WHERE id = ? AND role = 'supervisor'", 'i', $sup)) {
                flash('Invalid supervisor selected.', 'error');
            } elseif (!$id && row("SELECT id FROM placements WHERE student_id = ?", 'i', $student)) {
                flash('That student already has a placement. Edit or delete it first.', 'error');
            } else {
                if ($id) {
                    q("UPDATE placements SET company_id = ?, supervisor_id = ?, start_date = ?, end_date = ? WHERE id = ?",
                      'iissi', $company, $sup, $start, $end, $id);
                    flash('Placement updated.');
                } else {
                    q("INSERT INTO placements (student_id, company_id, supervisor_id, start_date, end_date) VALUES (?, ?, ?, ?, ?)",
                      'iiiss', $student, $company, $sup, $start, $end);
                    flash('Placement created.');
                }
                redirect('placements.php');
            }
            redirect('placements.php' . ($id ? '?edit=' . $id : '') . '#form');
        }
    } catch (mysqli_sql_exception $ex) {
        flash('Database error: ' . $ex->getMessage(), 'error');
        redirect('placements.php');
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $edit = row(placement_sql() . " WHERE p.id = ?", 'i', (int)$_GET['edit']);
}

$students = $edit ? [] : rows("SELECT id, full_name, reg_no FROM users
                               WHERE role = 'student' AND status = 'active' AND id NOT IN (SELECT student_id FROM placements)
                               ORDER BY full_name");
$companies   = rows("SELECT id, name FROM companies ORDER BY name");
$supervisors = rows("SELECT id, full_name FROM users WHERE role = 'supervisor' AND status = 'active' ORDER BY full_name");
$list = rows(placement_sql() . " ORDER BY s.full_name");

$pageTitle = 'Placements';
require 'includes/header.php';
?>
<div class="page-head"><h1>Placements</h1></div>

<div class="card" id="form">
  <h2><?= $edit ? 'Edit placement – ' . e($edit['student_name']) : 'Assign a student to a company' ?></h2>
  <?php if (!$edit && !$students): ?>
    <p class="muted">Every active student already has a placement. New students appear here after they register (or after the admin adds them).</p>
  <?php elseif (!$companies): ?>
    <p class="muted">Add a company first on the <a href="companies.php">Companies</a> page.</p>
  <?php else: ?>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= $edit ? (int)$edit['id'] : 0 ?>">
    <div class="form-grid">
      <?php if (!$edit): ?>
      <div>
        <label>Student</label>
        <select name="student_id" required>
          <option value="">— choose —</option>
          <?php foreach ($students as $s): ?>
            <option value="<?= (int)$s['id'] ?>"><?= e($s['full_name']) ?> (<?= e($s['reg_no']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <div>
        <label>Company</label>
        <select name="company_id" required>
          <option value="">— choose —</option>
          <?php foreach ($companies as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= $edit && (int)$edit['company_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Industry supervisor</label>
        <select name="supervisor_id">
          <option value="0">— not assigned —</option>
          <?php foreach ($supervisors as $v): ?>
            <option value="<?= (int)$v['id'] ?>" <?= $edit && (int)$edit['supervisor_id'] === (int)$v['id'] ? 'selected' : '' ?>><?= e($v['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Start date</label>
        <input type="date" name="start_date" value="<?= e($edit['start_date'] ?? '') ?>" required>
      </div>
      <div>
        <label>End date</label>
        <input type="date" name="end_date" value="<?= e($edit['end_date'] ?? '') ?>" required>
      </div>
    </div>
    <div class="form-actions">
      <button type="submit"><?= $edit ? 'Save changes' : 'Create placement' ?></button>
      <?php if ($edit): ?><a class="btn secondary" href="placements.php">Cancel</a><?php endif; ?>
    </div>
  </form>
  <?php endif; ?>
</div>

<div class="card">
  <h2>All placements (<?= count($list) ?>)</h2>
  <?php placement_table($list, true, true, true); ?>
</div>
<?php require 'includes/footer.php'; ?>
