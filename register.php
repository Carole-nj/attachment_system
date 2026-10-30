<?php
require 'includes/init.php';
if (current_user()) {
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['full_name'] ?? '');
    $reg   = trim($_POST['reg_no'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $pw    = $_POST['password'] ?? '';
    $pw2   = $_POST['password2'] ?? '';

    if ($name === '' || $reg === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('Please enter your name, registration number and a valid email.', 'error');
    } elseif (strlen($pw) < 6) {
        flash('Password must be at least 6 characters.', 'error');
    } elseif ($pw !== $pw2) {
        flash('The two passwords do not match.', 'error');
    } else {
        try {
            q("INSERT INTO users (full_name, email, password, role, reg_no, phone) VALUES (?, ?, ?, 'student', ?, ?)",
              'sssss', $name, $email, password_hash($pw, PASSWORD_DEFAULT), $reg, $phone !== '' ? $phone : null);
            flash('Account created. You can now log in.');
            redirect('login.php');
        } catch (mysqli_sql_exception $ex) {
            flash($ex->getCode() == 1062 ? 'That email is already registered.' : 'Could not create the account.', 'error');
        }
    }
    redirect('register.php');
}

$pageTitle = 'Student Registration';
require 'includes/header.php';
?>
<div class="card auth-box">
  <h1>Student registration</h1>
  <form method="post">
    <?= csrf_field() ?>
    <div class="field"><label>Full name</label><input type="text" name="full_name" required></div>
    <div class="field"><label>Registration number</label><input type="text" name="reg_no" required></div>
    <div class="field"><label>Email</label><input type="email" name="email" required></div>
    <div class="field"><label>Phone (optional)</label><input type="text" name="phone"></div>
    <div class="field"><label>Password</label><input type="password" name="password" minlength="6" required></div>
    <div class="field"><label>Confirm password</label><input type="password" name="password2" minlength="6" required></div>
    <button type="submit">Create account</button>
  </form>
  <p class="small muted" style="text-align:center;margin-top:16px">
    Already registered? <a href="login.php">Login</a>
  </p>
</div>
<?php require 'includes/footer.php'; ?>
