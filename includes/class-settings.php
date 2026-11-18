<?php
/**
 * Settings storage and sanitizing. One option holds everything.
 *
 * @package Super_Widget
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Super_Widget_Settings {

	const OPTION = 'super_widget_settings';
	const GROUP  = 'super_widget_group';

	/**
	 * Default values.
	 *
	 * widgets            null = every widget is on, array = only these slugs are on.
	 * disable_animations Turns off entrance + hover animations everywhere.
	 * accent_color       Hex color used by widget buttons, "" = widget default.
	 * delete_data        Remove settings when the plugin is deleted.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'widgets'            => null,
			'disable_animations' => false,
			'accent_color'       => '',
			'delete_data'        => false,
			'names'              => array(),
			'category_name'      => '',
			'update_source'      => '',
			'auto_update'        => true,
		);
	}

	/**
	 * Name shown in the Elementor panel for a widget. The admin can rename
	 * widgets on Super Widget > Widgets; otherwise the built-in name is used.
	 *
	 * @param string $slug    Widget slug, e.g. "card-grid".
	 * @param string $default Built-in name.
	 * @return string
	 */
	public static function widget_title( $slug, $default ) {
		$names = self::get( 'names' );
		if ( is_array( $names ) && ! empty( $names[ $slug ] ) ) {
			return (string) $names[ $slug ];
		}
		return $default;
	}

	/**
	 * Name of the Elementor panel category that holds the widgets.
	 *
	 * @return string
	 */
	public static function category_title() {
		$name = self::get( 'category_name' );
		return $name ? (string) $name : __( 'Super Widget', 'super-widget' );
	}

	public static function register() {
		register_setting(
			self::GROUP,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => array(),
			)
		);
	}

	/* ------------------------------------------------------------------
	 * Reading
	 * ------------------------------------------------------------------ */

	/**
	 * @return array Full, sanitized settings.
	 */
	public static function get_all() {
		$stored = get_option( self::OPTION, array() );
		return self::normalize( is_array( $stored ) ? $stored : array() );
	}

	public static function get( $key ) {
		$all = self::get_all();
		return array_key_exists( $key, $all ) ? $all[ $key ] : null;
	}

	/**
	 * Slugs of the widgets that are switched on.
	 *
	 * @return string[]
	 */
	public static function active_widgets() {
		$all     = array_keys( Super_Widget_Manager::discover() );
		$widgets = self::get( 'widgets' );

		if ( null === $widgets ) {
			return $all;
		}

		return array_values( array_intersect( $all, $widgets ) );
	}

	/**
	 * CSS variables shared by all widgets.
	 *
	 * @return string
	 */
	public static function global_css() {
		$accent = self::get( 'accent_color' );
		if ( ! $accent ) {
			return '';
		}

		return sprintf(
			':root{--spw-accent:%1$s;--spw-accent-dark:%2$s;}',
			$accent,
			self::darken_hex( $accent, 14 )
		);
	}

	/**
	 * Darken a #rrggbb / #rgb color by a percentage.
	 *
	 * @param string $hex     Valid hex color.
	 * @param int    $percent 0-100.
	 * @return string
	 */
	public static function darken_hex( $hex, $percent = 12 ) {
		$hex = ltrim( (string) $hex, '#' );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		if ( 6 !== strlen( $hex ) || ! ctype_xdigit( $hex ) ) {
			return '#000000';
		}

		$factor = max( 0, min( 100, 100 - (int) $percent ) ) / 100;

		return sprintf(
			'#%02x%02x%02x',
			(int) round( hexdec( substr( $hex, 0, 2 ) ) * $factor ),
			(int) round( hexdec( substr( $hex, 2, 2 ) ) * $factor ),
			(int) round( hexdec( substr( $hex, 4, 2 ) ) * $factor )
		);
	}

	/* ------------------------------------------------------------------
	 * Sanitizing
	 * ------------------------------------------------------------------ */

	/**
	 * Build a clean, complete settings array from anything.
	 *
	 * @param array $in Raw values.
	 * @return array
	 */
	public static function normalize( $in ) {
		$in  = is_array( $in ) ? $in : array();
		$out = self::defaults();

		if ( array_key_exists( 'widgets', $in ) && null !== $in['widgets'] ) {
			$slugs          = array_map( 'sanitize_key', (array) $in['widgets'] );
			$out['widgets'] = array_values( array_intersect( array_keys( Super_Widget_Manager::discover() ), $slugs ) );
		}

		$out['disable_animations'] = ! empty( $in['disable_animations'] );
		$out['delete_data']        = ! empty( $in['delete_data'] );

		$out['names'] = array();
		if ( ! empty( $in['names'] ) && is_array( $in['names'] ) ) {
			$known = array_keys( Super_Widget_Manager::discover() );
			foreach ( $in['names'] as $slug => $label ) {
				$slug = sanitize_key( (string) $slug );
				if ( ! in_array( $slug, $known, true ) || ! is_scalar( $label ) ) {
					continue;
				}
				$label = trim( sanitize_text_field( (string) $label ) );
				if ( '' !== $label ) {
					$out['names'][ $slug ] = function_exists( 'mb_substr' ) ? mb_substr( $label, 0, 60 ) : substr( $label, 0, 60 );
				}
			}
		}

		$category = isset( $in['category_name'] ) && is_scalar( $in['category_name'] ) ? trim( sanitize_text_field( (string) $in['category_name'] ) ) : '';
		$out['category_name'] = function_exists( 'mb_substr' ) ? mb_substr( $category, 0, 40 ) : substr( $category, 0, 40 );

		$out['update_source'] = '';
		if ( isset( $in['update_source'] ) && is_scalar( $in['update_source'] ) ) {
			$src = trim( (string) $in['update_source'] );
			$ok  = Super_Widget_Updater::parse_source( $src );
			$out['update_source'] = '' !== $ok[0] ? $src : '';
		}
		// Default is ON; a saved form that leaves it unticked stores false.
		$out['auto_update'] = array_key_exists( 'auto_update', $in ) ? ! empty( $in['auto_update'] ) : true;

		$color = isset( $in['accent_color'] ) && is_scalar( $in['accent_color'] ) ? sanitize_hex_color( trim( (string) $in['accent_color'] ) ) : '';

		$out['accent_color'] = $color ? $color : '';

		return $out;
	}

	/**
	 * Settings API callback. A form sends "_tab" with the tab it belongs to,
	 * so saving one tab never wipes the values of another tab.
	 *
	 * @param mixed $input Submitted value.
	 * @return array
	 */
	public static function sanitize( $input ) {
		if ( ! is_array( $input ) ) {
			return self::get_all();
		}

		$tab = isset( $input['_tab'] ) ? sanitize_key( (string) $input['_tab'] ) : '';

		if ( '' === $tab ) {
			return self::normalize( $input );
		}

		$current = self::get_all();

		if ( 'widgets' === $tab ) {
			$current['widgets']       = isset( $input['widgets'] ) && is_array( $input['widgets'] ) ? $input['widgets'] : array();
			$current['names']         = isset( $input['names'] ) && is_array( $input['names'] ) ? $input['names'] : array();
			$current['category_name'] = isset( $input['category_name'] ) ? $input['category_name'] : '';
		}

		if ( 'settings' === $tab ) {
			$current['disable_animations'] = ! empty( $input['disable_animations'] );
			$current['delete_data']        = ! empty( $input['delete_data'] );
			$current['accent_color']       = isset( $input['accent_color'] ) ? $input['accent_color'] : '';
			$current['update_source']      = isset( $input['update_source'] ) ? $input['update_source'] : '';
			$current['auto_update']        = ! empty( $input['auto_update'] );
		}

		return self::normalize( $current );
	}
}
