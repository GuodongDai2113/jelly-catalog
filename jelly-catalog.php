<?php

/**
 * Plugin Name:       Jelly Catalog
 * Plugin URI:        https://jellydai.com/
 * Description:       
 * Version:           1.0.0
 * Author:            JellyDai
 * Author URI:        https://jellydai.com/
 */

if (! defined('ABSPATH')) exit; // 禁止直接访问

/** 插件版本 */
define('JELLY_CATALOG_VERSION', '1.0.0');

/** 插件URL路径 */
define('JELLY_CATALOG_PLUGIN_URL', plugin_dir_url(__FILE__));

/** 插件本地路径 */
define('JELLY_CATALOG_PLUGIN_PATH',  plugin_dir_path(__FILE__));

if (!function_exists('is_plugin_active')) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

if (!is_plugin_active('woocommerce/woocommerce.php')) {
	define('JELLY_CATALOG_WC_ACTIVE', false);
	require JELLY_CATALOG_PLUGIN_PATH . 'includes/class-jc-post-types.php';
}else{
	define('JELLY_CATALOG_WC_ACTIVE', true);
}

require JELLY_CATALOG_PLUGIN_PATH . 'includes/class-jc-post-meta-box.php';
require JELLY_CATALOG_PLUGIN_PATH . 'includes/class-jc-admin.php';

if (is_plugin_active('advanced-custom-fields/acf.php')) {
	require JELLY_CATALOG_PLUGIN_PATH . 'includes/class-jc-acf.php';
}

new JC_Admin();
new JC_Post_Meta_Box();