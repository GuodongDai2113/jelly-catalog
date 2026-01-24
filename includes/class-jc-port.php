<?php

/**
 * includes\class-jc-port.php
 * 
 * @see: https://jellydai.com
 * @author: Jelly Dai <daiguo1003@gmail.com>
 * @created : 2025.12.20 13:48
 */

if (! defined('ABSPATH')) exit; // 禁止直接访问

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
        add_action('admin_enqueue_scripts', array($this, 'enqueue_script'));
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_post_jc_export_products', array($this, 'export_products'));
        add_action('admin_post_jc_import_products', array($this, 'import_products'));
        add_action('admin_post_jc_clear_import_log', array($this, 'clear_import_log'));
        add_action('admin_notices', array($this, 'import_notices'));

        // 设置日志文件路径
        $upload_dir = wp_upload_dir();
        $this->log_file = $upload_dir['basedir'] . '/jc_import_log.txt';
    }


    /**
     * 后台加载所需脚本与样式
     *
     * @return void
     */
    public function enqueue_script() {}

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
            array($this, 'render_products_port')
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
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
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
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'clear_import_log')) {
            wp_die(__('Security check failed', 'jelly-catalog'));
        }

        // 删除日志文件
        if (file_exists($this->log_file)) {
            unlink($this->log_file);
        }

        // 重定向回导入导出页面
        wp_redirect(admin_url('edit.php?post_type=product&page=products-port'));
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
     * 导出产品
     */
    public function export_products()
    {
        // 检查权限
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'jelly-catalog'));
        }

        // 验证 nonce
        if (!isset($_POST['jc_export_nonce']) || !wp_verify_nonce($_POST['jc_export_nonce'], 'jc_export_products')) {
            wp_die(__('Security check failed', 'jelly-catalog'));
        }

        // 获取所有产品
        $products = get_posts(array(
            'post_type' => 'product',
            'post_status' => 'any',
            'numberposts' => -1,
            'orderby' => 'ID',
            'order' => 'ASC'
        ));

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
        $headers = array(
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
        );
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
            $gallery_images = array();
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
            $categories = wp_get_post_terms($post_id, 'product_cat', array('fields' => 'names'));
            $tags = wp_get_post_terms($post_id, 'product_tag', array('fields' => 'names'));

            // 获取FAQ和属性
            $faqs = get_post_meta($post_id, '_product_faqs', true);
            $faqs_serialized = $faqs ? base64_encode(json_encode($faqs)) : '';

            $attributes = get_post_meta($post_id, '_product_attributes', true);
            $attributes_serialized = $attributes ? base64_encode(json_encode($attributes)) : '';

            // 写入产品数据到CSV
            $row = array(
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
            );

            fputcsv($fp, $row);
        }

        fclose($fp);

        // 打包为ZIP文件
        if (class_exists('ZipArchive')) {
            $zip_file = $export_dir . '.zip';
            $zip = new ZipArchive();
            if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
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
        // 检查权限
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'jelly-catalog'));
        }

        // 验证 nonce
        if (!isset($_POST['jc_import_nonce']) || !wp_verify_nonce($_POST['jc_import_nonce'], 'jc_import_products')) {
            wp_die(__('Security check failed', 'jelly-catalog'));
        }

        // 清空之前的日志
        if (file_exists($this->log_file)) {
            unlink($this->log_file);
        }

        $this->log("开始导入产品");

        // 检查文件上传
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $this->log("CSV文件上传失败: " . ($_FILES['csv_file']['error'] ?? '未知错误'));
            wp_redirect(add_query_arg('import_error', 'file_upload_failed', wp_get_referer()));
            exit;
        }

        $this->log("CSV文件上传成功: " . $_FILES['csv_file']['name']);

        // 创建临时目录用于解压文件
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/jc_import_' . time();
        wp_mkdir_p($temp_dir);

        $this->log("创建临时目录: " . $temp_dir);

        // 解压图片ZIP文件（如果提供了）
        $images_path = '';
        if (isset($_FILES['images_zip']) && $_FILES['images_zip']['error'] === UPLOAD_ERR_OK) {
            $this->log("开始解压图片ZIP文件: " . $_FILES['images_zip']['name']);
            $zip = new ZipArchive;
            if ($zip->open($_FILES['images_zip']['tmp_name']) === TRUE) {
                if ($zip->extractTo($temp_dir . '/images/')) {
                    $zip->close();
                    $images_path = $temp_dir . '/images/';
                    $this->log("图片ZIP文件解压成功到: " . $images_path);

                    // 查找实际的图片目录
                    $actual_images_path = $this->find_images_directory($images_path);
                    if ($actual_images_path !== false) {
                        $images_path = $actual_images_path;
                        $this->log("找到实际图片目录: " . $images_path);
                    } else {
                        $this->log("警告：未找到有效的图片目录");
                    }
                } else {
                    $this->log("图片ZIP文件解压失败");
                    $zip->close();
                }
            } else {
                $this->log("无法打开图片ZIP文件");
            }
        } else {
            $this->log("未提供图片ZIP文件或上传失败，错误代码: " . ($_FILES['images_zip']['error'] ?? '无文件'));
        }

        // 处理CSV文件
        $csv_file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($csv_file, 'r');

        if (!$handle) {
            $this->log("无法打开CSV文件进行读取");
            $this->rrmdir($temp_dir);
            wp_redirect(add_query_arg('import_error', 'cannot_read_file', wp_get_referer()));
            exit;
        }

        $this->log("开始读取CSV文件");

        // 跳过BOM头
        $bom = fread($handle, 3);
        if ($bom !== chr(0xEF) . chr(0xBB) . chr(0xBF)) {
            rewind($handle);
        }

        // 读取表头
        $headers = fgetcsv($handle);
        $this->log("读取到表头: " . implode(', ', $headers));

        // 确定FAQ和属性的数量
        $faq_count = 0;
        $attribute_count = 0;

        foreach ($headers as $header) {
            if (strpos($header, 'FAQ_Q_') === 0) {
                $faq_num = (int)substr($header, 6);
                if ($faq_num > $faq_count) {
                    $faq_count = $faq_num;
                }
            } elseif (strpos($header, 'Attribute_Name_') === 0) {
                $attr_num = (int)substr($header, 15);
                if ($attr_num > $attribute_count) {
                    $attribute_count = $attr_num;
                }
            }
        }

        $this->log("检测到FAQ数量: " . $faq_count);
        $this->log("检测到属性数量: " . $attribute_count);

        // 存储导入结果
        $imported = 0;
        $errors = 0;

        // 处理每行数据
        while (($row = fgetcsv($handle)) !== FALSE) {
            $data = array_combine($headers, $row);
            $this->log("处理产品: " . $data['Title'] . " (ID: " . ($data['ID'] ?? 'new') . ")");

            // 创建或更新产品
            $post_data = array(
                'post_type' => 'product',
                'post_title' => $data['Title'],
                'post_excerpt' => $data['Short Description'],
                'post_content' => $data['Description'],
                'post_status' => 'publish'
            );

            // 如果有ID则尝试更新现有产品
            if (!empty($data['ID'])) {
                $existing_post = get_post($data['ID']);
                if ($existing_post && $existing_post->post_type === 'product') {
                    $post_data['ID'] = $data['ID'];
                    $this->log("更新现有产品 ID: " . $data['ID']);
                } else {
                    $this->log("指定的产品ID不存在，将创建新产品: " . $data['ID']);
                }
            }

            $post_id = wp_insert_post($post_data);

            if (is_wp_error($post_id)) {
                $this->log("创建/更新产品失败: " . $post_id->get_error_message());
                $errors++;
                continue;
            }

            $this->log("产品保存成功，ID: " . $post_id);

            // 处理特色图像
            if (!empty($data['Featured Image'])) {
                $image_name = basename($data['Featured Image']);
                $image_path = $images_path . $image_name;

                $this->log("处理特色图像: " . $image_name);
                $this->log("图像路径: " . $image_path);

                if (file_exists($image_path)) {
                    $attachment_id = $this->import_image_as_attachment($image_path, $post_id, $image_name);
                    if ($attachment_id) {
                        set_post_thumbnail($post_id, $attachment_id);
                        $this->log("特色图像设置成功，附件ID: " . $attachment_id);
                    } else {
                        $this->log("特色图像导入失败");
                    }
                } else {
                    // 尝试在子目录中查找
                    $found_image = $this->find_file_in_subdirectories($images_path, $image_name);
                    if ($found_image !== false) {
                        $this->log("在子目录中找到图像: " . $found_image);
                        $attachment_id = $this->import_image_as_attachment($found_image, $post_id, $image_name);
                        if ($attachment_id) {
                            set_post_thumbnail($post_id, $attachment_id);
                            $this->log("特色图像设置成功，附件ID: " . $attachment_id);
                        } else {
                            $this->log("特色图像导入失败");
                        }
                    } else {
                        $this->log("特色图像文件不存在: " . $image_path);
                    }
                }
            }

            // 处理画廊图像
            if (!empty($data['Gallery Images'])) {
                $gallery_images = explode(',', $data['Gallery Images']);
                $gallery_ids = array();

                $this->log("处理画廊图像，共 " . count($gallery_images) . " 张图片");

                foreach ($gallery_images as $image_filename) {
                    $image_name = basename($image_filename);
                    $image_path = $images_path . $image_name;

                    $this->log("处理画廊图像: " . $image_name);

                    if (file_exists($image_path)) {
                        $attachment_id = $this->import_image_as_attachment($image_path, $post_id, $image_name);
                        if ($attachment_id) {
                            $gallery_ids[] = $attachment_id;
                            $this->log("画廊图像导入成功，附件ID: " . $attachment_id);
                        } else {
                            $this->log("画廊图像导入失败: " . $image_name);
                        }
                    } else {
                        // 尝试在子目录中查找
                        $found_image = $this->find_file_in_subdirectories($images_path, $image_name);
                        if ($found_image !== false) {
                            $this->log("在子目录中找到图像: " . $found_image);
                            $attachment_id = $this->import_image_as_attachment($found_image, $post_id, $image_name);
                            if ($attachment_id) {
                                $gallery_ids[] = $attachment_id;
                                $this->log("画廊图像导入成功，附件ID: " . $attachment_id);
                            } else {
                                $this->log("画廊图像导入失败: " . $image_name);
                            }
                        } else {
                            $this->log("画廊图像文件不存在: " . $image_path);
                        }
                    }
                }

                if (!empty($gallery_ids)) {
                    update_post_meta($post_id, '_product_image_gallery', implode(',', $gallery_ids));
                    $this->log("画廊图像元数据已保存，IDs: " . implode(',', $gallery_ids));
                }
            }

            // 处理分类
            if (!empty($data['Categories'])) {
                $categories = explode('|', $data['Categories']);
                // 确保分类存在
                $category_ids = array();
                foreach ($categories as $category_name) {
                    $category_name = trim($category_name);
                    if (empty($category_name)) continue;

                    $category = get_term_by('name', $category_name, 'product_cat');
                    if (!$category) {
                        $new_category = wp_insert_term($category_name, 'product_cat');
                        if (!is_wp_error($new_category)) {
                            $category_ids[] = $new_category['term_id'];
                            $this->log("创建新分类: " . $category_name . " (ID: " . $new_category['term_id'] . ")");
                        } else {
                            $this->log("创建分类失败: " . $category_name . " (" . $new_category->get_error_message() . ")");
                        }
                    } else {
                        $category_ids[] = $category->term_id;
                        $this->log("使用现有分类: " . $category_name . " (ID: " . $category->term_id . ")");
                    }
                }
                wp_set_post_terms($post_id, $category_ids, 'product_cat');
                $this->log("产品分类已设置，IDs: " . implode(',', $category_ids));
            }

            // 处理标签
            if (!empty($data['Tags'])) {
                $tags = explode('|', $data['Tags']);
                // 确保标签存在
                $tag_ids = array();
                foreach ($tags as $tag_name) {
                    $tag_name = trim($tag_name);
                    if (empty($tag_name)) continue;

                    $tag = get_term_by('name', $tag_name, 'product_tag');
                    if (!$tag) {
                        $new_tag = wp_insert_term($tag_name, 'product_tag');
                        if (!is_wp_error($new_tag)) {
                            $tag_ids[] = $new_tag['term_id'];
                            $this->log("创建新标签: " . $tag_name . " (ID: " . $new_tag['term_id'] . ")");
                        } else {
                            $this->log("创建标签失败: " . $tag_name . " (" . $new_tag->get_error_message() . ")");
                        }
                    } else {
                        $tag_ids[] = $tag->term_id;
                        $this->log("使用现有标签: " . $tag_name . " (ID: " . $tag->term_id . ")");
                    }
                }
                wp_set_post_terms($post_id, $tag_ids, 'product_tag');
                $this->log("产品标签已设置，IDs: " . implode(',', $tag_ids));
            }

            // 处理FAQ
            $faqs = array();
            for ($i = 1; $i <= $faq_count; $i++) {
                $question_key = 'FAQ_Q_' . $i;
                $answer_key = 'FAQ_A_' . $i;

                if (!empty($data[$question_key]) || !empty($data[$answer_key])) {
                    $faqs[] = array(
                        'name' => isset($data[$question_key]) ? $data[$question_key] : '',
                        'value' => isset($data[$answer_key]) ? $data[$answer_key] : ''
                    );
                }
            }

            if (!empty($faqs)) {
                update_post_meta($post_id, '_product_faqs', $faqs);
                $this->log("FAQ数据已保存，共 " . count($faqs) . " 条");
            }

            // 处理属性
            $attributes = array();
            for ($i = 1; $i <= $attribute_count; $i++) {
                $name_key = 'Attribute_Name_' . $i;
                $value_key = 'Attribute_Value_' . $i;

                if (!empty($data[$name_key]) || !empty($data[$value_key])) {
                    $attributes[] = array(
                        'name' => isset($data[$name_key]) ? $data[$name_key] : '',
                        'value' => isset($data[$value_key]) ? $data[$value_key] : ''
                    );
                }
            }

            if (!empty($attributes)) {
                update_post_meta($post_id, '_product_attributes', $attributes);
                $this->log("属性数据已保存，共 " . count($attributes) . " 条");
            }

            $imported++;
            $this->log("产品处理完成: " . $data['Title'] . "\n---");
        }

        fclose($handle);

        // 清理临时文件
        $this->rrmdir($temp_dir);
        $this->log("导入过程结束，清理临时文件");

        // 设置通知消息
        set_transient('jc_import_result', array(
            'imported' => $imported,
            'errors' => $errors
        ), 60);

        $this->log("导入完成 - 成功: {$imported}, 失败: {$errors}");

        wp_redirect(add_query_arg('import_success', 1, wp_get_referer()));
        exit;
    }

    /**
     * 在目录及其子目录中查找图片文件
     */
    private function find_file_in_subdirectories($root_path, $filename)
    {
        $this->log("在目录 {$root_path} 及其子目录中搜索文件: {$filename}");

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root_path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getFilename() === $filename) {
                $this->log("找到文件: " . $file->getPathname());
                return $file->getPathname();
            }
        }

        $this->log("未找到文件: {$filename}");
        return false;
    }

    /**
     * 查找图片目录
     */
    private function find_images_directory($path)
    {
        $this->log("查找图片目录: " . $path);

        // 检查当前目录是否包含图片文件
        $files = glob($path . "*.{jpg,jpeg,png,gif,webp,JPG,JPEG,PNG,GIF,WEBP}", GLOB_BRACE);
        if (!empty($files)) {
            $this->log("在当前目录找到图片文件");
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
                $files = glob($sub_path . "*.{jpg,jpeg,png,gif,webp,JPG,JPEG,PNG,GIF,WEBP}", GLOB_BRACE);
                if (!empty($files)) {
                    $this->log("在子目录找到图片文件: " . $sub_path);
                    return $sub_path;
                }
            }
        }

        $this->log("未在目录及其子目录中找到图片文件");
        return false;
    }

    /**
     * 将图片导入为附件
     */
    private function import_image_as_attachment($image_path, $post_id, $filename)
    {
        $this->log("开始导入图像为附件: " . $filename);

        if (!file_exists($image_path)) {
            $this->log("图像文件不存在: " . $image_path);
            return false;
        }

        $upload_dir = wp_upload_dir();

        // 创建唯一的文件名
        $unique_filename = wp_unique_filename($upload_dir['path'], $filename);
        $new_filepath = $upload_dir['path'] . '/' . $unique_filename;

        $this->log("目标文件路径: " . $new_filepath);

        // 复制文件到上传目录
        if (copy($image_path, $new_filepath)) {
            $this->log("文件复制成功");

            // 获取文件类型
            $wp_filetype = wp_check_filetype($filename, null);
            $this->log("文件类型: " . $wp_filetype['type']);

            // 创建附件元数据
            $attachment = array(
                'post_mime_type' => $wp_filetype['type'],
                'post_title' => sanitize_file_name(pathinfo($filename, PATHINFO_FILENAME)),
                'post_content' => '',
                'post_status' => 'inherit'
            );

            // 插入附件
            $attach_id = wp_insert_attachment($attachment, $new_filepath, $post_id);

            if (is_wp_error($attach_id)) {
                $this->log("附件插入失败: " . $attach_id->get_error_message());
                return false;
            }

            $this->log("附件插入成功，ID: " . $attach_id);

            // 生成并更新附件元数据
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attach_data = wp_generate_attachment_metadata($attach_id, $new_filepath);
            wp_update_attachment_metadata($attach_id, $attach_data);

            $this->log("附件元数据更新完成");

            return $attach_id;
        } else {
            $this->log("文件复制失败");
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
                    '<div class="notice notice-success is-dismissible"><p>%s %d %s, %d %s。<a href="%s">%s</a></p></div>',
                    __('成功导入', 'jelly-catalog'),
                    $result['imported'],
                    __('个产品', 'jelly-catalog'),
                    $result['errors'],
                    __('个错误', 'jelly-catalog'),
                    admin_url('edit.php?post_type=product&page=products-port'),
                    __('查看详细日志', 'jelly-catalog')
                );
                delete_transient('jc_import_result');
            }
        }

        if (isset($_GET['import_error'])) {
            $error_msg = '';
            switch ($_GET['import_error']) {
                case 'file_upload_failed':
                    $error_msg = __('文件上传失败。', 'jelly-catalog');
                    break;
                case 'cannot_read_file':
                    $error_msg = __('无法读取上传的文件。', 'jelly-catalog');
                    break;
                default:
                    $error_msg = __('导入过程中发生未知错误。', 'jelly-catalog');
            }

            printf('<div class="notice notice-error is-dismissible"><p>%s</p></div>', $error_msg);
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
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object))
                        $this->rrmdir($dir . "/" . $object);
                    else
                        unlink($dir . "/" . $object);
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
            if ($filename != "." && $filename != "..") {
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
