<?php

/**
 * includes\class-jc-post-types.php
 * 
 * @see: https://jellydai.com
 * @author: Jelly Dai <d@jellydai.com>
 * @created : 2025.09.08 10:22
 */

if (! defined('ABSPATH')) exit; // 禁止直接访问

/**
 * Post types Class.
 */
class JC_Post_Types
{

	/**
	 * Hook in methods.
	 */
	public static function init()
	{
		add_action('init', array(__CLASS__, 'register_taxonomies'), 5);
		add_action('init', array(__CLASS__, 'register_post_types'), 5);
		add_filter('term_updated_messages', array(__CLASS__, 'updated_term_messages'));
		add_filter('rest_api_allowed_post_types', array(__CLASS__, 'rest_api_allowed_post_types'));
		add_filter('gutenberg_can_edit_post_type', array(__CLASS__, 'gutenberg_can_edit_post_type'), 10, 2);
		add_filter('use_block_editor_for_post_type', array(__CLASS__, 'gutenberg_can_edit_post_type'), 10, 2);
	}

	/**
	 * Register core taxonomies.
	 */
	public static function register_taxonomies()
	{

		if (! is_blog_installed()) {
			return;
		}

		register_taxonomy(
			'product_cat',
			array('product'),
			array(
				'hierarchical'          => true,
				'label'                 => __('Categories', 'jelly-catalog'),
				'labels'                => array(
					'name'                  => __('Product categories', 'jelly-catalog'),
					'singular_name'         => __('Category', 'jelly-catalog'),
					'menu_name'             => _x('Categories', 'Admin menu name', 'jelly-catalog'),
					'search_items'          => __('Search categories', 'jelly-catalog'),
					'all_items'             => __('All categories', 'jelly-catalog'),
					'parent_item'           => __('Parent category', 'jelly-catalog'),
					'parent_item_colon'     => __('Parent category:', 'jelly-catalog'),
					'edit_item'             => __('Edit category', 'jelly-catalog'),
					'update_item'           => __('Update category', 'jelly-catalog'),
					'add_new_item'          => __('Add new category', 'jelly-catalog'),
					'new_item_name'         => __('New category name', 'jelly-catalog'),
					'not_found'             => __('No categories found', 'jelly-catalog'),
					'item_link'             => __('Product Category Link', 'jelly-catalog'),
					'item_link_description' => __('A link to a product category.', 'jelly-catalog'),
					'template_name'         => _x('Products by Category', 'Template name', 'jelly-catalog'),
				),
				'show_in_rest'          => true,
				'show_ui'               => true,
				'query_var'             => true,
				'rewrite'               => array(
					'slug'         => 'product-category',
					'with_front'   => false,
					'hierarchical' => true,
				),
			)

		);

		register_taxonomy(
			'product_tag',
			array('product'),
			array(
				'hierarchical'          => false,
				'label'                 => __('Product tags', 'jelly-catalog'),
				'labels'                => array(
					'name'                       => __('Product tags', 'jelly-catalog'),
					'singular_name'              => __('Tag', 'jelly-catalog'),
					'menu_name'                  => _x('Tags', 'Admin menu name', 'jelly-catalog'),
					'search_items'               => __('Search tags', 'jelly-catalog'),
					'all_items'                  => __('All tags', 'jelly-catalog'),
					'edit_item'                  => __('Edit tag', 'jelly-catalog'),
					'update_item'                => __('Update tag', 'jelly-catalog'),
					'add_new_item'               => __('Add new tag', 'jelly-catalog'),
					'new_item_name'              => __('New tag name', 'jelly-catalog'),
					'popular_items'              => __('Popular tags', 'jelly-catalog'),
					'separate_items_with_commas' => __('Separate tags with commas', 'jelly-catalog'),
					'add_or_remove_items'        => __('Add or remove tags', 'jelly-catalog'),
					'choose_from_most_used'      => __('Choose from the most used tags', 'jelly-catalog'),
					'not_found'                  => __('No tags found', 'jelly-catalog'),
					'item_link'                  => __('Product Tag Link', 'jelly-catalog'),
					'item_link_description'      => __('A link to a product tag.', 'jelly-catalog'),
					'template_name'              => _x('Products by Tag', 'Template name', 'jelly-catalog'),
				),
				'show_in_rest'          => true,
				'show_ui'               => true,
				'query_var'             => true,
				'rewrite'               => array(
					'slug'       => 'product-tag',
					'with_front' => false,
				),
			)

		);
	}

