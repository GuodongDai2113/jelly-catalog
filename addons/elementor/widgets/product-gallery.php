<?php

namespace Jelly_Catalog\Addons\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Icons_Manager;

if (!defined('ABSPATH')) exit; // 禁止直接访问

class Product_Gallery extends Widget_Base
{

    public function get_name()
    {
        return 'product_gallery';
    }

    public function get_title()
    {
        return esc_html__('Product Gallery', 'jelly-catalog');
    }

    public function get_icon()
    {
        return 'eicon-product-images';
    }

    public function get_categories()
    {
        return ['jc-elements-single'];
    }

    public function get_keywords()
    {
        return ['product', 'gallery'];
    }

    public function get_style_depends()
    {
        return [];
    }

    protected function register_controls()
    {
        $this->start_controls_section(
            'section_content',
            [
                'label' => esc_html__('Content', 'jelly-catalog'),
            ]
        );


        $this->end_controls_section();

        $this->start_controls_section(
            'section_style',
            [
                'label' => esc_html__('Style', 'jelly-catalog'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );



        $this->end_controls_section();
    }

    protected function render()
    {
        // 获取当前产品ID
        $post_id = get_the_ID();

        // 获取特色图像ID
        $featured_id = get_post_thumbnail_id($post_id);

        // 获取产品画廊图像IDs
        $gallery_ids = get_post_meta($post_id, '_product_image_gallery', true);
        $gallery_ids = $gallery_ids ? array_map('intval', explode(',', $gallery_ids)) : [];

        // 合并所有图像IDs并去重
        $all_ids = array_values(array_unique(array_filter(array_merge([$featured_id], $gallery_ids))));

        // 获取产品视频URL
        $video_url = get_post_meta($post_id, '_product_videourl', true);
        $embed_video_url = $video_url ? $this->convert_youtube_url_to_embed($video_url) : '';
        $youtube_id = $video_url ? $this->get_youtube_video_id($video_url) : '';
?>
        <?php if (!empty($all_ids)): ?>
            <div class="jc-product-gallery">
                <div class="swiper jc-gallery-top">
                    <div class="swiper-wrapper">
                        <?php if (!empty($video_url)): ?>
                            <div class="swiper-slide">
                                <iframe width="100%" height="100%" src="<?php echo esc_url($embed_video_url); ?>?rel=0&autoplay=0" frameborder="0" allowfullscreen></iframe>
                            </div>
                        <?php endif; ?>
                        <?php foreach ($all_ids as $id):
                            $img_url = wp_get_attachment_url($id); ?>
                            <div class="swiper-slide">
                                <img src="<?php echo esc_url($img_url); ?>" alt="">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                </div>
                <?php if (count($all_ids) > 1): ?>
                    <div thumbsSlider="" class="swiper jc-gallery-thumbs">
                        <div class="swiper-wrapper">
                            <?php if (!empty($video_url)): ?>
                                <div class="swiper-slide">
                                    <img src="https://img.youtube.com/vi/<?php echo esc_attr($youtube_id); ?>/hqdefault.jpg" alt="<?php esc_html__('Vieo Thumbnail', 'jelly-frame'); ?>">
                                </div>
                            <?php endif; ?>
                            <?php foreach ($all_ids as $id):
                                $thumb_url = wp_get_attachment_image_url($id, 'thumbnail'); ?>
                                <div class="swiper-slide">
                                    <img src="<?php echo esc_url($thumb_url); ?>" alt="">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <?php else:?>
                <div class="jc-product-no-gallery">
                    <img src="<?php echo JELLY_CATALOG_PLUGIN_URL.'/assets/images/placeholder.webp' ?>" alt="placeholder">
                </div>
        <?php endif; ?>
<?php
    }

    // 将YouTube URL转换为嵌入URL
    private function convert_youtube_url_to_embed($url)
    {
        if (preg_match('/youtube\.com\/watch\?v=([^\&]+)/', $url, $matches)) {
            return 'https://www.youtube.com/embed/' . $matches[1];
        } elseif (preg_match('/youtu\.be\/([^\?]+)/', $url, $matches)) {
            return 'https://www.youtube.com/embed/' . $matches[1];
        } elseif (preg_match('/youtube\.com\/embed\/([^\?]+)/', $url, $matches)) {
            return $url;
        }
        return $url;
    }

    // 获取YouTube视频ID
    private function get_youtube_video_id($url)
    {
        if (preg_match('/youtube\.com\/watch\?v=([^\&]+)/', $url, $matches)) {
            return $matches[1];
        } elseif (preg_match('/youtu\.be\/([^\?]+)/', $url, $matches)) {
            return $matches[1];
        } elseif (preg_match('/youtube\.com\/embed\/([^\?]+)/', $url, $matches)) {
            return $matches[1];
        }
        return '';
    }
}
