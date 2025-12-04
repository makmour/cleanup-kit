<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin UI for the Woo Cleanup plugin.
 */
class Woo_Cleanup_Admin {

	const ADMIN_SLUG = 'woo-cleanup';
	const NONCE_ACTION = 'woo_cleanup_run_nonce';
	const FORM_ACTION = 'woo_cleanup_run_form';
	const OPTION_KEY_COLUMNS = 'woo_cleanup_columns';
	const OPTION_KEY_PER_PAGE = 'woo_cleanup_per_page';

	private $screen_hook_suffix = null;

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_admin_page' ] );
		add_action( 'admin_post_' . self::FORM_ACTION, [ $this, 'handle_form_submission' ] );
		
		// Hook into admin_init to reliably save custom checkboxes
		add_action( 'admin_init', [ $this, 'save_custom_screen_options' ] );
	}

	public function add_admin_page() {
		$this->screen_hook_suffix = add_submenu_page(
			'woocommerce',
			__( 'Woo Cleanup', 'woo-clean-up' ),
			__( 'Woo Cleanup', 'woo-clean-up' ),
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
				'label'   => __( 'Number of items per page:', 'woo-clean-up' ),
				'default' => 20,
				'option'  => self::OPTION_KEY_PER_PAGE,
			]
		);

		// This filter saves the 'per_page' integer automatically
		add_filter( 'set-screen-option', [ $this, 'save_screen_options' ], 10, 3 );
		// This filter renders the HTML for checkboxes
		add_filter( 'screen_settings', [ $this, 'render_screen_options_content' ], 10, 2 );
	}

	/**
	 * Saves custom column checkboxes.
	 */
	public function save_custom_screen_options() {
		// 1. Check if the user clicked "Apply" in Screen Options
		if ( ! isset( $_POST['screen-options-apply'] ) ) {
			return;
		}

		// 2. Verify we are on the right page
		if ( ! isset( $_POST['wp_screen_options']['option'] ) || self::OPTION_KEY_PER_PAGE !== $_POST['wp_screen_options']['option'] ) {
			return;
		}

		// 3. Check permissions
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		check_admin_referer( 'screen-options-nonce', 'screenoptionnonce' );

		// 4. Manually process the columns array to handle unchecked boxes
		$valid_keys = [ 'image', 'description', 'slug', 'count' ];
		$posted_columns = isset( $_POST[ self::OPTION_KEY_COLUMNS ] ) ? $_POST[ self::OPTION_KEY_COLUMNS ] : [];
		$columns_to_save = [];

		foreach ( $valid_keys as $key ) {
			$columns_to_save[ $key ] = isset( $posted_columns[ $key ] ) ? 1 : 0;
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
		$html .= '<legend>' . __( 'Columns', 'woo-clean-up' ) . '</legend>';
		$html .= '<div class="metabox-prefs-container">';
		
		$html .= '<label><input type="checkbox" name="' . self::OPTION_KEY_COLUMNS . '[image]" value="1" ' . checked( $columns['image'], 1, false ) . ' /> ' . __( 'Image', 'woo-clean-up' ) . '</label>';
		$html .= '<label><input type="checkbox" name="' . self::OPTION_KEY_COLUMNS . '[description]" value="1" ' . checked( $columns['description'], 1, false ) . ' /> ' . __( 'Description', 'woo-clean-up' ) . '</label>';
		$html .= '<label><input type="checkbox" name="' . self::OPTION_KEY_COLUMNS . '[slug]" value="1" ' . checked( $columns['slug'], 1, false ) . ' /> ' . __( 'Slug', 'woo-clean-up' ) . '</label>';
		$html .= '<label><input type="checkbox" name="' . self::OPTION_KEY_COLUMNS . '[count]" value="1" ' . checked( $columns['count'], 1, false ) . ' /> ' . __( 'Count', 'woo-clean-up' ) . '</label>';
		
		$html .= '</div></fieldset><br class="clear">';

		return $status . $html;
	}

	public function handle_form_submission() {
		check_admin_referer( self::NONCE_ACTION );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( __( 'You do not have permission to perform this action.', 'woo-clean-up' ) );
		}

		$term_ids = isset( $_POST['term_ids'] ) ? array_map( 'intval', $_POST['term_ids'] ) : [];

		if ( empty( $term_ids ) ) {
			wp_redirect( add_query_arg( 'message', 'no_selection', admin_url( 'admin.php?page=' . self::ADMIN_SLUG ) ) );
			exit;
		}

		$is_dry_run = isset( $_POST['dry_run'] ) && '1' === $_POST['dry_run'];
		
		$core     = new Woo_Cleanup_Core();
		$log_path = $core->run_cleanup( $term_ids, $is_dry_run );

		$query_args = [
			'page'    => self::ADMIN_SLUG,
			'message' => 'success',
			'log'     => urlencode( basename( $log_path ) ),
		];

		if ( $is_dry_run ) {
			$query_args['mode'] = 'dry_run';
		}

		wp_redirect( add_query_arg( $query_args, admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Helper to generate sortable column headers.
	 */
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
		// 1. Get User Preferences
		$user_columns = get_user_option( self::OPTION_KEY_COLUMNS );
		$defaults     = [ 'image' => 1, 'description' => 1, 'slug' => 1, 'count' => 1 ];
		$columns      = wp_parse_args( $user_columns, $defaults );

		$per_page = get_user_option( self::OPTION_KEY_PER_PAGE );
		if ( empty( $per_page ) || $per_page < 1 ) {
			$per_page = 20;
		}

		// 2. Handle Search & Sort Parameters
		$search  = isset( $_REQUEST['s'] ) ? sanitize_text_field( $_REQUEST['s'] ) : '';
		$orderby = isset( $_REQUEST['orderby'] ) ? sanitize_text_field( $_REQUEST['orderby'] ) : 'name';
		$order   = isset( $_REQUEST['order'] ) && 'desc' === strtolower( $_REQUEST['order'] ) ? 'desc' : 'asc';

		// Validate orderby to prevent errors
		$allowed_orderby = [ 'name', 'slug', 'count', 'description', 'term_id' ];
		if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
			$orderby = 'name';
		}

		$current_page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
		$offset       = ( $current_page - 1 ) * $per_page;

		// 3. Prepare Query Arguments
		$args = [
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'search'     => $search,
			'orderby'    => $orderby,
			'order'      => $order,
		];

		// 4. Fetch Data & Counts
		// First, get the total count for pagination (accounting for search)
		$count_args = $args;
		$count_args['fields'] = 'count';
		$total_terms = get_terms( $count_args ); 
		
		// Fallback for empty/error results
		if ( is_wp_error( $total_terms ) ) {
			$total_terms = 0;
		}
		if ( is_array( $total_terms ) ) {
			$total_terms = count( $total_terms );
		}

		// Now fetch the actual slice of data
		$args['number'] = $per_page;
		$args['offset'] = $offset;
		$categories = get_terms( $args );

		$total_pages = ceil( $total_terms / $per_page );

		?>
		<div class="wrap woocommerce">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'WooCommerce Category Cleanup', 'woo-clean-up' ); ?></h1>
			<p><?php esc_html_e( 'Select categories to permanently delete products and clean up orphaned data.', 'woo-clean-up' ); ?></p>
			<hr class="wp-header-end">

			<?php if ( isset( $_GET['message'] ) && 'success' === $_GET['message'] ) : ?>
				<div class="notice notice-success is-dismissible">
					<p>
						<?php 
						if ( isset( $_GET['mode'] ) && 'dry_run' === $_GET['mode'] ) {
							esc_html_e( 'Dry Run Complete. No data was deleted.', 'woo-clean-up' );
						} else {
							esc_html_e( 'Cleanup Complete.', 'woo-clean-up' );
						}
						?>
						<?php if ( isset( $_GET['log'] ) ) : ?>
							<a href="<?php echo esc_url( content_url( 'uploads/woo-clean-up-logs/' . urldecode( $_GET['log'] ) ) ); ?>" target="_blank" class="button button-small"><?php esc_html_e( 'View Log', 'woo-clean-up' ); ?></a>
						<?php endif; ?>
					</p>
				</div>
			<?php elseif ( isset( $_GET['message'] ) && 'no_selection' === $_GET['message'] ) : ?>
				<div class="notice notice-warning is-dismissible">
					<p><?php esc_html_e( 'Please select at least one category.', 'woo-clean-up' ); ?></p>
				</div>
			<?php endif; ?>

			<form method="get">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::ADMIN_SLUG ); ?>" />
				<?php if ( isset( $_GET['paged'] ) ) : ?>
					<input type="hidden" name="paged" value="<?php echo esc_attr( $_GET['paged'] ); ?>" />
				<?php endif; ?>
				<p class="search-box">
					<label class="screen-reader-text" for="tag-search-input"><?php esc_html_e( 'Search Categories:', 'woo-clean-up' ); ?></label>
					<input type="search" id="tag-search-input" name="s" value="<?php echo esc_attr( $search ); ?>">
					<input type="submit" id="search-submit" class="button" value="<?php esc_attr_e( 'Search Categories', 'woo-clean-up' ); ?>">
				</p>
			</form>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::FORM_ACTION ); ?>">
				<?php wp_nonce_field( self::NONCE_ACTION ); ?>

				<?php
				$pagination_args = [
					'base'    => add_query_arg( 'paged', '%#%' ),
					'format'  => '',
					'total'   => $total_pages,
					'current' => $current_page,
				];
				?>
				<div class="tablenav top">
					<div class="alignleft actions bulkactions">
						<select name="dry_run">
							<option value="1"><?php esc_html_e( 'Dry Run (Simulation)', 'woo-clean-up' ); ?></option>
							<option value="0"><?php esc_html_e( 'Live Cleanup (Delete Data)', 'woo-clean-up' ); ?></option>
						</select>
						<input type="submit" class="button action" value="<?php esc_attr_e( 'Run', 'woo-clean-up' ); ?>">
					</div>
					<div class="tablenav-pages">
						<span class="displaying-num"><?php echo sprintf( _n( '%s item', '%s items', $total_terms, 'woo-clean-up' ), number_format_i18n( $total_terms ) ); ?></span>
						<?php echo paginate_links( $pagination_args ); ?>
					</div>
				</div>

				<table class="wp-list-table widefat striped fixed tags">
					<thead>
						<tr>
							<td id="cb" class="manage-column column-cb check-column"><input type="checkbox" /></td>
							
							<?php if ( ! empty( $columns['image'] ) ) : ?>
								<th scope="col" class="manage-column column-thumb"><span class="wc-image tips"><?php esc_html_e( 'Image', 'woo-clean-up' ); ?></span></th>
							<?php endif; ?>

							<th scope="col" class="manage-column column-name column-primary sortable <?php echo ( 'name' === $orderby ) ? esc_attr( $order ) : 'desc'; ?>">
								<?php $this->print_column_header( 'name', __( 'Name', 'woo-clean-up' ), $orderby, $order ); ?>
							</th>

							<?php if ( ! empty( $columns['description'] ) ) : ?>
								<th scope="col" class="manage-column column-description sortable <?php echo ( 'description' === $orderby ) ? esc_attr( $order ) : 'desc'; ?>">
									<?php $this->print_column_header( 'description', __( 'Description', 'woo-clean-up' ), $orderby, $order ); ?>
								</th>
							<?php endif; ?>

							<?php if ( ! empty( $columns['slug'] ) ) : ?>
								<th scope="col" class="manage-column column-slug sortable <?php echo ( 'slug' === $orderby ) ? esc_attr( $order ) : 'desc'; ?>">
									<?php $this->print_column_header( 'slug', __( 'Slug', 'woo-clean-up' ), $orderby, $order ); ?>
								</th>
							<?php endif; ?>

							<?php if ( ! empty( $columns['count'] ) ) : ?>
								<th scope="col" class="manage-column column-posts num sortable <?php echo ( 'count' === $orderby ) ? esc_attr( $order ) : 'desc'; ?>">
									<?php $this->print_column_header( 'count', __( 'Count', 'woo-clean-up' ), $orderby, $order ); ?>
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
										<td class="thumb column-thumb"><?php echo $image; ?></td>
									<?php endif; ?>

									<td class="name column-name" data-colname="<?php esc_attr_e( 'Name', 'woo-clean-up' ); ?>">
										<strong><a href="<?php echo esc_url( get_term_link( $category ) ); ?>" target="_blank" class="row-title"><?php echo esc_html( $category->name ); ?></a></strong>
										<div class="row-actions">
											<span class="view"><a href="<?php echo esc_url( get_term_link( $category ) ); ?>" target="_blank"><?php esc_html_e( 'View', 'woo-clean-up' ); ?></a></span>
											<span class="id">ID: <?php echo esc_html( $category->term_id ); ?></span>
										</div>
									</td>

									<?php if ( ! empty( $columns['description'] ) ) : ?>
										<td class="description column-description"><?php echo wp_trim_words( $category->description, 15 ); ?></td>
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
								<td colspan="6"><?php esc_html_e( 'No categories found.', 'woo-clean-up' ); ?></td>
							</tr>
							<?php
						}
						?>
					</tbody>
					<tfoot>
						<tr>
							<td class="manage-column column-cb check-column"><input type="checkbox" /></td>
							
							<?php if ( ! empty( $columns['image'] ) ) : ?>
								<th scope="col" class="manage-column column-thumb"><?php esc_html_e( 'Image', 'woo-clean-up' ); ?></th>
							<?php endif; ?>

							<th scope="col" class="manage-column column-name column-primary sortable <?php echo ( 'name' === $orderby ) ? esc_attr( $order ) : 'desc'; ?>">
								<?php $this->print_column_header( 'name', __( 'Name', 'woo-clean-up' ), $orderby, $order ); ?>
							</th>

							<?php if ( ! empty( $columns['description'] ) ) : ?>
								<th scope="col" class="manage-column column-description sortable <?php echo ( 'description' === $orderby ) ? esc_attr( $order ) : 'desc'; ?>">
									<?php $this->print_column_header( 'description', __( 'Description', 'woo-clean-up' ), $orderby, $order ); ?>
								</th>
							<?php endif; ?>

							<?php if ( ! empty( $columns['slug'] ) ) : ?>
								<th scope="col" class="manage-column column-slug sortable <?php echo ( 'slug' === $orderby ) ? esc_attr( $order ) : 'desc'; ?>">
									<?php $this->print_column_header( 'slug', __( 'Slug', 'woo-clean-up' ), $orderby, $order ); ?>
								</th>
							<?php endif; ?>

							<?php if ( ! empty( $columns['count'] ) ) : ?>
								<th scope="col" class="manage-column column-posts num sortable <?php echo ( 'count' === $orderby ) ? esc_attr( $order ) : 'desc'; ?>">
									<?php $this->print_column_header( 'count', __( 'Count', 'woo-clean-up' ), $orderby, $order ); ?>
								</th>
							<?php endif; ?>
						</tr>
					</tfoot>
				</table>

				<div class="tablenav bottom">
					<div class="tablenav-pages">
						<span class="displaying-num"><?php echo sprintf( _n( '%s item', '%s items', $total_terms, 'woo-clean-up' ), number_format_i18n( $total_terms ) ); ?></span>
						<?php echo paginate_links( $pagination_args ); ?>
					</div>
				</div>

			</form>
		</div>
		<?php
	}
}
