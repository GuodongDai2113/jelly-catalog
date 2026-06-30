<?php

/**
 * modules\permalinks.php
 *
 * @see https://jellydai.com
 * @author Jelly Dai
 * @created 2026.05.08 07:10
 */

namespace Jelly_Catalog\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 产品固定链接模块
 *
 * 负责产品与分类链接输出，以及在重写规则生成阶段注入自定义规则
 */
class Permalinks {

	/** 产品基础路径常量：使用 products */
	public const PRODUCT_BASE_PRODUCTS = 'products';

	/** 产品基础路径常量：使用 product */
	public const PRODUCT_BASE_PRODUCT = 'product';

	/** 产品基础路径常量：使用分类 slug */
	public const PRODUCT_BASE_CATEGORY = '%product_cat%';

	/** 分类基础路径常量：默认 product-category */
	public const CATEGORY_BASE_DEFAULT = 'product-category';

	/** 分类基础路径常量：无前缀 */
	public const CATEGORY_BASE_NONE = '';

	/**
	 * 注册固定链接相关钩子
	 */
	public function __construct() {
		add_filter( 'rewrite_rules_array', array( $this, 'filter_rewrite_rules' ) );
		add_filter( 'post_type_link', array( $this, 'filter_product_permalink' ), 10, 4 );
		add_filter( 'term_link', array( $this, 'filter_product_cat_link' ), 10, 3 );
	}

	/**
	 * 注入产品与分类自定义重写规则
	 *
	 * 仅在 WordPress 生成 rewrite 规则时执行
	 *
	 * @param array $rules 现有重写规则
	 * @return array
	 */
	public function filter_rewrite_rules( $rules ) {
		$permalinks     = self::get_permalink_structure();
		$custom_rules   = array();
		$category_slugs = $this->get_product_category_rewrite_slugs();

		if ( self::uses_category_base_without_prefix( $permalinks ) ) {
			foreach ( $category_slugs as $category_slug ) {
				$quoted_slug = preg_quote( $category_slug, '/' );
				$custom_rules[ '^' . $quoted_slug . '/page/([0-9]{1,})/?$' ] =
					'index.php?taxonomy=product_cat&term=' . $category_slug . '&paged=$matches[1]';
				$custom_rules[ '^' . $quoted_slug . '/?$' ]                  =
					'index.php?taxonomy=product_cat&term=' . $category_slug;
			}
		}

		if ( self::uses_category_in_product_permalink( $permalinks ) ) {
			foreach ( $category_slugs as $category_slug ) {
				$quoted_slug                                        = preg_quote( $category_slug, '/' );
				$custom_rules[ '^' . $quoted_slug . '/([^/]+)/?$' ] =
					'index.php?post_type=product&name=$matches[1]';
			}
		}

		if ( empty( $custom_rules ) ) {
			return $rules;
		}

		return $custom_rules + $rules;
	}

	/**
	 * 过滤产品固定链接
	 *
	 * 当产品固定链接策略为分类 slug 时，输出 `/{category-slug}/{product-slug}`
	 *
	 * @param string   $post_link 产品链接
	 * @param \WP_Post $post 文章对象
	 * @param bool     $leavename 是否保留 post name 占位符
	 * @param bool     $sample 是否为 sample permalink
	 * @return string
	 */
	public function filter_product_permalink( $post_link, $post, $leavename, $sample ) {
		if ( 'product' !== $post->post_type ) {
			return $post_link;
		}

		$permalinks = self::get_permalink_structure();

		if ( ! self::uses_category_in_product_permalink( $permalinks ) ) {
			return $post_link;
		}

		$post_name = $leavename ? '%postname%' : $post->post_name;
		if ( ! $post_name ) {
			$post_name = $sample ? sanitize_title( $post->post_title ) : $post->post_name;
		}

		$permalink_term = self::get_permalink_product_category( $post );
		$base_path      = $permalink_term
			? self::get_product_category_path( $permalink_term, false )
			: $permalinks['product_archive_slug'];
		$path           = trim( $base_path . '/' . $post_name, '/' );

		return home_url( user_trailingslashit( $path, 'single' ) );
	}

	/**
	 * 过滤产品分类固定链接
	 *
	 * 当分类基础设置为无前缀时，输出 `/{category-slug}`
	 *
	 * @param string          $termlink 分类链接
	 * @param \WP_Term|string $term 分类对象
	 * @param string          $taxonomy 分类法名称
	 * @return string
	 */
	public function filter_product_cat_link( $termlink, $term, $taxonomy ) {
		if ( 'product_cat' !== $taxonomy ) {
			return $termlink;
		}

		$permalinks = self::get_permalink_structure();

		if ( ! self::uses_category_base_without_prefix( $permalinks ) ) {
			return $termlink;
		}

		$category_slug = self::get_product_category_path( $term, false );

		if ( '' === $category_slug ) {
			return $termlink;
		}

		return home_url( user_trailingslashit( $category_slug, 'category' ) );
	}

