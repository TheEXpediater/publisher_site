(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var toggle = document.querySelector('[data-cp-toggle-password]');
        var password = document.getElementById('cp-login-password');

        if (toggle && password) {
            toggle.addEventListener('click', function () {
                var shouldShow = 'password' === password.type;
                password.type = shouldShow ? 'text' : 'password';
                toggle.setAttribute('aria-label', shouldShow ? toggle.dataset.hideLabel : toggle.dataset.showLabel);
            });
        }

        initLockoutCountdown();
    });

    // Renders a live mm:ss countdown to the server-issued lockout expiry
    // (data-cp-lockout-until, a Unix timestamp) so refreshing the page never
    // resets what the user sees - the server transient is the only source
    // of truth; this only formats it. The submit button is disabled purely
    // as a UX affordance: the server still rejects the request either way
    // if the countdown is bypassed (e.g. devtools) or drifts from the
    // client clock.
    function initLockoutCountdown() {
        var errorBox = document.querySelector('[data-cp-lockout-until]');
        var submitButton = document.querySelector('.cp-login-submit');

        if (!errorBox) {
            return;
        }

        var lockedUntil = parseInt(errorBox.getAttribute('data-cp-lockout-until'), 10);
        if (!lockedUntil) {
            return;
        }

        var textEl = errorBox.querySelector('[data-cp-lockout-text]');
        var template = (window.cpLoginLockout && window.cpLoginLockout.template)
            || 'Too many sign-in attempts. Please try again in %s.';

        function formatRemaining(totalSeconds) {
            var minutes = Math.floor(totalSeconds / 60);
            var seconds = totalSeconds % 60;
            return (minutes < 10 ? '0' : '') + minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
        }

        function tick() {
            var remaining = lockedUntil - Math.floor(Date.now() / 1000);

            if (remaining <= 0) {
                clearInterval(timer);
                if (submitButton) {
                    submitButton.disabled = false;
                }
                if (textEl) {
                    textEl.textContent = '';
                }
                errorBox.hidden = true;
                return;
            }

            if (textEl) {
                textEl.textContent = template.replace('%s', formatRemaining(remaining));
            }
        }

        if (submitButton) {
            submitButton.disabled = true;
        }

        tick();
        var timer = window.setInterval(tick, 1000);
    }
}());
