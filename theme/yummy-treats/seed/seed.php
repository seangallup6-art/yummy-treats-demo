<?php
/**
 * Demo content for Yummy Treats!
 *
 * Idempotent: run it twice and you get the same shop, not two of everything. Products are keyed by SKU,
 * pages/posts/stockists by slug. Called from the Playground Blueprint with:
 *   require WP_CONTENT_DIR . '/themes/yummy-treats/seed/seed.php'; yt_seed_all();
 *
 * Everything in [brackets] is a placeholder the client fills in. Nothing here is a real brand, statistic or review.
 *
 * @package YummyTreats
 */

defined( 'ABSPATH' ) || exit;

/**
 * Run every seeder in order and return a short log.
 *
 * @return string[]
 */
function yt_seed_all() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return array( 'WooCommerce is not active; nothing seeded.' );
	}
	$log   = array();
	$log[] = yt_seed_settings();
	$log[] = yt_seed_shipping();
	$log[] = yt_seed_payments();
	$log[] = yt_seed_categories();
	$log[] = yt_seed_products();
	$log[] = yt_seed_guides();
	$log[] = yt_seed_stockists();
	$log[] = yt_seed_orders();
	$log[] = yt_seed_pages();
	$log[] = yt_seed_translatepress();
	// Do not flush here: changing the permalink structure mid-request resets WP_Rewrite, which drops every endpoint
	// plugins registered on init (WooCommerce's order-received and my-account endpoints, TranslatePress's rules).
	// Deleting the stored rules makes WordPress rebuild them on the next ordinary request, with everything registered.
	delete_option( 'rewrite_rules' );
	delete_option( 'trp_db_errors' ); // Logged during plugin activation, before the theme's SQLite shims were loaded.
	$log[] = 'rewrite rules: cleared, rebuilt on the next request';
	return $log;
}

/* ------------------------------------------------------------------------- settings */

function yt_seed_settings() {
	$options = array(
		'permalink_structure'                  => '/%postname%/',
		'timezone_string'                      => 'Asia/Bangkok',
		'date_format'                          => 'j M Y',
		'woocommerce_currency'                 => 'THB',
		'woocommerce_currency_pos'             => 'left',
		'woocommerce_price_thousand_sep'       => ',',
		'woocommerce_price_decimal_sep'        => '.',
		'woocommerce_price_num_decimals'       => '0',
		'woocommerce_weight_unit'              => 'kg',
		'woocommerce_dimension_unit'           => 'cm',
		'woocommerce_default_country'          => 'TH:TH-10',
		'woocommerce_store_address'            => '[Street address]',
		'woocommerce_store_city'               => 'Bangkok',
		'woocommerce_store_postcode'           => '10110',
		'woocommerce_allowed_countries'        => 'specific',
		'woocommerce_specific_allowed_countries' => array( 'TH' ),
		'woocommerce_ship_to_countries'        => 'specific',
		'woocommerce_specific_ship_to_countries' => array( 'TH' ),
		'woocommerce_calc_taxes'               => 'no',
		'woocommerce_enable_coupons'           => 'yes',
		'woocommerce_enable_guest_checkout'    => 'yes',
		'woocommerce_enable_checkout_login_reminder' => 'yes',
		'woocommerce_enable_signup_and_login_from_checkout' => 'yes',
		'woocommerce_notify_low_stock_amount'  => 3,
		'woocommerce_notify_no_stock_amount'   => 0,
		'woocommerce_manage_stock'             => 'yes',
		'woocommerce_hold_stock_minutes'       => '60',
		'woocommerce_enable_reviews'           => 'yes',
		'woocommerce_review_rating_verification_required' => 'yes',
		'woocommerce_shop_page_display'        => '',
		'woocommerce_catalog_columns'          => 4,
		'woocommerce_catalog_rows'             => 3,
		'woocommerce_thumbnail_cropping'       => '1:1',
		'woocommerce_coming_soon'              => 'no',
		'woocommerce_task_list_hidden'         => 'yes',
		'woocommerce_onboarding_profile'       => array( 'skipped' => true ),
		'woocommerce_allow_tracking'           => 'no',
		'woocommerce_show_marketplace_suggestions' => 'no',
		'woocommerce_feature_product_block_editor_enabled' => 'no',
		'woocommerce_demo_store'               => 'no',
		'woocommerce_demo_store_notice'        => 'Demo shop for a portfolio — browse and “order” freely, nothing is charged or shipped.',
	);
	foreach ( $options as $k => $v ) {
		update_option( $k, $v );
	}
	// Default product attribute "Size" so variations share one taxonomy across products.
	if ( ! taxonomy_exists( 'pa_size' ) && function_exists( 'wc_create_attribute' ) ) {
		wc_create_attribute(
			array(
				'name'         => 'Size',
				'slug'         => 'size',
				'type'         => 'select',
				'order_by'     => 'menu_order',
				'has_archives' => false,
			)
		);
		// Register it for this request so terms can be added right away.
		register_taxonomy( 'pa_size', 'product', array( 'hierarchical' => false, 'show_ui' => false, 'query_var' => true, 'rewrite' => false ) );
		delete_transient( 'wc_attribute_taxonomies' );
	}
	return 'settings: ok';
}

/* ------------------------------------------------------------------------- shipping */

function yt_seed_shipping() {
	if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
		return 'shipping: skipped';
	}
	foreach ( WC_Shipping_Zones::get_zones() as $z ) {
		if ( in_array( $z['zone_name'], array( 'Bangkok', 'Rest of Thailand' ), true ) ) {
			return 'shipping: already set';
		}
	}
	$make = function ( $name, $locations, $flat, $free_over ) {
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( $name );
		$zone->set_locations( $locations );
		$zone->save();
		$id = $zone->add_shipping_method( 'flat_rate' );
		update_option(
			'woocommerce_flat_rate_' . $id . '_settings',
			array( 'title' => 'Courier delivery', 'tax_status' => 'none', 'cost' => (string) $flat )
		);
		$id2 = $zone->add_shipping_method( 'free_shipping' );
		update_option(
			'woocommerce_free_shipping_' . $id2 . '_settings',
			array( 'title' => 'Free delivery', 'requires' => 'min_amount', 'min_amount' => (string) $free_over, 'ignore_discounts' => 'no' )
		);
	};
	$make( 'Bangkok', array( array( 'code' => 'TH:TH-10', 'type' => 'state' ) ), 50, 1000 );
	$make( 'Rest of Thailand', array( array( 'code' => 'TH', 'type' => 'country' ) ), 80, 1500 );
	// Nonthaburi / Samut Prakan ride with Bangkok.
	foreach ( WC_Shipping_Zones::get_zones() as $z ) {
		if ( 'Bangkok' === $z['zone_name'] ) {
			$zone = new WC_Shipping_Zone( $z['id'] );
			$zone->set_locations(
				array(
					array( 'code' => 'TH:TH-10', 'type' => 'state' ),
					array( 'code' => 'TH:TH-12', 'type' => 'state' ),
					array( 'code' => 'TH:TH-11', 'type' => 'state' ),
				)
			);
			$zone->save();
		}
	}
	return 'shipping: Bangkok ฿50 (free ≥ ฿1,000), rest of Thailand ฿80 (free ≥ ฿1,500)';
}

/* ------------------------------------------------------------------------- payments */

