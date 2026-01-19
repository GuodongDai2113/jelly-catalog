<?php

/**
 * addons\elementor\widgets\product-cat-nav.php
 * 
 * @see: https://jellydai.com
 * @author: Jelly Dai <daiguo1003@gmail.com>
 * @created : 2026.01.19 21:58
 */

namespace Jelly_Catalog\Addons\Elementor\Widgets;

use Elementor\Widget_Base;


if (! defined('ABSPATH')) exit; // 禁止直接访问

class Product_Cat_Nav extends Widget_Base
{
    public function get_name()
    {
        return 'product_cat_nav';
    }

    public function get_title()
    {
        return __('Product Category Nav', 'product-cat-nav');
    }

    public function get_icon()
    {
        return 'eicon-tabs';
    }

    public function get_categories()
    {
        return ['general'];
    }

    public function get_keywords()
    {
        return ['product', 'category', 'nav', 'tabs'];
    }

    protected function register_controls()
    {
        $this->start_controls_section(
            'section_content',
            [
                'label' => __('Content', 'product-cat-nav'),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'hide_empty',
            [
                'label'        => __('Hide empty categories', 'product-cat-nav'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __('Yes', 'product-cat-nav'),
                'label_off'    => __('No', 'product-cat-nav'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->end_controls_section();
    }

    private function get_current_top_term_id()
    {
        // 分类归档页：直接高亮当前顶级分类（如果是子分类，自动追溯到顶级）
        $qo = get_queried_object();
        if ($qo && isset($qo->taxonomy) && $qo->taxonomy === 'product_cat' && !empty($qo->term_id)) {
            $term = get_term((int)$qo->term_id, 'product_cat');
            if ($term && !is_wp_error($term)) {
                while (!empty($term->parent)) {
                    $term = get_term((int)$term->parent, 'product_cat');
                    if (!$term || is_wp_error($term)) break;
                }
                if ($term && !is_wp_error($term)) return (int)$term->term_id;
            }
            return (int)$qo->term_id;
        }

        // 单产品页：取产品第一个分类的顶级父类高亮
        if (is_singular('product')) {
            $terms = get_the_terms(get_the_ID(), 'product_cat');
            if (!empty($terms) && !is_wp_error($terms)) {
                $term = $terms[0];
                $top = $term;
                while (!empty($top->parent)) {
                    $top = get_term((int)$top->parent, 'product_cat');
                    if (!$top || is_wp_error($top)) break;
                }
                if ($top && !is_wp_error($top)) return (int)$top->term_id;
                return (int)$term->term_id;
            }
        }

        return 0;
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();

        if (!taxonomy_exists('product_cat')) {
            return;
        }

        $hide_empty = ($settings['hide_empty'] === 'yes');

        $terms = get_terms([
            'taxonomy'   => 'product_cat',
            'parent'     => 0,
            'hide_empty' => $hide_empty,
            'orderby'    => 'menu_order',
            'order'      => 'ASC',
        ]);

        if (empty($terms) || is_wp_error($terms)) {
            return;
        }

        $current_top_id = $this->get_current_top_term_id();
?>
        <div class="jc-cat-nav">
            <?php foreach ($terms as $term): ?>
                <?php
                $link = get_term_link($term, 'product_cat');
                if (is_wp_error($link)) continue;

                $is_active = ($current_top_id && (int)$term->term_id === (int)$current_top_id);
                ?>
                <a
                    class="jc-cat-nav__tab <?php echo $is_active ? 'is-active' : ''; ?>"
                    href="<?php echo esc_url($link); ?>"
                    role="tab"
                    aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>">
                    <?php echo esc_html($term->name); ?>
                </a>
            <?php endforeach; ?>
        </div>
<?php
    }
}
