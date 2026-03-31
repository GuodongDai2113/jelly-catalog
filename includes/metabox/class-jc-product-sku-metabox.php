<?php

/**
 * includes\metabox\class-jc-product-sku-metabox.php
 * 
 * @see: https://jellydai.com
 * @author: Jelly Dai <daiguo1003@gmail.com>
 * @created: 2026.03.27
 */


if (! defined('ABSPATH')) exit; // 禁止直接访问

class JC_Product_SKU_Metabox
{
    public function __construct()
    {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'), 30);
        add_action('save_post_product', array($this, 'save_metabox'));
    }

    function add_meta_boxes()
    {
        add_meta_box('product_sku_metabox', __('Product Model', 'jelly-catalog'), array($this, 'render_metabox'), 'product', 'side', 'default');
    }

    public function render_metabox($post)
    {
        $sku = get_post_meta($post->ID, '_product_sku', true);
        wp_nonce_field('jc_save_product_sku', 'jc_sku_nonce');
        
        echo '<div class="product-sku-field">';
        echo '<input type="text" id="product_sku" name="product_sku" value="' . esc_attr($sku) . '" class="widefat" placeholder="' . esc_attr__('Enter product model or SKU', 'jelly-catalog') . '" />';
        echo '<p class="description">' . __('Enter the product model number or SKU (Stock Keeping Unit)', 'jelly-catalog') . '</p>';
        echo '</div>';
    }

    public function save_metabox($post_id)
    {
        // 检查是否为自动保存
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // 检查权限
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // 验证 nonce
        if (
            !isset($_POST['jc_sku_nonce']) ||
            !wp_verify_nonce($_POST['jc_sku_nonce'], 'jc_save_product_sku')
        ) {
            return;
        }

        // 保存数据
        if (isset($_POST['product_sku'])) {
            $sku = sanitize_text_field($_POST['product_sku']);
            if (!empty($sku)) {
                update_post_meta($post_id, '_product_sku', $sku);
            } else {
                delete_post_meta($post_id, '_product_sku');
            }
        }
    }
}
