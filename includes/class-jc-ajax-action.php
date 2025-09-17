<?php

/**
 * includes\class-jc-ajax-action.php
 * 
 * @see: https://jellydai.com
 * @author: Jelly Dai <d@jellydai.com>
 * @created : 2025.09.17 14:37
 */

if (! defined('ABSPATH')) exit; // 禁止直接访问

class JC_Ajax_Action { 
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

}

JC_Ajax_Action::instance();