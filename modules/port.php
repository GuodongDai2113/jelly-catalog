<?php

/**
 * includes\class-jc-port.php
 *
 * @see: https://jellydai.com
 * @author: Jelly Dai <daiguo1003@gmail.com>
 * @created : 2025.12.20 13:48
 */

namespace Jelly_Catalog\Modules;

use Jelly_Catalog\Utils;
use ZipArchive;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // 禁止直接访问

/**
 * 后台产品导入导出功能
 */
class Port {

	use Port_Import;

	/**
	 * 构造函数
	 * 注册后台菜单与请求处理
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_post_jc_export_products', array( $this, 'export_products' ) );
		add_action( 'admin_post_jc_export_product_categories', array( $this, 'export_product_categories' ) );
		add_action( 'admin_post_jc_export_products_json', array( $this, 'export_products_json' ) );
		add_action( 'admin_post_jc_import_products', array( $this, 'import_products' ) );
		add_action( 'admin_post_jc_clear_import_log', array( $this, 'clear_import_log' ) );
		add_action( 'wp_ajax_jc_start_import_products', array( $this, 'ajax_start_import_products' ) );
		add_action( 'wp_ajax_jc_process_import_products', array( $this, 'ajax_process_import_products' ) );
		add_action( 'admin_notices', array( $this, 'import_notices' ) );

		// 设置日志文件路径
		$upload_dir     = wp_upload_dir();
		$this->log_file = $upload_dir['basedir'] . '/jc_import_log.txt';
	}

	/**
	 * 向产品菜单添加"导入/导出"子菜单
	 *
	 * @return void
	 */
	public function add_menu() {
		add_submenu_page(
			'edit.php?post_type=product',
			__( 'Import/Export', 'jelly-catalog' ),
			__( 'Import/Export', 'jelly-catalog' ),
			'manage_options',
			'products-port',
			array( $this, 'render_products_port' )
		);
	}

