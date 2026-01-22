<?php

namespace Jelly_Catalog\Addons\Elementor\tags;

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Product_Excerpt extends \Elementor\Core\DynamicTags\Tag
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
		return 'jc-product-excerpt-tag';
	}

	public function get_title()
	{
		return esc_html__('Product Short Description', 'jelly-catalog');
	}

	public function render()
	{
		if (has_excerpt()) {
			$excerpt = get_the_excerpt();

			$excerpt = wpautop($excerpt);

			echo wp_kses_post($excerpt);
		}
	}
}
