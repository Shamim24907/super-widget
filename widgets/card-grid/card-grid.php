<?php
/**
 * Card Grid widget.
 *
 * One widget = a responsive CSS grid of product cards. Cards come from
 * WooCommerce (hand-picked products or whole collections, with real variants)
 * or from a plain repeater. Equal heights and aligned rows are handled in CSS.
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

require_once __DIR__ . '/products.php';

class SPW_Card_Grid_Widget extends Widget_Base {

	public function get_name() {
		return 'spw-card-grid';
	}

	public function get_title() {
		return esc_html( Super_Widget_Settings::widget_title( 'card-grid', __( 'Card Grid', 'super-widget' ) ) );
	}

	public function get_icon() {
		return 'eicon-posts-grid';
	}

	public function get_categories() {
		return array( 'super-widget' );
	}

	public function get_keywords() {
		return array( 'card', 'grid', 'product', 'cards', 'shop' );
	}

	public function get_style_depends() {
		return array( 'spw-global', 'spw-card-grid' );
	}

	public function get_script_depends() {
		return array( 'spw-card-grid' );
	}

	/* ==================================================================
	 * Controls
	 * ================================================================== */

	protected function register_controls() {
		$this->controls_source();
		$this->controls_cards();
		$this->controls_layout();
		$this->controls_alignment();
		$this->controls_animation();

		$this->style_card();
		$this->style_image();
		$this->style_wishlist();
		$this->style_title();
		$this->style_options();
		$this->style_description();
		$this->style_badge();
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
				// "custom" keeps cards from older versions of this widget working.
				'default' => 'custom',
				'options' => $sources,
			)
		);

		if ( ! $woo ) {
			$this->add_control(
				'woo_notice',
				array(
					'type'            => Controls_Manager::RAW_HTML,
					'raw'             => esc_html__( 'Install and activate WooCommerce to pick real products, collections and variants.', 'super-widget' ),
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
				'description' => esc_html__( 'For shops with more than 500 products.', 'super-widget' ),
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
				'default'      => '',
				'condition'    => array( 'source!' => 'custom' ),
			)
		);

		$this->add_control(
			'woo_heading',
			array(
				'label'     => esc_html__( 'Card content', 'super-widget' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => array( 'source!' => 'custom' ),
			)
		);

		$this->add_control(
			'show_variants',
			array(
				'label'        => esc_html__( 'Show variants', 'super-widget' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'Variable products show one chip per variation. Choosing a chip updates price, image and the Add to Cart button.', 'super-widget' ),
				'condition'    => array( 'source!' => 'custom' ),
			)
		);

		$this->add_control(
			'show_desc',
			array(
				'label'        => esc_html__( 'Show description', 'super-widget' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array( 'source!' => 'custom' ),
			)
		);

		$this->add_control(
			'show_wishlist',
			array(
				'label'        => esc_html__( 'Wishlist heart', 'super-widget' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array( 'source!' => 'custom' ),
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
			'button_text',
			array(
				'label'     => esc_html__( 'Button text', 'super-widget' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Add to Cart', 'super-widget' ),
				'condition' => array( 'source!' => 'custom' ),
			)
		);

		$this->add_control(
			'added_text',
			array(
				'label'     => esc_html__( 'Text after adding', 'super-widget' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Added ✓', 'super-widget' ),
				'condition' => array( 'source!' => 'custom' ),
			)
		);

		$this->add_control(
			'sale_badge_text',
			array(
				'label'     => esc_html__( 'Sale badge text', 'super-widget' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Sale', 'super-widget' ),
				'condition' => array( 'source!' => 'custom' ),
			)
		);

		$this->add_control(
			'button_icon',
			array(
				'label'       => esc_html__( 'Button icon', 'super-widget' ),
				'type'        => Controls_Manager::ICONS,
				'default'     => array(
					'value'   => 'fas fa-shopping-cart',
					'library' => 'fa-solid',
				),
				'skin'        => 'inline',
				'label_block' => false,
				'condition'   => array( 'source!' => 'custom' ),
			)
		);

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
				'raw'             => esc_html__( 'All cards in a row share the same height. Title, variants, description and the price/button row sit at the same position in every card.', 'super-widget' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);

		$this->add_control(
			'title_lines',
			array(
				'label'     => esc_html__( 'Title lines (reserved)', 'super-widget' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 2,
				'min'       => 1,
				'max'       => 4,
				'selectors' => array(
					'{{WRAPPER}} .spw-card-grid' => '--spw-title-lines: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'desc_lines',
			array(
				'label'     => esc_html__( 'Description lines (reserved)', 'super-widget' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 3,
				'min'       => 1,
				'max'       => 10,
				'selectors' => array(
					'{{WRAPPER}} .spw-card-grid' => '--spw-desc-lines: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'chips_layout',
			array(
				'label'       => esc_html__( 'Variant chips', 'super-widget' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'scroll',
				'options'     => array(
					'scroll' => esc_html__( 'One row (swipe / scroll)', 'super-widget' ),
					'wrap'   => esc_html__( 'Wrap to more rows', 'super-widget' ),
				),
				'description' => esc_html__( 'One row keeps every card perfectly aligned. Wrap can push the description down on cards with many variants.', 'super-widget' ),
			)
		);

		$this->add_control(
			'reserve_old_price',
			array(
				'label'        => esc_html__( 'Reserve old-price space', 'super-widget' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'Keeps the price at the same height on sale and non-sale cards.', 'super-widget' ),
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
				'dynamic' => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'card_title',
			array(
				'label'       => esc_html__( 'Title', 'super-widget' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Card title', 'super-widget' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'card_badge',
			array(
				'label'       => esc_html__( 'Badge (optional)', 'super-widget' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => esc_html__( 'New, Sale, -20%...', 'super-widget' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'card_options',
			array(
				'label'       => esc_html__( 'Options (comma separated)', 'super-widget' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '300 ml, 500 ml, 800 ml, 1 Litre',
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'card_active_option',
			array(
				'label'       => esc_html__( 'Selected option', 'super-widget' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '800 ml',
				'description' => esc_html__( 'Must match one of the options above.', 'super-widget' ),
			)
		);

		$repeater->add_control(
			'card_description',
			array(
				'label'   => esc_html__( 'Description', 'super-widget' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 4,
				'default' => esc_html__( 'Short description of the product goes here.', 'super-widget' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'card_price',
			array(
				'label'   => esc_html__( 'Price', 'super-widget' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '$99',
				'dynamic' => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'card_old_price',
			array(
				'label'       => esc_html__( 'Old price (optional)', 'super-widget' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => '$159',
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'card_show_wishlist',
			array(
				'label'        => esc_html__( 'Wishlist heart', 'super-widget' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$repeater->add_control(
			'card_button_text',
			array(
				'label'   => esc_html__( 'Button text', 'super-widget' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Add to Cart', 'super-widget' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'card_link',
			array(
				'label'       => esc_html__( 'Button link', 'super-widget' ),
				'type'        => Controls_Manager::URL,
				'placeholder' => 'https://your-link.com',
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'card_button_icon',
			array(
				'label'       => esc_html__( 'Button icon', 'super-widget' ),
				'type'        => Controls_Manager::ICONS,
				'default'     => array(
					'value'   => 'fas fa-shopping-cart',
					'library' => 'fa-solid',
				),
				'skin'        => 'inline',
				'label_block' => false,
			)
		);

		$this->add_control(
			'cards',
			array(
				'label'       => esc_html__( 'Cards', 'super-widget' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ card_title }}}',
				'default'     => array(
					array(
						'card_title'       => 'HydroSync Pro Water Bottle',
						'card_description' => 'Stay on top of your daily water intake with this innovative bottle featuring built-in hydration reminders and Bluetooth connectivity.',
						'card_price'       => '$137',
					),
					array(
						'card_title'       => 'HydroSync Mini Bottle',
						'card_description' => 'A compact bottle for your daily commute with smart reminders and a leak-proof lid.',
						'card_price'       => '$89',
						'card_old_price'   => '$109',
						'card_badge'       => 'Sale',
						'card_active_option' => '500 ml',
					),
					array(
						'card_title'       => 'HydroSync Active Bottle',
						'card_description' => 'Built for workouts: keeps drinks cold for 24 hours and tracks every sip.',
						'card_price'       => '$119',
						'card_active_option' => '1 Litre',
					),
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
				'default'        => '3',
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
					'{{WRAPPER}} .spw-card-grid' => 'grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr));',
				),
			)
		);

		$this->add_responsive_control(
			'column_gap',
			array(
				'label'      => esc_html__( 'Column gap', 'super-widget' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', '%' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 100 ) ),
				'default'    => array( 'size' => 24, 'unit' => 'px' ),
				'selectors'  => array(
					'{{WRAPPER}} .spw-card-grid' => 'column-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'row_gap',
			array(
				'label'      => esc_html__( 'Row gap', 'super-widget' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', '%' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 100 ) ),
				'default'    => array( 'size' => 24, 'unit' => 'px' ),
				'selectors'  => array(
					'{{WRAPPER}} .spw-card-grid' => 'row-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			array(
				'name'      => 'thumb',
				'default'   => 'large',
				'exclude'   => array( 'custom' ),
				'separator' => 'before',
			)
		);

		$this->add_control(
			'title_tag',
			array(
				'label'   => esc_html__( 'Title HTML tag', 'super-widget' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'h3',
				'options' => array(
					'h1'   => 'H1',
					'h2'   => 'H2',
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

	private function controls_animation() {
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
					'{{WRAPPER}} .spw-card-grid' => '--spw-dur: {{SIZE}}ms;',
				),
			)
		);

		$this->add_control(
			'animation_stagger',
			array(
				'label'     => esc_html__( 'Stagger between cards (ms)', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 500, 'step' => 10 ) ),
				'default'   => array( 'size' => 100 ),
				'condition' => array( 'entrance_animation!' => 'none' ),
				'selectors' => array(
					'{{WRAPPER}} .spw-card-grid' => '--spw-stagger: {{SIZE}}ms;',
				),
			)
		);

		$this->add_control(
			'animation_distance',
			array(
				'label'     => esc_html__( 'Travel distance (px)', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 150 ) ),
				'default'   => array( 'size' => 30 ),
				'condition' => array( 'entrance_animation' => array( 'fade-up', 'fade-down', 'slide-left', 'slide-right' ) ),
				'selectors' => array(
					'{{WRAPPER}} .spw-card-grid' => '--spw-dist: {{SIZE}}px;',
				),
			)
		);

		$this->add_control(
			'hover_heading',
			array(
				'label'     => esc_html__( 'Hover', 'super-widget' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'hover_effect',
			array(
				'label'   => esc_html__( 'Hover effect', 'super-widget' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'lift',
				'options' => array(
					'none'      => esc_html__( 'None', 'super-widget' ),
					'lift'      => esc_html__( 'Lift card', 'super-widget' ),
					'zoom'      => esc_html__( 'Zoom image', 'super-widget' ),
					'lift-zoom' => esc_html__( 'Lift card + zoom image', 'super-widget' ),
				),
			)
		);

		$this->add_control(
			'hover_lift',
			array(
				'label'     => esc_html__( 'Lift distance (px)', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'default'   => array( 'size' => 8 ),
				'condition' => array( 'hover_effect' => array( 'lift', 'lift-zoom' ) ),
				'selectors' => array(
					'{{WRAPPER}} .spw-card-grid' => '--spw-lift: -{{SIZE}}px;',
				),
			)
		);

		$this->add_control(
			'hover_zoom',
			array(
				'label'     => esc_html__( 'Image zoom scale', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 1, 'max' => 1.5, 'step' => 0.01 ) ),
				'default'   => array( 'size' => 1.06 ),
				'condition' => array( 'hover_effect' => array( 'zoom', 'lift-zoom' ) ),
				'selectors' => array(
					'{{WRAPPER}} .spw-card-grid' => '--spw-zoom: {{SIZE}};',
				),
			)
		);

		$this->add_control(
			'hover_duration',
			array(
				'label'     => esc_html__( 'Hover transition (ms)', 'super-widget' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 1000, 'step' => 50 ) ),
				'default'   => array( 'size' => 300 ),
				'selectors' => array(
					'{{WRAPPER}} .spw-card-grid' => '--spw-hover-dur: {{SIZE}}ms;',
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

	private function style_card() {
		$card = '{{WRAPPER}} .spw-card-grid .spw-card';

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

		$this->start_controls_tabs( 'card_shadow_tabs' );

		$this->start_controls_tab(
			'card_shadow_normal',
			array( 'label' => esc_html__( 'Normal', 'super-widget' ) )
		);
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'card_shadow',
				'selector' => $card,
			)
		);
		$this->end_controls_tab();

		$this->start_controls_tab(
			'card_shadow_hover',
			array( 'label' => esc_html__( 'Hover', 'super-widget' ) )
		);
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
		$media = '{{WRAPPER}} .spw-card-grid .spw-card__media';

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
				'label'     => esc_html__( 'Aspect ratio', 'super-widget' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => array(
					''        => esc_html__( 'Default', 'super-widget' ),
					'1 / 1'   => '1 : 1',
					'4 / 3'   => '4 : 3',
					'3 / 2'   => '3 : 2',
					'16 / 9'  => '16 : 9',
					'3 / 4'   => '3 : 4',
					'4 / 5'   => '4 : 5',
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
				'default'   => 'cover',
				'options'   => array(
					'cover'   => esc_html__( 'Cover', 'super-widget' ),
					'contain' => esc_html__( 'Contain', 'super-widget' ),
					'fill'    => esc_html__( 'Fill', 'super-widget' ),
				),
				'selectors' => array(
					$media . ' img' => 'object-fit: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'image_bg',
			array(
				'label'     => esc_html__( 'Background color', 'super-widget' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					$media => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'image_radius',
			array(
				'label'      => esc_html__( 'Border radius', 'super-widget' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					$media => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	private function style_wishlist() {
		$btn = '{{WRAPPER}} .spw-card-grid .spw-card__wish';

		$this->start_controls_section(
			'style_wishlist',
			array(
				'label' => esc_html__( 'Wishlist Heart', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'wish_size',
			array(
				'label'      => esc_html__( 'Button size', 'super-widget' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 24, 'max' => 80 ) ),
				'selectors'  => array(
					$btn => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'wish_bg',
			array(
				'label'     => esc_html__( 'Background', 'super-widget' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( $btn => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'wish_color',
			array(
				'label'     => esc_html__( 'Icon color', 'super-widget' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( $btn => 'color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'wish_active_color',
			array(
				'label'     => esc_html__( 'Icon color (active)', 'super-widget' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( $btn . '.is-active' => 'color: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

	private function style_title() {
		$sel = '{{WRAPPER}} .spw-card-grid .spw-card__title';

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
				'selector' => $sel,
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => esc_html__( 'Color', 'super-widget' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( $sel => 'color: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'title_spacing',
			array(
				'label'      => esc_html__( 'Space below', 'super-widget' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'selectors'  => array( $sel => 'margin-bottom: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	private function style_options() {
		$chip   = '{{WRAPPER}} .spw-card-grid .spw-chip';
		$active = '{{WRAPPER}} .spw-card-grid .spw-chip.is-active';

		$this->start_controls_section(
			'style_options',
			array(
				'label' => esc_html__( 'Options (chips)', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'chip_typography',
				'selector' => $chip,
			)
		);

		$this->add_responsive_control(
			'chip_gap',
			array(
				'label'      => esc_html__( 'Gap', 'super-widget' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array(
					'{{WRAPPER}} .spw-card-grid .spw-card__options' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'chip_radius',
			array(
				'label'      => esc_html__( 'Border radius', 'super-widget' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 50 ) ),
				'selectors'  => array( $chip => 'border-radius: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'chip_padding',
			array(
				'label'      => esc_html__( 'Padding', 'super-widget' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					$chip => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->start_controls_tabs( 'chip_tabs' );

		$this->start_controls_tab( 'chip_normal', array( 'label' => esc_html__( 'Normal', 'super-widget' ) ) );
		$this->add_control( 'chip_color', array(
			'label'     => esc_html__( 'Text color', 'super-widget' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( $chip => 'color: {{VALUE}};' ),
		) );
		$this->add_control( 'chip_bg', array(
			'label'     => esc_html__( 'Background', 'super-widget' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( $chip => 'background-color: {{VALUE}};' ),
		) );
		$this->add_control( 'chip_border', array(
			'label'     => esc_html__( 'Border color', 'super-widget' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( $chip => 'border-color: {{VALUE}};' ),
		) );
		$this->end_controls_tab();

		$this->start_controls_tab( 'chip_active_tab', array( 'label' => esc_html__( 'Selected', 'super-widget' ) ) );
		$this->add_control( 'chip_active_color', array(
			'label'     => esc_html__( 'Text color', 'super-widget' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( $active => 'color: {{VALUE}};' ),
		) );
		$this->add_control( 'chip_active_bg', array(
			'label'     => esc_html__( 'Background', 'super-widget' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( $active => 'background-color: {{VALUE}};' ),
		) );
		$this->add_control( 'chip_active_border', array(
			'label'     => esc_html__( 'Border color', 'super-widget' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( $active => 'border-color: {{VALUE}};' ),
		) );
		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	private function style_description() {
		$sel = '{{WRAPPER}} .spw-card-grid .spw-card__desc';

		$this->start_controls_section(
			'style_description',
			array(
				'label' => esc_html__( 'Description', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'desc_typography',
				'selector' => $sel,
			)
		);

		$this->add_control(
			'desc_color',
			array(
				'label'     => esc_html__( 'Color', 'super-widget' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( $sel => 'color: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

	private function style_badge() {
		$sel = '{{WRAPPER}} .spw-card-grid .spw-card__badge';

		$this->start_controls_section(
			'style_badge',
			array(
				'label' => esc_html__( 'Badge', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'badge_typography',
				'selector' => $sel,
			)
		);

		$this->add_control(
			'badge_color',
			array(
				'label'     => esc_html__( 'Text color', 'super-widget' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( $sel => 'color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'badge_bg',
			array(
				'label'     => esc_html__( 'Background', 'super-widget' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( $sel => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'badge_radius',
			array(
				'label'      => esc_html__( 'Border radius', 'super-widget' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 50 ) ),
				'selectors'  => array( $sel => 'border-radius: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'badge_padding',
			array(
				'label'      => esc_html__( 'Padding', 'super-widget' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					$sel => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	private function style_price() {
		$sel = '{{WRAPPER}} .spw-card-grid .spw-card__price';
		$old = '{{WRAPPER}} .spw-card-grid .spw-card__old-price';

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
				'selector' => $sel,
			)
		);

		$this->add_control(
			'price_color',
			array(
				'label'     => esc_html__( 'Color', 'super-widget' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( $sel => 'color: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'      => 'old_price_typography',
				'label'     => esc_html__( 'Old price typography', 'super-widget' ),
				'selector'  => $old,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'old_price_color',
			array(
				'label'     => esc_html__( 'Old price color', 'super-widget' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( $old => 'color: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

	private function style_button() {
		$btn   = '{{WRAPPER}} .spw-card-grid .spw-card__btn';
		$hover = $btn . ':hover, ' . $btn . ':focus-visible';

		$this->start_controls_section(
			'style_button',
			array(
				'label' => esc_html__( 'Button', 'super-widget' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'btn_typography',
				'selector' => $btn,
			)
		);

		$this->add_responsive_control(
			'btn_icon_size',
			array(
				'label'      => esc_html__( 'Icon size', 'super-widget' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 8, 'max' => 50 ) ),
				'selectors'  => array(
					$btn . ' .spw-card__btn-icon' => 'font-size: {{SIZE}}{{UNIT}};',
					$btn . ' .spw-card__btn-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'btn_radius',
			array(
				'label'      => esc_html__( 'Border radius', 'super-widget' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					$btn => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'btn_padding',
			array(
				'label'      => esc_html__( 'Padding', 'super-widget' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					$btn => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->start_controls_tabs( 'btn_tabs' );

		$this->start_controls_tab( 'btn_normal', array( 'label' => esc_html__( 'Normal', 'super-widget' ) ) );
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
		$this->end_controls_tab();

		$this->start_controls_tab( 'btn_hover_tab', array( 'label' => esc_html__( 'Hover', 'super-widget' ) ) );
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
		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	/* ==================================================================
	 * Render
	 * ================================================================== */

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
		if ( ! in_array( $tag, array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'span', 'p' ), true ) ) {
			$tag = 'h3';
		}

		$classes = array( 'spw-card-grid' );
		if ( 'none' !== $entrance ) {
			$classes[] = 'spw-anim-' . $entrance;
		}
		if ( in_array( $hover, array( 'lift', 'lift-zoom' ), true ) ) {
			$classes[] = 'spw-hover-lift';
		}
		if ( in_array( $hover, array( 'zoom', 'lift-zoom' ), true ) ) {
			$classes[] = 'spw-hover-zoom';
		}
		if ( isset( $settings['chips_layout'] ) && 'wrap' === $settings['chips_layout'] ) {
			$classes[] = 'spw-chips-wrap';
		}
		if ( isset( $settings['reserve_old_price'] ) && 'yes' !== $settings['reserve_old_price'] ) {
			$classes[] = 'spw-no-old-slot';
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
	 * Build one normalized card array per card, whatever the source.
	 *
	 * @return array[]
	 */
	private function collect_cards( $source, $settings ) {
		$cards = array();

		if ( 'custom' !== $source ) {
			if ( ! SPW_Card_Grid_Products::is_active() ) {
				return array();
			}

			foreach ( SPW_Card_Grid_Products::query( $settings ) as $product ) {
				$card = SPW_Card_Grid_Products::to_card( $product, $settings );

				if ( empty( $settings['show_variants'] ) || 'yes' !== $settings['show_variants'] ) {
					$card['options'] = array();
				}
				if ( empty( $settings['show_desc'] ) || 'yes' !== $settings['show_desc'] ) {
					$card['desc'] = '';
				}
				$card['wish']     = ! empty( $settings['show_wishlist'] ) && 'yes' === $settings['show_wishlist'];
				$card['linked']   = ! empty( $settings['link_to_product'] ) && 'yes' === $settings['link_to_product'];
				$card['icon']     = isset( $settings['button_icon'] ) ? $settings['button_icon'] : array();
				$card['custom']   = false;
				$cards[]          = $card;
			}
			return $cards;
		}

		$items = ! empty( $settings['cards'] ) && is_array( $settings['cards'] ) ? $settings['cards'] : array();
		foreach ( $items as $item ) {
			$options = array_filter(
				array_map( 'trim', explode( ',', isset( $item['card_options'] ) ? (string) $item['card_options'] : '' ) ),
				'strlen'
			);
			$active  = isset( $item['card_active_option'] ) ? strtolower( trim( (string) $item['card_active_option'] ) ) : '';
			$chips   = array();
			foreach ( array_slice( array_values( $options ), 0, 12 ) as $label ) {
				$chips[] = array(
					'label'  => $label,
					'active' => ( '' !== $active && strtolower( $label ) === $active ),
				);
			}

			$cards[] = array(
				'custom'     => true,
				'item'       => $item,
				'image'      => isset( $item['card_image'] ) ? $item['card_image'] : array(),
				'title'      => isset( $item['card_title'] ) ? (string) $item['card_title'] : '',
				'badge'      => isset( $item['card_badge'] ) ? (string) $item['card_badge'] : '',
				'badge_sale' => false,
				'options'    => $chips,
				'desc'       => isset( $item['card_description'] ) ? (string) $item['card_description'] : '',
				'price'      => isset( $item['card_price'] ) ? (string) $item['card_price'] : '',
				'old_price'  => isset( $item['card_old_price'] ) ? (string) $item['card_old_price'] : '',
				'wish'       => ! empty( $item['card_show_wishlist'] ) && 'yes' === $item['card_show_wishlist'],
				'linked'     => false,
				'icon'       => isset( $item['card_button_icon'] ) ? $item['card_button_icon'] : array(),
				'button'     => array(
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
			return Group_Control_Image_Size::get_attachment_image_html(
				array(
					'image'      => $card['image'],
					'thumb_size' => $size,
				),
				'thumb',
				'image'
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
		$title_tag  = esc_attr( $tag );
		$has_chips  = ! empty( $card['options'] );
		$linked     = ! empty( $card['linked'] ) && ! empty( $card['url'] );

		// Button attributes.
		$btn_key = 'btn_' . $index;
		$this->add_render_attribute( $btn_key, 'class', 'spw-card__btn' );
		$btn_tag = 'a';

		if ( 'custom' === $mode ) {
			$item    = $card['item'];
			$has_url = ! empty( $item['card_link']['url'] );
			if ( $has_url ) {
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
						'data-oos'          => __( 'Out of stock', 'super-widget' ),
						'rel'               => 'nofollow',
					)
				);
			}
		}
		?>
		<article class="spw-card">
			<div class="spw-card__media">
				<?php if ( $linked ) : ?>
					<a class="spw-card__media-link" href="<?php echo esc_url( $card['url'] ); ?>" tabindex="-1" aria-hidden="true">
						<?php echo $image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</a>
				<?php else : ?>
					<?php echo $image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endif; ?>

				<?php if ( '' !== $card['badge'] || ! empty( $card['sale_any'] ) ) : ?>
					<?php
					$badge_text = '' !== $card['badge'] ? $card['badge'] : ( isset( $settings['sale_badge_text'] ) ? $settings['sale_badge_text'] : __( 'Sale', 'super-widget' ) );
					$is_sale    = ! empty( $card['badge_sale'] ) || ! empty( $card['sale_any'] );
					?>
					<span class="spw-card__badge"<?php echo $is_sale ? ' data-sale="1"' : ''; ?><?php echo ( $is_sale && empty( $card['badge_sale'] ) ) ? ' hidden' : ''; ?>><?php echo esc_html( $badge_text ); ?></span>
				<?php endif; ?>

				<?php if ( ! empty( $card['wish'] ) ) : ?>
					<button type="button" class="spw-card__wish" aria-pressed="false"<?php echo ! empty( $card['id'] ) ? ' data-product-id="' . esc_attr( (string) $card['id'] ) . '"' : ''; ?> aria-label="<?php echo esc_attr__( 'Add to wishlist', 'super-widget' ); ?>">
						<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 20.5s-7.5-4.6-9.4-9.3C1.2 7.7 3.4 4.5 6.7 4.5c2 0 3.6 1.1 5.3 3.1 1.7-2 3.3-3.1 5.3-3.1 3.3 0 5.5 3.2 4.1 6.7-1.9 4.7-9.4 9.3-9.4 9.3z"/></svg>
					</button>
				<?php endif; ?>
			</div>

			<div class="spw-card__body">
				<<?php echo $title_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="spw-card__title">
					<?php if ( $linked ) : ?>
						<a href="<?php echo esc_url( $card['url'] ); ?>"><?php echo esc_html( $card['title'] ); ?></a>
					<?php else : ?>
						<?php echo esc_html( $card['title'] ); ?>
					<?php endif; ?>
				</<?php echo $title_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>

				<?php /* The options row is always printed, so cards without variants keep the same height. */ ?>
				<div class="spw-card__options" role="group" aria-label="<?php echo esc_attr__( 'Options', 'super-widget' ); ?>">
					<?php if ( $has_chips ) : ?>
						<?php foreach ( $card['options'] as $option ) : ?>
							<?php
							$is_active = ! empty( $option['active'] );
							$out       = isset( $option['stock'] ) && ! $option['stock'];
							$cls       = 'spw-chip' . ( $is_active ? ' is-active' : '' ) . ( $out ? ' is-oos' : '' );
							?>
							<button type="button" class="<?php echo esc_attr( $cls ); ?>" aria-pressed="<?php echo $is_active ? 'true' : 'false'; ?>"
								<?php if ( isset( $option['variation'] ) ) : ?>
									data-variation-id="<?php echo esc_attr( (string) $option['variation'] ); ?>"
									data-price="<?php echo esc_attr( $option['price'] ); ?>"
									data-old="<?php echo esc_attr( $option['old_price'] ); ?>"
									data-sale="<?php echo $option['sale'] ? '1' : '0'; ?>"
									data-stock="<?php echo $option['stock'] ? '1' : '0'; ?>"
									<?php if ( ! empty( $option['image'] ) ) : ?>
										data-img="<?php echo esc_url( $option['image'] ); ?>"
									<?php endif; ?>
								<?php endif; ?>
							><?php echo esc_html( $option['label'] ); ?></button>
						<?php endforeach; ?>
					<?php else : ?>
						<span class="spw-chip spw-chip--ghost" aria-hidden="true">&nbsp;</span>
					<?php endif; ?>
				</div>

				<div class="spw-card__desc"><?php echo ! empty( $card['custom'] ) ? wp_kses_post( $card['desc'] ) : esc_html( $card['desc'] ); ?></div>
			</div>

			<div class="spw-card__footer">
				<span class="spw-card__pricing">
					<del class="spw-card__old-price<?php echo '' === $card['old_price'] ? ' is-empty' : ''; ?>"><?php echo '' === $card['old_price'] ? '&nbsp;' : esc_html( $card['old_price'] ); ?></del>
					<span class="spw-card__price"><?php echo esc_html( $card['price'] ); ?></span>
				</span>

				<?php if ( '' !== $button['text'] ) : ?>
					<<?php echo esc_attr( $btn_tag ); ?> <?php $this->print_render_attribute_string( $btn_key ); ?>>
						<?php if ( ! empty( $card['icon']['value'] ) ) : ?>
							<span class="spw-card__btn-icon"><?php Icons_Manager::render_icon( $card['icon'], array( 'aria-hidden' => 'true' ) ); ?></span>
						<?php endif; ?>
						<span class="spw-card__btn-text"><?php echo esc_html( $button['text'] ); ?></span>
					</<?php echo esc_attr( $btn_tag ); ?>>
				<?php endif; ?>
			</div>
		</article>
		<?php
	}
}
