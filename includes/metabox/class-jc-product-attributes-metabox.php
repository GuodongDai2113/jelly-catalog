<?php

/**
 * includes\metabox\class-jc-product-attributes-metabox.php
 * 
 * @see: https://jellydai.com
 * @author: Jelly Dai <daiguo1003@gmail.com>
 * @created: 2025.09.15 15:25
 */


if (! defined('ABSPATH')) exit; // 禁止直接访问

class JC_Product_Attributes_Metabox
{
    public function __construct()
    {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'), 30);
        add_action('save_post_product', array($this, 'save_metabox'));
    }

    function add_meta_boxes()
    {
        add_meta_box('product_attributes_metabox', __('Product Attributes', 'jelly-catalog'), array($this, 'render_metabox'), 'product', 'normal', 'default');
    }

    public function render_metabox($post)
    {
        $attributes = get_post_meta($post->ID, '_product_attributes', true);
        $attributes = is_array($attributes) ? $attributes : [];
        wp_nonce_field('jc_save_product_attributes', 'jc_attributes');

        // 使用通用 repeater 函数生成 FAQ 表单
        jc_render_repeater_field(array(
            'id' => 'product_attributes',
            'name' => 'product_attributes',
            'items' => $attributes,
            'fields' => array(
                array(
                    'type' => 'text',
                    'name' => 'name',
                    'label' => __('Name:', 'jelly-frame'),
                    'class' => 'repeater-item__key-input'
                ),
                array(
                    'type' => 'text',
                    'name' => 'value',
                    'label' => __('Value:', 'jelly-frame'),
                    'class' => 'repeater-item__value-input'
                )
            )
        ));
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
            !isset($_POST['jc_attributes']) ||
            !wp_verify_nonce($_POST['jc_attributes'], 'jc_save_product_attributes')
        ) {
            return;
        }

        // 保存数据
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
        } else {
            delete_post_meta($post_id, '_product_attributes');
        }
    }
}
