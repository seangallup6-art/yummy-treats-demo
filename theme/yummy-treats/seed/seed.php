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
	$log[] = yt_seed_pages();
	$log[] = yt_seed_translatepress();
	flush_rewrite_rules();
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
			'description'  => 'Transfer to the account below, then upload your payment slip on the confirmation page or from your account. We pack as soon as we see it.',
			'instructions' => 'Transfer the total to [Bank name], account [XXX-X-XXXXX-X], name [Company name] Co., Ltd. Then upload your slip. Orders unpaid after 24 hours are cancelled automatically.',
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
	// Opn (Omise) — keys are never typed by us. Sean enters test keys in WooCommerce → Settings → Payments → Opn.
	$omise = get_option( 'omise', array() );
	$omise = array_merge(
		is_array( $omise ) ? $omise : array(),
		array(
			'sandbox'                  => 'yes',
			'test_public_key'          => $omise['test_public_key'] ?? '',
			'test_private_key'         => $omise['test_private_key'] ?? '',
			'live_public_key'          => '',
			'live_private_key'         => '',
		)
	);
	update_option( 'omise', $omise );
	update_option( 'woocommerce_omise_settings', array( 'enabled' => 'yes', 'title' => 'Credit / debit card', 'description' => 'Visa, Mastercard and JCB. Processed securely by Opn Payments.', 'payment_action' => 'auto_capture' ) );
	update_option( 'woocommerce_omise_promptpay_settings', array( 'enabled' => 'yes', 'title' => 'PromptPay', 'description' => 'Scan the QR code with any Thai banking app. Confirms within seconds.' ) );
	update_option(
		'woocommerce_gateway_order',
		array( 'omise_promptpay' => 0, 'omise' => 1, 'bacs' => 2, 'cod' => 3 )
	);
	return 'payments: PromptPay, card (Opn test mode, keys to be entered), bank transfer, COD';
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
	$ships   = gmdate( 'j M', strtotime( '+10 days' ) );
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
			update_post_meta( $id, '_yt_preorder_ships', 'Ships ' . $ships );
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
				$att = media_handle_sideload( array( 'name' => $p['sku'] . '-' . $n . '.png', 'tmp_name' => $tmp ), $id, $p['name'] . ( 2 === $n ? ' (back of bag)' : '' ) );
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

function yt_seed_product_body( $p ) {
	$pet = 'Dogs' === $p['pet'] ? 'dog' : 'cat';
	return implode(
		"\n\n",
		array(
			'<!-- wp:paragraph --><p>' . esc_html( $p['short'] ) . ' Made in small batches in [province] and packed within days of cooking.</p><!-- /wp:paragraph -->',
			'<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Ingredients</h3><!-- /wp:heading -->',
			'<!-- wp:paragraph --><p>[Ingredient list, in descending order by weight — supplied by the client.]</p><!-- /wp:paragraph -->',
			'<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Feeding guide</h3><!-- /wp:heading -->',
			'<!-- wp:paragraph --><p>Treats should stay a small part of your ' . $pet . "'s day; the rest comes from their main food. [Feeding table by body weight — supplied by the client or their vet.] Always keep fresh water nearby.</p><!-- /wp:paragraph -->",
			'<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Storage</h3><!-- /wp:heading -->',
			'<!-- wp:paragraph --><p>Reseal after opening and keep cool and dry. In Bangkok humidity, the fridge is your friend. Use within [N] weeks of opening.</p><!-- /wp:paragraph -->',
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
	$guides = array(
		array(
			'slug'    => 'how-much-should-a-condo-dog-eat',
			'title'   => 'How much should a Bangkok condo dog actually eat?',
			'cat'     => 'Dogs',
			'excerpt' => 'Less than the bag says, usually. A plain-language way to work it out with your vet, and the signs you have it right.',
		),
		array(
			'slug'    => 'switching-your-cat-to-a-new-food',
			'title'   => 'Switching your cat to a new food without the drama',
			'cat'     => 'Cats',
			'excerpt' => 'Cats hate surprises. A seven-day plan for mixing old and new, and what to do when they stage a hunger strike.',
		),
		array(
			'slug'    => 'treats-and-training-how-many-is-too-many',
			'title'   => 'Treats and training: how many is too many?',
			'cat'     => 'Training',
			'excerpt' => 'Why tiny treats train better than big ones, how to count them against meals, and which of ours are built for it.',
		),
	);
	$n = 0;
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
				'post_content'  => yt_seed_guide_body( $g ),
				'post_category' => $term ? array( $term->term_id ) : array(),
				'post_date'     => gmdate( 'Y-m-d H:i:s', strtotime( '-' . ( 7 + 10 * $n ) . ' days' ) ),
			)
		);
		++$n;
	}
	return "guides: {$n} created";
}

