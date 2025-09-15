<?php

/**
 * includes\metabox\product-gallery-metabox.php
 * 
 * @see: https://jellydai.com
 * @author: Jelly Dai <d@jellydai.com>
 * @created: 2025.09.10 09:15
 */


if (! defined('ABSPATH')) exit; // 禁止直接访问

class JC_Product_Gallery_Metabox
{
    public function __construct()
    {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'), 30);
        add_action('save_post_product', array($this, 'save_metabox'));
    }

    function add_meta_boxes()
    {
        add_meta_box('product-images', __('Product Gallery', 'jelly-catalog'), array($this, 'render_metabox'), 'product', 'side', 'low');
    }

    public function render_metabox($post)
    {
        $gallery_image_ids = get_post_meta($post->ID, '_product_image_gallery', true);
        $gallery_image_ids = !empty($gallery_image_ids) ? explode(',', $gallery_image_ids) : array();
        echo wp_nonce_field('jc_save_product_image_gallery', 'jc_image_gallery');
        echo '<div id="jc-gallery">';
        echo '<ul class="product-images">';

        if (!empty($gallery_image_ids)) {
            foreach ($gallery_image_ids as $image_id) {
                $image_id = intval($image_id);
                if ($image_id) {
                    $image_url = wp_get_attachment_image_url($image_id, 'thumbnail');
                    if ($image_url) {
                        echo '<li class="image" data-attachment_id="' . esc_attr($image_id) . '">';
                        echo '<img src="' . esc_url($image_url) . '" alt="" />';
                        echo '<a href="#" class="actions delete" title="' . esc_attr__('Delete image', 'jelly-catalog') . '"></a>';
                        echo '</li>';
                    }
                }
            }
        }

        echo '</ul>';
        echo '<div class="jc-add-image hide-if-no-js">';
        echo '<p><a href="#" data-choose="' . esc_attr__('Add Images to Product Gallery', 'jelly-catalog') . '" 
              data-update="' . esc_attr__('Add to gallery', 'jelly-catalog') . '" 
              data-delete="' . esc_attr__('Delete image', 'jelly-catalog') . '" 
              data-text="' . esc_attr__('Delete', 'jelly-catalog') . '">'
            . __('Add product gallery images', 'jelly-catalog') . '</a></p>';
        echo '</div>';
        echo '<input type="hidden" id="product_image_gallery" name="product_image_gallery" value="' . esc_attr(implode(',', $gallery_image_ids)) . '" />';
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
            !isset($_POST['jc_image_gallery']) ||
            !wp_verify_nonce($_POST['jc_image_gallery'], 'jc_save_product_image_gallery')
        ) {
            return;
        }

        // 保存数据
        if (isset($_POST['product_image_gallery'])) {
            $gallery_images = sanitize_text_field($_POST['product_image_gallery']);
            // 可以进一步验证每个 ID 是否为有效附件
            update_post_meta($post_id, '_product_image_gallery', $gallery_images);
        }
    }
}
