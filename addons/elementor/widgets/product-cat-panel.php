<?php

/**
 * addons\elementor\widgets\product-cat-panel.php
 *
 * @see: https://jellydai.com
 * @author: Jelly Dai <daiguo1003@gmail.com>
 * @created : 2026.06.03 00:00
 */

namespace Jelly_Catalog\Addons\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
} // 禁止直接访问

/**
 * 产品分类面板组件。
 */
class Product_Cat_Panel extends Widget_Base
{
    /**
     * 获取组件唯一名称。
     *
     * @return string
     */
    public function get_name()
    {
        return 'jc-product-cat-panel';
    }

    /**
     * 获取组件标题。
     *
     * @return string
     */
    public function get_title()
    {
        return esc_html__('Product Category Panel', 'jelly-catalog');
    }

    /**
     * 获取组件图标。
     *
     * @return string
     */
    public function get_icon()
    {
        return 'eicon-menu-bar jelly-engine-icon';
    }

    /**
     * 获取组件分类。
     *
     * @return array
     */
    public function get_categories()
    {
        return ['jc-elements-archive', 'jc-elements-single'];
    }

    /**
     * 获取组件关键词。
     *
     * @return array
     */
    public function get_keywords()
    {
        return ['product', 'category', 'panel', 'menu', 'tree'];
    }

    /**
     * 返回组件依赖的样式句柄。
     *
     * @return array
     */
    public function get_style_depends()
    {
        return ['jelly-catalog-product-cat-panel'];
    }

    /**
     * 返回组件依赖的脚本句柄。
     *
     * @return array
     */
    public function get_script_depends()
    {
        return ['jelly-catalog-product-cat-panel'];
    }

