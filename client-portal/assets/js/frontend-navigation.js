(function () {
    'use strict';

    function closeDropdown(toggle, menu) {
        toggle.setAttribute('aria-expanded', 'false');
        menu.setAttribute('hidden', 'hidden');
    }

    function openDropdown(toggle, menu) {
        toggle.setAttribute('aria-expanded', 'true');
        menu.removeAttribute('hidden');
    }

    function initMoreDropdown(nav) {
        var toggle = nav.querySelector('.cp-primary-nav-more-toggle');
        var menu = nav.querySelector('.cp-primary-nav-more-menu');

        if (!toggle || !menu) {
            return;
        }

        closeDropdown(toggle, menu);

        toggle.addEventListener('click', function (event) {
            event.stopPropagation();
            var isOpen = toggle.getAttribute('aria-expanded') === 'true';
            if (isOpen) {
                closeDropdown(toggle, menu);
            } else {
                openDropdown(toggle, menu);
            }
        });

        document.addEventListener('click', function (event) {
            if (!nav.contains(event.target)) {
                closeDropdown(toggle, menu);
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeDropdown(toggle, menu);
                toggle.focus();
            }
        });
    }

    function initMobileToggle(nav) {
        var toggle = nav.querySelector('.cp-primary-nav-toggle');
        var menu = nav.querySelector('.cp-primary-nav-menu');

        if (!toggle || !menu) {
            return;
        }

        toggle.addEventListener('click', function () {
            var isOpen = menu.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }

    function initNavigation() {
        var nav = document.getElementById('cp-primary-navigation');
        if (!nav) {
            return;
        }

        nav.classList.add('is-collapsible');
        initMoreDropdown(nav);
        initMobileToggle(nav);
    }

    if ('loading' === document.readyState) {
        document.addEventListener('DOMContentLoaded', initNavigation);
    } else {
        initNavigation();
    }
}());
