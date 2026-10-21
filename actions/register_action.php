<?php
// actions/register_action.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../register.php");
    exit();
}
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['flash_error'] = "Security check failed: Invalid CSRF token. Please refresh and try again.";
    header("Location: ../login.php");
    exit();
}

$user_code = sanitize($_POST['user_code'] ?? '');
$name = sanitize($_POST['name'] ?? '');
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? '';

if (empty($user_code) || empty($name) || !$email || strlen($password) < 6) {
    $_SESSION['flash_error'] = "Please check your inputs. Password must be at least 6 characters.";
    header("Location: ../register.php");
    exit();
}

try {
    $pdo = getDBConnection();

    // Check if user_code or email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email OR user_code = :user_code LIMIT 1");
    $stmt->execute([':email' => $email, ':user_code' => $user_code]);
    if ($stmt->fetch()) {
        $_SESSION['flash_error'] = "Email or Student ID is already registered.";
        header("Location: ../register.php");
        exit();
    }

    // Insert user
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $insertStmt = $pdo->prepare("INSERT INTO users (user_code, name, email, password, role) VALUES (:user_code, :name, :email, :password, 'user')");
    $insertStmt->execute([
        ':user_code' => $user_code,
        ':name'      => $name,
        ':email'     => $email,
        ':password'  => $hashedPassword
    ]);

    $_SESSION['flash_success'] = "Registration successful! You can now log in.";
    header("Location: ../login.php");
    exit();

} catch (Exception $e) {
    error_log("Register Exception: " . $e->getMessage());
    $_SESSION['flash_error'] = "Failed to register account. Please try again.";
    header("Location: ../register.php");
    exit();
}