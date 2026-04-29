<?php

/**
 * addons\elementor\widgets\product-cat-nav.php
 *
 * @see: https://jellydai.com
 * @author: Jelly Dai <daiguo1003@gmail.com>
 * @created : 2026.01.19 21:58
 */

namespace Jelly_Catalog\Addons\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;

if (!defined('ABSPATH')) {
    exit;
} // 禁止直接访问

class Product_Cat_Nav extends Widget_Base
{
    public function get_name()
    {
        return 'product_cat_nav';
    }

    public function get_title()
    {
        return __('Product Category Nav', 'jelly-catalog');
    }

    public function get_icon()
    {
        return 'eicon-tabs jelly-engine-icon';
    }

    public function get_categories()
    {
        return ['jc-elements-archive'];
    }

    public function get_keywords()
    {
        return ['product', 'category', 'nav', 'tabs'];
    }

    protected function register_controls()
    {
        $this->start_controls_section(
            'section_content',
            [
                'label' => __('Content', 'jelly-catalog'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'hide_empty',
            [
                'label' => __('Hide empty categories', 'jelly-catalog'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'jelly-catalog'),
                'label_off' => __('No', 'jelly-catalog'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_nav_style',
            [
                'label' => __('Navigation', 'jelly-catalog'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'alignment',
            [
                'label' => __('Alignment', 'jelly-catalog'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'flex-start' => [
                        'title' => __('Left', 'jelly-catalog'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => __('Center', 'jelly-catalog'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'flex-end' => [
                        'title' => __('Right', 'jelly-catalog'),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .jc-cat-nav' => 'display: flex; justify-content: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'tab_gap',
            [
                'label' => __('Gap', 'jelly-catalog'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'rem', 'em'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 80,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .jc-cat-nav' => 'display: flex; flex-wrap: wrap; gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_tab_style',
            [
                'label' => __('Tabs', 'jelly-catalog'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'tab_typography',
                'selector' => '{{WRAPPER}} .jc-cat-nav__tab',
            ]
        );

        $this->add_responsive_control(
            'tab_padding',
            [
                'label' => __('Padding', 'jelly-catalog'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'rem', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .jc-cat-nav__tab' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->start_controls_tabs('tabs_tab_states');

        $this->start_controls_tab(
            'tab_normal',
            [
                'label' => __('Normal', 'jelly-catalog'),
            ]
        );

        $this->add_control(
            'tab_color',
            [
                'label' => __('Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .jc-cat-nav__tab' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'tab_background',
            [
                'label' => __('Background Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .jc-cat-nav__tab' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_hover',
            [
                'label' => __('Hover', 'jelly-catalog'),
            ]
        );

        $this->add_control(
            'tab_hover_color',
            [
                'label' => __('Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .jc-cat-nav__tab:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'tab_hover_background',
            [
                'label' => __('Background Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .jc-cat-nav__tab:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_active',
            [
                'label' => __('Active', 'jelly-catalog'),
            ]
        );

        $this->add_control(
            'tab_active_color',
            [
                'label' => __('Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .jc-cat-nav__tab.is-active' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'tab_active_background',
            [
                'label' => __('Background Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .jc-cat-nav__tab.is-active' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'tab_border',
                'separator' => 'before',
                'selector' => '{{WRAPPER}} .jc-cat-nav__tab',
            ]
        );

        $this->add_responsive_control(
            'tab_border_radius',
            [
                'label' => __('Border Radius', 'jelly-catalog'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'rem', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .jc-cat-nav__tab' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    private function get_current_top_term_id()
    {
        // 分类归档页：直接高亮当前顶级分类（如果是子分类，自动追溯到顶级）
        $qo = get_queried_object();
        if ($qo && isset($qo->taxonomy) && $qo->taxonomy === 'product_cat' && !empty($qo->term_id)) {
            $term = get_term((int)$qo->term_id, 'product_cat');
            if ($term && !is_wp_error($term)) {
                while (!empty($term->parent)) {
                    $term = get_term((int)$term->parent, 'product_cat');
                    if (!$term || is_wp_error($term)) {
                        break;
                    }
                }
                if ($term && !is_wp_error($term)) {
                    return (int)$term->term_id;
                }
            }
            return (int)$qo->term_id;
        }

        // 单产品页：取产品第一个分类的顶级父类高亮
        if (is_singular('product')) {
            $terms = get_the_terms(get_the_ID(), 'product_cat');
            if (!empty($terms) && !is_wp_error($terms)) {
                $term = $terms[0];
                $top = $term;
                while (!empty($top->parent)) {
                    $top = get_term((int)$top->parent, 'product_cat');
                    if (!$top || is_wp_error($top)) {
                        break;
                    }
                }
                if ($top && !is_wp_error($top)) {
                    return (int)$top->term_id;
                }
                return (int)$term->term_id;
            }
        }

        return 0;
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();

        if (!taxonomy_exists('product_cat')) {
            return;
        }

        $hide_empty = ($settings['hide_empty'] === 'yes');

        $terms = get_terms([
            'taxonomy' => 'product_cat',
            'parent' => 0,
            'hide_empty' => $hide_empty,
            'orderby' => 'menu_order',
            'order' => 'ASC',
        ]);

        if (empty($terms) || is_wp_error($terms)) {
            return;
        }

        $current_top_id = $this->get_current_top_term_id();
        ?>
<div class="jc-cat-nav">
    <?php foreach ($terms as $term): ?>
    <?php
        $term_id = absint($term->term_id ?? 0);
        $term_name = $term->name ?? '';
        $link = !empty($term->url) ? $term->url : get_term_link($term, 'product_cat');
        if (is_wp_error($link)) {
            continue;
        }

        $is_active = ($current_top_id && $term_id === (int)$current_top_id);
        ?>
    <a class="jc-cat-nav__tab <?php echo $is_active ? 'is-active' : ''; ?>" href="<?php echo esc_url($link); ?>"
        role="tab" aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>">
        <?php echo esc_html($term_name); ?>
    </a>
    <?php endforeach; ?>
</div>
<?php
    }

    protected function content_template(): void
    {
        $preview_terms = [
            [
                'name' => esc_html__('All Products', 'jelly-catalog'),
            ],
            [
                'name' => esc_html__('Industrial Parts', 'jelly-catalog'),
            ],
            [
                'name' => esc_html__('Accessories', 'jelly-catalog'),
            ],
        ];
        ?>
<# var terms = <?php echo wp_json_encode($preview_terms); ?>; #>
<div class="jc-cat-nav">
    <# _.each(terms, function(term, index) { #>
    <a class="jc-cat-nav__tab <# if (index === 0) { #>is-active<# } #>" href="#"
        role="tab" aria-selected="<# if (index === 0) { #>true<# } else { #>false<# } #>" onclick="return false;">
        {{ term.name }}
    </a>
    <# }); #>
</div>
<?php
    }
}
