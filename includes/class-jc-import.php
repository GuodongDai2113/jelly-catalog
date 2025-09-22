<?php

/**
 * includes\class-jc-import.php
 * 
 * @see: https://jellydai.com
 * @author: Jelly Dai <d@jellydai.com>
 * @created : 2025.09.22 16:35
 */

if (! defined('ABSPATH')) exit; // 禁止直接访问

class JC_Import
{
    /**
     * Import products from CSV data
     *
     * @param string $csv_file Path to CSV file
     * @return array Result of import process
     */
    public function import_from_csv($csv_file)
    {
        $result = array(
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => array()
        );

        // Parse CSV file
        $csv_data = $this->parse_csv($csv_file);

        if (is_wp_error($csv_data)) {
            return array('error' => $csv_data->get_error_message());
        }

        foreach ($csv_data as $row) {
            $import_result = $this->import_single_product($row);

            if (isset($import_result['error'])) {
                $result['skipped']++;
                $result['errors'][] = $import_result['error'];
            } elseif (isset($import_result['updated']) && $import_result['updated']) {
                $result['updated']++;
            } else {
                $result['imported']++;
            }
        }

        return $result;
    }

    /**
     * Parse CSV file
     *
     * @param string $csv_file Path to CSV file
     * @return array|WP_Error Parsed data or error
     */
    private function parse_csv($csv_file)
    {
        if (!file_exists($csv_file)) {
            return new WP_Error('file_not_exists', __('CSV file does not exist.', 'jelly-catalog'));
        }

        $file = fopen($csv_file, 'r');
        if (!$file) {
            return new WP_Error('file_open_error', __('Cannot open CSV file.', 'jelly-catalog'));
        }

        $header = fgetcsv($file);
        if (!$header) {
            return new WP_Error('invalid_csv', __('Invalid CSV file format.', 'jelly-catalog'));
        }

        $data = array();
        while (($row = fgetcsv($file)) !== false) {
            $data[] = array_combine($header, $row);
        }

        fclose($file);
        return $data;
    }

    /**
     * Import a single product
     *
     * @param array $product_data Single product data
     * @return array Result of single product import
     */
    private function import_single_product($product_data)
    {
        // Check if product already exists by ID or slug
        $existing_product = null;
        if (!empty($product_data['id']) && is_numeric($product_data['id'])) {
            $existing_product = get_post($product_data['id']);
        } elseif (!empty($product_data['slug'])) {
            $existing_product = get_page_by_path($product_data['slug'], OBJECT, 'product');
        }

        // Prepare post data
        $post_data = array(
            'post_type' => 'product',
            'post_title' => isset($product_data['title']) ? $product_data['title'] : '',
            'post_content' => isset($product_data['content']) ? $product_data['content'] : '',
            'post_excerpt' => isset($product_data['excerpt']) ? $product_data['excerpt'] : '',
            'post_status' => isset($product_data['status']) ? $product_data['status'] : 'publish',
            'post_name' => isset($product_data['slug']) ? $product_data['slug'] : '',
        );

        // Add ID if exists
        if ($existing_product) {
            $post_data['ID'] = $existing_product->ID;
        }

        // Insert or update product
        $post_id = wp_insert_post($post_data, true);

        if (is_wp_error($post_id)) {
            return array('error' => $post_id->get_error_message());
        }

        // Handle categories
        if (!empty($product_data['categories'])) {
            $categories = explode(',', $product_data['categories']);
            $this->set_product_terms($post_id, $categories, 'product_cat');
        }

        // Handle tags
        if (!empty($product_data['tags'])) {
            $tags = explode(',', $product_data['tags']);
            $this->set_product_terms($post_id, $tags, 'product_tag');
        }

        // Handle images (featured image and gallery)
        $gallery_image_ids = array();
        if (!empty($product_data['images'])) {
            $images = explode(',', $product_data['images']);
            foreach ($images as $index => $image_url) {
                $image_url = trim($image_url);
                if (empty($image_url)) {
                    continue;
                }

                // Download image and get attachment ID
                $attachment_id = $this->import_image_from_url($image_url);

                if ($attachment_id && !is_wp_error($attachment_id)) {
                    if ($index === 0) {
                        // First image is featured image
                        set_post_thumbnail($post_id, $attachment_id);
                    } else {
                        // Other images are gallery images
                        $gallery_image_ids[] = $attachment_id;
                    }
                }
            }
        }

        // Save gallery images to meta
        if (!empty($gallery_image_ids)) {
            update_post_meta($post_id, '_product_image_gallery', implode(',', $gallery_image_ids));
        } else {
            delete_post_meta($post_id, '_product_image_gallery');
        }

        // Handle meta fields
        $meta_fields = array_keys($product_data);
        foreach ($meta_fields as $field) {
            // Process meta fields (those not in standard fields)
            if (!in_array($field, array('id', 'slug', 'title', 'content', 'excerpt', 'status', 'categories', 'tags', 'images'))) {
                update_post_meta($post_id, $field, $product_data[$field]);
            }
        }

        return array(
            'post_id' => $post_id,
            'updated' => !is_null($existing_product)
        );
    }

    /**
     * Set product terms (categories or tags)
     *
     * @param int $post_id Post ID
     * @param array $terms Terms to set
     * @param string $taxonomy Taxonomy name
     */
    private function set_product_terms($post_id, $terms, $taxonomy)
    {
        $term_ids = array();

        foreach ($terms as $term_name) {
            $term_name = trim($term_name);
            if (empty($term_name)) {
                continue;
            }

            // Check if term exists
            $term_obj = term_exists($term_name, $taxonomy);
            if (!$term_obj) {
                // Create term if it doesn't exist
                $term_obj = wp_insert_term($term_name, $taxonomy);
            }

            if (!is_wp_error($term_obj)) {
                $term_ids[] = $term_obj['term_id'];
            }
        }

        wp_set_object_terms($post_id, $term_ids, $taxonomy);
    }

    /**
     * Import image from URL
     *
     * @param string $image_url Image URL
     * @return int|WP_Error Attachment ID or error
     */
    private function import_image_from_url($image_url)
    {
        // Check if image already exists in media library
        $attachment = get_page_by_title(basename($image_url), OBJECT, 'attachment');
        if ($attachment) {
            return $attachment->ID;
        }

        // Download image
        $image_data = file_get_contents($image_url);
        if ($image_data === false) {
            return new WP_Error('image_download_failed', __('Failed to download image from URL.', 'jelly-catalog'));
        }

        // Get image type
        $image_type = wp_check_filetype(basename($image_url));
        if (!$image_type['ext']) {
            return new WP_Error('invalid_image_type', __('Invalid image type.', 'jelly-catalog'));
        }

        // Create attachment
        $upload_dir = wp_upload_dir();
        $filename = basename($image_url);
        $unique_filename = wp_unique_filename($upload_dir['path'], $filename);
        $upload_file = $upload_dir['path'] . '/' . $unique_filename;

        file_put_contents($upload_file, $image_data);

        $attachment_data = array(
            'guid' => $upload_dir['url'] . '/' . $unique_filename,
            'post_mime_type' => $image_type['type'],
            'post_title' => sanitize_file_name(basename($filename, '.' . $image_type['ext'])),
            'post_content' => '',
            'post_status' => 'inherit'
        );

        $attachment_id = wp_insert_attachment($attachment_data, $upload_file);
        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }

        // Generate metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attachment_id, $upload_file);
        wp_update_attachment_metadata($attachment_id, $attach_data);

        return $attachment_id;
    }
}
