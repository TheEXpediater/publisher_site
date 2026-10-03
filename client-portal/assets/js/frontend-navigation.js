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
        // Astra's Header Builder renders this exact markup into two
        // separate slots at once (theme_location "primary" and
        // "mobile_menu" - see cp_replace_primary_wp_nav_menu(),
        // includes/frontend-navigation.php) that it CSS-toggles by
        // viewport width rather than ever having only one in the DOM;
        // both need the About Us dropdown wiring, each fully independent
        // since their element IDs no longer collide
        // (cp_render_primary_navigation_markup() gives each instance its
        // own id prefix).
        var navs = document.querySelectorAll('.cp-primary-nav');
        navs.forEach(function (nav) {
            /*
             * The copy of this nav rendered inside Astra's own native
             * mobile-menu slot (.ast-builder-menu-mobile) must NEVER
             * receive its own internal collapse/toggle state: Astra's own
             * outer hamburger (#masthead .main-header-menu-toggle) already
             * has a complete, working open/close system for that slot
             * (astraNavMenuToggle() in Astra's frontend.js), and this
             * instance's own is-collapsible/is-open mechanism was only
             * ever meant for the "primary" desktop-row instance's own
             * (effectively unreachable in practice, since Astra's own
             * mobile breakpoint takes over first - see the 782px CSS tier
             * further down this file) minimal-JS fallback. Adding
             * is-collapsible here made THIS instance's menu list start
             * hidden behind its OWN internal toggle button too - a nested
             * second "MENU" trigger inside the drawer Astra's own
             * hamburger had just opened, requiring two taps to ever see a
             * nav link. Skipping it here means the CSS rules gated behind
             * .is-collapsible (assets/css/frontend-navigation.css) simply
             * never match for this instance, regardless of viewport width
             * or CSS specificity - the ancestor-scoped ".ast-builder-menu-mobile
             * .cp-primary-nav-toggle { display: none }" rule already in
             * that file is kept only as a defensive second layer.
             */
            if (nav.closest('.ast-builder-menu-mobile')) {
                initAboutDropdown(nav);
                return;
            }

            nav.classList.add('is-collapsible');
            initAboutDropdown(nav);
            initMobileToggle(nav);
        });
    }

    if ('loading' === document.readyState) {
        document.addEventListener('DOMContentLoaded', initNavigation);
    } else {
        initNavigation();
    }
}());
