<?php
// actions/add_user_action.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../admin_dashboard.php");
    exit();
}

// Verify CSRF Token
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['flash_error'] = "Security check failed: Invalid CSRF token.";
    header("Location: ../admin_dashboard.php");
    exit();
}

$userCode = sanitize($_POST['user_code'] ?? '');
$name     = sanitize($_POST['name'] ?? '');
$email    = sanitize($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role     = sanitize($_POST['role'] ?? 'user');

if (empty($userCode) || empty($name) || empty($email) || empty($password)) {
    $_SESSION['flash_error'] = "All fields are required.";
    header("Location: ../admin_dashboard.php");
    exit();
}

if (!in_array($role, ['admin', 'user'])) {
    $role = 'user';
}

try {
    $pdo = getDBConnection();

    // Check if email or user code already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email OR user_code = :user_code");
    $stmt->execute([':email' => $email, ':user_code' => $userCode]);
    
    if ($stmt->fetch()) {
        $_SESSION['flash_error'] = "User Code or Email Address is already registered.";
        header("Location: ../admin_dashboard.php");
        exit();
    }

    // Insert new user
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("
        INSERT INTO users (user_code, name, email, password, role) 
        VALUES (:user_code, :name, :email, :password, :role)
    ");
    
    $stmt->execute([
        ':user_code' => $userCode,
        ':name'      => $name,
        ':email'     => $email,
        ':password'  => $hashedPassword,
        ':role'      => $role
    ]);

    $_SESSION['flash_success'] = "User account '{$name}' successfully created!";
    header("Location: ../admin_dashboard.php");
    exit();

} catch (Exception $e) {
    error_log("Add User Error: " . $e->getMessage());
    $_SESSION['flash_error'] = "Failed to create new user account.";
    header("Location: ../admin_dashboard.php");
    exit();
}