function yt_seed_payments() {
	update_option(
		'woocommerce_bacs_settings',
		array(
			'enabled'      => 'yes',
			'title'        => 'Bank transfer (upload your slip)',
			'description'  => 'Pay by transfer after you order. The next page shows our account, then you send us the slip.',
			'instructions' => 'Transfer the exact total to the account below, then send us the slip. Unpaid orders are cancelled after 24 hours.',
		)
	);
	update_option(
		'woocommerce_bacs_accounts',
		array(
			array(
				'account_name'   => '[Company name] Co., Ltd.',
				'account_number' => '[XXX-X-XXXXX-X]',
				'bank_name'      => '[Bank name]',
				'sort_code'      => '',
				'iban'           => '',
				'bic'            => '',
			),
		)
	);
	update_option(
		'woocommerce_cod_settings',
		array(
			'enabled'            => 'yes',
			'title'              => 'Cash on delivery',
			'description'        => 'Pay the courier in cash when your order arrives. Bangkok, Nonthaburi and Samut Prakan only.',
			'instructions'       => 'Please have the exact amount ready for the courier.',
			'enable_for_methods' => array(),
			'enable_for_virtual' => 'no',
		)
	);
	// Opn (Omise), test mode only. Keys come from the blueprint: Sean pastes the two TEST keys of a throwaway Opn
	// account into YT_OPN_TEST_PUBLIC_KEY / YT_OPN_TEST_SECRET_KEY in blueprint/playground.json (option B in the
	// handoff doc). Anything that is not a test key (pkey_test_… / skey_test_…) is ignored, so a live key pasted by
	// mistake never switches payments on. Keys entered by hand in WooCommerce → Settings → Payments → Opn still work.
	// The Opn plugin keeps its account keys and the card method's settings in one option, woocommerce_omise_settings.
	$card  = get_option( 'woocommerce_omise_settings', array() );
	$card  = is_array( $card ) ? $card : array();
	$pkey  = yt_seed_opn_test_key( 'YT_OPN_TEST_PUBLIC_KEY', 'pkey_test_' ) ?? ( $card['test_public_key'] ?? '' );
	$skey  = yt_seed_opn_test_key( 'YT_OPN_TEST_SECRET_KEY', 'skey_test_' ) ?? ( $card['test_private_key'] ?? '' );
	$opn_on = ( '' !== $pkey && '' !== $skey ) ? 'yes' : 'no'; // Card and PromptPay stay off without keys, so Opn's scripts never run keyless.
	update_option(
		'woocommerce_omise_settings',
		array_merge(
			$card,
			array(
				'sandbox'          => 'yes',
				'test_public_key'  => $pkey,
				'test_private_key' => $skey,
				'live_public_key'  => '',
				'live_private_key' => '',
				'enabled'          => $opn_on,
				'title'            => 'Credit / debit card',
				'description'      => 'Visa, Mastercard and JCB. Processed securely by Opn Payments. Demo in test mode: pay with 4242 4242 4242 4242, any future date and any 3 digits. No real money moves.',
				'payment_action'   => 'auto_capture',
			)
		)
	);
	delete_option( 'omise' ); // Written by earlier versions of this seed; the plugin never read it.
	update_option(
		'woocommerce_omise_promptpay_settings',
		array(
			'enabled'     => $opn_on,
			'title'       => 'PromptPay',
			'description' => 'Scan the QR code with any Thai banking app. Demo in test mode: the QR code is a test code that banking apps cannot pay, so no real money moves.',
		)
	);
	update_option(
		'woocommerce_gateway_order',
		array( 'omise_promptpay' => 0, 'omise' => 1, 'bacs' => 2, 'cod' => 3 )
	);
	if ( 'yes' !== $opn_on ) {
		return 'payments: bank transfer + slip, COD; Opn card/PromptPay off until test keys are entered';
	}
	return 'payments: bank transfer + slip, COD, PromptPay and card (Opn test mode); ' . yt_seed_opn_playground_headers();
}

/**
 * Playground only: let Opn's API calls through Playground's CORS proxy.
 *
 * In the browser, PHP's outgoing requests become fetch() calls. When the target does not answer the browser's
 * CORS check, Playground retries through its CORS proxy, and that proxy drops the Authorization header (which
 * carries the Opn key) unless the request opts in with "X-Cors-Proxy-Allowed-Request-Headers: authorization".
 * The Opn library has no hook for extra headers, so the seed adds that one header to its HTTP executor, once, at
 * boot. Without it the checkout quietly hides Card and PromptPay: the plugin shows them only after it has read
 * the account's capabilities from Opn's API. If Opn ever changes that file, nothing is patched and the log says so.
 *
 * @return string Log line.
 */
