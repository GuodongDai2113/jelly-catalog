<?php

/**
 * includes/class-jc-template-loader.php
 *
 * @see https://jellydai.com
 * @author Jelly
 * @created 2025.11.04
 */

if (! defined('ABSPATH')) exit; // 禁止直接访问

class JC_Template_Loader
{
    private static $products_page_id = 0;

    public static function init()
    {
        self::$products_page_id = absint(get_option('page_for_products', 0));

        if (self::$products_page_id > 0) {
            add_action('template_redirect', [__CLASS__, 'inject_products_page_context'], 9);
        }
    }

    /**
     * 在产品归档/分类页中注入 page_for_products 页面内容
     * 模拟成一个虚拟 post，防止分页 404
     */
    public static function inject_products_page_context()
    {
        if (! (is_post_type_archive('product') )) {
            return;
        }
    
        global $wp_query, $post;
    
        $page = get_post(self::$products_page_id);
        if (! $page || $page->post_status !== 'publish') {
            return;
        }
    
        $queried_object = get_queried_object();
        $prefix = '';
    
        if (! empty($queried_object->description) && (! isset($_GET['product-page']) || absint($_GET['product-page']) === 1)) {
            $prefix = '<div class="term-description">' . wpautop(wp_kses_post($queried_object->description)) . '</div>';
        }
    
        $page_content = apply_filters('the_content', $page->post_content);
        $loop_shortcode = '[products paginate="true"]';
        $composed_content = $prefix . $page_content . do_shortcode($loop_shortcode);
    
        $dummy_post = [
            'ID'                    => 0,
            'post_status'           => 'publish',
            'post_author'           => $page->post_author,
            'post_parent'           => 0,
            'post_type'             => 'page',
            'post_date'             => $page->post_date,
            'post_date_gmt'         => $page->post_date_gmt,
            'post_modified'         => $page->post_modified,
            'post_modified_gmt'     => $page->post_modified_gmt,
            'post_content'          => $composed_content,
            'post_title'            => $page->post_title,
            'post_excerpt'          => '',
            'post_content_filtered' => '',
            'post_mime_type'        => '',
            'post_password'         => '',
            'post_name'             => $page->post_name,
            'guid'                  => '',
            'menu_order'            => 0,
            'pinged'                => '',
            'to_ping'               => '',
            'ping_status'           => '',
            'comment_status'        => 'closed',
            'comment_count'         => 0,
            'filter'                => 'raw',
        ];
    
        $post = new WP_Post((object)$dummy_post);
    
        // ✅ 替换主查询（关键修复）
        $wp_query->post               = $post;
        $wp_query->posts              = [$post];
        $wp_query->post_count         = 1;
        $wp_query->is_404             = false;
        $wp_query->is_page            = true;
        $wp_query->is_single          = true;
        $wp_query->is_archive         = false;
        $wp_query->is_tax             = is_tax(['product_cat', 'product_tag']);
        $wp_query->max_num_pages      = 1;
        $wp_query->queried_object     = $post;     // ✅ 核心修复
        $wp_query->queried_object_id  = $post->ID; // ✅ 核心修复
    
        setup_postdata($post);
        remove_all_filters('the_excerpt');
    
        add_filter('template_include', [__CLASS__, 'force_page_template']);
    }
    

    /**
     * 强制加载页面模板 (Elementor / theme page.php)
     */
    public static function force_page_template($template)
    {
		$possible_templates = array(
			'page',
			'single',
			'singular',
			'index',
		);

		foreach ( $possible_templates as $possible_template ) {
			$path = get_query_template( $possible_template );
			if ( $path ) {
				return $path;
			}
		}

		return $template;
    }
}

JC_Template_Loader::init();
