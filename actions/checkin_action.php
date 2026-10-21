<?php
// actions/checkin_action.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../user_dashboard.php");
    exit();
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['flash_error'] = "Security check failed: Invalid CSRF token. Please refresh and try again.";
    header("Location: ../login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$today = date('Y-m-d');
$now = date('Y-m-d H:i:s');
$currentTimeOnly = date('H:i:s');

try {
    $pdo = getDBConnection();

    // Check if user already checked in today
    $checkStmt = $pdo->prepare("SELECT id FROM attendance_logs WHERE user_id = :user_id AND date_logged = :today LIMIT 1");
    $checkStmt->execute([':user_id' => $userId, ':today' => $today]);
    if ($checkStmt->fetch()) {
        $_SESSION['flash_error'] = "You have already checked in today!";
        header("Location: ../user_dashboard.php");
        exit();
    }

    // Find applicable schedule for today (defaults to schedule_id 1)
    $schedStmt = $pdo->prepare("SELECT id, start_time, grace_period_minutes FROM schedules ORDER BY id ASC LIMIT 1");
    $schedStmt->execute();
    $schedule = $schedStmt->fetch();

    $status = 'On-Time';
    $scheduleId = null;

    if ($schedule) {
        $scheduleId = $schedule['id'];
        $startTime = $schedule['start_time'];
        $graceMinutes = (int)$schedule['grace_period_minutes'];

        // Calculate late threshold = start_time + grace_period_minutes
        $startTimeDT = new DateTime($today . ' ' . $startTime);
        $lateThresholdDT = clone $startTimeDT;
        $lateThresholdDT->modify("+{$graceMinutes} minutes");

        $nowDT = new DateTime($now);

        if ($nowDT > $lateThresholdDT) {
            $status = 'Late';
        }
    }

    // Insert attendance log
    $insertStmt = $pdo->prepare("
        INSERT INTO attendance_logs (user_id, schedule_id, check_in_time, status, date_logged)
        VALUES (:user_id, :schedule_id, :check_in_time, :status, :date_logged)
    ");
    $insertStmt->execute([
        ':user_id'       => $userId,
        ':schedule_id'   => $scheduleId,
        ':check_in_time' => $now,
        ':status'        => $status,
        ':date_logged'   => $today
    ]);

    $_SESSION['flash_success'] = "Successfully checked in! Status: <strong>{$status}</strong>";
    header("Location: ../user_dashboard.php");
    exit();

} catch (Exception $e) {
    error_log("Check-in Error: " . $e->getMessage());
    $_SESSION['flash_error'] = "Failed to record check-in. Please try again.";
    header("Location: ../user_dashboard.php");
    exit();
}