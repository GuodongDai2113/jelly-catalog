<?php

/**
 * addons\elementor\widgets\product-loop.php
 *
 * @see: https://jellydai.com
 * @author: Jelly Dai <daiguo1003@gmail.com>
 * @created : 2026.06.03 00:00
 */

namespace Jelly_Catalog\Addons\Elementor\Widgets;

use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
} // 绂佹鐩存帴璁块棶

/**
 * 产品循环卡片组件。
 */
class Product_Loop extends Widget_Base
{
    /**
     * 获取组件唯一名称。
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'jc-product-loop';
    }

    /**
     * 获取组件标题。
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Product Loop Card', 'jelly-catalog');
    }

    /**
     * 获取组件图标。
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-posts-grid jelly-engine-icon';
    }

    /**
     * 获取组件分类。
     *
     * @return array
     */
    public function get_categories(): array
    {
        return ['jc-elements-archive'];
    }

    /**
     * 获取组件关键词。
     *
     * @return array
     */
    public function get_keywords(): array
    {
        return ['product', 'loop', 'archive', 'card'];
    }

    /**
     * 返回产品循环卡片组件依赖的样式句柄。
     *
     * @return array
     */
    public function get_style_depends(): array
    {
        return ['jelly-catalog-product-loop'];
    }

    /**
     * 输出前台内容。
     *
     * @return void
     */
    protected function render(): void
    {
        $product_id = get_the_ID();

        if (!$product_id) {
            $this->render_preview_card();
            return;
        }

        $permalink = get_permalink($product_id);
        $title = get_the_title($product_id);
        $image_html = has_post_thumbnail($product_id)
            ? get_the_post_thumbnail(
                $product_id,
                'large',
                [
                    'class' => 'jc-product-loop__image',
                    'loading' => 'lazy',
                    'alt' => esc_attr($title),
                ]
            )
            : sprintf(
                '<img class="jc-product-loop__image" src="%s" alt="%s" loading="lazy">',
                esc_url(jc_placeholder_img_src()),
                esc_attr($title)
            );
        ?>
<article class="jc-product-loop" data-widget="jc-product-loop">
    <div class="jc-product-loop__image-wrap">
        <a href="<?php echo esc_url($permalink); ?>" aria-label="<?php echo esc_attr($title); ?>">
            <?php echo $image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped?>
        </a>
    </div>

    <div class="jc-product-loop__content">
        <a href="<?php echo esc_url($permalink); ?>">
            <h2 class="jc-product-loop__title"><?php echo esc_html($title); ?></h2>
        </a>
    </div>
</article>
<?php
    }

    /**
     * 输出编辑器预览模板。
     *
     * @return void
     */
    protected function content_template(): void
    {
        $placeholder_url = function_exists('jc_placeholder_img_src') ? jc_placeholder_img_src() : '';
        ?>
<article class="jc-product-loop" data-widget="jc-product-loop">
    <div class="jc-product-loop__image-wrap">
        <a href="#" aria-disabled="true" onclick="return false;">
            <img class="jc-product-loop__image" src="<?php echo esc_url($placeholder_url); ?>"
                alt="<?php echo esc_attr__('Preview Product', 'jelly-catalog'); ?>" loading="lazy">
        </a>
    </div>

    <div class="jc-product-loop__content">
        <a href="#" aria-disabled="true" onclick="return false;">
            <h2 class="jc-product-loop__title"><?php echo esc_html__('Preview Product Title', 'jelly-catalog'); ?></h2>
        </a>
    </div>
</article>
<?php
    }

    /**
     * 输出无上下文时的预览卡片。
     *
     * @return void
     */
    private function render_preview_card(): void
    {
        $placeholder_url = function_exists('jc_placeholder_img_src') ? jc_placeholder_img_src() : '';
        ?>
<article class="jc-product-loop" data-widget="jc-product-loop">
    <div class="jc-product-loop__image-wrap">
        <span>
            <img class="jc-product-loop__image" src="<?php echo esc_url($placeholder_url); ?>"
                alt="<?php echo esc_attr__('Preview Product', 'jelly-catalog'); ?>" loading="lazy">
        </span>
    </div>

    <div class="jc-product-loop__content">
        <h2 class="jc-product-loop__title"><?php echo esc_html__('Preview Product Title', 'jelly-catalog'); ?></h2>
    </div>
</article>
<?php
    }
}
