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
        add_action('acf/include_fields', array($this, 'add_prodcut_fields'));
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