<?php

/**
 * includes\plugin.php
 *
 * @see: https://jellydai.com
 * @author: Jelly Dai <daiguo1003@gmail.com>
 * @created : 2026.05.04 16:59
 */

namespace Jelly_Catalog;

if (!defined('ABSPATH')) {
    exit;
} // 禁止直接访问

/**
 * 插件核心入口
 *
 * @since 1.0.0
 */
class Plugin
{
    /**
     * 插件核心入口
     *
     * @since 1.0.0
     *
     * @var self $instance 单例实例
     */
    public static $instance = null;

    /**
     * 获取单例实例
     *
     * @since 1.0.0
     *
     * @return self 返回单例实例
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 注册 类自动加载器 与 引入 钩子加载器
     *
     * @since 1.0.0
     *
     * @return void
     */
    private function register_autoloader()
    {
        require JELLY_CATALOG_PLUGIN_PATH . 'includes/autoloader.php';

        Autoloader::run();
    }

    /**
     * 插件核心初始化
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function __construct()
    {
        $this->register_autoloader();
        if (!Utils::is_wc_activated()) {
            new Modules\Post_Types();
        }
        new Enqueue();

        if (is_admin()) {
            new Ajax_Action();
            if (!Utils::is_wc_activated()) {
                new Admin();
                new Settings();
            }
            new Modules\Post_Meta_Box();
            new Modules\Port();
            new Modules\Sheet_Editor();
        }

        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $is_elementor_active = is_plugin_active('elementor/elementor.php');
        $is_elementor_pro_active = is_plugin_active('elementor-pro/elementor-pro.php');

        if ($is_elementor_active) {
            new Addons\Elementor($is_elementor_pro_active);
        }

        if (is_plugin_active('seo-by-rank-math/rank-math.php')) {
            new Addons\Rank_Math();
        }
    }
}

Plugin::instance();
