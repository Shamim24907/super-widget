<?php
/**
 * Self-hosted updates, so the plugin updates itself on every site.
 *
 * Why: a plugin that is not on WordPress.org never gets update notices. This class adds
 * them. The site asks ONE place (a public GitHub repository's latest release, or a small
 * JSON file on your own server) whether a newer version exists. If yes, WordPress shows the
 * normal "update available" notice and can update it automatically.
 *
 * Safety:
 * - Admin / cron only. Nothing runs for visitors and no widget calls out.
 * - HTTPS only. The download must come from GitHub or from the same host as your JSON file.
 * - Optional SHA-256 in the JSON file: the zip is rejected when it does not match.
 * - The request carries no site URL or data (WordPress normally puts the site URL in the
 *   User-Agent, this sends only "SuperWidget/<version>").
 * - Cached for 12 hours, so it never slows the admin down.
 *
 * Source (first one that is filled wins):
 *   1. Super Widget > Settings > Updates > "Update source"
 *   2. SUPER_WIDGET_UPDATE_SOURCE in super-widget.php (set once before you build the zip,
 *      then every site you install it on already knows where to look)
 * Value: "owner/repository" (GitHub, latest release) or "https://example.com/super-widget.json".
 *
 * @package Super_Widget
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Super_Widget_Updater {

	const CACHE = 'super_widget_update_info';
	const SLUG  = 'super-widget';

	public static function init() {
		add_filter( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'inject' ) );
		add_filter( 'plugins_api', array( __CLASS__, 'plugin_info' ), 10, 3 );
		add_filter( 'upgrader_pre_download', array( __CLASS__, 'verify_download' ), 10, 3 );
		add_filter( 'upgrader_source_selection', array( __CLASS__, 'fix_folder' ), 10, 4 );
		add_filter( 'auto_update_plugin', array( __CLASS__, 'auto_update' ), 10, 2 );
		add_action( 'admin_post_super_widget_check_update', array( __CLASS__, 'handle_check' ) );
	}

	/* ------------------------------------------------------------------
	 * Source
	 * ------------------------------------------------------------------ */

	/**
	 * @return array{0:string,1:string} type ("github"|"manifest"|""), value.
	 */
	public static function source() {
		if ( Super_Widget_License::enabled() ) {
			return array( 'license', Super_Widget_License::server() );
		}
		$src = trim( (string) Super_Widget_Settings::get( 'update_source' ) );
		if ( '' === $src && defined( 'SUPER_WIDGET_UPDATE_SOURCE' ) ) {
			$src = trim( (string) SUPER_WIDGET_UPDATE_SOURCE );
		}
		return self::parse_source( $src );
	}

	public static function parse_source( $src ) {
		$src = trim( (string) $src );
		if ( preg_match( '#^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$#', $src ) ) {
			return array( 'github', $src );
		}
		if ( 0 === strpos( $src, 'https://' ) && filter_var( $src, FILTER_VALIDATE_URL ) ) {
			return array( 'manifest', $src );
		}
		return array( '', '' );
	}

	private static function basename() {
		return plugin_basename( SUPER_WIDGET_FILE );
	}

	private static function valid_version( $v ) {
		return is_string( $v ) && (bool) preg_match( '/^\d+(\.\d+){1,3}([-+][0-9A-Za-z.\-]+)?$/', $v );
	}

	/** A download may only come from GitHub or from the same host as the manifest. */
	private static function safe_package( $url, $type, $value ) {
		if ( ! is_string( $url ) || 0 !== strpos( $url, 'https://' ) ) {
			return false;
		}
		$host    = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		$allowed = array( 'github.com', 'api.github.com', 'codeload.github.com', 'objects.githubusercontent.com' );
		if ( 'license' === $type ) {
			$allowed = array(); // Licensed downloads come only from the license server itself.
		}
		if ( in_array( $type, array( 'manifest', 'license' ), true ) ) {
			$allowed[] = strtolower( (string) wp_parse_url( $value, PHP_URL_HOST ) );
		}
		return in_array( $host, $allowed, true );
	}

	/* ------------------------------------------------------------------
	 * Remote lookup (cached)
	 * ------------------------------------------------------------------ */

	/**
	 * @param bool $force Ignore the cache.
	 * @return array|null version, package, url, changelog, requires, tested, requires_php, sha256, checked
	 */
	public static function remote( $force = false ) {
		list( $type, $value ) = self::source();
		if ( '' === $type ) {
			return null;
		}
		$for = $type . '|' . $value;
		if ( 'license' === $type ) {
			if ( ! Super_Widget_License::is_valid() ) {
				return null; // No valid license, no update offered.
			}
			$for .= '|' . md5( Super_Widget_License::key() );
		}

		if ( ! $force ) {
			$cached = get_site_transient( self::CACHE );
			if ( is_array( $cached ) && isset( $cached['for'] ) && $cached['for'] === $for ) {
				return ! empty( $cached['data'] ) ? $cached['data'] : null;
			}
		}

		if ( 'github' === $type ) {
			$data = self::fetch_github( $value );
		} elseif ( 'license' === $type ) {
			$data = self::fetch_license( $value );
		} else {
			$data = self::fetch_manifest( $value );
		}
		if ( $data ) {
			$data['checked'] = time();
		}

		// A failed lookup is cached for 1 hour so a broken source cannot slow the admin down.
		set_site_transient(
			self::CACHE,
			array( 'for' => $for, 'data' => $data ),
			$data ? 12 * HOUR_IN_SECONDS : HOUR_IN_SECONDS
		);

		return $data;
	}

	private static function get_json( $url, $accept ) {
		$res = wp_remote_get(
			$url,
			array(
				'timeout'     => 8,
				'redirection' => 3,
				'user-agent'  => 'SuperWidget/' . SUPER_WIDGET_VERSION,
				'headers'     => array( 'Accept' => $accept ),
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

	private static function fetch_github( $repo ) {
		$json = self::get_json( 'https://api.github.com/repos/' . $repo . '/releases/latest', 'application/vnd.github+json' );
		if ( ! $json || empty( $json['tag_name'] ) ) {
			return null;
		}
		$version = ltrim( (string) $json['tag_name'], 'vV' );
		if ( ! self::valid_version( $version ) ) {
			return null;
		}

		// Prefer an attached .zip (built with the right folder name); fall back to the source zip.
		$package = '';
		if ( ! empty( $json['assets'] ) && is_array( $json['assets'] ) ) {
			foreach ( $json['assets'] as $asset ) {
				if ( isset( $asset['name'], $asset['browser_download_url'] ) && '.zip' === strtolower( substr( (string) $asset['name'], -4 ) ) ) {
					$package = (string) $asset['browser_download_url'];
					break;
				}
			}
		}
		if ( '' === $package && ! empty( $json['zipball_url'] ) ) {
			$package = (string) $json['zipball_url'];
		}
		if ( ! self::safe_package( $package, 'github', $repo ) ) {
			return null;
		}

		return array(
			'version'      => $version,
			'package'      => $package,
			'url'          => isset( $json['html_url'] ) ? esc_url_raw( (string) $json['html_url'] ) : '',
			'changelog'    => isset( $json['body'] ) ? wp_kses_post( (string) $json['body'] ) : '',
			'requires'     => '',
			'tested'       => '',
			'requires_php' => '',
			'sha256'       => '',
		);
	}

	/** Licensed update check: the server answers only for a valid key activated on this site. */
	private static function fetch_license( $server ) {
		$json = Super_Widget_License::request( 'update_check', Super_Widget_License::key() );
		if ( ! $json ) {
			return null;
		}
		if ( empty( $json['success'] ) ) {
			// The server says the license is no longer good: remember it, so the License tab shows why.
			Super_Widget_License::refresh();
			return null;
		}
		return self::normalize_manifest( $json, 'license', $server );
	}

	private static function fetch_manifest( $url ) {
		$json = self::get_json( $url, 'application/json' );
		if ( ! $json ) {
			return null;
		}
		return self::normalize_manifest( $json, 'manifest', $url );
	}

	private static function normalize_manifest( $json, $type, $url ) {
		if ( empty( $json['version'] ) || empty( $json['download_url'] ) ) {
			return null;
		}
		$version = ltrim( (string) $json['version'], 'vV' );
		if ( ! self::valid_version( $version ) || ! self::safe_package( (string) $json['download_url'], $type, $url ) ) {
			return null;
		}
		$sha = isset( $json['sha256'] ) ? strtolower( trim( (string) $json['sha256'] ) ) : '';
		if ( '' !== $sha && ! preg_match( '/^[0-9a-f]{64}$/', $sha ) ) {
			return null;
		}
		$pick = function ( $k ) use ( $json ) {
			return isset( $json[ $k ] ) && is_scalar( $json[ $k ] ) ? sanitize_text_field( (string) $json[ $k ] ) : '';
		};
		return array(
			'version'      => $version,
			'package'      => (string) $json['download_url'],
			'url'          => isset( $json['homepage'] ) ? esc_url_raw( (string) $json['homepage'] ) : '',
			'changelog'    => isset( $json['changelog'] ) ? wp_kses_post( (string) $json['changelog'] ) : '',
			'requires'     => $pick( 'requires' ),
			'tested'       => $pick( 'tested' ),
			'requires_php' => $pick( 'requires_php' ),
			'sha256'       => $sha,
		);
	}

	/* ------------------------------------------------------------------
	 * WordPress hooks
	 * ------------------------------------------------------------------ */

	public static function inject( $transient ) {
		if ( ! is_object( $transient ) || empty( $transient->checked ) ) {
			return $transient;
		}
		$info = self::remote();
		if ( ! $info ) {
			return $transient;
		}

		$item = (object) array(
			'id'           => self::basename(),
			'slug'         => self::SLUG,
			'plugin'       => self::basename(),
			'new_version'  => $info['version'],
			'url'          => $info['url'],
			'package'      => $info['package'],
			'requires'     => $info['requires'],
			'tested'       => $info['tested'],
			'requires_php' => $info['requires_php'],
		);

		if ( version_compare( $info['version'], SUPER_WIDGET_VERSION, '>' ) ) {
			$transient->response[ self::basename() ] = $item;
		} else {
			// Listed as "up to date": this is also what makes the auto-update switch appear on the Plugins screen.
			$transient->no_update[ self::basename() ] = $item;
		}
		return $transient;
	}

	public static function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || ! isset( $args->slug ) || self::SLUG !== $args->slug ) {
			return $result;
		}
		$info = self::remote();
		if ( ! $info ) {
			return $result;
		}
		return (object) array(
			'name'          => 'Super Widget',
			'slug'          => self::SLUG,
			'version'       => $info['version'],
			'author'        => 'Super Widget',
			'homepage'      => $info['url'],
			'requires'      => $info['requires'],
			'tested'        => $info['tested'],
			'requires_php'  => $info['requires_php'],
			'last_updated'  => gmdate( 'Y-m-d', (int) $info['checked'] ),
			'download_link' => $info['package'],
			'sections'      => array(
				'description' => esc_html__( 'A modular widget pack for Elementor.', 'super-widget' ),
				'changelog'   => '' !== $info['changelog'] ? wpautop( $info['changelog'] ) : esc_html__( 'See the release notes.', 'super-widget' ),
			),
		);
	}

	/**
	 * Reject the download when the checksum does not match. For licensed updates the download link is
	 * short-lived (15 minutes), so a fresh one is requested right before downloading.
	 */
	public static function verify_download( $reply, $package, $upgrader ) {
		$info = self::remote();
		if ( ! $info ) {
			return $reply;
		}
		list( $type ) = self::source();

		if ( 'license' === $type && 0 === strpos( (string) $package, Super_Widget_License::server() ) ) {
			$info = self::remote( true );
			if ( ! $info || empty( $info['sha256'] ) ) {
				return new WP_Error( 'super_widget_license', __( 'The license server did not give a download. Check your license on Super Widget > License.', 'super-widget' ) );
			}
			$package = $info['package'];
		} elseif ( $package !== $info['package'] || '' === $info['sha256'] ) {
			return $reply;
		}

		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		$file = download_url( $package, 300 );
		if ( is_wp_error( $file ) ) {
			return $file;
		}
		if ( ! hash_equals( $info['sha256'], strtolower( (string) hash_file( 'sha256', $file ) ) ) ) {
			wp_delete_file( $file );
			return new WP_Error( 'super_widget_hash', __( 'The downloaded update did not match its checksum, so it was not installed.', 'super-widget' ) );
		}
		return $file;
	}

	/** GitHub source zips unpack into a folder with a random name; always install into "super-widget". */
	public static function fix_folder( $source, $remote_source, $upgrader, $hook_extra = array() ) {
		if ( empty( $hook_extra['plugin'] ) || self::basename() !== $hook_extra['plugin'] ) {
			return $source;
		}
		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			return $source;
		}

		$dir = trailingslashit( $source );
		if ( ! $wp_filesystem->exists( $dir . 'super-widget.php' ) ) {
			// The plugin may sit one folder deeper.
			$list = $wp_filesystem->dirlist( $dir );
			if ( is_array( $list ) ) {
				foreach ( $list as $name => $meta ) {
					if ( 'd' === $meta['type'] && $wp_filesystem->exists( $dir . $name . '/super-widget.php' ) ) {
						$dir = trailingslashit( $dir . $name );
						break;
					}
				}
			}
		}
		if ( ! $wp_filesystem->exists( $dir . 'super-widget.php' ) ) {
			return new WP_Error( 'super_widget_package', __( 'The update package is not a Super Widget plugin, so it was not installed.', 'super-widget' ) );
		}

		$wanted = trailingslashit( $remote_source ) . self::SLUG . '/';
		if ( untrailingslashit( $dir ) === untrailingslashit( $wanted ) ) {
			return $wanted;
		}
		if ( $wp_filesystem->exists( $wanted ) ) {
			$wp_filesystem->delete( $wanted, true );
		}
		if ( ! $wp_filesystem->move( untrailingslashit( $dir ), untrailingslashit( $wanted ), true ) ) {
			return new WP_Error( 'super_widget_rename', __( 'Could not prepare the update folder.', 'super-widget' ) );
		}
		return $wanted;
	}

	/** "Update automatically" setting. Never turns a site's own choice off, only on. */
	public static function auto_update( $update, $item ) {
		if ( isset( $item->plugin ) && self::basename() === $item->plugin && Super_Widget_Settings::get( 'auto_update' ) ) {
			return true;
		}
		return $update;
	}

	/* ------------------------------------------------------------------
	 * "Check now" button (Tools tab)
	 * ------------------------------------------------------------------ */

	public static function handle_check() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'super-widget' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'super_widget_check_update' );

		delete_site_transient( self::CACHE );
		delete_site_transient( 'update_plugins' ); // WordPress rebuilds it, which calls inject() with a fresh lookup.
		$info   = self::remote( true );
		$notice = ! $info ? 'update_none' : ( version_compare( $info['version'], SUPER_WIDGET_VERSION, '>' ) ? 'update_found' : 'update_current' );
		if ( function_exists( 'wp_update_plugins' ) ) {
			wp_update_plugins();
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'      => 'super-widget',
					'tab'       => 'tools',
					'sw_notice' => $notice,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
