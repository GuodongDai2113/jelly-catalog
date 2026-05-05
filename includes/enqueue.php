<?php

/**
 * includes\class-jc-enqueue.php
 *
 * @see https://jellydai.com
 * @author Jelly Dai
 * @created 2026.04.29 00:00
 */

namespace Jelly_Catalog;

if (!defined('ABSPATH')) {
    exit;
} // 禁止直接访问

/**
 * 统一管理前后台样式与脚本加载。
 */
class Enqueue
{
    /**
     * 判断是否需要加载前端基础资源。
     *
     * @return bool
     */
    public static function should_enqueue_frontend_assets()
    {
        return is_jc_product_single()
            || is_jc_product_archive()
            || is_jc_product_search()
            || is_jc_products();
    }

    /**
     * 构造函数。
     */
    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'register_frontend_assets'], 5);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('elementor/frontend/before_register_styles', [$this, 'register_frontend_assets']);
        add_action('elementor/frontend/before_register_scripts', [$this, 'register_frontend_assets']);
        add_action('elementor/preview/enqueue_styles', [$this, 'enqueue_preview_style']);
        add_action('elementor/preview/enqueue_scripts', [$this, 'enqueue_preview_script']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * 注册可由组件按需声明的前端资源。
     *
     * @return void
     */
    public function register_frontend_assets()
    {
        wp_register_style(
            'jelly-catalog-gallery',
            JELLY_CATALOG_PLUGIN_URL . 'assets/css/jelly-catalog-gallery.css',
            [],
            JELLY_CATALOG_VERSION
        );

        wp_register_script(
            'jelly-catalog-gallery',
            JELLY_CATALOG_PLUGIN_URL . 'assets/js/jelly-catalog-gallery.js',
            ['jquery'],
            JELLY_CATALOG_VERSION,
            true
        );
    }

    /**
     * 加载前端基础资源。
     *
     * @return void
     */
    public function enqueue_frontend_assets()
    {
        if (!self::should_enqueue_frontend_assets()) {
            return;
        }

        wp_enqueue_style(
            'jelly-catalog-main',
            JELLY_CATALOG_PLUGIN_URL . 'assets/css/jelly-catalog-main.css',
            [],
            JELLY_CATALOG_VERSION
        );

        wp_enqueue_script(
            'jelly-catalog-main',
            JELLY_CATALOG_PLUGIN_URL . 'assets/js/jelly-catalog-main.js',
            [],
            JELLY_CATALOG_VERSION,
            true
        );
    }

    /**
     * 加载前台预览资源。
     *
     * @return void
     */
    public function enqueue_preview_style()
    {
        wp_enqueue_style(
            'jelly-catalog-main-preview',
            JELLY_CATALOG_PLUGIN_URL . 'assets/css/jelly-catalog-main.css',
            [],
            JELLY_CATALOG_VERSION
        );
        wp_enqueue_style(
            'jelly-catalog-gallery-preview',
            JELLY_CATALOG_PLUGIN_URL . 'assets/css/jelly-catalog-gallery.css',
            [],
            JELLY_CATALOG_VERSION
        );
    }

    /**
     * 加载预览脚本。
     *
     * @return void
     */
    public function enqueue_preview_script()
    {
        wp_enqueue_script(
            'jelly-catalog-main-preview',
            JELLY_CATALOG_PLUGIN_URL . 'assets/js/jelly-catalog-main.js',
            [],
            JELLY_CATALOG_VERSION,
            true
        );
        wp_enqueue_script(
            'jelly-catalog-gallery-preview',
            JELLY_CATALOG_PLUGIN_URL . 'assets/js/jelly-catalog-gallery.js',
            [],
            JELLY_CATALOG_VERSION,
            true
        );
    }

    /**
     * 根据后台页面加载对应资源。
     *
     * @param string $hook 当前后台页面 hook。
     * @return void
     */
    public function enqueue_admin_assets($hook)
    {
        $this->enqueue_admin_ajax_settings();

        if ($this->is_product_edit_screen()) {
            $this->enqueue_product_admin_assets($hook);
        }

        if ($this->is_product_sheet_screen()) {
            $this->enqueue_sheet_editor_assets();
        }

        if ($this->is_product_port_screen($hook)) {
            $this->enqueue_port_import_assets();
        }
    }

    /**
     * 加载产品编辑、列表、分类相关后台资源。
     *
     * @param string $hook 当前后台页面 hook。
     * @return void
     */
    private function enqueue_product_admin_assets($hook)
    {
        $is_product_editor = in_array($hook, ['post.php', 'post-new.php'], true);
        $is_product_taxonomy = in_array($hook, ['edit-tags.php', 'term.php'], true);

        wp_enqueue_style(
            'jelly-catalog-admin',
            JELLY_CATALOG_PLUGIN_URL . 'assets/css/jelly-catalog-admin.css',
            [],
            JELLY_CATALOG_VERSION
        );

        wp_enqueue_script(
            'jelly-core',
            JELLY_CATALOG_PLUGIN_URL . 'assets/js/jelly-core.js',
            ['jquery'],
            '1.0.2',
            true
        );

        wp_enqueue_style(
            'jelly-core',
            JELLY_CATALOG_PLUGIN_URL . 'assets/css/jelly-core.css',
            [],
            '1.0.2'
        );

        if ($is_product_editor || $is_product_taxonomy) {
            wp_enqueue_media();

            wp_enqueue_script(
                'jelly-catalog-admin-repeater',
                JELLY_CATALOG_PLUGIN_URL . 'assets/js/jelly-catalog-admin-repeater.js',
                ['jquery'],
                JELLY_CATALOG_VERSION,
                true
            );

            wp_localize_script(
                'jelly-catalog-admin-repeater',
                'jc_product_i18n',
                $this->get_product_i18n()
            );
        }

        if ($is_product_editor) {
            wp_enqueue_script(
                'jelly-catalog-admin-product',
                JELLY_CATALOG_PLUGIN_URL . 'assets/js/jelly-catalog-admin-product.js',
                ['jquery', 'jquery-ui-sortable'],
                JELLY_CATALOG_VERSION,
                true
            );
        }

        if ($is_product_taxonomy) {
            wp_enqueue_script(
                'jelly-catalog-admin-category',
                JELLY_CATALOG_PLUGIN_URL . 'assets/js/jelly-catalog-admin-category.js',
                ['jquery'],
                JELLY_CATALOG_VERSION,
                true
            );
        }
    }

    /**
     * 加载产品表格编辑器资源。
     *
     * @return void
     */
    private function enqueue_sheet_editor_assets()
    {
        wp_enqueue_style('tabulator', 'https://unpkg.com/tabulator-tables@5.5.0/dist/css/tabulator.min.css', [], '5.5.0');
        wp_enqueue_script('tabulator', 'https://unpkg.com/tabulator-tables@5.5.0/dist/js/tabulator.min.js', ['jquery'], '5.5.0', true);

        wp_enqueue_style(
            'jelly-catalog-admin-sheet-editor',
            JELLY_CATALOG_PLUGIN_URL . 'assets/css/jelly-catalog-admin.css',
            [],
            JELLY_CATALOG_VERSION
        );

        wp_enqueue_script(
            'jelly-catalog-admin-sheet-editor',
            JELLY_CATALOG_PLUGIN_URL . 'assets/js/jelly-catalog-admin-sheet-editor.js',
            ['jquery', 'tabulator'],
            JELLY_CATALOG_VERSION,
            true
        );
    }

    /**
     * 加载导入导出页资源。
     *
     * @return void
     */
    private function enqueue_port_import_assets()
    {
        wp_enqueue_script(
            'jelly-catalog-admin-port',
            JELLY_CATALOG_PLUGIN_URL . 'assets/js/jelly-catalog-admin-port.js',
            ['jquery'],
            JELLY_CATALOG_VERSION,
            true
        );

        wp_localize_script('jelly-catalog-admin-port', 'jcPortImport', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('jc_import_products'),
            'resumeJobId' => isset($_GET['jc_import_job']) ? sanitize_key(wp_unslash($_GET['jc_import_job'])) : '',
            'maxRetries' => max(0, absint(apply_filters('jc_import_ajax_max_retries', 3))),
            'retryDelay' => max(250, absint(apply_filters('jc_import_ajax_retry_delay', 1500))),
            'maxLockWaits' => max(1, absint(apply_filters('jc_import_lock_max_waits', 60))),
            'messages' => [
                'uploading' => __('Uploading files and preparing import...', 'jelly-catalog'),
                'processing' => __('Importing products...', 'jelly-catalog'),
                'complete' => __('Import completed.', 'jelly-catalog'),
                'failed' => __('Import failed.', 'jelly-catalog'),
                'networkError' => __('Network error. Please try again.', 'jelly-catalog'),
                'retrying' => __('Temporary error. Retrying...', 'jelly-catalog'),
                'resumeReady' => __('Import paused. You can retry from the last saved position.', 'jelly-catalog'),
                'lockTimeout' => __('Import is still locked by another request. Please retry from the last saved position.', 'jelly-catalog'),
            ],
        ]);

        wp_enqueue_style(
            'jelly-catalog-admin-port',
            JELLY_CATALOG_PLUGIN_URL . 'assets/css/jelly-catalog-admin-port.css',
            [],
            JELLY_CATALOG_VERSION
        );
    }

    /**
     * 输出后台 AJAX 配置。
     *
     * @return void
     */
    private function enqueue_admin_ajax_settings()
    {
        if (!$this->is_product_admin_screen()) {
            return;
        }

        wp_enqueue_script('jquery');
        wp_localize_script('jquery', 'jc_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('jc_nonce'),
        ]);
    }

    /**
     * 判断是否为产品相关后台页面。
     *
     * @return bool
     */
    private function is_product_admin_screen()
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;

        if (!$screen) {
            return false;
        }

        $post_type = $screen->post_type ?? '';
        $taxonomy = $screen->taxonomy ?? '';
        $screen_id = $screen->id ?? '';

        return 'product' === $post_type
            || in_array($taxonomy, ['product_cat', 'product_tag'], true)
            || false !== strpos($screen_id, 'product_page_')
            || false !== strpos($screen_id, 'edit-product_page_');
    }

    /**
     * 判断是否为产品编辑、列表或分类页。
     *
     * @return bool
     */
    private function is_product_edit_screen()
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;

        if (!$screen) {
            return false;
        }

        if (in_array($screen->base, ['post', 'edit'], true) && 'product' === $screen->post_type) {
            return true;
        }

        return in_array($screen->base, ['edit-tags', 'term'], true)
            && in_array($screen->taxonomy, ['product_cat', 'product_tag'], true);
    }

    /**
     * 判断是否为产品表格页。
     *
     * @return bool
     */
    private function is_product_sheet_screen()
    {
        return isset($_GET['post_type'], $_GET['page'])
            && 'product' === sanitize_text_field(wp_unslash($_GET['post_type']))
            && 'products-sheet' === sanitize_text_field(wp_unslash($_GET['page']));
    }

    /**
     * 判断是否为导入导出页。
     *
     * @param string $hook 当前后台页面 hook。
     * @return bool
     */
    private function is_product_port_screen($hook)
    {
        return 'product_page_products-port' === $hook;
    }

    /**
     * 产品后台编辑器本地化文案。
     *
     * @return array
     */
    private function get_product_i18n()
    {
        return [
            'postdivrich' => __('Product Description', 'jelly-catalog'),
            'bulk_create_title' => __('Bulk Create Items', 'jelly-catalog'),
            'bulk_create_placeholder' => __("Enter content here...\nFor FAQ: First line is Question, second line is Answer\nQuestion 1\nAnswer 1\nQuestion 2\nAnswer 2\n...\n\nFor Attributes: First line is Name, second line is Value\nName 1\nValue 1\nName 2\nValue 2...", 'jelly-catalog'),
            'create_items_btn' => __('Create Items', 'jelly-catalog'),
            'cancel_btn' => __('Cancel', 'jelly-catalog'),
            'success_title' => __('Success', 'jelly-catalog'),
            'error_title' => __('Error', 'jelly-catalog'),
            'ok_btn' => __('OK', 'jelly-catalog'),
            'no_content_error' => __('Please enter content.', 'jelly-catalog'),
            'no_valid_items_error' => __('No valid items found.', 'jelly-catalog'),
            'success_created_items' => __('Successfully created {count} items.', 'jelly-catalog'),
            'delete_item_tooltip' => __('Delete item', 'jelly-catalog'),
            'add_new_item_btn' => __('Add New Item', 'jelly-catalog'),
            'bulk_create_tooltip' => __('Bulk Create Items from Text', 'jelly-catalog'),
            'bulk_create_btn' => __('Bulk Create', 'jelly-catalog'),
            'faq_question_label' => __('Question', 'jelly-catalog'),
            'faq_answer_label' => __('Answer', 'jelly-catalog'),
            'attribute_name_label' => __('Name', 'jelly-catalog'),
            'attribute_value_label' => __('Value', 'jelly-catalog'),
            'product_faqs' => __('Product FAQs', 'jelly-catalog'),
            'product_attributes' => __('Product Attributes', 'jelly-catalog'),
            'product_cat_faqs' => __('Category FAQs', 'jelly-catalog'),
        ];
    }
}
