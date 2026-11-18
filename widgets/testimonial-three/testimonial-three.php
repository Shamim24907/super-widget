<?php
/**
 * Testimonial 3 widget: review cards in a grid + summary bar.
 *
 * Pure CSS grid, no slider and no layout script: columns, gaps and the bar
 * layout come from Elementor controls as CSS variables, so every device can
 * differ. A tiny script only reveals the cards on scroll.
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
use Elementor\Icons_Manager;
use Elementor\Repeater;
use Elementor\Widget_Base;

class SPW_Testimonial_Three_Widget extends Widget_Base {

	public function get_name() {
		return 'spw-testimonial-three';
	}

	public function get_title() {
		return esc_html( Super_Widget_Settings::widget_title( 'testimonial-three', __( 'Testimonial 3', 'super-widget' ) ) );
	}

	public function get_icon() {
		return 'eicon-review';
	}

	public function get_categories() {
		return array( 'super-widget' );
	}

	public function get_keywords() {
		return array( 'testimonial', 'review', 'reviews', 'google', 'rating', 'stars', 'grid', 'feedback' );
	}

	public function get_style_depends() {
		return array( 'spw-global', 'spw-testimonial-three' );
	}

	public function get_script_depends() {
		return array( 'spw-testimonial-three' );
	}

	/* ==================================================================
	 * Controls
	 * ================================================================== */

	protected function register_controls() {
		$this->controls_reviews();
		$this->controls_layout();
		$this->controls_bar();
		$this->controls_motion();

		$this->style_section();
		$this->style_cards();
		$this->style_avatar();
		$this->style_text();
		$this->style_stars();
		$this->style_quote();
		$this->style_bar();
		$this->style_button();
	}

	private function switcher( $id, $label, $default = 'yes', $extra = array() ) {
		$this->add_control(
			$id,
			array_merge(
				array(
					'label'        => $label,
					'type'         => Controls_Manager::SWITCHER,
					'return_value' => 'yes',
					'default'      => $default,
				),
				$extra
			)
		);
	}

	/** Heading + typography + color for one text element. */
	private function text_style( $key, $label, $selector, $first = false, $condition = array() ) {
		$cond = empty( $condition ) ? array() : array( 'condition' => $condition );

		$this->add_control(
			$key . '_heading',
			array_merge(
				array(
					'label'     => $label,
					'type'      => Controls_Manager::HEADING,
					'separator' => $first ? 'none' : 'before',
				),
				$cond
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array_merge(
				array(
					'name'     => $key . '_typography',
					'selector' => '{{WRAPPER}} ' . $selector,
				),
				$cond
			)
		);

		$this->add_control(
			$key . '_color',
			array_merge(
				array(
					'label'     => esc_html__( 'Color', 'super-widget' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( '{{WRAPPER}} ' . $selector => 'color: {{VALUE}};' ),
				),
				$cond
			)
		);
	}

	/* ---------------------------- Content ---------------------------- */

	private function controls_reviews() {
		$this->start_controls_section(
			'section_reviews',
			array(
				'label' => esc_html__( 'Reviews', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'image',
			array(
				'label'       => esc_html__( 'Photo (optional)', 'super-widget' ),
				'type'        => Controls_Manager::MEDIA,
				'description' => esc_html__( 'Without a photo the avatar icon below is shown.', 'super-widget' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'name',
			array(
				'label'       => esc_html__( 'Name', 'super-widget' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Sarah M.',
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'date',
			array(
				'label'       => esc_html__( 'Date / small text', 'super-widget' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Oct 12, 2026',
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
			'review',
			array(
				'label'   => esc_html__( 'Review', 'super-widget' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 5,
				'default' => esc_html__( 'The friendliest dental office I\'ve ever visited. My kids actually ask when the next appointment is! The team is patient, gentle, and truly caring.', 'super-widget' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'link',
			array(
				'label'       => esc_html__( 'Link on the name (optional)', 'super-widget' ),
				'type'        => Controls_Manager::URL,
				'placeholder' => 'https://',
				'dynamic'     => array( 'active' => true ),
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
						'name'   => 'Sarah M.',
						'date'   => 'Oct 12, 2026',
						'review' => 'The friendliest dental office I\'ve ever visited. My kids actually ask when the next appointment is! The team is patient, gentle, and truly caring.',
					),
					array(
						'name'   => 'James R.',
						'date'   => 'Sep 28, 2026',
						'review' => 'The doctor explained every step of my aligner plan and the results exceeded my expectations. Worth every minute of the commute.',
					),
					array(
						'name'   => 'Priya K.',
						'date'   => 'Sep 15, 2026',
						'review' => 'Beautiful, calming office and zero wait time. My whitening treatment was painless and my smile has never looked better.',
					),
					array(
						'name'   => 'Daniel T.',
						'date'   => 'Aug 30, 2026',
						'review' => 'Booked a same-day emergency visit and they took incredible care of me. I\'ve moved my whole family\'s dental care here.',
					),
				),
			)
		);

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			array(
				'name'      => 'photo',
				'default'   => 'thumbnail',
				'exclude'   => array( 'custom' ),
				'separator' => 'before',
			)
		);

		$this->switcher( 'show_avatar', esc_html__( 'Avatar', 'super-widget' ) );

		$this->add_control(
			'avatar_icon',
			array(
				'label'       => esc_html__( 'Avatar icon (when no photo)', 'super-widget' ),
				'type'        => Controls_Manager::ICONS,
				'default'     => array( 'value' => 'fab fa-google', 'library' => 'fa-brands' ),
				'skin'        => 'inline',
				'label_block' => false,
				'condition'   => array( 'show_avatar' => 'yes' ),
			)
		);

		$this->switcher( 'show_date', esc_html__( 'Date / small text', 'super-widget' ) );
		$this->switcher( 'show_stars', esc_html__( 'Star rating', 'super-widget' ) );
		$this->switcher( 'show_quote', esc_html__( 'Quote icon', 'super-widget' ) );

		$this->add_control(
			'quote_icon',
			array(
				'label'       => esc_html__( 'Quote icon', 'super-widget' ),
				'type'        => Controls_Manager::ICONS,
				'default'     => array( 'value' => 'fas fa-quote-right', 'library' => 'fa-solid' ),
				'skin'        => 'inline',
				'label_block' => false,
				'condition'   => array( 'show_quote' => 'yes' ),
			)
		);

		$this->add_control(
			'name_tag',
			array(
				'label'   => esc_html__( 'Name HTML tag', 'super-widget' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'span',
				'options' => array(
					'h3'   => 'H3',
					'h4'   => 'H4',
					'h5'   => 'H5',
					'h6'   => 'H6',
					'div'  => 'div',
					'span' => 'span',
					'p'    => 'p',
				),
			)
		);

		$this->end_controls_section();
	}

	private function controls_layout() {
		$this->start_controls_section(
			'section_layout',
			array(
				'label' => esc_html__( 'Grid Layout', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_responsive_control(
			'columns',
			array(
				'label'          => esc_html__( 'Columns', 'super-widget' ),
				'type'           => Controls_Manager::SELECT,
				'default'        => '2',
				'tablet_default' => '2',
				'mobile_default' => '1',
				'options'        => array(
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
				),
				'selectors'      => array( '{{WRAPPER}} .spw-t3' => '--t3-cols: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'column_gap',
			array(
				'label'          => esc_html__( 'Column gap', 'super-widget' ),
				'type'           => Controls_Manager::SLIDER,
				'range'          => array( 'px' => array( 'min' => 0, 'max' => 100 ) ),
				'default'        => array( 'size' => 24 ),
				'tablet_default' => array( 'size' => 20 ),
				'mobile_default' => array( 'size' => 16 ),
				'selectors'      => array( '{{WRAPPER}} .spw-t3' => '--t3-gap-x: {{SIZE}}px;' ),
			)
		);

		$this->add_responsive_control(
			'row_gap',
			array(
				'label'          => esc_html__( 'Row gap', 'super-widget' ),
				'type'           => Controls_Manager::SLIDER,
				'range'          => array( 'px' => array( 'min' => 0, 'max' => 100 ) ),
				'default'        => array( 'size' => 24 ),
				'tablet_default' => array( 'size' => 20 ),
				'mobile_default' => array( 'size' => 16 ),
				'selectors'      => array( '{{WRAPPER}} .spw-t3' => '--t3-gap-y: {{SIZE}}px;' ),
			)
		);

		$this->switcher(
			'equal_height',
			esc_html__( 'Equal height cards', 'super-widget' ),
			'yes',
			array(
				'separator'   => 'before',
				'description' => esc_html__( 'Cards in the same row share the height of the tallest one.', 'super-widget' ),
			)
		);

		$this->add_control(
			'quote_place',
			array(
				'label'     => esc_html__( 'Quote icon position', 'super-widget' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'side',
				'options'   => array(
					'side' => esc_html__( 'Beside the review', 'super-widget' ),
					'top'  => esc_html__( 'Above the review', 'super-widget' ),
				),
				'condition' => array( 'show_quote' => 'yes' ),
			)
		);

		$this->switcher( 'limit_text', esc_html__( 'Limit review length', 'super-widget' ), '', array( 'separator' => 'before' ) );

		$this->add_responsive_control(
			'text_lines',
			array(
				'label'     => esc_html__( 'Max lines', 'super-widget' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 4,
				'min'       => 1,
				'max'       => 20,
				'selectors' => array( '{{WRAPPER}} .spw-t3' => '--t3-lines: {{VALUE}};' ),
				'condition' => array( 'limit_text' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_bar() {
		$this->start_controls_section(
			'section_bar',
			array(
				'label' => esc_html__( 'Summary Bar', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->switcher( 'show_bar', esc_html__( 'Show summary bar', 'super-widget' ) );

		$this->add_control(
			'bar_position',
			array(
				'label'     => esc_html__( 'Position', 'super-widget' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'bottom',
				'options'   => array(
					'bottom' => esc_html__( 'Below the reviews', 'super-widget' ),
					'top'    => esc_html__( 'Above the reviews', 'super-widget' ),
				),
				'condition' => array( 'show_bar' => 'yes' ),
			)
		);

		$this->add_control(
			'bar_title',
			array(
				'label'       => esc_html__( 'Title', 'super-widget' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( '5 Star Customer Reviews', 'super-widget' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_bar' => 'yes' ),
			)
		);

		$this->add_control(
			'bar_title_tag',
			array(
				'label'     => esc_html__( 'Title HTML tag', 'super-widget' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'h3',
				'options'   => array(
					'h2'   => 'H2',
					'h3'   => 'H3',
					'h4'   => 'H4',
					'h5'   => 'H5',
					'div'  => 'div',
					'p'    => 'p',
					'span' => 'span',
				),
				'condition' => array( 'show_bar' => 'yes' ),
			)
		);

		$this->switcher( 'bar_show_stars', esc_html__( 'Stars', 'super-widget' ), 'yes', array( 'condition' => array( 'show_bar' => 'yes' ) ) );

		$this->add_control(
			'bar_rating',
			array(
				'label'     => esc_html__( 'Rating', 'super-widget' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 5,
				'min'       => 0,
				'max'       => 5,
				'step'      => 0.1,
				'condition' => array( 'show_bar' => 'yes', 'bar_show_stars' => 'yes' ),
			)
		);

		$this->add_control(
			'bar_rating_text',
			array(
				'label'       => esc_html__( 'Text next to the stars', 'super-widget' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => esc_html__( '4.9 from 120 reviews', 'super-widget' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_bar' => 'yes', 'bar_show_stars' => 'yes' ),
			)
		);

		$this->switcher( 'bar_show_button', esc_html__( 'Button', 'super-widget' ), 'yes', array( 'separator' => 'before', 'condition' => array( 'show_bar' => 'yes' ) ) );

		$this->add_control(
			'bar_button_text',
			array(
				'label'     => esc_html__( 'Button text', 'super-widget' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'See All Reviews', 'super-widget' ),
				'dynamic'   => array( 'active' => true ),
				'condition' => array( 'show_bar' => 'yes', 'bar_show_button' => 'yes' ),
			)
		);

		$this->add_control(
			'bar_button_link',
			array(
				'label'       => esc_html__( 'Button link', 'super-widget' ),
				'type'        => Controls_Manager::URL,
				'placeholder' => 'https://',
				'default'     => array( 'url' => '#' ),
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_bar' => 'yes', 'bar_show_button' => 'yes' ),
			)
		);

		$this->add_control(
			'bar_button_icon',
			array(
				'label'       => esc_html__( 'Button icon', 'super-widget' ),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'label_block' => false,
				'condition'   => array( 'show_bar' => 'yes', 'bar_show_button' => 'yes' ),
			)
		);

		$this->add_control(
			'bar_button_icon_pos',
			array(
				'label'     => esc_html__( 'Icon position', 'super-widget' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'after',
				'options'   => array(
					'before' => esc_html__( 'Before', 'super-widget' ),
					'after'  => esc_html__( 'After', 'super-widget' ),
				),
				'condition' => array( 'show_bar' => 'yes', 'bar_show_button' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'bar_layout',
			array(
				'label'                => esc_html__( 'Bar layout', 'super-widget' ),
				'type'                 => Controls_Manager::SELECT,
				'default'              => 'spread',
				'tablet_default'       => 'spread',
				'mobile_default'       => 'stack',
				'separator'            => 'before',
				'options'              => array(
					'spread' => esc_html__( 'Title left, stars center, button right', 'super-widget' ),
					'stack'  => esc_html__( 'Stacked and centered', 'super-widget' ),
					'start'  => esc_html__( 'Stacked and left', 'super-widget' ),
				),
				'selectors_dictionary' => array(
					'spread' => '--t3-bar-cols: 1fr auto 1fr; --t3-bar-self: start; --t3-bar-end: end; --t3-bar-mid: center; --t3-bar-text: left; --t3-spacer: block;',
					'stack'  => '--t3-bar-cols: minmax(0, 1fr); --t3-bar-self: center; --t3-bar-end: center; --t3-bar-mid: center; --t3-bar-text: center; --t3-spacer: none;',
					'start'  => '--t3-bar-cols: minmax(0, 1fr); --t3-bar-self: start; --t3-bar-end: start; --t3-bar-mid: start; --t3-bar-text: left; --t3-spacer: none;',
				),
				'selectors'            => array( '{{WRAPPER}} .spw-t3' => '{{VALUE}}' ),
				'condition'            => array( 'show_bar' => 'yes' ),
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

		$this->add_control(
			'entrance_animation',
			array(
				'label'   => esc_html__( 'Entrance animation', 'super-widget' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'fade-up',
				'options' => $this->entrance_options(),
			)
		);

		$this->switcher( 'animate_once', esc_html__( 'Animate only once', 'super-widget' ), 'yes', array( 'condition' => array( 'entrance_animation!' => 'none' ) ) );

		$this->add_control(
			'animation_duration',
			array(
				'label'     => esc_html__( 'Duration (ms)', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 100, 'max' => 2000, 'step' => 50 ) ),
				'default'   => array( 'size' => 600 ),
				'condition' => array( 'entrance_animation!' => 'none' ),
				'selectors' => array( '{{WRAPPER}} .spw-t3' => '--t3-dur: {{SIZE}}ms;' ),
			)
		);

		$this->add_control(
			'animation_stagger',
			array(
				'label'     => esc_html__( 'Delay between cards (ms)', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 400, 'step' => 20 ) ),
				'default'   => array( 'size' => 100 ),
				'condition' => array( 'entrance_animation!' => 'none' ),
				'selectors' => array( '{{WRAPPER}} .spw-t3' => '--t3-stagger: {{SIZE}}ms;' ),
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
				'default'   => array( 'size' => 4 ),
				'condition' => array( 'hover_effect' => 'lift' ),
				'selectors' => array( '{{WRAPPER}} .spw-t3' => '--t3-lift: -{{SIZE}}px;' ),
			)
		);

		$this->end_controls_section();
	}

	private function entrance_options() {
		return array(
			'none'        => esc_html__( 'None', 'super-widget' ),
			'fade-in'     => esc_html__( 'Fade in', 'super-widget' ),
			'fade-up'     => esc_html__( 'Fade up', 'super-widget' ),
			'fade-down'   => esc_html__( 'Fade down', 'super-widget' ),
			'slide-left'  => esc_html__( 'Slide from left', 'super-widget' ),
			'slide-right' => esc_html__( 'Slide from right', 'super-widget' ),
			'zoom-in'     => esc_html__( 'Zoom in', 'super-widget' ),
		);
	}

	/* ----------------------------- Style ----------------------------- */

	private function style_section() {
		$root = '{{WRAPPER}} .spw-t3';

		$this->start_controls_section(
			'style_section',
			array(
				'label' => esc_html__( 'Section', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'accent',
			array(
				'label'       => esc_html__( 'Accent color', 'super-widget' ),
				'type'        => Controls_Manager::COLOR,
				'selectors'   => array( $root => '--t3-accent: {{VALUE}};' ),
				'description' => esc_html__( 'Button and quote icon. Each part can still be set on its own.', 'super-widget' ),
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'section_background',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => $root,
			)
		);

		$this->add_responsive_control(
			'section_padding',
			array(
				'label'      => esc_html__( 'Padding', 'super-widget' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array( $root => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'section_radius',
			array(
				'label'      => esc_html__( 'Border radius', 'super-widget' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array( $root => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	private function style_cards() {
		$card = '{{WRAPPER}} .spw-t3__card';

		$this->start_controls_section(
			'style_cards',
			array(
				'label' => esc_html__( 'Cards', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'card_background',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => $card,
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
				'selectors'  => array( $card => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'card_padding',
			array(
				'label'      => esc_html__( 'Padding', 'super-widget' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array( $card => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'card_inner_gap',
			array(
				'label'     => esc_html__( 'Space between header and review', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
				'default'   => array( 'size' => 18 ),
				'selectors' => array( $card => 'gap: {{SIZE}}px;' ),
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
		$sel = '{{WRAPPER}} .spw-t3__avatar';

		$this->start_controls_section(
			'style_avatar',
			array(
				'label'     => esc_html__( 'Avatar', 'super-widget' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_avatar' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'avatar_size',
			array(
				'label'     => esc_html__( 'Size', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 24, 'max' => 120 ) ),
				'default'   => array( 'size' => 44 ),
				'selectors' => array( '{{WRAPPER}} .spw-t3' => '--t3-av: {{SIZE}}px;' ),
			)
		);

		$this->add_responsive_control(
			'avatar_icon_size',
			array(
				'label'     => esc_html__( 'Icon size', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 8, 'max' => 60 ) ),
				'default'   => array( 'size' => 20 ),
				'selectors' => array( $sel => 'font-size: {{SIZE}}px;' ),
			)
		);

		$this->add_control(
			'avatar_icon_color',
			array(
				'label'     => esc_html__( 'Icon color', 'super-widget' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( $sel => 'color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'avatar_bg',
			array(
				'label'     => esc_html__( 'Background', 'super-widget' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( $sel => 'background-color: {{VALUE}};' ),
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
			'avatar_radius',
			array(
				'label'      => esc_html__( 'Border radius', 'super-widget' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ), '%' => array( 'min' => 0, 'max' => 50 ) ),
				'default'    => array( 'size' => 50, 'unit' => '%' ),
				'selectors'  => array( $sel => 'border-radius: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'avatar_gap',
			array(
				'label'     => esc_html__( 'Space to the name', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'default'   => array( 'size' => 12 ),
				'selectors' => array( '{{WRAPPER}} .spw-t3__who' => 'gap: {{SIZE}}px;' ),
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

		$this->text_style( 'name', esc_html__( 'Name', 'super-widget' ), '.spw-t3__name', true );
		$this->text_style( 'date', esc_html__( 'Date / small text', 'super-widget' ), '.spw-t3__date', false, array( 'show_date' => 'yes' ) );
		$this->text_style( 'review', esc_html__( 'Review', 'super-widget' ), '.spw-t3__text' );

		$this->add_responsive_control(
			'review_align',
			array(
				'label'     => esc_html__( 'Review alignment', 'super-widget' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'left'    => array( 'title' => esc_html__( 'Left', 'super-widget' ), 'icon' => 'eicon-text-align-left' ),
					'center'  => array( 'title' => esc_html__( 'Center', 'super-widget' ), 'icon' => 'eicon-text-align-center' ),
					'right'   => array( 'title' => esc_html__( 'Right', 'super-widget' ), 'icon' => 'eicon-text-align-right' ),
					'justify' => array( 'title' => esc_html__( 'Justify', 'super-widget' ), 'icon' => 'eicon-text-align-justify' ),
				),
				'selectors' => array( '{{WRAPPER}} .spw-t3__text' => 'text-align: {{VALUE}};' ),
			)
		);

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
				'range'     => array( 'px' => array( 'min' => 8, 'max' => 40 ) ),
				'default'   => array( 'size' => 16 ),
				'selectors' => array( '{{WRAPPER}} .spw-t3' => '--t3-star: {{SIZE}}px;' ),
			)
		);

		$this->add_control(
			'star_color',
			array(
				'label'     => esc_html__( 'Color', 'super-widget' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .spw-t3' => '--t3-star-on: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'star_empty',
			array(
				'label'     => esc_html__( 'Empty star color', 'super-widget' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .spw-t3' => '--t3-star-off: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'star_space',
			array(
				'label'     => esc_html__( 'Space between stars', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 20 ) ),
				'default'   => array( 'size' => 0 ),
				'selectors' => array( '{{WRAPPER}} .spw-t3' => '--t3-star-gap: {{SIZE}}px;' ),
			)
		);

		$this->add_control(
			'star_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Cards and the summary bar share these star settings. The bar has its own size in the Summary Bar style section.', 'super-widget' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);

		$this->end_controls_section();
	}

	private function style_quote() {
		$sel = '{{WRAPPER}} .spw-t3__q';

		$this->start_controls_section(
			'style_quote',
			array(
				'label'     => esc_html__( 'Quote Icon', 'super-widget' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_quote' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'quote_size',
			array(
				'label'     => esc_html__( 'Size', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 8, 'max' => 80 ) ),
				'default'   => array( 'size' => 16 ),
				'selectors' => array( $sel => 'font-size: {{SIZE}}px;' ),
			)
		);

		$this->add_control(
			'quote_color',
			array(
				'label'     => esc_html__( 'Color', 'super-widget' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( $sel => 'color: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'quote_gap',
			array(
				'label'     => esc_html__( 'Space to the review', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'default'   => array( 'size' => 12 ),
				'selectors' => array( '{{WRAPPER}} .spw-t3__body' => 'gap: {{SIZE}}px;' ),
			)
		);

		$this->add_responsive_control(
			'quote_offset',
			array(
				'label'     => esc_html__( 'Vertical offset', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => -20, 'max' => 40 ) ),
				'default'   => array( 'size' => 4 ),
				'selectors' => array( $sel => 'margin-top: {{SIZE}}px;' ),
			)
		);

		$this->end_controls_section();
	}

	private function style_bar() {
		$bar = '{{WRAPPER}} .spw-t3__bar';

		$this->start_controls_section(
			'style_bar',
			array(
				'label'     => esc_html__( 'Summary Bar', 'super-widget' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_bar' => 'yes' ),
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'bar_background',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => $bar,
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'bar_border',
				'selector' => $bar,
			)
		);

		$this->add_responsive_control(
			'bar_radius',
			array(
				'label'      => esc_html__( 'Border radius', 'super-widget' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array( $bar => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'bar_padding',
			array(
				'label'      => esc_html__( 'Padding', 'super-widget' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array( $bar => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'bar_shadow',
				'selector' => $bar,
			)
		);

		$this->add_responsive_control(
			'bar_gap_out',
			array(
				'label'     => esc_html__( 'Space between bar and reviews', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 120 ) ),
				'default'   => array( 'size' => 24 ),
				'selectors' => array( '{{WRAPPER}} .spw-t3' => '--t3-bar-gap: {{SIZE}}px;' ),
			)
		);

		$this->add_responsive_control(
			'bar_gap_in',
			array(
				'label'     => esc_html__( 'Space between bar items', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
				'default'   => array( 'size' => 16 ),
				'selectors' => array( $bar => 'gap: {{SIZE}}px 24px;' ),
			)
		);

		$this->text_style( 'bar_title', esc_html__( 'Title', 'super-widget' ), '.spw-t3__bar-title', false );
		$this->text_style( 'bar_rtext', esc_html__( 'Text next to the stars', 'super-widget' ), '.spw-t3__bar-rtext', false, array( 'bar_rating_text!' => '' ) );

		$this->add_control(
			'bar_stars_heading',
			array(
				'label'     => esc_html__( 'Bar stars', 'super-widget' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => array( 'bar_show_stars' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'bar_star_size',
			array(
				'label'     => esc_html__( 'Size', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 8, 'max' => 48 ) ),
				'default'   => array( 'size' => 16 ),
				'selectors' => array( '{{WRAPPER}} .spw-t3' => '--t3-bar-star: {{SIZE}}px;' ),
				'condition' => array( 'bar_show_stars' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function style_button() {
		$btn   = '{{WRAPPER}} .spw-t3__btn';
		$hover = $btn . ':hover, ' . $btn . ':focus-visible';

		$this->start_controls_section(
			'style_button',
			array(
				'label'     => esc_html__( 'Button', 'super-widget' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_bar' => 'yes', 'bar_show_button' => 'yes' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'btn_typography',
				'selector' => $btn,
			)
		);

		$this->start_controls_tabs( 'btn_tabs' );

		$this->start_controls_tab( 'btn_tab_normal', array( 'label' => esc_html__( 'Normal', 'super-widget' ) ) );
		$this->add_control( 'btn_color', array(
			'label'     => esc_html__( 'Text color', 'super-widget' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( $btn => 'color: {{VALUE}};' ),
		) );
		$this->add_control( 'btn_bg', array(
			'label'     => esc_html__( 'Background', 'super-widget' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( $btn => 'background-color: {{VALUE}};' ),
		) );
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'btn_shadow',
				'selector' => $btn,
			)
		);
		$this->end_controls_tab();

		$this->start_controls_tab( 'btn_tab_hover', array( 'label' => esc_html__( 'Hover', 'super-widget' ) ) );
		$this->add_control( 'btn_hover_color', array(
			'label'     => esc_html__( 'Text color', 'super-widget' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( $hover => 'color: {{VALUE}};' ),
		) );
		$this->add_control( 'btn_hover_bg', array(
			'label'     => esc_html__( 'Background', 'super-widget' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( $hover => 'background-color: {{VALUE}};' ),
		) );
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'btn_shadow_hover',
				'selector' => $btn . ':hover',
			)
		);
		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'      => 'btn_border',
				'selector'  => $btn,
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'btn_radius',
			array(
				'label'      => esc_html__( 'Border radius', 'super-widget' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array( $btn => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'btn_padding',
			array(
				'label'      => esc_html__( 'Padding', 'super-widget' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array( $btn => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'btn_icon_size',
			array(
				'label'     => esc_html__( 'Icon size', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 8, 'max' => 40 ) ),
				'default'   => array( 'size' => 14 ),
				'selectors' => array( $btn . ' .spw-t3__btn-icon' => 'font-size: {{SIZE}}px;' ),
			)
		);

		$this->add_responsive_control(
			'btn_icon_gap',
			array(
				'label'     => esc_html__( 'Space between icon and text', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 30 ) ),
				'default'   => array( 'size' => 8 ),
				'selectors' => array( $btn => 'gap: {{SIZE}}px;' ),
			)
		);

		$this->end_controls_section();
	}

	/* ==================================================================
	 * Render
	 * ================================================================== */

	protected function render() {
		$settings = $this->get_settings_for_display();
		$items    = ! empty( $settings['items'] ) && is_array( $settings['items'] ) ? $settings['items'] : array();

		$yes = function ( $key ) use ( $settings ) {
			return isset( $settings[ $key ] ) && 'yes' === $settings[ $key ];
		};

		$show_bar = $yes( 'show_bar' );

		if ( empty( $items ) && ! $show_bar ) {
			return;
		}

		$entrance = isset( $settings['entrance_animation'] ) ? (string) $settings['entrance_animation'] : 'none';
		if ( ! array_key_exists( $entrance, $this->entrance_options() ) ) {
			$entrance = 'none';
		}

		$hover = isset( $settings['hover_effect'] ) && 'lift' === $settings['hover_effect'] ? 'lift' : 'none';

		// Global switch from Super Widget > Settings.
		if ( Super_Widget_Settings::get( 'disable_animations' ) ) {
			$entrance = 'none';
			$hover    = 'none';
		}

		$classes = array( 'spw-t3' );
		if ( 'none' !== $entrance ) {
			$classes[] = 'spw-t3--anim-' . $entrance;
		}
		if ( 'lift' === $hover ) {
			$classes[] = 'spw-t3--lift';
		}
		if ( ! $yes( 'equal_height' ) ) {
			$classes[] = 'spw-t3--top';
		}
		if ( $yes( 'limit_text' ) ) {
			$classes[] = 'spw-t3--clamp';
		}
		if ( $yes( 'show_quote' ) && isset( $settings['quote_place'] ) && 'top' === $settings['quote_place'] ) {
			$classes[] = 'spw-t3--q-top';
		}
		$bar_top = $show_bar && isset( $settings['bar_position'] ) && 'top' === $settings['bar_position'];
		if ( $bar_top ) {
			$classes[] = 'spw-t3--bar-top';
		}

		$this->add_render_attribute(
			'root',
			array(
				'class'     => $classes,
				'data-once' => $yes( 'animate_once' ) ? 'yes' : 'no',
			)
		);

		$name_tag = isset( $settings['name_tag'] ) ? (string) $settings['name_tag'] : 'span';
		if ( ! in_array( $name_tag, array( 'h3', 'h4', 'h5', 'h6', 'div', 'span', 'p' ), true ) ) {
			$name_tag = 'span';
		}
		?>
		<div <?php $this->print_render_attribute_string( 'root' ); ?>>
			<?php
			if ( $bar_top ) {
				$this->render_bar( $settings );
			}
			?>

			<?php if ( ! empty( $items ) ) : ?>
				<div class="spw-t3__grid">
					<?php foreach ( $items as $i => $item ) : ?>
						<?php $this->render_card( $item, (int) $i, $settings, $name_tag ); ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php
			if ( $show_bar && ! $bar_top ) {
				$this->render_bar( $settings );
			}
			?>
		</div>
		<?php
	}

	private function stars_html( $rating, $extra_class = '' ) {
		$rating = max( 0, min( 5, (float) $rating ) );

		return sprintf(
			'<span class="spw-t3__stars%1$s" style="--r:%2$s" role="img" aria-label="%3$s"></span>',
			'' !== $extra_class ? ' ' . esc_attr( $extra_class ) : '',
			esc_attr( (string) $rating ),
			/* translators: %s: rating */
			esc_attr( sprintf( __( 'Rated %s out of 5', 'super-widget' ), number_format_i18n( $rating, 1 ) ) )
		);
	}

	private function render_card( $item, $index, $settings, $name_tag ) {
		$yes = function ( $key ) use ( $settings ) {
			return isset( $settings[ $key ] ) && 'yes' === $settings[ $key ];
		};

		$name   = isset( $item['name'] ) ? trim( (string) $item['name'] ) : '';
		$date   = isset( $item['date'] ) ? trim( (string) $item['date'] ) : '';
		$review = isset( $item['review'] ) ? (string) $item['review'] : '';
		$rating = isset( $item['rating'] ) && is_numeric( $item['rating'] ) ? (float) $item['rating'] : 5;

		$has_image = ! empty( $item['image']['url'] ) || ! empty( $item['image']['id'] );
		$image     = '';
		if ( $has_image ) {
			$image = Group_Control_Image_Size::get_attachment_image_html(
				array(
					'image'      => $item['image'],
					'photo_size' => isset( $settings['photo_size'] ) ? $settings['photo_size'] : 'thumbnail',
				),
				'photo',
				'image'
			);
		}

		$link_key = 'link_' . $index;
		$has_link = ! empty( $item['link']['url'] );
		if ( $has_link ) {
			$this->add_link_attributes( $link_key, $item['link'] );
		}
		?>
		<article class="spw-t3__card">
			<div class="spw-t3__head">
				<div class="spw-t3__who">
					<?php if ( $yes( 'show_avatar' ) ) : ?>
						<span class="spw-t3__avatar">
							<?php if ( '' !== $image ) : ?>
								<?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php elseif ( ! empty( $settings['avatar_icon']['value'] ) ) : ?>
								<?php Icons_Manager::render_icon( $settings['avatar_icon'], array( 'aria-hidden' => 'true' ) ); ?>
							<?php else : ?>
								<span class="spw-t3__letter" aria-hidden="true"><?php echo esc_html( function_exists( 'mb_substr' ) ? mb_strtoupper( mb_substr( $name, 0, 1 ) ) : strtoupper( substr( $name, 0, 1 ) ) ); ?></span>
							<?php endif; ?>
						</span>
					<?php endif; ?>

					<span class="spw-t3__meta">
						<?php if ( '' !== $name ) : ?>
							<<?php echo esc_attr( $name_tag ); ?> class="spw-t3__name">
								<?php if ( $has_link ) : ?>
									<a <?php $this->print_render_attribute_string( $link_key ); ?>><?php echo esc_html( $name ); ?></a>
								<?php else : ?>
									<?php echo esc_html( $name ); ?>
								<?php endif; ?>
							</<?php echo esc_attr( $name_tag ); ?>>
						<?php endif; ?>
						<?php if ( $yes( 'show_date' ) && '' !== $date ) : ?>
							<span class="spw-t3__date"><?php echo esc_html( $date ); ?></span>
						<?php endif; ?>
					</span>
				</div>

				<?php if ( $yes( 'show_stars' ) ) : ?>
					<?php echo $this->stars_html( $rating ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endif; ?>
			</div>

			<?php if ( '' !== trim( $review ) ) : ?>
				<div class="spw-t3__body">
					<?php if ( $yes( 'show_quote' ) && ! empty( $settings['quote_icon']['value'] ) ) : ?>
						<span class="spw-t3__q" aria-hidden="true"><?php Icons_Manager::render_icon( $settings['quote_icon'], array( 'aria-hidden' => 'true' ) ); ?></span>
					<?php endif; ?>
					<blockquote class="spw-t3__text"><?php echo esc_html( $review ); ?></blockquote>
				</div>
			<?php endif; ?>
		</article>
		<?php
	}

	private function render_bar( $settings ) {
		$yes = function ( $key ) use ( $settings ) {
			return isset( $settings[ $key ] ) && 'yes' === $settings[ $key ];
		};

		$tag = isset( $settings['bar_title_tag'] ) ? (string) $settings['bar_title_tag'] : 'h3';
		if ( ! in_array( $tag, array( 'h2', 'h3', 'h4', 'h5', 'div', 'p', 'span' ), true ) ) {
			$tag = 'h3';
		}

		$show_stars  = $yes( 'bar_show_stars' );
		$show_button = $yes( 'bar_show_button' ) && ! empty( $settings['bar_button_text'] );
		$rating      = isset( $settings['bar_rating'] ) && is_numeric( $settings['bar_rating'] ) ? (float) $settings['bar_rating'] : 5;
		$rtext       = isset( $settings['bar_rating_text'] ) ? trim( (string) $settings['bar_rating_text'] ) : '';
		$title       = isset( $settings['bar_title'] ) ? trim( (string) $settings['bar_title'] ) : '';

		if ( '' === $title && ! $show_stars && ! $show_button ) {
			return;
		}

		$has_link = $show_button && ! empty( $settings['bar_button_link']['url'] );
		if ( $has_link ) {
			$this->add_link_attributes( 'bar_btn', $settings['bar_button_link'] );
		}
		$this->add_render_attribute( 'bar_btn', 'class', 'spw-t3__btn' );

		$icon     = isset( $settings['bar_button_icon'] ) ? $settings['bar_button_icon'] : array();
		$icon_pos = isset( $settings['bar_button_icon_pos'] ) && 'before' === $settings['bar_button_icon_pos'] ? 'before' : 'after';
		?>
		<div class="spw-t3__bar">
			<?php if ( '' !== $title ) : ?>
				<<?php echo esc_attr( $tag ); ?> class="spw-t3__bar-title"><?php echo esc_html( $title ); ?></<?php echo esc_attr( $tag ); ?>>
			<?php else : ?>
				<span aria-hidden="true"></span>
			<?php endif; ?>

			<?php if ( $show_stars ) : ?>
				<span class="spw-t3__bar-mid">
					<?php echo $this->stars_html( $rating, 'spw-t3__stars--bar' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php if ( '' !== $rtext ) : ?>
						<span class="spw-t3__bar-rtext"><?php echo esc_html( $rtext ); ?></span>
					<?php endif; ?>
				</span>
			<?php else : ?>
				<span aria-hidden="true"></span>
			<?php endif; ?>

			<?php if ( $show_button ) : ?>
				<<?php echo $has_link ? 'a' : 'span'; ?> <?php $this->print_render_attribute_string( 'bar_btn' ); ?>>
					<?php if ( 'before' === $icon_pos && ! empty( $icon['value'] ) ) : ?>
						<span class="spw-t3__btn-icon"><?php Icons_Manager::render_icon( $icon, array( 'aria-hidden' => 'true' ) ); ?></span>
					<?php endif; ?>
					<span class="spw-t3__btn-text"><?php echo esc_html( $settings['bar_button_text'] ); ?></span>
					<?php if ( 'after' === $icon_pos && ! empty( $icon['value'] ) ) : ?>
						<span class="spw-t3__btn-icon"><?php Icons_Manager::render_icon( $icon, array( 'aria-hidden' => 'true' ) ); ?></span>
					<?php endif; ?>
				</<?php echo $has_link ? 'a' : 'span'; ?>>
			<?php else : ?>
				<span aria-hidden="true"></span>
			<?php endif; ?>
		</div>
		<?php
	}
}
