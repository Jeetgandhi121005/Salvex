document.addEventListener('DOMContentLoaded', function() {
    
    // Page load par modal ensure close rahe
    const modal = document.getElementById('authModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.classList.remove('no-scroll');
    }
});

// Modal switch logic (Login <-> Signup)
function switchAuth(type) {
    const loginSec = document.getElementById('loginSection');
    const signupSec = document.getElementById('signupSection');
    const toggleContainer = document.querySelector('.auth-toggle-container');
    const btnLogin = document.getElementById('btn-login');
    const btnSignup = document.getElementById('btn-signup');
    
    if (type === 'signup') {
        loginSec.style.display = 'none';
        signupSec.style.display = 'block';
        toggleContainer.classList.add('toggle-signup-active');
        btnSignup.classList.add('active');
        btnLogin.classList.remove('active');
    } else {
        loginSec.style.display = 'block';
        signupSec.style.display = 'none';
        toggleContainer.classList.remove('toggle-signup-active');
        btnLogin.classList.add('active');
        btnSignup.classList.remove('active');
    }
}