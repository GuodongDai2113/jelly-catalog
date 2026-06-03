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
} // 绂佹鐩存帴璁块棶

/**
 * 产品 FAQ 组件。
 */
class Product_FAQ extends \Elementor\Widget_Base
{
    /**
     * 获取组件唯一名称。
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'jc-product-faq';
    }

    /**
     * 获取组件标题。
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Product FAQ', 'jelly-catalog');
    }

    /**
     * 获取组件图标。
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-checkbox jelly-engine-icon';
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
        return ['product', 'faq', 'question', 'answer'];
    }

    /**
     * 返回产品 FAQ 组件依赖的样式句柄。
     *
     * @return array
     */
    public function get_style_depends(): array
    {
        return ['jelly-catalog-product-faq'];
    }

    /**
     * 注册样式控制项。
     *
     * @return void
     */
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
                    '{{WRAPPER}} .jc-product-faq' => 'gap: {{SIZE}}{{UNIT}};',
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
                    '{{WRAPPER}} .jc-product-faq-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'item_background',
            [
                'label' => esc_html__('Background Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .jc-product-faq-item' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'item_border',
                'selector' => '{{WRAPPER}} .jc-product-faq-item',
            ]
        );

        $this->add_responsive_control(
            'item_border_radius',
            [
                'label' => esc_html__('Border Radius', 'jelly-catalog'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'rem', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .jc-product-faq-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
                'selector' => '{{WRAPPER}} .jc-product-faq-question',
            ]
        );

        $this->add_control(
            'question_color',
            [
                'label' => esc_html__('Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .jc-product-faq-question' => 'color: {{VALUE}};',
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
                    '{{WRAPPER}} .jc-product-faq-question' => 'margin-bottom: {{SIZE}}{{UNIT}};',
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
                'selector' => '{{WRAPPER}} .jc-product-faq-answer',
            ]
        );

        $this->add_control(
            'answer_color',
            [
                'label' => esc_html__('Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .jc-product-faq-answer' => 'color: {{VALUE}};',
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
                    '{{WRAPPER}} .jc-product-faq-answer' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
        $faq_items = jc_get_product_faq_items(get_the_ID());

        if (empty($faq_items)) {
            return;
        }

        ?>
<div class="jc-product-faq">
    <?php foreach ($faq_items as $faq_item): ?>
    <article class="jc-product-faq-item">
        <h3 class="jc-product-faq-question"><?php echo esc_html($faq_item['name']); ?></h3>
        <div class="jc-product-faq-answer">
            <?php echo wp_kses_post($faq_item['value']); ?>
        </div>
    </article>
    <?php endforeach; ?>
</div>
<?php
    }

    /**
     * 输出编辑器预览模板。
     *
     * @return void
     */
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
<div class="jc-product-faq">
    <# _.each(faqs, function(item) { #>
    <article class="jc-product-faq-item">
        <h3 class="jc-product-faq-question">{{ item.name }}</h3>
        <div class="jc-product-faq-answer">{{{ item.value }}}</div>
    </article>
    <# }); #>
</div>
<?php
    }
}
