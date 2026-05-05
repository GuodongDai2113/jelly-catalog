<?php

/**
 * includes\metabox\class-jc-product-attributes-metabox.php
 *
 * @see: https://jellydai.com
 * @author: Jelly Dai <daiguo1003@gmail.com>
 * @created: 2025.09.15 15:25
 */

namespace Jelly_Catalog\Modules\Metabox;

use Jelly_Catalog\Utils;

if (!defined('ABSPATH')) {
    exit;
} // 禁止直接访问

class Product_Attributes_Metabox
{
    public function __construct()
    {
        add_action('add_meta_boxes', [$this, 'add_meta_boxes'], 30);
        add_action('save_post_product', [$this, 'save_metabox']);
    }

    public function add_meta_boxes()
    {
        add_meta_box('product_attributes_metabox', __('Product Attributes', 'jelly-catalog'), [$this, 'render_metabox'], 'product', 'normal', 'default');
    }

    public function render_metabox($post)
    {
        $attributes = get_post_meta($post->ID, '_product_attributes', true);
        $attributes = is_array($attributes) ? $attributes : [];
        wp_nonce_field('jc_save_product_attributes', 'jc_attributes');

        Utils::render_repeater_field([
            'id' => 'product_attributes',
            'name' => 'product_attributes',
            'title' => __('Product Attributes', 'jelly-catalog'),
            'items' => $attributes,
            'fields' => [
                [
                    'type' => 'text',
                    'name' => 'name',
                    'label' => __('Name:', 'jelly-catalog'),
                    'class' => 'repeater-item__key-input'
                ],
                [
                    'type' => 'text',
                    'name' => 'value',
                    'label' => __('Value:', 'jelly-catalog'),
                    'class' => 'repeater-item__value-input'
                ]
            ]
        ]);
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
