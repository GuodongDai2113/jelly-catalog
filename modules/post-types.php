<?php

/**
 * includes\class-jc-post-types.php
 *
 * @see: https://jellydai.com
 * @author: Jelly Dai <daiguo1003@gmail.com>
 * @created : 2025.09.08 10:22
 */

namespace Jelly_Catalog\Modules;

use Jelly_Catalog\Utils;

if (!defined('ABSPATH')) {
    exit;
} // 禁止直接访问

/**
 * Post types Class.
 */
class Post_Types
{
    /**
     * Hook in methods.
     */
    public function __construct()
    {
        add_action('init', [$this, 'register_taxonomies'], 5);
        add_action('init', [$this, 'register_post_types'], 5);
        add_action('init', [$this, 'add_dynamic_rewrite_rules'], 20);
        add_action('init', [$this, 'maybe_flush_rewrite_rules'], 99);
        add_filter('term_updated_messages', [$this, 'updated_term_messages']);
        add_filter('rest_api_allowed_post_types', [$this, 'rest_api_allowed_post_types']);
        add_filter('gutenberg_can_edit_post_type', [$this, 'gutenberg_can_edit_post_type'], 10, 2);
        add_filter('use_block_editor_for_post_type', [$this, 'gutenberg_can_edit_post_type'], 10, 2);
        add_filter('post_type_link', [$this, 'filter_product_permalink'], 10, 4);
        add_filter('term_link', [$this, 'filter_product_cat_link'], 10, 3);
        add_action('pre_get_posts', [$this, 'product_query'], 9);
        add_action('created_product_cat', [$this, 'queue_rewrite_flush']);
        add_action('edited_product_cat', [$this, 'queue_rewrite_flush']);
        add_action('delete_product_cat', [$this, 'queue_rewrite_flush']);
    }

    /**
     * Register core taxonomies.
     */
    public function register_taxonomies()
    {
        if (!is_blog_installed()) {
            return;
        }

        $permalinks = Utils::get_permalink_structure();
        $category_rewrite_slug = $permalinks['category_rewrite_slug'];

        register_taxonomy(
            'product_cat',
            ['product'],
            [
                'hierarchical' => true,
                'label' => __('Categories', 'jelly-catalog'),
                'labels' => [
                    'name' => __('Product categories', 'jelly-catalog'),
                    'singular_name' => __('Category', 'jelly-catalog'),
                    'menu_name' => _x('Categories', 'Admin menu name', 'jelly-catalog'),
                    'search_items' => __('Search categories', 'jelly-catalog'),
                    'all_items' => __('All categories', 'jelly-catalog'),
                    'parent_item' => __('Parent category', 'jelly-catalog'),
                    'parent_item_colon' => __('Parent category:', 'jelly-catalog'),
                    'edit_item' => __('Edit category', 'jelly-catalog'),
                    'update_item' => __('Update category', 'jelly-catalog'),
                    'add_new_item' => __('Add new category', 'jelly-catalog'),
                    'new_item_name' => __('New category name', 'jelly-catalog'),
                    'not_found' => __('No categories found', 'jelly-catalog'),
                    'item_link' => __('Product Category Link', 'jelly-catalog'),
                    'item_link_description' => __('A link to a product category.', 'jelly-catalog'),
                    'template_name' => _x('Products by Category', 'Template name', 'jelly-catalog'),
                ],
                'show_in_rest' => true,
                'show_ui' => true,
                'query_var' => true,
                'rewrite' => $category_rewrite_slug ? [
                    'slug' => $category_rewrite_slug,
                    'with_front' => false,
                    'hierarchical' => true,
                ] : false,
            ]
        );

        register_taxonomy(
            'product_tag',
            ['product'],
            [
                'hierarchical' => false,
                'label' => __('Product tags', 'jelly-catalog'),
                'labels' => [
                    'name' => __('Product tags', 'jelly-catalog'),
                    'singular_name' => __('Tag', 'jelly-catalog'),
                    'menu_name' => _x('Tags', 'Admin menu name', 'jelly-catalog'),
                    'search_items' => __('Search tags', 'jelly-catalog'),
                    'all_items' => __('All tags', 'jelly-catalog'),
                    'edit_item' => __('Edit tag', 'jelly-catalog'),
                    'update_item' => __('Update tag', 'jelly-catalog'),
                    'add_new_item' => __('Add new tag', 'jelly-catalog'),
                    'new_item_name' => __('New tag name', 'jelly-catalog'),
                    'popular_items' => __('Popular tags', 'jelly-catalog'),
                    'separate_items_with_commas' => __('Separate tags with commas', 'jelly-catalog'),
                    'add_or_remove_items' => __('Add or remove tags', 'jelly-catalog'),
                    'choose_from_most_used' => __('Choose from the most used tags', 'jelly-catalog'),
                    'not_found' => __('No tags found', 'jelly-catalog'),
                    'item_link' => __('Product Tag Link', 'jelly-catalog'),
                    'item_link_description' => __('A link to a product tag.', 'jelly-catalog'),
                    'template_name' => _x('Products by Tag', 'Template name', 'jelly-catalog'),
                ],
                'show_in_rest' => true,
                'show_ui' => true,
                'query_var' => true,
                'rewrite' => [
                    'slug' => 'product-tag',
                    'with_front' => false,
                ],
            ]
        );
    }

