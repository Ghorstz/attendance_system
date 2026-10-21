<?php
// register.php
require_once __DIR__ . '/config/auth.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

include __DIR__ . '/views/header.php';
?>

<div class="row justify-content-center mt-4">
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h3 class="card-title text-center mb-4 fw-bold">Create Account</h3>
                <form action="actions/register_action.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">
                    <div class="mb-3">
                        <label for="user_code" class="form-label">Student ID / User Code</label>
                        <input type="text" name="user_code" id="user_code" class="form-control" required placeholder="e.g. STU-201">
                    </div>
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" name="name" id="name" class="form-control" required placeholder="Firstname Lastname">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" name="email" id="email" class="form-control" required placeholder="name@domain.com">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" name="password" id="password" class="form-control" minlength="6" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <div class="input-group">
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" minlength="6" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="confirm_password">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success w-100 py-2 fw-semibold">Register</button>
                </form>
                <div class="text-center mt-3">
                    <span class="text-muted small">Already have an account?</span> 
                    <a href="login.php" class="small text-decoration-none fw-bold">Login here</a>
                </div>
            </div>
        </div>
    </div>
</div>
<input type="hidden" name="csrf_token" value="<?= generateCSRFToken(); ?>">

<?php include __DIR__ . '/views/footer.php'; ?>