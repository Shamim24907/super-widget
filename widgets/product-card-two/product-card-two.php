<?php
/**
 * Product Card 2 widget.
 *
 * A responsive grid of clean product cards: category pill on the image,
 * serif title, price and a full-width Add to Cart button. Products come from
 * WooCommerce (hand-picked or whole collections) or from a plain repeater.
 * The product query is shared with the Card Grid widget.
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
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Repeater;
use Elementor\Utils;
use Elementor\Widget_Base;

// Shared WooCommerce helpers (query + product -> card data).
if ( ! class_exists( 'SPW_Card_Grid_Products' ) ) {
	require_once dirname( __DIR__ ) . '/card-grid/products.php';
}

class SPW_Product_Card_Two_Widget extends Widget_Base {

	public function get_name() {
		return 'spw-product-card-two';
	}

	public function get_title() {
		return esc_html( Super_Widget_Settings::widget_title( 'product-card-two', __( 'Product Card 2', 'super-widget' ) ) );
	}

	public function get_icon() {
		return 'eicon-products';
	}

	public function get_categories() {
		return array( 'super-widget' );
	}

	public function get_keywords() {
		return array( 'product', 'card', 'shop', 'woocommerce', 'grid', 'cart' );
	}

	public function get_style_depends() {
		return array( 'spw-global', 'spw-product-card-two' );
	}

	public function get_script_depends() {
		return array( 'spw-product-card-two' );
	}

	/* ==================================================================
	 * Controls
	 * ================================================================== */

	protected function register_controls() {
		$this->controls_source();
		$this->controls_cards();
		$this->controls_layout();
		$this->controls_content();
		$this->controls_animation();

		$this->style_card();
		$this->style_image();
		$this->style_badge();
		$this->style_title();
		$this->style_description();
		$this->style_price();
		$this->style_button();
	}

	/* ---------------------------- Content ---------------------------- */

	private function controls_source() {
		$woo = SPW_Card_Grid_Products::is_active();

		$this->start_controls_section(
			'section_source',
			array(
				'label' => esc_html__( 'Products', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$sources = array(
			'custom' => esc_html__( 'Custom cards (manual)', 'super-widget' ),
		);
		if ( $woo ) {
			$sources = array(
				'manual' => esc_html__( 'Specific products', 'super-widget' ),
				'cat'    => esc_html__( 'Collection (category)', 'super-widget' ),
				'tag'    => esc_html__( 'Collection (tag)', 'super-widget' ),
			) + $sources;
		}

		$this->add_control(
			'source',
			array(
				'label'   => esc_html__( 'Source', 'super-widget' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'custom',
				'options' => $sources,
			)
		);

		if ( ! $woo ) {
			$this->add_control(
				'woo_notice',
				array(
					'type'            => Controls_Manager::RAW_HTML,
					'raw'             => esc_html__( 'Install and activate WooCommerce to pick real products and collections.', 'super-widget' ),
					'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
				)
			);
			$this->end_controls_section();
			return;
		}

		$this->add_control(
			'products_manual',
			array(
				'label'       => esc_html__( 'Products', 'super-widget' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => SPW_Card_Grid_Products::product_options(),
				'condition'   => array( 'source' => 'manual' ),
				'description' => esc_html__( 'Cards follow the order you pick them in.', 'super-widget' ),
			)
		);

		$this->add_control(
			'products_ids_text',
			array(
				'label'       => esc_html__( 'More product IDs (optional)', 'super-widget' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => '123, 456',
				'label_block' => true,
				'condition'   => array( 'source' => 'manual' ),
			)
		);

		$this->add_control(
			'products_cats',
			array(
				'label'       => esc_html__( 'Collections', 'super-widget' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => SPW_Card_Grid_Products::term_options( 'product_cat' ),
				'condition'   => array( 'source' => 'cat' ),
			)
		);

		$this->add_control(
			'products_tags',
			array(
				'label'       => esc_html__( 'Collections (tags)', 'super-widget' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => SPW_Card_Grid_Products::term_options( 'product_tag' ),
				'condition'   => array( 'source' => 'tag' ),
			)
		);

		$this->add_control(
			'products_limit',
			array(
				'label'     => esc_html__( 'Number of products', 'super-widget' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 8,
				'min'       => 1,
				'max'       => 48,
				'condition' => array( 'source' => array( 'cat', 'tag' ) ),
			)
		);

		$this->add_control(
			'products_orderby',
			array(
				'label'     => esc_html__( 'Order by', 'super-widget' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'date',
				'options'   => array(
					'date'       => esc_html__( 'Newest', 'super-widget' ),
					'title'      => esc_html__( 'Title', 'super-widget' ),
					'price'      => esc_html__( 'Price', 'super-widget' ),
					'sales'      => esc_html__( 'Best selling', 'super-widget' ),
					'menu_order' => esc_html__( 'Menu order', 'super-widget' ),
					'rand'       => esc_html__( 'Random', 'super-widget' ),
				),
				'condition' => array( 'source' => array( 'cat', 'tag' ) ),
			)
		);

		$this->add_control(
			'products_order',
			array(
				'label'     => esc_html__( 'Order', 'super-widget' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'DESC',
				'options'   => array(
					'DESC' => esc_html__( 'Descending', 'super-widget' ),
					'ASC'  => esc_html__( 'Ascending', 'super-widget' ),
				),
				'condition' => array(
					'source'            => array( 'cat', 'tag' ),
					'products_orderby!' => 'rand',
				),
			)
		);

		$this->add_control(
			'products_hide_oos',
			array(
				'label'        => esc_html__( 'Hide out-of-stock products', 'super-widget' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'condition'    => array( 'source!' => 'custom' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_cards() {
		$this->start_controls_section(
			'section_cards',
			array(
				'label'     => esc_html__( 'Custom Cards', 'super-widget' ),
				'tab'       => Controls_Manager::TAB_CONTENT,
				'condition' => array( 'source' => 'custom' ),
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'card_image',
			array(
				'label'   => esc_html__( 'Image', 'super-widget' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => array( 'url' => Utils::get_placeholder_image_src() ),
			)
		);

		$repeater->add_control(
			'card_badge',
			array(
				'label'       => esc_html__( 'Badge', 'super-widget' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Oral Care', 'super-widget' ),
				'label_block' => true,
			)
		);

		$repeater->add_control(
			'card_title',
			array(
				'label'       => esc_html__( 'Title', 'super-widget' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Product name', 'super-widget' ),
				'label_block' => true,
			)
		);

		$repeater->add_control(
			'card_description',
			array(
				'label' => esc_html__( 'Description', 'super-widget' ),
				'type'  => Controls_Manager::TEXTAREA,
				'rows'  => 3,
			)
		);

		$repeater->add_control(
			'card_price',
			array(
				'label'   => esc_html__( 'Price', 'super-widget' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '$79.00',
			)
		);

		$repeater->add_control(
			'card_old_price',
			array(
				'label'       => esc_html__( 'Old price', 'super-widget' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => '$99.00',
			)
		);

		$repeater->add_control(
			'card_button_text',
			array(
				'label'   => esc_html__( 'Button text', 'super-widget' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Add to Cart', 'super-widget' ),
			)
		);

		$repeater->add_control(
			'card_link',
			array(
				'label'       => esc_html__( 'Link', 'super-widget' ),
				'type'        => Controls_Manager::URL,
				'placeholder' => 'https://',
			)
		);

		$demo = array(
			array( 'Oral Care', 'Complete Dental Care Kit', '$79.00' ),
			array( 'Oral Care', 'Whitening Toothpaste', '$24.00' ),
			array( 'Skin Care', 'Daily Hydrating Cream', '$48.00' ),
			array( 'Hair Care', 'Nourishing Hair Oil', '$32.00' ),
		);
		$defaults = array();
		foreach ( $demo as $row ) {
			$defaults[] = array(
				'card_badge' => $row[0],
				'card_title' => $row[1],
				'card_price' => $row[2],
			);
		}

		$this->add_control(
			'cards',
			array(
				'label'       => esc_html__( 'Cards', 'super-widget' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => $defaults,
				'title_field' => '{{{ card_title }}}',
			)
		);

		$this->end_controls_section();
	}

	private function controls_layout() {
		$r = '{{WRAPPER}} .spw-pc2';

		$this->start_controls_section(
			'section_layout',
			array(
				'label' => esc_html__( 'Layout', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_responsive_control(
			'columns',
			array(
				'label'          => esc_html__( 'Columns', 'super-widget' ),
				'type'           => Controls_Manager::SELECT,
				'default'        => '4',
				'tablet_default' => '2',
				'mobile_default' => '1',
				'options'        => array(
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
					'6' => '6',
				),
				'selectors'      => array(
					$r => 'grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr));',
				),
			)
		);

		$this->add_responsive_control(
			'column_gap',
			array(
				'label'     => esc_html__( 'Column gap', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 100 ) ),
				'default'   => array( 'size' => 28, 'unit' => 'px' ),
				'selectors' => array(
					$r => 'column-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'row_gap',
			array(
				'label'     => esc_html__( 'Row gap', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 100 ) ),
				'default'   => array( 'size' => 28, 'unit' => 'px' ),
				'selectors' => array(
					$r => 'row-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			array(
				'name'    => 'thumb',
				'default' => 'large',
				'exclude' => array( 'custom' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_content() {
		$this->start_controls_section(
			'section_content',
			array(
				'label' => esc_html__( 'Card Content', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'badge_source',
			array(
				'label'   => esc_html__( 'Badge', 'super-widget' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'category',
				'options' => array(
					'category' => esc_html__( 'Product category', 'super-widget' ),
					'sale'     => esc_html__( 'Sale label (only on sale)', 'super-widget' ),
					'custom'   => esc_html__( 'Same text on every card', 'super-widget' ),
					'none'     => esc_html__( 'None', 'super-widget' ),
				),
				'description' => esc_html__( 'Custom cards use the badge text typed in each card.', 'super-widget' ),
			)
		);

		$this->add_control(
			'badge_text',
			array(
				'label'     => esc_html__( 'Badge text', 'super-widget' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'New', 'super-widget' ),
				'condition' => array( 'badge_source' => 'custom' ),
			)
		);

		$this->add_control(
			'sale_badge_text',
			array(
				'label'     => esc_html__( 'Sale text', 'super-widget' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Sale', 'super-widget' ),
				'condition' => array( 'badge_source' => 'sale' ),
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
					'p'    => 'p',
					'span' => 'span',
				),
			)
		);

		$this->add_control(
			'show_desc',
			array(
				'label'        => esc_html__( 'Show description', 'super-widget' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'link_to_product',
			array(
				'label'        => esc_html__( 'Link image & title to product', 'super-widget' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array( 'source!' => 'custom' ),
			)
		);

		$this->add_control(
			'button_heading',
			array(
				'label'     => esc_html__( 'Button', 'super-widget' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'show_button',
			array(
				'label'        => esc_html__( 'Show button', 'super-widget' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'button_text',
			array(
				'label'     => esc_html__( 'Button text', 'super-widget' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Add to Cart', 'super-widget' ),
				'condition' => array( 'show_button' => 'yes', 'source!' => 'custom' ),
			)
		);

		$this->add_control(
			'added_text',
			array(
				'label'     => esc_html__( 'Text after adding', 'super-widget' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Added ✓', 'super-widget' ),
				'condition' => array( 'show_button' => 'yes', 'source!' => 'custom' ),
			)
		);

		$this->add_control(
			'show_icon',
			array(
				'label'        => esc_html__( 'Cart icon', 'super-widget' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array( 'show_button' => 'yes' ),
			)
		);

		$this->add_control(
			'button_icon',
			array(
				'label'       => esc_html__( 'Custom icon (optional)', 'super-widget' ),
				'type'        => Controls_Manager::ICONS,
				'skin'        => 'inline',
				'label_block' => false,
				'condition'   => array( 'show_button' => 'yes', 'show_icon' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	private function controls_animation() {
		$r = '{{WRAPPER}} .spw-pc2';

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
				'default' => 'fade-up',
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
				'default'   => array( 'size' => 600 ),
				'condition' => array( 'entrance_animation!' => 'none' ),
				'selectors' => array(
					$r => '--pc2-dur: {{SIZE}}ms;',
				),
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
				'selectors' => array(
					$r => '--pc2-stagger: {{SIZE}}ms;',
				),
			)
		);

		$this->add_control(
			'hover_effect',
			array(
				'label'     => esc_html__( 'Hover effect', 'super-widget' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'lift',
				'separator' => 'before',
				'options'   => array(
					'none'      => esc_html__( 'None', 'super-widget' ),
					'lift'      => esc_html__( 'Lift card', 'super-widget' ),
					'zoom'      => esc_html__( 'Zoom image', 'super-widget' ),
					'lift-zoom' => esc_html__( 'Lift card + zoom image', 'super-widget' ),
				),
			)
		);

		$this->add_control(
			'hover_duration',
			array(
				'label'     => esc_html__( 'Hover speed (ms)', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 50, 'max' => 1000, 'step' => 50 ) ),
				'default'   => array( 'size' => 300 ),
				'condition' => array( 'hover_effect!' => 'none' ),
				'selectors' => array(
					$r => '--pc2-hover-dur: {{SIZE}}ms;',
				),
			)
		);

		$this->add_control(
			'hover_lift',
			array(
				'label'     => esc_html__( 'Lift distance (px)', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 30 ) ),
				'default'   => array( 'size' => 8 ),
				'condition' => array( 'hover_effect' => array( 'lift', 'lift-zoom' ) ),
				'selectors' => array(
					$r => '--pc2-lift: -{{SIZE}}px;',
				),
			)
		);

		$this->add_control(
			'hover_zoom',
			array(
				'label'     => esc_html__( 'Image zoom', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 1, 'max' => 1.4, 'step' => 0.01 ) ),
				'default'   => array( 'size' => 1.06 ),
				'condition' => array( 'hover_effect' => array( 'zoom', 'lift-zoom' ) ),
				'selectors' => array(
					$r => '--pc2-zoom: {{SIZE}};',
				),
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

	/**
	 * Small helpers keep the style tab short.
	 */
	private function color( $id, $label, $selector, $property = 'color', $condition = array() ) {
		$args = array(
			'label'     => $label,
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				$selector => $property . ': {{VALUE}};',
			),
		);
		if ( $condition ) {
			$args['condition'] = $condition;
		}
		$this->add_control( $id, $args );
	}

	private function dimensions( $id, $label, $selector, $property ) {
		$this->add_responsive_control(
			$id,
			array(
				'label'      => $label,
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					$selector => $property . ': {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);
	}

	private function style_card() {
		$card = '{{WRAPPER}} .spw-pc2 .spw-pc2__card';

		$this->start_controls_section(
			'style_card',
			array(
				'label' => esc_html__( 'Card', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		// One accent color drives badge, price and button; each can still be overridden below.
		$this->color( 'accent_color', esc_html__( 'Accent color', 'super-widget' ), '{{WRAPPER}} .spw-pc2', '--pc2-accent' );
		$this->color( 'accent_hover', esc_html__( 'Accent color (hover)', 'super-widget' ), '{{WRAPPER}} .spw-pc2', '--pc2-accent-dark' );

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'      => 'card_background',
				'types'     => array( 'classic', 'gradient' ),
				'selector'  => $card,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'card_border',
				'selector' => $card,
			)
		);

		$this->dimensions( 'card_radius', esc_html__( 'Border radius', 'super-widget' ), $card, 'border-radius' );
		$this->dimensions( 'card_padding', esc_html__( 'Padding', 'super-widget' ), $card, 'padding' );

		$this->add_responsive_control(
			'card_gap',
			array(
				'label'     => esc_html__( 'Space between items', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'selectors' => array(
					$card => '--pc2-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'content_align',
			array(
				'label'     => esc_html__( 'Text alignment', 'super-widget' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'left'   => array( 'title' => esc_html__( 'Left', 'super-widget' ), 'icon' => 'eicon-text-align-left' ),
					'center' => array( 'title' => esc_html__( 'Center', 'super-widget' ), 'icon' => 'eicon-text-align-center' ),
					'right'  => array( 'title' => esc_html__( 'Right', 'super-widget' ), 'icon' => 'eicon-text-align-right' ),
				),
				'selectors' => array(
					$card . ' .spw-pc2__body' => 'text-align: {{VALUE}};',
				),
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

	private function style_image() {
		$media = '{{WRAPPER}} .spw-pc2 .spw-pc2__media';

		$this->start_controls_section(
			'style_image',
			array(
				'label' => esc_html__( 'Image', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'image_ratio',
			array(
				'label'     => esc_html__( 'Shape', 'super-widget' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '1 / 1',
				'options'   => array(
					'1 / 1'   => esc_html__( 'Square 1:1', 'super-widget' ),
					'5 / 4'   => esc_html__( 'Landscape 5:4', 'super-widget' ),
					'4 / 3'   => esc_html__( 'Landscape 4:3', 'super-widget' ),
					'16 / 9'  => esc_html__( 'Wide 16:9', 'super-widget' ),
					'4 / 5'   => esc_html__( 'Portrait 4:5', 'super-widget' ),
					'3 / 4'   => esc_html__( 'Portrait 3:4', 'super-widget' ),
				),
				'selectors' => array(
					$media => 'aspect-ratio: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'image_fit',
			array(
				'label'     => esc_html__( 'Image fit', 'super-widget' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'contain',
				'options'   => array(
					'contain' => esc_html__( 'Contain (whole product)', 'super-widget' ),
					'cover'   => esc_html__( 'Cover (fill)', 'super-widget' ),
				),
				'selectors' => array(
					$media . ' img' => 'object-fit: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'image_padding',
			array(
				'label'     => esc_html__( 'Image padding', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
				'selectors' => array(
					$media . ' img' => 'padding: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'image_background',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => $media,
			)
		);

		$this->dimensions( 'image_radius', esc_html__( 'Border radius', 'super-widget' ), $media, 'border-radius' );

		$this->end_controls_section();
	}

	private function style_badge() {
		$badge = '{{WRAPPER}} .spw-pc2 .spw-pc2__badge';

		$this->start_controls_section(
			'style_badge',
			array(
				'label'     => esc_html__( 'Badge', 'super-widget' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'badge_source!' => 'none' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'badge_typography',
				'selector' => $badge,
			)
		);

		$this->add_control(
			'badge_transform',
			array(
				'label'     => esc_html__( 'Text case', 'super-widget' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'uppercase',
				'options'   => array(
					'uppercase'  => esc_html__( 'UPPERCASE', 'super-widget' ),
					'capitalize' => esc_html__( 'Capitalize', 'super-widget' ),
					'none'       => esc_html__( 'As typed', 'super-widget' ),
				),
				'selectors' => array(
					$badge => 'text-transform: {{VALUE}};',
				),
			)
		);

		$this->color( 'badge_color', esc_html__( 'Text color', 'super-widget' ), $badge );
		$this->color( 'badge_bg', esc_html__( 'Background', 'super-widget' ), $badge, 'background-color' );

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'badge_border',
				'selector' => $badge,
			)
		);

		$this->dimensions( 'badge_radius', esc_html__( 'Border radius', 'super-widget' ), $badge, 'border-radius' );
		$this->dimensions( 'badge_padding', esc_html__( 'Padding', 'super-widget' ), $badge, 'padding' );

		$this->add_responsive_control(
			'badge_x',
			array(
				'label'     => esc_html__( 'Offset left', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'selectors' => array(
					$badge => 'left: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'badge_y',
			array(
				'label'     => esc_html__( 'Offset top', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'selectors' => array(
					$badge => 'top: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	private function style_title() {
		$title = '{{WRAPPER}} .spw-pc2 .spw-pc2__title';

		$this->start_controls_section(
			'style_title',
			array(
				'label' => esc_html__( 'Title', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'selector' => $title . ', {{WRAPPER}} .spw-pc2 .spw-pc2__title a',
			)
		);

		$this->color( 'title_color', esc_html__( 'Color', 'super-widget' ), $title . ', {{WRAPPER}} .spw-pc2 .spw-pc2__title a' );
		$this->color( 'title_hover_color', esc_html__( 'Hover color', 'super-widget' ), '{{WRAPPER}} .spw-pc2 .spw-pc2__title a:hover' );

		$this->add_control(
			'title_lines',
			array(
				'label'       => esc_html__( 'Max lines', 'super-widget' ),
				'type'        => Controls_Manager::SLIDER,
				'range'       => array( 'px' => array( 'min' => 1, 'max' => 4 ) ),
				'default'     => array( 'size' => 2 ),
				'description' => esc_html__( 'Longer titles are cut. This height is reserved on every card so prices and buttons line up.', 'super-widget' ),
				'selectors'   => array(
					'{{WRAPPER}} .spw-pc2' => '--pc2-title-lines: {{SIZE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	private function style_description() {
		$desc = '{{WRAPPER}} .spw-pc2 .spw-pc2__desc';

		$this->start_controls_section(
			'style_desc',
			array(
				'label'     => esc_html__( 'Description', 'super-widget' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_desc' => 'yes' ),
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

		$this->add_control(
			'desc_lines',
			array(
				'label'     => esc_html__( 'Max lines', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 1, 'max' => 8 ) ),
				'default'   => array( 'size' => 2 ),
				'selectors' => array(
					'{{WRAPPER}} .spw-pc2' => '--pc2-desc-lines: {{SIZE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	private function style_price() {
		$price = '{{WRAPPER}} .spw-pc2 .spw-pc2__price';
		$old   = '{{WRAPPER}} .spw-pc2 .spw-pc2__old-price';

		$this->start_controls_section(
			'style_price',
			array(
				'label' => esc_html__( 'Price', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'price_typography',
				'selector' => $price,
			)
		);

		$this->color( 'price_color', esc_html__( 'Color', 'super-widget' ), $price );

		$this->add_control(
			'old_price_heading',
			array(
				'label'     => esc_html__( 'Old price', 'super-widget' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'old_price_typography',
				'selector' => $old,
			)
		);

		$this->color( 'old_price_color', esc_html__( 'Color', 'super-widget' ), $old );

		$this->end_controls_section();
	}

	private function style_button() {
		$btn = '{{WRAPPER}} .spw-pc2 .spw-pc2__btn';

		$this->start_controls_section(
			'style_button',
			array(
				'label'     => esc_html__( 'Button', 'super-widget' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_button' => 'yes' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'button_typography',
				'selector' => $btn,
			)
		);

		$this->add_responsive_control(
			'button_align',
			array(
				'label'     => esc_html__( 'Button width', 'super-widget' ),
				'type'      => Controls_Manager::CHOOSE,
				'default'   => 'stretch',
				'options'   => array(
					'flex-start' => array( 'title' => esc_html__( 'Left', 'super-widget' ), 'icon' => 'eicon-h-align-left' ),
					'center'     => array( 'title' => esc_html__( 'Center', 'super-widget' ), 'icon' => 'eicon-h-align-center' ),
					'flex-end'   => array( 'title' => esc_html__( 'Right', 'super-widget' ), 'icon' => 'eicon-h-align-right' ),
					'stretch'    => array( 'title' => esc_html__( 'Full width', 'super-widget' ), 'icon' => 'eicon-h-align-stretch' ),
				),
				'selectors' => array(
					$btn => 'align-self: {{VALUE}};',
				),
			)
		);

		$this->start_controls_tabs( 'button_tabs' );

		$this->start_controls_tab( 'button_normal', array( 'label' => esc_html__( 'Normal', 'super-widget' ) ) );
		$this->color( 'button_color', esc_html__( 'Text color', 'super-widget' ), $btn );
		$this->color( 'button_bg', esc_html__( 'Background', 'super-widget' ), $btn, 'background-color' );
		$this->color( 'button_border_color', esc_html__( 'Border color', 'super-widget' ), $btn, 'border-color' );
		$this->end_controls_tab();

		$this->start_controls_tab( 'button_hover', array( 'label' => esc_html__( 'Hover', 'super-widget' ) ) );
		$this->color( 'button_hover_color', esc_html__( 'Text color', 'super-widget' ), $btn . ':hover, ' . $btn . ':focus-visible' );
		$this->color( 'button_hover_bg', esc_html__( 'Background', 'super-widget' ), $btn . ':hover, ' . $btn . ':focus-visible', 'background-color' );
		$this->color( 'button_hover_border_color', esc_html__( 'Border color', 'super-widget' ), $btn . ':hover, ' . $btn . ':focus-visible', 'border-color' );
		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'      => 'button_border',
				'selector'  => $btn,
				'separator' => 'before',
			)
		);

		$this->dimensions( 'button_radius', esc_html__( 'Border radius', 'super-widget' ), $btn, 'border-radius' );
		$this->dimensions( 'button_padding', esc_html__( 'Padding', 'super-widget' ), $btn, 'padding' );

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'button_shadow',
				'selector' => $btn,
			)
		);

		$this->add_responsive_control(
			'button_spacing',
			array(
				'label'     => esc_html__( 'Space above button', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'selectors' => array(
					$btn => 'margin-top: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'icon_heading',
			array(
				'label'     => esc_html__( 'Icon', 'super-widget' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => array( 'show_icon' => 'yes' ),
			)
		);

		$this->add_control(
			'icon_position',
			array(
				'label'     => esc_html__( 'Position', 'super-widget' ),
				'type'      => Controls_Manager::CHOOSE,
				'default'   => 'row',
				'options'   => array(
					'row'         => array( 'title' => esc_html__( 'Before text', 'super-widget' ), 'icon' => 'eicon-h-align-left' ),
					'row-reverse' => array( 'title' => esc_html__( 'After text', 'super-widget' ), 'icon' => 'eicon-h-align-right' ),
				),
				'selectors' => array(
					$btn => 'flex-direction: {{VALUE}};',
				),
				'condition' => array( 'show_icon' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'icon_size',
			array(
				'label'     => esc_html__( 'Size', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 8, 'max' => 40 ) ),
				'selectors' => array(
					$btn . ' .spw-pc2__btn-icon' => 'font-size: {{SIZE}}{{UNIT}};',
				),
				'condition' => array( 'show_icon' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'icon_gap',
			array(
				'label'     => esc_html__( 'Gap', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 30 ) ),
				'selectors' => array(
					$btn => 'gap: {{SIZE}}{{UNIT}};',
				),
				'condition' => array( 'show_icon' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	/* ============================ Render ============================== */

	protected function render() {
		$settings = $this->get_settings_for_display();
		$source   = isset( $settings['source'] ) ? (string) $settings['source'] : 'custom';
		$cards    = $this->collect_cards( $source, $settings );

		if ( empty( $cards ) ) {
			$this->render_empty( $source );
			return;
		}

		// Whitelisted values only.
		$entrance = isset( $settings['entrance_animation'] ) ? (string) $settings['entrance_animation'] : 'none';
		if ( ! array_key_exists( $entrance, $this->entrance_options() ) ) {
			$entrance = 'none';
		}

		$hover = isset( $settings['hover_effect'] ) ? (string) $settings['hover_effect'] : 'none';
		if ( ! in_array( $hover, array( 'none', 'lift', 'zoom', 'lift-zoom' ), true ) ) {
			$hover = 'none';
		}

		// Global switch from Super Widget > Settings.
		if ( Super_Widget_Settings::get( 'disable_animations' ) ) {
			$entrance = 'none';
			$hover    = 'none';
		}

		$tag = isset( $settings['title_tag'] ) ? (string) $settings['title_tag'] : 'h3';
		if ( ! in_array( $tag, array( 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'span', 'p' ), true ) ) {
			$tag = 'h3';
		}

		$classes = array( 'spw-pc2' );
		if ( 'none' !== $entrance ) {
			$classes[] = 'spw-pc2--anim-' . $entrance;
		}
		if ( in_array( $hover, array( 'lift', 'lift-zoom' ), true ) ) {
			$classes[] = 'spw-pc2--lift';
		}
		if ( in_array( $hover, array( 'zoom', 'lift-zoom' ), true ) ) {
			$classes[] = 'spw-pc2--zoom';
		}

		$attrs = array(
			'class'     => $classes,
			'data-once' => ( isset( $settings['animate_once'] ) && 'yes' === $settings['animate_once'] ) ? 'yes' : 'no',
		);

		if ( 'custom' !== $source && class_exists( 'WC_AJAX' ) ) {
			$attrs['data-ajax']  = esc_url_raw( WC_AJAX::get_endpoint( 'add_to_cart' ) );
			$attrs['data-added'] = isset( $settings['added_text'] ) ? (string) $settings['added_text'] : '';
			if ( 'yes' === get_option( 'woocommerce_cart_redirect_after_add' ) ) {
				$attrs['data-cart'] = esc_url_raw( wc_get_cart_url() );
			}
		}

		$this->add_render_attribute( 'grid', $attrs );
		?>
		<div <?php $this->print_render_attribute_string( 'grid' ); ?>>
			<?php foreach ( $cards as $index => $card ) : ?>
				<?php $this->render_card( $card, (int) $index, $settings, $tag ); ?>
			<?php endforeach; ?>
		</div>
		<?php
	}

	private function render_empty( $source ) {
		// Visitors see nothing; editors get a hint.
		if ( ! ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) ) {
			return;
		}

		if ( 'custom' !== $source && ! SPW_Card_Grid_Products::is_active() ) {
			$msg = __( 'WooCommerce is not active.', 'super-widget' );
		} elseif ( 'custom' === $source ) {
			$msg = __( 'Add a card in the Custom Cards section.', 'super-widget' );
		} else {
			$msg = __( 'No products found. Pick products or a collection in the Products section.', 'super-widget' );
		}

		echo '<div class="elementor-alert elementor-alert-info" style="padding:16px;">' . esc_html( $msg ) . '</div>';
	}

	/**
	 * Name of the first (non default) category of a product.
	 */
	private function category_name( $product_id ) {
		$terms = get_the_terms( $product_id, 'product_cat' );
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return '';
		}

		$default = (int) get_option( 'default_product_cat' );
		foreach ( $terms as $term ) {
			if ( (int) $term->term_id !== $default ) {
				return $term->name;
			}
		}

		return '';
	}

	/**
	 * Build one normalized card array per card, whatever the source.
	 *
	 * @return array[]
	 */
	private function collect_cards( $source, $settings ) {
		$cards  = array();
		$badge  = isset( $settings['badge_source'] ) ? (string) $settings['badge_source'] : 'category';
		$linked = ! empty( $settings['link_to_product'] ) && 'yes' === $settings['link_to_product'];

		if ( 'custom' !== $source ) {
			if ( ! SPW_Card_Grid_Products::is_active() ) {
				return array();
			}

			foreach ( SPW_Card_Grid_Products::query( $settings ) as $product ) {
				$card = SPW_Card_Grid_Products::to_card( $product, $settings );

				switch ( $badge ) {
					case 'category':
						$card['badge'] = $this->category_name( $card['id'] );
						break;
					case 'custom':
						$card['badge'] = isset( $settings['badge_text'] ) ? (string) $settings['badge_text'] : '';
						break;
					case 'sale':
						// Keeps the sale text only when the product is on sale.
						if ( empty( $card['badge_sale'] ) ) {
							$card['badge'] = '';
						}
						break;
					default:
						$card['badge'] = '';
				}

				if ( empty( $settings['show_desc'] ) || 'yes' !== $settings['show_desc'] ) {
					$card['desc'] = '';
				}

				$card['linked'] = $linked;
				$card['custom'] = false;
				$cards[]        = $card;
			}
			return $cards;
		}

		$items = ! empty( $settings['cards'] ) && is_array( $settings['cards'] ) ? $settings['cards'] : array();
		foreach ( $items as $item ) {
			$cards[] = array(
				'custom'    => true,
				'item'      => $item,
				'image'     => isset( $item['card_image'] ) ? $item['card_image'] : array(),
				'title'     => isset( $item['card_title'] ) ? (string) $item['card_title'] : '',
				'badge'     => 'none' === $badge ? '' : ( isset( $item['card_badge'] ) ? (string) $item['card_badge'] : '' ),
				'desc'      => ( ! empty( $settings['show_desc'] ) && 'yes' === $settings['show_desc'] && isset( $item['card_description'] ) ) ? (string) $item['card_description'] : '',
				'price'     => isset( $item['card_price'] ) ? (string) $item['card_price'] : '',
				'old_price' => isset( $item['card_old_price'] ) ? (string) $item['card_old_price'] : '',
				'linked'    => false,
				'button'    => array(
					'mode' => 'custom',
					'text' => isset( $item['card_button_text'] ) ? (string) $item['card_button_text'] : '',
				),
			);
		}
		return $cards;
	}

	private function card_image_html( $card, $settings ) {
		$size = isset( $settings['thumb_size'] ) ? $settings['thumb_size'] : 'large';

		if ( ! empty( $card['custom'] ) ) {
			$html = Group_Control_Image_Size::get_attachment_image_html(
				array(
					'image'      => $card['image'],
					'thumb_size' => $size,
				),
				'thumb',
				'image'
			);
			if ( '' !== $html ) {
				return $html;
			}
			return sprintf(
				'<img src="%s" alt="%s" loading="lazy" decoding="async">',
				esc_url( Utils::get_placeholder_image_src() ),
				esc_attr( $card['title'] )
			);
		}

		if ( ! empty( $card['image_id'] ) ) {
			return wp_get_attachment_image(
				$card['image_id'],
				$size,
				false,
				array(
					'loading'  => 'lazy',
					'decoding' => 'async',
					'alt'      => $card['title'],
				)
			);
		}

		return sprintf(
			'<img src="%s" alt="%s" loading="lazy" decoding="async">',
			esc_url( $card['image_url'] ),
			esc_attr( $card['title'] )
		);
	}

	private function render_card( $card, $index, $settings, $tag ) {
		$image_html = $this->card_image_html( $card, $settings );
		$button     = $card['button'];
		$mode       = $button['mode'];
		$linked     = ! empty( $card['linked'] ) && ! empty( $card['url'] );
		$show_btn   = ( ! isset( $settings['show_button'] ) || 'yes' === $settings['show_button'] ) && '' !== $button['text'];
		$show_icon  = ! isset( $settings['show_icon'] ) || 'yes' === $settings['show_icon'];

		$btn_key = 'btn_' . $index;
		$btn_tag = 'a';
		$this->add_render_attribute( $btn_key, 'class', 'spw-pc2__btn' );

		if ( $show_btn ) {
			if ( 'custom' === $mode ) {
				$item = $card['item'];
				if ( ! empty( $item['card_link']['url'] ) ) {
					$this->add_link_attributes( $btn_key, $item['card_link'] );
				} else {
					$btn_tag = 'button';
					$this->add_render_attribute( $btn_key, 'type', 'button' );
				}
			} elseif ( 'disabled' === $mode ) {
				$btn_tag = 'button';
				$this->add_render_attribute( $btn_key, array( 'type' => 'button', 'disabled' => 'disabled' ) );
			} else {
				$this->add_render_attribute( $btn_key, 'href', esc_url( $button['url'] ) );
				if ( 'atc' === $mode ) {
					$this->add_render_attribute(
						$btn_key,
						array(
							'data-product-id'   => (string) $card['id'],
							'data-variation-id' => (string) $button['variation'],
							'data-label'        => $button['text'],
							'rel'               => 'nofollow',
						)
					);
				}
			}
		}
		?>
		<article class="spw-pc2__card">
			<div class="spw-pc2__media">
				<?php if ( $linked ) : ?>
					<a class="spw-pc2__media-link" href="<?php echo esc_url( $card['url'] ); ?>" tabindex="-1" aria-hidden="true">
						<?php echo $image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</a>
				<?php else : ?>
					<?php echo $image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endif; ?>

				<?php if ( '' !== $card['badge'] ) : ?>
					<span class="spw-pc2__badge"><?php echo esc_html( $card['badge'] ); ?></span>
				<?php endif; ?>
			</div>

			<div class="spw-pc2__body">
				<<?php echo tag_escape( $tag ); ?> class="spw-pc2__title">
					<?php if ( $linked ) : ?>
						<a href="<?php echo esc_url( $card['url'] ); ?>"><?php echo esc_html( $card['title'] ); ?></a>
					<?php else : ?>
						<?php echo esc_html( $card['title'] ); ?>
					<?php endif; ?>
				</<?php echo tag_escape( $tag ); ?>>

				<?php if ( '' !== $card['desc'] ) : ?>
					<div class="spw-pc2__desc"><?php echo esc_html( $card['desc'] ); ?></div>
				<?php endif; ?>

				<?php if ( '' !== $card['price'] ) : ?>
					<div class="spw-pc2__pricing">
						<?php if ( '' !== $card['old_price'] ) : ?>
							<del class="spw-pc2__old-price"><?php echo esc_html( $card['old_price'] ); ?></del>
						<?php endif; ?>
						<span class="spw-pc2__price"><?php echo esc_html( $card['price'] ); ?></span>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( $show_btn ) : ?>
				<<?php echo tag_escape( $btn_tag ); ?> <?php $this->print_render_attribute_string( $btn_key ); ?>>
					<?php if ( $show_icon ) : ?>
						<span class="spw-pc2__btn-icon" aria-hidden="true">
							<?php
							if ( ! empty( $settings['button_icon']['value'] ) ) {
								Icons_Manager::render_icon( $settings['button_icon'], array( 'aria-hidden' => 'true' ) );
							} else {
								// Built-in cart icon: no icon font is needed.
								echo '<svg viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false"><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2 3h3l2.7 12.4a1.5 1.5 0 0 0 1.5 1.2h8.6a1.5 1.5 0 0 0 1.5-1.1L21 8H6"/></svg>';
							}
							?>
						</span>
					<?php endif; ?>
					<span class="spw-pc2__btn-text"><?php echo esc_html( $button['text'] ); ?></span>
				</<?php echo tag_escape( $btn_tag ); ?>>
			<?php endif; ?>
		</article>
		<?php
	}
}
