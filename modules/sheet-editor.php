<?php

/**
 * includes/class-jc-sheet-editor.php
 *
 * @see https://jellydai.com
 * @author Jelly
 * @created 2025.10.03 23:37
 */

namespace Jelly_Catalog\Modules;

if (!defined('ABSPATH')) {
    exit; // 禁止直接访问
}

/**
 * 后台产品表格编辑器管理类
 * 
 * 提供一个类似 Excel 的表格界面，用于批量编辑产品信息
 * 支持分页、批量保存等功能，提高产品管理效率
 * 
 * @since 1.0.0
 */
class Sheet_Editor
{
    /**
     * 构造函数
     * 
     * 注册后台菜单钩子，添加产品表格编辑子菜单
     */
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_menu']);
    }

    /**
     * 向产品菜单添加“表格编辑”子菜单
     * 
     * 在 WordPress 后台的产品菜单下添加一个子菜单项
     * 点击后进入类似 Excel 的批量产品编辑界面
     * 
     * @since 1.0.0
     * @return void
     */
    public function add_menu()
    {
        add_submenu_page(
            'edit.php?post_type=product',
            __('Product Sheet', 'jelly-catalog'),
            __('Product Sheet', 'jelly-catalog'),
            'edit_posts',
            'products-sheet',
            [$this, 'render_products_sheet']
        );
    }

    /**
     * 渲染产品表格编辑器页面
     * 
     * 输出产品表格编辑器的 HTML 结构
     * 包括表格容器和分页控件（首页、上一页、下一页、末页、跳转、每页数量、保存按钮）
     * 
     * @since 1.0.0
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
            [
                'span' => [
                    'id' => [],
                ],
            ]
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
