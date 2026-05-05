<?php

/**
 * includes/autoloader.php
 *
 * Author  : Jelly Dai
 * Email   : daiguo1003@gmail.com
 * Created : 2025.03.29 10:22
 */

namespace Jelly_Catalog;

if (!defined('ABSPATH')) {
    exit;
} // 禁止直接访问

/**
 * 插件自动加载器。
 *
 * @since 1.0.0
 */
class Autoloader
{
    /**
     * Classes map.
     *
     * Maps Elementor classes to file names.
     *
     * @since 1.6.0
     * @access private
     * @static
     *
     * @var array Classes used by elementor.
     */
    private static $classes_map;

    /**
     * Default path for autoloader.
     *
     * @var string
     */
    private static $default_path;

    /**
     * Default namespace for autoloader.
     *
     * @var string
     */
    private static $default_namespace;

    /**
     * 执行自动加载器的运行方法
     *
     * 该方法用于初始化自动加载器，设置默认的路径和命名空间，然后注册自动加载函数
     *
     * @param string $default_path 默认的路径，用于指定基目录
     * @param string $default_namespace 默认的命名空间，用于解决命名空间冲突
     */
    public static function run($default_path = '', $default_namespace = '')
    {
        if ('' === $default_path) {
            $default_path = JELLY_CATALOG_PLUGIN_PATH;
        }

        if ('' === $default_namespace) {
            $default_namespace = __NAMESPACE__;
        }

        self::$default_path = trailingslashit($default_path);
        self::$default_namespace = $default_namespace;
        spl_autoload_register([__CLASS__, 'autoload']);
    }

    /**
     * 获取类映射数组
     *
     * 该方法用于获取一个类映射数组，如果数组尚未初始化，则调用初始化方法
     * 类映射数组可能用于记录框架或库中的类与文件的对应关系，以便实现自动加载等功能
     *
     * @return array 类映射数组
     */
    public static function get_classes_map()
    {
        if (!self::$classes_map) {
            self::init_classes_map();
        }

        return self::$classes_map;
    }

    /**
     * 初始化类映射
     *
     * 该方法用于初始化一个类映射数组，用于存储类名和其对应文件路径的映射关系
     * 这有助于在需要时通过类名快速定位到类的定义位置，提高类的加载效率
     *
     * @return void 该方法没有返回值
     */
    private static function init_classes_map()
    {
        self::$classes_map = [
            'Utils' => 'includes/utils.php',
            'Admin' => 'includes/admin.php',
            'Ajax_Action' => 'includes/ajax-action.php',
            'Enqueue' => 'includes/enqueue.php',
            'Settings' => 'includes/settings.php',
        ];
    }

    /**
     * 根据类名加载对应的类文件
     *
     * 该方法主要用于实现自动加载机制，将类名转换为类文件的路径，并尝试加载该文件
     * 如果类名在预定义的类映射中，则直接使用映射中的文件路径
     * 否则，根据类名生成文件路径，并尝试加载
     *
     * @param string $relative_class_name 相对类名，即需要加载的类名
     */
    private static function load_class($relative_class_name)
    {
        $classes_map = self::get_classes_map();

        if (isset($classes_map[$relative_class_name])) {
            $filename = self::$default_path . $classes_map[$relative_class_name];
        } else {
            $filename = strtolower(
                preg_replace(
                    ['/([a-z])([A-Z])/', '/_/', '/\\\/'],
                    ['$1-$2', '-', DIRECTORY_SEPARATOR],
                    $relative_class_name
                )
            );

            $filename = self::$default_path . $filename . '.php';
        }

        if (is_readable($filename)) {
            require $filename;
        }
    }

    /**
     * 自动加载指定的类
     *
     * 该方法用于自动加载属于默认命名空间的类它通过检查类名是否以默认命名空间开头，
     * 然后根据类名转换为文件路径并加载该文件如果类已经存在，则不会重新加载
     *
     * @param string $class 要加载的类名
     */
    private static function autoload($class)
    {
        $namespace_prefix = self::$default_namespace . '\\';
        if (0 !== strpos($class, $namespace_prefix)) {
            return;
        }

        $relative_class_name = substr($class, strlen($namespace_prefix));
        $final_class_name = $namespace_prefix . $relative_class_name;

        if (!class_exists($final_class_name)) {
            self::load_class($relative_class_name);
        }
    }
}
