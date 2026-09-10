<?php

if (!defined('ABSPATH')) {
    exit;
}

$editing_category = isset($editing_category) && $editing_category instanceof WP_Term ? $editing_category : null;
$modal_category_id = $editing_category ? absint($editing_category->term_id) : 0;
$modal_category_name = $editing_category ? $editing_category->name : '';
$modal_category_slug = $editing_category ? $editing_category->slug : '';
$modal_category_description = $editing_category ? $editing_category->description : '';
$modal_category_active = $editing_category ? cp_category_is_active($editing_category) : true;
$category_page = isset($category_page) ? max(1, absint($category_page)) : 1;
$category_total = isset($category_total) ? absint($category_total) : count($categories);
$category_start = isset($category_start) ? absint($category_start) : 0;
$category_end = isset($category_end) ? absint($category_end) : 0;
$category_max_pages = isset($category_max_pages) ? max(1, absint($category_max_pages)) : 1;
$category_page_args = cp_category_page_args($category_page);

$nav_header_categories = isset($nav_header_categories) && is_array($nav_header_categories) ? $nav_header_categories : [];
$nav_footer_categories = isset($nav_footer_categories) && is_array($nav_footer_categories) ? $nav_footer_categories : [];
$nav_about_us_url = isset($nav_about_us_url) ? (string) $nav_about_us_url : home_url('/about-us/');
$nav_header_typography = isset($nav_header_typography) && is_array($nav_header_typography) ? $nav_header_typography : cp_nav_default_typography();
$nav_footer_typography = isset($nav_footer_typography) && is_array($nav_footer_typography) ? $nav_footer_typography : cp_nav_default_typography();
$nav_font_families = isset($nav_font_families) && is_array($nav_font_families) ? $nav_font_families : cp_nav_font_family_choices();
$nav_font_sizes = isset($nav_font_sizes) && is_array($nav_font_sizes) ? $nav_font_sizes : cp_nav_font_size_choices();
$nav_max_visible = isset($nav_max_visible) ? absint($nav_max_visible) : CP_NAV_MAX_VISIBLE_CATEGORIES;
$nav_editor_data = wp_json_encode([
    'header' => [
        'categories' => $nav_header_categories,
        'fontFamily' => isset($nav_header_typography['font_family']) ? $nav_header_typography['font_family'] : 'default',
        'fontSize' => isset($nav_header_typography['font_size']) ? absint($nav_header_typography['font_size']) : 13,
    ],
    'footer' => [
        'categories' => $nav_footer_categories,
        'fontFamily' => isset($nav_footer_typography['font_family']) ? $nav_footer_typography['font_family'] : 'default',
        'fontSize' => isset($nav_footer_typography['font_size']) ? absint($nav_footer_typography['font_size']) : 13,
    ],
    'aboutUsUrl' => $nav_about_us_url,
    'maxVisible' => $nav_max_visible,
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('cp_save_nav_menu'),
]);

if (0 === $category_total) {
    $category_count_text = __('No categories found', 'client-portal');
} else {
    $category_count_text = sprintf(
        /* translators: 1: first visible category number, 2: last visible category number, 3: total categories */
        __('Showing %1$s-%2$s of %3$s categories', 'client-portal'),
        number_format_i18n($category_start),
        number_format_i18n($category_end),
        number_format_i18n($category_total)
    );
}
?>
<div class="cp-page-heading">
    <div>
        <p class="cp-eyebrow"><?php esc_html_e('Publication Sections', 'client-portal'); ?></p>
        <h2><?php esc_html_e('Category Manager', 'client-portal'); ?></h2>
        <p><?php esc_html_e('Build a clear structure for your publication.', 'client-portal'); ?></p>
    </div>
    <div class="cp-page-heading-actions">
        <button
            class="btn btn-outline-primary"
            type="button"
            data-bs-toggle="modal"
            data-bs-target="#cp-menu-modal"
            data-cp-menu-view
        >
            <i class="bi bi-menu-button-wide" aria-hidden="true"></i>
            <?php esc_html_e('View Menu', 'client-portal'); ?>
        </button>
        <?php if (function_exists('cp_can_manage_pages') && cp_can_manage_pages()) : ?>
            <a class="btn btn-outline-primary" href="<?php echo esc_url(cp_admin_url('cp-pages')); ?>">
                <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
                <?php esc_html_e('Pages', 'client-portal'); ?>
            </a>
        <?php endif; ?>
        <button
            class="btn btn-primary"
            type="button"
            data-bs-toggle="modal"
            data-bs-target="#cp-category-modal"
            data-cp-category-add
        >
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <?php esc_html_e('Add Category', 'client-portal'); ?>
        </button>
    </div>
