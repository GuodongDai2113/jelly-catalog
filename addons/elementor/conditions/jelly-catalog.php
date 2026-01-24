<?php
/*
 * 摘取文件 elementor-pro\modules\woocommerce\conditions\woocommerce.php
 * 
 */

namespace Jelly_Catalog\Addons\Elementor\Conditions;

use ElementorPro\Modules\ThemeBuilder as ThemeBuilder;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Jelly_Catalog extends ThemeBuilder\Conditions\Condition_Base
{

    public static function get_type()
    {
        return 'jelly-catalog';
    }

    public function get_name()
    {
        return 'jelly-catalog';
    }

    public function get_label()
    {
        return esc_html__('Jelly Catalog', 'jelly-catalog');
    }

    public function get_all_label()
    {
        return esc_html__('Entire Catalog', 'jelly-catalog');
    }

    public function register_sub_conditions()
    {
        $product_archive = new Product_Archive();

        $product_single = new ThemeBuilder\Conditions\Post([
            'post_type' => 'product',
        ]);

        $this->register_sub_condition($product_archive);
        $this->register_sub_condition($product_single);
    }

    public function check($args)
    {
        return is_jc_product_single() || is_jc_product_archive() || is_jc_product_search();
    }
}
