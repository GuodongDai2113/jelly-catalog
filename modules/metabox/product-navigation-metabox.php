<?php

/**
 * includes\metabox\class-jc-product-navigation-metabox.php
 *
 * @see: https://jellydai.com
 * @author: Jelly Dai <daiguo1003@gmail.com>
 * @created: 2026.05.09
 */

namespace Jelly_Catalog\Modules\Metabox;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // 禁止直接访问

/**
 * 产品编辑页前后跳转元框
 *
 * 提供上一条和下一条产品的快速跳转入口
 */
class Product_Navigation_Metabox {

	/**
	 * 注册产品导航 metabox。
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 30 );
	}

	/**
	 * 挂载 metabox 到产品编辑页。
	 *
	 * @return void
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'product_navigation_metabox',
			__( 'Product Navigation', 'jelly-catalog' ),
			array( $this, 'render_metabox' ),
			'product',
			'side',
			'low'
		);
	}

	/**
	 * 输出前后产品跳转内容。
	 *
	 * @param \WP_Post $post 当前产品对象。
	 * @return void
	 */
	public function render_metabox( $post ) {
		$previous_product = $this->get_adjacent_product( $post->ID, 'previous' );
		$next_product     = $this->get_adjacent_product( $post->ID, 'next' );
		?>
<div class="jc-product-navigation">
		<?php $this->render_navigation_item( $previous_product, __( 'Previous Product', 'jelly-catalog' ) ); ?>
		<?php $this->render_navigation_item( $next_product, __( 'Next Product', 'jelly-catalog' ) ); ?>
</div>
		<?php
	}

	/**
	 * 根据当前产品 ID 获取相邻产品。
	 *
	 * @param int    $post_id   当前产品 ID。
	 * @param string $direction 方向，支持 previous / next。
	 * @return \WP_Post|null
	 */
	protected function get_adjacent_product( $post_id, $direction ) {
		global $wpdb;

		$post_id = absint( $post_id );

		if ( ! $post_id ) {
			return null;
		}

		$statuses            = array( 'publish', 'future', 'draft', 'pending', 'private' );
		$status_placeholders = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );
		$operator            = 'previous' === $direction ? '<' : '>';
		$sort_order          = 'previous' === $direction ? 'DESC' : 'ASC';

		// 通过 ID 顺序查找相邻产品，便于在后台编辑流中快速切换。
		$query            = $wpdb->prepare(
			"
            SELECT ID
            FROM {$wpdb->posts}
            WHERE post_type = %s
              AND post_status IN ({$status_placeholders})
              AND ID {$operator} %d
            ORDER BY ID {$sort_order}
            LIMIT 1
            ",
			array_merge( array( 'product' ), $statuses, array( $post_id ) )
		);
		$adjacent_post_id = absint( $wpdb->get_var( $query ) );

		if ( ! $adjacent_post_id ) {
			return null;
		}

		$adjacent_post = get_post( $adjacent_post_id );

		return $adjacent_post instanceof \WP_Post ? $adjacent_post : null;
	}

	/**
	 * 渲染单个跳转条目。
	 *
	 * @param \WP_Post|null $product 当前相邻产品对象。
	 * @param string        $label   条目标签。
	 * @return void
	 */
	protected function render_navigation_item( $product, $label ) {
		$title          = $product ? get_the_title( $product ) : __( 'No product available', 'jelly-catalog' );
		$title          = '' !== trim( (string) $title ) ? $title : __( '(no title)', 'jelly-catalog' );
		$edit_link      = $product ? get_edit_post_link( $product->ID, 'raw' ) : '';
		$thumbnail_id   = $product ? absint( get_post_thumbnail_id( $product->ID ) ) : 0;
		$thumbnail_html = $thumbnail_id
			? wp_get_attachment_image(
				$thumbnail_id,
				'thumbnail',
				false,
				array(
					'class'   => 'jc-product-navigation__image',
					'loading' => 'lazy',
					'alt'     => $title,
				)
			)
			: '';
		?>
<div class="jc-product-navigation__item<?php echo $product ? '' : ' is-empty'; ?>">
	<span class="jc-product-navigation__label"><?php echo esc_html( $label ); ?></span>

		<?php if ( $edit_link ) : ?>
	<a class="jc-product-navigation__link" href="<?php echo esc_url( $edit_link ); ?>">
		<span class="jc-product-navigation__thumb">
			<?php if ( $thumbnail_html ) : ?>
				<?php echo $thumbnail_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php else : ?>
			<span class="jc-product-navigation__placeholder dashicons dashicons-format-image" aria-hidden="true"></span>
			<?php endif; ?>
		</span>
		<strong class="jc-product-navigation__title"><?php echo esc_html( $title ); ?></strong>
	</a>
	<?php else : ?>
	<div class="jc-product-navigation__link">
		<span class="jc-product-navigation__thumb">
			<span class="jc-product-navigation__placeholder dashicons dashicons-format-image" aria-hidden="true"></span>
		</span>
		<strong class="jc-product-navigation__title"><?php echo esc_html( $title ); ?></strong>
	</div>
	<?php endif; ?>
</div>
		<?php
	}
}
