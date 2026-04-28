<?php

/**
 * Jelly Catalog AJAX 处理类
 *
 * 处理所有来自前端的 AJAX 请求，包括产品数据的获取、更新以及分类管理等功能
 */

class JC_Ajax_Action
{
    /**
     * 单例实例
     *
     * @var JC_Ajax_Action|null
     */
    private static $instance = null;

    /**
     * 获取单例实例
     *
     * @return JC_Ajax_Action
     */
    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 构造函数
     *
     * 添加 AJAX 钩子
     *
     * @return void
     */
    public function __construct()
    {
        add_action('admin_enqueue_scripts', [$this, 'admin_ajax_scripts']);
        add_action('wp_ajax_update_product_category_image', [$this, 'update_product_category_image']);
        add_action('wp_ajax_update_product_category_description', [$this, 'update_product_category_description']);
        add_action('wp_ajax_get_products_sheet', [$this, 'get_products_sheet']);
        add_action('wp_ajax_save_products_sheet', [$this, 'save_products_sheet']);
        // 添加获取分类法术语的AJAX处理
        add_action('wp_ajax_get_taxonomy_terms', [$this, 'get_taxonomy_terms']);
    }

    public function admin_ajax_scripts()
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $post_type = $screen ? $screen->post_type : '';
        $taxonomy = $screen ? $screen->taxonomy : '';
        $screen_id = $screen ? $screen->id : '';
        if (
            !$screen ||
            (
                'product' !== $post_type &&
                !in_array($taxonomy, ['product_cat', 'product_tag'], true) &&
                false === strpos($screen_id, 'product_page_') &&
                false === strpos($screen_id, 'edit-product_page_')
            )
        ) {
            return;
        }

