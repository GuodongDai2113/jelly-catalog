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
} // 绂佹鐩存帴璁块棶

/**
 * Get the placeholder image URL either from media, or use the fallback image.
 *
 * @return string
 */
function jc_placeholder_img_src()
{
    $src = JELLY_CATALOG_PLUGIN_URL . 'assets/images/placeholder.webp';
    return $src;
}

/**
 * 鑾峰彇浜у搧涓嬭浇鏂囦欢淇℃伅銆?
 *
 * @param int $product_id 浜у搧 ID銆?
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

/**
 * 获取产品 FAQ 列表，并过滤空问题与空答案。
 *
 * @param int $product_id 产品 ID。
 * @return array
 */
function jc_get_product_faq_items($product_id)
{
    $product_id = absint($product_id);

    if (!$product_id) {
        return [];
    }

    $faq_items = get_post_meta($product_id, '_product_faqs', true);

    if (empty($faq_items) || !is_array($faq_items)) {
        return [];
    }

    $normalized_items = [];

    foreach ($faq_items as $faq_item) {
        $question = trim((string) ($faq_item['name'] ?? ''));
        $answer = (string) ($faq_item['value'] ?? '');

        if ($question === '' || $answer === '') {
            continue;
        }

        $normalized_items[] = [
            'name' => $question,
            'value' => $answer,
        ];
    }

    return $normalized_items;
}

/**
 * 鍒ゆ柇褰撳墠椤甸潰鏄惁涓轰骇鍝佽鎯呴〉
 *
 * @return bool 濡傛灉鏄骇鍝佽鎯呴〉杩斿洖 true锛屽惁鍒欒繑鍥?false
 */
function is_jc_product_single()
{
    return is_singular('product');
}

/**
 * 鍒ゆ柇褰撳墠椤甸潰鏄惁涓轰骇鍝佸垎绫绘垨鏍囩褰掓。椤?
 *
 * @return bool 濡傛灉鏄骇鍝佸垎绫绘垨鏍囩椤佃繑鍥?true锛屽惁鍒欒繑鍥?false
 */
function is_jc_product_category()
{
    return is_tax('product_cat') || is_tax('product_tag');
}

/**
 * 鍒ゆ柇褰撳墠椤甸潰鏄惁涓轰骇鍝佸綊妗ｉ〉锛堝寘鎷垎绫汇€佹爣绛撅級
 *
 * @return bool 濡傛灉鏄骇鍝佸綊妗ｉ〉杩斿洖 true锛屽惁鍒欒繑鍥?false
 */
function is_jc_product_archive()
{
    return is_post_type_archive('product') || is_tax('product_cat') || is_tax('product_tag');
}

/**
 * 鍒ゆ柇褰撳墠椤甸潰鏄惁涓轰骇鍝佹悳绱㈢粨鏋滈〉
 *
 * @return bool 濡傛灉鏄骇鍝佹悳绱㈤〉杩斿洖 true锛屽惁鍒欒繑鍥?false
 */
function is_jc_product_search()
{
    return is_search() && 'product' === get_query_var('post_type');
}

/**
 * 鍒ゆ柇褰撳墠椤甸潰鏄惁涓轰骇鍝佸垪琛ㄩ〉鎴栦骇鍝佸綊妗ｉ〉
 *
 * @return bool 濡傛灉鏄骇鍝佸垪琛ㄩ〉杩斿洖 true锛屽惁鍒欒繑鍥?false
 */
function is_jc_products()
{
    return is_post_type_archive('product') || is_page(get_option('page_for_products', 0));
}

/**
 * 鑾峰彇鎸囧畾椤甸潰鐨?ID
 *
 * @param string $page 椤甸潰绫诲瀷锛岀洰鍓嶆敮鎸?'products'
 * @return int 椤甸潰 ID锛屽鏋滄湭鎵惧埌鍒欒繑鍥?-1
 */
function jc_get_page_id($page)
{
    if ($page === 'products') {
        $page = get_option('page_for_products');
    }

    return $page ? absint($page) : -1;
}
