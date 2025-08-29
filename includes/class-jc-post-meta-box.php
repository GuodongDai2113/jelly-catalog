<?php

/**
 * includes\class-jc-post-meta-box.php
 * 
 * @see: https://jellydai.com
 * @author: Jelly Dai <d@jellydai.com>
 * @created: 2025.07.29 14:47
 */


if (! defined('ABSPATH')) exit; // 禁止直接访问

/**
 * 请填写描述
 */
class JC_Post_Meta_Box
{
    public function __construct()
    {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'), 30);
        add_action('save_post', array($this, 'save_meta_boxes'));
    }

    function add_meta_boxes()
    {
        if (!JELLY_CATALOG_WC_ACTIVE) {
            add_meta_box('postexcerpt', __('Product Short Description', 'jelly-catalog'), array($this, 'short_description_box'), 'product', 'normal');
            add_meta_box('product-images', __('Product Gallery', 'jelly-catalog'), array($this, 'product_images_box'), 'product', 'side', 'low');
            add_meta_box('product_attributes_metabox', __('Product Attributes', 'jelly-catalog'), array($this, 'product_attributes_metabox'), 'product', 'normal', 'default');
        }
        add_meta_box('product_faq_metabox', __('Product FAQ', 'jelly-catalog'), array($this, 'product_faq_metabox'), 'product', 'normal', 'default');
        add_meta_box('product_video_url', __('Product Video', 'jelly-catalog'), array($this, 'product_video_url_box'), 'product', 'normal', 'default');
    }

    public function short_description_box($post)
    {

        $settings = array(
            'textarea_name' => 'excerpt',
            'quicktags'     => array('buttons' => 'em,strong,link'),
            'tinymce'       => array(
                'theme_advanced_buttons1' => 'bold,italic,strikethrough,separator,bullist,numlist,separator,blockquote,separator,justifyleft,justifycenter,justifyright,separator,link,unlink,separator,undo,redo,separator',
                'theme_advanced_buttons2' => '',
            ),
            'editor_css'    => '<style>#wp-excerpt-editor-container .wp-editor-area{height:175px; width:100%;}</style>',
        );

        wp_editor(htmlspecialchars_decode($post->post_excerpt, ENT_QUOTES), 'excerpt', $settings);
    }

    public function product_images_box($post)
    {
        // 获取产品画廊图片ID数组
        $gallery_image_ids = get_post_meta($post->ID, '_product_image_gallery', true);
        $gallery_image_ids = !empty($gallery_image_ids) ? explode(',', $gallery_image_ids) : array();

        // wp_nonce_field('product_gallery_save', 'product_gallery_nonce');

        echo '<div id="jelly_product_images_container">';
        echo '<ul class="product_images">';

        if (!empty($gallery_image_ids)) {
            foreach ($gallery_image_ids as $image_id) {
                $image_id = intval($image_id);
                if ($image_id) {
                    $image_url = wp_get_attachment_image_url($image_id, 'thumbnail');
                    if ($image_url) {
                        echo '<li class="image" data-attachment_id="' . esc_attr($image_id) . '">';
                        echo '<img src="' . esc_url($image_url) . '" alt="" />';
                        echo '<ul class="actions">';
                        echo '<li><a href="#" class="delete" title="' . esc_attr__('Delete image', 'jelly-catalog') . '">X</a></li>';
                        echo '</ul>';
                        echo '</li>';
                    }
                }
            }
        }

        echo '</ul>';
        echo '<div class="jelly_add_product_images hide-if-no-js">';
        echo '<p><a href="#" data-choose="' . esc_attr__('Add Images to Product Gallery', 'jelly-catalog') . '" 
              data-update="' . esc_attr__('Add to gallery', 'jelly-catalog') . '" 
              data-delete="' . esc_attr__('Delete image', 'jelly-catalog') . '" 
              data-text="' . esc_attr__('Delete', 'jelly-catalog') . '">'
            . __('Add product gallery images', 'jelly-catalog') . '</a></p>';
        echo '</div>';
        echo '<input type="hidden" id="product_image_gallery" name="product_image_gallery" value="' . esc_attr(implode(',', $gallery_image_ids)) . '" />';
        echo '</div>';
    }

