<?php

/**
 * Plugin Name:       Jelly Catalog
 * Plugin URI:        https://jellydai.com/
 * Description:       Only enable product features, fully compatible with Woocommerce
 * Version:           1.0.3
 * Author:            JellyDai
 * Author URI:        https://jellydai.com/
 * Text Domain: jelly-catalog
 * Domain Path: /languages
 */

if (! defined('ABSPATH')) exit; // 禁止直接访问

/** 插件版本 */
define('JELLY_CATALOG_VERSION', '1.0.4');

/** 插件URL路径 */
define('JELLY_CATALOG_PLUGIN_URL', plugin_dir_url(__FILE__));

/** 插件本地路径 */
define('JELLY_CATALOG_PLUGIN_PATH',  plugin_dir_path(__FILE__));

// 优化后的代码结构
if (!function_exists('is_plugin_active')) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

// 检查 WooCommerce 是否激活
$woocommerce_active = is_plugin_active('woocommerce/woocommerce.php');
define('JELLY_CATALOG_WC_ACTIVE', $woocommerce_active);

if (!$woocommerce_active) {
	require JELLY_CATALOG_PLUGIN_PATH . 'includes/class-jc-post-types.php';
	add_action('plugins_loaded', 'jelly_catalog_init');
} else {
	require JELLY_CATALOG_PLUGIN_PATH . 'includes/class-jc-woocommerce.php';
}

// 基础依赖
require JELLY_CATALOG_PLUGIN_PATH . 'includes/jc-functions.php';

// 可选依赖
if (is_plugin_active('advanced-custom-fields/acf.php')) {
	require JELLY_CATALOG_PLUGIN_PATH . 'addons/class-jc-acf.php';
}

if (is_plugin_active('seo-by-rank-math/rank-math.php') && !$woocommerce_active) {
	require JELLY_CATALOG_PLUGIN_PATH . 'addons/class-jc-rank-math.php';
}

function jelly_catalog_load_plugin_textdomain() {
    load_plugin_textdomain( 'jelly-catalog', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'jelly_catalog_load_plugin_textdomain' );

function jelly_catalog_init()
{
	require JELLY_CATALOG_PLUGIN_PATH . 'includes/class-jc-admin.php';
	require JELLY_CATALOG_PLUGIN_PATH . 'includes/class-jc-post-meta-box.php';
	require JELLY_CATALOG_PLUGIN_PATH . 'includes/class-jc-ajax-action.php';
	new JC_Admin();
	new JC_Post_Meta_Box();
}
