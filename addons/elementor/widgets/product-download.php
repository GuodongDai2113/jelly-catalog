<?php

namespace Jelly_Catalog\Addons\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Core\Kits\Documents\Tabs\Global_Colors;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;

if (!defined('ABSPATH')) exit; // 禁止直接访问

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
        return 'eicon-download-button';
    }

    public function get_categories()
    {
        return ['jelly-catalog'];
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
            'download_label',
            [
                'label' => esc_html__('Download Label', 'jelly-catalog'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Download Product Catalog', 'jelly-catalog'),
                'placeholder' => esc_html__('Enter download button label', 'jelly-catalog'),
            ]
        );

        $this->add_control(
            'no_file_text',
            [
                'label' => esc_html__('No File Text', 'jelly-catalog'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('No download file available', 'jelly-catalog'),
                'placeholder' => esc_html__('Text to show when no file is available', 'jelly-catalog'),
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style',
            [
                'label' => esc_html__('Style', 'jelly-catalog'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'button_color',
            [
                'label' => esc_html__('Button Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .jc-product-download-btn' => 'background-color: {{VALUE}};',
                ],
                'default' => '#2c62ed',
            ]
        );

        $this->add_control(
            'text_color',
            [
                'label' => esc_html__('Text Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .jc-product-download-btn' => 'color: {{VALUE}};',
                ],
                'default' => '#ffffff',
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        if (!function_exists('get_field')) {
            return;
        }
        $settings = $this->get_settings_for_display();

        // 获取当前产品的ID
        $product_id = get_the_ID();

        // 从ACF字段获取产品文件
        $product_file = get_field('product_file', $product_id);

        $download_label = $settings['download_label'] ?: esc_html__('Download Product Catalog', 'jelly-catalog');
        $no_file_text = $settings['no_file_text'] ?: esc_html__('No download file available', 'jelly-catalog');
?>
        <div class="jc-product-download">
            <?php if ($product_file): ?>
                <a href="<?php echo esc_url($product_file['url']); ?>"
                    class="jc-product-download-btn"
                    download="<?php echo esc_attr($product_file['filename']); ?>">
                    <?php echo esc_html($download_label); ?>
                </a>
            <?php else: ?>
                <p class="jc-no-file"><?php echo esc_html($no_file_text); ?></p>
            <?php endif; ?>
        </div>
<?php
    }
}
