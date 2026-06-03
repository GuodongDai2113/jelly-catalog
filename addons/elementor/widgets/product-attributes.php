<?php

/**
 * addons\elementor\widgets\product-attributes.php
 *
 * @see: https://jellydai.com
 * @author: Jelly Dai <daiguo1003@gmail.com>
 * @created : 2026.01.19 19:23
 */

namespace Jelly_Catalog\Addons\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
} // 绂佹鐩存帴璁块棶

/**
 * 产品属性组件。
 */
class Product_Attributes extends Widget_Base
{
    /**
     * 获取组件唯一名称。
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'jc-product-attributes';
    }

    /**
     * 获取组件标题。
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Product Attributes', 'jelly-catalog');
    }

    /**
     * 获取组件图标。
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-editor-list-ul jelly-engine-icon';
    }

    /**
     * 获取组件分类。
     *
     * @return array
     */
    public function get_categories(): array
    {
        return ['jc-elements-single'];
    }

    /**
     * 获取组件关键词。
     *
     * @return array
     */
    public function get_keywords(): array
    {
        return ['product', 'attributes', 'specs', 'properties'];
    }

    /**
     * 返回产品属性组件依赖的样式句柄。
     *
     * @return array
     */
    public function get_style_depends(): array
    {
        return ['jelly-catalog-product-attributes'];
    }

    /**
     * 注册组件控制项。
     *
     * @return void
     */
    protected function register_controls(): void
    {
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
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'selected_icon',
            [
                'label' => esc_html__('Choose Icon', 'jelly-catalog'),
                'type' => Controls_Manager::ICONS,
                'fa4compatibility' => 'icon',
                'default' => [],
                'condition' => [
                    'enable_icon' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

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
                    '{{WRAPPER}} .jc-product-attributes' => 'gap: {{SIZE}}{{UNIT}};',
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
                    '{{WRAPPER}} .jc-product-attributes-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'item_background',
            [
                'label' => esc_html__('Item Background', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .jc-product-attributes-item' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'item_border',
                'selector' => '{{WRAPPER}} .jc-product-attributes-item',
            ]
        );

        $this->add_responsive_control(
            'item_border_radius',
            [
                'label' => esc_html__('Item Border Radius', 'jelly-catalog'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'rem', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .jc-product-attributes-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'font_size',
            [
                'label' => esc_html__('Font Size', 'jelly-catalog'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'em', 'rem'],
                'default' => [
                    'size' => 16,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .jc-product-attributes-item' => 'font-size: {{SIZE}}{{UNIT}};',
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
                    '{{WRAPPER}} .jc-product-attributes' => 'text-align: {{VALUE}};',
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
                'selector' => '{{WRAPPER}} .jc-product-attributes__name',
            ]
        );

        $this->add_control(
            'name_color',
            [
                'label' => esc_html__('Name Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .jc-product-attributes__name' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'value_typography',
                'label' => esc_html__('Value Typography', 'jelly-catalog'),
                'selector' => '{{WRAPPER}} .jc-product-attributes__value',
            ]
        );

        $this->add_control(
            'value_color',
            [
                'label' => esc_html__('Value Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .jc-product-attributes__value' => 'color: {{VALUE}};',
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
                'selectors' => [
                    '{{WRAPPER}} .jc-product-attributes__icon' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .jc-product-attributes__icon svg' => 'fill: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'icon_size',
            [
                'label' => esc_html__('Size', 'jelly-catalog'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'default' => [
                    'size' => 10,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}}' => '--jc-product-attributes-icon-size: {{SIZE}}{{UNIT}};',
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
                    '{{WRAPPER}} .jc-product-attributes__icon' => 'margin-right: {{SIZE}}{{UNIT}};',
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
    protected function render(): void
    {
        $settings = $this->get_settings_for_display();
        $attributes = get_post_meta(get_the_ID(), '_product_attributes', true);

        if (empty($attributes) || !is_array($attributes)) {
            return;
        }

        $max_items = absint($settings['max_items'] ?? 0);

        if ($max_items > 0 && count($attributes) > $max_items) {
            $attributes = array_slice($attributes, 0, $max_items, true);
        }

        $columns = max(1, min(4, absint($settings['columns'] ?? 1)));
        ?>
<ul class="jc-product-attributes jc-product-attributes--cols-<?php echo esc_attr($columns); ?>">
    <?php foreach ($attributes as $attribute): ?>
    <?php if (isset($attribute['name'], $attribute['value'])): ?>
    <li class="jc-product-attributes-item">
        <?php $this->render_attribute_icon($settings); ?>
        <span class="jc-product-attributes__name"><?php echo esc_html($attribute['name']); ?>:</span>
        <span class="jc-product-attributes__value"><strong><?php echo esc_html($attribute['value']); ?></strong></span>
    </li>
    <?php endif; ?>
    <?php endforeach; ?>
</ul>
<?php
    }

    /**
     * 输出编辑器预览模板。
     *
     * @return void
     */
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
var iconHTML = elementor.helpers.renderIcon(view, settings.selected_icon, { 'aria-hidden': true }, 'i', 'object');

if (maxItems > 0) {
    attributes = attributes.slice(0, maxItems);
}
#>
<ul class="jc-product-attributes jc-product-attributes--cols-{{ columns }}">
    <# _.each(attributes, function(item) { #>
    <li class="jc-product-attributes-item">
        <# if ('yes' === settings.enable_icon) { #>
        <span class="jc-product-attributes__icon" aria-hidden="true">
            <# if (iconHTML && iconHTML.value) { #>
            {{{ iconHTML.value }}}
            <# } else { #>
            <i class="ri-circle-fill ri"></i>
            <# } #>
        </span>
        <# } #>
        <span class="jc-product-attributes__name">{{ item.name }}:</span>
        <span class="jc-product-attributes__value"><strong>{{ item.value }}</strong></span>
    </li>
    <# }); #>
</ul>
<?php
    }

    /**
     * 输出属性项图标。
     *
     * @param array $settings 组件设置。
     * @return void
     */
    private function render_attribute_icon(array $settings): void
    {
        if (($settings['enable_icon'] ?? 'yes') !== 'yes') {
            return;
        }

        echo '<span class="jc-product-attributes__icon" aria-hidden="true">';

        if (!empty($settings['selected_icon']['value'])) {
            Icons_Manager::render_icon($settings['selected_icon'], ['aria-hidden' => 'true']);
        } else {
            echo '<i class="ri-circle-fill ri"></i>';
        }

        echo '</span>';
    }
}
