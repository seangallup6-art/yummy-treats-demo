<?php
/**
 * WooCommerce behaviour the designs rely on: one badge per card, price per kilogram,
 * pre-order dates carried into cart and order, Thai address order, PDPA consent.
 *
 * @package YummyTreats
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Badges: exactly one per product, in priority order.
 * ---------------------------------------------------------------------- */

/**
 * Work out the single badge a product earns.
 *
 * Priority: sold out > pre-order > sale > only N left > new. Returns null for none.
 *
 * @param WC_Product $product Product.
 * @return array{label:string,kind:string}|null
 */
function yt_product_badge( $product ) {
	if ( ! $product instanceof WC_Product ) {
		return null;
	}

	if ( ! $product->is_in_stock() ) {
		return array(
			'label' => __( 'Sold out', 'yummy-treats' ),
			'kind'  => 'sold',
		);
	}

	$ships = $product->get_meta( '_yt_preorder_ships', true );
	if ( $ships ) {
		return array(
			/* translators: %s: a date range such as 12-19 Nov */
			'label' => sprintf( __( 'Pre-order, ships %s', 'yummy-treats' ), $ships ),
			'kind'  => 'pre',
		);
	}

	if ( $product->is_on_sale() ) {
		$regular = (float) $product->get_regular_price();
		$sale    = (float) $product->get_sale_price();
		if ( $product->is_type( 'variable' ) ) {
			$regular = (float) $product->get_variation_regular_price( 'min' );
			$sale    = (float) $product->get_variation_sale_price( 'min' );
		}
		$pct = ( $regular > 0 && $sale > 0 ) ? round( ( 1 - $sale / $regular ) * 100 ) : 0;
		return array(
			/* translators: %d: percentage off */
			'label' => $pct ? sprintf( __( 'Sale -%d%%', 'yummy-treats' ), $pct ) : __( 'Sale', 'yummy-treats' ),
			'kind'  => 'sale',
		);
	}

	$stock     = $product->get_stock_quantity();
	$threshold = (int) get_option( 'woocommerce_notify_low_stock_amount', 3 );
	if ( $product->managing_stock() && null !== $stock && $stock > 0 && $stock <= $threshold ) {
		return array(
			/* translators: %d: items left */
			'label' => sprintf( _n( 'Only %d left', 'Only %d left', $stock, 'yummy-treats' ), $stock ),
			'kind'  => 'low',
		);
	}

	$days_new = 45;
	$created  = $product->get_date_created();
	if ( $created && ( time() - $created->getTimestamp() ) < $days_new * DAY_IN_SECONDS ) {
		return array(
			'label' => __( 'New', 'yummy-treats' ),
			'kind'  => 'new',
		);
	}

	return null;
}

/**
 * Which product a block is talking about: the block's own postId attribute, the loop context, or the current post.
 *
 * @param array          $block    Parsed block.
 * @param \WP_Block|null $instance Block instance (carries context inside Product Collection loops).
 * @return int
 */
function yt_block_post_id( $block, $instance = null ) {
	if ( $instance instanceof WP_Block && ! empty( $instance->context['postId'] ) ) {
		return (int) $instance->context['postId'];
	}
	if ( ! empty( $block['attrs']['postId'] ) ) {
		return (int) $block['attrs']['postId'];
	}
	return (int) get_the_ID();
}

/**
 * Replace the Sale badge block's output with our single badge, or nothing (one badge per card, R12).
 *
 * @param string         $content  Rendered block.
 * @param array          $block    Parsed block.
 * @param \WP_Block|null $instance Block instance.
 * @return string
 */
function yt_render_badge_block( $content, $block, $instance = null ) {
	$post_id = yt_block_post_id( $block, $instance );
	$product = wc_get_product( $post_id );
	$badge   = yt_product_badge( $product );
	if ( ! $badge ) {
		return '';
	}
	return sprintf(
		'<span class="yt-badge yt-badge--%1$s">%2$s</span>',
		esc_attr( $badge['kind'] ),
		esc_html( $badge['label'] )
	);
}
add_filter( 'render_block_woocommerce/product-sale-badge', 'yt_render_badge_block', 10, 3 );

/**
 * Dim the picture of sold-out products: add a class on the product image block.
 *
 * @param string $content Rendered block.
 * @param array  $block   Parsed block.
 * @return string
 */
function yt_dim_sold_out_image( $content, $block, $instance = null ) {
	$post_id = yt_block_post_id( $block, $instance );
	$product = wc_get_product( $post_id );
	if ( $product && ! $product->is_in_stock() ) {
		$content = preg_replace( '/class="([^"]*wc-block-components-product-image[^"]*)"/', 'class="$1 is-sold-out"', $content, 1 );
	}
	return $content;
}
add_filter( 'render_block_woocommerce/product-image', 'yt_dim_sold_out_image', 10, 3 );

/* -------------------------------------------------------------------------
 * Price per kilogram (R3). Weight is kept in kg in WooCommerce settings.
 * ---------------------------------------------------------------------- */

