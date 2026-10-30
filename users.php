<?php
require 'includes/init.php';
$u = require_login(['admin']);
$roles = ['student', 'supervisor', 'coordinator', 'admin'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    try {
        if ($action === 'add') {
            $name  = trim($_POST['full_name'] ?? '');
            $email = strtolower(trim($_POST['email'] ?? ''));
            $role  = $_POST['role'] ?? '';
            $reg   = trim($_POST['reg_no'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $pw    = $_POST['password'] ?? '';
            if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($role, $roles, true) || strlen($pw) < 6) {
                flash('Fill in the name, a valid email, a role and a password of at least 6 characters.', 'error');
            } else {
                q("INSERT INTO users (full_name, email, password, role, reg_no, phone) VALUES (?, ?, ?, ?, ?, ?)", 'ssssss',
                  $name, $email, password_hash($pw, PASSWORD_DEFAULT), $role, $reg !== '' ? $reg : null, $phone !== '' ? $phone : null);
                flash("User $name added.");
            }
        } elseif ($action === 'toggle' && $id !== (int)$u['id']) {
            q("UPDATE users SET status = IF(status = 'active', 'disabled', 'active') WHERE id = ?", 'i', $id);
            flash('User status changed.');
        } elseif ($action === 'reset') {
            $pw = $_POST['password'] ?? '';
            if (strlen($pw) < 6) {
                flash('The new password must be at least 6 characters.', 'error');
            } else {
                q("UPDATE users SET password = ? WHERE id = ?", 'si', password_hash($pw, PASSWORD_DEFAULT), $id);
                flash('Password reset.');
            }
        } elseif ($action === 'delete' && $id !== (int)$u['id']) {
            q("DELETE FROM users WHERE id = ?", 'i', $id);
            flash('User deleted.');
        } else {
            flash('You cannot do that to your own account.', 'error');
        }
    } catch (mysqli_sql_exception $ex) {
        flash($ex->getCode() == 1062 ? 'That email is already registered.' : 'Database error: ' . $ex->getMessage(), 'error');
    }
    redirect('users.php' . (isset($_GET['role']) ? '?role=' . urlencode($_GET['role']) : ''));
}

$filter = in_array($_GET['role'] ?? '', $roles, true) ? $_GET['role'] : '';
$list = $filter
    ? rows("SELECT * FROM users WHERE role = ? ORDER BY full_name", 's', $filter)
    : rows("SELECT * FROM users ORDER BY FIELD(role, 'admin', 'coordinator', 'supervisor', 'student'), full_name");

$pageTitle = 'Users';
require 'includes/header.php';
?>
<div class="page-head"><h1>Users</h1></div>

<div class="card">
  <h2>Add a user</h2>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add">
    <div class="form-grid">
      <div><label>Full name</label><input type="text" name="full_name" required></div>
      <div><label>Email</label><input type="email" name="email" required></div>
      <div>
        <label>Role</label>
        <select name="role" required>
          <?php foreach ($roles as $r): ?><option value="<?= e($r) ?>"><?= e(ucfirst($r)) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div><label>Reg. number (students)</label><input type="text" name="reg_no"></div>
      <div><label>Phone</label><input type="text" name="phone"></div>
      <div><label>Password</label><input type="password" name="password" minlength="6" required></div>
    </div>
    <div class="form-actions"><button type="submit">Add user</button></div>
  </form>
</div>

<div class="card">
  <div class="page-head" style="margin-bottom:8px">
    <h2 style="margin:0">All users (<?= count($list) ?>)</h2>
    <div>
      <a class="btn small <?= $filter === '' ? '' : 'secondary' ?>" href="users.php">All</a>
      <?php foreach ($roles as $r): ?>
        <a class="btn small <?= $filter === $r ? '' : 'secondary' ?>" href="users.php?role=<?= e($r) ?>"><?= e(ucfirst($r)) ?>s</a>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="table-wrap"><table>
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Reg. no.</th><th>Status</th><th>Reset password</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($list as $r): $self = (int)$r['id'] === (int)$u['id']; ?>
      <tr>
        <td><strong><?= e($r['full_name']) ?></strong><?= $self ? ' <span class="muted small">(you)</span>' : '' ?></td>
        <td><?= e($r['email']) ?></td>
        <td><span class="badge info"><?= e(ucfirst($r['role'])) ?></span></td>
        <td><?= e($r['reg_no']) ?></td>
        <td><?= $r['status'] === 'active' ? '<span class="badge ok">Active</span>' : '<span class="badge bad">Disabled</span>' ?></td>
        <td>
          <form class="inline" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="reset">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <input type="password" name="password" placeholder="New password" minlength="6" required>
            <button class="small secondary" style="background:#64748b">Set</button>
          </form>
        </td>
        <td class="nowrap">
          <?php if (!$self): ?>
          <form class="inline" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="toggle">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button class="small secondary" style="background:#64748b"><?= $r['status'] === 'active' ? 'Disable' : 'Enable' ?></button>
          </form>
          <form class="inline" method="post" onsubmit="return confirm('Delete this user and all of their records?')">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button class="small danger">Delete</button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<?php require 'includes/footer.php'; ?>
