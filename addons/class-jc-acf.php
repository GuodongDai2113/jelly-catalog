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
        // add_action('acf/include_fields', array($this, 'add_prodcut_category_fields'));
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
                    'key' => 'field_category_h1_title',
                    'label' => esc_html__('Category H1 Title', 'jelly-catalog'),
                    'name' => 'category_h1_title',
                    'aria-label' => '',
                    'type' => 'text',
                    'instructions' => esc_html__('Enter the H1 title for this category.', 'jelly-catalog'),
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => array(
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ),
                    'default_value' => '',
                    'placeholder' => '',
                    'prepend' => '',
                    'append' => '',
                    'maxlength' => '',
                ),
                array(
                    'key' => 'field_category_why_choose_title',
                    'label' => esc_html__('Why Choose Us Title', 'jelly-catalog'),
                    'name' => 'category_why_choose_title',
                    'aria-label' => '',
                    'type' => 'text',
                    'instructions' => esc_html__('Enter the why choose us title for this category.', 'jelly-catalog'),
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => array(
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ),
                    'default_value' => '',
                    'placeholder' => '',
                    'prepend' => '',
                    'append' => '',
                    'maxlength' => '',
                ),
                array(
                    'key' => 'field_category_why_choose',
                    'label' => esc_html__('Category Why Choose', 'jelly-catalog'),
                    'name' => 'category_why_choose',
                    'aria-label' => '',
                    'type' => 'wysiwyg',
                    'instructions' => esc_html__('Enter the why choose for this category.', 'jelly-catalog'),
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
                array(
                    'key' => 'field_category_advantages',
                    'label' => esc_html__('Category Advantages', 'jelly-catalog'),
                    'name' => 'category_advantages',
                    'aria-label' => '',
                    'type' => 'wysiwyg',
                    'instructions' => esc_html__('Enter the advantages for this category.', 'jelly-catalog'),
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