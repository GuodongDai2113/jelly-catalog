<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 产品导入、断点续传与导入日志能力。
 */
trait JC_Port_Import
{
    /**
     * 日志文件路径
     */
    private $log_file;

    /**
     * 写入日志
     */
    private function log($message, $level = 'debug')
    {
        $levels = [
            'debug' => 0,
            'notice' => 1,
            'warning' => 2,
            'error' => 3,
        ];
        $level = isset($levels[$level]) ? $level : 'debug';
        $min_level = apply_filters('jc_import_log_min_level', 'notice');
        $min_level = isset($levels[$min_level]) ? $min_level : 'notice';

        if ($levels[$level] < $levels[$min_level]) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($this->log_file, "[{$timestamp}] [" . strtoupper($level) . "] {$message}\n", FILE_APPEND | LOCK_EX);
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
                'retryable' => $this->is_import_error_retryable($job),
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
            $status_code = $this->is_import_error_retryable($job) ? 500 : 400;

            wp_send_json_error([
                'code' => $this->get_import_error_code($job),
                'message' => $job->get_error_message(),
                'retryable' => $this->is_import_error_retryable($job),
            ], $status_code);
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

        $this->cleanup_stale_import_temp_dirs();

        if (file_exists($this->log_file)) {
            unlink($this->log_file);
        }

        $this->log('开始导入产品', 'notice');

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $this->log('CSV文件上传失败: ' . ($_FILES['csv_file']['error'] ?? '未知错误'), 'error');
            return new WP_Error('file_upload_failed', __('File upload failed.', 'jelly-catalog'));
        }

        $csv_name = sanitize_file_name($_FILES['csv_file']['name']);
        if ('csv' !== strtolower(pathinfo($csv_name, PATHINFO_EXTENSION))) {
            $this->log('CSV文件扩展名无效: ' . $csv_name, 'error');
            return new WP_Error('invalid_csv_file', __('Invalid CSV file.', 'jelly-catalog'));
        }

        $this->log('CSV文件上传成功: ' . $csv_name);

        $upload_dir = wp_upload_dir();
        $job_id = sanitize_key(str_replace('-', '', wp_generate_uuid4()));
        $temp_dir = trailingslashit($upload_dir['basedir']) . 'jc_import_' . $job_id;

        if (!wp_mkdir_p($temp_dir)) {
            $this->log('无法创建临时目录: ' . $temp_dir, 'error');
            return new WP_Error('cannot_read_file', __('Cannot read uploaded file.', 'jelly-catalog'));
        }

        $this->log('创建临时目录: ' . $temp_dir);

        $csv_file = trailingslashit($temp_dir) . 'products.csv';
        if (!$this->move_uploaded_file_to_path($_FILES['csv_file']['tmp_name'], $csv_file)) {
            $this->log('无法保存CSV文件到临时目录', 'error');
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
            'max_retries' => max(0, absint(apply_filters('jc_import_row_max_retries', 2))),
            'message' => __('Import job prepared. Starting batch import...', 'jelly-catalog'),
            'created_at' => time(),
            'updated_at' => time(),
        ];

        $this->save_import_job($job);
        $this->log('导入任务已创建，产品总数: ' . $job['total'], 'notice');

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

        $lock_ttl = max(120, absint(apply_filters('jc_import_batch_lock_ttl', 300)));
        $locked_at = !empty($job['locked_at']) ? (int) $job['locked_at'] : 0;
        if ($locked_at && time() - $locked_at < $lock_ttl) {
            $job['status'] = 'waiting';
            $job['message'] = __('Another import request is still running. Checking again...', 'jelly-catalog');
            $job['next_delay'] = max(500, absint(apply_filters('jc_import_lock_wait_delay', 2000)));
            return $job;
        }

        if ($locked_at) {
            $job = $this->handle_expired_import_lock($job);
            if (!empty($job['status']) && 'waiting' === $job['status']) {
                return $job;
            }
        }

        $lock_token = wp_generate_uuid4();
        $job['locked_at'] = time();
        $job['lock_token'] = $lock_token;
        $job['status'] = 'running';
        $this->save_import_job($job);

        $handle = null;

