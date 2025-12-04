<?php
/**
 * Plugin Name:       Cleanup Kit for WooCommerce
 * Plugin URI:        https://wprepublic.com/
 * Description:       Safely bulk delete products by category and remove orphaned data. Features WP-CLI support and Dry Run mode.
 * Version:           1.0.6
 * Author:            WP Republic
 * Author URI:        https://wprepublic.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cleanup-kit
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Tested up to: 6.9
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 8.3
 * WC Blocks: true
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'CLEANUP_KIT_VERSION', '1.0.6' );
define( 'CLEANUP_KIT_PATH', plugin_dir_path( __FILE__ ) );
define( 'CLEANUP_KIT_URL', plugin_dir_url( __FILE__ ) );

/**
 * The main plugin class.
 */
final class Cleanup_Kit {

	/**
	 * The single instance of the class.
	 * @var Cleanup_Kit
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
			new Cleanup_Kit_Admin();
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( 'cleanup-kit', 'Cleanup_Kit_CLI' );
		}
	}

	/**
	 * Include required files.
	 */
	private function includes() {
		require_once CLEANUP_KIT_PATH . 'includes/class-cleanup-kit-core.php';
		require_once CLEANUP_KIT_PATH . 'includes/class-cleanup-kit-admin.php';
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			require_once CLEANUP_KIT_PATH . 'includes/class-cleanup-kit-cli.php';
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
				<strong><?php esc_html_e( 'Cleanup Kit', 'cleanup-kit' ); ?></strong>
				<?php esc_html_e( 'requires WooCommerce to be installed and activated.', 'cleanup-kit' ); ?>
			</p>
		</div>
		<?php
	}
}

/**
 * Begins execution of the plugin.
 */
function cleanup_kit() {
	return Cleanup_Kit::instance();
}

// Let's go!
cleanup_kit();
