(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var toggle = document.querySelector('[data-cp-toggle-password]');
        var password = document.getElementById('cp-login-password');

        if (!toggle || !password) {
            return;
        }

        toggle.addEventListener('click', function () {
            var shouldShow = 'password' === password.type;
            password.type = shouldShow ? 'text' : 'password';
            toggle.setAttribute('aria-label', shouldShow ? toggle.dataset.hideLabel : toggle.dataset.showLabel);
        });
    });
}());