	/**
	 * Register core post types.
	 */
	public static function register_post_types()
	{
		if (! is_blog_installed() || post_type_exists('product')) {
			return;
		}

		// $supports   = array('title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'publicize', 'wpcom-markdown');
		$supports   = array('title', 'editor', 'excerpt', 'thumbnail');

		$has_archive = 'products';

		register_post_type(
			'product',
			array(
				'labels'              => array(
					'name'                  => __('Products', 'jelly-catalog'),
					'singular_name'         => __('Product', 'jelly-catalog'),
					'all_items'             => __('All Products', 'jelly-catalog'),
					'menu_name'             => _x('Products', 'Admin menu name', 'jelly-catalog'),
					'add_new'               => __('Add New', 'jelly-catalog'),
					'add_new_item'          => __('Add new product', 'jelly-catalog'),
					'edit'                  => __('Edit', 'jelly-catalog'),
					'edit_item'             => __('Edit product', 'jelly-catalog'),
					'new_item'              => __('New product', 'jelly-catalog'),
					'view_item'             => __('View product', 'jelly-catalog'),
					'view_items'            => __('View products', 'jelly-catalog'),
					'search_items'          => __('Search products', 'jelly-catalog'),
					'not_found'             => __('No products found', 'jelly-catalog'),
					'not_found_in_trash'    => __('No products found in trash', 'jelly-catalog'),
					'parent'                => __('Parent product', 'jelly-catalog'),
					'featured_image'        => __('Product image', 'jelly-catalog'),
					'set_featured_image'    => __('Set product image', 'jelly-catalog'),
					'remove_featured_image' => __('Remove product image', 'jelly-catalog'),
					'use_featured_image'    => __('Use as product image', 'jelly-catalog'),
					'insert_into_item'      => __('Insert into product', 'jelly-catalog'),
					'uploaded_to_this_item' => __('Uploaded to this product', 'jelly-catalog'),
					'filter_items_list'     => __('Filter products', 'jelly-catalog'),
					'items_list_navigation' => __('Products navigation', 'jelly-catalog'),
					'items_list'            => __('Products list', 'jelly-catalog'),
					'item_link'             => __('Product Link', 'jelly-catalog'),
					'item_link_description' => __('A link to a product.', 'jelly-catalog'),
				),
				'description'         => __('This is where you can browse products in this store.', 'jelly-catalog'),
				'public'              => true,
				'show_ui'             => true,
				'menu_icon'           => 'dashicons-archive',
				'map_meta_cap'        => true,
				'publicly_queryable'  => true,
				'exclude_from_search' => false,
				'hierarchical'        => false, // Hierarchical causes memory issues - WP loads all records!
				'rewrite'             => 'products' ? array(
					'slug'       => 'products',
					'with_front' => false,
					'feeds'      => true,
				) : false,
				'query_var'           => true,
				'supports'            => $supports,
				'has_archive'         => $has_archive,
				'show_in_nav_menus'   => true,
				'show_in_rest'        => true,
			)

		);

		do_action( 'jc_after_register_post_type' );
	}

	/**
	 * Customize taxonomies update messages.
	 *
	 * @param array $messages The list of available messages.
	 * @since 4.4.0
	 * @return bool
	 */
	public static function updated_term_messages($messages)
	{
		$messages['product_cat'] = array(
			0 => '',
			1 => __('Category added.', 'jelly-catalog'),
			2 => __('Category deleted.', 'jelly-catalog'),
			3 => __('Category updated.', 'jelly-catalog'),
			4 => __('Category not added.', 'jelly-catalog'),
			5 => __('Category not updated.', 'jelly-catalog'),
			6 => __('Categories deleted.', 'jelly-catalog'),
		);

		$messages['product_tag'] = array(
			0 => '',
			1 => __('Tag added.', 'jelly-catalog'),
			2 => __('Tag deleted.', 'jelly-catalog'),
			3 => __('Tag updated.', 'jelly-catalog'),
			4 => __('Tag not added.', 'jelly-catalog'),
			5 => __('Tag not updated.', 'jelly-catalog'),
			6 => __('Tags deleted.', 'jelly-catalog'),
		);

		return $messages;
	}

	/**
	 * Disable Gutenberg for products.
	 *
	 * @param bool   $can_edit Whether the post type can be edited or not.
	 * @param string $post_type The post type being checked.
	 * @return bool
	 */
	public static function gutenberg_can_edit_post_type($can_edit, $post_type)
	{
		return 'product' === $post_type ? false : $can_edit;
	}

	/**
	 * Added product for Jetpack related posts.
	 *
	 * @param  array $post_types Post types.
	 * @return array
	 */
	public static function rest_api_allowed_post_types($post_types)
	{
		$post_types[] = 'product';

		return $post_types;
	}

	/**
	 * Flush rules if the event is queued.
	 *
	 * @since 3.3.0
	 */
	public static function maybe_flush_rewrite_rules() {
		if ( 'yes' === get_option( 'jc_queue_flush_rewrite_rules' ) ) {
			update_option( 'jc_queue_flush_rewrite_rules', 'no' );
			self::flush_rewrite_rules();
		}
	}

	/**
	 * Flush rewrite rules.
	 */
	public static function flush_rewrite_rules() {
		flush_rewrite_rules();
	}
}

JC_Post_Types::init();
