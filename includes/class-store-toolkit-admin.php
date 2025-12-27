<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin UI for Store Toolkit.
 */
class Store_Toolkit_Admin {

	const ADMIN_SLUG = 'store-toolkit';
	const NONCE_ACTION = 'store_toolkit_run_nonce';
	const FORM_ACTION = 'store_toolkit_run_form';
	const OPTION_KEY_COLUMNS = 'store_toolkit_columns';
	const OPTION_KEY_PER_PAGE = 'store_toolkit_per_page';

	private $screen_hook_suffix = null;

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_admin_page' ] );
		add_action( 'admin_post_' . self::FORM_ACTION, [ $this, 'handle_form_submission' ] );
		
		add_action( 'admin_init', [ $this, 'save_custom_screen_options' ] );
	}

	public function add_admin_page() {
		$this->screen_hook_suffix = add_submenu_page(
			'woocommerce',
			__( 'Store Toolkit', 'store-toolkit-woocommerce' ),
			__( 'Store Toolkit', 'store-toolkit-woocommerce' ),
			'manage_woocommerce',
			self::ADMIN_SLUG,
			[ $this, 'render_page' ]
		);

		add_action( 'load-' . $this->screen_hook_suffix, [ $this, 'setup_screen_options' ] );
	}

	public function setup_screen_options() {
		add_screen_option(
			'per_page',
			[
				'label'   => __( 'Number of items per page:', 'store-toolkit-woocommerce' ),
				'default' => 20,
				'option'  => self::OPTION_KEY_PER_PAGE,
			]
		);

		add_filter( 'set-screen-option', [ $this, 'save_screen_options' ], 10, 3 );
		add_filter( 'screen_settings', [ $this, 'render_screen_options_content' ], 10, 2 );
	}

	/**
	 * Saves custom column checkboxes.
	 */
	public function save_custom_screen_options() {
		if ( ! isset( $_POST['screen-options-apply'] ) ) {
			return;
		}

		if ( ! isset( $_POST['wp_screen_options']['option'] ) || self::OPTION_KEY_PER_PAGE !== $_POST['wp_screen_options']['option'] ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		check_admin_referer( 'screen-options-nonce', 'screenoptionnonce' );

		$valid_keys = [ 'image', 'description', 'slug', 'count' ];
		
		$posted_data = isset( $_POST[ self::OPTION_KEY_COLUMNS ] ) ? wp_unslash( $_POST[ self::OPTION_KEY_COLUMNS ] ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		
		$columns_to_save = [];

		foreach ( $valid_keys as $key ) {
			$columns_to_save[ $key ] = isset( $posted_data[ $key ] ) ? 1 : 0;
		}

		update_user_meta( get_current_user_id(), self::OPTION_KEY_COLUMNS, $columns_to_save );
	}

	public function save_screen_options( $status, $option, $value ) {
		if ( self::OPTION_KEY_PER_PAGE === $option ) {
			return $value;
		}
		return $status;
	}

	public function render_screen_options_content( $status, $screen ) {
		if ( $screen->id !== $this->screen_hook_suffix ) {
			return $status;
		}

		$columns = get_user_option( self::OPTION_KEY_COLUMNS );
		$defaults = [ 
			'image'       => 1, 
			'description' => 1, 
			'slug'        => 1, 
			'count'       => 1 
		];
		$columns = wp_parse_args( $columns, $defaults );

		$html = '<fieldset class="metabox-prefs">';
		$html .= '<legend>' . esc_html__( 'Columns', 'store-toolkit-woocommerce' ) . '</legend>';
		$html .= '<div class="metabox-prefs-container">';
		
		$html .= '<label><input type="checkbox" name="' . esc_attr( self::OPTION_KEY_COLUMNS ) . '[image]" value="1" ' . checked( $columns['image'], 1, false ) . ' /> ' . esc_html__( 'Image', 'store-toolkit-woocommerce' ) . '</label>';
		$html .= '<label><input type="checkbox" name="' . esc_attr( self::OPTION_KEY_COLUMNS ) . '[description]" value="1" ' . checked( $columns['description'], 1, false ) . ' /> ' . esc_html__( 'Description', 'store-toolkit-woocommerce' ) . '</label>';
		$html .= '<label><input type="checkbox" name="' . esc_attr( self::OPTION_KEY_COLUMNS ) . '[slug]" value="1" ' . checked( $columns['slug'], 1, false ) . ' /> ' . esc_html__( 'Slug', 'store-toolkit-woocommerce' ) . '</label>';
		$html .= '<label><input type="checkbox" name="' . esc_attr( self::OPTION_KEY_COLUMNS ) . '[count]" value="1" ' . checked( $columns['count'], 1, false ) . ' /> ' . esc_html__( 'Count', 'store-toolkit-woocommerce' ) . '</label>';
		
		$html .= '</div></fieldset><br class="clear">';

		return $status . $html;
	}

	public function handle_form_submission() {
		check_admin_referer( self::NONCE_ACTION );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'store-toolkit-woocommerce' ) );
		}

		$term_ids = isset( $_POST['term_ids'] ) ? array_map( 'intval', $_POST['term_ids'] ) : [];

		if ( empty( $term_ids ) ) {
			wp_safe_redirect( add_query_arg( 'message', 'no_selection', admin_url( 'admin.php?page=' . self::ADMIN_SLUG ) ) );
			exit;
		}

		$is_dry_run = isset( $_POST['dry_run'] ) && '1' === $_POST['dry_run'];
		
		$core     = new Store_Toolkit_Core();
		$log_path = $core->run_cleanup( $term_ids, $is_dry_run );

		$query_args = [
			'page'    => self::ADMIN_SLUG,
			'message' => 'success',
			'log'     => urlencode( basename( $log_path ) ),
		];

		if ( $is_dry_run ) {
			$query_args['mode'] = 'dry_run';
		}

		wp_safe_redirect( add_query_arg( $query_args, admin_url( 'admin.php' ) ) );
		exit;
	}

	private function print_column_header( $id, $title, $current_orderby, $current_order ) {
		$new_order = 'asc';
		$sorted_class = '';

		if ( $id === $current_orderby ) {
			$new_order = ( 'asc' === $current_order ) ? 'desc' : 'asc';
			$sorted_class = ' sorted ' . $current_order;
		}

		$url = add_query_arg(
			[
				'orderby' => $id,
				'order'   => $new_order,
			]
		);

		echo '<a href="' . esc_url( $url ) . '">';
		echo '<span>' . esc_html( $title ) . '</span>';
		echo '<span class="sorting-indicator"></span>';
		echo '</a>';
	}

	public function render_page() {
		$user_columns = get_user_option( self::OPTION_KEY_COLUMNS );
		$defaults     = [ 'image' => 1, 'description' => 1, 'slug' => 1, 'count' => 1 ];
		$columns      = wp_parse_args( $user_columns, $defaults );

		$per_page = get_user_option( self::OPTION_KEY_PER_PAGE );
		if ( empty( $per_page ) || $per_page < 1 ) {
			$per_page = 20;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$search    = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$orderby   = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'name';
		$order     = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'asc';
		$message   = isset( $_GET['message'] ) ? sanitize_text_field( wp_unslash( $_GET['message'] ) ) : '';
		$mode      = isset( $_GET['mode'] ) ? sanitize_text_field( wp_unslash( $_GET['mode'] ) ) : '';
		$log_file  = isset( $_GET['log'] ) ? basename( sanitize_text_field( wp_unslash( $_GET['log'] ) ) ) : '';
		$paged_val = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( 'desc' !== strtolower( $order ) ) {
			$order = 'asc';
		}

		$allowed_orderby = [ 'name', 'slug', 'count', 'description', 'term_id' ];
		if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
			$orderby = 'name';
		}

		$current_page = $paged_val;
		$offset       = ( $current_page - 1 ) * $per_page;

		$args = [
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'search'     => $search,
			'orderby'    => $orderby,
			'order'      => $order,
		];

		$count_args = $args;
		$count_args['fields'] = 'count';
		$total_terms = get_terms( $count_args ); 
		
		if ( is_wp_error( $total_terms ) ) {
			$total_terms = 0;
		}
		if ( is_array( $total_terms ) ) {
			$total_terms = count( $total_terms );
		}

		$args['number'] = $per_page;
		$args['offset'] = $offset;
		$categories = get_terms( $args );

		$total_pages = ceil( $total_terms / $per_page );
		
		$pagination_args = [
			'base'    => add_query_arg( 'paged', '%#%' ),
			'format'  => '',
			'current' => $current_page,
			'total'   => $total_pages,
		];

		?>
		<div class="wrap woocommerce">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Store Toolkit for WooCommerce', 'store-toolkit-woocommerce' ); ?></h1>
			<p><?php esc_html_e( 'Tools to manage, clean, and optimize your store.', 'store-toolkit-woocommerce' ); ?></p>
			<hr class="wp-header-end">
			
			<h2 class="nav-tab-wrapper">
				<a href="#" class="nav-tab nav-tab-active"><?php esc_html_e( 'Cleanup Tool', 'store-toolkit-woocommerce' ); ?></a>
			</h2>
			<br>

			<?php if ( 'success' === $message ) : ?>
				<div class="notice notice-success is-dismissible">
					<p>
						<?php 
						if ( 'dry_run' === $mode ) {
							esc_html_e( 'Dry Run Complete. No data was deleted.', 'store-toolkit-woocommerce' );
						} else {
							esc_html_e( 'Cleanup Complete.', 'store-toolkit-woocommerce' );
						}
						?>
						<?php if ( ! empty( $log_file ) ) : ?>
							<a href="<?php echo esc_url( content_url( 'uploads/store-toolkit-logs/' . $log_file ) ); ?>" target="_blank" class="button button-small"><?php esc_html_e( 'View Log', 'store-toolkit-woocommerce' ); ?></a>
						<?php endif; ?>
					</p>
				</div>
			<?php elseif ( 'no_selection' === $message ) : ?>
				<div class="notice notice-warning is-dismissible">
					<p><?php esc_html_e( 'Please select at least one category.', 'store-toolkit-woocommerce' ); ?></p>
				</div>
			<?php endif; ?>

			<form method="get">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::ADMIN_SLUG ); ?>" />
				<?php if ( $paged_val > 1 ) : ?>
					<input type="hidden" name="paged" value="<?php echo esc_attr( $paged_val ); ?>" />
				<?php endif; ?>
				<p class="search-box">
					<label class="screen-reader-text" for="tag-search-input"><?php esc_html_e( 'Search Categories:', 'store-toolkit-woocommerce' ); ?></label>
					<input type="search" id="tag-search-input" name="s" value="<?php echo esc_attr( $search ); ?>">
					<input type="submit" id="search-submit" class="button" value="<?php esc_attr_e( 'Search Categories', 'store-toolkit-woocommerce' ); ?>">
				</p>
			</form>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::FORM_ACTION ); ?>">
				<?php wp_nonce_field( self::NONCE_ACTION ); ?>

				<div class="tablenav top">
					<div class="alignleft actions bulkactions">
						<select name="dry_run">
							<option value="1"><?php esc_html_e( 'Dry Run (Simulation)', 'store-toolkit-woocommerce' ); ?></option>
							<option value="0"><?php esc_html_e( 'Live Cleanup (Delete Data)', 'store-toolkit-woocommerce' ); ?></option>
						</select>
						<input type="submit" class="button action" value="<?php esc_attr_e( 'Run Cleanup', 'store-toolkit-woocommerce' ); ?>">
					</div>
					<div class="tablenav-pages">
						<span class="displaying-num">
							<?php
							printf(
								/* translators: %s: Number of items */
								esc_html( _n( '%s item', '%s items', $total_terms, 'store-toolkit-woocommerce' ) ),
								esc_html( number_format_i18n( $total_terms ) )
							);
							?>
						</span>
						<?php 
						// paginate_links outputs safe HTML
						echo wp_kses_post( paginate_links( $pagination_args ) ); 
						?>
					</div>
				</div>

				<table class="wp-list-table widefat striped fixed tags">
					<thead>
						<tr>
							<td id="cb" class="manage-column column-cb check-column"><input type="checkbox" /></td>
							
							<?php if ( ! empty( $columns['image'] ) ) : ?>
								<th scope="col" class="manage-column column-thumb"><span class="wc-image tips"><?php esc_html_e( 'Image', 'store-toolkit-woocommerce' ); ?></span></th>
							<?php endif; ?>

							<th scope="col" class="manage-column column-name column-primary sortable <?php echo ( 'name' === $orderby ) ? esc_attr( $order ) : 'desc'; ?>">
								<?php $this->print_column_header( 'name', __( 'Name', 'store-toolkit-woocommerce' ), $orderby, $order ); ?>
							</th>

							<?php if ( ! empty( $columns['description'] ) ) : ?>
								<th scope="col" class="manage-column column-description sortable <?php echo ( 'description' === $orderby ) ? esc_attr( $order ) : 'desc'; ?>">
									<?php $this->print_column_header( 'description', __( 'Description', 'store-toolkit-woocommerce' ), $orderby, $order ); ?>
								</th>
							<?php endif; ?>

							<?php if ( ! empty( $columns['slug'] ) ) : ?>
								<th scope="col" class="manage-column column-slug sortable <?php echo ( 'slug' === $orderby ) ? esc_attr( $order ) : 'desc'; ?>">
									<?php $this->print_column_header( 'slug', __( 'Slug', 'store-toolkit-woocommerce' ), $orderby, $order ); ?>
								</th>
							<?php endif; ?>

							<?php if ( ! empty( $columns['count'] ) ) : ?>
								<th scope="col" class="manage-column column-posts num sortable <?php echo ( 'count' === $orderby ) ? esc_attr( $order ) : 'desc'; ?>">
									<?php $this->print_column_header( 'count', __( 'Count', 'store-toolkit-woocommerce' ), $orderby, $order ); ?>
								</th>
							<?php endif; ?>
						</tr>
					</thead>
					<tbody id="the-list">
						<?php
						if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
							foreach ( $categories as $category ) {
								$thumbnail_id = get_term_meta( $category->term_id, 'thumbnail_id', true );
								if ( $thumbnail_id ) {
									$image = wp_get_attachment_image( $thumbnail_id, 'thumbnail' );
								} else {
									$image = wc_placeholder_img( 'thumbnail' );
								}
								?>
								<tr>
									<th scope="row" class="check-column"><input type="checkbox" name="term_ids[]" value="<?php echo esc_attr( $category->term_id ); ?>"></th>
									
									<?php if ( ! empty( $columns['image'] ) ) : ?>
										<td class="thumb column-thumb"><?php echo wp_kses_post( $image ); ?></td>
									<?php endif; ?>

									<td class="name column-name" data-colname="<?php esc_attr_e( 'Name', 'store-toolkit-woocommerce' ); ?>">
										<strong><a href="<?php echo esc_url( get_term_link( $category ) ); ?>" target="_blank" class="row-title"><?php echo esc_html( $category->name ); ?></a></strong>
										<div class="row-actions">
											<span class="view"><a href="<?php echo esc_url( get_term_link( $category ) ); ?>" target="_blank"><?php esc_html_e( 'View', 'store-toolkit-woocommerce' ); ?></a></span>
											<span class="id">ID: <?php echo esc_html( $category->term_id ); ?></span>
										</div>
									</td>

									<?php if ( ! empty( $columns['description'] ) ) : ?>
										<td class="description column-description"><?php echo esc_html( wp_trim_words( $category->description, 15 ) ); ?></td>
									<?php endif; ?>

									<?php if ( ! empty( $columns['slug'] ) ) : ?>
										<td class="slug column-slug"><?php echo esc_html( $category->slug ); ?></td>
									<?php endif; ?>

									<?php if ( ! empty( $columns['count'] ) ) : ?>
										<td class="posts column-posts num"><?php echo esc_html( $category->count ); ?></td>
									<?php endif; ?>
								</tr>
								<?php
							}
						} else {
							?>
							<tr>
								<td colspan="6"><?php esc_html_e( 'No categories found.', 'store-toolkit-woocommerce' ); ?></td>
							</tr>
							<?php
						}
						?>
					</tbody>
					<tfoot>
						<tr>
							<td class="manage-column column-cb check-column"><input type="checkbox" /></td>
							
							<?php if ( ! empty( $columns['image'] ) ) : ?>
								<th scope="col" class="manage-column column-thumb"><?php esc_html_e( 'Image', 'store-toolkit-woocommerce' ); ?></th>
							<?php endif; ?>

							<th scope="col" class="manage-column column-name column-primary sortable <?php echo ( 'name' === $orderby ) ? esc_attr( $order ) : 'desc'; ?>">
								<?php $this->print_column_header( 'name', __( 'Name', 'store-toolkit-woocommerce' ), $orderby, $order ); ?>
							</th>

							<?php if ( ! empty( $columns['description'] ) ) : ?>
								<th scope="col" class="manage-column column-description sortable <?php echo ( 'description' === $orderby ) ? esc_attr( $order ) : 'desc'; ?>">
									<?php $this->print_column_header( 'description', __( 'Description', 'store-toolkit-woocommerce' ), $orderby, $order ); ?>
								</th>
							<?php endif; ?>

							<?php if ( ! empty( $columns['slug'] ) ) : ?>
								<th scope="col" class="manage-column column-slug sortable <?php echo ( 'slug' === $orderby ) ? esc_attr( $order ) : 'desc'; ?>">
									<?php $this->print_column_header( 'slug', __( 'Slug', 'store-toolkit-woocommerce' ), $orderby, $order ); ?>
								</th>
							<?php endif; ?>

							<?php if ( ! empty( $columns['count'] ) ) : ?>
								<th scope="col" class="manage-column column-posts num sortable <?php echo ( 'count' === $orderby ) ? esc_attr( $order ) : 'desc'; ?>">
									<?php $this->print_column_header( 'count', __( 'Count', 'store-toolkit-woocommerce' ), $orderby, $order ); ?>
								</th>
							<?php endif; ?>
						</tr>
					</tfoot>
				</table>

				<div class="tablenav bottom">
					<div class="tablenav-pages">
						<span class="displaying-num">
							<?php
							printf(
								/* translators: %s: Number of items */
								esc_html( _n( '%s item', '%s items', $total_terms, 'store-toolkit-woocommerce' ) ),
								esc_html( number_format_i18n( $total_terms ) )
							);
							?>
						</span>
						<?php echo wp_kses_post( paginate_links( $pagination_args ) ); ?>
					</div>
				</div>

			</form>
		</div>
		<?php
	}
}
