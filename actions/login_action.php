<?php
// actions/login_action.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../login.php");
    exit();
}

$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? '';

if (!$email || empty($password)) {
    $_SESSION['flash_error'] = "Please fill in all required fields.";
    header("Location: ../login.php");
    exit();
}

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id, user_code, name, email, password, role FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Prevent session fixation
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_code'] = $user['user_code'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];

        $_SESSION['flash_success'] = "Welcome back, " . htmlspecialchars($user['name']) . "!";
        header("Location: ../index.php");
        exit();
    } else {
        $_SESSION['flash_error'] = "Invalid email address or password.";
        header("Location: ../login.php");
        exit();
    }
} catch (Exception $e) {
    error_log("Login Exception: " . $e->getMessage());
    $_SESSION['flash_error'] = "An unexpected error occurred. Please try again.";
    header("Location: ../login.php");
    exit();
}