    /**
     * 注册组件控制项。
     *
     * @return void
     */
    protected function register_controls()
    {
        $this->start_controls_section(
            'section_content',
            [
                'label' => esc_html__('Content', 'jelly-catalog'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'panel_title',
            [
                'label' => esc_html__('Panel Title', 'jelly-catalog'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Product Categories', 'jelly-catalog'),
            ]
        );

        $this->add_control(
            'hide_empty',
            [
                'label' => esc_html__('Hide Empty Categories', 'jelly-catalog'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'jelly-catalog'),
                'label_off' => esc_html__('No', 'jelly-catalog'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_title_style',
            [
                'label' => esc_html__('Title', 'jelly-catalog'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'selector' => '{{WRAPPER}} .jc-product-category-panel__title',
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label' => esc_html__('Text Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .jc-product-category-panel__title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'title_background',
            [
                'label' => esc_html__('Background Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .jc-product-category-panel__title' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_panel_style',
            [
                'label' => esc_html__('Panel', 'jelly-catalog'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'panel_border',
                'selector' => '{{WRAPPER}} .jc-product-category-tree',
            ]
        );

        $this->add_responsive_control(
            'panel_border_radius',
            [
                'label' => esc_html__('Border Radius', 'jelly-catalog'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'rem', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .jc-product-category-panel__title' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} 0 0;',
                    '{{WRAPPER}} .jc-product-category-tree' => 'border-radius: 0 0 {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * 输出前台内容。
     *
     * @return void
     */
    protected function render()
    {
        if (!taxonomy_exists('product_cat')) {
            return;
        }

        $settings = $this->get_settings_for_display();
        $current_term = $this->get_current_product_term();
        $current_term_id = $current_term instanceof \WP_Term ? (int) $current_term->term_id : 0;
        $current_parent_id = $current_term instanceof \WP_Term ? (int) $current_term->parent : 0;
        $ancestor_ids = $current_term_id ? get_ancestors($current_term_id, 'product_cat', 'taxonomy') : [];
        $menu_items = wp_list_categories(
            [
                'taxonomy' => 'product_cat',
                'hide_empty' => ($settings['hide_empty'] ?? 'yes') === 'yes',
                'hierarchical' => true,
                'orderby' => 'name',
                'order' => 'ASC',
                'title_li' => '',
                'echo' => false,
                'walker' => new Product_Cat_Menu_Walker($current_term_id, $current_parent_id, $ancestor_ids),
            ]
        );

        if (empty($menu_items)) {
            return;
        }

        $panel_title = $settings['panel_title'] ?? esc_html__('Product Categories', 'jelly-catalog');
        ?>
<section class="jc-product-category-panel" data-widget="jc-product-cat-panel">
    <h3 class="jc-product-category-panel__title"><?php echo esc_html($panel_title); ?></h3>
    <ul class="jc-product-category-tree" data-widget="jc-product-category-menu">
        <?php echo $menu_items; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </ul>
</section>
<?php
    }

    /**
     * 输出编辑器预览模板。
     *
     * @return void
     */
    protected function content_template(): void
    {
        ?>
<section class="jc-product-category-panel" data-widget="jc-product-cat-panel">
    <h3 class="jc-product-category-panel__title">{{ settings.panel_title || '<?php echo esc_js(__('Product Categories', 'jelly-catalog')); ?>' }}</h3>
    <ul class="jc-product-category-tree" data-widget="jc-product-category-menu">
        <li class="jc-product-category-node current-cat-ancestor is-expanded">
            <div class="jc-product-category-node__row">
                <a class="jc-product-category-node__link" href="#" onclick="return false;">
                    <span class="jc-product-category-node__label"><?php echo esc_html__('Product Categories', 'jelly-catalog'); ?></span>
                </a>
                <button class="jc-product-category-node__toggle" type="button" aria-expanded="true"
                    aria-label="<?php echo esc_attr__('Toggle subcategories', 'jelly-catalog'); ?>">
                    <i class="ri-arrow-down-s-line" aria-hidden="true"></i>
                </button>
            </div>
            <ul class="jc-product-category-children">
                <li class="jc-product-category-node current-cat">
                    <div class="jc-product-category-node__row">
                        <a class="jc-product-category-node__link" href="#" onclick="return false;">
                            <span class="jc-product-category-node__label"><?php echo esc_html__('Industrial Parts', 'jelly-catalog'); ?></span>
                        </a>
                    </div>
                </li>
                <li class="jc-product-category-node">
                    <div class="jc-product-category-node__row">
                        <a class="jc-product-category-node__link" href="#" onclick="return false;">
                            <span class="jc-product-category-node__label"><?php echo esc_html__('Accessories', 'jelly-catalog'); ?></span>
                        </a>
                    </div>
                </li>
            </ul>
        </li>
    </ul>
</section>
<?php
    }

    /**
     * 返回当前产品上下文对应的分类。
     *
     * @return \WP_Term|null
     */
    private function get_current_product_term()
    {
        $queried_object = get_queried_object();

        if ($queried_object instanceof \WP_Term && $queried_object->taxonomy === 'product_cat') {
            return $queried_object;
        }

        if (!is_singular('product')) {
            return null;
        }

        $post_id = get_queried_object_id();

        if (!$post_id) {
            return null;
        }

        $terms = get_the_terms($post_id, 'product_cat');

        if (empty($terms) || is_wp_error($terms)) {
            return null;
        }

        $current_term = null;
        $current_depth = -1;

        foreach ($terms as $term) {
            $depth = count(get_ancestors($term->term_id, 'product_cat', 'taxonomy'));

            if (
                null === $current_term
                || $depth > $current_depth
                || ($depth === $current_depth && strcmp($term->name, $current_term->name) < 0)
            ) {
                $current_term = $term;
                $current_depth = $depth;
            }
        }

        return $current_term;
    }
}

/**
 * 产品分类面板 Walker。
 */
class Product_Cat_Menu_Walker extends \Walker_Category
{
    /**
     * 当前分类 ID。
     *
     * @var int
     */
    protected $current_term_id = 0;

    /**
     * 当前父级分类 ID。
     *
     * @var int
     */
    protected $current_parent_id = 0;

    /**
     * 当前分类祖先 ID 列表。
     *
     * @var int[]
     */
    protected $ancestor_ids = [];

    /**
     * 初始化 Walker。
     *
     * @param int   $current_term_id 当前分类 ID。
     * @param int   $current_parent_id 当前父级分类 ID。
     * @param int[] $ancestor_ids 当前分类祖先 ID 列表。
     */
    public function __construct($current_term_id = 0, $current_parent_id = 0, $ancestor_ids = [])
    {
        $this->current_term_id = (int) $current_term_id;
        $this->current_parent_id = (int) $current_parent_id;
        $this->ancestor_ids = array_map('intval', (array) $ancestor_ids);
    }

    /**
     * 输出子级分类列表起始标签。
     *
     * @param string $output 输出字符串。
     * @param int    $depth 当前深度。
     * @param array  $args Walker 参数。
     * @return void
     */
    public function start_lvl(&$output, $depth = 0, $args = [])
    {
        $output .= "\n<ul class=\"jc-product-category-children\">\n";
    }

    /**
     * 输出分类项起始标签。
     *
     * @param string   $output 输出字符串。
     * @param \WP_Term $data_object 分类对象。
     * @param int      $depth 当前深度。
     * @param array    $args Walker 参数。
     * @param int      $current_object_id 当前对象 ID。
     * @return void
     */
    public function start_el(&$output, $data_object, $depth = 0, $args = [], $current_object_id = 0)
    {
        $category = $data_object;

        if (!$category instanceof \WP_Term) {
            return;
        }

        $name = apply_filters('list_cats', $category->name, $category);

        if ('' === $name) {
            return;
        }

        $term_link = get_term_link($category);

        if (is_wp_error($term_link)) {
            return;
        }

        $term_id = (int) $category->term_id;
        $classes = ['jc-product-category-node'];

        if (!empty($args['has_children'])) {
            $classes[] = 'jc-product-category-node--has-children';
        }

        if ($term_id === $this->current_term_id) {
            $classes[] = 'current-cat';
        }

        if ($term_id === $this->current_parent_id) {
            $classes[] = 'current-cat-parent';
        }

        if (in_array($term_id, $this->ancestor_ids, true)) {
            $classes[] = 'current-cat-ancestor';
        }

        $attributes = [
            'href' => $term_link,
            'class' => 'jc-product-category-node__link',
        ];

        if ($term_id === $this->current_term_id) {
            $attributes['aria-current'] = 'page';
        }

        $output .= "\t<li";

        if (!empty($classes)) {
            $output .= ' class="' . esc_attr(implode(' ', $classes)) . '"';
        }

        $output .= '>';
        $output .= '<div class="jc-product-category-node__row">';
        $output .= '<a' . $this->build_attributes($attributes) . '>';
        $output .= '<span class="jc-product-category-node__label">' . esc_html($name) . '</span>';
        $output .= '</a>';

        if (!empty($args['has_children'])) {
            $output .= '<button class="jc-product-category-node__toggle" type="button" aria-expanded="' . ($term_id === $this->current_term_id || $term_id === $this->current_parent_id || in_array($term_id, $this->ancestor_ids, true) ? 'true' : 'false') . '" aria-label="' . esc_attr__('Toggle subcategories', 'jelly-catalog') . '"><i class="ri-arrow-down-s-line" aria-hidden="true"></i></button>';
        }

        $output .= '</div>';
    }

    /**
     * 拼接 HTML 属性字符串。
     *
     * @param array<string, scalar|bool> $attributes 属性集合。
     * @return string
     */
    protected function build_attributes(array $attributes)
    {
        $html = '';

        foreach ($attributes as $attr => $value) {
            if (!is_scalar($value) || '' === (string) $value || false === $value) {
                continue;
            }

            $html .= sprintf(
                ' %s="%s"',
                esc_attr($attr),
                'href' === $attr ? esc_url((string) $value) : esc_attr((string) $value)
            );
        }

        return $html;
    }
}