        wp_localize_script('jquery', 'jc_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('jc_nonce')
        ]);
    }

    /**
     * 更新产品分类描述
     *
     * 该函数用于更新指定产品分类的描述信息，包含安全验证、权限检查和参数验证
     *
     * @return void 返回 JSON 响应
     */
    public function update_product_category_description()
    {
        // 验证 nonce 安全令牌
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!$nonce || !wp_verify_nonce($nonce, 'jc_nonce')) {
            wp_send_json_error(__('Security verification failed', 'jelly-catalog'));
        }

        // 检查当前用户是否具有管理分类的权限
        if (!jc_current_user_can_edit_product_terms('product_cat')) {
            wp_send_json_error(__('Insufficient permissions', 'jelly-catalog'));
        }

        // 获取并处理请求参数
        $term_id = isset($_POST['term_id']) ? absint(wp_unslash($_POST['term_id'])) : 0;
        $description = isset($_POST['description']) ? wp_kses_post(wp_unslash($_POST['description'])) : '';

        // 验证必要参数是否有效
        if (!$term_id) {
            wp_send_json_error(__('Parameter error', 'jelly-catalog'));
        }

        // 执行分类描述更新操作
        $result = wp_update_term($term_id, 'product_cat', [
            'description' => $description
        ]);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(__('Description updated successfully', 'jelly-catalog'));
    }

    /**
     * 更新产品分类图片
     *
     * 该函数用于更新指定产品分类的缩略图图片。
     * 通过AJAX方式接收分类ID和图片ID，验证安全性后更新分类图片。
     *
     * @return void 返回 JSON 响应
     */
    public function update_product_category_image()
    {
        // 验证安全令牌，防止CSRF攻击
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!$nonce || !wp_verify_nonce($nonce, 'jc_nonce')) {
            wp_send_json_error(__('Security verification failed', 'jelly-catalog'));
        }

        // 检查当前用户是否具有管理分类的权限
        if (!jc_current_user_can_edit_product_terms('product_cat')) {
            wp_send_json_error(__('Insufficient permissions', 'jelly-catalog'));
        }

        // 获取并验证分类ID和图片ID参数
        $category_id = isset($_POST['category_id']) ? absint(wp_unslash($_POST['category_id'])) : 0;
        $image_id = isset($_POST['image_id']) ? absint(wp_unslash($_POST['image_id'])) : 0;

        // 检查必要参数是否完整
        if (!$category_id || !$image_id) {
            wp_send_json_error(__('Parameter error', 'jelly-catalog'));
        }

        // 更新分类的缩略图元数据
        update_term_meta($category_id, 'thumbnail_id', $image_id);
        wp_send_json_success(__('Category image updated successfully', 'jelly-catalog'));
    }

    /**
     * 获取产品列表数据（支持分页），并附带指定的SEO元数据。
     *
     * 此函数通过 AJAX 调用，需验证 nonce。它会查询已发布的 WooCommerce 产品，
     * 并为每个产品附加一组自定义字段（如 Rank Math 的关键词、标题和描述）。
     * 返回的数据包括当前页的产品信息、总数及分页相关参数。
     *
     * @return void 返回 JSON 响应
     */
    public function get_products_sheet()
    {
        global $wpdb;
        check_ajax_referer('jc_nonce', 'nonce');

        if (!jc_current_user_can_edit_products()) {
            wp_send_json_error(__('Insufficient permissions', 'jelly-catalog'));
        }

        // 获取分页参数
        $page = isset($_POST['page']) ? absint(wp_unslash($_POST['page'])) : 1;
        $per_page = isset($_POST['per_page']) ? absint(wp_unslash($_POST['per_page'])) : 20;

        $page = max(1, $page);
        $per_page = max(10, min(100, $per_page));

        // 定义需要获取的元数据键
        $meta_keys = [
            'rank_math_focus_keyword',
            'rank_math_title',
            'rank_math_description',
            '_thumbnail_id',
            '_product_image_gallery'
        ];

        // 计算偏移量用于分页查询
        $offset = ($page - 1) * $per_page;
        $where = "post_type='product' AND post_status IN ('publish', 'draft')";
        $where_args = [];

        if (!$this->current_user_can_edit_others_products()) {
            $where .= ' AND post_author = %d';
            $where_args[] = get_current_user_id();
        }

        // 查询产品基础信息：ID、标题、摘要、内容、状态，并按 ID 倒序排列
        $query_args = array_merge($where_args, [$per_page, $offset]);
        $products = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID, post_title, post_excerpt, post_content, post_status FROM {$wpdb->posts} WHERE {$where} ORDER BY ID DESC LIMIT %d OFFSET %d",
                $query_args
            ),
            ARRAY_A
        );

        $product_ids = array_map('absint', wp_list_pluck($products, 'ID'));
        if (!empty($product_ids)) {
            update_meta_cache('post', $product_ids);
            update_object_term_cache($product_ids, 'product');
        }

        $attachment_ids = [];
        foreach ($product_ids as $product_id) {
            $thumbnail_id = absint(get_post_meta($product_id, '_thumbnail_id', true));
            if ($thumbnail_id) {
                $attachment_ids[] = $thumbnail_id;
            }

            $gallery_ids = $this->parse_attachment_ids(get_post_meta($product_id, '_product_image_gallery', true));
            $attachment_ids = array_merge($attachment_ids, $gallery_ids);
        }

        $attachment_ids = array_values(array_unique(array_filter($attachment_ids)));
        if (!empty($attachment_ids)) {
            update_meta_cache('post', $attachment_ids);
        }

        $product_category_terms = $this->get_category_terms_map();
        $response_products = [];

        // 为每个产品加载所需的 SEO 元数据和分类标签信息
        foreach ($products as $product) {
            $product_id = absint($product['ID']);
            if (!jc_current_user_can_edit_product($product_id)) {
                continue;
            }

            $meta_data = [];

            foreach ($meta_keys as $meta_key) {
                $meta_value = get_post_meta($product_id, $meta_key, true);
                // 对于图片相关的元字段，获取附件链接
                if ($meta_key === '_thumbnail_id' || $meta_key === '_product_image_gallery') {
                    if ($meta_key === '_thumbnail_id') {
                        // 单个特色图片
                        if (!empty($meta_value) && is_numeric($meta_value)) {
                            $attachment_url = wp_get_attachment_image_url($meta_value, 'thumbnail');
                            $meta_data[$meta_key] = [
                                [
                                    'id' => (int)$meta_value,
                                    'url' => $attachment_url ? $attachment_url : ''
                                ]
                            ];
                        } else {
                            $meta_data[$meta_key] = [];
                        }
                    } elseif ($meta_key === '_product_image_gallery') {
                        // 画廊图片数组
                        $gallery_images = [];
                        if (!empty($meta_value)) {
                            foreach ($this->parse_attachment_ids($meta_value) as $image_id) {
                                $attachment_url = wp_get_attachment_image_url($image_id, 'thumbnail');
                                $gallery_images[] = [
                                    'id' => (int)$image_id,
                                    'url' => $attachment_url ? $attachment_url : ''
                                ];
                            }
                        }
                        $meta_data[$meta_key] = $gallery_images;
                    }
                } else {
                    $meta_data[$meta_key] = $meta_value;
                }
            }

            // 获取产品分类（获取完整的分类树结构）
            $categories = $this->get_product_category_hierarchy($product_id, $product_category_terms);
            $product['categories'] = $categories;

            // 获取产品标签
            $tag_terms = get_the_terms($product_id, 'product_tag');
            $product['tags'] = (!empty($tag_terms) && !is_wp_error($tag_terms))
                ? array_map('absint', wp_list_pluck($tag_terms, 'term_id'))
                : [];

            $product['meta_data'] = $meta_data;
            $response_products[] = $product;
        }

        // 获取所有符合条件的产品总数，用于计算总页数
        if (!empty($where_args)) {
            $total_products = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->posts} WHERE {$where}",
                    $where_args
                )
            );
        } else {
            $total_products = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->posts} WHERE {$where}"
            );
        }

        // 构造并发送 JSON 响应结果
        wp_send_json([
            'products' => $response_products,
            'total' => intval($total_products),
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total_products / $per_page)
        ]);
    }

    /**
     * 判断当前用户是否可编辑他人产品。
     *
     * @return bool
     */
    private function current_user_can_edit_others_products()
    {
        return current_user_can('edit_others_products') || current_user_can('edit_others_posts');
    }

    /**
     * 将逗号分隔的附件 ID 字符串转换为整数数组。
     *
     * @param mixed $value 附件 ID 字符串。
     * @return array
     */
    private function parse_attachment_ids($value)
    {
        if (empty($value)) {
            return [];
        }

        if (is_array($value)) {
            $ids = $value;
        } else {
            $ids = explode(',', (string) $value);
        }

        return array_values(array_filter(array_map('absint', $ids)));
    }

    /**
     * 获取产品分类术语映射，避免逐项查询父级分类。
     *
     * @return array
     */
    private function get_category_terms_map()
    {
        $terms = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ]);

        if (empty($terms) || is_wp_error($terms)) {
            return [];
        }

        $map = [];
        foreach ($terms as $term) {
            $map[$term->term_id] = $term;
        }

        return $map;
    }

    /**
     * 获取产品分类层级结构
     *
     * @param int $product_id 产品ID
     * @param array $term_map 产品分类映射。
     * @return array 分类层级结构数组
     */
    private function get_product_category_hierarchy($product_id, $term_map = [])
    {
        // 获取产品所有分类
        $terms = get_the_terms($product_id, 'product_cat');

        if (empty($terms) || is_wp_error($terms)) {
            return [];
        }

        $hierarchy = [];

        foreach ($terms as $term) {
            $hierarchy[] = $this->build_category_levels($term, $term_map);
        }

        return $hierarchy;
    }

    /**
     * 构建分类层级数组
     *
     * @param WP_Term $term 分类对象
     * @param array   $term_map 产品分类映射。
     * @return array
     */
    private function build_category_levels($term, $term_map = [])
    {
        $levels = [];

        // 当前分类 ID
        $levels[] = $term->term_id;
        $parent_id = $term->parent;

        // 向上查找父级（最多 3 层）
        while ($parent_id > 0 && count($levels) < 3) {
            $parent = $term_map[$parent_id] ?? get_term($parent_id, 'product_cat');

            if (!$parent || is_wp_error($parent)) {
                break;
            }

            array_unshift($levels, $parent->term_id);
            $parent_id = $parent->parent;
        }

        return [
            'level_1' => $levels[0] ?? null,
            'level_2' => $levels[1] ?? null,
            'level_3' => $levels[2] ?? null,
        ];
    }

    /**
     * 批量保存产品数据的 AJAX 处理函数
     *
     * 该函数用于处理从前端传来的批量产品数据更新请求，包括产品标题、摘要以及指定的元数据字段。
     * 函数会验证用户权限和请求来源，并对每个产品执行安全过滤后更新到数据库。
     *
     * @return void 返回 JSON 响应
     */
    public function save_products_sheet()
    {
        // 安全检查：验证 nonce 和用户权限
        check_ajax_referer('jc_nonce', 'nonce');

        if (!jc_current_user_can_edit_products()) {
            wp_send_json_error(__('Insufficient permissions', 'jelly-catalog'));
        }

        // 获取并检查提交的数据
        $data = isset($_POST['data']) ? wp_unslash($_POST['data']) : [];

        if (empty($data) || !is_array($data)) {
            wp_send_json_error(__('No data needs to be updated', 'jelly-catalog'));
        }

        // 定义需要保存的元数据键名列表
        $meta_keys = [
            'rank_math_focus_keyword',
            'rank_math_title',
            'rank_math_description'
        ];

        $updated_count = 0;

        // 遍历所有产品数据进行更新处理
        foreach ($data as $item) {
            if (!is_array($item)) {
                continue;
            }

            $product_id = isset($item['ID']) ? intval($item['ID']) : 0;

            if (!$product_id) {
                continue;
            }

            $post = get_post($product_id);
            if (!$post || 'product' !== $post->post_type || !jc_current_user_can_edit_product($product_id)) {
                continue;
            }

            $product_updated = false;

            // 构建待更新的文章基础数据
            $update_data = ['ID' => $product_id];

            // 只更新有变化的字段，并进行安全过滤
            if (isset($item['post_title'])) {
                $update_data['post_title'] = sanitize_text_field($item['post_title']);
            }

            if (isset($item['post_excerpt'])) {
                $update_data['post_excerpt'] = wp_kses_post($item['post_excerpt']);
            }

            if (isset($item['post_content'])) {
                $update_data['post_content'] = wp_kses_post($item['post_content']);
            }

            if (isset($item['post_status'])) {
                $post_status = sanitize_key($item['post_status']);
                if (in_array($post_status, ['publish', 'draft'], true)) {
                    $post_type_object = get_post_type_object('product');
                    $publish_cap = ($post_type_object && !empty($post_type_object->cap->publish_posts)) ? $post_type_object->cap->publish_posts : 'publish_posts';
                    if ('publish' !== $post_status || current_user_can($publish_cap)) {
                        $update_data['post_status'] = $post_status;
                    }
                }
            }

            // 执行文章更新操作
            if (count($update_data) > 1) {
                $result = wp_update_post($update_data, true);
                $product_updated = !is_wp_error($result) && $result > 0;
            }

            // 更新相关的元数据字段
            if (isset($item['meta_data']) && is_array($item['meta_data'])) {
                foreach ($meta_keys as $meta_key) {
                    if (isset($item['meta_data'][$meta_key])) {
                        $meta_value = $item['meta_data'][$meta_key];
                        update_post_meta($product_id, $meta_key, sanitize_text_field($meta_value));
                        $product_updated = true;
                    }
                }
            }

            // 更新产品分类（处理分级分类）
            if (isset($item['categories'])) {
                $product_updated = $this->update_product_terms($product_id, $item['categories'], 'product_cat') || $product_updated;
            }

            // 更新产品标签
            if (isset($item['tags'])) {
                $product_updated = $this->update_product_tags($product_id, $item['tags']) || $product_updated;
            }

            // 统计成功更新的产品数量
            if ($product_updated) {
                $updated_count++;
            }
        }
        /* translators: %d: Number of products updated */
        wp_send_json_success(sprintf(__('Successfully updated %d products', 'jelly-catalog'), $updated_count));
    }

    /**
     * 更新产品的分类（基于层级 ID 结构）
     *
     * @param int   $product_id 产品ID
     * @param array $levels     分类层级数组，如：
     *                          [
     *                              'level_1' => 203,
     *                              'level_2' => '',
     *                              'level_3' => ''
     *                          ]
     * @param string $taxonomy  分类法名称
     * @return bool
     */
    private function update_product_terms($product_id, $levels, $taxonomy)
    {
        if (empty($levels) || !is_array($levels)) {
            return false;
        }

        // 按优先级选取最深层级
        $term_id = 0;

        if (!empty($levels['level_3'])) {
            $term_id = (int) $levels['level_3'];
        } elseif (!empty($levels['level_2'])) {
            $term_id = (int) $levels['level_2'];
        } elseif (!empty($levels['level_1'])) {
            $term_id = (int) $levels['level_1'];
        }

        if ($term_id <= 0) {
            return false;
        }

        // 校验 term 是否存在（防御式）
        $term = get_term($term_id, $taxonomy);
        if (!$term || is_wp_error($term)) {
            return false;
        }

        // 只设置最深层级
        $result = wp_set_object_terms($product_id, [$term_id], $taxonomy);

        return !is_wp_error($result);
    }

    /**
     * 更新产品的标签
     *
     * @param int    $product_id 产品ID
     * @param mixed  $tags_value 逗号分隔的标签字符串或标签数组。
     * @return bool
     */
    private function update_product_tags($product_id, $tags_value)
    {
        if (is_array($tags_value)) {
            $tags = $tags_value;
        } else {
            $tags = explode(',', (string) $tags_value);
        }

        $tags = array_filter(array_map('trim', $tags));

        if (empty($tags)) {
            // 如果没有标签，清空现有的
            $result = wp_set_object_terms($product_id, [], 'product_tag');
            return !is_wp_error($result);
        }

        // 为每个标签检查或创建
        $tag_ids = [];
        foreach ($tags as $tag_value) {
            $tag_name = sanitize_text_field($tag_value);
            if ('' === $tag_name) {
                continue;
            }

            if (is_numeric($tag_name)) {
                $tag = get_term((int) $tag_name, 'product_tag');
                if ($tag && !is_wp_error($tag)) {
                    $tag_ids[] = (int) $tag->term_id;
                    continue;
                }
            }

            $tag = term_exists($tag_name, 'product_tag');

            if (!$tag) {
                // 如果标签不存在，则创建它
                $new_tag = wp_insert_term($tag_name, 'product_tag');
                if (!is_wp_error($new_tag)) {
                    $tag_ids[] = (int) $new_tag['term_id'];
                }
            } else {
                $tag_ids[] = (int) $tag['term_id'];
            }
        }

        // 设置产品的新标签
        $result = wp_set_object_terms($product_id, array_values(array_unique($tag_ids)), 'product_tag');

        return !is_wp_error($result);
    }

    /**
     * 获取分类法术语用于自动完成
     *
     * @return void
     */
    public function get_taxonomy_terms()
    {
        check_ajax_referer('jc_nonce', 'nonce');

        if (!jc_current_user_can_edit_products()) {
            wp_send_json_error(__('Insufficient permissions', 'jelly-catalog'));
        }

        $taxonomy = isset($_POST['taxonomy']) ? sanitize_key(wp_unslash($_POST['taxonomy'])) : '';

        if (empty($taxonomy) || !taxonomy_exists($taxonomy)) {
            wp_send_json_error(__('Invalid taxonomy', 'jelly-catalog'));
        }

        if ($taxonomy === 'product_cat') {
            // 获取产品分类层级结构
            $terms = $this->get_category_hierarchy();
            wp_send_json_success($terms);
        } else {
            // 获取所有术语对象
            $terms = get_terms([
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
            ]);

            if (is_wp_error($terms)) {
                wp_send_json_error($terms->get_error_message());
            }

            // 组装返回格式：id / name / slug
            $result = [];

            if (!empty($terms)) {
                foreach ($terms as $term) {
                    $result[] = [
                        'id' => $term->term_id,
                        'name' => $term->name,
                        'slug' => $term->slug,
                    ];
                }
            }

            wp_send_json_success($result);
        }
    }

    /**
     * 获取分类层级结构（扁平化）
     *
     * @return array 分类结构数组
     */
    private function get_category_hierarchy()
    {
        $terms = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ]);

        if (is_wp_error($terms) || empty($terms)) {
            return [];
        }

        $result = [];

        foreach ($terms as $term) {
            $result[] = [
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'parent_id' => $term->parent ?: 0,
            ];
        }

        return $result;
    }
}

JC_Ajax_Action::instance();
