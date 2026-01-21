<?php

/**
 * includes/metabox/class-jc-product-cat-thumbnail-metabox.php
 * 
 * Product Category Thumbnail Metabox
 */

if (! defined('ABSPATH')) exit; // 禁止直接访问

class JC_Product_Cat_Thumbnail_Metabox
{

    public function __construct()
    {
        // add_action('product_cat_add_form_fields', array($this, 'add_thumbnail_field'));
        add_action('product_cat_edit_form_fields', array($this, 'edit_thumbnail_field'));
        add_action('edited_product_cat', array($this, 'save_thumbnail_field'));
        // add_action('create_product_cat', array($this, 'save_thumbnail_field'));
    }

    /**
     * 在添加分类页面添加缩略图字段
     */
    public function add_thumbnail_field()
    {
?>
        <div class="form-field">
            <label for="thumbnail_id"><?php esc_html_e('Category Thumbnail', 'jelly-catalog'); ?></label>
            <div id="thumbnail_id_container">
                <input type="hidden" name="thumbnail_id" id="thumbnail_id" value="">
                <div class="thumbnail-preview" style="display: none;">
                    <img src="" alt="" style="max-width: 150px; height: auto;" />
                </div>
                <p>
                    <button class="button button-secondary" id="thumbnail_id_button"><?php esc_html_e('Select Thumbnail', 'jelly-catalog'); ?></button>
                    <button class="button button-secondary remove-thumbnail" style="display: none;"><?php esc_html_e('Remove Thumbnail', 'jelly-catalog'); ?></button>
                </p>
            </div>
            <p class="description"><?php esc_html_e('Select a thumbnail for this product category.', 'jelly-catalog'); ?></p>
        </div>
    <?php
    }

    /**
     * 在编辑分类页面添加缩略图字段
     */
    public function edit_thumbnail_field($term)
    {
        $thumbnail_id = get_term_meta($term->term_id, 'thumbnail_id', true);
        $thumbnail_url = $thumbnail_id ? wp_get_attachment_url($thumbnail_id) : '';
    ?>
        <tr class="form-field">
            <th scope="row" valign="top">
                <label for="thumbnail_id"><?php esc_html_e('Category Thumbnail', 'jelly-catalog'); ?></label>
            </th>
            <td>
                <div id="thumbnail_id_container">
                    <input type="hidden" name="thumbnail_id" id="thumbnail_id" value="<?php echo esc_attr($thumbnail_id); ?>">
                    <div class="thumbnail-preview" <?php echo $thumbnail_id ? '' : 'display: none;'; ?>>
                        <img src="<?php echo esc_url($thumbnail_url); ?>" alt=""/>
                    </div>
                    <p>
                        <button class="button button-secondary" id="thumbnail_id_button"><?php esc_html_e('Select Thumbnail', 'jelly-catalog'); ?></button>
                        <button class="button button-secondary remove-thumbnail" style="<?php echo $thumbnail_id ? '' : 'display: none;'; ?>"><?php esc_html_e('Remove Thumbnail', 'jelly-catalog'); ?></button>
                    </p>
                </div>
                <p class="description"><?php esc_html_e('Select a thumbnail for this product category.', 'jelly-catalog'); ?></p>
            </td>
        </tr>
<?php
    }

    /**
     * 保存缩略图字段
     */
    public function save_thumbnail_field($term_id)
    {
        if (isset($_POST['thumbnail_id'])) {
            $thumbnail_id = absint($_POST['thumbnail_id']);
            update_term_meta($term_id, 'thumbnail_id', $thumbnail_id);
        }
    }
}
