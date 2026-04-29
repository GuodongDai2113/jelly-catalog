<?php

/**
 * includes\class-jc-post-meta-box.php
 *
 * @see: https://jellydai.com
 * @author: Jelly Dai <daiguo1003@gmail.com>
 * @created: 2025.07.29 14:47
 */

if (!defined('ABSPATH')) {
    exit;
} // 禁止直接访问

/**
 * 添加自定义 metabox
 */
class JC_Post_Meta_Box
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
        add_action('admin_head', [$this, 'add_help']);

        $this->initialize_metaboxes();
    }

    public function add_help()
    {
        $screen = get_current_screen();
        if ($screen->id !== 'product') {
            return;
        }

        $screen->add_help_tab([
            'id' => 'title_help',
            'title' => __('Title', 'jelly-catalog'),
            'content' => '<div class="title-description">' . __('Recommended format: <code>Model number + core product keywords + specifications/attributes</code>', 'jelly-catalog') . '</div>',
        ]);

        $screen->add_help_tab([
            'id' => 'postexcerpt_help',
            'title' => __('Short Description', 'jelly-catalog'),
            'content' => '<div class="edit-description">
                <p class="edit-title"><span class="dashicons dashicons-info-outline"></span>' . esc_html__('Writing Guidelines:', 'jelly-catalog') . '</p>
                <ol>
                    <li>' . esc_html__('The short description should naturally include high-frequency user search ', 'jelly-catalog') . '<code>' . esc_html__('keywords', 'jelly-catalog') . '</code>' . esc_html__(' or ', 'jelly-catalog') . '<code>' . esc_html__('long-tail keywords', 'jelly-catalog') . '</code></li>
                    <li>' . esc_html__('Place important keywords at the beginning to improve keyword weighting', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Recommended length is between ', 'jelly-catalog') . '<code>' . esc_html__('100-160', 'jelly-catalog') . '</code>' . esc_html__(' characters, providing sufficient value without being overly lengthy', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Use clear and concise language, avoiding excessive technical terminology', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Highlight unique product advantages, such as materials, performance, price, usage scenarios, etc.', 'jelly-catalog') . '</li>
                </ol>
            </div>',
        ]);

        $screen->add_help_tab([
            'id' => 'postdivrich_help',
            'title' => __('Description', 'jelly-catalog'),
            'content' => '<div class="edit-description">
                <p class="edit-title"><span class="dashicons dashicons-info-outline"></span>' . esc_html__('Writing Guidelines:', 'jelly-catalog') . '</p>
                <ol>
                    <li>' . esc_html__('It is recommended to use relevant keywords in the details, no less than 5 times, evenly distributed throughout the page.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Replace all pronouns in the details with keywords - use specific names instead of words like "it" whenever possible.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Each paragraph in the details should not exceed ', 'jelly-catalog') . '<code>' . esc_html__('500', 'jelly-catalog') . '</code>' . esc_html__(' characters. If exceeded, split into paragraphs. The full text should have a minimum of ', 'jelly-catalog') . '<code>' . esc_html__('1000', 'jelly-catalog') . '</code>' . esc_html__(' characters.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Ensure original content in the details. Do not copy directly from the internet. Page similarity within the website should not exceed ', 'jelly-catalog') . '<code>' . esc_html__('30%', 'jelly-catalog') . '</code>' . esc_html__('.', 'jelly-catalog') . '</li>
                </ol>
            </div>',
        ]);

        $screen->add_help_tab([
            'id' => 'product_catdiv_help',
            'title' => __('Categories', 'jelly-catalog'),
            'content' => '<div class="edit-description">
                <p class="edit-title"><span class="dashicons dashicons-info-outline"></span>' . esc_html__('Category Selection Guidelines:', 'jelly-catalog') . '</p>
                <ol>
                    <li>' . esc_html__('Select the most relevant categories for your product to improve discoverability.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Use specific categories rather than generic ones when possible.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Avoid selecting too many categories - 2-4 relevant categories are recommended.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Check existing categories before creating new ones to maintain consistency.', 'jelly-catalog') . '</li>
                </ol>
            </div>',
        ]);

        $screen->add_help_tab([
            'id' => 'tagsdiv-product_tag_help',
            'title' => __('Tags', 'jelly-catalog'),
            'content' => '<div class="edit-description">
                <p class="edit-title"><span class="dashicons dashicons-info-outline"></span>' . esc_html__('Tag Usage Guidelines:', 'jelly-catalog') . '</p>
                <ol>
                    <li>' . esc_html__('Use tags to highlight specific features, materials, or use cases of your product.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Tags should be single words or short phrases separated by commas.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Focus on important keywords that customers might search for.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Avoid duplicate tags and excessive tagging - 5-10 relevant tags are sufficient.', 'jelly-catalog') . '</li>
                </ol>
            </div>',
        ]);

        $screen->add_help_tab([
            'id' => 'product_download_metabox_help',
            'title' => __('Download', 'jelly-catalog'),
            'content' => '<div class="edit-description">
                <p class="edit-title"><span class="dashicons dashicons-info-outline"></span>' . esc_html__('Downloadable Content Guidelines:', 'jelly-catalog') . '</p>
                <ol>
                    <li>' . esc_html__('Upload high-quality files in supported formats (PDF, DOC, ZIP, etc.).', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Ensure files are virus-free and safe for users to download.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Provide clear file descriptions to help users understand content.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Compress large files to reduce download time and server load.', 'jelly-catalog') . '</li>
                </ol>
            </div>',
        ]);

        $screen->add_help_tab([
            'id' => 'product_faq_metabox_help',
            'title' => __('FAQ', 'jelly-catalog'),
            'content' => '<div class="edit-description">
                <p class="edit-title"><span class="dashicons dashicons-info-outline"></span>' . esc_html__('FAQ Creation Guidelines:', 'jelly-catalog') . '</p>
                <ol>
                    <li>' . esc_html__('Address common customer questions about product features, usage, and specifications.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Write clear, concise questions and detailed, helpful answers.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Organize FAQs in order of importance or frequency of questions.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Regularly update FAQs based on customer feedback and new questions.', 'jelly-catalog') . '</li>
                </ol>
            </div>',
        ]);

        $screen->add_help_tab([
            'id' => 'product_attributes_metabox_help',
            'title' => __('Attributes', 'jelly-catalog'),
            'content' => '<div class="edit-description">
                <p class="edit-title"><span class="dashicons dashicons-info-outline"></span>' . esc_html__('Product Attributes Guidelines:', 'jelly-catalog') . '</p>
                <ol>
                    <li>' . esc_html__('Include all relevant technical specifications and product details.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Use consistent naming conventions for attribute keys.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Provide accurate measurements and specifications.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Avoid duplicate attributes and ensure all values are filled in.', 'jelly-catalog') . '</li>
                </ol>
            </div>',
        ]);

        // SKU 帮助标签页
        $screen->add_help_tab([
            'id' => 'product_sku_metabox_help',
            'title' => __('Product Model/SKU', 'jelly-catalog'),
            'content' => '<div class="edit-description">
                <p class="edit-title"><span class="dashicons dashicons-info-outline"></span>' . esc_html__('SKU Management Guidelines:', 'jelly-catalog') . '</p>
                <ol>
                    <li>' . esc_html__('Enter a unique product model number or SKU to identify your product.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Use alphanumeric characters, hyphens, and underscores for best compatibility.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Avoid using spaces and special characters in SKUs.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Keep SKUs consistent in format across similar products for easier management.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('SKUs help with inventory tracking and e-commerce integration.', 'jelly-catalog') . '</li>
                </ol>
            </div>',
        ]);

        // Keywords 帮助标签页
        $screen->add_help_tab([
            'id' => 'longtail_keywords_metabox_help',
            'title' => __('Long-tail Keywords', 'jelly-catalog'),
            'content' => '<div class="edit-description">
                <p class="edit-title"><span class="dashicons dashicons-info-outline"></span>' . esc_html__('Long-tail Keywords Guidelines:', 'jelly-catalog') . '</p>
                <ol>
                    <li>' . esc_html__('Enter basic keywords to generate related long-tail variations automatically.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Long-tail keywords typically have lower competition and higher conversion rates.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Use generated keywords in product titles, descriptions, and meta fields.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Focus on specific customer intents and use cases for better targeting.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Review and customize generated keywords to match your specific product niche.', 'jelly-catalog') . '</li>
                </ol>
            </div>',
        ]);

        // Video URL 帮助标签页
        $screen->add_help_tab([
            'id' => 'product_videourl_metabox_help',
            'title' => __('Product Video', 'jelly-catalog'),
            'content' => '<div class="edit-description">
                <p class="edit-title"><span class="dashicons dashicons-info-outline"></span>' . esc_html__('Video Integration Guidelines:', 'jelly-catalog') . '</p>
                <ol>
                    <li>' . esc_html__('Enter a full URL to your product video hosted on YouTube, Vimeo, or other platforms.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Videos can significantly improve customer engagement and conversion rates.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Ensure videos are optimized for fast loading and mobile viewing.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Keep videos short and focused on product features and benefits.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Use clear titles and descriptions that complement the product information.', 'jelly-catalog') . '</li>
                </ol>
            </div>',
        ]);
    }

    private function initialize_metaboxes()
    {
        // 只在未激活 WooCommerce 时加载基础产品 metaboxes
        if (!jc_is_woocommerce_activated()) {
            $this->load_core_product_metaboxes();
        }

        // 始终加载扩展功能 metaboxes
        $this->load_extended_product_metaboxes();
    }

    /**
     * 加载核心产品 metaboxes（仅在无 WooCommerce 时）
     */
    private function load_core_product_metaboxes()
    {
        include JELLY_CATALOG_PLUGIN_PATH . 'includes/metabox/class-jc-product-gallery-metabox.php';
        include JELLY_CATALOG_PLUGIN_PATH . 'includes/metabox/class-jc-product-postexcerpt-metabox.php';
        include JELLY_CATALOG_PLUGIN_PATH . 'includes/metabox/class-jc-product-cat-thumbnail-metabox.php';

        // 图片相册功能
        new JC_Product_Gallery_Metabox();
        // 产品摘要功能
        new JC_Product_Postexcerpt_Metabox();
        // 分类缩略图功能
        new JC_Product_Cat_Thumbnail_Metabox();
    }

    /**
     * 加载扩展功能 metaboxes（始终加载）
     */
    private function load_extended_product_metaboxes()
    {
        // 长尾关键词生成器
        include JELLY_CATALOG_PLUGIN_PATH . 'includes/metabox/class-jc-longtail-keywords-metabox.php';
        new JC_Longtail_Keywords_Metabox();

        // 产品型号/SKU 功能
        include JELLY_CATALOG_PLUGIN_PATH . 'includes/metabox/class-jc-product-sku-metabox.php';
        new JC_Product_SKU_Metabox();

        // FAQ 功能
        include JELLY_CATALOG_PLUGIN_PATH . 'includes/metabox/class-jc-product-faq-metabox.php';
        new JC_Product_FAQ_Metabox();

        // 产品下载功能
        include JELLY_CATALOG_PLUGIN_PATH . 'includes/metabox/class-jc-product-download-metabox.php';
        new JC_Product_Download_Metabox();

        // 视频链接功能
        include JELLY_CATALOG_PLUGIN_PATH . 'includes/metabox/class-jc-product-videourl-metabox.php';
        new JC_Product_VideoURL_Metabox();

        // 产品属性功能
        include JELLY_CATALOG_PLUGIN_PATH . 'includes/metabox/class-jc-product-attributes-metabox.php';
        new JC_Product_Attributes_Metabox();

        // 分类横幅图功能
        include JELLY_CATALOG_PLUGIN_PATH . 'includes/metabox/class-jc-product-cat-banner-metabox.php';
        new JC_Product_Cat_Banner_Metabox();

        // 分类产品字段功能
        include JELLY_CATALOG_PLUGIN_PATH . 'includes/metabox/class-jc-product-cat-fields-metabox.php';
        new JC_Product_Cat_Fields_Metabox();

        // 分类问答功能
        include JELLY_CATALOG_PLUGIN_PATH . 'includes/metabox/class-jc-product-cat-faq-metabox.php';
        new JC_Product_Cat_FAQ_Metabox();
    }
}
JC_Post_Meta_Box::instance();
