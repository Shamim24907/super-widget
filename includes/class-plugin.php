<?php
/**
 * Plugin bootstrap: dependency checks and loading.
 *
 * @package Super_Widget
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Super_Widget_Plugin {

	/**
	 * @var Super_Widget_Plugin|null
	 */
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		// Priority 20 so Elementor (priority 10) has already loaded.
		add_action( 'plugins_loaded', array( $this, 'init' ), 20 );
	}

	public function init() {
		load_plugin_textdomain( 'super-widget', false, dirname( plugin_basename( SUPER_WIDGET_FILE ) ) . '/languages' );

		// The dashboard is always available, even if Elementor is missing,
		// so the status page can explain what is wrong.
		if ( is_admin() ) {
			Super_Widget_Admin::instance();
		}

		if ( ! did_action( 'elementor/loaded' ) ) {
			add_action( 'admin_notices', array( $this, 'notice_missing_elementor' ) );
			return;
		}

		if ( ! defined( 'ELEMENTOR_VERSION' ) || version_compare( ELEMENTOR_VERSION, SUPER_WIDGET_MIN_ELEMENTOR, '<' ) ) {
			add_action( 'admin_notices', array( $this, 'notice_old_elementor' ) );
			return;
		}

		Super_Widget_Manager::instance();
	}

	public function notice_missing_elementor() {
		$this->print_notice(
			__( 'Super Widget requires the Elementor plugin to be installed and activated.', 'super-widget' )
		);
	}

	public function notice_old_elementor() {
		$this->print_notice(
			sprintf(
				/* translators: %s: minimum Elementor version */
				__( 'Super Widget requires Elementor %s or newer. Please update Elementor.', 'super-widget' ),
				SUPER_WIDGET_MIN_ELEMENTOR
			)
		);
	}

	private function print_notice( $message ) {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		printf( '<div class="notice notice-warning"><p>%s</p></div>', esc_html( $message ) );
	}
}