function yt_seed_guide_body( $g ) {
	return implode(
		"\n\n",
		array(
			'<!-- wp:paragraph {"fontSize":"large"} --><p class="has-large-font-size">' . esc_html( $g['excerpt'] ) . '</p><!-- /wp:paragraph -->',
			'<!-- wp:heading --><h2 class="wp-block-heading">Start with your vet, not the bag</h2><!-- /wp:heading -->',
			'<!-- wp:paragraph --><p>[Opening section — 2 to 3 short paragraphs in the brand voice. Practical, specific to Bangkok life (condos, heat, long work days), no scare stories. Reviewed by [vet name], [clinic].]</p><!-- /wp:paragraph -->',
			'<!-- wp:heading --><h2 class="wp-block-heading">The simple method</h2><!-- /wp:heading -->',
			'<!-- wp:list {"ordered":true} --><ol class="wp-block-list"><!-- wp:list-item --><li>[Step one]</li><!-- /wp:list-item --><!-- wp:list-item --><li>[Step two]</li><!-- /wp:list-item --><!-- wp:list-item --><li>[Step three]</li><!-- /wp:list-item --></ol><!-- /wp:list -->',
			'<!-- wp:quote --><blockquote class="wp-block-quote"><!-- wp:paragraph --><p>[A one-line tip from the vet reviewer, in their words.]</p><!-- /wp:paragraph --><cite>[Vet name], [clinic]</cite></blockquote><!-- /wp:quote -->',
			'<!-- wp:heading --><h2 class="wp-block-heading">Signs you have it right</h2><!-- /wp:heading -->',
			'<!-- wp:paragraph --><p>[Closing section with 3 to 4 observable signs, then a soft link to the relevant products.]</p><!-- /wp:paragraph -->',
			'<!-- wp:paragraph --><p><strong>Products mentioned:</strong> <a href="/shop/">see the shop</a>.</p><!-- /wp:paragraph -->',
		)
	);
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
	return 'pages: home, guides, stockists, help, terms, privacy';
}

function yt_seed_page_stockists() {
	return '<!-- wp:paragraph {"fontSize":"large"} --><p class="has-large-font-size">Same treats, no delivery wait. Yummy Treats! is on the shelf at these pet shops and vet clinics. Call ahead for stock of a specific size.</p><!-- /wp:paragraph -->';
}