    /**
     * Register core post types.
     */
    public function register_post_types()
    {
        if (!is_blog_installed() || post_type_exists('product')) {
            return;
        }

        // $supports   = array('title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'publicize', 'wpcom-markdown');
        $supports = ['title', 'editor', 'excerpt', 'thumbnail'];

        $permalinks = Utils::get_permalink_structure();
        $has_archive = $permalinks['product_archive_slug'];

        register_post_type(
            'product',
            [
                'labels' => [
                    'name' => __('Products', 'jelly-catalog'),
                    'singular_name' => __('Product', 'jelly-catalog'),
                    'all_items' => __('All Products', 'jelly-catalog'),
                    'menu_name' => _x('Products', 'Admin menu name', 'jelly-catalog'),
                    'add_new' => __('Add New', 'jelly-catalog'),
                    'add_new_item' => __('Add new product', 'jelly-catalog'),
                    'edit' => __('Edit', 'jelly-catalog'),
                    'edit_item' => __('Edit product', 'jelly-catalog'),
                    'new_item' => __('New product', 'jelly-catalog'),
                    'view_item' => __('View product', 'jelly-catalog'),
                    'view_items' => __('View products', 'jelly-catalog'),
                    'search_items' => __('Search products', 'jelly-catalog'),
                    'not_found' => __('No products found', 'jelly-catalog'),
                    'not_found_in_trash' => __('No products found in trash', 'jelly-catalog'),
                    'parent' => __('Parent product', 'jelly-catalog'),
                    'featured_image' => __('Product image', 'jelly-catalog'),
                    'set_featured_image' => __('Set product image', 'jelly-catalog'),
                    'remove_featured_image' => __('Remove product image', 'jelly-catalog'),
                    'use_featured_image' => __('Use as product image', 'jelly-catalog'),
                    'insert_into_item' => __('Insert into product', 'jelly-catalog'),
                    'uploaded_to_this_item' => __('Uploaded to this product', 'jelly-catalog'),
                    'filter_items_list' => __('Filter products', 'jelly-catalog'),
                    'items_list_navigation' => __('Products navigation', 'jelly-catalog'),
                    'items_list' => __('Products list', 'jelly-catalog'),
                    'item_link' => __('Product Link', 'jelly-catalog'),
                    'item_link_description' => __('A link to a product.', 'jelly-catalog'),
                ],
                'description' => __('This is where you can browse products in this store.', 'jelly-catalog'),
                'public' => true,
                'show_ui' => true,
                'menu_icon' => 'dashicons-archive',
                'capability_type' => 'post',
                'map_meta_cap' => true,
                'publicly_queryable' => true,
                'exclude_from_search' => false,
                'hierarchical' => false, // Hierarchical causes memory issues - WP loads all records!
                'rewrite' => $permalinks['product_rewrite_slug'] ? [
                    'slug' => $permalinks['product_rewrite_slug'],
                    'with_front' => false,
                    'feeds' => true,
                ] : false,
                'query_var' => true,
                'supports' => $supports,
                'has_archive' => $has_archive,
                'show_in_nav_menus' => true,
                'show_in_rest' => true,
            ]
        );
    }

