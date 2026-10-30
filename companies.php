<?php
require 'includes/init.php';
$u = require_login(['coordinator', 'admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'add') {
            $name = trim($_POST['name'] ?? '');
            if ($name === '') {
                flash('Company name is required.', 'error');
            } else {
                q("INSERT INTO companies (name, location, contact_person, phone) VALUES (?, ?, ?, ?)", 'ssss',
                  $name, trim($_POST['location'] ?? ''), trim($_POST['contact_person'] ?? ''), trim($_POST['phone'] ?? ''));
                flash('Company added.');
            }
        } elseif ($action === 'delete') {
            q("DELETE FROM companies WHERE id = ?", 'i', (int)($_POST['id'] ?? 0));
            flash('Company deleted.');
        }
    } catch (mysqli_sql_exception $ex) {
        flash($ex->getCode() == 1451
            ? 'This company still has students placed with it. Delete or change those placements first.'
            : 'Database error: ' . $ex->getMessage(), 'error');
    }
    redirect('companies.php');
}

$list = rows("SELECT c.*, (SELECT COUNT(*) FROM placements p WHERE p.company_id = c.id) AS students
              FROM companies c ORDER BY c.name");

$pageTitle = 'Companies';
require 'includes/header.php';
?>
<div class="page-head"><h1>Companies</h1></div>

<div class="card">
  <h2>Add a company</h2>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add">
    <div class="form-grid">
      <div><label>Company name</label><input type="text" name="name" required></div>
      <div><label>Location</label><input type="text" name="location"></div>
      <div><label>Contact person</label><input type="text" name="contact_person"></div>
      <div><label>Phone</label><input type="text" name="phone"></div>
    </div>
    <div class="form-actions"><button type="submit">Add company</button></div>
  </form>
</div>

<div class="card">
  <h2>All companies (<?= count($list) ?>)</h2>
  <?php if (!$list): ?>
    <p class="muted">No companies yet.</p>
  <?php else: ?>
  <div class="table-wrap"><table>
    <thead><tr><th>Name</th><th>Location</th><th>Contact</th><th>Phone</th><th>Students</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($list as $c): ?>
      <tr>
        <td><strong><?= e($c['name']) ?></strong></td>
        <td><?= e($c['location']) ?></td>
        <td><?= e($c['contact_person']) ?></td>
        <td><?= e($c['phone']) ?></td>
        <td><?= (int)$c['students'] ?></td>
        <td>
          <form class="inline" method="post" onsubmit="return confirm('Delete this company?')">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
            <button class="small danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</div>
<?php require 'includes/footer.php'; ?>
