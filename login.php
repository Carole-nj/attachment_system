<?php
require 'includes/init.php';
if (current_user()) {
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pw    = $_POST['password'] ?? '';
    $user  = row("SELECT * FROM users WHERE email = ?", 's', $email);

    if ($user && $user['status'] === 'active' && password_verify($pw, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'    => (int)$user['id'],
            'name'  => $user['full_name'],
            'email' => $user['email'],
            'role'  => $user['role'],
        ];
        redirect('dashboard.php');
    }
    flash('Invalid email or password, or the account is disabled.', 'error');
    redirect('login.php');
}

$pageTitle = 'Login';
require 'includes/header.php';
?>
<div class="card auth-box">
  <h1>Sign in</h1>
  <form method="post">
    <?= csrf_field() ?>
    <div class="field">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" required autofocus>
    </div>
    <div class="field">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required>
    </div>
    <button type="submit">Login</button>
  </form>
  <p class="small muted" style="text-align:center;margin-top:16px">
    Student without an account? <a href="register.php">Register here</a>
  </p>
</div>
<?php require 'includes/footer.php'; ?>
