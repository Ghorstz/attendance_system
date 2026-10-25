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

// admin_dashboard.php (Top PHP Section)

// Filter parameters
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$search = sanitize($_GET['search'] ?? '');

// Pagination settings
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10; // Number of records per page
$offset = ($page - 1) * $limit;

try {
    $pdo = getDBConnection();

    // 1. Get total record count for pagination calculation
    $countSql = "
        SELECT COUNT(*) 
        FROM attendance_logs l
        JOIN users u ON l.user_id = u.id
        WHERE l.date_logged BETWEEN :start_date AND :end_date
    ";
    $countParams = [
        ':start_date' => $startDate,
        ':end_date'   => $endDate
    ];

    if (!empty($search)) {
        $countSql .= " AND (u.name LIKE :search OR u.user_code LIKE :search)";
        $countParams[':search'] = "%{$search}%";
    }

    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($countParams);
    $totalRecords = (int)$countStmt->fetchColumn();
    $totalPages = max(1, ceil($totalRecords / $limit));

    // 2. Fetch paginated records using LIMIT and OFFSET
    $sql = "
        SELECT 
            u.user_code,
            u.name as user_name,
            l.date_logged,
            l.check_in_time,
            l.check_out_time,
            s.title as schedule_title,
            l.status
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

    $sql .= " ORDER BY l.check_in_time DESC LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    
    // Bind integer parameters explicitly for LIMIT/OFFSET
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $logs = $stmt->fetchAll();

} catch (Exception $e) {
    error_log("Pagination Error: " . $e->getMessage());
    $logs = [];
    $totalRecords = 0;
    $totalPages = 1;
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
                <button type="button" class="btn btn-success ms-2" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fa-solid fa-user-plus me-1"></i> Add User
                </button>
                <!-- Print Report Button -->
                <button type="button" class="btn btn-outline-dark ms-2" onclick="window.print()">
                    <i class="fa-solid fa-print me-1"></i> Print Report
                </button>
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
            <!-- Pagination Controls -->
            <?php if ($totalPages > 1): ?>
            <nav aria-label="Logs navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <!-- Previous Page Link -->
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?start_date=<?= urlencode($startDate); ?>&end_date=<?= urlencode($endDate); ?>&search=<?= urlencode($search); ?>&page=<?= $page - 1; ?>">
                            <i class="fa-solid fa-chevron-left me-1"></i> Previous
                        </a>
                    </li>

                    <!-- Numeric Page Numbers -->
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= ($i === $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?start_date=<?= urlencode($startDate); ?>&end_date=<?= urlencode($endDate); ?>&search=<?= urlencode($search); ?>&page=<?= $i; ?>">
                                <?= $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <!-- Next Page Link -->
                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?start_date=<?= urlencode($startDate); ?>&end_date=<?= urlencode($endDate); ?>&search=<?= urlencode($search); ?>&page=<?= $page + 1; ?>">
                            Next <i class="fa-solid fa-chevron-right ms-1"></i>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>
<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold" id="addUserModalLabel"><i class="fa-solid fa-user-plus me-2"></i>Add New User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="actions/add_user_action.php" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">

                    <div class="mb-3">
                        <label for="modal_user_code" class="form-label font-weight-bold">User Code / ID</label>
                        <input type="text" name="user_code" id="modal_user_code" class="form-control" placeholder="e.g., 23-00775" required>
                    </div>

                    <div class="mb-3">
                        <label for="modal_name" class="form-label">Full Name</label>
                        <input type="text" name="name" id="modal_name" class="form-control" placeholder="e.g., Jane Doe" required>
                    </div>

                    <div class="mb-3">
                        <label for="modal_email" class="form-label">Email Address</label>
                        <input type="email" name="email" id="modal_email" class="form-control" placeholder="jane@example.com" required>
                    </div>

                    <div class="mb-3">
                        <label for="modal_password" class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" name="password" id="modal_password" class="form-control" placeholder="••••••••" minlength="6" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="modal_password">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="modal_role" class="form-label">Account Role</label>
                        <select name="role" id="modal_role" class="form-select">
                            <option value="user" selected>User (Student)</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold">Create Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/views/footer.php'; ?>