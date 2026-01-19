<?php

namespace Jelly_Catalog\Addons\Elementor\tags;

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Product_Cat_Advantages extends \Elementor\Core\DynamicTags\Tag
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
		return 'jc-product-cat-advantages-tag';
	}

	public function get_title()
	{
		return esc_html__('Category Advantages', 'jelly-catalog');
	}

	public function render()
	{
		$queried_object = get_queried_object();
		
		if (isset($queried_object->term_id)) {
			$category_advantages = get_term_meta($queried_object->term_id, 'category_advantages', true);
			
			if ($category_advantages) {
				echo wp_kses_post($category_advantages);
			} else {
				echo '';
			}
		} else {
			echo '';
		}
	}
}