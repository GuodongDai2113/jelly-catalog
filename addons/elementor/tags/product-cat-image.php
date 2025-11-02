<?php

namespace Jelly_Catalog\Addons\Elementor\tags;

use ElementorPro\Modules\LoopBuilder\Providers\Taxonomy_Loop_Provider;
use ElementorPro\Modules\DynamicTags\Tags\Base\Tag_Trait;
use Elementor\Controls_Manager;

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class Product_Cat_Image extends \ElementorPro\Modules\DynamicTags\Tags\Base\Data_Tag
{
	use Tag_Trait;
	public function get_group()
	{
		return 'jelly-catalog';
	}

	public function get_categories()
	{
		return ['image'];
	}

	public function get_name()
	{
		return 'jc-product-cat-image-tag';
	}

	public function get_title()
	{
		return esc_html__('Category Image', 'jelly-catalog');
	}

	protected function register_controls()
	{
		$this->add_control(
			'fallback',
			[
				'label' => esc_html__('Fallback', 'jelly-catalog'),
				'type' => Controls_Manager::MEDIA,
			]
		);
	}

	public function get_value(array $options = [])
	{
		$category_id = $this->get_category_id();

		if ($category_id) {
			$image_data = $this->get_category_image_data($category_id);
		} else {
			$image_data = $this->get_settings('fallback');
		}

		if (empty($image_data)) {
			return [];
		}

		return $image_data;
	}

	/**
	 * 获取当前分类ID
	 * 
	 * @return int
	 */
	private function get_category_id()
	{
		if (is_jc_product_category()) {
			return get_queried_object_id();
		} elseif (Taxonomy_Loop_Provider::is_loop_taxonomy()) {
			return $this->get_data_id_from_taxonomy_loop_query();
		}

		return 0;
	}

	/**
	 * 获取分类图片数据
	 * 
	 * @param int $category_id
	 * @return array
	 */
	private function get_category_image_data($category_id)
	{
		$image_id = get_term_meta($category_id, 'thumbnail_id', true);

		if ($image_id) {
			$src = wp_get_attachment_image_src($image_id, 'full');
			if ($src) {
				return [
					'id' => $image_id,
					'url' => $src[0]
				];
			}
		}

		return $this->get_settings('fallback');
	}
}
