<?php

/**
 * addons\elementor\widgets\product-attributes.php
 *
 * @see: https://jellydai.com
 * @author: Jelly Dai <daiguo1003@gmail.com>
 * @created : 2026.01.19 19:23
 */

namespace Jelly_Catalog\Addons\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Icons_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;

if (!defined('ABSPATH')) {
    exit;
} // 禁止直接访问

class Product_Attributes extends Widget_Base
{
    public function get_name(): string
    {
        return 'jc-product-attributes';
    }

    public function get_title(): string
    {
        return esc_html__('Product Attributes', 'jelly-catalog');
    }

    public function get_icon(): string
    {
        return 'eicon-editor-list-ul jelly-engine-icon';
    }

    public function get_categories(): array
    {
        return ['jc-elements-single'];
    }

    public function get_keywords(): array
    {
        return ['product', 'attributes', 'specs', 'properties'];
    }

    /**
     * Get style dependencies.
     *
     * Retrieve the list of style dependencies the widget requires.
     *
     * @since 3.24.0
     * @access public
     *
     * @return array Widget style dependencies.
     */
    public function get_style_depends(): array
    {
        return ['widget-icon-list'];
    }

    protected function register_controls(): void
    {
        // 内容控制面板
        $this->start_controls_section(
            'section_content',
            [
                'label' => esc_html__('Attributes Settings', 'jelly-catalog'),
            ]
        );

        $this->add_control(
            'columns',
            [
                'label' => esc_html__('Columns', 'jelly-catalog'),
                'type' => Controls_Manager::SELECT,
                'default' => '1',
                'options' => [
                    '1' => esc_html__('1 Column', 'jelly-catalog'),
                    '2' => esc_html__('2 Columns', 'jelly-catalog'),
                    '3' => esc_html__('3 Columns', 'jelly-catalog'),
                    '4' => esc_html__('4 Columns', 'jelly-catalog'),
                ],
            ]
        );

        $this->add_control(
            'max_items',
            [
                'label' => esc_html__('Max Items to Show', 'jelly-catalog'),
                'type' => Controls_Manager::NUMBER,
                'description' => esc_html__('Maximum number of attributes to display', 'jelly-catalog'),
                'default' => 4,
                'min' => 1,
                'step' => 1,
            ]
        );

        $this->end_controls_section();

        // 图标设置
        $this->start_controls_section(
            'section_icon',
            [
                'label' => esc_html__('Icon Settings', 'jelly-catalog'),
            ]
        );

        $this->add_control(
            'enable_icon',
            [
                'label' => esc_html__('Enable Icon', 'jelly-catalog'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'jelly-catalog'),
                'label_off' => esc_html__('No', 'jelly-catalog'),
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        $this->add_control(
            'selected_icon',
            [
                'label' => esc_html__('Choose Icon', 'jelly-catalog'),
                'type' => Controls_Manager::ICONS,
                'fa4compatibility' => 'icon',
                'default' => [
                    'value' => 'fas fa-check',
                    'library' => 'fa-solid',
                ],
                'condition' => [
                    'enable_icon' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // 样式设置
        $this->start_controls_section(
            'section_style',
            [
                'label' => esc_html__('Layout', 'jelly-catalog'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'items_gap',
            [
                'label' => esc_html__('Items Gap', 'jelly-catalog'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'rem', 'em'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 80,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .jc-attributes-wrapper' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'item_padding',
            [
                'label' => esc_html__('Item Padding', 'jelly-catalog'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'rem', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .jc-attribute' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'item_background',
            [
                'label' => esc_html__('Item Background', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .jc-attribute' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'item_border',
                'selector' => '{{WRAPPER}} .jc-attribute',
            ]
        );

        $this->add_responsive_control(
            'item_border_radius',
            [
                'label' => esc_html__('Item Border Radius', 'jelly-catalog'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'rem', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .jc-attribute' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'font_size',
            [
                'label' => esc_html__('Font Size', 'jelly-catalog'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'em', 'rem'],
                'default' => [
                    'size' => 14,
                    'unit' => 'px',
                ],
                'range' => [
                    'px' => [
                        'min' => 10,
                        'max' => 100,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .jc-attribute' => 'font-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'item_alignment',
            [
                'label' => esc_html__('Item Alignment', 'jelly-catalog'),
                'type' => Controls_Manager::CHOOSE,
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
                'selectors' => [
                    '{{WRAPPER}} .jc-attribute' => 'justify-content: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'alignment',
            [
                'label' => esc_html__('Text Alignment', 'jelly-catalog'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => esc_html__('Left', 'jelly-catalog'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'jelly-catalog'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => esc_html__('Right', 'jelly-catalog'),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .jc-attributes-wrapper' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_text_style',
            [
                'label' => esc_html__('Text', 'jelly-catalog'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'name_typography',
                'label' => esc_html__('Name Typography', 'jelly-catalog'),
                'selector' => '{{WRAPPER}} .jc-attribute-name',
            ]
        );

        $this->add_control(
            'name_color',
            [
                'label' => esc_html__('Name Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .jc-attribute-name' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'value_typography',
                'label' => esc_html__('Value Typography', 'jelly-catalog'),
                'selector' => '{{WRAPPER}} .jc-attribute-value',
            ]
        );

        $this->add_control(
            'value_color',
            [
                'label' => esc_html__('Value Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .jc-attribute-value' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_icon_style',
            [
                'label' => esc_html__('Icon', 'jelly-catalog'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'enable_icon' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'icon_color',
            [
                'label' => esc_html__('Icon Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .jc-attribute-icon' => 'fill: {{VALUE}}; color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'icon_size',
            [
                'label' => esc_html__('Size', 'jelly-catalog'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'em', 'rem', 'vw', 'custom'],
                'default' => [
                    'size' => 14,
                ],
                'range' => [
                    'px' => [
                        'min' => 6,
                    ],
                    '%' => [
                        'min' => 6,
                    ],
                    'vw' => [
                        'min' => 6,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}}' => '--jc-attribute-icon-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'icon_spacing',
            [
                'label' => esc_html__('Spacing', 'jelly-catalog'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'rem', 'em'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 80,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .jc-attribute-icon' => 'margin-right: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render(): void
    {
        $settings = $this->get_settings_for_display();

        $attributes = get_post_meta(get_the_ID(), '_product_attributes', true);

        if (empty($attributes) || !is_array($attributes)) {
            return;
        }

        // 限制显示的属性数量
        $max_items = absint($settings['max_items'] ?? 0);
        if ($max_items > 0 && count($attributes) > $max_items) {
            $attributes = array_slice($attributes, 0, $max_items, true);
        }

        $grid_columns = 'cols-' . absint($settings['columns'] ?? 1);
        ?>
<div class="jc-attributes-wrapper <?php echo esc_attr($grid_columns); ?>">
    <?php foreach ($attributes as $item): ?>
    <div class="jc-attribute">
        <?php if ($settings['enable_icon'] === 'yes'): ?>
        <span class="jc-attribute-icon">
            <?php Icons_Manager::render_icon($settings['selected_icon'], ['aria-hidden' => 'true']); ?>
        </span>
        <?php endif; ?>
        <div class="jc-attribute-item">
            <span class="jc-attribute-name"><?php echo esc_html($item['name'] ?? ''); ?></span>:&nbsp;
            <span class="jc-attribute-value"><?php echo esc_html($item['value'] ?? ''); ?></span>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php
    }

    protected function content_template(): void
    {
        $preview_attributes = [
            [
                'name' => esc_html__('Material', 'jelly-catalog'),
                'value' => esc_html__('Stainless Steel', 'jelly-catalog'),
            ],
            [
                'name' => esc_html__('Size', 'jelly-catalog'),
                'value' => esc_html__('120 x 80 x 40 mm', 'jelly-catalog'),
            ],
            [
                'name' => esc_html__('Application', 'jelly-catalog'),
                'value' => esc_html__('Industrial Equipment', 'jelly-catalog'),
            ],
            [
                'name' => esc_html__('Warranty', 'jelly-catalog'),
                'value' => esc_html__('12 Months', 'jelly-catalog'),
            ],
        ];
        ?>
<#
var attributes = <?php echo wp_json_encode($preview_attributes); ?>;
var columns = settings.columns || '1';
var maxItems = parseInt(settings.max_items, 10);

if (maxItems > 0) {
    attributes = attributes.slice(0, maxItems);
}
#>
<div class="jc-attributes-wrapper cols-{{ columns }}">
    <# _.each(attributes, function(item) { #>
    <div class="jc-attribute">
        <# if ('yes' === settings.enable_icon) {
            var iconHTML = elementor.helpers.renderIcon(view, settings.selected_icon, { 'aria-hidden': true }, 'i', 'object');
        #>
        <span class="jc-attribute-icon">
            <# if (iconHTML && iconHTML.value) { #>
            {{{ iconHTML.value }}}
            <# } #>
        </span>
        <# } #>
        <div class="jc-attribute-item">
            <span class="jc-attribute-name">{{ item.name }}</span>:&nbsp;
            <span class="jc-attribute-value">{{ item.value }}</span>
        </div>
    </div>
    <# }); #>
</div>
<?php
    }
}
