(function () {
    const savedTheme = localStorage.getItem('theme') || 'dark';
    document.documentElement.setAttribute('data-theme', savedTheme);
})();

function checkPasswordStrength() {
    const password = document.getElementById('password').value;
    const strengthFill = document.getElementById('strengthFill');

    const requirements = {
        length: password.length >= 8,
        upper: /[A-Z]/.test(password),
        lower: /[a-z]/.test(password),
        number: /[0-9]/.test(password)
    };

    Object.entries(requirements).forEach(([key, met]) => {
        const el = document.getElementById(`req-${key}`);
        if (el) {
            el.className = met ? 'valid' : '';
        }
    });

    const strength = Object.values(requirements).filter(Boolean).length;
    strengthFill.className = 'strength-fill';
    if (strength <= 2) {
        strengthFill.classList.add('strength-weak');
    } else if (strength === 3) {
        strengthFill.classList.add('strength-medium');
    } else if (strength === 4) {
        strengthFill.classList.add('strength-strong');
    }
}

window.checkPasswordStrength = checkPasswordStrength;

document.addEventListener('DOMContentLoaded', () => {
    const registerForm = document.getElementById('registerForm');
    if (!registerForm) return;

    registerForm.addEventListener('submit', (e) => {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;

        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Пароли не совпадают!');
        }
    });
});
