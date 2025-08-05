<?php

/**
 * includes\class-jc-woocommerce.php
 * 
 * @see: https://jellydai.com
 * @author: Jelly Dai <d@jellydai.com>
 * @created : 2025.08.05 15:35
 */

if (! defined('ABSPATH')) exit; // 禁止直接访问

class JC_WooCommerce
{
    public static $instance;


    public static function instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    public function __construct()
    {
        add_action('admin_menu', array($this, 'remove_woocommerce_dashboard'));
        add_filter('woocommerce_customer_meta_fields', array($this, '__return_empty_array'));
        add_filter('woocommerce_register_post_type_shop_order', array($this, '__return_empty_array'));
        add_filter('woocommerce_payment_gateways', array($this, '__return_empty_array'));
    }

        /**
     * 移除 woocommerce 仪表盘
     * 
     * @since 1.1.0
     * 
     * @return void
     */
    public function remove_woocommerce_dashboard()
    {
        remove_meta_box('woocommerce_dashboard_recent_reviews', 'dashboard', 'normal');
        remove_meta_box('woocommerce_dashboard_status', 'dashboard', 'normal');
        remove_meta_box('wc_admin_dashboard_setup', 'dashboard', 'normal');
    }


    /**
     * 返回空数组
     * 
     * @since 1.1.0
     * 
     * @return array
     */
    function __return_empty_array()
    {
        return [];
    }


    public static function init_woocommerce_settings()
    {
        update_option('woocommerce_ship_to_countries', '');
        update_option('woocommerce_default_customer_address', 'base');
        update_option('woocommerce_calc_taxes', 'no');
        update_option('woocommerce_enable_coupons', 'no');
        update_option('woocommerce_manage_stock', 'no');
        update_option('woocommerce_feature_site_visibility_badge_enabled', 'no');
        update_option('woocommerce_coming_soon', 'no');

        $woocommerce_pages = [
            'woocommerce_shop_page_id',
            'woocommerce_cart_page_id',
            'woocommerce_checkout_page_id',
            'woocommerce_myaccount_page_id',
            'woocommerce_terms_page_id',
        ];

        foreach ($woocommerce_pages as $option) {
            $page_id = get_option($option, '');
            if (!empty($page_id)) {
                wp_delete_post($page_id, true); // 第二个参数设置为 true 以彻底删除页面
                update_option($option, '');
            }
        }

        $products = get_page_by_path('Products');
        if ($products) {
            update_option('woocommerce_shop_page_id', $products->ID);
        }

        update_option('woocommerce_cart_page_id', '');
        update_option('woocommerce_checkout_page_id', '');
        update_option('woocommerce_myaccount_page_id', '');
        update_option('woocommerce_terms_page_id', '');

        update_option('woocommerce_myaccount_add_payment_method_endpoint', '');
        update_option('woocommerce_myaccount_delete_payment_method_endpoint', '');
        update_option('woocommerce_myaccount_set_default_payment_method_endpoint', '');
        update_option('woocommerce_myaccount_orders_endpoint', '');
        update_option('woocommerce_myaccount_view_order_endpoint', '');
        update_option('woocommerce_myaccount_downloads_endpoint', '');
        update_option('woocommerce_myaccount_edit_account_endpoint', '');
        update_option('woocommerce_myaccount_edit_address_endpoint', '');
        update_option('woocommerce_myaccount_payment_methods_endpoint', '');
        update_option('woocommerce_myaccount_lost_password_endpoint', '');
        update_option('woocommerce_analytics_enabled', 'no');
        update_option('woocommerce_feature_order_attribution_enabled', 'no');
        update_option('woocommerce_default_country', 'CN:CN12');
        update_option('woocommerce_enable_ajax_add_to_cart', 'no');
        // 移除品牌
        update_option('wc_feature_woocommerce_brands_enabled', 'no');

        $woocommerce_permalinks = get_option('woocommerce_permalinks', []);
        if (!empty($woocommerce_permalinks) || isset($woocommerce_permalinks['product_base'])) {
            $woocommerce_permalinks['product_base'] = '/products';
        };
        update_option('woocommerce_permalinks', $woocommerce_permalinks);
    }
}
JC_WooCommerce::instance();
