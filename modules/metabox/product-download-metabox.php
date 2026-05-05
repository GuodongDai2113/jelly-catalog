<?php

/**
 * includes\metabox\class-jc-product-download-metabox.php
 *
 * @see: https://jellydai.com
 * @author: Jelly Dai <daiguo1003@gmail.com>
 * @created: 2026.04.29
 */

namespace Jelly_Catalog\Modules\Metabox;

if (!defined('ABSPATH')) {
    exit;
} // 禁止直接访问

class Product_Download_Metabox
{
    public function __construct()
    {
        add_action('add_meta_boxes', [$this, 'add_meta_boxes'], 30);
        add_action('save_post_product', [$this, 'save_metabox']);
    }

    public function add_meta_boxes()
    {
        add_meta_box('product_download_metabox', __('Product Download', 'jelly-catalog'), [$this, 'render_metabox'], 'product', 'side', 'default');
    }

    public function render_metabox($post)
    {
        $product_file = jc_get_product_download_file($post->ID);
        $file_id = !empty($product_file['id']) ? absint($product_file['id']) : 0;
        $file_name = !empty($product_file['filename']) ? $product_file['filename'] : __('No file selected', 'jelly-catalog');
        $file_meta = $this->get_file_meta_text($product_file);

        wp_nonce_field('jc_save_product_download', 'jc_product_download_nonce');
        ?>
<div id="jc-product-download" class="jc-product-download-field"
    data-no-file="<?php echo esc_attr__('No file selected', 'jelly-catalog'); ?>">
    <input type="hidden" id="product_file" name="product_file" value="<?php echo esc_attr($file_id); ?>">

    <div class="jc-product-download-preview <?php echo $file_id ? '' : 'is-empty'; ?>">
        <span class="dashicons dashicons-media-default" aria-hidden="true"></span>
        <div class="jc-product-download-summary">
            <strong class="jc-product-download-name"><?php echo esc_html($file_name); ?></strong>
            <span class="description jc-product-download-meta"><?php echo esc_html($file_meta); ?></span>
        </div>
    </div>

    <p class="jc-product-download-actions">
        <button type="button" class="button jc-product-download-select"
            data-choose="<?php echo esc_attr__('Select Product File', 'jelly-catalog'); ?>"
            data-update="<?php echo esc_attr__('Use this file', 'jelly-catalog'); ?>">
            <?php esc_html_e('Select File', 'jelly-catalog'); ?>
        </button>
        <button type="button" class="button jc-product-download-remove"
            <?php echo $file_id ? '' : 'style="display: none;"'; ?>>
            <?php esc_html_e('Remove File', 'jelly-catalog'); ?>
        </button>
    </p>

    <p class="description">
        <?php esc_html_e('Product catalog, multiple files can be uploaded in compressed packages', 'jelly-catalog'); ?>
    </p>
</div>
<?php
    }

    public function save_metabox($post_id)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $nonce = isset($_POST['jc_product_download_nonce']) ? sanitize_text_field(wp_unslash($_POST['jc_product_download_nonce'])) : '';
        if (!$nonce || !wp_verify_nonce($nonce, 'jc_save_product_download')) {
            return;
        }

        $file_id = isset($_POST['product_file']) ? absint(wp_unslash($_POST['product_file'])) : 0;
        delete_post_meta($post_id, '_product_file');

        if ($file_id && 'attachment' === get_post_type($file_id)) {
            update_post_meta($post_id, 'product_file', $file_id);
            return;
        }

        delete_post_meta($post_id, 'product_file');
    }

    private function get_file_meta_text($product_file)
    {
        if (empty($product_file) || !is_array($product_file)) {
            return '';
        }

        $parts = [];

        if (!empty($product_file['filesize'])) {
            $parts[] = size_format(absint($product_file['filesize']));
        }

        if (!empty($product_file['mime_type'])) {
            $parts[] = $product_file['mime_type'];
        }

        return implode(' / ', $parts);
    }
}
