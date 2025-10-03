<?php

/**
 * includes\clsss-jc-sheet-editor.php
 * 
 * @see: https://jellydai.com
 * @author: Jelly Dai <d@jellydai.com>
 * @created : 2025.10.03 23:37
 */

if (! defined('ABSPATH')) exit; // 禁止直接访问

class JC_Sheet_Editor
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
        add_action('admin_enqueue_scripts', array($this, 'enqueue_script'));
        add_action('admin_menu', array($this, 'add_menu'));
    }

    public function enqueue_script($hook)
    {
        if ($hook !== 'toplevel_page_products-sheet') return;

        wp_enqueue_style('handsontable', 'https://cdn.jsdelivr.net/npm/handsontable@14.3.0/dist/handsontable.min.css');
        wp_enqueue_script('handsontable', 'https://cdn.jsdelivr.net/npm/handsontable@14.3.0/dist/handsontable.min.js', [], null, true);

        wp_enqueue_script('jelly-catalog-sheet-editor', JELLY_CATALOG_PLUGIN_URL . 'assets/js/jelly-catalog-sheet-editor.js', ['jquery', 'handsontable'], null, true);
    }

    public function add_menu()
    {
        add_menu_page(
            '产品表格',
            '产品表格demo',
            'manage_options',
            'products-sheet',
            array($this, 'render_products_sheet'),
            'dashicons-table-col-after',
            58
        );
    }

    public function render_products_sheet()
    {
?>
        <div class="wrap">
            <div id="hot"></div>
            <br>
            <div id="pagination-controls">
                <button class="button" id="first-page">&laquo; 首页</button>
                <button class="button" id="prev-page">&lt; 上一页</button>
                <span id="page-info">第 <span id="current-page">1</span> 页，共 <span id="total-pages">1</span> 页</span>
                <button class="button" id="next-page">下一页 &gt;</button>
                <button class="button" id="last-page">末页 &raquo;</button>
                <span>跳转到: <input type="number" id="goto-page" min="1" style="width: 60px;"> <button class="button" id="goto-page-btn">Go</button></span>
            </div>
            <br>
            <button class="button" id="save-sheet"><?php esc_html_e('Save','jelly-catalog') ?></button>
        </div>
<?php
    }
}
JC_Sheet_Editor::instance();
