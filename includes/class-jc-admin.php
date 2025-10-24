<?php

/**
 * includes/class-jc-admin.php
 *
 * @see https://jellydai.com
 * @author Jelly Dai
 * @created 2025.07.29 14:47
 */

if (! defined('ABSPATH')) {
    exit; // 禁止直接访问
}

/**
 * 后台相关管理逻辑
 */
class JC_Admin
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
     * 注册后台钩子并按需加载目录模块
     */
    public function __construct()
    {
        $this->register_global_hooks();

        if (!jc_is_woocommerce_activated()) {
            $this->init_catalog_modules();
        }
    }

    /**
     * 注册与后台无关 WooCommerce 状态的通用钩子
     *
     * @return void
     */
    protected function register_global_hooks()
    {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_filter('jelly_register_sections', array($this, 'add_theme_sections'));
        add_filter('jelly_register_tabs', array($this, 'add_theme_tabs'));
    }

    /**
     * 初始化产品目录相关的后台模块
     * 主要注册列表列、筛选器等钩子
     *
     * @return void
     */
    public function init_catalog_modules()
    {
        $this->register_product_list_hooks();
        $this->register_product_filter_hooks();
        $this->register_product_category_hooks();
    }

    /**
     * 注册产品列表页的列与排序逻辑
     *
     * @return void
     */
    protected function register_product_list_hooks()
    {
        add_filter('manage_edit-product_columns', array($this, 'columns'), 5);
        add_filter('manage_edit-product_columns', array($this, 'define_columns'), 10);
        add_action('manage_product_posts_custom_column', array($this, 'column'), 10, 2);

        add_filter('manage_edit-product_sortable_columns', array($this, 'sortable_columns'));
        add_action('pre_get_posts', array($this, 'product_orderby'));
    }

    /**
     * 注册产品列表页的筛选器
     *
     * @return void
     */
    protected function register_product_filter_hooks()
    {
        add_action('restrict_manage_posts', array($this, 'product_filters'));
    }

    /**
     * 注册产品分类页面的列
     *
     * @return void
     */
    protected function register_product_category_hooks()
    {
        add_filter('manage_edit-product_cat_columns', array($this, 'product_cat_columns'));
        add_filter('manage_product_cat_custom_column', array($this, 'product_cat_column'), 10, 3);
    }

    /**
     * 判断当前页面是否与产品后台编辑相关
     *
     * @return bool
     */
    private function is_product_edit_page(): bool
    {
        // 检查是否在管理后台环境
        if (!is_admin()) {
            return false;
        }

        global $pagenow, $typenow, $hook_suffix;

        // 获取当前页面信息
        $current_page = isset($pagenow) ? $pagenow : '';
        $current_post_type = isset($typenow) ? $typenow : '';

        // 判断是否为产品相关的编辑页面
        $is_product_page = (
            // 产品列表页面
            ($current_page === 'edit.php' && $current_post_type === 'product') ||
            // 添加新产品页面
            ($current_page === 'post-new.php' && $current_post_type === 'product') ||
            // 编辑产品页面
            ($current_page === 'post.php' && $current_post_type === 'product') ||
            // 产品分类页面
            ($current_page === 'edit-tags.php' && isset($_GET['taxonomy']) && $_GET['taxonomy'] === 'product_cat') ||
            // 产品标签页面
            ($current_page === 'edit-tags.php' && isset($_GET['taxonomy']) && $_GET['taxonomy'] === 'product_tag')
        );

        return $is_product_page;
    }

    /**
     * 加载后台样式与脚本
     *
     * @param string $hook 当前页面 Hook 名称
     * @return void
     */
    public function enqueue_admin_assets($hook)
    {
        if (!$this->is_product_edit_page()) {
            return;
        }


        wp_enqueue_style(
            'jelly-catalog-admin',
            JELLY_CATALOG_PLUGIN_URL . 'assets/css/jelly-catalog.css',
            array(),
            JELLY_CATALOG_VERSION
        );

        wp_enqueue_media();

        if ($hook === 'edit.php' && !jc_is_woocommerce_activated()) {
            wp_enqueue_script(
                'jelly-catalog-io',
                JELLY_CATALOG_PLUGIN_URL . 'assets/js/jelly-catalog-io.js',
                array('jquery'),
                JELLY_CATALOG_VERSION,
                true
            );
        }

        if ($hook === 'post.php' || $hook === 'post-new.php') {
            wp_enqueue_script(
                'jelly-catalog-product-editor',
                JELLY_CATALOG_PLUGIN_URL . 'assets/js/jelly-catalog-product-editor.js',
                array('jquery', 'jquery-ui-sortable'),
                JELLY_CATALOG_VERSION,
                true
            );
        }

        if ($hook === 'edit-tags.php') {
            wp_enqueue_script(
                'jelly-catalog-editor',
                JELLY_CATALOG_PLUGIN_URL . 'assets/js/jelly-catalog-category-editor.js',
                array('jquery'),
                JELLY_CATALOG_VERSION,
                true
            );
        }
    }

    /**
     * 调整产品列表列顺序，确保缩略图列在最前
     *
     * @param array $columns 原始列数组
     * @return array
     */
    public function columns($columns)
    {
        if (empty($columns) || !isset($columns['cb'])) {
            return $columns;
        }

        $new_columns           = array();
        $new_columns['cb']     = $columns['cb'];
        $new_columns['jc-thumb'] = __('Image', 'jelly-catalog');
        unset($columns['cb']);

        return array_merge($new_columns, $columns);
    }

    /**
     * 在标题列后插入分类、标签及完整度列
     *
     * @param array $columns 当前列数组
     * @return array
     */
    public function define_columns($columns)
    {
        $reordered = array();
        foreach ($columns as $key => $value) {
            $reordered[$key] = $value;

            if ('title' === $key) {
                $reordered['product_cat']          = __('Categories', 'jelly-catalog');
                $reordered['product_tag']          = __('Tags', 'jelly-catalog');
                $reordered['product_completeness'] = __('Completeness', 'jelly-catalog');
            }
        }

        return $reordered;
    }

    /**
     * 渲染产品列表的自定义列
     *
     * @param string $column 列名
     * @param int    $post_id 产品 ID
     * @return void
     */
    public function column($column, $post_id)
    {
        switch ($column) {
            case 'jc-thumb':
                $this->render_product_thumbnail_column($post_id);
                break;
            case 'product_cat':
                $this->render_product_category_column($post_id);
                break;
            case 'product_tag':
                $this->render_product_tag_column($post_id);
                break;
            case 'product_completeness':
                $this->render_product_completeness_column($post_id);
                break;
        }
    }

    /**
     * 输出产品缩略图及画廊数量
     *
     * @param int $post_id 产品 ID
     * @return void
     */
    protected function render_product_thumbnail_column($post_id)
    {
        $thumbnail_id = get_post_meta($post_id, '_thumbnail_id', true);
        $image        = $thumbnail_id ? wp_get_attachment_url($thumbnail_id) : '';

        if ($thumbnail_id) {
            $gallery_ids = get_post_meta($post_id, '_product_image_gallery', true);
            if (!empty($gallery_ids)) {
                $gallery = array_filter(explode(',', $gallery_ids));
                printf(
                    '<div class="jc-gallery-count">%d</div>',
                    count($gallery) + 1 // +1 代表封面图
                );
            }
        }

        if (empty($image)) {
            $image = jc_placeholder_img_src();
        }

        echo '<img src="' . esc_url($image) . '" alt="Thumbnail" class="wp-post-image" height="48" width="48" />';
    }

    /**
     * 输出产品分类列
     *
     * @param int $post_id 产品 ID
     * @return void
     */
    protected function render_product_category_column($post_id)
    {
        $terms = get_the_terms($post_id, 'product_cat');
        if (empty($terms) || is_wp_error($terms)) {
            echo '<span aria-hidden="true">—</span>';
            return;
        }

        $links = array();
        foreach ($terms as $term) {
            $level = 0;
            if ($term->parent) {
                $ancestors = get_ancestors($term->term_id, 'product_cat');
                $level     = count($ancestors);
            }

            $level_class = 'level-' . max(1, min(3, $level + 1));

            $links[] = sprintf(
                '<a href="%s" class="%s">%s</a>',
                esc_url(add_query_arg(array('post_type' => 'product', 'product_cat' => $term->slug), 'edit.php')),
                esc_attr($level_class),
                esc_html(sanitize_term_field('name', $term->name, $term->term_id, 'product_cat', 'display'))
            );
        }

        echo implode(', ', $links);
    }

    /**
     * 输出产品标签列
     *
     * @param int $post_id 产品 ID
     * @return void
     */
    protected function render_product_tag_column($post_id)
    {
        $terms = get_the_terms($post_id, 'product_tag');
        if (empty($terms) || is_wp_error($terms)) {
            echo '<span aria-hidden="true">—</span>';
            return;
        }

        $links = array();
        foreach ($terms as $term) {
            $links[] = sprintf(
                '<a href="%s">%s</a>',
                esc_url(add_query_arg(array('post_type' => 'product', 'product_tag' => $term->slug), 'edit.php')),
                esc_html(sanitize_term_field('name', $term->name, $term->term_id, 'product_tag', 'display'))
            );
        }

        echo implode(', ', $links);
    }

    /**
     * 输出产品完整度列
     *
     * @param int $post_id 产品 ID
     * @return void
     */
    protected function render_product_completeness_column($post_id)
    {
        $post    = get_post($post_id);
        $excerpt = $post ? $post->post_excerpt : '';
        $content = $post ? $post->post_content : '';

        $excerpt_length = strlen(trim(strip_tags($excerpt)));
        $content_length = strlen(trim(strip_tags($content)));

        $excerpt_complete = $excerpt_length >= 60;
        $content_complete = $content_length >= 100;

        $this->print_completeness_line($excerpt_complete, __('Description', 'jelly-catalog'));
        $this->print_completeness_line($content_complete, __('Content', 'jelly-catalog'));
    }

    /**
     * 输出单行完整度提示
     *
     * @param bool   $is_complete 是否达标
     * @param string $label       对应字段标签
     * @return void
     */
    protected function print_completeness_line($is_complete, $label)
    {
        $icon = $is_complete
            ? '<span class="dashicons dashicons-yes-alt" style="color:#52c41a;"></span>'
            : '<span class="dashicons dashicons-dismiss" style="color:#ff4d4f;"></span>';

        printf('<p class="jc-completeness">%s%s</p>', $icon, esc_html($label));
    }

    /**
     * 定义产品分类页的列
     *
     * @param array $columns 原始列数组
     * @return array
     */
    public function product_cat_columns($columns)
    {
        $new_columns = array();

        if (isset($columns['cb'])) {
            $new_columns['cb'] = $columns['cb'];
            unset($columns['cb']);
        }

        $new_columns['jc-thumb'] = __('Image', 'jelly-catalog');
        $columns                 = array_merge($new_columns, $columns);

        $columns['handle'] = '';

        return $columns;
    }

    /**
     * 渲染产品分类页的自定义列
     *
     * @param string $content 当前列输出
     * @param string $column  列名
     * @param int    $term_id 分类 ID
     * @return string
     */
    public function product_cat_column($content, $column, $term_id)
    {
        if ('jc-thumb' === $column) {
            return $content . $this->render_product_category_thumbnail($term_id);
        }

        if ('handle' === $column) {
            $content .= '<input type="hidden" name="term_id" value="' . esc_attr($term_id) . '" />';
        }

        if ('description' === $column) {
            $term     = get_term($term_id, 'product_cat');
            $content .= esc_html($term->description);
        }

        return $content;
    }

    /**
     * 渲染分类缩略图 HTML
     *
     * @param int $term_id 分类 ID
     * @return string
     */
    protected function render_product_category_thumbnail($term_id)
    {
        $thumbnail_id = get_term_meta($term_id, 'thumbnail_id', true);
        $image        = $thumbnail_id ? wp_get_attachment_thumb_url($thumbnail_id) : jc_placeholder_img_src();

        $image = str_replace(' ', '%20', $image);

        return sprintf(
            '<img src="%s" alt="%s" class="wp-post-image" height="48" width="48" />',
            esc_url($image),
            esc_attr__('Thumbnail', 'jelly-catalog')
        );
    }

    /**
     * 声明可排序列
     *
     * @param array $columns 列数组
     * @return array
     */
    public function sortable_columns($columns)
    {
        $columns['product_cat'] = 'product_cat';
        $columns['product_tag'] = 'product_tag';

        return $columns;
    }

    /**
     * 自定义排序逻辑
     *
     * @param WP_Query $query 查询对象
     * @return void
     */
    public function product_orderby($query)
    {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        if ('product_cat' === $query->get('orderby')) {
            $query->set('orderby', 'taxonomy');
            $query->set('taxonomy', 'product_cat');
        }

        if ('product_tag' === $query->get('orderby')) {
            $query->set('orderby', 'taxonomy');
            $query->set('taxonomy', 'product_tag');
        }
    }

    /**
     * 输出产品列表的分类与标签筛选器
     *
     * @return void
     */
    public function product_filters()
    {
        global $typenow;

        if ('product' !== $typenow) {
            return;
        }

        $this->render_product_category_filter();
        $this->render_product_tag_filter();
    }

    /**
     * 输出分类筛选下拉框
     *
     * @return void
     */
    protected function render_product_category_filter()
    {
        $current_cat = isset($_GET['product_cat']) ? sanitize_text_field($_GET['product_cat']) : '';
        $cat_terms   = get_terms(array(
            'taxonomy'     => 'product_cat',
            'hide_empty'   => false,
            'hierarchical' => true,
            'orderby'      => 'name',
        ));

        if (empty($cat_terms) || is_wp_error($cat_terms)) {
            return;
        }

        echo '<select name="product_cat">';
        echo '<option value="">' . esc_html__('All categories', 'jelly-catalog') . '</option>';

        echo walk_category_dropdown_tree($cat_terms, 0, array(
            'selected'    => $current_cat,
            'value_field' => 'slug',
            'show_count'  => true,
        ));

        echo '</select>';
    }

    /**
     * 输出标签筛选下拉框
     *
     * @return void
     */
    protected function render_product_tag_filter()
    {
        $current_tag = isset($_GET['product_tag']) ? sanitize_text_field($_GET['product_tag']) : '';
        $tag_terms   = get_terms(array(
            'taxonomy'   => 'product_tag',
            'hide_empty' => false,
        ));

        if (empty($tag_terms) || is_wp_error($tag_terms)) {
            return;
        }

        echo '<select name="product_tag">';
        echo '<option value="">' . esc_html__('All tags', 'jelly-catalog') . '</option>';

        foreach ($tag_terms as $term) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr($term->slug),
                selected($current_tag, $term->slug, false),
                esc_html($term->name)
            );
        }

        echo '</select>';
    }

    /**
     * 向主题设置面板新增标签页
     *
     * @param array $tabs 已注册标签数组
     * @return array
     */
    public function add_theme_tabs($tabs)
    {
        $tabs['product'] = array(
            'title' => esc_html__('Product', 'jelly-catalog'),
            'icon'  => 'ri-puzzle-line',
        );

        return $tabs;
    }

    /**
     * 向主题设置面板新增产品相关配置节
     *
     * @param array $sections 已注册配置节
     * @return array
     */
    public function add_theme_sections($sections)
    {
        $pages        = get_pages();
        $page_options = array(
            '' => __('None', 'jelly-catalog'),
        );

        foreach ($pages as $page) {
            $page_options[$page->ID] = $page->post_title;
        }

        $sections['product'] = array(
            'product' => array(
                'title'  => '',
                'fields' => array(
                    array(
                        'id'          => 'products_per_page',
                        'label'       => __('Products per page', 'jelly-catalog'),
                        'type'        => 'number',
                        'wp_sync'     => 'products_per_page',
                        'description' => __('Number of products to show per page.', 'jelly-catalog'),
                    ),
                ),
            ),
        );

        return $sections;
    }
}
JC_Admin::instance();