</div>

<?php cp_render_admin_notice($notice); ?>

<section class="cp-card cp-table-card">
    <div class="cp-card-header">
        <div>
            <h3><?php esc_html_e('Categories', 'client-portal'); ?></h3>
            <p><?php echo esc_html($category_count_text); ?></p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="cp-table table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Name', 'client-portal'); ?></th>
                    <th><?php esc_html_e('Slug', 'client-portal'); ?></th>
                    <th><?php esc_html_e('Articles', 'client-portal'); ?></th>
                    <th><?php esc_html_e('Status', 'client-portal'); ?></th>
                    <th><?php esc_html_e('Actions', 'client-portal'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($categories) : ?>
                    <?php foreach ($categories as $category) : ?>
                        <?php
                        $category_is_active = cp_category_is_active($category);
                        $category_data = wp_json_encode([
                            'id' => absint($category->term_id),
                            'name' => (string) $category->name,
                            'slug' => (string) $category->slug,
                            'description' => (string) $category->description,
                            'active' => $category_is_active,
                        ]);
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($category->name); ?></strong>
                                <small class="cp-table-subtitle"><?php echo esc_html($category->description); ?></small>
                            </td>
                            <td><code><?php echo esc_html($category->slug); ?></code></td>
                            <td><?php echo esc_html(number_format_i18n($category->count)); ?></td>
                            <td>
                                <span class="cp-badge cp-badge-<?php echo $category_is_active ? 'success' : 'secondary'; ?>">
                                    <?php echo $category_is_active ? esc_html__('Active', 'client-portal') : esc_html__('Inactive', 'client-portal'); ?>
                                </span>
                            </td>
                            <td>
                                <div class="cp-actions">
                                    <button
                                        class="btn btn-sm btn-outline-primary"
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#cp-category-modal"
                                        data-cp-category-edit
                                        data-cp-category="<?php echo esc_attr($category_data); ?>"
                                    >
                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                        <span><?php esc_html_e('Edit', 'client-portal'); ?></span>
                                    </button>
                                    <a
                                        class="btn btn-sm btn-outline-danger"
                                        data-cp-confirm="<?php echo esc_attr__('Delete this category?', 'client-portal'); ?>"
                                        data-cp-confirm-title="<?php echo esc_attr__('Delete category', 'client-portal'); ?>"
                                        data-cp-confirm-label="<?php echo esc_attr__('Delete', 'client-portal'); ?>"
                                        data-cp-confirm-tone="danger"
                                        href="<?php echo esc_url(wp_nonce_url(cp_admin_url('cp-categories', array_merge($category_page_args, ['action' => 'delete', 'id' => $category->term_id])), 'cp_delete_category_' . $category->term_id)); ?>"
                                    >
                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                        <span><?php esc_html_e('Delete', 'client-portal'); ?></span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5" class="cp-empty-state"><?php esc_html_e('No categories found.', 'client-portal'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($category_max_pages > 1) : ?>
        <?php
        $large_number = 999999999;
        $pagination_base = str_replace(
            (string) $large_number,
            '%#%',
            add_query_arg(
                [
                    'page' => 'cp-categories',
                    'category_page' => $large_number,
                ],
                admin_url('admin.php')
            )
        );
        $pagination_links = paginate_links([
            'base' => $pagination_base,
            'format' => '',
            'current' => $category_page,
            'total' => $category_max_pages,
            'type' => 'array',
            'prev_text' => __('Previous', 'client-portal'),
            'next_text' => __('Next', 'client-portal'),
        ]);
        ?>
        <?php if (!empty($pagination_links)) : ?>
            <nav class="cp-table-pagination" aria-label="<?php esc_attr_e('Category Manager pagination', 'client-portal'); ?>">
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

<div
    class="modal fade cp-modal"
    id="cp-category-modal"
    tabindex="-1"
    aria-labelledby="cp-category-modal-title"
    aria-hidden="true"
    data-cp-open="<?php echo esc_attr($editing_category ? '1' : '0'); ?>"
    data-cp-add-title="<?php echo esc_attr__('Add Category', 'client-portal'); ?>"
    data-cp-edit-title="<?php echo esc_attr__('Edit Category', 'client-portal'); ?>"
    data-cp-add-label="<?php echo esc_attr__('Add Category', 'client-portal'); ?>"
    data-cp-edit-label="<?php echo esc_attr__('Update Category', 'client-portal'); ?>"
    data-cp-add-confirm="<?php echo esc_attr__('Create this category?', 'client-portal'); ?>"
    data-cp-edit-confirm="<?php echo esc_attr__('Save these category changes?', 'client-portal'); ?>"
    data-cp-edit-confirm-deactivating="<?php echo esc_attr__('Save these changes? This category will become inactive and its category page will be placed under maintenance.', 'client-portal'); ?>"
    data-cp-edit-confirm-activating="<?php echo esc_attr__('Save these changes? This category will become active and its category page will be available again.', 'client-portal'); ?>"
>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="<?php echo esc_url(cp_admin_url('cp-categories')); ?>" data-cp-category-form>
                <?php wp_nonce_field('cp_save_category', 'cp_category_nonce'); ?>
                <input type="hidden" name="cp_category_action" value="save">
                <input type="hidden" name="category_page" value="<?php echo esc_attr($category_page); ?>">
                <input type="hidden" name="category_id" value="<?php echo esc_attr($modal_category_id); ?>" data-cp-category-id>
                <input type="hidden" name="mode" value="<?php echo esc_attr($editing_category ? 'edit' : 'create'); ?>" data-cp-category-mode>

                <div class="modal-header">
                    <div>
                        <p class="cp-eyebrow mb-1"><?php esc_html_e('Publication Section', 'client-portal'); ?></p>
                        <h2 class="modal-title" id="cp-category-modal-title" data-cp-category-modal-title>
                            <?php echo $editing_category ? esc_html__('Edit Category', 'client-portal') : esc_html__('Add Category', 'client-portal'); ?>
                        </h2>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e('Close', 'client-portal'); ?>"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <span class="form-label d-block"><?php esc_html_e('Status', 'client-portal'); ?></span>
                        <input type="hidden" name="active" value="0">
                        <label class="cp-setting-toggle" for="cp-category-active">
                            <input
                                type="checkbox"
                                role="switch"
                                id="cp-category-active"
                                name="active"
                                value="1"
                                data-cp-category-active
                                <?php checked($modal_category_active); ?>
                            >
                            <span class="cp-setting-toggle-track" aria-hidden="true"><span class="cp-setting-toggle-knob"></span></span>
                            <span class="cp-setting-toggle-copy">
                                <strong data-cp-category-active-label><?php echo $modal_category_active ? esc_html__('Active', 'client-portal') : esc_html__('Inactive', 'client-portal'); ?></strong>
                                <small><?php esc_html_e('Inactive categories are hidden from the public navigation. Articles and the category page are kept.', 'client-portal'); ?></small>
                            </span>
                        </label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="cp-category-name"><?php esc_html_e('Name', 'client-portal'); ?></label>
                        <input
                            class="form-control"
                            id="cp-category-name"
                            name="name"
                            value="<?php echo esc_attr($modal_category_name); ?>"
                            data-cp-category-name
                            required
                        >
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="cp-category-slug"><?php esc_html_e('Slug', 'client-portal'); ?></label>
                        <input
                            class="form-control"
                            id="cp-category-slug"
                            name="slug"
                            value="<?php echo esc_attr($modal_category_slug); ?>"
                            data-cp-category-slug
                        >
                        <div class="form-text"><?php esc_html_e('Leave blank to generate the slug from the category name.', 'client-portal'); ?></div>
                    </div>
                    <div>
                        <label class="form-label" for="cp-category-description"><?php esc_html_e('Description', 'client-portal'); ?></label>
                        <textarea
                            class="form-control"
                            id="cp-category-description"
                            name="description"
                            rows="5"
                            data-cp-category-description
                        ><?php echo esc_textarea($modal_category_description); ?></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?php esc_html_e('Cancel', 'client-portal'); ?></button>
                    <button
                        type="submit"
                        class="btn btn-primary"
                        data-cp-category-submit
                        data-cp-confirm-title="<?php echo esc_attr($editing_category ? __('Update category', 'client-portal') : __('Add category', 'client-portal')); ?>"
                        data-cp-confirm="<?php echo esc_attr($editing_category ? __('Save these category changes?', 'client-portal') : __('Create this category?', 'client-portal')); ?>"
                        data-cp-confirm-label="<?php echo esc_attr($editing_category ? __('Save Changes', 'client-portal') : __('Add Category', 'client-portal')); ?>"
                    >
                        <?php echo $editing_category ? esc_html__('Update Category', 'client-portal') : esc_html__('Add Category', 'client-portal'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$render_nav_typography_fields = static function ($surface, $typography) use ($nav_font_families, $nav_font_sizes) {
    ?>
    <div class="cp-menu-editor-toolbar">
        <div class="cp-menu-editor-toolbar-field">
            <label for="cp-menu-font-family-<?php echo esc_attr($surface); ?>"><?php esc_html_e('Font Family', 'client-portal'); ?></label>
            <select class="form-select form-select-sm" id="cp-menu-font-family-<?php echo esc_attr($surface); ?>" data-cp-menu-font-family="<?php echo esc_attr($surface); ?>">
                <?php foreach ($nav_font_families as $font_key => $font_choice) : ?>
                    <option value="<?php echo esc_attr($font_key); ?>" <?php selected(isset($typography['font_family']) ? $typography['font_family'] : '', $font_key); ?>>
                        <?php echo esc_html($font_choice['label']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="cp-menu-editor-toolbar-field">
            <label for="cp-menu-font-size-<?php echo esc_attr($surface); ?>"><?php esc_html_e('Font Size', 'client-portal'); ?></label>
            <select class="form-select form-select-sm" id="cp-menu-font-size-<?php echo esc_attr($surface); ?>" data-cp-menu-font-size="<?php echo esc_attr($surface); ?>">
                <?php foreach ($nav_font_sizes as $size_choice) : ?>
                    <option value="<?php echo esc_attr($size_choice); ?>" <?php selected(isset($typography['font_size']) ? absint($typography['font_size']) : 0, $size_choice); ?>>
                        <?php echo esc_html(sprintf(__('%dpx', 'client-portal'), $size_choice)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php
};
?>
<div
    class="modal fade cp-modal cp-menu-modal"
    id="cp-menu-modal"
    tabindex="-1"
    aria-labelledby="cp-menu-modal-title"
    aria-hidden="true"
    data-cp-menu-editor="<?php echo esc_attr($nav_editor_data); ?>"
>
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <p class="cp-eyebrow mb-1"><?php esc_html_e('Frontend Navigation', 'client-portal'); ?></p>
                    <h2 class="modal-title" id="cp-menu-modal-title"><?php esc_html_e('Site Menu', 'client-portal'); ?></h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e('Close', 'client-portal'); ?>"></button>
            </div>
            <div class="modal-body">
                <div class="cp-menu-editor-feedback" data-cp-menu-feedback hidden role="status" aria-live="polite"></div>

                <div data-cp-menu-view-mode>
                    <div class="cp-menu-editor-tabs" role="tablist" aria-label="<?php esc_attr_e('Navigation preview', 'client-portal'); ?>" data-cp-view-tabs>
                        <button type="button" class="cp-menu-editor-tab is-active" data-cp-view-tab="header" role="tab" aria-selected="true" aria-controls="cp-menu-view-panel-header" id="cp-menu-view-tab-header">
                            <?php esc_html_e('Header', 'client-portal'); ?>
                        </button>
                        <button type="button" class="cp-menu-editor-tab" data-cp-view-tab="footer" role="tab" aria-selected="false" aria-controls="cp-menu-view-panel-footer" id="cp-menu-view-tab-footer" tabindex="-1">
                            <?php esc_html_e('Footer', 'client-portal'); ?>
                        </button>
                    </div>

                    <div class="cp-menu-editor-tab-panel" data-cp-view-tab-panel="header" id="cp-menu-view-panel-header" role="tabpanel" aria-labelledby="cp-menu-view-tab-header">
                        <h3 class="cp-menu-editor-section-title"><?php esc_html_e('Header Navigation Preview', 'client-portal'); ?></h3>
                        <div class="cp-menu-editor-preview" data-cp-menu-view-preview="header" aria-label="<?php esc_attr_e('Header navigation preview', 'client-portal'); ?>">
                            <?php echo cp_render_primary_navigation_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </div>
                    </div>
                    <div class="cp-menu-editor-tab-panel" data-cp-view-tab-panel="footer" id="cp-menu-view-panel-footer" role="tabpanel" aria-labelledby="cp-menu-view-tab-footer" hidden>
                        <h3 class="cp-menu-editor-section-title"><?php esc_html_e('Footer Navigation Preview', 'client-portal'); ?></h3>
                        <div class="cp-menu-editor-preview" data-cp-menu-view-preview="footer" aria-label="<?php esc_attr_e('Footer navigation preview', 'client-portal'); ?>">
                            <?php echo cp_render_footer_navigation_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </div>
                    </div>

                    <p class="cp-menu-editor-intro cp-menu-editor-intro-below">
                        <?php esc_html_e('This is the actual navigation currently live on the site, built from your Categories in their saved order. About Us always stays last; the first 7 categories appear directly, the rest inside its dropdown.', 'client-portal'); ?>
                    </p>
                </div>

                <div data-cp-menu-edit-mode hidden>
                    <div class="cp-menu-editor-tabs" role="tablist" aria-label="<?php esc_attr_e('Menu Editor', 'client-portal'); ?>">
                        <button type="button" class="cp-menu-editor-tab is-active" data-cp-menu-tab="header" role="tab" aria-selected="true" aria-controls="cp-menu-tab-panel-header" id="cp-menu-tab-header">
                            <?php esc_html_e('Header', 'client-portal'); ?>
                        </button>
                        <button type="button" class="cp-menu-editor-tab" data-cp-menu-tab="footer" role="tab" aria-selected="false" aria-controls="cp-menu-tab-panel-footer" id="cp-menu-tab-footer" tabindex="-1">
                            <?php esc_html_e('Footer', 'client-portal'); ?>
                        </button>
                    </div>

                    <div class="cp-menu-editor-tab-panel" data-cp-menu-tab-panel="header" id="cp-menu-tab-panel-header" role="tabpanel" aria-labelledby="cp-menu-tab-header">
                        <h3 class="cp-menu-editor-section-title"><?php esc_html_e('Header Menu', 'client-portal'); ?></h3>
                        <div class="cp-menu-editor-preview" data-cp-menu-preview="header" aria-label="<?php esc_attr_e('Header navigation preview', 'client-portal'); ?>"></div>

                        <h4 class="cp-menu-editor-subsection-title"><?php esc_html_e('Header Appearance', 'client-portal'); ?></h4>
                        <?php $render_nav_typography_fields('header', $nav_header_typography); ?>

                        <h4 class="cp-menu-editor-subsection-title"><?php esc_html_e('Menu Position', 'client-portal'); ?></h4>
                        <p class="cp-menu-editor-body-help">
                            <?php esc_html_e('Press and hold a category to drag it, or use the arrow buttons. The first 7 positions appear directly in the header; the rest move into the About Us dropdown. About Us always stays last and cannot be moved.', 'client-portal'); ?>
                        </p>
                        <ul class="cp-menu-editor-list" data-cp-menu-list="header" role="list"></ul>
                        <div class="cp-menu-editor-fixed-item">
                            <span class="cp-menu-editor-fixed-badge"><i class="bi bi-lock-fill" aria-hidden="true"></i> <?php esc_html_e('Fixed', 'client-portal'); ?></span>
                            <span><?php esc_html_e('About Us', 'client-portal'); ?></span>
                            <span class="cp-menu-editor-fixed-note"><?php esc_html_e('Always last. Not draggable.', 'client-portal'); ?></span>
                        </div>
                    </div>

                    <div class="cp-menu-editor-tab-panel" data-cp-menu-tab-panel="footer" id="cp-menu-tab-panel-footer" role="tabpanel" aria-labelledby="cp-menu-tab-footer" hidden>
                        <h3 class="cp-menu-editor-section-title"><?php esc_html_e('Footer Menu', 'client-portal'); ?></h3>
                        <div class="cp-menu-editor-preview" data-cp-menu-preview="footer" aria-label="<?php esc_attr_e('Footer navigation preview', 'client-portal'); ?>"></div>

                        <h4 class="cp-menu-editor-subsection-title"><?php esc_html_e('Footer Appearance', 'client-portal'); ?></h4>
                        <?php $render_nav_typography_fields('footer', $nav_footer_typography); ?>

                        <h4 class="cp-menu-editor-subsection-title"><?php esc_html_e('Menu Position', 'client-portal'); ?></h4>
                        <p class="cp-menu-editor-body-help">
                            <?php esc_html_e('Press and hold a category to drag it, or use the arrow buttons. The footer shows every active category in this order - there is no direct-item limit. About Us always stays last and cannot be moved.', 'client-portal'); ?>
                        </p>
                        <ul class="cp-menu-editor-list" data-cp-menu-list="footer" role="list"></ul>
                        <div class="cp-menu-editor-fixed-item">
                            <span class="cp-menu-editor-fixed-badge"><i class="bi bi-lock-fill" aria-hidden="true"></i> <?php esc_html_e('Fixed', 'client-portal'); ?></span>
                            <span><?php esc_html_e('About Us', 'client-portal'); ?></span>
                            <span class="cp-menu-editor-fixed-note"><?php esc_html_e('Always last. Not draggable.', 'client-portal'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" data-cp-menu-close>
                    <?php esc_html_e('Close', 'client-portal'); ?>
                </button>
                <button type="button" class="btn btn-primary" data-cp-menu-edit>
                    <i class="bi bi-pencil" aria-hidden="true"></i>
                    <?php esc_html_e('Edit Menu', 'client-portal'); ?>
                </button>
                <button type="button" class="btn btn-outline-secondary" data-cp-menu-cancel hidden>
                    <?php esc_html_e('Cancel', 'client-portal'); ?>
                </button>
                <button type="button" class="btn btn-primary" data-cp-menu-save hidden>
                    <i class="bi bi-check-lg" aria-hidden="true"></i>
                    <?php esc_html_e('Save Changes', 'client-portal'); ?>
                </button>
            </div>
        </div>
    </div>
</div>
