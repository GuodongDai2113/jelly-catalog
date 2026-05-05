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
 * @return string
 */
function jc_placeholder_img_src()
{
    $src = JELLY_CATALOG_PLUGIN_URL . 'assets/images/placeholder.webp';
    return  $src;
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
