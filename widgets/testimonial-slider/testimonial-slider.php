<?php
/**
 * Testimonial Slider widget.
 *
 * Centered slider: the active card is flat in the middle, neighbouring cards
 * are tilted and clipped by the edges. All cards share one grid cell, so they
 * always have the same height without any JavaScript measuring.
 *
 * @package Super_Widget
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Utils;
use Elementor\Widget_Base;

class SPW_Testimonial_Slider_Widget extends Widget_Base {

	public function get_name() {
		return 'spw-testimonial-slider';
	}

	public function get_title() {
		return esc_html( Super_Widget_Settings::widget_title( 'testimonial-slider', __( 'Testimonial Slider', 'super-widget' ) ) );
	}

	public function get_icon() {
		return 'eicon-testimonial-carousel';
	}

	public function get_categories() {
		return array( 'super-widget' );
	}

	public function get_keywords() {
		return array( 'testimonial', 'review', 'slider', 'carousel', 'quote', 'rating' );
	}

	public function get_style_depends() {
		return array( 'spw-global', 'spw-testimonial-slider' );
	}

	public function get_script_depends() {
		return array( 'spw-testimonial-slider' );
	}

	/* ==================================================================
	 * Controls
	 * ================================================================== */

	protected function register_controls() {
		$this->controls_heading();
		$this->controls_items();
		$this->controls_layout();
		$this->controls_slider();
		$this->controls_alignment();
		$this->controls_motion();

		$this->style_widget();
		$this->style_heading();
		$this->style_card();
		$this->style_avatar();
		$this->style_text();
		$this->style_stars();
		$this->style_nav();
	}

	/* ---------------------------- Content ---------------------------- */

	private function controls_heading() {
		$this->start_controls_section(
			'section_heading',
			array(
				'label' => esc_html__( 'Heading', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_heading',
			array(
				'label'        => esc_html__( 'Show heading', 'super-widget' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'heading',
			array(
				'label'       => esc_html__( 'Text', 'super-widget' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 2,
				'default'     => esc_html__( 'Trusted By 1200+ World Class Businesses', 'super-widget' ),
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_heading' => 'yes' ),
			)
		);

		$this->add_control(
			'heading_tag',
			array(
				'label'     => esc_html__( 'HTML tag', 'super-widget' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'h2',
				'options'   => array(
					'h1'  => 'H1',
					'h2'  => 'H2',
					'h3'  => 'H3',
					'h4'  => 'H4',
					'div' => 'div',
					'p'   => 'p',
				),
				'condition' => array( 'show_heading' => 'yes' ),
			)
		);

		$this->add_control(
			'show_sparkles',
			array(
				'label'        => esc_html__( 'Sparkle decorations', 'super-widget' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array( 'show_heading' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_items() {
		$this->start_controls_section(
			'section_items',
			array(
				'label' => esc_html__( 'Testimonials', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'image',
			array(
				'label'   => esc_html__( 'Photo', 'super-widget' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => array( 'url' => Utils::get_placeholder_image_src() ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'name',
			array(
				'label'       => esc_html__( 'Name', 'super-widget' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Rina P.',
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'job',
			array(
				'label'       => esc_html__( 'Job title', 'super-widget' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Business Owner',
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'subtitle',
			array(
				'label'       => esc_html__( 'Small text (optional)', 'super-widget' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Recent graduate, Marketing at Sweatpals',
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'rating',
			array(
				'label'   => esc_html__( 'Rating', 'super-widget' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 5,
				'min'     => 0,
				'max'     => 5,
				'step'    => 0.1,
			)
		);

		$repeater->add_control(
			'quote',
			array(
				'label'   => esc_html__( 'Review', 'super-widget' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 5,
				'default' => esc_html__( 'QFR has helped me manage my business finances more efficiently. The detailed and accurate reports make financial planning much easier.', 'super-widget' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'items',
			array(
				'label'       => esc_html__( 'Items', 'super-widget' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ name }}}',
				'default'     => array(
					array(
						'name'     => 'Indra M.',
						'job'      => 'Private Employee',
						'quote'    => 'QFR is the best solution for anyone looking to take control of their finances. Its analysis is detailed, features are comprehensive, and the interface is incredibly user-friendly!',
					),
					array(
						'name'     => 'Rina P.',
						'job'      => 'Business Owner',
						'quote'    => 'QFR has helped me manage my business finances more efficiently. The detailed and accurate reports make financial planning much easier. Highly recommended for entrepreneurs!',
					),
					array(
						'name'     => 'Maya L.',
						'job'      => 'Freelancer',
						'quote'    => 'Data security and ease of access are my top priorities, and QFR delivers both. Now I can manage my personal finances with peace of mind.',
					),
				),
			)
		);

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			array(
				'name'      => 'avatar',
				'default'   => 'thumbnail',
				'exclude'   => array( 'custom' ),
				'separator' => 'before',
			)
		);

		$this->add_control(
			'name_prefix',
			array(
				'label'   => esc_html__( 'Text before name', 'super-widget' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '— ',
			)
		);

		$this->add_control(
			'show_rating_number',
			array(
				'label'        => esc_html__( 'Show rating number (5.0)', 'super-widget' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'quote_marks',
			array(
				'label'        => esc_html__( 'Wrap review in quotation marks', 'super-widget' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();
	}

	private function controls_layout() {
		$this->start_controls_section(
			'section_layout',
			array(
				'label' => esc_html__( 'Slider Layout', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_responsive_control(
			'cards_visible',
			array(
				'label'          => esc_html__( 'Cards visible', 'super-widget' ),
				'type'           => Controls_Manager::SELECT,
				'default'        => '1',
				'tablet_default' => '1',
				'mobile_default' => '1',
				'options'        => array(
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
				),
				'selectors'      => array( '{{WRAPPER}} .spw-t' => '--spw-v: {{VALUE}};' ),
				'render_type'    => 'template',
			)
		);

		$this->add_responsive_control(
			'side_cards',
			array(
				'label'          => esc_html__( 'Side cards peeking (each side)', 'super-widget' ),
				'type'           => Controls_Manager::SELECT,
				'default'        => '1',
				'tablet_default' => '1',
				'mobile_default' => '1',
				'options'        => array(
					'0' => esc_html__( 'None', 'super-widget' ),
					'1' => '1',
					'2' => '2',
					'3' => '3',
				),
				'selectors'      => array( '{{WRAPPER}} .spw-t' => '--spw-peek: {{VALUE}};' ),
				'render_type'    => 'template',
				'description'    => esc_html__( 'Tilted cards next to the visible ones. The container edges clip them, like in the reference design.', 'super-widget' ),
			)
		);

		$this->add_control(
			'layout_style',
			array(
				'label'       => esc_html__( 'Card arrangement', 'super-widget' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'curve',
				'separator'   => 'before',
				'options'     => array(
					'curve' => esc_html__( 'Curved (arc, like the reference)', 'super-widget' ),
					'flat'  => esc_html__( 'Flat (only side cards tilt)', 'super-widget' ),
				),
				'description' => esc_html__( 'Curved: the middle card is straight, every card further out tilts more and sits lower, so the row bends like an arc.', 'super-widget' ),
			)
		);

		$this->add_control(
			'card_fit',
			array(
				'label'     => esc_html__( 'Card width', 'super-widget' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'fit',
				'separator' => 'before',
				'options'   => array(
					'fit'   => esc_html__( 'Fit to the section width (follows the container)', 'super-widget' ),
					'fixed' => esc_html__( 'Fixed (px)', 'super-widget' ),
				),
			)
		);

		$this->add_responsive_control(
			'card_width',
			array(
				'label'          => esc_html__( 'Card width', 'super-widget' ),
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => array( 'px' ),
				'range'          => array( 'px' => array( 'min' => 200, 'max' => 800 ) ),
				'default'        => array( 'size' => 330, 'unit' => 'px' ),
				'tablet_default' => array( 'size' => 310, 'unit' => 'px' ),
				'mobile_default' => array( 'size' => 280, 'unit' => 'px' ),
				'selectors'      => array( '{{WRAPPER}} .spw-t' => '--spw-card-w: {{SIZE}}{{UNIT}};' ),
				'condition'      => array( 'card_fit' => 'fixed' ),
			)
		);

		$this->add_responsive_control(
			'card_max_width',
			array(
				'label'       => esc_html__( 'Max card width (optional)', 'super-widget' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array( 'px' => array( 'min' => 200, 'max' => 1200 ) ),
				'selectors'   => array( '{{WRAPPER}} .spw-t' => '--spw-card-max: {{SIZE}}{{UNIT}};' ),
				'condition'   => array( 'card_fit' => 'fit' ),
				'description' => esc_html__( 'Leave empty so the cards fill the section.', 'super-widget' ),
			)
		);

		$this->add_responsive_control(
			'peek_amount',
			array(
				'label'          => esc_html__( 'Side card visible part (px)', 'super-widget' ),
				'type'           => Controls_Manager::SLIDER,
				'range'          => array( 'px' => array( 'min' => 0, 'max' => 200 ) ),
				'default'        => array( 'size' => 60 ),
				'tablet_default' => array( 'size' => 48 ),
				'mobile_default' => array( 'size' => 28 ),
				'selectors'      => array( '{{WRAPPER}} .spw-t' => '--spw-peek-w: {{SIZE}}px;' ),
				'condition'      => array( 'card_fit' => 'fit' ),
			)
		);

		$this->add_responsive_control(
			'card_gap',
			array(
				'label'          => esc_html__( 'Space between cards', 'super-widget' ),
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => array( 'px' ),
				'range'          => array( 'px' => array( 'min' => 0, 'max' => 120 ) ),
				'default'        => array( 'size' => 30, 'unit' => 'px' ),
				'tablet_default' => array( 'size' => 24, 'unit' => 'px' ),
				'mobile_default' => array( 'size' => 16, 'unit' => 'px' ),
				'selectors'      => array( '{{WRAPPER}} .spw-t' => '--spw-gap: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_slider() {
		$this->start_controls_section(
			'section_slider',
			array(
				'label' => esc_html__( 'Slider', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'loop',
			array(
				'label'        => esc_html__( 'Infinite loop', 'super-widget' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'swipe',
			array(
				'label'        => esc_html__( 'Swipe / drag', 'super-widget' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'autoplay',
			array(
				'label'        => esc_html__( 'Autoplay', 'super-widget' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'autoplay_speed',
			array(
				'label'     => esc_html__( 'Autoplay delay (ms)', 'super-widget' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 4500,
				'min'       => 1500,
				'max'       => 20000,
				'step'      => 100,
				'condition' => array( 'autoplay' => 'yes' ),
			)
		);

		$this->add_control(
			'pause_on_hover',
			array(
				'label'        => esc_html__( 'Pause on hover', 'super-widget' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array( 'autoplay' => 'yes' ),
			)
		);

		$this->add_control(
			'show_dots',
			array(
				'label'        => esc_html__( 'Dots', 'super-widget' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
				'render_type'  => 'template',
			)
		);

		Super_Widget_Nav::add_dot_control( $this, '.spw-t', 'bottom-center', esc_html__( 'Dots position', 'super-widget' ) );

		$this->add_control(
			'show_arrows',
			array(
				'label'        => esc_html__( 'Arrows', 'super-widget' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'render_type'  => 'template',
			)
		);

		Super_Widget_Nav::add_arrow_control( $this, '.spw-t', array( 'bottom-center', 'bottom-center', 'bottom-center' ) );

		$this->end_controls_section();
	}

	private function controls_alignment() {
		$this->start_controls_section(
			'section_alignment',
			array(
				'label' => esc_html__( 'Equal Height & Alignment', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'align_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Every card has the same height. Photo, name, small text, stars and review sit at the same position in all cards.', 'super-widget' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);

		$this->add_control(
			'role_lines',
			array(
				'label'     => esc_html__( 'Small text lines (reserved)', 'super-widget' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 2,
				'min'       => 1,
				'max'       => 4,
				'selectors' => array( '{{WRAPPER}} .spw-t' => '--spw-role-lines: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'quote_lines',
			array(
				'label'       => esc_html__( 'Review lines (reserved)', 'super-widget' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 5,
				'min'         => 2,
				'max'         => 12,
				'selectors'   => array( '{{WRAPPER}} .spw-t' => '--spw-quote-lines: {{VALUE}};' ),
				'description' => esc_html__( 'Longer reviews are cut after this many lines.', 'super-widget' ),
			)
		);

		$this->add_responsive_control(
			'text_align',
			array(
				'label'     => esc_html__( 'Text alignment', 'super-widget' ),
				'type'      => Controls_Manager::CHOOSE,
				'default'   => 'center',
				'options'   => array(
					'left'   => array( 'title' => esc_html__( 'Left', 'super-widget' ), 'icon' => 'eicon-text-align-left' ),
					'center' => array( 'title' => esc_html__( 'Center', 'super-widget' ), 'icon' => 'eicon-text-align-center' ),
					'right'  => array( 'title' => esc_html__( 'Right', 'super-widget' ), 'icon' => 'eicon-text-align-right' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .spw-t__card' => 'text-align: {{VALUE}};',
					'{{WRAPPER}} .spw-t__avatar, {{WRAPPER}} .spw-t__rating' => 'margin-inline: var(--spw-m-{{VALUE}});',
				),
			)
		);

		$this->end_controls_section();
	}

	private function controls_motion() {
		$this->start_controls_section(
			'section_motion',
			array(
				'label' => esc_html__( 'Animation', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_responsive_control(
			'tilt',
			array(
				'label'      => esc_html__( 'Tilt per card step (deg)', 'super-widget' ),
				'type'       => Controls_Manager::SLIDER,
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 20, 'step' => 0.5 ) ),
				'default'    => array( 'size' => 7.5 ),
				'tablet_default' => array( 'size' => 6 ),
				'mobile_default' => array( 'size' => 5 ),
				'selectors'  => array( '{{WRAPPER}} .spw-t' => '--spw-tilt: {{SIZE}}deg;' ),
			)
		);

		$this->add_responsive_control(
			'side_offset',
			array(
				'label'      => esc_html__( 'Curve depth / side card drop (px)', 'super-widget' ),
				'type'       => Controls_Manager::SLIDER,
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
				'default'    => array( 'size' => 24 ),
				'selectors'  => array( '{{WRAPPER}} .spw-t' => '--spw-side-y: {{SIZE}}px;' ),
			)
		);

		$this->add_control(
			'side_opacity',
			array(
				'label'     => esc_html__( 'Side card opacity', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0.2, 'max' => 1, 'step' => 0.05 ) ),
				'default'   => array( 'size' => 1 ),
				'selectors' => array( '{{WRAPPER}} .spw-t' => '--spw-side-o: {{SIZE}};' ),
			)
		);

		$this->add_control(
			'slide_duration',
			array(
				'label'     => esc_html__( 'Slide speed (ms)', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 100, 'max' => 1500, 'step' => 50 ) ),
				'default'   => array( 'size' => 750 ),
				'selectors' => array( '{{WRAPPER}} .spw-t' => '--spw-dur: {{SIZE}}ms;' ),
			)
		);

		$this->add_control(
			'slide_easing',
			array(
				'label'     => esc_html__( 'Slide easing', 'super-widget' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'cubic-bezier(0.22, 1, 0.36, 1)',
				'options'   => array(
					'cubic-bezier(0.22, 1, 0.36, 1)'    => esc_html__( 'Smooth (glide and settle)', 'super-widget' ),
					'cubic-bezier(0.65, 0, 0.35, 1)'    => esc_html__( 'Soft (ease in-out)', 'super-widget' ),
					'cubic-bezier(0.34, 1.35, 0.64, 1)' => esc_html__( 'Springy', 'super-widget' ),
					'linear'                            => esc_html__( 'Linear', 'super-widget' ),
				),
				'selectors' => array( '{{WRAPPER}} .spw-t' => '--spw-ease: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'side_scale',
			array(
				'label'       => esc_html__( 'Side card scale', 'super-widget' ),
				'type'        => Controls_Manager::SLIDER,
				'range'       => array( 'px' => array( 'min' => 0.7, 'max' => 1, 'step' => 0.01 ) ),
				'default'     => array( 'size' => 0.96 ),
				'selectors'   => array( '{{WRAPPER}} .spw-t' => '--spw-side-scale: {{SIZE}};' ),
				'description' => esc_html__( 'Cards shrink slightly when they leave the center and grow back when they arrive.', 'super-widget' ),
			)
		);

		$this->add_control(
			'content_reveal',
			array(
				'label'        => esc_html__( 'Content reveal on slide', 'super-widget' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'Photo, name, stars and review fade in one after another when a card arrives.', 'super-widget' ),
			)
		);

		$this->add_control(
			'autoplay_progress',
			array(
				'label'        => esc_html__( 'Autoplay progress on active dot', 'super-widget' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array( 'autoplay' => 'yes' ),
			)
		);

		$this->add_control(
			'hover_effect',
			array(
				'label'     => esc_html__( 'Card hover', 'super-widget' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'lift',
				'separator' => 'before',
				'options'   => array(
					'none' => esc_html__( 'None', 'super-widget' ),
					'lift' => esc_html__( 'Lift', 'super-widget' ),
				),
			)
		);

		$this->add_control(
			'hover_lift',
			array(
				'label'     => esc_html__( 'Lift distance (px)', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 30 ) ),
				'default'   => array( 'size' => 6 ),
				'condition' => array( 'hover_effect' => 'lift' ),
				'selectors' => array( '{{WRAPPER}} .spw-t' => '--spw-lift: -{{SIZE}}px;' ),
			)
		);

		$this->add_control(
			'entrance_animation',
			array(
				'label'     => esc_html__( 'Entrance animation', 'super-widget' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'fade-up',
				'separator' => 'before',
				'options'   => array(
					'none'    => esc_html__( 'None', 'super-widget' ),
					'fade-in' => esc_html__( 'Fade in', 'super-widget' ),
					'fade-up' => esc_html__( 'Fade up', 'super-widget' ),
					'zoom-in' => esc_html__( 'Zoom in', 'super-widget' ),
				),
			)
		);

		$this->end_controls_section();
	}

	/* ----------------------------- Style ----------------------------- */

	private function style_widget() {
		$this->start_controls_section(
			'style_widget',
			array(
				'label' => esc_html__( 'Container', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'wrap_background',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .spw-t',
			)
		);

		$this->add_responsive_control(
			'wrap_padding',
			array(
				'label'      => esc_html__( 'Padding', 'super-widget' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .spw-t' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'head_gap',
			array(
				'label'     => esc_html__( 'Space under heading', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 160 ) ),
				'selectors' => array( '{{WRAPPER}} .spw-t__head' => 'margin-bottom: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	private function style_heading() {
		$sel = '{{WRAPPER}} .spw-t__title';

		$this->start_controls_section(
			'style_heading',
			array(
				'label'     => esc_html__( 'Heading', 'super-widget' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_heading' => 'yes' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'heading_typography',
				'selector' => $sel,
			)
		);

		$this->add_control(
			'heading_color',
			array(
				'label'     => esc_html__( 'Color', 'super-widget' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( $sel => 'color: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'heading_width',
			array(
				'label'      => esc_html__( 'Max width', 'super-widget' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array( 'px' => array( 'min' => 200, 'max' => 1200 ), '%' => array( 'min' => 20, 'max' => 100 ) ),
				'selectors'  => array( '{{WRAPPER}} .spw-t__head-inner' => 'max-width: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'spark_color',
			array(
				'label'     => esc_html__( 'Sparkle color', 'super-widget' ),
				'type'      => Controls_Manager::COLOR,
				'separator' => 'before',
				'selectors' => array( '{{WRAPPER}} .spw-t__spark' => 'color: {{VALUE}};' ),
				'condition' => array( 'show_sparkles' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'spark_size',
			array(
				'label'     => esc_html__( 'Sparkle size', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 10, 'max' => 90 ) ),
				'selectors' => array( '{{WRAPPER}} .spw-t' => '--spw-spark: {{SIZE}}px;' ),
				'condition' => array( 'show_sparkles' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function style_card() {
		$card = '{{WRAPPER}} .spw-t__card';

		$this->start_controls_section(
			'style_card',
			array(
				'label' => esc_html__( 'Card', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'      => 'card_background',
				'types'     => array( 'classic', 'gradient' ),
				'selector'  => $card,
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'card_border',
				'selector' => $card,
			)
		);

		$this->add_responsive_control(
			'card_radius',
			array(
				'label'      => esc_html__( 'Border radius', 'super-widget' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					$card => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'card_padding',
			array(
				'label'      => esc_html__( 'Padding', 'super-widget' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					$card => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'profile_gap',
			array(
				'label'     => esc_html__( 'Space between profile and review', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 120 ) ),
				'default'   => array( 'size' => 32 ),
				'selectors' => array( '{{WRAPPER}} .spw-t__bottom' => 'padding-top: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->start_controls_tabs( 'card_shadow_tabs' );

		$this->start_controls_tab( 'card_shadow_normal', array( 'label' => esc_html__( 'Normal', 'super-widget' ) ) );
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'card_shadow',
				'selector' => $card,
			)
		);
		$this->end_controls_tab();

		$this->start_controls_tab( 'card_shadow_active', array( 'label' => esc_html__( 'Active', 'super-widget' ) ) );
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'card_shadow_active',
				'selector' => $card . '.is-active',
			)
		);
		$this->end_controls_tab();

		$this->start_controls_tab( 'card_shadow_hover', array( 'label' => esc_html__( 'Hover', 'super-widget' ) ) );
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'card_shadow_hover',
				'selector' => $card . ':hover',
			)
		);
		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	private function style_avatar() {
		$sel = '{{WRAPPER}} .spw-t__avatar';

		$this->start_controls_section(
			'style_avatar',
			array(
				'label' => esc_html__( 'Photo', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'avatar_size',
			array(
				'label'     => esc_html__( 'Size', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 24, 'max' => 160 ) ),
				'default'   => array( 'size' => 48 ),
				'selectors' => array( $sel => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'avatar_radius',
			array(
				'label'      => esc_html__( 'Border radius', 'super-widget' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ), '%' => array( 'min' => 0, 'max' => 50 ) ),
				'default'    => array( 'size' => 50, 'unit' => '%' ),
				'selectors'  => array( $sel => 'border-radius: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'avatar_border',
				'selector' => $sel,
			)
		);

		$this->add_responsive_control(
			'avatar_space',
			array(
				'label'     => esc_html__( 'Space below', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'default'   => array( 'size' => 14 ),
				'selectors' => array( $sel => 'margin-bottom: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	private function style_text() {
		$this->start_controls_section(
			'style_text',
			array(
				'label' => esc_html__( 'Text', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$items = array(
			'name'   => array( esc_html__( 'Name', 'super-widget' ), '.spw-t__name' ),
			'role'   => array( esc_html__( 'Small text', 'super-widget' ), '.spw-t__role' ),
			'rnum'   => array( esc_html__( 'Rating number', 'super-widget' ), '.spw-t__rnum' ),
			'quote'  => array( esc_html__( 'Review', 'super-widget' ), '.spw-t__quote' ),
		);

		foreach ( $items as $key => $def ) {
			$this->add_control(
				$key . '_heading',
				array(
					'label'     => $def[0],
					'type'      => Controls_Manager::HEADING,
					'separator' => 'name' === $key ? 'none' : 'before',
				)
			);

			$this->add_group_control(
				Group_Control_Typography::get_type(),
				array(
					'name'     => $key . '_typography',
					'selector' => '{{WRAPPER}} ' . $def[1],
				)
			);

			$this->add_control(
				$key . '_color',
				array(
					'label'     => esc_html__( 'Color', 'super-widget' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} ' . $def[1] => 'color: {{VALUE}};' ),
				)
			);
		}

		$this->end_controls_section();
	}

	private function style_stars() {
		$this->start_controls_section(
			'style_stars',
			array(
				'label' => esc_html__( 'Stars', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'star_size',
			array(
				'label'     => esc_html__( 'Size', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 8, 'max' => 48 ) ),
				'default'   => array( 'size' => 15 ),
				'selectors' => array( '{{WRAPPER}} .spw-t' => '--spw-star: {{SIZE}}px;' ),
			)
		);

		$this->add_control(
			'star_color',
			array(
				'label'     => esc_html__( 'Color', 'super-widget' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .spw-t' => '--spw-star-on: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'star_empty',
			array(
				'label'     => esc_html__( 'Empty star color', 'super-widget' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .spw-t' => '--spw-star-off: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'star_space',
			array(
				'label'     => esc_html__( 'Space below stars', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'default'   => array( 'size' => 14 ),
				'selectors' => array( '{{WRAPPER}} .spw-t__rating' => 'margin-bottom: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	private function style_nav() {
		$this->start_controls_section(
			'style_nav',
			array(
				'label' => esc_html__( 'Arrows & Dots', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'nav_space',
			array(
				'label'     => esc_html__( 'Space between navigation and slider', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 120 ) ),
				'default'   => array( 'size' => 18 ),
				'selectors' => array( '{{WRAPPER}} .spw-t' => '--spw-nav-gap: {{SIZE}}px;' ),
			)
		);

		$this->add_responsive_control(
			'arrow_edge',
			array(
				'label'     => esc_html__( 'Distance from the edge (side arrows)', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => -40, 'max' => 120 ) ),
				'default'   => array( 'size' => 8 ),
				'selectors' => array( '{{WRAPPER}} .spw-t' => '--spw-arrow-x: {{SIZE}}px;' ),
			)
		);

		$this->add_control(
			'arrow_heading',
			array(
				'label'     => esc_html__( 'Arrows', 'super-widget' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'arrow_size',
			array(
				'label'     => esc_html__( 'Button size', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 28, 'max' => 90 ) ),
				'default'   => array( 'size' => 50 ),
				'selectors' => array( '{{WRAPPER}} .spw-t__arrow' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'arrow_icon_size',
			array(
				'label'     => esc_html__( 'Icon size', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 8, 'max' => 40 ) ),
				'default'   => array( 'size' => 16 ),
				'selectors' => array( '{{WRAPPER}} .spw-t__arrow svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'arrow_gap',
			array(
				'label'     => esc_html__( 'Gap', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'default'   => array( 'size' => 12 ),
				'selectors' => array( '{{WRAPPER}} .spw-t__arrows' => 'gap: {{SIZE}}{{UNIT}};' ),
			)
		);

		Super_Widget_Nav::add_arrow_style( $this, '.spw-t__arrow', array( 'border' => true ) );

		$this->add_control(
			'dots_heading',
			array(
				'label'     => esc_html__( 'Dots', 'super-widget' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'dot_size',
			array(
				'label'     => esc_html__( 'Size', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 4, 'max' => 24 ) ),
				'default'   => array( 'size' => 7 ),
				'selectors' => array( '{{WRAPPER}} .spw-t' => '--spw-dot: {{SIZE}}px;' ),
			)
		);

		$this->add_responsive_control(
			'dot_active_width',
			array(
				'label'     => esc_html__( 'Active dot width', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 4, 'max' => 80 ) ),
				'default'   => array( 'size' => 26 ),
				'selectors' => array( '{{WRAPPER}} .spw-t' => '--spw-dot-active: {{SIZE}}px;' ),
			)
		);

		$this->add_control(
			'dot_color',
			array(
				'label'     => esc_html__( 'Color', 'super-widget' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .spw-t' => '--spw-dot-off: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'dot_active_color',
			array(
				'label'     => esc_html__( 'Active color', 'super-widget' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .spw-t' => '--spw-dot-on: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'dot_gap',
			array(
				'label'     => esc_html__( 'Gap', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 30 ) ),
				'default'   => array( 'size' => 6 ),
				'selectors' => array( '{{WRAPPER}} .spw-t__dots' => 'gap: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'dots_space',
			array(
				'label'     => esc_html__( 'Space between dots and arrows (same area)', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
				'default'   => array( 'size' => 24 ),
				'selectors' => array( '{{WRAPPER}} .spw-t' => '--spw-nav-between: {{SIZE}}px;' ),
			)
		);

		$this->end_controls_section();
	}

	/* ==================================================================
	 * Render
	 * ================================================================== */

	/**
	 * Position of card $i relative to the middle of the visible window.
	 * With V visible cards the window starts at the active index and its
	 * middle is (V - 1) / 2. When looping, the shortest way round is used.
	 */
	private function position( $i, $n, $visible, $loop ) {
		$k = $i - ( $visible - 1 ) / 2;
		if ( ! $loop ) {
			return $k;
		}
		$p = fmod( $k + $n / 2, $n );
		if ( $p < 0 ) {
			$p += $n;
		}
		return $p - $n / 2;
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$items    = ! empty( $settings['items'] ) && is_array( $settings['items'] ) ? $settings['items'] : array();

		if ( empty( $items ) ) {
			return;
		}

		$n     = count( $items );
		$side  = isset( $settings['side_cards'] ) ? max( 0, min( 3, (int) $settings['side_cards'] ) ) : 1;
		$vis   = isset( $settings['cards_visible'] ) ? max( 1, min( 4, (int) $settings['cards_visible'] ) ) : 1;
		$vis   = min( $vis, $n );
		$loop  = ( isset( $settings['loop'] ) && 'yes' === $settings['loop'] ) && $n > 1;

		// Side cards need spare cards to show (same rule as in the script).
		if ( $n <= $vis ) {
			$side = 0;
		} elseif ( $loop ) {
			$side = min( $side, (int) floor( ( $n - $vis ) / 2 ) );
		}
		$anim  = isset( $settings['entrance_animation'] ) ? (string) $settings['entrance_animation'] : 'none';
		$hover = isset( $settings['hover_effect'] ) ? (string) $settings['hover_effect'] : 'none';

		if ( ! in_array( $anim, array( 'none', 'fade-in', 'fade-up', 'zoom-in' ), true ) ) {
			$anim = 'none';
		}
		if ( ! in_array( $hover, array( 'none', 'lift' ), true ) ) {
			$hover = 'none';
		}
		if ( Super_Widget_Settings::get( 'disable_animations' ) ) {
			$anim  = 'none';
			$hover = 'none';
		}

		$tag = isset( $settings['heading_tag'] ) ? (string) $settings['heading_tag'] : 'h2';
		if ( ! in_array( $tag, array( 'h1', 'h2', 'h3', 'h4', 'div', 'p' ), true ) ) {
			$tag = 'h2';
		}

		$classes = array( 'spw-t', 'spw-t--init' );
		if ( ! isset( $settings['layout_style'] ) || 'flat' !== $settings['layout_style'] ) {
			$classes[] = 'spw-t--curve';
		}
		if ( isset( $settings['card_fit'] ) && 'fit' === $settings['card_fit'] ) {
			$classes[] = 'spw-t--fit';
		}
		if ( isset( $settings['content_reveal'] ) && 'yes' === $settings['content_reveal'] ) {
			$classes[] = 'spw-t--reveal';
		}
		if ( isset( $settings['swipe'] ) && 'yes' === $settings['swipe'] && $n > 1 ) {
			$classes[] = 'spw-t--swipe';
		}
		if ( 'none' !== $anim ) {
			$classes[] = 'spw-t-anim-' . $anim;
		}
		if ( 'lift' === $hover ) {
			$classes[] = 'spw-t--lift';
		}

		$autoplay = ( isset( $settings['autoplay'] ) && 'yes' === $settings['autoplay'] ) && $n > 1;

		$this->add_render_attribute(
			'root',
			array(
				'class'         => $classes,
				'data-count'    => (string) $n,
				'data-side'     => (string) $side,
				'data-visible'  => (string) $vis,
				'data-loop'     => $loop ? 'yes' : 'no',
				'data-swipe'    => ( isset( $settings['swipe'] ) && 'yes' === $settings['swipe'] && $n > 1 ) ? 'yes' : 'no',
				'data-autoplay' => $autoplay ? 'yes' : 'no',
				'data-speed'    => isset( $settings['autoplay_speed'] ) ? (string) absint( $settings['autoplay_speed'] ) : '4500',
				'data-pause'    => ( isset( $settings['pause_on_hover'] ) && 'yes' === $settings['pause_on_hover'] ) ? 'yes' : 'no',
				'data-progress' => ( $autoplay && isset( $settings['autoplay_progress'] ) && 'yes' === $settings['autoplay_progress'] ) ? 'yes' : 'no',
			)
		);

		$show_heading = isset( $settings['show_heading'] ) && 'yes' === $settings['show_heading'] && ! empty( $settings['heading'] );
		$sparkles     = isset( $settings['show_sparkles'] ) && 'yes' === $settings['show_sparkles'];
		?>
		<div <?php $this->print_render_attribute_string( 'root' ); ?>>

			<?php if ( $show_heading ) : ?>
				<div class="spw-t__head">
					<div class="spw-t__head-inner">
						<?php if ( $sparkles ) : ?>
							<?php $this->render_spark( 'a' ); ?>
							<?php $this->render_spark( 'b' ); ?>
						<?php endif; ?>
						<<?php echo esc_attr( $tag ); ?> class="spw-t__title"><?php echo nl2br( esc_html( $settings['heading'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></<?php echo esc_attr( $tag ); ?>>
					</div>
				</div>
			<?php endif; ?>

			<?php
			$show_dots   = isset( $settings['show_dots'] ) && 'yes' === $settings['show_dots'] && $n > 1;
			$show_arrows = isset( $settings['show_arrows'] ) && 'yes' === $settings['show_arrows'] && $n > 1;

			$arrow_zones = $show_arrows ? Super_Widget_Nav::arrow_zones( $settings ) : array();
			$dot_zones   = $show_dots ? Super_Widget_Nav::dot_zones( $settings ) : array();
			$zone_top    = in_array( 'top', $arrow_zones, true ) || in_array( 'top', $dot_zones, true );
			$zone_bottom = in_array( 'bottom', $arrow_zones, true ) || in_array( 'fb', $arrow_zones, true ) || in_array( 'bottom', $dot_zones, true );
			?>

			<?php if ( $zone_top ) : ?>
				<div class="spw-t__zone spw-t__zone--top">
					<?php
					if ( in_array( 'top', $dot_zones, true ) ) {
						$this->render_dots( 'top', $n );
					}
					if ( in_array( 'top', $arrow_zones, true ) ) {
						$this->render_arrows( 'top' );
					}
					?>
				</div>
			<?php endif; ?>

			<div class="spw-t__stagewrap">
				<?php
				if ( in_array( 'side', $arrow_zones, true ) ) {
					$this->render_arrows( 'side' );
				}
				?>

				<div class="spw-t__viewport" role="region" aria-roledescription="carousel" aria-label="<?php echo esc_attr__( 'Testimonials', 'super-widget' ); ?>">
					<div class="spw-t__stage">
						<?php foreach ( $items as $i => $item ) : ?>
							<?php
							$d      = $this->position( $i, $n, $vis, $loop );
							$half   = ( $vis - 1 ) / 2;
							$active = abs( $d ) <= $half + 0.001;
							$shown  = abs( $d ) <= $half + $side + 0.001;
							$cls    = 'spw-t__card' . ( $active ? ' is-active' : '' ) . ( $shown ? ' is-shown' : '' );
							$sign   = $active ? 0 : ( $d <=> 0 );
							$dval   = rtrim( rtrim( number_format( $d, 2, '.', '' ), '0' ), '.' );
							?>
							<article class="<?php echo esc_attr( $cls ); ?>" style="--d:<?php echo esc_attr( '' === $dval || '-0' === $dval ? '0' : $dval ); ?>;--a:<?php echo esc_attr( (string) min( 1, abs( round( $d, 2 ) ) ) ); ?>;--s:<?php echo (int) $sign; ?>"<?php echo $active ? '' : ' aria-hidden="true"'; ?> role="group" aria-roledescription="slide" aria-label="<?php echo esc_attr( sprintf( /* translators: 1: slide number, 2: total */ __( '%1$d of %2$d', 'super-widget' ), $i + 1, $n ) ); ?>">
								<?php $this->render_card( $item, $settings ); ?>
							</article>
						<?php endforeach; ?>
					</div>
				</div>

			</div>

			<?php if ( $zone_bottom ) : ?>
				<div class="spw-t__zone spw-t__zone--bottom">
					<?php
					if ( in_array( 'bottom', $dot_zones, true ) ) {
						$this->render_dots( 'bottom', $n );
					}
					if ( in_array( 'bottom', $arrow_zones, true ) ) {
						$this->render_arrows( 'bottom' );
					}
					if ( in_array( 'fb', $arrow_zones, true ) ) {
						$this->render_arrows( 'fb' );
					}
					?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	private function render_arrows( $variant ) {
		?>
		<div class="spw-t__arrows spw-t__arrows--<?php echo esc_attr( $variant ); ?>">
			<button type="button" class="spw-t__arrow spw-t__arrow--prev" aria-label="<?php echo esc_attr__( 'Previous testimonial', 'super-widget' ); ?>">
				<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
			</button>
			<button type="button" class="spw-t__arrow spw-t__arrow--next" aria-label="<?php echo esc_attr__( 'Next testimonial', 'super-widget' ); ?>">
				<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
			</button>
		</div>
		<?php
	}

	private function render_dots( $variant, $n ) {
		?>
		<div class="spw-t__dots spw-t__dots--<?php echo esc_attr( $variant ); ?>">
			<?php for ( $i = 0; $i < $n; $i++ ) : ?>
				<button type="button" class="spw-t__dot<?php echo 0 === $i ? ' is-active' : ''; ?>" data-index="<?php echo (int) $i; ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: slide number */ __( 'Go to slide %d', 'super-widget' ), $i + 1 ) ); ?>"<?php echo 0 === $i ? ' aria-current="true"' : ''; ?>></button>
			<?php endfor; ?>
		</div>
		<?php
	}

	private function render_spark( $which ) {
		?>
		<span class="spw-t__spark spw-t__spark--<?php echo esc_attr( $which ); ?>" aria-hidden="true">
			<svg viewBox="0 0 24 24" fill="currentColor" focusable="false"><path d="M12 0c.6 6.6 4.8 11.4 12 12-7.2.6-11.4 5.4-12 12-.6-6.6-4.8-11.4-12-12C7.2 11.4 11.4 6.6 12 0z"/></svg>
		</span>
		<?php
	}

	private function render_card( $item, $settings ) {
		$name   = isset( $item['name'] ) ? trim( (string) $item['name'] ) : '';
		$job    = isset( $item['job'] ) ? trim( (string) $item['job'] ) : '';
		$sub    = isset( $item['subtitle'] ) ? trim( (string) $item['subtitle'] ) : '';
		$quote  = isset( $item['quote'] ) ? (string) $item['quote'] : '';
		$rating = isset( $item['rating'] ) && is_numeric( $item['rating'] ) ? max( 0, min( 5, (float) $item['rating'] ) ) : 5;
		$prefix = isset( $settings['name_prefix'] ) ? (string) $settings['name_prefix'] : '';

		if ( '' !== trim( $quote ) && isset( $settings['quote_marks'] ) && 'yes' === $settings['quote_marks'] ) {
			$quote = '"' . trim( $quote ) . '"';
		}

		$who = $name;
		if ( '' !== $job ) {
			$who .= ( '' !== $who ? ', ' : '' ) . $job;
		}

		// Photo, or a letter circle when there is none.
		$has_image = ! empty( $item['image']['url'] ) || ! empty( $item['image']['id'] );
		$image     = '';
		if ( $has_image ) {
			$image = Group_Control_Image_Size::get_attachment_image_html(
				array(
					'image'       => $item['image'],
					'avatar_size' => isset( $settings['avatar_size'] ) ? $settings['avatar_size'] : 'thumbnail',
				),
				'avatar',
				'image'
			);
		}
		?>
		<div class="spw-t__top">
			<?php if ( '' !== $image ) : ?>
				<div class="spw-t__avatar"><?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
			<?php else : ?>
				<div class="spw-t__avatar spw-t__avatar--letter" aria-hidden="true"><?php echo esc_html( function_exists( 'mb_substr' ) ? mb_strtoupper( mb_substr( $name, 0, 1 ) ) : strtoupper( substr( $name, 0, 1 ) ) ); ?></div>
			<?php endif; ?>

			<?php if ( '' !== $who ) : ?>
				<div class="spw-t__name"><?php echo esc_html( $prefix . $who ); ?></div>
			<?php endif; ?>

			<div class="spw-t__role"><?php echo esc_html( $sub ); ?></div>
		</div>

		<div class="spw-t__bottom">
			<div class="spw-t__rating">
				<span class="spw-t__stars" style="--r:<?php echo esc_attr( (string) $rating ); ?>" role="img" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: rating */ __( 'Rated %s out of 5', 'super-widget' ), number_format_i18n( $rating, 1 ) ) ); ?>"></span>
				<?php if ( isset( $settings['show_rating_number'] ) && 'yes' === $settings['show_rating_number'] ) : ?>
					<span class="spw-t__rnum"><?php echo esc_html( number_format_i18n( $rating, 1 ) ); ?></span>
				<?php endif; ?>
			</div>
			<blockquote class="spw-t__quote"><?php echo esc_html( $quote ); ?></blockquote>
		</div>
		<?php
	}
}
