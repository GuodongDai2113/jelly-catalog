<?php

/**
 * includes\metabox\class-jc-product-postexcerpt-metabox
 *
 * @see: https://jellydai.com
 * @author: Jelly Dai <daiguo1003@gmail.com>
 * @created: 2025.09.15 15:30
 */

if (!defined('ABSPATH')) {
    exit;
} // 禁止直接访问

class JC_Product_Postexcerpt_Metabox
{
    public function __construct()
    {
        add_action('add_meta_boxes', [$this, 'add_meta_boxes'], 30);
    }

    public function add_meta_boxes()
    {
        add_meta_box('postexcerpt', __('Product Short Description', 'jelly-catalog'), [$this, 'render_metabox'], 'product', 'normal');
    }

    public function render_metabox($post)
    {
        $settings = [
            'textarea_name' => 'excerpt',
            'media_buttons' => false,
            'quicktags' => ['buttons' => 'em,strong,link'],
            'tinymce' => [
                'theme_advanced_buttons1' => 'bold,italic,strikethrough,separator,bullist,numlist,separator,blockquote,separator,justifyleft,justifycenter,justifyright,separator,link,unlink,separator,undo,redo,separator',
                'theme_advanced_buttons2' => '',
            ],
        ];

        wp_editor(htmlspecialchars_decode($post->post_excerpt, ENT_QUOTES), 'excerpt', $settings);
    }
}
