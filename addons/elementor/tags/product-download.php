<?php

namespace Jelly_Catalog\Addons\Elementor\tags;

use Elementor\Controls_Manager;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * 产品下载动态标签。
 */
class Product_Download extends \Elementor\Core\DynamicTags\Tag
{
    /**
     * 获取标签所属组。
     *
     * @return string
     */
    public function get_group()
    {
        return 'jelly-catalog';
    }

    /**
     * 获取标签支持的分类。
     *
     * @return array
     */
    public function get_categories()
    {
        return ['text', 'url'];
    }

    /**
     * 获取标签内部名称。
     *
     * @return string
     */
    public function get_name()
    {
        return 'jc-product-download-tag';
    }

    /**
     * 获取标签显示标题。
     *
     * @return string
     */
    public function get_title()
    {
        return esc_html__('Product Download', 'jelly-catalog');
    }

    /**
     * 注册标签控制参数。
     *
     * @return void
     */
    protected function register_controls()
    {
        $this->add_control(
            'output_type',
            [
                'label' => esc_html__('Output', 'jelly-catalog'),
                'type' => Controls_Manager::SELECT,
                'default' => 'url',
                'options' => [
                    'url' => esc_html__('File URL', 'jelly-catalog'),
                    'filename' => esc_html__('File Name', 'jelly-catalog'),
                ],
            ]
        );
    }

    /**
     * 渲染标签输出内容。
     *
     * @return void
     */
    public function render()
    {
        $product_id = get_the_ID();
        if (!$product_id) {
            return;
        }

        $download_file = jc_get_product_download_file($product_id);
        if (empty($download_file)) {
            return;
        }

        $output_type = $this->get_settings('output_type');
        $output = '';

        if ('filename' === $output_type) {
            $output = $download_file['filename'];
        } else {
            $output = $download_file['url'];
        }

        echo wp_kses_post($output);
    }
}
