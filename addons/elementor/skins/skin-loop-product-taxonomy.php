<?php
namespace Jelly_Catalog\Addons\Elementor\Skins;

use ElementorPro\Modules\LoopBuilder\Skins\Skin_Loop_Taxonomy_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Skin_Loop_Product_Taxonomy extends Skin_Loop_Taxonomy_Base {
	protected $post_type = 'product';

	public function get_id() {
		return 'product_taxonomy';
	}

	public function get_title() {
		return esc_html__( 'Product Taxonomy', 'elementor-pro' );
	}

	public function render() {
		$this->parent->add_render_attribute( '_wrapper', 'class', 'jelly-catalog' );
		parent::render();
	}

	protected function get_default_source_option() {
		return 'product_cat';
	}
}
