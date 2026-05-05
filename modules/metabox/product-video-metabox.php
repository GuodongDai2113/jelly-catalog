<?php

/**
 * includes\metabox\class-jc-product-attributes-metabox.php
 *
 * @see: https://jellydai.com
 * @author: Jelly Dai <daiguo1003@gmail.com>
 * @created: 2025.09.15 15:25
 */

namespace Jelly_Catalog\Modules\Metabox;

if (!defined('ABSPATH')) {
    exit;
} // 禁止直接访问

class Product_Video_Metabox
{
    public function __construct()
    {
        add_action('add_meta_boxes', [$this, 'add_meta_boxes'], 30);
        add_action('save_post_product', [$this, 'save_metabox']);
    }

    public function add_meta_boxes()
    {
        add_meta_box('product_videourl_metabox', __('Product Video', 'jelly-catalog'), [$this, 'render_metabox'], 'product', 'normal', 'default');
    }

    public function render_metabox($post)
    {
        $video_url = get_post_meta($post->ID, '_product_videourl', true);
        wp_nonce_field('jc_save_product_videourl', 'jc_videourl');
        echo '<p>';
        echo '<label for="product_video_url">' . __('Video URL', 'jelly-catalog') . ':</label>';
        echo '<input type="url" id="product_videourl" name="product_videourl" value="' . esc_url($video_url) . '" class="large-text" />';
        echo '<span class="description">' . __('Enter the full URL to your product video (YouTube, Vimeo, etc.)', 'jelly-catalog') . '</span>';
        echo '</p>';
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
            !isset($_POST['jc_videourl']) ||
            !wp_verify_nonce($_POST['jc_videourl'], 'jc_save_product_videourl')
        ) {
            return;
        }

        // 保存数据
        if (isset($_POST['product_videourl'])) {
            $video_url = esc_url_raw($_POST['product_videourl']);
            update_post_meta($post_id, '_product_videourl', $video_url);
        }
    }
}
