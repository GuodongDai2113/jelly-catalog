<?php

/**
 * includes\class-jc-post-meta-box.php
 * 
 * @see: https://jellydai.com
 * @author: Jelly Dai <d@jellydai.com>
 * @created: 2025.07.29 14:47
 */


if (! defined('ABSPATH')) exit; // 禁止直接访问

/**
 * 请填写描述
 */
class JC_Admin
{
    public function __construct()
    {

        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        if (!is_woocommerce_activated()) {
            $this->init_catalog_modules();
        }
    }

    public function init_catalog_modules()
    {
        add_filter('manage_edit-product_columns', array($this, 'columns'));
        add_filter('manage_edit-product_columns', array($this, 'define_columns'));
        add_action('manage_product_posts_custom_column', array($this, 'column'), 10, 2);

        add_filter('manage_edit-product_sortable_columns', array($this, 'sortable_columns'));
        add_action('pre_get_posts', array($this, 'product_orderby'));
        add_action('restrict_manage_posts', array($this, 'product_filters'));

        add_filter('manage_edit-product_cat_columns', array($this, 'product_cat_columns'));
        add_filter('manage_product_cat_custom_column', array($this, 'product_cat_column'), 10, 3);
    }

    public function enqueue_admin_assets()
    {
        wp_enqueue_style(
            'jelly-catalog-admin',
            JELLY_CATALOG_PLUGIN_URL . 'assets/css/jelly-catalog.css',
            array(),
            JELLY_CATALOG_VERSION
        );

        wp_enqueue_script(
            'jelly-catalog-admin',
            JELLY_CATALOG_PLUGIN_URL . 'assets/js/jelly-catalog.js',
            array('jquery', 'jquery-ui-sortable'),
            JELLY_CATALOG_VERSION,
            true
        );

        wp_enqueue_media();

        wp_enqueue_script(
            'jelly-catalog-editor',
            JELLY_CATALOG_PLUGIN_URL . 'assets/js/jelly-catalog-editor.js',
            array('jquery'),
            JELLY_CATALOG_VERSION,
            true
        );

        // if (!wp_script_is('jelly-ajax', 'registered')) {
        wp_localize_script('jelly-catalog-editor', 'jc_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('jc_nonce')
        ));
        // }
    }


    /**
     * Columns function.
     *
     * @param mixed $columns Columns.
     */
    public function columns($columns)
    {
        if (empty($columns)) {
            return $columns;
        }

        $new_columns          = array();
        $new_columns['cb']    = $columns['cb'];
        $new_columns['jc-thumb'] = __('Image', 'jelly-catalog');
        unset($columns['cb']);
        $columns = array_merge($new_columns, $columns);

        return $columns;
    }

    public function define_columns($columns)
    {
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            // 在标题列后插入新的列
            if ('title' === $key) {
                $new_columns['product_cat'] = __('Categories', 'jelly-catalog');
                $new_columns['product_tag'] = __('Tags', 'jelly-catalog');
                $new_columns['product_completeness'] = __('Completeness', 'jelly-catalog');
            }
        }
        return array_merge($new_columns, $columns);
    }

    /**
     * Column function.
     *
     * @param string $column Column name.
     * @param int    $id     Post ID.
     */
    public function column($column, $id)
    {
        if ('jc-thumb' === $column) {
            $image        = '';
            $thumbnail_id = get_post_meta($id, '_thumbnail_id', true);

            if ($thumbnail_id) {
                $image = wp_get_attachment_url($thumbnail_id);
                $images = get_post_meta($id, '_product_image_gallery', true);
                if (!empty($images)) {
                    $gallery = explode(',', $images);
                    $gallery_count = count($gallery);
                    echo sprintf('<div class="jc-gallery-count">%d</div>', $gallery_count + 1);
                }
            }
            if (empty($image)) {
                $image = jc_placeholder_img_src();
            }

            echo '<img src="' . $image . '" alt="Thumbnail" class="wp-post-image" height="48" width="48" />';
        }

        // 显示产品分类
        if ('product_cat' === $column) {
            $terms = get_the_terms($id, 'product_cat');
            if (!empty($terms)) {
                $out = array();
                foreach ($terms as $term) {
                    // 获取分类层级
                    $level = 0;
                    if (isset($term->parent)) {
                        $ancestors = get_ancestors($term->term_id, 'product_cat');
                        $level = count($ancestors);
                    }

                    // 确保层级在合理范围内
                    $level_class = 'level-' . max(1, min(3, $level + 1));

                    $out[] = sprintf(
                        '<a href="%s" class="%s">%s</a>',
                        esc_url(add_query_arg(array('post_type' => 'product', 'product_cat' => $term->slug), 'edit.php')),
                        $level_class,
                        esc_html(sanitize_term_field('name', $term->name, $term->term_id, 'product_cat', 'display'))
                    );
                }
                echo join(', ', $out);
            } else {
                echo '<span aria-hidden="true">—</span>';
            }
        }

        // 显示产品标签
        if ('product_tag' === $column) {
            $terms = get_the_terms($id, 'product_tag');
            if (!empty($terms)) {
                $out = array();
                foreach ($terms as $term) {
                    $out[] = sprintf(
                        '<a href="%s">%s</a>',
                        esc_url(add_query_arg(array('post_type' => 'product', 'product_tag' => $term->slug), 'edit.php')),
                        esc_html(sanitize_term_field('name', $term->name, $term->term_id, 'product_tag', 'display'))
                    );
                }
                echo join(', ', $out);
            } else {
                echo '<span aria-hidden="true">—</span>';
            }
        }


        // 显示产品完整度
        if ('product_completeness' === $column) {
            $post = get_post($id);
            $excerpt = $post->post_excerpt;
            $content = $post->post_content;

            $excerpt_length = strlen(trim(strip_tags($excerpt)));
            $content_length = strlen(trim(strip_tags($content)));

            $excerpt_complete = $excerpt_length >= 60;
            $content_complete = $content_length >= 100;

            echo '<p class="jc-completeness">';
            echo $excerpt_complete ? '<span class="dashicons dashicons-yes-alt" style="color:#52c41a;"></span>' : '<span class="dashicons dashicons-dismiss" style="color:#ff4d4f;"></span>';
            echo __('Description','jelly-catalog') . '</p>';

            echo '<p class="jc-completeness">';
            echo $content_complete ? '<span class="dashicons dashicons-yes-alt" style="color:#52c41a;"></span>' : '<span class="dashicons dashicons-dismiss" style="color:#ff4d4f;"></span>';
            echo __('Content','jelly-catalog') . '</p>';
        }
    }

    /**
     * Thumbnail column value added to category admin.
     *
     * @param string $columns Column HTML output.
     * @param string $column Column name.
     * @param int    $id Product ID.
     *
     * @return string
     */
    public function product_cat_column($columns, $column, $id)
    {
        if ('jc-thumb' === $column) {

            $thumbnail_id = get_term_meta($id, 'thumbnail_id', true);

            if ($thumbnail_id) {
                $image = wp_get_attachment_thumb_url($thumbnail_id);
            } else {
                $image = jc_placeholder_img_src();
            }

            // Prevent esc_url from breaking spaces in urls for image embeds. Ref: https://core.trac.wordpress.org/ticket/23605 .
            $image    = str_replace(' ', '%20', $image);
            $columns .= '<img src="' . esc_url($image) . '" alt="' . esc_attr__('Thumbnail', 'jelly-catalog') . '" class="wp-post-image" height="48" width="48" />';
        }
        if ('handle' === $column) {
            $columns .= '<input type="hidden" name="term_id" value="' . esc_attr($id) . '" />';
        }

        if ('description' === $column) {
            $term = get_term($id, 'product_cat');
            $columns .= esc_html($term->description);
        }
        return $columns;
    }


    /**
     * Thumbnail column added to category admin.
     *
     * @param mixed $columns Columns array.
     * @return array
     */
    public function product_cat_columns($columns)
    {
        $new_columns = array();

        if (isset($columns['cb'])) {
            $new_columns['cb'] = $columns['cb'];
            unset($columns['cb']);
        }
        $new_columns['jc-thumb'] = __('Image', 'jelly-catalog');
        // $new_columns['name'] = __('Name', 'jelly-catalog');
        // $new_columns['description'] = __('Description', 'jelly-catalog');


        $columns           = array_merge($new_columns, $columns);
        $columns['handle'] = '';

        return $columns;
    }

    /**
     * 使自定义列可排序
     *
     * @param array $columns 可排序列
     * @return array
     */
    public function sortable_columns($columns)
    {
        $columns['product_cat'] = 'product_cat';
        $columns['product_tag'] = 'product_tag';
        return $columns;
    }
    /**
     * 处理排序逻辑
     *
     * @param WP_Query $query 查询对象
     */
    public function product_orderby($query)
    {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        if ('product_cat' === $query->get('orderby')) {
            $query->set('orderby', 'taxonomy');
            $query->set('taxonomy', 'product_cat');
        }

        if ('product_tag' === $query->get('orderby')) {
            $query->set('orderby', 'taxonomy');
            $query->set('taxonomy', 'product_tag');
        }
    }
    /**
     * 添加筛选器
     */
    public function product_filters()
    {
        global $typenow;

        if ('product' === $typenow) {
            // 产品分类筛选器
            $current_cat = isset($_GET['product_cat']) ? $_GET['product_cat'] : '';
            $cat_terms = get_terms(array(
                'taxonomy' => 'product_cat',
                'hide_empty' => false,
                'hierarchical' => true,
                'orderby' => 'name',
            ));

            if (!empty($cat_terms) && !is_wp_error($cat_terms)) {
                echo '<select name="product_cat">';
                echo '<option value="">' . __('All categories', 'jelly-catalog') . '</option>';

                // 构建分类树
                // $walker = new Walker_CategoryDropdown();
                echo walk_category_dropdown_tree($cat_terms, 0, array(
                    'selected' => $current_cat,
                    'value_field' => 'slug',
                    'show_count' => true,
                ));

                echo '</select>';
            }

            // 产品标签筛选器
            $current_tag = isset($_GET['product_tag']) ? $_GET['product_tag'] : '';
            $tag_terms = get_terms(array(
                'taxonomy' => 'product_tag',
                'hide_empty' => false,
            ));

            if (!empty($tag_terms)) {
                echo '<select name="product_tag">';
                echo '<option value="">' . __('All tags', 'jelly-catalog') . '</option>';
                foreach ($tag_terms as $term) {
                    printf(
                        '<option value="%s"%s>%s</option>',
                        $term->slug,
                        selected($current_tag, $term->slug, false),
                        $term->name
                    );
                }
                echo '</select>';
            }
        }
    }

}
