<?php

/**
 * Post Types
 *
 * Registers post types and taxonomies.
 *
 * @package jelly_catalog\Classes\Products
 * @version 2.5.0
 */

defined('ABSPATH') || exit;



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
				'label'                 => __('Categories', 'jelly_catalog'),
				'labels'                => array(
					'name'                  => __('Product categories', 'jelly_catalog'),
					'singular_name'         => __('Category', 'jelly_catalog'),
					'menu_name'             => _x('Categories', 'Admin menu name', 'jelly_catalog'),
					'search_items'          => __('Search categories', 'jelly_catalog'),
					'all_items'             => __('All categories', 'jelly_catalog'),
					'parent_item'           => __('Parent category', 'jelly_catalog'),
					'parent_item_colon'     => __('Parent category:', 'jelly_catalog'),
					'edit_item'             => __('Edit category', 'jelly_catalog'),
					'update_item'           => __('Update category', 'jelly_catalog'),
					'add_new_item'          => __('Add new category', 'jelly_catalog'),
					'new_item_name'         => __('New category name', 'jelly_catalog'),
					'not_found'             => __('No categories found', 'jelly_catalog'),
					'item_link'             => __('Product Category Link', 'jelly_catalog'),
					'item_link_description' => __('A link to a product category.', 'jelly_catalog'),
					'template_name'         => _x('Products by Category', 'Template name', 'jelly_catalog'),
				),
				'show_in_rest'          => true,
				'show_ui'               => true,
				'query_var'             => true,
				'capabilities'          => array(
					'manage_terms' => 'manage_product_terms',
					'edit_terms'   => 'edit_product_terms',
					'delete_terms' => 'delete_product_terms',
					'assign_terms' => 'assign_product_terms',
				),
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
				'label'                 => __('Product tags', 'jelly_catalog'),
				'labels'                => array(
					'name'                       => __('Product tags', 'jelly_catalog'),
					'singular_name'              => __('Tag', 'jelly_catalog'),
					'menu_name'                  => _x('Tags', 'Admin menu name', 'jelly_catalog'),
					'search_items'               => __('Search tags', 'jelly_catalog'),
					'all_items'                  => __('All tags', 'jelly_catalog'),
					'edit_item'                  => __('Edit tag', 'jelly_catalog'),
					'update_item'                => __('Update tag', 'jelly_catalog'),
					'add_new_item'               => __('Add new tag', 'jelly_catalog'),
					'new_item_name'              => __('New tag name', 'jelly_catalog'),
					'popular_items'              => __('Popular tags', 'jelly_catalog'),
					'separate_items_with_commas' => __('Separate tags with commas', 'jelly_catalog'),
					'add_or_remove_items'        => __('Add or remove tags', 'jelly_catalog'),
					'choose_from_most_used'      => __('Choose from the most used tags', 'jelly_catalog'),
					'not_found'                  => __('No tags found', 'jelly_catalog'),
					'item_link'                  => __('Product Tag Link', 'jelly_catalog'),
					'item_link_description'      => __('A link to a product tag.', 'jelly_catalog'),
					'template_name'              => _x('Products by Tag', 'Template name', 'jelly_catalog'),
				),
				'show_in_rest'          => true,
				'show_ui'               => true,
				'query_var'             => true,
				'capabilities'          => array(
					'manage_terms' => 'manage_product_terms',
					'edit_terms'   => 'edit_product_terms',
					'delete_terms' => 'delete_product_terms',
					'assign_terms' => 'assign_product_terms',
				),
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

		$supports   = array('title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'publicize', 'wpcom-markdown');

		$has_archive = 'products';

		register_post_type(
			'product',
				array(
					'labels'              => array(
						'name'                  => __('Products', 'jelly_catalog'),
						'singular_name'         => __('Product', 'jelly_catalog'),
						'all_items'             => __('All Products', 'jelly_catalog'),
						'menu_name'             => _x('Products', 'Admin menu name', 'jelly_catalog'),
						'add_new'               => __('Add New', 'jelly_catalog'),
						'add_new_item'          => __('Add new product', 'jelly_catalog'),
						'edit'                  => __('Edit', 'jelly_catalog'),
						'edit_item'             => __('Edit product', 'jelly_catalog'),
						'new_item'              => __('New product', 'jelly_catalog'),
						'view_item'             => __('View product', 'jelly_catalog'),
						'view_items'            => __('View products', 'jelly_catalog'),
						'search_items'          => __('Search products', 'jelly_catalog'),
						'not_found'             => __('No products found', 'jelly_catalog'),
						'not_found_in_trash'    => __('No products found in trash', 'jelly_catalog'),
						'parent'                => __('Parent product', 'jelly_catalog'),
						'featured_image'        => __('Product image', 'jelly_catalog'),
						'set_featured_image'    => __('Set product image', 'jelly_catalog'),
						'remove_featured_image' => __('Remove product image', 'jelly_catalog'),
						'use_featured_image'    => __('Use as product image', 'jelly_catalog'),
						'insert_into_item'      => __('Insert into product', 'jelly_catalog'),
						'uploaded_to_this_item' => __('Uploaded to this product', 'jelly_catalog'),
						'filter_items_list'     => __('Filter products', 'jelly_catalog'),
						'items_list_navigation' => __('Products navigation', 'jelly_catalog'),
						'items_list'            => __('Products list', 'jelly_catalog'),
						'item_link'             => __('Product Link', 'jelly_catalog'),
						'item_link_description' => __('A link to a product.', 'jelly_catalog'),
					),
					'description'         => __('This is where you can browse products in this store.', 'jelly_catalog'),
					'public'              => true,
					'show_ui'             => true,
					'menu_icon'           => 'dashicons-archive',
					'capability_type'     => 'product',
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
			1 => __('Category added.', 'jelly_catalog'),
			2 => __('Category deleted.', 'jelly_catalog'),
			3 => __('Category updated.', 'jelly_catalog'),
			4 => __('Category not added.', 'jelly_catalog'),
			5 => __('Category not updated.', 'jelly_catalog'),
			6 => __('Categories deleted.', 'jelly_catalog'),
		);

		$messages['product_tag'] = array(
			0 => '',
			1 => __('Tag added.', 'jelly_catalog'),
			2 => __('Tag deleted.', 'jelly_catalog'),
			3 => __('Tag updated.', 'jelly_catalog'),
			4 => __('Tag not added.', 'jelly_catalog'),
			5 => __('Tag not updated.', 'jelly_catalog'),
			6 => __('Tags deleted.', 'jelly_catalog'),
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
}

JC_Post_types::init();