	/**
	 * 获取参与 rewrite 生成的产品分类 slug 列表
	 *
	 * 仅使用扁平 slug，避免重复生成层级路径规则
	 *
	 * @return array
	 */
	private function get_product_category_rewrite_slugs() {
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			)
		);

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return array();
		}

		$slugs = array();

		foreach ( $terms as $term ) {
			$slug = self::get_product_category_path( $term, false );

			if ( '' !== $slug ) {
				$slugs[ $slug ] = $slug;
			}
		}

		return array_values( $slugs );
	}

	/**
	 * 获取产品固定链接结构配置
	 *
	 * @return array
	 */
	public static function get_permalink_structure() {
		$saved_permalinks = (array) get_option( 'jelly_catalog_permalinks', array() );
		$permalinks       = wp_parse_args(
			$saved_permalinks,
			array(
				'product_base'  => self::PRODUCT_BASE_PRODUCTS,
				'category_base' => self::CATEGORY_BASE_DEFAULT,
			)
		);

		$allowed_product_bases = array(
			self::PRODUCT_BASE_PRODUCTS,
			self::PRODUCT_BASE_PRODUCT,
			self::PRODUCT_BASE_CATEGORY,
		);

		if ( ! in_array( $permalinks['product_base'], $allowed_product_bases, true ) ) {
			$permalinks['product_base'] = self::PRODUCT_BASE_PRODUCTS;
		}

		$allowed_category_bases = array(
			self::CATEGORY_BASE_DEFAULT,
			self::CATEGORY_BASE_NONE,
		);

		if ( ! in_array( $permalinks['category_base'], $allowed_category_bases, true ) ) {
			$permalinks['category_base'] = self::CATEGORY_BASE_DEFAULT;
		}

		if ( $saved_permalinks !== $permalinks ) {
			update_option( 'jelly_catalog_permalinks', $permalinks );
		}

		$permalinks['product_rewrite_slug']  = self::uses_category_in_product_permalink( $permalinks )
			? self::PRODUCT_BASE_PRODUCTS
			: untrailingslashit( $permalinks['product_base'] );
		$permalinks['product_archive_slug']  = $permalinks['product_rewrite_slug'] ?: self::PRODUCT_BASE_PRODUCTS;
		$permalinks['category_rewrite_slug'] = self::uses_category_base_without_prefix( $permalinks )
			? ''
			: untrailingslashit( $permalinks['category_base'] );

		return $permalinks;
	}

	/**
	 * 检查产品固定链接是否使用分类 slug
	 *
	 * @param array|null $permalinks 固定链接配置
	 * @return bool
	 */
	public static function uses_category_in_product_permalink( $permalinks = null ) {
		if ( null === $permalinks ) {
			$permalinks = self::get_permalink_structure();
		}

		return isset( $permalinks['product_base'] ) && self::PRODUCT_BASE_CATEGORY === $permalinks['product_base'];
	}

	/**
	 * 检查分类固定链接是否使用无前缀 slug
	 *
	 * @param array|null $permalinks 固定链接配置
	 * @return bool
	 */
	public static function uses_category_base_without_prefix( $permalinks = null ) {
		if ( null === $permalinks ) {
			$permalinks = self::get_permalink_structure();
		}

		return isset( $permalinks['category_base'] ) && self::CATEGORY_BASE_NONE === $permalinks['category_base'];
	}

	/**
	 * 获取分类 slug 路径
	 *
	 * @param \WP_Term|int $term 分类对象或分类 ID
	 * @param bool $include_ancestors 是否拼接父级 slug
	 * @return string
	 */
	public static function get_product_category_path( $term, $include_ancestors = true ) {
		$term = $term instanceof \WP_Term ? $term : get_term( $term, 'product_cat' );

		if ( ! $term || is_wp_error( $term ) || 'product_cat' !== $term->taxonomy ) {
			return '';
		}

		if ( ! $include_ancestors ) {
			return trim( (string) $term->slug, '/' );
		}

		$slugs        = array();
		$ancestor_ids = array_reverse( get_ancestors( $term->term_id, 'product_cat', 'taxonomy' ) );

		foreach ( $ancestor_ids as $ancestor_id ) {
			$ancestor = get_term( $ancestor_id, 'product_cat' );

			if ( $ancestor && ! is_wp_error( $ancestor ) ) {
				$slugs[] = $ancestor->slug;
			}
		}

		$slugs[] = $term->slug;

		return trim( implode( '/', array_filter( $slugs ) ), '/' );
	}

	/**
	 * 获取产品固定链接使用的分类
	 *
	 * 固定链接只使用一个分类，并优先选择当前已分配分类中层级最深的分类
	 *
	 * @param \WP_Post|int $product 产品对象或产品 ID
	 * @return \WP_Term|null
	 */
	public static function get_permalink_product_category( $product ) {
		$product_id = $product instanceof \WP_Post ? (int) $product->ID : absint( $product );

		if ( ! $product_id ) {
			return null;
		}

		$terms = get_the_terms( $product_id, 'product_cat' );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return null;
		}

		usort( $terms, array( 'Jelly_Catalog\Utils', 'sort_terms_by_depth' ) );

		return $terms[0];
	}

	/**
	 * 获取产品的主要分类
	 *
	 * 优先使用 SEO 插件设置的主要分类，否则回退到层级最深的分类
	 *
	 * @param \WP_Post|int $product 产品对象或产品 ID
	 * @return \WP_Term|null
	 */
	public static function get_primary_product_category( $product ) {
		$product_id = $product instanceof \WP_Post ? (int) $product->ID : absint( $product );

		if ( ! $product_id ) {
			return null;
		}

		$terms = get_the_terms( $product_id, 'product_cat' );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return null;
		}

		foreach ( array( 'rank_math_primary_product_cat', '_yoast_wpseo_primary_product_cat' ) as $meta_key ) {
			$primary_term_id = absint( get_post_meta( $product_id, $meta_key, true ) );

			if ( ! $primary_term_id ) {
				continue;
			}

			foreach ( $terms as $term ) {
				if ( (int) $term->term_id === $primary_term_id ) {
					return $term;
				}
			}
		}

		usort( $terms, array( 'Jelly_Catalog\Utils', 'sort_terms_by_depth' ) );

		return $terms[0];
	}
}
