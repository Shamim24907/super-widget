<?php
/**
 * License key support (optional).
 *
 * The plugin keeps working without a license. A license only unlocks automatic updates
 * (and support) from YOUR license server. It is switched on by setting
 * SUPER_WIDGET_LICENSE_SERVER in super-widget.php to the https address of api.php of the
 * license server. With '' (the default) licensing is off and nothing here runs.
 *
 * What is sent to the license server (and only to it): the license key, this site's host name
 * (for example "example.com"), the plugin version. Nothing else, nothing on the front end.
 *
 * The key is stored in its own option, so "Export settings" never contains it.
 *
 * @package Super_Widget
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Super_Widget_License {

	const OPTION = 'super_widget_license';

	public static function init() {
		if ( ! self::enabled() ) {
			return;
		}
		add_action( 'admin_post_super_widget_license_activate', array( __CLASS__, 'handle_activate' ) );
		add_action( 'admin_post_super_widget_license_deactivate', array( __CLASS__, 'handle_deactivate' ) );
		add_action( 'admin_post_super_widget_license_check', array( __CLASS__, 'handle_check' ) );
		add_action( 'admin_notices', array( __CLASS__, 'notice' ) );
	}

	/* ------------------------------------------------------------------
	 * State
	 * ------------------------------------------------------------------ */

	/** https address of the license server's api.php, or ''. */
	public static function server() {
		if ( ! defined( 'SUPER_WIDGET_LICENSE_SERVER' ) ) {
			return '';
		}
		$url = trim( (string) SUPER_WIDGET_LICENSE_SERVER );
		return ( 0 === strpos( $url, 'https://' ) && filter_var( $url, FILTER_VALIDATE_URL ) ) ? $url : '';
	}

	public static function enabled() {
		return '' !== self::server();
	}

	/** @return array key, status, message, expires, sites_used, sites_max, checked */
	public static function get() {
		$d = get_option( self::OPTION, array() );
		$d = is_array( $d ) ? $d : array();
		return array(
			'key'        => isset( $d['key'] ) ? self::clean_key( $d['key'] ) : '',
			'status'     => isset( $d['status'] ) ? sanitize_key( (string) $d['status'] ) : 'inactive',
			'message'    => isset( $d['message'] ) ? sanitize_text_field( (string) $d['message'] ) : '',
			'expires'    => isset( $d['expires'] ) ? (int) $d['expires'] : 0,
			'sites_used' => isset( $d['sites_used'] ) ? (int) $d['sites_used'] : 0,
			'sites_max'  => isset( $d['sites_max'] ) ? (int) $d['sites_max'] : 0,
			'checked'    => isset( $d['checked'] ) ? (int) $d['checked'] : 0,
		);
	}

	public static function key() {
		$d = self::get();
		return $d['key'];
	}

	public static function is_valid() {
		$d = self::get();
		return 'valid' === $d['status'] && '' !== $d['key'];
	}

	public static function clean_key( $key ) {
		$key = strtoupper( trim( (string) $key ) );
		return preg_match( '/^[A-Z0-9]{2,6}(-[A-Z0-9]{3,8}){2,6}$/', $key ) ? $key : '';
	}

	/** This site's host name, e.g. "example.com" (no path, no scheme). */
	public static function site() {
		$host = wp_parse_url( home_url(), PHP_URL_HOST );
		return strtolower( (string) $host );
	}

	private static function save( $data ) {
		update_option( self::OPTION, $data, false );
		delete_site_transient( Super_Widget_Updater::CACHE );
	}

	/* ------------------------------------------------------------------
	 * Talking to the license server
	 * ------------------------------------------------------------------ */

	/**
	 * @param string $action activate | deactivate | check | update_check
	 * @param string $key    License key.
	 * @return array|null Decoded JSON, or null when the server could not be reached / answered nonsense.
	 */
	public static function request( $action, $key ) {
		$server = self::server();
		if ( '' === $server ) {
			return null;
		}
		$res = wp_remote_post(
			$server,
			array(
				'timeout'    => 12,
				'user-agent' => 'SuperWidget/' . SUPER_WIDGET_VERSION,
				'body'       => array(
					'action'  => $action,
					'key'     => $key,
					'site'    => self::site(),
					'version' => SUPER_WIDGET_VERSION,
				),
			)
		);
		if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
			return null;
		}
		$body = wp_remote_retrieve_body( $res );
		if ( strlen( $body ) > 262144 ) {
			return null;
		}
		$json = json_decode( $body, true );
		return is_array( $json ) ? $json : null;
	}

	private static function store_from_response( $key, $json ) {
		$status = isset( $json['status'] ) ? sanitize_key( (string) $json['status'] ) : 'invalid';
		if ( ! in_array( $status, array( 'valid', 'invalid', 'expired', 'revoked', 'limit', 'inactive' ), true ) ) {
			$status = 'invalid';
		}
		self::save(
			array(
				'key'        => $key,
				'status'     => ! empty( $json['success'] ) ? 'valid' : $status,
				'message'    => isset( $json['message'] ) ? sanitize_text_field( (string) $json['message'] ) : '',
				'expires'    => isset( $json['expires'] ) ? (int) $json['expires'] : 0,
				'sites_used' => isset( $json['sites_used'] ) ? (int) $json['sites_used'] : 0,
				'sites_max'  => isset( $json['sites_max'] ) ? (int) $json['sites_max'] : 0,
				'checked'    => time(),
			)
		);
	}

	/** @return string one of: ok, bad_key, unreachable, rejected */
	public static function activate( $raw_key ) {
		$key = self::clean_key( $raw_key );
		if ( '' === $key ) {
			return 'bad_key';
		}
		$json = self::request( 'activate', $key );
		if ( null === $json ) {
			return 'unreachable';
		}
		self::store_from_response( $key, $json );
		return ! empty( $json['success'] ) ? 'ok' : 'rejected';
	}

	public static function deactivate() {
		$key = self::key();
		if ( '' !== $key ) {
			self::request( 'deactivate', $key ); // Frees the slot. If the server is down the key is still forgotten here.
		}
		self::save( array( 'key' => '', 'status' => 'inactive' ) );
	}

	/** Ask the server again (used by "Check now" and once a day). */
	public static function refresh() {
		$key = self::key();
		if ( '' === $key ) {
			return 'bad_key';
		}
		$json = self::request( 'check', $key );
		if ( null === $json ) {
			return 'unreachable';
		}
		self::store_from_response( $key, $json );
		return ! empty( $json['success'] ) ? 'ok' : 'rejected';
	}

	/* ------------------------------------------------------------------
	 * Admin actions
	 * ------------------------------------------------------------------ */

	private static function guard( $nonce ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'super-widget' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( $nonce );
	}

	private static function back( $notice ) {
		wp_safe_redirect( add_query_arg( array( 'page' => 'super-widget', 'tab' => 'license', 'sw_notice' => $notice ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function handle_activate() {
		self::guard( 'super_widget_license_activate' );
		$raw = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked in guard().
		self::back( 'license_' . self::activate( $raw ) );
	}

	public static function handle_deactivate() {
		self::guard( 'super_widget_license_deactivate' );
		self::deactivate();
		self::back( 'license_deactivated' );
	}

	public static function handle_check() {
		self::guard( 'super_widget_license_check' );
		self::back( 'license_' . self::refresh() );
	}

	/** One short reminder in the admin until a license is active. */
	public static function notice() {
		if ( ! current_user_can( 'manage_options' ) || self::is_valid() ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || ! in_array( $screen->id, array( 'plugins', 'dashboard' ), true ) ) {
			return;
		}
		printf(
			'<div class="notice notice-info"><p><strong>Super Widget:</strong> %1$s <a href="%2$s">%3$s</a></p></div>',
			esc_html__( 'Enter your license key to receive automatic updates and support.', 'super-widget' ),
			esc_url( add_query_arg( array( 'page' => 'super-widget', 'tab' => 'license' ), admin_url( 'admin.php' ) ) ),
			esc_html__( 'Activate license', 'super-widget' )
		);
	}
}
