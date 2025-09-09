<?php

/**
 * includes\class-jc-post-meta-box.php
 * 
 * @see: https://jellydai.com
 * @author: Jelly Dai <d@jellydai.com>
 * @created: 2025.07.29 14:47
 */


if (! defined('ABSPATH')) exit; // 禁止直接访问

/**
 * 请填写描述
 */
class JC_Post_Meta_Box
{
    public function __construct()
    {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'), 30);
        add_action('save_post_product', array($this, 'save_meta_boxes'));
        add_action('admin_head', array($this, 'add_help'));
    }

    function add_meta_boxes()
    {
        if (!is_woocommerce_activated()) {
            add_meta_box('postexcerpt', __('Product Short Description', 'jelly-catalog'), array($this, 'short_description_box'), 'product', 'normal');
            add_meta_box('product-images', __('Product Gallery', 'jelly-catalog'), array($this, 'product_images_box'), 'product', 'side', 'low');
            add_meta_box('product_attributes_metabox', __('Product Attributes', 'jelly-catalog'), array($this, 'product_attributes_metabox'), 'product', 'normal', 'default');
        }
        add_meta_box('product_faq_metabox', __('Product FAQ', 'jelly-catalog'), array($this, 'product_faq_metabox'), 'product', 'normal', 'default');
        add_meta_box('product_video_url', __('Product Video', 'jelly-catalog'), array($this, 'product_video_url_box'), 'product', 'normal', 'default');
    }

    function add_help()
    {
        $screen = get_current_screen();
        if ($screen->id !== 'product') {
            return;
        }

        $screen->add_help_tab(array(
            'id'      => 'postexcerpt_help',
            'title'   => __('Short Description', 'jelly-catalog'),
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
        ));

        $screen->add_help_tab(array(
            'id'      => 'postdivrich_help',
            'title'   => __('Description', 'jelly-catalog'),
            'content' => '<div class="edit-description">
                <p class="edit-title"><span class="dashicons dashicons-info-outline"></span>' . esc_html__('Writing Guidelines:', 'jelly-catalog') . '</p>
                <ol>
                    <li>' . esc_html__('It is recommended to use relevant keywords in the details, no less than 5 times, evenly distributed throughout the page.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Replace all pronouns in the details with keywords - use specific names instead of words like "it" whenever possible.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Each paragraph in the details should not exceed ', 'jelly-catalog') . '<code>' . esc_html__('500', 'jelly-catalog') . '</code>' . esc_html__(' characters. If exceeded, split into paragraphs. The full text should have a minimum of ', 'jelly-catalog') . '<code>' . esc_html__('1000', 'jelly-catalog') . '</code>' . esc_html__(' characters.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Ensure original content in the details. Do not copy directly from the internet. Page similarity within the website should not exceed ', 'jelly-catalog') . '<code>' . esc_html__('30%', 'jelly-catalog') . '</code>' . esc_html__('.', 'jelly-catalog') . '</li>
                </ol>
            </div>',
        ));

        $screen->add_help_tab(array(
            'id'      => 'product_catdiv_help',
            'title'   => __('Categories', 'jelly-catalog'),
            'content' => '<div class="edit-description">
                <p class="edit-title"><span class="dashicons dashicons-info-outline"></span>' . esc_html__('Category Selection Guidelines:', 'jelly-catalog') . '</p>
                <ol>
                    <li>' . esc_html__('Select the most relevant categories for your product to improve discoverability.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Use specific categories rather than generic ones when possible.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Avoid selecting too many categories - 2-4 relevant categories are recommended.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Check existing categories before creating new ones to maintain consistency.', 'jelly-catalog') . '</li>
                </ol>
            </div>',
        ));

        $screen->add_help_tab(array(
            'id'      => 'tagsdiv-product_tag_help',
            'title'   => __('Tags', 'jelly-catalog'),
            'content' => '<div class="edit-description">
                <p class="edit-title"><span class="dashicons dashicons-info-outline"></span>' . esc_html__('Tag Usage Guidelines:', 'jelly-catalog') . '</p>
                <ol>
                    <li>' . esc_html__('Use tags to highlight specific features, materials, or use cases of your product.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Tags should be single words or short phrases separated by commas.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Focus on important keywords that customers might search for.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Avoid duplicate tags and excessive tagging - 5-10 relevant tags are sufficient.', 'jelly-catalog') . '</li>
                </ol>
            </div>',
        ));

        $screen->add_help_tab(array(
            'id'      => 'acf-group_product_field_help',
            'title'   => __('Download', 'jelly-catalog'),
            'content' => '<div class="edit-description">
                <p class="edit-title"><span class="dashicons dashicons-info-outline"></span>' . esc_html__('Downloadable Content Guidelines:', 'jelly-catalog') . '</p>
                <ol>
                    <li>' . esc_html__('Upload high-quality files in supported formats (PDF, DOC, ZIP, etc.).', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Ensure files are virus-free and safe for users to download.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Provide clear file descriptions to help users understand content.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Compress large files to reduce download time and server load.', 'jelly-catalog') . '</li>
                </ol>
            </div>',
        ));

