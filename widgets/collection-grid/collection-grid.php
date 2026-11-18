<?php
/**
 * Collection Grid widget.
 *
 * Category / collection cards: a colored tile, a label and a (cut-out) picture.
 * Layouts: "Collage" (the bento layout from the reference), a normal grid, or
 * a slider. Everything is native Elementor controls.
 *
 * @package SPW_Addons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Css_Filter;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;

class SPW_Collection_Grid_Widget extends Widget_Base {

	public function get_name() {
		return 'spw-collection-grid';
	}

	public function get_title() {
		return esc_html( Super_Widget_Settings::widget_title( 'collection-grid', __( 'Collection Grid', 'super-widget' ) ) );
	}

	public function get_icon() {
		return 'eicon-gallery-grid';
	}

	public function get_categories() {
		return array( 'super-widget' );
	}

	public function get_keywords() {
		return array( 'collection', 'category', 'grid', 'slider', 'bento', 'cards', 'shop' );
	}

	public function get_style_depends() {
		return array( 'spw-global', 'spw-collection-grid' );
	}

	public function get_script_depends() {
		return array( 'spw-collection-grid' );
	}

	private function positions() {
		return array(
			'tl' => esc_html__( 'Top left', 'super-widget' ),
			'tr' => esc_html__( 'Top right', 'super-widget' ),
			'bl' => esc_html__( 'Bottom left', 'super-widget' ),
			'br' => esc_html__( 'Bottom right', 'super-widget' ),
			'tc' => esc_html__( 'Top center', 'super-widget' ),
			'bc' => esc_html__( 'Bottom center', 'super-widget' ),
		);
	}

	private function collage_styles() {
		return array(
			'classic'  => esc_html__( 'Classic: tall left (the reference)', 'super-widget' ),
			'mirror'   => esc_html__( 'Mirror: tall right', 'super-widget' ),
			'sides'    => esc_html__( 'Sides: two tall + wide centre', 'super-widget' ),
			'half'     => esc_html__( 'Half: one big + four small', 'super-widget' ),
			'banner'   => esc_html__( 'Banner: wide top + three', 'super-widget' ),
			'stair'    => esc_html__( 'Stair: 8 + 4, then 4 + 8', 'super-widget' ),
			'magazine' => esc_html__( 'Magazine: two + three', 'super-widget' ),
		);
	}

	private function img_positions() {
		return array(
			'center bottom' => esc_html__( 'Bottom', 'super-widget' ),
			'center center' => esc_html__( 'Center', 'super-widget' ),
			'center top'    => esc_html__( 'Top', 'super-widget' ),
			'left bottom'   => esc_html__( 'Bottom left', 'super-widget' ),
			'right bottom'  => esc_html__( 'Bottom right', 'super-widget' ),
			'left center'   => esc_html__( 'Left', 'super-widget' ),
			'right center'  => esc_html__( 'Right', 'super-widget' ),
		);
	}

	protected function register_controls() {

		/* ------------------------- Items ------------------------- */
		$this->start_controls_section( 'section_items', array( 'label' => esc_html__( 'Cards', 'super-widget' ) ) );

		$r = new Repeater();
		$cur = '{{WRAPPER}} {{CURRENT_ITEM}}';
		$r->add_control( 'title', array( 'label' => esc_html__( 'Label', 'super-widget' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'Category', 'super-widget' ), 'label_block' => true ) );
		$r->add_control( 'subtitle', array( 'label' => esc_html__( 'Small text under the label', 'super-widget' ), 'type' => Controls_Manager::TEXT, 'label_block' => true ) );
		$r->add_control( 'badge', array( 'label' => esc_html__( 'Badge (e.g. New, -20%)', 'super-widget' ), 'type' => Controls_Manager::TEXT, 'label_block' => true ) );
		$r->add_control( 'image', array( 'label' => esc_html__( 'Picture (PNG cut-out works best)', 'super-widget' ), 'type' => Controls_Manager::MEDIA, 'default' => array( 'url' => '' ) ) );
		$r->add_control( 'link', array( 'label' => esc_html__( 'Link', 'super-widget' ), 'type' => Controls_Manager::URL, 'options' => array( 'url', 'is_external', 'nofollow' ), 'default' => array( 'url' => '' ) ) );

		$r->add_control( 'h_card', array( 'label' => esc_html__( 'Card', 'super-widget' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$r->add_control( 'bg', array( 'label' => esc_html__( 'Card color', 'super-widget' ), 'type' => Controls_Manager::COLOR, 'selectors' => array( $cur => '--cg-bg: {{VALUE}};' ) ) );
		$r->add_control( 'bg2', array( 'label' => esc_html__( 'Second color (makes a gradient)', 'super-widget' ), 'type' => Controls_Manager::COLOR, 'selectors' => array( $cur => '--cg-bg2: {{VALUE}};' ) ) );
		$r->add_control( 'fg', array( 'label' => esc_html__( 'Label color', 'super-widget' ), 'type' => Controls_Manager::COLOR, 'selectors' => array( $cur => '--cg-fg: {{VALUE}};' ) ) );
		$r->add_control( 'label_pos', array( 'label' => esc_html__( 'Label position', 'super-widget' ), 'type' => Controls_Manager::SELECT, 'default' => 'default', 'options' => array( 'default' => esc_html__( 'Same as widget', 'super-widget' ) ) + $this->positions() ) );

		$r->add_control( 'h_pic', array( 'label' => esc_html__( 'Picture', 'super-widget' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$r->add_control( 'img_pos', array( 'label' => esc_html__( 'Picture position', 'super-widget' ), 'type' => Controls_Manager::SELECT, 'default' => 'center bottom', 'options' => $this->img_positions(), 'selectors' => array( $cur . ' .spw-cg__img' => 'object-position: {{VALUE}}; transform-origin: {{VALUE}};' ) ) );
		$r->add_control( 'img_scale', array( 'label' => esc_html__( 'Picture size (%)', 'super-widget' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( '%' ), 'range' => array( '%' => array( 'min' => 20, 'max' => 220 ) ), 'default' => array( 'size' => 100, 'unit' => '%' ), 'selectors' => array( $cur => '--cg-scale: calc({{SIZE}} / 100);' ) ) );
		$r->add_control( 'img_x', array( 'label' => esc_html__( 'Move left / right (%)', 'super-widget' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( '%' ), 'range' => array( '%' => array( 'min' => -60, 'max' => 60 ) ), 'default' => array( 'size' => 0, 'unit' => '%' ), 'selectors' => array( $cur => '--cg-x: {{SIZE}}%;' ) ) );
		$r->add_control( 'img_y', array( 'label' => esc_html__( 'Move up / down (%)', 'super-widget' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( '%' ), 'range' => array( '%' => array( 'min' => -60, 'max' => 60 ) ), 'default' => array( 'size' => 0, 'unit' => '%' ), 'selectors' => array( $cur => '--cg-y: {{SIZE}}%;' ) ) );
		$r->add_control( 'img_rot', array( 'label' => esc_html__( 'Rotate (deg)', 'super-widget' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'deg' ), 'range' => array( 'deg' => array( 'min' => -45, 'max' => 45 ) ), 'default' => array( 'size' => 0, 'unit' => 'deg' ), 'selectors' => array( $cur => '--cg-rot: {{SIZE}}deg;' ) ) );
		$r->add_control( 'img_flip', array( 'label' => esc_html__( 'Flip picture', 'super-widget' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => '-1', 'selectors' => array( $cur => '--cg-flip: {{VALUE}};' ) ) );
		$r->add_control( 'img_fit_i', array( 'label' => esc_html__( 'Picture fit', 'super-widget' ), 'type' => Controls_Manager::SELECT, 'default' => '', 'options' => array( '' => esc_html__( 'Same as widget', 'super-widget' ), 'contain' => esc_html__( 'Whole picture', 'super-widget' ), 'cover' => esc_html__( 'Fill the card', 'super-widget' ) ), 'selectors' => array( $cur . ' .spw-cg__img' => 'object-fit: {{VALUE}};' ) ) );

		$r->add_control( 'h_size', array( 'label' => esc_html__( 'Size of this card (Collage / Grid)', 'super-widget' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$r->add_control( 'col_span', array( 'label' => esc_html__( 'Width (of 12 columns)', 'super-widget' ), 'type' => Controls_Manager::SELECT, 'default' => '', 'options' => array( '' => esc_html__( 'Automatic', 'super-widget' ), '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6', '7' => '7', '8' => '8', '9' => '9', '10' => '10', '12' => '12 (full)' ), 'selectors' => array( $cur => '--cs: {{VALUE}};' ) ) );
		$r->add_control( 'row_span', array( 'label' => esc_html__( 'Height (rows)', 'super-widget' ), 'type' => Controls_Manager::SELECT, 'default' => '', 'options' => array( '' => esc_html__( 'Automatic', 'super-widget' ), '1' => '1', '2' => '2', '3' => '3' ), 'selectors' => array( $cur => '--rs: {{VALUE}};' ) ) );
		$r->add_control( 'm_full', array( 'label' => esc_html__( 'Full width on mobile', 'super-widget' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => '' ) );

		$this->add_control(
			'items',
			array(
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $r->get_controls(),
				'title_field' => '{{{ title }}}',
				'default'     => array(
					array( 'title' => esc_html__( 'For dogs', 'super-widget' ), 'm_full' => 'yes', 'image' => array( 'url' => SUPER_WIDGET_URL . 'assets/img/cg-dog.svg', 'id' => '' ), 'bg' => '#c9a98c', 'label_pos' => 'tr', 'img_pos' => 'center bottom' ),
					array( 'title' => esc_html__( 'For birds', 'super-widget' ), 'image' => array( 'url' => SUPER_WIDGET_URL . 'assets/img/cg-bird.svg', 'id' => '' ), 'bg' => '#ee7a2f', 'label_pos' => 'br', 'img_pos' => 'left center' ),
					array( 'title' => esc_html__( 'For cats', 'super-widget' ), 'image' => array( 'url' => SUPER_WIDGET_URL . 'assets/img/cg-cat.svg', 'id' => '' ), 'bg' => '#d9bfa5', 'label_pos' => 'tl', 'img_pos' => 'right center' ),
					array( 'title' => esc_html__( 'For fish', 'super-widget' ), 'image' => array( 'url' => SUPER_WIDGET_URL . 'assets/img/cg-fish.svg', 'id' => '' ), 'bg' => '#0d3b57', 'label_pos' => 'tl', 'img_pos' => 'center bottom' ),
					array( 'title' => esc_html__( 'For rodents', 'super-widget' ), 'image' => array( 'url' => SUPER_WIDGET_URL . 'assets/img/cg-rabbit.svg', 'id' => '' ), 'bg' => '#ee7a2f', 'label_pos' => 'br', 'img_pos' => 'left bottom' ),
				),
			)
		);

		$this->add_group_control( Group_Control_Image_Size::get_type(), array( 'name' => 'thumb', 'default' => 'large', 'separator' => 'before' ) );
		$this->add_control( 'title_tag', array( 'label' => esc_html__( 'Label tag', 'super-widget' ), 'type' => Controls_Manager::SELECT, 'default' => 'h3', 'options' => array( 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'h5' => 'H5', 'p' => 'p', 'span' => 'span' ) ) );
		$this->end_controls_section();

		/* ------------------------- Layout ------------------------- */
		$this->start_controls_section( 'section_layout', array( 'label' => esc_html__( 'Layout', 'super-widget' ) ) );
		$this->add_control(
			'layout',
			array(
				'label'   => esc_html__( 'Layout', 'super-widget' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'collage',
				'options' => array(
					'collage' => esc_html__( 'Collage (like the reference)', 'super-widget' ),
					'grid'    => esc_html__( 'Normal grid', 'super-widget' ),
					'slider'  => esc_html__( 'Slider', 'super-widget' ),
				),
			)
		);
		$this->add_control(
			'collage_style',
			array(
				'label'     => esc_html__( 'Collage style', 'super-widget' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'classic',
				'options'   => $this->collage_styles(),
				'condition' => array( 'layout' => 'collage' ),
			)
		);
		$this->add_responsive_control(
			'cols',
			array(
				'label'          => esc_html__( 'Columns', 'super-widget' ),
				'type'           => Controls_Manager::SELECT,
				'default'        => '4',
				'tablet_default' => '3',
				'mobile_default' => '2',
				'options'        => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6' ),
				'condition'      => array( 'layout' => array( 'grid', 'slider' ) ),
				'selectors'      => array( '{{WRAPPER}} .spw-cg' => '--cg-cols: {{VALUE}};' ),
			)
		);
		$this->add_control( 'show_arrows', array( 'label' => esc_html__( 'Arrows', 'super-widget' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes', 'condition' => array( 'layout' => 'slider' ) ) );
		$this->add_control( 'show_dots', array( 'label' => esc_html__( 'Dots', 'super-widget' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes', 'condition' => array( 'layout' => 'slider' ) ) );
		$this->add_control( 'autoplay', array( 'label' => esc_html__( 'Autoplay', 'super-widget' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => '', 'condition' => array( 'layout' => 'slider' ) ) );
		$this->add_control( 'autoplay_ms', array( 'label' => esc_html__( 'Autoplay delay (ms)', 'super-widget' ), 'type' => Controls_Manager::NUMBER, 'default' => 4000, 'min' => 1500, 'max' => 15000, 'condition' => array( 'layout' => 'slider', 'autoplay' => 'yes' ) ) );
		$this->add_control(
			'label_pos',
			array(
				'label'     => esc_html__( 'Label position', 'super-widget' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'tl',
				'options'   => $this->positions(),
				'separator' => 'before',
			)
		);
		$this->add_control(
			'img_fit',
			array(
				'label'     => esc_html__( 'Picture fit', 'super-widget' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'contain',
				'options'   => array(
					'contain' => esc_html__( 'Whole picture (cut-outs)', 'super-widget' ),
					'cover'   => esc_html__( 'Fill the card (photos)', 'super-widget' ),
				),
				'selectors' => array( '{{WRAPPER}} .spw-cg__img' => 'object-fit: {{VALUE}};' ),
			)
		);
		$this->end_controls_section();

		/* ------------------------- Animation ------------------------- */
		$this->start_controls_section( 'section_anim', array( 'label' => esc_html__( 'Animation', 'super-widget' ) ) );
		$this->add_control( 'entrance', array( 'label' => esc_html__( 'Entrance', 'super-widget' ), 'type' => Controls_Manager::SELECT, 'default' => 'fade-up', 'options' => $this->entrances() ) );
		$this->add_control( 'anim_dur', array( 'label' => esc_html__( 'Entrance speed (ms)', 'super-widget' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 200, 'max' => 1800, 'step' => 50 ) ), 'default' => array( 'size' => 700, 'unit' => 'px' ), 'condition' => array( 'entrance!' => 'none' ), 'selectors' => array( '{{WRAPPER}} .spw-cg' => '--cg-dur: {{SIZE}}ms;' ) ) );
		$this->add_control( 'anim_stagger', array( 'label' => esc_html__( 'Delay between cards (ms)', 'super-widget' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 400, 'step' => 10 ) ), 'default' => array( 'size' => 70, 'unit' => 'px' ), 'condition' => array( 'entrance!' => 'none' ), 'selectors' => array( '{{WRAPPER}} .spw-cg' => '--cg-stagger: {{SIZE}}ms;' ) ) );
		$this->add_control( 'hover', array( 'label' => esc_html__( 'Hover', 'super-widget' ), 'type' => Controls_Manager::SELECT, 'default' => 'both', 'separator' => 'before', 'options' => array( 'none' => esc_html__( 'None', 'super-widget' ), 'lift' => esc_html__( 'Lift card', 'super-widget' ), 'zoom' => esc_html__( 'Zoom picture', 'super-widget' ), 'both' => esc_html__( 'Lift + zoom', 'super-widget' ) ) ) );
		$this->add_control( 'hover_zoom', array( 'label' => esc_html__( 'Picture zoom on hover', 'super-widget' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 100, 'max' => 130 ) ), 'default' => array( 'size' => 107, 'unit' => 'px' ), 'condition' => array( 'hover' => array( 'zoom', 'both' ) ), 'selectors' => array( '{{WRAPPER}} .spw-cg' => '--cg-zoom: calc({{SIZE}} / 100);' ) ) );
		$this->add_control( 'hover_lift', array( 'label' => esc_html__( 'Lift distance (px)', 'super-widget' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 20 ) ), 'default' => array( 'size' => 4, 'unit' => 'px' ), 'condition' => array( 'hover' => array( 'lift', 'both' ) ), 'selectors' => array( '{{WRAPPER}} .spw-cg' => '--cg-lift: -{{SIZE}}px;' ) ) );
		$this->add_control( 'shine', array( 'label' => esc_html__( 'Shine sweep on hover', 'super-widget' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => '' ) );
		$this->add_control( 'hover_ms', array( 'label' => esc_html__( 'Hover speed (ms)', 'super-widget' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 100, 'max' => 1000, 'step' => 50 ) ), 'default' => array( 'size' => 400, 'unit' => 'px' ), 'selectors' => array( '{{WRAPPER}} .spw-cg' => '--cg-hov: {{SIZE}}ms;' ) ) );
		$this->end_controls_section();

		/* ------------------------- Style: Cards ------------------------- */
		$card = '{{WRAPPER}} .spw-cg .spw-cg__card';
		$this->start_controls_section( 'style_card', array( 'label' => esc_html__( 'Cards', 'super-widget' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_responsive_control( 'gap', array( 'label' => esc_html__( 'Gap', 'super-widget' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 60 ) ), 'default' => array( 'size' => 12, 'unit' => 'px' ), 'selectors' => array( '{{WRAPPER}} .spw-cg' => '--cg-gap: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'row_h', array( 'label' => esc_html__( 'Collage row height', 'super-widget' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 80, 'max' => 400 ) ), 'default' => array( 'size' => 150, 'unit' => 'px' ), 'tablet_default' => array( 'size' => 130, 'unit' => 'px' ), 'mobile_default' => array( 'size' => 140, 'unit' => 'px' ), 'condition' => array( 'layout' => 'collage' ), 'selectors' => array( '{{WRAPPER}} .spw-cg' => '--cg-row: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'card_h', array( 'label' => esc_html__( 'Card height', 'super-widget' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 120, 'max' => 600 ) ), 'default' => array( 'size' => 260, 'unit' => 'px' ), 'tablet_default' => array( 'size' => 230, 'unit' => 'px' ), 'mobile_default' => array( 'size' => 200, 'unit' => 'px' ), 'condition' => array( 'layout' => array( 'grid', 'slider' ) ), 'selectors' => array( '{{WRAPPER}} .spw-cg' => '--cg-h: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_control( 'bg_all', array( 'label' => esc_html__( 'Default card color', 'super-widget' ), 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .spw-cg' => '--cg-bg: {{VALUE}};' ) ) );
		$this->add_control( 'bg_angle', array( 'label' => esc_html__( 'Gradient angle', 'super-widget' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'deg' ), 'range' => array( 'deg' => array( 'min' => 0, 'max' => 360 ) ), 'default' => array( 'size' => 135, 'unit' => 'deg' ), 'description' => esc_html__( 'Used by cards that have a second color.', 'super-widget' ), 'selectors' => array( '{{WRAPPER}} .spw-cg' => '--cg-angle: {{SIZE}}deg;' ) ) );
		$this->add_responsive_control( 'radius', array( 'label' => esc_html__( 'Corner radius', 'super-widget' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 80 ) ), 'default' => array( 'size' => 4, 'unit' => 'px' ), 'selectors' => array( $card => 'border-radius: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'img_pad', array( 'label' => esc_html__( 'Picture spacing', 'super-widget' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 60 ) ), 'default' => array( 'size' => 0, 'unit' => 'px' ), 'selectors' => array( '{{WRAPPER}} .spw-cg' => '--cg-pad: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_control( 'scrim', array( 'label' => esc_html__( 'Dark fade behind the label (%)', 'super-widget' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( '%' ), 'range' => array( '%' => array( 'min' => 0, 'max' => 90 ) ), 'default' => array( 'size' => 0, 'unit' => '%' ), 'description' => esc_html__( 'Helps white text stay readable on photos.', 'super-widget' ), 'selectors' => array( '{{WRAPPER}} .spw-cg' => '--cg-scrim: calc({{SIZE}} / 100);' ) ) );
		$this->add_group_control( Group_Control_Border::get_type(), array( 'name' => 'card_border', 'selector' => $card ) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array( 'name' => 'card_shadow', 'selector' => $card ) );
		$this->add_group_control( Group_Control_Css_Filter::get_type(), array( 'name' => 'img_filter', 'label' => esc_html__( 'Picture filter', 'super-widget' ), 'selector' => '{{WRAPPER}} .spw-cg .spw-cg__img' ) );
		$this->add_control( 'hover_head', array( 'label' => esc_html__( 'On hover', 'super-widget' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array( 'name' => 'card_shadow_h', 'label' => esc_html__( 'Hover shadow', 'super-widget' ), 'selector' => $card . ':hover, ' . $card . ':focus-visible' ) );
		$this->add_group_control( Group_Control_Css_Filter::get_type(), array( 'name' => 'img_filter_h', 'label' => esc_html__( 'Picture filter on hover', 'super-widget' ), 'selector' => $card . ':hover .spw-cg__img, ' . $card . ':focus-visible .spw-cg__img' ) );
		$this->add_control( 'hover_overlay', array( 'label' => esc_html__( 'Hover tint', 'super-widget' ), 'type' => Controls_Manager::COLOR, 'selectors' => array( $card . '::after' => 'background: {{VALUE}};' ) ) );
		$this->add_control( 'hover_label', array( 'label' => esc_html__( 'Hover label color', 'super-widget' ), 'type' => Controls_Manager::COLOR, 'selectors' => array( $card . ':hover .spw-cg__title, ' . $card . ':focus-visible .spw-cg__title' => 'color: {{VALUE}};' ) ) );
		$this->end_controls_section();

		/* ------------------------- Style: Label ------------------------- */
		$lab = '{{WRAPPER}} .spw-cg .spw-cg__label';
		$this->start_controls_section( 'style_label', array( 'label' => esc_html__( 'Label', 'super-widget' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'title_typo', 'selector' => '{{WRAPPER}} .spw-cg .spw-cg__title' ) );
		$this->add_control( 'title_color', array( 'label' => esc_html__( 'Color', 'super-widget' ), 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .spw-cg' => '--cg-fg: {{VALUE}};' ) ) );
		$this->add_responsive_control( 'label_off', array( 'label' => esc_html__( 'Distance from the edge', 'super-widget' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 60 ) ), 'default' => array( 'size' => 16, 'unit' => 'px' ), 'selectors' => array( '{{WRAPPER}} .spw-cg' => '--cg-off: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_control( 'label_bg', array( 'label' => esc_html__( 'Label background', 'super-widget' ), 'type' => Controls_Manager::COLOR, 'separator' => 'before', 'selectors' => array( $lab => 'background-color: {{VALUE}};' ) ) );
		$this->add_responsive_control( 'label_pad', array( 'label' => esc_html__( 'Label padding', 'super-widget' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', 'em' ), 'selectors' => array( $lab => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
		$this->add_control( 'label_radius', array( 'label' => esc_html__( 'Label corner radius', 'super-widget' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 60 ) ), 'selectors' => array( $lab => 'border-radius: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_control( 'h_sub', array( 'label' => esc_html__( 'Small text', 'super-widget' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'sub_typo', 'selector' => '{{WRAPPER}} .spw-cg .spw-cg__sub' ) );
		$this->add_control( 'sub_color', array( 'label' => esc_html__( 'Color', 'super-widget' ), 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .spw-cg .spw-cg__sub' => 'color: {{VALUE}}; opacity: 1;' ) ) );
		$this->add_control( 'h_badge', array( 'label' => esc_html__( 'Badge', 'super-widget' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'badge_typo', 'selector' => '{{WRAPPER}} .spw-cg .spw-cg__badge' ) );
		$this->add_control( 'badge_color', array( 'label' => esc_html__( 'Text color', 'super-widget' ), 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .spw-cg .spw-cg__badge' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'badge_bg', array( 'label' => esc_html__( 'Background', 'super-widget' ), 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .spw-cg .spw-cg__badge' => 'background-color: {{VALUE}};' ) ) );
		$this->end_controls_section();

		/* ------------------------- Style: Slider controls ------------------------- */
		$btn = '{{WRAPPER}} .spw-cg .spw-cg__btn';
		$this->start_controls_section( 'style_nav', array( 'label' => esc_html__( 'Arrows & dots', 'super-widget' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => array( 'layout' => 'slider' ) ) );
		$this->add_control( 'nav_size', array( 'label' => esc_html__( 'Arrow size', 'super-widget' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 28, 'max' => 72 ) ), 'default' => array( 'size' => 42, 'unit' => 'px' ), 'selectors' => array( '{{WRAPPER}} .spw-cg' => '--cg-btn: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_control( 'nav_color', array( 'label' => esc_html__( 'Arrow icon', 'super-widget' ), 'type' => Controls_Manager::COLOR, 'selectors' => array( $btn => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'nav_bg', array( 'label' => esc_html__( 'Arrow background', 'super-widget' ), 'type' => Controls_Manager::COLOR, 'selectors' => array( $btn => 'background-color: {{VALUE}};' ) ) );
		$this->add_control( 'nav_hover_bg', array( 'label' => esc_html__( 'Arrow hover background', 'super-widget' ), 'type' => Controls_Manager::COLOR, 'selectors' => array( $btn . ':hover, ' . $btn . ':focus-visible' => 'background-color: {{VALUE}};' ) ) );
		$this->add_control( 'dot_color', array( 'label' => esc_html__( 'Dot color', 'super-widget' ), 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .spw-cg' => '--cg-dot: {{VALUE}};' ) ) );
		$this->add_control( 'dot_active', array( 'label' => esc_html__( 'Active dot color', 'super-widget' ), 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .spw-cg' => '--cg-dot-on: {{VALUE}};' ) ) );
		$this->end_controls_section();
	}

	protected function render() {
		$s     = $this->get_settings_for_display();
		$items = ! empty( $s['items'] ) && is_array( $s['items'] ) ? $s['items'] : array();
		if ( ! $items ) {
			return;
		}

		$layout = isset( $s['layout'] ) && in_array( $s['layout'], array( 'collage', 'grid', 'slider' ), true ) ? $s['layout'] : 'collage';
		$cstyle = isset( $s['collage_style'] ) && isset( $this->collage_styles()[ $s['collage_style'] ] ) ? $s['collage_style'] : 'classic';
		$pos    = array_keys( $this->positions() );
		$def    = isset( $s['label_pos'] ) && in_array( $s['label_pos'], $pos, true ) ? $s['label_pos'] : 'tl';
		$tags   = array( 'h2', 'h3', 'h4', 'h5', 'p', 'span' );
		$tag    = isset( $s['title_tag'] ) && in_array( $s['title_tag'], $tags, true ) ? $s['title_tag'] : 'h3';

		$entrance = isset( $s['entrance'] ) && isset( $this->entrances()[ $s['entrance'] ] ) ? $s['entrance'] : 'fade-up';
		$hover    = isset( $s['hover'] ) && in_array( $s['hover'], array( 'none', 'lift', 'zoom', 'both' ), true ) ? $s['hover'] : 'both';
		$shine    = isset( $s['shine'] ) && 'yes' === $s['shine'];
		if ( Super_Widget_Settings::get( 'disable_animations' ) ) {
			$entrance = 'none';
			$hover    = 'none';
			$shine    = false;
		}

		$slider   = 'slider' === $layout;
		$arrows   = $slider && ( ! isset( $s['show_arrows'] ) || 'yes' === $s['show_arrows'] );
		$dots     = $slider && ( ! isset( $s['show_dots'] ) || 'yes' === $s['show_dots'] );
		$autoplay = $slider && isset( $s['autoplay'] ) && 'yes' === $s['autoplay'] && ! Super_Widget_Settings::get( 'disable_animations' );
		$ms       = isset( $s['autoplay_ms'] ) ? max( 1500, min( 15000, (int) $s['autoplay_ms'] ) ) : 4000;

		$classes = array( 'spw-cg', 'spw-cg--' . $layout, 'spw-cg--anim-' . $entrance );
		if ( 'collage' === $layout ) {
			$classes[] = 'spw-cg--cs-' . $cstyle;
		}
		if ( 'lift' === $hover || 'both' === $hover ) {
			$classes[] = 'spw-cg--hover-lift';
		}
		if ( 'zoom' === $hover || 'both' === $hover ) {
			$classes[] = 'spw-cg--hover-zoom';
		}
		if ( $shine ) {
			$classes[] = 'spw-cg--shine';
		}

		echo '<div class="' . esc_attr( implode( ' ', $classes ) ) . '" data-layout="' . esc_attr( $layout ) . '"' . ( $autoplay ? ' data-autoplay="' . esc_attr( $ms ) . '"' : '' ) . '>';
		echo '<div class="spw-cg__stage">';
		echo '<div class="spw-cg__viewport">';
		echo '<div class="spw-cg__track"' . ( $slider ? ' role="list"' : '' ) . '>';

		foreach ( $items as $i => $item ) {
			$label = isset( $item['title'] ) ? (string) $item['title'] : '';
			$sub   = isset( $item['subtitle'] ) ? (string) $item['subtitle'] : '';
			$badge = isset( $item['badge'] ) ? (string) $item['badge'] : '';
			$lp    = isset( $item['label_pos'] ) && in_array( $item['label_pos'], $pos, true ) ? $item['label_pos'] : $def;
			$id    = isset( $item['_id'] ) ? sanitize_html_class( $item['_id'] ) : 'i' . (int) $i;
			$link  = isset( $item['link'] ) && is_array( $item['link'] ) ? $item['link'] : array();
			$url   = ! empty( $link['url'] ) ? esc_url( $link['url'] ) : '';

			$slide = 'spw-cg__slide elementor-repeater-item-' . $id . ( isset( $item['m_full'] ) && 'yes' === $item['m_full'] ? ' is-m-full' : '' );
			$cls   = 'spw-cg__card spw-cg__card--' . $lp;

			echo '<div class="' . esc_attr( $slide ) . '" style="--i:' . (int) $i . ';"' . ( $slider ? ' role="listitem"' : '' ) . '>';
			if ( $url ) {
				$rel = array();
				if ( ! empty( $link['is_external'] ) ) {
					$rel[] = 'noopener';
				}
				if ( ! empty( $link['nofollow'] ) ) {
					$rel[] = 'nofollow';
				}
				echo '<a class="' . esc_attr( $cls ) . '" href="' . $url . '"' . ( ! empty( $link['is_external'] ) ? ' target="_blank"' : '' ) . ( $rel ? ' rel="' . esc_attr( implode( ' ', $rel ) ) . '"' : '' ) . '>';
			} else {
				echo '<div class="' . esc_attr( $cls ) . '">';
			}

			if ( ! empty( $item['image']['id'] ) || ! empty( $item['image']['url'] ) ) {
				$img = Group_Control_Image_Size::get_attachment_image_html(
					array_merge( $s, array( 'thumb_image' => $item['image'] ) ),
					'thumb',
					'thumb_image'
				);
				if ( $img ) {
					// Add our class to the <img>; the markup comes from WordPress and is already escaped.
					if ( preg_match( '/<img[^>]*\sclass="/', $img ) ) {
						$img = preg_replace( '/(<img[^>]*\s)class="/', '$1class="spw-cg__img ', $img, 1 );
					} else {
						$img = preg_replace( '/<img\s/', '<img class="spw-cg__img" ', $img, 1 );
					}
					echo $img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
			}

			if ( $shine ) {
				echo '<span class="spw-cg__shine" aria-hidden="true"></span>';
			}
			if ( '' !== $badge ) {
				echo '<span class="spw-cg__badge">' . esc_html( $badge ) . '</span>';
			}
			if ( '' !== $label || '' !== $sub ) {
				echo '<span class="spw-cg__label">';
				if ( '' !== $label ) {
					echo '<' . tag_escape( $tag ) . ' class="spw-cg__title">' . esc_html( $label ) . '</' . tag_escape( $tag ) . '>';
				}
				if ( '' !== $sub ) {
					echo '<span class="spw-cg__sub">' . esc_html( $sub ) . '</span>';
				}
				echo '</span>';
			}

			echo $url ? '</a>' : '</div>';
			echo '</div>';
		}

		echo '</div></div>';

		if ( $arrows ) {
			echo '<button type="button" class="spw-cg__btn spw-cg__btn--prev" aria-label="' . esc_attr__( 'Previous', 'super-widget' ) . '"><svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg></button>';
			echo '<button type="button" class="spw-cg__btn spw-cg__btn--next" aria-label="' . esc_attr__( 'Next', 'super-widget' ) . '"><svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg></button>';
		}
		echo '</div>'; // .spw-cg__stage
		if ( $dots ) {
			echo '<div class="spw-cg__dots"></div>';
		}
		echo '</div>';
	}

	private function entrances() {
		return array(
			'none'    => esc_html__( 'None', 'super-widget' ),
			'fade-in' => esc_html__( 'Fade in', 'super-widget' ),
			'fade-up' => esc_html__( 'Fade up', 'super-widget' ),
			'zoom-in' => esc_html__( 'Zoom in', 'super-widget' ),
			'blur-in' => esc_html__( 'Blur in (soft focus)', 'super-widget' ),
			'pop'     => esc_html__( 'Pop (bounce)', 'super-widget' ),
		);
	}
}
