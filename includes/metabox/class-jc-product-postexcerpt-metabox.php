<?php

/**
 * includes\metabox\class-jc-product-postexcerpt-metabox
 * 
 * @see: https://jellydai.com
 * @author: Jelly Dai <daiguo1003@gmail.com>
 * @created: 2025.09.15 15:30
 */


if (! defined('ABSPATH')) exit; // 禁止直接访问

class JC_Product_Postexcerpt_Metabox
{
    public function __construct()
    {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'), 30);
    }

    function add_meta_boxes()
    {
        add_meta_box('postexcerpt', __('Product Short Description', 'jelly-catalog'), array($this, 'render_metabox'), 'product', 'normal');
    }

    public function render_metabox($post)
    {
        ?>
        <div class="short-description-wrapper">
            <div class="character-count">
                <span class="count-label"><?php esc_html_e('Characters:', 'jelly-catalog'); ?></span>
                <code><span id="excerpt-character-count" class="count-value"><?php echo strlen($post->post_excerpt); ?></span></code>
                <span class="count-separator">/</span>
                <code><span class="count-max">160</span></code>
            </div>
            <?php
            $settings = array(
                'textarea_name' => 'excerpt',
                'quicktags'     => array('buttons' => 'em,strong,link'),
                'tinymce'       => array(
                    'theme_advanced_buttons1' => 'bold,italic,strikethrough,separator,bullist,numlist,separator,blockquote,separator,justifyleft,justifycenter,justifyright,separator,link,unlink,separator,undo,redo,separator',
                    'theme_advanced_buttons2' => '',
                ),
            );

            wp_editor(htmlspecialchars_decode($post->post_excerpt, ENT_QUOTES), 'excerpt', $settings);
            ?>
        </div>
<?php
    }
}