/**
 * Append "฿x / kg" to the price when the product has a weight and is food or treats.
 *
 * @param string     $html    Price HTML.
 * @param WC_Product $product Product.
 * @return string
 */
function yt_price_per_kg( $html, $product ) {
	if ( is_admin() || ! $product instanceof WC_Product ) {
		return $html;
	}
	if ( 'yes' === $product->get_meta( '_yt_hide_unit_price', true ) ) {
		return $html;
	}

	$weight = 0.0;
	$price  = 0.0;
	if ( $product->is_type( 'variable' ) ) {
		$children = $product->get_visible_children();
		$best     = null;
		foreach ( $children as $child_id ) {
			$v = wc_get_product( $child_id );
			if ( ! $v || ! $v->get_weight() || ! $v->get_price() ) {
				continue;
			}
			$per = (float) $v->get_price() / (float) $v->get_weight();
			if ( null === $best || $per < $best ) {
				$best   = $per;
				$weight = (float) $v->get_weight();
				$price  = (float) $v->get_price();
			}
		}
	} else {
		$weight = (float) $product->get_weight();
		$price  = (float) $product->get_price();
	}

	if ( $weight <= 0 || $price <= 0 ) {
		return $html;
	}

	$unit = 'kg' === get_option( 'woocommerce_weight_unit', 'kg' ) ? $weight : $weight / 1000;
	$per  = $price / $unit;
	$text = $product->is_type( 'variable' )
		/* translators: %s: formatted price per kg */
		? sprintf( __( 'from %s / kg', 'yummy-treats' ), wp_strip_all_tags( wc_price( $per, array( 'decimals' => 0 ) ) ) )
		/* translators: %s: formatted price per kg */
		: sprintf( __( '%s / kg', 'yummy-treats' ), wp_strip_all_tags( wc_price( $per, array( 'decimals' => 0 ) ) ) );

	return $html . ' <span class="yt-perkg">' . esc_html( $text ) . '</span>';
}
add_filter( 'woocommerce_get_price_html', 'yt_price_per_kg', 20, 2 );

/* -------------------------------------------------------------------------
 * Pre-orders: the ship date travels from product to cart line to order line.
 * ---------------------------------------------------------------------- */

/**
 * Cart item data (shown by the Cart and Checkout blocks and the Store API).
 *
 * @param array $item_data Existing item data.
 * @param array $cart_item Cart item.
 * @return array
 */
function yt_cart_preorder_line( $item_data, $cart_item ) {
	$product = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
	if ( ! $product instanceof WC_Product ) {
		return $item_data;
	}
	$ships = $product->get_meta( '_yt_preorder_ships', true );
	if ( ! $ships && $product->get_parent_id() ) {
		$parent = wc_get_product( $product->get_parent_id() );
		$ships  = $parent ? $parent->get_meta( '_yt_preorder_ships', true ) : '';
	}
	if ( $ships ) {
		$item_data[] = array(
			'key'   => __( 'Pre-order', 'yummy-treats' ),
			/* translators: %s: a date range such as 12-19 Nov */
			'value' => sprintf( __( 'Ships %s', 'yummy-treats' ), $ships ),
		);
	}
	return $item_data;
}
add_filter( 'woocommerce_get_item_data', 'yt_cart_preorder_line', 10, 2 );

/**
 * Copy the ship date onto the order line so it shows in emails and My Account.
 *
 * @param WC_Order_Item_Product $item          Order item.
 * @param string                $cart_item_key Cart key.
 * @param array                 $values        Cart values.
 */
function yt_order_preorder_line( $item, $cart_item_key, $values ) {
	$product = isset( $values['data'] ) ? $values['data'] : null;
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	$ships = $product->get_meta( '_yt_preorder_ships', true );
	if ( $ships ) {
		/* translators: %s: a date range such as 12-19 Nov */
		$item->add_meta_data( __( 'Pre-order', 'yummy-treats' ), sprintf( __( 'Ships %s', 'yummy-treats' ), $ships ), true );
	}
}
add_action( 'woocommerce_checkout_create_order_line_item', 'yt_order_preorder_line', 10, 3 );

/* -------------------------------------------------------------------------
 * Thai address: postcode first, province as state, district and subdistrict named as such.
 * ---------------------------------------------------------------------- */

/**
 * Field order and labels for Thailand.
 *
 * @param array $locale Country locale rules.
 * @return array
 */
function yt_thai_address_locale( $locale ) {
	$locale['TH'] = array_merge(
		isset( $locale['TH'] ) ? $locale['TH'] : array(),
		array(
			'postcode'  => array(
				'priority' => 70,
				'label'    => __( 'Postcode', 'yummy-treats' ),
			),
			'state'     => array(
				'priority' => 60,
				'label'    => __( 'Province', 'yummy-treats' ),
				'required' => true,
			),
			'city'      => array(
				'priority' => 50,
				'label'    => __( 'District (เขต/อำเภอ)', 'yummy-treats' ),
			),
			'address_2' => array(
				'priority' => 40,
				'label'    => __( 'Subdistrict (แขวง/ตำบล)', 'yummy-treats' ),
				'required' => true,
				'hidden'   => false,
			),
			'address_1' => array(
				'priority' => 30,
				'label'    => __( 'House number, building, street', 'yummy-treats' ),
			),
		)
	);
	return $locale;
}
add_filter( 'woocommerce_get_country_locale', 'yt_thai_address_locale' );

