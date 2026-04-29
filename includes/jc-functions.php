<?php

/**
 * includes\jc-functions.php
 *
 * @see: https://jellydai.com
 * @author: Jelly Dai <daiguo1003@gmail.com>
 * @created : 2025.09.05 15:56
 */

if (!defined('ABSPATH')) {
    exit;
} // 禁止直接访问

/**
 * Get the placeholder image URL either from media, or use the fallback image.
 *
 * @param string $size Thumbnail size to use.
 * @return string
 */
function jc_placeholder_img_src()
{
    $src = JELLY_CATALOG_PLUGIN_URL . 'assets/images/placeholder.webp';
    return  $src;
}

/**
 * 检查 WooCommerce 插件是否已激活
 *
 * @return bool 返回 WooCommerce 插件是否激活的状态
 */
function jc_is_woocommerce_activated()
{
    if (function_exists('WC')) {
        return true;
    }
    if (class_exists('WooCommerce')) {
        return true;
    }
    return false;
}

/**
 * 获取当前产品管理入口应使用的基础权限。
 *
 * @return string
 */
function jc_get_product_edit_capability()
{
    $woocommerce_active = (defined('JELLY_CATALOG_WC_ACTIVE') && JELLY_CATALOG_WC_ACTIVE) || jc_is_woocommerce_activated();

    return $woocommerce_active ? 'edit_products' : 'edit_posts';
}

/**
 * 判断当前用户是否可以管理产品列表级功能。
 *
 * @return bool
 */
function jc_current_user_can_edit_products()
{
    return current_user_can(jc_get_product_edit_capability());
}

/**
 * 判断当前用户是否可以编辑指定产品。
 *
 * @param int $product_id 产品 ID。
 * @return bool
 */
function jc_current_user_can_edit_product($product_id)
{
    $product_id = absint($product_id);

    if ($product_id <= 0) {
        return jc_current_user_can_edit_products();
    }

    return current_user_can('edit_post', $product_id);
}

/**
 * 判断当前用户是否可以编辑产品分类法。
 *
 * @param string $taxonomy 分类法。
 * @return bool
 */
function jc_current_user_can_edit_product_terms($taxonomy = 'product_cat')
{
    $taxonomy_object = get_taxonomy($taxonomy);

    if ($taxonomy_object && !empty($taxonomy_object->cap->edit_terms)) {
        return current_user_can($taxonomy_object->cap->edit_terms);
    }

    return jc_current_user_can_edit_products();
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
function jc_render_repeater_field($args)
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

/**
 * 获取产品下载文件信息。
 *
 * @param int $product_id 产品 ID。
 * @return array
 */
function jc_get_product_download_file($product_id)
{
    $product_id = absint($product_id);
    if (!$product_id) {
        return [];
    }

    $file_id = absint(get_post_meta($product_id, 'product_file', true));
    if (!$file_id) {
        return [];
    }

    $file_url = wp_get_attachment_url($file_id);
    if (!$file_url) {
        return [];
    }

    $file_path = get_attached_file($file_id);
    $url_path = wp_parse_url($file_url, PHP_URL_PATH);
    $file_name = $file_path ? wp_basename($file_path) : ($url_path ? wp_basename($url_path) : '');
    $metadata = wp_get_attachment_metadata($file_id);
    $file_size = 0;

    if (is_array($metadata) && !empty($metadata['filesize'])) {
        $file_size = absint($metadata['filesize']);
    }

    if (!$file_size && $file_path && file_exists($file_path)) {
        $file_size = absint(filesize($file_path));
    }

    return [
        'id' => $file_id,
        'url' => $file_url,
        'filename' => $file_name ?: get_the_title($file_id),
        'filesize' => $file_size,
        'mime_type' => get_post_mime_type($file_id),
    ];
}

function is_jc_product_single()
{
    return is_singular('product');
}

function is_jc_product_category()
{
    return is_tax('product_cat') || is_tax('product_tag');
}

function is_jc_product_archive()
{
    return is_post_type_archive('product') || is_tax('product_cat') || is_tax('product_tag');
}

function is_jc_product_search()
{
    return is_search() && 'product' === get_query_var('post_type');
}

function is_jc_products()
{
    return is_post_type_archive('product') || is_page(get_option('page_for_products', 0));
}

function jc_get_page_id($page)
{
    if ($page === 'products') {
        $page = get_option('page_for_products');
    }
    return $page ? absint($page) : -1;
}

function jc_get_permalink_structure()
{
    $saved_permalinks = (array) get_option('jelly_catalog_permalinks', []);
    $permalinks = wp_parse_args(
        array_filter($saved_permalinks),
        [
            'product_base' => _x('products', 'slug', 'jelly-catalog'),
            // 'category_base'          => _x( 'product-category', 'slug', 'jelly-catalog' ),
            // 'tag_base'               => _x( 'product-tag', 'slug', 'jelly-catalog' ),
            // 'attribute_base'         => '',
            // 'use_verbose_page_rules' => false,
        ]
    );

    if ($saved_permalinks !== $permalinks) {
        update_option('jelly_catalog_permalinks', $permalinks);
    }

    $permalinks['product_rewrite_slug'] = untrailingslashit($permalinks['product_base']);
    // $permalinks['category_rewrite_slug']  = untrailingslashit( $permalinks['category_base'] );
    // $permalinks['tag_rewrite_slug']       = untrailingslashit( $permalinks['tag_base'] );
    // $permalinks['attribute_rewrite_slug'] = untrailingslashit( $permalinks['attribute_base'] );

    return $permalinks;
}
