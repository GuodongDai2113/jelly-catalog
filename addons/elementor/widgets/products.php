<?php

/**
 * addons\elementor\widgets\products.php
 *
 * @see: https://jellydai.com
 * @author: Jelly Dai <daiguo1003@gmail.com>
 * @created : 2026.06.03 00:00
 */

namespace Jelly_Catalog\Addons\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 产品列表组件。
 */
class Products extends Widget_Base
{
    /**
     * 获取组件唯一名称。
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'jc-products';
    }

    /**
     * 获取组件标题。
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Products', 'jelly-catalog');
    }

    /**
     * 获取组件图标。
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-products jelly-engine-icon';
    }

    /**
     * 获取组件分类。
     *
     * @return array
     */
    public function get_categories(): array
    {
        return ['jc-elements-archive', 'jc-elements-single'];
    }

    /**
     * 获取组件关键词。
     *
     * @return array
     */
    public function get_keywords(): array
    {
        return ['products', 'product', 'grid', 'archive', 'pagination'];
    }

    /**
     * 返回组件依赖的样式句柄。
     *
     * @return array
     */
    public function get_style_depends(): array
    {
        return [
            'jelly-catalog-product-loop',
            'jelly-catalog-products',
        ];
    }

    /**
     * 注册组件控件。
     *
     * @return void
     */
    protected function register_controls(): void
    {
        $this->start_controls_section(
            'section_layout',
            [
                'label' => esc_html__('Layout', 'jelly-catalog'),
            ]
        );

        $this->add_control(
            'columns',
            [
                'label' => esc_html__('Columns', 'jelly-catalog'),
                'type' => Controls_Manager::SELECT,
                'default' => '4',
                'options' => [
                    '2' => esc_html__('2 Columns', 'jelly-catalog'),
                    '3' => esc_html__('3 Columns', 'jelly-catalog'),
                    '4' => esc_html__('4 Columns', 'jelly-catalog'),
                    '5' => esc_html__('5 Columns', 'jelly-catalog'),
                    '6' => esc_html__('6 Columns', 'jelly-catalog'),
                ],
            ]
        );

        $this->add_control(
            'rows',
            [
                'label' => esc_html__('Rows', 'jelly-catalog'),
                'type' => Controls_Manager::NUMBER,
                'default' => 2,
                'min' => 1,
                'max' => 12,
                'step' => 1,
            ]
        );

        $this->add_control(
            'max_products',
            [
                'label' => esc_html__('Max Products', 'jelly-catalog'),
                'type' => Controls_Manager::NUMBER,
                'default' => 0,
                'min' => 0,
                'step' => 1,
                'description' => esc_html__('Set to 0 to show all matching products.', 'jelly-catalog'),
            ]
        );

        $this->add_control(
            'enable_pagination',
            [
                'label' => esc_html__('Enable Pagination', 'jelly-catalog'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'jelly-catalog'),
                'label_off' => esc_html__('No', 'jelly-catalog'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_responsive_control(
            'grid_gap',
            [
                'label' => esc_html__('Grid Gap', 'jelly-catalog'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 80,
                    ],
                    'rem' => [
                        'min' => 0,
                        'max' => 6,
                        'step' => 0.125,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .jc-products__grid' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'pagination_align',
            [
                'label' => esc_html__('Pagination Align', 'jelly-catalog'),
                'type' => Controls_Manager::CHOOSE,
                'default' => 'flex-start',
                'options' => [
                    'flex-start' => [
                        'title' => esc_html__('Left', 'jelly-catalog'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'jelly-catalog'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'flex-end' => [
                        'title' => esc_html__('Right', 'jelly-catalog'),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'condition' => [
                    'enable_pagination' => 'yes',
                ],
                'selectors' => [
                    '{{WRAPPER}} .jc-products__pagination .page-numbers' => 'justify-content: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_query',
            [
                'label' => esc_html__('Query', 'jelly-catalog'),
            ]
        );

        $this->add_control(
            'query_source',
            [
                'label' => esc_html__('Source', 'jelly-catalog'),
                'type' => Controls_Manager::SELECT,
                'default' => 'latest',
                'options' => [
                    'latest' => esc_html__('Latest Products', 'jelly-catalog'),
                    'current_term' => esc_html__('Current Category/Tag', 'jelly-catalog'),
                    'categories' => esc_html__('Selected Categories', 'jelly-catalog'),
                    'tags' => esc_html__('Selected Tags', 'jelly-catalog'),
                    'manual' => esc_html__('Manual Selection', 'jelly-catalog'),
                ],
            ]
        );

        $this->add_control(
            'category_ids',
            [
                'label' => esc_html__('Categories', 'jelly-catalog'),
                'type' => Controls_Manager::SELECT2,
                'multiple' => true,
                'label_block' => true,
                'options' => $this->get_product_term_options('product_cat'),
                'condition' => [
                    'query_source' => 'categories',
                ],
            ]
        );

        $this->add_control(
            'include_children',
            [
                'label' => esc_html__('Include Child Categories', 'jelly-catalog'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'jelly-catalog'),
                'label_off' => esc_html__('No', 'jelly-catalog'),
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'query_source' => 'categories',
                ],
            ]
        );

        $this->add_control(
            'tag_ids',
            [
                'label' => esc_html__('Tags', 'jelly-catalog'),
                'type' => Controls_Manager::SELECT2,
                'multiple' => true,
                'label_block' => true,
                'options' => $this->get_product_term_options('product_tag'),
                'condition' => [
                    'query_source' => 'tags',
                ],
            ]
        );

        $this->add_control(
            'manual_product_ids',
            [
                'label' => esc_html__('Product IDs', 'jelly-catalog'),
                'type' => Controls_Manager::TEXTAREA,
                'rows' => 3,
                'description' => esc_html__('Enter product IDs separated by commas.', 'jelly-catalog'),
                'condition' => [
                    'query_source' => 'manual',
                ],
            ]
        );

        $this->add_control(
            'orderby',
            [
                'label' => esc_html__('Order By', 'jelly-catalog'),
                'type' => Controls_Manager::SELECT,
                'default' => 'date',
                'options' => [
                    'date' => esc_html__('Date', 'jelly-catalog'),
                    'title' => esc_html__('Title', 'jelly-catalog'),
                    'modified' => esc_html__('Modified', 'jelly-catalog'),
                    'menu_order' => esc_html__('Menu Order', 'jelly-catalog'),
                    'rand' => esc_html__('Random', 'jelly-catalog'),
                ],
                'condition' => [
                    'query_source!' => 'manual',
                ],
            ]
        );

        $this->add_control(
            'order',
            [
                'label' => esc_html__('Order', 'jelly-catalog'),
                'type' => Controls_Manager::SELECT,
                'default' => 'DESC',
                'options' => [
                    'DESC' => esc_html__('Descending', 'jelly-catalog'),
                    'ASC' => esc_html__('Ascending', 'jelly-catalog'),
                ],
                'condition' => [
                    'query_source!' => 'manual',
                ],
            ]
        );

        $this->add_control(
            'offset',
            [
                'label' => esc_html__('Offset', 'jelly-catalog'),
                'type' => Controls_Manager::NUMBER,
                'default' => 0,
                'min' => 0,
                'step' => 1,
                'description' => esc_html__('Skip the first N matching products.', 'jelly-catalog'),
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_content',
            [
                'label' => esc_html__('Content', 'jelly-catalog'),
            ]
        );

        $this->add_control(
            'show_image',
            [
                'label' => esc_html__('Show Image', 'jelly-catalog'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'jelly-catalog'),
                'label_off' => esc_html__('No', 'jelly-catalog'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'image_size',
            [
                'label' => esc_html__('Image Size', 'jelly-catalog'),
                'type' => Controls_Manager::SELECT,
                'default' => 'large',
                'options' => [
                    'thumbnail' => esc_html__('Thumbnail', 'jelly-catalog'),
                    'medium' => esc_html__('Medium', 'jelly-catalog'),
                    'large' => esc_html__('Large', 'jelly-catalog'),
                    'full' => esc_html__('Full', 'jelly-catalog'),
                ],
                'condition' => [
                    'show_image' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_title',
            [
                'label' => esc_html__('Show Title', 'jelly-catalog'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'jelly-catalog'),
                'label_off' => esc_html__('No', 'jelly-catalog'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'empty_message',
            [
                'label' => esc_html__('Empty Message', 'jelly-catalog'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('No products found.', 'jelly-catalog'),
            ]
        );

        $this->end_controls_section();
    }

    /**
     * 输出前台内容。
     *
     * @return void
     */
    protected function render(): void
    {
        $settings = $this->get_settings_for_display();
        $columns = $this->get_columns($settings);
        $rows = $this->get_rows($settings);
        $offset = $this->get_offset($settings);
        $max_products = $this->get_max_products($settings);
        $pagination_enabled = ($settings['enable_pagination'] ?? 'yes') === 'yes';
        $per_page = max(1, $columns * $rows);
        $current_page = $pagination_enabled ? $this->get_current_page() : 1;
        $page_key = $this->get_page_key();
        $query = new \WP_Query($this->build_query_args($settings, $per_page, $current_page));
        $total_products = max(0, (int) $query->found_posts - $offset);

        if ($max_products > 0) {
            $total_products = min($total_products, $max_products);
        }

        $total_pages = $pagination_enabled ? max(1, (int) ceil($total_products / $per_page)) : 1;

        if ($pagination_enabled && $current_page > $total_pages) {
            wp_reset_postdata();
            return;
        }

        $visible_products = $query->post_count;

        if ($max_products > 0) {
            $remaining_products = max(0, $max_products - (($current_page - 1) * $per_page));
            $visible_products = min($visible_products, $remaining_products);
        }

        if (!$query->have_posts() || $visible_products <= 0) {
            wp_reset_postdata();
            $this->render_empty_message($settings);
            return;
        }
        ?>
<div class="jc-products" data-widget="jc-products">
    <div class="jc-products__grid jc-products__grid--cols-<?php echo esc_attr($columns); ?>">
        <?php
        $rendered = 0;

        while ($query->have_posts()) {
            $query->the_post();

            if ($rendered >= $visible_products) {
                break;
            }

            $this->render_product_card(get_the_ID(), $settings);
            $rendered++;
        }
        ?>
    </div>

    <?php if ($pagination_enabled && $total_pages > 1): ?>
    <nav class="jc-products__pagination" aria-label="<?php echo esc_attr__('Products pagination', 'jelly-catalog'); ?>">
        <?php
        echo wp_kses_post(
            paginate_links(
                [
                    'base' => esc_url(add_query_arg($page_key, '%#%')),
                    'format' => '',
                    'current' => $current_page,
                    'total' => $total_pages,
                    'type' => 'list',
                    'prev_text' => esc_html__('Prev', 'jelly-catalog'),
                    'next_text' => esc_html__('Next', 'jelly-catalog'),
                ]
            )
        );
        ?>
    </nav>
    <?php endif; ?>
</div>
<?php

        wp_reset_postdata();
    }

    /**
     * 输出编辑器预览模板。
     *
     * @return void
     */
    protected function content_template(): void
    {
        $placeholder_url = function_exists('jc_placeholder_img_src') ? jc_placeholder_img_src() : '';
        ?>
<#
var columns = parseInt(settings.columns, 10) || 4;
var rows = parseInt(settings.rows, 10) || 2;
var totalCards = Math.max(1, Math.min(columns * rows, 8));
var showImage = 'yes' === settings.show_image;
var showTitle = 'yes' === settings.show_title;
#>
<div class="jc-products" data-widget="jc-products">
    <div class="jc-products__grid jc-products__grid--cols-{{ columns }}">
        <# for (var index = 0; index < totalCards; index++) { #>
        <article class="jc-product-loop" data-widget="jc-product-loop">
            <# if (showImage) { #>
            <div class="jc-product-loop__image-wrap">
                <a href="#" aria-disabled="true" onclick="return false;">
                    <img class="jc-product-loop__image" src="<?php echo esc_url($placeholder_url); ?>"
                        alt="<?php echo esc_attr__('Preview Product', 'jelly-catalog'); ?>" loading="lazy">
                </a>
            </div>
            <# } #>

            <# if (showTitle) { #>
            <div class="jc-product-loop__content">
                <a href="#" aria-disabled="true" onclick="return false;">
                    <h2 class="jc-product-loop__title"><?php echo esc_html__('Preview Product Title', 'jelly-catalog'); ?></h2>
                </a>
            </div>
            <# } #>
        </article>
        <# } #>
    </div>

    <# if ('yes' === settings.enable_pagination) { #>
    <nav class="jc-products__pagination" aria-label="<?php echo esc_attr__('Products pagination', 'jelly-catalog'); ?>">
        <ul class="page-numbers">
            <li><span class="prev page-numbers"><?php echo esc_html__('Prev', 'jelly-catalog'); ?></span></li>
            <li><span class="page-numbers current">1</span></li>
            <li><a class="page-numbers" href="#" onclick="return false;">2</a></li>
            <li><a class="next page-numbers" href="#" onclick="return false;"><?php echo esc_html__('Next', 'jelly-catalog'); ?></a></li>
        </ul>
    </nav>
    <# } #>
</div>
<?php
    }

    /**
     * 返回列数设置。
     *
     * @param array $settings 组件设置。
     * @return int
     */
    private function get_columns(array $settings): int
    {
        return max(2, min(6, absint($settings['columns'] ?? 4)));
    }

    /**
     * 返回行数设置。
     *
     * @param array $settings 组件设置。
     * @return int
     */
    private function get_rows(array $settings): int
    {
        return max(1, min(12, absint($settings['rows'] ?? 2)));
    }

    /**
     * 返回最大产品数设置。
     *
     * @param array $settings 组件设置。
     * @return int
     */
    private function get_max_products(array $settings): int
    {
        return max(0, absint($settings['max_products'] ?? 0));
    }

    /**
     * 返回偏移量设置。
     *
     * @param array $settings 组件设置。
     * @return int
     */
    private function get_offset(array $settings): int
    {
        return max(0, absint($settings['offset'] ?? 0));
    }

    /**
     * 返回当前分页参数名。
     *
     * @return string
     */
    private function get_page_key(): string
    {
        return 'jc_products_page_' . $this->get_id();
    }

    /**
     * 返回当前页码。
     *
     * @return int
     */
    private function get_current_page(): int
    {
        $page_key = $this->get_page_key();
        $page = isset($_GET[$page_key]) ? absint(wp_unslash($_GET[$page_key])) : 1;

        return max(1, $page);
    }

    /**
     * 返回产品分类或标签选项。
     *
     * @param string $taxonomy 分类法名称。
     * @return array
     */
    private function get_product_term_options(string $taxonomy): array
    {
        if (!taxonomy_exists($taxonomy)) {
            return [];
        }

        $terms = get_terms(
            [
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
                'orderby' => 'name',
                'order' => 'ASC',
            ]
        );

        if (empty($terms) || is_wp_error($terms)) {
            return [];
        }

        $options = [];

        foreach ($terms as $term) {
            $options[(string) $term->term_id] = $term->name;
        }

        return $options;
    }

    /**
     * 返回当前上下文中的产品分类或标签。
     *
     * @return \WP_Term|null
     */
    private function get_current_context_term(): ?\WP_Term
    {
        $queried_object = get_queried_object();

        if ($queried_object instanceof \WP_Term && in_array($queried_object->taxonomy, ['product_cat', 'product_tag'], true)) {
            return $queried_object;
        }

        if (!is_singular('product')) {
            return null;
        }

        $terms = get_the_terms(get_queried_object_id(), 'product_cat');

        if (empty($terms) || is_wp_error($terms)) {
            return null;
        }

        $current_term = null;
        $current_depth = -1;

        foreach ($terms as $term) {
            $depth = count(get_ancestors($term->term_id, 'product_cat', 'taxonomy'));

            if (null === $current_term || $depth > $current_depth) {
                $current_term = $term;
                $current_depth = $depth;
            }
        }

        return $current_term;
    }

    /**
     * 返回多选分类或标签 ID 列表。
     *
     * @param array  $settings 组件设置。
     * @param string $key 设置键名。
     * @return int[]
     */
    private function get_selected_ids(array $settings, string $key): array
    {
        return array_values(array_filter(array_map('absint', (array) ($settings[$key] ?? []))));
    }

    /**
     * 返回手动指定的产品 ID 列表。
     *
     * @param array $settings 组件设置。
     * @return int[]
     */
    private function get_manual_product_ids(array $settings): array
    {
        $raw_ids = isset($settings['manual_product_ids']) ? (string) $settings['manual_product_ids'] : '';

        return array_values(wp_parse_id_list($raw_ids));
    }

    /**
     * 构建产品查询参数。
     *
     * @param array $settings 组件设置。
     * @param int   $per_page 每页产品数。
     * @param int   $current_page 当前页码。
     * @return array
     */
    private function build_query_args(array $settings, int $per_page, int $current_page): array
    {
        $query_source = isset($settings['query_source']) ? (string) $settings['query_source'] : 'latest';
        $offset = $this->get_offset($settings);
        $query_args = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'ignore_sticky_posts' => true,
            'posts_per_page' => $per_page,
            'paged' => $current_page,
            'offset' => $offset + (($current_page - 1) * $per_page),
            'orderby' => $this->get_orderby($settings),
            'order' => 'ASC' === ($settings['order'] ?? 'DESC') ? 'ASC' : 'DESC',
        ];

        if ('manual' === $query_source) {
            $manual_ids = $this->get_manual_product_ids($settings);

            if (!empty($manual_ids)) {
                $query_args['post__in'] = $manual_ids;
                $query_args['orderby'] = 'post__in';
            } else {
                $query_args['post__in'] = [0];
            }
        } elseif ('current_term' === $query_source) {
            $current_term = $this->get_current_context_term();

            if ($current_term instanceof \WP_Term) {
                $query_args['tax_query'] = [
                    [
                        'taxonomy' => $current_term->taxonomy,
                        'field' => 'term_id',
                        'terms' => [(int) $current_term->term_id],
                    ],
                ];
            }
        } elseif ('categories' === $query_source) {
            $category_ids = $this->get_selected_ids($settings, 'category_ids');

            if (!empty($category_ids)) {
                $query_args['tax_query'] = [
                    [
                        'taxonomy' => 'product_cat',
                        'field' => 'term_id',
                        'terms' => $category_ids,
                        'include_children' => ($settings['include_children'] ?? 'yes') === 'yes',
                    ],
                ];
            } else {
                $query_args['post__in'] = [0];
            }
        } elseif ('tags' === $query_source) {
            $tag_ids = $this->get_selected_ids($settings, 'tag_ids');

            if (!empty($tag_ids)) {
                $query_args['tax_query'] = [
                    [
                        'taxonomy' => 'product_tag',
                        'field' => 'term_id',
                        'terms' => $tag_ids,
                    ],
                ];
            } else {
                $query_args['post__in'] = [0];
            }
        }

        return $query_args;
    }

    /**
     * 返回允许的排序字段。
     *
     * @param array $settings 组件设置。
     * @return string
     */
    private function get_orderby(array $settings): string
    {
        $orderby = isset($settings['orderby']) ? (string) $settings['orderby'] : 'date';

        if (!in_array($orderby, ['date', 'title', 'modified', 'menu_order', 'rand'], true)) {
            return 'date';
        }

        return $orderby;
    }

    /**
     * 返回图片尺寸设置。
     *
     * @param array $settings 组件设置。
     * @return string
     */
    private function get_image_size(array $settings): string
    {
        $image_size = isset($settings['image_size']) ? (string) $settings['image_size'] : 'large';

        if (!in_array($image_size, ['thumbnail', 'medium', 'large', 'full'], true)) {
            return 'large';
        }

        return $image_size;
    }

    /**
     * 输出无结果提示。
     *
     * @param array $settings 组件设置。
     * @return void
     */
    private function render_empty_message(array $settings): void
    {
        $message = isset($settings['empty_message']) && '' !== trim((string) $settings['empty_message'])
            ? (string) $settings['empty_message']
            : esc_html__('No products found.', 'jelly-catalog');
        ?>
<div class="jc-products jc-products--empty" data-widget="jc-products">
    <div class="jc-products__empty"><?php echo esc_html($message); ?></div>
</div>
<?php
    }

    /**
     * 输出产品卡片。
     *
     * @param int   $product_id 产品 ID。
     * @param array $settings 组件设置。
     * @return void
     */
    private function render_product_card(int $product_id, array $settings): void
    {
        $permalink = get_permalink($product_id);
        $title = get_the_title($product_id);
        $show_image = ($settings['show_image'] ?? 'yes') === 'yes';
        $show_title = ($settings['show_title'] ?? 'yes') === 'yes';
        $image_html = '';

        if ($show_image) {
            $image_html = has_post_thumbnail($product_id)
                ? get_the_post_thumbnail(
                    $product_id,
                    $this->get_image_size($settings),
                    [
                        'class' => 'jc-product-loop__image',
                        'loading' => 'lazy',
                        'alt' => esc_attr($title),
                    ]
                )
                : sprintf(
                    '<img class="jc-product-loop__image" src="%s" alt="%s" loading="lazy">',
                    esc_url(jc_placeholder_img_src()),
                    esc_attr($title)
                );
        }
        ?>
<article class="jc-product-loop" data-widget="jc-product-loop">
    <?php if ($show_image): ?>
    <div class="jc-product-loop__image-wrap">
        <a href="<?php echo esc_url($permalink); ?>" aria-label="<?php echo esc_attr($title); ?>">
            <?php echo $image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </a>
    </div>
    <?php endif; ?>

    <?php if ($show_title): ?>
    <div class="jc-product-loop__content">
        <a href="<?php echo esc_url($permalink); ?>">
            <h2 class="jc-product-loop__title"><?php echo esc_html($title); ?></h2>
        </a>
    </div>
    <?php endif; ?>
</article>
<?php
    }
}
