<?php

/**
 * includes\metabox\class-jc-product-cat-faq-metabox.php
 * 
 * @see: https://jellydai.com
 * @author: Jelly Dai <daiguo1003@gmail.com>
 * @created: 2025.09.15 15:25
 */


if (! defined('ABSPATH')) exit; // 禁止直接访问

class JC_Product_Cat_FAQ_Metabox
{
    public function __construct()
    {
        add_action('product_cat_add_form_fields', array($this, 'add_category_faq_field'));
        add_action('product_cat_edit_form_fields', array($this, 'edit_category_faq_field'));
        add_action('edited_product_cat', array($this, 'save_category_faq_field'));
        add_action('create_product_cat', array($this, 'save_category_faq_field'));
    }

    /**
     * 在添加产品分类页面添加 FAQ 字段
     */
    public function add_category_faq_field()
    {
        echo '<div class="form-field">';
        echo '<label for="product_cat_faqs">' . __('Category FAQ', 'jelly-catalog') . '</label>';
        echo '<div id="product_cat_faqs_container">';
        // 使用通用 repeater 函数生成 FAQ 表单
        jc_render_repeater_field(array(
            'id' => 'product_cat_faqs',
            'name' => 'product_cat_faqs',
            'items' => array(),
            'fields' => array(
                array(
                    'type' => 'text',
                    'name' => 'name',
                    'label' => __('Question:', 'jelly-catalog'),
                    'class' => 'repeater-item__key-input'
                ),
                array(
                    'type' => 'textarea',
                    'name' => 'value',
                    'label' => __('Answer:', 'jelly-catalog'),
                    'class' => 'repeater-item__value-input'
                )
            )
        ));
        echo '</div>';
        echo '<p class="description">' . __('Add frequently asked questions for this product category.', 'jelly-catalog') . '</p>';
        echo '</div>';
    }

    /**
     * 在编辑产品分类页面添加 FAQ 字段
     */
    public function edit_category_faq_field($term)
    {
        $faqs = get_term_meta($term->term_id, 'product_cat_faqs', true);
        $faqs = is_array($faqs) ? $faqs : array();

        echo '<tr class="form-field">';
        echo '<th scope="row" valign="top">';
        echo '<label for="product_cat_faqs">' . __('Category FAQ', 'jelly-catalog') . '</label>';
        echo '</th>';
        echo '<td>';
        echo '<div id="product_cat_faqs_container">';
        // 使用通用 repeater 函数生成 FAQ 表单
        jc_render_repeater_field(array(
            'id' => 'product_cat_faqs',
            'name' => 'product_cat_faqs',
            'items' => $faqs,
            'fields' => array(
                array(
                    'type' => 'text',
                    'name' => 'name',
                    'label' => __('Question:', 'jelly-catalog'),
                    'class' => 'repeater-item__key-input'
                ),
                array(
                    'type' => 'textarea',
                    'name' => 'value',
                    'label' => __('Answer:', 'jelly-catalog'),
                    'class' => 'repeater-item__value-input'
                )
            )
        ));
        echo '</div>';
        echo '<p class="description">' . __('Add frequently asked questions for this product category.', 'jelly-catalog') . '</p>';
        echo '</td>';
        echo '</tr>';
    }

    /**
     * 保存产品分类 FAQ 字段
     */
    public function save_category_faq_field($term_id)
    {
        // 保存数据
        if (isset($_POST['product_cat_faqs'])) {
            $raw = $_POST['product_cat_faqs'] ?? array();
            $clean = array();

            foreach ($raw as $item) {
                $q = sanitize_text_field($item['name'] ?? '');
                $a = sanitize_textarea_field($item['value'] ?? '');
                if ($q || $a) {
                    $clean[] = array('name' => $q, 'value' => $a);
                }
            }

            update_term_meta($term_id, 'product_cat_faqs', $clean);
        } else {
            // 如果没有收到数据，说明用户可能删除了所有条目，需要清除元数据
            delete_term_meta($term_id, 'product_cat_faqs');
        }
    }
}
