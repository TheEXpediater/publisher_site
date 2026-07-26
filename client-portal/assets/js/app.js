(function () {
    'use strict';

    function dismissToast(toast) {
        if (!toast || toast.dataset.cpToastDismissed) {
            return;
        }

        toast.dataset.cpToastDismissed = '1';
        toast.classList.add('cp-toast-hiding');
        window.setTimeout(function () {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 180);
    }

    function cleanupNoticeUrl(region) {
        if (!region || !region.dataset.cpCleanUrlParam || !window.history || !window.history.replaceState) {
            return;
        }

        var url = new URL(window.location.href);
        if (!url.searchParams.has(region.dataset.cpCleanUrlParam)) {
            return;
        }

        url.searchParams.delete(region.dataset.cpCleanUrlParam);
        window.history.replaceState({}, document.title, url.pathname + (url.search ? '?' + url.searchParams.toString() : '') + url.hash);
    }

    function initToasts(app) {
        var region = app.querySelector('.cp-toast-region');
        if (!region) {
            return;
        }

        cleanupNoticeUrl(region);

        region.querySelectorAll('[data-cp-toast]').forEach(function (toast) {
            var delay = parseInt(toast.getAttribute('data-cp-toast-delay'), 10) || 5000;
            var close = toast.querySelector('[data-cp-toast-close]');

            if (close) {
                close.addEventListener('click', function () {
                    dismissToast(toast);
                });
            }

            window.setTimeout(function () {
                dismissToast(toast);
            }, delay);
        });
    }

    function initConfirmModal(app) {
        var modalElement = document.getElementById('cp-action-confirm-modal');
        var confirmButton = modalElement ? modalElement.querySelector('[data-cp-confirm-modal-button]') : null;
        var titleElement = modalElement ? modalElement.querySelector('#cp-action-confirm-title') : null;
        var messageElement = modalElement ? modalElement.querySelector('#cp-action-confirm-message') : null;
        var iconElement = modalElement ? modalElement.querySelector('.cp-confirm-icon') : null;
        var modal = modalElement && window.bootstrap && window.bootstrap.Modal
            ? new window.bootstrap.Modal(modalElement)
            : null;
        var pendingUrl = '';
        var triggerElement = null;

        if (!modal || !confirmButton || !titleElement || !messageElement) {
            return;
        }

        function setTone(tone) {
            confirmButton.classList.remove('btn-primary', 'btn-danger', 'btn-warning');
            modalElement.classList.remove('cp-action-confirm-danger', 'cp-action-confirm-warning');

            if ('danger' === tone) {
                confirmButton.classList.add('btn-danger');
                modalElement.classList.add('cp-action-confirm-danger');
                return;
            }

            if ('warning' === tone) {
                confirmButton.classList.add('btn-warning');
                modalElement.classList.add('cp-action-confirm-warning');
                return;
            }

            confirmButton.classList.add('btn-primary');
        }

        app.querySelectorAll('[data-cp-confirm]').forEach(function (link) {
            link.addEventListener('click', function (event) {
                event.preventDefault();

                triggerElement = link;
                pendingUrl = link.href;

                titleElement.textContent = link.getAttribute('data-cp-confirm-title') || 'Confirm action';
                messageElement.textContent = link.getAttribute('data-cp-confirm') || 'Are you sure you want to continue?';
                confirmButton.textContent = link.getAttribute('data-cp-confirm-label') || 'Confirm';

                var tone = link.getAttribute('data-cp-confirm-tone') || (link.classList.contains('btn-outline-danger') || link.classList.contains('btn-danger') ? 'danger' : 'default');
                setTone(tone);

                if (iconElement) {
                    iconElement.hidden = 'default' === tone;
                }

                modal.show();
            });
        });

        confirmButton.addEventListener('click', function () {
            var target = pendingUrl;
            pendingUrl = '';
            modal.hide();

            if (target) {
                window.location.href = target;
            }
        });

        modalElement.addEventListener('hidden.bs.modal', function () {
            pendingUrl = '';
            if (triggerElement) {
                triggerElement.focus();
                triggerElement = null;
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var app = document.querySelector('.cp-app');
        if (!app) {
            return;
        }

        initToasts(app);

        var logoutModal = document.getElementById('cp-logout-modal');
        if (logoutModal && logoutModal.parentNode !== app) {
            app.appendChild(logoutModal);
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

        initConfirmModal(app);

        document.addEventListener('keydown', function (event) {
            if ('Escape' === event.key) {
                app.classList.remove('cp-sidebar-open');
            }
        });
    });
}());
