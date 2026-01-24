<?php

/**
 * includes/metabox/class-jc-product-cat-banner-metabox.php
 * 
 * Product Category Banner Metabox
 */

if (! defined('ABSPATH')) exit; // 禁止直接访问

class JC_Product_Cat_Banner_Metabox
{

    public function __construct()
    {
        // add_action('product_cat_add_form_fields', array($this, 'add_banner_field'));
        add_action('product_cat_edit_form_fields', array($this, 'edit_banner_field'));
        add_action('edited_product_cat', array($this, 'save_banner_field'));
        // add_action('create_product_cat', array($this, 'save_banner_field'));
    }

    /**
     * 在添加分类页面添加横幅图字段
     */
    public function add_banner_field()
    {
?>
        <div class="form-field">
            <label for="banner_id"><?php esc_html_e('Category Banner', 'jelly-catalog'); ?></label>
            <div id="banner_id_container">
                <input type="hidden" name="banner_id" id="banner_id" value="">
                <div class="banner-preview" style="display: none;">
                    <img src="" alt="" style="max-width: 150px; height: auto;" />
                </div>
                <p>
                    <button class="button button-secondary"
                        id="banner_id_button"><?php esc_html_e('Select Banner', 'jelly-catalog'); ?></button>
                    <button class="button button-secondary remove-banner"
                        style="display: none;"><?php esc_html_e('Remove Banner', 'jelly-catalog'); ?></button>
                </p>
            </div>
            <p class="description"><?php esc_html_e('Select a banner image for this product category.', 'jelly-catalog'); ?></p>
        </div>
    <?php
    }

    /**
     * 在编辑分类页面添加横幅图字段
     */
    public function edit_banner_field($term)
    {
        $banner_id = get_term_meta($term->term_id, 'banner_id', true);
        $banner_url = $banner_id ? wp_get_attachment_url($banner_id) : '';

        echo wp_nonce_field('jc_save_product_cat_banner', 'jc_product_cat_banner_nonce', true, false);

    ?>
        <tr class="form-field">
            <th scope="row" valign="top">
                <label for="banner_id"><?php esc_html_e('Category Banner', 'jelly-catalog'); ?></label>
            </th>
            <td>
                <div id="banner_id_container">
                    <input type="hidden" name="banner_id" id="banner_id" value="<?php echo esc_attr($banner_id); ?>">
                    <div class="banner-preview" style="<?php echo $banner_id ? '' : 'display: none;'; ?>">
                        <img src="<?php echo esc_url($banner_url); ?>" alt="" />
                    </div>
                    <p>
                        <button class="button button-secondary"
                            id="banner_id_button"><?php esc_html_e('Select Banner', 'jelly-catalog'); ?></button>
                        <button class="button button-secondary remove-banner"
                            style="<?php echo $banner_id ? '' : 'display: none;'; ?>"><?php esc_html_e('Remove Banner', 'jelly-catalog'); ?></button>
                    </p>
                </div>
                <p class="description"><?php esc_html_e('Select a banner image for this product category.', 'jelly-catalog'); ?>
                </p>
            </td>
        </tr>
<?php
    }

    /**
     * 保存横幅图字段
     */
    public function save_banner_field($term_id)
    {
        if (!current_user_can('manage_categories')) {
            return;
        }

        if (
            !isset($_POST['jc_product_cat_banner_nonce']) ||
            !wp_verify_nonce($_POST['jc_product_cat_banner_nonce'], 'jc_save_product_cat_banner')
        ) {
            return;
        }

        if (isset($_POST['banner_id'])) {
            $banner_id = absint($_POST['banner_id']);
            update_term_meta($term_id, 'banner_id', $banner_id);
        }
    }
}
