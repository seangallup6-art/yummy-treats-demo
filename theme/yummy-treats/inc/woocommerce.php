<?php
/**
 * WooCommerce behaviour the designs rely on: one badge per card, price per kilogram,
 * pre-order dates carried into cart and order, Thai address order, PDPA consent.
 *
 * @package YummyTreats
 */

defined( 'ABSPATH' ) || exit;

/**
 * Pre-order ship date of a product (or of its parent, for a size), formatted for the page language ("12 Nov"), or ''.
 * Stored as Y-m-d in the product meta _yt_preorder_ships.
 *
 * @param WC_Product|null $product Product or variation.
 * @return string
 */
function yt_preorder_ships( $product ) {
	if ( ! $product instanceof WC_Product ) {
		return '';
	}
	$raw = $product->get_meta( '_yt_preorder_ships', true );
	if ( ! $raw && $product->get_parent_id() ) {
		$parent = wc_get_product( $product->get_parent_id() );
		$raw    = $parent ? $parent->get_meta( '_yt_preorder_ships', true ) : '';
	}
	$raw = trim( preg_replace( '/^Ships\s+/i', '', (string) $raw ) );
	if ( '' === $raw ) {
		return '';
	}
	$time = strtotime( $raw );
	return $time ? date_i18n( 'j M', $time ) : $raw;
}

/**
 * The shop's product card grid as block markup: image, category, name, price, button.
 * One definition so the home page, the guides and the empty cart all show the same card (styled by .yt-cards).
 *
 * @param array $args {
 *     @type string $collection Product Collection type, e.g. woocommerce/product-collection/best-sellers or …/hand-picked.
 *     @type array  $query      Overrides for the collection query (perPage, orderBy, woocommerceHandPickedProducts…).
 *     @type int    $columns    Cards per row on wide screens.
 *     @type int    $query_id   Unique per page when a page shows more than one grid.
 *     @type string $class      Extra class names after yt-cards.
 * }
 * @return string
 */
function yt_product_cards( $args = array() ) {
	$args  = wp_parse_args(
		$args,
		array(
			'collection' => 'woocommerce/product-collection/best-sellers',
			'query'      => array(),
			'columns'    => 4,
			'query_id'   => 1,
			'class'      => '',
		)
	);
	$query = array_merge(
		array(
			'perPage'                       => 8,
			'pages'                         => 1,
			'offset'                        => 0,
			'postType'                      => 'product',
			'order'                         => 'desc',
			'orderBy'                       => 'popularity',
			'search'                        => '',
			'exclude'                       => array(),
			'inherit'                       => false,
			'taxQuery'                      => new stdClass(),
			'isProductCollectionBlock'      => true,
			'featured'                      => false,
			'woocommerceOnSale'             => false,
			'woocommerceStockStatus'        => array( 'instock', 'onbackorder', 'outofstock' ),
			'woocommerceAttributes'         => array(),
			'woocommerceHandPickedProducts' => array(),
			'filterable'                    => false,
		),
		$args['query']
	);
	$class = trim( 'yt-cards ' . $args['class'] );
	$attrs = array(
		'align'                => 'wide',
		'queryId'              => (int) $args['query_id'],
		'query'                => $query,
		'tagName'              => 'div',
		'displayLayout'        => array( 'type' => 'flex', 'columns' => (int) $args['columns'], 'shrinkColumns' => true ),
		'dimensions'           => array( 'widthType' => 'fill' ),
		'collection'           => $args['collection'],
		'hideControls'         => array( 'inherit', 'order', 'filterable' ),
		'queryContextIncludes' => array( 'collection' ),
		'className'            => $class,
	);
	return '<!-- wp:woocommerce/product-collection ' . serialize_block_attributes( $attrs ) . ' -->
<div class="wp-block-woocommerce-product-collection alignwide ' . esc_attr( $class ) . '">
<!-- wp:woocommerce/product-template -->
<!-- wp:woocommerce/product-image {"showSaleBadge":false,"imageSizing":"thumbnail","isDescendentOfQueryLoop":true} -->
<!-- wp:woocommerce/product-sale-badge {"isDescendentOfQueryLoop":true,"align":"left"} /-->
<!-- /wp:woocommerce/product-image -->
<!-- wp:post-terms {"term":"product_cat"} /-->
<!-- wp:post-title {"level":3,"isLink":true,"__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->
<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true} /-->
<!-- wp:woocommerce/product-button {"isDescendentOfQueryLoop":true} /-->
<!-- /wp:woocommerce/product-template -->
</div>
<!-- /wp:woocommerce/product-collection -->';
}

