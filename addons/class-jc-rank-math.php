<?php

/**
 * addons\class-jc-rank-math.php
 * 
 * @see: https://jellydai.com
 * @author: Jelly Dai <daiguo1003@gmail.com>
 * @created : 2025.09.05 15:18
 */

if (! defined('ABSPATH')) exit; // 禁止直接访问

class JC_Rank_Math
{
    public static $instance;


    public static function instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    public function __construct()
    {
        add_filter('rank_math/frontend/breadcrumb/items', array($this, 'replace_product_cat_name'), 20, 2);
    }

    function replace_product_cat_name($crumbs, $breadcrumbs)
    {
        // 检查是否是产品分类页面
        if (is_tax('product_cat') || is_tax('product_tag') || is_post_type_archive('product')) {
            foreach ($crumbs as $key => $crumb) {
                if ($crumb[0] === 'Product categories') {
                    $crumb[0] = "Products";
                    $crumb[1] = home_url('products/');
                    $crumbs[$key] = $crumb;
                    break;
                }
                if ($crumb[0] === 'Product tags') {
                    $crumb[0] = "Products";
                    $crumb[1] = home_url('products/');
                    $crumbs[$key] = $crumb;
                    break;
                }
            }
        }
        return $crumbs;
    }
}

JC_Rank_Math::instance();
