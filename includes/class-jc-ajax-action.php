<?php

/**
 * includes\class-jc-ajax-action.php
 * 
 * @see: https://jellydai.com
 * @author: Jelly Dai <daiguo1003@gmail.com>
 * @created : 2025.09.17 14:37
 */

if (! defined('ABSPATH')) exit; // 禁止直接访问

/**
 * AJAX 操作类
 */
class JC_Ajax_Action
{
    public static $instance;

    public static function instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 构造函数
     * 
     * 添加 AJAX 钩子
     * 
     * @return void
     */
    public function __construct()
    {
        add_action('admin_enqueue_scripts', array($this, 'admin_ajax_scripts'));
        add_action('wp_ajax_update_product_category_image', array($this, 'update_product_category_image'));
        add_action('wp_ajax_update_product_category_description', array($this, 'update_product_category_description'));
        add_action('wp_ajax_get_products_sheet',  array($this, 'get_products_sheet'));
        add_action('wp_ajax_save_products_sheet', array($this, 'save_products_sheet'));
        add_action('wp_ajax_jc_catalog_export', array($this, 'handle_export_products'));
        add_action('wp_ajax_jc_catalog_import', array($this, 'handle_import_products'));
    }

    public function admin_ajax_scripts()
    {
        wp_localize_script('jquery', 'jc_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('jc_nonce')
        ));
    }

    /**
     * 更新产品分类描述
     * 
     * 该函数用于更新指定产品分类的描述信息，包含安全验证、权限检查和参数验证
     * 
     * @return void 返回 JSON 响应
     */
    public function update_product_category_description()
    {

        // 验证 nonce 安全令牌
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jc_nonce')) {
            wp_send_json_error(__('Security verification failed', 'jelly-catalog'));
        }

        // 检查当前用户是否具有管理分类的权限
        if (!current_user_can('manage_categories')) {
            wp_send_json_error(__('Insufficient permissions', 'jelly-catalog'));
        }

        // 获取并处理请求参数
        $term_id = isset($_POST['term_id']) ? intval($_POST['term_id']) : 0;
        $description = isset($_POST['description']) ? wp_kses_post($_POST['description']) : '';

        // 验证必要参数是否有效
        if (!$term_id) {
            wp_send_json_error(__('Parameter error', 'jelly-catalog'));
        }

        // 执行分类描述更新操作
        $result = wp_update_term($term_id, 'product_cat', array(
            'description' => $description
        ));

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(__('Description updated successfully', 'jelly-catalog'));
    }

    /**
     * 更新产品分类图片
     * 
     * 该函数用于更新指定产品分类的缩略图图片。
     * 通过AJAX方式接收分类ID和图片ID，验证安全性后更新分类图片。
     * 
     * @return void 返回 JSON 响应
     */
    public function update_product_category_image()
    {

        // 验证安全令牌，防止CSRF攻击
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jc_nonce')) {
            wp_send_json_error(__('Security verification failed', 'jelly-catalog'));
        }

        // 获取并验证分类ID和图片ID参数
        $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
        $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;

        // 检查必要参数是否完整
        if (!$category_id || !$image_id) {
            wp_send_json_error(__('Parameter error', 'jelly-catalog'));
        }

        // 更新分类的缩略图元数据
        update_term_meta($category_id, 'thumbnail_id', $image_id);
        wp_send_json_success(__('Category image updated successfully', 'jelly-catalog'));
    }

    /**
     * 获取产品列表数据（支持分页），并附带指定的SEO元数据。
     *
     * 此函数通过 AJAX 调用，需验证 nonce。它会查询已发布的 WooCommerce 产品，
     * 并为每个产品附加一组自定义字段（如 Rank Math 的关键词、标题和描述）。
     * 返回的数据包括当前页的产品信息、总数及分页相关参数。
     *
     * @return void 返回 JSON 响应
     */
    public function get_products_sheet()
    {
        global $wpdb;
        check_ajax_referer('jc_nonce', 'nonce');

        // 获取分页参数
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 20;

        // 定义需要获取的元数据键
        $meta_keys = array(
            'rank_math_focus_keyword',
            'rank_math_title',
            'rank_math_description'
        );

        // 计算偏移量用于分页查询
        $offset = ($page - 1) * $per_page;

        // 查询产品基础信息：ID、标题、摘要，并按 ID 倒序排列
        $products = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID, post_title, post_excerpt FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish' ORDER BY ID DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ),
            ARRAY_A
        );

        // 为每个产品加载所需的 SEO 元数据
        foreach ($products as &$product) {
            $product_id = $product['ID'];
            $meta_data = array();

            foreach ($meta_keys as $meta_key) {
                $meta_value = get_post_meta($product_id, $meta_key, true);
                $meta_data[$meta_key] = $meta_value;
            }

            $product['meta_data'] = $meta_data;
        }

        // 获取所有符合条件的产品总数，用于计算总页数
        $total_products = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish'"
        );

        // 构造并发送 JSON 响应结果
        wp_send_json(array(
            'products' => $products ?: array(),
            'total' => intval($total_products),
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total_products / $per_page)
        ));
    }

    /**
     * 批量保存产品数据的 AJAX 处理函数
     *
     * 该函数用于处理从前端传来的批量产品数据更新请求，包括产品标题、摘要以及指定的元数据字段。
     * 函数会验证用户权限和请求来源，并对每个产品执行安全过滤后更新到数据库。
     *
     * @return void 返回 JSON 响应
     */
    public function save_products_sheet()
    {
        // 安全检查：验证 nonce 和用户权限
        check_ajax_referer('jc_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Insufficient permissions', 'jelly-catalog'));
        }

        // 获取并检查提交的数据
        $data = isset($_POST['data']) ? $_POST['data'] : array();

        if (empty($data)) {
            wp_send_json_error(__('No data needs to be updated', 'jelly-catalog'));
        }

        // 定义需要保存的元数据键名列表
        $meta_keys = array(
            'rank_math_focus_keyword',
            'rank_math_title',
            'rank_math_description'
        );

        $updated_count = 0;

        // 遍历所有产品数据进行更新处理
        foreach ($data as $item) {
            $product_id = isset($item['ID']) ? intval($item['ID']) : 0;

            if (!$product_id) {
                continue;
            }

            // 构建待更新的文章基础数据
            $update_data = array('ID' => $product_id);

            // 只更新有变化的字段，并进行安全过滤
            if (isset($item['post_title'])) {
                $update_data['post_title'] = sanitize_text_field($item['post_title']);
            }

            if (isset($item['post_excerpt'])) {
                $update_data['post_excerpt'] = wp_kses_post($item['post_excerpt']);
            }

            // 执行文章更新操作
            $result = wp_update_post($update_data, true);

            // 更新相关的元数据字段
            if (isset($item['meta_data']) && is_array($item['meta_data'])) {
                foreach ($meta_keys as $meta_key) {
                    if (isset($item['meta_data'][$meta_key])) {
                        $meta_value = $item['meta_data'][$meta_key];
                        update_post_meta($product_id, $meta_key, sanitize_text_field($meta_value));
                    }
                }
            }

            // 统计成功更新的产品数量
            if (!is_wp_error($result) && $result > 0) {
                $updated_count++;
            }
        }
        /* translators: %d: Number of products updated */
        wp_send_json_success(sprintf(__('Successfully updated %d products', 'jelly-catalog'), $updated_count));
    }

    public function handle_export_products()
{
    check_ajax_referer('jc_nonce', 'nonce');

    if (! current_user_can('export')) {
        wp_send_json_error(array('message' => __('Insufficient permissions', 'jelly-catalog')));
    }

    $upload_dir = wp_upload_dir();
    if (! empty($upload_dir['error'])) {
        wp_send_json_error(array('message' => $upload_dir['error']));
    }

    $filename  = 'jc-products-' . gmdate('Ymd-His') . '.csv';
    $file_path = trailingslashit($upload_dir['path']) . $filename;

    if (! is_dir($upload_dir['path'])) {
        wp_mkdir_p($upload_dir['path']);
    }

    require_once plugin_dir_path(__FILE__) . 'class-jc-export.php';

    $result = JC_Export::instance()->export_to_csv($file_path);

    if (empty($result['success'])) {
        wp_send_json_error(array('message' => isset($result['message']) ? $result['message'] : __('Export failed', 'jelly-catalog')));
    }

    $file_url = trailingslashit($upload_dir['url']) . $filename;
    wp_send_json_success(array('url' => $file_url));
}

