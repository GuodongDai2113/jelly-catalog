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
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;

if (! defined('ABSPATH')) exit; // 禁止直接访问

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
        return 'eicon-editor-list-ul';
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
                'label' => esc_html__('Style Settings', 'jelly-catalog'),
                'tab' => Controls_Manager::TAB_STYLE,
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

        $this->add_responsive_control(
            'alignment',
            [
                'label' => esc_html__('Alignment', 'jelly-catalog'),
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

        $this->add_control(
            'icon_color',
            [
                'label' => esc_html__('Icon Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'separator' => 'before',
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
        $max_items = $settings['max_items'];
        if ($max_items > 0 && count($attributes) > $max_items) {
            $attributes = array_slice($attributes, 0, $max_items, true);
        }

        $grid_columns =  'cols-' . $settings['columns'];
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
                        <span class="jc-attribute-name"><?php echo esc_html($item['name']); ?></span>:&nbsp;
                        <span class="jc-attribute-value"><?php echo esc_html($item['value']); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
<?php
    }
}
