/* ============================================================
   SALVEX ADMIN — login.js
   ============================================================ */

// ---- PASSWORD TOGGLE ----
function togglePassword() {
    const input = document.getElementById('admin_password');
    const icon  = document.getElementById('eyeIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

// ---- FORM VALIDATION + LOGIN SIMULATION ----
function handleLogin(e) {
    e.preventDefault();
    const email    = document.getElementById('admin_email');
    const password = document.getElementById('admin_password');
    const emailErr = document.getElementById('emailError');
    const passErr  = document.getElementById('passError');
    const errorAlert   = document.getElementById('errorAlert');
    const successAlert = document.getElementById('successAlert');
    const loginBtn = document.getElementById('loginBtn');

    // Reset errors
    emailErr.textContent = '';
    passErr.textContent  = '';
    errorAlert.style.display   = 'none';
    successAlert.style.display = 'none';
    email.classList.remove('error');
    password.classList.remove('error');

    let valid = true;

    // Validate email
    if (!email.value.trim()) {
        emailErr.textContent = 'Email address is required.';
        email.classList.add('error');
        valid = false;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
        emailErr.textContent = 'Please enter a valid email address.';
        email.classList.add('error');
        valid = false;
    }

    // Validate password
    if (!password.value) {
        passErr.textContent = 'Password is required.';
        password.classList.add('error');
        valid = false;
    } else if (password.value.length < 4) {
        passErr.textContent = 'Password must be at least 4 characters.';
        password.classList.add('error');
        valid = false;
    }

    if (!valid) return;

    // Show loading state
    const btnText   = loginBtn.querySelector('.btn-text');
    const btnLoader = loginBtn.querySelector('.btn-loader');
    btnText.style.display   = 'none';
    btnLoader.style.display = 'flex';
    loginBtn.disabled = true;

    // Simulate authentication (replace with real PHP form submission)
    setTimeout(() => {
        // Demo: accept any email ending in @salvex.com or specific credentials
        const validEmail = email.value.toLowerCase().includes('admin') ||
                           email.value.toLowerCase().includes('salvex');
        const validPass  = password.value.length >= 4;

        if (validEmail && validPass) {
            successAlert.style.display = 'flex';
            btnText.style.display   = 'flex';
            btnLoader.style.display = 'none';
            loginBtn.disabled = false;
            // Redirect to dashboard after 1.5s
            setTimeout(() => {
                window.location.href = 'dashboard.php';
            }, 1500);
        } else {
            errorAlert.style.display = 'flex';
            document.getElementById('errorMsg').textContent =
                'Invalid credentials. Use an admin email and password.';
            btnText.style.display   = 'flex';
            btnLoader.style.display = 'none';
            loginBtn.disabled = false;
            // Shake animation
            const card = document.querySelector('.login-card');
            card.style.animation = 'none';
            requestAnimationFrame(() => {
                card.style.animation = 'shakeCard 0.4s ease';
            });
        }
    }, 1600);
}

// ---- FORGOT PASSWORD MODAL ----
function showForgotModal() {
    const modal = document.getElementById('forgotModal');
    modal.classList.add('open');
}
function hideForgotModal() {
    const modal = document.getElementById('forgotModal');
    modal.classList.remove('open');
}
function closeForgotModal(e) {
    if (e.target === document.getElementById('forgotModal')) {
        hideForgotModal();
    }
}

// ---- INPUT FOCUS EFFECTS ----
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.form-input').forEach(input => {
        input.addEventListener('focus', () => {
            input.closest('.input-wrapper')?.classList.add('focused');
        });
        input.addEventListener('blur', () => {
            input.closest('.input-wrapper')?.classList.remove('focused');
        });
    });
});

// ---- CSS for shake + input error (injected) ----
const style = document.createElement('style');
style.textContent = `
@keyframes shakeCard {
  0%,100%{transform:translateX(0)}
  15%{transform:translateX(-8px)}
  30%{transform:translateX(8px)}
  45%{transform:translateX(-6px)}
  60%{transform:translateX(6px)}
  75%{transform:translateX(-4px)}
  90%{transform:translateX(4px)}
}
.form-input.error {
  border-color: var(--accent) !important;
  box-shadow: 0 0 0 3px rgba(244,63,94,0.12) !important;
}
`;
document.head.appendChild(style);