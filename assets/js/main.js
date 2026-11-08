/**
 * SIMAYA - Helper JavaScript Sederhana
 */

document.addEventListener('DOMContentLoaded', function() {
    // Auto hide alerts after 5 seconds if desired
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(function() {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 500);
        }, 6000);
    });
});

/**
 * Pengisian cepat form login untuk demonstrasi dosen/evaluator
 */
function quickLogin(username, password) {
    const userField = document.getElementById('username');
    const passField = document.getElementById('password');
    if (userField && passField) {
        userField.value = username;
        passField.value = password;
        document.getElementById('loginForm').submit();
    }
}