        try {
            $csv_file = $job['csv_file'] ?? '';
            if (!$csv_file || !file_exists($csv_file)) {
                $this->log('导入任务CSV文件不存在', 'error');
                unset($job['locked_at'], $job['lock_token']);
                $job['status'] = 'error';
                $job['message'] = __('Cannot read uploaded file.', 'jelly-catalog');
                $this->save_import_job($job);
                return new WP_Error('cannot_read_file', __('Cannot read uploaded file.', 'jelly-catalog'), ['retryable' => false]);
            }

            $handle = fopen($csv_file, 'r');
            if (!$handle) {
                $this->log('无法打开导入任务CSV文件', 'error');
                unset($job['locked_at'], $job['lock_token']);
                $job['status'] = 'error';
                $job['message'] = __('Cannot read uploaded file.', 'jelly-catalog');
                $this->save_import_job($job);
                return new WP_Error('cannot_read_file', __('Cannot read uploaded file.', 'jelly-catalog'), ['retryable' => false]);
            }

            if (0 !== fseek($handle, (int) ($job['offset'] ?? 0))) {
                $this->log('无法定位CSV断点位置: ' . (int) ($job['offset'] ?? 0), 'error');
                fclose($handle);
                $handle = null;
                unset($job['locked_at'], $job['lock_token']);
                $job['status'] = 'error';
                $job['message'] = __('Cannot read uploaded file.', 'jelly-catalog');
                $this->save_import_job($job);
                return new WP_Error('cannot_read_file', __('Cannot read uploaded file.', 'jelly-catalog'), ['retryable' => false]);
            }

            $batch_size = max(1, absint(apply_filters('jc_import_batch_size', 5)));
            $time_limit = max(5, absint(apply_filters('jc_import_batch_time_limit', 20)));
            $started_at = microtime(true);
            $processed_in_batch = 0;
            $image_index = $this->load_image_index($job['image_index_file'] ?? '');

            while ($processed_in_batch < $batch_size && (microtime(true) - $started_at) < $time_limit) {
                $job['locked_at'] = time();
                $job['lock_token'] = $lock_token;
                $row_start_offset = ftell($handle);
                $row = fgetcsv($handle);
                if (false === $row) {
                    break;
                }

                $row_end_offset = ftell($handle);
                $row_number = absint($job['processed']) + 1;
                $job['current_row'] = $this->prepare_import_current_row(
                    $job,
                    $row,
                    $row_number,
                    false !== $row_start_offset ? $row_start_offset : (int) ($job['offset'] ?? 0),
                    false !== $row_end_offset ? $row_end_offset : 0
                );
                $this->save_import_job($job);

                $result = $this->process_import_row_with_retries(
                    $row,
                    $job['headers'],
                    absint($job['faq_count']),
                    absint($job['attribute_count']),
                    $job['images_path'] ?? '',
                    $image_index,
                    $job,
                    $row_number
                );

                if (false !== $row_end_offset) {
                    $job['offset'] = $row_end_offset;
                }

                $job['processed'] = absint($job['processed']) + 1;
                if ($result) {
                    $job['imported'] = absint($job['imported']) + 1;
                } else {
                    $job['errors'] = absint($job['errors']) + 1;
                    $this->log_import_current_row_failure(
                        $job['current_row'],
                        __('Product import failed after retries. Skipping this row.', 'jelly-catalog')
                    );
                }

                unset($job['current_row']);
                $processed_in_batch++;
                $job['locked_at'] = time();
                $job['lock_token'] = $lock_token;
                $job['updated_at'] = time();
                $job['message'] = sprintf(
                    /* translators: 1: processed count, 2: total count */
                    __('Processed %1$d of %2$d products...', 'jelly-catalog'),
                    absint($job['processed']),
                    absint($job['total'])
                );
                $this->save_import_job($job);
            }

            fclose($handle);
            $handle = null;
        } catch (Throwable $error) {
            if (is_resource($handle)) {
                fclose($handle);
            }

            unset($job['locked_at'], $job['lock_token']);
            $job['status'] = 'error';
            $job['message'] = __('Temporary import error. Retrying from the last saved position...', 'jelly-catalog');
            $job['last_error'] = $error->getMessage();
            $job['updated_at'] = time();
            $this->save_import_job($job);
            $this->log('导入批次发生异常: ' . $error->getMessage(), 'warning');

            return new WP_Error(
                'import_batch_failed',
                __('Temporary import error. The import can be retried from the last saved position.', 'jelly-catalog'),
                ['retryable' => true]
            );
        }

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
            $this->log("导入完成 - 成功: {$job['imported']}, 失败: {$job['errors']}", 'notice');
        } else {
            $job['status'] = 'running';
            $job['message'] = sprintf(
                /* translators: 1: processed count, 2: total count */
                __('Processed %1$d of %2$d products...', 'jelly-catalog'),
                absint($job['processed']),
                absint($job['total'])
            );
        }

        unset($job['locked_at'], $job['lock_token']);
        $job['updated_at'] = time();
        $this->save_import_job($job);

        return $job;
    }

    /**
     * 处理过期导入锁，必要时跳过导致请求中断的当前行。
     */
    private function handle_expired_import_lock($job)
    {
        unset($job['locked_at'], $job['lock_token']);
        $this->log('导入锁已过期，接管任务继续处理', 'warning');

        if (empty($job['current_row']) || !is_array($job['current_row'])) {
            return $job;
        }

        $current_row = $job['current_row'];
        $started_at = absint($current_row['started_at'] ?? 0);
        $grace_period = max(300, absint(apply_filters('jc_import_fatal_row_grace_period', 900)));
        if ($started_at && time() - $started_at < $grace_period) {
            $job['status'] = 'waiting';
            $job['message'] = __('The current row is still within the safe wait window. Checking again...', 'jelly-catalog');
            $job['next_delay'] = max(1000, absint(apply_filters('jc_import_lock_wait_delay', 2000)));
            $this->save_import_job($job);

            return $job;
        }

        $max_fatal_retries = max(0, absint(apply_filters('jc_import_fatal_row_max_retries', 0)));
        $fatal_attempts = absint($current_row['fatal_attempts'] ?? 0) + 1;
        $current_row['fatal_attempts'] = $fatal_attempts;
        $job['current_row'] = $current_row;

        if ($fatal_attempts <= $max_fatal_retries) {
            $this->log_import_current_row_failure(
                $current_row,
                sprintf(
                    /* translators: 1: attempt count, 2: max retry count */
                    __('The previous request stopped while importing this row. Retrying it (%1$d/%2$d).', 'jelly-catalog'),
                    $fatal_attempts,
                    $max_fatal_retries
                )
            );
            $this->save_import_job($job);

            return $job;
        }

        $end_offset = absint($current_row['end_offset'] ?? 0);
        if ($end_offset <= 0) {
            $this->log_import_current_row_failure(
                $current_row,
                __('The previous request stopped on this row, but no safe CSV offset was saved. Retrying from the last stable position.', 'jelly-catalog')
            );
            unset($job['current_row']);
            $this->save_import_job($job);

            return $job;
        }

        $job['offset'] = $end_offset;
        $job['processed'] = absint($job['processed']) + 1;
        $job['errors'] = absint($job['errors']) + 1;
        $job['updated_at'] = time();
        $job['status'] = 'running';
        $job['message'] = sprintf(
            /* translators: 1: processed count, 2: total count */
            __('Processed %1$d of %2$d products...', 'jelly-catalog'),
            absint($job['processed']),
            absint($job['total'])
        );
        unset($job['current_row']);

        $this->log_import_current_row_failure(
            $current_row,
            __('The previous request stopped while importing this row. Skipping this product and continuing.', 'jelly-catalog')
        );
        $this->save_import_job($job);

        return $job;
    }

    /**
     * 保存当前正在处理的 CSV 行信息，便于请求中断后定位问题行。
     */
    private function prepare_import_current_row($job, $row, $row_number, $row_start_offset, $row_end_offset)
    {
        $row_key = $this->get_import_row_key($job, $row, $row_number);
        $summary = $this->get_import_row_summary($row, $job['headers'] ?? []);
        $fatal_attempts = 0;

        if (!empty($job['current_row']['key']) && hash_equals((string) $job['current_row']['key'], $row_key)) {
            $fatal_attempts = absint($job['current_row']['fatal_attempts'] ?? 0);
        }

        return [
            'key' => $row_key,
            'row_number' => absint($row_number),
            'start_offset' => absint($row_start_offset),
            'end_offset' => absint($row_end_offset),
            'source_id' => $summary['source_id'],
            'title' => $summary['title'],
            'fatal_attempts' => $fatal_attempts,
            'started_at' => time(),
        ];
    }

    /**
     * 获取行摘要。
     */
    private function get_import_row_summary($row, $headers)
    {
        $data = [];
        if (is_array($headers) && count($headers) === count($row)) {
            $combined = array_combine($headers, $row);
            $data = is_array($combined) ? $combined : [];
        }

        return [
            'source_id' => isset($data['ID']) ? sanitize_text_field((string) $data['ID']) : '',
            'title' => isset($data['Title']) ? sanitize_text_field((string) $data['Title']) : '',
        ];
    }

    /**
     * 记录具体失败产品。
     */
    private function log_import_current_row_failure($current_row, $reason)
    {
        if (!is_array($current_row)) {
            $this->log($reason, 'warning');
            return;
        }

        $parts = [];
        if (!empty($current_row['row_number'])) {
            $parts[] = 'CSV行: ' . absint($current_row['row_number']);
        }

        if (!empty($current_row['source_id'])) {
            $parts[] = '原ID: ' . $current_row['source_id'];
        }

        if (!empty($current_row['title'])) {
            $parts[] = '标题: ' . $current_row['title'];
        }

        if (!empty($current_row['start_offset']) || !empty($current_row['end_offset'])) {
            $parts[] = 'offset: ' . absint($current_row['start_offset'] ?? 0) . '-' . absint($current_row['end_offset'] ?? 0);
        }

        $message = $parts ? implode('，', $parts) . '，原因: ' . $reason : $reason;
        $this->log($message, 'warning');
    }

    /**
     * 带重试处理单行产品数据。
     */
    private function process_import_row_with_retries($row, $headers, $faq_count, $attribute_count, $images_path, $image_index, $job, $row_number)
    {
        $max_retries = isset($job['max_retries']) ? max(0, absint($job['max_retries'])) : max(0, absint(apply_filters('jc_import_row_max_retries', 2)));
        $retry_delay = max(0, absint(apply_filters('jc_import_row_retry_delay', 250)));
        $row_key = $this->get_import_row_key($job, $row, $row_number);
        $attempt = 0;
        $last_error = '';

        while ($attempt <= $max_retries) {
            $attempt++;

            try {
                $result = $this->process_import_row(
                    $row,
                    $headers,
                    $faq_count,
                    $attribute_count,
                    $images_path,
                    $image_index,
                    $row_key
                );

                if ($result) {
                    return true;
                }

                $last_error = 'row returned false';
                $this->log('第 ' . $row_number . ' 行导入失败，尝试次数: ' . $attempt . '/' . ($max_retries + 1));
            } catch (Throwable $error) {
                $last_error = $error->getMessage();
                $this->log('第 ' . $row_number . ' 行导入异常: ' . $last_error . '，尝试次数: ' . $attempt . '/' . ($max_retries + 1));
            }

            if ($attempt <= $max_retries && $retry_delay > 0) {
                usleep($retry_delay * 1000 * min($attempt, 5));
            }
        }

        if ($last_error) {
            $this->log('第 ' . $row_number . ' 行重试结束，仍然失败: ' . $last_error, 'warning');
        }

        return false;
    }

    /**
     * 获取导入行的幂等标记。
     */
    private function get_import_row_key($job, $row, $row_number)
    {
        return md5(($job['id'] ?? '') . '|' . absint($row_number) . '|' . wp_json_encode($row));
    }

    /**
     * 处理单行产品数据。
     */
    private function process_import_row($row, $headers, $faq_count, $attribute_count, $images_path, $image_index, $row_key = '')
    {
        $data = array_combine($headers, $row);
        if (false === $data) {
            $this->log('CSV列数不匹配，跳过该行。Headers: ' . count($headers) . ' Row: ' . count($row), 'warning');
            return false;
        }

        $row_key = $row_key ? sanitize_key($row_key) : '';
        $post_id = $this->save_import_post($data, $row_key);
        if (!$post_id) {
            return false;
        }

        if ($row_key) {
            update_post_meta($post_id, '_jc_import_row_key', $row_key);
        }

        $this->maybe_import_featured_image($post_id, $data, $images_path, $image_index);
        $this->maybe_import_gallery_images($post_id, $data, $images_path, $image_index);
        $this->maybe_update_import_terms($post_id, $data, 'Categories', 'product_cat');
        $this->maybe_update_import_terms($post_id, $data, 'Tags', 'product_tag');
        $this->maybe_update_import_collection_meta($post_id, '_product_faqs', $this->parse_import_faqs($data, $faq_count), 'FAQ数据');
        $this->maybe_update_import_collection_meta($post_id, '_product_attributes', $this->parse_import_attributes($data, $attribute_count), '属性数据');

        $title = array_key_exists('Title', $data) ? (string) $data['Title'] : '';
        $this->log('产品处理完成: ' . $title . "\n---");

        return true;
    }

    /**
     * 创建或更新产品基础字段。缺列时跳过对应字段。
     */
    private function save_import_post($data, $row_key)
    {
        $title = array_key_exists('Title', $data) ? (string) $data['Title'] : '';
        $this->log('处理产品: ' . ($title !== '' ? $title : '(未提供标题字段)') . ' (ID: ' . ($data['ID'] ?? 'new') . ')');

        $existing_post_id = $this->find_existing_import_post_id($data, $row_key);
        $is_update = $existing_post_id > 0;
        $post_data = ['post_type' => 'product'];

        if ($is_update) {
            $post_data['ID'] = $existing_post_id;
        }

        $field_map = [
            'Title' => ['post_title', 'sanitize_text_field'],
            'Short Description' => ['post_excerpt', 'wp_kses_post'],
            'Description' => ['post_content', 'wp_kses_post'],
        ];

        foreach ($field_map as $csv_key => $field_config) {
            if (array_key_exists($csv_key, $data)) {
                $post_data[$field_config[0]] = call_user_func($field_config[1], $data[$csv_key] ?? '');
            }
        }

        if (!$is_update) {
            $post_data['post_status'] = 'publish';
        } elseif (array_key_exists('Status', $data)) {
            $post_status = sanitize_key($data['Status']);
            if (in_array($post_status, ['publish', 'draft', 'pending', 'private'], true)) {
                $post_data['post_status'] = $post_status;
            }
        }

        $post_field_updates = array_diff(array_keys($post_data), ['ID', 'post_type']);
        if ($is_update && empty($post_field_updates)) {
            $this->log('CSV未提供基础字段，跳过产品基础字段更新，ID: ' . $existing_post_id);
            return $existing_post_id;
        }

        $post_id = $is_update ? wp_update_post($post_data, true) : wp_insert_post($post_data, true);
        if (is_wp_error($post_id)) {
            $this->log('创建/更新产品失败: ' . $post_id->get_error_message(), 'warning');
            return 0;
        }

        if (!$post_id) {
            $this->log('创建/更新产品失败: 未返回有效产品ID', 'warning');
            return 0;
        }

        $this->log('产品保存成功，ID: ' . $post_id);

        return absint($post_id);
    }

    /**
     * 查找 CSV 指定的现有产品，或当前导入任务已创建过的产品。
     */
    private function find_existing_import_post_id($data, $row_key)
    {
        if (!empty($data['ID'])) {
            $existing_post_id = absint($data['ID']);
            $existing_post = get_post($existing_post_id);
            if ($existing_post && $existing_post->post_type === 'product') {
                $this->log('更新现有产品 ID: ' . $existing_post_id);
                return $existing_post_id;
            }

            $this->log('指定的产品ID不存在，将创建新产品: ' . $data['ID']);
        }

        if ($row_key) {
            $imported_post_id = $this->find_imported_post_by_row_key($row_key);
            if ($imported_post_id) {
                $this->log('从断点记录恢复已创建产品 ID: ' . $imported_post_id);
                return $imported_post_id;
            }
        }

        return 0;
    }

    /**
     * 按 CSV 特色图列导入或清空特色图。缺列时跳过。
     */
    private function maybe_import_featured_image($post_id, $data, $images_path, $image_index)
    {
        if (!array_key_exists('Featured Image', $data)) {
            return;
        }

        $image_reference = trim((string) $data['Featured Image']);
        if ('' === $image_reference) {
            delete_post_thumbnail($post_id);
            $this->log('特色图像已清空');
            return;
        }

        $attachment_id = $this->import_image_reference($image_reference, $post_id, $images_path, $image_index);
        if ($attachment_id) {
            set_post_thumbnail($post_id, $attachment_id);
            $this->log('特色图像设置成功，附件ID: ' . $attachment_id);
        } else {
            $this->log('特色图像导入失败: ' . $image_reference, 'warning');
        }
    }

    /**
     * 按 CSV 画廊列导入或清空画廊图。缺列时跳过。
     */
    private function maybe_import_gallery_images($post_id, $data, $images_path, $image_index)
    {
        if (!array_key_exists('Gallery Images', $data)) {
            return;
        }

        $gallery_value = trim((string) $data['Gallery Images']);
        if ('' === $gallery_value) {
            delete_post_meta($post_id, '_product_image_gallery');
            $this->log('画廊图像已清空');
            return;
        }

        $gallery_ids = [];
        $gallery_images = array_filter(array_map('trim', explode(',', $gallery_value)), 'strlen');
        $this->log('处理画廊图像，共 ' . count($gallery_images) . ' 张图片');

        foreach ($gallery_images as $image_reference) {
            $attachment_id = $this->import_image_reference($image_reference, $post_id, $images_path, $image_index);
            if ($attachment_id) {
                $gallery_ids[] = $attachment_id;
                $this->log('画廊图像导入成功，附件ID: ' . $attachment_id);
            } else {
                $this->log('画廊图像导入失败: ' . $image_reference, 'warning');
            }
        }

        if (!empty($gallery_ids)) {
            update_post_meta($post_id, '_product_image_gallery', implode(',', array_values(array_unique($gallery_ids))));
            $this->log('画廊图像元数据已保存，IDs: ' . implode(',', $gallery_ids));
        }
    }

    /**
     * 导入图片引用，并返回附件 ID。
     */
    private function import_image_reference($image_reference, $post_id, $images_path, $image_index)
    {
        $image_reference = $this->sanitize_import_image_reference($image_reference);
        if (!$image_reference) {
            return 0;
        }

        $image_path = $this->resolve_import_image_path($images_path, $image_reference, $image_index);
        $this->log('处理导入图像: ' . $image_reference);
        $this->log('图像路径: ' . ($image_path ?: ''));

        if (!$image_path) {
            $this->log('图像文件不存在: ' . $image_reference, 'warning');
            return 0;
        }

        return absint($this->import_image_as_attachment($image_path, $post_id, basename($image_reference), $image_reference));
    }

    /**
     * 更新分类或标签。缺列时跳过，列存在但为空时清空。
     */
    private function maybe_update_import_terms($post_id, $data, $column, $taxonomy)
    {
        if (!array_key_exists($column, $data)) {
            return;
        }

        $term_names = array_filter(array_map('trim', explode('|', (string) $data[$column])), 'strlen');
        $term_ids = [];

        foreach ($term_names as $term_name) {
            $term_name = sanitize_text_field($term_name);
            if ('' === $term_name) {
                continue;
            }

            $term = get_term_by('name', $term_name, $taxonomy);
            if (!$term) {
                $new_term = wp_insert_term($term_name, $taxonomy);
                if (is_wp_error($new_term)) {
                    $this->log('创建术语失败: ' . $term_name . ' (' . $new_term->get_error_message() . ')', 'warning');
                    continue;
                }

                $term_ids[] = absint($new_term['term_id']);
                $this->log('创建新术语: ' . $term_name . ' (ID: ' . $new_term['term_id'] . ')');
                continue;
            }

            $term_ids[] = absint($term->term_id);
            $this->log('使用现有术语: ' . $term_name . ' (ID: ' . $term->term_id . ')');
        }

        $result = wp_set_post_terms($post_id, array_values(array_unique($term_ids)), $taxonomy);
        if (is_wp_error($result)) {
            $this->log('设置术语失败: ' . $result->get_error_message(), 'warning');
            return;
        }

        $this->log('产品术语已设置，taxonomy: ' . $taxonomy . '，IDs: ' . implode(',', $term_ids));
    }

    /**
     * 解析 FAQ 数据。返回 null 表示缺列跳过。
     */
    private function parse_import_faqs($data, $faq_count)
    {
        $faqs = [];
        $has_columns = false;

        for ($i = 1; $i <= $faq_count; $i++) {
            $question_key = 'FAQ_Q_' . $i;
            $answer_key = 'FAQ_A_' . $i;
            if (!array_key_exists($question_key, $data) && !array_key_exists($answer_key, $data)) {
                continue;
            }

            $has_columns = true;
            if (!empty($data[$question_key]) || !empty($data[$answer_key])) {
                $faqs[] = [
                    'name' => sanitize_text_field($data[$question_key] ?? ''),
                    'value' => sanitize_textarea_field($data[$answer_key] ?? ''),
                ];
            }
        }

        if ($has_columns) {
            return $faqs;
        }

        if (!array_key_exists('FAQs', $data)) {
            return null;
        }

        return $this->decode_import_collection($data['FAQs']);
    }

    /**
     * 解析属性数据。返回 null 表示缺列跳过。
     */
    private function parse_import_attributes($data, $attribute_count)
    {
        $attributes = [];
        $has_columns = false;

        for ($i = 1; $i <= $attribute_count; $i++) {
            $name_key = 'Attribute_Name_' . $i;
            $value_key = 'Attribute_Value_' . $i;
            if (!array_key_exists($name_key, $data) && !array_key_exists($value_key, $data)) {
                continue;
            }

            $has_columns = true;
            if (!empty($data[$name_key]) || !empty($data[$value_key])) {
                $attributes[] = [
                    'name' => sanitize_text_field($data[$name_key] ?? ''),
                    'value' => sanitize_text_field($data[$value_key] ?? ''),
                ];
            }
        }

        if ($has_columns) {
            return $attributes;
        }

        if (!array_key_exists('Attributes', $data)) {
            return null;
        }

        return $this->decode_import_collection($data['Attributes']);
    }

    /**
     * 解析 base64 JSON 集合字段。
     */
    private function decode_import_collection($value)
    {
        $value = trim((string) $value);
        if ('' === $value) {
            return [];
        }

        $decoded = base64_decode($value, true);
        $parsed = $decoded ? json_decode($decoded, true) : null;

        return is_array($parsed) ? $parsed : [];
    }

    /**
     * 更新集合型元数据。null 表示缺列跳过，空数组表示清空。
     */
    private function maybe_update_import_collection_meta($post_id, $meta_key, $items, $label)
    {
        if (null === $items) {
            return;
        }

        if (empty($items)) {
            delete_post_meta($post_id, $meta_key);
            $this->log($label . '已清空');
            return;
        }

        update_post_meta($post_id, $meta_key, $items);
        $this->log($label . '已保存，共 ' . count($items) . ' 条');
    }

    /**
     * 查找当前导入任务已经创建过的产品。
     */
    private function find_imported_post_by_row_key($row_key)
    {
        if (!$row_key) {
            return 0;
        }

        $posts = get_posts([
            'post_type' => 'product',
            'post_status' => 'any',
            'numberposts' => 1,
            'fields' => 'ids',
            'meta_key' => '_jc_import_row_key',
            'meta_value' => $row_key,
            'suppress_filters' => true,
        ]);

        return !empty($posts) ? absint($posts[0]) : 0;
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

        return $this->copy_file_with_retries($source, $destination, '上传文件复制');
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
                $this->log('图片ZIP文件无效或当前环境不支持ZipArchive', 'warning');
                return $images_path;
            }

            $zip = new ZipArchive();
            if ($zip->open($_FILES['images_zip']['tmp_name']) !== true) {
                $this->log('无法打开图片ZIP文件', 'warning');
                return $images_path;
            }

            $extract_dir = trailingslashit($temp_dir) . 'images/';
            wp_mkdir_p($extract_dir);

            if (!$this->zip_has_safe_paths($zip)) {
                $this->log('图片ZIP文件包含不安全路径，已跳过解压', 'warning');
                $zip->close();
                return $images_path;
            }

            if ($zip->extractTo($extract_dir)) {
                $zip->close();
                $images_path = $extract_dir;
                $this->log('图片ZIP文件解压成功到: ' . $images_path);

                if (false === $this->find_images_directory($images_path)) {
                    $this->log('警告：未找到有效的图片目录', 'warning');
                }
            } else {
                $this->log('图片ZIP文件解压失败', 'warning');
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
            $this->log('无法打开CSV文件进行读取', 'error');
            return new WP_Error('cannot_read_file', __('Cannot read uploaded file.', 'jelly-catalog'));
        }

        $this->log('开始读取CSV文件');
        $this->skip_csv_bom($handle);

        $headers = fgetcsv($handle);
        if (empty($headers) || !is_array($headers)) {
            $this->log('CSV表头为空或格式无效', 'error');
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
            if (strpos($header, 'FAQ_Q_') === 0 || strpos($header, 'FAQ_A_') === 0) {
                $faq_num = (int) substr($header, 6);
                if ($faq_num > $faq_count) {
                    $faq_count = $faq_num;
                }
            } elseif (strpos($header, 'Attribute_Name_') === 0 || strpos($header, 'Attribute_Value_') === 0) {
                $attr_num = (int) preg_replace('/^\D+/', '', $header);
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

        $root_path = rtrim(str_replace('\\', '/', $images_path), '/') . '/';

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $filename = $file->getFilename();
            $path = $file->getPathname();
            $normalized_path = str_replace('\\', '/', $path);
            $relative_path = 0 === strpos($normalized_path, $root_path)
                ? substr($normalized_path, strlen($root_path))
                : $filename;
            $lower_filename = strtolower($filename);
            $lower_relative_path = strtolower($relative_path);

            if ($relative_path && !isset($index[$relative_path])) {
                $index[$relative_path] = $path;
            }

            if ($lower_relative_path && !isset($index[$lower_relative_path])) {
                $index[$lower_relative_path] = $path;
            }

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
    private function resolve_import_image_path($images_path, $image_reference, $image_index)
    {
        $image_reference = $this->sanitize_import_image_reference($image_reference);
        if (!$images_path || !is_dir($images_path) || !$image_reference) {
            $this->log('未提供可用的图片目录，跳过图像: ' . $image_reference);
            return false;
        }

        $direct_path = trailingslashit($images_path) . $image_reference;
        if (file_exists($direct_path)) {
            return $direct_path;
        }

        if (isset($image_index[$image_reference]) && file_exists($image_index[$image_reference])) {
            return $image_index[$image_reference];
        }

        $lower_reference = strtolower($image_reference);
        if (isset($image_index[$lower_reference]) && file_exists($image_index[$lower_reference])) {
            return $image_index[$lower_reference];
        }

        $filename = basename($image_reference);
        if (isset($image_index[$filename]) && file_exists($image_index[$filename])) {
            return $image_index[$filename];
        }

        $lower_name = strtolower($filename);
        if (isset($image_index[$lower_name]) && file_exists($image_index[$lower_name])) {
            return $image_index[$lower_name];
        }

        return $this->find_file_in_subdirectories($images_path, $filename);
    }

    /**
     * 规范化 CSV 中的图片引用，允许相对路径，拒绝绝对路径和目录穿越。
     */
    private function sanitize_import_image_reference($image_reference)
    {
        $image_reference = str_replace('\\', '/', trim((string) $image_reference));
        $image_reference = ltrim($image_reference, '/');

        if (
            '' === $image_reference ||
            preg_match('/^[a-zA-Z]:/', $image_reference) ||
            preg_match('#(^|/)\.\.(/|$)#', $image_reference)
        ) {
            return sanitize_file_name(basename($image_reference));
        }

        $parts = array_filter(explode('/', $image_reference), 'strlen');
        $parts = array_map('sanitize_file_name', $parts);

        return implode('/', $parts);
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
        $response = [
            'job_id' => $job['id'],
            'status' => $job['status'],
            'total' => absint($job['total']),
            'processed' => absint($job['processed']),
            'imported' => absint($job['imported']),
            'errors' => absint($job['errors']),
            'message' => $message ?: ($job['message'] ?? ''),
        ];

        if (isset($job['next_delay'])) {
            $response['next_delay'] = absint($job['next_delay']);
        }

        return $response;
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
            'import_batch_failed',
        ];

        return in_array($code, $allowed_codes, true) ? $code : 'unknown';
    }

    /**
     * 判断导入错误是否适合自动重试。
     */
    private function is_import_error_retryable($error)
    {
        if (!$error instanceof WP_Error) {
            return false;
        }

        $data = $error->get_error_data();

        return is_array($data) && !empty($data['retryable']);
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
     * 带重试复制文件。
     */
    private function copy_file_with_retries($source, $destination, $context = '文件复制')
    {
        $max_retries = max(0, absint(apply_filters('jc_import_file_copy_retries', 2)));
        $retry_delay = max(0, absint(apply_filters('jc_import_file_copy_retry_delay', 250)));

        for ($attempt = 1; $attempt <= $max_retries + 1; $attempt++) {
            if (@copy($source, $destination)) {
                return true;
            }

            $this->log($context . '失败，尝试次数: ' . $attempt . '/' . ($max_retries + 1));

            if ($attempt <= $max_retries && $retry_delay > 0) {
                usleep($retry_delay * 1000 * min($attempt, 5));
            }
        }

        $this->log($context . '失败，已重试 ' . $max_retries . ' 次', 'warning');

        return false;
    }

    /**
     * 清理过期的导入临时目录，避免中断任务长期占用上传目录。
     */
    private function cleanup_stale_import_temp_dirs()
    {
        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'] ?? '';
        if (!$base_dir || !is_dir($base_dir)) {
            return;
        }

        $ttl = max(DAY_IN_SECONDS, absint(apply_filters('jc_import_temp_dir_ttl', 2 * DAY_IN_SECONDS)));
        $base_real = realpath($base_dir);
        if (!$base_real) {
            return;
        }

        $dirs = glob(trailingslashit($base_dir) . 'jc_import_*', GLOB_ONLYDIR);
        if (empty($dirs)) {
            return;
        }

        foreach ($dirs as $dir) {
            $dir_real = realpath($dir);
            if (!$dir_real || 0 !== strpos($dir_real, $base_real) || time() - filemtime($dir) < $ttl) {
                continue;
            }

            $this->rrmdir($dir_real);
        }
    }

    /**
     * 将图片导入为附件
     */
    private function import_image_as_attachment($image_path, $post_id, $filename, $source_reference = '')
    {
        $filename = sanitize_file_name(basename($filename));
        $this->log('开始导入图像为附件: ' . $filename);

        if (!$filename || !file_exists($image_path)) {
            $this->log('图像文件不存在: ' . $image_path, 'warning');
            return false;
        }

        $file_hash = @md5_file($image_path);
        if ($file_hash) {
            $existing_attachment_id = $this->find_imported_attachment_by_hash($file_hash);
            if ($existing_attachment_id) {
                $this->log('复用已导入附件，ID: ' . $existing_attachment_id);
                return $existing_attachment_id;
            }
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
            $this->log('图像文件类型无效: ' . $filename, 'warning');
            return false;
        }

        $upload_dir = wp_upload_dir();

        // 创建唯一的文件名
        $unique_filename = wp_unique_filename($upload_dir['path'], $filename);
        $new_filepath = $upload_dir['path'] . '/' . $unique_filename;

        $this->log('目标文件路径: ' . $new_filepath);

        // 复制文件到上传目录
        if ($this->copy_file_with_retries($image_path, $new_filepath, '图像文件复制')) {
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
                $this->log('附件插入失败: ' . $attach_id->get_error_message(), 'warning');
                unlink($new_filepath);
                return false;
            }

            $this->log('附件插入成功，ID: ' . $attach_id);

            if ($file_hash) {
                update_post_meta($attach_id, '_jc_import_file_hash', $file_hash);
            }

            if ($source_reference) {
                update_post_meta($attach_id, '_jc_import_source_reference', $source_reference);
            }

            // 生成并更新附件元数据
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $attach_data = wp_generate_attachment_metadata($attach_id, $new_filepath);
            wp_update_attachment_metadata($attach_id, $attach_data);

            $this->log('附件元数据更新完成');

            return $attach_id;
        } else {
            $this->log('文件复制失败', 'warning');
        }

        return false;
    }

    /**
     * 根据文件 hash 查找已导入附件，减少重复媒体文件。
     */
    private function find_imported_attachment_by_hash($file_hash)
    {
        static $cache = [];

        if (!$file_hash) {
            return 0;
        }

        if (isset($cache[$file_hash])) {
            return $cache[$file_hash];
        }

        $attachments = get_posts([
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'numberposts' => 1,
            'fields' => 'ids',
            'meta_key' => '_jc_import_file_hash',
            'meta_value' => $file_hash,
            'suppress_filters' => true,
        ]);

        if (empty($attachments)) {
            return 0;
        }

        $cache[$file_hash] = absint($attachments[0]);

        return $cache[$file_hash];
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
}
