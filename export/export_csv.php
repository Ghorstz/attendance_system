<?php
// export/export_csv.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

// Enforce admin privileges
requireAdmin();

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$search = sanitize($_GET['search'] ?? '');

try {
    $pdo = getDBConnection();

    // Query logs based on date range and optional search filter
    $sql = "
        SELECT 
            u.user_code,
            u.name as user_name,
            u.email,
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

    $sql .= " ORDER BY l.check_in_time DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();

    // Generate filename based on date range
    $filename = "attendance_report_" . $startDate . "_to_" . $endDate . ".csv";

    // Set download headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    // Open PHP output stream
    $output = fopen('php://output', 'w');

    // Write CSV header row
    fputcsv($output, [
        'User Code / ID',
        'Full Name',
        'Email',
        'Date Logged',
        'Check In Time',
        'Check Out Time',
        'Schedule',
        'Status'
    ]);

    // Populate CSV rows
    foreach ($logs as $row) {
        fputcsv($output, [
            $row['user_code'],
            $row['user_name'],
            $row['email'],
            $row['date_logged'],
            date('h:i:s A', strtotime($row['check_in_time'])),
            $row['check_out_time'] ? date('h:i:s A', strtotime($row['check_out_time'])) : 'N/A',
            $row['schedule_title'] ?? 'Default',
            $row['status']
        ]);
    }

    fclose($output);
    exit();

} catch (Exception $e) {
    error_log("CSV Export Error: " . $e->getMessage());
    $_SESSION['flash_error'] = "Failed to export CSV report.";
    header("Location: ../admin_dashboard.php");
    exit();
}