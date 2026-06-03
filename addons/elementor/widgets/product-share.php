<?php

/**
 * addons\elementor\widgets\product-share.php
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
 * 产品分享组件。
 */
class Product_Share extends Widget_Base
{
    /**
     * 获取组件唯一名称。
     *
     * @return string
     */
    public function get_name(): string
    {
        return 'jc-product-share';
    }

    /**
     * 获取组件标题。
     *
     * @return string
     */
    public function get_title(): string
    {
        return esc_html__('Product Share', 'jelly-catalog');
    }

    /**
     * 获取组件图标。
     *
     * @return string
     */
    public function get_icon(): string
    {
        return 'eicon-share-arrow jelly-engine-icon';
    }

    /**
     * 获取组件分类。
     *
     * @return array
     */
    public function get_categories(): array
    {
        return ['jc-elements-single'];
    }

    /**
     * 获取组件关键词。
     *
     * @return array
     */
    public function get_keywords(): array
    {
        return ['product', 'share', 'social'];
    }

    /**
     * 返回产品分享组件依赖的样式句柄。
     *
     * @return array
     */
    public function get_style_depends(): array
    {
        return ['jelly-catalog-product-share'];
    }

    /**
     * 输出前台内容。
     *
     * @return void
     */
    protected function render(): void
    {
        $product_url = urlencode(get_permalink());
        $product_title = urlencode(get_the_title());
        ?>
<div class="jc-product-share" data-widget="jc-product-share">
    <div class="jc-product-share__title">
        <?php echo esc_html__('Share to:', 'jelly-catalog'); ?>
    </div>
    <div class="jc-product-share__list">
        <a class="jc-product-share__link is-facebook"
            href="https://www.facebook.com/sharer/sharer.php?u=<?php echo esc_attr($product_url); ?>" target="_blank"
            rel="noopener">
            <i class="ri-facebook-fill"></i>
        </a>
        <a class="jc-product-share__link is-twitter"
            href="https://twitter.com/intent/tweet?url=<?php echo esc_attr($product_url); ?>&text=<?php echo esc_attr($product_title); ?>"
            target="_blank" rel="noopener">
            <i class="ri-twitter-x-line"></i>
        </a>
        <a class="jc-product-share__link is-linkedin"
            href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo esc_attr($product_url); ?>&title=<?php echo esc_attr($product_title); ?>"
            target="_blank" rel="noopener">
            <i class="ri-linkedin-fill"></i>
        </a>
        <a class="jc-product-share__link is-whatsapp"
            href="https://api.whatsapp.com/send?text=<?php echo esc_attr($product_title); ?>%20<?php echo esc_attr($product_url); ?>"
            target="_blank" rel="noopener">
            <i class="ri-whatsapp-line"></i>
        </a>
    </div>
</div>
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
<div class="jc-product-share" data-widget="jc-product-share">
    <div class="jc-product-share__title">
        <?php echo esc_html__('Share to:', 'jelly-catalog'); ?>
    </div>
    <div class="jc-product-share__list">
        <a class="jc-product-share__link is-facebook" href="#" aria-disabled="true" onclick="return false;">
            <i class="ri-facebook-fill"></i>
        </a>
        <a class="jc-product-share__link is-twitter" href="#" aria-disabled="true" onclick="return false;">
            <i class="ri-twitter-x-line"></i>
        </a>
        <a class="jc-product-share__link is-linkedin" href="#" aria-disabled="true" onclick="return false;">
            <i class="ri-linkedin-fill"></i>
        </a>
        <a class="jc-product-share__link is-whatsapp" href="#" aria-disabled="true" onclick="return false;">
            <i class="ri-whatsapp-line"></i>
        </a>
    </div>
</div>
<?php
    }
}
