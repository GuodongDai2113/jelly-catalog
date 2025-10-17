<?php

/**
 * includes\class-jc-export.php
 * 
 * @see: https://jellydai.com
 * @author: Jelly Dai <daiguo1003@gmail.com>
 * @created : 2025.09.22 16:35
 */

if (! defined('ABSPATH')) exit; // 禁止直接访问

class JC_Export
{
    protected static ?JC_Export $instance = null;

    /** 基础列 */
    protected array $base_headers = [
        'ID',
        'Name',
        'Description',
        'Categories',
        'Tags',
        'Images',
    ];

    /** 导出设置默认值 */
    protected array $defaults = [
        'post_type'          => 'product',
        'post_status'        => ['publish', 'draft', 'pending', 'private'],
        'limit'              => -1,
        'category_taxonomy'  => 'product_cat',
        'tag_taxonomy'       => 'product_tag',
        'category_delimiter' => '|',
        'hierarchy_delimiter'=> '>',
        'tag_delimiter'      => ',',
        'image_delimiter'    => ',',
        'gallery_meta_key'   => '_product_image_gallery',
    ];

    private function __construct()
    {
    }

    public static function instance(): JC_Export
    {
        if (! self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 导出为 CSV
     */
    public function export_to_csv(string $file_path, array $args = []): array
    {
        $args = wp_parse_args($args, $this->defaults);

        $query = new WP_Query([
            'post_type'      => $args['post_type'],
            'post_status'    => $args['post_status'],
            'posts_per_page' => $args['limit'],
            'fields'         => 'ids',
            'orderby'        => 'ID',
            'order'          => 'ASC',
        ]);

        if (! $query->have_posts()) {
            return [
                'success' => false,
                'message' => '没有符合条件的内容。',
            ];
        }

        // 收集所有 meta 键
        $meta_keys = $this->collect_meta_keys($query->posts);

        $headers = array_merge(
            $this->base_headers,
            array_map(fn ($key) => 'Meta: ' . $key, $meta_keys)
        );

        $handle = fopen($file_path, 'w');
        if (! $handle) {
            return [
                'success' => false,
                'message' => sprintf('无法写入文件：%s', $file_path),
            ];
        }

        fputcsv($handle, $headers);

        foreach ($query->posts as $post_id) {
            $row = $this->build_row($post_id, $meta_keys, $args);
            fputcsv($handle, $row);
        }

        fclose($handle);

        return [
            'success' => true,
            'count'   => count($query->posts),
            'path'    => $file_path,
        ];
    }

    protected function collect_meta_keys(array $post_ids): array
    {
        $keys = [];

        foreach ($post_ids as $post_id) {
            $meta = get_post_meta($post_id);
            foreach ($meta as $key => $_values) {
                $keys[$key] = true;
            }
        }

        unset($keys['_edit_last'], $keys['_edit_lock']); // 排除编辑痕迹
        return array_keys($keys);
    }

    protected function build_row(int $post_id, array $meta_keys, array $args): array
    {
        $post = get_post($post_id);

        $row = [
            $post->ID,
            $post->post_title,
            $post->post_content,
            $this->format_categories($post_id, $args),
            $this->format_tags($post_id, $args),
            $this->format_images($post_id, $args),
        ];

        foreach ($meta_keys as $meta_key) {
            $value = get_post_meta($post_id, $meta_key, true);

            if (is_array($value) || is_object($value)) {
                $value = wp_json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            $row[] = $value;
        }

        return $row;
    }

    protected function format_categories(int $post_id, array $args): string
    {
        $taxonomy = apply_filters('jc_catalog_category_taxonomy', $args['category_taxonomy']);
        if (! taxonomy_exists($taxonomy)) {
            return '';
        }

        $terms = get_the_terms($post_id, $taxonomy);
        if (empty($terms) || is_wp_error($terms)) {
            return '';
        }

        $paths = [];
        foreach ($terms as $term) {
            $ancestors = array_reverse(get_ancestors($term->term_id, $taxonomy, 'taxonomy'));
            $names     = [];

            foreach ($ancestors as $ancestor_id) {
                $ancestor = get_term($ancestor_id, $taxonomy);
                if ($ancestor && ! is_wp_error($ancestor)) {
                    $names[] = $ancestor->name;
                }
            }

            $names[] = $term->name;
            $paths[] = implode(' ' . $args['hierarchy_delimiter'] . ' ', $names);
        }

        $paths = array_unique($paths);
        return implode($args['category_delimiter'], $paths);
    }

    protected function format_tags(int $post_id, array $args): string
    {
        $taxonomy = apply_filters('jc_catalog_tag_taxonomy', $args['tag_taxonomy']);
        if (! taxonomy_exists($taxonomy)) {
            return '';
        }

        $terms = get_the_terms($post_id, $taxonomy);
        if (empty($terms) || is_wp_error($terms)) {
            return '';
        }

        $names = wp_list_pluck($terms, 'name');
        return implode($args['tag_delimiter'], $names);
    }

    protected function format_images(int $post_id, array $args): string
    {
        $images = [];

        $thumbnail_id = get_post_thumbnail_id($post_id);
        if ($thumbnail_id) {
            $url = wp_get_attachment_url($thumbnail_id);
            if ($url) {
                $images[] = $url;
            }
        }

        $gallery_ids = get_post_meta($post_id, $args['gallery_meta_key'], true);
        if (is_array($gallery_ids)) {
            foreach ($gallery_ids as $attachment_id) {
                $url = wp_get_attachment_url($attachment_id);
                if ($url) {
                    $images[] = $url;
                }
            }
        }

        $images = array_unique(array_filter($images));
        return implode($args['image_delimiter'], $images);
    }
}