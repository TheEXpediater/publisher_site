<?php

if (!defined('ABSPATH')) {
    exit;
}

$categories = isset($categories) ? $categories : [];
$editing_category = isset($editing_category) ? $editing_category : null;

?>
<div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h2 class="h4 mb-1"><?php esc_html_e('Categories', 'client-portal'); ?></h2>
        <p class="text-muted mb-0">Organize content using WordPress categories.</p>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="post">
            <?php wp_nonce_field('cp_categories_action', 'cp_categories_nonce'); ?>
            <input type="hidden" name="category_id" value="<?php echo esc_attr($editing_category ? $editing_category->term_id : ''); ?>" />
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label" for="cp-category-name"><?php esc_html_e('Name', 'client-portal'); ?></label>
                    <input type="text" class="form-control" id="cp-category-name" name="name" value="<?php echo esc_attr($editing_category ? $editing_category->name : ''); ?>" required />
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="cp-category-slug"><?php esc_html_e('Slug', 'client-portal'); ?></label>
                    <input type="text" class="form-control" id="cp-category-slug" name="slug" value="<?php echo esc_attr($editing_category ? $editing_category->slug : ''); ?>" />
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary" name="cp_categories_submit" value="1">
                        <?php echo $editing_category ? esc_html__('Update Category', 'client-portal') : esc_html__('Add Category', 'client-portal'); ?>
                    </button>
                    <?php if ($editing_category) : ?>
                        <a class="btn btn-outline-secondary" href="<?php echo esc_url(cp_admin_url('cp-categories')); ?>"><?php esc_html_e('Cancel', 'client-portal'); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Name', 'client-portal'); ?></th>
                        <th><?php esc_html_e('Slug', 'client-portal'); ?></th>
                        <th><?php esc_html_e('Actions', 'client-portal'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($categories)) : ?>
                        <?php foreach ($categories as $category) : ?>
                            <tr>
                                <td><?php echo esc_html($category->name); ?></td>
                                <td><?php echo esc_html($category->slug); ?></td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a class="btn btn-sm btn-outline-primary" href="<?php echo esc_url(wp_nonce_url(add_query_arg(['page' => 'cp-categories', 'action' => 'edit', 'id' => $category->term_id], admin_url('admin.php')), 'cp_edit_category_' . $category->term_id)); ?>"><?php esc_html_e('Edit', 'client-portal'); ?></a>
                                        <a class="btn btn-sm btn-outline-danger" href="<?php echo esc_url(wp_nonce_url(add_query_arg(['page' => 'cp-categories', 'action' => 'delete', 'id' => $category->term_id], admin_url('admin.php')), 'cp_delete_category_' . $category->term_id)); ?>"><?php esc_html_e('Delete', 'client-portal'); ?></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="3" class="text-muted py-4 text-center"><?php esc_html_e('No categories found.', 'client-portal'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>