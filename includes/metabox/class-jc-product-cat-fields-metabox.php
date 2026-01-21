<?php

/**
 * includes\metabox\class-jc-product-cat-fields-metabox.php
 * 
 * @see: https://jellydai.com
 * @author: Jelly Dai <daiguo1003@gmail.com>
 * @created: 2026.01.21 15:30
 */

if (! defined('ABSPATH')) exit; // 禁止直接访问

class JC_Product_Cat_Fields_Metabox
{
    public function __construct()
    {
        // add_action('product_cat_add_form_fields', array($this, 'add_category_fields'));
        add_action('product_cat_edit_form_fields', array($this, 'edit_category_fields'));
        add_action('edited_product_cat', array($this, 'save_category_fields'));
        // add_action('create_product_cat', array($this, 'save_category_fields'));
    }

    /**
     * 在添加产品分类页面添加字段
     */
    public function add_category_fields()
    {
        // Category H1 Title
        echo '<div class="form-field">';
        echo '<label for="category_h1_title">' . esc_html__('Category H1 Title', 'jelly-catalog') . '</label>';
        echo '<input type="text" name="category_h1_title" id="category_h1_title" value="">';
        echo '<p class="description">' . esc_html__('Enter the H1 title for this category.', 'jelly-catalog') . '</p>';
        echo '</div>';

        // Category Why Choose Us Title
        echo '<div class="form-field">';
        echo '<label for="category_why_choose_title">' . esc_html__('Why Choose Us Title', 'jelly-catalog') . '</label>';
        echo '<input type="text" name="category_why_choose_title" id="category_why_choose_title" value="">';
        echo '<p class="description">' . esc_html__('Enter the why choose us title for this category.', 'jelly-catalog') . '</p>';
        echo '</div>';

        // Category Why Choose Content
        echo '<div class="form-field">';
        echo '<label for="category_why_choose">' . esc_html__('Category Why Choose', 'jelly-catalog') . '</label>';
        wp_editor('', 'category_why_choose', array(
            'textarea_name' => 'category_why_choose',
            'textarea_rows' => 10,
            'media_buttons' => false,
            'teeny' => false,
            'tinymce' => array(
                'toolbar1' => 'formatselect,bold,italic,bullist,numlist,link,unlink,undo,redo',
                'toolbar2' => ''
            ),
            'quicktags' => true
        ));
        echo '<p class="description">' . esc_html__('Enter the why choose content for this category.', 'jelly-catalog') . '</p>';
        echo '</div>';

        // Category Advantages
        echo '<div class="form-field">';
        echo '<label for="category_advantages">' . esc_html__('Category Advantages', 'jelly-catalog') . '</label>';
        wp_editor('', 'category_advantages', array(
            'textarea_name' => 'category_advantages',
            'textarea_rows' => 10,
            'media_buttons' => false,
            'teeny' => false,
            'tinymce' => array(
                'toolbar1' => 'formatselect,bold,italic,bullist,numlist,link,unlink,undo,redo',
                'toolbar2' => ''
            ),
            'quicktags' => true
        ));
        echo '<p class="description">' . esc_html__('Enter the advantages for this category.', 'jelly-catalog') . '</p>';
        echo '</div>';
    }

    /**
     * 在编辑产品分类页面添加字段
     */
    public function edit_category_fields($term)
    {
        $category_h1_title = get_term_meta($term->term_id, 'category_h1_title', true);
        $category_why_choose_title = get_term_meta($term->term_id, 'category_why_choose_title', true);
        $category_why_choose = get_term_meta($term->term_id, 'category_why_choose', true);
        $category_advantages = get_term_meta($term->term_id, 'category_advantages', true);

        // Category H1 Title
        echo '<tr class="form-field">';
        echo '<th scope="row" valign="top">';
        echo '<label for="category_h1_title">' . esc_html__('Category H1 Title', 'jelly-catalog') . '</label>';
        echo '</th>';
        echo '<td>';
        echo '<input type="text" name="category_h1_title" id="category_h1_title" value="' . esc_attr($category_h1_title) . '">';
        echo '<p class="description">' . esc_html__('Enter the H1 title for this category.', 'jelly-catalog') . '</p>';
        echo '</td>';
        echo '</tr>';

        // Category Why Choose Us Title
        echo '<tr class="form-field">';
        echo '<th scope="row" valign="top">';
        echo '<label for="category_why_choose_title">' . esc_html__('Why Choose Us Title', 'jelly-catalog') . '</label>';
        echo '</th>';
        echo '<td>';
        echo '<input type="text" name="category_why_choose_title" id="category_why_choose_title" value="' . esc_attr($category_why_choose_title) . '">';
        echo '<p class="description">' . esc_html__('Enter the why choose us title for this category.', 'jelly-catalog') . '</p>';
        echo '</td>';
        echo '</tr>';

        // Category Why Choose Content
        echo '<tr class="form-field">';
        echo '<th scope="row" valign="top">';
        echo '<label for="category_why_choose">' . esc_html__('Category Why Choose', 'jelly-catalog') . '</label>';
        echo '</th>';
        echo '<td>';
        wp_editor($category_why_choose, 'category_why_choose', array(
            'textarea_name' => 'category_why_choose',
            'textarea_rows' => 10,
            'media_buttons' => false,
            'teeny' => false,
            'tinymce' => array(
                'toolbar1' => 'formatselect,bold,italic,bullist,numlist,link,unlink,undo,redo',
                'toolbar2' => ''
            ),
            'quicktags' => true
        ));
        echo '<p class="description">' . esc_html__('Enter the why choose content for this category.', 'jelly-catalog') . '</p>';
        echo '</td>';
        echo '</tr>';

        // Category Advantages
        echo '<tr class="form-field">';
        echo '<th scope="row" valign="top">';
        echo '<label for="category_advantages">' . esc_html__('Category Advantages', 'jelly-catalog') . '</label>';
        echo '</th>';
        echo '<td>';
        wp_editor($category_advantages, 'category_advantages', array(
            'textarea_name' => 'category_advantages',
            'textarea_rows' => 10,
            'media_buttons' => false,
            'teeny' => false,
            'tinymce' => array(
                'toolbar1' => 'formatselect,bold,italic,bullist,numlist,link,unlink,undo,redo',
                'toolbar2' => ''
            ),
            'quicktags' => true
        ));
        echo '<p class="description">' . esc_html__('Enter the advantages for this category.', 'jelly-catalog') . '</p>';
        echo '</td>';
        echo '</tr>';
    }

    /**
     * 保存产品分类字段
     */
    public function save_category_fields($term_id)
    {
        // Save Category H1 Title
        if (isset($_POST['category_h1_title'])) {
            $category_h1_title = sanitize_text_field($_POST['category_h1_title']);
            update_term_meta($term_id, 'category_h1_title', $category_h1_title);
        }

        // Save Category Why Choose Title
        if (isset($_POST['category_why_choose_title'])) {
            $category_why_choose_title = sanitize_text_field($_POST['category_why_choose_title']);
            update_term_meta($term_id, 'category_why_choose_title', $category_why_choose_title);
        }

        // Save Category Why Choose Content
        if (isset($_POST['category_why_choose'])) {
            $category_why_choose = wp_kses_post($_POST['category_why_choose']);
            update_term_meta($term_id, 'category_why_choose', $category_why_choose);
        }

        // Save Category Advantages
        if (isset($_POST['category_advantages'])) {
            $category_advantages = wp_kses_post($_POST['category_advantages']);
            update_term_meta($term_id, 'category_advantages', $category_advantages);
        }
    }
}