<?php

/**
 * includes/class-jc-sheet-editor.php
 *
 * @see https://jellydai.com
 * @author Jelly
 * @created 2025.10.03 23:37
 */

if (! defined('ABSPATH')) {
    exit; // 禁止直接访问
}

/**
 * 后台产品表格编辑器
 */
class JC_Sheet_Editor
{
    /**
     * @var JC_Sheet_Editor 单例实例
     */
    protected static $instance;

    /**
     * 获取单例实例
     *
     * @return JC_Sheet_Editor
     */
    public static function instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * 构造函数
     * 注册后台脚本与菜单
     */
    public function __construct()
    {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_script'));
        add_action('admin_menu', array($this, 'add_menu'));
    }

    /**
     * 判断当前是否为产品表格页面
     *
     * @return bool
     */
    protected function is_product_sheet_screen()
    {
        return isset($_GET['post_type'], $_GET['page'])
            && 'product' === sanitize_text_field(wp_unslash($_GET['post_type']))
            && 'products-sheet' === sanitize_text_field(wp_unslash($_GET['page']));
    }

    /**
     * 后台加载所需脚本与样式
     *
     * @return void
     */
    public function enqueue_script()
    {
        if (!$this->is_product_sheet_screen()) {
            return;
        }

        // 引入Tabulator库
        wp_enqueue_style('tabulator', 'https://unpkg.com/tabulator-tables@5.5.0/dist/css/tabulator.min.css');
        wp_enqueue_script('tabulator', 'https://unpkg.com/tabulator-tables@5.5.0/dist/js/tabulator.min.js', array('jquery'), null, true);

        // 加载本地CSS样式文件
        wp_enqueue_style(
            'jelly-catalog-sheet-editor',
            JELLY_CATALOG_PLUGIN_URL . 'assets/css/jelly-catalog.css',
            array(),
            JELLY_CATALOG_VERSION
        );

        // 加载基于Tabulator的JavaScript文件
        wp_enqueue_script(
            'jelly-catalog-sheet-editor',
            JELLY_CATALOG_PLUGIN_URL . 'assets/js/jelly-catalog-sheet-editor-tabulator.js',
            array('jquery', 'tabulator'),
            null,
            true
        );
    }

    /**
     * 向产品菜单添加“表格编辑”子菜单
     *
     * @return void
     */
    public function add_menu()
    {
        add_submenu_page(
            'edit.php?post_type=product',
            __('Product Sheet', 'jelly-catalog'),
            __('Product Sheet', 'jelly-catalog'),
            'manage_options',
            'products-sheet',
            array($this, 'render_products_sheet')
        );
    }

    /**
     * 渲染产品表格页面内容
     *
     * @return void
     */
    public function render_products_sheet()
    {
?>
        <div class="wrap">
            <div id="hot"></div>
            <br>
            <div id="pagination-controls">
                <button class="button" id="first-page">
                    <?php esc_html_e('« First', 'jelly-catalog'); ?>
                </button>
                <button class="button" id="prev-page">
                    <?php esc_html_e('‹ Previous', 'jelly-catalog'); ?>
                </button>
                <span id="page-info">
                    <?php
                    $page_info = sprintf(
                        /* translators: 1: current page placeholder, 2: total pages placeholder */
                        esc_html__('Page %1$s of %2$s', 'jelly-catalog'),
                        '<span id="current-page">1</span>',
                        '<span id="total-pages">1</span>'
                    );

                    echo wp_kses(
                        $page_info,
                        array(
                            'span' => array(
                                'id' => array(),
                            ),
                        )
                    );
                    ?>
                </span>
                <button class="button" id="next-page">
                    <?php esc_html_e('Next ›', 'jelly-catalog'); ?>
                </button>
                <button class="button" id="last-page">
                    <?php esc_html_e('Last »', 'jelly-catalog'); ?>
                </button>
                <span class="pagination-jump">
                    <?php esc_html_e('Jump to:', 'jelly-catalog'); ?>
                    <input type="number" id="goto-page" min="1" style="width: 60px;">
                    <button class="button" id="goto-page-btn">
                        <?php esc_html_e('Go', 'jelly-catalog'); ?>
                    </button>
                </span>
                <span class="per-page-selector">
                    <?php esc_html_e('Per page:', 'jelly-catalog'); ?>
                    <select id="per-page-select">
                        <?php for ($i = 10; $i <= 100; $i += 10): ?>
                            <option value="<?php echo $i; ?>" <?php selected($i, 20); ?>><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </span>
                <button class="button button-primary" id="save-sheet">
                    <?php esc_html_e('Save Sheet', 'jelly-catalog'); ?>
                </button>
            </div>
        </div>
<?php
    }
}

JC_Sheet_Editor::instance();
