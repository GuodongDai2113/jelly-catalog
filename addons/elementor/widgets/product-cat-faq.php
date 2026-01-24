<?php

namespace Jelly_Catalog\Addons\Elementor\Widgets;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Product_Cat_Faq extends \Elementor\Widget_Base
{

    public function get_name()
    {
        return 'jc-product-cat-faq';
    }

    public function get_title()
    {
        return esc_html__('Category FAQ', 'jelly-catalog');
    }

    public function get_icon()
    {
        return 'eicon-checkbox';
    }

    public function get_categories()
    {
        return ['jc-elements-archive'];
    }

    public function get_keywords()
    {
        return ['faq', 'category', 'questions', 'answers'];
    }

    protected function register_controls() {}

    protected function render()
    {

        $faqs = array();

        $queried_object = get_queried_object();
        if (isset($queried_object->term_id)) {
            $faqs = get_term_meta($queried_object->term_id, 'product_cat_faqs', true);
        }

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
