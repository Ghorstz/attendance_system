<?php
// login.php
require_once __DIR__ . '/config/auth.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

include __DIR__ . '/views/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-5">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h3 class="card-title text-center mb-4 fw-bold">Sign In</h3>
                <form action="actions/login_action.php" method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" name="email" id="email" class="form-control" required placeholder="admin@example.com">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" name="password" id="password" class="form-control" required placeholder="••••••••">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Login</button>
                </form>
                <div class="text-center mt-3">
                    <span class="text-muted small">Don't have an account?</span> 
                    <a href="register.php" class="small text-decoration-none fw-bold">Register here</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/views/footer.php'; ?>