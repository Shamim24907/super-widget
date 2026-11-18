<?php
/**
 * Arrow / pagination placement shared by the slider widgets.
 *
 * Each placement is a set of CSS variables on the widget root, so it can be
 * chosen separately per device without any JavaScript:
 *
 *   --ad-side / --ad-top / --ad-bot / --ad-fb   display of the arrow groups
 *   --ad-self / --ad-v / --ad-inset              alignment of the arrows
 *   --dd-top / --dd-bot / --dd-self              display + alignment of the dots
 *
 * The widget renders only the groups that some device needs.
 *
 * @package Super_Widget
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Box_Shadow;

final class Super_Widget_Nav {

	/** @return array<string,string> value => label */
	public static function arrow_options() {
		return array(
			'side-mid'      => __( 'Sides – middle', 'super-widget' ),
			'side-top'      => __( 'Sides – top', 'super-widget' ),
			'side-bottom'   => __( 'Sides – bottom', 'super-widget' ),
			'top-left'      => __( 'Top – left', 'super-widget' ),
			'top-center'    => __( 'Top – center', 'super-widget' ),
			'top-right'     => __( 'Top – right', 'super-widget' ),
			'bottom-left'   => __( 'Bottom – left', 'super-widget' ),
			'bottom-center' => __( 'Bottom – center', 'super-widget' ),
			'bottom-right'  => __( 'Bottom – right', 'super-widget' ),
		);
	}

	/** @return array<string,string> value => label */
	public static function dot_options() {
		$options = self::arrow_options();
		unset( $options['side-mid'], $options['side-top'], $options['side-bottom'] );
		return $options;
	}

	private static function align( $word ) {
		$map = array(
			'left'   => 'flex-start',
			'center' => 'center',
			'right'  => 'flex-end',
		);
		return isset( $map[ $word ] ) ? $map[ $word ] : 'center';
	}

	private static function arrow_dictionary() {
		$dict = array();

		$sides = array(
			'side-mid'    => 'center',
			'side-top'    => 'flex-start',
			'side-bottom' => 'flex-end',
		);
		foreach ( $sides as $key => $v ) {
			$dict[ $key ] = '--ad-side: flex; --ad-top: none; --ad-bot: none; --ad-fb: flex; --ad-self: center; --ad-v: ' . $v . '; --ad-inset: var(--spw-inset, 0px);';
		}

		foreach ( array( 'left', 'center', 'right' ) as $h ) {
			$dict[ 'top-' . $h ]    = '--ad-side: none; --ad-top: flex; --ad-bot: none; --ad-fb: none; --ad-self: ' . self::align( $h ) . '; --ad-v: center; --ad-inset: 0px;';
			$dict[ 'bottom-' . $h ] = '--ad-side: none; --ad-top: none; --ad-bot: flex; --ad-fb: none; --ad-self: ' . self::align( $h ) . '; --ad-v: center; --ad-inset: 0px;';
		}

		return $dict;
	}

	private static function dot_dictionary() {
		$dict = array();
		foreach ( array( 'left', 'center', 'right' ) as $h ) {
			$dict[ 'top-' . $h ]    = '--dd-top: flex; --dd-bot: none; --dd-self: ' . self::align( $h ) . ';';
			$dict[ 'bottom-' . $h ] = '--dd-top: none; --dd-bot: flex; --dd-self: ' . self::align( $h ) . ';';
		}
		return $dict;
	}

	/**
	 * Arrow position control (responsive).
	 *
	 * @param object $widget   Elementor widget.
	 * @param string $selector Root selector, e.g. ".spw-t".
	 * @param array  $defaults desktop / tablet / mobile.
	 */
	public static function add_arrow_control( $widget, $selector, $defaults ) {
		$widget->add_responsive_control(
			'arrows_pos',
			array(
				'label'                => esc_html__( 'Arrow position', 'super-widget' ),
				'type'                 => Controls_Manager::SELECT,
				'default'              => $defaults[0],
				'tablet_default'       => $defaults[1],
				'mobile_default'       => $defaults[2],
				'options'              => self::arrow_options(),
				'selectors_dictionary' => self::arrow_dictionary(),
				'selectors'            => array( '{{WRAPPER}} ' . $selector => '{{VALUE}}' ),
				'render_type'          => 'template',
				'condition'            => array( 'show_arrows' => 'yes' ),
			)
		);
	}

	public static function add_dot_control( $widget, $selector, $default, $label ) {
		$widget->add_responsive_control(
			'dots_pos',
			array(
				'label'                => $label,
				'type'                 => Controls_Manager::SELECT,
				'default'              => $default,
				'options'              => self::dot_options(),
				'selectors_dictionary' => self::dot_dictionary(),
				'selectors'            => array( '{{WRAPPER}} ' . $selector => '{{VALUE}}' ),
				'render_type'          => 'template',
				'condition'            => array( 'show_dots' => 'yes' ),
			)
		);
	}

	/**
	 * Arrow colors: ONE set of controls that styles both arrows, so a color
	 * change always reaches the previous and the next arrow. A switch lets the
	 * next arrow get its own colors when that is wanted.
	 *
	 * Control ids keep the old "prev_*" names for the shared set, so colors that
	 * were already saved on the "Previous" tab now apply to both arrows.
	 *
	 * @param object $widget Elementor widget.
	 * @param string $arrow  Arrow class selector, e.g. ".spw-t__arrow".
	 * @param array  $opts   border (bool), shadow (bool).
	 */
	public static function add_arrow_style( $widget, $arrow, $opts = array() ) {
		$opts = array_merge(
			array(
				'border' => false,
				'shadow' => false,
			),
			$opts
		);

		self::arrow_color_set(
			$widget,
			'prev',
			'{{WRAPPER}} ' . $arrow,
			array(),
			$opts,
			esc_html__( 'Both arrows', 'super-widget' )
		);

		$widget->add_control(
			'next_separate',
			array(
				'label'        => esc_html__( 'Style the next arrow separately', 'super-widget' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
				'separator'    => 'before',
				'description'  => esc_html__( 'Off: the colors above are used by both arrows. On: the next arrow gets the colors below.', 'super-widget' ),
			)
		);

		// One extra class beats the shared rule, whatever the CSS order.
		self::arrow_color_set(
			$widget,
			'next',
			'{{WRAPPER}} ' . $arrow . $arrow . '--next',
			array( 'next_separate' => 'yes' ),
			$opts,
			esc_html__( 'Next arrow', 'super-widget' )
		);
	}

	private static function arrow_color_set( $widget, $key, $sel, $cond, $opts, $heading ) {
		$hover = $sel . ':hover:not(:disabled), ' . $sel . ':focus-visible';

		$add = static function ( $id, $label, $selectors ) use ( $widget, $cond ) {
			$args = array(
				'label'     => $label,
				'type'      => Controls_Manager::COLOR,
				'selectors' => $selectors,
			);
			if ( $cond ) {
				$args['condition'] = $cond;
			}
			$widget->add_control( $id, $args );
		};

		$widget->add_control(
			$key . '_heading',
			array_merge(
				array(
					'label'     => $heading,
					'type'      => Controls_Manager::HEADING,
					'separator' => 'prev' === $key ? 'none' : 'before',
				),
				$cond ? array( 'condition' => $cond ) : array()
			)
		);

		$tab_args = $cond ? array( 'condition' => $cond ) : array();

		$widget->start_controls_tabs( 'arrow_tabs_' . $key, $tab_args );

		$widget->start_controls_tab( 'arrow_tab_' . $key . '_normal', array_merge( array( 'label' => esc_html__( 'Normal', 'super-widget' ) ), $tab_args ) );
		$add( $key . '_color', esc_html__( 'Icon color', 'super-widget' ), array( $sel => 'color: {{VALUE}};' ) );
		$add( $key . '_bg', esc_html__( 'Background', 'super-widget' ), array( $sel => 'background-color: {{VALUE}};' ) );
		if ( $opts['border'] ) {
			$add( $key . '_border', esc_html__( 'Border color', 'super-widget' ), array( $sel => 'border-color: {{VALUE}};' ) );
		}
		if ( $opts['shadow'] ) {
			$widget->add_group_control(
				Group_Control_Box_Shadow::get_type(),
				array_merge(
					array(
						'name'     => $key . '_shadow',
						'selector' => $sel,
					),
					$cond ? array( 'condition' => $cond ) : array()
				)
			);
		}
		$widget->end_controls_tab();

		$widget->start_controls_tab( 'arrow_tab_' . $key . '_hover', array_merge( array( 'label' => esc_html__( 'Hover', 'super-widget' ) ), $tab_args ) );
		$add( $key . '_hover_color', esc_html__( 'Icon color', 'super-widget' ), array( $hover => 'color: {{VALUE}};' ) );
		$add( $key . '_hover_bg', esc_html__( 'Background', 'super-widget' ), array( $hover => 'background-color: {{VALUE}};' ) );
		if ( $opts['border'] ) {
			$add( $key . '_hover_border', esc_html__( 'Border color', 'super-widget' ), array( $hover => 'border-color: {{VALUE}};' ) );
		}
		$widget->end_controls_tab();

		$widget->end_controls_tabs();
	}

	/** Position values chosen on any device, desktop first. */
	private static function values( $settings, $key, $fallback ) {
		$out = array();
		foreach ( array( $key, $key . '_tablet', $key . '_mobile' ) as $k ) {
			if ( ! empty( $settings[ $k ] ) && is_string( $settings[ $k ] ) ) {
				$out[] = $settings[ $k ];
			}
		}
		return empty( $out ) ? array( $fallback ) : $out;
	}

	/**
	 * Arrow groups to render: side, top, bottom and fb (fallback under the
	 * content when a "sides" widget gets too narrow).
	 *
	 * @return string[]
	 */
	public static function arrow_zones( $settings ) {
		$zones = array();
		foreach ( self::values( $settings, 'arrows_pos', 'bottom-center' ) as $value ) {
			$zone = strtok( $value, '-' );
			if ( in_array( $zone, array( 'side', 'top', 'bottom' ), true ) ) {
				$zones[] = $zone;
				if ( 'side' === $zone ) {
					$zones[] = 'fb';
				}
			}
		}
		return array_values( array_unique( $zones ) );
	}

	/** @return string[] top and / or bottom. */
	public static function dot_zones( $settings ) {
		$zones = array();
		foreach ( self::values( $settings, 'dots_pos', 'bottom-center' ) as $value ) {
			$zone = strtok( $value, '-' );
			if ( in_array( $zone, array( 'top', 'bottom' ), true ) ) {
				$zones[] = $zone;
			}
		}
		return array_values( array_unique( $zones ) );
	}
}
