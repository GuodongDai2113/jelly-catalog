<?php

/**
 * includes\class-jc-acf.php
 * 
 * @see: https://jellydai.com
 * @author: Jelly Dai <daiguo1003@gmail.com>
 * @created : 2025.07.31 10:45
 */

if (! defined('ABSPATH')) exit; // 禁止直接访问

class JC_ACF
{
    public static $instance;


    public static function instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        add_action('acf/include_fields', array($this, 'add_prodcut_category_fields'));
        add_action('acf/include_fields', array($this, 'add_prodcut_fields'));
    }

    function add_prodcut_category_fields()
    {
        if (! function_exists('acf_add_local_field_group')) {
            return;
        }

        $product_category_field_group = array(
            'key' => 'group_product_category',
            'title' => esc_html__('Product Category Field Group', 'jelly-catalog'),
            'fields' => array(
                array(
                    'key' => 'field_category_content',
                    'label' => esc_html__('Category Content', 'jelly-catalog'),
                    'name' => 'category_content',
                    'aria-label' => '',
                    'type' => 'wysiwyg',
                    'instructions' => esc_html__('Enter the content for this category.', 'jelly-catalog'),
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => array(
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ),
                    'default_value' => '',
                    'allow_in_bindings' => 0,
                    'tabs' => 'all',
                    'toolbar' => 'full',
                    'media_upload' => 1,
                    'delay' => 0,
                ),

            ),
            'location' => array(
                array(
                    array(
                        'param' => 'taxonomy',
                        'operator' => '==',
                        'value' => 'product_cat',
                    ),
                ),
            ),
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'seamless',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen' => '',
            'active' => true,
            'description' => '',
            'show_in_rest' => 0,
        );

        if (is_plugin_active('elementor/elementor.php')) {
            $product_category_field_group['fields'][] = array(
                'key' => 'field_elementor_template_id',
                'label' => esc_html__('Elementor Page Template', 'jelly-catalog'),
                'name' => 'elementor_template_id',
                'aria-label' => '',
                'type' => 'post_object',
                'instructions' => esc_html__('Choose an Elementor-designed page to display for this category.', 'jelly-catalog'),
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'post_type' => array(
                    0 => 'elementor_library',
                ),
                'post_status' => array(
                    0 => 'publish',
                ),
                'query_args' => array(
                    'meta_query' => array(
                        array(
                            'key' => '_elementor_template_type',
                            'value' => 'page',
                            'compare' => '='
                        )
                    )
                ),
                'taxonomy' => '',
                'return_format' => 'id',
                'multiple' => 0,
                'allow_null' => 1,
                'allow_in_bindings' => 0,
                'bidirectional' => 0,
                'ui' => 1,
                'bidirectional_target' => array(),
            );
        }

        acf_add_local_field_group($product_category_field_group);
    }

    function add_prodcut_fields()
    {
        if (! function_exists('acf_add_local_field_group')) {
            return;
        }

        $product_field_group =  array(
            'key' => 'group_product_field',
            'title' => esc_html__('Product Download', 'jelly-catalog'),
            'fields' => array(
                array(
                    'key' => 'field_product_file',
                    'label' => 'Product File',
                    'name' => 'product_file',
                    'aria-label' => '',
                    'type' => 'file',
                    'instructions' => esc_html__('Product catalog, multiple files can be uploaded in compressed packages', 'jelly-catalog'),
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => array(
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ),
                    'return_format' => 'array',
                    'library' => 'all',
                    'min_size' => '',
                    'max_size' => '',
                    'mime_types' => '',
                    'allow_in_bindings' => 0,
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'product',
                    ),
                ),
            ),
            'menu_order' => 0,
            'position' => 'side',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen' => '',
            'active' => true,
            'description' => '',
            'show_in_rest' => 0,
        );

        acf_add_local_field_group($product_field_group);
    }
}

JC_ACF::instance();
