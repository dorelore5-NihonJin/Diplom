(function () {
    const savedTheme = localStorage.getItem('theme') || 'dark';
    document.documentElement.setAttribute('data-theme', savedTheme);
})();

window.switchTab = function (tab) {
    document.querySelectorAll('.login-tab').forEach((t) => t.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach((c) => c.classList.remove('active'));

    if (tab === 'login') {
        const loginTabButton = document.querySelector('.login-tab:first-child');
        if (loginTabButton) {
            loginTabButton.classList.add('active');
        }
        document.getElementById('login-form')?.classList.add('active');
    } else {
        const registerTabButton = document.querySelector('.login-tab:last-child');
        if (registerTabButton) {
            registerTabButton.classList.add('active');
        }
        document.getElementById('register-form')?.classList.add('active');
    }
};
