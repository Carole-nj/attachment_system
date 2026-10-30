<?php
require 'includes/init.php';
redirect(current_user() ? 'dashboard.php' : 'login.php');
