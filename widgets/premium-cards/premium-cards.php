<?php
/**
 * Premium Cards widget.
 *
 * One card widget with nine designs (blog, clean, inset post, image overlay with a
 * notch, icon over the picture, centered icon + bar button, glass, soft tint,
 * accent bar). Grid or slider. Every part can be switched off and styled.
 *
 * @package SPW_Addons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Css_Filter;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Repeater;
use Elementor\Widget_Base;

class SPW_Premium_Cards_Widget extends Widget_Base {

	public function get_name() {
		return 'spw-premium-cards';
	}

	public function get_title() {
		return esc_html( Super_Widget_Settings::widget_title( 'premium-cards', __( 'Premium Cards', 'super-widget' ) ) );
	}

	public function get_icon() {
		return 'eicon-posts-grid';
	}

	public function get_categories() {
		return array( 'super-widget' );
	}

	public function get_keywords() {
		return array( 'card', 'cards', 'blog', 'post', 'service', 'feature', 'icon box', 'overlay', 'slider' );
	}

	public function get_style_depends() {
		return array( 'spw-global', 'spw-premium-cards' );
	}

	public function get_script_depends() {
		return array( 'spw-premium-cards' );
	}

	/* ==================================================================
	 * Options
	 * ================================================================== */

	private function styles() {
		return array(
			'blog'       => esc_html__( 'Blog: picture, badge, author', 'super-widget' ),
			'clean'      => esc_html__( 'Clean: picture, text, plain pill button', 'super-widget' ),
			'post'       => esc_html__( 'Post: inset picture, meta, pill button', 'super-widget' ),
			'overlay'    => esc_html__( 'Overlay: text on picture + notch arrow', 'super-widget' ),
			'icon-image' => esc_html__( 'Icon over the picture', 'super-widget' ),
			'icon-center' => esc_html__( 'Centered icon + bar button', 'super-widget' ),
			'glass'      => esc_html__( 'Glass: icon, text, dark pill button', 'super-widget' ),
			'soft'       => esc_html__( 'Soft tint: icon tile + round arrow', 'super-widget' ),
			'accent'     => esc_html__( 'Accent bar: icon, text, link', 'super-widget' ),
		);
	}

	/** Default button look for each design. */
	private function default_kind( $style ) {
		$map = array(
			'blog'        => 'none',
			'clean'       => 'pill',
			'post'        => 'pill',
			'overlay'     => 'circle',
			'icon-image'  => 'circle',
			'icon-center' => 'bar',
			'glass'       => 'pill',
			'soft'        => 'circle',
			'accent'      => 'link',
		);
		return isset( $map[ $style ] ) ? $map[ $style ] : 'pill';
	}

	private function entrances() {
		return array(
			'none'    => esc_html__( 'None', 'super-widget' ),
			'fade-in' => esc_html__( 'Fade in', 'super-widget' ),
			'fade-up' => esc_html__( 'Fade up', 'super-widget' ),
			'zoom-in' => esc_html__( 'Zoom in', 'super-widget' ),
			'blur-in' => esc_html__( 'Blur in (soft focus)', 'super-widget' ),
			'pop'     => esc_html__( 'Pop (bounce)', 'super-widget' ),
			'flip'    => esc_html__( 'Flip up', 'super-widget' ),
		);
	}

	/** True when the layout is "slider" on at least one device (used to show the slider controls). */
	private function any_slider_conditions() {
		$terms = array();
		foreach ( array( 'layout', 'layout_widescreen', 'layout_laptop', 'layout_tablet_extra', 'layout_tablet', 'layout_mobile_extra', 'layout_mobile' ) as $k ) {
			$terms[] = array( 'name' => $k, 'operator' => '==', 'value' => 'slider' );
		}
		return array( 'relation' => 'or', 'terms' => $terms );
	}

	/** Slider on some device AND the navigation type is one of $types. */
	private function nav_conditions( $types ) {
		return array(
			'relation' => 'and',
			'terms'    => array(
				$this->any_slider_conditions(),
				array( 'name' => 'nav_type', 'operator' => 'in', 'value' => $types ),
			),
		);
	}

	private function nav_positions( $with_middle_center ) {
		$o = array(
			'top-left'      => esc_html__( 'Top left', 'super-widget' ),
			'top-center'    => esc_html__( 'Top center', 'super-widget' ),
			'top-right'     => esc_html__( 'Top right', 'super-widget' ),
			'middle-left'   => esc_html__( 'Middle left (on the cards)', 'super-widget' ),
			'middle-right'  => esc_html__( 'Middle right (on the cards)', 'super-widget' ),
			'bottom-left'   => esc_html__( 'Bottom left', 'super-widget' ),
			'bottom-center' => esc_html__( 'Bottom center', 'super-widget' ),
			'bottom-right'  => esc_html__( 'Bottom right', 'super-widget' ),
		);
		if ( $with_middle_center ) {
			$o = array_slice( $o, 0, 4, true ) + array( 'middle-center' => esc_html__( 'Middle, one on each side', 'super-widget' ) ) + array_slice( $o, 4, null, true );
		}
		return $o;
	}

	/** Small line icons (24 x 24, stroke only). Trusted constants, picked by key. */
	private function presets() {
		return array(
			'home'   => '<path d="M3 11l9-8 9 8v10a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
			'trend'  => '<path d="M3 17l6-6 4 4 8-8M15 7h6v6"/>',
			'sun'    => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
			'shield' => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M9 12l2 2 4-4"/>',
			'wallet' => '<path d="M4 7h15a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h12"/><path d="M16 13h2"/>',
			'card'   => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h3"/>',
			'leaf'   => '<path d="M5 19c0-9 5-14 15-14 0 10-5 15-14 15"/><path d="M5 19l7-7"/>',
			'pen'    => '<path d="M4 20l4-1 11-11-3-3L5 16z"/><path d="M14 6l3 3"/>',
			'bulb'   => '<path d="M9 18h6M10 21h4M12 3a6 6 0 0 0-4 10.5c.7.7 1 1.5 1 2.5h6c0-1 .3-1.8 1-2.5A6 6 0 0 0 12 3z"/>',
			'layout' => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/>',
			'rocket' => '<path d="M5 15c-1 1-2 4-2 6 2 0 5-1 6-2M14 4c3-1 6-1 7-1 0 1 0 4-1 7l-6 6-6-6z"/><circle cx="15" cy="9" r="1"/>',
			'heart'  => '<path d="M12 21s-8-5-8-11a4.5 4.5 0 0 1 8-2.5A4.5 4.5 0 0 1 20 10c0 6-8 11-8 11z"/>',
			'star'   => '<path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/>',
			'phone'  => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/>',
			'mail'   => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
			'tools'  => '<path d="M14 6a4 4 0 0 0 5 5l-9 9a2 2 0 0 1-3-3l9-9a4 4 0 0 0-2-2z"/>',
		);
	}

	private function preset_options() {
		$o = array( '' => esc_html__( 'None', 'super-widget' ) );
		foreach ( array_keys( $this->presets() ) as $k ) {
			$o[ $k ] = ucfirst( $k );
		}
		return $o;
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

	private function color( $id, $label, $selectors, $cond = array(), $sep = '' ) {
		$args = array(
			'label'     => $label,
			'type'      => Controls_Manager::COLOR,
			'selectors' => $selectors,
		);
		if ( $cond ) {
			$args['condition'] = $cond;
		}
		if ( $sep ) {
			$args['separator'] = $sep;
		}
		$this->add_control( $id, $args );
	}

	private function heading( $id, $label ) {
		$this->add_control( $id, array( 'label' => $label, 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
	}

	private function switcher( $id, $label, $default = 'yes', $cond = array() ) {
		$args = array( 'label' => $label, 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => $default );
		if ( $cond ) {
			$args['condition'] = $cond;
		}
		$this->add_control( $id, $args );
	}

	/* ==================================================================
	 * Controls
	 * ================================================================== */

	protected function register_controls() {
		$root = '{{WRAPPER}} .spw-pc';
		$card = '{{WRAPPER}} .spw-pc__card';
		$cur  = '{{WRAPPER}} {{CURRENT_ITEM}}';

		/* ------------------------- Content: Cards ------------------------- */
		$this->start_controls_section( 'section_items', array( 'label' => esc_html__( 'Cards', 'super-widget' ) ) );

		$r = new Repeater();
		$r->add_control( 'title', array( 'label' => esc_html__( 'Title', 'super-widget' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'Card title', 'super-widget' ), 'label_block' => true ) );
		$r->add_control( 'desc', array( 'label' => esc_html__( 'Text', 'super-widget' ), 'type' => Controls_Manager::TEXTAREA, 'rows' => 3 ) );
		$r->add_control( 'badge', array( 'label' => esc_html__( 'Badge / category', 'super-widget' ), 'type' => Controls_Manager::TEXT, 'label_block' => true ) );
		$r->add_control( 'badge_c1', array( 'label' => esc_html__( 'Badge color', 'super-widget' ), 'type' => Controls_Manager::COLOR, 'selectors' => array( $cur => '--pc-b1: {{VALUE}};' ) ) );
		$r->add_control( 'badge_c2', array( 'label' => esc_html__( 'Badge second color (gradient)', 'super-widget' ), 'type' => Controls_Manager::COLOR, 'selectors' => array( $cur => '--pc-b2: {{VALUE}};' ) ) );

		$r->add_control( 'h_media', array( 'label' => esc_html__( 'Picture and icon', 'super-widget' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$r->add_control( 'image', array( 'label' => esc_html__( 'Picture', 'super-widget' ), 'type' => Controls_Manager::MEDIA, 'default' => array( 'url' => '' ) ) );
		$r->add_control( 'preset', array( 'label' => esc_html__( 'Icon (built in)', 'super-widget' ), 'type' => Controls_Manager::SELECT, 'default' => '', 'options' => $this->preset_options() ) );
		$r->add_control( 'icon', array( 'label' => esc_html__( 'Or your own icon', 'super-widget' ), 'type' => Controls_Manager::ICONS ) );

		$r->add_control( 'h_meta', array( 'label' => esc_html__( 'Author / date', 'super-widget' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$r->add_control( 'author', array( 'label' => esc_html__( 'Name', 'super-widget' ), 'type' => Controls_Manager::TEXT ) );
		$r->add_control( 'date', array( 'label' => esc_html__( 'Date / time', 'super-widget' ), 'type' => Controls_Manager::TEXT ) );
		$r->add_control( 'avatar', array( 'label' => esc_html__( 'Photo (blog style)', 'super-widget' ), 'type' => Controls_Manager::MEDIA, 'default' => array( 'url' => '' ) ) );

		$r->add_control( 'h_link', array( 'label' => esc_html__( 'Link and button', 'super-widget' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$r->add_control( 'link', array( 'label' => esc_html__( 'Link (whole card)', 'super-widget' ), 'type' => Controls_Manager::URL, 'options' => array( 'url', 'is_external', 'nofollow' ), 'default' => array( 'url' => '' ) ) );
		$r->add_control( 'btn_text', array( 'label' => esc_html__( 'Button text (empty = widget text)', 'super-widget' ), 'type' => Controls_Manager::TEXT, 'label_block' => true ) );

		$r->add_control( 'h_color', array( 'label' => esc_html__( 'Colors of this card', 'super-widget' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$r->add_control( 'accent', array( 'label' => esc_html__( 'Accent color', 'super-widget' ), 'type' => Controls_Manager::COLOR, 'selectors' => array( $cur => '--pc-accent: {{VALUE}};' ) ) );
		$r->add_control( 'card_bg', array( 'label' => esc_html__( 'Card background', 'super-widget' ), 'type' => Controls_Manager::COLOR, 'selectors' => array( $cur . ' .spw-pc__card' => 'background: {{VALUE}};' ) ) );

		$lorem = esc_html__( 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Sequi perferendis molestiae non nemo doloribus.', 'super-widget' );
		$this->add_control(
			'items',
			array(
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $r->get_controls(),
				'title_field' => '{{{ title }}}',
				'default'     => array(
					array( 'title' => esc_html__( "What's new in 2026 tech", 'super-widget' ), 'desc' => $lorem, 'badge' => esc_html__( 'Technology', 'super-widget' ), 'badge_c1' => '#2b8cf0', 'badge_c2' => '#35c0f0', 'image' => array( 'url' => SUPER_WIDGET_URL . 'assets/img/pc-1.svg', 'id' => '' ), 'preset' => 'layout', 'author' => esc_html__( 'Jane Doe', 'super-widget' ), 'date' => esc_html__( '2h ago', 'super-widget' ) ),
					array( 'title' => esc_html__( 'Delicious food', 'super-widget' ), 'desc' => $lorem, 'badge' => esc_html__( 'Food', 'super-widget' ), 'badge_c1' => '#f0a24a', 'badge_c2' => '#e8742c', 'image' => array( 'url' => SUPER_WIDGET_URL . 'assets/img/pc-2.svg', 'id' => '' ), 'preset' => 'leaf', 'author' => esc_html__( 'Jony Doe', 'super-widget' ), 'date' => esc_html__( 'Yesterday', 'super-widget' ) ),
					array( 'title' => esc_html__( 'Race to your heart content', 'super-widget' ), 'desc' => $lorem, 'badge' => esc_html__( 'Automobile', 'super-widget' ), 'badge_c1' => '#e5484d', 'badge_c2' => '#d02a3a', 'image' => array( 'url' => SUPER_WIDGET_URL . 'assets/img/pc-3.svg', 'id' => '' ), 'preset' => 'rocket', 'author' => esc_html__( 'John Doe', 'super-widget' ), 'date' => esc_html__( '2d ago', 'super-widget' ) ),
				),
			)
		);
		$this->add_group_control( Group_Control_Image_Size::get_type(), array( 'name' => 'thumb', 'default' => 'large', 'separator' => 'before' ) );
		$this->add_control( 'title_tag', array( 'label' => esc_html__( 'Title tag', 'super-widget' ), 'type' => Controls_Manager::SELECT, 'default' => 'h3', 'options' => array( 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'h5' => 'H5', 'p' => 'p', 'span' => 'span' ) ) );
		$this->end_controls_section();

		/* ------------------------- Content: Design & layout ------------------------- */
		$this->start_controls_section( 'section_layout', array( 'label' => esc_html__( 'Design & layout', 'super-widget' ) ) );
		$this->add_control( 'style', array( 'label' => esc_html__( 'Card design', 'super-widget' ), 'type' => Controls_Manager::SELECT, 'default' => 'blog', 'options' => $this->styles() ) );
		$this->add_responsive_control(
			'layout',
			array(
				'label'                => esc_html__( 'Layout (can differ per device)', 'super-widget' ),
				'type'                 => Controls_Manager::SELECT,
				'default'              => 'grid',
				'options'              => array( 'grid' => esc_html__( 'Grid', 'super-widget' ), 'slider' => esc_html__( 'Slider', 'super-widget' ) ),
				'selectors_dictionary' => array(
					'grid'   => '--pc-tpl:repeat(var(--pc-cols),minmax(0,1fr));--pc-flow:row;--pc-auto:auto;--pc-ovx:visible;--pc-ovy:visible;--pc-snap:none;--pc-ui:none;--pc-uig:none;--pc-vpad:0;--pc-vmar:0;',
					'slider' => '--pc-tpl:none;--pc-flow:column;--pc-auto:calc((100% - var(--pc-peek) - (var(--pc-cols) - 1) * var(--pc-gap)) / var(--pc-cols));--pc-ovx:auto;--pc-ovy:hidden;--pc-snap:x mandatory;--pc-ui:flex;--pc-uig:grid;--pc-vpad:14px 0 34px;--pc-vmar:-14px 0 -34px;',
				),
				'selectors'            => array( $root => '{{VALUE}}' ),
			)
		);
		$this->add_responsive_control(
			'cols',
			array(
				'label'          => esc_html__( 'Columns', 'super-widget' ),
				'type'           => Controls_Manager::SELECT,
				'default'        => '3',
				'tablet_default' => '2',
				'mobile_default' => '1',
				'options'        => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6' ),
				'selectors'      => array( $root => '--pc-cols: {{VALUE}};' ),
			)
		);
		$this->add_responsive_control(
			'peek',
			array(
				'label'       => esc_html__( 'Slider: show a bit of the next card (px)', 'super-widget' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px' ),
				'range'       => array( 'px' => array( 'min' => 0, 'max' => 120 ) ),
				'default'     => array( 'size' => 0, 'unit' => 'px' ),
				'selectors'   => array( $root => '--pc-peek: {{SIZE}}{{UNIT}};' ),
				'conditions'  => $this->any_slider_conditions(),
			)
		);
		$this->add_responsive_control(
			'align',
			array(
				'label'                => esc_html__( 'Content alignment', 'super-widget' ),
				'type'                 => Controls_Manager::CHOOSE,
				'options'              => array(
					'left'   => array( 'title' => esc_html__( 'Left', 'super-widget' ), 'icon' => 'eicon-text-align-left' ),
					'center' => array( 'title' => esc_html__( 'Center', 'super-widget' ), 'icon' => 'eicon-text-align-center' ),
					'right'  => array( 'title' => esc_html__( 'Right', 'super-widget' ), 'icon' => 'eicon-text-align-right' ),
				),
				'selectors_dictionary' => array(
					'left'   => '--pc-ta:left;--pc-ai:flex-start;--pc-jc:flex-start;',
					'center' => '--pc-ta:center;--pc-ai:center;--pc-jc:center;',
					'right'  => '--pc-ta:right;--pc-ai:flex-end;--pc-jc:flex-end;',
				),
				'selectors'            => array( $root => '{{VALUE}}' ),
			)
		);

		$this->heading( 'h_show', esc_html__( 'Show / hide', 'super-widget' ) );
		$auto = array( 'auto' => esc_html__( 'Auto (by design)', 'super-widget' ), 'yes' => esc_html__( 'Show', 'super-widget' ), 'no' => esc_html__( 'Hide', 'super-widget' ) );
		foreach ( array( 'image' => esc_html__( 'Picture', 'super-widget' ), 'icon' => esc_html__( 'Icon', 'super-widget' ), 'badge' => esc_html__( 'Badge', 'super-widget' ) ) as $k => $l ) {
			$this->add_control( 'show_' . $k, array( 'label' => $l, 'type' => Controls_Manager::SELECT, 'default' => 'auto', 'options' => $auto ) );
		}
		$this->switcher( 'show_text', esc_html__( 'Text', 'super-widget' ) );
		$this->add_control( 'show_meta', array( 'label' => esc_html__( 'Author / date', 'super-widget' ), 'type' => Controls_Manager::SELECT, 'default' => 'auto', 'options' => $auto ) );
		$this->switcher( 'show_button', esc_html__( 'Button', 'super-widget' ) );

		$this->heading( 'h_btn', esc_html__( 'Button', 'super-widget' ) );
		$this->add_control( 'btn_kind', array( 'label' => esc_html__( 'Button look', 'super-widget' ), 'type' => Controls_Manager::SELECT, 'default' => 'auto', 'options' => array( 'auto' => esc_html__( 'Automatic (by design)', 'super-widget' ), 'pill' => esc_html__( 'Pill with arrow', 'super-widget' ), 'bar' => esc_html__( 'Full-width bar', 'super-widget' ), 'circle' => esc_html__( 'Round arrow', 'super-widget' ), 'link' => esc_html__( 'Text link', 'super-widget' ) ), 'condition' => array( 'show_button' => 'yes' ) ) );
		$this->add_control( 'btn_text', array( 'label' => esc_html__( 'Button text', 'super-widget' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'Learn more', 'super-widget' ), 'condition' => array( 'show_button' => 'yes' ) ) );
		$this->add_control( 'btn_icon', array( 'label' => esc_html__( 'Button icon (empty = arrow)', 'super-widget' ), 'type' => Controls_Manager::ICONS, 'condition' => array( 'show_button' => 'yes' ) ) );

		$this->heading( 'h_slider', esc_html__( 'Slider', 'super-widget' ) );
		$anyc = $this->any_slider_conditions();
		$this->add_control( 'nav_type', array( 'label' => esc_html__( 'Navigation', 'super-widget' ), 'type' => Controls_Manager::SELECT, 'default' => 'both', 'options' => array( 'both' => esc_html__( 'Arrows + pagination dots', 'super-widget' ), 'arrows' => esc_html__( 'Arrows only', 'super-widget' ), 'dots' => esc_html__( 'Pagination dots only', 'super-widget' ), 'none' => esc_html__( 'None (swipe only)', 'super-widget' ) ), 'conditions' => $anyc ) );
		$this->add_control( 'arrow_pos', array( 'label' => esc_html__( 'Arrows position', 'super-widget' ), 'type' => Controls_Manager::SELECT, 'default' => 'bottom-center', 'options' => $this->nav_positions( true ), 'conditions' => $this->nav_conditions( array( 'both', 'arrows' ) ) ) );
		$this->add_control( 'dots_pos', array( 'label' => esc_html__( 'Pagination position', 'super-widget' ), 'type' => Controls_Manager::SELECT, 'default' => 'bottom-center', 'options' => $this->nav_positions( false ), 'conditions' => $this->nav_conditions( array( 'both', 'dots' ) ) ) );
		$this->add_control( 'autoplay', array( 'label' => esc_html__( 'Autoplay', 'super-widget' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => '', 'conditions' => $anyc ) );
		$this->add_control( 'autoplay_ms', array( 'label' => esc_html__( 'Autoplay delay (ms)', 'super-widget' ), 'type' => Controls_Manager::NUMBER, 'default' => 4000, 'min' => 1500, 'max' => 15000, 'condition' => array( 'autoplay' => 'yes' ) ) );
		$this->end_controls_section();

		/* ------------------------- Content: Animation ------------------------- */
		$this->start_controls_section( 'section_anim', array( 'label' => esc_html__( 'Animation', 'super-widget' ) ) );
		$this->add_control( 'entrance', array( 'label' => esc_html__( 'Entrance', 'super-widget' ), 'type' => Controls_Manager::SELECT, 'default' => 'fade-up', 'options' => $this->entrances() ) );
		$this->slider( 'anim_dur', esc_html__( 'Entrance speed (ms)', 'super-widget' ), $root, '--pc-dur: {{SIZE}}ms;', 200, 1800, 700, 'px', array( 'entrance!' => 'none' ), false, 50 );
		$this->slider( 'anim_stagger', esc_html__( 'Delay between cards (ms)', 'super-widget' ), $root, '--pc-stagger: {{SIZE}}ms;', 0, 400, 80, 'px', array( 'entrance!' => 'none' ), false, 10 );
		$this->add_control( 'hover', array( 'label' => esc_html__( 'Hover', 'super-widget' ), 'type' => Controls_Manager::SELECT, 'default' => 'both', 'separator' => 'before', 'options' => array( 'none' => esc_html__( 'None', 'super-widget' ), 'lift' => esc_html__( 'Lift card', 'super-widget' ), 'zoom' => esc_html__( 'Zoom picture', 'super-widget' ), 'both' => esc_html__( 'Lift + zoom', 'super-widget' ) ) ) );
		$this->slider( 'hover_lift', esc_html__( 'Lift distance (px)', 'super-widget' ), $root, '--pc-lift: -{{SIZE}}px;', 0, 24, 6, 'px', array( 'hover' => array( 'lift', 'both' ) ), false );
		$this->slider( 'hover_zoom', esc_html__( 'Picture zoom (%)', 'super-widget' ), $root, '--pc-zoom: calc({{SIZE}} / 100);', 100, 130, 106, 'px', array( 'hover' => array( 'zoom', 'both' ) ), false );
		$this->add_control( 'icon_hover', array( 'label' => esc_html__( 'Icon on hover', 'super-widget' ), 'type' => Controls_Manager::SELECT, 'default' => 'pop', 'options' => array( 'none' => esc_html__( 'None', 'super-widget' ), 'pop' => esc_html__( 'Pop', 'super-widget' ), 'spin' => esc_html__( 'Spin', 'super-widget' ), 'wiggle' => esc_html__( 'Wiggle', 'super-widget' ) ) ) );
		$this->switcher( 'shine', esc_html__( 'Shine sweep on hover', 'super-widget' ), '' );
		$this->slider( 'hover_ms', esc_html__( 'Hover speed (ms)', 'super-widget' ), $root, '--pc-hov: {{SIZE}}ms;', 100, 1000, 350, 'px', array(), false, 50 );
		$this->end_controls_section();

		/* ------------------------- Style: Cards ------------------------- */
		$this->start_controls_section( 'style_card', array( 'label' => esc_html__( 'Cards', 'super-widget' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->color( 'accent_all', esc_html__( 'Accent color', 'super-widget' ), array( $root => '--pc-accent: {{VALUE}};' ) );
		$this->slider( 'gap', esc_html__( 'Gap', 'super-widget' ), $root, '--pc-gap: {{SIZE}}{{UNIT}};', 0, 80, 22 );
		$this->slider( 'card_minh', esc_html__( 'Minimum height', 'super-widget' ), $root, '--pc-minh: {{SIZE}}{{UNIT}};', 0, 700 );
		$this->slider( 'radius', esc_html__( 'Corner radius', 'super-widget' ), $root, '--pc-radius: {{SIZE}}{{UNIT}};', 0, 80 );
		$this->add_responsive_control( 'card_pad', array( 'label' => esc_html__( 'Card padding', 'super-widget' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', 'em' ), 'selectors' => array( $card => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'body_pad', array( 'label' => esc_html__( 'Text area padding', 'super-widget' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', 'em' ), 'selectors' => array( '{{WRAPPER}} .spw-pc__body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
		$this->slider( 'inner_gap', esc_html__( 'Space between parts', 'super-widget' ), '{{WRAPPER}} .spw-pc__body', 'gap: {{SIZE}}{{UNIT}};', 0, 40 );
		$this->add_group_control( Group_Control_Background::get_type(), array( 'name' => 'card_bg', 'types' => array( 'classic', 'gradient' ), 'selector' => $card ) );
		$this->add_group_control( Group_Control_Border::get_type(), array( 'name' => 'card_border', 'selector' => $card ) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array( 'name' => 'card_shadow', 'selector' => $card ) );
		$this->heading( 'h_card_hover', esc_html__( 'On hover', 'super-widget' ) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array( 'name' => 'card_shadow_h', 'label' => esc_html__( 'Hover shadow', 'super-widget' ), 'selector' => $card . ':hover, ' . $card . ':focus-visible' ) );
		$this->color( 'card_bg_h', esc_html__( 'Hover background', 'super-widget' ), array( $card . ':hover, ' . $card . ':focus-visible' => 'background: {{VALUE}};' ) );
		$this->color( 'card_border_h', esc_html__( 'Hover border color', 'super-widget' ), array( $card . ':hover, ' . $card . ':focus-visible' => 'border-color: {{VALUE}};' ) );
		$this->end_controls_section();

		/* ------------------------- Style: Picture ------------------------- */
		$img = '{{WRAPPER}} .spw-pc__img';
		$this->start_controls_section( 'style_image', array( 'label' => esc_html__( 'Picture', 'super-widget' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->slider( 'img_h', esc_html__( 'Height', 'super-widget' ), $root, '--pc-img-h: {{SIZE}}{{UNIT}};', 80, 500 );
		$this->slider( 'img_r', esc_html__( 'Picture corner radius', 'super-widget' ), $root, '--pc-img-r: {{SIZE}}{{UNIT}};', 0, 60 );
		$this->slider( 'img_inset', esc_html__( 'Space around the picture', 'super-widget' ), $root, '--pc-inset: {{SIZE}}{{UNIT}};', 0, 40 );
		$this->add_control( 'img_fit', array( 'label' => esc_html__( 'Picture fit', 'super-widget' ), 'type' => Controls_Manager::SELECT, 'default' => 'cover', 'options' => array( 'cover' => esc_html__( 'Fill (crop)', 'super-widget' ), 'contain' => esc_html__( 'Whole picture', 'super-widget' ) ), 'selectors' => array( $img => 'object-fit: {{VALUE}};' ) ) );
		$this->color( 'ov_color', esc_html__( 'Overlay color (Overlay design)', 'super-widget' ), array( $root => '--pc-ov: {{VALUE}};' ), array( 'style' => 'overlay' ), 'before' );
		$this->slider( 'ov_str', esc_html__( 'Overlay strength (%)', 'super-widget' ), $root, '--pc-ov-s: calc({{SIZE}} / 100);', 0, 100, null, '%', array( 'style' => 'overlay' ), false );
		$this->add_group_control( Group_Control_Css_Filter::get_type(), array( 'name' => 'img_filter', 'selector' => $img ) );
		$this->add_group_control( Group_Control_Css_Filter::get_type(), array( 'name' => 'img_filter_h', 'label' => esc_html__( 'Picture filter on hover', 'super-widget' ), 'selector' => $card . ':hover .spw-pc__img, ' . $card . ':focus-visible .spw-pc__img' ) );
		$this->end_controls_section();

		/* ------------------------- Style: Icon ------------------------- */
		$ic = '{{WRAPPER}} .spw-pc__icon';
		$this->start_controls_section( 'style_icon', array( 'label' => esc_html__( 'Icon', 'super-widget' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->slider( 'ic_box', esc_html__( 'Box size', 'super-widget' ), $root, '--pc-ic-box: {{SIZE}}{{UNIT}};', 28, 140 );
		$this->slider( 'ic_size', esc_html__( 'Icon size', 'super-widget' ), $root, '--pc-ic: {{SIZE}}{{UNIT}};', 12, 80 );
		$this->slider( 'ic_r', esc_html__( 'Box corner radius', 'super-widget' ), $root, '--pc-ic-r: {{SIZE}}{{UNIT}};', 0, 70 );
		$this->color( 'ic_color', esc_html__( 'Icon color', 'super-widget' ), array( $root => '--pc-ic-c: {{VALUE}};' ) );
		$this->add_group_control( Group_Control_Background::get_type(), array( 'name' => 'ic_bg', 'types' => array( 'classic', 'gradient' ), 'selector' => $ic ) );
		$this->add_group_control( Group_Control_Border::get_type(), array( 'name' => 'ic_border', 'selector' => $ic ) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array( 'name' => 'ic_shadow', 'selector' => $ic ) );
		$this->color( 'ic_color_h', esc_html__( 'Icon color on hover', 'super-widget' ), array( $card . ':hover .spw-pc__icon' => 'color: {{VALUE}};' ), array(), 'before' );
		$this->color( 'ic_bg_h', esc_html__( 'Box color on hover', 'super-widget' ), array( $card . ':hover .spw-pc__icon' => 'background: {{VALUE}};' ) );
		$this->end_controls_section();

		/* ------------------------- Style: Text ------------------------- */
		$this->start_controls_section( 'style_text', array( 'label' => esc_html__( 'Title & text', 'super-widget' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'title_typo', 'selector' => '{{WRAPPER}} .spw-pc__title' ) );
		$this->color( 'title_color', esc_html__( 'Title color', 'super-widget' ), array( '{{WRAPPER}} .spw-pc__title' => 'color: {{VALUE}};' ) );
		$this->color( 'title_color_h', esc_html__( 'Title color on hover', 'super-widget' ), array( $card . ':hover .spw-pc__title, ' . $card . ':focus-visible .spw-pc__title' => 'color: {{VALUE}};' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'text_typo', 'selector' => '{{WRAPPER}} .spw-pc__text', 'separator' => 'before' ) );
		$this->color( 'text_color', esc_html__( 'Text color', 'super-widget' ), array( '{{WRAPPER}} .spw-pc__text' => 'color: {{VALUE}};' ) );
		$this->slider( 'text_lines', esc_html__( 'Text lines (0 = all)', 'super-widget' ), $root, '--pc-lines: {{SIZE}};', 0, 10, 0, 'px', array(), false );
		$this->end_controls_section();

		/* ------------------------- Style: Badge & meta ------------------------- */
		$this->start_controls_section( 'style_badge', array( 'label' => esc_html__( 'Badge & author', 'super-widget' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$b = '{{WRAPPER}} .spw-pc__badge';
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'badge_typo', 'selector' => $b ) );
		$this->color( 'badge_color', esc_html__( 'Badge text color', 'super-widget' ), array( $b => 'color: {{VALUE}};' ) );
		$this->color( 'badge_bg', esc_html__( 'Badge background (overrides gradient)', 'super-widget' ), array( $b => 'background: {{VALUE}};' ) );
		$this->add_responsive_control( 'badge_pad', array( 'label' => esc_html__( 'Badge padding', 'super-widget' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', 'em' ), 'selectors' => array( $b => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
		$this->slider( 'badge_r', esc_html__( 'Badge corner radius', 'super-widget' ), $b, 'border-radius: {{SIZE}}{{UNIT}};', 0, 40, null, 'px', array(), false );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'meta_typo', 'label' => esc_html__( 'Author / date typography', 'super-widget' ), 'selector' => '{{WRAPPER}} .spw-pc__meta, {{WRAPPER}} .spw-pc__who', 'separator' => 'before' ) );
		$this->color( 'meta_color', esc_html__( 'Author / date color', 'super-widget' ), array( '{{WRAPPER}} .spw-pc__meta, {{WRAPPER}} .spw-pc__date' => 'color: {{VALUE}};' ) );
		$this->color( 'name_color', esc_html__( 'Author name color (blog)', 'super-widget' ), array( '{{WRAPPER}} .spw-pc__name' => 'color: {{VALUE}};' ) );
		$this->slider( 'avatar_size', esc_html__( 'Photo size', 'super-widget' ), $root, '--pc-av: {{SIZE}}{{UNIT}};', 20, 80, null, 'px', array(), false );
		$this->end_controls_section();

		/* ------------------------- Style: Button ------------------------- */
		$btn = '{{WRAPPER}} .spw-pc__btn';
		$this->start_controls_section( 'style_btn', array( 'label' => esc_html__( 'Button', 'super-widget' ), 'tab' => Controls_Manager::TAB_STYLE, 'condition' => array( 'show_button' => 'yes' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'btn_typo', 'selector' => $btn . ' .spw-pc__btn-t' ) );
		$this->color( 'btn_color', esc_html__( 'Text color', 'super-widget' ), array( $btn => 'color: {{VALUE}};' ) );
		$this->color( 'btn_bg', esc_html__( 'Background', 'super-widget' ), array( $btn => 'background: {{VALUE}};' ) );
		$this->color( 'btn_color_h', esc_html__( 'Text color on hover', 'super-widget' ), array( $card . ':hover .spw-pc__btn, ' . $card . ':focus-visible .spw-pc__btn' => 'color: {{VALUE}};' ) );
		$this->color( 'btn_bg_h', esc_html__( 'Background on hover', 'super-widget' ), array( $card . ':hover .spw-pc__btn, ' . $card . ':focus-visible .spw-pc__btn' => 'background: {{VALUE}};' ) );
		$this->color( 'arrow_color', esc_html__( 'Arrow color', 'super-widget' ), array( '{{WRAPPER}} .spw-pc__arrow' => 'color: {{VALUE}};' ), array(), 'before' );
		$this->color( 'arrow_bg', esc_html__( 'Arrow circle color', 'super-widget' ), array( '{{WRAPPER}} .spw-pc__arrow' => 'background: {{VALUE}};' ) );
		$this->slider( 'btn_r', esc_html__( 'Corner radius', 'super-widget' ), $btn, 'border-radius: {{SIZE}}{{UNIT}};', 0, 60, null, 'px', array(), false );
		$this->slider( 'btn_size', esc_html__( 'Round button size', 'super-widget' ), $root, '--pc-cbtn: {{SIZE}}{{UNIT}};', 28, 90, null, 'px', array(), false );
		$this->add_responsive_control( 'btn_pad', array( 'label' => esc_html__( 'Padding', 'super-widget' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', 'em' ), 'selectors' => array( $btn => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
		$this->add_group_control( Group_Control_Border::get_type(), array( 'name' => 'btn_border', 'selector' => $btn ) );
		$this->end_controls_section();

		/* ------------------------- Style: Slider controls ------------------------- */
		$nb = '{{WRAPPER}} .spw-pc__btnav';
		$this->start_controls_section( 'style_nav', array( 'label' => esc_html__( 'Arrows & dots', 'super-widget' ), 'tab' => Controls_Manager::TAB_STYLE, 'conditions' => $this->any_slider_conditions() ) );
		$this->slider( 'nav_gap', esc_html__( 'Distance from the cards (top / bottom)', 'super-widget' ), $root, '--pc-navm: {{SIZE}}{{UNIT}};', 0, 80, 22, 'px', array(), false );
		$this->slider( 'nav_inset', esc_html__( 'Distance from the edge (middle positions)', 'super-widget' ), $root, '--pc-navi: {{SIZE}}{{UNIT}};', 0, 60, 8, 'px', array(), false );
		$this->slider( 'nav_size', esc_html__( 'Arrow size', 'super-widget' ), $root, '--pc-nav: {{SIZE}}{{UNIT}};', 28, 72, 44, 'px', array(), false );
		$this->color( 'nav_color', esc_html__( 'Arrow icon', 'super-widget' ), array( $nb => 'color: {{VALUE}};' ) );
		$this->color( 'nav_bg', esc_html__( 'Arrow background', 'super-widget' ), array( $nb => 'background-color: {{VALUE}};' ) );
		$this->color( 'nav_bg_h', esc_html__( 'Arrow hover background', 'super-widget' ), array( $nb . ':hover, ' . $nb . ':focus-visible' => 'background-color: {{VALUE}};' ) );
		$this->color( 'dot_color', esc_html__( 'Dot color', 'super-widget' ), array( $root => '--pc-dot: {{VALUE}};' ) );
		$this->color( 'dot_active', esc_html__( 'Active dot color', 'super-widget' ), array( $root => '--pc-dot-on: {{VALUE}};' ) );
		$this->end_controls_section();
	}

	/* ==================================================================
	 * Render
	 * ================================================================== */

	private function arrow_svg() {
		return '<svg class="spw-pc__pi" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';
	}

	private function small_svg( $paths ) {
		return '<svg class="spw-pc__pi" viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">' . $paths . '</svg>';
	}

	protected function render() {
		$s     = $this->get_settings_for_display();
		$items = ! empty( $s['items'] ) && is_array( $s['items'] ) ? $s['items'] : array();
		if ( ! $items ) {
			return;
		}

		$styles = $this->styles();
		$style  = isset( $s['style'] ) && isset( $styles[ $s['style'] ] ) ? $s['style'] : 'blog';
		// The layout can differ per device, so the slider parts are output when ANY device uses a slider.
		$slider = false;
		foreach ( array( 'layout', 'layout_widescreen', 'layout_laptop', 'layout_tablet_extra', 'layout_tablet', 'layout_mobile_extra', 'layout_mobile' ) as $lk ) {
			if ( isset( $s[ $lk ] ) && 'slider' === $s[ $lk ] ) {
				$slider = true;
			}
		}
		$tags   = array( 'h2', 'h3', 'h4', 'h5', 'p', 'span' );
		$tag    = isset( $s['title_tag'] ) && in_array( $s['title_tag'], $tags, true ) ? $s['title_tag'] : 'h3';

		$on = function ( $key ) use ( $s ) {
			return ! isset( $s[ $key ] ) || 'yes' === $s[ $key ];
		};

		// Each design shows the parts its reference look uses; "Show" / "Hide" override that.
		$auto_on = array(
			'image' => array( 'blog', 'clean', 'post', 'overlay', 'icon-image', 'icon-center' ),
			'badge' => array( 'blog' ),
			'meta'  => array( 'blog', 'post' ),
			'icon'  => array( 'icon-image', 'icon-center', 'glass', 'soft', 'accent' ),
		);
		$show = function ( $key ) use ( $s, $style, $auto_on ) {
			$v = isset( $s[ 'show_' . $key ] ) ? $s[ 'show_' . $key ] : 'auto';
			if ( 'yes' === $v ) {
				return true;
			}
			if ( 'auto' !== $v ) {
				return false;
			}
			return in_array( $style, $auto_on[ $key ], true );
		};

		$kinds = array( 'pill', 'bar', 'circle', 'link' );
		$kind  = isset( $s['btn_kind'] ) && in_array( $s['btn_kind'], $kinds, true ) ? $s['btn_kind'] : $this->default_kind( $style );
		$btn_on = $on( 'show_button' ) && 'none' !== $kind;

		$entrance = isset( $s['entrance'] ) && isset( $this->entrances()[ $s['entrance'] ] ) ? $s['entrance'] : 'fade-up';
		$hover    = isset( $s['hover'] ) && in_array( $s['hover'], array( 'none', 'lift', 'zoom', 'both' ), true ) ? $s['hover'] : 'both';
		$ihover   = isset( $s['icon_hover'] ) && in_array( $s['icon_hover'], array( 'none', 'pop', 'spin', 'wiggle' ), true ) ? $s['icon_hover'] : 'pop';
		$shine    = isset( $s['shine'] ) && 'yes' === $s['shine'];
		$noanim   = (bool) Super_Widget_Settings::get( 'disable_animations' );
		if ( $noanim ) {
			$entrance = 'none';
			$hover    = 'none';
			$ihover   = 'none';
			$shine    = false;
		}

		$ntype    = isset( $s['nav_type'] ) && in_array( $s['nav_type'], array( 'both', 'arrows', 'dots', 'none' ), true ) ? $s['nav_type'] : 'both';
		$arrows   = $slider && in_array( $ntype, array( 'both', 'arrows' ), true );
		$dots     = $slider && in_array( $ntype, array( 'both', 'dots' ), true );
		$apos     = isset( $s['arrow_pos'] ) && isset( $this->nav_positions( true )[ $s['arrow_pos'] ] ) ? $s['arrow_pos'] : 'bottom-center';
		$dpos     = isset( $s['dots_pos'] ) && isset( $this->nav_positions( false )[ $s['dots_pos'] ] ) ? $s['dots_pos'] : 'bottom-center';
		$autoplay = $slider && isset( $s['autoplay'] ) && 'yes' === $s['autoplay'] && ! $noanim;
		$ms       = isset( $s['autoplay_ms'] ) ? max( 1500, min( 15000, (int) $s['autoplay_ms'] ) ) : 4000;

		$classes = array( 'spw-pc', 'spw-pc--' . $style, 'spw-pc--anim-' . $entrance, 'spw-pc--ih-' . $ihover );
		if ( $slider ) {
			$classes[] = 'spw-pc--sl';
		}
		if ( $btn_on ) {
			$classes[] = 'spw-pc--btn-' . $kind;
			$classes[] = 'has-btn';
		}
		if ( 'lift' === $hover || 'both' === $hover ) {
			$classes[] = 'spw-pc--hover-lift';
		}
		if ( 'zoom' === $hover || 'both' === $hover ) {
			$classes[] = 'spw-pc--hover-zoom';
		}
		if ( $shine ) {
			$classes[] = 'spw-pc--shine';
		}

		$presets  = $this->presets();
		$btn_text = isset( $s['btn_text'] ) ? (string) $s['btn_text'] : esc_html__( 'Learn more', 'super-widget' );

		$svg_prev = '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>';
		$svg_next = '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>';
		$btn      = function ( $dir, $extra = '' ) use ( $svg_prev, $svg_next ) {
			$label = 'prev' === $dir ? esc_attr__( 'Previous', 'super-widget' ) : esc_attr__( 'Next', 'super-widget' );
			return '<button type="button" class="spw-pc__btnav spw-pc__btnav--' . $dir . $extra . '" aria-label="' . $label . '">' . ( 'prev' === $dir ? $svg_prev : $svg_next ) . '</button>';
		};

		// Every position is a "cell": top / middle / bottom  x  left / center / right.
		// Arrows and dots that share a cell sit together: previous, dots, next.
		$cells = array();
		$sides = '';
		if ( $arrows ) {
			if ( 'middle-center' === $apos ) {
				$sides = $btn( 'prev', ' spw-pc__btnav--side' ) . $btn( 'next', ' spw-pc__btnav--side' );
			} else {
				$cells[ $apos ]['arrows'] = true;
			}
		}
		if ( $dots ) {
			$cells[ $dpos ]['dots'] = true;
		}
		$cell_html = function ( $key ) use ( $cells, $btn ) {
			if ( empty( $cells[ $key ] ) ) {
				return '';
			}
			$vertical = false !== strpos( $key, 'middle' );
			$html     = '';
			if ( ! empty( $cells[ $key ]['arrows'] ) ) {
				$html .= $btn( 'prev' );
			}
			if ( ! empty( $cells[ $key ]['dots'] ) ) {
				$html .= '<div class="spw-pc__dots' . ( $vertical ? ' spw-pc__dots--v' : '' ) . '"></div>';
			}
			if ( ! empty( $cells[ $key ]['arrows'] ) ) {
				$html .= $btn( 'next' );
			}
			return $html;
		};
		$row_html = function ( $v ) use ( $cell_html ) {
			$l = $cell_html( $v . '-left' );
			$c = $cell_html( $v . '-center' );
			$r = $cell_html( $v . '-right' );
			if ( '' === $l . $c . $r ) {
				return '';
			}
			return '<div class="spw-pc__navrow spw-pc__navrow--' . $v . '"><div class="spw-pc__navcell spw-pc__navcell--l">' . $l . '</div><div class="spw-pc__navcell spw-pc__navcell--c">' . $c . '</div><div class="spw-pc__navcell spw-pc__navcell--r">' . $r . '</div></div>';
		};

		$ml = $cell_html( 'middle-left' );
		$mr = $cell_html( 'middle-right' );

		echo '<div class="' . esc_attr( implode( ' ', $classes ) ) . '" ' . ( $slider ? ' data-slider="1"' : '' ) . ( $autoplay ? ' data-autoplay="' . esc_attr( $ms ) . '"' : '' ) . '>';
		echo $row_html( 'top' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<div class="spw-pc__stage"><div class="spw-pc__viewport"><div class="spw-pc__track"' . ( $slider ? ' role="list"' : '' ) . '>';

		foreach ( $items as $i => $item ) {
			$title  = isset( $item['title'] ) ? (string) $item['title'] : '';
			$desc   = isset( $item['desc'] ) ? (string) $item['desc'] : '';
			$badge  = $show( 'badge' ) && isset( $item['badge'] ) ? (string) $item['badge'] : '';
			$author = $show( 'meta' ) && isset( $item['author'] ) ? (string) $item['author'] : '';
			$date   = $show( 'meta' ) && isset( $item['date'] ) ? (string) $item['date'] : '';
			$id     = isset( $item['_id'] ) ? sanitize_html_class( $item['_id'] ) : 'i' . (int) $i;
			$link   = isset( $item['link'] ) && is_array( $item['link'] ) ? $item['link'] : array();
			$url    = ! empty( $link['url'] ) ? esc_url( $link['url'] ) : '';
			$btn_t  = isset( $item['btn_text'] ) && '' !== $item['btn_text'] ? (string) $item['btn_text'] : $btn_text;

			// Picture.
			$img_html = '';
			if ( $show( 'image' ) && isset( $item['image'] ) && ( ! empty( $item['image']['id'] ) || ! empty( $item['image']['url'] ) ) ) {
				$img_html = Group_Control_Image_Size::get_attachment_image_html( array_merge( $s, array( 'thumb_image' => $item['image'] ) ), 'thumb', 'thumb_image' );
				if ( $img_html ) {
					if ( preg_match( '/<img[^>]*\sclass="/', $img_html ) ) {
						$img_html = preg_replace( '/(<img[^>]*\s)class="/', '$1class="spw-pc__img ', $img_html, 1 );
					} else {
						$img_html = preg_replace( '/<img\s/', '<img class="spw-pc__img" ', $img_html, 1 );
					}
				}
			}

			// Icon: your own icon wins over the built-in one.
			$icon_html = '';
			if ( $show( 'icon' ) ) {
				if ( ! empty( $item['icon']['value'] ) ) {
					ob_start();
					Icons_Manager::render_icon( $item['icon'], array( 'aria-hidden' => 'true' ) );
					$icon_html = '<span class="spw-pc__icon">' . ob_get_clean() . '</span>';
				} elseif ( ! empty( $item['preset'] ) && isset( $presets[ $item['preset'] ] ) ) {
					$icon_html = '<span class="spw-pc__icon"><svg class="spw-pc__pi" viewBox="0 0 24 24" width="1em" height="1em" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $presets[ $item['preset'] ] . '</svg></span>';
				}
			}

			$overlap = in_array( $style, array( 'icon-image', 'icon-center' ), true ) && '' !== $img_html && '' !== $icon_html;
			$cls     = 'spw-pc__card' . ( $overlap ? ' has-over' : '' ) . ( '' !== $img_html ? ' has-img' : ' no-img' );
			$slide   = 'spw-pc__slide elementor-repeater-item-' . $id;

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

			// Picture (+ icon that overlaps it).
			if ( '' !== $img_html ) {
				echo '<div class="spw-pc__media"><div class="spw-pc__imgwrap">' . $img_html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				if ( $overlap ) {
					echo $icon_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				echo '</div>';
			}
			if ( '' === $img_html && 'overlay' === $style ) {
				echo '<div class="spw-pc__media"><div class="spw-pc__imgwrap"></div></div>';
			}
			if ( $shine ) {
				echo '<span class="spw-pc__shine" aria-hidden="true"></span>';
			}
			if ( '' !== $badge && 'overlay' === $style ) {
				echo '<span class="spw-pc__badge">' . esc_html( $badge ) . '</span>';
			}

			echo '<div class="spw-pc__body">';
			if ( '' !== $icon_html && ! $overlap ) {
				echo $icon_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			if ( '' !== $badge && 'overlay' !== $style ) {
				echo '<span class="spw-pc__badge">' . esc_html( $badge ) . '</span>';
			}

			$meta = '';
			if ( 'blog' !== $style && ( '' !== $author || '' !== $date ) ) {
				$meta = '<div class="spw-pc__meta">';
				if ( '' !== $author ) {
					$meta .= '<span class="spw-pc__mi">' . $this->small_svg( '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>' ) . esc_html( $author ) . '</span>';
				}
				if ( '' !== $date ) {
					$meta .= '<span class="spw-pc__mi">' . $this->small_svg( '<rect x="4" y="5" width="16" height="16" rx="2"/><path d="M4 10h16M8 3v4M16 3v4"/>' ) . esc_html( $date ) . '</span>';
				}
				$meta .= '</div>';
			}
			if ( 'post' === $style && '' !== $meta ) {
				echo $meta; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			if ( '' !== $title ) {
				echo '<' . tag_escape( $tag ) . ' class="spw-pc__title">' . esc_html( $title ) . '</' . tag_escape( $tag ) . '>';
			}
			if ( $on( 'show_text' ) && '' !== $desc ) {
				echo '<p class="spw-pc__text">' . nl2br( esc_html( $desc ) ) . '</p>';
			}
			if ( 'post' !== $style && '' !== $meta ) {
				echo $meta; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			// Blog: author line with a round photo (or the first letter).
			if ( 'blog' === $style && ( '' !== $author || '' !== $date ) ) {
				$av = '';
				if ( ! empty( $item['avatar']['id'] ) ) {
					$av = (string) wp_get_attachment_image_url( (int) $item['avatar']['id'], 'thumbnail' );
				}
				if ( '' === $av && ! empty( $item['avatar']['url'] ) ) {
					$av = (string) $item['avatar']['url'];
				}
				echo '<div class="spw-pc__who">';
				if ( '' !== $av ) {
					echo '<img class="spw-pc__av" src="' . esc_url( $av ) . '" alt="" loading="lazy" decoding="async">';
				} elseif ( '' !== $author ) {
					echo '<span class="spw-pc__av spw-pc__av--i" aria-hidden="true">' . esc_html( function_exists( 'mb_substr' ) ? mb_substr( $author, 0, 1 ) : substr( $author, 0, 1 ) ) . '</span>';
				}
				echo '<span class="spw-pc__who-t">';
				if ( '' !== $author ) {
					echo '<span class="spw-pc__name">' . esc_html( $author ) . '</span>';
				}
				if ( '' !== $date ) {
					echo '<span class="spw-pc__date">' . esc_html( $date ) . '</span>';
				}
				echo '</span></div>';
			}

			if ( $btn_on ) {
				$icon = '';
				if ( ! empty( $s['btn_icon']['value'] ) ) {
					ob_start();
					Icons_Manager::render_icon( $s['btn_icon'], array( 'aria-hidden' => 'true' ) );
					$icon = ob_get_clean();
				} else {
					$icon = $this->arrow_svg();
				}
				echo '<div class="spw-pc__foot"><span class="spw-pc__btn"><span class="spw-pc__btn-t">' . esc_html( $btn_t ) . '</span><span class="spw-pc__arrow">' . $icon . '</span></span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			echo '</div>';

			echo $url ? '</a>' : '</div>';
			echo '</div>';
		}

		echo '</div></div>';

		// Middle positions sit on top of the cards, inside the stage.
		echo $sides; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '' !== $ml ? '<div class="spw-pc__mid spw-pc__mid--l">' . $ml . '</div>' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '' !== $mr ? '<div class="spw-pc__mid spw-pc__mid--r">' . $mr . '</div>' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>'; // .spw-pc__stage
		echo $row_html( 'bottom' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
	}
}
