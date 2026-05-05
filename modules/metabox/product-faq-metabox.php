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

class Product_FAQ_Metabox
{
    public function __construct()
    {
        add_action('add_meta_boxes', [$this, 'add_meta_boxes'], 30);
        add_action('save_post_product', [$this, 'save_metabox']);
    }

    public function add_meta_boxes()
    {
        add_meta_box('product_faq_metabox', __('Product FAQ', 'jelly-catalog'), [$this, 'render_metabox'], 'product', 'normal', 'default');
    }

    public function render_metabox($post)
    {
        $faqs = get_post_meta($post->ID, '_product_faqs', true);
        $faqs = is_array($faqs) ? $faqs : [];
        wp_nonce_field('jc_save_product_faq', 'jc_faq');
        // 使用通用 repeater 函数生成 FAQ 表单
        Utils::render_repeater_field([
            'id' => 'product_faqs',
            'name' => 'product_faqs',
            'title' => __('Product FAQ', 'jelly-catalog'),
            'items' => $faqs,
            'fields' => [
                [
                    'type' => 'text',
                    'name' => 'name',
                    'label' => __('Question:', 'jelly-catalog'),
                    'class' => 'repeater-item__key-input'
                ],
                [
                    'type' => 'textarea',
                    'name' => 'value',
                    'label' => __('Answer:', 'jelly-catalog'),
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
            !isset($_POST['jc_faq']) ||
            !wp_verify_nonce($_POST['jc_faq'], 'jc_save_product_faq')
        ) {
            return;
        }

        // 保存数据
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

            update_post_meta($post_id, '_product_faqs', $clean);
        } else {
            // 如果没有收到数据，说明用户可能删除了所有条目，需要清除元数据
            delete_post_meta($post_id, '_product_faqs');
        }
    }
}
