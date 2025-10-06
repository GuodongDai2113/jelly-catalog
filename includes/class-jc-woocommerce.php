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
}
JC_WooCommerce::instance();
