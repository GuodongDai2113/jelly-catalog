<?php

namespace Jelly_Catalog\Addons\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Icons_Manager;

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


        $this->end_controls_section();

        $this->start_controls_section(
            'section_style',
            [
                'label' => esc_html__('Style', 'jelly-catalog'),
                'tab' => Controls_Manager::TAB_STYLE,
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

?>
        <?php if ($product_file): ?>
            <div class="jc-download">
                <a href="<?php echo esc_url($product_file['url']); ?> download"
                    class="jc-download-item"
                    download="<?php echo esc_attr($product_file['filename']); ?>">
                    <span class="jc-download-icon">
                        <?php Icons_Manager::render_icon(['value' => 'fas fa-file-pdf', 'library' => 'fa-solid'], ['aria-hidden' => 'true']); ?>
                    </span>
                    <div class="jc-download-file">
                        <span class="jc-download-name">
                            <?php echo esc_html($product_file['filename']); ?>
                        </span>
                        <span class="jc-download-size">
                            <?php echo esc_html(size_format($product_file['filesize'])); ?>
                        </span>
                    </div>
                    <span class="jc-download-button">
                        <?php Icons_Manager::render_icon(['value' => 'fas fa-cloud-download-alt', 'library' => 'fa-solid'], ['aria-hidden' => 'true']); ?>
                    </span>
                </a>
            </div>
        <?php else: ?>
            <p class="jc-download-no-file"><?php echo esc_html__('The product files have not yet been uploaded. Please contact customer service to obtain them.'); ?></p>
        <?php endif; ?>
<?php
    }
}
