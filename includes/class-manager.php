<?php
/**
 * Widget manager: discovers widget modules, registers them with Elementor
 * and registers their assets.
 *
 * Convention for a new widget module (slug = "icon-box", for example):
 *   widgets/icon-box/icon-box.php  -> class SPW_Icon_Box_Widget
 *   widgets/icon-box/meta.php      -> optional, returns array( title, description, icon, version )
 *   assets/css/icon-box.css        -> optional, handle "spw-icon-box"
 *   assets/js/icon-box.js          -> optional, handle "spw-icon-box"
 *
 * @package Super_Widget
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Super_Widget_Manager {

	/**
	 * @var Super_Widget_Manager|null
	 */
	private static $instance = null;

	/**
	 * @var array|null
	 */
	private static $discovered = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
		add_action( 'elementor/frontend/after_register_styles', array( $this, 'register_styles' ) );
		add_action( 'elementor/frontend/after_register_scripts', array( $this, 'register_scripts' ) );
	}

	/* ------------------------------------------------------------------
	 * Discovery
	 * ------------------------------------------------------------------ */

	/**
	 * Find widget modules on disk.
	 *
	 * @return array<string, array{title:string,description:string,icon:string,version:string,file:string,class:string}>
	 */
	public static function discover() {
		if ( null !== self::$discovered ) {
			return self::$discovered;
		}

		$found = array();
		$dirs  = glob( SUPER_WIDGET_PATH . 'widgets/*', GLOB_ONLYDIR );
		$dirs  = is_array( $dirs ) ? $dirs : array();
		sort( $dirs );

		foreach ( $dirs as $dir ) {
			$slug = basename( $dir );

			// Only plain lowercase slugs are accepted.
			if ( ! preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug ) ) {
				continue;
			}

			$file = $dir . '/' . $slug . '.php';
			if ( ! is_readable( $file ) ) {
				continue;
			}

			$meta      = array();
			$meta_file = $dir . '/meta.php';
			if ( is_readable( $meta_file ) ) {
				$loaded = include $meta_file;
				if ( is_array( $loaded ) ) {
					$meta = $loaded;
				}
			}

			$found[ $slug ] = array(
				'title'       => isset( $meta['title'] ) ? sanitize_text_field( (string) $meta['title'] ) : ucwords( str_replace( '-', ' ', $slug ) ),
				'description' => isset( $meta['description'] ) ? sanitize_text_field( (string) $meta['description'] ) : '',
				'category'    => isset( $meta['category'] ) ? sanitize_key( (string) $meta['category'] ) : 'other',
				'icon'        => isset( $meta['icon'] ) ? sanitize_html_class( (string) $meta['icon'] ) : 'dashicons-screenoptions',
				'version'     => isset( $meta['version'] ) ? sanitize_text_field( (string) $meta['version'] ) : SUPER_WIDGET_VERSION,
				'file'        => $file,
				'class'       => 'SPW_' . implode( '_', array_map( 'ucfirst', explode( '-', $slug ) ) ) . '_Widget',
			);
		}

		/**
		 * Filter the discovered widget modules.
		 *
		 * @param array $found Slug => module data.
		 */
		self::$discovered = apply_filters( 'super_widget_widgets', $found );

		return self::$discovered;
	}

	/* ------------------------------------------------------------------
	 * Elementor integration
	 * ------------------------------------------------------------------ */

	public function register_category( $elements_manager ) {
		$elements_manager->add_category(
			'super-widget',
			array(
				'title' => esc_html( Super_Widget_Settings::category_title() ),
				'icon'  => 'eicon-apps',
			)
		);
	}

	public function register_widgets( $widgets_manager ) {
		$discovered = self::discover();

		foreach ( Super_Widget_Settings::active_widgets() as $slug ) {
			if ( ! isset( $discovered[ $slug ] ) ) {
				continue;
			}

			require_once $discovered[ $slug ]['file'];

			$class = $discovered[ $slug ]['class'];
			if ( class_exists( $class ) ) {
				$widgets_manager->register( new $class() );
			}
		}
	}

	/**
	 * Version string for an asset: plugin version + the file's modified time, so browsers,
	 * CDNs and optimizer plugins always fetch a changed file even if the version number stays.
	 */
	private function asset_ver( $rel ) {
		$file  = SUPER_WIDGET_PATH . $rel;
		$mtime = is_readable( $file ) ? (int) filemtime( $file ) : 0;
		return $mtime ? SUPER_WIDGET_VERSION . '.' . $mtime : SUPER_WIDGET_VERSION;
	}

	public function register_styles() {
		// Shared handle that carries the global design variables (accent color...).
		wp_register_style( 'spw-global', false, array(), SUPER_WIDGET_VERSION );
		$css = Super_Widget_Settings::global_css();
		if ( '' !== $css ) {
			wp_add_inline_style( 'spw-global', $css );
		}

		foreach ( Super_Widget_Settings::active_widgets() as $slug ) {
			$rel = 'assets/css/' . $slug . '.css';
			if ( is_readable( SUPER_WIDGET_PATH . $rel ) ) {
				wp_register_style( 'spw-' . $slug, SUPER_WIDGET_URL . $rel, array( 'spw-global' ), $this->asset_ver( $rel ) );
			}
		}
	}

	public function register_scripts() {
		foreach ( Super_Widget_Settings::active_widgets() as $slug ) {
			$rel = 'assets/js/' . $slug . '.js';
			if ( is_readable( SUPER_WIDGET_PATH . $rel ) ) {
				wp_register_script(
					'spw-' . $slug,
					SUPER_WIDGET_URL . $rel,
					array( 'jquery', 'elementor-frontend' ),
					$this->asset_ver( $rel ),
					true
				);
			}
		}
	}
}
