<?php

/**
 * includes\metabox\product-gallery-metabox.php
 *
 * @see: https://jellydai.com
 * @author: Jelly Dai <daiguo1003@gmail.com>
 * @created: 2025.09.10 09:15
 */

namespace Jelly_Catalog\Modules\Metabox;

if (!defined('ABSPATH')) {
    exit;
} // 禁止直接访问

class Product_Gallery_Metabox
{
    /**
     * 注册 metabox 与保存钩子。
     */
    public function __construct()
    {
        add_action('add_meta_boxes', [$this, 'add_meta_boxes'], 30);
        add_action('save_post_product', [$this, 'save_metabox']);
    }

    /**
     * 注册产品相册 metabox。
     */
    public function add_meta_boxes()
    {
        add_meta_box('product-images', __('Product Gallery', 'jelly-catalog'), [$this, 'render_metabox'], 'product', 'side', 'low');
    }

    /**
     * 渲染产品相册编辑器。
     */
    public function render_metabox($post)
    {
        $gallery_image_ids = get_post_meta($post->ID, '_product_image_gallery', true);
        $gallery_image_ids = !empty($gallery_image_ids) ? explode(',', $gallery_image_ids) : [];
        wp_nonce_field('jc_save_product_image_gallery', 'jc_image_gallery');
        echo '<p class="description">' . __('Upload multiple product images and drag to reorder the gallery', 'jelly-catalog') . '</p>';
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

        echo $this->render_add_image_trigger();
        echo '</ul>';
        echo '<input type="hidden" id="product_image_gallery" name="product_image_gallery" value="' . esc_attr(implode(',', $gallery_image_ids)) . '" />';
        echo '</div>';
    }

    /**
     * 渲染始终可见的“添加图片”方框。
     */
    protected function render_add_image_trigger()
    {
        return sprintf(
            '<li class="jc-add-image hide-if-no-js">
                <a href="#"
                    class="jc-add-image__trigger"
                    data-choose="%1$s"
                    data-update="%2$s"
                    data-delete="%3$s"
                    data-text="%4$s"
                    aria-label="%5$s"
                    title="%5$s">
                    <span class="dashicons dashicons-plus" aria-hidden="true"></span>
                    <span class="screen-reader-text">%5$s</span>
                </a>
            </li>',
            esc_attr__('Add Images to Product Gallery', 'jelly-catalog'),
            esc_attr__('Add to gallery', 'jelly-catalog'),
            esc_attr__('Delete image', 'jelly-catalog'),
            esc_attr__('Delete', 'jelly-catalog'),
            esc_attr__('Add product gallery images', 'jelly-catalog')
        );
    }

    /**
     * 保存产品相册字段。
     */
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
