<?php
// index.php
require_once __DIR__ . '/config/auth.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['user_role'] === 'admin') {
    header("Location: admin_dashboard.php");
} else {
    header("Location: user_dashboard.php");
}
exit();