public function handle_import_products()
{
    check_ajax_referer('jc_nonce', 'nonce');

    if (! current_user_can('import')) {
        wp_send_json_error(array('message' => __('Insufficient permissions', 'jelly-catalog')));
    }

    if (empty($_FILES['file']) || ! is_uploaded_file($_FILES['file']['tmp_name'])) {
        wp_send_json_error(array('message' => __('No file uploaded', 'jelly-catalog')));
    }

    $overrides = array(
        'test_form' => false,
        'mimes'     => array('csv' => 'text/csv'),
    );

    $uploaded = wp_handle_upload($_FILES['file'], $overrides);

    if (isset($uploaded['error'])) {
        wp_send_json_error(array('message' => $uploaded['error']));
    }

    require_once plugin_dir_path(__FILE__) . 'class-jc-import.php';

    $result = JC_Import::instance()->import_from_csv($uploaded['file']);

    if (is_file($uploaded['file'])) {
        @unlink($uploaded['file']);
    }

    if (! empty($result['errors'])) {
        wp_send_json_error(array(
            'message' => implode("\n", $result['errors']),
            'imported' => $result['imported'],
            'updated'  => $result['updated'],
            'skipped'  => $result['skipped'],
        ));
    }

    wp_send_json_success(array(
        'imported' => $result['imported'],
        'updated'  => $result['updated'],
        'skipped'  => $result['skipped'],
    ));
}

}

JC_Ajax_Action::instance();
