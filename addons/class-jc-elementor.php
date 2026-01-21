<?php

/**
 * addons\class-jc-elementor.php
 * 
 * @see: https://jellydai.com
 * @author: Jelly Dai <daiguo1003@gmail.com>
 * @created : 2025.11.01 19:37
 */

use Elementor\Widget_Base;
use Elementor\Core\Documents_Manager;
use ElementorPro\Plugin;
use ElementorPro\Modules\LoopBuilder\Module as LoopBuilderModule;


if (! defined('ABSPATH')) exit; // 禁止直接访问

class JC_Elementor
{
    public static $instance;

    protected $docs_types = [];

    const LOOP_PRODUCT_SKIN_ID = 'product';
    const LOOP_PRODUCT_TAXONOMY_SKIN_ID = 'product_taxonomy';

    const RECOMMENDED_POSTS_WIDGET_NAMES = [
        'theme-post-featured-image',
    ];

    public static function instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    public function __construct()
    {
        // $this->include_elementor();

        add_action('elementor/template-library/create_new_dialog_fields', [$this, 'add_products_type_to_template_popup'], 11);
        add_action('elementor-pro/modules/loop-builder/documents/loop/query_settings', [$this, 'add_products_type_to_loop_settings_query'], 11);

        add_action('elementor/template-library/create_new_dialog_fields', [$this, 'add_products_taxonomy_type_to_template_popup'], 13);
        add_action('elementor-pro/modules/loop-builder/documents/loop/query_settings', [$this, 'add_products_taxonomy_type_to_loop_settings_query'], 13);

        add_action('elementor/dynamic_tags/register', [$this, 'register_tags']);
        add_action('elementor/theme/register_conditions', [$this, 'register_conditions']);
        add_action('elementor/documents/register', [$this, 'register_documents']);
        add_action('elementor/widgets/register', [$this, 'register_widgets']);

        add_filter('elementor/theme/need_override_location', [$this, 'theme_template_include'], 10, 2);
        add_filter('elementor/document/config', [$this, 'add_loop_recommended_widgets'], 11, 2);

        add_filter('elementor_pro/init', [$this, 'register_skins']);

        add_filter('elementor/query/query_args', function ($query_args, $widget) {
            return $this->loop_query($query_args, $widget);
        }, 10, 2);
    }

    /**
     * @param $form
     * @param $control_name
     * @return void
     */
    protected function add_products_to_options($form, $control_name)
    {
        if (empty($form)) {
            return;
        }

        $controls = $form->get_controls($control_name);
        if (! $controls || ! isset($controls['options'])) {
            return;
        }

        $options = $controls['options'];
        $options[self::LOOP_PRODUCT_SKIN_ID] = esc_html__('Products', 'elementor-pro');

        $form->update_control($control_name, [
            'options' => $options,
        ]);
    }

    protected function add_taxonomies_to_options($form, $control_name)
    {
        $controls = $form->get_controls($control_name);

        if (! $controls || ! isset($controls['options'])) {
            return;
        }

        $options = $controls['options'];
        $options[self::LOOP_PRODUCT_TAXONOMY_SKIN_ID] = esc_html__('Product Taxonomy', 'elementor-pro');

        $form->update_control($control_name, [
            'options' => $options,
        ]);
    }


    public function add_products_type_to_template_popup($form)
    {
        $this->add_products_to_options($form, '_elementor_source');
    }

    public function add_products_type_to_loop_settings_query($form)
    {
        $this->add_products_to_options($form, 'source');
    }

    public function add_products_taxonomy_type_to_template_popup($form)
    {
        $this->add_taxonomies_to_options($form, '_elementor_source');
    }

    public function add_products_taxonomy_type_to_loop_settings_query($form)
    {
        $this->add_taxonomies_to_options($form, 'source');
    }

    public function register_tags()
    {

        include_once(JELLY_CATALOG_PLUGIN_PATH . 'addons/elementor/tags/product-title.php');
        include_once(JELLY_CATALOG_PLUGIN_PATH . 'addons/elementor/tags/product-cat-image.php');
        include_once(JELLY_CATALOG_PLUGIN_PATH . 'addons/elementor/tags/product-cat-banner.php');
        include_once(JELLY_CATALOG_PLUGIN_PATH . 'addons/elementor/tags/product-cat-why-choose.php');
        include_once(JELLY_CATALOG_PLUGIN_PATH . 'addons/elementor/tags/product-cat-advantages.php');
        include_once(JELLY_CATALOG_PLUGIN_PATH . 'addons/elementor/tags/product-cat-h1-title.php');
        include_once(JELLY_CATALOG_PLUGIN_PATH . 'addons/elementor/tags/product-cat-why-choose-title.php');

        $tags = [
            'Product_Title',
            'Product_Cat_Image',
            'Product_Cat_Banner',
            'Product_Cat_Why_Choose',
            'Product_Cat_Advantages',
            'Product_Cat_H1_Title',
            'Product_Cat_Why_Choose_Title',
        ];

        /** @var \Elementor\Core\DynamicTags\Manager $module */
        $module = Plugin::elementor()->dynamic_tags;

        $module->register_group('jelly-catalog', [
            'title' => esc_html__('Jelly Catalog', 'jelly-catalog'),
        ]);

        foreach ($tags as $tag) {
            $tag = 'Jelly_Catalog\\Addons\\Elementor\\tags\\' . $tag;

            $module->register(new $tag());
        }
    }