function yt_seed_opn_playground_headers() {
	$files = glob( WP_PLUGIN_DIR . '/*/includes/libraries/omise-php/lib/omise/http/OmiseHttpExecutor.php' );
	if ( ! $files ) {
		return 'Opn network: library not found, not patched';
	}
	$file = $files[0];
	$code = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	if ( false !== strpos( $code, 'X-Cors-Proxy-Allowed-Request-Headers' ) ) {
		return 'Opn network: already patched';
	}
	$find = 'CURLOPT_USERPWD => $userpwd';
	if ( 1 !== substr_count( $code, $find ) ) {
		return 'Opn network: library changed, not patched';
	}
	$add = $find . ",\n"
		. "            // Yummy Treats demo (added by the theme's seed.php): lets the key through Playground's CORS proxy.\n"
		. "            CURLOPT_HTTPHEADER => array_merge(array('X-Cors-Proxy-Allowed-Request-Headers: authorization'), defined('OMISE_API_VERSION') ? array('Omise-Version: ' . OMISE_API_VERSION) : array())";
	file_put_contents( $file, str_replace( $find, $add, $code ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	return 'Opn network: CORS proxy opt-in added';
}

/**
 * An Opn test key from a wp-config constant (set by the blueprint), or null when the constant is missing or empty.
 * Only test keys pass: a live key, or anything else, is ignored and logged so the demo never runs on live keys.
 *
 * @param string $constant Constant name.
 * @param string $prefix   Required prefix, pkey_test_ or skey_test_.
 * @return string|null
 */
function yt_seed_opn_test_key( $constant, $prefix ) {
	if ( ! defined( $constant ) ) {
		return null;
	}
	$key = trim( (string) constant( $constant ) );
	if ( '' === $key ) {
		return null;
	}
	if ( 0 !== strpos( $key, $prefix ) || ! preg_match( '/^[a-z0-9_]+$/i', $key ) ) {
		error_log( "Yummy Treats seed: {$constant} is not an Opn test key ({$prefix}…), so it was ignored." );
		return null;
	}
	return $key;
}

/* ------------------------------------------------------------------------- categories */

function yt_seed_categories() {
	$cats = array(
		'dogs'    => array( 'Dogs', 'Treats, chews and fresh meals for Bangkok dogs of every size.' ),
		'cats'    => array( 'Cats', 'Freeze-dried bites, lickable treats and wet food for picky Bangkok cats.' ),
		'treats'  => array( 'Treats', 'Single-ingredient treats, training bites and chews.' ),
		'meals'   => array( 'Meals', 'Fresh frozen meals, wet food and toppers.' ),
		'bundles' => array( 'Bundles', 'Starter sets and gift boxes, a little cheaper than buying separately.' ),
	);
	foreach ( $cats as $slug => $c ) {
		if ( ! term_exists( $slug, 'product_cat' ) ) {
			wp_insert_term( $c[0], 'product_cat', array( 'slug' => $slug, 'description' => $c[1] ) );
		}
	}
	$uncat = get_term_by( 'slug', 'uncategorized', 'product_cat' );
	if ( $uncat ) {
		wp_update_term( $uncat->term_id, 'product_cat', array( 'name' => 'Other', 'slug' => 'other' ) );
	}
	return 'categories: ' . implode( ', ', array_keys( $cats ) );
}

/* ------------------------------------------------------------------------- products */

function yt_seed_products() {
	$data = json_decode( file_get_contents( __DIR__ . '/products.json' ), true );
	if ( ! $data ) {
		return 'products: products.json missing';
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';

	$created = 0;
	$ships   = gmdate( 'Y-m-d', strtotime( '+10 days' ) );
	foreach ( $data as $p ) {
		if ( wc_get_product_id_by_sku( $p['sku'] ) ) {
			continue;
		}
		$is_var  = ! empty( $p['sizes'] );
		$product = $is_var ? new WC_Product_Variable() : new WC_Product_Simple();
		$product->set_name( $p['name'] );
		$product->set_sku( $p['sku'] );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'visible' );
		$product->set_short_description( $p['short'] );
		$product->set_description( yt_seed_product_body( $p ) );
		$product->set_category_ids( yt_seed_term_ids( array( $p['pet'], $p['type'] ), 'product_cat' ) );
		$product->set_reviews_allowed( true );

		// Attributes: Size (for variations) + three plain attributes shown in the specs table.
		$attributes = array();
		if ( $is_var ) {
			$terms = array_map( fn( $s ) => $s[0], $p['sizes'] );
			foreach ( $terms as $t ) {
				if ( ! term_exists( $t, 'pa_size' ) ) {
					wp_insert_term( $t, 'pa_size' );
				}
			}
			$attr = new WC_Product_Attribute();
			$attr->set_id( wc_attribute_taxonomy_id_by_name( 'size' ) );
			$attr->set_name( 'pa_size' );
			$attr->set_options( array_map( fn( $t ) => get_term_by( 'name', $t, 'pa_size' )->term_id, $terms ) );
			$attr->set_position( 0 );
			$attr->set_visible( false );
			$attr->set_variation( true );
			$attributes[] = $attr;
		}
		$pos = 1;
		foreach ( $p['attrs'] as $k => $v ) {
			$attr = new WC_Product_Attribute();
			$attr->set_name( $k );
			$attr->set_options( array( $v ) );
			$attr->set_position( $pos++ );
			$attr->set_visible( true );
			$attr->set_variation( false );
			$attributes[] = $attr;
		}
		$product->set_attributes( $attributes );

		if ( ! $is_var ) {
			$product->set_regular_price( (string) $p['simple'][1] );
			$product->set_weight( (string) $p['simple'][0] );
			if ( ! empty( $p['sale'] ) ) {
				$product->set_sale_price( (string) round( $p['simple'][1] * $p['sale'], -1 ) );
			}
			yt_seed_apply_stock( $product, $p );
		}

		$id = $product->save();

		if ( $is_var ) {
			foreach ( $p['sizes'] as $i => $s ) {
				$v = new WC_Product_Variation();
				$v->set_parent_id( $id );
				$v->set_attributes( array( 'pa_size' => get_term_by( 'name', $s[0], 'pa_size' )->slug ) );
				$v->set_regular_price( (string) $s[2] );
				if ( ! empty( $p['sale'] ) ) {
					$v->set_sale_price( (string) round( $s[2] * $p['sale'], -1 ) );
				}
				$v->set_weight( (string) $s[1] );
				$v->set_sku( $p['sku'] . '-' . ( $i + 1 ) );
				$v->set_status( 'publish' );
				yt_seed_apply_stock( $v, $p, $i );
				$v->save();
			}
			$product = wc_get_product( $id );
			WC_Product_Variable::sync( $id );
		}

		// Flags the theme reads.
		if ( ! empty( $p['preorder'] ) ) {
			update_post_meta( $id, '_yt_preorder_ships', $ships );
		}
		if ( ! empty( $p['new'] ) ) {
			// Published "recently" so the New badge logic (45 days) picks it up; older ones get an older date.
			wp_update_post( array( 'ID' => $id, 'post_date' => gmdate( 'Y-m-d H:i:s', strtotime( '-5 days' ) ), 'post_date_gmt' => gmdate( 'Y-m-d H:i:s', strtotime( '-5 days' ) ) ) );
		} else {
			$old = gmdate( 'Y-m-d H:i:s', strtotime( '-200 days' ) );
			wp_update_post( array( 'ID' => $id, 'post_date' => $old, 'post_date_gmt' => $old ) );
		}
		update_post_meta( $id, 'total_sales', (int) $p['sales'] );
		if ( 'Bundles' === $p['type'] ) {
			update_post_meta( $id, '_yt_hide_unit_price', 'yes' );
		}

		// Images: front + back.
		$ids = array();
		foreach ( array( 1, 2 ) as $n ) {
			$file = __DIR__ . '/img/' . $p['sku'] . '-' . $n . '.png';
			if ( file_exists( $file ) ) {
				$tmp = wp_tempnam( basename( $file ) );
				copy( $file, $tmp );
				// The caption is the picture's short name on the product page (sticker and zoom view).
				$att = media_handle_sideload( array( 'name' => $p['sku'] . '-' . $n . '.png', 'tmp_name' => $tmp ), $id, $p['name'] . ( 2 === $n ? ' (back of bag)' : '' ), array( 'post_excerpt' => 2 === $n ? 'Back of the bag' : 'Front' ) );
				if ( ! is_wp_error( $att ) ) {
					update_post_meta( $att, '_wp_attachment_image_alt', 2 === $n ? '[Back of bag: ingredients panel for ' . $p['name'] . ']' : '[Pack shot: ' . $p['name'] . ']' );
					$ids[] = $att;
				}
			}
		}
		if ( $ids ) {
			$product = wc_get_product( $id );
			$product->set_image_id( $ids[0] );
			$product->set_gallery_image_ids( array_slice( $ids, 1 ) );
			$product->save();
		}
		++$created;
	}
	// Clear WooCommerce's transients so counts and lookup tables are fresh.
	wc_delete_product_transients();
	if ( function_exists( 'wc_update_product_lookup_tables' ) ) {
		wc_update_product_lookup_tables();
	}
	return "products: {$created} created";
}

/**
 * Stock state per product flag. Variable products apply it to each variation.
 */
function yt_seed_apply_stock( $obj, $p, $i = 0 ) {
	if ( ! empty( $p['soldout'] ) ) {
		$obj->set_manage_stock( true );
		$obj->set_stock_quantity( 0 );
		$obj->set_stock_status( 'outofstock' );
		return;
	}
	if ( ! empty( $p['preorder'] ) ) {
		$obj->set_manage_stock( false );
		$obj->set_backorders( 'yes' );
		$obj->set_stock_status( 'onbackorder' );
		return;
	}
	$obj->set_manage_stock( true );
	$obj->set_backorders( 'no' );
	// Low stock: the smallest size has 2 left, the rest are fine.
	$obj->set_stock_quantity( ! empty( $p['low'] ) && 0 === $i ? 2 : 40 );
	$obj->set_stock_status( 'instock' );
}

function yt_seed_term_ids( $names, $tax ) {
	$ids = array();
	foreach ( $names as $n ) {
		$t = get_term_by( 'name', $n, $tax );
		if ( $t ) {
			$ids[] = $t->term_id;
		}
	}
	return $ids;
}

/**
 * Long description for a product, laid out for the "What's in the bag" card (board P5, option B): the short
 * description as a black callout, then ingredients and storage as tinted cards side by side, then the feeding
 * guide with a table to fill in. Each part is a plain group with a class, so the client edits text, not layout.
 * The feeding and storage notes follow the product: meals count against the daily food, treats stay a small
 * extra; frozen and wet food get fridge and freezer advice. The short description is its own paragraph so the
 * card and the product page share one translation.
 *
 * @param array $p Product from products.json.
 * @return string
 */
function yt_seed_product_body( $p ) {
	$pet     = 'Dogs' === $p['pet'] ? 'dog' : 'cat';
	$texture = $p['attrs']['Texture'] ?? '';
	if ( 'Meals' === $p['type'] ) {
		$feeding = "Count it as part of your {$pet}’s daily food, not on top of it, and reduce their usual food to match.";
	} else {
		$feeding = "Treats should stay a small part of your {$pet}’s day; the rest comes from their main food.";
	}
	if ( 'Fresh (frozen)' === $texture ) {
		$storage = 'Keep frozen. Thaw a portion in the fridge overnight and use it within [N] days of thawing. Do not refreeze.';
	} elseif ( in_array( $texture, array( 'Wet', 'Mousse', 'Purée', 'Liquid' ), true ) ) {
		$storage = 'Store unopened packs somewhere cool and dry. Once opened, cover, keep in the fridge and use within [N] days.';
	} else {
		$storage = 'Reseal after opening and keep cool and dry. In Bangkok humidity, the fridge is your friend. Use within [N] weeks of opening.';
	}
	$para  = static fn( $text, $class = '' ) => $class
		? '<!-- wp:paragraph {"className":"' . $class . '"} --><p class="' . $class . '">' . esc_html( $text ) . '</p><!-- /wp:paragraph -->'
		: '<!-- wp:paragraph --><p>' . esc_html( $text ) . '</p><!-- /wp:paragraph -->';
	$h3    = static fn( $text ) => '<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">' . esc_html( $text ) . '</h3><!-- /wp:heading -->';
	$group = static fn( $class, $inner ) => '<!-- wp:group {"className":"' . $class . '","layout":{"type":"default"}} --><div class="wp-block-group ' . $class . '">' . implode( '', $inner ) . '</div><!-- /wp:group -->';
	$rows  = '';
	foreach ( array( '[Up to N kg]', '[N to N kg]', '[Over N kg]' ) as $band ) {
		$rows .= '<tr><td>' . esc_html( $band ) . '</td><td>[N] g</td></tr>';
	}
	$table = '<!-- wp:table {"className":"yt-bag__table"} --><figure class="wp-block-table yt-bag__table"><table><thead><tr><th>Body weight</th><th>Per day</th></tr></thead><tbody>' . $rows . '</tbody></table></figure><!-- /wp:table -->';

	return implode(
		"\n\n",
		array(
			$group( 'yt-bag__lead', array( $para( $p['short'] ), $para( 'Made in small batches in [province] and packed within days of cooking.' ) ) ),
			$group(
				'yt-bag__cards',
				array(
					$group( 'yt-bag__card is-ingredients', array( $h3( 'Ingredients' ), $para( '[Ingredient list, in descending order by weight — supplied by the client.]' ) ) ),
					$group( 'yt-bag__card is-storage', array( $h3( 'Storage' ), $para( $storage ) ) ),
					$group( 'yt-bag__card is-feeding', array( $h3( 'Feeding guide' ), $para( $feeding, 'yt-bag__rule' ), $table, $para( '[Amounts by body weight, supplied by the client or their vet.] Always keep fresh water nearby.', 'yt-bag__note' ) ) ),
				)
			),
		)
	);
}

/* ------------------------------------------------------------------------- guides */

function yt_seed_guides() {
	$cats = array( 'Dogs', 'Cats', 'Training' );
	foreach ( $cats as $c ) {
		if ( ! term_exists( $c, 'category' ) ) {
			wp_insert_term( $c, 'category' );
		}
	}
	$hello = get_page_by_path( 'hello-world', OBJECT, 'post' );
	if ( $hello ) {
		wp_delete_post( $hello->ID, true );
	}
	$guides = yt_seed_guide_content();
	$n      = 0;
	foreach ( $guides as $g ) {
		if ( get_page_by_path( $g['slug'], OBJECT, 'post' ) ) {
			continue;
		}
		$term = get_term_by( 'name', $g['cat'], 'category' );
		wp_insert_post(
			array(
				'post_type'     => 'post',
				'post_status'   => 'publish',
				'post_name'     => $g['slug'],
				'post_title'    => $g['title'],
				'post_excerpt'  => $g['excerpt'],
				'post_content'  => yt_seed_blocks( array_merge( array( array( 'lead', $g['excerpt'] ) ), $g['body'] ) ),
				'post_category' => $term ? array( $term->term_id ) : array(),
				'post_date'     => gmdate( 'Y-m-d H:i:s', strtotime( '-' . ( 7 + 10 * $n ) . ' days' ) ),
				// Products shown under the guide (patterns/guide-products.php). Editable in the Custom Fields panel.
				'meta_input'    => array( 'yt_guide_products' => implode( ', ', $g['products'] ) ),
			)
		);
		++$n;
	}
	return "guides: {$n} created";
}

/**
 * The three guides. Facts are checked against the sources listed at the end of each one (see also
 * docs/woo-demo-decisions.md). The vet reviewer stays a [placeholder] in the byline until the client names one.
 * Text avoids straight quotes, apostrophes and ampersands: WordPress curls or encodes those on output, and the
 * Thai dictionary matches the rendered text exactly.
 *
 * @return array[]
 */
function yt_seed_guide_content() {
	$disclaimer = array( 'small', 'General information, not a substitute for advice from your own vet.' );
	$wsava_dog  = array( 'WSAVA Global Nutrition Committee: Feeding treats to your dog (PDF)', 'https://wsava.org/wp-content/uploads/2024/06/Feeding-treats-to-your-dog-v2.pdf' );
	$tufts      = array( 'Tufts Cummings School of Veterinary Medicine, via Newswise: treats and table food', 'https://www.newswise.com/articles/the-most-popular-holiday-foods-that-your-pet-should-avoid' );
	return array(
		array(
			'slug'     => 'how-much-should-a-condo-dog-eat',
			'title'    => 'How much should a Bangkok condo dog actually eat?',
			'cat'      => 'Dogs',
			'excerpt'  => 'The number on the bag is a starting point, not a rule. How to work it out with your vet, and the signs you have it right.',
			'products' => array( 'YT-D-007', 'YT-D-009', 'YT-D-005' ),
			'body'     => array(
				array( 'p', 'Feeding charts on the bag are written for an average dog. A young dog who runs laps of the park every evening and an older dog whose big outing is the lift down to the lobby can weigh the same and still need very different amounts of food.' ),
				array( 'h2', 'Start with your vet, not the bag' ),
				array( 'p', 'Ask your vet for a daily calorie target based on your dog’s weight, age and how active they really are. Ask for the number in kilocalories (kcal) rather than cups or scoops, because every food packs a different amount of energy into the same scoop.' ),
				array( 'p', 'For a sense of the range: vet nutritionists at Tufts University use the example of a fairly active 11 kg dog needing about 600 kcal a day, while a less active dog of the same weight may need closer to 400.' ),
				array( 'h2', 'The simple method' ),
				array(
					'ol',
					array(
						'Get a daily calorie target from your vet.',
						'Find the kcal per 100 g on the food label and work out how many grams a day that is.',
						'Weigh meals on a kitchen scale for the first few weeks instead of guessing with a scoop.',
						'Count treats, chews and table scraps in the same budget: no more than 10% of the day’s calories, with the rest from complete meals.',
						'Check your dog’s shape every few weeks and change portions a little at a time.',
					),
				),
				array( 'h2', 'Signs you have it right' ),
				array( 'p', 'Vets judge this with a body condition score, by looking and feeling rather than by weight alone. A rough version at home:' ),
				array(
					'ul',
					array(
						'You can feel the ribs easily with light pressure, under a thin layer of padding.',
						'From above, there is a visible waist behind the ribs.',
						'From the side, the belly tucks up behind the rib cage.',
					),
				),
				array( 'p', 'If you have to press hard to find the ribs, or you can see them clearly, talk to your vet before changing anything. The same goes for puppies, older dogs and dogs with a health condition, whose needs are different.' ),
				$disclaimer,
				array( 'h2', 'Sources' ),
				array(
					'links',
					array(
						array( 'WSAVA Global Nutrition Committee: Body Condition Score chart for dogs (PDF)', 'https://wsava.org/wp-content/uploads/2020/01/Body-Condition-Score-Dog.pdf' ),
						$wsava_dog,
						$tufts,
					),
				),
			),
		),
		array(
			'slug'     => 'switching-your-cat-to-a-new-food',
			'title'    => 'Switching your cat to a new food without the drama',
			'cat'      => 'Cats',
			'excerpt'  => 'Cats hate surprises. A 7 to 10 day plan for mixing old and new, and what to do if they will not eat it.',
			'products' => array( 'YT-C-011', 'YT-C-006', 'YT-C-003' ),
			'body'     => array(
				array( 'p', 'Cats are creatures of habit. Swap their food overnight and you may get an upset stomach, or a cat who sniffs the bowl and walks away. Going slowly avoids most of it.' ),
				array( 'h2', 'The 7 to 10 day plan' ),
				array( 'p', 'Mix the new food into the old in small steps. Most vet guidance uses a schedule like this:' ),
				array(
					'table',
					array(
						array( 'Days', 'Old food', 'New food' ),
						array( '1–2', '75%', '25%' ),
						array( '3–4', '50%', '50%' ),
						array( '5–6', '25%', '75%' ),
						array( '7 onwards', '0%', '100%' ),
					),
				),
				array( 'p', 'Fussy and older cats often need longer, sometimes a few weeks. There is no prize for finishing on day seven: if a step is not going well, stay on it for a few more days.' ),
				array( 'h2', 'If they will not eat it' ),
				array(
					'ul',
					array(
						'Go back to the last mix they ate happily and stay there longer.',
						'Start with less new food, about a tenth of the bowl.',
						'Some cats object to mixing. Serve the new food in a separate bowl next to the old one instead.',
						'Warm wet food for a few seconds so it smells stronger. Stir it and check it is not hot.',
						'Moving between dry and wet food is a texture change too, so allow the full ten days or more.',
					),
				),
				array( 'h2', 'Never wait it out' ),
				array( 'p', 'Never try to starve a cat into accepting a new food. A cat that goes a few days without eating can develop a serious liver condition called hepatic lipidosis.' ),
				array( 'p', 'If your cat stops eating, go back to the food they know and call your vet. Check with your vet before switching a cat with a health condition or one on a prescription diet.' ),
				array( 'h2', 'Signs it is working' ),
				array(
					'ul',
					array(
						'Meals are finished at the usual pace.',
						'Stools stay normal.',
						'Your cat is as keen at mealtimes as before.',
					),
				),
				$disclaimer,
				array( 'h2', 'Sources' ),
				array(
					'links',
					array(
						array( 'PetMD: How to switch cat food', 'https://www.petmd.com/cat/nutrition/how-to-switch-cat-food' ),
						array( 'AAFP Cat Friendly Clinic: Changing your cat’s food (PDF)', 'https://catvets.com/wp-content/uploads/2025/09/CCC-Changing-Your-Cats-Food.pdf' ),
						array( 'University of Illinois College of Veterinary Medicine: Hepatic lipidosis, when cats do not eat', 'https://vetmed.illinois.edu/pet-health-columns/hepatic-lipidosis-when-cats-dont-eat/' ),
						array( 'Cornell Feline Health Center: Hepatic lipidosis', 'https://www.vet.cornell.edu/departments-centers-and-institutes/cornell-feline-health-center/health-information/feline-health-topics/hepatic-lipidosis' ),
					),
				),
			),
		),
		array(
			'slug'     => 'treats-and-training-how-many-is-too-many',
			'title'    => 'Treats and training: how many is too many?',
			'cat'      => 'Training',
			'excerpt'  => 'Why small treats suit training, how to count them against meals, and which of ours are built for it.',
			'products' => array( 'YT-D-005', 'YT-D-006', 'YT-C-001' ),
			'body'     => array(
				array( 'p', 'Training runs on rewards, and rewards add up. Twenty treats in a session can quietly become a small meal.' ),
				array( 'h2', 'The 10% rule' ),
				array( 'p', 'Veterinary nutrition groups, including WSAVA and the nutrition service at UC Davis, give the same ceiling: treats, chews and extras should be no more than 10% of a dog’s or cat’s daily calories. The other 90% should come from a complete and balanced food.' ),
				array( 'p', 'Tufts University vet nutritionists put numbers on it. A fairly active 11 kg dog needs about 600 kcal a day, so up to 60 kcal from treats. A 4.5 kg cat needs just over 200 kcal a day, so about 20 kcal from treats.' ),
				array( 'h2', 'Why small treats suit training' ),
				array( 'p', 'A training session can mean ten or twenty rewards. That only fits the budget if each one is tiny. A pea-sized piece is enough: your pet gets the taste, swallows it quickly and is ready for the next try.' ),
				array( 'h2', 'Making the budget stretch' ),
				array(
					'ul',
					array(
						'Break bigger treats into small pieces before you start.',
						'Check the kcal per piece on the pack and count out the day’s treats in the morning.',
						'On busy training days, take a little out of their meals so the total stays the same.',
						'Save the most exciting treats for the hardest jobs, like coming back when called.',
					),
				),
				array( 'h2', 'Treats to skip' ),
				array( 'p', 'Some human foods are unsafe for dogs and cats, including chocolate, grapes and raisins, onions and garlic. If you use peanut butter, check the label for xylitol, a sweetener that is dangerous for dogs.' ),
				$disclaimer,
				array( 'h2', 'Sources' ),
				array(
					'links',
					array(
						$wsava_dog,
						array( 'WSAVA Global Nutrition Committee: Guide to treats for cats (PDF)', 'https://wsava.org/wp-content/uploads/2025/11/WSAVA_GuidetoTreats_Cats_251107.pdf' ),
						array( 'UC Davis Veterinary Nutrition Service: Treat guidelines for dogs (PDF)', 'https://nutrition.vetmed.ucdavis.edu/sites/g/files/dgvnsk491/files/inline-files/Treats-guidelines-for-dogs.pdf' ),
						$tufts,
						array( 'U.S. Food and Drug Administration: Xylitol and your dog', 'https://www.fda.gov/consumers/consumer-updates/xylitol-and-your-dog-danger-paws' ),
					),
				),
			),
		),
	);
}

/**
 * Turn a short list of [type, content] pairs into core block markup, so seeded copy reads like copy.
 * Types: lead, p, small, h2, ul, ol (array of strings), table (rows; first row is the header),
 * links (array of [label, url], opened in a new tab).
 *
 * @param array[] $spec Blocks in order.
 * @return string
 */
function yt_seed_blocks( $spec ) {
	$out = array();
	foreach ( $spec as $b ) {
		list( $type, $c ) = $b;
		switch ( $type ) {
			case 'lead':
				$out[] = '<!-- wp:paragraph {"fontSize":"large"} --><p class="has-large-font-size">' . esc_html( $c ) . '</p><!-- /wp:paragraph -->';
				break;
			case 'small':
				$out[] = '<!-- wp:paragraph {"fontSize":"small"} --><p class="has-small-font-size">' . esc_html( $c ) . '</p><!-- /wp:paragraph -->';
				break;
			case 'h2':
				$out[] = '<!-- wp:heading --><h2 class="wp-block-heading">' . esc_html( $c ) . '</h2><!-- /wp:heading -->';
				break;
			case 'ul':
			case 'ol':
			case 'links':
				$items = '';
				foreach ( $c as $item ) {
					$text   = 'links' === $type ? '<a href="' . esc_url( $item[1] ) . '" target="_blank" rel="noopener">' . esc_html( $item[0] ) . '</a>' : esc_html( $item );
					$items .= '<!-- wp:list-item --><li>' . $text . '</li><!-- /wp:list-item -->';
				}
				$ordered = 'ol' === $type;
				$out[]   = '<!-- wp:list ' . ( $ordered ? '{"ordered":true} ' : '' ) . '--><' . ( $ordered ? 'ol' : 'ul' ) . ' class="wp-block-list">' . $items . '</' . ( $ordered ? 'ol' : 'ul' ) . '><!-- /wp:list -->';
				break;
			case 'table':
				$head = '<thead><tr>' . implode( '', array_map( fn( $t ) => '<th>' . esc_html( $t ) . '</th>', $c[0] ) ) . '</tr></thead>';
				$body = '';
				foreach ( array_slice( $c, 1 ) as $row ) {
					$body .= '<tr>' . implode( '', array_map( fn( $t ) => '<td>' . esc_html( $t ) . '</td>', $row ) ) . '</tr>';
				}
				$out[] = '<!-- wp:table {"className":"yt-table"} --><figure class="wp-block-table yt-table"><table>' . $head . '<tbody>' . $body . '</tbody></table></figure><!-- /wp:table -->';
				break;
			default:
				$out[] = '<!-- wp:paragraph --><p>' . esc_html( $c ) . '</p><!-- /wp:paragraph -->';
		}
	}
	return implode( "\n\n", $out );
}

/* ------------------------------------------------------------------------- stockists */

function yt_seed_stockists() {
	$list = array(
		array( 'Paws Pantry Thonglor', 'Pet shop', 'Watthana', 'Bangkok', '[Address], Sukhumvit 55 (Thonglor), Watthana 10110', '[0X-XXX-XXXX]' ),
		array( 'Happy Tails Vet Clinic', 'Vet clinic', 'Watthana', 'Bangkok', '[Address], Sukhumvit 49, Watthana 10110', '[0X-XXX-XXXX]' ),
		array( 'Ari Pet Corner', 'Pet shop', 'Phaya Thai', 'Bangkok', '[Address], Phahonyothin 7 (Ari), Phaya Thai 10400', '[0X-XXX-XXXX]' ),
		array( 'Ratchada Animal Hospital', 'Vet clinic', 'Huai Khwang', 'Bangkok', '[Address], Ratchadaphisek Rd, Huai Khwang 10310', '[0X-XXX-XXXX]' ),
		array( 'Sathorn Dog & Cat Supply', 'Pet shop', 'Sathon', 'Bangkok', '[Address], Narathiwat Rd, Sathon 10120', '[0X-XXX-XXXX]' ),
		array( 'Lat Phrao Pet Mart', 'Pet shop', 'Lat Phrao', 'Bangkok', '[Address], Lat Phrao Rd, Lat Phrao 10230', '[0X-XXX-XXXX]' ),
		array( 'Bang Na Vet Centre', 'Vet clinic', 'Bang Na', 'Bangkok', '[Address], Bang Na-Trat Rd, Bang Na 10260', '[0X-XXX-XXXX]' ),
		array( 'Chaeng Watthana Pet House', 'Pet shop', 'Pak Kret', 'Nonthaburi', '[Address], Chaeng Watthana Rd, Pak Kret 11120', '[0X-XXX-XXXX]' ),
	);
	$n = 0;
	foreach ( $list as $s ) {
		$slug = sanitize_title( $s[0] );
		if ( get_page_by_path( $slug, OBJECT, 'yt_stockist' ) ) {
			continue;
		}
		$id = wp_insert_post(
			array(
				'post_type'   => 'yt_stockist',
				'post_status' => 'publish',
				'post_title'  => $s[0],
				'post_name'   => $slug,
				'meta_input'  => array( 'yt_address' => $s[4], 'yt_phone' => $s[5], 'yt_maps_url' => 'https://maps.google.com/?q=' . rawurlencode( $s[0] . ' ' . $s[3] ) ),
			)
		);
		foreach ( array( 'yt_stockist_type' => $s[1], 'yt_district' => $s[2], 'yt_province' => $s[3] ) as $tax => $name ) {
			$t = term_exists( $name, $tax ) ?: wp_insert_term( $name, $tax );
			if ( ! is_wp_error( $t ) ) {
				wp_set_object_terms( $id, (int) $t['term_id'], $tax );
			}
		}
		++$n;
	}
	return "stockists: {$n} created";
}

/* ------------------------------------------------------------------------- pages */

function yt_seed_pages() {
	// WooCommerce's own pages (shop, cart, checkout, my-account) exist after activation; make sure.
	if ( function_exists( 'wc_create_pages' ) ) {
		wc_create_pages();
	}
	$pages = array(
		'home'      => array( 'Home', '', '' ),
		'guides'    => array( 'Guides', '', '' ),
		'stockists' => array( 'Find us in a shop', yt_seed_page_stockists(), 'page-stockists' ),
		'help'      => array( 'Help', yt_seed_page_help(), 'page-help' ),
		'terms'     => array( 'Terms of sale', '<!-- wp:paragraph --><p>[Terms of sale — supplied by the client. Covers ordering, payment, delivery times, returns and contact.]</p><!-- /wp:paragraph -->', '' ),
	);
	$ids = array();
	foreach ( $pages as $slug => $p ) {
		$existing = get_page_by_path( $slug, OBJECT, 'page' );
		if ( $existing ) {
			$ids[ $slug ] = $existing->ID;
			continue;
		}
		$ids[ $slug ] = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $p[0],
				'post_name'    => $slug,
				'post_content' => $p[1],
				'page_template' => $p[2],
			)
		);
	}
	// Privacy policy: publish WordPress's draft under a PDPA title.
	$privacy = (int) get_option( 'wp_page_for_privacy_policy' );
	if ( $privacy ) {
		wp_update_post( array( 'ID' => $privacy, 'post_status' => 'publish', 'post_title' => 'Privacy (PDPA)', 'post_name' => 'privacy-policy' ) );
	}
	$sample = get_page_by_path( 'sample-page', OBJECT, 'page' );
	if ( $sample ) {
		wp_delete_post( $sample->ID, true );
	}
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $ids['home'] );
	update_option( 'page_for_posts', $ids['guides'] );
	update_option( 'woocommerce_terms_page_id', $ids['terms'] );
	yt_seed_empty_cart( (int) wc_get_page_id( 'cart' ) );
	return 'pages: home, guides, stockists, help, terms, privacy';
}

