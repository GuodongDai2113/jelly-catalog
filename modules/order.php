<?php

/**
 * modules\order.php
 *
 * @see: https://jellydai.com
 * @author: Jelly Dai <daiguo1003@gmail.com>
 * @created : 2026.06.30 09:45
 */

namespace Jelly_Catalog\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // 禁止直接访问
}

class Order {

	/**
	 * 确保 wp_terms 表中存在 term_order 列。
	 *
	 * @return void
	 */
	public static function maybe_setup_term_order_db() {
		// 索引检查使用独立标记，确保已有用户也能自动创建
		if ( is_admin() && ! get_option( 'jelly_catalog_term_order_idx', false ) ) {
			global $wpdb;
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- 表名是常量
			$index_exists = $wpdb->query( "SHOW INDEX FROM $wpdb->terms WHERE Key_name = 'term_order_idx'" );
			if ( ! $index_exists ) {
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- 同上
				$wpdb->query( "ALTER TABLE $wpdb->terms ADD INDEX term_order_idx (`term_order`)" );
			}
			update_option( 'jelly_catalog_term_order_idx', true );
		}

		if ( get_option( 'jelly_catalog_term_order_db', false ) ) {
			return;
		}
		// 仅后台执行数据库操作
		if ( ! is_admin() ) {
			return;
		}
		global $wpdb;
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- 表名是常量, 列名是固定字符串
		$result = $wpdb->query( "DESCRIBE $wpdb->terms `term_order`" );
		if ( ! $result ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- 同上
			$wpdb->query( "ALTER TABLE $wpdb->terms ADD `term_order` INT(4) NULL DEFAULT '0'" );
		}
		update_option( 'jelly_catalog_term_order_db', true );
	}



	/**
	 * 注册排序相关钩子。
	 *
	 * @return void
	 */
	public function __construct() {
		// 首次运行时自动添加数据库列
		self::maybe_setup_term_order_db();
		if ( ! isset( $_GET['action'] ) ) {
			add_filter( 'manage_product_posts_columns', array( $this, 'add_order_column' ), 99 );
			add_action( 'manage_product_posts_custom_column', array( $this, 'display_order_column' ), 10, 2 );
			add_filter( 'manage_edit-product_cat_columns', array( $this, 'add_term_order_column' ), 99 );
			add_filter( 'manage_product_cat_custom_column', array( $this, 'display_term_order_column' ), 10, 3 );
			add_action( 'load-edit.php', array( $this, 'load_product_order' ) );
			add_action( 'load-edit-tags.php', array( $this, 'load_product_cat_order' ) );
			add_action( 'create_term', array( $this, 'assign_term_order_on_create' ), 10, 3 );
			add_action( 'wp_insert_post', array( $this, 'assign_post_order_on_create' ), 10, 3 );
			add_filter( 'terms_clauses', array( $this, 'terms_clauses_for_order' ), 10, 3 );
		}
	}
	/**
	 * 添加"排序"列
	 *
	 * @param array $columns 现有的列
	 * @return array 修改后的列
	 */
	public function add_order_column( $columns ) {
		$columns['jelly_catalog_order_column'] = __( 'Sort', 'jelly-catalog' );
		return $columns;
	}

	/**
	 * 显示"排序"列的内容
	 *
	 * @param string $column_name 列名
	 * @param int $post_id 文章ID
	 * @return void
	 */
	public function display_order_column( $column_name, $post_id ) {
		if ( $column_name !== 'jelly_catalog_order_column' ) {
			return;
		}
		// 显示拖动排序的图标和当前的排序号
		echo '<span class="jelly-order-handle" title="' . esc_attr__( 'Drag to sort', 'jelly-catalog' ) . '" data-post-id="' . esc_attr( $post_id ) . '"></span>';
		// $menu_order = get_post_field('menu_order', $post_id);
		// echo '<div class="jelly-order-number">' . esc_html($menu_order) . '</div>';
	}

	/**
	 * 添加 term 排序列。
	 *
	 * @param array $columns 现有列
	 * @return array 修改后的列
	 */
	public function add_term_order_column( $columns ) {
		$columns['jelly_catalog_order_column'] = __( 'Sort', 'jelly-catalog' );
		return $columns;
	}

