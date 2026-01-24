<?php
/*
 * 摘取文件 elementor-pro\modules\woocommerce\conditions\product-search.php
 * 
 */

namespace Jelly_Catalog\Addons\Elementor\Conditions;

use ElementorPro\Modules\ThemeBuilder as ThemeBuilder;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Product_Search extends ThemeBuilder\Conditions\Condition_Base
{

    public static function get_type()
    {
        return 'archive';
    }

    public function get_name()
    {
        return 'product_search';
    }

    public static function get_priority()
    {
        return 40;
    }

    public function get_label()
    {
        return esc_html__('Search Results', 'jelly-catalog');
    }

    public function check($args)
    {
        return is_jc_product_search();
    }
}
