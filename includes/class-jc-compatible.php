<?php

/**
 * includes\class-jc-compatible.php
 * 
 * @see: https://jellydai.com
 * @author: Jelly Dai <d@jellydai.com>
 * @created : 2025.09.01 15:13
 */

if (! defined('ABSPATH')) exit; // 禁止直接访问

add_filter('rank_math/frontend/breadcrumb/items', 'jc_replace_product_cat_name', 20, 2);

function jc_replace_product_cat_name($crumbs, $breadcrumbs)
{
    // 检查是否是产品分类页面
    if (is_tax('product_cat') || is_tax('product_tag') || is_post_type_archive('product')) {
        // 查找并修改shop页面的面包屑
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

