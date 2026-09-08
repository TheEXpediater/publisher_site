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
        var pendingForm = null;
        var pendingSubmitter = null;
        var pendingCallback = null;
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

        app.querySelectorAll('[data-cp-confirm]').forEach(function (trigger) {
            trigger.addEventListener('click', function (event) {
                var isFormSubmitter = 'BUTTON' === trigger.tagName && 'submit' === trigger.getAttribute('type') && trigger.form;

                event.preventDefault();

                triggerElement = trigger;
                pendingUrl = isFormSubmitter ? '' : trigger.href;
                pendingForm = isFormSubmitter ? trigger.form : null;
                pendingSubmitter = isFormSubmitter ? trigger : null;

                titleElement.textContent = trigger.getAttribute('data-cp-confirm-title') || 'Confirm action';
                messageElement.textContent = trigger.getAttribute('data-cp-confirm') || 'Are you sure you want to continue?';
                confirmButton.textContent = trigger.getAttribute('data-cp-confirm-label') || 'Confirm';

                var tone = trigger.getAttribute('data-cp-confirm-tone') || (trigger.classList.contains('btn-outline-danger') || trigger.classList.contains('btn-danger') ? 'danger' : 'default');
                setTone(tone);

                if (iconElement) {
                    iconElement.hidden = 'default' === tone;
                }

                modal.show();
            });
        });

        confirmButton.addEventListener('click', function () {
            var target = pendingUrl;
            var form = pendingForm;
            var submitter = pendingSubmitter;
            var callback = pendingCallback;
            pendingUrl = '';
            pendingForm = null;
            pendingSubmitter = null;
            pendingCallback = null;
            modal.hide();

            if (callback) {
                callback();
                return;
            }

            if (form) {
                if (form.requestSubmit) {
                    form.requestSubmit(submitter);
                } else {
                    form.submit();
                }
                return;
            }

            if (target) {
                window.location.href = target;
            }
        });

        modalElement.addEventListener('hidden.bs.modal', function () {
            pendingUrl = '';
            pendingForm = null;
            pendingSubmitter = null;
            pendingCallback = null;
            if (triggerElement) {
                triggerElement.focus();
                triggerElement = null;
            }
        });

        /**
         * Programmatic counterpart to the declarative [data-cp-confirm]
         * triggers above, for actions that aren't a plain link or form
         * submit (e.g. an AJAX save) - same shared confirm dialog, same
         * visual language, just invoked from JS with a callback instead of
         * a URL/form. Additive only; every existing [data-cp-confirm]
         * trigger above is unaffected.
         */
        window.cpConfirmAction = function (options) {
            options = options || {};

            titleElement.textContent = options.title || 'Confirm action';
            messageElement.textContent = options.message || 'Are you sure you want to continue?';
            confirmButton.textContent = options.confirmLabel || 'Confirm';

            var tone = options.tone || 'default';
            setTone(tone);
            if (iconElement) {
                iconElement.hidden = 'default' === tone;
            }

            pendingUrl = '';
            pendingForm = null;
            pendingSubmitter = null;
            pendingCallback = 'function' === typeof options.onConfirm ? options.onConfirm : null;
            triggerElement = options.returnFocusTo instanceof HTMLElement ? options.returnFocusTo : null;

            modal.show();
        };
    }

    function initSidebarCollapse(app) {
        var toggle = app.querySelector('[data-cp-sidebar-collapse]');
        var storageKey = 'cpPortalSidebarCollapsed';
        var isDesktop = function () {
            return window.matchMedia('(min-width: 1081px)').matches;
        };

        if (!toggle) {
            return;
        }

        function setCollapsed(collapsed, persist) {
            collapsed = Boolean(collapsed) && isDesktop();
            app.classList.toggle('cp-sidebar-collapsed', collapsed);
            toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            toggle.setAttribute('aria-label', collapsed ? 'Expand navigation' : 'Collapse navigation');
            toggle.setAttribute('title', collapsed ? 'Expand navigation' : 'Collapse navigation');

            var icon = toggle.querySelector('i');
            if (icon) {
                icon.classList.toggle('bi-chevron-left', !collapsed);
                icon.classList.toggle('bi-chevron-right', collapsed);
            }

            if (persist) {
                try {
                    window.localStorage.setItem(storageKey, collapsed ? '1' : '0');
                } catch (error) {
                    // Storage is optional. The navigation still works for this page view.
                }
            }
        }

        var stored = '0';
        try {
            stored = window.localStorage.getItem(storageKey) || '0';
        } catch (error) {
            stored = '0';
        }
        setCollapsed('1' === stored, false);

        toggle.addEventListener('click', function () {
            setCollapsed(!app.classList.contains('cp-sidebar-collapsed'), true);
        });

        window.addEventListener('resize', function () {
            if (!isDesktop()) {
                app.classList.remove('cp-sidebar-collapsed');
                toggle.setAttribute('aria-expanded', 'true');
            } else {
                try {
                    setCollapsed('1' === window.localStorage.getItem(storageKey), false);
                } catch (error) {
                    setCollapsed(false, false);
                }
            }
        });
    }

    function initActivityLogModal(app) {
        var modalElement = document.getElementById('cp-activity-detail-modal');
        var modal = modalElement && window.bootstrap && window.bootstrap.Modal
            ? new window.bootstrap.Modal(modalElement)
            : null;
        var actionElement = modalElement ? modalElement.querySelector('[data-cp-log-modal-action]') : null;
        var userElement = modalElement ? modalElement.querySelector('[data-cp-log-modal-user]') : null;
        var timeElement = modalElement ? modalElement.querySelector('[data-cp-log-modal-time]') : null;
        var detailsElement = modalElement ? modalElement.querySelector('[data-cp-log-modal-details]') : null;
        var emptyElement = modalElement ? modalElement.querySelector('[data-cp-log-modal-empty]') : null;
        var triggerElement = null;

        if (!modal || !actionElement || !userElement || !timeElement || !detailsElement || !emptyElement) {
            return;
        }

        app.querySelectorAll('[data-cp-log-detail]').forEach(function (button) {
            button.addEventListener('click', function () {
                var details = {};
                triggerElement = button;

                try {
                    details = JSON.parse(button.getAttribute('data-cp-log-details') || '{}');
                } catch (error) {
                    details = {};
                }

                actionElement.textContent = button.getAttribute('data-cp-log-action') || '';
                userElement.textContent = button.getAttribute('data-cp-log-user') || '';
                timeElement.textContent = button.getAttribute('data-cp-log-time') || '';
                detailsElement.textContent = '';

                var keys = Object.keys(details);
                emptyElement.hidden = keys.length > 0;
                detailsElement.hidden = 0 === keys.length;

                keys.forEach(function (key) {
                    var term = document.createElement('dt');
                    var description = document.createElement('dd');
                    term.textContent = key;
                    description.textContent = null === details[key] || undefined === details[key] ? '' : String(details[key]);
                    detailsElement.appendChild(term);
                    detailsElement.appendChild(description);
                });

                modal.show();
            });
        });

        modalElement.addEventListener('hidden.bs.modal', function () {
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
        initSidebarCollapse(app);

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
        initActivityLogModal(app);

        document.addEventListener('keydown', function (event) {
            if ('Escape' === event.key) {
                app.classList.remove('cp-sidebar-open');
            }
        });
    });
}());
