<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Google Analytics / Search Console data for the Analytics page, sourced
 * entirely through Site Kit's own REST API (installed version verified:
 * Site Kit by Google 1.186.0, google-site-kit/v1 namespace).
 *
 * This file never talks to Google directly, never touches Site Kit's OAuth
 * credentials, and never renders another GA tag - it only reads report data
 * Site Kit already has, through the same REST routes Site Kit's own
 * dashboard uses (modules/analytics-4/data/report,
 * modules/search-console/data/searchanalytics), dispatched in-process via
 * rest_do_request() so the request runs as the current logged-in WP user.
 * Site Kit's own permission_callback for those routes (the
 * googlesitekit_view_posts_insights meta capability) is what actually
 * decides access - including Dashboard Sharing grants to non-admin roles -
 * so this file does not need to (and must not try to) reimplement that
 * logic itself.
 */

function cp_site_kit_is_active()
{
    return defined('GOOGLESITEKIT_VERSION');
}

/**
 * Dispatches a GET request against a Site Kit REST route in-process (no
 * HTTP round trip, no nonce needed - it runs under the current request's
 * already-authenticated WP user, exactly like any other internal
 * rest_do_request() call) and normalizes the result.
 *
 * @return array {
 *     @type string $status  'ok' | 'not_connected' | 'forbidden' | 'error'
 *     @type mixed  $data    Response data on 'ok', otherwise null.
 * }
 */
function cp_site_kit_rest_get($route, $params = [])
{
    if (!cp_site_kit_is_active()) {
        return ['status' => 'unavailable', 'data' => null];
    }

    $request = new WP_REST_Request('GET', '/google-site-kit/v1/' . ltrim($route, '/'));
    foreach ($params as $key => $value) {
        $request->set_param($key, $value);
    }

    $response = rest_do_request($request);

    if (!$response->is_error()) {
        return ['status' => 'ok', 'data' => $response->get_data()];
    }

    $error = $response->as_error();
    $code = $error instanceof WP_Error ? $error->get_error_code() : '';
    $status = (int) $response->get_status();

    if (in_array($code, ['invalid_module_slug', 'module_not_active'], true)) {
        return ['status' => 'not_connected', 'data' => null];
    }

    if (401 === $status || 403 === $status || 'rest_forbidden' === $code) {
        return ['status' => 'forbidden', 'data' => null];
    }

    cp_debug_log('Site Kit report request failed', ['route' => $route, 'code' => $code, 'status' => $status]);

    return ['status' => 'error', 'data' => null];
}

/**
 * User-facing message for a non-'ok' report state. Deliberately generic -
 * never surfaces the underlying Google/OAuth error to a client user.
 */
function cp_google_report_state_message($status)
{
    $messages = [
        'unavailable' => __('Google Site Kit is not available.', 'client-portal'),
        'not_connected' => __('This Google service is not connected.', 'client-portal'),
        'forbidden' => __('Analytics access is not available for this account.', 'client-portal'),
        'error' => __('Website analytics are temporarily unavailable.', 'client-portal'),
        'empty' => __('No data is available for this period.', 'client-portal'),
    ];

    return isset($messages[$status]) ? $messages[$status] : $messages['error'];
}

function cp_analytics_date_ranges()
{
    return [
        '7' => __('7 Days', 'client-portal'),
        '28' => __('28 Days', 'client-portal'),
        '90' => __('90 Days', 'client-portal'),
    ];
}

function cp_analytics_default_date_range()
{
    return '28';
}

/**
 * A pre-formatted "Last updated" time string (site timezone/format, per
 * WordPress's own date/time settings) - sent as a ready-to-display string
 * rather than a raw timestamp so the browser never needs to reconcile its
 * own timezone against the site's.
 */
function cp_analytics_generated_at_label()
{
    $format = trim(get_option('date_format') . ' ' . get_option('time_format'));
    return current_time($format ?: 'Y-m-d g:i a');
}

