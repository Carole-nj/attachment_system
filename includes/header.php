<?php
$u = current_user();
$role = $u['role'] ?? '';
$cur = basename($_SERVER['PHP_SELF']);

$nav = [];
if ($role) { $nav['dashboard.php'] = 'Dashboard'; }
if ($role === 'student') { $nav['attendance.php'] = 'Sign In / Out'; }
if ($role === 'supervisor') { $nav['review.php'] = 'Review Logs'; }
if (in_array($role, ['coordinator', 'admin'], true)) {
    $nav['placements.php'] = 'Placements';
    $nav['companies.php'] = 'Companies';
}
if ($role === 'admin') { $nav['users.php'] = 'Users'; }
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? 'Welcome') ?> · <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<header class="topbar">
  <div class="wrap bar-inner">
    <a class="brand" href="dashboard.php"><?= e(APP_NAME) ?></a>
    <?php if ($u): ?>
      <nav>
        <?php foreach ($nav as $file => $label): ?>
          <a href="<?= e($file) ?>" class="<?= $cur === $file ? 'active' : '' ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
      </nav>
      <div class="user">
        <span><?= e($u['name']) ?> <em>(<?= e(ucfirst($u['role'])) ?>)</em></span>
        <a href="logout.php">Logout</a>
      </div>
    <?php endif; ?>
  </div>
</header>
<main class="wrap">
<?php show_flash(); ?>
