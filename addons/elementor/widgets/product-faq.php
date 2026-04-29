<?php

/**
 * addons\elementor\widgets\product-faq.php
 *
 * @see: https://jellydai.com
 * @author: Jelly Dai <daiguo1003@gmail.com>
 * @created : 2025.11.10 13:32
 */

namespace Jelly_Catalog\Addons\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;

if (!defined('ABSPATH')) {
    exit;
} // 禁止直接访问

class Product_FAQ extends \Elementor\Widget_Base
{
    public function get_name(): string
    {
        return 'jc-product-faq';
    }

    public function get_title(): string
    {
        return esc_html__('Product FAQ', 'jelly-catalog');
    }

    public function get_icon(): string
    {
        return 'eicon-checkbox jelly-engine-icon';
    }

    public function get_categories(): array
    {
        return ['jc-elements-single'];
    }

    public function get_keywords(): array
    {
        return ['product', 'faq', 'question', 'answer'];
    }

    protected function register_controls(): void
    {
        $this->start_controls_section(
            'section_faq_style',
            [
                'label' => esc_html__('FAQ Items', 'jelly-catalog'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'item_spacing',
            [
                'label' => esc_html__('Item Spacing', 'jelly-catalog'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'rem', 'em'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 80,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .jc-faq' => 'display: flex; flex-direction: column; gap: {{SIZE}}{{UNIT}};',
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
                    '{{WRAPPER}} .jc-faq-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'item_background',
            [
                'label' => esc_html__('Background Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .jc-faq-item' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'item_border',
                'selector' => '{{WRAPPER}} .jc-faq-item',
            ]
        );

        $this->add_responsive_control(
            'item_border_radius',
            [
                'label' => esc_html__('Border Radius', 'jelly-catalog'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'rem', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .jc-faq-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_question_style',
            [
                'label' => esc_html__('Question', 'jelly-catalog'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'question_typography',
                'selector' => '{{WRAPPER}} .jc-faq-header h3',
            ]
        );

        $this->add_control(
            'question_color',
            [
                'label' => esc_html__('Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .jc-faq-header h3' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .jc-faq-header' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'question_spacing',
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
                    '{{WRAPPER}} .jc-faq-header' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_answer_style',
            [
                'label' => esc_html__('Answer', 'jelly-catalog'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'answer_typography',
                'selector' => '{{WRAPPER}} .jc-faq-content, {{WRAPPER}} .jc-faq-content p',
            ]
        );

        $this->add_control(
            'answer_color',
            [
                'label' => esc_html__('Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .jc-faq-content, {{WRAPPER}} .jc-faq-content p' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'answer_padding',
            [
                'label' => esc_html__('Padding', 'jelly-catalog'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'rem', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .jc-faq-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render(): void
    {
        $faqs = get_post_meta(get_the_ID(), '_product_faqs', true);

        if (empty($faqs) || !is_array($faqs)) {
            return;
        }

        echo "<div class='jc-faq'>";
        foreach ($faqs as $item) {
            $name = esc_html($item['name'] ?? '');
            $value = wp_kses_post($item['value'] ?? '');
            echo "<details class='jc-faq-item'>";
            echo "<summary class='jc-faq-header'><h3>{$name}</h3></summary>";
            echo "<div class='jc-faq-content'><p>{$value}</p></div>";
            echo '</details>';
        }
        echo '</div>';
    }

    protected function content_template(): void
    {
        $preview_faqs = [
            [
                'name' => esc_html__('What product information appears here?', 'jelly-catalog'),
                'value' => esc_html__('This preview shows the FAQ layout when the current product has no FAQ data.', 'jelly-catalog'),
            ],
            [
                'name' => esc_html__('Can these styles be edited?', 'jelly-catalog'),
                'value' => esc_html__('Use the style controls to adjust item spacing, borders, question text, and answer text.', 'jelly-catalog'),
            ],
        ];
        ?>
<# var faqs = <?php echo wp_json_encode($preview_faqs); ?>; #>
<div class="jc-faq">
    <# _.each(faqs, function(item) { #>
    <details class="jc-faq-item" open>
        <summary class="jc-faq-header"><h3>{{ item.name }}</h3></summary>
        <div class="jc-faq-content"><p>{{ item.value }}</p></div>
    </details>
    <# }); #>
</div>
<?php
    }
}
