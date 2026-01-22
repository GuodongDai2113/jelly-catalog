<?php

namespace Jelly_Catalog\Addons\Elementor\tags;

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Product_Primary_Category extends \Elementor\Core\DynamicTags\Tag
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
		return 'jc-product-primary-category-tag';
	}

	public function get_title()
	{
		return esc_html__('Product Primary Category', 'jelly-catalog');
	}

	public function render()
	{
		$terms = get_the_terms(get_the_ID(), 'product_cat');

		if (empty($terms) || is_wp_error($terms)) {
			return;
		}

		$primary_term = $terms[0];

		if (isset($primary_term->name)) {
			echo esc_html($primary_term->name);
		}
	}
}
