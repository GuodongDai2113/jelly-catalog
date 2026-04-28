<?php

/**
 * includes\class-jc-port.php
 *
 * @see: https://jellydai.com
 * @author: Jelly Dai <daiguo1003@gmail.com>
 * @created : 2025.12.20 13:48
 */

if (!defined('ABSPATH')) {
    exit;
} // 禁止直接访问

/**
 * 后台产品导入导出功能
 */
class JC_Port
{
    /**
     * @var JC_Port 单例实例
     */
    protected static $instance;

    /**
     * 日志文件路径
     */
    private $log_file;

    /**
     * 获取单例实例
     *
     * @return JC_Port
     */
    public static function instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * 构造函数
     * 注册后台脚本与菜单
     */
    public function __construct()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_script']);
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
     * 后台加载所需脚本与样式
     *
     * @return void
     */
    public function enqueue_script($hook = '')
    {
        if ('product_page_products-port' !== $hook) {
            return;
        }

        wp_enqueue_script('jquery');

        $settings = [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('jc_import_products'),
            'resumeJobId' => isset($_GET['jc_import_job']) ? sanitize_key(wp_unslash($_GET['jc_import_job'])) : '',
            'messages' => [
                'uploading' => __('Uploading files and preparing import...', 'jelly-catalog'),
                'processing' => __('Importing products...', 'jelly-catalog'),
                'complete' => __('Import completed.', 'jelly-catalog'),
                'failed' => __('Import failed.', 'jelly-catalog'),
                'networkError' => __('Network error. Please try again.', 'jelly-catalog'),
            ],
        ];

        wp_add_inline_script(
            'jquery',
            'window.jcPortImport = ' . wp_json_encode($settings) . ';',
            'before'
        );

        wp_add_inline_script(
            'jquery',
            <<<'JS'
(function ($) {
    $(function () {
        var settings = window.jcPortImport || {};
        var $form = $('#jc-import-form');
        var $progress = $('#jc-import-progress');
        var $bar = $('#jc-import-progress-bar');
        var $percent = $('#jc-import-progress-percent');
        var $message = $('#jc-import-progress-message');
        var $processed = $('#jc-import-progress-processed');
        var $total = $('#jc-import-progress-total');
        var $imported = $('#jc-import-progress-imported');
        var $errors = $('#jc-import-progress-errors');
        var $submit = $form.find('[type="submit"], #import');
        var jobId = settings.resumeJobId || '';
        var isRunning = false;

        function setFormDisabled(disabled) {
            $form.find('input, button, select, textarea').prop('disabled', disabled);
        }

        function showProgress(message) {
            $progress.removeClass('hidden').show();
            $message.text(message || '');
        }

        function setProgress(data) {
            var total = parseInt(data.total, 10) || 0;
            var processed = parseInt(data.processed, 10) || 0;
            var imported = parseInt(data.imported, 10) || 0;
            var errors = parseInt(data.errors, 10) || 0;
            var percent = total > 0 ? Math.min(100, Math.round((processed / total) * 100)) : 0;

            $bar.css('width', percent + '%');
            $bar.attr('aria-valuenow', percent);
            $percent.text(percent + '%');
            $processed.text(processed);
            $total.text(total);
            $imported.text(imported);
            $errors.text(errors);

            if (data.message) {
                $message.text(data.message);
            }
        }

        function failImport(message) {
            isRunning = false;
            setFormDisabled(false);
            $submit.prop('disabled', false);
            $progress.addClass('jc-port-progress--error');
            $message.text(message || settings.messages.failed);
        }

        function processNextBatch() {
            if (!jobId || !isRunning) {
                return;
            }

            $.post(settings.ajaxUrl, {
                action: 'jc_process_import_products',
                nonce: settings.nonce,
                job_id: jobId
            }).done(function (response) {
                if (!response || !response.success) {
                    failImport(response && response.data && response.data.message ? response.data.message : settings.messages.failed);
                    return;
                }

                setProgress(response.data);

                if (response.data.status === 'complete') {
                    isRunning = false;
                    setFormDisabled(false);
                    $submit.prop('disabled', false);
                    $message.text(settings.messages.complete);
                    return;
                }

                window.setTimeout(processNextBatch, 250);
            }).fail(function () {
                failImport(settings.messages.networkError);
            });
        }

        $form.on('submit', function (event) {
            event.preventDefault();

            if (isRunning) {
                return;
            }

            var formData = new FormData(this);
            formData.set('action', 'jc_start_import_products');
            formData.set('nonce', settings.nonce);

            isRunning = true;
            setFormDisabled(true);
            $submit.prop('disabled', true);
            $progress.removeClass('jc-port-progress--error');
            showProgress(settings.messages.uploading);
            setProgress({ total: 0, processed: 0, imported: 0, errors: 0 });

            $.ajax({
                url: settings.ajaxUrl,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false
            }).done(function (response) {
                if (!response || !response.success) {
                    failImport(response && response.data && response.data.message ? response.data.message : settings.messages.failed);
                    return;
                }

                jobId = response.data.job_id;
                setProgress(response.data);
                $message.text(settings.messages.processing);
                processNextBatch();
            }).fail(function () {
                failImport(settings.messages.networkError);
            });
        });

        if (jobId) {
            isRunning = true;
            setFormDisabled(true);
            $submit.prop('disabled', true);
            showProgress(settings.messages.processing);
            processNextBatch();
        }
    });
})(jQuery);
JS
        );

        wp_register_style('jc-port-import', false, [], JELLY_CATALOG_VERSION);
        wp_enqueue_style('jc-port-import');
        wp_add_inline_style(
            'jc-port-import',
            <<<'CSS'
.jc-port-progress.hidden {
    display: none;
}

.jc-port-progress {
    margin-top: 16px;
    border: 1px solid #c3c4c7;
    border-radius: 8px;
    background: #fff;
    padding: 16px;
}

.jc-port-progress__header,
.jc-port-progress__stats {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 8px 16px;
}

.jc-port-progress__track {
    width: 100%;
    height: 14px;
    margin: 12px 0;
    overflow: hidden;
    border-radius: 999px;
    background: #e5e7eb;
}

.jc-port-progress__bar {
    display: block;
    width: 0;
    height: 100%;
    border-radius: inherit;
    background: #2271b1;
    transition: width 0.2s ease;
}

.jc-port-progress__message {
    margin: 12px 0 0;
}

.jc-port-progress--error {
    border-color: #d63638;
}

.jc-port-progress--error .jc-port-progress__bar {
    background: #d63638;
}
CSS
        );
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
        <form id="jc-import-form" method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
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
        <div id="jc-import-progress" class="jc-port-progress mt-4 rounded-lg border border-gray-200 bg-white p-4 shadow-xs hidden" aria-live="polite">
            <div class="jc-port-progress__header">
                <strong><?php esc_html_e('Import Progress', 'jelly-catalog'); ?></strong>
                <span id="jc-import-progress-percent">0%</span>
            </div>
            <div class="jc-port-progress__track">
                <span id="jc-import-progress-bar" class="jc-port-progress__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"></span>
            </div>
            <div class="jc-port-progress__stats">
                <span><?php esc_html_e('Processed', 'jelly-catalog'); ?>: <strong id="jc-import-progress-processed">0</strong>/<strong id="jc-import-progress-total">0</strong></span>
                <span><?php esc_html_e('Imported', 'jelly-catalog'); ?>: <strong id="jc-import-progress-imported">0</strong></span>
                <span><?php esc_html_e('Errors', 'jelly-catalog'); ?>: <strong id="jc-import-progress-errors">0</strong></span>
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
     * 写入日志
     */
    private function log($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($this->log_file, "[{$timestamp}] {$message}\n", FILE_APPEND | LOCK_EX);
    }

    /**
     * 获取导入导出页面的安全跳转地址。
     *
     * @param array $args 查询参数。
     * @return string
     */
    private function get_redirect_url($args = [])
    {
        $url = wp_get_referer();
        if (!$url) {
            $url = admin_url('edit.php?post_type=product&page=products-port');
        }

        return $args ? add_query_arg($args, $url) : $url;
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
                    // 复制图像到导出目录
                    $image_path = get_attached_file($featured_image_id);
                    if ($image_path && file_exists($image_path)) {
                        $image_dir = $export_dir . '/images/';
                        wp_mkdir_p($image_dir);
                        copy($image_path, $image_dir . basename($image_path));
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
                        // 复制图像到导出目录
                        $image_path = get_attached_file($gallery_id);
                        if ($image_path && file_exists($image_path)) {
                            $image_dir = $export_dir . '/images/';
                            wp_mkdir_p($image_dir);
                            copy($image_path, $image_dir . basename($image_path));
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
     * 导入产品
     */
    public function import_products()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'jelly-catalog'));
        }

        $nonce = isset($_POST['jc_import_nonce']) ? sanitize_text_field(wp_unslash($_POST['jc_import_nonce'])) : '';
        if (!$nonce || !wp_verify_nonce($nonce, 'jc_import_products')) {
            wp_die(__('Security check failed', 'jelly-catalog'));
        }

        $job = $this->create_import_job();
        if (is_wp_error($job)) {
            wp_safe_redirect(add_query_arg(
                ['import_error' => $this->get_import_error_code($job)],
                admin_url('edit.php?post_type=product&page=products-port')
            ));
            exit;
        }

        wp_safe_redirect(add_query_arg(
            ['jc_import_job' => $job['id']],
            admin_url('edit.php?post_type=product&page=products-port')
        ));
        exit;
    }

