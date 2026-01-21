<?php

namespace Jelly_Catalog\Addons\Elementor\tags;

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Product_Cat_H1_Title extends \Elementor\Core\DynamicTags\Tag
{

	public function get_group()
	{
		return 'jelly-catalog';
	}

	public function get_categories()
	{
		return ['text'];
	}

	public function get_name()
	{
		return 'jc-product-cat-h1-title-tag';
	}

	public function get_title()
	{
		return esc_html__('Category H1 Title', 'jelly-catalog');
	}

	public function render()
	{
		$queried_object = get_queried_object();
		
		if (isset($queried_object->term_id)) {
			$category_h1_title = get_term_meta($queried_object->term_id, 'category_h1_title', true);
			
			if (!empty($category_h1_title)) {
				echo esc_html($category_h1_title);
			} else {
				// 如果meta为空，则使用站点标题 + 分类名称 + Supplier
				$site_name = get_bloginfo('name');
				$category_name = $queried_object->name;
				echo esc_html($site_name . ' ' . $category_name . ' Supplier');
			}
		} else {
			echo '';
		}
	}
}