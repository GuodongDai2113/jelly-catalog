<?php

/**
 * addons\elementor\widgets\product-gallery.php
 *
 * @see: https://jellydai.com
 * @author: Jelly Dai <daiguo1003@gmail.com>
 * @created : 2026.01.19 19:23
 */

namespace Jelly_Catalog\Addons\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;

if (!defined('ABSPATH')) {
    exit;
} // 禁止直接访问

class Product_Gallery extends Widget_Base
{
    public function get_name(): string
    {
        return 'product_gallery';
    }

    public function get_title(): string
    {
        return esc_html__('Product Gallery', 'jelly-catalog');
    }

    public function get_icon(): string
    {
        return 'eicon-product-images jelly-engine-icon';
    }

    public function get_categories(): array
    {
        return ['jc-elements-single'];
    }

    public function get_keywords(): array
    {
        return ['product', 'gallery'];
    }

    public function get_style_depends(): array
    {
        return $this->should_use_fallback_assets() ? ['jelly-catalog-product-gallery'] : [];
    }

    public function get_script_depends(): array
    {
        return $this->should_use_fallback_assets() ? ['jelly-catalog-product-gallery'] : [];
    }

    protected function register_controls(): void
    {
        $this->start_controls_section(
            'section_gallery_content',
            [
                'label' => esc_html__('Gallery', 'jelly-catalog'),
            ]
        );

        $this->add_control(
            'visible_thumbs',
            [
                'label' => esc_html__('Visible Thumbnails', 'jelly-catalog'),
                'type' => Controls_Manager::NUMBER,
                'default' => 4,
                'min' => 1,
                'max' => 8,
                'step' => 1,
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_layout_style',
            [
                'label' => esc_html__('Layout', 'jelly-catalog'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'column_gap',
            [
                'label' => esc_html__('Column Gap', 'jelly-catalog'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'rem', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .product-gallery' => '--product-gallery-column-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'thumb_gap',
            [
                'label' => esc_html__('Thumbnail Gap', 'jelly-catalog'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'rem', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .product-gallery' => '--product-gallery-thumb-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_media_style',
            [
                'label' => esc_html__('Media', 'jelly-catalog'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'media_background',
            [
                'label' => esc_html__('Background Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .product-gallery__media, {{WRAPPER}} .product-gallery__viewport, {{WRAPPER}} .product-gallery__slide' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'media_border',
                'selector' => '{{WRAPPER}} .product-gallery__media img, {{WRAPPER}} .product-gallery__slide img, {{WRAPPER}} .product-gallery__viewport',
            ]
        );

        $this->add_responsive_control(
            'media_border_radius',
            [
                'label' => esc_html__('Border Radius', 'jelly-catalog'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'rem', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .product-gallery__media, {{WRAPPER}} .product-gallery__viewport, {{WRAPPER}} .product-gallery__slide, {{WRAPPER}} .product-gallery__media img, {{WRAPPER}} .product-gallery__slide img, {{WRAPPER}} .product-gallery__media iframe, {{WRAPPER}} .product-gallery__slide iframe' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'image_fit',
            [
                'label' => esc_html__('Image Fit', 'jelly-catalog'),
                'type' => Controls_Manager::SELECT,
                'default' => '',
                'options' => [
                    '' => esc_html__('Default', 'jelly-catalog'),
                    'cover' => esc_html__('Cover', 'jelly-catalog'),
                    'contain' => esc_html__('Contain', 'jelly-catalog'),
                ],
                'selectors' => [
                    '{{WRAPPER}} .product-gallery__media img, {{WRAPPER}} .product-gallery__slide img' => 'object-fit: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_thumb_style',
            [
                'label' => esc_html__('Thumbnails', 'jelly-catalog'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'thumb_opacity',
            [
                'label' => esc_html__('Opacity', 'jelly-catalog'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'max' => 1,
                'step' => 0.05,
                'selectors' => [
                    '{{WRAPPER}} .product-gallery__thumb' => 'opacity: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'thumb_active_opacity',
            [
                'label' => esc_html__('Active Opacity', 'jelly-catalog'),
                'type' => Controls_Manager::NUMBER,
                'min' => 0,
                'max' => 1,
                'step' => 0.05,
                'selectors' => [
                    '{{WRAPPER}} .product-gallery__thumb.is-active' => 'opacity: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'thumb_border',
                'selector' => '{{WRAPPER}} .product-gallery__thumb',
            ]
        );

        $this->add_responsive_control(
            'thumb_border_radius',
            [
                'label' => esc_html__('Border Radius', 'jelly-catalog'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'rem', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .product-gallery__thumb, {{WRAPPER}} .product-gallery__thumb img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_nav_style',
            [
                'label' => esc_html__('Arrows', 'jelly-catalog'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'nav_size',
            [
                'label' => esc_html__('Button Size', 'jelly-catalog'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'rem', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .product-gallery__nav' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'nav_icon_size',
            [
                'label' => esc_html__('Icon Size', 'jelly-catalog'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'rem', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .product-gallery__nav' => 'font-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'nav_color',
            [
                'label' => esc_html__('Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .product-gallery__nav' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'nav_background',
            [
                'label' => esc_html__('Background Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .product-gallery__nav' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_dots_style',
            [
                'label' => esc_html__('Dots', 'jelly-catalog'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'dot_size',
            [
                'label' => esc_html__('Size', 'jelly-catalog'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'rem', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .product-gallery__dot' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'dot_gap',
            [
                'label' => esc_html__('Gap', 'jelly-catalog'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'rem', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .product-gallery__dots' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'dot_color',
            [
                'label' => esc_html__('Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .product-gallery__dot' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'dot_active_color',
            [
                'label' => esc_html__('Active Color', 'jelly-catalog'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .product-gallery__dot.is-active' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render(): void
    {
        $post_id = get_the_ID();

        if (!$post_id) {
            return;
        }

        $settings = $this->get_settings_for_display();
        $gallery_items = $this->get_gallery_items($post_id);
        $gallery_count = count($gallery_items);
        $visible_thumbs = min(8, max(1, absint($settings['visible_thumbs'] ?? 4)));
        $main_image_sizes = '(max-width: 768px) 100vw, 50vw';
        $thumb_image_sizes = '100px';
        ?>

<?php if ($gallery_count <= 0): ?>
<div class="product-gallery product-gallery--empty" data-widget="product-gallery">
    <div class="product-gallery__media product-image">
        <i class="ri-image-line"></i>
    </div>
</div>
<?php elseif ($gallery_count === 1): ?>
<?php $item = $gallery_items[0]; ?>
<div class="product-gallery product-gallery--single" data-widget="product-gallery">
    <div class="product-gallery__media product-image <?php echo $item['type'] === 'video' ? 'product-gallery__media--video product-image--video' : ''; ?>">
        <?php if ($item['type'] === 'video'): ?>
        <?php $this->render_gallery_video($item['embed_url']); ?>
        <?php else: ?>
        <?php $this->render_gallery_image($item['image_url'], $item['image_alt'], $item['image_srcset'], $main_image_sizes, 'eager', 'high'); ?>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<div class="product-gallery product-gallery--interactive" data-product-gallery data-widget="product-gallery" data-visible-thumbs="<?php echo esc_attr($visible_thumbs); ?>">
    <div class="product-gallery__thumbs" role="tablist"
        aria-label="<?php echo esc_attr__('Product media thumbnails', 'jelly-catalog'); ?>">
        <div class="product-gallery__thumbs-viewport" data-gallery-thumbs-viewport>
            <div class="product-gallery__thumbs-track" data-gallery-thumbs-track>
                <?php foreach ($gallery_items as $index => $item): ?>
                <div class="product-gallery__thumb <?php echo $index === 0 ? 'is-active' : ''; ?>" type="button"
                    data-gallery-thumb="<?php echo esc_attr($index); ?>"
                    aria-label="<?php echo esc_attr(sprintf(__('Show media %d', 'jelly-catalog'), $index + 1)); ?>"
                    aria-pressed="<?php echo $index === 0 ? 'true' : 'false'; ?>">
                    <?php if ($item['type'] === 'video'): ?>
                    <span class="product-gallery__thumb-media">
                        <?php $this->render_gallery_image($item['thumb_url'], $item['thumb_alt'], '', '', $index === 0 ? 'eager' : 'lazy'); ?>
                        <span class="product-gallery__thumb-badge" aria-hidden="true">
                            <i class="ri-play-fill"></i>
                        </span>
                    </span>
                    <?php else: ?>
                    <?php $this->render_gallery_image($item['thumb_url'], $item['thumb_alt'], $item['thumb_srcset'], $thumb_image_sizes, $index === 0 ? 'eager' : 'lazy'); ?>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="product-gallery__main">
        <div class="product-gallery__viewport" data-gallery-viewport tabindex="0"
            aria-label="<?php echo esc_attr__('Product media gallery', 'jelly-catalog'); ?>">
            <div class="product-gallery__track" data-gallery-track>
                <?php foreach ($gallery_items as $index => $item): ?>
                <div class="product-gallery__slide <?php echo $index === 0 ? 'is-active' : ''; ?>"
                    data-gallery-slide="<?php echo esc_attr($index); ?>"
                    aria-hidden="<?php echo $index === 0 ? 'false' : 'true'; ?>">
                    <?php if ($item['type'] === 'video'): ?>
                    <div class="product-gallery__video">
                        <?php $this->render_gallery_video($item['embed_url']); ?>
                    </div>
                    <?php else: ?>
                    <?php $this->render_gallery_image($item['image_url'], $item['image_alt'], $item['image_srcset'], $main_image_sizes, $index === 0 ? 'eager' : 'lazy', $index === 0 ? 'high' : ''); ?>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="product-gallery__nav product-gallery__nav--prev" type="button" data-gallery-prev
            aria-label="<?php echo esc_attr__('Previous media', 'jelly-catalog'); ?>" disabled>
            <i class="ri-arrow-left-s-line"></i>
        </div>
        <div class="product-gallery__nav product-gallery__nav--next" type="button" data-gallery-next
            aria-label="<?php echo esc_attr__('Next media', 'jelly-catalog'); ?>">
            <i class="ri-arrow-right-s-line"></i>
        </div>

        <div class="product-gallery__dots" role="tablist"
            aria-label="<?php echo esc_attr__('Product media pagination', 'jelly-catalog'); ?>">
            <?php foreach ($gallery_items as $index => $item): ?>
            <div class="product-gallery__dot <?php echo $index === 0 ? 'is-active' : ''; ?>" type="button"
                data-gallery-dot="<?php echo esc_attr($index); ?>"
                aria-label="<?php echo esc_attr(sprintf(__('Go to media %d', 'jelly-catalog'), $index + 1)); ?>"
                aria-pressed="<?php echo $index === 0 ? 'true' : 'false'; ?>">
                <span
                    class="screen-reader-text"><?php echo esc_html(sprintf(__('Media %d', 'jelly-catalog'), $index + 1)); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>
<?php
    }

    protected function content_template(): void
    {
        $placeholder_url = function_exists('jc_placeholder_img_src') ? jc_placeholder_img_src() : '';
        $preview_items = [];

        for ($index = 1; $index <= 3; $index++) {
            $preview_items[] = [
                'thumb_url' => $placeholder_url,
                'thumb_alt' => sprintf(esc_html__('Preview image %d', 'jelly-catalog'), $index),
                'image_url' => $placeholder_url,
                'image_alt' => sprintf(esc_html__('Preview image %d', 'jelly-catalog'), $index),
            ];
        }
        ?>
<#
var galleryItems = <?php echo wp_json_encode($preview_items); ?>;
var visibleThumbs = Math.max(1, Math.min(8, parseInt(settings.visible_thumbs, 10) || 4));
#>
<div class="product-gallery product-gallery--interactive" data-product-gallery data-widget="product-gallery" data-visible-thumbs="{{ visibleThumbs }}">
    <div class="product-gallery__thumbs" role="tablist"
        aria-label="<?php echo esc_attr__('Product media thumbnails', 'jelly-catalog'); ?>">
        <div class="product-gallery__thumbs-viewport" data-gallery-thumbs-viewport>
            <div class="product-gallery__thumbs-track" data-gallery-thumbs-track>
                <# _.each(galleryItems, function(item, index) { #>
                <div class="product-gallery__thumb <# if (index === 0) { #>is-active<# } #>" type="button"
                    data-gallery-thumb="{{ index }}"
                    aria-label="<?php echo esc_attr__('Show media', 'jelly-catalog'); ?> {{ index + 1 }}"
                    aria-pressed="<# if (index === 0) { #>true<# } else { #>false<# } #>">
                    <img src="{{ item.thumb_url }}" alt="{{ item.thumb_alt }}" loading="<# if (index === 0) { #>eager<# } else { #>lazy<# } #>">
                </div>
                <# }); #>
            </div>
        </div>
    </div>

    <div class="product-gallery__main">
        <div class="product-gallery__viewport" data-gallery-viewport tabindex="0"
            aria-label="<?php echo esc_attr__('Product media gallery', 'jelly-catalog'); ?>">
            <div class="product-gallery__track" data-gallery-track>
                <# _.each(galleryItems, function(item, index) { #>
                <div class="product-gallery__slide <# if (index === 0) { #>is-active<# } #>"
                    data-gallery-slide="{{ index }}"
                    aria-hidden="<# if (index === 0) { #>false<# } else { #>true<# } #>">
                    <img src="{{ item.image_url }}" alt="{{ item.image_alt }}" loading="<# if (index === 0) { #>eager<# } else { #>lazy<# } #>">
                </div>
                <# }); #>
            </div>
        </div>

        <div class="product-gallery__nav product-gallery__nav--prev" type="button" data-gallery-prev
            aria-label="<?php echo esc_attr__('Previous media', 'jelly-catalog'); ?>" disabled>
            <i class="ri-arrow-left-s-line"></i>
        </div>
        <div class="product-gallery__nav product-gallery__nav--next" type="button" data-gallery-next
            aria-label="<?php echo esc_attr__('Next media', 'jelly-catalog'); ?>">
            <i class="ri-arrow-right-s-line"></i>
        </div>

        <div class="product-gallery__dots" role="tablist"
            aria-label="<?php echo esc_attr__('Product media pagination', 'jelly-catalog'); ?>">
            <# _.each(galleryItems, function(item, index) { #>
            <div class="product-gallery__dot <# if (index === 0) { #>is-active<# } #>" type="button"
                data-gallery-dot="{{ index }}"
                aria-label="<?php echo esc_attr__('Go to media', 'jelly-catalog'); ?> {{ index + 1 }}"
                aria-pressed="<# if (index === 0) { #>true<# } else { #>false<# } #>">
                <span class="screen-reader-text"><?php echo esc_html__('Media', 'jelly-catalog'); ?> {{ index + 1 }}</span>
            </div>
            <# }); #>
        </div>
    </div>
</div>
<?php
    }

    private function should_use_fallback_assets(): bool
    {
        return !class_exists('\JC_Enqueue') || !\JC_Enqueue::is_jelly_frame_theme();
    }

    private function get_gallery_items(int $post_id): array
    {
        $featured_id = get_post_thumbnail_id($post_id);
        $gallery_ids = $this->get_gallery_ids($post_id);
        $all_ids = array_values(array_unique(array_filter(array_merge([$featured_id], $gallery_ids))));
        $video_url = get_post_meta($post_id, '_product_videourl', true);
        $embed_video_url = is_string($video_url) ? $this->convert_youtube_url_to_embed($video_url) : '';
        $youtube_id = is_string($video_url) ? $this->get_youtube_video_id($video_url) : '';
        $default_image_alt = get_the_title($post_id) . ' product image';
        $gallery_items = [];

        if (!empty($embed_video_url) && !empty($youtube_id)) {
            $gallery_items[] = [
                'type' => 'video',
                'thumb_url' => sprintf('https://img.youtube.com/vi/%s/hqdefault.jpg', rawurlencode($youtube_id)),
                'thumb_alt' => esc_html__('Video Thumbnail', 'jelly-catalog'),
                'embed_url' => $embed_video_url,
            ];
        }

        foreach ($all_ids as $id) {
            $thumb_url = wp_get_attachment_image_url($id, 'thumbnail');
            $image_url = wp_get_attachment_url($id);

            if (empty($thumb_url) || empty($image_url)) {
                continue;
            }

            $attachment_alt = get_post_meta($id, '_wp_attachment_image_alt', true) ?: $default_image_alt;

            $gallery_items[] = [
                'type' => 'image',
                'thumb_url' => $thumb_url,
                'thumb_alt' => $attachment_alt,
                'thumb_srcset' => function_exists('wp_get_attachment_image_srcset') ? wp_get_attachment_image_srcset($id, 'thumbnail') : '',
                'image_url' => $image_url,
                'image_alt' => $attachment_alt,
                'image_srcset' => function_exists('wp_get_attachment_image_srcset') ? wp_get_attachment_image_srcset($id, 'full') : '',
            ];
        }

        return $gallery_items;
    }

    private function get_gallery_ids(int $post_id): array
    {
        $gallery_ids = get_post_meta($post_id, '_product_image_gallery', true);

        if (is_array($gallery_ids)) {
            return array_map('intval', $gallery_ids);
        }

        if (is_string($gallery_ids) && $gallery_ids !== '') {
            return array_map('intval', explode(',', $gallery_ids));
        }

        return [];
    }

    private function render_gallery_image($src, $alt, $srcset = '', $sizes = '', $loading = 'lazy', $fetchpriority = ''): void
    {
        ?>
<img src="<?php echo esc_url($src); ?>" alt="<?php echo esc_attr($alt); ?>"
    <?php if (!empty($srcset)): ?>srcset="<?php echo esc_attr($srcset); ?>" <?php endif; ?>
    <?php if (!empty($sizes)): ?>sizes="<?php echo esc_attr($sizes); ?>" <?php endif; ?>
    loading="<?php echo esc_attr($loading); ?>" <?php if (!empty($fetchpriority)): ?>
    fetchpriority="<?php echo esc_attr($fetchpriority); ?>" <?php endif; ?>>
<?php
    }

    private function render_gallery_video($embed_url): void
    {
        ?>
<iframe src="<?php echo esc_url($embed_url); ?>?rel=0&autoplay=0" frameborder="0" allowfullscreen></iframe>
<?php
    }

    private function convert_youtube_url_to_embed(string $url): string
    {
        $youtube_id = $this->get_youtube_video_id($url);

        return $youtube_id ? 'https://www.youtube.com/embed/' . $youtube_id : '';
    }

    private function get_youtube_video_id(string $url): string
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }

        $parts = wp_parse_url($url);

        if (!is_array($parts) || empty($parts['host'])) {
            return '';
        }

        $host = strtolower($parts['host']);
        $allowed_hosts = ['youtube.com', 'youtu.be', 'www.youtube.com'];

        if (!in_array($host, $allowed_hosts, true)) {
            return '';
        }

        if ($host === 'youtu.be') {
            $video_id = trim($parts['path'] ?? '', '/');
        } elseif (!empty($parts['path']) && strpos($parts['path'], '/embed/') === 0) {
            $video_id = trim(substr($parts['path'], 7), '/');
        } else {
            parse_str($parts['query'] ?? '', $query);
            $video_id = $query['v'] ?? '';
        }

        return is_string($video_id) && preg_match('/^[a-zA-Z0-9_-]{11}$/', $video_id) ? $video_id : '';
    }
}
