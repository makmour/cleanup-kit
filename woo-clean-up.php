<?php
/**
 * Plugin Name:       Woo Cleanup
 * Plugin URI:        https://example.com/
 * Description:       A safe, powerful bulk-cleanup utility for large WooCommerce stores.
 * Version:           1.0.6
 * Author:            Your Name
 * Author URI:        https://example.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       woo-clean-up
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 8.3
 * WC High-Performance Order Storage: true
 * WC Blocks: true
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'WOO_CLEANUP_VERSION', '1.0.6' );
define( 'WOO_CLEANUP_PATH', plugin_dir_path( __FILE__ ) );
define( 'WOO_CLEANUP_URL', plugin_dir_url( __FILE__ ) );

/**
 * Declare compatibility with Custom Order Tables (HPOS) and Blocks.
 */
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
	}
} );

/**
 * The main plugin class.
 */
final class Woo_Cleanup {

	/**
	 * The single instance of the class.
	 * @var Woo_Cleanup
	 */
	private static $_instance = null;

	/**
	 * Ensures only one instance of the class is loaded.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'plugins_loaded', [ $this, 'init' ] );
	}

	/**
	 * Initialize the plugin.
	 */
	public function init() {
		// Check if WooCommerce is active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', [ $this, 'notice_missing_woocommerce' ] );
			return;
		}

		// Load core components.
		$this->includes();

		// Instantiate classes.
		if ( $this->is_request( 'admin' ) ) {
			new Woo_Cleanup_Admin();
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( 'woo-clean-up', 'Woo_Cleanup_CLI' );
		}
	}

	/**
	 * Include required files.
	 */
	private function includes() {
		require_once WOO_CLEANUP_PATH . 'includes/class-woo-cleanup-core.php';
		require_once WOO_CLEANUP_PATH . 'includes/class-woo-cleanup-admin.php';
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			require_once WOO_CLEANUP_PATH . 'includes/class-woo-cleanup-cli.php';
		}
	}

	/**
	 * Check the type of request.
	 * @param string $type admin, ajax, cron or frontend.
	 * @return bool
	 */
	private function is_request( $type ) {
		switch ( $type ) {
			case 'admin':
				return is_admin();
			case 'ajax':
				return defined( 'DOING_AJAX' );
			case 'cron':
				return defined( 'DOING_CRON' );
			case 'frontend':
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
		}
	}

	/**
	 * Admin notice for missing WooCommerce.
	 */
	public function notice_missing_woocommerce() {
		?>
		<div class="notice notice-error is-dismissible">
			<p>
				<strong><?php esc_html_e( 'Woo Cleanup', 'woo-clean-up' ); ?></strong>
				<?php esc_html_e( 'requires WooCommerce to be installed and activated.', 'woo-clean-up' ); ?>
			</p>
		</div>
		<?php
	}
}

/**
 * Begins execution of the plugin.
 */
function woo_cleanup() {
	return Woo_Cleanup::instance();
}

// Let's go!
woo_cleanup();