/* -------------------------------------------------------------------------
 * PDPA consent at checkout (Additional Checkout Fields API, block checkout).
 * ---------------------------------------------------------------------- */

/**
 * A required consent checkbox in the contact step. Stored on the order.
 */
function yt_register_pdpa_field() {
	if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
		return;
	}
	woocommerce_register_additional_checkout_field(
		array(
			'id'       => 'yummy-treats/pdpa-consent',
			'label'    => __( 'I agree that Yummy Treats! uses my name, address and phone number to deliver this order and contact me about it, as described in the privacy notice (PDPA). Nothing else.', 'yummy-treats' ),
			'location' => 'contact',
			'type'     => 'checkbox',
			'required' => true,
		)
	);
	woocommerce_register_additional_checkout_field(
		array(
			'id'       => 'yummy-treats/line-updates',
			'label'    => __( 'Tell me about new treats on LINE', 'yummy-treats' ),
			'location' => 'contact',
			'type'     => 'checkbox',
			'required' => false,
		)
	);
}
add_action( 'woocommerce_init', 'yt_register_pdpa_field' );

/* -------------------------------------------------------------------------
 * Small things.
 * ---------------------------------------------------------------------- */

/**
 * "Add to cart" copy on cards: pre-orders say so, variable products say "Choose a size".
 *
 * @param string     $text    Button text.
 * @param WC_Product $product Product.
 * @return string
 */
function yt_add_to_cart_text( $text, $product ) {
	if ( ! $product instanceof WC_Product ) {
		return $text;
	}
	if ( ! $product->is_in_stock() ) {
		return __( 'Notify me', 'yummy-treats' );
	}
	if ( $product->get_meta( '_yt_preorder_ships', true ) ) {
		return __( 'Pre-order', 'yummy-treats' );
	}
	if ( $product->is_type( 'variable' ) ) {
		return __( 'Choose a size', 'yummy-treats' );
	}
	return __( 'Add to cart', 'yummy-treats' );
}
add_filter( 'woocommerce_product_add_to_cart_text', 'yt_add_to_cart_text', 10, 2 );
add_filter( 'woocommerce_product_single_add_to_cart_text', 'yt_add_to_cart_text', 10, 2 );

/**
 * Low-stock threshold used by the "Only N left" badge, if the shop has not set one.
 */
add_filter(
	'option_woocommerce_notify_low_stock_amount',
	function ( $value ) {
		return $value ? $value : 3;
	}
);

/**
 * The free-shipping minimum for the Bangkok zone (falls back to the first free-shipping method, then ฿1,000).
 * Used by the cart progress bar so the number never drifts from the real shipping settings.
 *
 * @return float
 */
function yt_free_shipping_threshold() {
	if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
		return 1000;
	}
	$fallback = null;
	foreach ( WC_Shipping_Zones::get_zones() as $zone ) {
		foreach ( $zone['shipping_methods'] as $method ) {
			if ( 'free_shipping' !== $method->id || 'yes' !== $method->enabled ) {
				continue;
			}
			$min = (float) $method->get_option( 'min_amount' );
			if ( $min <= 0 ) {
				continue;
			}
			if ( false !== stripos( $zone['zone_name'], 'bangkok' ) ) {
				return $min;
			}
			if ( null === $fallback ) {
				$fallback = $min;
			}
		}
	}
	return $fallback ?? 1000;
}

/**
 * Sold-out cards: mark the button so CSS can draw it as a quiet outline, never the mustard buy button (R8).
 *
 * @param string         $content  Rendered block.
 * @param array          $block    Parsed block.
 * @param \WP_Block|null $instance Block instance.
 * @return string
 */
function yt_notify_button_class( $content, $block, $instance = null ) {
	$product = wc_get_product( yt_block_post_id( $block, $instance ) );
	if ( $product && ! $product->is_in_stock() ) {
		$content = preg_replace( '/class="([^"]*wc-block-components-product-button[^"]*)"/', 'class="$1 yt-btn--notify"', $content, 1 );
	}
	return $content;
}
add_filter( 'render_block_woocommerce/product-button', 'yt_notify_button_class', 10, 3 );

/**
 * WooCommerce auto-inserts a Mini Cart and a Customer Account block after the navigation in block themes.
 * The header already has its own cart button, and the account icon loads a Gravatar (offline in Playground),
 * so both hooked blocks are declined.
 *
 * @param string[] $hooked_block_types Block types hooked at this anchor.
 * @return string[]
 */
function yt_unhook_woo_header_blocks( $hooked_block_types ) {
	return array_values( array_diff( (array) $hooked_block_types, array( 'woocommerce/mini-cart', 'woocommerce/customer-account' ) ) );
}
add_filter( 'hooked_block_types', 'yt_unhook_woo_header_blocks', 20 );
