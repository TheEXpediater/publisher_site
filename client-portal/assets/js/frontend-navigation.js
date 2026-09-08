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

    function initAboutDropdown(nav) {
        var parent = nav.querySelector('.cp-primary-nav-about');
        var toggle = nav.querySelector('.cp-primary-nav-about-toggle');
        var menu = nav.querySelector('.cp-primary-nav-about-menu');

        if (!parent || !toggle || !menu) {
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

        // Desktop hover/focus reveal is CSS-driven ([hidden] is overridden
        // by a :hover/:focus-within rule) so the dropdown still works with
        // JS disabled; this only keeps aria-expanded truthful while that
        // happens, without changing what's actually shown.
        parent.addEventListener('mouseenter', function () {
            toggle.setAttribute('aria-expanded', 'true');
        });
        parent.addEventListener('mouseleave', function () {
            if (!parent.contains(document.activeElement)) {
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
        parent.addEventListener('focusin', function () {
            menu.removeAttribute('hidden');
            toggle.setAttribute('aria-expanded', 'true');
        });
        parent.addEventListener('focusout', function () {
            window.setTimeout(function () {
                if (!parent.contains(document.activeElement) && !parent.matches(':hover')) {
                    closeDropdown(toggle, menu);
                }
            }, 0);
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
        initAboutDropdown(nav);
        initMobileToggle(nav);
    }

    if ('loading' === document.readyState) {
        document.addEventListener('DOMContentLoaded', initNavigation);
    } else {
        initNavigation();
    }
}());