function yt_seed_page_stockists() {
	return '<!-- wp:paragraph {"fontSize":"large"} --><p class="has-large-font-size">Same treats, no delivery wait. Yummy Treats! is on the shelf at these pet shops and vet clinics. Call ahead for stock of a specific size.</p><!-- /wp:paragraph -->';
}

/**
 * Help page copy. Links sit at the end of a sentence or on their own line: the Thai dictionary translates each text
 * piece separately, so a link in mid-sentence would force English word order on the Thai.
 *
 * @return string
 */
function yt_seed_page_help() {
	$h  = fn( $anchor, $text ) => '<!-- wp:heading {"anchor":"' . $anchor . '"} --><h2 class="wp-block-heading" id="' . $anchor . '">' . $text . '</h2><!-- /wp:heading -->';
	$p  = fn( $html ) => '<!-- wp:paragraph --><p>' . $html . '</p><!-- /wp:paragraph -->';
	$li = fn( $label, $text ) => '<!-- wp:list-item --><li><strong>' . $label . '</strong> — ' . $text . '</li><!-- /wp:list-item -->';
	// Each section is a tinted card (templates/page-help.html + theme.css "yt-help-card"); the tint rotates.
	$tints = array( 'blue', 'raspberry', 'green', 'violet', 'teal', 'mustard', 'blue' );
	$n     = 0;
	$card  = function ( ...$blocks ) use ( $tints, &$n ) {
		$tint = $tints[ $n % count( $tints ) ];
		++$n;
		return '<!-- wp:group {"className":"yt-help-card is-style-card-flat","backgroundColor":"tint-' . $tint . '","layout":{"type":"constrained"}} --><div class="wp-block-group yt-help-card is-style-card-flat has-tint-' . $tint . '-background-color has-background" data-n="' . $n . '">' . implode( "\n\n", $blocks ) . '</div><!-- /wp:group -->';
	};
	return implode(
		"\n\n",
		array(
			$card( $h( 'delivery', 'Delivery' ), $p( '<strong>Bangkok, Nonthaburi and Samut Prakan:</strong> ฿50, free on orders over ฿1,000. Usually next day. Order before 14:00 Mon–Sat for same-day dispatch.' ), $p( '<strong>Rest of Thailand:</strong> ฿80, free over ฿1,500. Two to three days with [courier name].' ), $p( 'Fresh frozen meals ship in an insulated box with ice packs, to the Bangkok area only.' ) ),
			$card( $h( 'payment', 'How to pay' ), '<!-- wp:list --><ul class="wp-block-list">' . $li( 'PromptPay', 'scan the QR at checkout with any Thai banking app. Confirms in seconds.' ) . $li( 'Credit / debit card', 'Visa, Mastercard and JCB, processed by Opn Payments. We never see your card number.' ) . $li( 'Bank transfer', 'transfer to [Bank name] [XXX-X-XXXXX-X], then send us the slip from the confirmation page, the link in your order email, or My account. We check slips within the hour on working days, 9:00 to 18:00. Unpaid orders are cancelled after 24 hours.' ) . $li( 'Cash on delivery', 'Bangkok, Nonthaburi and Samut Prakan only. Please have the exact amount ready.' ) . '</ul><!-- /wp:list -->' ),
			$card( $h( 'preorders', 'Pre-orders and sold-out items' ), $p( 'Items marked Pre-order ship on the date shown on the product. If your order also has items in stock, those leave first and the pre-order follows on its own.' ), $p( 'Sold out? Leave your email on the product page and we send one message when it is back. We do not use that email for anything else.' ) ),
			$card( $h( 'returns', 'Not a hit with your pet?' ), $p( 'Message us on LINE within 7 days with a photo of the pack and we will swap it for something else or refund it. Opened food cannot be resold, so we donate it to [shelter name]. Damaged or wrong items are replaced free, no questions.' ) ),
			$card( $h( 'orders', 'Orders and accounts' ), $p( 'You can check out as a guest. An account lets you see past orders, order again in one tap and send a slip later.' ), $p( 'Order status and slips are under <a href="/my-account/orders/">My account → Orders</a>' ) ),
			$card( $h( 'stockists', 'Buying in a shop instead' ), $p( 'Prices in shops are set by the shop and may differ slightly from the website. For shop purchases, returns go through the shop.' ), $p( '<a href="/stockists/">Find a stockist →</a>' ) ),
			$card( $h( 'pdpa', 'Your data (PDPA)' ), $p( 'We keep your name, address and phone number to deliver your order and for the tax invoice. We only message you on LINE if you tick the box at checkout.' ), $p( '<a href="/privacy-policy/">Read the full privacy notice →</a>' ) ),
		)
	);
}