    /**
     * 添加动态固定链接重写规则。
     */
    public function add_dynamic_rewrite_rules()
    {
        $permalinks = Utils::get_permalink_structure();
        $terms = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ]);

        if (empty($terms) || is_wp_error($terms)) {
            return;
        }

        usort($terms, function ($left, $right) {
            $left_path = Utils::get_product_category_path($left);
            $right_path = Utils::get_product_category_path($right);

            return strlen($right_path) <=> strlen($left_path);
        });

        if (Utils::uses_category_base_without_prefix($permalinks)) {
            foreach ($terms as $term) {
                $term_path = Utils::get_product_category_path($term);

                if (!$term_path) {
                    continue;
                }

                $term_regex = '^' . preg_quote($term_path, '/') . '/?$';
                $paged_regex = '^' . preg_quote($term_path, '/') . '/page/([0-9]{1,})/?$';

                add_rewrite_rule($paged_regex, 'index.php?taxonomy=product_cat&term=' . $term->slug . '&paged=$matches[1]', 'top');
                add_rewrite_rule($term_regex, 'index.php?taxonomy=product_cat&term=' . $term->slug, 'top');
            }
        }

        if (!Utils::uses_category_in_product_permalink($permalinks)) {
            return;
        }

        foreach ($terms as $term) {
            $term_path = Utils::get_product_category_path($term);

            if (!$term_path) {
                continue;
            }

            $product_regex = '^' . preg_quote($term_path, '/') . '/([^/]+)/?$';
            add_rewrite_rule($product_regex, 'index.php?post_type=product&name=$matches[1]', 'top');
        }
    }

    /**
     * 过滤产品固定链接。
     *
     * @param string   $post_link 产品链接。
     * @param \WP_Post $post      文章对象。
     * @param bool     $leavename 是否保留 post name 占位符。
     * @param bool     $sample    是否为 sample permalink。
     * @return string
     */
    public function filter_product_permalink($post_link, $post, $leavename, $sample)
    {
        if ('product' !== $post->post_type) {
            return $post_link;
        }

        $permalinks = Utils::get_permalink_structure();

        if (!Utils::uses_category_in_product_permalink($permalinks)) {
            return $post_link;
        }

        $post_name = $leavename ? '%postname%' : $post->post_name;
        if (!$post_name) {
            $post_name = $sample ? sanitize_title($post->post_title) : $post->post_name;
        }

        $primary_term = Utils::get_primary_product_category($post);
        $base_path = $primary_term ? Utils::get_product_category_path($primary_term) : $permalinks['product_archive_slug'];
        $path = trim($base_path . '/' . $post_name, '/');

        return home_url(user_trailingslashit($path, 'single'));
    }

    /**
     * 过滤产品分类固定链接。
     *
     * @param string          $termlink 分类链接。
     * @param \WP_Term|string $term     分类对象。
     * @param string          $taxonomy 分类法名称。
     * @return string
     */
    public function filter_product_cat_link($termlink, $term, $taxonomy)
    {
        if ('product_cat' !== $taxonomy) {
            return $termlink;
        }

        $permalinks = Utils::get_permalink_structure();

        if (!Utils::uses_category_base_without_prefix($permalinks)) {
            return $termlink;
        }

        $term_path = Utils::get_product_category_path($term);

        if (!$term_path) {
            return $termlink;
        }

        return home_url(user_trailingslashit($term_path, 'category'));
    }

    /**
     * 标记需要刷新 rewrite 规则。
     */
    public function queue_rewrite_flush(...$args)
    {
        update_option('jelly_catalog_queue_flush_rewrite', 1);
    }

    /**
     * 按需刷新 rewrite 规则。
     */
    public function maybe_flush_rewrite_rules()
    {
        if (!get_option('jelly_catalog_queue_flush_rewrite')) {
            return;
        }

        flush_rewrite_rules(false);
        delete_option('jelly_catalog_queue_flush_rewrite');
    }

    /**
     * Customize taxonomies update messages.
     *
     * @param array $messages The list of available messages.
     * @since 4.4.0
     * @return bool
     */
    public function updated_term_messages($messages)
    {
        $messages['product_cat'] = [
            0 => '',
            1 => __('Category added.', 'jelly-catalog'),
            2 => __('Category deleted.', 'jelly-catalog'),
            3 => __('Category updated.', 'jelly-catalog'),
            4 => __('Category not added.', 'jelly-catalog'),
            5 => __('Category not updated.', 'jelly-catalog'),
            6 => __('Categories deleted.', 'jelly-catalog'),
        ];

        $messages['product_tag'] = [
            0 => '',
            1 => __('Tag added.', 'jelly-catalog'),
            2 => __('Tag deleted.', 'jelly-catalog'),
            3 => __('Tag updated.', 'jelly-catalog'),
            4 => __('Tag not added.', 'jelly-catalog'),
            5 => __('Tag not updated.', 'jelly-catalog'),
            6 => __('Tags deleted.', 'jelly-catalog'),
        ];

        return $messages;
    }

    /**
     * Disable Gutenberg for products.
     *
     * @param bool   $can_edit Whether the post type can be edited or not.
     * @param string $post_type The post type being checked.
     * @return bool
     */
    public function gutenberg_can_edit_post_type($can_edit, $post_type)
    {
        return 'product' === $post_type ? false : $can_edit;
    }

    /**
     * Added product for Jetpack related posts.
     *
     * @param  array $post_types Post types.
     * @return array
     */
    public function rest_api_allowed_post_types($post_types)
    {
        $post_types[] = 'product';

        return $post_types;
    }

    /**
     * 修改产品归档页面的查询参数
     *
     * @param \WP_Query $query 查询对象
     */
    public function product_query($query)
    {
        // 只在产品归档页面且是主查询时修改
        if (!is_admin() && $query->is_main_query()) {
            if (is_jc_product_archive()) {
                $product_per_page = max(1, absint(get_option('products_per_page', 16)));
                $query->set('posts_per_page', $product_per_page);

                // 增加查询顺序设定
                $orderby = get_option('products_orderby', 'date');
                $allowed_orderby = ['date', 'title', 'menu_order', 'rand', 'modified'];
                if (!in_array($orderby, $allowed_orderby, true)) {
                    $orderby = 'date';
                }

                $order = strtoupper(get_option('products_order', 'DESC'));
                if (!in_array($order, ['ASC', 'DESC'], true)) {
                    $order = 'DESC';
                }

                $query->set('orderby', $orderby);
                $query->set('order', $order);
            }
        }
    }
}
