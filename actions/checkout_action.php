<?php
// actions/checkout_action.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../user_dashboard.php");
    exit();
}

$userId = $_SESSION['user_id'];
$today = date('Y-m-d');
$now = date('Y-m-d H:i:s');

try {
    $pdo = getDBConnection();

    // Check if log exists and not checked out yet
    $stmt = $pdo->prepare("SELECT id, check_out_time FROM attendance_logs WHERE user_id = :user_id AND date_logged = :today LIMIT 1");
    $stmt->execute([':user_id' => $userId, ':today' => $today]);
    $log = $stmt->fetch();

    if (!$log) {
        $_SESSION['flash_error'] = "You need to check in before you can check out.";
        header("Location: ../user_dashboard.php");
        exit();
    }

    if ($log['check_out_time'] !== null) {
        $_SESSION['flash_error'] = "You have already checked out today.";
        header("Location: ../user_dashboard.php");
        exit();
    }

    // Update check_out_time
    $updateStmt = $pdo->prepare("UPDATE attendance_logs SET check_out_time = :check_out_time WHERE id = :id");
    $updateStmt->execute([':check_out_time' => $now, ':id' => $log['id']]);

    $_SESSION['flash_success'] = "Successfully checked out at " . date('h:i A');
    header("Location: ../user_dashboard.php");
    exit();

} catch (Exception $e) {
    error_log("Check-out Error: " . $e->getMessage());
    $_SESSION['flash_error'] = "Failed to record check-out. Please try again.";
    header("Location: ../user_dashboard.php");
    exit();
}