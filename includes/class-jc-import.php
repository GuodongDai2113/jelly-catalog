<?php

/**
 * includes\class-jc-import.php
 * 
 * @see: https://jellydai.com
 * @author: Jelly Dai <daiguo1003@gmail.com>
 * @created : 2025.09.22 16:35
 */

if (! defined('ABSPATH')) exit; // 禁止直接访问

class JC_Import
{
    protected static ?JC_Import $instance = null;

    /** 基础列映射 */
    protected array $base_fields = [
        'ID'          => 'id',
        'Name'        => 'name',
        'Description' => 'description',
        'Categories'  => 'categories',
        'Tags'        => 'tags',
        'Images'      => 'images',
    ];

    /** 导入选项默认值 */
    protected array $defaults = [
        'delimiter'                    => ',',
        'enclosure'                    => '"',
        'escape'                       => '\\',
        'update_existing'              => true,
        'post_type'                    => 'product',
        'post_status'                  => 'publish',
        'category_taxonomy'            => 'product_cat',
        'tag_taxonomy'                 => 'product_tag',
        'category_delimiter'           => '|',
        'category_hierarchy_delimiter' => '>',
        'tag_delimiter'                => ',',
        'image_delimiter'              => ',',
        'gallery_meta_key'             => '_product_image_gallery',
    ];

    private function __construct() {}