function cp_sanitize_analytics_date_range($range)
{
    $range = sanitize_key($range);
    return isset(cp_analytics_date_ranges()[$range]) ? $range : cp_analytics_default_date_range();
}

/**
 * Resolves a range key ('7'/'28'/'90') to [startDate, endDate] (Y-m-d),
 * anchored on yesterday - Analytics data for "today" is still incomplete
 * while the day is in progress, the same convention Site Kit's own
 * Date::parse_date_range() helper uses.
 */
function cp_analytics_date_range_bounds($range)
{
    $days = absint(cp_sanitize_analytics_date_range($range));
    $end = current_datetime()->modify('-1 day');
    $start = $end->modify('-' . ($days - 1) . ' days');

    return [$start->format('Y-m-d'), $end->format('Y-m-d')];
}

function cp_get_cached_google_report($cache_key, $ttl, $callback)
{
    $cached = get_transient($cache_key);
    if (false !== $cached && is_array($cached)) {
        return $cached;
    }

    $result = $callback();

    if (is_array($result) && 'ok' === ($result['status'] ?? '')) {
        set_transient($cache_key, $result, $ttl);
    }

    return $result;
}

function cp_get_ga_report($range, $metrics, $dimensions = [], $extra_args = [])
{
    list($start_date, $end_date) = cp_analytics_date_range_bounds($range);

    $params = array_merge(
        [
            'startDate' => $start_date,
            'endDate' => $end_date,
            'metrics' => $metrics,
        ],
        $dimensions ? ['dimensions' => $dimensions] : [],
        $extra_args
    );

    return cp_site_kit_rest_get('modules/analytics-4/data/report', $params);
}

function cp_get_gsc_report($range, $dimensions = [], $row_limit = 10)
{
    list($start_date, $end_date) = cp_analytics_date_range_bounds($range);

    $params = [
        'startDate' => $start_date,
        'endDate' => $end_date,
        'rowLimit' => absint($row_limit),
    ];
    if ($dimensions) {
        $params['dimensions'] = $dimensions;
    }

    return cp_site_kit_rest_get('modules/search-console/data/searchanalytics', $params);
}

/**
 * Website traffic: cards (visitors/page views/sessions) and a day-by-day
 * series for the chart, from a single GA4 report request (one Site Kit/
 * Google API call serves both, using the response's aggregate "totals" row
 * for the cards and its per-day "rows" for the chart).
 */
function cp_get_website_traffic($range)
{
    return cp_get_cached_google_report(
        'cp_ga_traffic_' . $range,
        20 * MINUTE_IN_SECONDS,
        function () use ($range) {
            $result = cp_get_ga_report($range, ['totalUsers', 'screenPageViews', 'sessions'], ['date']);
            if ('ok' !== $result['status']) {
                return $result;
            }

            $report = $result['data'];
            $metric_headers = isset($report['metricHeaders']) ? wp_list_pluck($report['metricHeaders'], 'name') : [];
            $rows = isset($report['rows']) && is_array($report['rows']) ? $report['rows'] : [];
            $totals_row = isset($report['totals'][0]['metricValues']) ? $report['totals'][0]['metricValues'] : [];

            $totals = [];
            foreach ($metric_headers as $index => $name) {
                $totals[$name] = isset($totals_row[$index]['value']) ? (float) $totals_row[$index]['value'] : 0;
            }

            $series = [];
            foreach ($rows as $row) {
                $date_value = isset($row['dimensionValues'][0]['value']) ? (string) $row['dimensionValues'][0]['value'] : '';
                if (!preg_match('/^\d{8}$/', $date_value)) {
                    continue;
                }

                $point = ['date' => gmdate('Y-m-d', strtotime($date_value))];
                foreach ($metric_headers as $index => $name) {
                    $point[$name] = isset($row['metricValues'][$index]['value']) ? (float) $row['metricValues'][$index]['value'] : 0;
                }
                $series[] = $point;
            }

            return [
                'status' => empty($rows) ? 'empty' : 'ok',
                'data' => [
                    'visitors' => (int) round($totals['totalUsers'] ?? 0),
                    'page_views' => (int) round($totals['screenPageViews'] ?? 0),
                    'sessions' => (int) round($totals['sessions'] ?? 0),
                    'series' => $series,
                ],
            ];
        }
    );
}