/**
 * The cart page's empty state: WooCommerce's default ("Your cart is currently empty!" plus a newest-products grid)
 * swapped for the shop's own message and ways back in.
 *
 * @param int $cart_id Cart page ID.
 */
function yt_seed_empty_cart( $cart_id ) {
	$post = get_post( $cart_id );
	if ( ! $post || false === strpos( $post->post_content, 'wp:woocommerce/empty-cart-block' ) ) {
		return;
	}
	$inner = '<!-- wp:woocommerce/empty-cart-block -->
<div class="wp-block-woocommerce-empty-cart-block"><!-- wp:group {"className":"yt-empty is-style-card-flat","layout":{"type":"constrained"}} -->
<div class="wp-block-group yt-empty is-style-card-flat"><!-- wp:heading {"textAlign":"center","level":2} --><h2 class="wp-block-heading has-text-align-center">Your cart is empty</h2><!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">Nothing in here yet. Pick a side, or start with a bundle.</p><!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/product-category/dogs/">Shop dogs</a></div><!-- /wp:button -->

<!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/product-category/cats/">Shop cats</a></div><!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/product-category/bundles/">See the bundles</a></div><!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:woocommerce/empty-cart-block -->';
	$content = preg_replace( '#<!-- wp:woocommerce/empty-cart-block -->.*?<!-- /wp:woocommerce/empty-cart-block -->#s', $inner, $post->post_content, 1 );
	if ( $content && $content !== $post->post_content ) {
		wp_update_post( array( 'ID' => $cart_id, 'post_content' => $content ) );
	}
}