	/**
	 * 显示 term 排序列的内容。
	 *
	 * @param string $content 现有列内容
	 * @param string $column_name 列名
	 * @param int    $term_id term ID
	 * @return string 列内容
	 */
	public function display_term_order_column( $content, $column_name, $term_id ) {
		if ( $column_name !== 'jelly_catalog_order_column' ) {
			return $content;
		}
		// $term       = get_term($term_id);
		// $term_order = ($term && ! is_wp_error($term) && isset($term->term_order)) ? (int) $term->term_order : 0;

		$handle = '<span class="jelly-order-handle" title="' . esc_attr__( 'Drag to sort', 'jelly-catalog' ) . '" data-term-id="' . esc_attr( $term_id ) . '"></span>';
		// $handle .= '<div class="jelly-order-number">' . esc_html($term_order) . '</div>';
		return $handle;
	}

	public function load_product_order() {
		// 如果已有排序参数，则不加载自定义排序功能
		if ( isset( $_GET['orderby'] ) && ! empty( $_GET['orderby'] ) ) {
			return;
		}

		$this->maybe_refresh_product_order();

		$screen = get_current_screen();
		if ( ! empty( $screen->post_type ) && $screen->post_type === 'product' ) {
			add_action( 'pre_get_posts', array( $this, 'pre_get_posts_for_order' ) );
		}
	}

	/**
	 * 在文章查询前修改查询参数，以支持自定义排序
	 *
	 * @param \WP_Query $wp_query
	 * @return false|void
	 */
	public function pre_get_posts_for_order( $wp_query ) {
		if ( isset( $wp_query->query['post_type'] ) && ! $wp_query->get( 'orderby' ) && $wp_query->query['post_type'] === 'product' ) {
			$wp_query->set( 'orderby', 'menu_order' );
			$wp_query->set( 'order', 'ASC' );
		}
	}

	/**
	 * 加载 term 排序功能（仅在 edit-tags.php 页面触发）。
	 *
	 * @return void
	 */
	public function load_product_cat_order() {
		$taxonomy = isset( $_GET['taxonomy'] ) ? sanitize_key( wp_unslash( $_GET['taxonomy'] ) ) : '';
		if ( empty( $taxonomy ) || $taxonomy !== 'product_cat' ) {
			return;
		}

		// 自动归一化 term_order（修复子分类序号异常）
		$this->maybe_refresh_product_cat_order( $taxonomy );
	}

	/**
	 * 修改 term 查询的 ORDER BY 子句，按 term_order 排序。
	 *
	 * @param array $clauses 查询子句
	 * @param array $taxonomies 分类法数组
	 * @param array $args 查询参数
	 * @return array
	 */
	public function terms_clauses_for_order( $clauses, $taxonomies, $args ) {
		if ( ! is_admin() || ! in_array( 'product_cat', (array) $taxonomies, true ) ) {
			return $clauses;
		}
		$clauses['orderby'] = 'ORDER BY tt.parent ASC, t.term_order ASC, t.name ASC';
		$clauses['order']   = '';
		return $clauses;
	}

	private function maybe_refresh_product_order() {
		global $wpdb;

		$post_statuses = "('publish', 'pending', 'draft', 'private', 'future')";

		$stats = $wpdb->get_row(
			$wpdb->prepare(
				"
            SELECT COUNT(*) as post_count, MAX(menu_order) as max_order, COUNT(DISTINCT menu_order) as distinct_orders
            FROM {$wpdb->posts}
            WHERE post_type = %s AND post_status IN {$post_statuses}
            ",
				'product'
			)
		);

		if ( ! $stats || (int) $stats->post_count === 0
			|| ( (int) $stats->post_count === (int) $stats->max_order && (int) $stats->post_count === (int) $stats->distinct_orders )
		) {
			return;
		}

		$post_ids = $wpdb->get_col(
			$wpdb->prepare(
				"
            SELECT ID
            FROM {$wpdb->posts}
            WHERE post_type = %s AND post_status IN {$post_statuses}
            ORDER BY menu_order ASC, post_date DESC
            ",
				'product'
			)
		);

		if ( empty( $post_ids ) ) {
			return;
		}

		$sql    = "UPDATE {$wpdb->posts} SET menu_order = CASE ID ";
		$params = array();
		foreach ( $post_ids as $index => $post_id ) {
			$sql     .= 'WHEN %d THEN %d ';
			$params[] = (int) $post_id;
			$params[] = $index + 1;
		}

		$ids_placeholder = implode( ', ', array_fill( 0, count( $post_ids ), '%d' ) );
		$sql            .= "END WHERE ID IN ({$ids_placeholder})";

		$all_params = array_merge( $params, array_map( 'intval', $post_ids ) );

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql is dynamically built but placeholders are prepared below.
		$wpdb->query( $wpdb->prepare( $sql, ...$all_params ) );
	}

