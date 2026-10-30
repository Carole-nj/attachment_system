<?php
// ---- Database settings (XAMPP defaults) ----
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'attachment_db');

define('APP_NAME', 'Attachment Management System');
date_default_timezone_set('Africa/Nairobi');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
