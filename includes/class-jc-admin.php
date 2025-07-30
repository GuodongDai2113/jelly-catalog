<?php

/**
 * includes\class-jc-post-meta-box.php
 * 
 * @see: https://jellydai.com
 * @author: Jelly Dai <d@jellydai.com>
 * @created: 2025.07.29 14:47
 */


if (! defined('ABSPATH')) exit; // 禁止直接访问

/**
 * 请填写描述
 */
class JC_Admin
{
    public function __construct()
    {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    public function enqueue_admin_assets($hook)
    {
        // 注册并引入 admin.css
        wp_enqueue_style(
            'jelly-products-admin-css',
            JELLY_CATALOG_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            JELLY_CATALOG_VERSION
        );

        // 注册并引入 admin.js
        wp_enqueue_script(
            'jelly-products-admin-js',
            JELLY_CATALOG_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'jquery-ui-sortable'),
            JELLY_CATALOG_VERSION,
            true
        );
    }

}
