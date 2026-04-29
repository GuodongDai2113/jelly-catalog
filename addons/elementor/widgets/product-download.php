<?php

namespace Jelly_Catalog\Addons\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Icons_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;

if (!defined('ABSPATH')) {
    exit;
} // 禁止直接访问

class Product_Download extends Widget_Base
{
    public function get_name()
    {
        return 'product_download';
    }

    public function get_title()
    {
        return esc_html__('Product Download', 'jelly-catalog');
    }

    public function get_icon()
    {
        return 'eicon-download-button jelly-engine-icon';
    }

    public function get_categories()
    {
        return ['jc-elements-single'];
    }

    public function get_keywords()
    {
        return ['product', 'download', 'catalog', 'file'];
    }

    public function get_style_depends()
    {
        return [];
    }

    protected function register_controls()
    {
        $this->start_controls_section(
            'section_content',
            [
                'label' => esc_html__('Content', 'jelly-catalog'),
            ]
        );

        $this->add_control(
            'file_icon',
            [
                'label' => esc_html__('File Icon', 'jelly-catalog'),
                'type' => Controls_Manager::ICONS,
                'fa4compatibility' => 'icon',
                'default' => [
                    'value' => 'fas fa-file-pdf',
                    'library' => 'fa-solid',
                ],
            ]
        );

        $this->add_control(
            'download_icon',
            [
                'label' => esc_html__('Download Icon', 'jelly-catalog'),
                'type' => Controls_Manager::ICONS,
                'fa4compatibility' => 'icon',
                'default' => [
                    'value' => 'fas fa-cloud-download-alt',
                    'library' => 'fa-solid',
                ],
            ]
        );

        $this->add_control(
            'no_file_message',
            [
                'label' => esc_html__('Empty Message', 'jelly-catalog'),
                'type' => Controls_Manager::TEXTAREA,
                'default' => esc_html__('The product files have not yet been uploaded. Please contact customer service to obtain them.', 'jelly-catalog'),
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style',
            [
                'label' => esc_html__('Download Item', 'jelly-catalog'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'item_padding',
            [
                'label' => esc_html__('Padding', 'jelly-catalog'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'rem', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .jc-download-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'item_gap',
            [
                'label' => esc_html__('Gap', 'jelly-catalog'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'rem', 'em'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 80,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .jc-download-item' => 'display: flex; align-items: center; gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'item_background',
            [
                'label' => esc_html__('Background Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .jc-download-item' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'item_border',
                'selector' => '{{WRAPPER}} .jc-download-item',
            ]
        );

        $this->add_responsive_control(
            'item_border_radius',
            [
                'label' => esc_html__('Border Radius', 'jelly-catalog'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'rem', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .jc-download-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'item_box_shadow',
                'selector' => '{{WRAPPER}} .jc-download-item',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_icon_style',
            [
                'label' => esc_html__('Icons', 'jelly-catalog'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'file_icon_size',
            [
                'label' => esc_html__('File Icon Size', 'jelly-catalog'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'rem', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .jc-download-icon' => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .jc-download-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'file_icon_color',
            [
                'label' => esc_html__('File Icon Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .jc-download-icon' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .jc-download-icon svg' => 'fill: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'download_icon_size',
            [
                'label' => esc_html__('Download Icon Size', 'jelly-catalog'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'rem', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .jc-download-button' => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .jc-download-button svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'download_icon_color',
            [
                'label' => esc_html__('Download Icon Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .jc-download-button' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .jc-download-button svg' => 'fill: {{VALUE}};',
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
                'selector' => '{{WRAPPER}} .jc-download-name',
            ]
        );

        $this->add_control(
            'name_color',
            [
                'label' => esc_html__('Name Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .jc-download-name' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'size_typography',
                'label' => esc_html__('Size Typography', 'jelly-catalog'),
                'selector' => '{{WRAPPER}} .jc-download-size',
            ]
        );

        $this->add_control(
            'size_color',
            [
                'label' => esc_html__('Size Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .jc-download-size' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'empty_typography',
                'label' => esc_html__('Empty Message Typography', 'jelly-catalog'),
                'selector' => '{{WRAPPER}} .jc-download-no-file',
            ]
        );

        $this->add_control(
            'empty_color',
            [
                'label' => esc_html__('Empty Message Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .jc-download-no-file' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();

        // 获取当前产品的ID
        $product_id = get_the_ID();

        $product_file = jc_get_product_download_file($product_id);

        if ($product_file) {
            $file_url = $product_file['url'] ?? '#';
            $file_name = $product_file['filename'] ?? esc_html__('Product Catalog.pdf', 'jelly-catalog');
            $file_size = !empty($product_file['filesize']) ? size_format(absint($product_file['filesize'])) : '';
        }

        ?>
<?php if ($product_file): ?>
<div class="jc-download">
    <a href="<?php echo esc_url($file_url); ?>" class="jc-download-item"
        download="<?php echo esc_attr($file_name); ?>">
        <span class="jc-download-icon">
            <?php Icons_Manager::render_icon($settings['file_icon'], ['aria-hidden' => 'true']); ?>
        </span>
        <div class="jc-download-file">
            <span class="jc-download-name">
                <?php echo esc_html($file_name); ?>
            </span>
            <?php if ($file_size): ?>
            <span class="jc-download-size">
                <?php echo esc_html($file_size); ?>
            </span>
            <?php endif; ?>
        </div>
        <span class="jc-download-button">
            <?php Icons_Manager::render_icon($settings['download_icon'], ['aria-hidden' => 'true']); ?>
        </span>
    </a>
</div>
<?php else: ?>
<p class="jc-download-no-file">
    <?php echo esc_html($settings['no_file_message']); ?>
</p>
<?php endif; ?>
<?php
    }

    protected function content_template(): void
    {
        $file_name = esc_html__('Product Catalog.pdf', 'jelly-catalog');
        $file_size = esc_html__('2.4 MB', 'jelly-catalog');
        ?>
<#
var fileIconHTML = elementor.helpers.renderIcon(view, settings.file_icon, { 'aria-hidden': true }, 'i', 'object');
var downloadIconHTML = elementor.helpers.renderIcon(view, settings.download_icon, { 'aria-hidden': true }, 'i', 'object');
#>
<div class="jc-download">
    <a href="#" class="jc-download-item" download="<?php echo esc_attr($file_name); ?>" aria-disabled="true" onclick="return false;">
        <span class="jc-download-icon">
            <# if (fileIconHTML && fileIconHTML.value) { #>
            {{{ fileIconHTML.value }}}
            <# } #>
        </span>
        <div class="jc-download-file">
            <span class="jc-download-name"><?php echo esc_html($file_name); ?></span>
            <span class="jc-download-size"><?php echo esc_html($file_size); ?></span>
        </div>
        <span class="jc-download-button">
            <# if (downloadIconHTML && downloadIconHTML.value) { #>
            {{{ downloadIconHTML.value }}}
            <# } #>
        </span>
    </a>
</div>
<?php
    }
}
