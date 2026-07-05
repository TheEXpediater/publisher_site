(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var app = document.querySelector('.cp-app');
        if (!app) {
            return;
        }

        document.querySelectorAll('[data-cp-sidebar-open]').forEach(function (button) {
            button.addEventListener('click', function () {
                app.classList.add('cp-sidebar-open');
            });
        });

        document.querySelectorAll('[data-cp-sidebar-close]').forEach(function (button) {
            button.addEventListener('click', function () {
                app.classList.remove('cp-sidebar-open');
            });
        });

        document.querySelectorAll('[data-cp-confirm]').forEach(function (link) {
            link.addEventListener('click', function (event) {
                if (!window.confirm(link.getAttribute('data-cp-confirm'))) {
                    event.preventDefault();
                }
            });
        });

        document.addEventListener('keydown', function (event) {
            if ('Escape' === event.key) {
                app.classList.remove('cp-sidebar-open');
            }
        });
    });
}());
