<?php
/**
 * Card Grid: WooCommerce product source.
 *
 * Turns WooCommerce products (picked by hand or by collection) into the same
 * plain "card" arrays the widget template already renders, so the template
 * has one code path for both sources.
 *
 * Only loaded when WooCommerce is active. Nothing here runs on pages that do
 * not contain a Card Grid widget.
 *
 * @package Super_Widget
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SPW_Card_Grid_Products {

	/** Max variant chips per card. */
	const MAX_VARIANTS = 12;

	public static function is_active() {
		return class_exists( 'WooCommerce' ) && function_exists( 'wc_get_product' );
	}

	/* ------------------------------------------------------------------
	 * Editor-only option lists (never queried on the front end)
	 * ------------------------------------------------------------------ */

	/**
	 * Product id => title, for the "Products" picker. Editor only.
	 *
	 * @return array<int,string>
	 */
	public static function product_options() {
		if ( ! self::is_active() || ! self::in_editor() ) {
			return array();
		}

		$ids = get_posts(
			array(
				'post_type'              => 'product',
				'post_status'            => 'publish',
				'posts_per_page'         => 500,
				'orderby'                => 'title',
				'order'                  => 'ASC',
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
			)
		);

		$out = array();
		foreach ( $ids as $id ) {
			$out[ (int) $id ] = get_the_title( $id ) . ' (#' . $id . ')';
		}
		return $out;
	}

	/**
	 * Term id => name, for the "Collections" picker. Editor only.
	 *
	 * @param string $taxonomy product_cat or product_tag.
	 * @return array<int,string>
	 */
	public static function term_options( $taxonomy = 'product_cat' ) {
		if ( ! self::is_active() || ! self::in_editor() ) {
			return array();
		}

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'number'     => 300,
			)
		);
		if ( is_wp_error( $terms ) ) {
			return array();
		}

		$out = array();
		foreach ( $terms as $term ) {
			$out[ (int) $term->term_id ] = $term->name . ' (' . (int) $term->count . ')';
		}
		return $out;
	}

	private static function in_editor() {
		return is_admin() || ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor && \Elementor\Plugin::$instance->editor->is_edit_mode() );
	}

	/* ------------------------------------------------------------------
	 * Query
	 * ------------------------------------------------------------------ */

	/**
	 * @param array $settings Widget settings.
	 * @return WC_Product[]
	 */
	public static function query( $settings ) {
		$source = isset( $settings['source'] ) ? $settings['source'] : 'manual';
		$limit  = isset( $settings['products_limit'] ) ? max( 1, min( 48, (int) $settings['products_limit'] ) ) : 8;

		$args = array(
			'post_type'              => 'product',
			'post_status'            => 'publish',
			'posts_per_page'         => $limit,
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'fields'                 => 'ids',
			'update_post_term_cache' => false,
		);

		if ( 'manual' === $source ) {
			$ids = isset( $settings['products_manual'] ) ? (array) $settings['products_manual'] : array();
			$ids = array_filter( array_map( 'absint', $ids ) );

			// Extra ids typed by hand (for shops with more than 500 products).
			if ( ! empty( $settings['products_ids_text'] ) ) {
				$typed = array_filter( array_map( 'absint', explode( ',', (string) $settings['products_ids_text'] ) ) );
				$ids   = array_merge( $ids, $typed );
			}

			$ids = array_values( array_unique( $ids ) );
			if ( empty( $ids ) ) {
				return array();
			}

			$args['post__in']       = $ids;
			$args['orderby']        = 'post__in';
			$args['posts_per_page'] = count( $ids );
		} else {
			$taxonomy = ( 'tag' === $source ) ? 'product_tag' : 'product_cat';
			$key      = ( 'tag' === $source ) ? 'products_tags' : 'products_cats';
			$terms    = isset( $settings[ $key ] ) ? array_filter( array_map( 'absint', (array) $settings[ $key ] ) ) : array();
			if ( empty( $terms ) ) {
				return array();
			}

			$args['tax_query'] = array(
				'relation' => 'AND',
				array(
					'taxonomy'         => $taxonomy,
					'field'            => 'term_id',
					'terms'            => array_values( $terms ),
					'include_children' => true,
				),
				array(
					'taxonomy' => 'product_visibility',
					'field'    => 'name',
					'terms'    => array( 'exclude-from-catalog' ),
					'operator' => 'NOT IN',
				),
			);

			$orderby = isset( $settings['products_orderby'] ) ? $settings['products_orderby'] : 'date';
			$order   = ( isset( $settings['products_order'] ) && 'ASC' === $settings['products_order'] ) ? 'ASC' : 'DESC';

			switch ( $orderby ) {
				case 'title':
					$args['orderby'] = 'title';
					break;
				case 'menu_order':
					$args['orderby'] = 'menu_order title';
					break;
				case 'rand':
					$args['orderby'] = 'rand';
					break;
				case 'price':
					$args['meta_key'] = '_price'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					$args['orderby']  = 'meta_value_num';
					break;
				case 'sales':
					$args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					$args['orderby']  = 'meta_value_num';
					break;
				default:
					$args['orderby'] = 'date';
			}
			$args['order'] = $order;
		}

		if ( ! empty( $settings['products_hide_oos'] ) && 'yes' === $settings['products_hide_oos'] ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => '_stock_status',
					'value' => 'instock',
				),
			);
		}

		$ids      = get_posts( $args );
		$products = array();
		foreach ( $ids as $id ) {
			$product = wc_get_product( $id );
			if ( $product && ( 'manual' === $source || $product->is_visible() ) ) {
				$products[] = $product;
			}
		}
		return $products;
	}

	/* ------------------------------------------------------------------
	 * Product -> card
	 * ------------------------------------------------------------------ */

	/**
	 * @param WC_Product $product  Product.
	 * @param array      $settings Widget settings.
	 * @return array Card data, see SPW_Card_Grid_Widget::render_card().
	 */
	public static function to_card( $product, $settings ) {
		$size = isset( $settings['thumb_size'] ) ? (string) $settings['thumb_size'] : 'large';

		$card = array(
			'id'         => $product->get_id(),
			'image_id'   => (int) $product->get_image_id(),
			'image_url'  => '',
			'title'      => $product->get_name(),
			'url'        => $product->get_permalink(),
			'badge'      => '',
			'badge_sale' => false,
			'options'    => array(),
			'desc'       => '',
			'price'      => '',
			'old_price'  => '',
			'button'     => array(
				'mode'       => 'link', // atc | link | disabled.
				'url'        => $product->get_permalink(),
				'text'       => '',
				'product_id' => $product->get_id(),
				'variation'  => 0,
			),
		);

		if ( ! $card['image_id'] ) {
			$card['image_url'] = wc_placeholder_img_src( $size );
		}

		// Description: short description, else a trimmed full description.
		$desc = $product->get_short_description();
		if ( '' === trim( wp_strip_all_tags( $desc ) ) ) {
			$desc = $product->get_description();
		}
		$card['desc'] = wp_trim_words( wp_strip_all_tags( strip_shortcodes( $desc ) ), 40, '…' );

		$button_text = isset( $settings['button_text'] ) && '' !== $settings['button_text'] ? $settings['button_text'] : __( 'Add to Cart', 'super-widget' );
		$sale_text   = isset( $settings['sale_badge_text'] ) ? (string) $settings['sale_badge_text'] : __( 'Sale', 'super-widget' );

		if ( $product->is_type( 'variable' ) ) {
			self::fill_variable( $card, $product, $size, $button_text );
		} elseif ( $product->is_type( 'external' ) ) {
			$card['price']        = self::money( $product->get_price() );
			$card['button']['mode'] = 'link';
			$card['button']['url']  = $product->get_product_url();
			$card['button']['text'] = $product->single_add_to_cart_text();
			self::fill_prices( $card, $product );
		} elseif ( $product->is_type( 'grouped' ) ) {
			$card['price']          = wp_strip_all_tags( html_entity_decode( $product->get_price_html(), ENT_QUOTES, 'UTF-8' ) );
			$card['button']['mode'] = 'link';
			$card['button']['text'] = __( 'View products', 'super-widget' );
		} else {
			self::fill_prices( $card, $product );
			if ( $product->is_purchasable() && $product->is_in_stock() ) {
				$card['button']['mode'] = 'atc';
				$card['button']['url']  = $product->add_to_cart_url();
				$card['button']['text'] = $button_text;
			} else {
				$card['button']['mode'] = 'disabled';
				$card['button']['text'] = __( 'Out of stock', 'super-widget' );
			}
		}

		if ( $product->is_on_sale() && 'variable' !== $product->get_type() ) {
			$card['badge_sale'] = true;
		}
		if ( $card['badge_sale'] ) {
			$card['badge'] = $sale_text;
		}

		return $card;
	}

	private static function fill_prices( array &$card, $product ) {
		$regular = $product->get_regular_price();
		$current = $product->get_price();

		$card['price'] = self::money( $current );
		if ( $product->is_on_sale() && '' !== $regular ) {
			$card['old_price'] = self::money( $regular );
		}
	}

	private static function fill_variable( array &$card, $product, $size, $button_text ) {
		$children = $product->get_children();
		$defaults = $product->get_default_attributes();
		$options  = array();
		$selected = -1;
		$any_sale = false;

		foreach ( $children as $child_id ) {
			if ( count( $options ) >= self::MAX_VARIANTS ) {
				break;
			}

			$variation = wc_get_product( $child_id );
			if ( ! $variation || ! $variation->exists() || ! $variation->variation_is_visible() ) {
				continue;
			}

			$attrs = $variation->get_variation_attributes( false );
			$parts = array();
			$match = ! empty( $defaults );
			foreach ( $attrs as $name => $value ) {
				if ( '' === $value ) {
					// "Any" value: a chip cannot represent it, skip this variation.
					continue 2;
				}
				$parts[] = self::attribute_value_label( $name, $value );
				if ( $match && ( ! isset( $defaults[ $name ] ) || (string) $defaults[ $name ] !== (string) $value ) ) {
					$match = false;
				}
			}
			if ( empty( $parts ) ) {
				continue;
			}

			$regular = $variation->get_regular_price();
			$current = $variation->get_price();
			$on_sale = $variation->is_on_sale() && '' !== $regular;
			$in_stock = $variation->is_purchasable() && $variation->is_in_stock();
			$img_id  = (int) $variation->get_image_id();

			if ( $on_sale ) {
				$any_sale = true;
			}

			$options[] = array(
				'label'     => implode( ' / ', $parts ),
				'active'    => false,
				'variation' => (int) $child_id,
				'price'     => self::money( $current ),
				'old_price' => $on_sale ? self::money( $regular ) : '',
				'sale'      => $on_sale,
				'stock'     => $in_stock,
				'image'     => $img_id ? (string) wp_get_attachment_image_url( $img_id, $size ) : '',
			);

			$index = count( $options ) - 1;
			if ( $match && $in_stock && $selected < 0 ) {
				$selected = $index;
			}
		}

		if ( empty( $options ) ) {
			// Nothing selectable: show the price range and link to the product page.
			$card['price']          = wp_strip_all_tags( html_entity_decode( $product->get_price_html(), ENT_QUOTES, 'UTF-8' ) );
			$card['button']['mode'] = 'link';
			$card['button']['text'] = __( 'Select options', 'super-widget' );
			return;
		}

		// Default: product default, else first in-stock variant, else first.
		if ( $selected < 0 ) {
			foreach ( $options as $i => $opt ) {
				if ( $opt['stock'] ) {
					$selected = $i;
					break;
				}
			}
		}
		if ( $selected < 0 ) {
			$selected = 0;
		}

		$options[ $selected ]['active'] = true;
		$chosen                         = $options[ $selected ];

		$card['options']    = $options;
		$card['price']      = $chosen['price'];
		$card['old_price']  = $chosen['old_price'];
		$card['badge_sale'] = $chosen['sale'];
		$card['sale_any']   = $any_sale;

		if ( $chosen['image'] ) {
			$card['image_id']  = 0;
			$card['image_url'] = $chosen['image'];
		}

		if ( $chosen['stock'] ) {
			$card['button']['mode']      = 'atc';
			$card['button']['text']      = $button_text;
			$card['button']['variation'] = $chosen['variation'];
		} else {
			$card['button']['mode'] = 'disabled';
			$card['button']['text'] = __( 'Out of stock', 'super-widget' );
		}
	}

	private static function attribute_value_label( $attribute_name, $value ) {
		// Variation attribute keys look like "attribute_pa_size" or "attribute_size".
		$taxonomy = str_replace( 'attribute_', '', $attribute_name );
		if ( taxonomy_exists( $taxonomy ) ) {
			$term = get_term_by( 'slug', $value, $taxonomy );
			if ( $term && ! is_wp_error( $term ) ) {
				return $term->name;
			}
		}
		return (string) $value;
	}

	/**
	 * Plain-text price, e.g. "$137.00". Output is escaped by the caller.
	 */
	private static function money( $amount ) {
		if ( '' === $amount || null === $amount ) {
			return '';
		}
		return html_entity_decode( wp_strip_all_tags( wc_price( $amount ) ), ENT_QUOTES, 'UTF-8' );
	}
}
