<?php

/**
 * includes/class-jc-settings.php
 *
 * @see https://jellydai.com
 * @author Jelly Dai
 * @created 2025.11.10 15:30
 */

if (! defined('ABSPATH')) {
    exit; // 禁止直接访问
}

/**
 * 设置管理类
 */
class JC_Settings
{

    private $permalinks = array();
    /**
     * 初始化设置
     */
    public static function init()
    {
        add_action('admin_init', array(__CLASS__, 'register_settings'));
        add_action('admin_init', array(__CLASS__, 'register_permalink_settings'));
        add_action('admin_init', array(__CLASS__, 'save_permalink_settings'), 10);
    }

    /**
     * 注册所有设置
     */
    public static function register_settings()
    {
        // 注册设置段
        add_settings_section(
            'jelly_catalog_products_section',
            __('Products Settings', 'jelly-catalog'),
            array(__CLASS__, 'render_products_section'),
            'reading'
        );

        // 添加产品页面设置字段
        add_settings_field(
            'page_for_products',
            __('Products Page', 'jelly-catalog'),
            array(__CLASS__, 'render_page_for_products_select'),
            'reading',
            'jelly_catalog_products_section',
            array('label_for' => 'page_for_products')
        );

        // 添加每页产品数量设置字段
        add_settings_field(
            'products_per_page',
            __('Products Per Page', 'jelly-catalog'),
            array(__CLASS__, 'render_products_per_page_input'),
            'reading',
            'jelly_catalog_products_section',
            array('label_for' => 'products_per_page')
        );

        // 添加产品排序依据设置字段
        add_settings_field(
            'products_orderby',
            __('Products Order By', 'jelly-catalog'),
            array(__CLASS__, 'render_products_orderby_select'),
            'reading',
            'jelly_catalog_products_section',
            array('label_for' => 'products_orderby')
        );

        // 添加产品排序方向设置字段
        add_settings_field(
            'products_order',
            __('Products Order', 'jelly-catalog'),
            array(__CLASS__, 'render_products_order_select'),
            'reading',
            'jelly_catalog_products_section',
            array('label_for' => 'products_order')
        );

        // 注册设置选项
        register_setting('reading', 'page_for_products');
        register_setting('reading', 'products_per_page', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 10
        ));
        register_setting('reading', 'products_orderby', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'date'
        ));
        register_setting('reading', 'products_order', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'DESC'
        ));
    }

    /**
     * 渲染产品设置段描述
     */
    public static function render_products_section()
    {
        echo '<p>' . __('Configure how your products are displayed on your site.', 'jelly-catalog') . '</p>';
    }

    /**
     * 渲染产品页面选择下拉框
     */
    public static function render_page_for_products_select()
    {
        $selected_page = get_option('page_for_products', 0);

        wp_dropdown_pages(array(
            'name'             => 'page_for_products',
            'selected'         => $selected_page,
            'show_option_none' => __('— Select a page —', 'jelly-catalog'),
            'option_none_value' => '0',
        ));

        echo '<p class="description">' . __('Select the page where your products will be displayed.', 'jelly-catalog') . '</p>';
    }

    /**
     * 渲染每页产品数量输入框
     */
    public static function render_products_per_page_input()
    {
        $products_per_page = get_option('products_per_page', 16);

        echo '<input name="products_per_page" type="number" step="1" min="1" id="products_per_page" value="' . esc_attr($products_per_page) . '" class="small-text" />';
        echo '<p class="description">' . __('Set the number of products to display per page.', 'jelly-catalog') . '</p>';
    }

    /**
     * 渲染产品排序依据下拉选择框
     */
    public static function render_products_orderby_select()
    {
        $orderby_options = array(
            'date'          => __('Date', 'jelly-catalog'),
            'title'         => __('Title', 'jelly-catalog'),
            'menu_order'    => __('Menu Order', 'jelly-catalog'),
            'rand'          => __('Random', 'jelly-catalog'),
            'modified'      => __('Last Modified', 'jelly-catalog')
        );

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
    public static function render_products_order_select()
    {
        $order_options = array(
            'ASC'   => __('Ascending', 'jelly-catalog'),
            'DESC'  => __('Descending', 'jelly-catalog')
        );

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
    public static function register_permalink_settings()
    {
        add_settings_section(
            'jelly_catalog_permalink_section',
            __('Product Permalinks', 'jelly-catalog'),
            array(__CLASS__, 'render_permalink_section'),
            'permalink'
        );

        add_settings_field(
            'jelly_catalog_product_base',
            __('Product Base', 'jelly-catalog'),
            array(__CLASS__, 'render_product_base_input'),
            'permalink',
            'jelly_catalog_permalink_section'
        );
    }

    /**
     * 渲染固定链接设置段
     */
    public static function render_permalink_section()
    {
        echo '<p>' . __('Configure the permalink structure for your product catalog.', 'jelly-catalog') . '</p>';
    }

    /**
     * 渲染产品基础URL输入框
     */
    public static function render_product_base_input()
    {
        $permalinks = jc_get_permalink_structure();
        $product_base = $permalinks['product_base'];

        echo '<input name="jelly_catalog_product_base" type="text" class="regular-text code" value="' . esc_attr($product_base) . '" placeholder="products" />';
        echo '<p class="description">' . __('Enter the base slug for your products URL.', 'jelly-catalog') . '</p>';
    }

    /**
     * 保存固定链接设置
     */
    public static function save_permalink_settings()
    {
        if (!is_admin()) {
            return;
        }

        if (empty($_POST['jelly_catalog_product_base'])) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_POST['_wpnonce'])) {
            check_admin_referer('update-permalink');
        }

        if (isset($_POST['jelly_catalog_product_base'])) {
            $permalinks = get_option('jelly_catalog_permalinks', array());
            $permalinks['product_base'] = sanitize_title_with_dashes($_POST['jelly_catalog_product_base']);

            update_option('jelly_catalog_permalinks', $permalinks);
        }
    }
}

JC_Settings::init();
