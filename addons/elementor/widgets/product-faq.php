<?php

/**
 * addons\elementor\widgets\product-faq.php
 * 
 * @see: https://jellydai.com
 * @author: Jelly Dai <daiguo1003@gmail.com>
 * @created : 2025.11.10 13:32
 */

namespace Jelly_Catalog\Addons\Elementor\Widgets;

if (! defined('ABSPATH')) exit; // 禁止直接访问

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
        return 'eicon-checkbox';
    }

    public function get_categories(): array
    {
        return ['jc-elements-single'];
    }

    public function get_keywords(): array
    {
        return ['product', 'faq', 'question', 'answer'];
    }
    protected function register_controls(): void {}

    protected function render(): void
    {
        $faqs = get_post_meta(get_the_ID(), '_product_faqs', true);
        if (! empty($faqs) && is_array($faqs)) {
            echo "<div class='jc-faq'>";
            foreach ($faqs as $item) {
                $name = esc_html($item['name'] ?? '');
                $value = wp_kses_post($item['value'] ?? '');
                echo "<details class='jc-faq-item'>";
                echo "<summary class='jc-faq-header'><h3>{$name}</h3></summary>";
                echo "<div class='jc-faq-content'><p>{$value}</p></div>";
                echo "</details>";
            }
            echo "</div>";
        }
    }
}