/**
 * Most-viewed content: GA4 report by pagePath, with the path resolved back
 * to the actual WordPress post/page title where possible.
 */
function cp_get_top_content($range, $limit = 10)
{
    return cp_get_cached_google_report(
        'cp_ga_top_content_' . $range,
        20 * MINUTE_IN_SECONDS,
        function () use ($range, $limit) {
            $result = cp_get_ga_report(
                $range,
                ['screenPageViews', 'totalUsers'],
                ['pagePath'],
                [
                    'limit' => $limit,
                    'orderby' => [['metric' => ['metricName' => 'screenPageViews'], 'desc' => true]],
                ]
            );

            if ('ok' !== $result['status']) {
                return $result;
            }

            $rows = isset($result['data']['rows']) && is_array($result['data']['rows']) ? $result['data']['rows'] : [];
            $items = [];

            foreach ($rows as $row) {
                $path = isset($row['dimensionValues'][0]['value']) ? (string) $row['dimensionValues'][0]['value'] : '';
                if ('' === $path) {
                    continue;
                }

                $items[] = [
                    'path' => $path,
                    'title' => cp_resolve_content_title($path),
                    'views' => isset($row['metricValues'][0]['value']) ? (int) round((float) $row['metricValues'][0]['value']) : 0,
                    'users' => isset($row['metricValues'][1]['value']) ? (int) round((float) $row['metricValues'][1]['value']) : 0,
                ];
            }

            return ['status' => empty($items) ? 'empty' : 'ok', 'data' => $items];
        }
    );
}

/**
 * Resolves a GA path back to its published WordPress title. Falls back to
 * the raw (escaped-on-output) path when it can't be resolved, or when the
 * matched post isn't publicly published - draft/private content is never
 * surfaced through this even if GA somehow recorded a hit against it.
 */
function cp_resolve_content_title($path)
{
    $path = '/' . ltrim((string) $path, '/');
    $post_id = url_to_postid(home_url($path));

    if ($post_id) {
        $post = get_post($post_id);
        if ($post instanceof WP_Post && 'publish' === $post->post_status) {
            return get_the_title($post);
        }
    }

    return $path;
}

function cp_get_search_performance_summary($range)
{
    return cp_get_cached_google_report(
        'cp_gsc_summary_' . $range,
        2 * HOUR_IN_SECONDS,
        function () use ($range) {
            $result = cp_get_gsc_report($range, [], 1);
            if ('ok' !== $result['status']) {
                return $result;
            }

            $rows = is_array($result['data']) ? $result['data'] : [];
            if (empty($rows)) {
                return ['status' => 'empty', 'data' => null];
            }

            $row = $rows[0];

            return [
                'status' => 'ok',
                'data' => [
                    'clicks' => isset($row['clicks']) ? (int) round((float) $row['clicks']) : 0,
                    'impressions' => isset($row['impressions']) ? (int) round((float) $row['impressions']) : 0,
                    'ctr' => isset($row['ctr']) ? (float) $row['ctr'] : 0.0,
                    'position' => isset($row['position']) ? (float) $row['position'] : 0.0,
                ],
            ];
        }
    );
}

