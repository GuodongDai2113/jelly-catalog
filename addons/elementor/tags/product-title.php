<?php

namespace Jelly_Catalog\Addons\Elementor\tags;

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Product_Title extends \Elementor\Core\DynamicTags\Tag
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
		return 'jc-product-title-tag';
	}

	public function get_title()
	{
		return esc_html__('Product Title', 'jelly-catalog');
	}

	public function render()
	{
		$title = get_the_title();

		echo wp_kses_post($title);
	}
}
