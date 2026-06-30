<?php

/**
 * includes\ajax-action.php
 *
 * @see: https://jellydai.com
 * @author: Jelly Dai <daiguo1003@gmail.com>
 * @created : 2026.05.04 17:51
 */

namespace Jelly_Catalog;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // 禁止直接访问

/**
 * Jelly Catalog AJAX 处理类
 *
 * 处理所有来自前端的 AJAX 请求，包括产品数据的获取、更新以及分类管理等功能
 */

class Ajax_Action {

	/**
	 * 构造函数
	 *
	 * 添加 AJAX 钩子
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp_ajax_update_product_category_image', array( $this, 'update_product_category_image' ) );
		add_action( 'wp_ajax_update_product_category_description', array( $this, 'update_product_category_description' ) );
		add_action( 'wp_ajax_get_products_sheet', array( $this, 'get_products_sheet' ) );
		add_action( 'wp_ajax_save_products_sheet', array( $this, 'save_products_sheet' ) );
		add_action( 'wp_ajax_get_taxonomy_terms', array( $this, 'get_taxonomy_terms' ) );
		add_action( 'wp_ajax_jelly_catalog_update_post_order', array( $this, 'update_post_order' ) );
		add_action( 'wp_ajax_jelly_catalog_update_term_order', array( $this, 'update_term_order' ) );
	}

	/**
	 * 更新产品分类描述
	 *
	 * 该函数用于更新指定产品分类的描述信息，包含安全验证、权限检查和参数验证
	 *
	 * @return void 返回 JSON 响应
	 */
	public function update_product_category_description() {
		// 验证 nonce 安全令牌
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'jc_nonce' ) ) {
			wp_send_json_error( __( 'Security verification failed', 'jelly-catalog' ) );
		}

		// 检查当前用户是否具有管理分类的权限
		if ( ! current_user_can( 'manage_categories' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'jelly-catalog' ) );
		}

		// 获取并处理请求参数
		$term_id     = isset( $_POST['term_id'] ) ? absint( wp_unslash( $_POST['term_id'] ) ) : 0;
		$description = isset( $_POST['description'] ) ? wp_kses_post( wp_unslash( $_POST['description'] ) ) : '';

		// 验证必要参数是否有效
		if ( ! $term_id ) {
			wp_send_json_error( __( 'Parameter error', 'jelly-catalog' ) );
		}

		// 执行分类描述更新操作
		$result = wp_update_term(
			$term_id,
			'product_cat',
			array(
				'description' => $description,
			)
		);

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( __( 'Description updated successfully', 'jelly-catalog' ) );
	}

	/**
	 * 更新产品分类图片
	 *
	 * 该函数用于更新指定产品分类的缩略图图片。
	 * 通过AJAX方式接收分类ID和图片ID，验证安全性后更新分类图片。
	 *
	 * @return void 返回 JSON 响应
	 */
	public function update_product_category_image() {
		// 验证安全令牌，防止CSRF攻击
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'jc_nonce' ) ) {
			wp_send_json_error( __( 'Security verification failed', 'jelly-catalog' ) );
		}

		// 检查当前用户是否具有管理分类的权限
		if ( ! current_user_can( 'manage_categories' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'jelly-catalog' ) );
		}

		// 获取并验证分类ID和图片ID参数
		$category_id = isset( $_POST['category_id'] ) ? absint( wp_unslash( $_POST['category_id'] ) ) : 0;
		$image_id    = isset( $_POST['image_id'] ) ? absint( wp_unslash( $_POST['image_id'] ) ) : 0;

		// 检查必要参数是否完整
		if ( ! $category_id || ! $image_id ) {
			wp_send_json_error( __( 'Parameter error', 'jelly-catalog' ) );
		}

		// 更新分类的缩略图元数据
		update_term_meta( $category_id, 'thumbnail_id', $image_id );
		wp_send_json_success( __( 'Category image updated successfully', 'jelly-catalog' ) );
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
	public function get_products_sheet() {
		global $wpdb;
		check_ajax_referer( 'jc_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'jelly-catalog' ) );
		}

		// 获取分页参数
		$page     = isset( $_POST['page'] ) ? absint( wp_unslash( $_POST['page'] ) ) : 1;
		$per_page = isset( $_POST['per_page'] ) ? absint( wp_unslash( $_POST['per_page'] ) ) : 20;

		$page     = max( 1, $page );
		$per_page = max( 10, min( 100, $per_page ) );

		// 定义需要获取的元数据键
		$meta_keys = array(
			'rank_math_focus_keyword',
			'rank_math_title',
			'rank_math_description',
			'_thumbnail_id',
			'_product_image_gallery',
		);

		// 计算偏移量用于分页查询
		$offset     = ( $page - 1 ) * $per_page;
		$where      = "post_type='product' AND post_status IN ('publish', 'draft')";
		$where_args = array();

		if ( ! current_user_can( 'edit_others_posts' ) ) {
			$where       .= ' AND post_author = %d';
			$where_args[] = get_current_user_id();
		}

		// 查询产品基础信息：ID、标题、摘要、内容、状态，并按 ID 倒序排列
		$query_args = array_merge( $where_args, array( $per_page, $offset ) );
		$products   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_title, post_excerpt, post_content, post_status FROM {$wpdb->posts} WHERE {$where} ORDER BY ID DESC LIMIT %d OFFSET %d",
				$query_args
			),
			ARRAY_A
		);

		$product_ids = array_map( 'absint', wp_list_pluck( $products, 'ID' ) );
		if ( ! empty( $product_ids ) ) {
			update_meta_cache( 'post', $product_ids );
			update_object_term_cache( $product_ids, 'product' );
		}

		$attachment_ids = array();
		foreach ( $product_ids as $product_id ) {
			$thumbnail_id = absint( get_post_meta( $product_id, '_thumbnail_id', true ) );
			if ( $thumbnail_id ) {
				$attachment_ids[] = $thumbnail_id;
			}

			$gallery_ids    = $this->parse_attachment_ids( get_post_meta( $product_id, '_product_image_gallery', true ) );
			$attachment_ids = array_merge( $attachment_ids, $gallery_ids );
		}

		$attachment_ids = array_values( array_unique( array_filter( $attachment_ids ) ) );
		if ( ! empty( $attachment_ids ) ) {
			update_meta_cache( 'post', $attachment_ids );
		}

		$product_category_terms = $this->get_category_terms_map();
		$response_products      = array();

		// 为每个产品加载所需的 SEO 元数据和分类标签信息
		foreach ( $products as $product ) {
			$product_id = absint( $product['ID'] );
			if ( ! current_user_can( 'edit_post', $product_id ) ) {
				continue;
			}

			$meta_data = array();

			foreach ( $meta_keys as $meta_key ) {
				$meta_value = get_post_meta( $product_id, $meta_key, true );
				// 对于图片相关的元字段，获取附件链接
				if ( $meta_key === '_thumbnail_id' || $meta_key === '_product_image_gallery' ) {
					if ( $meta_key === '_thumbnail_id' ) {
						// 单个特色图片
						if ( ! empty( $meta_value ) && is_numeric( $meta_value ) ) {
							$attachment_url         = wp_get_attachment_image_url( $meta_value, 'thumbnail' );
							$meta_data[ $meta_key ] = array(
								array(
									'id'  => (int) $meta_value,
									'url' => $attachment_url ? $attachment_url : '',
								),
							);
						} else {
							$meta_data[ $meta_key ] = array();
						}
					} elseif ( $meta_key === '_product_image_gallery' ) {
						// 画廊图片数组
						$gallery_images = array();
						if ( ! empty( $meta_value ) ) {
							foreach ( $this->parse_attachment_ids( $meta_value ) as $image_id ) {
								$attachment_url   = wp_get_attachment_image_url( $image_id, 'thumbnail' );
								$gallery_images[] = array(
									'id'  => (int) $image_id,
									'url' => $attachment_url ? $attachment_url : '',
								);
							}
						}
						$meta_data[ $meta_key ] = $gallery_images;
					}
				} else {
					$meta_data[ $meta_key ] = $meta_value;
				}
			}

			// 获取产品分类（获取完整的分类树结构）
			$categories            = $this->get_product_category_hierarchy( $product_id, $product_category_terms );
			$product['categories'] = $categories;

			// 获取产品标签
			$tag_terms       = get_the_terms( $product_id, 'product_tag' );
			$product['tags'] = ( ! empty( $tag_terms ) && ! is_wp_error( $tag_terms ) )
				? array_map( 'absint', wp_list_pluck( $tag_terms, 'term_id' ) )
				: array();

			$product['meta_data'] = $meta_data;
			$response_products[]  = $product;
		}

		// 获取所有符合条件的产品总数，用于计算总页数
		if ( ! empty( $where_args ) ) {
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
		wp_send_json(
			array(
				'products'    => $response_products,
				'total'       => intval( $total_products ),
				'page'        => $page,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_products / $per_page ),
			)
		);
	}

	/**
	 * 将逗号分隔的附件 ID 字符串转换为整数数组。
	 *
	 * @param mixed $value 附件 ID 字符串。
	 * @return array
	 */
	private function parse_attachment_ids( $value ) {
		if ( empty( $value ) ) {
			return array();
		}

		if ( is_array( $value ) ) {
			$ids = $value;
		} else {
			$ids = explode( ',', (string) $value );
		}

		return array_values( array_filter( array_map( 'absint', $ids ) ) );
	}

	/**
	 * 获取产品分类术语映射，避免逐项查询父级分类。
	 *
	 * @return array
	 */
	private function get_category_terms_map() {
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			)
		);

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return array();
		}

		$map = array();
		foreach ( $terms as $term ) {
			$map[ $term->term_id ] = $term;
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
	private function get_product_category_hierarchy( $product_id, $term_map = array() ) {
		// 获取产品所有分类
		$terms = get_the_terms( $product_id, 'product_cat' );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return array();
		}

		$hierarchy = array();

		foreach ( $terms as $term ) {
			$hierarchy[] = $this->build_category_levels( $term, $term_map );
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
	private function build_category_levels( $term, $term_map = array() ) {
		$levels = array();

		// 当前分类 ID
		$levels[]  = $term->term_id;
		$parent_id = $term->parent;

		// 向上查找父级（最多 3 层）
		while ( $parent_id > 0 && count( $levels ) < 3 ) {
			$parent = $term_map[ $parent_id ] ?? get_term( $parent_id, 'product_cat' );

			if ( ! $parent || is_wp_error( $parent ) ) {
				break;
			}

			array_unshift( $levels, $parent->term_id );
			$parent_id = $parent->parent;
		}

		return array(
			'level_1' => $levels[0] ?? null,
			'level_2' => $levels[1] ?? null,
			'level_3' => $levels[2] ?? null,
		);
	}

	/**
	 * 批量保存产品数据的 AJAX 处理函数
	 *
	 * 该函数用于处理从前端传来的批量产品数据更新请求，包括产品标题、摘要以及指定的元数据字段。
	 * 函数会验证用户权限和请求来源，并对每个产品执行安全过滤后更新到数据库。
	 *
	 * @return void 返回 JSON 响应
	 */
	public function save_products_sheet() {
		// 安全检查：验证 nonce 和用户权限
		check_ajax_referer( 'jc_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'jelly-catalog' ) );
		}

		// 获取并检查提交的数据
		$data = isset( $_POST['data'] ) ? wp_unslash( $_POST['data'] ) : array();

		if ( empty( $data ) || ! is_array( $data ) ) {
			wp_send_json_error( __( 'No data needs to be updated', 'jelly-catalog' ) );
		}

		// 定义需要保存的元数据键名列表
		$meta_keys = array(
			'rank_math_focus_keyword',
			'rank_math_title',
			'rank_math_description',
		);

		$updated_count = 0;

		// 遍历所有产品数据进行更新处理
		foreach ( $data as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$product_id = isset( $item['ID'] ) ? intval( $item['ID'] ) : 0;

			if ( ! $product_id ) {
				continue;
			}

			$post = get_post( $product_id );
			if ( ! $post || 'product' !== $post->post_type || ! current_user_can( 'edit_post', $product_id ) ) {
				continue;
			}

			$product_updated = false;

			// 构建待更新的文章基础数据
			$update_data = array( 'ID' => $product_id );

			// 只更新有变化的字段，并进行安全过滤
			if ( isset( $item['post_title'] ) ) {
				$update_data['post_title'] = sanitize_text_field( $item['post_title'] );
			}

			if ( isset( $item['post_excerpt'] ) ) {
				$update_data['post_excerpt'] = wp_kses_post( $item['post_excerpt'] );
			}

			if ( isset( $item['post_content'] ) ) {
				$update_data['post_content'] = wp_kses_post( $item['post_content'] );
			}

			if ( isset( $item['post_status'] ) ) {
				$post_status = sanitize_key( $item['post_status'] );
				if ( in_array( $post_status, array( 'publish', 'draft' ), true ) ) {
					if ( 'publish' !== $post_status || current_user_can( 'publish_posts' ) ) {
						$update_data['post_status'] = $post_status;
					}
				}
			}

			// 执行文章更新操作
			if ( count( $update_data ) > 1 ) {
				$result          = wp_update_post( $update_data, true );
				$product_updated = ! is_wp_error( $result ) && $result > 0;
			}

			// 更新相关的元数据字段
			if ( isset( $item['meta_data'] ) && is_array( $item['meta_data'] ) ) {
				foreach ( $meta_keys as $meta_key ) {
					if ( isset( $item['meta_data'][ $meta_key ] ) ) {
						$meta_value = $item['meta_data'][ $meta_key ];
						update_post_meta( $product_id, $meta_key, sanitize_text_field( $meta_value ) );
						$product_updated = true;
					}
				}
			}

			// 更新产品分类（处理分级分类）
			if ( isset( $item['categories'] ) ) {
				$product_updated = $this->update_product_terms( $product_id, $item['categories'], 'product_cat' ) || $product_updated;
			}

			// 更新产品标签
			if ( isset( $item['tags'] ) ) {
				$product_updated = $this->update_product_tags( $product_id, $item['tags'] ) || $product_updated;
			}

			// 统计成功更新的产品数量
			if ( $product_updated ) {
				++$updated_count;
			}
		}
		/* translators: %d: Number of products updated */
		wp_send_json_success( sprintf( __( 'Successfully updated %d products', 'jelly-catalog' ), $updated_count ) );
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
	private function update_product_terms( $product_id, $levels, $taxonomy ) {
		if ( empty( $levels ) || ! is_array( $levels ) ) {
			return false;
		}

		// 按优先级选取最深层级
		$term_id = 0;

		if ( ! empty( $levels['level_3'] ) ) {
			$term_id = (int) $levels['level_3'];
		} elseif ( ! empty( $levels['level_2'] ) ) {
			$term_id = (int) $levels['level_2'];
		} elseif ( ! empty( $levels['level_1'] ) ) {
			$term_id = (int) $levels['level_1'];
		}

		if ( $term_id <= 0 ) {
			return false;
		}

		// 校验 term 是否存在（防御式）
		$term = get_term( $term_id, $taxonomy );
		if ( ! $term || is_wp_error( $term ) ) {
			return false;
		}

		// 只设置最深层级
		$result = wp_set_object_terms( $product_id, array( $term_id ), $taxonomy );

		return ! is_wp_error( $result );
	}

	/**
	 * 更新产品的标签
	 *
	 * @param int    $product_id 产品ID
	 * @param mixed  $tags_value 逗号分隔的标签字符串或标签数组。
	 * @return bool
	 */
	private function update_product_tags( $product_id, $tags_value ) {
		if ( is_array( $tags_value ) ) {
			$tags = $tags_value;
		} else {
			$tags = explode( ',', (string) $tags_value );
		}

		$tags = array_filter( array_map( 'trim', $tags ) );

		if ( empty( $tags ) ) {
			// 如果没有标签，清空现有的
			$result = wp_set_object_terms( $product_id, array(), 'product_tag' );
			return ! is_wp_error( $result );
		}

		// 为每个标签检查或创建
		$tag_ids = array();
		foreach ( $tags as $tag_value ) {
			$tag_name = sanitize_text_field( $tag_value );
			if ( '' === $tag_name ) {
				continue;
			}

			if ( is_numeric( $tag_name ) ) {
				$tag = get_term( (int) $tag_name, 'product_tag' );
				if ( $tag && ! is_wp_error( $tag ) ) {
					$tag_ids[] = (int) $tag->term_id;
					continue;
				}
			}

			$tag = term_exists( $tag_name, 'product_tag' );

			if ( ! $tag ) {
				// 如果标签不存在，则创建它
				$new_tag = wp_insert_term( $tag_name, 'product_tag' );
				if ( ! is_wp_error( $new_tag ) ) {
					$tag_ids[] = (int) $new_tag['term_id'];
				}
			} else {
				$tag_ids[] = (int) $tag['term_id'];
			}
		}

		// 设置产品的新标签
		$result = wp_set_object_terms( $product_id, array_values( array_unique( $tag_ids ) ), 'product_tag' );

		return ! is_wp_error( $result );
	}

	/**
	 * 获取分类法术语用于自动完成
	 *
	 * @return void
	 */
	public function get_taxonomy_terms() {
		check_ajax_referer( 'jc_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'jelly-catalog' ) );
		}

		$taxonomy = isset( $_POST['taxonomy'] ) ? sanitize_key( wp_unslash( $_POST['taxonomy'] ) ) : '';

		if ( empty( $taxonomy ) || ! taxonomy_exists( $taxonomy ) ) {
			wp_send_json_error( __( 'Invalid taxonomy', 'jelly-catalog' ) );
		}

		if ( $taxonomy === 'product_cat' ) {
			// 获取产品分类层级结构
			$terms = $this->get_category_hierarchy();
			wp_send_json_success( $terms );
		} else {
			// 获取所有术语对象
			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
				)
			);

			if ( is_wp_error( $terms ) ) {
				wp_send_json_error( $terms->get_error_message() );
			}

			// 组装返回格式：id / name / slug
			$result = array();

			if ( ! empty( $terms ) ) {
				foreach ( $terms as $term ) {
					$result[] = array(
						'id'   => $term->term_id,
						'name' => $term->name,
						'slug' => $term->slug,
					);
				}
			}

			wp_send_json_success( $result );
		}
	}

	/**
	 * 获取分类层级结构（扁平化）
	 *
	 * @return array 分类结构数组
	 */
	private function get_category_hierarchy() {
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		$result = array();

		foreach ( $terms as $term ) {
			$result[] = array(
				'id'        => $term->term_id,
				'name'      => $term->name,
				'slug'      => $term->slug,
				'parent_id' => $term->parent ?: 0,
			);
		}

		return $result;
	}

	/**
	 * 更新产品排序的 AJAX 处理。
	 *
	 * @return void
	 */
	public function update_post_order() {
		check_ajax_referer( 'jc_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to reorder posts.', 'jelly-catalog' ) ), 403 );
		}

		if ( ! isset( $_POST['order'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing order payload.', 'jelly-catalog' ) ), 400 );
		}

		$order_string = sanitize_text_field( wp_unslash( $_POST['order'] ) );
		parse_str( $order_string, $order_data );

		if ( ! is_array( $order_data ) || empty( $order_data ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid order payload.', 'jelly-catalog' ) ), 400 );
		}

		global $wpdb;

		$post_ids = array();
		foreach ( $order_data as $key => $values ) {
			foreach ( $values as $position => $id ) {
				$post_ids[] = absint( $id );
			}
		}

		if ( empty( $post_ids ) ) {
			wp_send_json_success( array( 'message' => __( 'Nothing to update.', 'jelly-catalog' ) ) );
		}

		$placeholders    = implode( ', ', array_fill( 0, count( $post_ids ), '%d' ) );
		$existing_orders = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT menu_order FROM $wpdb->posts WHERE ID IN ($placeholders) ORDER BY menu_order ASC",
				$post_ids
			)
		);

		sort( $existing_orders );

		foreach ( $order_data as $key => $values ) {
			foreach ( $values as $position => $id ) {
				$new_order = isset( $existing_orders[ $position ] ) ? (int) $existing_orders[ $position ] : 0;
				$wpdb->update(
					$wpdb->posts,
					array( 'menu_order' => $new_order ),
					array( 'ID' => absint( $id ) ),
					array( '%d' ),
					array( '%d' )
				);
			}
		}

		$last_post_id = end( $post_ids );
		$post_type    = $last_post_id ? get_post_type( $last_post_id ) : 'product';

		$conflict_query = $wpdb->prepare(
			"
            SELECT COUNT(menu_order) AS mo_count, menu_order FROM $wpdb->posts
            WHERE post_type = %s AND post_status IN ('publish', 'pending', 'draft', 'private', 'future')
            AND menu_order > 0 GROUP BY menu_order HAVING mo_count > 1
            ",
			$post_type
		);

		$conflicts = $wpdb->get_results( $conflict_query );

		if ( count( $conflicts ) > 0 ) {
			$all_posts_query = $wpdb->prepare(
				"
                SELECT ID, menu_order FROM $wpdb->posts
                WHERE post_type = %s AND post_status IN ('publish', 'pending', 'draft', 'private', 'future')
                ORDER BY menu_order ASC, post_modified DESC
                ",
				$post_type
			);

			$all_posts = $wpdb->get_results( $all_posts_query );

			$current_order = 0;
			$sql           = "UPDATE {$wpdb->posts} SET menu_order = CASE ID ";
			$params        = array();
			foreach ( $all_posts as $post_item ) {
				++$current_order;
				$sql     .= 'WHEN %d THEN %d ';
				$params[] = (int) $post_item->ID;
				$params[] = $current_order;
			}

			$post_ids_list   = wp_list_pluck( $all_posts, 'ID' );
			$ids_placeholder = implode( ', ', array_fill( 0, count( $post_ids_list ), '%d' ) );
			$sql            .= "END WHERE ID IN ({$ids_placeholder})";

			$all_params = array_merge( $params, array_map( 'intval', $post_ids_list ) );

            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- 全部使用 %d 占位符
			$wpdb->query( $wpdb->prepare( $sql, ...$all_params ) );
		}

		$new_orders    = array();
		$final_results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, menu_order FROM $wpdb->posts WHERE ID IN ($placeholders)",
				$post_ids
			)
		);
		foreach ( $final_results as $row ) {
			$new_orders[ $row->ID ] = $row->menu_order;
		}

		wp_send_json_success(
			array(
				'message'    => __( 'Order updated.', 'jelly-catalog' ),
				'new_orders' => $new_orders,
			)
		);
	}

	/**
	 * 更新 term 排序的 AJAX 处理。
	 *
	 * @return void
	 */
	public function update_term_order() {
		check_ajax_referer( 'jc_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_categories' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to reorder terms.', 'jelly-catalog' ) ), 403 );
		}

		if ( ! isset( $_POST['order'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing order payload.', 'jelly-catalog' ) ), 400 );
		}

		$order_string = sanitize_text_field( wp_unslash( $_POST['order'] ) );
		parse_str( $order_string, $order_data );

		if ( ! is_array( $order_data ) || empty( $order_data ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid order payload.', 'jelly-catalog' ) ), 400 );
		}

		global $wpdb;

		$term_ids = array();
		foreach ( $order_data as $key => $values ) {
			foreach ( $values as $position => $id ) {
				$term_ids[] = absint( $id );
			}
		}

		if ( empty( $term_ids ) ) {
			wp_send_json_success( array( 'message' => __( 'Nothing to update.', 'jelly-catalog' ) ) );
		}

		$placeholders    = implode( ', ', array_fill( 0, count( $term_ids ), '%d' ) );
		$term_rows       = $wpdb->get_results(
			$wpdb->prepare(
				"
                SELECT terms.term_id, COALESCE(terms.term_order, 0) AS term_order, term_taxonomy.parent, term_taxonomy.taxonomy
                FROM $wpdb->terms AS terms
                INNER JOIN $wpdb->term_taxonomy AS term_taxonomy ON (terms.term_id = term_taxonomy.term_id)
                WHERE terms.term_id IN ($placeholders)
                ",
				$term_ids
			)
		);
		$term_rows_by_id = array();
		$taxonomy        = '';
		foreach ( $term_rows as $term_row ) {
			if ( '' === $taxonomy ) {
				$taxonomy = $term_row->taxonomy;
			}

			if ( $term_row->taxonomy !== $taxonomy ) {
				continue;
			}

			$term_rows_by_id[ (int) $term_row->term_id ] = $term_row;
		}

		if ( empty( $term_rows_by_id ) ) {
			wp_send_json_success( array( 'message' => __( 'Nothing to update.', 'jelly-catalog' ) ) );
		}

		// 按父级分类分组，避免二级分类与顶级分类或其他父级下的分类混用 term_order。
		$order_groups = array();
		foreach ( $term_ids as $term_id ) {
			if ( ! isset( $term_rows_by_id[ $term_id ] ) ) {
				continue;
			}

			$parent                    = (int) $term_rows_by_id[ $term_id ]->parent;
			$order_groups[ $parent ][] = $term_id;
		}

		foreach ( $order_groups as $parent => $group_term_ids ) {
			$existing_orders = array();
			foreach ( $group_term_ids as $term_id ) {
				$existing_orders[] = (int) $term_rows_by_id[ $term_id ]->term_order;
			}

			sort( $existing_orders, SORT_NUMERIC );
			foreach ( $group_term_ids as $position => $term_id ) {
				$new_order = isset( $existing_orders[ $position ] ) ? (int) $existing_orders[ $position ] : $position + 1;
				$wpdb->update( $wpdb->terms, array( 'term_order' => $new_order ), array( 'term_id' => absint( $term_id ) ), array( '%d' ), array( '%d' ) );
			}
		}

		if ( '' === $taxonomy ) {
			wp_send_json_success( array( 'message' => __( 'Order updated.', 'jelly-catalog' ) ) );
		}
		// 按 (taxonomy, parent) 分组检查冲突
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- 使用 prepare
		$conflict_query = $wpdb->prepare(
			"
            SELECT COUNT(term_order) AS to_count, term_taxonomy.parent
            FROM $wpdb->terms AS terms
            INNER JOIN $wpdb->term_taxonomy AS term_taxonomy ON (terms.term_id = term_taxonomy.term_id)
            WHERE term_taxonomy.taxonomy = %s
            GROUP BY term_taxonomy.parent, term_order
            HAVING to_count > 1
            ",
			$taxonomy
		);
		$conflicts      = $wpdb->get_results( $conflict_query );

		if ( count( $conflicts ) > 0 ) {
			// 按 parent 分组取回所有 term
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- 使用 prepare
			$all_terms = $wpdb->get_results(
				$wpdb->prepare(
					"
                    SELECT terms.term_id, term_taxonomy.parent, COALESCE(terms.term_order, 0) as term_order
                    FROM $wpdb->terms AS terms
                    INNER JOIN $wpdb->term_taxonomy AS term_taxonomy ON (terms.term_id = term_taxonomy.term_id)
                    WHERE term_taxonomy.taxonomy = %s
                    ORDER BY term_taxonomy.parent ASC, COALESCE(terms.term_order, 0) ASC, terms.name ASC
                    ",
					$taxonomy
				)
			);

			$groups = array();
			foreach ( $all_terms as $t ) {
				$groups[ $t->parent ][] = $t->term_id;
			}

			$sql    = '';
			$params = array();
			foreach ( $groups as $parent => $g_term_ids ) {
				$order = 0;
				foreach ( $g_term_ids as $tid ) {
					++$order;
					$sql     .= 'WHEN %d THEN %d ';
					$params[] = (int) $tid;
					$params[] = $order;
				}
			}

			if ( '' !== $sql ) {
				$all_term_ids = array();
				foreach ( $groups as $g_term_ids ) {
					$all_term_ids = array_merge( $all_term_ids, $g_term_ids );
				}
				$ids_placeholder = implode( ', ', array_fill( 0, count( $all_term_ids ), '%d' ) );
				$sql             = "UPDATE {$wpdb->terms} SET term_order = CASE term_id " . $sql . "END WHERE term_id IN ({$ids_placeholder})";
				$params          = array_merge( $params, array_map( 'intval', $all_term_ids ) );

                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- 全部使用 %d 占位符
				$wpdb->query( $wpdb->prepare( $sql, ...$params ) );
			}
		}

		$new_orders    = array();
		$final_results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT term_id, COALESCE(term_order, 0) as term_order FROM $wpdb->terms WHERE term_id IN ($placeholders)",
				$term_ids
			)
		);
		foreach ( $final_results as $row ) {
			$new_orders[ $row->term_id ] = (int) $row->term_order;
		}

		wp_send_json_success(
			array(
				'message'    => __( 'Order updated.', 'jelly-catalog' ),
				'new_orders' => $new_orders,
			)
		);
	}
}
