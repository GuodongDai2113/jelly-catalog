<?php

/**
 * includes/utils.php
 *
 * Auther  :   JellyDai
 * Email   :   daiguo1003@gmail.com
 */

namespace Jelly_Catalog;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 工具类 - 提供插件常用的辅助功能
 * 
 * @since 1.0.0
 */
class Utils
{
    /** 产品基础路径常量：使用 'products' 作为复数形式 */
    public const PRODUCT_BASE_PRODUCTS = 'products';
    
    /** 产品基础路径常量：使用 'product' 作为单数形式 */
    public const PRODUCT_BASE_PRODUCT = 'product';
    
    /** 产品基础路径常量：使用分类slug作为路径 */
    public const PRODUCT_BASE_CATEGORY = '%product_cat%';

    /** 分类基础路径常量：默认值 'product-category' */
    public const CATEGORY_BASE_DEFAULT = 'product-category';
    
    /** 分类基础路径常量：无前缀（空字符串） */
    public const CATEGORY_BASE_NONE = '';

    /**
     * 检查 WooCommerce 是否已激活
     * 
     * 通过多种方式检测 WooCommerce 插件是否处于激活状态
     * 
     * @since 1.0.0
     * @return bool 如果 WooCommerce 已激活返回 true，否则返回 false
     */
    public static function is_wc_activated()
    {
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        if (is_plugin_active('woocommerce/woocommerce.php')) {
            return true;
        }

        if (function_exists('WC')) {
            return true;
        }
        if (class_exists('WooCommerce')) {
            return true;
        }
        return false;
    }

    /**
     * 获取产品的固定链接结构配置
     * 
     * 从选项表中读取保存的固定链接设置，并提供默认值
     * 同时验证配置值是否在允许范围内，如果无效则重置为默认值
     * 
     * @since 1.0.0
     * @return array 返回包含固定链接配置的数组
     *               - product_base: 产品基础路径
     *               - category_base: 分类基础路径
     *               - product_rewrite_slug: 产品重写slug
     *               - product_archive_slug: 产品归档slug
     *               - category_rewrite_slug: 分类重写slug
     */
    public static function get_permalink_structure()
    {
        $saved_permalinks = (array) get_option('jelly_catalog_permalinks', []);
        $permalinks = wp_parse_args(
            $saved_permalinks,
            [
                'product_base' => self::PRODUCT_BASE_PRODUCTS,
                'category_base' => self::CATEGORY_BASE_DEFAULT,
            ]
        );

        $allowed_product_bases = [
            self::PRODUCT_BASE_PRODUCTS,
            self::PRODUCT_BASE_PRODUCT,
            self::PRODUCT_BASE_CATEGORY,
        ];
        if (!in_array($permalinks['product_base'], $allowed_product_bases, true)) {
            $permalinks['product_base'] = self::PRODUCT_BASE_PRODUCTS;
        }

        $allowed_category_bases = [
            self::CATEGORY_BASE_DEFAULT,
            self::CATEGORY_BASE_NONE,
        ];
        if (!in_array($permalinks['category_base'], $allowed_category_bases, true)) {
            $permalinks['category_base'] = self::CATEGORY_BASE_DEFAULT;
        }

        if ($saved_permalinks !== $permalinks) {
            update_option('jelly_catalog_permalinks', $permalinks);
        }

        $permalinks['product_rewrite_slug'] = self::uses_category_in_product_permalink($permalinks)
            ? self::PRODUCT_BASE_PRODUCTS
            : untrailingslashit($permalinks['product_base']);
        $permalinks['product_archive_slug'] = $permalinks['product_rewrite_slug'] ?: self::PRODUCT_BASE_PRODUCTS;
        $permalinks['category_rewrite_slug'] = self::uses_category_base_without_prefix($permalinks)
            ? ''
            : untrailingslashit($permalinks['category_base']);

        return $permalinks;
    }

    /**
     * 检查是否在产品固定链接中使用分类路径
     * 
     * @since 1.0.0
     * @param array|null $permalinks 固定链接配置数组，如果为 null 则自动获取
     * @return bool 如果使用分类路径返回 true，否则返回 false
     */
    public static function uses_category_in_product_permalink($permalinks = null)
    {
        if (null === $permalinks) {
            $permalinks = self::get_permalink_structure();
        }

        return isset($permalinks['product_base']) && self::PRODUCT_BASE_CATEGORY === $permalinks['product_base'];
    }

    /**
     * 检查分类基础路径是否无前缀
     * 
     * @since 1.0.0
     * @param array|null $permalinks 固定链接配置数组，如果为 null 则自动获取
     * @return bool 如果分类基础路径无前缀返回 true，否则返回 false
     */
    public static function uses_category_base_without_prefix($permalinks = null)
    {
        if (null === $permalinks) {
            $permalinks = self::get_permalink_structure();
        }

        return isset($permalinks['category_base']) && self::CATEGORY_BASE_NONE === $permalinks['category_base'];
    }

