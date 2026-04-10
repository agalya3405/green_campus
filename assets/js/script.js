/**
 * Campus Green Innovation Portal - Frontend script
 * Simple enhancements: form validation, smooth behavior
 */

document.addEventListener('DOMContentLoaded', function () {
    // Auto-hide alerts after 5 seconds
    var alerts = document.querySelectorAll('.alert');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            if (alert.parentNode) {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.3s';
                setTimeout(function () {
                    if (alert.parentNode) alert.parentNode.removeChild(alert);
                }, 300);
            }
        }, 5000);
    });

    // Confirm before leaving if form might have changes (optional)
    var forms = document.querySelectorAll('form');
    forms.forEach(function (form) {
        form.addEventListener('submit', function () {
            var btn = form.querySelector('button[type="submit"]');
            if (btn && !btn.disabled) {
                btn.disabled = true;
                btn.textContent = btn.dataset.loading || 'Please wait...';
            }
        });
    });

    // Password match check on register page
    var confirmField = document.getElementById('confirm_password');
    var passwordField = document.getElementById('password');
    if (confirmField && passwordField) {
        function checkMatch() {
            if (confirmField.value && passwordField.value !== confirmField.value) {
                confirmField.setCustomValidity('Passwords do not match');
            } else {
                confirmField.setCustomValidity('');
            }
        }
        confirmField.addEventListener('input', checkMatch);
        passwordField.addEventListener('input', checkMatch);
    }
});