	/**
	 * 渲染产品导入导出界面
	 *
	 * @return void
	 */
	public function render_products_port() {
		/**
		 * 导出字段定义
		 *
		 * @var array $export_field_definitions
		 */
		$export_field_definitions = $this->get_export_field_definitions();
		$import_log_entries       = $this->get_import_log_entries();
		?>
<div class="wrap jc-port-page">
	<div class="jc-port-shell">

		<div class="jc-port-grid">
			<section class="jc-port-section">
				<div class="jc-port-section__header">
					<h2 class="jc-port-section__title"><?php _e( 'Export Products', 'jelly-catalog' ); ?></h2>
				</div>
				<p class="description">
					<?php _e( 'Export all products to CSV file with images as separate files.', 'jelly-catalog' ); ?></p>
				<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" class="jc-port-form">
					<?php wp_nonce_field( 'jc_export_products', 'jc_export_nonce' ); ?>
					<input type="hidden" name="action" value="jc_export_products">
					<div class="jc-port-fieldset">
						<div class="jc-port-fieldset__title"><?php esc_html_e( 'Export Fields', 'jelly-catalog' ); ?>
						</div>
						<div class="jc-port-field-grid">
							<?php foreach ( $export_field_definitions as $field_key => $field_definition ) : ?>
								<?php if ( ! empty( $field_definition['required'] ) ) : ?>
							<input type="hidden" name="export_fields[]" value="<?php echo esc_attr( $field_key ); ?>">
							<?php endif; ?>
							<label class="jc-port-check">
								<input type="checkbox" name="export_fields[]"
									value="<?php echo esc_attr( $field_key ); ?>"
									<?php checked( ! empty( $field_definition['default'] ) ); ?>
									<?php disabled( ! empty( $field_definition['required'] ) ); ?>>
								<span
									class="jc-port-check__label"><?php echo esc_html( $field_definition['label'] ); ?></span>
								<?php if ( ! empty( $field_definition['required'] ) ) : ?>
								<?php endif; ?>
							</label>
							<?php endforeach; ?>
						</div>
						<p class="description jc-port-help">
							<?php esc_html_e( 'ID is required. Uncheck image fields to export CSV only without packaging images.', 'jelly-catalog' ); ?>
						</p>
					</div>
					<div class="jc-port-actions">
						<?php submit_button( __( 'Export Products', 'jelly-catalog' ), 'primary', 'export', false ); ?>
					</div>
				</form>

				<div class="jc-port-divider"></div>

				<div class="jc-port-subsection">
					<h3 class="jc-port-subsection__title"><?php esc_html_e( 'Category CSV Export', 'jelly-catalog' ); ?>
					</h3>
					<p class="description">
						<?php _e( 'Export product categories as a dedicated CSV file including hierarchy and Rank Math fields.', 'jelly-catalog' ); ?>
					</p>
					<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" class="jc-port-actions">
						<?php wp_nonce_field( 'jc_export_product_categories', 'jc_export_categories_nonce' ); ?>
						<input type="hidden" name="action" value="jc_export_product_categories">
						<?php submit_button( __( 'Export Categories CSV', 'jelly-catalog' ), 'secondary', 'export_categories', false ); ?>
					</form>
				</div>

				<div class="jc-port-divider"></div>

				<div class="jc-port-subsection">
					<h3 class="jc-port-subsection__title"><?php esc_html_e( 'SEO JSON Export', 'jelly-catalog' ); ?></h3>
					<p class="description">
						<?php _e( 'Export product titles and slugs to JSON for SEO link diagnostics.', 'jelly-catalog' ); ?>
					</p>
					<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" class="jc-port-actions">
						<?php wp_nonce_field( 'jc_export_products_json', 'jc_export_json_nonce' ); ?>
						<input type="hidden" name="action" value="jc_export_products_json">
						<?php submit_button( __( 'Export Products JSON', 'jelly-catalog' ), 'secondary', 'export_json', false ); ?>
					</form>
				</div>
			</section>

			<section class="jc-port-section">
				<div class="jc-port-section__header">
					<h2 class="jc-port-section__title"><?php _e( 'Import Products / Categories', 'jelly-catalog' ); ?>
					</h2>
				</div>
				<p class="description">
					<?php _e( 'Import products and product categories from CSV files. Upload a product CSV, a category CSV, and an optional images ZIP file.', 'jelly-catalog' ); ?>
				</p>
				<form id="jc-import-form" method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>"
					enctype="multipart/form-data" class="jc-port-form">
					<?php wp_nonce_field( 'jc_import_products', 'jc_import_nonce' ); ?>
					<input type="hidden" name="action" value="jc_import_products">
					<div class="jc-port-upload-grid">
						<label class="jc-port-upload">
							<span class="jc-port-upload__label"><?php _e( 'CSV File', 'jelly-catalog' ); ?></span>
							<input type="file" name="csv_file" accept=".csv">
						</label>
						<label class="jc-port-upload">
							<span
								class="jc-port-upload__label"><?php _e( 'Category CSV File', 'jelly-catalog' ); ?></span>
							<input type="file" name="category_csv" accept=".csv">
						</label>
						<label class="jc-port-upload">
							<span class="jc-port-upload__label"><?php _e( 'Images ZIP File', 'jelly-catalog' ); ?></span>
							<input type="file" name="images_zip" accept=".zip">
						</label>
					</div>
					<p class="description jc-port-help">
						<?php _e( 'Upload a product CSV, a category CSV, or both. When both are provided, categories are created first. The images ZIP file should contain all images referenced in the product CSV.', 'jelly-catalog' ); ?>
					</p>
					<div class="jc-port-actions">
						<?php submit_button( __( 'Start Import', 'jelly-catalog' ), 'primary', 'import', false ); ?>
					</div>
				</form>
				<div id="jc-import-progress" class="jc-port-progress hidden" aria-live="polite">
					<div class="jc-port-progress__header">
						<strong><?php esc_html_e( 'Import Progress', 'jelly-catalog' ); ?></strong>
						<span class="jc-port-progress__status">
							<span id="jc-import-progress-percent">0%</span>
							<button type="button" id="jc-import-retry" class="jelly-button warning jc-port-progress__retry hidden">
								<?php esc_html_e( 'Retry Import', 'jelly-catalog' ); ?>
							</button>
						</span>
					</div>
					<div id="jc-import-progress-track" class="jc-port-progress__track"></div>
					<div class="jc-port-progress__stats">
						<span><?php esc_html_e( 'Processed', 'jelly-catalog' ); ?>: <strong
								id="jc-import-progress-processed">0</strong>/<strong
								id="jc-import-progress-total">0</strong></span>
						<span><?php esc_html_e( 'Imported', 'jelly-catalog' ); ?>: <strong
								id="jc-import-progress-imported">0</strong></span>
						<span><?php esc_html_e( 'Errors', 'jelly-catalog' ); ?>: <strong
								id="jc-import-progress-errors">0</strong></span>
					</div>
					<p id="jc-import-progress-message" class="jc-port-progress__message description"></p>
				</div>
			</section>
		</div>
		<?php if ( ! empty( $import_log_entries ) ) : ?>
		<section class="jc-port-section jc-port-section--log">
			<div class="jc-port-section__header">
				<h2 class="jc-port-section__title"><?php _e( 'Import Log', 'jelly-catalog' ); ?></h2>
				<a href="<?php echo wp_nonce_url( admin_url( 'admin-post.php?action=jc_clear_import_log' ), 'clear_import_log' ); ?>"
					class="button">
					<?php _e( 'Clear Log', 'jelly-catalog' ); ?>
				</a>
			</div>
			<div class="jc-port-log">
				<?php foreach ( $import_log_entries as $entry ) : ?>
				<div class="jc-port-log__line"><?php echo esc_html( $this->format_import_log_entry( $entry ) ); ?></div>
				<?php endforeach; ?>
			</div>
		</section>
		<?php endif; ?>
	</div>
</div>
		<?php
	}

