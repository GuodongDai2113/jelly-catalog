<?php

/**
 * addons\elementor\widgets\product-content.php
 *
 * @see: https://jellydai.com
 * @author: Jelly Dai <daiguo1003@gmail.com>
 * @created : 2026.06.03 00:00
 */

namespace Jelly_Catalog\Addons\Elementor\Widgets;

use Elementor\Controls_Manager;
use ElementorPro\Modules\ThemeBuilder\Widgets\Post_Content;
use ElementorPro\Plugin;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * 产品详情内容组件。
 */
class Product_Content extends Post_Content
{
    /**
     * 获取组件唯一名称。
     *
     * @return string
     */
    public function get_name()
    {
        return 'jc-product-content';
    }

    /**
     * 获取组件标题。
     *
     * @return string
     */
    public function get_title()
    {
        return esc_html__('Product Content', 'elementor-pro');
    }

    /**
     * 获取组件分类。
     *
     * @return array
     */
    public function get_categories()
    {
        return ['jc-elements-single'];
    }

    /**
     * 获取组件关键词。
     *
     * @return array
     */
    public function get_keywords()
    {
        return ['content', 'post', 'product'];
    }

    /**
     * 获取分组名称。
     *
     * @return string
     */
    public function get_group_name()
    {
        return 'jelly';
    }

    /**
     * 返回产品详情组件依赖的样式句柄。
     *
     * @return array
     */
    public function get_style_depends(): array
    {
        return [
            'jelly-catalog-product-content',
            'jelly-catalog-product-faq',
        ];
    }

    /**
     * 返回产品详情组件依赖的脚本句柄。
     *
     * @return array
     */
    public function get_script_depends(): array
    {
        return ['jelly-catalog-product-content'];
    }

    /**
     * 是否保留 Elementor 内层包裹。
     *
     * @return bool
     */
    public function has_widget_inner_wrapper(): bool
    {
        return !Plugin::elementor()->experiments->is_feature_active('e_optimized_markup');
    }

    /**
     * 注册组件控制项。
     *
     * @return void
     */
    protected function register_controls(): void
    {
        parent::register_controls();

        $this->start_controls_section(
            'section_product_detail',
            [
                'label' => esc_html__('Detail Layout', 'jelly-catalog'),
            ]
        );

        $this->add_control(
            'show_faq',
            [
                'label' => esc_html__('Show FAQ Section', 'jelly-catalog'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'jelly-catalog'),
                'label_off' => esc_html__('No', 'jelly-catalog'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_inquiry',
            [
                'label' => esc_html__('Show Inquiry Section', 'jelly-catalog'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'jelly-catalog'),
                'label_off' => esc_html__('No', 'jelly-catalog'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * 输出前台内容。
     *
     * @return void
     */
    protected function render(): void
    {
        $post_id = get_the_ID();
        $settings = $this->get_settings_for_display();

        if (!$post_id) {
            return;
        }

        $faq_items = jc_get_product_faq_items($post_id);
        $show_faq = ($settings['show_faq'] ?? 'yes') === 'yes';
        $show_inquiry = ($settings['show_inquiry'] ?? 'yes') === 'yes';
        ?>
<section class="jc-product-detail">
    <div class="jc-container">
        <nav class="jc-product-detail__tabs" data-jc-product-detail-nav
            aria-label="<?php echo esc_attr__('Product detail navigation', 'jelly-catalog'); ?>"></nav>

        <article class="jc-product-detail__sections prose" data-jc-product-detail-sections>
            <section class="jc-product-detail__section">
                <h2 id="jc-product-detail-description"><?php echo esc_html__('Description', 'jelly-catalog'); ?></h2>
                <?php parent::render(); ?>
            </section>

            <?php if ($show_faq && !empty($faq_items)): ?>
            <section class="jc-product-detail__section">
                <h2 id="jc-product-detail-faq"><?php echo esc_html__('FAQ', 'jelly-catalog'); ?></h2>
                <div class="jc-product-faq">
                    <?php foreach ($faq_items as $faq_item): ?>
                    <article class="jc-product-faq-item">
                        <h3 class="jc-product-faq-question"><?php echo esc_html($faq_item['name']); ?></h3>
                        <div class="jc-product-faq-answer">
                            <?php echo wp_kses_post($faq_item['value']); ?>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <?php if ($show_inquiry && function_exists('the_jelly_form')): ?>
            <section class="jc-product-detail__section">
                <h2 id="jc-product-detail-inquiry"><?php echo esc_html__('Request Quote', 'jelly-catalog'); ?></h2>
                <div class="jc-product-detail__inquiry">
                    <p>
                        <?php echo esc_html__('Tell us your specifications, quantity, or application scenario and we will reply with a tailored quote.', 'jelly-catalog'); ?>
                    </p>
                    <?php the_jelly_form(['layout' => 'two-column']); ?>
                </div>
            </section>
            <?php endif; ?>
        </article>
    </div>
</section>
<?php
    }

    /**
     * 输出编辑器预览模板。
     *
     * @return void
     */
    protected function content_template(): void
    {
        ?>
<section class="jc-product-detail">
    <div class="jc-container">
        <nav class="jc-product-detail__tabs" data-jc-product-detail-nav
            aria-label="<?php echo esc_attr__('Product detail navigation', 'jelly-catalog'); ?>"></nav>

        <article class="jc-product-detail__sections prose" data-jc-product-detail-sections>
            <section class="jc-product-detail__section">
                <h2 id="jc-product-detail-description"><?php echo esc_html__('Description', 'jelly-catalog'); ?></h2>
                <p><?php echo esc_html__('Use the product editor to write the long description. This widget will render it in the same detail layout as jelly-frame.', 'jelly-catalog'); ?>
                </p>
            </section>

            <section class="jc-product-detail__section">
                <h2 id="jc-product-detail-faq"><?php echo esc_html__('FAQ', 'jelly-catalog'); ?></h2>
                <div class="jc-product-faq">
                    <article class="jc-product-faq-item">
                        <h3 class="jc-product-faq-question">
                            <?php echo esc_html__('What can be configured here?', 'jelly-catalog'); ?></h3>
                        <div class="jc-product-faq-answer">
                            <?php echo esc_html__('The detail navigation is generated automatically from the section headings below.', 'jelly-catalog'); ?>
                        </div>
                    </article>
                    <article class="jc-product-faq-item">
                        <h3 class="jc-product-faq-question">
                            <?php echo esc_html__('Does it use product FAQ data?', 'jelly-catalog'); ?></h3>
                        <div class="jc-product-faq-answer">
                            <?php echo esc_html__('Yes. On the frontend it reads the current product FAQ data and renders it with the same structure as jelly-frame.', 'jelly-catalog'); ?>
                        </div>
                    </article>
                </div>
            </section>
        </article>
    </div>
</section>
<?php
    }
}
