(function () {
    'use strict';

    var config = window.cpAnalyticsGoogleData || {};
    var strings = config.strings || {};

    function formatNumber(value) {
        var number = Number(value) || 0;
        return number.toLocaleString();
    }

    function formatPercent(value) {
        var number = Number(value) || 0;
        return (number * 100).toFixed(1) + '%';
    }

    function formatPosition(value) {
        var number = Number(value) || 0;
        return number.toFixed(1);
    }

    function setSectionState(section, message) {
        var stateEl = section.querySelector('[data-cp-state]');
        var body = section.querySelector('.cp-analytics-body, .cp-stat-grid, .table-responsive');

        if (message) {
            if (stateEl) {
                stateEl.textContent = message;
                stateEl.hidden = false;
            }
            if (body) {
                body.hidden = true;
            }
        } else {
            if (stateEl) {
                stateEl.hidden = true;
                stateEl.textContent = '';
            }
            if (body) {
                body.hidden = false;
            }
        }
    }

    function renderMetrics(section, values, formatters) {
        Object.keys(values).forEach(function (key) {
            var el = section.querySelector('[data-cp-metric="' + key + '"]');
            if (!el) {
                return;
            }
            var format = (formatters && formatters[key]) || formatNumber;
            el.textContent = format(values[key]);
        });
    }

    function renderTrafficChart(container, series) {
        container.innerHTML = '';

        if (!series || !series.length) {
            return;
        }

        var width = 600;
        var height = 160;
        var padding = 8;
        var visitors = series.map(function (point) { return Number(point.totalUsers) || 0; });
        var pageViews = series.map(function (point) { return Number(point.screenPageViews) || 0; });
        var maxValue = Math.max(1, visitors.reduce(function (a, b) { return Math.max(a, b); }, 0), pageViews.reduce(function (a, b) { return Math.max(a, b); }, 0));

        function pointsToPath(values) {
            var step = series.length > 1 ? (width - padding * 2) / (series.length - 1) : 0;
            return values.map(function (value, index) {
                var x = padding + step * index;
                var y = height - padding - ((value / maxValue) * (height - padding * 2));
                return (0 === index ? 'M' : 'L') + x.toFixed(1) + ',' + y.toFixed(1);
            }).join(' ');
        }

        var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('viewBox', '0 0 ' + width + ' ' + height);
        svg.setAttribute('preserveAspectRatio', 'none');
        svg.setAttribute('class', 'cp-analytics-chart-svg');

        var pageViewsPath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        pageViewsPath.setAttribute('d', pointsToPath(pageViews));
        pageViewsPath.setAttribute('class', 'cp-analytics-chart-line cp-analytics-chart-line-secondary');
        svg.appendChild(pageViewsPath);

        var visitorsPath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        visitorsPath.setAttribute('d', pointsToPath(visitors));
        visitorsPath.setAttribute('class', 'cp-analytics-chart-line cp-analytics-chart-line-primary');
        svg.appendChild(visitorsPath);

        container.appendChild(svg);

        var legend = document.createElement('div');
        legend.className = 'cp-analytics-chart-legend';
        legend.innerHTML =
            '<span class="cp-analytics-chart-legend-item cp-analytics-chart-legend-primary">' + (strings.visitors || 'Visitors') + '</span>' +
            '<span class="cp-analytics-chart-legend-item cp-analytics-chart-legend-secondary">' + (strings.pageViews || 'Page Views') + '</span>';
        container.appendChild(legend);
    }

    function renderTopContentRows(tbody, items) {
        tbody.innerHTML = '';

        items.forEach(function (item) {
            var row = document.createElement('tr');

            var titleCell = document.createElement('td');
            titleCell.textContent = item.title || item.path;
            row.appendChild(titleCell);

            var viewsCell = document.createElement('td');
            viewsCell.textContent = formatNumber(item.views);
            row.appendChild(viewsCell);

            var usersCell = document.createElement('td');
            usersCell.textContent = formatNumber(item.users);
            row.appendChild(usersCell);

            tbody.appendChild(row);
        });
    }

    function renderTopQueryRows(tbody, items) {
        tbody.innerHTML = '';

        items.forEach(function (item) {
            var row = document.createElement('tr');

            [
                item.query,
                formatNumber(item.clicks),
                formatNumber(item.impressions),
                formatPercent(item.ctr),
                formatPosition(item.position)
            ].forEach(function (value) {
                var cell = document.createElement('td');
                cell.textContent = value;
                row.appendChild(cell);
            });

            tbody.appendChild(row);
        });
    }

    function applySection(sectionKey, result) {
        var section = document.querySelector('[data-cp-analytics-section="' + sectionKey + '"]');
        if (!section) {
            return;
        }

        if (!result || 'ok' !== result.status) {
            setSectionState(section, (result && result.message) || strings.error || 'Website analytics are temporarily unavailable.');
            return;
        }

        var data = result.data;

        if ('traffic' === sectionKey) {
            if (!data || !data.series || !data.series.length) {
                setSectionState(section, strings.noData || 'No data is available for this period.');
                return;
            }
            setSectionState(section, '');
            renderMetrics(section, { visitors: data.visitors, page_views: data.page_views, sessions: data.sessions });
            var chartEl = section.querySelector('[data-cp-chart]');
            if (chartEl) {
                renderTrafficChart(chartEl, data.series);
            }
            return;
        }

        if ('search_summary' === sectionKey) {
            if (!data) {
                setSectionState(section, strings.noData || 'No data is available for this period.');
                return;
            }
            setSectionState(section, '');
            renderMetrics(
                section,
                { clicks: data.clicks, impressions: data.impressions, ctr: data.ctr, position: data.position },
                { ctr: formatPercent, position: formatPosition }
            );
            return;
        }

        if ('top_content' === sectionKey || 'top_queries' === sectionKey) {
            var tbody = section.querySelector('[data-cp-rows]');
            if (!tbody) {
                return;
            }
            if (!data || !data.length) {
                setSectionState(section, strings.noData || 'No data is available for this period.');
                return;
            }
            setSectionState(section, '');
            if ('top_content' === sectionKey) {
                renderTopContentRows(tbody, data);
            } else {
                renderTopQueryRows(tbody, data);
            }
        }
    }

    function setSectionsLoading() {
        ['traffic', 'top_content', 'search_summary', 'top_queries'].forEach(function (key) {
            var section = document.querySelector('[data-cp-analytics-section="' + key + '"]');
            if (section) {
                setSectionState(section, strings.loading || 'Loading...');
            }
        });
    }

    var currentRange = config.defaultRange || '28';
    var isFetching = false;

    function setUpdatedLabel(text, isError) {
        var el = document.querySelector('[data-cp-analytics-updated]');
        if (!el) {
            return;
        }

        if (!text) {
            el.hidden = true;
            el.textContent = '';
            return;
        }

        el.hidden = false;
        el.textContent = text;
        el.classList.toggle('cp-analytics-updated-error', Boolean(isError));
    }

    function setRefreshButtonState(state) {
        var button = document.querySelector('[data-cp-analytics-refresh]');
        if (!button) {
            return;
        }

        var label = button.querySelector('[data-cp-refresh-label]');
        button.disabled = 'refreshing' === state;
        button.classList.toggle('is-refreshing', 'refreshing' === state);

        if (label) {
            label.textContent = 'refreshing' === state
                ? (strings.refreshing || 'Refreshing...')
                : (strings.refreshLabel || 'Refresh Data');
        }
    }

    function fetchGoogleData(range, isRefresh) {
        if (!config.ajaxUrl || !config.nonce || isFetching) {
            return;
        }

        isFetching = true;
        currentRange = range;
        setSectionsLoading();

        if (isRefresh) {
            setRefreshButtonState('refreshing');
            setUpdatedLabel(strings.refreshing || 'Refreshing...', false);
        }

        var body = new URLSearchParams();
        body.set('action', 'cp_analytics_google_data');
        body.set('nonce', config.nonce);
        body.set('range', range);
        if (isRefresh) {
            body.set('refresh', '1');
        }

        fetch(config.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        })
            .then(function (response) { return response.json(); })
            .then(function (payload) {
                if (!payload || !payload.success || !payload.data) {
                    throw new Error('invalid_response');
                }

                var result = payload.data;
                applySection('traffic', result.traffic);
                applySection('top_content', result.top_content);
                applySection('search_summary', result.search_summary);
                applySection('top_queries', result.top_queries);

                var updatedAt = result.generated_at || '';
                if (isRefresh) {
                    setUpdatedLabel(
                        (strings.refreshed || 'Analytics refreshed successfully.') + (updatedAt ? ' ' + (strings.lastUpdated || 'Last updated:') + ' ' + updatedAt : ''),
                        false
                    );
                } else if (updatedAt) {
                    setUpdatedLabel((strings.lastUpdated || 'Last updated:') + ' ' + updatedAt, false);
                }
            })
            .catch(function () {
                ['traffic', 'top_content', 'search_summary', 'top_queries'].forEach(function (key) {
                    applySection(key, { status: 'error' });
                });
                if (isRefresh) {
                    setUpdatedLabel(strings.error || 'Website analytics are temporarily unavailable.', true);
                }
            })
            .finally(function () {
                isFetching = false;
                setRefreshButtonState('idle');
            });
    }

    function initRangeSelector() {
        var group = document.querySelector('[data-cp-analytics-range-group]');
        if (!group) {
            return;
        }

        var buttons = group.querySelectorAll('[data-cp-range]');

        function setActive(range) {
            buttons.forEach(function (button) {
                var isActive = button.getAttribute('data-cp-range') === range;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });
        }

        buttons.forEach(function (button) {
            button.addEventListener('click', function () {
                var range = button.getAttribute('data-cp-range');
                setActive(range);
                fetchGoogleData(range, false);
            });
        });

        setActive(currentRange);
        fetchGoogleData(currentRange, false);
    }

    function initRefreshButton() {
        var button = document.querySelector('[data-cp-analytics-refresh]');
        if (!button) {
            return;
        }

        button.addEventListener('click', function () {
            fetchGoogleData(currentRange, true);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initRangeSelector();
        initRefreshButton();
    });
}());
