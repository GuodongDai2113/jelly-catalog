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
     * 检查 Rank Math 是否已激活
     *
     * 兼容插件函数检测与主类检测两种方式
     *
     * @since 1.0.0
     * @return bool
     */
    public static function is_rank_math_active()
    {
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        if (is_plugin_active('seo-by-rank-math/rank-math.php')) {
            return true;
        }

        if (class_exists('RankMath')) {
            return true;
        }

        return false;
    }

    /**
     * 获取产品分类名称路径
     *
     * 根据需要返回完整层级名称或当前分类名称
     *
     * @since 1.0.0
     * @param \WP_Term|int $term 分类对象或分类ID
     * @param bool $include_ancestors 是否包含父级分类名称
     * @param string $separator 名称分隔符
     * @return string 返回分类名称路径字符串，如果获取失败返回空字符串
     */
    public static function get_product_category_name_path($term, $include_ancestors = true, $separator = ' > ')
    {
        $term = $term instanceof \WP_Term ? $term : get_term($term, 'product_cat');

        if (!$term || is_wp_error($term) || 'product_cat' !== $term->taxonomy) {
            return '';
        }

        if (!$include_ancestors) {
            return trim((string) $term->name);
        }

        $names = [];
        $ancestor_ids = array_reverse(get_ancestors($term->term_id, 'product_cat', 'taxonomy'));

        foreach ($ancestor_ids as $ancestor_id) {
            $ancestor = get_term($ancestor_id, 'product_cat');

            if ($ancestor && !is_wp_error($ancestor)) {
                $names[] = $ancestor->name;
            }
        }

        $names[] = $term->name;

        return trim(implode($separator, array_filter($names)), $separator);
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
