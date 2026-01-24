<?php
/*
 * 摘取文件 elementor-pro\modules\woocommerce\conditions\shop-page.php
 * 
 */

namespace Jelly_Catalog\Addons\Elementor\Conditions;

use ElementorPro\Modules\ThemeBuilder as ThemeBuilder;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Products_Page extends ThemeBuilder\Conditions\Condition_Base
{

    public static function get_type()
    {
        return 'singular';
    }

    public function get_name()
    {
        return 'product_page';
    }

    public static function get_priority()
    {
        return 40;
    }

    public function get_label()
    {
        return esc_html__('Product Page', 'jelly-catalog');
    }

    public function check($args)
    {
        return is_jc_products();
    }
}
