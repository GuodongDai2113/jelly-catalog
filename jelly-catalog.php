<?php

/**
 * Plugin Name: Jelly Catalog
 * Plugin URI:  https://jellydai.com/
 * Description: Only enable product features, fully compatible with Woocommerce
 * Version:     2.3.1
 * Author:      JellyDai
 * Author URI:  https://jellydai.com/
 * Text Domain: jelly-catalog
 * Domain Path: /language
 */

if (! defined('ABSPATH')) exit; // 禁止直接访问

/** 插件版本 */
define('JELLY_CATALOG_VERSION', '2.3.1');

/** 插件URL路径 */
define('JELLY_CATALOG_PLUGIN_URL', plugin_dir_url(__FILE__));

/** 插件本地路径 */
define('JELLY_CATALOG_PLUGIN_PATH',  plugin_dir_path(__FILE__));

// 基础依赖
require JELLY_CATALOG_PLUGIN_PATH . 'includes/jc-functions.php';

// 优化后的代码结构
if (!function_exists('is_plugin_active')) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

// 检查 WooCommerce 是否激活
$woocommerce_active = is_plugin_active('woocommerce/woocommerce.php');
define('JELLY_CATALOG_WC_ACTIVE', $woocommerce_active);

if (!$woocommerce_active) {
	require JELLY_CATALOG_PLUGIN_PATH . 'includes/class-jc-post-types.php';
	// require JELLY_CATALOG_PLUGIN_PATH . 'includes/class-jc-template-loader.php';
} else {
	require JELLY_CATALOG_PLUGIN_PATH . 'includes/class-jc-woocommerce.php';
}

// 可选依赖
if (is_plugin_active('advanced-custom-fields/acf.php')) {
	require JELLY_CATALOG_PLUGIN_PATH . 'addons/class-jc-acf.php';
}

if (is_plugin_active('seo-by-rank-math/rank-math.php') && !jc_is_woocommerce_activated()) {
	require JELLY_CATALOG_PLUGIN_PATH . 'addons/class-jc-rank-math.php';
}

if (is_plugin_active('elementor-pro/elementor-pro.php') && !jc_is_woocommerce_activated()) {
	require JELLY_CATALOG_PLUGIN_PATH . 'addons/class-jc-elementor.php';
}

function jelly_catalog_load_plugin_textdomain()
{
	load_plugin_textdomain('jelly-catalog', FALSE, basename(dirname(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'jelly_catalog_load_plugin_textdomain');

function jelly_catalog_init()
{
	if (!is_admin()) {
		return;
	}
	require JELLY_CATALOG_PLUGIN_PATH . 'includes/class-jc-admin.php';
	require JELLY_CATALOG_PLUGIN_PATH . 'includes/class-jc-settings.php';
	require JELLY_CATALOG_PLUGIN_PATH . 'includes/class-jc-post-meta-box.php';
	require JELLY_CATALOG_PLUGIN_PATH . 'includes/class-jc-ajax-action.php';
	require JELLY_CATALOG_PLUGIN_PATH . 'includes/class-jc-sheet-editor.php';
	require JELLY_CATALOG_PLUGIN_PATH . 'includes/class-jc-port.php';
}
add_action('plugins_loaded', 'jelly_catalog_init');

// 前端资源加载
function jelly_catalog_enqueue_frontend_assets()
{
	wp_enqueue_style(
		'jelly-catalog',
		JELLY_CATALOG_PLUGIN_URL . 'assets/css/jc.css',
		array(),
		JELLY_CATALOG_VERSION
	);

	if (is_singular('product')) {

		wp_enqueue_style(
			'jelly-catalog-swiper',
			JELLY_CATALOG_PLUGIN_URL . 'assets/css/swiper-bundle.min.css',
			array(),
			JELLY_CATALOG_VERSION
		);
		wp_enqueue_script(
			'jelly-catalog-swiper',
			JELLY_CATALOG_PLUGIN_URL . 'assets/js/swiper-bundle.min.js',
			array(),
			JELLY_CATALOG_VERSION,
			true
		);
		wp_enqueue_script(
			'jelly-catalog',
			JELLY_CATALOG_PLUGIN_URL . 'assets/js/jc.js',
			array(),
			JELLY_CATALOG_VERSION,
			true
		);
	}
}
add_action('wp_enqueue_scripts', 'jelly_catalog_enqueue_frontend_assets');