/* ------------------------------------------------------------------------- translatepress */

/**
 * TranslatePress: English at /, Thai at /th/, and a seeded Thai dictionary for the demo's own copy.
 *
 * The seed content is written in English, so English has to be TranslatePress's "default" (source) language;
 * Thai is the translation layer on top. The design calls for Thai first, so the header switcher lists ไทย first
 * and the hosted link can be shared as /th/ directly.
 *
 * @return string
 */
function yt_seed_translatepress() {
	if ( ! class_exists( 'TRP_Translate_Press' ) ) {
		return 'translatepress: not active (local dev: drop the zip into vendor/ and re-run tools/setup-site.sh)';
	}
	$trp      = TRP_Translate_Press::get_trp_instance();
	$settings = $trp->get_component( 'settings' );
	$query    = $trp->get_component( 'query' );
	$current  = get_option( 'trp_settings', array() );
	$desired  = array_merge(
		is_array( $current ) ? $current : array(),
		array(
			'default-language'                     => 'en_US',
			'translation-languages'                => array( 'en_US', 'th' ),
			'publish-languages'                    => array( 'en_US', 'th' ),
			'native_or_english_name'               => 'native_name',
			'add-subdirectory-to-default-language' => 'no',
			'force-language-to-custom-links'       => 'yes',
			'url-slugs'                            => array( 'en_US' => 'en', 'th' => 'th' ),
			'trp-ls-floater'                       => 'no',
			'shortcode-options'                    => 'short-names',
			'menu-options'                         => 'short-names',
			'floater-options'                      => 'short-names',
			'trp-ls-show-poweredby'                => 'no',
		)
	);
	// sanitize_settings() also creates the dictionary tables for each language.
	update_option( 'trp_settings', $settings->sanitize_settings( $desired ) );
	// The v2 floating switcher has its own option; the theme ships its own ไทย / EN links instead.
	$ls = get_option( 'trp_language_switcher_settings', array() );
	if ( is_array( $ls ) ) {
		$ls['floater']   = array_merge( $ls['floater'] ?? array(), array( 'enabled' => false ) );
		$ls['menu']      = array_merge( $ls['menu'] ?? array(), array( 'enabled' => false ) );
		update_option( 'trp_language_switcher_settings', $ls );
	}

	$dict = require __DIR__ . '/translations-th.php';
	global $wpdb;

	// Regular strings (text nodes).
	$originals = array_keys( $dict['regular'] );
	$existing  = $query->get_string_ids( $originals, 'th' );
	$missing   = array_values( array_diff( $originals, array_keys( (array) $existing ) ) );
	if ( $missing ) {
		$query->insert_strings( $missing, 'th' );
	}
	$table = $query->get_table_name( 'th' );
	$ids   = $query->get_string_ids( $originals, 'th' );
	$n     = 0;
	foreach ( $dict['regular'] as $original => $thai ) {
		if ( isset( $ids[ $original ] ) ) {
			$wpdb->update( $table, array( 'translated' => $thai, 'status' => $query->get_constant_human_reviewed() ), array( 'id' => (int) $ids[ $original ]->id ) );
			++$n;
		}
	}

	// Gettext strings are NOT routed through TranslatePress: on Playground's SQLite its string store cannot write
	// (ON DUPLICATE KEY UPDATE … LAST_INSERT_ID()), so every request re-queried thousands of pending strings (~7 s a page).
	// The theme ships languages/th.mo and languages/woocommerce-th.mo instead (tools/make-mo.py), which WordPress
	// loads natively when TranslatePress switches the locale to th on /th/ pages.
	$adv = get_option( 'trp_advanced_settings', array() );
	$adv = is_array( $adv ) ? $adv : array();
	$adv['disable_translation_for_gettext_strings'] = 'yes';
	$adv['disable_dynamic_translation']             = 'no';
	update_option( 'trp_advanced_settings', $adv );
	$m = 0;

	// Full Thai language packs for core and WooCommerce, when the network allows (hosted Playground does; local dev does not).
	if ( function_exists( 'wp_can_install_language_pack' ) === false ) {
		require_once ABSPATH . 'wp-admin/includes/translation-install.php';
	}
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	if ( wp_can_install_language_pack() ) {
		wp_download_language_pack( 'th' );
		wp_clean_update_cache();
		wp_update_plugins();
		$updates = wp_get_translation_updates();
		if ( $updates ) {
			$upgrader = new Language_Pack_Upgrader( new Automatic_Upgrader_Skin() );
			$upgrader->bulk_upgrade( $updates );
			$m = count( $updates );
		}
	}
	return "translatepress: en_US at /, th at /th/; {$n} text strings in Thai via dictionary, gettext via languages/*.mo, {$m} language packs downloaded";
}

