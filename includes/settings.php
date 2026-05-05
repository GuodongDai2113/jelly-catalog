<?php

/**
 * includes/class-jc-settings.php
 *
 * @see https://jellydai.com
 * @author Jelly Dai
 * @created 2025.11.10 15:30
 */

namespace Jelly_Catalog;

if (!defined('ABSPATH')) {
    exit; // 禁止直接访问
}

/**
 * 设置管理类
 */
class Settings
{
    /**
     * 初始化设置
     */
    public function __construct()
    {
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_init', [$this, 'register_permalink_settings']);
        add_action('admin_init', [$this, 'save_permalink_settings'], 10);
    }

    /**
     * 注册所有设置
     */
    public function register_settings()
    {
        // 注册设置段
        add_settings_section(
            'jelly_catalog_products_section',
            __('Products Settings', 'jelly-catalog'),
            [$this, 'render_products_section'],
            'reading'
        );

        // 添加产品页面设置字段
        add_settings_field(
            'page_for_products',
            __('Products Page', 'jelly-catalog'),
            [$this, 'render_page_for_products_select'],
            'reading',
            'jelly_catalog_products_section',
            ['label_for' => 'page_for_products']
        );

        // 添加每页产品数量设置字段
        add_settings_field(
            'products_per_page',
            __('Products Per Page', 'jelly-catalog'),
            [$this, 'render_products_per_page_input'],
            'reading',
            'jelly_catalog_products_section',
            ['label_for' => 'products_per_page']
        );

        // 添加产品排序依据设置字段
        add_settings_field(
            'products_orderby',
            __('Products Order By', 'jelly-catalog'),
            [$this, 'render_products_orderby_select'],
            'reading',
            'jelly_catalog_products_section',
            ['label_for' => 'products_orderby']
        );

        // 添加产品排序方向设置字段
        add_settings_field(
            'products_order',
            __('Products Order', 'jelly-catalog'),
            [$this, 'render_products_order_select'],
            'reading',
            'jelly_catalog_products_section',
            ['label_for' => 'products_order']
        );

        // 注册设置选项
        register_setting('reading', 'page_for_products');
        register_setting('reading', 'products_per_page', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 10
        ]);
        register_setting('reading', 'products_orderby', [
            'type' => 'string',
            'sanitize_callback' => [$this, 'sanitize_products_orderby'],
            'default' => 'date'
        ]);
        register_setting('reading', 'products_order', [
            'type' => 'string',
            'sanitize_callback' => [$this, 'sanitize_products_order'],
            'default' => 'DESC'
        ]);
    }

    /**
     * 清理产品排序字段。
     *
     * @param string $value 设置值。
     * @return string
     */
    public function sanitize_products_orderby($value)
    {
        $value = sanitize_key($value);
        $allowed = ['date', 'title', 'menu_order', 'rand', 'modified'];

        return in_array($value, $allowed, true) ? $value : 'date';
    }

    /**
     * 清理产品排序方向。
     *
     * @param string $value 设置值。
     * @return string
     */
    public function sanitize_products_order($value)
    {
        $value = strtoupper(sanitize_text_field($value));

        return in_array($value, ['ASC', 'DESC'], true) ? $value : 'DESC';
    }

    /**
     * 渲染产品设置段描述
     */
    public function render_products_section()
    {
        echo '<p>' . __('Configure how your products are displayed on your site.', 'jelly-catalog') . '</p>';
    }

    /**
     * 渲染产品页面选择下拉框
     */
    public function render_page_for_products_select()
    {
        $selected_page = get_option('page_for_products', 0);

        wp_dropdown_pages([
            'name' => 'page_for_products',
            'selected' => $selected_page,
            'show_option_none' => __('— Select a page —', 'jelly-catalog'),
            'option_none_value' => '0',
        ]);

        echo '<p class="description">' . __('Select the page where your products will be displayed.', 'jelly-catalog') . '</p>';
    }

    /**
     * 渲染每页产品数量输入框
     */
    public function render_products_per_page_input()
    {
        $products_per_page = get_option('products_per_page', 16);

        echo '<input name="products_per_page" type="number" step="1" min="1" id="products_per_page" value="' . esc_attr($products_per_page) . '" class="small-text" />';
        echo '<p class="description">' . __('Set the number of products to display per page.', 'jelly-catalog') . '</p>';
    }

    /**
     * 渲染产品排序依据下拉选择框
     */
    public function render_products_orderby_select()
    {
        $orderby_options = [
            'date' => __('Date', 'jelly-catalog'),
            'title' => __('Title', 'jelly-catalog'),
            'menu_order' => __('Menu Order', 'jelly-catalog'),
            'rand' => __('Random', 'jelly-catalog'),
            'modified' => __('Last Modified', 'jelly-catalog')
        ];

        $selected_orderby = get_option('products_orderby', 'date');

        echo '<select name="products_orderby" id="products_orderby">';
        foreach ($orderby_options as $value => $label) {
            echo '<option value="' . esc_attr($value) . '"' . selected($selected_orderby, $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('Select how products should be ordered.', 'jelly-catalog') . '</p>';
    }

    /**
     * 渲染产品排序方向选择框
     */
    public function render_products_order_select()
    {
        $order_options = [
            'ASC' => __('Ascending', 'jelly-catalog'),
            'DESC' => __('Descending', 'jelly-catalog')
        ];

        $selected_order = get_option('products_order', 'DESC');

        echo '<select name="products_order" id="products_order">';
        foreach ($order_options as $value => $label) {
            echo '<option value="' . esc_attr($value) . '"' . selected($selected_order, $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('Select the direction of product ordering.', 'jelly-catalog') . '</p>';
    }

    /**
     * 注册固定链接设置
     */
    public function register_permalink_settings()
    {
        add_settings_section(
            'jelly_catalog_permalink_section',
            __('Product Permalinks', 'jelly-catalog'),
            [$this, 'render_permalink_section'],
            'permalink'
        );

        add_settings_field(
            'jelly_catalog_product_base',
            __('Product Base', 'jelly-catalog'),
            [$this, 'render_product_base_input'],
            'permalink',
            'jelly_catalog_permalink_section'
        );

        add_settings_field(
            'jelly_catalog_category_base',
            __('Category Base', 'jelly-catalog'),
            [$this, 'render_category_base_input'],
            'permalink',
            'jelly_catalog_permalink_section'
        );
    }

    /**
     * 渲染固定链接设置段
     */
    public function render_permalink_section()
    {
        echo '<p>' . __('Configure the permalink structure for your product catalog.', 'jelly-catalog') . '</p>';
    }

    /**
     * 渲染产品基础URL输入框
     */
    public function render_product_base_input()
    {
        $permalinks = Utils::get_permalink_structure();
        $product_base = $permalinks['product_base'];

        $options = [
            Utils::PRODUCT_BASE_PRODUCTS => __('products', 'jelly-catalog'),
            Utils::PRODUCT_BASE_PRODUCT => __('product', 'jelly-catalog'),
            Utils::PRODUCT_BASE_CATEGORY => __('Primary category slug', 'jelly-catalog'),
        ];

        foreach ($options as $value => $label) {
            echo '<label style="display:block;margin-bottom:8px;">';
            echo '<input name="jelly_catalog_product_base" type="radio" value="' . esc_attr($value) . '"' . checked($product_base, $value, false) . ' />';
            echo ' <code>' . esc_html($label) . '</code>';
            echo '</label>';
        }

        echo '<p class="description">' . __('Choose whether product URLs use /products/, /product/, or the product primary category path.', 'jelly-catalog') . '</p>';
    }

    /**
     * 渲染产品分类基础URL输入框
     */
    public function render_category_base_input()
    {
        $permalinks = Utils::get_permalink_structure();
        $category_base = $permalinks['category_base'];

        $options = [
            Utils::CATEGORY_BASE_DEFAULT => __('product-category', 'jelly-catalog'),
            Utils::CATEGORY_BASE_NONE => __('Category slug', 'jelly-catalog'),
        ];

        foreach ($options as $value => $label) {
            echo '<label style="display:block;margin-bottom:8px;">';
            echo '<input name="jelly_catalog_category_base" type="radio" value="' . esc_attr($value) . '"' . checked($category_base, $value, false) . ' />';
            echo ' <code>' . esc_html($label) . '</code>';
            echo '</label>';
        }

        echo '<p class="description">' . __('Choose whether category URLs use /product-category/ or the category hierarchy directly.', 'jelly-catalog') . '</p>';
    }

    /**
     * 保存固定链接设置
     */
    public function save_permalink_settings()
    {
        if (!is_admin()) {
            return;
        }

        if (!isset($_POST['jelly_catalog_product_base']) && !isset($_POST['jelly_catalog_category_base'])) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_POST['_wpnonce'])) {
            check_admin_referer('update-permalink');
        }

        $permalinks = Utils::get_permalink_structure();
        $updated_permalinks = $permalinks;

        if (isset($_POST['jelly_catalog_product_base'])) {
            $product_base = sanitize_text_field(wp_unslash($_POST['jelly_catalog_product_base']));
            $allowed_product_bases = [
                Utils::PRODUCT_BASE_PRODUCTS,
                Utils::PRODUCT_BASE_PRODUCT,
                Utils::PRODUCT_BASE_CATEGORY,
            ];

            $updated_permalinks['product_base'] = in_array($product_base, $allowed_product_bases, true)
                ? $product_base
                : Utils::PRODUCT_BASE_PRODUCTS;
        }

        if (isset($_POST['jelly_catalog_category_base'])) {
            $category_base = sanitize_text_field(wp_unslash($_POST['jelly_catalog_category_base']));
            $allowed_category_bases = [
                Utils::CATEGORY_BASE_DEFAULT,
                Utils::CATEGORY_BASE_NONE,
            ];

            $updated_permalinks['category_base'] = in_array($category_base, $allowed_category_bases, true)
                ? $category_base
                : Utils::CATEGORY_BASE_DEFAULT;
        }

        if ($updated_permalinks !== $permalinks) {
            update_option('jelly_catalog_permalinks', [
                'product_base' => $updated_permalinks['product_base'],
                'category_base' => $updated_permalinks['category_base'],
            ]);
            update_option('jelly_catalog_queue_flush_rewrite', 1);
        }
    }
}
