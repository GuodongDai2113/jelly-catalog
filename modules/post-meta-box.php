<?php

/**
 * includes\class-jc-post-meta-box.php
 *
 * @see: https://jellydai.com
 * @author: Jelly Dai <daiguo1003@gmail.com>
 * @created: 2025.07.29 14:47
 */

namespace Jelly_Catalog\Modules;

use Jelly_Catalog\Utils;

if (!defined('ABSPATH')) {
    exit;
} // 禁止直接访问

/**
 * 产品后台编辑页面的自定义元框（Meta Box）管理类
 *
 * 负责注册和管理产品编辑页面的各个元框，包括产品图片、属性、FAQ、下载等功能
 * 同时提供详细的帮助文档指导用户正确填写产品信息
 *
 * @since 1.0.0
 */
class Post_Meta_Box
{
    /**
     * 构造函数
     *
     * 初始化元框管理器，注册帮助标签页，并根据 WooCommerce 是否激活加载相应的元框
     */
    public function __construct()
    {
        add_action('admin_head', [$this, 'add_help']);
        // 只在未激活 WooCommerce 时加载基础产品 metaboxes
        if (!Utils::is_wc_activated()) {
            $this->load_core_product_metaboxes();
        }

        // 始终加载扩展功能 metaboxes
        $this->load_extended_product_metaboxes();
    }

    /**
     * 为产品编辑页面添加帮助标签页
     *
     * 在产品编辑页面的右上角“帮助”菜单中添加多个标签页
     * 每个标签页提供对应字段的填写指导和最佳实践建议
     *
     * @since 1.0.0
     * @return void
     */
    public function add_help()
    {
        $screen = get_current_screen();
        if ($screen->id !== 'product') {
            return;
        }

        // 产品图片帮助标签页
        $screen->add_help_tab([
            'id' => 'product-images',
            'title' => __('Product Images', 'jelly-catalog'),
            'content' => __('Use high-quality images at least 800x800 pixels with white background, ensure clear product details and consistent lighting across all images', 'jelly-catalog'),
        ]);

        // 特色图片帮助标签页
        $screen->add_help_tab([
            'id' => 'postimagediv',
            'title' => __('Featured Image', 'jelly-catalog'),
            'content' => __('Set a representative featured image at 800x800 pixels with white background, this image will be used as the main thumbnail in product listings', 'jelly-catalog'),
        ]);

        $screen->add_help_tab([
            'id' => 'title_help',
            'title' => __('Title', 'jelly-catalog'),
            'content' => __('Recommended format: Model number + core product keywords + specifications/attributes', 'jelly-catalog'),
        ]);

        $screen->add_help_tab([
            'id' => 'postexcerpt_help',
            'title' => __('Short Description', 'jelly-catalog'),
            'content' => __('Include high-frequency keywords at the beginning, recommended length 100-160 characters, highlight unique product advantages', 'jelly-catalog'),
        ]);

        $screen->add_help_tab([
            'id' => 'postdivrich_help',
            'title' => __('Description', 'jelly-catalog'),
            'content' => __('Use keywords at least 5 times, paragraphs under 500 characters, minimum 1000 characters total, ensure original content with similarity under 30%', 'jelly-catalog'),
        ]);

        $screen->add_help_tab([
            'id' => 'product_catdiv_help',
            'title' => __('Categories', 'jelly-catalog'),
            'content' => __('Select 2-4 most relevant categories, use specific categories, avoid creating duplicates', 'jelly-catalog'),
        ]);

        $screen->add_help_tab([
            'id' => 'tagsdiv-product_tag_help',
            'title' => __('Tags', 'jelly-catalog'),
            'content' => __('Use 5-10 relevant tags highlighting features, materials, or use cases, separated by commas', 'jelly-catalog'),
        ]);

        $screen->add_help_tab([
            'id' => 'product_download_metabox_help',
            'title' => __('Download', 'jelly-catalog'),
            'content' => __('Upload virus-free files in supported formats with clear descriptions, compress large files', 'jelly-catalog'),
        ]);

        $screen->add_help_tab([
            'id' => 'product_faq_metabox_help',
            'title' => __('FAQ', 'jelly-catalog'),
            'content' => __('Address common customer questions with clear answers, organize by importance, update regularly based on feedback', 'jelly-catalog'),
        ]);

        $screen->add_help_tab([
            'id' => 'product_attributes_metabox_help',
            'title' => __('Attributes', 'jelly-catalog'),
            'content' => __('Include all technical specifications with consistent naming, provide accurate measurements, avoid duplicates', 'jelly-catalog'),
        ]);

        // SKU 帮助标签页
        $screen->add_help_tab([
            'id' => 'product_sku_metabox_help',
            'title' => __('Product Model/SKU', 'jelly-catalog'),
            'content' => __('Enter unique alphanumeric SKU without spaces or special characters, maintain consistent format across products', 'jelly-catalog'),
        ]);

        // Keywords 帮助标签页
        $screen->add_help_tab([
            'id' => 'longtail_keywords_metabox_help',
            'title' => __('Long-tail Keywords', 'jelly-catalog'),
            'content' => __('Enter basic keywords to generate long-tail variations automatically, focus on specific customer intents and customize as needed', 'jelly-catalog'),
        ]);

        // Video URL 帮助标签页
        $screen->add_help_tab([
            'id' => 'product_videourl_metabox_help',
            'title' => __('Product Video', 'jelly-catalog'),
            'content' => __('Enter full video URL from YouTube or Vimeo, keep videos short and focused on product features, optimize for mobile viewing', 'jelly-catalog'),
        ]);
    }

    /**
     * 加载核心产品元框（仅在未安装 WooCommerce 时）
     *
     * 这些是产品管理的基础功能元框，包括图片、摘要、分类、属性等
     * 如果已安装 WooCommerce，则使用 WooCommerce 提供的相应功能
     *
     * @since 1.0.0
     * @return void
     */
    private function load_core_product_metaboxes()
    {
        // 图片相册功能
        new Metabox\Product_Gallery_Metabox();
        // 产品摘要功能
        new Metabox\Product_Postexcerpt_Metabox();
        // 分类缩略图功能
        new Metabox\Product_Cat_Thumbnail_Metabox();
        // 产品属性功能
        new Metabox\Product_Attributes_Metabox();
        // 产品型号/SKU 功能
        new Metabox\Product_SKU_Metabox();
    }

    /**
     * 加载扩展功能元框（始终加载）
     *
     * 这些是 Jelly Catalog 插件提供的增强功能，无论是否安装 WooCommerce 都会加载
     * 包括长尾关键词、FAQ、下载、视频、分类横幅等高级功能
     *
     * @since 1.0.0
     * @return void
     */
    private function load_extended_product_metaboxes()
    {
        // 长尾关键词生成器
        new Metabox\Longtail_Keywords_Metabox();

        // FAQ 功能
        new Metabox\Product_FAQ_Metabox();

        // 产品下载功能
        new Metabox\Product_Download_Metabox();

        // 视频链接功能
        new Metabox\Product_Video_Metabox();

        // 产品前后跳转功能
        new Metabox\Product_Navigation_Metabox();

        // 分类横幅图功能
        new Metabox\Product_Cat_Banner_Metabox();

        // 分类产品字段功能
        new Metabox\Product_Cat_Fields_Metabox();

        // 分类问答功能
        new Metabox\Product_Cat_FAQ_Metabox();
    }
}