    /**
     * 获取产品分类的完整路径
     * 
     * 构建从根分类到当前分类的完整路径，用斜杠分隔
     * 
     * @since 1.0.0
     * @param \WP_Term|int $term 分类对象或分类ID
     * @return string 返回分类路径字符串，如果获取失败返回空字符串
     */
    public static function get_product_category_path($term)
    {
        $term = $term instanceof \WP_Term ? $term : get_term($term, 'product_cat');

        if (!$term || is_wp_error($term) || 'product_cat' !== $term->taxonomy) {
            return '';
        }

        $slugs = [];
        $ancestor_ids = array_reverse(get_ancestors($term->term_id, 'product_cat', 'taxonomy'));

        foreach ($ancestor_ids as $ancestor_id) {
            $ancestor = get_term($ancestor_id, 'product_cat');

            if ($ancestor && !is_wp_error($ancestor)) {
                $slugs[] = $ancestor->slug;
            }
        }

        $slugs[] = $term->slug;

        return trim(implode('/', array_filter($slugs)), '/');
    }

    /**
     * 获取产品的主要分类
     * 
     * 优先使用 SEO 插件（Rank Math 或 Yoast）设置的主要分类
     * 如果没有设置，则返回层级最深的分类
     * 
     * @since 1.0.0
     * @param \WP_Post|int $product 产品对象或产品ID
     * @return \WP_Term|null 返回主要分类对象，如果没有分类则返回 null
     */
    public static function get_primary_product_category($product)
    {
        $product_id = $product instanceof \WP_Post ? (int) $product->ID : absint($product);

        if (!$product_id) {
            return null;
        }

        $terms = get_the_terms($product_id, 'product_cat');

        if (empty($terms) || is_wp_error($terms)) {
            return null;
        }

        foreach (['rank_math_primary_product_cat', '_yoast_wpseo_primary_product_cat'] as $meta_key) {
            $primary_term_id = absint(get_post_meta($product_id, $meta_key, true));

            if (!$primary_term_id) {
                continue;
            }

            foreach ($terms as $term) {
                if ((int) $term->term_id === $primary_term_id) {
                    return $term;
                }
            }
        }

        usort($terms, [__CLASS__, 'sort_terms_by_depth']);

        return $terms[0];
    }

    /**
     * 对分类数组按层级深度排序
     * 
     * 用于排序产品分类，层级深的排在前面
     * 如果层级相同，则按名称字母顺序排序
     * 
     * @since 1.0.0
     * @param \WP_Term $left 左侧分类对象
     * @param \WP_Term $right 右侧分类对象
     * @return int 返回排序比较结果
     */
    public static function sort_terms_by_depth($left, $right)
    {
        $left_depth = count(get_ancestors($left->term_id, 'product_cat', 'taxonomy'));
        $right_depth = count(get_ancestors($right->term_id, 'product_cat', 'taxonomy'));

        if ($left_depth === $right_depth) {
            return strnatcasecmp($left->name, $right->name);
        }

        return $right_depth <=> $left_depth;
    }

    /**
     * 通用 repeater 字段渲染函数
     *
     * @param array $args 参数数组
     *  - id: 字段 ID
     *  - name: 字段名称
     *  - items: 项目数组
     *  - fields: 字段定义数组
     */
    public static function render_repeater_field($args)
    {
        $defaults = [
            'id' => '',
            'name' => '',
            'title' => '',
            'items' => [],
            'fields' => []
        ];

        $args = wp_parse_args($args, $defaults);

        echo '<div class="jc-repeater-wrapper" data-key="' . esc_attr($args['name']) . '">';
        $index = 1;
        foreach ($args['items'] as $key => $item) {
            echo '<div class="repeater-item">';
            echo '<div class="repeater-item-header">';
            $title = esc_html(str_replace('_', ' ', $args['title']));
            echo '<span class="item-title">' . esc_html($index) . '. ' . $title . '</span>';

            echo '</div>';

            foreach ($args['fields'] as $field) {
                $field_name = $field['name'];
                $field_value = isset($item[$field_name]) ? $item[$field_name] : '';

                echo '<div class="repeater-item__' . esc_attr($field_name) . '">';
                echo '<label for="' . esc_attr($args['name']) . '[' . $index . '][' . $field_name . ']">';
                echo esc_html($field['label']);
                echo '</label>';

                switch ($field['type']) {
                    case 'textarea':
                        echo '<textarea rows="5" class="' . esc_attr($field['class']) . '" id="' . esc_attr($args['name']) . '[' . $index . '][' . $field_name . ']" name="' . esc_attr($args['name']) . '[' . $index . '][' . $field_name . ']">' . esc_textarea($field_value) . '</textarea>';
                        break;
                    case 'text':
                    default:
                        echo '<input class="' . esc_attr($field['class']) . '" type="text" id="' . esc_attr($args['name']) . '[' . $index . '][' . $field_name . ']" name="' . esc_attr($args['name']) . '[' . $index . '][' . $field_name . ']" value="' . esc_attr($field_value) . '" />';
                        break;
                }

                echo '</div>';
            }

            $index++;

            echo '</div>';
        }

        echo '</div>';
    }
}
