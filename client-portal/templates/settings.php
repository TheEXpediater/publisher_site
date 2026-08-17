<?php

if (!defined('ABSPATH')) {
    exit;
}

$active_tab = isset($active_tab) ? sanitize_key($active_tab) : 'preferences';
$preferences_url = cp_admin_url('cp-settings', ['tab' => 'preferences']);
$activity_url = cp_admin_url('cp-settings', ['tab' => 'activity']);
?>
<div class="cp-page-heading">
    <div>
        <p class="cp-eyebrow"><?php esc_html_e('Configuration', 'client-portal'); ?></p>
        <h2><?php esc_html_e('Portal Settings', 'client-portal'); ?></h2>
        <p><?php esc_html_e('Manage publishing preferences and review account activity.', 'client-portal'); ?></p>
    </div>
</div>

<div class="cp-settings-shell">
    <nav class="cp-settings-tabs" aria-label="<?php esc_attr_e('Settings sections', 'client-portal'); ?>">
        <a class="cp-settings-tab <?php echo 'preferences' === $active_tab ? 'active' : ''; ?>" href="<?php echo esc_url($preferences_url); ?>" <?php if ('preferences' === $active_tab) : ?>aria-current="page"<?php endif; ?>>
            <i class="bi bi-sliders" aria-hidden="true"></i>
            <span><?php esc_html_e('Preferences', 'client-portal'); ?></span>
        </a>
        <a class="cp-settings-tab <?php echo 'activity' === $active_tab ? 'active' : ''; ?>" href="<?php echo esc_url($activity_url); ?>" <?php if ('activity' === $active_tab) : ?>aria-current="page"<?php endif; ?>>
            <i class="bi bi-clock-history" aria-hidden="true"></i>
            <span><?php esc_html_e('Activity Logs', 'client-portal'); ?></span>
        </a>
    </nav>

    <?php if ('preferences' === $active_tab) : ?>
        <?php cp_render_admin_notice(cp_settings_errors_as_notices('cp_portal_settings_group')); ?>
        <section class="cp-card cp-settings-card">
            <form method="post" action="options.php">
                <?php settings_fields('cp_portal_settings_group'); ?>
                <?php do_settings_sections('cp-settings'); ?>
                <?php submit_button(__('Save Settings', 'client-portal'), 'primary', 'submit', false); ?>
            </form>
        </section>
    <?php else : ?>
        <?php
        $activity_filters = isset($activity_filters) && is_array($activity_filters) ? $activity_filters : ['date_from' => '', 'date_to' => '', 'action' => ''];
        $activity_log = isset($activity_log) && is_array($activity_log) ? $activity_log : ['rows' => [], 'total' => 0, 'page' => 1, 'max_pages' => 1];
        $activity_actions = isset($activity_actions) && is_array($activity_actions) ? $activity_actions : [];
        ?>
        <section class="cp-card cp-log-filter-card">
            <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
                <input type="hidden" name="page" value="cp-settings">
                <input type="hidden" name="tab" value="activity">
                <div class="cp-log-filter-grid">
                    <div>
                        <label class="form-label" for="cp-log-date-from"><?php esc_html_e('From date', 'client-portal'); ?></label>
                        <input class="form-control" type="date" id="cp-log-date-from" name="log_date_from" value="<?php echo esc_attr($activity_filters['date_from']); ?>">
                    </div>
                    <div>
                        <label class="form-label" for="cp-log-date-to"><?php esc_html_e('To date', 'client-portal'); ?></label>
                        <input class="form-control" type="date" id="cp-log-date-to" name="log_date_to" value="<?php echo esc_attr($activity_filters['date_to']); ?>">
                    </div>
                    <div>
                        <label class="form-label" for="cp-log-action"><?php esc_html_e('Action', 'client-portal'); ?></label>
                        <select class="form-select" id="cp-log-action" name="log_action">
                            <option value=""><?php esc_html_e('All actions', 'client-portal'); ?></option>
                            <?php foreach ($activity_actions as $action_key => $action_label) : ?>
                                <option value="<?php echo esc_attr($action_key); ?>" <?php selected($activity_filters['action'], $action_key); ?>><?php echo esc_html($action_label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="cp-filter-actions">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-funnel" aria-hidden="true"></i><span><?php esc_html_e('Filter', 'client-portal'); ?></span></button>
                        <a class="btn btn-outline-secondary" href="<?php echo esc_url($activity_url); ?>"><?php esc_html_e('Reset', 'client-portal'); ?></a>
                    </div>
                </div>
            </form>
        </section>

        <section class="cp-card">
            <div class="cp-log-summary">
                <div>
                    <h3><?php esc_html_e('Activity Logs', 'client-portal'); ?></h3>
                    <p>
                        <?php
                        printf(
                            /* translators: %s: Number of log records. */
                            esc_html(_n('%s recorded event', '%s recorded events', absint($activity_log['total']), 'client-portal')),
                            esc_html(number_format_i18n(absint($activity_log['total'])))
                        );
                        ?>
                    </p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table cp-table align-middle">
                    <thead>
                        <tr>
                            <th scope="col"><?php esc_html_e('User', 'client-portal'); ?></th>
                            <th scope="col"><?php esc_html_e('Time', 'client-portal'); ?></th>
                            <th scope="col"><?php esc_html_e('Action', 'client-portal'); ?></th>
                            <th scope="col" class="text-end"><?php esc_html_e('Details', 'client-portal'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($activity_log['rows'])) : ?>
                            <tr>
                                <td class="cp-empty-state" colspan="4">
                                    <i class="bi bi-clock-history" aria-hidden="true"></i>
                                    <strong><?php esc_html_e('No activity found', 'client-portal'); ?></strong>
                                    <span><?php esc_html_e('Adjust the filters or check again after portal activity occurs.', 'client-portal'); ?></span>
                                </td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($activity_log['rows'] as $log_row) : ?>
                                <?php
                                $user_name = !empty($log_row->user_name) ? $log_row->user_name : __('System', 'client-portal');
                                $user_email = !empty($log_row->user_email) ? $log_row->user_email : __('Automated action', 'client-portal');
                                $local_time = get_date_from_gmt($log_row->created_at, get_option('date_format') . ' ' . get_option('time_format'));
                                $action_label = cp_activity_log_action_label($log_row->action);
                                $badge_class = cp_activity_log_action_badge_class($log_row->action);
                                $details = cp_activity_log_decode_details($log_row->details);
                                $detail_header = [];
                                if (!empty($log_row->object_label) && !isset($details[__('Item', 'client-portal')])) {
                                    $detail_header[__('Item', 'client-portal')] = $log_row->object_label;
                                }
                                if (!empty($log_row->object_id)) {
                                    $detail_header[__('Reference ID', 'client-portal')] = absint($log_row->object_id);
                                }
                                if (!empty($detail_header)) {
                                    $details = array_merge($detail_header, $details);
                                }
                                ?>
                                <tr>
                                    <td>
                                        <div class="cp-log-user">
                                            <strong><?php echo esc_html($user_name); ?></strong>
                                            <small><?php echo esc_html($user_email); ?></small>
                                        </div>
                                    </td>
                                    <td class="cp-log-time"><?php echo esc_html($local_time); ?></td>
                                    <td><span class="cp-log-action-badge cp-log-action-<?php echo esc_attr($badge_class); ?>"><?php echo esc_html($action_label); ?></span></td>
                                    <td class="text-end">
                                        <button
                                            class="btn btn-sm btn-outline-secondary cp-log-detail-button"
                                            type="button"
                                            data-cp-log-detail
                                            data-cp-log-action="<?php echo esc_attr($action_label); ?>"
                                            data-cp-log-user="<?php echo esc_attr($user_name); ?>"
                                            data-cp-log-time="<?php echo esc_attr($local_time); ?>"
                                            data-cp-log-details="<?php echo esc_attr(wp_json_encode($details)); ?>"
                                        >
                                            <i class="bi bi-eye" aria-hidden="true"></i>
                                            <span><?php esc_html_e('View', 'client-portal'); ?></span>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (absint($activity_log['max_pages']) > 1) : ?>
                <?php
                $large_number = 999999999;
                $pagination_args = array_filter([
                    'page' => 'cp-settings',
                    'tab' => 'activity',
                    'log_date_from' => $activity_filters['date_from'],
                    'log_date_to' => $activity_filters['date_to'],
                    'log_action' => $activity_filters['action'],
                    'log_page' => $large_number,
                ]);
                $pagination_base = str_replace((string) $large_number, '%#%', add_query_arg($pagination_args, admin_url('admin.php')));
                $pagination_links = paginate_links([
                    'base' => $pagination_base,
                    'format' => '',
                    'current' => max(1, absint($activity_log['page'])),
                    'total' => max(1, absint($activity_log['max_pages'])),
                    'type' => 'array',
                    'prev_text' => '<i class="bi bi-chevron-left" aria-hidden="true"></i><span class="visually-hidden">' . esc_html__('Previous', 'client-portal') . '</span>',
                    'next_text' => '<i class="bi bi-chevron-right" aria-hidden="true"></i><span class="visually-hidden">' . esc_html__('Next', 'client-portal') . '</span>',
                ]);
                ?>
                <?php if (!empty($pagination_links)) : ?>
                    <nav class="cp-table-pagination" aria-label="<?php esc_attr_e('Activity log pages', 'client-portal'); ?>">
                        <ul class="cp-pagination-list">
                            <?php foreach ($pagination_links as $pagination_link) : ?>
                                <?php
                                $pagination_link = str_replace('class="page-numbers current"', 'class="page-numbers current cp-pagination-current"', $pagination_link);
                                $pagination_link = str_replace('class="page-numbers"', 'class="page-numbers cp-pagination-link"', $pagination_link);
                                $pagination_link = str_replace('class="prev page-numbers"', 'class="prev page-numbers cp-pagination-link"', $pagination_link);
                                $pagination_link = str_replace('class="next page-numbers"', 'class="next page-numbers cp-pagination-link"', $pagination_link);
                                ?>
                                <li><?php echo wp_kses_post($pagination_link); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <div class="modal fade cp-modal" id="cp-activity-detail-modal" tabindex="-1" aria-labelledby="cp-activity-detail-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title" id="cp-activity-detail-title"><?php esc_html_e('Activity Details', 'client-portal'); ?></h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e('Close', 'client-portal'); ?>"></button>
                    </div>
                    <div class="modal-body">
                        <div class="cp-log-modal-meta">
                            <div><span><?php esc_html_e('Action', 'client-portal'); ?></span><strong data-cp-log-modal-action></strong></div>
                            <div><span><?php esc_html_e('User', 'client-portal'); ?></span><strong data-cp-log-modal-user></strong></div>
                            <div><span><?php esc_html_e('Time', 'client-portal'); ?></span><strong data-cp-log-modal-time></strong></div>
                        </div>
                        <dl class="cp-log-detail-list" data-cp-log-modal-details></dl>
                        <p class="cp-log-empty-detail" data-cp-log-modal-empty hidden><?php esc_html_e('No additional details were recorded for this action.', 'client-portal'); ?></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?php esc_html_e('Close', 'client-portal'); ?></button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
