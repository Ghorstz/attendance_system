// assets/js/main.js

document.addEventListener('DOMContentLoaded', () => {
    // 1. Password Visibility Toggle Logic
    const toggleButtons = document.querySelectorAll('.toggle-password');
    toggleButtons.forEach(button => {
        button.addEventListener('click', () => {
            const targetId = button.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = button.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    // 2. Client-Side Register Form Password Match Validation
    const registerForm = document.querySelector('form[action*="register_action.php"]');
    if (registerForm) {
        registerForm.addEventListener('submit', (e) => {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password')?.value;

            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long.');
                return;
            }

            if (confirmPassword && password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match. Please re-enter.');
            }
        });
    }
});