function cp_get_top_search_queries($range, $limit = 10)
{
    return cp_get_cached_google_report(
        'cp_gsc_queries_' . $range,
        2 * HOUR_IN_SECONDS,
        function () use ($range, $limit) {
            $result = cp_get_gsc_report($range, ['query'], $limit);
            if ('ok' !== $result['status']) {
                return $result;
            }

            $rows = is_array($result['data']) ? $result['data'] : [];
            $items = [];

            foreach ($rows as $row) {
                $query = isset($row['keys'][0]) ? (string) $row['keys'][0] : '';
                if ('' === $query) {
                    continue;
                }

                $items[] = [
                    'query' => $query,
                    'clicks' => isset($row['clicks']) ? (int) round((float) $row['clicks']) : 0,
                    'impressions' => isset($row['impressions']) ? (int) round((float) $row['impressions']) : 0,
                    'ctr' => isset($row['ctr']) ? (float) $row['ctr'] : 0.0,
                    'position' => isset($row['position']) ? (float) $row['position'] : 0.0,
                ];
            }

            usort($items, function ($a, $b) {
                return $b['clicks'] <=> $a['clicks'];
            });

            return ['status' => empty($items) ? 'empty' : 'ok', 'data' => $items];
        }
    );
}

function cp_ajax_analytics_google_data_error($message, $status_code = 400)
{
    wp_send_json_error(['message' => sanitize_text_field($message)], $status_code);
}

/**
 * Deletes only the four Google report transients for one date range - never
 * touches the existing local-analytics data, other ranges' caches, or any
 * unrelated transient.
 */
function cp_invalidate_google_report_cache($range)
{
    $range = cp_sanitize_analytics_date_range($range);

    delete_transient('cp_ga_traffic_' . $range);
    delete_transient('cp_ga_top_content_' . $range);
    delete_transient('cp_gsc_summary_' . $range);
    delete_transient('cp_gsc_queries_' . $range);
}

function cp_ajax_get_analytics_google_data()
{
    if (!is_user_logged_in()) {
        cp_ajax_analytics_google_data_error(__('Please sign in to continue.', 'client-portal'), 401);
    }

    if (!check_ajax_referer('cp_analytics_google_data', 'nonce', false)) {
        cp_ajax_analytics_google_data_error(__('Your request expired. Please refresh the page and try again.', 'client-portal'), 403);
    }

    if (!current_user_can('read')) {
        cp_ajax_analytics_google_data_error(__('You do not have permission to view this data.', 'client-portal'), 403);
    }

    $range = cp_sanitize_analytics_date_range(isset($_POST['range']) ? wp_unslash($_POST['range']) : '');
    $force_refresh = !empty($_POST['refresh']);

    if ($force_refresh) {
        cp_invalidate_google_report_cache($range);
    }

    if (!cp_site_kit_is_active()) {
        wp_send_json_success([
            'range' => $range,
            'generated_at' => cp_analytics_generated_at_label(),
            'traffic' => ['status' => 'unavailable', 'message' => cp_google_report_state_message('unavailable')],
            'top_content' => ['status' => 'unavailable', 'message' => cp_google_report_state_message('unavailable')],
            'search_summary' => ['status' => 'unavailable', 'message' => cp_google_report_state_message('unavailable')],
            'top_queries' => ['status' => 'unavailable', 'message' => cp_google_report_state_message('unavailable')],
        ]);
    }

    $traffic = cp_get_website_traffic($range);
    $top_content = cp_get_top_content($range);
    $search_summary = cp_get_search_performance_summary($range);
    $top_queries = cp_get_top_search_queries($range);

    $format = function ($result) {
        if ('ok' === $result['status']) {
            return ['status' => 'ok', 'data' => $result['data']];
        }
        return ['status' => $result['status'], 'message' => cp_google_report_state_message($result['status'])];
    };

    wp_send_json_success([
        'range' => $range,
        'generated_at' => cp_analytics_generated_at_label(),
        'traffic' => $format($traffic),
        'top_content' => $format($top_content),
        'search_summary' => $format($search_summary),
        'top_queries' => $format($top_queries),
    ]);
}
add_action('wp_ajax_cp_analytics_google_data', 'cp_ajax_get_analytics_google_data');