/* ------------------------------------------------------------------------- demo orders */

/**
 * Four demo orders, one per confirmation state, so Admin → Orders and the slip column have something to show.
 * Guest orders with example.com addresses; customers' names are marked [demo]. No emails are sent while seeding.
 *
 * @return string
 */
function yt_seed_orders() {
	$existing = wc_get_orders( array( 'limit' => 1, 'created_via' => 'yt-demo', 'return' => 'ids' ) );
	if ( $existing ) {
		return 'orders: already seeded';
	}
	add_filter( 'pre_wp_mail', '__return_false' );
	$plan = array(
		array( 'Nok', 'bacs', 'on-hold', array( 'YT-D-007' => 2, 'YT-D-001' => 1 ), null, '-3 hours' ),
		array( 'Ploy', 'bacs', 'on-hold', array( 'YT-C-001' => 2, 'YT-C-006' => 1 ), 'slip', '-1 hours' ),
		array( 'Beam', 'cod', 'processing', array( 'YT-D-012' => 1 ), null, '-1 days' ),
		array( 'Mint', 'bacs', 'completed', array( 'YT-C-012' => 1, 'YT-C-002' => 1 ), 'slip-paid', '-4 days' ),
	);
	$titles = array( 'bacs' => 'Bank transfer (upload your slip)', 'cod' => 'Cash on delivery' );
	$n      = 0;
	$urls   = array();
	foreach ( $plan as $p ) {
		list( $name, $method, $status, $items, $slip, $when ) = $p;
		$order = wc_create_order( array( 'status' => 'pending', 'customer_id' => 0, 'created_via' => 'yt-demo' ) );
		foreach ( $items as $sku => $qty ) {
			$product = wc_get_product( wc_get_product_id_by_sku( $sku ) );
			if ( $product && $product->is_type( 'variable' ) ) {
				$children = $product->get_children();
				$product  = wc_get_product( $children[0] ?? 0 );
			}
			if ( $product ) {
				$order->add_product( $product, $qty );
			}
		}
		$address = array(
			'first_name' => $name,
			'last_name'  => '[demo]',
			'address_1'  => '[House number, street]',
			'address_2'  => 'Khlong Toei Nuea',
			'city'       => 'Watthana',
			'state'      => 'TH-10',
			'postcode'   => '10110',
			'country'    => 'TH',
		);
		$order->set_address( $address + array( 'email' => strtolower( $name ) . '@example.com', 'phone' => '[08X-XXX-XXXX]' ), 'billing' );
		$order->set_address( $address, 'shipping' );
		$ship = new WC_Order_Item_Shipping();
		$ship->set_method_title( 'Courier delivery' );
		$ship->set_method_id( 'flat_rate' );
		$ship->set_total( 50 );
		$order->add_item( $ship );
		$order->set_payment_method( $method );
		$order->set_payment_method_title( $titles[ $method ] );
		$order->calculate_totals( false );
		$order->set_date_created( strtotime( $when ) );
		$order->update_meta_data( '_yt_demo_order', 1 );
		$order->save();
		$order->update_status( 'on-hold' === $status || 'completed' === $status ? 'on-hold' : $status, 'Demo order.' );
		if ( $slip && function_exists( 'yt_slip_upload_attach' ) ) {
			yt_slip_upload_attach( $order, __DIR__ . '/img/sample-slip.png' );
		}
		if ( 'completed' === $status ) {
			$order->payment_complete();
			$order->update_status( 'completed', 'Demo order: shipped.' );
		}
		$urls[] = $name . ' ' . $order->get_checkout_order_received_url();
		++$n;
	}
	remove_filter( 'pre_wp_mail', '__return_false' );
	return "orders: {$n} demo orders\n  " . implode( "\n  ", $urls );
}
