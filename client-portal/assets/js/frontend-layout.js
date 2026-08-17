(function () {
    'use strict';

    function pixels(value) {
        var number = parseFloat(value);
        return Number.isFinite(number) ? number : 0;
    }

    function isPageShell(element) {
        if (!element || element === document.body || element === document.documentElement) {
            return true;
        }
        return Boolean(element.querySelector(':scope > header, :scope > footer, :scope > .site-header, :scope > .site-footer, :scope > #masthead, :scope > #colophon'));
    }

    function ancestorsUntilBody(element) {
        var ancestors = [];
        var current = element ? element.parentElement : null;
        while (current && current !== document.body && current !== document.documentElement) {
            ancestors.push(current);
            current = current.parentElement;
        }
        return ancestors;
    }

    function wrapperSelector(element) {
        var selector = element.tagName.toLowerCase();
        if (element.id) {
            return selector + '#' + element.id;
        }
        var classes = Array.prototype.filter.call(element.classList, function (className) {
            return className.indexOf('cp-enterprise-') !== 0;
        });
        return selector + (classes.length ? '.' + classes.join('.') : '');
    }

    function responsivePagePadding(viewportWidth) {
        if (viewportWidth <= 480) {
            return 16;
        }
        if (viewportWidth <= 768) {
            return 24;
        }
        if (viewportWidth <= 1024) {
            return 32;
        }
        if (viewportWidth <= 1280) {
            return 48;
        }
        return 64;
    }

    function markHomepageWrappers() {
        if (!document.body.classList.contains('enterprise-publication-homepage')) {
            return;
        }

        var publications = document.querySelectorAll('.enterprise-publication');
        if (!publications.length) {
            return;
        }

        var viewportWidth = document.documentElement.clientWidth;
        var targetWidth = Math.min(1440, Math.max(0, viewportWidth - (responsivePagePadding(viewportWidth) * 2)));
        var processed = new Set();
        window.cpEnterpriseLayoutReport = [];

        Array.prototype.forEach.call(publications, function (publication) {
            ancestorsUntilBody(publication).forEach(function (wrapper) {
                if (processed.has(wrapper) || isPageShell(wrapper)) {
                    return;
                }
                processed.add(wrapper);

                var styles = window.getComputedStyle(wrapper);
                var maxWidth = pixels(styles.maxWidth);
                var width = wrapper.getBoundingClientRect().width;
                var horizontalPadding = pixels(styles.paddingLeft) + pixels(styles.paddingRight);
                var horizontalMargins = pixels(styles.marginLeft) + pixels(styles.marginRight);
                var hasRestrictiveMaxWidth = maxWidth > 0 && maxWidth <= 1240;
                var isDesktopLimiter = viewportWidth > 1250 && width > 0 && width <= 1240;
                var isConstrainedContentWrapper = viewportWidth > 768
                    && width > 0
                    && targetWidth > 0
                    && width < (targetWidth - 48)
                    && horizontalMargins > 0;

                if (isDesktopLimiter || hasRestrictiveMaxWidth || isConstrainedContentWrapper) {
                    wrapper.classList.add('cp-enterprise-home-width-wrapper');
                    wrapper.setAttribute('data-cp-original-max-width', styles.maxWidth);
                    window.cpEnterpriseLayoutReport.push({
                        selector: wrapperSelector(wrapper),
                        computedMaxWidth: styles.maxWidth,
                        computedWidth: Math.round(width),
                        horizontalPadding: Math.round(horizontalPadding)
                    });
                }

                if ((isDesktopLimiter || hasRestrictiveMaxWidth || isConstrainedContentWrapper) && horizontalPadding > 0) {
                    wrapper.classList.add('cp-enterprise-home-padding-wrapper');
                }
            });
        });
    }

    function initializeLayout() {
        markHomepageWrappers();
    }

    if ('loading' === document.readyState) {
        document.addEventListener('DOMContentLoaded', initializeLayout);
    } else {
        initializeLayout();
    }
}());
