<?php
/**
 * Image Shapes widget.
 *
 * One image widget with many ready-made shapes. "Single" shapes use one picture
 * (arch, leaf, capsule, hexagon, offset frames, slash...). "Multi" shapes are small
 * collages of two or three pictures with a badge, a round arrow button or a seal.
 * Shapes are plain CSS (border-radius / clip-path), so they scale with the widget.
 *
 * @package SPW_Addons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Css_Filter;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;

class SPW_Image_Shapes_Widget extends Widget_Base {

	public function get_name() {
		return 'spw-image-shapes';
	}

	public function get_title() {
		return esc_html( Super_Widget_Settings::widget_title( 'image-shapes', __( 'Image Shapes', 'super-widget' ) ) );
	}

	public function get_icon() {
		return 'eicon-image-rollover';
	}

	public function get_categories() {
		return array( 'super-widget' );
	}

	public function get_keywords() {
		return array( 'image', 'shape', 'mask', 'arch', 'blob', 'collage', 'frame', 'photo' );
	}

	public function get_style_depends() {
		return array( 'spw-global', 'spw-image-shapes' );
	}

	public function get_script_depends() {
		return array( 'spw-image-shapes' );
	}

	/* ==================================================================
	 * Options
	 * ================================================================== */

	/**
	 * Every shape: label, group, number of pictures, and which extras it shows by default.
	 * extras: b = badge, g = round arrow button, s = seal, c = chips.
	 */
	private function shapes() {
		return array(
			// Single shapes.
			'arch'            => array( esc_html__( 'Arch', 'super-widget' ), 'single', 1, '' ),
			'arch-chips'      => array( esc_html__( 'Arch with floating labels', 'super-widget' ), 'single', 1, 'c' ),
			'capsule'         => array( esc_html__( 'Capsule with colour oval + ring', 'super-widget' ), 'single', 1, '' ),
			'leaf'            => array( esc_html__( 'Leaf (top-left / bottom-right)', 'super-widget' ), 'single', 1, '' ),
			'leaf-alt'        => array( esc_html__( 'Leaf (top-right / bottom-left)', 'super-widget' ), 'single', 1, '' ),
			'drop'            => array( esc_html__( 'Drop', 'super-widget' ), 'single', 1, '' ),
			'pebble'          => array( esc_html__( 'Pebble (organic)', 'super-widget' ), 'single', 1, '' ),
			'circle'          => array( esc_html__( 'Circle', 'super-widget' ), 'single', 1, '' ),
			'ring'            => array( esc_html__( 'Circle with outer ring', 'super-widget' ), 'single', 1, '' ),
			'squircle'        => array( esc_html__( 'Soft square', 'super-widget' ), 'single', 1, '' ),
			'pill-h'          => array( esc_html__( 'Wide pill', 'super-widget' ), 'single', 1, '' ),
			'offset-solid'    => array( esc_html__( 'Rounded + colour block behind', 'super-widget' ), 'single', 1, '' ),
			'offset-corner'   => array( esc_html__( 'Rounded + corner block', 'super-widget' ), 'single', 1, '' ),
			'offset-outline'  => array( esc_html__( 'Rounded + outline frame', 'super-widget' ), 'single', 1, '' ),
			'tilt-backdrop'   => array( esc_html__( 'Tilted colour card + seal', 'super-widget' ), 'single', 1, 'w' ),
			'slash-circle'    => array( esc_html__( 'Circle with diagonal slash', 'super-widget' ), 'single', 1, '' ),
			'slash-square'    => array( esc_html__( 'Rounded square, slash + arrow button', 'super-widget' ), 'single', 1, 'g' ),
			'hexagon'         => array( esc_html__( 'Hexagon', 'super-widget' ), 'single', 1, '' ),
			'diamond'         => array( esc_html__( 'Diamond', 'super-widget' ), 'single', 1, '' ),
			'octagon'         => array( esc_html__( 'Octagon', 'super-widget' ), 'single', 1, '' ),
			'cut-corner'      => array( esc_html__( 'Cut corners', 'super-widget' ), 'single', 1, '' ),
			'wave'            => array( esc_html__( 'Curved bottom', 'super-widget' ), 'single', 1, '' ),
			'star'            => array( esc_html__( 'Star', 'super-widget' ), 'single', 1, '' ),
			// Multi-picture shapes.
			'inset-frame'     => array( esc_html__( 'Big picture + framed small picture + arrow', 'super-widget' ), 'multi', 2, 'g' ),
			'duo-badge'       => array( esc_html__( 'Two pictures + number badge', 'super-widget' ), 'multi', 2, 'b' ),
			'card-circle'     => array( esc_html__( 'Picture + info card + round picture', 'super-widget' ), 'multi', 2, 'b' ),
			'twin-capsules'   => array( esc_html__( 'Twin capsules + round badge', 'super-widget' ), 'multi', 2, 'b' ),
			'offset-pair'     => array( esc_html__( 'Colour block + two pictures', 'super-widget' ), 'multi', 2, '' ),
			'circle-duo'      => array( esc_html__( 'Big circle + small circle', 'super-widget' ), 'multi', 2, '' ),
			'overlap-rounded' => array( esc_html__( 'Two overlapping rounded pictures', 'super-widget' ), 'multi', 2, '' ),
			'trio'            => array( esc_html__( 'Trio: one big, two stacked', 'super-widget' ), 'multi', 3, '' ),
		);
	}

	private function shape_options( $group ) {
		$out = array();
		foreach ( $this->shapes() as $key => $def ) {
			if ( $group === $def[1] ) {
				$out[ $key ] = $def[0];
			}
		}
		return $out;
	}

	private function entrances() {
		return array(
			'none'        => esc_html__( 'None', 'super-widget' ),
			'fade-in'     => esc_html__( 'Fade in', 'super-widget' ),
			'fade-up'     => esc_html__( 'Fade up', 'super-widget' ),
			'zoom-in'     => esc_html__( 'Zoom in', 'super-widget' ),
			'blur-in'     => esc_html__( 'Blur in', 'super-widget' ),
			'pop'         => esc_html__( 'Pop', 'super-widget' ),
			'slide-left'  => esc_html__( 'Slide from left', 'super-widget' ),
			'slide-right' => esc_html__( 'Slide from right', 'super-widget' ),
		);
	}

	private function hovers() {
		return array(
			'none'   => esc_html__( 'None', 'super-widget' ),
			'zoom'   => esc_html__( 'Zoom picture', 'super-widget' ),
			'lift'   => esc_html__( 'Lift', 'super-widget' ),
			'tilt'   => esc_html__( 'Tilt', 'super-widget' ),
			'spread' => esc_html__( 'Spread pictures apart', 'super-widget' ),
		);
	}

	/** Conditions: the control belongs to one of the listed shapes. */
	private function for_shapes( $single, $multi = array() ) {
		$terms = array();
		if ( $single ) {
			$terms[] = array(
				'relation' => 'and',
				'terms'    => array(
					array( 'name' => 'mode', 'operator' => '==', 'value' => 'single' ),
					array( 'name' => 'single_shape', 'operator' => 'in', 'value' => $single ),
				),
			);
		}
		if ( $multi ) {
			$terms[] = array(
				'relation' => 'and',
				'terms'    => array(
					array( 'name' => 'mode', 'operator' => '==', 'value' => 'multi' ),
					array( 'name' => 'multi_shape', 'operator' => 'in', 'value' => $multi ),
				),
			);
		}
		return array( 'relation' => 'or', 'terms' => $terms );
	}

	/* ==================================================================
	 * Small helpers for the long control list
	 * ================================================================== */

	private function slider( $id, $label, $selector, $decl, $min, $max, $default = null, $unit = 'px', $cond = array(), $responsive = true, $step = 1 ) {
		$args = array(
			'label'      => $label,
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( $unit ),
			'range'      => array( $unit => array( 'min' => $min, 'max' => $max, 'step' => $step ) ),
			'selectors'  => array( $selector => $decl ),
		);
		if ( null !== $default ) {
			$args['default'] = array( 'size' => $default, 'unit' => $unit );
		}
		if ( $cond ) {
			$args['condition'] = $cond;
		}
		if ( $responsive ) {
			$this->add_responsive_control( $id, $args );
		} else {
			$this->add_control( $id, $args );
		}
	}

	private function color( $id, $label, $selectors, $cond = array() ) {
		$args = array(
			'label'     => $label,
			'type'      => Controls_Manager::COLOR,
			'selectors' => $selectors,
		);
		if ( $cond ) {
			$args['condition'] = $cond;
		}
		$this->add_control( $id, $args );
	}

	private function heading( $id, $label ) {
		$this->add_control( $id, array( 'label' => $label, 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
	}

	/* ==================================================================
	 * Controls
	 * ================================================================== */

	protected function register_controls() {
		$root = '{{WRAPPER}} .spw-is';

		/* ------------------------- Content: Shape ------------------------- */
		$this->start_controls_section( 'section_shape', array( 'label' => esc_html__( 'Shape', 'super-widget' ) ) );
		$this->add_control( 'mode', array(
			'label'   => esc_html__( 'Type', 'super-widget' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'single',
			'options' => array(
				'single' => esc_html__( 'Single image shapes', 'super-widget' ),
				'multi'  => esc_html__( 'Multi image collages', 'super-widget' ),
			),
		) );
		$this->add_control( 'single_shape', array(
			'label'     => esc_html__( 'Shape', 'super-widget' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'arch',
			'options'   => $this->shape_options( 'single' ),
			'condition' => array( 'mode' => 'single' ),
		) );
		$this->add_control( 'multi_shape', array(
			'label'     => esc_html__( 'Collage', 'super-widget' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'card-circle',
			'options'   => $this->shape_options( 'multi' ),
			'condition' => array( 'mode' => 'multi' ),
		) );
		$this->end_controls_section();

		/* ------------------------- Content: Images ------------------------- */
		$this->start_controls_section( 'section_images', array( 'label' => esc_html__( 'Images', 'super-widget' ) ) );
		$this->add_control( 'image1', array( 'label' => esc_html__( 'Main image', 'super-widget' ), 'type' => Controls_Manager::MEDIA, 'default' => array( 'url' => SUPER_WIDGET_URL . 'assets/img/is-1.svg' ) ) );
		$this->add_control( 'image2', array( 'label' => esc_html__( 'Second image', 'super-widget' ), 'type' => Controls_Manager::MEDIA, 'default' => array( 'url' => SUPER_WIDGET_URL . 'assets/img/is-2.svg' ), 'condition' => array( 'mode' => 'multi' ) ) );
		$this->add_control( 'image3', array( 'label' => esc_html__( 'Third image', 'super-widget' ), 'type' => Controls_Manager::MEDIA, 'default' => array( 'url' => SUPER_WIDGET_URL . 'assets/img/is-3.svg' ), 'condition' => array( 'mode' => 'multi', 'multi_shape' => 'trio' ) ) );
		$this->add_group_control( Group_Control_Image_Size::get_type(), array( 'name' => 'thumb', 'default' => 'large', 'separator' => 'before' ) );
		$this->add_control( 'link', array( 'label' => esc_html__( 'Link (optional)', 'super-widget' ), 'type' => Controls_Manager::URL, 'dynamic' => array( 'active' => true ), 'placeholder' => 'https://', 'separator' => 'before' ) );
		$this->add_control( 'object_pos', array(
			'label'     => esc_html__( 'Picture focus', 'super-widget' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'center',
			'options'   => array(
				'center'       => esc_html__( 'Center', 'super-widget' ),
				'top'          => esc_html__( 'Top', 'super-widget' ),
				'bottom'       => esc_html__( 'Bottom', 'super-widget' ),
				'left'         => esc_html__( 'Left', 'super-widget' ),
				'right'        => esc_html__( 'Right', 'super-widget' ),
				'center 20%'   => esc_html__( 'Upper third (faces)', 'super-widget' ),
			),
			'selectors' => array( $root => '--is-op: {{VALUE}};' ),
		) );
		$this->end_controls_section();

		/* ------------------------- Content: Extras ------------------------- */
		$auto = array(
			'auto' => esc_html__( 'Automatic (only shapes made for it)', 'super-widget' ),
			'yes'  => esc_html__( 'Show', 'super-widget' ),
			'no'   => esc_html__( 'Hide', 'super-widget' ),
		);
		$this->start_controls_section( 'section_extras', array( 'label' => esc_html__( 'Badges & extras', 'super-widget' ) ) );

		$this->add_control( 'h_badge', array( 'label' => esc_html__( 'Badge card', 'super-widget' ), 'type' => Controls_Manager::HEADING ) );
		$this->add_control( 'show_badge', array( 'label' => esc_html__( 'Show', 'super-widget' ), 'type' => Controls_Manager::SELECT, 'default' => 'auto', 'options' => $auto ) );
		$this->add_control( 'badge_title', array( 'label' => esc_html__( 'Number / title', 'super-widget' ), 'type' => Controls_Manager::TEXT, 'default' => '25+', 'dynamic' => array( 'active' => true ), 'condition' => array( 'show_badge!' => 'no' ) ) );
		$this->add_control( 'badge_text', array( 'label' => esc_html__( 'Text', 'super-widget' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'Years of experience', 'super-widget' ), 'dynamic' => array( 'active' => true ), 'condition' => array( 'show_badge!' => 'no' ) ) );
		$this->add_control( 'badge_icon', array( 'label' => esc_html__( 'Leaf icon (info card shape)', 'super-widget' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes', 'condition' => array( 'show_badge!' => 'no' ) ) );

		$this->add_control( 'h_go', array( 'label' => esc_html__( 'Round arrow button', 'super-widget' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_control( 'show_go', array( 'label' => esc_html__( 'Show', 'super-widget' ), 'type' => Controls_Manager::SELECT, 'default' => 'auto', 'options' => $auto ) );
		$this->add_control( 'go_icon', array( 'label' => esc_html__( 'Icon', 'super-widget' ), 'type' => Controls_Manager::SELECT, 'default' => 'arrow', 'options' => array( 'arrow' => esc_html__( 'Arrow', 'super-widget' ), 'chevron' => esc_html__( 'Chevron', 'super-widget' ), 'play' => esc_html__( 'Play', 'super-widget' ), 'plus' => esc_html__( 'Plus', 'super-widget' ) ), 'condition' => array( 'show_go!' => 'no' ) ) );

		$this->add_control( 'h_seal', array( 'label' => esc_html__( 'Round seal (ring text + play)', 'super-widget' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_control( 'show_seal', array( 'label' => esc_html__( 'Show', 'super-widget' ), 'type' => Controls_Manager::SELECT, 'default' => 'auto', 'options' => $auto ) );
		$this->add_control( 'seal_text', array( 'label' => esc_html__( 'Ring text', 'super-widget' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'Business Consulting', 'super-widget' ), 'condition' => array( 'show_seal!' => 'no' ) ) );

		$this->add_control( 'h_chips', array( 'label' => esc_html__( 'Floating labels', 'super-widget' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_control( 'show_chips', array( 'label' => esc_html__( 'Show', 'super-widget' ), 'type' => Controls_Manager::SELECT, 'default' => 'auto', 'options' => $auto ) );
		$cr = new Repeater();
		$cr->add_control( 'text', array( 'label' => esc_html__( 'Text', 'super-widget' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'Label', 'super-widget' ), 'label_block' => true ) );
		$cr->add_control( 'check', array( 'label' => esc_html__( 'Tick icon', 'super-widget' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$cr->add_control( 'pos_x', array( 'label' => esc_html__( 'Position: from left (%)', 'super-widget' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( '%' ), 'range' => array( '%' => array( 'min' => 0, 'max' => 100 ) ), 'selectors' => array( '{{WRAPPER}} {{CURRENT_ITEM}}' => 'left: {{SIZE}}{{UNIT}};' ) ) );
		$cr->add_control( 'pos_y', array( 'label' => esc_html__( 'Position: from top (%)', 'super-widget' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( '%' ), 'range' => array( '%' => array( 'min' => 0, 'max' => 100 ) ), 'selectors' => array( '{{WRAPPER}} {{CURRENT_ITEM}}' => 'top: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_control( 'chips', array(
			'label'         => esc_html__( 'Labels (add / remove)', 'super-widget' ),
			'type'          => Controls_Manager::REPEATER,
			'fields'        => $cr->get_controls(),
			'default'       => array(
				array( 'text' => esc_html__( 'Insurance Policies', 'super-widget' ), 'check' => 'yes' ),
				array( 'text' => esc_html__( 'Trust Guard Consulting', 'super-widget' ), 'check' => 'yes' ),
				array( 'text' => esc_html__( 'Premium Care', 'super-widget' ), 'check' => 'yes' ),
			),
			'title_field'   => '{{{ text }}}',
			'prevent_empty' => false,
			'condition'     => array( 'show_chips!' => 'no' ),
		) );
		$this->end_controls_section();

		/* ------------------------- Style: Layout ------------------------- */
		$this->start_controls_section( 'style_layout', array( 'label' => esc_html__( 'Size & layout', 'super-widget' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_responsive_control( 'width', array(
			'label'      => esc_html__( 'Width', 'super-widget' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( '%', 'px', 'vw' ),
			'range'      => array( '%' => array( 'min' => 20, 'max' => 100 ), 'px' => array( 'min' => 120, 'max' => 1000 ), 'vw' => array( 'min' => 20, 'max' => 100 ) ),
			'default'    => array( 'size' => 100, 'unit' => '%' ),
			'selectors'  => array( $root => 'width: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'align', array(
			'label'                => esc_html__( 'Alignment', 'super-widget' ),
			'type'                 => Controls_Manager::CHOOSE,
			'options'              => array(
				'left'   => array( 'title' => esc_html__( 'Left', 'super-widget' ), 'icon' => 'eicon-text-align-left' ),
				'center' => array( 'title' => esc_html__( 'Center', 'super-widget' ), 'icon' => 'eicon-text-align-center' ),
				'right'  => array( 'title' => esc_html__( 'Right', 'super-widget' ), 'icon' => 'eicon-text-align-right' ),
			),
			'default'              => 'center',
			'selectors_dictionary' => array(
				'left'   => 'margin-left:0;margin-right:auto;',
				'center' => 'margin-left:auto;margin-right:auto;',
				'right'  => 'margin-left:auto;margin-right:0;',
			),
			'selectors'            => array( $root => '{{VALUE}}' ),
		) );
		$this->add_responsive_control( 'ratio', array(
			'label'     => esc_html__( 'Shape of the box', 'super-widget' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => '',
			'options'   => array(
				''        => esc_html__( 'Default for the shape', 'super-widget' ),
				'1 / 1'   => '1 : 1',
				'4 / 5'   => '4 : 5',
				'3 / 4'   => '3 : 4',
				'2 / 3'   => '2 : 3',
				'5 / 4'   => '5 : 4',
				'4 / 3'   => '4 : 3',
				'3 / 2'   => '3 : 2',
				'16 / 9'  => '16 : 9',
			),
			'selectors' => array( $root => '--is-ar: {{VALUE}};' ),
		) );
		$this->slider( 'off', esc_html__( 'Block / frame distance', 'super-widget' ), $root, '--is-off: {{SIZE}}{{UNIT}};', 0, 20, null, '%' );
		$this->end_controls_section();

		/* ------------------------- Style: Colour block + outline ------------------------- */
		$dec = array(
			'auto' => esc_html__( 'Automatic (only shapes made for it)', 'super-widget' ),
			'yes'  => esc_html__( 'Add / show', 'super-widget' ),
			'no'   => esc_html__( 'Remove / hide', 'super-widget' ),
		);
		$d1  = '{{WRAPPER}} .spw-is .spw-is__d1';
		$d2  = '{{WRAPPER}} .spw-is .spw-is__d2';
		$this->start_controls_section( 'style_decor', array( 'label' => esc_html__( 'Colour block & outline', 'super-widget' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'h_d1', array( 'label' => esc_html__( 'Colour block (behind the picture)', 'super-widget' ), 'type' => Controls_Manager::HEADING ) );
		$this->add_control( 'decor_block', array( 'label' => esc_html__( 'Block', 'super-widget' ), 'type' => Controls_Manager::SELECT, 'default' => 'auto', 'options' => $dec, 'description' => esc_html__( 'Adding a block works on single shapes; collages show it only where it is part of the design.', 'super-widget' ) ) );
		$this->add_group_control( Group_Control_Background::get_type(), array( 'name' => 'd1_bg', 'types' => array( 'classic', 'gradient' ), 'selector' => $d1, 'condition' => array( 'decor_block!' => 'no' ) ) );
		$this->add_group_control( Group_Control_Border::get_type(), array( 'name' => 'd1_border', 'selector' => $d1, 'condition' => array( 'decor_block!' => 'no' ) ) );
		$this->add_responsive_control( 'd1_radius', array( 'label' => esc_html__( 'Corner radius', 'super-widget' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', '%' ), 'selectors' => array( $d1 => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ), 'condition' => array( 'decor_block!' => 'no' ) ) );
		$this->slider( 'd1_rot', esc_html__( 'Rotation', 'super-widget' ), $d1, 'rotate: {{SIZE}}deg;', -30, 30, null, 'px', array( 'decor_block!' => 'no' ), false );
		$this->slider( 'd1_op', esc_html__( 'Opacity', 'super-widget' ), $d1, 'opacity: {{SIZE}};', 0, 1, null, 'px', array( 'decor_block!' => 'no' ), false, 0.05 );

		$this->add_control( 'h_d2', array( 'label' => esc_html__( 'Outline (frame or ring)', 'super-widget' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_control( 'decor_outline', array( 'label' => esc_html__( 'Outline', 'super-widget' ), 'type' => Controls_Manager::SELECT, 'default' => 'auto', 'options' => $dec ) );
		$this->color( 'acc', esc_html__( 'Main colour (blocks, outlines, buttons)', 'super-widget' ), array( $root => '--is-acc: {{VALUE}};' ) );
		$this->color( 'acc2', esc_html__( 'Second colour (backdrop card, oval)', 'super-widget' ), array( $root => '--is-acc2: {{VALUE}};' ) );
		$this->add_group_control( Group_Control_Border::get_type(), array( 'name' => 'd2_border', 'selector' => $d2, 'condition' => array( 'decor_outline!' => 'no' ) ) );
		$this->add_responsive_control( 'd2_radius', array( 'label' => esc_html__( 'Corner radius', 'super-widget' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', '%' ), 'selectors' => array( $d2 => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ), 'condition' => array( 'decor_outline!' => 'no' ) ) );
		$this->slider( 'd2_rot', esc_html__( 'Rotation', 'super-widget' ), $d2, 'rotate: {{SIZE}}deg;', -30, 30, null, 'px', array( 'decor_outline!' => 'no' ), false );
		$this->slider( 'd2_op', esc_html__( 'Opacity', 'super-widget' ), $d2, 'opacity: {{SIZE}};', 0, 1, null, 'px', array( 'decor_outline!' => 'no' ), false, 0.05 );
		$this->slider( 'lw', esc_html__( 'Outline thickness', 'super-widget' ), $root, '--is-lw: {{SIZE}}{{UNIT}};', 1, 10, null, 'px', array( 'decor_outline!' => 'no' ), false );

		$this->add_control( 'h_slash', array( 'label' => esc_html__( 'Diagonal slash (slash shapes)', 'super-widget' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before', 'condition' => array( 'mode' => 'single' ) ) );
		$this->color( 'stripe', esc_html__( 'Slash colour', 'super-widget' ), array( $root => '--is-stripe: {{VALUE}};' ), array( 'mode' => 'single' ) );
		$this->slider( 'sw', esc_html__( 'Slash thickness', 'super-widget' ), $root, '--is-sw: {{SIZE}}{{UNIT}};', 0, 10, null, '%', array( 'mode' => 'single' ), false, 0.1 );
		$this->end_controls_section();

		/* ------------------------- Style: Pictures ------------------------- */
		$this->start_controls_section( 'style_pics', array( 'label' => esc_html__( 'Pictures', 'super-widget' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'h_pbg', array( 'label' => esc_html__( 'Background behind the picture (shows through PNG cut-outs)', 'super-widget' ), 'type' => Controls_Manager::HEADING ) );
		$this->add_group_control( Group_Control_Background::get_type(), array( 'name' => 'pic_bg', 'types' => array( 'classic', 'gradient' ), 'selector' => '{{WRAPPER}} .spw-is .spw-is__img--1 .spw-is__pic' ) );
		$this->add_group_control( Group_Control_Background::get_type(), array( 'name' => 'pic_bg2', 'label' => esc_html__( 'Small pictures background', 'super-widget' ), 'types' => array( 'classic', 'gradient' ), 'selector' => '{{WRAPPER}} .spw-is .spw-is__img--2 .spw-is__pic, {{WRAPPER}} .spw-is .spw-is__img--3 .spw-is__pic', 'condition' => array( 'mode' => 'multi' ) ) );
		$this->add_control( 'h_pborder', array( 'label' => esc_html__( 'Main picture border', 'super-widget' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_group_control( Group_Control_Border::get_type(), array( 'name' => 'pic_border', 'selector' => '{{WRAPPER}} .spw-is .spw-is__img--1 .spw-is__pic' ) );
		$this->color( 'bc', esc_html__( 'Small pictures border colour', 'super-widget' ), array( $root => '--is-bc: {{VALUE}};' ), array( 'mode' => 'multi' ) );
		$this->slider( 'b2', esc_html__( 'Small pictures border width', 'super-widget' ), $root, '--is-b2: {{SIZE}}{{UNIT}};', 0, 20, null, 'px', array( 'mode' => 'multi' ), false );
		$this->add_control( 'h_shadow', array( 'label' => esc_html__( 'Shadow', 'super-widget' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->color( 'shc', esc_html__( 'Shadow colour', 'super-widget' ), array( $root => '--is-shc: {{VALUE}};' ) );
		$this->slider( 'shb', esc_html__( 'Shadow blur', 'super-widget' ), $root, '--is-shb: {{SIZE}}{{UNIT}};', 0, 80, null, 'px', array(), false );
		$this->slider( 'shy', esc_html__( 'Shadow distance (down)', 'super-widget' ), $root, '--is-shy: {{SIZE}}{{UNIT}};', 0, 50, null, 'px', array(), false );
		$this->heading( 'h_filter', esc_html__( 'Picture filters', 'super-widget' ) );
		$this->add_group_control( Group_Control_Css_Filter::get_type(), array( 'name' => 'css_f', 'selector' => '{{WRAPPER}} .spw-is__pic img' ) );
		$this->add_control( 'h_filter_hover', array( 'label' => esc_html__( 'Filters on hover', 'super-widget' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_group_control( Group_Control_Css_Filter::get_type(), array( 'name' => 'css_fh', 'selector' => '{{WRAPPER}} .spw-is:hover .spw-is__pic img' ) );
		$this->end_controls_section();

		/* ------------------------- Style: Badge card ------------------------- */
		$bd = '{{WRAPPER}} .spw-is .spw-is__badge';
		$this->start_controls_section( 'style_badge', array( 'label' => esc_html__( 'Badge card', 'super-widget' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => array( 'show_badge!' => 'no' ) ) );
		$this->color( 'bg_badge', esc_html__( 'Background', 'super-widget' ), array( $root => '--is-bgb: {{VALUE}};' ) );
		$this->color( 'c_badge', esc_html__( 'Text colour', 'super-widget' ), array( $root => '--is-bgt: {{VALUE}};' ) );
		$this->color( 'bi_bg', esc_html__( 'Icon circle colour', 'super-widget' ), array( '{{WRAPPER}} .spw-is .spw-is__bi' => 'background-color: {{VALUE}};' ) );
		$this->color( 'bi_c', esc_html__( 'Icon colour', 'super-widget' ), array( '{{WRAPPER}} .spw-is .spw-is__bi' => 'color: {{VALUE}};' ) );
		$this->slider( 'badge_w', esc_html__( 'Size (width)', 'super-widget' ), $root, '--is-bw: {{SIZE}}{{UNIT}};', 14, 60, null, '%' );
		$this->slider( 'badge_r', esc_html__( 'Corner radius', 'super-widget' ), $root, '--is-br: {{SIZE}}{{UNIT}};', 0, 60, null, 'px', array(), false );
		$this->add_responsive_control( 'badge_pad', array( 'label' => esc_html__( 'Padding', 'super-widget' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', '%' ), 'selectors' => array( $bd => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
		$this->add_group_control( Group_Control_Border::get_type(), array( 'name' => 'badge_border', 'selector' => $bd ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'bt_typo', 'label' => esc_html__( 'Number typography', 'super-widget' ), 'selector' => '{{WRAPPER}} .spw-is__bt' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'bx_typo', 'label' => esc_html__( 'Text typography', 'super-widget' ), 'selector' => '{{WRAPPER}} .spw-is__bx' ) );
		$this->heading( 'h_badge_pos', esc_html__( 'Move the badge (leave empty for the shape default)', 'super-widget' ) );
		$this->slider( 'badge_x', esc_html__( 'Centre: from left', 'super-widget' ), $bd, 'left: {{SIZE}}{{UNIT}}; right: auto; margin-left: 0; --is-bx: -50%;', 0, 100, null, '%' );
		$this->slider( 'badge_y', esc_html__( 'Centre: from top', 'super-widget' ), $bd, 'top: {{SIZE}}{{UNIT}}; bottom: auto; margin-top: 0; --is-by: -50%;', 0, 100, null, '%' );
		$this->end_controls_section();

		/* ------------------------- Style: Arrow button ------------------------- */
		$go = '{{WRAPPER}} .spw-is .spw-is__go';
		$this->start_controls_section( 'style_go', array( 'label' => esc_html__( 'Round arrow button', 'super-widget' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => array( 'show_go!' => 'no' ) ) );
		$this->color( 'go_bg', esc_html__( 'Background', 'super-widget' ), array( $go => 'background-color: {{VALUE}};' ) );
		$this->color( 'go_c', esc_html__( 'Icon colour', 'super-widget' ), array( $go => 'color: {{VALUE}};' ) );
		$this->color( 'go_bc', esc_html__( 'Border colour', 'super-widget' ), array( $go => 'border-color: {{VALUE}};' ) );
		$this->slider( 'go_bw', esc_html__( 'Border width', 'super-widget' ), $go, 'border-width: {{SIZE}}{{UNIT}};', 0, 12, null, 'px', array(), false );
		$this->slider( 'go_size', esc_html__( 'Size', 'super-widget' ), $go, 'width: {{SIZE}}{{UNIT}};', 8, 40, null, '%' );
		$this->slider( 'go_x', esc_html__( 'Centre: from left', 'super-widget' ), $go, 'left: {{SIZE}}{{UNIT}}; right: auto; margin-left: 0; --is-bx: -50%;', 0, 100, null, '%' );
		$this->slider( 'go_y', esc_html__( 'Centre: from top', 'super-widget' ), $go, 'top: {{SIZE}}{{UNIT}}; bottom: auto; margin-top: 0; --is-by: -50%;', 0, 100, null, '%' );
		$this->end_controls_section();

		/* ------------------------- Style: Seal ------------------------- */
		$sl = '{{WRAPPER}} .spw-is .spw-is__seal';
		$this->start_controls_section( 'style_seal', array( 'label' => esc_html__( 'Round seal', 'super-widget' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => array( 'show_seal!' => 'no' ) ) );
		$this->color( 'seal_bg', esc_html__( 'Background', 'super-widget' ), array( $sl => 'background-color: {{VALUE}};' ) );
		$this->color( 'seal_c', esc_html__( 'Ring text colour', 'super-widget' ), array( $sl => 'color: {{VALUE}};' ) );
		$this->color( 'play_bg', esc_html__( 'Play circle colour', 'super-widget' ), array( '{{WRAPPER}} .spw-is .spw-is__play' => 'background-color: {{VALUE}};' ) );
		$this->color( 'play_c', esc_html__( 'Play icon colour', 'super-widget' ), array( '{{WRAPPER}} .spw-is .spw-is__play' => 'color: {{VALUE}};' ) );
		$this->slider( 'seal_size', esc_html__( 'Size', 'super-widget' ), $sl, 'width: {{SIZE}}{{UNIT}};', 10, 50, null, '%' );
		$this->add_control( 'seal_spin', array( 'label' => esc_html__( 'Rotate the ring text', 'super-widget' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->slider( 'seal_x', esc_html__( 'Centre: from left', 'super-widget' ), $sl, 'left: {{SIZE}}{{UNIT}}; right: auto; margin-left: 0; --is-bx: -50%;', 0, 100, null, '%' );
		$this->slider( 'seal_y', esc_html__( 'Centre: from top', 'super-widget' ), $sl, 'top: {{SIZE}}{{UNIT}}; bottom: auto; margin-top: 0; --is-by: -50%;', 0, 100, null, '%' );
		$this->end_controls_section();

		/* ------------------------- Style: Labels ------------------------- */
		$ch = '{{WRAPPER}} .spw-is .spw-is__chip';
		$this->start_controls_section( 'style_chips', array( 'label' => esc_html__( 'Floating labels', 'super-widget' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => array( 'show_chips!' => 'no' ) ) );
		$this->color( 'chip_bg', esc_html__( 'Background', 'super-widget' ), array( $root => '--is-chipbg: {{VALUE}};' ) );
		$this->color( 'chip_c', esc_html__( 'Text colour', 'super-widget' ), array( $root => '--is-chipc: {{VALUE}};' ) );
		$this->color( 'chip_tick', esc_html__( 'Tick background', 'super-widget' ), array( $ch . '.has-check::before' => 'background-color: {{VALUE}};' ) );
		$this->color( 'chip_tickc', esc_html__( 'Tick colour', 'super-widget' ), array( $ch . '.has-check::before' => 'color: {{VALUE}};' ) );
		$this->slider( 'chip_r', esc_html__( 'Corner radius', 'super-widget' ), $ch, 'border-radius: {{SIZE}}{{UNIT}};', 0, 40, null, 'px', array(), false );
		$this->add_responsive_control( 'chip_pad', array( 'label' => esc_html__( 'Padding', 'super-widget' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px' ), 'selectors' => array( $ch => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
		$this->add_group_control( Group_Control_Border::get_type(), array( 'name' => 'chip_border', 'selector' => $ch ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'chip_typo', 'selector' => '{{WRAPPER}} .spw-is__chip' ) );
		$this->end_controls_section();

		/* ------------------------- Style: Motion ------------------------- */
		$this->start_controls_section( 'style_motion', array( 'label' => esc_html__( 'Hover & motion', 'super-widget' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'hover', array( 'label' => esc_html__( 'Hover effect', 'super-widget' ), 'type' => Controls_Manager::SELECT, 'default' => 'zoom', 'options' => $this->hovers() ) );
		$this->add_control( 'shine', array( 'label' => esc_html__( 'Shine sweep on hover', 'super-widget' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => '' ) );
		$this->slider( 'hz', esc_html__( 'Zoom amount', 'super-widget' ), $root, '--is-hz: {{SIZE}};', 1.02, 1.4, null, 'px', array( 'hover' => 'zoom' ), false, 0.01 );
		$this->slider( 'hd', esc_html__( 'Hover speed (ms)', 'super-widget' ), $root, '--is-hd: {{SIZE}}ms;', 100, 1500, null, 'px', array(), false, 50 );
		$this->heading( 'h_entr', esc_html__( 'Entrance', 'super-widget' ) );
		$this->add_control( 'entrance', array( 'label' => esc_html__( 'Animation', 'super-widget' ), 'type' => Controls_Manager::SELECT, 'default' => 'fade-up', 'options' => $this->entrances() ) );
		$this->slider( 'ad', esc_html__( 'Duration (ms)', 'super-widget' ), $root, '--is-ad: {{SIZE}}ms;', 200, 2000, null, 'px', array( 'entrance!' => 'none' ), false, 50 );
		$this->slider( 'adl', esc_html__( 'Delay (ms)', 'super-widget' ), $root, '--is-adl: {{SIZE}}ms;', 0, 1500, null, 'px', array( 'entrance!' => 'none' ), false, 50 );
		$this->heading( 'h_float', esc_html__( 'Floating', 'super-widget' ) );
		$this->add_control( 'float', array( 'label' => esc_html__( 'Float small pictures + badge', 'super-widget' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->slider( 'fd', esc_html__( 'Float distance', 'super-widget' ), $root, '--is-fd: {{SIZE}}{{UNIT}};', 2, 30, null, 'px', array( 'float' => 'yes' ), false );
		$this->slider( 'fs', esc_html__( 'Float speed (s per cycle)', 'super-widget' ), $root, '--is-fs: {{SIZE}}s;', 2, 12, null, 'px', array( 'float' => 'yes' ), false );
		$this->end_controls_section();
	}

	/* ==================================================================
	 * Render
	 * ================================================================== */

	/** One picture slot. $link is an array(href, attrs) or empty. */
	private function pic( $s, $n, $link ) {
		$key = 'image' . $n;
		if ( empty( $s[ $key ] ) || ( empty( $s[ $key ]['id'] ) && empty( $s[ $key ]['url'] ) ) ) {
			return '';
		}
		$img = Group_Control_Image_Size::get_attachment_image_html( array_merge( $s, array( 'thumb_image' => $s[ $key ] ) ), 'thumb', 'thumb_image' );
		if ( ! $img ) {
			return '';
		}
		$cls = 'spw-is__img spw-is__img--' . (int) $n;
		if ( $link ) {
			$inner = '<a class="spw-is__pic" href="' . esc_url( $link['href'] ) . '"' . $link['attrs'] . '>' . $img . '</a>';
		} else {
			$inner = '<div class="spw-is__pic">' . $img . '</div>';
		}
		return '<div class="' . esc_attr( $cls ) . '">' . $inner . '</div>';
	}

	protected function render() {
		$s      = $this->get_settings_for_display();
		$shapes = $this->shapes();

		$mode  = isset( $s['mode'] ) && 'multi' === $s['mode'] ? 'multi' : 'single';
		$skey  = 'multi' === $mode ? 'multi_shape' : 'single_shape';
		$shape = isset( $s[ $skey ] ) && isset( $shapes[ $s[ $skey ] ] ) && $shapes[ $s[ $skey ] ][1] === $mode ? $s[ $skey ] : ( 'multi' === $mode ? 'card-circle' : 'arch' );
		$def   = $shapes[ $shape ];
		$count = (int) $def[2];
		$extra = (string) $def[3];

		$entrance = isset( $s['entrance'] ) && isset( $this->entrances()[ $s['entrance'] ] ) ? $s['entrance'] : 'fade-up';
		$hover    = isset( $s['hover'] ) && isset( $this->hovers()[ $s['hover'] ] ) ? $s['hover'] : 'zoom';
		$shine    = isset( $s['shine'] ) && 'yes' === $s['shine'];
		$float    = ! isset( $s['float'] ) || 'yes' === $s['float'];
		if ( Super_Widget_Settings::get( 'disable_animations' ) ) {
			$entrance = 'none';
			$hover    = 'none';
			$shine    = false;
			$float    = false;
		}

		// Extras: each one is Automatic (only shapes made for it), Show or Hide.
		$pick = function ( $key, $flag ) use ( $s, $extra ) {
			$v = isset( $s[ $key ] ) ? $s[ $key ] : 'auto';
			return 'yes' === $v || ( 'auto' === $v && false !== strpos( $extra, $flag ) );
		};
		$title = isset( $s['badge_title'] ) ? (string) $s['badge_title'] : '';
		$btext = isset( $s['badge_text'] ) ? (string) $s['badge_text'] : '';
		$badge = $pick( 'show_badge', 'b' ) && ( '' !== $title || '' !== $btext );
		$go    = $pick( 'show_go', 'g' );
		$seal  = $pick( 'show_seal', 'w' );
		$chips = array();
		if ( $pick( 'show_chips', 'c' ) && ! empty( $s['chips'] ) && is_array( $s['chips'] ) ) {
			foreach ( array_slice( $s['chips'], 0, 8 ) as $c ) {
				if ( isset( $c['text'] ) && '' !== trim( (string) $c['text'] ) ) {
					$chips[] = $c;
				}
			}
		}

		// Colour block (d1) and outline (d2): own to the shape, added on single shapes, or removed.
		$own1 = in_array( $shape, array( 'capsule', 'offset-solid', 'offset-corner', 'tilt-backdrop', 'offset-pair' ), true );
		$own2 = in_array( $shape, array( 'capsule', 'ring', 'offset-outline', 'circle-duo' ), true );
		$d1s  = isset( $s['decor_block'] ) ? $s['decor_block'] : 'auto';
		$d2s  = isset( $s['decor_outline'] ) ? $s['decor_outline'] : 'auto';

		// Link: on the arrow button when the shape has one, otherwise on the main picture.
		$link = array();
		if ( ! empty( $s['link']['url'] ) ) {
			$attrs = '';
			if ( ! empty( $s['link']['is_external'] ) ) {
				$attrs .= ' target="_blank"';
			}
			$rel = array();
			if ( ! empty( $s['link']['is_external'] ) ) {
				$rel[] = 'noopener';
			}
			if ( ! empty( $s['link']['nofollow'] ) ) {
				$rel[] = 'nofollow';
			}
			if ( $rel ) {
				$attrs .= ' rel="' . esc_attr( implode( ' ', $rel ) ) . '"';
			}
			$link = array( 'href' => $s['link']['url'], 'attrs' => $attrs );
		}

		$classes = array( 'spw-is', 'spw-is--' . $shape, 'spw-is--' . $mode, 'spw-is--anim-' . $entrance, 'spw-is--hv-' . $hover );
		if ( $shine ) {
			$classes[] = 'spw-is--shine';
		}
		if ( $float ) {
			$classes[] = 'spw-is--float';
		}
		if ( $badge ) {
			$classes[] = 'spw-is--hasbadge';
		}
		if ( $go ) {
			$classes[] = 'spw-is--hasgo';
		}
		if ( $seal ) {
			$classes[] = 'spw-is--hasseal';
		}
		if ( $chips ) {
			$classes[] = 'spw-is--haschips';
		}
		if ( 'no' === $d1s ) {
			$classes[] = 'spw-is--no-d1';
		} elseif ( 'yes' === $d1s && ! $own1 && 'single' === $mode ) {
			$classes[] = 'spw-is--gd1';
		}
		if ( 'no' === $d2s ) {
			$classes[] = 'spw-is--no-d2';
		} elseif ( 'yes' === $d2s && ! $own2 && 'single' === $mode ) {
			$classes[] = 'spw-is--gd2';
		}
		if ( isset( $s['seal_spin'] ) && '' === $s['seal_spin'] ) {
			$classes[] = 'spw-is--nospin';
		}

		echo '<div class="' . esc_attr( implode( ' ', array_map( 'sanitize_html_class', $classes ) ) ) . '">';
		echo '<div class="spw-is__stage">';
		echo '<span class="spw-is__d1" aria-hidden="true"></span><span class="spw-is__d2" aria-hidden="true"></span>';

		for ( $n = 1; $n <= $count; $n++ ) {
			// Link sits on the first picture unless the arrow button carries it.
			echo $this->pic( $s, $n, ( 1 === $n && ! $go ) ? $link : array() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		if ( $badge ) {
			$bicon = ! isset( $s['badge_icon'] ) || 'yes' === $s['badge_icon'];
			echo '<div class="spw-is__badge' . ( $bicon ? ' has-icon' : '' ) . '"><span class="spw-is__bi" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18"><path d="M5 19c0-8 5-14 14-14 0 9-6 14-14 14zm0 0l7-7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>';
			if ( '' !== $title ) {
				echo '<span class="spw-is__bt">' . esc_html( $title ) . '</span>';
			}
			if ( '' !== $btext ) {
				echo '<span class="spw-is__bx">' . esc_html( $btext ) . '</span>';
			}
			echo '</div>';
		}

		if ( $go ) {
			$paths = array(
				'arrow'   => 'M7 17L17 7M9 7h8v8',
				'chevron' => 'M9 5l7 7-7 7',
				'plus'    => 'M12 5v14M5 12h14',
			);
			$gi    = isset( $s['go_icon'] ) ? $s['go_icon'] : 'arrow';
			if ( 'play' === $gi ) {
				$arrow = '<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path d="M9 6.5v11l9-5.5z" fill="currentColor"/></svg>';
			} else {
				$arrow = '<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path d="' . esc_attr( isset( $paths[ $gi ] ) ? $paths[ $gi ] : $paths['arrow'] ) . '" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
			}
			if ( $link ) {
				echo '<a class="spw-is__go" href="' . esc_url( $link['href'] ) . '"' . $link['attrs'] . ' aria-label="' . esc_attr__( 'Open link', 'super-widget' ) . '">' . $arrow . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				echo '<span class="spw-is__go" aria-hidden="true">' . $arrow . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}

		if ( $seal ) {
			$txt = isset( $s['seal_text'] ) ? trim( (string) $s['seal_text'] ) : '';
			$pid = 'spw-is-p-' . sanitize_html_class( (string) $this->get_id() );
			echo '<div class="spw-is__seal" aria-hidden="true"><svg viewBox="0 0 100 100" class="spw-is__sealring"><defs><path id="' . esc_attr( $pid ) . '" d="M50 50 m-38 0 a38 38 0 1 1 76 0 a38 38 0 1 1 -76 0"/></defs>';
			if ( '' !== $txt ) {
				echo '<text><textPath href="#' . esc_attr( $pid ) . '" startOffset="0" textLength="236" lengthAdjust="spacing">' . esc_html( $txt . ' · ' ) . '</textPath></text>';
			}
			echo '</svg><span class="spw-is__play"><svg viewBox="0 0 24 24" width="20" height="20"><path d="M9 6.5v11l9-5.5z" fill="currentColor"/></svg></span></div>';
		}

		foreach ( $chips as $i => $c ) {
			$cls = 'spw-is__chip spw-is__chip--' . ( (int) $i + 1 );
			if ( ! isset( $c['check'] ) || 'yes' === $c['check'] ) {
				$cls .= ' has-check';
			}
			if ( ! empty( $c['_id'] ) ) {
				$cls .= ' elementor-repeater-item-' . sanitize_html_class( $c['_id'] );
			}
			echo '<span class="' . esc_attr( $cls ) . '">' . esc_html( (string) $c['text'] ) . '</span>';
		}

		echo '</div></div>';
	}
}
