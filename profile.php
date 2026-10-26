<?php
// profile.php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

requireLogin();

$userId = $_SESSION['user_id'];
$pdo    = getDBConnection();

$stmt = $pdo->prepare("SELECT user_code, name, email, role, avatar, created_at FROM users WHERE id = :id");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

include __DIR__ . '/views/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">

            <?php if (isset($_SESSION['flash_success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['flash_error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white text-center py-4">
                    <div class="mb-3 position-relative display-inline-block">
                        <?php if (!empty($user['avatar']) && file_exists(__DIR__ . '/' . $user['avatar'])): ?>
                            <img src="<?= htmlspecialchars($user['avatar']); ?>" alt="Profile Picture" class="rounded-circle img-thumbnail shadow-sm" style="width: 120px; height: 120px; object-fit: cover;">
                        <?php else: ?>
                            <div class="rounded-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 120px; height: 120px; font-size: 3rem;">
                                <?= strtoupper(substr($user['name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <h4 class="fw-bold mb-0"><?= htmlspecialchars($user['name']); ?></h4>
                    <span class="badge bg-primary text-uppercase mt-1"><?= htmlspecialchars($user['role']); ?></span>
                </div>

                <div class="card-body p-4">
                    <form action="actions/update_profile_action.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">

                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">User Code / ID</label>
                            <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($user['user_code']); ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">Full Name</label>
                            <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($user['name']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Email Address</label>
                            <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($user['email']); ?>" required>
                        </div>

                        <div class="mb-4">
                            <label for="avatar" class="form-label fw-semibold">Upload New Profile Picture</label>
                            <input type="file" name="avatar" id="avatar" class="form-control" accept="image/jpeg,image/png,image/webp">
                            <div class="form-text">Max size: 2MB. Accepted formats: JPG, PNG, WEBP.</div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fa-solid fa-arrow-left me-1"></i> Back to Dashboard
                            </a>
                            <button type="submit" class="btn btn-primary px-4 fw-bold">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/views/footer.php'; ?>