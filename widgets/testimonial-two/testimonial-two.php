<?php
/**
 * Testimonial 2 widget: one testimonial at a time.
 *
 * All slides share one grid cell, so the slider is always as tall as the
 * tallest slide (equal height) and the photo, text, name and role sit at the
 * same position in every slide.
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
use Elementor\Utils;
use Elementor\Widget_Base;

class SPW_Testimonial_Two_Widget extends Widget_Base {

	public function get_name() {
		return 'spw-testimonial-two';
	}

	public function get_title() {
		return esc_html( Super_Widget_Settings::widget_title( 'testimonial-two', __( 'Testimonial 2', 'super-widget' ) ) );
	}

	public function get_icon() {
		return 'eicon-testimonial';
	}

	public function get_categories() {
		return array( 'super-widget' );
	}

	public function get_keywords() {
		return array( 'testimonial', 'review', 'slider', 'quote', 'rating', 'client' );
	}

	public function get_style_depends() {
		return array( 'spw-global', 'spw-testimonial-two' );
	}

	public function get_script_depends() {
		return array( 'spw-testimonial-two' );
	}

	/* ==================================================================
	 * Controls
	 * ================================================================== */

	protected function register_controls() {
		$this->controls_header();
		$this->controls_items();
		$this->controls_layout();
		$this->controls_slider();
		$this->controls_motion();

		$this->style_panel();
		$this->style_header();
		$this->style_photo();
		$this->style_text();
		$this->style_quote_icons();
		$this->style_arrows();
		$this->style_dots();
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
	private function text_style( $key, $label, $selector, $first = false ) {
		$this->add_control(
			$key . '_heading',
			array(
				'label'     => $label,
				'type'      => Controls_Manager::HEADING,
				'separator' => $first ? 'none' : 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => $key . '_typography',
				'selector' => '{{WRAPPER}} ' . $selector,
			)
		);

		$this->add_control(
			$key . '_color',
			array(
				'label'     => esc_html__( 'Color', 'super-widget' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} ' . $selector => 'color: {{VALUE}};' ),
			)
		);
	}

	/* ---------------------------- Content ---------------------------- */

	private function controls_header() {
		$this->start_controls_section(
			'section_header',
			array(
				'label' => esc_html__( 'Heading', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->switcher( 'show_header', esc_html__( 'Show heading area', 'super-widget' ) );

		$this->switcher( 'show_line', esc_html__( 'Accent line', 'super-widget' ), 'yes', array( 'condition' => array( 'show_header' => 'yes' ) ) );

		$this->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Title', 'super-widget' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Testimonials.', 'super-widget' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_header' => 'yes' ),
			)
		);

		$this->add_control(
			'subtitle',
			array(
				'label'       => esc_html__( 'Subtitle', 'super-widget' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 2,
				'default'     => esc_html__( 'Lorem Ipsum is simply dummy text', 'super-widget' ),
				'dynamic'     => array( 'active' => true ),
				'condition'   => array( 'show_header' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'header_align',
			array(
				'label'     => esc_html__( 'Alignment', 'super-widget' ),
				'type'      => Controls_Manager::CHOOSE,
				'default'   => 'center',
				'options'   => array(
					'left'   => array( 'title' => esc_html__( 'Left', 'super-widget' ), 'icon' => 'eicon-text-align-left' ),
					'center' => array( 'title' => esc_html__( 'Center', 'super-widget' ), 'icon' => 'eicon-text-align-center' ),
					'right'  => array( 'title' => esc_html__( 'Right', 'super-widget' ), 'icon' => 'eicon-text-align-right' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .spw-tc__head' => 'text-align: {{VALUE}};',
					'{{WRAPPER}} .spw-tc__line' => 'margin-inline: var(--spw-m-{{VALUE}});',
				),
				'condition' => array( 'show_header' => 'yes' ),
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
				'default'     => 'Gloria Morrison',
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'role',
			array(
				'label'       => esc_html__( 'Role / company', 'super-widget' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Lorem and ipsum',
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'rating',
			array(
				'label'       => esc_html__( 'Rating (shown when stars are on)', 'super-widget' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 5,
				'min'         => 0,
				'max'         => 5,
				'step'        => 0.1,
			)
		);

		$repeater->add_control(
			'quote',
			array(
				'label'   => esc_html__( 'Review', 'super-widget' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 6,
				'default' => esc_html__( 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.', 'super-widget' ),
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
					array( 'name' => 'Gloria Morrison', 'role' => 'Lorem and ipsum' ),
					array( 'name' => 'Daniel Carter', 'role' => 'Product designer' ),
					array( 'name' => 'Aisha Rahman', 'role' => 'Marketing lead' ),
					array( 'name' => 'Marco Rossi', 'role' => 'Founder' ),
				),
			)
		);

		$this->switcher( 'show_quote_icons', esc_html__( 'Quote marks', 'super-widget' ) );
		$this->switcher( 'show_stars', esc_html__( 'Star rating', 'super-widget' ), '' );
		$this->switcher( 'show_deco', esc_html__( 'Decorative circles behind photo', 'super-widget' ) );

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
				'default'        => '1',
				'tablet_default' => '1',
				'mobile_default' => '1',
				'options'        => array(
					'1' => '1',
					'2' => '2',
					'3' => '3',
				),
				'selectors'      => array( '{{WRAPPER}} .spw-tc' => '--spw-v: {{VALUE}};' ),
				// Re-render in the editor so the slides are re-placed at once.
				'render_type'    => 'template',
				'description'    => esc_html__( 'How many testimonials are visible at once. With 2 or more, set Layout to "Photo above text".', 'super-widget' ),
			)
		);

		$this->add_responsive_control(
			'column_gap',
			array(
				'label'          => esc_html__( 'Column gap', 'super-widget' ),
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => array( 'px' ),
				'range'          => array( 'px' => array( 'min' => 0, 'max' => 120 ) ),
				'default'        => array( 'size' => 30, 'unit' => 'px' ),
				'tablet_default' => array( 'size' => 24, 'unit' => 'px' ),
				'mobile_default' => array( 'size' => 16, 'unit' => 'px' ),
				'selectors'      => array( '{{WRAPPER}} .spw-tc' => '--spw-cgap: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'row_gap',
			array(
				'label'          => esc_html__( 'Row gap (photo above text)', 'super-widget' ),
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => array( 'px' ),
				'range'          => array( 'px' => array( 'min' => 0, 'max' => 120 ) ),
				'default'        => array( 'size' => 24, 'unit' => 'px' ),
				'tablet_default' => array( 'size' => 22, 'unit' => 'px' ),
				'mobile_default' => array( 'size' => 20, 'unit' => 'px' ),
				'selectors'      => array( '{{WRAPPER}} .spw-tc' => '--spw-row-gap: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			array(
				'name'      => 'photo',
				'default'   => 'medium',
				'exclude'   => array( 'custom' ),
			)
		);

		$this->add_control(
			'title_tag',
			array(
				'label'     => esc_html__( 'Title HTML tag', 'super-widget' ),
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
			)
		);

		$this->add_control(
			'name_tag',
			array(
				'label'   => esc_html__( 'Name HTML tag', 'super-widget' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'div',
				'options' => array(
					'h2'  => 'H2',
					'h3'  => 'H3',
					'h4'  => 'H4',
					'h5'  => 'H5',
					'div' => 'div',
					'p'   => 'p',
				),
			)
		);

		$this->add_control(
			'layout_heading',
			array(
				'label'     => esc_html__( 'Photo and text', 'super-widget' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'photo_side',
			array(
				'label'   => esc_html__( 'Photo position', 'super-widget' ),
				'type'    => Controls_Manager::CHOOSE,
				'default' => 'left',
				'toggle'  => false,
				'options' => array(
					'left'  => array( 'title' => esc_html__( 'Left', 'super-widget' ), 'icon' => 'eicon-h-align-left' ),
					'right' => array( 'title' => esc_html__( 'Right', 'super-widget' ), 'icon' => 'eicon-h-align-right' ),
				),
			)
		);

		$this->add_responsive_control(
			'photo_col',
			array(
				'label'      => esc_html__( 'Photo column width (%)', 'super-widget' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array( '%' => array( 'min' => 20, 'max' => 70 ) ),
				'default'    => array( 'size' => 45, 'unit' => '%' ),
				'selectors'  => array( '{{WRAPPER}} .spw-tc' => '--spw-photo-col: {{SIZE}}%;' ),
				'condition'  => array( 'layout_mode!' => 'stack' ),
			)
		);

		$this->add_responsive_control(
			'layout_mode',
			array(
				'label'                => esc_html__( 'Layout', 'super-widget' ),
				'type'                 => Controls_Manager::SELECT,
				'default'              => 'side',
				'tablet_default'       => 'side',
				'mobile_default'       => 'stack',
				'options'              => array(
					'side'  => esc_html__( 'Photo beside text', 'super-widget' ),
					'stack' => esc_html__( 'Photo above text', 'super-widget' ),
				),
				'selectors_dictionary' => array(
					'side'  => 'grid-template-columns: var(--spw-col-1) var(--spw-col-2); grid-template-rows: none; --spw-media-order: var(--spw-side-order);',
					'stack' => 'grid-template-columns: minmax(0, 1fr); grid-template-rows: auto 1fr; --spw-media-order: 0;',
				),
				'selectors'            => array( '{{WRAPPER}} .spw-tc__inner' => '{{VALUE}}' ),
			)
		);

		$this->add_responsive_control(
			'col_gap',
			array(
				'label'          => esc_html__( 'Column gap (photo beside text)', 'super-widget' ),
				'type'           => Controls_Manager::SLIDER,
				'range'          => array( 'px' => array( 'min' => 0, 'max' => 160 ) ),
				'default'        => array( 'size' => 40 ),
				'tablet_default' => array( 'size' => 28 ),
				'mobile_default' => array( 'size' => 20 ),
				'selectors'      => array( '{{WRAPPER}} .spw-tc' => '--spw-col-gap: {{SIZE}}px;' ),
			)
		);

		$this->add_responsive_control(
			'text_align',
			array(
				'label'          => esc_html__( 'Text alignment', 'super-widget' ),
				'type'           => Controls_Manager::CHOOSE,
				'default'        => 'left',
				'mobile_default' => 'center',
				'options'        => array(
					'left'   => array( 'title' => esc_html__( 'Left', 'super-widget' ), 'icon' => 'eicon-text-align-left' ),
					'center' => array( 'title' => esc_html__( 'Center', 'super-widget' ), 'icon' => 'eicon-text-align-center' ),
					'right'  => array( 'title' => esc_html__( 'Right', 'super-widget' ), 'icon' => 'eicon-text-align-right' ),
				),
				'selectors'      => array( '{{WRAPPER}} .spw-tc__content' => 'text-align: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'review_space',
			array(
				'label'       => esc_html__( 'Review space', 'super-widget' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'auto',
				'separator'   => 'before',
				'options'     => array(
					'auto'  => esc_html__( 'Match the longest review (no extra gap)', 'super-widget' ),
					'lines' => esc_html__( 'Reserve a fixed number of lines', 'super-widget' ),
				),
				'description' => esc_html__( 'Match: every slide is as tall as the longest review, name and role stay at the bottom, nothing is cut. Fixed: reserve N lines and cut longer reviews.', 'super-widget' ),
			)
		);

		$this->add_control(
			'text_lines',
			array(
				'label'       => esc_html__( 'Review lines (reserved)', 'super-widget' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 6,
				'min'         => 2,
				'max'         => 16,
				'condition'   => array( 'review_space' => 'lines' ),
				'selectors'   => array( '{{WRAPPER}} .spw-tc' => '--spw-lines: {{VALUE}};' ),
				'description' => esc_html__( 'Every slide reserves this much room, so photo, name and role never jump between slides. Longer reviews are cut.', 'super-widget' ),
			)
		);

		$this->add_control(
			'text_lines_narrow',
			array(
				'label'       => esc_html__( 'Review lines in narrow slides', 'super-widget' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 9,
				'min'         => 2,
				'max'         => 20,
				'condition'   => array( 'review_space' => 'lines' ),
				'selectors'   => array( '{{WRAPPER}} .spw-tc' => '--spw-lines-narrow: {{VALUE}};' ),
				'description' => esc_html__( 'When a slide is narrower than 520px (2-3 columns, small sections) the photo stacks above the text, the text centers and this many lines are reserved.', 'super-widget' ),
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
			'transition',
			array(
				'label'   => esc_html__( 'Transition', 'super-widget' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'slide',
				'options' => array(
					'slide' => esc_html__( 'Slide + fade', 'super-widget' ),
					'fade'  => esc_html__( 'Fade', 'super-widget' ),
					'zoom'  => esc_html__( 'Zoom + fade', 'super-widget' ),
				),
			)
		);

		$this->switcher( 'loop', esc_html__( 'Infinite loop', 'super-widget' ) );
		$this->switcher( 'swipe', esc_html__( 'Swipe / drag', 'super-widget' ) );
		$this->switcher( 'autoplay', esc_html__( 'Autoplay', 'super-widget' ), '' );

		$this->add_control(
			'autoplay_speed',
			array(
				'label'     => esc_html__( 'Autoplay delay (ms)', 'super-widget' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 5000,
				'min'       => 1500,
				'max'       => 30000,
				'step'      => 100,
				'condition' => array( 'autoplay' => 'yes' ),
			)
		);

		$this->switcher( 'pause_on_hover', esc_html__( 'Pause on hover', 'super-widget' ), 'yes', array( 'condition' => array( 'autoplay' => 'yes' ) ) );

		$this->switcher( 'show_arrows', esc_html__( 'Arrows', 'super-widget' ), 'yes', array( 'separator' => 'before', 'render_type' => 'template' ) );

		Super_Widget_Nav::add_arrow_control( $this, '.spw-tc', array( 'side-mid', 'side-mid', 'bottom-center' ) );

		$this->add_control(
			'prev_icon',
			array(
				'label'     => esc_html__( 'Previous icon', 'super-widget' ),
				'type'      => Controls_Manager::ICONS,
				'default'   => array( 'value' => 'fas fa-chevron-left', 'library' => 'fa-solid' ),
				'skin'      => 'inline',
				'label_block' => false,
				'condition' => array( 'show_arrows' => 'yes' ),
			)
		);

		$this->add_control(
			'next_icon',
			array(
				'label'     => esc_html__( 'Next icon', 'super-widget' ),
				'type'      => Controls_Manager::ICONS,
				'default'   => array( 'value' => 'fas fa-chevron-right', 'library' => 'fa-solid' ),
				'skin'      => 'inline',
				'label_block' => false,
				'condition' => array( 'show_arrows' => 'yes' ),
			)
		);

		$this->switcher( 'show_dots', esc_html__( 'Pill indicator', 'super-widget' ), 'yes', array( 'render_type' => 'template' ) );

		Super_Widget_Nav::add_dot_control( $this, '.spw-tc', 'bottom-center', esc_html__( 'Pill indicator position', 'super-widget' ) );

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
			'slide_duration',
			array(
				'label'     => esc_html__( 'Slide speed (ms)', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 150, 'max' => 1800, 'step' => 50 ) ),
				'default'   => array( 'size' => 800 ),
				'selectors' => array( '{{WRAPPER}} .spw-tc' => '--spw-dur: {{SIZE}}ms;' ),
			)
		);

		$this->add_control(
			'slide_easing',
			array(
				'label'     => esc_html__( 'Easing', 'super-widget' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'cubic-bezier(0.22, 1, 0.36, 1)',
				'options'   => array(
					'cubic-bezier(0.22, 1, 0.36, 1)'    => esc_html__( 'Smooth (glide and settle)', 'super-widget' ),
					'cubic-bezier(0.65, 0, 0.35, 1)'    => esc_html__( 'Soft (ease in-out)', 'super-widget' ),
					'cubic-bezier(0.34, 1.35, 0.64, 1)' => esc_html__( 'Springy', 'super-widget' ),
					'linear'                            => esc_html__( 'Linear', 'super-widget' ),
				),
				'selectors' => array( '{{WRAPPER}} .spw-tc' => '--spw-ease: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'slide_distance',
			array(
				'label'     => esc_html__( 'Slide distance (px)', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 240 ) ),
				'default'   => array( 'size' => 80 ),
				'condition' => array( 'transition' => 'slide' ),
				'selectors' => array( '{{WRAPPER}} .spw-tc' => '--spw-shift: {{SIZE}}px;' ),
			)
		);

		$this->switcher(
			'content_reveal',
			esc_html__( 'Content reveal', 'super-widget' ),
			'yes',
			array( 'description' => esc_html__( 'Photo pops in, then quote marks, review, name and role fade up one after another.', 'super-widget' ) )
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

	private function style_panel() {
		$panel = '{{WRAPPER}} .spw-tc';

		$this->start_controls_section(
			'style_panel',
			array(
				'label' => esc_html__( 'Panel', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'accent',
			array(
				'label'       => esc_html__( 'Accent color', 'super-widget' ),
				'type'        => Controls_Manager::COLOR,
				'selectors'   => array( $panel => '--tc-accent: {{VALUE}};' ),
				'description' => esc_html__( 'Used for the role, next arrow, pill and decorative circles unless you set those individually.', 'super-widget' ),
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'panel_background',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => $panel,
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'panel_border',
				'selector' => $panel,
			)
		);

		$this->add_responsive_control(
			'panel_radius',
			array(
				'label'      => esc_html__( 'Border radius', 'super-widget' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array( $panel => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'panel_shadow',
				'selector' => $panel,
			)
		);

		$this->add_responsive_control(
			'panel_padding',
			array(
				'label'      => esc_html__( 'Padding', 'super-widget' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array( $panel => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'stage_inset',
			array(
				'label'       => esc_html__( 'Space for side arrows (px)', 'super-widget' ),
				'type'        => Controls_Manager::SLIDER,
				'range'       => array( 'px' => array( 'min' => 0, 'max' => 200 ) ),
				'default'     => array( 'size' => 90 ),
				'tablet_default' => array( 'size' => 70 ),
				'mobile_default' => array( 'size' => 0 ),
				'selectors'   => array( '{{WRAPPER}} .spw-tc' => '--spw-inset: {{SIZE}}px;' ),
				'description' => esc_html__( 'Keeps the review text clear of the arrows.', 'super-widget' ),
			)
		);

		$this->end_controls_section();
	}

	private function style_header() {
		$this->start_controls_section(
			'style_header',
			array(
				'label'     => esc_html__( 'Heading', 'super-widget' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_header' => 'yes' ),
			)
		);

		$this->text_style( 'title', esc_html__( 'Title', 'super-widget' ), '.spw-tc__title', true );
		$this->text_style( 'sub', esc_html__( 'Subtitle', 'super-widget' ), '.spw-tc__sub' );

		$this->add_control(
			'line_heading',
			array(
				'label'     => esc_html__( 'Accent line', 'super-widget' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => array( 'show_line' => 'yes' ),
			)
		);

		$this->add_control(
			'line_color',
			array(
				'label'     => esc_html__( 'Color', 'super-widget' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .spw-tc__line' => 'background-color: {{VALUE}};' ),
				'condition' => array( 'show_line' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'line_width',
			array(
				'label'     => esc_html__( 'Width', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 10, 'max' => 200 ) ),
				'default'   => array( 'size' => 50 ),
				'selectors' => array( '{{WRAPPER}} .spw-tc__line' => 'width: {{SIZE}}px;' ),
				'condition' => array( 'show_line' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'line_height',
			array(
				'label'     => esc_html__( 'Thickness', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 1, 'max' => 20 ) ),
				'default'   => array( 'size' => 4 ),
				'selectors' => array( '{{WRAPPER}} .spw-tc__line' => 'height: {{SIZE}}px;' ),
				'condition' => array( 'show_line' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'head_space',
			array(
				'label'     => esc_html__( 'Space under heading', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 160 ) ),
				'default'   => array( 'size' => 40 ),
				'selectors' => array( '{{WRAPPER}} .spw-tc__head' => 'margin-bottom: {{SIZE}}px;' ),
				'separator' => 'before',
			)
		);

		$this->end_controls_section();
	}

	private function style_photo() {
		$this->start_controls_section(
			'style_photo',
			array(
				'label' => esc_html__( 'Photo', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'photo_size',
			array(
				'label'          => esc_html__( 'Size', 'super-widget' ),
				'type'           => Controls_Manager::SLIDER,
				'range'          => array( 'px' => array( 'min' => 80, 'max' => 420 ) ),
				'default'        => array( 'size' => 180 ),
				'tablet_default' => array( 'size' => 160 ),
				'mobile_default' => array( 'size' => 140 ),
				'selectors'      => array( '{{WRAPPER}} .spw-tc' => '--spw-ph: {{SIZE}}px;' ),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'photo_border',
				'selector' => '{{WRAPPER}} .spw-tc__photo',
				'fields_options' => array(
					'border' => array( 'default' => 'solid' ),
					'width'  => array( 'default' => array( 'top' => 3, 'right' => 3, 'bottom' => 3, 'left' => 3, 'unit' => 'px', 'isLinked' => true ) ),
					'color'  => array( 'default' => '#ffffff' ),
				),
			)
		);

		$this->add_control(
			'deco_heading',
			array(
				'label'     => esc_html__( 'Decorative circles', 'super-widget' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => array( 'show_deco' => 'yes' ),
			)
		);

		$this->add_control(
			'deco_ring',
			array(
				'label'     => esc_html__( 'Ring color', 'super-widget' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .spw-tc' => '--tc-ring: {{VALUE}};' ),
				'condition' => array( 'show_deco' => 'yes' ),
			)
		);

		$this->add_control(
			'deco_circle',
			array(
				'label'     => esc_html__( 'Big circle color', 'super-widget' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .spw-tc' => '--tc-circle: {{VALUE}};' ),
				'condition' => array( 'show_deco' => 'yes' ),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'      => 'deco_shadow',
				'selector'  => '{{WRAPPER}} .spw-tc__media::before, {{WRAPPER}} .spw-tc__media::after',
				'condition' => array( 'show_deco' => 'yes' ),
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

		$this->text_style( 'quote', esc_html__( 'Review', 'super-widget' ), '.spw-tc__text', true );
		$this->text_style( 'name', esc_html__( 'Name', 'super-widget' ), '.spw-tc__name' );
		$this->text_style( 'role', esc_html__( 'Role', 'super-widget' ), '.spw-tc__role' );

		$this->add_responsive_control(
			'name_space',
			array(
				'label'     => esc_html__( 'Space above name', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 100 ) ),
				'default'   => array( 'size' => 26 ),
				'selectors' => array( '{{WRAPPER}} .spw-tc__name' => 'margin-top: {{SIZE}}px;' ),
				'separator' => 'before',
			)
		);

		$this->add_control(
			'stars_heading',
			array(
				'label'     => esc_html__( 'Stars', 'super-widget' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => array( 'show_stars' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'star_size',
			array(
				'label'     => esc_html__( 'Size', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 8, 'max' => 40 ) ),
				'default'   => array( 'size' => 16 ),
				'selectors' => array( '{{WRAPPER}} .spw-tc' => '--spw-star: {{SIZE}}px;' ),
				'condition' => array( 'show_stars' => 'yes' ),
			)
		);

		$this->add_control(
			'star_color',
			array(
				'label'     => esc_html__( 'Color', 'super-widget' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .spw-tc' => '--spw-star-on: {{VALUE}};' ),
				'condition' => array( 'show_stars' => 'yes' ),
			)
		);

		$this->add_control(
			'star_empty',
			array(
				'label'     => esc_html__( 'Empty star color', 'super-widget' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .spw-tc' => '--spw-star-off: {{VALUE}};' ),
				'condition' => array( 'show_stars' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'star_align',
			array(
				'label'       => esc_html__( 'Alignment', 'super-widget' ),
				'type'        => Controls_Manager::CHOOSE,
				'toggle'      => true,
				'options'     => array(
					'left'   => array( 'title' => esc_html__( 'Left', 'super-widget' ), 'icon' => 'eicon-text-align-left' ),
					'center' => array( 'title' => esc_html__( 'Center', 'super-widget' ), 'icon' => 'eicon-text-align-center' ),
					'right'  => array( 'title' => esc_html__( 'Right', 'super-widget' ), 'icon' => 'eicon-text-align-right' ),
				),
				'selectors'   => array( '{{WRAPPER}} .spw-tc__rate' => 'text-align: {{VALUE}};' ),
				'condition'   => array( 'show_stars' => 'yes' ),
				'description' => esc_html__( 'Empty = follow the text alignment.', 'super-widget' ),
			)
		);

		$this->add_responsive_control(
			'star_gap_top',
			array(
				'label'     => esc_html__( 'Space above stars', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
				'default'   => array( 'size' => 12 ),
				'selectors' => array( '{{WRAPPER}} .spw-tc__rate' => 'margin-top: {{SIZE}}px;' ),
				'condition' => array( 'show_stars' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'star_gap_bottom',
			array(
				'label'     => esc_html__( 'Space below stars', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
				'default'   => array( 'size' => 0 ),
				'selectors' => array( '{{WRAPPER}} .spw-tc__rate' => 'margin-bottom: {{SIZE}}px;' ),
				'condition' => array( 'show_stars' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'star_spacing',
			array(
				'label'       => esc_html__( 'Space between stars', 'super-widget' ),
				'type'        => Controls_Manager::SLIDER,
				'range'       => array( 'px' => array( 'min' => 0, 'max' => 24 ) ),
				'default'     => array( 'size' => 0 ),
				'selectors'   => array( '{{WRAPPER}} .spw-tc' => '--spw-star-space: {{SIZE}}px;' ),
				'condition'   => array( 'show_stars' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function style_quote_icons() {
		$this->start_controls_section(
			'style_quote_icons',
			array(
				'label'     => esc_html__( 'Quote Marks', 'super-widget' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_quote_icons' => 'yes' ),
			)
		);

		$this->add_control(
			'quote_open_icon',
			array(
				'label'       => esc_html__( 'Opening icon', 'super-widget' ),
				'type'        => Controls_Manager::ICONS,
				'default'     => array( 'value' => 'fas fa-quote-left', 'library' => 'fa-solid' ),
				'skin'        => 'inline',
				'label_block' => false,
			)
		);

		$this->add_control(
			'quote_close_icon',
			array(
				'label'       => esc_html__( 'Closing icon', 'super-widget' ),
				'type'        => Controls_Manager::ICONS,
				'default'     => array( 'value' => 'fas fa-quote-right', 'library' => 'fa-solid' ),
				'skin'        => 'inline',
				'label_block' => false,
			)
		);

		$this->add_responsive_control(
			'qi_size',
			array(
				'label'     => esc_html__( 'Size', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 12, 'max' => 120 ) ),
				'default'   => array( 'size' => 40 ),
				'tablet_default' => array( 'size' => 34 ),
				'mobile_default' => array( 'size' => 26 ),
				'selectors' => array( '{{WRAPPER}} .spw-tc__q' => 'font-size: {{SIZE}}px;' ),
			)
		);

		$this->add_control(
			'qi_color',
			array(
				'label'     => esc_html__( 'Color', 'super-widget' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .spw-tc__q' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'qi_open_x',
			array(
				'label'     => esc_html__( 'Opening mark: horizontal offset', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => -150, 'max' => 150 ) ),
				'selectors' => array( '{{WRAPPER}} .spw-tc' => '--spw-qo-x: {{SIZE}}px;' ),
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'qi_open_y',
			array(
				'label'     => esc_html__( 'Opening mark: vertical offset', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => -150, 'max' => 150 ) ),
				'selectors' => array( '{{WRAPPER}} .spw-tc' => '--spw-qo-y: {{SIZE}}px;' ),
			)
		);

		$this->add_responsive_control(
			'qi_close_x',
			array(
				'label'     => esc_html__( 'Closing mark: horizontal offset', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => -150, 'max' => 150 ) ),
				'selectors' => array( '{{WRAPPER}} .spw-tc' => '--spw-qc-x: {{SIZE}}px;' ),
			)
		);

		$this->add_responsive_control(
			'qi_close_y',
			array(
				'label'     => esc_html__( 'Closing mark: vertical offset', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => -150, 'max' => 150 ) ),
				'selectors' => array( '{{WRAPPER}} .spw-tc' => '--spw-qc-y: {{SIZE}}px;' ),
			)
		);

		$this->end_controls_section();
	}

	private function style_arrows() {
		$this->start_controls_section(
			'style_arrows',
			array(
				'label'     => esc_html__( 'Arrows', 'super-widget' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_arrows' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'arrow_size',
			array(
				'label'          => esc_html__( 'Button size', 'super-widget' ),
				'type'           => Controls_Manager::SLIDER,
				'range'          => array( 'px' => array( 'min' => 28, 'max' => 100 ) ),
				'default'        => array( 'size' => 60 ),
				'tablet_default' => array( 'size' => 52 ),
				'mobile_default' => array( 'size' => 46 ),
				'selectors'      => array( '{{WRAPPER}} .spw-tc__arrow' => 'width: {{SIZE}}px; height: {{SIZE}}px;' ),
			)
		);

		$this->add_responsive_control(
			'arrow_icon_size',
			array(
				'label'     => esc_html__( 'Icon size', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 8, 'max' => 40 ) ),
				'default'   => array( 'size' => 18 ),
				'selectors' => array( '{{WRAPPER}} .spw-tc__arrow' => 'font-size: {{SIZE}}px;' ),
			)
		);

		$this->add_responsive_control(
			'arrow_gap',
			array(
				'label'     => esc_html__( 'Gap between arrows', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'default'   => array( 'size' => 12 ),
				'selectors' => array( '{{WRAPPER}} .spw-tc' => '--spw-arrow-gap: {{SIZE}}px;' ),
			)
		);

		$this->add_responsive_control(
			'arrow_offset',
			array(
				'label'     => esc_html__( 'Distance from the edge (side arrows)', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => -40, 'max' => 120 ) ),
				'default'   => array( 'size' => 0 ),
				'selectors' => array( '{{WRAPPER}} .spw-tc' => '--spw-arrow-x: {{SIZE}}px;' ),
			)
		);

		Super_Widget_Nav::add_arrow_style( $this, '.spw-tc__arrow', array( 'shadow' => true ) );

		$this->end_controls_section();
	}

	private function style_dots() {
		$this->start_controls_section(
			'style_dots',
			array(
				'label'     => esc_html__( 'Pill Indicator', 'super-widget' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_dots' => 'yes' ),
			)
		);

		$this->add_control(
			'dots_track',
			array(
				'label'     => esc_html__( 'Pill color', 'super-widget' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .spw-tc' => '--tc-track: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'dots_dot',
			array(
				'label'     => esc_html__( 'Dot color', 'super-widget' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .spw-tc' => '--tc-dot: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'dots_thumb',
			array(
				'label'     => esc_html__( 'Active marker color', 'super-widget' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .spw-tc' => '--tc-thumb: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'dots_thumb_size',
			array(
				'label'     => esc_html__( 'Active marker size', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 8, 'max' => 40 ) ),
				'default'   => array( 'size' => 18 ),
				'selectors' => array( '{{WRAPPER}} .spw-tc' => '--spw-thumb: {{SIZE}}px;' ),
			)
		);

		$this->add_responsive_control(
			'dots_step',
			array(
				'label'     => esc_html__( 'Space per dot', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 14, 'max' => 50 ) ),
				'default'   => array( 'size' => 24 ),
				'selectors' => array( '{{WRAPPER}} .spw-tc' => '--spw-step: {{SIZE}}px;' ),
			)
		);

		$this->add_responsive_control(
			'dots_space',
			array(
				'label'     => esc_html__( 'Space between navigation and slider', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 120 ) ),
				'default'   => array( 'size' => 28 ),
				'selectors' => array( '{{WRAPPER}} .spw-tc' => '--spw-nav-gap: {{SIZE}}px;' ),
			)
		);

		$this->add_responsive_control(
			'nav_between',
			array(
				'label'     => esc_html__( 'Space between pill and arrows (same area)', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
				'default'   => array( 'size' => 16 ),
				'selectors' => array( '{{WRAPPER}} .spw-tc' => '--spw-nav-between: {{SIZE}}px;' ),
			)
		);

		$this->end_controls_section();
	}

	/* ==================================================================
	 * Render
	 * ================================================================== */

	/**
	 * Position of slide $i relative to the middle of the visible window.
	 * The window starts at the active index and holds $visible slides.
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
		$loop  = ( isset( $settings['loop'] ) && 'yes' === $settings['loop'] ) && $n > 1;
		$trans = isset( $settings['transition'] ) ? (string) $settings['transition'] : 'slide';
		$anim  = isset( $settings['entrance_animation'] ) ? (string) $settings['entrance_animation'] : 'none';

		if ( ! in_array( $trans, array( 'slide', 'fade', 'zoom' ), true ) ) {
			$trans = 'slide';
		}
		if ( ! in_array( $anim, array( 'none', 'fade-in', 'fade-up', 'zoom-in' ), true ) ) {
			$anim = 'none';
		}
		if ( Super_Widget_Settings::get( 'disable_animations' ) ) {
			$anim = 'none';
		}

		$tag = isset( $settings['title_tag'] ) ? (string) $settings['title_tag'] : 'h2';
		if ( ! in_array( $tag, array( 'h1', 'h2', 'h3', 'h4', 'div', 'p' ), true ) ) {
			$tag = 'h2';
		}

		$yes = function ( $key ) use ( $settings ) {
			return isset( $settings[ $key ] ) && 'yes' === $settings[ $key ];
		};

		$vis = isset( $settings['columns'] ) ? max( 1, min( 3, (int) $settings['columns'] ) ) : 1;
		$vis = min( $vis, $n );

		$classes = array( 'spw-tc', 'spw-tc--init', 'spw-tc--' . $trans );
		if ( $vis > 1 ) {
			$classes[] = 'spw-tc--multi';
		}
		if ( 'none' !== $anim ) {
			$classes[] = 'spw-tc-anim-' . $anim;
		}
		if ( isset( $settings['review_space'] ) && 'lines' === $settings['review_space'] ) {
			$classes[] = 'spw-tc--lines';
		}
		if ( $yes( 'content_reveal' ) ) {
			$classes[] = 'spw-tc--reveal';
		}
		if ( $yes( 'show_deco' ) ) {
			$classes[] = 'spw-tc--deco';
		}
		if ( isset( $settings['photo_side'] ) && 'right' === $settings['photo_side'] ) {
			$classes[] = 'spw-tc--photo-right';
		}
		if ( $yes( 'swipe' ) && $n > 1 ) {
			$classes[] = 'spw-tc--swipe';
		}

		$autoplay = $yes( 'autoplay' ) && $n > 1;

		$this->add_render_attribute(
			'root',
			array(
				'class'         => $classes,
				'data-count'    => (string) $n,
				'data-loop'     => $loop ? 'yes' : 'no',
				'data-visible'  => (string) $vis,
				'data-swipe'    => ( $yes( 'swipe' ) && $n > 1 ) ? 'yes' : 'no',
				'data-autoplay' => $autoplay ? 'yes' : 'no',
				'data-speed'    => isset( $settings['autoplay_speed'] ) ? (string) absint( $settings['autoplay_speed'] ) : '5000',
				'data-pause'    => $yes( 'pause_on_hover' ) ? 'yes' : 'no',
			)
		);

		$show_header = $yes( 'show_header' ) && ( ! empty( $settings['title'] ) || ! empty( $settings['subtitle'] ) );
		$show_arrows = $yes( 'show_arrows' ) && $n > 1;
		$show_dots   = $yes( 'show_dots' ) && $n > 1;

		$arrow_zones = $show_arrows ? Super_Widget_Nav::arrow_zones( $settings ) : array();
		$dot_zones   = $show_dots ? Super_Widget_Nav::dot_zones( $settings ) : array();
		$zone_top    = in_array( 'top', $arrow_zones, true ) || in_array( 'top', $dot_zones, true );
		$zone_bottom = in_array( 'bottom', $arrow_zones, true ) || in_array( 'fb', $arrow_zones, true ) || in_array( 'bottom', $dot_zones, true );
		?>
		<div <?php $this->print_render_attribute_string( 'root' ); ?> style="--i:0;--n:<?php echo (int) $n; ?>">

			<?php if ( $show_header ) : ?>
				<div class="spw-tc__head">
					<?php if ( $yes( 'show_line' ) ) : ?>
						<span class="spw-tc__line" aria-hidden="true"></span>
					<?php endif; ?>
					<?php if ( ! empty( $settings['title'] ) ) : ?>
						<<?php echo esc_attr( $tag ); ?> class="spw-tc__title"><?php echo esc_html( $settings['title'] ); ?></<?php echo esc_attr( $tag ); ?>>
					<?php endif; ?>
					<?php if ( ! empty( $settings['subtitle'] ) ) : ?>
						<p class="spw-tc__sub"><?php echo nl2br( esc_html( $settings['subtitle'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( $zone_top ) : ?>
				<div class="spw-tc__zone spw-tc__zone--top">
					<?php
					if ( in_array( 'top', $dot_zones, true ) ) {
						$this->render_dots( 'top', $n );
					}
					if ( in_array( 'top', $arrow_zones, true ) ) {
						$this->render_arrows( 'top', $settings );
					}
					?>
				</div>
			<?php endif; ?>

			<div class="spw-tc__stage">
				<?php
				if ( in_array( 'side', $arrow_zones, true ) ) {
					$this->render_arrows( 'side', $settings );
				}
				?>

				<div class="spw-tc__viewport" role="region" aria-roledescription="carousel" aria-label="<?php echo esc_attr__( 'Testimonials', 'super-widget' ); ?>">
					<div class="spw-tc__track">
						<?php foreach ( $items as $i => $item ) : ?>
							<?php
							$d      = $this->position( $i, $n, $vis, $loop );
							$active = abs( $d ) <= ( $vis - 1 ) / 2 + 0.001;
							$dval   = rtrim( rtrim( number_format( $d, 2, '.', '' ), '0' ), '.' );
							$dval   = ( '' === $dval || '-0' === $dval ) ? '0' : $dval;
							?>
							<article class="spw-tc__slide<?php echo $active ? ' is-active' : ''; ?>" style="--d:<?php echo esc_attr( $dval ); ?>"<?php echo $active ? '' : ' aria-hidden="true"'; ?> role="group" aria-roledescription="slide" aria-label="<?php echo esc_attr( sprintf( /* translators: 1: slide number, 2: total */ __( '%1$d of %2$d', 'super-widget' ), $i + 1, $n ) ); ?>">
								<?php $this->render_slide( $item, $settings ); ?>
							</article>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

			<?php if ( $zone_bottom ) : ?>
				<div class="spw-tc__zone spw-tc__zone--bottom">
					<?php
					if ( in_array( 'bottom', $dot_zones, true ) ) {
						$this->render_dots( 'bottom', $n );
					}
					if ( in_array( 'bottom', $arrow_zones, true ) ) {
						$this->render_arrows( 'bottom', $settings );
					}
					if ( in_array( 'fb', $arrow_zones, true ) ) {
						$this->render_arrows( 'fb', $settings );
					}
					?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	private function render_arrows( $variant, $settings ) {
		?>
		<div class="spw-tc__arrows spw-tc__arrows--<?php echo esc_attr( $variant ); ?>">
			<button type="button" class="spw-tc__arrow spw-tc__arrow--prev" aria-label="<?php echo esc_attr__( 'Previous testimonial', 'super-widget' ); ?>">
				<?php $this->render_icon_or( $settings['prev_icon'], 'prev' ); ?>
			</button>
			<button type="button" class="spw-tc__arrow spw-tc__arrow--next" aria-label="<?php echo esc_attr__( 'Next testimonial', 'super-widget' ); ?>">
				<?php $this->render_icon_or( $settings['next_icon'], 'next' ); ?>
			</button>
		</div>
		<?php
	}

	private function render_dots( $variant, $n ) {
		?>
		<div class="spw-tc__dots spw-tc__dots--<?php echo esc_attr( $variant ); ?>" role="group" aria-label="<?php echo esc_attr__( 'Choose testimonial', 'super-widget' ); ?>">
			<span class="spw-tc__thumb" aria-hidden="true"></span>
			<?php for ( $i = 0; $i < $n; $i++ ) : ?>
				<button type="button" class="spw-tc__dot<?php echo 0 === $i ? ' is-active' : ''; ?>" data-index="<?php echo (int) $i; ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: slide number */ __( 'Go to testimonial %d', 'super-widget' ), $i + 1 ) ); ?>"<?php echo 0 === $i ? ' aria-current="true"' : ''; ?>></button>
			<?php endfor; ?>
		</div>
		<?php
	}

	private function render_icon_or( $icon, $which ) {
		if ( ! empty( $icon['value'] ) ) {
			Icons_Manager::render_icon( $icon, array( 'aria-hidden' => 'true' ) );
			return;
		}

		$path = 'prev' === $which ? 'M19 12H5M11 6l-6 6 6 6' : 'M5 12h14M13 6l6 6-6 6';
		echo '<svg class="spw-tc__svg-line" viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="' . esc_attr( $path ) . '"/></svg>';
	}

	private function render_slide( $item, $settings ) {
		$name   = isset( $item['name'] ) ? trim( (string) $item['name'] ) : '';
		$role   = isset( $item['role'] ) ? trim( (string) $item['role'] ) : '';
		$quote  = isset( $item['quote'] ) ? (string) $item['quote'] : '';
		$rating = isset( $item['rating'] ) && is_numeric( $item['rating'] ) ? max( 0, min( 5, (float) $item['rating'] ) ) : 5;

		$has_image = ! empty( $item['image']['url'] ) || ! empty( $item['image']['id'] );
		$image     = '';
		if ( $has_image ) {
			$image = Group_Control_Image_Size::get_attachment_image_html(
				array(
					'image'      => $item['image'],
					'photo_size' => isset( $settings['photo_size'] ) ? $settings['photo_size'] : 'medium',
				),
				'photo',
				'image'
			);
		}

		$name_tag = isset( $settings['name_tag'] ) ? (string) $settings['name_tag'] : 'div';
		if ( ! in_array( $name_tag, array( 'h2', 'h3', 'h4', 'h5', 'div', 'p' ), true ) ) {
			$name_tag = 'div';
		}

		$icons = isset( $settings['show_quote_icons'] ) && 'yes' === $settings['show_quote_icons'];
		$stars = isset( $settings['show_stars'] ) && 'yes' === $settings['show_stars'];
		?>
		<div class="spw-tc__inner">
		<div class="spw-tc__media">
			<div class="spw-tc__photo">
				<?php if ( '' !== $image ) : ?>
					<?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php else : ?>
					<span class="spw-tc__letter" aria-hidden="true"><?php echo esc_html( function_exists( 'mb_substr' ) ? mb_strtoupper( mb_substr( $name, 0, 1 ) ) : strtoupper( substr( $name, 0, 1 ) ) ); ?></span>
				<?php endif; ?>
			</div>
		</div>

		<div class="spw-tc__content">
			<div class="spw-tc__qbox">
			<?php if ( $icons && ! empty( $settings['quote_open_icon']['value'] ) ) : ?>
				<span class="spw-tc__q spw-tc__q--open" aria-hidden="true"><?php Icons_Manager::render_icon( $settings['quote_open_icon'], array( 'aria-hidden' => 'true' ) ); ?></span>
			<?php endif; ?>

			<div class="spw-tc__text"><?php echo esc_html( $quote ); ?></div>

			<?php if ( $icons && ! empty( $settings['quote_close_icon']['value'] ) ) : ?>
				<span class="spw-tc__q spw-tc__q--close" aria-hidden="true"><?php Icons_Manager::render_icon( $settings['quote_close_icon'], array( 'aria-hidden' => 'true' ) ); ?></span>
			<?php endif; ?>
			</div>

			<?php if ( '' !== $name ) : ?>
				<<?php echo esc_attr( $name_tag ); ?> class="spw-tc__name"><?php echo esc_html( $name ); ?></<?php echo esc_attr( $name_tag ); ?>>
			<?php endif; ?>

			<div class="spw-tc__role"><?php echo '' !== $role ? esc_html( $role ) : '&nbsp;'; ?></div>

			<?php if ( $stars ) : ?>
				<div class="spw-tc__rate"><span class="spw-tc__stars" style="--r:<?php echo esc_attr( (string) $rating ); ?>" role="img" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: rating */ __( 'Rated %s out of 5', 'super-widget' ), number_format_i18n( $rating, 1 ) ) ); ?>"></span></div>
			<?php endif; ?>
		</div>
		</div>
		<?php
	}
}