        $screen->add_help_tab(array(
            'id'      => 'product_faq_metabox_help',
            'title'   => __('FAQ', 'jelly-catalog'),
            'content' => '<div class="edit-description">
                <p class="edit-title"><span class="dashicons dashicons-info-outline"></span>' . esc_html__('FAQ Creation Guidelines:', 'jelly-catalog') . '</p>
                <ol>
                    <li>' . esc_html__('Address common customer questions about product features, usage, and specifications.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Write clear, concise questions and detailed, helpful answers.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Organize FAQs in order of importance or frequency of questions.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Regularly update FAQs based on customer feedback and new questions.', 'jelly-catalog') . '</li>
                </ol>
            </div>',
        ));

        $screen->add_help_tab(array(
            'id'      => 'product_attributes_metabox_help',
            'title'   => __('Attributes', 'jelly-catalog'),
            'content' => '<div class="edit-description">
                <p class="edit-title"><span class="dashicons dashicons-info-outline"></span>' . esc_html__('Product Attributes Guidelines:', 'jelly-catalog') . '</p>
                <ol>
                    <li>' . esc_html__('Include all relevant technical specifications and product details.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Use consistent naming conventions for attribute keys.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Provide accurate measurements and specifications.', 'jelly-catalog') . '</li>
                    <li>' . esc_html__('Avoid duplicate attributes and ensure all values are filled in.', 'jelly-catalog') . '</li>
                </ol>
            </div>',
        ));
    }

    public function short_description_box($post)
    {
?>
        <div class="short-description-wrapper">
            <div class="character-count">
                <span class="count-label"><?php esc_html_e('Characters:', 'jelly-catalog'); ?></span>
                <code><span id="excerpt-character-count" class="count-value"><?php echo strlen($post->post_excerpt); ?></span></code>
                <span class="count-separator">/</span>
                <code><span class="count-max">160</span></code>
            </div>
            <?php
            $settings = array(
                'textarea_name' => 'excerpt',
                'quicktags'     => array('buttons' => 'em,strong,link'),
                'tinymce'       => array(
                    'theme_advanced_buttons1' => 'bold,italic,strikethrough,separator,bullist,numlist,separator,blockquote,separator,justifyleft,justifycenter,justifyright,separator,link,unlink,separator,undo,redo,separator',
                    'theme_advanced_buttons2' => '',
                ),
            );

            wp_editor(htmlspecialchars_decode($post->post_excerpt, ENT_QUOTES), 'excerpt', $settings);
            ?>
        </div>
<?php
    }


    public function product_images_box($post)
    {
        // 获取产品画廊图片ID数组
        $gallery_image_ids = get_post_meta($post->ID, '_product_image_gallery', true);
        $gallery_image_ids = !empty($gallery_image_ids) ? explode(',', $gallery_image_ids) : array();

        echo '<div id="jc-gallery">';
        echo '<ul class="product-images">';

        if (!empty($gallery_image_ids)) {
            foreach ($gallery_image_ids as $image_id) {
                $image_id = intval($image_id);
                if ($image_id) {
                    $image_url = wp_get_attachment_image_url($image_id, 'thumbnail');
                    if ($image_url) {
                        echo '<li class="image" data-attachment_id="' . esc_attr($image_id) . '">';
                        echo '<img src="' . esc_url($image_url) . '" alt="" />';
                        echo '<a href="#" class="actions delete" title="' . esc_attr__('Delete image', 'jelly-catalog') . '"></a>';
                        echo '</li>';
                    }
                }
            }
        }

        echo '</ul>';
        echo '<div class="jc-add-image hide-if-no-js">';
        echo '<p><a href="#" data-choose="' . esc_attr__('Add Images to Product Gallery', 'jelly-catalog') . '" 
              data-update="' . esc_attr__('Add to gallery', 'jelly-catalog') . '" 
              data-delete="' . esc_attr__('Delete image', 'jelly-catalog') . '" 
              data-text="' . esc_attr__('Delete', 'jelly-catalog') . '">'
            . __('Add product gallery images', 'jelly-catalog') . '</a></p>';
        echo '</div>';
        echo '<input type="hidden" id="product_image_gallery" name="product_image_gallery" value="' . esc_attr(implode(',', $gallery_image_ids)) . '" />';
        echo '</div>';
    }

    public function product_faq_metabox($post)
    {
        $faqs = get_post_meta($post->ID, '_product_faqs', true);
        $faqs = is_array($faqs) ? $faqs : [];

        // 使用通用 repeater 函数生成 FAQ 表单
        jc_render_repeater_field(array(
            'id' => 'product_faqs',
            'name' => 'product_faqs',
            'items' => $faqs,
            'fields' => array(
                array(
                    'type' => 'text',
                    'name' => 'name',
                    'label' => __('Question:', 'jelly-frame'),
                    'class' => 'repeater-item__key-input'
                ),
                array(
                    'type' => 'textarea',
                    'name' => 'value',
                    'label' => __('Answer:', 'jelly-frame'),
                    'class' => 'repeater-item__value-input'
                )
            )
        ));
    }


    public function product_video_url_box($post)
    {
        $video_url = get_post_meta($post->ID, 'video_url', true);
        echo '<p>';
        echo '<label for="product_video_url">' . __('Video URL', 'jelly-catalog') . ':</label>';
        echo '<input type="url" id="product_video_url" name="product_video_url" value="' . esc_url($video_url) . '" class="large-text" />';
        echo '<span class="description">' . __('Enter the full URL to your product video (YouTube, Vimeo, etc.)', 'jelly-catalog') . '</span>';
        echo '</p>';
    }

    /**
     * 显示产品属性 metabox
     */
    public function product_attributes_metabox($post)
    {
        $attributes = get_post_meta($post->ID, '_product_attributes', true);
        $attributes = is_array($attributes) ? $attributes : [];

        // 使用通用 repeater 函数生成 FAQ 表单
        jc_render_repeater_field(array(
            'id' => 'product_attributes',
            'name' => 'product_attributes',
            'items' => $attributes,
            'fields' => array(
                array(
                    'type' => 'text',
                    'name' => 'name',
                    'label' => __('Name:', 'jelly-frame'),
                    'class' => 'repeater-item__key-input'
                ),
                array(
                    'type' => 'text',
                    'name' => 'value',
                    'label' => __('Value:', 'jelly-frame'),
                    'class' => 'repeater-item__value-input'
                )
            )
        ));

    }

    public function save_meta_boxes($post_id)
    {

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) return;

        // 保存产品画廊图片
        if (!is_woocommerce_activated()) {
            if (isset($_POST['product_image_gallery'])) {
                $gallery_images = sanitize_text_field($_POST['product_image_gallery']);
                update_post_meta($post_id, '_product_image_gallery', $gallery_images);
            }

            if (isset($_POST['product_attributes'])) {
                $raw = $_POST['product_attributes'] ?? [];
                $clean = [];
                foreach ($raw as $item) {
                    $name = sanitize_text_field($item['name'] ?? '');
                    $value = sanitize_text_field($item['value'] ?? '');
                    if ($name && $value) {
                        $clean[] = ['name' => $name, 'value' => $value];
                    }
                }

                update_post_meta($post_id, '_product_attributes', $clean);
            }
        }


        // 保存视频 URL
        if (isset($_POST['product_video_url'])) {
            $video_url = esc_url_raw($_POST['product_video_url']);
            update_post_meta($post_id, '_product_video_url', $video_url);
        }

        if (isset($_POST['product_faqs'])) {
            
            $raw = $_POST['product_faqs'] ?? [];
            $clean = [];

            foreach ($raw as $item) {
                $q = sanitize_text_field($item['name'] ?? '');
                $a = sanitize_textarea_field($item['value'] ?? '');
                if ($q || $a) {
                    $clean[] = ['name' => $q, 'value' => $a];
                }
            }
            
            update_post_meta($post_id, '_product_faqs', $clean);
        }
    }
}