	/**
	 * 检查并归一化当前 taxonomy 的 term_order，确保值从 1 开始连续不重复。
	 *
	 * @param string $taxonomy 分类法
	 * @return void
	 */
	private function maybe_refresh_product_cat_order( $taxonomy ) {
		global $wpdb;
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $query 已使用 $wpdb->prepare()
		$terms = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT terms.term_id, term_taxonomy.parent
				FROM $wpdb->terms AS terms
				INNER JOIN $wpdb->term_taxonomy AS term_taxonomy ON ( terms.term_id = term_taxonomy.term_id )
				WHERE term_taxonomy.taxonomy = %s
				ORDER BY term_taxonomy.parent ASC, term_order ASC, terms.name ASC
				",
				$taxonomy
			)
		);

		if ( empty( $terms ) ) {
			return;
		}

		$groups = array();
		foreach ( $terms as $term ) {
			$groups[ $term->parent ][] = $term->term_id;
		}

		foreach ( $groups as $parent => $term_ids ) {
			foreach ( $term_ids as $index => $term_id ) {
				$wpdb->update(
					$wpdb->terms,
					array( 'term_order' => $index + 1 ),
					array( 'term_id' => absint( $term_id ) ),
					array( '%d' ),
					array( '%d' )
				);
			}
		}
	}

	/**
	 * 新建 term 时自动分配 term_order，避免默认 0 值导致序号异常。
	 *
	 * @param int    $term_id  新创建的 term ID
	 * @param int    $tt_id    term_taxonomy ID
	 * @param string $taxonomy 分类法
	 * @return void
	 */
	public function assign_term_order_on_create( $term_id, $tt_id, $taxonomy ) {
		if ( $taxonomy !== 'product_cat' ) {
			return;
		}
		global $wpdb;
		$parent = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT parent FROM $wpdb->term_taxonomy WHERE term_taxonomy_id = %d",
				$tt_id
			)
		);
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- 表名是常量
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE $wpdb->terms AS terms
				INNER JOIN $wpdb->term_taxonomy AS term_taxonomy ON ( terms.term_id = term_taxonomy.term_id )
				SET terms.term_order = COALESCE(terms.term_order, 0) + 1
				WHERE term_taxonomy.taxonomy = %s AND term_taxonomy.parent = %d",
				$taxonomy,
				$parent
			)
		);
		$wpdb->update(
			$wpdb->terms,
			array( 'term_order' => 1 ),
			array( 'term_id' => absint( $term_id ) ),
			array( '%d' ),
			array( '%d' )
		);
	}

	/**
	 * 新建 post 时自动分配 menu_order。
	 *
	 * @param int     $post_id 文章 ID
	 * @param \WP_Post $post    文章对象
	 * @param bool    $update  是否为更新
	 * @return void
	 */
	public function assign_post_order_on_create( $post_id, $post, $update ) {
		if ( $update || $post->post_type !== 'product' ) {
			return;
		}
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE $wpdb->posts SET menu_order = menu_order + 1 WHERE post_type = %s AND post_status IN ( 'publish', 'pending', 'draft', 'private', 'future' )",
				'product'
			)
		);
		$wpdb->update(
			$wpdb->posts,
			array( 'menu_order' => 1 ),
			array( 'ID' => $post_id ),
			array( '%d' ),
			array( '%d' )
		);
	}
}
