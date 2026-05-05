<?php

/**
 * includes\class-jc-port.php
 *
 * @see: https://jellydai.com
 * @author: Jelly Dai <daiguo1003@gmail.com>
 * @created : 2025.12.20 13:48
 */

namespace Jelly_Catalog\Modules;

use ZipArchive;

if (!defined('ABSPATH')) {
    exit;
} // 禁止直接访问

/**
 * 后台产品导入导出功能
 */
class Port
{
    use Port_Import;

    /**
     * 构造函数
     * 注册后台菜单与请求处理
     */
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_post_jc_export_products', [$this, 'export_products']);
        add_action('admin_post_jc_import_products', [$this, 'import_products']);
        add_action('admin_post_jc_clear_import_log', [$this, 'clear_import_log']);
        add_action('wp_ajax_jc_start_import_products', [$this, 'ajax_start_import_products']);
        add_action('wp_ajax_jc_process_import_products', [$this, 'ajax_process_import_products']);
        add_action('admin_notices', [$this, 'import_notices']);

        // 设置日志文件路径
        $upload_dir = wp_upload_dir();
        $this->log_file = $upload_dir['basedir'] . '/jc_import_log.txt';
    }

    /**
     * 向产品菜单添加"导入/导出"子菜单
     *
     * @return void
     */
    public function add_menu()
    {
        add_submenu_page(
            'edit.php?post_type=product',
            __('Import/Export', 'jelly-catalog'),
            __('Import/Export', 'jelly-catalog'),
            'manage_options',
            'products-port',
            [$this, 'render_products_port']
        );
    }

    /**
     * 渲染产品导入导出界面
     *
     * @return void
     */
    public function render_products_port()
    {
        ?>
<div class="wrap">
    <h1><?php _e('Import/Export Products', 'jelly-catalog'); ?></h1>

    <div class="card">
        <h2><?php esc_html_e('Import Logic', 'jelly-catalog'); ?></h2>
        <ul>
            <li><?php esc_html_e('Rows with an existing product ID update that product; rows without a valid product ID create a new product.', 'jelly-catalog'); ?>
            </li>
            <li><?php esc_html_e('When updating an existing product, CSV columns that are not present are ignored so existing values are kept.', 'jelly-catalog'); ?>
            </li>
            <li><?php esc_html_e('Image columns import files by path or filename from the optional images ZIP; missing image files are logged and skipped.', 'jelly-catalog'); ?>
            </li>
        </ul>
    </div>

    <div class="card">
        <h2><?php _e('Export Products', 'jelly-catalog'); ?></h2>
        <p><?php _e('Export all products to CSV file with images as separate files.', 'jelly-catalog'); ?></p>
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <?php wp_nonce_field('jc_export_products', 'jc_export_nonce'); ?>
            <input type="hidden" name="action" value="jc_export_products">
            <?php submit_button(__('Export Products', 'jelly-catalog'), 'primary', 'export'); ?>
        </form>
    </div>

    <div class="card">
        <h2><?php _e('Import Products', 'jelly-catalog'); ?></h2>
        <p><?php _e('Import products from CSV file. Upload the CSV file and a ZIP file containing all images.', 'jelly-catalog'); ?>
        </p>
        <form id="jc-import-form" method="post" action="<?php echo admin_url('admin-post.php'); ?>"
            enctype="multipart/form-data">
            <?php wp_nonce_field('jc_import_products', 'jc_import_nonce'); ?>
            <input type="hidden" name="action" value="jc_import_products">
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('CSV File', 'jelly-catalog'); ?></th>
                    <td><input type="file" name="csv_file" accept=".csv" required></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Images ZIP File', 'jelly-catalog'); ?></th>
                    <td><input type="file" name="images_zip" accept=".zip"></td>
                </tr>
            </table>
            <p class="description">
                <?php _e('The images ZIP file should contain all images referenced in the CSV file.', 'jelly-catalog'); ?>
            </p>
            <?php submit_button(__('Import Products', 'jelly-catalog'), 'primary', 'import'); ?>
        </form>
        <div id="jc-import-progress"
            class="jc-port-progress mt-4 rounded-lg border border-gray-200 bg-white p-4 shadow-xs hidden"
            aria-live="polite">
            <div class="jc-port-progress__header">
                <strong><?php esc_html_e('Import Progress', 'jelly-catalog'); ?></strong>
                <span class="jc-port-progress__status">
                    <span id="jc-import-progress-percent">0%</span>
                    <button type="button" id="jc-import-retry" class="button jc-port-progress__retry hidden">
                        <?php esc_html_e('Retry Import', 'jelly-catalog'); ?>
                    </button>
                </span>
            </div>
            <div class="jc-port-progress__track">
                <span id="jc-import-progress-bar" class="jc-port-progress__bar" role="progressbar" aria-valuemin="0"
                    aria-valuemax="100" aria-valuenow="0"></span>
            </div>
            <div class="jc-port-progress__stats">
                <span><?php esc_html_e('Processed', 'jelly-catalog'); ?>: <strong
                        id="jc-import-progress-processed">0</strong>/<strong
                        id="jc-import-progress-total">0</strong></span>
                <span><?php esc_html_e('Imported', 'jelly-catalog'); ?>: <strong
                        id="jc-import-progress-imported">0</strong></span>
                <span><?php esc_html_e('Errors', 'jelly-catalog'); ?>: <strong
                        id="jc-import-progress-errors">0</strong></span>
            </div>
            <p id="jc-import-progress-message" class="jc-port-progress__message description"></p>
        </div>
    </div>

    <?php if (file_exists($this->log_file)): ?>
    <div class="card">
        <h2><?php _e('Import Log', 'jelly-catalog'); ?></h2>
        <textarea rows="20" cols="100" readonly
            style="width: 100%; font-family: monospace;"><?php echo esc_textarea(file_get_contents($this->log_file)); ?></textarea>
        <p>
            <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=jc_clear_import_log'), 'clear_import_log'); ?>"
                class="button">
                <?php _e('Clear Log', 'jelly-catalog'); ?>
            </a>
        </p>
    </div>
    <?php endif; ?>
</div>
<?php
    }

    /**
     * 清除导入日志
     */
    public function clear_import_log()
    {
        // 检查权限
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'jelly-catalog'));
        }

        // 验证 nonce
        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
        if (!$nonce || !wp_verify_nonce($nonce, 'clear_import_log')) {
            wp_die(__('Security check failed', 'jelly-catalog'));
        }

        // 删除日志文件
        if (file_exists($this->log_file)) {
            unlink($this->log_file);
        }

        // 重定向回导入导出页面
        wp_safe_redirect(admin_url('edit.php?post_type=product&page=products-port'));
        exit;
    }

    /**
     * 导出产品
     */
    public function export_products()
    {
        // 检查权限
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'jelly-catalog'));
        }

        // 验证 nonce
        $nonce = isset($_POST['jc_export_nonce']) ? sanitize_text_field(wp_unslash($_POST['jc_export_nonce'])) : '';
        if (!$nonce || !wp_verify_nonce($nonce, 'jc_export_products')) {
            wp_die(__('Security check failed', 'jelly-catalog'));
        }

        // 获取所有产品
        $products = get_posts([
            'post_type' => 'product',
            'post_status' => 'any',
            'numberposts' => -1,
            'orderby' => 'ID',
            'order' => 'ASC'
        ]);

        if (empty($products)) {
            wp_die(__('No products found to export.', 'jelly-catalog'));
        }

        // 创建临时目录用于存放导出文件
        $upload_dir = wp_upload_dir();
        $export_dir = $upload_dir['basedir'] . '/jc_export_' . time();
        wp_mkdir_p($export_dir);

        // 创建CSV文件
        $csv_file = $export_dir . '/products.csv';
        $fp = fopen($csv_file, 'w');

        // 写入UTF-8 BOM头以支持中文
        fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // 写入表头
        $headers = [
            'ID',
            'Title',
            'Status',
            'Featured Image',
            'Gallery Images',
            'Short Description',
            'Description',
            'Categories',
            'Tags',
            'FAQs',
            'Attributes'
        ];
        fputcsv($fp, $headers);

        // 处理每个产品
        foreach ($products as $product) {
            $post_id = $product->ID;

            // 获取特色图像
            $featured_image = '';
            $featured_image_id = get_post_thumbnail_id($post_id);
            if ($featured_image_id) {
                $featured_image_data = wp_get_attachment_metadata($featured_image_id);
                if ($featured_image_data && isset($featured_image_data['file'])) {
                    $featured_image = $featured_image_data['file'];
                    $image_path = get_attached_file($featured_image_id);
                    if ($image_path && file_exists($image_path)) {
                        $this->copy_export_image($image_path, $featured_image, $export_dir);
                    }
                }
            }

            // 获取画廊图像
            $gallery_images = [];
            $gallery_ids = get_post_meta($post_id, '_product_image_gallery', true);
            if ($gallery_ids) {
                $gallery_ids = explode(',', $gallery_ids);
                foreach ($gallery_ids as $gallery_id) {
                    $gallery_image_data = wp_get_attachment_metadata($gallery_id);
                    if ($gallery_image_data && isset($gallery_image_data['file'])) {
                        $gallery_images[] = $gallery_image_data['file'];
                        $image_path = get_attached_file($gallery_id);
                        if ($image_path && file_exists($image_path)) {
                            $this->copy_export_image($image_path, $gallery_image_data['file'], $export_dir);
                        }
                    }
                }
            }

            // 获取分类和标签
            $categories = wp_get_post_terms($post_id, 'product_cat', ['fields' => 'names']);
            $tags = wp_get_post_terms($post_id, 'product_tag', ['fields' => 'names']);

            // 获取FAQ和属性
            $faqs = get_post_meta($post_id, '_product_faqs', true);
            $faqs_serialized = $faqs ? base64_encode(json_encode($faqs)) : '';

            $attributes = get_post_meta($post_id, '_product_attributes', true);
            $attributes_serialized = $attributes ? base64_encode(json_encode($attributes)) : '';

            // 写入产品数据到CSV
            $row = [
                $post_id,
                $product->post_title,
                $product->post_status,
                $featured_image,
                implode(',', $gallery_images),
                $product->post_excerpt,
                $product->post_content,
                implode('|', $categories),
                implode('|', $tags),
                $faqs_serialized,
                $attributes_serialized
            ];

            fputcsv($fp, $row);
        }

        fclose($fp);

        // 打包为ZIP文件
        if (class_exists('ZipArchive')) {
            $zip_file = $export_dir . '.zip';
            $zip = new ZipArchive();

            // 如果存在 images 目录，先将其单独压缩为一个 ZIP 文件
            $images_dir = $export_dir . '/images';
            if (is_dir($images_dir)) {
                $images_zip_path = $export_dir . '/images.zip';
                $images_zip = new ZipArchive();
                if ($images_zip->open($images_zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                    $this->add_files_to_zip($images_zip, $images_dir, $images_dir);
                    $images_zip->close();

                    // 压缩完成后删除原始 images 目录，避免在大包中重复文件
                    $this->rrmdir($images_dir);
                }
            }

            if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                $this->add_files_to_zip($zip, $export_dir, $export_dir);
                $zip->close();

                // 删除临时目录
                $this->rrmdir($export_dir);

                // 提供下载
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="products_export_' . date('Y-m-d_H-i-s') . '.zip"');
                header('Content-Length: ' . filesize($zip_file));
                readfile($zip_file);
                unlink($zip_file);
                exit;
            }
        }

        // 如果不能创建ZIP，则只提供CSV下载
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="products_export_' . date('Y-m-d_H-i-s') . '.csv"');
        readfile($csv_file);

        // 清理临时文件
        unlink($csv_file);
        if (is_dir($export_dir)) {
            $this->rrmdir($export_dir);
        }

        exit;
    }

    /**
     * 将导出图片复制到 images 目录，并保留上传目录中的相对路径。
     */
    private function copy_export_image($source_path, $relative_path, $export_dir)
    {
        $relative_path = $this->normalize_export_image_path($relative_path);
        if (!$source_path || !$relative_path || !$export_dir) {
            return false;
        }

        $target_path = trailingslashit($export_dir) . 'images/' . $relative_path;
        wp_mkdir_p(dirname($target_path));

        return @copy($source_path, $target_path);
    }

    /**
     * 规范化导出图片相对路径，避免写出 ZIP 目录外。
     */
    private function normalize_export_image_path($relative_path)
    {
        $relative_path = str_replace('\\', '/', (string) $relative_path);
        $relative_path = ltrim($relative_path, '/');

        if (
            '' === $relative_path ||
            preg_match('/^[a-zA-Z]:/', $relative_path) ||
            preg_match('#(^|/)\.\.(/|$)#', $relative_path)
        ) {
            return sanitize_file_name(basename($relative_path));
        }

        $parts = array_filter(explode('/', $relative_path), 'strlen');
        $parts = array_map('sanitize_file_name', $parts);

        return implode('/', $parts);
    }

    /**
     * 添加文件到ZIP归档
     */
    private function add_files_to_zip($zip, $path, $base_dir)
    {
        $handler = opendir($path);
        while (($filename = readdir($handler)) !== false) {
            if ($filename != '.' && $filename != '..') {
                $full_path = $path . '/' . $filename;
                $local_path = str_replace($base_dir . '/', '', $full_path);

                if (is_file($full_path)) {
                    $zip->addFile($full_path, $local_path);
                } elseif (is_dir($full_path)) {
                    // 添加空目录
                    $zip->addEmptyDir($local_path);
                    $this->add_files_to_zip($zip, $full_path, $base_dir);
                }
            }
        }
        closedir($handler);
    }
}