    public function register_conditions($conditions_manager)
    {
        include_once(JELLY_CATALOG_PLUGIN_PATH . 'addons/elementor/conditions/jelly-catalog.php');
        include_once(JELLY_CATALOG_PLUGIN_PATH . 'addons/elementor/conditions/products-page.php');
        include_once(JELLY_CATALOG_PLUGIN_PATH . 'addons/elementor/conditions/product-archive.php');
        include_once(JELLY_CATALOG_PLUGIN_PATH . 'addons/elementor/conditions/product-search.php');

        $jc_condition = new Jelly_Catalog\Addons\Elementor\Conditions\Jelly_Catalog();

        $conditions_manager->get_condition('general')->register_sub_condition($jc_condition);
    }

    /**
     * @param Documents_Manager $documents_manager
     */
    public function register_documents($documents_manager)
    {
        include_once(JELLY_CATALOG_PLUGIN_PATH . 'addons/elementor/documents/product-archive.php');
        include_once(JELLY_CATALOG_PLUGIN_PATH . 'addons/elementor/documents/product-post.php');
        include_once(JELLY_CATALOG_PLUGIN_PATH . 'addons/elementor/documents/product.php');

        $this->docs_types = [
            'product-post' => Jelly_Catalog\Addons\Elementor\Documents\Product_Post::get_class_full_name(),
        ];

        $this->docs_types['product'] = Jelly_Catalog\Addons\Elementor\Documents\Product::get_class_full_name();

        $this->docs_types['product-archive'] = Jelly_Catalog\Addons\Elementor\Documents\Product_Archive::get_class_full_name();

        foreach ($this->docs_types as $type => $class_name) {
            $documents_manager->register_document_type($type, $class_name);
        }
    }

    function register_skins()
    {
        include_once(JELLY_CATALOG_PLUGIN_PATH . 'addons/elementor/skins/skin-loop-product.php');
        include_once(JELLY_CATALOG_PLUGIN_PATH . 'addons/elementor/skins/skin-loop-product-taxonomy.php');

        foreach (LoopBuilderModule::LOOP_WIDGETS as $widget_type) {
            add_action('elementor/widget/' . $widget_type . '/skins_init', function (Widget_Base $widget) {
                $widget->add_skin(new  Jelly_Catalog\Addons\Elementor\Skins\Skin_Loop_Product($widget));
            });
            add_action('elementor/widget/' . $widget_type . '/skins_init', function (Widget_Base $widget) {
                $widget->add_skin(new  Jelly_Catalog\Addons\Elementor\Skins\Skin_Loop_Product_Taxonomy($widget));
            }, 13);
        }
    }

    function register_widgets($widgets_manager)
    {
        include_once(JELLY_CATALOG_PLUGIN_PATH . 'addons/elementor/widgets/product-faq.php');
        include_once(JELLY_CATALOG_PLUGIN_PATH . 'addons/elementor/widgets/product-cat-faq.php');
        include_once(JELLY_CATALOG_PLUGIN_PATH . 'addons/elementor/widgets/product-content.php');
        include_once(JELLY_CATALOG_PLUGIN_PATH . 'addons/elementor/widgets/product-attributes.php');
        include_once(JELLY_CATALOG_PLUGIN_PATH . 'addons/elementor/widgets/product-cat-nav.php');
        include_once(JELLY_CATALOG_PLUGIN_PATH . 'addons/elementor/widgets/product-download.php');
        include_once(JELLY_CATALOG_PLUGIN_PATH . 'addons/elementor/widgets/product-gallery.php');

        $widgets_manager->register( new Jelly_Catalog\Addons\Elementor\Widgets\Product_Content() );
        $widgets_manager->register( new Jelly_Catalog\Addons\Elementor\Widgets\Product_FAQ() );
        $widgets_manager->register( new Jelly_Catalog\Addons\Elementor\Widgets\Product_Cat_FAQ() );
        $widgets_manager->register( new Jelly_Catalog\Addons\Elementor\Widgets\Product_Attributes() );
        $widgets_manager->register( new Jelly_Catalog\Addons\Elementor\Widgets\Product_Cat_Nav() );
        $widgets_manager->register( new Jelly_Catalog\Addons\Elementor\Widgets\Product_Download() );
        $widgets_manager->register( new Jelly_Catalog\Addons\Elementor\Widgets\Product_Gallery() );

    }

