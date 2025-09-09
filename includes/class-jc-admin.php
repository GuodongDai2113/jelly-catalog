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
        if (!JELLY_CATALOG_WC_ACTIVE) {

            add_filter('manage_edit-product_columns', array($this, 'columns'));
            add_action('manage_product_posts_custom_column', array($this, 'column'), 10, 2);

            add_filter('manage_edit-product_cat_columns', array($this, 'product_cat_columns'));
            add_filter('manage_product_cat_custom_column', array($this, 'product_cat_column'), 10, 3);
        }
    }

    public function enqueue_admin_assets()
    {
        wp_enqueue_style(
            'jelly-catalog-admin',
            JELLY_CATALOG_PLUGIN_URL . 'assets/css/jelly-catalog.css',
            array(),
            JELLY_CATALOG_VERSION
        );

        wp_enqueue_script(
            'jelly-catalog-admin',
            JELLY_CATALOG_PLUGIN_URL . 'assets/js/jelly-catalog.js',
            array('jquery', 'jquery-ui-sortable'),
            JELLY_CATALOG_VERSION,
            true
        );

        wp_enqueue_script(
            'jelly-catalog-editor',
            JELLY_CATALOG_PLUGIN_URL . 'assets/js/jelly-catalog-editor.js',
            array('jquery'),
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

    /**
     * Columns function.
     *
     * @param mixed $columns Columns.
     */
    public function columns($columns)
    {
        if (empty($columns)) {
            return $columns;
        }

        $new_columns          = array();
        $new_columns['cb']    = $columns['cb'];
        $new_columns['jc-thumb'] = __('Image', 'jelly-catalog');
        unset($columns['cb']);
        $columns = array_merge($new_columns, $columns);
        return $columns;
    }

    /**
     * Column function.
     *
     * @param string $column Column name.
     * @param int    $id     Post ID.
     */
    public function column($column, $id)
    {
        if ('jc-thumb' === $column) {
            $image        = '';
            $thumbnail_id = get_post_meta($id, '_thumbnail_id', true);

            if ($thumbnail_id) {
                $image = wp_get_attachment_url($thumbnail_id);
                $images = get_post_meta($id, '_product_image_gallery', true);
                if (!empty($images)) {
                    $gallery = explode(',', $images);
                    $gallery_count = count($gallery);
                    echo sprintf('<div class="jc-gallery-count">%d</div>', $gallery_count + 1);
                }
            }
            if (empty($image)) {
                $image = jc_placeholder_img_src();
            }

            echo '<img src="' . $image . '" alt="Thumbnail" class="wp-post-image" height="48" width="48" />';
        }
    }

    /**
     * Thumbnail column value added to category admin.
     *
     * @param string $columns Column HTML output.
     * @param string $column Column name.
     * @param int    $id Product ID.
     *
     * @return string
     */
    public function product_cat_column($columns, $column, $id)
    {
        if ('jc-thumb' === $column) {

            $thumbnail_id = get_term_meta($id, 'thumbnail_id', true);

            if ($thumbnail_id) {
                $image = wp_get_attachment_thumb_url($thumbnail_id);
            } else {
                $image = jc_placeholder_img_src();
            }

            // Prevent esc_url from breaking spaces in urls for image embeds. Ref: https://core.trac.wordpress.org/ticket/23605 .
            $image    = str_replace(' ', '%20', $image);
            $columns .= '<img src="' . esc_url($image) . '" alt="' . esc_attr__('Thumbnail', 'jelly-catalog') . '" class="wp-post-image" height="48" width="48" />';
        }
        if ('handle' === $column) {
            $columns .= '<input type="hidden" name="term_id" value="' . esc_attr($id) . '" />';
        }
        return $columns;
    }


    /**
     * Thumbnail column added to category admin.
     *
     * @param mixed $columns Columns array.
     * @return array
     */
    public function product_cat_columns($columns)
    {
        $new_columns = array();

        if (isset($columns['cb'])) {
            $new_columns['cb'] = $columns['cb'];
            unset($columns['cb']);
        }

        $new_columns['jc-thumb'] = __('Image', 'woocommerce');

        $columns           = array_merge($new_columns, $columns);
        $columns['handle'] = '';

        return $columns;
    }
}
