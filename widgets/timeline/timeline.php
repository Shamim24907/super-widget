<?php
/**
 * Timeline widget.
 *
 * A vertical or horizontal timeline. Each step has a year / label, a title,
 * a text and an optional picture. Without pictures it is a clean text
 * timeline. Sides alternate (zig-zag) or stay on one side, the line fills
 * while you scroll, and every step animates in.
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
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Repeater;
use Elementor\Widget_Base;

class SPW_Timeline_Widget extends Widget_Base {

	public function get_name() {
		return 'spw-timeline';
	}

	public function get_title() {
		return esc_html( Super_Widget_Settings::widget_title( 'timeline', __( 'Timeline', 'super-widget' ) ) );
	}

	public function get_icon() {
		return 'eicon-time-line';
	}

	public function get_categories() {
		return array( 'super-widget' );
	}

	public function get_keywords() {
		return array( 'timeline', 'history', 'journey', 'steps', 'roadmap', 'process' );
	}

	public function get_style_depends() {
		return array( 'spw-global', 'spw-timeline' );
	}

	public function get_script_depends() {
		return array( 'spw-timeline' );
	}

	/* ==================================================================
	 * Options
	 * ================================================================== */

	private function entrance_options() {
		return array(
			'none'    => esc_html__( 'None', 'super-widget' ),
			'reveal'  => esc_html__( 'Premium reveal (blur, rise, picture wipe)', 'super-widget' ),
			'fade-in' => esc_html__( 'Fade in', 'super-widget' ),
			'fade-up' => esc_html__( 'Fade up', 'super-widget' ),
			'slide'   => esc_html__( 'Slide in from the side', 'super-widget' ),
			'zoom-in' => esc_html__( 'Zoom in', 'super-widget' ),
		);
	}

	/**
	 * CSS variables for each vertical layout. Elementor writes the chosen one
	 * per device, so the layout can change on tablet and mobile.
	 *
	 * o = 1st, 3rd, 5th step. e = 2nd, 4th, 6th step.
	 * main / media = grid column of the text / the picture.
	 * ta = text alignment, hd = order of year and title, dir = side of the
	 * line the text sits on (1 = right, -1 = left).
	 */
	private function vertical_layouts() {
		$three = '--tl-cols:minmax(0,1fr) var(--tl-axis) minmax(0,1fr);--ax-col:2;--m-row:1;--i-row:1;--tl-mb:0px;';
		return array(
			'alternate' => $three . '--o-main:3;--o-media:1;--e-main:1;--e-media:3;--o-ta:left;--e-ta:right;--o-hd:row;--e-hd:row-reverse;--o-ai:flex-start;--e-ai:flex-end;--o-dir:1;--e-dir:-1;',
			'left'      => $three . '--o-main:1;--o-media:3;--e-main:1;--e-media:3;--o-ta:right;--e-ta:right;--o-hd:row-reverse;--e-hd:row-reverse;--o-ai:flex-end;--e-ai:flex-end;--o-dir:-1;--e-dir:-1;',
			'right'     => $three . '--o-main:3;--o-media:1;--e-main:3;--e-media:1;--o-ta:left;--e-ta:left;--o-hd:row;--e-hd:row;--o-ai:flex-start;--e-ai:flex-start;--o-dir:1;--e-dir:1;',
			'single'    => '--tl-cols:var(--tl-axis) minmax(0,1fr);--ax-col:1;--m-row:2;--i-row:1;--tl-mb:var(--tl-inner);--o-main:2;--o-media:2;--e-main:2;--e-media:2;--o-ta:left;--e-ta:left;--o-hd:row;--e-hd:row;--o-ai:flex-start;--e-ai:flex-start;--o-dir:1;--e-dir:1;',
		);
	}

	/* ==================================================================
	 * Controls
	 * ================================================================== */

	protected function register_controls() {
		$this->controls_items();
		$this->controls_layout();
		$this->controls_animation();

		$this->style_layout();
		$this->style_axis();
		$this->style_heading();
		$this->style_text();
		$this->style_card();
		$this->style_image();
		$this->style_nav();
	}

	/* ---------------------------- Content ---------------------------- */

	private function controls_items() {
		$this->start_controls_section(
			'section_items',
			array(
				'label' => esc_html__( 'Timeline Items', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'year',
			array(
				'label'       => esc_html__( 'Year / label', 'super-widget' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '2024',
				'label_block' => true,
			)
		);

		$repeater->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Title', 'super-widget' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'A new chapter', 'super-widget' ),
				'label_block' => true,
			)
		);

		$repeater->add_control(
			'description',
			array(
				'label'   => esc_html__( 'Text', 'super-widget' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 4,
				'default' => esc_html__( 'Tell what happened in this step in a few lines.', 'super-widget' ),
			)
		);

		$repeater->add_control(
			'image',
			array(
				'label'       => esc_html__( 'Picture (optional)', 'super-widget' ),
				'type'        => Controls_Manager::MEDIA,
				'description' => esc_html__( 'Leave empty for a text-only step.', 'super-widget' ),
			)
		);

		$repeater->add_control(
			'link',
			array(
				'label'       => esc_html__( 'Link on the title (optional)', 'super-widget' ),
				'type'        => Controls_Manager::URL,
				'placeholder' => 'https://',
			)
		);

		$repeater->add_control(
			'icon',
			array(
				'label'       => esc_html__( 'Marker icon (optional)', 'super-widget' ),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'label_block' => false,
				'description' => esc_html__( 'Shown when the marker style is "Icon".', 'super-widget' ),
			)
		);

		$demo = array(
			array( '2022', esc_html__( 'Where it started', 'super-widget' ), esc_html__( 'A small idea and a lot of curiosity. This is where the story begins.', 'super-widget' ) ),
			array( '2023', esc_html__( 'First big step', 'super-widget' ), esc_html__( 'We took the leap, found our first customers and learned what really matters.', 'super-widget' ) ),
			array( '2024', esc_html__( 'Growing up', 'super-widget' ), esc_html__( 'The team grew, the product matured and the community started to talk about us.', 'super-widget' ) ),
			array( esc_html__( 'Now', 'super-widget' ), esc_html__( 'What is next', 'super-widget' ), esc_html__( 'Today we keep building, with the same care as on day one.', 'super-widget' ) ),
		);
		$defaults = array();
		foreach ( $demo as $row ) {
			$defaults[] = array(
				'year'        => $row[0],
				'title'       => $row[1],
				'description' => $row[2],
			);
		}

		$this->add_control(
			'items',
			array(
				'label'       => esc_html__( 'Items', 'super-widget' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => $defaults,
				'title_field' => '{{{ year }}} - {{{ title }}}',
			)
		);

		$this->end_controls_section();
	}

	private function controls_layout() {
		$r = '{{WRAPPER}} .spw-tl';

		$this->start_controls_section(
			'section_layout',
			array(
				'label' => esc_html__( 'Layout', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'mode',
			array(
				'label'   => esc_html__( 'Direction', 'super-widget' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'vertical',
				'options' => array(
					'vertical'   => esc_html__( 'Vertical (top to bottom)', 'super-widget' ),
					'horizontal' => esc_html__( 'Horizontal (left to right)', 'super-widget' ),
				),
			)
		);

		$this->add_control(
			'design',
			array(
				'label'       => esc_html__( 'Design', 'super-widget' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'cards',
				'options'     => array(
					'cards'   => esc_html__( 'Premium cards', 'super-widget' ),
					'glass'   => esc_html__( 'Glass cards (on a colored / image background)', 'super-widget' ),
					'classic' => esc_html__( 'Classic (flat, like the reference)', 'super-widget' ),
				),
				'description' => esc_html__( 'A starting look. Every color, size and shadow can still be changed in the Style tab.', 'super-widget' ),
			)
		);

		/* Vertical */
		$this->add_responsive_control(
			'v_layout',
			array(
				'label'                => esc_html__( 'Sides', 'super-widget' ),
				'type'                 => Controls_Manager::SELECT,
				'default'              => 'alternate',
				'tablet_default'       => 'alternate',
				'mobile_default'       => 'single',
				'options'              => array(
					'alternate' => esc_html__( 'Alternate (left / right)', 'super-widget' ),
					'left'      => esc_html__( 'Text on the left', 'super-widget' ),
					'right'     => esc_html__( 'Text on the right', 'super-widget' ),
					'single'    => esc_html__( 'Line on the left edge', 'super-widget' ),
				),
				'selectors_dictionary' => $this->vertical_layouts(),
				'selectors'            => array( $r . '.spw-tl--vertical' => '{{VALUE}}' ),
				'condition'            => array( 'mode' => 'vertical' ),
			)
		);

		/* Horizontal */
		$this->add_control(
			'h_layout',
			array(
				'label'     => esc_html__( 'Text position', 'super-widget' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'alternate',
				'options'   => array(
					'alternate' => esc_html__( 'Alternate (above / below)', 'super-widget' ),
					'below'     => esc_html__( 'Below the line', 'super-widget' ),
					'above'     => esc_html__( 'Above the line', 'super-widget' ),
				),
				'condition' => array( 'mode' => 'horizontal' ),
			)
		);

		$this->add_responsive_control(
			'h_cols',
			array(
				'label'          => esc_html__( 'Steps visible', 'super-widget' ),
				'type'           => Controls_Manager::SELECT,
				'default'        => '3',
				'tablet_default' => '2',
				'mobile_default' => '1',
				'options'        => array(
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
				),
				'selectors'      => array( $r => '--tl-hcols: {{VALUE}};' ),
				'condition'      => array( 'mode' => 'horizontal' ),
				'description'    => esc_html__( 'More steps than this scroll sideways (swipe, scroll or use the arrows).', 'super-widget' ),
			)
		);

		$this->add_control(
			'show_nav',
			array(
				'label'        => esc_html__( 'Arrows', 'super-widget' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array( 'mode' => 'horizontal' ),
			)
		);

		/* Content */
		$this->add_control(
			'show_images',
			array(
				'label'        => esc_html__( 'Show pictures', 'super-widget' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
				'description'  => esc_html__( 'Steps without a picture are shown as text only.', 'super-widget' ),
			)
		);

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			array(
				'name'      => 'thumb',
				'default'   => 'medium_large',
				'exclude'   => array( 'custom' ),
				'condition' => array( 'show_images' => 'yes' ),
			)
		);

		$this->add_control(
			'title_tag',
			array(
				'label'   => esc_html__( 'Title tag', 'super-widget' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'h3',
				'options' => array(
					'h2'   => 'H2',
					'h3'   => 'H3',
					'h4'   => 'H4',
					'h5'   => 'H5',
					'h6'   => 'H6',
					'div'  => 'div',
					'span' => 'span',
				),
			)
		);

		$this->add_control(
			'head_layout',
			array(
				'label'   => esc_html__( 'Year and title', 'super-widget' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'auto',
				'options' => array(
					'auto'    => esc_html__( 'Automatic (by design)', 'super-widget' ),
					'inline'  => esc_html__( 'Side by side', 'super-widget' ),
					'stacked' => esc_html__( 'Stacked', 'super-widget' ),
				),
			)
		);

		$this->end_controls_section();
	}

	private function controls_animation() {
		$r = '{{WRAPPER}} .spw-tl';

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
				'default' => 'reveal',
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
				'selectors' => array( $r => '--tl-dur: {{SIZE}}ms;' ),
			)
		);

		$this->add_control(
			'progress_line',
			array(
				'label'        => esc_html__( 'Line fills while scrolling', 'super-widget' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
				'description'  => esc_html__( 'The line and markers take the highlight color as the visitor reaches each step.', 'super-widget' ),
			)
		);

		$this->add_control(
			'focus_current',
			array(
				'label'        => esc_html__( 'Spotlight the step in view', 'super-widget' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'The other steps fade back a little while the visitor scrolls.', 'super-widget' ),
			)
		);

		$this->add_control(
			'marker_pulse',
			array(
				'label'        => esc_html__( 'Pulse on the current marker', 'super-widget' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'parallax_images',
			array(
				'label'        => esc_html__( 'Picture parallax', 'super-widget' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'Pictures drift slightly while scrolling (vertical timeline).', 'super-widget' ),
				'condition'    => array( 'mode' => 'vertical' ),
			)
		);

		$this->add_control(
			'hover_effect',
			array(
				'label'     => esc_html__( 'Hover effect', 'super-widget' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'both',
				'separator' => 'before',
				'options'   => array(
					'none' => esc_html__( 'None', 'super-widget' ),
					'both' => esc_html__( 'Lift the card + zoom the picture', 'super-widget' ),
					'lift' => esc_html__( 'Lift the card', 'super-widget' ),
					'zoom' => esc_html__( 'Zoom the picture', 'super-widget' ),
				),
			)
		);

		$this->end_controls_section();
	}

	/* ----------------------------- Style ----------------------------- */

	private function color( $id, $label, $selector, $property = 'color', $condition = array() ) {
		$args = array(
			'label'     => $label,
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( $selector => $property . ': {{VALUE}};' ),
		);
		if ( $condition ) {
			$args['condition'] = $condition;
		}
		$this->add_control( $id, $args );
	}

	private function slider( $id, $label, $selector, $declaration, $min, $max, $default = null, $condition = array(), $unit = 'px' ) {
		$args = array(
			'label'      => $label,
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( $unit ),
			'range'      => array( $unit => array( 'min' => $min, 'max' => $max ) ),
			'selectors'  => array( $selector => $declaration ),
		);
		if ( null !== $default ) {
			$args['default'] = array( 'size' => $default, 'unit' => $unit );
		}
		if ( $condition ) {
			$args['condition'] = $condition;
		}
		$this->add_responsive_control( $id, $args );
	}

	private function style_layout() {
		$r = '{{WRAPPER}} .spw-tl';

		$this->start_controls_section(
			'style_layout',
			array(
				'label' => esc_html__( 'Spacing', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->slider( 'step_space', esc_html__( 'Space between steps', 'super-widget' ), $r . '.spw-tl--vertical', '--tl-space: {{SIZE}}{{UNIT}};', 0, 160, 44, array( 'mode' => 'vertical' ) );
		$this->slider( 'h_gap', esc_html__( 'Space between steps', 'super-widget' ), $r . '.spw-tl--horizontal', '--tl-hgap: {{SIZE}}{{UNIT}};', 0, 120, 28, array( 'mode' => 'horizontal' ) );
		$this->slider( 'axis_space', esc_html__( 'Space around the line', 'super-widget' ), $r . '.spw-tl--vertical', '--tl-axis: {{SIZE}}{{UNIT}};', 20, 200, 64, array( 'mode' => 'vertical' ) );
		$this->slider( 'axis_height', esc_html__( 'Space around the line', 'super-widget' ), $r . '.spw-tl--horizontal', '--tl-axis-h: {{SIZE}}{{UNIT}};', 20, 160, 44, array( 'mode' => 'horizontal' ) );
		$this->slider( 'inner_gap', esc_html__( 'Space between text and picture', 'super-widget' ), $r, '--tl-inner: {{SIZE}}{{UNIT}};', 0, 80, 16 );

		$this->add_responsive_control(
			'content_align',
			array(
				'label'                => esc_html__( 'Text alignment (override)', 'super-widget' ),
				'type'                 => Controls_Manager::CHOOSE,
				'options'              => array(
					'left'   => array( 'title' => esc_html__( 'Left', 'super-widget' ), 'icon' => 'eicon-text-align-left' ),
					'center' => array( 'title' => esc_html__( 'Center', 'super-widget' ), 'icon' => 'eicon-text-align-center' ),
					'right'  => array( 'title' => esc_html__( 'Right', 'super-widget' ), 'icon' => 'eicon-text-align-right' ),
				),
				'selectors_dictionary' => array(
					'left'   => 'text-align:left;--hd:row;--hj:flex-start;--ai:flex-start;',
					'center' => 'text-align:center;--hd:row;--hj:center;--ai:center;',
					'right'  => 'text-align:right;--hd:row;--hj:flex-end;--ai:flex-end;',
				),
				'selectors'            => array( $r . ' .spw-tl__main' => '{{VALUE}}' ),
				'description'          => esc_html__( 'By default the text leans toward the line.', 'super-widget' ),
			)
		);

		$this->end_controls_section();
	}

	private function style_axis() {
		$r = '{{WRAPPER}} .spw-tl';

		$this->start_controls_section(
			'style_axis',
			array(
				'label' => esc_html__( 'Line & Markers', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'marker_style',
			array(
				'label'   => esc_html__( 'Marker', 'super-widget' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'dot',
				'options' => array(
					'tick' => esc_html__( 'Tick (short bar)', 'super-widget' ),
					'dot'  => esc_html__( 'Dot', 'super-widget' ),
					'ring' => esc_html__( 'Ring', 'super-widget' ),
					'icon' => esc_html__( 'Icon', 'super-widget' ),
				),
			)
		);

		$this->color( 'line_color', esc_html__( 'Line color', 'super-widget' ), $r, '--tl-line' );
		$this->color( 'accent_color', esc_html__( 'Highlight color', 'super-widget' ), $r, '--tl-accent' );
		$this->color( 'accent_color_2', esc_html__( 'Highlight color 2 (gradient end)', 'super-widget' ), $r, '--tl-accent-2' );
		$this->slider( 'line_width', esc_html__( 'Line thickness', 'super-widget' ), $r, '--tl-line-w: {{SIZE}}{{UNIT}};', 1, 12, 3 );
		$this->slider( 'marker_size', esc_html__( 'Marker size', 'super-widget' ), $r, '--tl-marker: {{SIZE}}{{UNIT}};', 6, 60, 18 );
		$this->slider( 'tick_length', esc_html__( 'Tick length', 'super-widget' ), $r, '--tl-tick: {{SIZE}}{{UNIT}};', 8, 80, 22, array( 'marker_style' => 'tick' ) );
		$this->slider( 'marker_y', esc_html__( 'Marker position from the top of a step', 'super-widget' ), $r . '.spw-tl--vertical', '--tl-marker-y: {{SIZE}}{{UNIT}};', 0, 80, 22, array( 'mode' => 'vertical' ) );

		$this->color( 'marker_color', esc_html__( 'Marker color (before it is reached)', 'super-widget' ), $r, '--tl-marker-c' );
		$this->add_control(
			'marker_hover_color',
			array(
				'label'     => esc_html__( 'Marker color when the step is hovered', 'super-widget' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$r . ' .spw-tl__item:hover .spw-tl__marker' => 'background: {{VALUE}}; border-color: {{VALUE}};',
				),
			)
		);
		$this->color( 'marker_icon_color', esc_html__( 'Icon color', 'super-widget' ), $r, '--tl-icon', array( 'marker_style' => 'icon' ) );

		$this->end_controls_section();
	}

	private function style_heading() {
		$year  = '{{WRAPPER}} .spw-tl .spw-tl__year';
		$title = '{{WRAPPER}} .spw-tl .spw-tl__title, {{WRAPPER}} .spw-tl .spw-tl__title a';

		$this->start_controls_section(
			'style_heading',
			array(
				'label' => esc_html__( 'Year & Title', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'year_style',
			array(
				'label'   => esc_html__( 'Year style', 'super-widget' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'auto',
				'options' => array(
					'auto'  => esc_html__( 'Automatic (by design)', 'super-widget' ),
					'pill'  => esc_html__( 'Gradient pill', 'super-widget' ),
					'plain' => esc_html__( 'Plain text', 'super-widget' ),
				),
			)
		);

		$this->add_control(
			'year_heading',
			array(
				'label'     => esc_html__( 'Year / label', 'super-widget' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'year_typography',
				'selector' => $year,
			)
		);
		$this->color( 'year_color', esc_html__( 'Color', 'super-widget' ), $year );
		$this->color( 'year_bg', esc_html__( 'Pill background', 'super-widget' ), $year, 'background', array( 'year_style!' => 'plain' ) );
		$this->color( 'year_hover_color', esc_html__( 'Color when the step is hovered', 'super-widget' ), '{{WRAPPER}} .spw-tl .spw-tl__item:hover .spw-tl__year' );

		$this->add_control(
			'title_heading',
			array(
				'label'     => esc_html__( 'Title', 'super-widget' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'selector' => $title,
			)
		);
		$this->color( 'title_color', esc_html__( 'Color', 'super-widget' ), $title );
		$this->color( 'title_hover_color', esc_html__( 'Color when the step is hovered', 'super-widget' ), '{{WRAPPER}} .spw-tl .spw-tl__item:hover .spw-tl__title, {{WRAPPER}} .spw-tl .spw-tl__item:hover .spw-tl__title a' );

		$this->slider( 'head_gap', esc_html__( 'Space between year and title', 'super-widget' ), '{{WRAPPER}} .spw-tl .spw-tl__head', 'column-gap: {{SIZE}}{{UNIT}};', 0, 60, 14 );
		$this->slider( 'head_space', esc_html__( 'Space below', 'super-widget' ), '{{WRAPPER}} .spw-tl .spw-tl__head', 'margin-bottom: {{SIZE}}{{UNIT}};', 0, 60, 10 );

		$this->end_controls_section();
	}

	private function style_text() {
		$desc = '{{WRAPPER}} .spw-tl .spw-tl__desc';

		$this->start_controls_section(
			'style_text',
			array(
				'label' => esc_html__( 'Text', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'desc_typography',
				'selector' => $desc,
			)
		);
		$this->color( 'desc_color', esc_html__( 'Color', 'super-widget' ), $desc );
		$this->slider( 'desc_width', esc_html__( 'Max text width', 'super-widget' ), '{{WRAPPER}} .spw-tl .spw-tl__main', 'max-width: {{SIZE}}{{UNIT}};', 120, 800 );

		$this->end_controls_section();
	}

	private function style_card() {
		$main = '{{WRAPPER}} .spw-tl .spw-tl__main';

		$this->start_controls_section(
			'style_card',
			array(
				'label' => esc_html__( 'Card', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->color( 'card_bg', esc_html__( 'Background', 'super-widget' ), $main, 'background-color' );

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'card_border',
				'selector' => $main,
			)
		);

		$this->add_responsive_control(
			'card_radius',
			array(
				'label'      => esc_html__( 'Border radius', 'super-widget' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array( $main => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'card_padding',
			array(
				'label'      => esc_html__( 'Padding', 'super-widget' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array( $main => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'card_shadow',
				'selector' => $main,
			)
		);

		$this->add_control(
			'card_hover_heading',
			array(
				'label'     => esc_html__( 'On hover', 'super-widget' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->color( 'card_hover_bg', esc_html__( 'Background', 'super-widget' ), $main . ':hover', 'background-color' );
		$this->color( 'card_hover_border', esc_html__( 'Border color', 'super-widget' ), $main . ':hover', 'border-color' );
		$this->color( 'card_bar_color', esc_html__( 'Bottom bar color (replaces the gradient)', 'super-widget' ), '{{WRAPPER}} .spw-tl .spw-tl__fx::after', 'background' );

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'card_shadow_hover',
				'selector' => $main . ':hover',
			)
		);

		$this->slider( 'card_lift', esc_html__( 'Lift distance', 'super-widget' ), '{{WRAPPER}} .spw-tl', '--tl-lift: -{{SIZE}}{{UNIT}};', 0, 30, 6 );

		$this->end_controls_section();
	}

	private function style_image() {
		$frame = '{{WRAPPER}} .spw-tl .spw-tl__frame';

		$this->start_controls_section(
			'style_image',
			array(
				'label'     => esc_html__( 'Picture', 'super-widget' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_images' => 'yes' ),
			)
		);

		$this->slider( 'img_width', esc_html__( 'Width', 'super-widget' ), $frame, 'width: {{SIZE}}{{UNIT}};', 60, 600, 220 );

		$this->add_responsive_control(
			'img_ratio',
			array(
				'label'     => esc_html__( 'Shape', 'super-widget' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '4 / 3',
				'options'   => array(
					'1 / 1' => esc_html__( 'Square', 'super-widget' ),
					'4 / 3' => esc_html__( 'Landscape 4:3', 'super-widget' ),
					'3 / 2' => esc_html__( 'Landscape 3:2', 'super-widget' ),
					'3 / 4' => esc_html__( 'Portrait 3:4', 'super-widget' ),
					'2 / 3' => esc_html__( 'Portrait 2:3', 'super-widget' ),
					'auto'  => esc_html__( 'Original', 'super-widget' ),
				),
				'selectors' => array( $frame => 'aspect-ratio: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'img_align',
			array(
				'label'                => esc_html__( 'Position in its column', 'super-widget' ),
				'type'                 => Controls_Manager::CHOOSE,
				'options'              => array(
					'left'   => array( 'title' => esc_html__( 'Left', 'super-widget' ), 'icon' => 'eicon-h-align-left' ),
					'center' => array( 'title' => esc_html__( 'Center', 'super-widget' ), 'icon' => 'eicon-h-align-center' ),
					'right'  => array( 'title' => esc_html__( 'Right', 'super-widget' ), 'icon' => 'eicon-h-align-right' ),
				),
				'selectors_dictionary' => array(
					'left'   => 'flex-start',
					'center' => 'center',
					'right'  => 'flex-end',
				),
				'selectors'            => array( '{{WRAPPER}} .spw-tl .spw-tl__media' => 'justify-content: {{VALUE}};' ),
			)
		);

		$this->slider( 'img_offset', esc_html__( 'Move up / down', 'super-widget' ), '{{WRAPPER}} .spw-tl .spw-tl__media', 'margin-top: {{SIZE}}{{UNIT}};', -120, 200 );

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'img_border',
				'selector' => $frame,
			)
		);

		$this->add_responsive_control(
			'img_radius',
			array(
				'label'      => esc_html__( 'Border radius', 'super-widget' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array( $frame => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'img_shadow',
				'selector' => $frame,
			)
		);

		$this->end_controls_section();
	}

	private function style_nav() {
		$btn = '{{WRAPPER}} .spw-tl .spw-tl__nav-btn';

		$this->start_controls_section(
			'style_nav',
			array(
				'label'     => esc_html__( 'Arrows', 'super-widget' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'mode' => 'horizontal', 'show_nav' => 'yes' ),
			)
		);

		$this->slider( 'nav_size', esc_html__( 'Size', 'super-widget' ), $btn, 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};', 28, 80, 44 );
		$this->color( 'nav_color', esc_html__( 'Icon color', 'super-widget' ), $btn );
		$this->color( 'nav_bg', esc_html__( 'Background', 'super-widget' ), $btn, 'background-color' );
		$this->color( 'nav_border', esc_html__( 'Border color', 'super-widget' ), $btn, 'border-color' );
		$this->color( 'nav_hover_color', esc_html__( 'Hover icon color', 'super-widget' ), $btn . ':hover, ' . $btn . ':focus-visible' );
		$this->color( 'nav_hover_bg', esc_html__( 'Hover background', 'super-widget' ), $btn . ':hover, ' . $btn . ':focus-visible', 'background-color' );

		$this->end_controls_section();
	}

	/* ============================ Render ============================== */

	private function render_picture( $item, $settings ) {
		if ( empty( $item['image']['url'] ) && empty( $item['image']['id'] ) ) {
			return '';
		}

		return Group_Control_Image_Size::get_attachment_image_html(
			array(
				'image'                 => $item['image'],
				'thumb_size'            => isset( $settings['thumb_size'] ) ? $settings['thumb_size'] : 'medium_large',
				'thumb_custom_dimension' => isset( $settings['thumb_custom_dimension'] ) ? $settings['thumb_custom_dimension'] : array(),
			),
			'thumb',
			'image'
		);
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$items    = ! empty( $settings['items'] ) && is_array( $settings['items'] ) ? $settings['items'] : array();

		if ( empty( $items ) ) {
			return;
		}

		$mode = ( isset( $settings['mode'] ) && 'horizontal' === $settings['mode'] ) ? 'horizontal' : 'vertical';

		$h_layout = isset( $settings['h_layout'] ) ? (string) $settings['h_layout'] : 'alternate';
		if ( ! in_array( $h_layout, array( 'alternate', 'below', 'above' ), true ) ) {
			$h_layout = 'alternate';
		}

		$marker = isset( $settings['marker_style'] ) ? (string) $settings['marker_style'] : 'tick';
		if ( ! in_array( $marker, array( 'tick', 'dot', 'ring', 'icon' ), true ) ) {
			$marker = 'tick';
		}

		$entrance = isset( $settings['entrance_animation'] ) ? (string) $settings['entrance_animation'] : 'none';
		if ( ! array_key_exists( $entrance, $this->entrance_options() ) ) {
			$entrance = 'none';
		}

		$hover = isset( $settings['hover_effect'] ) ? (string) $settings['hover_effect'] : 'none';
		if ( ! in_array( $hover, array( 'none', 'lift', 'zoom', 'both' ), true ) ) {
			$hover = 'none';
		}

		$design = isset( $settings['design'] ) ? (string) $settings['design'] : 'cards';
		if ( ! in_array( $design, array( 'cards', 'glass', 'classic' ), true ) ) {
			$design = 'cards';
		}

		$progress = isset( $settings['progress_line'] ) && 'yes' === $settings['progress_line'];
		$focus    = ! isset( $settings['focus_current'] ) || 'yes' === $settings['focus_current'];
		$pulse    = ! isset( $settings['marker_pulse'] ) || 'yes' === $settings['marker_pulse'];
		$parallax = 'vertical' === $mode && ( ! isset( $settings['parallax_images'] ) || 'yes' === $settings['parallax_images'] );

		// Global switch from Super Widget > Settings.
		if ( Super_Widget_Settings::get( 'disable_animations' ) ) {
			$entrance = 'none';
			$hover    = 'none';
			$focus    = false;
			$pulse    = false;
			$parallax = false;
		}

		// "Automatic" follows the design: flat = inline + plain year, others = stacked + pill.
		$head = isset( $settings['head_layout'] ) ? (string) $settings['head_layout'] : 'auto';
		if ( 'auto' === $head || ! in_array( $head, array( 'inline', 'stacked' ), true ) ) {
			$head = 'classic' === $design ? 'inline' : 'stacked';
		}

		$year_style = isset( $settings['year_style'] ) ? (string) $settings['year_style'] : 'auto';
		if ( 'auto' === $year_style || ! in_array( $year_style, array( 'pill', 'plain' ), true ) ) {
			$year_style = 'classic' === $design ? 'plain' : 'pill';
		}

		$tag = isset( $settings['title_tag'] ) ? (string) $settings['title_tag'] : 'h3';
		if ( ! in_array( $tag, array( 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'span' ), true ) ) {
			$tag = 'h3';
		}

		$show_images = ! isset( $settings['show_images'] ) || 'yes' === $settings['show_images'];

		// Pictures, prepared once so we know if the whole timeline is text only.
		$pictures  = array();
		$has_media = false;
		foreach ( $items as $i => $item ) {
			$pictures[ $i ] = $show_images ? $this->render_picture( $item, $settings ) : '';
			if ( '' !== $pictures[ $i ] ) {
				$has_media = true;
			}
		}

		$classes = array( 'spw-tl', 'spw-tl--' . $mode, 'spw-tl--m-' . $marker, 'spw-tl--d-' . $design, 'spw-tl--head-' . $head, 'spw-tl--year-' . $year_style );
		if ( $focus ) {
			$classes[] = 'spw-tl--focus';
		}
		if ( $pulse ) {
			$classes[] = 'spw-tl--pulse';
		}
		if ( $parallax ) {
			$classes[] = 'spw-tl--parallax';
		}
		if ( 'horizontal' === $mode ) {
			$classes[] = 'spw-tl--h-' . $h_layout;
		}
		if ( $progress ) {
			$classes[] = 'spw-tl--progress';
		}
		if ( 'none' !== $entrance ) {
			$classes[] = 'spw-tl--anim-' . $entrance;
		}
		if ( in_array( $hover, array( 'lift', 'both' ), true ) ) {
			$classes[] = 'spw-tl--hover-lift';
		}
		if ( in_array( $hover, array( 'zoom', 'both' ), true ) ) {
			$classes[] = 'spw-tl--hover-zoom';
		}
		if ( ! $has_media ) {
			$classes[] = 'spw-tl--no-media';
		}

		$this->add_render_attribute(
			'root',
			array(
				'class'         => $classes,
				'data-mode'     => $mode,
				'data-once'     => ( isset( $settings['animate_once'] ) && 'yes' === $settings['animate_once'] ) ? 'yes' : 'no',
				'data-progress' => $progress ? 'yes' : 'no',
			)
		);

		$this->add_render_attribute( 'track', 'class', 'spw-tl__track' );
		if ( 'horizontal' === $mode ) {
			$this->add_render_attribute( 'track', array( 'tabindex' => '0', 'aria-label' => esc_attr__( 'Timeline', 'super-widget' ) ) );
		}

		$last = count( $items ) - 1;
		?>
		<div <?php $this->print_render_attribute_string( 'root' ); ?>>
			<ol <?php $this->print_render_attribute_string( 'track' ); ?>>
				<?php foreach ( $items as $i => $item ) : ?>
					<?php
					$cls = 'spw-tl__item ' . ( 0 === $i % 2 ? 'is-odd' : 'is-even' );
					if ( 0 === $i ) {
						$cls .= ' is-first';
					}
					if ( $i === $last ) {
						$cls .= ' is-last';
					}
					if ( '' !== $pictures[ $i ] ) {
						$cls .= ' has-media';
					}

					$year  = isset( $item['year'] ) ? (string) $item['year'] : '';
					$title = isset( $item['title'] ) ? (string) $item['title'] : '';
					$desc  = isset( $item['description'] ) ? (string) $item['description'] : '';
					$link  = 'link_' . $i;

					if ( ! empty( $item['link']['url'] ) ) {
						$this->add_link_attributes( $link, $item['link'] );
					}
					?>
					<li class="<?php echo esc_attr( $cls ); ?>">
						<div class="spw-tl__main">
							<span class="spw-tl__fx" aria-hidden="true"><?php if ( '' !== $year ) : ?><span class="spw-tl__ghost"><?php echo esc_html( $year ); ?></span><?php endif; ?></span>
							<?php if ( '' !== $year || '' !== $title ) : ?>
								<div class="spw-tl__head">
									<?php if ( '' !== $year ) : ?>
										<span class="spw-tl__year"><?php echo esc_html( $year ); ?></span>
									<?php endif; ?>
									<?php if ( '' !== $title ) : ?>
										<<?php echo tag_escape( $tag ); ?> class="spw-tl__title">
											<?php if ( ! empty( $item['link']['url'] ) ) : ?>
												<a <?php $this->print_render_attribute_string( $link ); ?>><?php echo esc_html( $title ); ?></a>
											<?php else : ?>
												<?php echo esc_html( $title ); ?>
											<?php endif; ?>
										</<?php echo tag_escape( $tag ); ?>>
									<?php endif; ?>
								</div>
							<?php endif; ?>
							<?php if ( '' !== $desc ) : ?>
								<div class="spw-tl__desc"><?php echo nl2br( esc_html( $desc ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
							<?php endif; ?>
						</div>

						<div class="spw-tl__axis" aria-hidden="true">
							<span class="spw-tl__marker">
								<?php
								if ( 'icon' === $marker && ! empty( $item['icon']['value'] ) ) {
									Icons_Manager::render_icon( $item['icon'], array( 'aria-hidden' => 'true' ) );
								}
								?>
							</span>
						</div>

						<?php if ( '' !== $pictures[ $i ] ) : ?>
							<div class="spw-tl__media">
								<div class="spw-tl__frame"><?php echo $pictures[ $i ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
							</div>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>

			<?php if ( 'horizontal' === $mode && ( ! isset( $settings['show_nav'] ) || 'yes' === $settings['show_nav'] ) && count( $items ) > 1 ) : ?>
				<div class="spw-tl__nav" hidden>
					<button type="button" class="spw-tl__nav-btn spw-tl__nav-btn--prev" aria-label="<?php echo esc_attr__( 'Previous', 'super-widget' ); ?>">
						<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
					</button>
					<button type="button" class="spw-tl__nav-btn spw-tl__nav-btn--next" aria-label="<?php echo esc_attr__( 'Next', 'super-widget' ); ?>">
						<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
					</button>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}