/**
 * Product IDs for a list of SKUs, in the same order, skipping any that no longer exist.
 *
 * @param string|array $skus Comma-separated string or array.
 * @return int[]
 */
function yt_product_ids_from_skus( $skus ) {
	$skus = is_array( $skus ) ? $skus : explode( ',', (string) $skus );
	return array_values( array_filter( array_map( 'wc_get_product_id_by_sku', array_filter( array_map( 'trim', $skus ) ) ) ) );
}

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

	$ships = yt_preorder_ships( $product );
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
	$ships = yt_preorder_ships( $product );
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
	$ships = yt_preorder_ships( $product );
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
 * @param string     $where   'card' (shop grid) or 'single' (product page).
 * @return string
 */
function yt_add_to_cart_text( $text, $product, $where = 'card' ) {
	if ( ! $product instanceof WC_Product ) {
		return $text;
	}
	if ( ! $product->is_in_stock() ) {
		return __( 'Notify me', 'yummy-treats' );
	}
	if ( yt_preorder_ships( $product ) ) {
		return __( 'Pre-order', 'yummy-treats' );
	}
	if ( $product->is_type( 'variable' ) && 'card' === $where ) {
		return __( 'Choose a size', 'yummy-treats' );
	}
	return __( 'Add to cart', 'yummy-treats' );
}
add_filter( 'woocommerce_product_add_to_cart_text', 'yt_add_to_cart_text', 10, 2 );
/**
 * On the product page the size buttons sit right above the button, so it always says "Add to cart" (it is dashed and
 * disabled until a size is picked). Before, it kept saying "Choose a size" after the size was chosen.
 */
add_filter( 'woocommerce_product_single_add_to_cart_text', static fn( $text, $product ) => yt_add_to_cart_text( $text, $product, 'single' ), 10, 2 );

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


/**
 * Screen readers announce a card button by its aria-label, which WooCommerce builds from its own wording
 * ("Select options for …"). Our visible wording differs, so the label must start with the same words
 * (WCAG 2.5.3 Label in Name): "Choose a size: Chicken Jerky Strips".
 *
 * @param string     $description WooCommerce's description.
 * @param WC_Product $product     Product.
 * @return string
 */
function yt_add_to_cart_description( $description, $product ) {
	if ( ! $product instanceof WC_Product ) {
		return $description;
	}
	/* translators: 1: button text, e.g. "Choose a size", 2: product name */
	return sprintf( __( '%1$s: %2$s', 'yummy-treats' ), yt_add_to_cart_text( '', $product ), wp_strip_all_tags( $product->get_name() ) );
}
add_filter( 'woocommerce_product_add_to_cart_description', 'yt_add_to_cart_description', 10, 2 );

/**
 * When an order qualifies for free delivery, offer only the free rate (WooCommerce would otherwise list the
 * paid courier rate next to it, and some customers pick the first line).
 *
 * @param array $rates Shipping rates for the package.
 * @return array
 */
function yt_only_free_when_free( $rates ) {
	$free = array_filter( $rates, static fn( $rate ) => 'free_shipping' === $rate->get_method_id() );
	return $free ? $free : $rates;
}
add_filter( 'woocommerce_package_rates', 'yt_only_free_when_free', 100 );

/* -------------------------------------------------------------------------
 * Labels that live in settings (payment and delivery names, attribute names) and WooCommerce's fixed breadcrumb
 * label, run through the theme's translations so /th/ shows them in Thai. The stored values stay in English, the
 * site's source language; seed/translations-th.php holds the Thai.
 * ---------------------------------------------------------------------- */

/**
 * Translate a stored label with the theme's text domain. Unknown labels come back unchanged.
 *
 * @param string $text Label.
 * @return string
 */
function yt_translate_stored_label( $text ) {
	// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- stored values, listed in seed/translations-th.php.
	return is_string( $text ) && '' !== $text ? translate( $text, 'yummy-treats' ) : $text;
}
add_filter( 'woocommerce_shipping_rate_label', 'yt_translate_stored_label' );
add_filter( 'woocommerce_attribute_label', 'yt_translate_stored_label' );

/**
 * WooCommerce writes aria-label="Breadcrumb" as fixed English; make it translatable.
 *
 * @param array $defaults Breadcrumb arguments.
 * @return array
 */
