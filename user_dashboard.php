<?php
// user_dashboard.php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

requireLogin();

$userId = $_SESSION['user_id'];
$today = date('Y-m-d');
$todayLog = null;
$historyLogs = [];

try {
    $pdo = getDBConnection();

    // Fetch today's record
    $todayStmt = $pdo->prepare("SELECT * FROM attendance_logs WHERE user_id = :user_id AND date_logged = :today LIMIT 1");
    $todayStmt->execute([':user_id' => $userId, ':today' => $today]);
    $todayLog = $todayStmt->fetch();

    // Fetch historical records
    $historyStmt = $pdo->prepare("
        SELECT l.*, s.title as schedule_title 
        FROM attendance_logs l
        LEFT JOIN schedules s ON l.schedule_id = s.id
        WHERE l.user_id = :user_id 
        ORDER BY l.date_logged DESC LIMIT 30
    ");
    $historyStmt->execute([':user_id' => $userId]);
    $historyLogs = $historyStmt->fetchAll();

} catch (Exception $e) {
    error_log("Dashboard Fetch Error: " . $e->getMessage());
}

include __DIR__ . '/views/header.php';
?>

<div class="row g-4 mb-4">
    <!-- User Info & Action Panel -->
    <div class="col-md-5">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body p-4 text-center">
                <div class="mb-3">
                    <span class="fs-1 text-primary"><i class="fa-solid fa-address-card"></i></span>
                </div>
                <h4 class="fw-bold mb-1"><?= htmlspecialchars($_SESSION['user_name']); ?></h4>
                <p class="text-muted small mb-3">ID: <?= htmlspecialchars($_SESSION['user_code']); ?> | <?= htmlspecialchars($_SESSION['user_email']); ?></p>
                
                <hr>

                <p class="text-muted mb-1 fw-semibold">Current Date & Time</p>
                <h5 class="fw-bold text-dark mb-4"><?= date('l, F j, Y'); ?></h5>

                <!-- Status Badge -->
                <div class="mb-4">
                    <?php if (!$todayLog): ?>
                        <span class="badge bg-secondary fs-6 px-3 py-2">Not Checked In</span>
                    <?php elseif ($todayLog['check_out_time'] === null): ?>
                        <span class="badge bg-success fs-6 px-3 py-2">Checked In at <?= date('h:i A', strtotime($todayLog['check_in_time'])); ?></span>
                    <?php else: ?>
                        <span class="badge bg-dark fs-6 px-3 py-2">Checked Out at <?= date('h:i A', strtotime($todayLog['check_out_time'])); ?></span>
                    <?php endif; ?>
                </div>

                <!-- Quick Action Buttons -->
                <div class="d-grid gap-2">
                    <?php if (!$todayLog): ?>
                        <form action="actions/checkin_action.php" method="POST">
                            <button type="submit" class="btn btn-success btn-lg w-100 fw-bold">
                                <i class="fa-solid fa-right-to-bracket me-2"></i> Check In Now
                            </button>
                        </form>
                    <?php elseif ($todayLog['check_out_time'] === null): ?>
                        <form action="actions/checkout_action.php" method="POST">
                            <button type="submit" class="btn btn-warning btn-lg w-100 fw-bold text-white">
                                <i class="fa-solid fa-right-from-bracket me-2"></i> Check Out Now
                            </button>
                        </form>
                    <?php else: ?>
                        <button class="btn btn-outline-secondary btn-lg w-100" disabled>
                            <i class="fa-solid fa-check-double me-2"></i> Attendance Complete for Today
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Log History -->
    <div class="col-md-7">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i> My Attendance History</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($historyLogs)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No attendance records logged yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($historyLogs as $log): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= date('M d, Y', strtotime($log['date_logged'])); ?></td>
                                        <td><?= date('h:i A', strtotime($log['check_in_time'])); ?></td>
                                        <td><?= $log['check_out_time'] ? date('h:i A', strtotime($log['check_out_time'])) : '<span class="text-muted">--:--</span>'; ?></td>
                                        <td>
                                            <?php if ($log['status'] === 'On-Time'): ?>
                                                <span class="badge bg-success">On-Time</span>
                                            <?php elseif ($log['status'] === 'Late'): ?>
                                                <span class="badge bg-warning text-dark">Late</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Incomplete</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/views/footer.php'; ?>