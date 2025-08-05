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
            add_meta_box('postexcerpt', __('Product short description', 'jelly_catalog'), array($this, 'short_description_box'), 'product', 'normal');
            add_meta_box('product-images', __('Product gallery', 'jelly_catalog'), array($this, 'product_images_box'), 'product', 'side', 'low');
        }
        add_meta_box('product_faq_metabox', __('Product FAQ', 'jelly-frame'), array($this, 'product_faq_metabox'), 'product', 'normal', 'default');
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
        $gallery_image_ids = get_post_meta($post->ID, '_jelly_product_image_gallery', true);
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
                        echo '<li><a href="#" class="delete" title="' . esc_attr__('Delete image', 'jelly_catalog') . '">X</a></li>';
                        echo '</ul>';
                        echo '</li>';
                    }
                }
            }
        }

        echo '</ul>';
        echo '<div class="jelly_add_product_images hide-if-no-js">';
        echo '<p><a href="#" data-choose="' . esc_attr__('Add Images to Product Gallery', 'jelly_catalog') . '" 
              data-update="' . esc_attr__('Add to gallery', 'jelly_catalog') . '" 
              data-delete="' . esc_attr__('Delete image', 'jelly_catalog') . '" 
              data-text="' . esc_attr__('Delete', 'jelly_catalog') . '">'
            . __('Add product gallery images', 'jelly_catalog') . '</a></p>';
        echo '</div>';
        echo '<input type="hidden" id="jelly_product_image_gallery" name="jelly_product_image_gallery" value="' . esc_attr(implode(',', $gallery_image_ids)) . '" />';
        echo '</div>';
    }

    public function product_faq_metabox($post)
    {
        $faqs = get_post_meta($post->ID, 'product_faqs', true);
        $faqs = is_array($faqs) ? $faqs : [];

        // wp_nonce_field('save_product_faqs', 'product_faqs_nonce');

        echo '<div class="jelly-faq-wrapper">';
        foreach ($faqs as $index => $faq) {
            echo '<div class="faq-item">';
            echo '<span class="item-number">' . esc_html($index) . '</span>';
            echo '<div class="faq-item__question">';
            echo '<label for="product_faqs[' . $index . '][question]">';
            esc_html_e('Question:', 'jelly-frame');
            echo '</label>';
            echo '<input class="faq-item__question-input" type="text" id="product_faqs[' . $index . '][question]" name="product_faqs[' . $index . '][question]" value="' . esc_attr($faq['question']) . '" />';
            echo '</div>';
            echo '<div class="faq-item__answer">';
            echo '<label for="product_faqs[' . $index . '][answer]">';
            esc_html_e('Answer:', 'jelly-frame');
            echo '</label>';
            echo '<textarea class="faq-item__answer-input" id="product_faqs[' . $index . '][answer]" name="product_faqs[' . $index . '][answer]">' . esc_textarea($faq['answer']) . '</textarea>';
            echo '</div>';
            echo '<div class="faq-item__remove">';
            echo '<button type="button" class="button remove-item">';
            esc_html_e('Remove FAQ', 'jelly-frame');
            echo '</button>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
        echo '<button type="button" class="button" id="jelly-add-faq">' . __('添加 FAQ') . '</button>';
    }
    
    public function save_meta_boxes($post_id)
    {
        // 验证nonce
        // if (!isset($_POST['product_gallery_nonce']) || !wp_verify_nonce($_POST['product_gallery_nonce'], 'product_gallery_save')) {
        //     return;
        // }

        // 保存产品画廊图片
        if (isset($_POST['jelly_product_image_gallery'])) {
            $gallery_images = sanitize_text_field($_POST['jelly_product_image_gallery']);
            update_post_meta($post_id, '_jelly_product_image_gallery', $gallery_images);
        }

        // 保存FAQ
        // if (!isset($_POST['product_faqs_nonce']) || !wp_verify_nonce($_POST['product_faqs_nonce'], 'save_product_faqs')) {
        //     return;
        // }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

        if (!current_user_can('edit_post', $post_id)) return;

        $raw = $_POST['product_faqs'] ?? [];
        $clean = [];

        foreach ($raw as $item) {
            $q = sanitize_text_field($item['question'] ?? '');
            $a = sanitize_textarea_field($item['answer'] ?? '');
            if ($q || $a) {
                $clean[] = ['question' => $q, 'answer' => $a];
            }
        }

        update_post_meta($post_id, 'product_faqs', $clean);
    }
}
