<?php
/**
 * Image Mosaic widget.
 *
 * One image is shown through several tiles (a collage). Each tile is a box
 * placed in percent units, and every tile shows the matching part of the SAME
 * image, so the tiles together form the picture with gaps in between.
 * Ready layouts or your own tiles (repeater) with a shape per tile.
 *
 * @package SPW_Addons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Image_Size;
use Elementor\Repeater;
use Elementor\Utils;
use Elementor\Widget_Base;

class SPW_Image_Mosaic_Widget extends Widget_Base {

	public function get_name() {
		return 'spw-image-mosaic';
	}

	public function get_title() {
		return esc_html( Super_Widget_Settings::widget_title( 'image-mosaic', __( 'Image Mosaic', 'super-widget' ) ) );
	}

	public function get_icon() {
		return 'eicon-gallery-grid';
	}

	public function get_categories() {
		return array( 'super-widget' );
	}

	public function get_keywords() {
		return array( 'image', 'mosaic', 'collage', 'shape', 'puzzle', 'gallery' );
	}

	public function get_style_depends() {
		return array( 'spw-global', 'spw-image-mosaic' );
	}

	public function get_script_depends() {
		return array( 'spw-image-mosaic' );
	}

	/* ==================================================================
	 * Layouts and shapes
	 * Tile = array( x, y, width, height ) in percent of the whole mosaic.
	 * 'ratio' = height of the mosaic in percent of its width.
	 * ================================================================== */

	private function layouts() {
		return array(
			'reference' => array(
				'label' => esc_html__( 'Soft collage (like the reference)', 'super-widget' ),
				'ratio' => 108,
				'tiles' => array(
					array( 33, 0, 33, 35 ),
					array( 69, 11, 31, 37 ),
					array( 0, 26, 30, 34 ),
					array( 33, 37, 33, 39 ),
					array( 69, 50, 31, 39 ),
					array( 0, 63, 30, 23 ),
					array( 33, 78, 33, 22 ),
				),
			),
			'puzzle'    => array(
				'label' => esc_html__( 'Full puzzle (whole picture visible)', 'super-widget' ),
				'ratio' => 100,
				'tiles' => array(
					array( 0, 0, 31, 34 ), array( 0, 36, 31, 28 ), array( 0, 66, 31, 34 ),
					array( 33, 0, 32, 22 ), array( 33, 24, 32, 44 ), array( 33, 70, 32, 30 ),
					array( 67, 0, 33, 42 ), array( 67, 44, 33, 26 ), array( 67, 72, 33, 28 ),
				),
			),
			'grid'      => array(
				'label' => esc_html__( 'Puzzle grid 3 x 3', 'super-widget' ),
				'ratio' => 100,
				'tiles' => array(
					array( 0, 0, 32, 32 ), array( 34, 0, 32, 32 ), array( 68, 0, 32, 32 ),
					array( 0, 34, 32, 32 ), array( 34, 34, 32, 32 ), array( 68, 34, 32, 32 ),
					array( 0, 68, 32, 32 ), array( 34, 68, 32, 32 ), array( 68, 68, 32, 32 ),
				),
			),
			'bento'     => array(
				'label' => esc_html__( 'Bento', 'super-widget' ),
				'ratio' => 100,
				'tiles' => array(
					array( 0, 0, 60, 58 ),
					array( 62, 0, 38, 28 ),
					array( 62, 30, 38, 28 ),
					array( 0, 60, 34, 40 ),
					array( 36, 60, 64, 40 ),
				),
			),
			'columns'   => array(
				'label' => esc_html__( 'Staggered columns', 'super-widget' ),
				'ratio' => 120,
				'tiles' => array(
					array( 0, 0, 32, 54 ), array( 0, 56, 32, 44 ),
					array( 34, 0, 32, 38 ), array( 34, 40, 32, 60 ),
					array( 68, 0, 32, 64 ), array( 68, 66, 32, 34 ),
				),
			),
			'hero'      => array(
				'label' => esc_html__( 'Wide top + 4 small', 'super-widget' ),
				'ratio' => 85,
				'tiles' => array(
					array( 0, 0, 100, 62 ),
					array( 0, 64, 24, 36 ), array( 25.33, 64, 24, 36 ), array( 50.66, 64, 24, 36 ), array( 76, 64, 24, 36 ),
				),
			),
			'steps'     => array(
				'label' => esc_html__( 'Steps', 'super-widget' ),
				'ratio' => 90,
				'tiles' => array(
					array( 0, 50, 24, 50 ),
					array( 25.33, 33, 24, 67 ),
					array( 50.66, 16, 24, 84 ),
					array( 76, 0, 24, 100 ),
				),
			),
		);
	}

	private function shapes( $with_inherit = false ) {
		$shapes = array(
			'rounded' => esc_html__( 'Rounded', 'super-widget' ),
			'square'  => esc_html__( 'Square', 'super-widget' ),
			'circle'  => esc_html__( 'Circle / oval', 'super-widget' ),
			'pill'    => esc_html__( 'Pill', 'super-widget' ),
			'arch'    => esc_html__( 'Arch', 'super-widget' ),
			'leaf'    => esc_html__( 'Leaf', 'super-widget' ),
			'hexagon' => esc_html__( 'Hexagon', 'super-widget' ),
			'diamond' => esc_html__( 'Diamond', 'super-widget' ),
		);
		if ( $with_inherit ) {
			$shapes = array( 'inherit' => esc_html__( 'Same as "Tile shape"', 'super-widget' ) ) + $shapes;
		}
		return $shapes;
	}

	private function entrance_options() {
		return array(
			'none'    => esc_html__( 'None', 'super-widget' ),
			'fade-in' => esc_html__( 'Fade in', 'super-widget' ),
			'fade-up' => esc_html__( 'Fade up', 'super-widget' ),
			'zoom-in' => esc_html__( 'Zoom in', 'super-widget' ),
		);
	}

	/* ==================================================================
	 * Controls
	 * ================================================================== */

	protected function register_controls() {
		$this->controls_image();
		$this->controls_shapes();
		$this->controls_animation();
		$this->style_layout();
		$this->style_tiles();
	}

	private function controls_image() {
		$r = '{{WRAPPER}} .spw-im';

		$this->start_controls_section(
			'section_image',
			array(
				'label' => esc_html__( 'Image', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'image',
			array(
				'label'   => esc_html__( 'Choose image', 'super-widget' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => array( 'url' => Utils::get_placeholder_image_src() ),
			)
		);

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			array(
				'name'    => 'image',
				'default' => 'large',
				'exclude' => array( 'custom' ),
			)
		);

		$this->add_control(
			'image_alt',
			array(
				'label'       => esc_html__( 'Alt text', 'super-widget' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'description' => esc_html__( 'Leave empty to use the alt text of the media library image.', 'super-widget' ),
			)
		);

		$this->add_control(
			'focus_x',
			array(
				'label'     => esc_html__( 'Focus point: horizontal (%)', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 100 ) ),
				'default'   => array( 'size' => 50 ),
				'selectors' => array( $r => '--im-fx: {{SIZE}}%;' ),
				'separator' => 'before',
			)
		);

		$this->add_control(
			'focus_y',
			array(
				'label'       => esc_html__( 'Focus point: vertical (%)', 'super-widget' ),
				'type'        => Controls_Manager::SLIDER,
				'range'       => array( 'px' => array( 'min' => 0, 'max' => 100 ) ),
				'default'     => array( 'size' => 50 ),
				'selectors'   => array( $r => '--im-fy: {{SIZE}}%;' ),
				'description' => esc_html__( 'Which part of the image stays visible when it is cropped to the mosaic.', 'super-widget' ),
			)
		);

		$this->add_control(
			'ratio_mode',
			array(
				'label'       => esc_html__( 'Mosaic shape', 'super-widget' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'image',
				'separator'   => 'before',
				'options'     => array(
					'image'  => esc_html__( 'Same proportions as the image (nothing is cropped)', 'super-widget' ),
					'layout' => esc_html__( 'Proportions of the chosen layout', 'super-widget' ),
				),
				'description' => esc_html__( 'With the first option the whole picture stays visible, so the person or object is easy to recognise.', 'super-widget' ),
			)
		);

		$this->add_control(
			'link',
			array(
				'label'     => esc_html__( 'Link (optional)', 'super-widget' ),
				'type'      => Controls_Manager::URL,
				'separator' => 'before',
			)
		);

		$this->end_controls_section();
	}

	private function controls_shapes() {
		$this->start_controls_section(
			'section_shapes',
			array(
				'label' => esc_html__( 'Shapes', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$layout_options = array();
		foreach ( $this->layouts() as $key => $layout ) {
			$layout_options[ $key ] = $layout['label'];
		}
		$layout_options['custom'] = esc_html__( 'Custom (add and move your own shapes)', 'super-widget' );

		$this->add_control(
			'layout',
			array(
				'label'   => esc_html__( 'Layout', 'super-widget' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'reference',
				'options' => $layout_options,
			)
		);

		$this->add_control(
			'tile_shape',
			array(
				'label'   => esc_html__( 'Tile shape', 'super-widget' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'rounded',
				'options' => $this->shapes(),
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'tile_shape',
			array(
				'label'   => esc_html__( 'Shape', 'super-widget' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'inherit',
				'options' => $this->shapes( true ),
			)
		);

		foreach ( array(
			'tile_x' => esc_html__( 'Left (%)', 'super-widget' ),
			'tile_y' => esc_html__( 'Top (%)', 'super-widget' ),
			'tile_w' => esc_html__( 'Width (%)', 'super-widget' ),
			'tile_h' => esc_html__( 'Height (%)', 'super-widget' ),
		) as $id => $label ) {
			$repeater->add_control(
				$id,
				array(
					'label'   => $label,
					'type'    => Controls_Manager::SLIDER,
					'range'   => array( 'px' => array( 'min' => 0, 'max' => 100, 'step' => 0.5 ) ),
					'default' => array( 'size' => ( 'tile_w' === $id || 'tile_h' === $id ) ? 30 : 0 ),
				)
			);
		}

		$layouts  = $this->layouts();
		$defaults = array();
		foreach ( $layouts['reference']['tiles'] as $t ) {
			$defaults[] = array(
				'tile_shape' => 'inherit',
				'tile_x'     => array( 'size' => $t[0], 'unit' => 'px' ),
				'tile_y'     => array( 'size' => $t[1], 'unit' => 'px' ),
				'tile_w'     => array( 'size' => $t[2], 'unit' => 'px' ),
				'tile_h'     => array( 'size' => $t[3], 'unit' => 'px' ),
			);
		}

		$this->add_control(
			'tiles',
			array(
				'label'       => esc_html__( 'Shapes', 'super-widget' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => $defaults,
				'title_field' => '{{{ tile_shape }}} - {{{ tile_x.size }}}% / {{{ tile_y.size }}}% - {{{ tile_w.size }}} x {{{ tile_h.size }}}',
				'condition'   => array( 'layout' => 'custom' ),
			)
		);

		$this->add_control(
			'tiles_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Positions are percent of the whole mosaic: Left + Width and Top + Height should stay at or below 100. Set the mosaic height in Style > Layout.', 'super-widget' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
				'condition'       => array( 'layout' => 'custom' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_animation() {
		$r = '{{WRAPPER}} .spw-im';

		$this->start_controls_section(
			'section_animation',
			array(
				'label' => esc_html__( 'Animation', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'entrance_animation',
			array(
				'label'   => esc_html__( 'Entrance animation', 'super-widget' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'zoom-in',
				'options' => $this->entrance_options(),
			)
		);

		$this->add_control(
			'animate_once',
			array(
				'label'        => esc_html__( 'Animate only once', 'super-widget' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array( 'entrance_animation!' => 'none' ),
			)
		);

		$this->add_control(
			'animation_duration',
			array(
				'label'     => esc_html__( 'Duration (ms)', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 100, 'max' => 2000, 'step' => 50 ) ),
				'default'   => array( 'size' => 700 ),
				'condition' => array( 'entrance_animation!' => 'none' ),
				'selectors' => array( $r => '--im-dur: {{SIZE}}ms;' ),
			)
		);

		$this->add_control(
			'animation_stagger',
			array(
				'label'     => esc_html__( 'Delay between tiles (ms)', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 500, 'step' => 20 ) ),
				'default'   => array( 'size' => 120 ),
				'condition' => array( 'entrance_animation!' => 'none' ),
				'selectors' => array( $r => '--im-stagger: {{SIZE}}ms;' ),
			)
		);

		$this->add_control(
			'hover_effect',
			array(
				'label'     => esc_html__( 'Hover effect', 'super-widget' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'pop',
				'separator' => 'before',
				'options'   => array(
					'none' => esc_html__( 'None', 'super-widget' ),
					'pop'  => esc_html__( 'Pop (tile grows)', 'super-widget' ),
				),
			)
		);

		$this->add_control(
			'hover_scale',
			array(
				'label'     => esc_html__( 'Pop size', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 1, 'max' => 1.3, 'step' => 0.01 ) ),
				'default'   => array( 'size' => 1.05 ),
				'condition' => array( 'hover_effect' => 'pop' ),
				'selectors' => array( $r => '--im-pop: {{SIZE}};' ),
			)
		);

		$this->end_controls_section();
	}

	/* ----------------------------- Style ----------------------------- */

	private function style_layout() {
		$r = '{{WRAPPER}} .spw-im';

		$this->start_controls_section(
			'style_layout',
			array(
				'label' => esc_html__( 'Layout', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'align',
			array(
				'label'                => esc_html__( 'Alignment', 'super-widget' ),
				'type'                 => Controls_Manager::CHOOSE,
				'options'              => array(
					'left'   => array( 'title' => esc_html__( 'Left', 'super-widget' ), 'icon' => 'eicon-h-align-left' ),
					'center' => array( 'title' => esc_html__( 'Center', 'super-widget' ), 'icon' => 'eicon-h-align-center' ),
					'right'  => array( 'title' => esc_html__( 'Right', 'super-widget' ), 'icon' => 'eicon-h-align-right' ),
				),
				'selectors_dictionary' => array(
					'left'   => '0 auto 0 0',
					'center' => '0 auto',
					'right'  => '0 0 0 auto',
				),
				'selectors'            => array( $r => 'margin: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'max_width',
			array(
				'label'      => esc_html__( 'Max width', 'super-widget' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array( 'min' => 150, 'max' => 1400 ),
					'%'  => array( 'min' => 10, 'max' => 100 ),
				),
				'selectors'  => array( $r => 'max-width: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'height_ratio',
			array(
				'label'       => esc_html__( 'Height (% of width)', 'super-widget' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( '%' ),
				'range'       => array( '%' => array( 'min' => 40, 'max' => 200 ) ),
				'selectors'   => array( $r => 'aspect-ratio: 100 / {{SIZE}};' ),
				'description' => esc_html__( 'Leave empty to use the height of the chosen layout. 100 = square.', 'super-widget' ),
			)
		);

		$this->add_responsive_control(
			'tile_gap',
			array(
				'label'     => esc_html__( 'Space between tiles', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'default'   => array( 'size' => 8, 'unit' => 'px' ),
				'selectors' => array( $r => '--im-gx: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'tile_gap_y',
			array(
				'label'       => esc_html__( 'Space between rows (optional)', 'super-widget' ),
				'type'        => Controls_Manager::SLIDER,
				'range'       => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'selectors'   => array( $r => '--im-gy: {{SIZE}}{{UNIT}};' ),
				'description' => esc_html__( 'Leave empty to use the same space as above. A small gap keeps the picture easy to read.', 'super-widget' ),
			)
		);

		$this->end_controls_section();
	}

	private function style_tiles() {
		$r    = '{{WRAPPER}} .spw-im';
		$tile = '{{WRAPPER}} .spw-im .spw-im__tile';

		$this->start_controls_section(
			'style_tiles',
			array(
				'label' => esc_html__( 'Tiles', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'tile_radius',
			array(
				'label'     => esc_html__( 'Corner radius', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 120 ) ),
				'default'   => array( 'size' => 22, 'unit' => 'px' ),
				'selectors' => array( $r => '--im-radius: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'tile_bg',
			array(
				'label'       => esc_html__( 'Tile background', 'super-widget' ),
				'type'        => Controls_Manager::COLOR,
				'selectors'   => array( $tile => 'background-color: {{VALUE}};' ),
				'description' => esc_html__( 'Visible behind transparent (PNG) images.', 'super-widget' ),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'tile_border',
				'selector' => $tile,
			)
		);

		$this->start_controls_tabs( 'tile_shadow_tabs' );

		$this->start_controls_tab( 'tile_shadow_normal', array( 'label' => esc_html__( 'Normal', 'super-widget' ) ) );
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'tile_shadow',
				'selector' => $tile,
			)
		);
		$this->end_controls_tab();

		$this->start_controls_tab( 'tile_shadow_hover', array( 'label' => esc_html__( 'Hover', 'super-widget' ) ) );
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'tile_shadow_hover',
				'selector' => $tile . ':hover',
			)
		);
		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	/* ============================ Render ============================== */

	/**
	 * Clamp a number to 0..100 with at most two decimals.
	 */
	private function pct( $value, $min = 0.0 ) {
		return number_format( max( $min, min( 100.0, (float) $value ) ), 2, '.', '' );
	}

	private function collect_tiles( $settings ) {
		$layouts = $this->layouts();
		$key     = isset( $settings['layout'] ) ? (string) $settings['layout'] : 'reference';
		$default = isset( $settings['tile_shape'] ) && array_key_exists( $settings['tile_shape'], $this->shapes() ) ? $settings['tile_shape'] : 'rounded';
		$tiles   = array();
		$ratio   = 108;

		if ( 'custom' === $key ) {
			$items = ! empty( $settings['tiles'] ) && is_array( $settings['tiles'] ) ? $settings['tiles'] : array();
			foreach ( $items as $item ) {
				$shape = isset( $item['tile_shape'] ) ? (string) $item['tile_shape'] : 'inherit';
				if ( 'inherit' === $shape || ! array_key_exists( $shape, $this->shapes() ) ) {
					$shape = $default;
				}
				$val = function ( $id ) use ( $item ) {
					return isset( $item[ $id ]['size'] ) ? (float) $item[ $id ]['size'] : 0.0;
				};
				$tiles[] = array( $this->pct( $val( 'tile_x' ) ), $this->pct( $val( 'tile_y' ) ), $this->pct( $val( 'tile_w' ), 1.0 ), $this->pct( $val( 'tile_h' ), 1.0 ), $shape );
			}
			return array( $tiles, 100 );
		}

		if ( ! isset( $layouts[ $key ] ) ) {
			$key = 'reference';
		}
		$ratio = $layouts[ $key ]['ratio'];
		foreach ( $layouts[ $key ]['tiles'] as $t ) {
			$tiles[] = array( $this->pct( $t[0] ), $this->pct( $t[1] ), $this->pct( $t[2], 1.0 ), $this->pct( $t[3], 1.0 ), $default );
		}

		return array( $tiles, $ratio );
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$image    = isset( $settings['image'] ) && is_array( $settings['image'] ) ? $settings['image'] : array();
		$id       = ! empty( $image['id'] ) ? absint( $image['id'] ) : 0;
		$url      = '';

		if ( $id ) {
			$src = Group_Control_Image_Size::get_attachment_image_src( $id, 'image', $settings );
			$url = $src ? $src : (string) wp_get_attachment_image_url( $id, 'large' );
		}
		if ( '' === $url && ! empty( $image['url'] ) ) {
			$url = (string) $image['url'];
		}
		if ( '' === $url ) {
			$url = Utils::get_placeholder_image_src();
		}

		$alt = isset( $settings['image_alt'] ) ? trim( (string) $settings['image_alt'] ) : '';
		if ( '' === $alt && $id ) {
			$alt = trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );
		}

		list( $tiles, $ratio ) = $this->collect_tiles( $settings );
		if ( empty( $tiles ) ) {
			if ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="elementor-alert elementor-alert-info" style="padding:16px;">' . esc_html__( 'Add a shape in the Shapes section.', 'super-widget' ) . '</div>';
			}
			return;
		}

		// Whitelisted values only.
		$entrance = isset( $settings['entrance_animation'] ) ? (string) $settings['entrance_animation'] : 'none';
		if ( ! array_key_exists( $entrance, $this->entrance_options() ) ) {
			$entrance = 'none';
		}
		$hover = ( isset( $settings['hover_effect'] ) && 'pop' === $settings['hover_effect'] ) ? 'pop' : 'none';

		// Global switch from Super Widget > Settings.
		if ( Super_Widget_Settings::get( 'disable_animations' ) ) {
			$entrance = 'none';
			$hover    = 'none';
		}

		$classes = array( 'spw-im' );
		if ( 'none' !== $entrance ) {
			$classes[] = 'spw-im--anim-' . $entrance;
		}
		if ( 'pop' === $hover ) {
			$classes[] = 'spw-im--pop';
		}

		// Same proportions as the image, so nothing is cropped away.
		$ratio_css = '100 / ' . (int) $ratio;
		if ( $id && ( ! isset( $settings['ratio_mode'] ) || 'layout' !== $settings['ratio_mode'] ) ) {
			$meta = wp_get_attachment_metadata( $id );
			if ( is_array( $meta ) && ! empty( $meta['width'] ) && ! empty( $meta['height'] ) ) {
				$rw = (int) $meta['width'];
				$rh = (int) $meta['height'];
				if ( $rw > 0 && $rh > 0 && $rw / $rh >= 0.3 && $rw / $rh <= 3.5 ) {
					$ratio_css = $rw . ' / ' . $rh;
				}
			}
		}

		$this->add_render_attribute(
			'root',
			array(
				'class'     => $classes,
				'style'     => '--im-ratio:' . $ratio_css . ';',
				'data-once' => ( isset( $settings['animate_once'] ) && 'yes' === $settings['animate_once'] ) ? 'yes' : 'no',
			)
		);

		$link_url = ! empty( $settings['link']['url'] );
		if ( $link_url ) {
			$this->add_link_attributes( 'link', $settings['link'] );
			$this->add_render_attribute( 'link', 'class', 'spw-im__link' );
			if ( '' !== $alt ) {
				$this->add_render_attribute( 'link', 'aria-label', $alt );
			}
		} elseif ( '' !== $alt ) {
			$this->add_render_attribute( 'root', array( 'role' => 'img', 'aria-label' => $alt ) );
		} else {
			$this->add_render_attribute( 'root', 'aria-hidden', 'true' );
		}
		?>
		<?php if ( $link_url ) : ?><a <?php $this->print_render_attribute_string( 'link' ); ?>><?php endif; ?>
		<div <?php $this->print_render_attribute_string( 'root' ); ?>>
			<?php foreach ( $tiles as $i => $t ) : ?>
				<div class="spw-im__tile spw-im__tile--<?php echo esc_attr( $t[4] ); ?>" style="--x:<?php echo esc_attr( $t[0] ); ?>;--y:<?php echo esc_attr( $t[1] ); ?>;--w:<?php echo esc_attr( $t[2] ); ?>;--h:<?php echo esc_attr( $t[3] ); ?>;--i:<?php echo (int) $i; ?>">
					<img src="<?php echo esc_url( $url ); ?>" alt="" aria-hidden="true" loading="lazy" decoding="async">
				</div>
			<?php endforeach; ?>
		</div>
		<?php if ( $link_url ) : ?></a><?php endif; ?>
		<?php
	}
}