    public function theme_template_include($need_override_location, $location)
    {
        if (is_jc_product_single() && 'single' === $location) {
            $need_override_location = true;
        }

        return $need_override_location;
    }

    public function add_loop_recommended_widgets($config, $post_id)
    {
        if (! $this->is_source_set_to_products($post_id)) {
            return $config;
        }

        $config = $this->add_jelly_catalog_widgets_to_recommended($config);
        return $this->hide_jelly_catalog_widgets_in_loop_document($config);
    }

    /**
     * @param $post_id
     * @return bool
     */
    private function is_source_set_to_products($post_id)
    {
        return 'product' === get_post_meta($post_id, '_elementor_source', true);
    }

    /**
     * @param array $config
     * @return array
     */
    private function add_jelly_catalog_widgets_to_recommended(array $config)
    {
        foreach (static::RECOMMENDED_POSTS_WIDGET_NAMES as $recommended_posts_widget_name) {
            $config['panel']['widgets_settings'][$recommended_posts_widget_name] = [
                'categories' => ['recommended'],
                'show_in_panel' => true,
            ];
        }
        return $config;
    }

    private function hide_jelly_catalog_widgets_in_loop_document(array $config)
    {
        // $config['panel']['widgets_settings']['woocommerce-product-images'] = [
        //     'show_in_panel' => false,
        // ];
        return $config;
    }

    public function loop_query($query_args, $widget)
    {
        if (! $this->is_product_query($widget)) {
            return $query_args;
        }

        return $this->parse_loop_query_args($widget, $query_args);
    }

    private function is_product_query($widget)
    {
        $widget_config = $widget->get_config();

        return (! empty($widget_config['is_loop']) && 'product' === $widget->get_current_skin_id());
    }

    private function parse_loop_query_args($widget, $query_args)
    {
        global $wp_query;

        $settings = $this->adjust_setting_for_product_renderer($widget);

        // For Products_Renderer.
        if (! isset($GLOBALS['post'])) {
            $GLOBALS['post'] = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        }

        $parsed_query_args = $this->parse_query_args($settings);

        if (empty($wp_query->include_field_ids_arg)) {
            unset($parsed_query_args['fields']);
        }

        $override_various_query_args = array_filter($query_args, function ($key) {
            return in_array($key, ['posts_per_page', 'offset', 'paged'], true);
        }, ARRAY_FILTER_USE_KEY);

        return wp_parse_args($override_various_query_args, $parsed_query_args);
    }

    public function parse_query_args($settings)
    {
        $query_args = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'ignore_sticky_posts' => true,
            'orderby' => $settings['query_orderby'],
            'order' => strtoupper($settings['query_order']),
        ];

        if (in_array($settings['query_post_type'], ['related_products'], true)) {
            // 获取当前产品ID
            $current_product_id = get_the_ID();

            // 获取当前产品的分类和标签
            $product_terms = wp_get_post_terms($current_product_id, ['product_cat', 'product_tag'], [
                'fields' => 'ids'
            ]);

            if (!empty($product_terms) && !is_wp_error($product_terms)) {
                $query_args['tax_query'] = [
                    [
                        'taxonomy' => 'product_cat',
                        'field'    => 'term_id',
                        'terms'    => $product_terms,
                    ],
                    [
                        'taxonomy' => 'product_tag',
                        'field'    => 'term_id',
                        'terms'    => $product_terms,
                    ]
                ];

                // 使用OR关系查询分类或标签相同的产品
                $query_args['tax_query']['relation'] = 'OR';
            }

            // 排除当前产品
            $query_args['post__not_in'] = [$current_product_id];

            // 设置关联产品数量
            if (!empty($settings['posts_per_page'])) {
                $query_args['posts_per_page'] = $settings['posts_per_page'];
            } else {
                $query_args['posts_per_page'] = 4; // 默认显示4个相关产品
            }
        }

        // Always query only IDs.
        $query_args['fields'] = 'ids';

        return $query_args;
    }

    private function adjust_setting_for_product_renderer($widget)
    {
        $settings = $widget->get_settings_for_display();

        $query_name = $widget->get_query_name();

        $unique_query_settings = array_filter($settings, function ($key) use ($query_name) {
            return 0 === strpos($key, $query_name);
        }, ARRAY_FILTER_USE_KEY);

        $query_settings = [];

        foreach ($unique_query_settings as $key => $value) {
            $query_settings['query' . str_replace($query_name, '', $key)] = $value;
        }

        $settings = array_merge($settings, $query_settings);

        if (isset($settings['posts_per_page']) && isset($settings['columns'])) {
            $settings['rows'] = ceil($settings['posts_per_page'] / $settings['columns']);
        }

        $settings['paginate'] = 'yes';
        $settings['allow_order'] = 'no';
        $settings['show_result_count'] = 'no';
        $settings['query_fields'] = false;

        return $settings;
    }
}

JC_Elementor::instance();