    /**
     * AJAX 初始化导入任务
     */
    public function ajax_start_import_products()
    {
        check_ajax_referer('jc_import_products', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('You do not have sufficient permissions to access this page.', 'jelly-catalog'),
            ], 403);
        }

        $job = $this->create_import_job();
        if (is_wp_error($job)) {
            wp_send_json_error([
                'code' => $this->get_import_error_code($job),
                'message' => $job->get_error_message(),
            ], 400);
        }

        wp_send_json_success($this->format_import_job_response(
            $job,
            __('Import job prepared. Starting batch import...', 'jelly-catalog')
        ));
    }

    /**
     * AJAX 分批处理导入任务
     */
    public function ajax_process_import_products()
    {
        check_ajax_referer('jc_import_products', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('You do not have sufficient permissions to access this page.', 'jelly-catalog'),
            ], 403);
        }

        $job_id = isset($_POST['job_id']) ? sanitize_key(wp_unslash($_POST['job_id'])) : '';
        $job = $this->get_import_job($job_id);
        if (is_wp_error($job)) {
            wp_send_json_error([
                'code' => $job->get_error_code(),
                'message' => $job->get_error_message(),
            ], 404);
        }

        $job = $this->process_import_batch($job);
        if (is_wp_error($job)) {
            wp_send_json_error([
                'code' => $this->get_import_error_code($job),
                'message' => $job->get_error_message(),
            ], 400);
        }

        wp_send_json_success($this->format_import_job_response($job));
    }

    /**
     * 创建导入任务，保存上传文件与导入状态。
     */
    private function create_import_job()
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(absint(apply_filters('jc_import_prepare_time_limit', 120)));
        }

        if (file_exists($this->log_file)) {
            unlink($this->log_file);
        }

        $this->log('开始导入产品');

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $this->log('CSV文件上传失败: ' . ($_FILES['csv_file']['error'] ?? '未知错误'));
            return new WP_Error('file_upload_failed', __('File upload failed.', 'jelly-catalog'));
        }

        $csv_name = sanitize_file_name($_FILES['csv_file']['name']);
        if ('csv' !== strtolower(pathinfo($csv_name, PATHINFO_EXTENSION))) {
            $this->log('CSV文件扩展名无效: ' . $csv_name);
            return new WP_Error('invalid_csv_file', __('Invalid CSV file.', 'jelly-catalog'));
        }

        $this->log('CSV文件上传成功: ' . $csv_name);

        $upload_dir = wp_upload_dir();
        $job_id = sanitize_key(str_replace('-', '', wp_generate_uuid4()));
        $temp_dir = trailingslashit($upload_dir['basedir']) . 'jc_import_' . $job_id;

        if (!wp_mkdir_p($temp_dir)) {
            $this->log('无法创建临时目录: ' . $temp_dir);
            return new WP_Error('cannot_read_file', __('Cannot read uploaded file.', 'jelly-catalog'));
        }

        $this->log('创建临时目录: ' . $temp_dir);

        $csv_file = trailingslashit($temp_dir) . 'products.csv';
        if (!$this->move_uploaded_file_to_path($_FILES['csv_file']['tmp_name'], $csv_file)) {
            $this->log('无法保存CSV文件到临时目录');
            $this->rrmdir($temp_dir);
            return new WP_Error('cannot_read_file', __('Cannot read uploaded file.', 'jelly-catalog'));
        }

        $images_path = $this->prepare_import_images($temp_dir);
        $csv_info = $this->inspect_import_csv($csv_file);

        if (is_wp_error($csv_info)) {
            $this->rrmdir($temp_dir);
            return $csv_info;
        }

        $image_index_file = trailingslashit($temp_dir) . 'image-index.json';
        $image_index = $this->build_image_index($images_path);
        file_put_contents($image_index_file, wp_json_encode($image_index));

        $job = [
            'id' => $job_id,
            'user_id' => get_current_user_id(),
            'status' => 'pending',
            'csv_file' => $csv_file,
            'temp_dir' => $temp_dir,
            'images_path' => $images_path,
            'image_index_file' => $image_index_file,
            'headers' => $csv_info['headers'],
            'faq_count' => $csv_info['faq_count'],
            'attribute_count' => $csv_info['attribute_count'],
            'offset' => $csv_info['data_offset'],
            'total' => $csv_info['total'],
            'processed' => 0,
            'imported' => 0,
            'errors' => 0,
            'message' => __('Import job prepared. Starting batch import...', 'jelly-catalog'),
            'created_at' => time(),
            'updated_at' => time(),
        ];

        $this->save_import_job($job);
        $this->log('导入任务已创建，产品总数: ' . $job['total']);

        return $job;
    }

    /**
     * 分批处理导入任务。
     */
    private function process_import_batch($job)
    {
        if (!empty($job['status']) && 'complete' === $job['status']) {
            return $job;
        }

        if (function_exists('set_time_limit')) {
            @set_time_limit(30);
        }

        $csv_file = $job['csv_file'] ?? '';
        if (!$csv_file || !file_exists($csv_file)) {
            $this->log('导入任务CSV文件不存在');
            $job['status'] = 'error';
            $job['message'] = __('Cannot read uploaded file.', 'jelly-catalog');
            $this->save_import_job($job);
            return new WP_Error('cannot_read_file', __('Cannot read uploaded file.', 'jelly-catalog'));
        }

        $handle = fopen($csv_file, 'r');
        if (!$handle) {
            $this->log('无法打开导入任务CSV文件');
            $job['status'] = 'error';
            $job['message'] = __('Cannot read uploaded file.', 'jelly-catalog');
            $this->save_import_job($job);
            return new WP_Error('cannot_read_file', __('Cannot read uploaded file.', 'jelly-catalog'));
        }

        fseek($handle, (int) ($job['offset'] ?? 0));

        $batch_size = max(1, absint(apply_filters('jc_import_batch_size', 3)));
        $time_limit = max(5, absint(apply_filters('jc_import_batch_time_limit', 20)));
        $started_at = microtime(true);
        $processed_in_batch = 0;
        $image_index = $this->load_image_index($job['image_index_file'] ?? '');

        while ($processed_in_batch < $batch_size && (microtime(true) - $started_at) < $time_limit) {
            $row = fgetcsv($handle);
            if (false === $row) {
                break;
            }

            $job['offset'] = ftell($handle);
            $result = $this->process_import_row(
                $row,
                $job['headers'],
                absint($job['faq_count']),
                absint($job['attribute_count']),
                $job['images_path'] ?? '',
                $image_index
            );

            $job['processed'] = absint($job['processed']) + 1;
            if ($result) {
                $job['imported'] = absint($job['imported']) + 1;
            } else {
                $job['errors'] = absint($job['errors']) + 1;
            }

            $processed_in_batch++;
        }

        fclose($handle);

        if (absint($job['processed']) >= absint($job['total'])) {
            $job['status'] = 'complete';
            $job['message'] = sprintf(
                /* translators: 1: imported count, 2: error count */
                __('Import completed. Imported: %1$d, Errors: %2$d.', 'jelly-catalog'),
                absint($job['imported']),
                absint($job['errors'])
            );

            set_transient('jc_import_result', [
                'imported' => absint($job['imported']),
                'errors' => absint($job['errors']),
            ], 60);

            $this->log('导入过程结束，清理临时文件');
            if (!empty($job['temp_dir']) && is_dir($job['temp_dir'])) {
                $this->rrmdir($job['temp_dir']);
            }
            $this->log("导入完成 - 成功: {$job['imported']}, 失败: {$job['errors']}");
        } else {
            $job['status'] = 'running';
            $job['message'] = sprintf(
                /* translators: 1: processed count, 2: total count */
                __('Processed %1$d of %2$d products...', 'jelly-catalog'),
                absint($job['processed']),
                absint($job['total'])
            );
        }

        $job['updated_at'] = time();
        $this->save_import_job($job);

        return $job;
    }

    /**
     * 处理单行产品数据。
     */
    private function process_import_row($row, $headers, $faq_count, $attribute_count, $images_path, $image_index)
    {
        $data = array_combine($headers, $row);
        if (false === $data) {
            $this->log('CSV列数不匹配，跳过该行。Headers: ' . count($headers) . ' Row: ' . count($row));
            return false;
        }

        $title = $data['Title'] ?? '';
        $this->log('处理产品: ' . $title . ' (ID: ' . ($data['ID'] ?? 'new') . ')');

        $post_data = [
            'post_type' => 'product',
            'post_title' => sanitize_text_field($title),
            'post_excerpt' => wp_kses_post($data['Short Description'] ?? ''),
            'post_content' => wp_kses_post($data['Description'] ?? ''),
            'post_status' => 'publish'
        ];

        if (!empty($data['ID'])) {
            $existing_post_id = absint($data['ID']);
            $existing_post = get_post($existing_post_id);
            if ($existing_post && $existing_post->post_type === 'product') {
                $post_data['ID'] = $existing_post_id;
                $this->log('更新现有产品 ID: ' . $existing_post_id);
            } else {
                $this->log('指定的产品ID不存在，将创建新产品: ' . $data['ID']);
            }
        }

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            $this->log('创建/更新产品失败: ' . $post_id->get_error_message());
            return false;
        }

        if (!$post_id) {
            $this->log('创建/更新产品失败: 未返回有效产品ID');
            return false;
        }

        $this->log('产品保存成功，ID: ' . $post_id);

        if (!empty($data['Featured Image'])) {
            $image_name = sanitize_file_name(basename($data['Featured Image']));
            $image_path = $this->resolve_import_image_path($images_path, $image_name, $image_index);

            $this->log('处理特色图像: ' . $image_name);
            $this->log('图像路径: ' . ($image_path ?: ''));

            if ($image_path) {
                $attachment_id = $this->import_image_as_attachment($image_path, $post_id, $image_name);
                if ($attachment_id) {
                    set_post_thumbnail($post_id, $attachment_id);
                    $this->log('特色图像设置成功，附件ID: ' . $attachment_id);
                } else {
                    $this->log('特色图像导入失败');
                }
            } else {
                $this->log('特色图像文件不存在: ' . $image_name);
            }
        }

        if (!empty($data['Gallery Images'])) {
            $gallery_images = explode(',', $data['Gallery Images']);
            $gallery_ids = [];

            $this->log('处理画廊图像，共 ' . count($gallery_images) . ' 张图片');

            foreach ($gallery_images as $image_filename) {
                $image_name = sanitize_file_name(basename(trim($image_filename)));
                if (!$image_name) {
                    continue;
                }

                $this->log('处理画廊图像: ' . $image_name);
                $image_path = $this->resolve_import_image_path($images_path, $image_name, $image_index);

                if ($image_path) {
                    $attachment_id = $this->import_image_as_attachment($image_path, $post_id, $image_name);
                    if ($attachment_id) {
                        $gallery_ids[] = $attachment_id;
                        $this->log('画廊图像导入成功，附件ID: ' . $attachment_id);
                    } else {
                        $this->log('画廊图像导入失败: ' . $image_name);
                    }
                } else {
                    $this->log('画廊图像文件不存在: ' . $image_name);
                }
            }

            if (!empty($gallery_ids)) {
                update_post_meta($post_id, '_product_image_gallery', implode(',', $gallery_ids));
                $this->log('画廊图像元数据已保存，IDs: ' . implode(',', $gallery_ids));
            }
        }

        if (!empty($data['Categories'])) {
            $categories = explode('|', $data['Categories']);
            $category_ids = [];
            foreach ($categories as $category_name) {
                $category_name = sanitize_text_field(trim($category_name));
                if (empty($category_name)) {
                    continue;
                }

                $category = get_term_by('name', $category_name, 'product_cat');
                if (!$category) {
                    $new_category = wp_insert_term($category_name, 'product_cat');
                    if (!is_wp_error($new_category)) {
                        $category_ids[] = $new_category['term_id'];
                        $this->log('创建新分类: ' . $category_name . ' (ID: ' . $new_category['term_id'] . ')');
                    } else {
                        $this->log('创建分类失败: ' . $category_name . ' (' . $new_category->get_error_message() . ')');
                    }
                } else {
                    $category_ids[] = $category->term_id;
                    $this->log('使用现有分类: ' . $category_name . ' (ID: ' . $category->term_id . ')');
                }
            }
            wp_set_post_terms($post_id, $category_ids, 'product_cat');
            $this->log('产品分类已设置，IDs: ' . implode(',', $category_ids));
        }

        if (!empty($data['Tags'])) {
            $tags = explode('|', $data['Tags']);
            $tag_ids = [];
            foreach ($tags as $tag_name) {
                $tag_name = sanitize_text_field(trim($tag_name));
                if (empty($tag_name)) {
                    continue;
                }

                $tag = get_term_by('name', $tag_name, 'product_tag');
                if (!$tag) {
                    $new_tag = wp_insert_term($tag_name, 'product_tag');
                    if (!is_wp_error($new_tag)) {
                        $tag_ids[] = $new_tag['term_id'];
                        $this->log('创建新标签: ' . $tag_name . ' (ID: ' . $new_tag['term_id'] . ')');
                    } else {
                        $this->log('创建标签失败: ' . $tag_name . ' (' . $new_tag->get_error_message() . ')');
                    }
                } else {
                    $tag_ids[] = $tag->term_id;
                    $this->log('使用现有标签: ' . $tag_name . ' (ID: ' . $tag->term_id . ')');
                }
            }
            wp_set_post_terms($post_id, $tag_ids, 'product_tag');
            $this->log('产品标签已设置，IDs: ' . implode(',', $tag_ids));
        }

        $faqs = [];
        if ($faq_count > 0) {
            for ($i = 1; $i <= $faq_count; $i++) {
                $question_key = 'FAQ_Q_' . $i;
                $answer_key = 'FAQ_A_' . $i;

                if (!empty($data[$question_key]) || !empty($data[$answer_key])) {
                    $faqs[] = [
                        'name' => isset($data[$question_key]) ? sanitize_text_field($data[$question_key]) : '',
                        'value' => isset($data[$answer_key]) ? sanitize_textarea_field($data[$answer_key]) : ''
                    ];
                }
            }
        } elseif (!empty($data['FAQs'])) {
            $decoded = base64_decode($data['FAQs'], true);
            $parsed = $decoded ? json_decode($decoded, true) : null;
            if (is_array($parsed)) {
                $faqs = $parsed;
            }
        }

        if (!empty($faqs)) {
            update_post_meta($post_id, '_product_faqs', $faqs);
            $this->log('FAQ数据已保存，共 ' . count($faqs) . ' 条');
        }

        $attributes = [];
        if ($attribute_count > 0) {
            for ($i = 1; $i <= $attribute_count; $i++) {
                $name_key = 'Attribute_Name_' . $i;
                $value_key = 'Attribute_Value_' . $i;

                if (!empty($data[$name_key]) || !empty($data[$value_key])) {
                    $attributes[] = [
                        'name' => isset($data[$name_key]) ? sanitize_text_field($data[$name_key]) : '',
                        'value' => isset($data[$value_key]) ? sanitize_text_field($data[$value_key]) : ''
                    ];
                }
            }
        } elseif (!empty($data['Attributes'])) {
            $decoded = base64_decode($data['Attributes'], true);
            $parsed = $decoded ? json_decode($decoded, true) : null;
            if (is_array($parsed)) {
                $attributes = $parsed;
            }
        }

        if (!empty($attributes)) {
            update_post_meta($post_id, '_product_attributes', $attributes);
            $this->log('属性数据已保存，共 ' . count($attributes) . ' 条');
        }

        $this->log('产品处理完成: ' . $title . "\n---");

        return true;
    }

    /**
     * 将上传文件保存到指定路径。
     */
    private function move_uploaded_file_to_path($source, $destination)
    {
        if (!$source || !$destination) {
            return false;
        }

        if (@move_uploaded_file($source, $destination)) {
            return true;
        }

        return @copy($source, $destination);
    }

    /**
     * 解压并定位导入图片目录。
     */
    private function prepare_import_images($temp_dir)
    {
        $images_path = '';

        if (isset($_FILES['images_zip']) && $_FILES['images_zip']['error'] === UPLOAD_ERR_OK) {
            $zip_name = sanitize_file_name($_FILES['images_zip']['name']);
            $this->log('开始解压图片ZIP文件: ' . $zip_name);

            if ('zip' !== strtolower(pathinfo($zip_name, PATHINFO_EXTENSION)) || !class_exists('ZipArchive')) {
                $this->log('图片ZIP文件无效或当前环境不支持ZipArchive');
                return $images_path;
            }

            $zip = new ZipArchive();
            if ($zip->open($_FILES['images_zip']['tmp_name']) !== true) {
                $this->log('无法打开图片ZIP文件');
                return $images_path;
            }

            $extract_dir = trailingslashit($temp_dir) . 'images/';
            wp_mkdir_p($extract_dir);

            if (!$this->zip_has_safe_paths($zip)) {
                $this->log('图片ZIP文件包含不安全路径，已跳过解压');
                $zip->close();
                return $images_path;
            }

            if ($zip->extractTo($extract_dir)) {
                $zip->close();
                $images_path = $extract_dir;
                $this->log('图片ZIP文件解压成功到: ' . $images_path);

                $actual_images_path = $this->find_images_directory($images_path);
                if (false !== $actual_images_path) {
                    $images_path = $actual_images_path;
                    $this->log('找到实际图片目录: ' . $images_path);
                } else {
                    $this->log('警告：未找到有效的图片目录');
                }
            } else {
                $this->log('图片ZIP文件解压失败');
                $zip->close();
            }
        } else {
            $this->log('未提供图片ZIP文件或上传失败，错误代码: ' . ($_FILES['images_zip']['error'] ?? '无文件'));
        }

        return $images_path;
    }

    /**
     * 读取CSV表头并统计总行数。
     */
    private function inspect_import_csv($csv_file)
    {
        $handle = fopen($csv_file, 'r');

        if (!$handle) {
            $this->log('无法打开CSV文件进行读取');
            return new WP_Error('cannot_read_file', __('Cannot read uploaded file.', 'jelly-catalog'));
        }

        $this->log('开始读取CSV文件');
        $this->skip_csv_bom($handle);

        $headers = fgetcsv($handle);
        if (empty($headers) || !is_array($headers)) {
            $this->log('CSV表头为空或格式无效');
            fclose($handle);
            return new WP_Error('cannot_read_file', __('Cannot read uploaded file.', 'jelly-catalog'));
        }

        $headers = array_map(function ($header) {
            return trim((string) $header);
        }, $headers);
        if (isset($headers[0])) {
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
        }

        $data_offset = ftell($handle);
        $total = 0;
        while (false !== fgetcsv($handle)) {
            $total++;
        }

        fclose($handle);

        $counts = $this->get_import_dynamic_column_counts($headers);

        $this->log('读取到表头: ' . implode(', ', $headers));
        $this->log('检测到FAQ数量: ' . $counts['faq_count']);
        $this->log('检测到属性数量: ' . $counts['attribute_count']);
        $this->log('检测到产品行数: ' . $total);

        return [
            'headers' => $headers,
            'faq_count' => $counts['faq_count'],
            'attribute_count' => $counts['attribute_count'],
            'data_offset' => $data_offset,
            'total' => $total,
        ];
    }

    /**
     * 跳过CSV UTF-8 BOM。
     */
    private function skip_csv_bom($handle)
    {
        $bom = fread($handle, 3);
        if ($bom !== chr(0xEF) . chr(0xBB) . chr(0xBF)) {
            rewind($handle);
        }
    }

    /**
     * 统计动态FAQ和属性列数量。
     */
    private function get_import_dynamic_column_counts($headers)
    {
        $faq_count = 0;
        $attribute_count = 0;

        foreach ($headers as $header) {
            if (strpos($header, 'FAQ_Q_') === 0) {
                $faq_num = (int) substr($header, 6);
                if ($faq_num > $faq_count) {
                    $faq_count = $faq_num;
                }
            } elseif (strpos($header, 'Attribute_Name_') === 0) {
                $attr_num = (int) substr($header, 15);
                if ($attr_num > $attribute_count) {
                    $attribute_count = $attr_num;
                }
            }
        }

        return [
            'faq_count' => $faq_count,
            'attribute_count' => $attribute_count,
        ];
    }

    /**
     * 为图片文件创建文件名索引，避免每个产品重复递归扫描。
     */
    private function build_image_index($images_path)
    {
        $index = [];

        if (!$images_path || !is_dir($images_path)) {
            return $index;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($images_path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $filename = $file->getFilename();
            $path = $file->getPathname();
            $lower_filename = strtolower($filename);

            if (!isset($index[$filename])) {
                $index[$filename] = $path;
            }

            if (!isset($index[$lower_filename])) {
                $index[$lower_filename] = $path;
            }
        }

        $this->log('图片索引创建完成，文件名数量: ' . count($index));

        return $index;
    }

    /**
     * 读取图片索引。
     */
    private function load_image_index($image_index_file)
    {
        if (!$image_index_file || !file_exists($image_index_file)) {
            return [];
        }

        $contents = file_get_contents($image_index_file);
        $index = json_decode($contents, true);

        return is_array($index) ? $index : [];
    }

    /**
     * 根据文件名查找待导入图片路径。
     */
    private function resolve_import_image_path($images_path, $image_name, $image_index)
    {
        if (!$images_path || !is_dir($images_path) || !$image_name) {
            $this->log('未提供可用的图片目录，跳过图像: ' . $image_name);
            return false;
        }

        $direct_path = trailingslashit($images_path) . $image_name;
        if (file_exists($direct_path)) {
            return $direct_path;
        }

        if (isset($image_index[$image_name]) && file_exists($image_index[$image_name])) {
            return $image_index[$image_name];
        }

        $lower_name = strtolower($image_name);
        if (isset($image_index[$lower_name]) && file_exists($image_index[$lower_name])) {
            return $image_index[$lower_name];
        }

        return $this->find_file_in_subdirectories($images_path, $image_name);
    }

    /**
     * 保存导入任务状态。
     */
    private function save_import_job($job)
    {
        set_transient($this->get_import_job_transient_key($job['id']), $job, DAY_IN_SECONDS);
    }

    /**
     * 获取导入任务状态。
     */
    private function get_import_job($job_id)
    {
        if (!$job_id) {
            return new WP_Error('missing_job', __('Import job not found.', 'jelly-catalog'));
        }

        $job = get_transient($this->get_import_job_transient_key($job_id));
        if (!$job || !is_array($job)) {
            return new WP_Error('missing_job', __('Import job not found.', 'jelly-catalog'));
        }

        if (!empty($job['user_id']) && (int) $job['user_id'] !== get_current_user_id()) {
            return new WP_Error('missing_job', __('Import job not found.', 'jelly-catalog'));
        }

        return $job;
    }

    /**
     * 导入任务 transient key。
     */
    private function get_import_job_transient_key($job_id)
    {
        return 'jc_import_job_' . sanitize_key($job_id);
    }

    /**
     * 格式化导入任务响应。
     */
    private function format_import_job_response($job, $message = '')
    {
        return [
            'job_id' => $job['id'],
            'status' => $job['status'],
            'total' => absint($job['total']),
            'processed' => absint($job['processed']),
            'imported' => absint($job['imported']),
            'errors' => absint($job['errors']),
            'message' => $message ?: ($job['message'] ?? ''),
        ];
    }

    /**
     * 获取前端可识别的导入错误代码。
     */
    private function get_import_error_code($error)
    {
        $code = $error instanceof WP_Error ? $error->get_error_code() : '';
        $allowed_codes = [
            'file_upload_failed',
            'invalid_csv_file',
            'cannot_read_file',
        ];

        return in_array($code, $allowed_codes, true) ? $code : 'unknown';
    }

    /**
     * 在目录及其子目录中查找图片文件
     */
    private function find_file_in_subdirectories($root_path, $filename)
    {
        $this->log("在目录 {$root_path} 及其子目录中搜索文件: {$filename}");

        if (!$root_path || !is_dir($root_path) || !$filename) {
            $this->log('图片目录或文件名无效');
            return false;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root_path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getFilename() === $filename) {
                $this->log('找到文件: ' . $file->getPathname());
                return $file->getPathname();
            }
        }

        $this->log("未找到文件: {$filename}");
        return false;
    }

    /**
     * 校验 ZIP 内路径，避免解压到目标目录外。
     *
     * @param ZipArchive $zip ZIP 对象。
     * @return bool
     */
    private function zip_has_safe_paths($zip)
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $normalized = str_replace('\\', '/', (string) $name);

            if (
                '' === $normalized ||
                '/' === $normalized[0] ||
                preg_match('/^[a-zA-Z]:/', $normalized) ||
                preg_match('#(^|/)\.\.(/|$)#', $normalized)
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * 查找图片目录
     */
    private function find_images_directory($path)
    {
        $this->log('查找图片目录: ' . $path);

        if (!$path || !is_dir($path)) {
            $this->log('图片目录不存在');
            return false;
        }

        // 检查当前目录是否包含图片文件
        $files = glob($path . '*.{jpg,jpeg,png,gif,webp,JPG,JPEG,PNG,GIF,WEBP}', GLOB_BRACE);
        if (!empty($files)) {
            $this->log('在当前目录找到图片文件');
            return $path;
        }

        // 搜索子目录
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                $sub_path = $file->getPathname() . DIRECTORY_SEPARATOR;
                $files = glob($sub_path . '*.{jpg,jpeg,png,gif,webp,JPG,JPEG,PNG,GIF,WEBP}', GLOB_BRACE);
                if (!empty($files)) {
                    $this->log('在子目录找到图片文件: ' . $sub_path);
                    return $sub_path;
                }
            }
        }

        $this->log('未在目录及其子目录中找到图片文件');
        return false;
    }

    /**
     * 将图片导入为附件
     */
    private function import_image_as_attachment($image_path, $post_id, $filename)
    {
        $filename = sanitize_file_name($filename);
        $this->log('开始导入图像为附件: ' . $filename);

        if (!$filename || !file_exists($image_path)) {
            $this->log('图像文件不存在: ' . $image_path);
            return false;
        }

        $allowed_mimes = [
            'jpg|jpeg|jpe' => 'image/jpeg',
            'gif' => 'image/gif',
            'png' => 'image/png',
            'webp' => 'image/webp',
        ];
        $wp_filetype = wp_check_filetype($filename, $allowed_mimes);
        $image_size = @getimagesize($image_path);

        if (empty($wp_filetype['type']) || false === $image_size || empty($image_size['mime']) || 0 !== strpos($image_size['mime'], 'image/')) {
            $this->log('图像文件类型无效: ' . $filename);
            return false;
        }

        $upload_dir = wp_upload_dir();

        // 创建唯一的文件名
        $unique_filename = wp_unique_filename($upload_dir['path'], $filename);
        $new_filepath = $upload_dir['path'] . '/' . $unique_filename;

        $this->log('目标文件路径: ' . $new_filepath);

        // 复制文件到上传目录
        if (copy($image_path, $new_filepath)) {
            $this->log('文件复制成功');

            $this->log('文件类型: ' . $wp_filetype['type']);

            // 创建附件元数据
            $attachment = [
                'post_mime_type' => $wp_filetype['type'],
                'post_title' => sanitize_file_name(pathinfo($filename, PATHINFO_FILENAME)),
                'post_content' => '',
                'post_status' => 'inherit'
            ];

            // 插入附件
            $attach_id = wp_insert_attachment($attachment, $new_filepath, $post_id);

            if (is_wp_error($attach_id)) {
                $this->log('附件插入失败: ' . $attach_id->get_error_message());
                unlink($new_filepath);
                return false;
            }

            $this->log('附件插入成功，ID: ' . $attach_id);

            // 生成并更新附件元数据
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $attach_data = wp_generate_attachment_metadata($attach_id, $new_filepath);
            wp_update_attachment_metadata($attach_id, $attach_data);

            $this->log('附件元数据更新完成');

            return $attach_id;
        } else {
            $this->log('文件复制失败');
        }

        return false;
    }

    /**
     * 显示导入结果通知
     */
    public function import_notices()
    {
        if (isset($_GET['import_success'])) {
            $result = get_transient('jc_import_result');
            if ($result) {
                printf(
                    '<div class="notice notice-success is-dismissible"><p>%s %d %s, %d %s. <a href="%s">%s</a></p></div>',
                    esc_html__('Successfully imported', 'jelly-catalog'),
                    absint($result['imported']),
                    esc_html__('products', 'jelly-catalog'),
                    absint($result['errors']),
                    esc_html__('errors', 'jelly-catalog'),
                    esc_url(admin_url('edit.php?post_type=product&page=products-port')),
                    esc_html__('View detailed log', 'jelly-catalog')
                );
                delete_transient('jc_import_result');
            }
        }

        if (isset($_GET['import_error'])) {
            $error_msg = '';
            switch (sanitize_key(wp_unslash($_GET['import_error']))) {
                case 'file_upload_failed':
                    $error_msg = __('File upload failed.', 'jelly-catalog');
                    break;
                case 'invalid_csv_file':
                    $error_msg = __('Invalid CSV file.', 'jelly-catalog');
                    break;
                case 'cannot_read_file':
                    $error_msg = __('Cannot read uploaded file.', 'jelly-catalog');
                    break;
                default:
                    $error_msg = __('An unknown error occurred during import.', 'jelly-catalog');
            }

            printf('<div class="notice notice-error is-dismissible"><p>%s</p></div>', esc_html($error_msg));
        }
    }

    /**
     * 递归删除目录
     */
    private function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != '.' && $object != '..') {
                    if (is_dir($dir . '/' . $object)) {
                        $this->rrmdir($dir . '/' . $object);
                    } else {
                        unlink($dir . '/' . $object);
                    }
                }
            }
            rmdir($dir);
        }
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
JC_Port::instance();
