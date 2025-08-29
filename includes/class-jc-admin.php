<?php

/**
 * includes\class-jc-post-meta-box.php
 * 
 * @see: https://jellydai.com
 * @author: Jelly Dai <d@jellydai.com>
 * @created: 2025.07.29 14:47
 */


if (! defined('ABSPATH')) exit; // 禁止直接访问

/**
 * 请填写描述
 */
class JC_Admin
{
    public function __construct()
    {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_update_category_image', array($this, 'update_category_image'));
    }

    public function enqueue_admin_assets($hook)
    {
        wp_enqueue_style(
            'jelly-products-admin-css',
            JELLY_CATALOG_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            JELLY_CATALOG_VERSION
        );

        wp_enqueue_script(
            'jelly-products-admin-js',
            JELLY_CATALOG_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'jquery-ui-sortable'),
            JELLY_CATALOG_VERSION,
            true
        );

        if (!wp_script_is('jelly-ajax', 'registered')) {
            wp_localize_script('jelly-ajax', 'jelly_ajax_object', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('jelly_nonce')
            ));
        }
    }

    /**
     * 更新分类图片
     * 
     * @since 1.3.0
     * 
     * @return void
     */
    public function update_category_image()
    {

        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jelly_nonce')) {
            wp_send_json_error(__('Security verification failed', 'jelly-catalog'));
        }

        $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
        $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;

        if (!$category_id || !$image_id) {
            wp_send_json_error(__('Missing required parameter', 'jelly-catalog'));
        }

        update_term_meta($category_id, 'thumbnail_id', $image_id);
        wp_send_json_success(__('Category image updated successfully', 'jelly-catalog'));
    }
}