    public function product_faq_metabox($post)
    {
        $faqs = get_post_meta($post->ID, 'product_faqs', true);
        $faqs = is_array($faqs) ? $faqs : [];

        // wp_nonce_field('save_product_faqs', 'product_faqs_nonce');

        echo '<div class="jelly-kv-wrapper" data-key="product_faqs"  data-key-name="name">';
        foreach ($faqs as $index => $faq) {
            echo '<div class="kv-item">';
            echo '<span class="item-number">' . esc_html($index) . '</span>';
            echo '<div class="kv-item__key">';
            echo '<label for="product_faqs[' . $index . '][name]">';
            esc_html_e('Question:', 'jelly-frame');
            echo '</label>';
            echo '<input class="kv-item__key-input" type="text" id="product_faqs[' . $index . '][name]" name="product_faqs[' . $index . '][name]" value="' . esc_attr($faq['name']) . '" />';
            echo '</div>';
            echo '<div class="kv-item__value">';
            echo '<label for="product_faqs[' . $index . '][value]">';
            esc_html_e('Answer:', 'jelly-frame');
            echo '</label>';
            echo '<textarea class="kv-item__value-input" id="product_faqs[' . $index . '][value]" name="product_faqs[' . $index . '][value]">' . esc_textarea($faq['value']) . '</textarea>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
    }

    public function product_video_url_box($post)
    {
        $video_url = get_post_meta($post->ID, 'video_url', true);
        wp_nonce_field('save_product_video_url', 'product_video_url_nonce');

        echo '<p>';
        echo '<label for="product_video_url">' . __('Video URL', 'jelly-catalog') . ':</label>';
        echo '<input type="url" id="product_video_url" name="product_video_url" value="' . esc_url($video_url) . '" class="large-text" />';
        echo '<span class="description">' . __('Enter the full URL to your product video (YouTube, Vimeo, etc.)', 'jelly-catalog') . '</span>';
        echo '</p>';
    }

    /**
     * 显示产品属性 metabox
     */
    public function product_attributes_metabox($post)
    {
        $attributes = get_post_meta($post->ID, '_product_attributes', true);
        $attributes = is_array($attributes) ? $attributes : [];

        wp_nonce_field('save_product_attributes', 'product_attributes_nonce');

        echo '<div class="jelly-kv-wrapper" data-key="product_attributes" data-key-name="name">';
        echo '<p class="description">' . __('添加产品的属性信息，如尺寸、重量、材质等。', 'jelly-catalog') . '</p>';
        foreach ($attributes as $index => $attribute) {
            echo '<div class="kv-item">';
            echo '<div class="kv-item__key">';
            echo '<label for="product_attributes[' . $index . '][key]">';
            esc_html_e('属性名称:', 'jelly-catalog');
            echo '</label>';
            echo '<input class="kv-item__key-input" type="text" id="product_attributes[' . $index . '][name]" name="product_attributes[' . $index . '][name]" value="' . esc_attr($attribute['name']) . '" />';
            echo '</div>';
            echo '<div class="kv-item__value">';
            echo '<label for="product_attributes[' . $index . '][value]">';
            esc_html_e('属性值:', 'jelly-catalog');
            echo '</label>';
            echo '<input class="kv-item__value-input" type="text" id="product_attributes[' . $index . '][value]" name="product_attributes[' . $index . '][value]" value="' . esc_attr($attribute['value']) . '" />';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
    }

    public function save_meta_boxes($post_id)
    {

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

        if (!current_user_can('edit_post', $post_id)) return;

        // 保存产品画廊图片
        if (isset($_POST['product_image_gallery'])) {
            $gallery_images = sanitize_text_field($_POST['product_image_gallery']);
            update_post_meta($post_id, '_product_image_gallery', $gallery_images);
        }

        // 保存视频 URL
        if (isset($_POST['product_video_url']) && wp_verify_nonce($_POST['product_video_url_nonce'], 'save_product_video_url')) {
            $video_url = esc_url_raw($_POST['product_video_url']);
            update_post_meta($post_id, 'video_url', $video_url);
        }

        if (isset($_POST['product_faqs'])) {
            $raw = $_POST['product_faqs'] ?? [];
            $clean = [];

            foreach ($raw as $item) {
                $q = sanitize_text_field($item['name'] ?? '');
                $a = sanitize_textarea_field($item['value'] ?? '');
                if ($q || $a) {
                    $clean[] = ['name' => $q, 'value' => $a];
                }
            }

            update_post_meta($post_id, 'product_faqs', $clean);
        }

        if (isset($_POST['product_attributes'])) {
            $raw = $_POST['product_attributes'] ?? [];
            $clean = [];
            foreach ($raw as $item) {
                $name = sanitize_text_field($item['name'] ?? '');
                $value = sanitize_text_field($item['value'] ?? '');
                if ($name && $value) {
                    $clean[] = ['name' => $name, 'value' => $value];
                }
            }

            update_post_meta($post_id, '_product_attributes', $clean);
        }
    }
}
