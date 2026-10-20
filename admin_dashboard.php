<?php
// admin_dashboard.php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

requireAdmin();

$today = date('Y-m-d');
$startDate = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$endDate = $_GET['end_date'] ?? $today;
$search = sanitize($_GET['search'] ?? '');

$totalUsers = 0;
$todayPresent = 0;
$todayLate = 0;
$logs = [];
$schedules = [];

try {
    $pdo = getDBConnection();

    // 1. Stat Cards Queries
    $totalUsersStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'");
    $totalUsers = $totalUsersStmt->fetchColumn();

    $todayPresentStmt = $pdo->prepare("SELECT COUNT(*) FROM attendance_logs WHERE date_logged = :today");
    $todayPresentStmt->execute([':today' => $today]);
    $todayPresent = $todayPresentStmt->fetchColumn();

    $todayLateStmt = $pdo->prepare("SELECT COUNT(*) FROM attendance_logs WHERE date_logged = :today AND status = 'Late'");
    $todayLateStmt->execute([':today' => $today]);
    $todayLate = $todayLateStmt->fetchColumn();

    // 2. Attendance Logs Query with Filtering
    $sql = "
        SELECT l.*, u.name as user_name, u.user_code, u.email, s.title as schedule_title
        FROM attendance_logs l
        JOIN users u ON l.user_id = u.id
        LEFT JOIN schedules s ON l.schedule_id = s.id
        WHERE l.date_logged BETWEEN :start_date AND :end_date
    ";

    $params = [
        ':start_date' => $startDate,
        ':end_date'   => $endDate
    ];

    if (!empty($search)) {
        $sql .= " AND (u.name LIKE :search OR u.user_code LIKE :search)";
        $params[':search'] = "%{$search}%";
    }

    $sql .= " ORDER BY l.check_in_time DESC";

    $logStmt = $pdo->prepare($sql);
    $logStmt->execute($params);
    $logs = $logStmt->fetchAll();

    // 3. Active Schedules Query
    $schedStmt = $pdo->query("SELECT * FROM schedules ORDER BY id ASC");
    $schedules = $schedStmt->fetchAll();

} catch (Exception $e) {
    error_log("Admin Dashboard Error: " . $e->getMessage());
}

include __DIR__ . '/views/header.php';
?>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-primary text-white p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-uppercase fw-semibold mb-1 opacity-75">Total Registered Users</h6>
                    <h2 class="fw-bold mb-0"><?= $totalUsers; ?></h2>
                </div>
                <span class="fs-1 opacity-50"><i class="fa-solid fa-users"></i></span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-success text-white p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-uppercase fw-semibold mb-1 opacity-75">Today's Present</h6>
                    <h2 class="fw-bold mb-0"><?= $todayPresent; ?></h2>
                </div>
                <span class="fs-1 opacity-50"><i class="fa-solid fa-user-check"></i></span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-warning text-dark p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-uppercase fw-semibold mb-1 opacity-75">Today's Late Arrivals</h6>
                    <h2 class="fw-bold mb-0"><?= $todayLate; ?></h2>
                </div>
                <span class="fs-1 opacity-50"><i class="fa-solid fa-clock-triangle-exclamation"></i></span>
            </div>
        </div>
    </div>
</div>

<!-- Filters & Actions -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="start_date" class="form-label fw-semibold">Start Date</label>
                <input type="date" name="start_date" id="start_date" class="form-control" value="<?= htmlspecialchars($startDate); ?>">
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label fw-semibold">End Date</label>
                <input type="date" name="end_date" id="end_date" class="form-control" value="<?= htmlspecialchars($endDate); ?>">
            </div>
            <div class="col-md-3">
                <label for="search" class="form-label fw-semibold">Search User / ID</label>
                <input type="text" name="search" id="search" class="form-control" placeholder="Name or ID..." value="<?= htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                <a href="admin_dashboard.php" class="btn btn-outline-secondary"><i class="fa-solid fa-rotate-left"></i></a>
                <a href="export/export_csv.php?start_date=<?= urlencode($startDate); ?>&end_date=<?= urlencode($endDate); ?>&search=<?= urlencode($search); ?>" class="btn btn-success"><i class="fa-solid fa-file-csv me-1"></i> Export</a>
            </div>
        </form>
    </div>
</div>

<!-- Logs Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-3"><i class="fa-solid fa-list-check me-2 text-primary"></i> Attendance Activity Logs</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>User ID</th>
                        <th>Name</th>
                        <th>Date</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Schedule</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No logs found for the selected date range.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $row): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($row['user_code']); ?></td>
                                <td><?= htmlspecialchars($row['user_name']); ?></td>
                                <td><?= date('M d, Y', strtotime($row['date_logged'])); ?></td>
                                <td><?= date('h:i A', strtotime($row['check_in_time'])); ?></td>
                                <td><?= $row['check_out_time'] ? date('h:i A', strtotime($row['check_out_time'])) : '<span class="text-muted">--:--</span>'; ?></td>
                                <td><?= htmlspecialchars($row['schedule_title'] ?? 'Default'); ?></td>
                                <td>
                                    <?php if ($row['status'] === 'On-Time'): ?>
                                        <span class="badge bg-success">On-Time</span>
                                    <?php elseif ($row['status'] === 'Late'): ?>
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

<?php include __DIR__ . '/views/footer.php'; ?>