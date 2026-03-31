<?php

/**
 * addons\elementor\widgets\product-cat-list.php
 *
 * @see: https://jellydai.com
 * @author: Jelly Dai <daiguo1003@gmail.com>
 * @created : 2026.03.07 10:00
 */

namespace Jelly_Catalog\Addons\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) {
    exit;
} // 禁止直接访问

class Product_Cat_List extends Widget_Base
{
    public function get_name()
    {
        return 'product_cat_list';
    }

    public function get_title()
    {
        return __('Product Category List', 'jelly-catalog');
    }

    public function get_icon()
    {
        return 'eicon-bullet-list jelly-engine-icon';
    }

    public function get_categories()
    {
        return ['jc-elements-archive'];
    }

    public function get_keywords()
    {
        return ['product', 'category', 'list', 'sub-categories'];
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

        $this->add_control(
            'show_count',
            [
                'label' => __('Show product count', 'jelly-catalog'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'jelly-catalog'),
                'label_off' => __('No', 'jelly-catalog'),
                'return_value' => 'yes',
                'default' => 'no',
            ]
        );

        $this->end_controls_section();

        // --- Style Sections ---

        // 1. Item Style
        $this->start_controls_section(
            'section_item_style',
            [
                'label' => __('Item Style', 'jelly-catalog'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'item_typography',
                'selector' => '{{WRAPPER}} .jc-cat-list__link',
            ]
        );

        $this->start_controls_tabs('tabs_item_style');

        $this->start_controls_tab(
            'tab_item_normal',
            [
                'label' => __('Normal', 'jelly-catalog'),
            ]
        );

        $this->add_control(
            'item_color',
            [
                'label' => __('Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .jc-cat-list__link' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'item_bg_color',
            [
                'label' => __('Background Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .jc-cat-list__header' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_item_hover',
            [
                'label' => __('Hover', 'jelly-catalog'),
            ]
        );

        $this->add_control(
            'item_color_hover',
            [
                'label' => __('Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .jc-cat-list__header:hover .jc-cat-list__link' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'item_bg_color_hover',
            [
                'label' => __('Background Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .jc-cat-list__header:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();

        // 2. Active Item Style
        $this->start_controls_section(
            'section_active_style',
            [
                'label' => __('Active Item Style', 'jelly-catalog'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'active_color',
            [
                'label' => __('Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .jc-cat-list__item.is-active > .jc-cat-list__header .jc-cat-list__link' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'active_bg_color',
            [
                'label' => __('Background Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .jc-cat-list__item.is-active > .jc-cat-list__header' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // 3. Count Badge Style
        $this->start_controls_section(
            'section_count_style',
            [
                'label' => __('Count Badge', 'jelly-catalog'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_count' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'count_color',
            [
                'label' => __('Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .jc-cat-list__count' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'count_bg_color',
            [
                'label' => __('Background Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .jc-cat-list__count' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'count_active_color',
            [
                'label' => __('Active Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .jc-cat-list__item.is-active > .jc-cat-list__header .jc-cat-list__count' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'count_active_bg_color',
            [
                'label' => __('Active Background Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .jc-cat-list__item.is-active > .jc-cat-list__header .jc-cat-list__count' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // 4. Toggle Style
        $this->start_controls_section(
            'section_toggle_style',
            [
                'label' => __('Toggle Style', 'jelly-catalog'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'toggle_color',
            [
                'label' => __('Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .jc-cat-list__toggle' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .jc-cat-list__toggle::before' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .jc-cat-list__toggle::after' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'toggle_color_hover',
            [
                'label' => __('Hover Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .jc-cat-list__toggle:hover' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .jc-cat-list__toggle:hover::before' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .jc-cat-list__toggle:hover::after' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();

        if (!taxonomy_exists('product_cat')) {
            return;
        }

        $hide_empty = ($settings['hide_empty'] === 'yes');
        $show_count = ($settings['show_count'] === 'yes');

        $args = [
            'taxonomy' => 'product_cat',
            'hide_empty' => $hide_empty,
            'orderby' => 'menu_order',
            'order' => 'ASC',
        ];

        $terms = get_terms($args);

        if (empty($terms) || is_wp_error($terms)) {
            return;
        }

        // 整理成父子结构
        $terms_by_parent = [];
        foreach ($terms as $term) {
            $terms_by_parent[$term->parent][] = $term;
        }

        $current_term_id = 0;
        $ancestors = [];
        $queried_object = get_queried_object();

        // 1. 分类存档页逻辑
        if ($queried_object && isset($queried_object->taxonomy) && $queried_object->taxonomy === 'product_cat') {
            $current_term_id = $queried_object->term_id;
            $ancestors = get_ancestors($current_term_id, 'product_cat');
        }
        // 2. 产品详情页逻辑：取产品的主分类或第一个分类
        elseif (is_singular('product')) {
            $product_terms = get_the_terms(get_the_ID(), 'product_cat');
            if (!empty($product_terms) && !is_wp_error($product_terms)) {
                // 优先取第一个分类（如果有 Yoast 或其他插件设置的主分类逻辑，可以后续扩展）
                $primary_term = $product_terms[0];
                $current_term_id = $primary_term->term_id;
                $ancestors = get_ancestors($current_term_id, 'product_cat');
            }
        }

        echo '<div class="jc-cat-list">';
        $this->render_terms_list($terms_by_parent, 0, $current_term_id, $ancestors, $show_count);
        echo '</div>';
    }

    private function render_terms_list($terms_by_parent, $parent_id, $current_term_id, $ancestors, $show_count, $depth = 0)
    {
        if (!isset($terms_by_parent[$parent_id])) {
            return;
        }

        echo '<ul class="jc-cat-list__ul jc-cat-list__depth-' . esc_attr($depth) . '">';
        foreach ($terms_by_parent[$parent_id] as $term) {
            $link = get_term_link($term, 'product_cat');
            if (is_wp_error($link)) {
                continue;
            }

            $is_active = ($current_term_id && (int)$term->term_id === (int)$current_term_id);
            $is_ancestor = in_array($term->term_id, $ancestors);
            $has_children = isset($terms_by_parent[$term->term_id]);
            $is_expanded = ($is_active || $is_ancestor);

            $li_class = 'jc-cat-list__item';
            if ($is_active) {
                $li_class .= ' is-active';
            }
            if ($is_ancestor) {
                $li_class .= ' is-ancestor';
            }
            if ($has_children) {
                $li_class .= ' has-children';
            }
            if ($is_expanded) {
                $li_class .= ' is-expanded';
            }

            echo '<li class="' . esc_attr($li_class) . '">';
            echo '<div class="jc-cat-list__header">';
            echo '<a class="jc-cat-list__link" href="' . esc_url($link) . '">';
            echo esc_html($term->name);
            if ($show_count) {
                echo ' <span class="jc-cat-list__count">' . esc_html($term->count) . '</span>';
            }
            echo '</a>';

            if ($has_children) {
                echo '<span class="jc-cat-list__toggle" role="button" aria-expanded="' . ($is_expanded ? 'true' : 'false') . '" aria-label="' . esc_attr__('Toggle subcategories', 'jelly-catalog') . '"></span>';
            }
            echo '</div>';

            if ($has_children) {
                $this->render_terms_list($terms_by_parent, $term->term_id, $current_term_id, $ancestors, $show_count, $depth + 1);
            }
            echo '</li>';
        }
        echo '</ul>';
    }
}
