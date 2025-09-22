<?php

/**
 * includes\class-jc-export.php
 * 
 * @see: https://jellydai.com
 * @author: Jelly Dai <d@jellydai.com>
 * @created : 2025.09.22 16:35
 */

if (! defined('ABSPATH')) exit; // 禁止直接访问

class JC_Export
{
    /**
     * Export products to CSV file
     *
     * @param array $args Query arguments
     * @param string $filename CSV filename
     * @return string|WP_Error Path to CSV file or error
     */
    public function export_products_to_csv($args = array(), $filename = 'products-export.csv') {
        $export_data = $this->export_products($args);
        
        if (empty($export_data)) {
            return new WP_Error('no_data', __('No products found to export.', 'jelly-catalog'));
        }
        
        // Create temporary file
        $upload_dir = wp_upload_dir();
        $csv_file = $upload_dir['path'] . '/' . $filename;
        
        $file = fopen($csv_file, 'w');
        if (!$file) {
            return new WP_Error('file_create_error', __('Cannot create CSV file.', 'jelly-catalog'));
        }
        
        // Get all possible headers
        $headers = $this->get_csv_headers($export_data);
        
        // Write headers
        fputcsv($file, $headers);
        
        // Write data
        foreach ($export_data as $product) {
            $row = array();
            foreach ($headers as $header) {
                if (isset($product[$header])) {
                    if (is_array($product[$header])) {
                        $row[] = implode(',', $product[$header]);
                    } else {
                        $row[] = $product[$header];
                    }
                } else {
                    $row[] = '';
                }
            }
            fputcsv($file, $row);
        }
        
        fclose($file);
        return $csv_file;
    }
    
    /**
     * Get all possible CSV headers from export data
     *
     * @param array $export_data Export data
     * @return array Headers
     */
    private function get_csv_headers($export_data) {
        $headers = array(
            'id',
            'slug',
            'title',
            'content',
            'excerpt',
            'status',
            'categories',
            'tags',
            'images'
        );
        
        // Add meta fields from all products
        foreach ($export_data as $product) {
            foreach ($product as $key => $value) {
                // Skip _product_image_gallery as it's handled by images field
                if (!in_array($key, $headers) && $key !== '_product_image_gallery') {
                    $headers[] = $key;
                }
            }
        }
        
        return $headers;
    }
    
    /**
     * Export products based on query args
     *
     * @param array $args Query arguments
     * @return array Exported product data
     */
    public function export_products($args = array()) {
        $default_args = array(
            'post_type' => 'product',
            'post_status' => 'any',
            'posts_per_page' => -1,
        );

        $query_args = wp_parse_args($args, $default_args);
        $products = get_posts($query_args);

        $export_data = array();

        foreach ($products as $product) {
            $export_data[] = $this->export_single_product($product);
        }

        return $export_data;
    }

    /**
     * Export a single product
     *
     * @param WP_Post $product Product post object
     * @return array Exported product data
     */
    private function export_single_product($product) {
        $export_data = array(
            'id' => $product->ID,
            'slug' => $product->post_name,
            'title' => $product->post_title,
            'content' => $product->post_content,
            'excerpt' => $product->post_excerpt,
            'status' => $product->post_status,
            'categories' => $this->get_product_terms($product->ID, 'product_cat'),
            'tags' => $this->get_product_terms($product->ID, 'product_tag'),
        );

        // Handle images (featured image and gallery)
        $images = array();
        
        // Get featured image
        $thumbnail_id = get_post_thumbnail_id($product->ID);
        if ($thumbnail_id) {
            $images[] = wp_get_attachment_url($thumbnail_id);
        }
        
        // Get gallery images
        $gallery_ids = get_post_meta($product->ID, '_product_image_gallery', true);
        if ($gallery_ids) {
            $gallery_ids = explode(',', $gallery_ids);
            foreach ($gallery_ids as $gallery_id) {
                $gallery_id = trim($gallery_id);
                if (!empty($gallery_id)) {
                    $images[] = wp_get_attachment_url($gallery_id);
                }
            }
        }
        
        // Add images field
        if (!empty($images)) {
            $export_data['images'] = $images;
        }

        // Get all meta fields
        $meta_fields = get_post_meta($product->ID);
        foreach ($meta_fields as $meta_key => $meta_values) {
            // Skip internal meta fields and _product_image_gallery (handled by images field)
            if (strpos($meta_key, '_') === 0 || $meta_key === '_product_image_gallery') {
                continue;
            }
            
            // Use first value if multiple values exist
            $export_data[$meta_key] = $meta_values[0];
        }

        return $export_data;
    }

    /**
     * Get product terms (categories or tags)
     *
     * @param int $post_id Post ID
     * @param string $taxonomy Taxonomy name
     * @return array Term names
     */
    private function get_product_terms($post_id, $taxonomy) {
        $terms = wp_get_object_terms($post_id, $taxonomy);
        $term_names = array();

        foreach ($terms as $term) {
            $term_names[] = $term->name;
        }

        return $term_names;
    }

    /**
     * Export product categories to CSV
     *
     * @param string $filename CSV filename
     * @return string|WP_Error Path to CSV file or error
     */
    public function export_categories_to_csv($filename = 'categories-export.csv') {
        $export_data = $this->export_categories();
        
        if (empty($export_data)) {
            return new WP_Error('no_data', __('No categories found to export.', 'jelly-catalog'));
        }
        
        // Create temporary file
        $upload_dir = wp_upload_dir();
        $csv_file = $upload_dir['path'] . '/' . $filename;
        
        $file = fopen($csv_file, 'w');
        if (!$file) {
            return new WP_Error('file_create_error', __('Cannot create CSV file.', 'jelly-catalog'));
        }
        
        // Write headers
        fputcsv($file, array('term_id', 'name', 'slug', 'description', 'parent'));
        
        // Write data
        foreach ($export_data as $category) {
            fputcsv($file, $category);
        }
        
        fclose($file);
        return $csv_file;
    }

    /**
     * Export product tags to CSV
     *
     * @param string $filename CSV filename
     * @return string|WP_Error Path to CSV file or error
     */
    public function export_tags_to_csv($filename = 'tags-export.csv') {
        $export_data = $this->export_tags();
        
        if (empty($export_data)) {
            return new WP_Error('no_data', __('No tags found to export.', 'jelly-catalog'));
        }
        
        // Create temporary file
        $upload_dir = wp_upload_dir();
        $csv_file = $upload_dir['path'] . '/' . $filename;
        
        $file = fopen($csv_file, 'w');
        if (!$file) {
            return new WP_Error('file_create_error', __('Cannot create CSV file.', 'jelly-catalog'));
        }
        
        // Write headers
        fputcsv($file, array('term_id', 'name', 'slug', 'description'));
        
        // Write data
        foreach ($export_data as $tag) {
            fputcsv($file, $tag);
        }
        
        fclose($file);
        return $csv_file;
    }

    /**
     * Export product categories
     *
     * @return array Exported category data
     */
    public function export_categories()
    {
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ));

        $export_data = array();

        foreach ($categories as $category) {
            $export_data[] = array(
                'term_id' => $category->term_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'parent' => $category->parent,
            );
        }

        return $export_data;
    }

    /**
     * Export product tags
     *
     * @return array Exported tag data
     */
    public function export_tags()
    {
        $tags = get_terms(array(
            'taxonomy' => 'product_tag',
            'hide_empty' => false,
        ));

        $export_data = array();

        foreach ($tags as $tag) {
            $export_data[] = array(
                'term_id' => $tag->term_id,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'description' => $tag->description,
            );
        }

        return $export_data;
    }
}