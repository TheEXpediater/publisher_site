<?php

if (!defined('ABSPATH')) {
    exit;
}

$editing_category = isset($editing_category) && $editing_category instanceof WP_Term ? $editing_category : null;
$modal_category_id = $editing_category ? absint($editing_category->term_id) : 0;
$modal_category_name = $editing_category ? $editing_category->name : '';
$modal_category_slug = $editing_category ? $editing_category->slug : '';
$modal_category_description = $editing_category ? $editing_category->description : '';
$category_page = isset($category_page) ? max(1, absint($category_page)) : 1;
$category_total = isset($category_total) ? absint($category_total) : count($categories);
$category_start = isset($category_start) ? absint($category_start) : 0;
$category_end = isset($category_end) ? absint($category_end) : 0;
$category_max_pages = isset($category_max_pages) ? max(1, absint($category_max_pages)) : 1;
$category_page_args = cp_category_page_args($category_page);

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
                    <th><?php esc_html_e('Actions', 'client-portal'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($categories) : ?>
                    <?php foreach ($categories as $category) : ?>
                        <?php
                        $category_data = wp_json_encode([
                            'id' => absint($category->term_id),
                            'name' => (string) $category->name,
                            'slug' => (string) $category->slug,
                            'description' => (string) $category->description,
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
                                        data-cp-confirm="<?php echo esc_attr__('Are you sure you want to delete this category?', 'client-portal'); ?>"
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
                        <td colspan="4" class="cp-empty-state"><?php esc_html_e('No categories found.', 'client-portal'); ?></td>
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
>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="<?php echo esc_url(cp_admin_url('cp-categories')); ?>" data-cp-category-form>
                <?php wp_nonce_field('cp_save_category', 'cp_category_nonce'); ?>
                <input type="hidden" name="cp_category_action" value="save">
                <input type="hidden" name="category_page" value="<?php echo esc_attr($category_page); ?>">
                <input type="hidden" name="category_id" value="<?php echo esc_attr($modal_category_id); ?>" data-cp-category-id>

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
                    <button type="submit" class="btn btn-primary" data-cp-category-submit>
                        <?php echo $editing_category ? esc_html__('Update Category', 'client-portal') : esc_html__('Add Category', 'client-portal'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
