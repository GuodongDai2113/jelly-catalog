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

class Utils
{
    public const PRODUCT_BASE_PRODUCTS = 'products';
    public const PRODUCT_BASE_PRODUCT = 'product';
    public const PRODUCT_BASE_CATEGORY = '%product_cat%';

    public const CATEGORY_BASE_DEFAULT = 'product-category';
    public const CATEGORY_BASE_NONE = '';

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

    public static function uses_category_in_product_permalink($permalinks = null)
    {
        if (null === $permalinks) {
            $permalinks = self::get_permalink_structure();
        }

        return isset($permalinks['product_base']) && self::PRODUCT_BASE_CATEGORY === $permalinks['product_base'];
    }

    public static function uses_category_base_without_prefix($permalinks = null)
    {
        if (null === $permalinks) {
            $permalinks = self::get_permalink_structure();
        }

        return isset($permalinks['category_base']) && self::CATEGORY_BASE_NONE === $permalinks['category_base'];
    }

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
