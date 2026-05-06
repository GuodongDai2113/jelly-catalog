<?php

/**
 * Plugin Name: Jelly Catalog
 * Plugin URI:  https://jellydai.com/
 * Description: Only enable product features, fully compatible with Woocommerce
 * Version:     3.2.0
 * Author:      JellyDai
 * Author URI:  https://jellydai.com/
 * Text Domain: jelly-catalog
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
} // 禁止直接访问

/** 插件版本 */
define('JELLY_CATALOG_VERSION', '3.2.0');

/** 插件URL路径 */
define('JELLY_CATALOG_PLUGIN_URL', plugin_dir_url(__FILE__));

/** 插件本地路径 */
define('JELLY_CATALOG_PLUGIN_PATH', plugin_dir_path(__FILE__));

/** 静态资源URL路径 */
define('JELLY_CATALOG_ASSETS_URL', JELLY_CATALOG_PLUGIN_URL . 'assets/');

/** 调试模式 */
define('JELLY_CATALOG_DEBUG', false);

/** 为主题提供函数 */
require JELLY_CATALOG_PLUGIN_PATH . 'functions.php';

/** 插件入口 */
require JELLY_CATALOG_PLUGIN_PATH . 'includes/plugin.php';

/**
 * 加载插件文本域以实现本地化
 * 
 * 此函数在 plugins_loaded 钩子中调用，用于加载插件的翻译文件
 * 翻译文件位于 /languages 目录下
 */
function jelly_catalog_load_plugin_textdomain()
{
    load_plugin_textdomain('jelly-catalog', false, basename(dirname(__FILE__)) . '/languages/');
}

add_action('plugins_loaded', 'jelly_catalog_load_plugin_textdomain');

/**
 * 插件激活时执行的回调函数
 * 
 * 刷新重写规则以确保自定义文章类型的固定链接正常工作
 */
function jelly_catalog_activate()
{
    flush_rewrite_rules();
}

register_activation_hook(__FILE__, 'jelly_catalog_activate');

/**
 * 插件停用时执行的回调函数
 * 
 * 刷新重写规则以清理自定义文章类型的固定链接规则
 */
function jelly_catalog_deactivate()
{
    flush_rewrite_rules();
}

register_deactivation_hook(__FILE__, 'jelly_catalog_deactivate');
