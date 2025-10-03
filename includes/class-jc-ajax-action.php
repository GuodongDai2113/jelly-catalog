<?php

/**
 * includes\class-jc-ajax-action.php
 * 
 * @see: https://jellydai.com
 * @author: Jelly Dai <d@jellydai.com>
 * @created : 2025.09.17 14:37
 */

if (! defined('ABSPATH')) exit; // 禁止直接访问

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

    public function __construct()
    {
        add_action('wp_ajax_update_product_category_image', array($this, 'update_product_category_image'));
        add_action('wp_ajax_update_product_category_description', array($this, 'update_product_category_description'));
        add_action('wp_ajax_get_products_sheet',  array($this, 'get_products_sheet'));
        add_action('wp_ajax_save_products_sheet', array($this, 'save_products_sheet'));
    }

    public function update_product_category_description()
    {

        // 验证 nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jc_nonce')) {
            wp_send_json_error(__('Security verification failed', 'jelly-catalog'));
        }

        // 检查权限
        if (!current_user_can('manage_categories')) {
            wp_send_json_error(__('Insufficient permissions', 'jelly-catalog'));
        }

        // 获取参数
        $term_id = isset($_POST['term_id']) ? intval($_POST['term_id']) : 0;
        $description = isset($_POST['description']) ? wp_kses_post($_POST['description']) : '';

        // 验证参数
        if (!$term_id) {
            wp_send_json_error(__('Parameter error', 'jelly-catalog'));
        }

        // 更新分类描述
        $result = wp_update_term($term_id, 'product_cat', array(
            'description' => $description
        ));

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(__('Description updated successfully', 'jelly-catalog'));
    }


    /**
     * 更新分类图片
     * 
     * @since 1.3.0
     * 
     * @return void
     */
    public function update_product_category_image()
    {

        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jc_nonce')) {
            wp_send_json_error(__('Security verification failed', 'jelly-catalog'));
        }

        $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
        $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;

        if (!$category_id || !$image_id) {
            wp_send_json_error(__('Parameter error', 'jelly-catalog'));
        }

        update_term_meta($category_id, 'thumbnail_id', $image_id);
        wp_send_json_success(__('Category image updated successfully', 'jelly-catalog'));
    }

    public function get_products_sheet()
    {
        global $wpdb;
        check_ajax_referer('jc_nonce', 'nonce');

        // 获取分页参数
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 5;

        // 计算偏移量
        $offset = ($page - 1) * $per_page;

        // 查询产品，包含摘要信息
        $products = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID, post_title, post_excerpt FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish' ORDER BY ID DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ),
            ARRAY_A
        );

        // // 为每个产品获取分类信息
        // if (!empty($products)) {
        //     foreach ($products as &$product) {
        //         // 获取产品分类
        //         $terms = get_the_terms($product['ID'], 'product_cat');
        //         if ($terms && !is_wp_error($terms)) {
        //             $categories = array();
        //             foreach ($terms as $term) {
        //                 $categories[] = $term->name;
        //             }
        //             $product['categories'] = implode(', ', $categories);
        //         } else {
        //             $product['categories'] = '';
        //         }
        //     }
        // }

        // 获取总产品数用于分页
        $total_products = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish'"
        );

        wp_send_json(array(
            'products' => $products ?: array(),
            'total' => intval($total_products),
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total_products / $per_page)
        ));
    }

    public function save_products_sheet()
    {
        // 安全检查
        check_ajax_referer('jc_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('权限不足', 'jelly-catalog'));
        }

        // 获取数据
        $data = isset($_POST['data']) ? $_POST['data'] : array();

        if (empty($data)) {
            wp_send_json_error(__('没有数据需要更新', 'jelly-catalog'));
        }

        $updated_count = 0;

        // 处理每个产品的更新
        foreach ($data as $item) {
            $product_id = isset($item['ID']) ? intval($item['ID']) : 0;

            if (!$product_id) {
                continue;
            }

            // 构建更新数据
            $update_data = array('ID' => $product_id);

            // 只更新有变化的字段
            if (isset($item['post_title'])) {
                $update_data['post_title'] = sanitize_text_field($item['post_title']);
            }

            if (isset($item['post_excerpt'])) {
                $update_data['post_excerpt'] = wp_kses_post($item['post_excerpt']);
            }

            // 执行更新
            $result = wp_update_post($update_data, true);

            if (!is_wp_error($result) && $result > 0) {
                $updated_count++;
            }
        }

        wp_send_json_success(sprintf(__('成功更新 %d 个产品', 'jelly-catalog'), $updated_count));
    }
}

JC_Ajax_Action::instance();