    public static function instance(): JC_Import
    {
        if (! self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 从 CSV 导入
     */
    public function import_from_csv(string $file_path, array $args = []): array
    {
        $args   = wp_parse_args($args, $this->defaults);
        $result = [
            'imported' => 0,
            'updated'  => 0,
            'skipped'  => 0,
            'errors'   => [],
        ];

        if (! file_exists($file_path)) {
            return $this->add_error($result, sprintf('CSV 文件不存在：%s', $file_path));
        }

        $csv = new SplFileObject($file_path, 'r');
        $csv->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
        $csv->setCsvControl($args['delimiter'], $args['enclosure'], $args['escape']);

        $headers = [];
        foreach ($csv as $row_index => $row) {
            if ($row_index === 0) {
                $headers = $this->normalize_headers($row);
                continue;
            }

            if ($row === [null] || empty(array_filter($row, 'strlen'))) {
                continue;
            }

            $data = $this->combine_row($headers, $row);
            if (empty($data['name'])) {
                $result['skipped']++;
                continue;
            }

            try {
                $updated = $this->persist_post($data, $args);
                $result[$updated ? 'updated' : 'imported']++;
            } catch (Throwable $e) {
                $result['errors'][] = sprintf('第 %d 行失败：%s', $row_index + 1, $e->getMessage());
            }
        }

        return $result;
    }

    protected function normalize_headers(array $headers): array
    {
        $map = [];
        foreach ($headers as $index => $raw) {
            $key = trim((string) $raw);
            if (isset($this->base_fields[$key])) {
                $map[$index] = $this->base_fields[$key];
            } elseif (str_starts_with($key, 'Meta:')) {
                $meta_key = trim(substr($key, strlen('Meta:')));
                $map[$index] = $this->sanitize_meta_key($meta_key);
            } else {
                // 未知列同样当作 meta key
                $map[$index] = $this->sanitize_meta_key($key);
            }
        }
        return $map;
    }

    protected function sanitize_meta_key(string $key): string
    {
        $key = trim($key);
        return $key === '' ? 'meta_' . wp_generate_password(8, false) : $key;
    }

    protected function combine_row(array $headers, array $row): array
    {
        $assoc = [];
        foreach ($headers as $index => $field) {
            $assoc[$field] = isset($row[$index]) ? trim((string) $row[$index]) : '';
        }
        return $assoc;
    }

    protected function persist_post(array $data, array $args): bool
    {
        $post_id  = 0;
        $is_update = false;

        if (! empty($data['id']) && $args['update_existing']) {
            $candidate = get_post((int) $data['id']);
            if ($candidate && $candidate->post_type === $args['post_type']) {
                $post_id  = $candidate->ID;
                $is_update = true;
            }
        }

        $post_data = [
            'post_type'    => $args['post_type'],
            'post_status'  => $args['post_status'],
            'post_title'   => wp_strip_all_tags($data['name']),
            'post_content' => $data['description'],
        ];

        if ($is_update) {
            $post_data['ID'] = $post_id;
            $post_id = wp_update_post($post_data, true);
        } else {
            $post_id = wp_insert_post($post_data, true);
        }

        if (is_wp_error($post_id)) {
            throw new RuntimeException($post_id->get_error_message());
        }

        // 分类与标签
        $this->sync_categories($post_id, $data['categories'] ?? '', $args);
        $this->sync_tags($post_id, $data['tags'] ?? '', $args);

        // 图片
        $this->sync_images($post_id, $data['images'] ?? '', $args);

        // Meta（除基础字段外余下字段）
        $base_keys = array_values($this->base_fields);
        foreach ($data as $key => $value) {
            if (in_array($key, $base_keys, true)) {
                continue;
            }
            if ($value === '') {
                delete_post_meta($post_id, $key);
            } else {
                update_post_meta($post_id, $key, $value);
            }
        }

        return $is_update;
    }

    protected function sync_categories(int $post_id, string $raw, array $args): void
    {
        $taxonomy = apply_filters('jc_catalog_category_taxonomy', $args['category_taxonomy']);
        if (! taxonomy_exists($taxonomy) || $raw === '') {
            return;
        }

        $paths = array_filter(array_map('trim', explode($args['category_delimiter'], $raw)));
        if (empty($paths)) {
            wp_set_object_terms($post_id, [], $taxonomy);
            return;
        }

        wp_defer_term_counting(true);
        $term_ids = [];

        foreach ($paths as $path) {
            $segments = array_filter(array_map('trim', explode($args['category_hierarchy_delimiter'], $path)));
            if (empty($segments)) {
                continue;
            }

            $parent  = 0;
            $term_id = 0;
            foreach ($segments as $segment) {
                $existing = term_exists($segment, $taxonomy, $parent);
                if (! $existing) {
                    $created = wp_insert_term($segment, $taxonomy, ['parent' => $parent]);
                    if (is_wp_error($created)) {
                        continue 2;
                    }
                    $term_id = (int) $created['term_id'];
                } else {
                    $term_id = (int) $existing['term_id'];
                }
                $parent = $term_id;
            }

            if ($term_id) {
                $term_ids[] = $term_id;
            }
        }

        if (! empty($term_ids)) {
            wp_set_object_terms($post_id, $term_ids, $taxonomy, false);
        }

        wp_defer_term_counting(false);
    }

    protected function sync_tags(int $post_id, string $raw, array $args): void
    {
        $taxonomy = apply_filters('jc_catalog_tag_taxonomy', $args['tag_taxonomy']);
        if (! taxonomy_exists($taxonomy) || $raw === '') {
            return;
        }

        $terms = array_filter(array_map('trim', explode($args['tag_delimiter'], $raw)));
        wp_set_object_terms($post_id, $terms, $taxonomy, false);
    }

    protected function sync_images(int $post_id, string $raw, array $args): void
    {
        if ($raw === '') {
            delete_post_thumbnail($post_id);
            update_post_meta($post_id, $args['gallery_meta_key'], []);
            return;
        }

        if (! function_exists('media_sideload_image')) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
        }

        $urls = array_values(array_filter(array_map('trim', explode($args['image_delimiter'], $raw))));
        if (empty($urls)) {
            return;
        }

        $gallery_ids = [];
        foreach ($urls as $index => $url) {
            $attachment_id = $this->resolve_attachment($url, $post_id);
            if (! $attachment_id) {
                continue;
            }

            if ($index === 0) {
                set_post_thumbnail($post_id, $attachment_id);
            } else {
                $gallery_ids[] = $attachment_id;
            }
        }

        update_post_meta($post_id, $args['gallery_meta_key'], $gallery_ids);
    }

    protected function resolve_attachment(string $url, int $post_id): ?int
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $existing = attachment_url_to_postid($url);
        if ($existing) {
            return $existing;
        }

        $tmp = download_url($url);
        if (is_wp_error($tmp)) {
            return null;
        }

        $file_array = [
            'name'     => wp_basename(parse_url($url, PHP_URL_PATH)),
            'tmp_name' => $tmp,
        ];

        $attachment_id = media_handle_sideload($file_array, $post_id);

        if (is_wp_error($attachment_id)) {
            @unlink($file_array['tmp_name']);
            return null;
        }

        return (int) $attachment_id;
    }

    protected function add_error(array $result, string $message): array
    {
        $result['errors'][] = $message;
        return $result;
    }
}
