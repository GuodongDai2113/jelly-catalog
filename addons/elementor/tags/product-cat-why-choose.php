<?php

namespace Jelly_Catalog\Addons\Elementor\tags;

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Product_Cat_Why_Choose extends \Elementor\Core\DynamicTags\Tag
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
		return 'jc-product-cat-why-choose-tag';
	}

	public function get_title()
	{
		return esc_html__('Category Why Choose', 'jelly-catalog');
	}

	public function render()
	{
		$queried_object = get_queried_object();
		
		if (isset($queried_object->term_id)) {
			$category_why_choose = get_term_meta($queried_object->term_id, 'category_why_choose', true);
			
			if ($category_why_choose) {
				echo wp_kses_post($category_why_choose);
			} else {
				echo '';
			}
		} else {
			echo '';
		}
	}
}