function yt_seed_page_help() {
	return implode(
		"\n\n",
		array(
			'<!-- wp:heading {"anchor":"delivery"} --><h2 class="wp-block-heading" id="delivery">Delivery</h2><!-- /wp:heading -->',
			'<!-- wp:paragraph --><p><strong>Bangkok, Nonthaburi and Samut Prakan:</strong> ฿50, free on orders over ฿1,000. Usually next day; order before 14:00 Mon–Sat for same-day dispatch.</p><!-- /wp:paragraph -->',
			'<!-- wp:paragraph --><p><strong>Rest of Thailand:</strong> ฿80, free over ฿1,500. Two to three days with [courier name]. Fresh frozen meals ship in an insulated box with ice packs, Bangkok area only.</p><!-- /wp:paragraph -->',
			'<!-- wp:heading {"anchor":"payment"} --><h2 class="wp-block-heading" id="payment">How to pay</h2><!-- /wp:heading -->',
			'<!-- wp:list --><ul class="wp-block-list"><!-- wp:list-item --><li><strong>PromptPay</strong> — scan the QR at checkout with any Thai banking app. Confirms in seconds.</li><!-- /wp:list-item --><!-- wp:list-item --><li><strong>Credit / debit card</strong> — Visa, Mastercard, JCB, processed by Opn Payments. We never see your card number.</li><!-- /wp:list-item --><!-- wp:list-item --><li><strong>Bank transfer</strong> — transfer to [Bank name] [XXX-X-XXXXX-X], then upload your slip on the confirmation page. Unpaid orders cancel after 24 hours.</li><!-- /wp:list-item --><!-- wp:list-item --><li><strong>Cash on delivery</strong> — Bangkok area only. Exact change helps the courier.</li><!-- /wp:list-item --></ul><!-- /wp:list -->',
			'<!-- wp:heading {"anchor":"returns"} --><h2 class="wp-block-heading" id="returns">Not a hit with your pet?</h2><!-- /wp:heading -->',
			'<!-- wp:paragraph --><p>Message us on LINE within 7 days with a photo of the pack and we will swap it for something else or refund it. Opened food cannot be resold, so we donate it to [shelter name]. Damaged or wrong items are replaced free, no questions.</p><!-- /wp:paragraph -->',
			'<!-- wp:heading {"anchor":"orders"} --><h2 class="wp-block-heading" id="orders">Orders and accounts</h2><!-- /wp:heading -->',
			'<!-- wp:paragraph --><p>You can check out as a guest. Create an account to see past orders, re-order in one tap and upload a slip later. Track your parcel from <a href="/my-account/orders/">My account → Orders</a>.</p><!-- /wp:paragraph -->',
			'<!-- wp:heading {"anchor":"stockists"} --><h2 class="wp-block-heading" id="stockists">Buying in a shop instead</h2><!-- /wp:heading -->',
			'<!-- wp:paragraph --><p>Prices in shops are set by the shop and may differ slightly from the website. For shop purchases, returns go through the shop. <a href="/stockists/">Find a stockist →</a></p><!-- /wp:paragraph -->',
			'<!-- wp:heading {"anchor":"pdpa"} --><h2 class="wp-block-heading" id="pdpa">Your data (PDPA)</h2><!-- /wp:heading -->',
			'<!-- wp:paragraph --><p>We keep your name, address and phone number to deliver your order and for the tax invoice. We only message you on LINE if you tick the box at checkout. Full details in our <a href="/privacy-policy/">privacy notice</a>.</p><!-- /wp:paragraph -->',
		)
	);
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

	// Gettext strings (__() in the theme and WooCommerce). Pattern copy goes through esc_html_e(), so TranslatePress
	// files it under gettext too; every "regular" line is therefore mirrored into the yummy-treats domain.
	// Rows are written with $wpdb->insert() because TranslatePress's own bulk insert uses
	// ON DUPLICATE KEY UPDATE ... LAST_INSERT_ID(), which the SQLite driver used by Playground does not support.
	$gi     = $query->get_query_component( 'gettext_insert_update' );
	$gtable = $query->get_gettext_table_name( 'th' );
	$rows   = array();
	foreach ( $dict['regular'] as $original => $thai ) {
		$rows[] = array( 'original' => $original, 'translated' => $thai, 'domain' => 'yummy-treats', 'plural_form' => 0, 'original_plural' => '' );
	}
	foreach ( $dict['gettext'] as $g ) {
		$rows[] = array( 'original' => $g[1], 'translated' => $g[2], 'domain' => $g[0], 'plural_form' => 0, 'original_plural' => '' );
	}
	$original_ids = $gi->gettext_original_strings_sync( $rows );
	$have         = $wpdb->get_results( "SELECT id, original, domain FROM `{$gtable}`", ARRAY_A ); // phpcs:ignore WordPress.DB
	$seen         = array();
	foreach ( (array) $have as $h ) {
		$seen[ $h['domain'] . "\0" . $h['original'] ] = (int) $h['id'];
	}
	$m = 0;
	foreach ( $rows as $key => $r ) {
		$k    = $r['domain'] . "\0" . $r['original'];
		$data = array( 'translated' => $r['translated'], 'status' => $query->get_constant_human_reviewed() );
		if ( isset( $seen[ $k ] ) ) {
			$wpdb->update( $gtable, $data, array( 'id' => $seen[ $k ] ) );
		} else {
			$wpdb->insert( $gtable, $data + array( 'original' => $r['original'], 'domain' => $r['domain'], 'plural_form' => 0, 'original_id' => isset( $original_ids[ $key ] ) ? (int) $original_ids[ $key ] : 0 ) );
		}
		++$m;
	}
	return "translatepress: en_US at /, th at /th/; {$n} text strings + {$m} gettext strings in Thai";
}