function yt_breadcrumb_label( $defaults ) {
	$defaults['wrap_before'] = '<nav class="woocommerce-breadcrumb" aria-label="' . esc_attr__( 'Breadcrumb', 'yummy-treats' ) . '">';
	// The separator in its own element, so each crumb ("Shop", "Dogs") is a whole text piece the Thai dictionary can match.
	$defaults['delimiter'] = '<span class="yt-crumb-sep" aria-hidden="true">&nbsp;/&nbsp;</span>';
	return $defaults;
}
add_filter( 'woocommerce_breadcrumb_defaults', 'yt_breadcrumb_label' );

/**
 * Payment method names, descriptions and instructions, translated where they are read (the block checkout reads the
 * settings directly, past the gateway title filters). Front end only, so the admin form always shows and saves English.
 *
 * @param mixed $settings Gateway settings.
 * @return mixed
 */
function yt_translate_gateway_settings( $settings ) {
	if ( ! is_array( $settings ) || ( is_admin() && ! wp_doing_ajax() ) ) {
		return $settings;
	}
	foreach ( array( 'title', 'description', 'instructions' ) as $key ) {
		if ( ! empty( $settings[ $key ] ) ) {
			$settings[ $key ] = yt_translate_stored_label( $settings[ $key ] );
		}
	}
	return $settings;
}
foreach ( array( 'bacs', 'cod', 'omise', 'omise_promptpay' ) as $yt_gateway ) {
	add_filter( "option_woocommerce_{$yt_gateway}_settings", 'yt_translate_gateway_settings' );
}

/* -------------------------------------------------------------------------
 * Shop filter labels in Thai (4 Nov). WooCommerce keeps its filter labels in JSON (the data-wp-context attribute its
 * scripts read), which TranslatePress never sees, so on /th/ the chips of the filters in use read "Category: Dogs".
 * Both places below go through yt_translate_stored_label(), the same dictionary as the rest of the shop.
 * ---------------------------------------------------------------------- */

/**
 * Translate a filter label one side of the colon at a time: "Category: Dogs" becomes "หมวดหมู่: สุนัข".
 * WooCommerce's chip template ("Category: {{label}}") keeps its placeholder.
 *
 * @param string $label Label as WooCommerce built it.
 * @return string
 */
function yt_translate_filter_label( $label ) {
	if ( ! is_string( $label ) || false === strpos( $label, ': ' ) ) {
		return yt_translate_stored_label( $label );
	}
	list( $group, $value ) = explode( ': ', $label, 2 );
	return yt_translate_stored_label( $group ) . ': ' . ( '{{label}}' === $value ? $value : yt_translate_stored_label( $value ) );
}

/**
 * Category chips of the filters in use, as WooCommerce sends them with the page. Price and availability chips come
 * from WooCommerce's own gettext strings, which seed/translations-th.php already covers.
 *
 * @param array $items Selected filter items.
 * @return array
 */
function yt_translate_selected_filter_items( $items ) {
	foreach ( (array) $items as $i => $item ) {
		if ( isset( $item['activeLabel'] ) && 0 === strpos( (string) ( $item['type'] ?? '' ), 'taxonomy/' ) ) {
			$items[ $i ]['activeLabel'] = yt_translate_filter_label( $item['activeLabel'] );
		}
	}
	return $items;
}
add_filter( 'woocommerce_blocks_product_filters_selected_items', 'yt_translate_selected_filter_items', 20 );

/**
 * The category filter's own list (labels, screen-reader labels, chip template), which WooCommerce's script reads the
 * moment a box is ticked, before the page catches up.
 *
 * @param string $html Rendered block.
 * @return string
 */
function yt_translate_taxonomy_filter_context( $html ) {
	$tags = new WP_HTML_Tag_Processor( $html );
	if ( ! $tags->next_tag( array( 'class_name' => 'wp-block-woocommerce-product-filter-taxonomy' ) ) ) {
		return $html;
	}
	$context = json_decode( (string) $tags->get_attribute( 'data-wp-context' ), true );
	if ( ! is_array( $context ) ) {
		return $html;
	}
	if ( isset( $context['activeLabelTemplate'] ) ) {
		$context['activeLabelTemplate'] = yt_translate_filter_label( $context['activeLabelTemplate'] );
	}
	foreach ( (array) ( $context['items'] ?? array() ) as $i => $item ) {
		foreach ( array( 'label', 'ariaLabel' ) as $key ) {
			if ( isset( $item[ $key ] ) ) {
				$context['items'][ $i ][ $key ] = yt_translate_stored_label( $item[ $key ] );
			}
		}
	}
	$tags->set_attribute( 'data-wp-context', wp_json_encode( $context, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ) );
	return $tags->get_updated_html();
}
add_filter( 'render_block_woocommerce/product-filter-taxonomy', 'yt_translate_taxonomy_filter_context' );