	/**
	 * 清除导入日志
	 */
	public function clear_import_log() {
		// 检查权限
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'jelly-catalog' ) );
		}

		// 验证 nonce
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'clear_import_log' ) ) {
			wp_die( __( 'Security check failed', 'jelly-catalog' ) );
		}

		// 删除日志文件
		if ( file_exists( $this->log_file ) ) {
			unlink( $this->log_file );
		}

		// 重定向回导入导出页面
		wp_safe_redirect( admin_url( 'edit.php?post_type=product&page=products-port' ) );
		exit;
	}

	/**
	 * 读取导入日志条目。
	 *
	 * 将日志文件解析为时间、级别、消息的结构化数组
	 *
	 * @return array
	 */
	private function get_import_log_entries() {
		if ( ! file_exists( $this->log_file ) ) {
			return array();
		}

		$raw_content = file_get_contents( $this->log_file );
		if ( false === $raw_content || '' === trim( $raw_content ) ) {
			return array();
		}

		$entries = array();
		$lines   = preg_split( "/\r\n|\n|\r/", $raw_content );

		foreach ( $lines as $line ) {
			$line = trim( (string) $line );
			if ( '' === $line ) {
				continue;
			}

			$entry = array(
				'timestamp' => '',
				'level'     => 'notice',
				'message'   => $line,
			);

			if ( preg_match( '/^\[([^\]]+)\]\s+\[([A-Z]+)\]\s*(.*)$/', $line, $matches ) ) {
				$entry['timestamp'] = sanitize_text_field( $matches[1] );
				$entry['level']     = strtolower( sanitize_key( $matches[2] ) );
				$entry['message']   = sanitize_text_field( $matches[3] );
			}

			$entries[] = $entry;
		}

		return array_reverse( $entries );
	}

	/**
	 * 格式化导入日志单条文本。
	 *
	 * @param array $entry 日志条目
	 * @return string
	 */
	private function format_import_log_entry( $entry ) {
		$timestamp = isset( $entry['timestamp'] ) && '' !== (string) $entry['timestamp']
			? '[' . (string) $entry['timestamp'] . '] '
			: '';
		$level     = isset( $entry['level'] ) && '' !== (string) $entry['level']
			? '[' . strtoupper( (string) $entry['level'] ) . '] '
			: '';
		$message   = isset( $entry['message'] ) ? (string) $entry['message'] : '';

		return trim( $timestamp . $level . $message );
	}

	/**
	 * 导出产品
	 */
	public function export_products() {
		// 检查权限
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'jelly-catalog' ) );
		}

		// 验证 nonce
		$nonce = isset( $_POST['jc_export_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['jc_export_nonce'] ) ) : '';
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'jc_export_products' ) ) {
			wp_die( __( 'Security check failed', 'jelly-catalog' ) );
		}

		/**
		 * 用户选中的导出字段
		 *
		 * @var array<int, string> $selected_field_keys
		 */
		$selected_field_keys = $this->get_selected_export_field_keys(
			isset( $_POST['export_fields'] ) ? wp_unslash( $_POST['export_fields'] ) : null
		);

		// 获取所有产品
		$products = $this->get_export_products();

		if ( empty( $products ) ) {
			wp_die( __( 'No products found to export.', 'jelly-catalog' ) );
		}

		/**
		 * 是否导出图片并打包 ZIP
		 *
		 * @var bool $export_images
		 */
		$export_images = $this->should_export_product_images( $selected_field_keys );

		// 创建临时目录用于存放导出文件
		$upload_dir = wp_upload_dir();
		$export_dir = $upload_dir['basedir'] . '/jc_export_' . time();
		wp_mkdir_p( $export_dir );

		// 创建CSV文件
		$csv_file = $export_dir . '/products.csv';
		$fp       = fopen( $csv_file, 'w' );

		// 写入UTF-8 BOM头以支持中文
		fprintf( $fp, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		// 写入表头
		$headers = $this->get_export_csv_headers( $selected_field_keys );
		fputcsv( $fp, $headers );

		// 处理每个产品
		foreach ( $products as $product ) {
			$row = $this->get_export_product_csv_row( $product, $selected_field_keys, $export_dir, $export_images );
			fputcsv( $fp, $row );
		}

		fclose( $fp );

		// 仅在选择了图片字段时打包为 ZIP 文件
		if ( $export_images && class_exists( 'ZipArchive' ) ) {
			$zip_file = $export_dir . '.zip';
			$zip      = new ZipArchive();

			// 如果存在 images 目录，先将其单独压缩为一个 ZIP 文件
			$images_dir = $export_dir . '/images';
			if ( is_dir( $images_dir ) ) {
				$images_zip_path = $export_dir . '/images.zip';
				$images_zip      = new ZipArchive();
				if ( $images_zip->open( $images_zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) === true ) {
					$this->add_files_to_zip( $images_zip, $images_dir, $images_dir );
					$images_zip->close();

					// 压缩完成后删除原始 images 目录，避免在大包中重复文件
					$this->rrmdir( $images_dir );
				}
			}

			if ( $zip->open( $zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE ) === true ) {
				$this->add_files_to_zip( $zip, $export_dir, $export_dir );
				$zip->close();

				// 删除临时目录
				$this->rrmdir( $export_dir );

				// 提供下载
				header( 'Content-Type: application/zip' );
				header( 'Content-Disposition: attachment; filename="products_export_' . date( 'Y-m-d_H-i-s' ) . '.zip"' );
				header( 'Content-Length: ' . filesize( $zip_file ) );
				readfile( $zip_file );
				unlink( $zip_file );
				exit;
			}
		}

		// 如果不能创建ZIP，则只提供CSV下载
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="products_export_' . date( 'Y-m-d_H-i-s' ) . '.csv"' );
		readfile( $csv_file );

		// 清理临时文件
		unlink( $csv_file );
		if ( is_dir( $export_dir ) ) {
			$this->rrmdir( $export_dir );
		}

		exit;
	}

	/**
	 * 导出产品分类 CSV。
	 *
	 * 导出分类标题、slug、父级 slug、描述和 Rank Math 字段
	 *
	 * @return void
	 */
	public function export_product_categories() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'jelly-catalog' ) );
		}

		$nonce = isset( $_POST['jc_export_categories_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['jc_export_categories_nonce'] ) ) : '';
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'jc_export_product_categories' ) ) {
			wp_die( __( 'Security check failed', 'jelly-catalog' ) );
		}

		$categories = $this->get_export_product_categories( false );
		if ( empty( $categories ) ) {
			wp_die( __( 'No product categories found to export.', 'jelly-catalog' ) );
		}

		usort( $categories, array( 'Jelly_Catalog\Utils', 'sort_terms_by_depth' ) );
		$categories = array_reverse( $categories );

		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="product_categories_' . date( 'Y-m-d_H-i-s' ) . '.csv"' );

		$fp = fopen( 'php://output', 'w' );
		fprintf( $fp, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );
		fputcsv( $fp, $this->get_export_product_category_headers() );

		foreach ( $categories as $category ) {
			fputcsv( $fp, $this->get_export_product_category_row( $category ) );
		}

		fclose( $fp );
		exit;
	}

	/**
	 * 导出产品标题和 Slug 的 JSON 文件。
	 *
	 * 用于 SEO 链接诊断场景，仅输出标题与 slug
	 *
	 * @return void
	 */
	public function export_products_json() {
		// 检查权限
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'jelly-catalog' ) );
		}

		// 验证 nonce
		$nonce = isset( $_POST['jc_export_json_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['jc_export_json_nonce'] ) ) : '';
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'jc_export_products_json' ) ) {
			wp_die( __( 'Security check failed', 'jelly-catalog' ) );
		}

		$products   = $this->get_export_products( 'publish' );
		$categories = $this->get_export_product_categories();

		if ( empty( $products ) && empty( $categories ) ) {
			wp_die( __( 'No products found to export.', 'jelly-catalog' ) );
		}

		/**
		 * SEO JSON 导出数据
		 *
		 * @var array<int, array<string, string>> $payload
		 */
		$payload = array();

		foreach ( $products as $product ) {
			$payload[] = array(
				'type'      => 'product',
				'title'     => (string) $product->post_title,
				'slug'      => (string) $product->post_name,
				'view_link' => (string) get_permalink( $product ),
			);
		}

		foreach ( $categories as $category ) {
			$view_link = get_term_link( $category, 'product_cat' );

			$payload[] = array(
				'type'      => 'product_cat',
				'title'     => (string) $category->name,
				'slug'      => (string) $category->slug,
				'view_link' => is_wp_error( $view_link ) ? '' : (string) $view_link,
			);
		}

		$payload = $this->sort_export_products_json_payload( $payload );

		$json = wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
		if ( false === $json ) {
			wp_die( __( 'JSON export failed.', 'jelly-catalog' ) );
		}

		header( 'Content-Type: application/json; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="products_seo_links_' . date( 'Y-m-d_H-i-s' ) . '.json"' );
		echo $json;
		exit;
	}

	/**
	 * 获取导出产品列表。
	 *
	 * 统一 CSV 与 JSON 导出的产品查询逻辑
	 *
	 * @param string|array $post_status 导出的文章状态
	 * @return array
	 */
	private function get_export_products( $post_status = 'any' ) {
		return get_posts(
			array(
				'post_type'   => 'product',
				'post_status' => $post_status,
				'numberposts' => -1,
				'orderby'     => 'ID',
				'order'       => 'ASC',
			)
		);
	}

	/**
	 * 获取 SEO JSON 导出的产品分类列表。
	 *
	 * 仅导出有内容的产品分类，用于补充 SEO 链接诊断数据
	 *
	 * @return array
	 */
	private function get_export_product_categories( $hide_empty = true ) {
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => (bool) $hide_empty,
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		return $terms;
	}

	/**
	 * 获取导出字段定义。
	 *
	 * 用于渲染勾选项，并控制 CSV 导出的字段顺序
	 *
	 * @return array
	 */
	private function get_export_field_definitions() {
		$definitions = array(
			'id'                => array(
				'header'   => 'ID',
				'label'    => __( 'ID', 'jelly-catalog' ),
				'default'  => true,
				'required' => true,
			),
			'title'             => array(
				'header'  => 'Title',
				'label'   => __( 'Title', 'jelly-catalog' ),
				'default' => true,
			),
			'slug'              => array(
				'header'  => 'Slug',
				'label'   => __( 'Slug', 'jelly-catalog' ),
				'default' => true,
			),
			'product_sku'       => array(
				'header'  => 'product_sku',
				'label'   => __( 'SKU', 'jelly-catalog' ),
				'default' => true,
			),
			'status'            => array(
				'header'  => 'Status',
				'label'   => __( 'Status', 'jelly-catalog' ),
				'default' => true,
			),
			'featured_image'    => array(
				'header'  => 'Featured Image',
				'label'   => __( 'Featured Image', 'jelly-catalog' ),
				'default' => true,
				'image'   => true,
			),
			'gallery_images'    => array(
				'header'  => 'Gallery Images',
				'label'   => __( 'Gallery Images', 'jelly-catalog' ),
				'default' => true,
				'image'   => true,
			),
			'short_description' => array(
				'header'  => 'Short Description',
				'label'   => __( 'Short Description', 'jelly-catalog' ),
				'default' => true,
			),
			'description'       => array(
				'header'  => 'Description',
				'label'   => __( 'Description', 'jelly-catalog' ),
				'default' => true,
			),
			'categories'        => array(
				'header'  => 'Categories',
				'label'   => __( 'Categories', 'jelly-catalog' ),
				'default' => true,
			),
			'category_slugs'    => array(
				'header'  => 'Category Slugs',
				'label'   => __( 'Category Slugs', 'jelly-catalog' ),
				'default' => true,
			),
			'tags'              => array(
				'header'  => 'Tags',
				'label'   => __( 'Tags', 'jelly-catalog' ),
				'default' => true,
			),
			'faqs'              => array(
				'header'  => 'FAQs',
				'label'   => __( 'FAQs', 'jelly-catalog' ),
				'default' => true,
			),
			'attributes'        => array(
				'header'  => 'Attributes',
				'label'   => __( 'Attributes', 'jelly-catalog' ),
				'default' => true,
			),
		);

		if ( Utils::is_rank_math_active() ) {
			$definitions['rank_math_focus_keyword'] = array(
				'header'  => 'Focus Keyword',
				'label'   => __( 'Focus Keyword', 'jelly-catalog' ),
				'default' => true,
			);
			$definitions['rank_math_title']         = array(
				'header'  => 'SEO Title',
				'label'   => __( 'SEO Title', 'jelly-catalog' ),
				'default' => true,
			);
			$definitions['rank_math_description']   = array(
				'header'  => 'Meta Description',
				'label'   => __( 'Meta Description', 'jelly-catalog' ),
				'default' => true,
			);
		}

		return $definitions;
	}

	/**
	 * 获取最终选中的导出字段。
	 *
	 * 未传入字段时使用默认勾选项，同时始终保留必选字段
	 *
	 * @param array|null $submitted_fields 提交的字段键名
	 * @return array
	 */
	private function get_selected_export_field_keys( $submitted_fields = null ) {
		$definitions          = $this->get_export_field_definitions();
		$submitted_field_keys = null;

		if ( is_array( $submitted_fields ) ) {
			$submitted_field_keys = array_map( 'sanitize_key', $submitted_fields );
		}

		$selected_field_keys = array();

		foreach ( $definitions as $field_key => $field_definition ) {
			$is_required            = ! empty( $field_definition['required'] );
			$is_selected_by_default = ! empty( $field_definition['default'] );
			$is_selected            = $is_required
				|| ( null === $submitted_field_keys ? $is_selected_by_default : in_array( $field_key, $submitted_field_keys, true ) );

			if ( $is_selected ) {
				$selected_field_keys[] = $field_key;
			}
		}

		return $selected_field_keys;
	}

	/**
	 * 判断当前导出是否需要携带图片文件。
	 *
	 * 只要勾选任一图片字段，就继续走 ZIP 打包流程
	 *
	 * @param array $selected_field_keys 选中的字段键名
	 * @return bool
	 */
	private function should_export_product_images( $selected_field_keys ) {
		$definitions = $this->get_export_field_definitions();

		foreach ( (array) $selected_field_keys as $field_key ) {
			if ( ! empty( $definitions[ $field_key ]['image'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * 对 SEO JSON 导出数据按标题首字母排序。
	 *
	 * 标题相同时再按 slug 排序，确保导出顺序稳定
	 *
	 * @param array $payload SEO 导出数据
	 * @return array
	 */
	private function sort_export_products_json_payload( $payload ) {
		if ( ! is_array( $payload ) || empty( $payload ) ) {
			return array();
		}

		usort(
			$payload,
			function ( $left, $right ) {
				$left_title    = isset( $left['title'] ) ? (string) $left['title'] : '';
				$right_title   = isset( $right['title'] ) ? (string) $right['title'] : '';
				$title_compare = strnatcasecmp( $left_title, $right_title );

				if ( 0 !== $title_compare ) {
					return $title_compare;
				}

				$left_slug  = isset( $left['slug'] ) ? (string) $left['slug'] : '';
				$right_slug = isset( $right['slug'] ) ? (string) $right['slug'] : '';

				return strnatcasecmp( $left_slug, $right_slug );
			}
		);

		return $payload;
	}

	/**
	 * 获取产品导出 CSV 表头。
	 *
	 * 根据当前插件环境动态附加 Rank Math SEO 字段
	 *
	 * @return array
	 */
	private function get_export_csv_headers( $selected_field_keys = null ) {
		$definitions         = $this->get_export_field_definitions();
		$selected_field_keys = is_array( $selected_field_keys )
			? $selected_field_keys
			: $this->get_selected_export_field_keys();
		$headers             = array();

		foreach ( $selected_field_keys as $field_key ) {
			if ( ! isset( $definitions[ $field_key ]['header'] ) ) {
				continue;
			}

			$headers[] = $definitions[ $field_key ]['header'];
		}

		return $headers;
	}

	/**
	 * 获取单个产品的 CSV 导出行。
	 *
	 * @param \WP_Post $product 产品对象
	 * @param array $selected_field_keys 选中的字段键名
	 * @param string $export_dir 导出目录
	 * @param bool $export_images 是否导出图片
	 * @return array
	 */
	private function get_export_product_csv_row( $product, $selected_field_keys, $export_dir, $export_images ) {
		$row = array();

		foreach ( (array) $selected_field_keys as $field_key ) {
			$row[] = $this->get_export_product_field_value( $field_key, $product, $export_dir, $export_images );
		}

		return $row;
	}

	/**
	 * 获取单个导出字段的值。
	 *
	 * @param string $field_key 字段键名
	 * @param \WP_Post $product 产品对象
	 * @param string $export_dir 导出目录
	 * @param bool $export_images 是否导出图片
	 * @return string
	 */
	private function get_export_product_field_value( $field_key, $product, $export_dir, $export_images ) {
		$post_id = absint( $product->ID );

		switch ( $field_key ) {
			case 'id':
				return (string) $post_id;
			case 'title':
				return (string) $product->post_title;
			case 'slug':
				return (string) $product->post_name;
			case 'product_sku':
				return (string) get_post_meta( $post_id, 'product_sku', true );
			case 'status':
				return (string) $product->post_status;
			case 'featured_image':
				return $this->get_export_featured_image_value( $post_id, $export_dir, $export_images );
			case 'gallery_images':
				return $this->get_export_gallery_images_value( $post_id, $export_dir, $export_images );
			case 'short_description':
				return (string) $product->post_excerpt;
			case 'description':
				return (string) $product->post_content;
			case 'categories':
				return $this->get_export_product_category_name_paths( $post_id );
			case 'category_slugs':
				return $this->get_export_product_category_slug_paths( $post_id );
			case 'tags':
				return implode( '|', wp_get_post_terms( $post_id, 'product_tag', array( 'fields' => 'names' ) ) );
			case 'faqs':
				return $this->encode_export_collection( get_post_meta( $post_id, '_product_faqs', true ) );
			case 'attributes':
				return $this->encode_export_collection( get_post_meta( $post_id, '_product_attributes', true ) );
			case 'rank_math_focus_keyword':
				return (string) get_post_meta( $post_id, 'rank_math_focus_keyword', true );
			case 'rank_math_title':
				return (string) get_post_meta( $post_id, 'rank_math_title', true );
			case 'rank_math_description':
				return (string) get_post_meta( $post_id, 'rank_math_description', true );
			default:
				return '';
		}
	}

	/**
	 * 获取特色图导出值。
	 *
	 * @param int $post_id 产品 ID
	 * @param string $export_dir 导出目录
	 * @param bool $export_images 是否导出图片
	 * @return string
	 */
	private function get_export_featured_image_value( $post_id, $export_dir, $export_images ) {
		$featured_image_id = get_post_thumbnail_id( $post_id );
		if ( ! $featured_image_id ) {
			return '';
		}

		$featured_image_data = wp_get_attachment_metadata( $featured_image_id );
		if ( ! $featured_image_data || ! isset( $featured_image_data['file'] ) ) {
			return '';
		}

		$featured_image = (string) $featured_image_data['file'];

		if ( $export_images ) {
			$image_path = get_attached_file( $featured_image_id );
			if ( $image_path && file_exists( $image_path ) ) {
				$this->copy_export_image( $image_path, $featured_image, $export_dir );
			}
		}

		return $featured_image;
	}

	/**
	 * 获取画廊图导出值。
	 *
	 * @param int $post_id 产品 ID
	 * @param string $export_dir 导出目录
	 * @param bool $export_images 是否导出图片
	 * @return string
	 */
	private function get_export_gallery_images_value( $post_id, $export_dir, $export_images ) {
		$gallery_images = array();
		$gallery_ids    = get_post_meta( $post_id, '_product_image_gallery', true );
		if ( ! $gallery_ids ) {
			return '';
		}

		foreach ( explode( ',', (string) $gallery_ids ) as $gallery_id ) {
			$gallery_image_data = wp_get_attachment_metadata( $gallery_id );
			if ( ! $gallery_image_data || ! isset( $gallery_image_data['file'] ) ) {
				continue;
			}

			$gallery_images[] = $gallery_image_data['file'];

			if ( $export_images ) {
				$image_path = get_attached_file( $gallery_id );
				if ( $image_path && file_exists( $image_path ) ) {
					$this->copy_export_image( $image_path, $gallery_image_data['file'], $export_dir );
				}
			}
		}

		return implode( ',', $gallery_images );
	}

	/**
	 * 编码集合字段导出值。
	 *
	 * @param mixed $items 集合数据
	 * @return string
	 */
	private function encode_export_collection( $items ) {
		return $items ? base64_encode( json_encode( $items ) ) : '';
	}

	/**
	 * 获取产品分类 CSV 表头。
	 *
	 * @return array
	 */
	private function get_export_product_category_headers() {
		$headers = array(
			'Title',
			'Slug',
			'Parent Slug',
			'Description',
		);

		if ( Utils::is_rank_math_active() ) {
			$headers = array_merge(
				$headers,
				array(
					'Focus Keyword',
					'SEO Title',
					'Meta Description',
				)
			);
		}

		return $headers;
	}

	/**
	 * 获取单个产品分类导出行。
	 *
	 * @param \WP_Term $category 分类对象
	 * @return array
	 */
	private function get_export_product_category_row( $category ) {
		$parent_slug = '';
		if ( ! empty( $category->parent ) ) {
			$parent_term = get_term( (int) $category->parent, 'product_cat' );
			if ( $parent_term && ! is_wp_error( $parent_term ) ) {
				$parent_slug = (string) $parent_term->slug;
			}
		}

		$row = array(
			(string) $category->name,
			(string) $category->slug,
			$parent_slug,
			(string) $category->description,
		);

		if ( Utils::is_rank_math_active() ) {
			$row[] = (string) get_term_meta( $category->term_id, 'rank_math_focus_keyword', true );
			$row[] = (string) get_term_meta( $category->term_id, 'rank_math_title', true );
			$row[] = (string) get_term_meta( $category->term_id, 'rank_math_description', true );
		}

		return $row;
	}

	/**
	 * 获取产品关联分类的名称路径集合。
	 *
	 * @param int $post_id 产品 ID
	 * @return string
	 */
	private function get_export_product_category_name_paths( $post_id ) {
		$terms = wp_get_post_terms( $post_id, 'product_cat' );
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return '';
		}

		usort( $terms, array( 'Jelly_Catalog\Utils', 'sort_terms_by_depth' ) );
		$paths = array();

		foreach ( $terms as $term ) {
			$paths[] = Utils::get_product_category_name_path( $term );
		}

		return implode( '|', array_filter( array_unique( $paths ) ) );
	}

	/**
	 * 获取产品关联分类的 slug 集合。
	 *
	 * 仅导出当前分类自身的 slug，不拼接父级路径
	 *
	 * @param int $post_id 产品 ID
	 * @return string
	 */
	private function get_export_product_category_slug_paths( $post_id ) {
		$terms = wp_get_post_terms( $post_id, 'product_cat' );
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return '';
		}

		usort( $terms, array( 'Jelly_Catalog\Utils', 'sort_terms_by_depth' ) );
		$slugs = array();

		foreach ( $terms as $term ) {
			$slugs[] = sanitize_title( (string) $term->slug );
		}

		return implode( '|', array_filter( array_unique( $slugs ) ) );
	}

	/**
	 * 将导出图片复制到 images 目录，并保留上传目录中的相对路径。
	 */
	private function copy_export_image( $source_path, $relative_path, $export_dir ) {
		$relative_path = $this->normalize_export_image_path( $relative_path );
		if ( ! $source_path || ! $relative_path || ! $export_dir ) {
			return false;
		}

		$target_path = trailingslashit( $export_dir ) . 'images/' . $relative_path;
		wp_mkdir_p( dirname( $target_path ) );

		return @copy( $source_path, $target_path );
	}

	/**
	 * 规范化导出图片相对路径，避免写出 ZIP 目录外。
	 */
	private function normalize_export_image_path( $relative_path ) {
		$relative_path = str_replace( '\\', '/', (string) $relative_path );
		$relative_path = ltrim( $relative_path, '/' );

		if (
			'' === $relative_path ||
			preg_match( '/^[a-zA-Z]:/', $relative_path ) ||
			preg_match( '#(^|/)\.\.(/|$)#', $relative_path )
		) {
			return sanitize_file_name( basename( $relative_path ) );
		}

		$parts = array_filter( explode( '/', $relative_path ), 'strlen' );
		$parts = array_map( 'sanitize_file_name', $parts );

		return implode( '/', $parts );
	}

	/**
	 * 添加文件到ZIP归档
	 */
	private function add_files_to_zip( $zip, $path, $base_dir ) {
		$handler = opendir( $path );
		while ( ( $filename = readdir( $handler ) ) !== false ) {
			if ( $filename != '.' && $filename != '..' ) {
				$full_path  = $path . '/' . $filename;
				$local_path = str_replace( $base_dir . '/', '', $full_path );

				if ( is_file( $full_path ) ) {
					$zip->addFile( $full_path, $local_path );
				} elseif ( is_dir( $full_path ) ) {
					// 添加空目录
					$zip->addEmptyDir( $local_path );
					$this->add_files_to_zip( $zip, $full_path, $base_dir );
				}
			}
		}
		closedir( $handler );
	}
}
