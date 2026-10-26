<?php
// actions/update_profile_action.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../profile.php");
    exit();
}

// Verify CSRF Token
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['flash_error'] = "Security check failed: Invalid CSRF token.";
    header("Location: ../profile.php");
    exit();
}

$userId = $_SESSION['user_id'];
$name   = sanitize($_POST['name'] ?? '');
$email  = sanitize($_POST['email'] ?? '');

if (empty($name) || empty($email)) {
    $_SESSION['flash_error'] = "Name and Email are required fields.";
    header("Location: ../profile.php");
    exit();
}

try {
    $pdo = getDBConnection();

    // Ensure email isn't taken by another user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
    $stmt->execute([':email' => $email, ':id' => $userId]);
    if ($stmt->fetch()) {
        $_SESSION['flash_error'] = "Email address is already in use by another account.";
        header("Location: ../profile.php");
        exit();
    }

    $avatarPath = null;

    // Handle File Upload
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['avatar']['tmp_name'];
        $fileName    = $_FILES['avatar']['name'];
        $fileSize    = $_FILES['avatar']['size'];
        $fileType    = mime_content_type($fileTmpPath);

        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $maxFileSize      = 2 * 1024 * 1024; // 2MB Limit

        if (!in_array($fileType, $allowedMimeTypes)) {
            $_SESSION['flash_error'] = "Invalid image type. Only JPG, PNG, and WEBP are allowed.";
            header("Location: ../profile.php");
            exit();
        }

        if ($fileSize > $maxFileSize) {
            $_SESSION['flash_error'] = "Image size exceeds maximum limit of 2MB.";
            header("Location: ../profile.php");
            exit();
        }

        // Generate unique filename
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        $newFileName   = 'avatar_' . $userId . '_' . time() . '.' . $fileExtension;
        $uploadDir     = __DIR__ . '/../uploads/avatars/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $destPath = $uploadDir . $newFileName;

        if (move_uploaded_file($fileTmpPath, $destPath)) {
            $avatarPath = 'uploads/avatars/' . $newFileName;
        }
    }

    // Build SQL query
    if ($avatarPath) {
        $sql = "UPDATE users SET name = :name, email = :email, avatar = :avatar WHERE id = :id";
        $params = [':name' => $name, ':email' => $email, ':avatar' => $avatarPath, ':id' => $userId];
        $_SESSION['user_avatar'] = $avatarPath;
    } else {
        $sql = "UPDATE users SET name = :name, email = :email WHERE id = :id";
        $params = [':name' => $name, ':email' => $email, ':id' => $userId];
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $_SESSION['user_name']  = $name;
    $_SESSION['user_email'] = $email;
    $_SESSION['flash_success'] = "Profile updated successfully!";

    header("Location: ../profile.php");
    exit();

} catch (Exception $e) {
    error_log("Profile Update Error: " . $e->getMessage());
    $_SESSION['flash_error'] = "Failed to update profile.";
    header("Location: ../profile.php");
    exit();
}