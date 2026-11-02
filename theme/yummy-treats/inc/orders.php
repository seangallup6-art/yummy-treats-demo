<?php
/**
 * Order confirmation, top-down:
 *   yt_order_state()  decides which of six states an order is in (from payment method, status and slip);
 *   the heading filters, the "what happens next" steps and the guide suggestion all read that one state.
 *
 * The payment box itself (bank details + slip upload, or the Opn PromptPay QR) comes from the payment method's
 * own "thank you" hook, rendered by WooCommerce's Additional Information block in templates/order-confirmation.html.
 *
 * @package YummyTreats
 */

defined( 'ABSPATH' ) || exit;

/**
 * The order shown on the order-received page, if the URL carries its key.
 *
 * @return WC_Order|null
 */
function yt_received_order() {
	static $order = false;
	if ( false !== $order ) {
		return $order;
	}
	$order = null;
	$id    = absint( get_query_var( 'order-received' ) );
	$key   = isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	if ( $id && $key ) {
		$found = wc_get_order( $id );
		if ( $found && $found->key_is_valid( $key ) ) {
			$order = $found;
		}
	}
	return $order;
}

/**
 * Which state the order is in, for the confirmation page.
 *
 * @param WC_Order $order Order.
 * @return string transfer-waiting | transfer-checking | promptpay-waiting | cod | paid | shipped | failed | cancelled
 */
function yt_order_state( $order ) {
	$status = $order->get_status();
	$method = $order->get_payment_method();
	if ( in_array( $status, array( 'failed' ), true ) ) {
		return 'failed';
	}
	if ( in_array( $status, array( 'cancelled', 'refunded' ), true ) ) {
		return 'cancelled';
	}
	if ( 'completed' === $status ) {
		return 'shipped';
	}
	if ( 'cod' === $method ) {
		return 'cod';
	}
	if ( in_array( $status, array( 'pending', 'on-hold' ), true ) ) {
		if ( 'bacs' === $method ) {
			$slip = function_exists( 'yt_slip_upload_get' ) ? yt_slip_upload_get( $order ) : null;
			return $slip ? 'transfer-checking' : 'transfer-waiting';
		}
		if ( str_contains( $method, 'promptpay' ) ) {
			return 'promptpay-waiting';
		}
	}
	return 'paid';
}

/**
 * Heading and lead text per state (WooCommerce's Order Confirmation Status block).
 *
 * @param WC_Order $order Order.
 * @return array{0:string,1:string}|null
 */
function yt_order_headline( $order ) {
	$total = wp_strip_all_tags( wc_price( $order->get_total() ) );
	switch ( yt_order_state( $order ) ) {
		case 'transfer-waiting':
			/* translators: %s: order total */
			return array( __( 'Almost there: transfer to pay', 'yummy-treats' ), sprintf( __( 'Your order is saved. Transfer %s to the account below, then send us the slip. We hold your order for 24 hours.', 'yummy-treats' ), $total ) );
		case 'transfer-checking':
			return array( __( 'Slip received, we are checking it', 'yummy-treats' ), __( 'Thanks! We check slips within the hour on working days, 9:00 to 18:00, then pack your order.', 'yummy-treats' ) );
		case 'promptpay-waiting':
			return array( __( 'Scan to pay', 'yummy-treats' ), __( 'Open any Thai bank app and scan the code below. This page updates itself when the payment lands.', 'yummy-treats' ) );
		case 'cod':
			/* translators: %s: order total */
			return array( __( 'Order in! Pay when it arrives', 'yummy-treats' ), sprintf( __( 'Have %s ready in cash for the courier. Orders before 14:00 leave today.', 'yummy-treats' ), $total ) );
		case 'paid':
			/* translators: %s: order total */
			return array( __( 'Paid, we are packing', 'yummy-treats' ), sprintf( __( 'Payment of %s received. Tracking comes by email once the courier scans your box.', 'yummy-treats' ), $total ) );
		case 'shipped':
			return array( __( 'On its way', 'yummy-treats' ), __( 'Your order has left us. Bangkok deliveries usually arrive the next day.', 'yummy-treats' ) );
	}
	return null;
}

add_filter(
	'woocommerce_thankyou_order_received_title',
	function ( $title, $order ) {
		$h = $order instanceof WC_Order ? yt_order_headline( $order ) : null;
		return $h ? esc_html( $h[0] ) : $title;
	},
	10,
	2
);

add_filter(
	'woocommerce_thankyou_order_received_text',
	function ( $text, $order ) {
		$h = $order instanceof WC_Order ? yt_order_headline( $order ) : null;
		return $h ? esc_html( $h[1] ) : $text;
	},
	10,
	2
);

add_filter(
	'woocommerce_thankyou_order_failed_text',
	function () {
		return esc_html__( 'Nothing was charged. Try again, or pick another way to pay. Your basket is still here.', 'yummy-treats' );
	}
);

/**
 * "What happens next": four steps, each done, current or to do.
 *
 * @param WC_Order $order Order.
 * @return array<int,array{title:string,sub:string,state:string}>
 */
function yt_order_steps( $order ) {
	$state = yt_order_state( $order );
	$total = wp_strip_all_tags( wc_price( $order->get_total() ) );
	$pay   = array(
		'transfer-waiting'  => array( __( 'Transfer and send the slip', 'yummy-treats' ), __( 'Upload it below or send it on LINE.', 'yummy-treats' ), 'current' ),
		'transfer-checking' => array( __( 'We check your slip', 'yummy-treats' ), __( 'Within the hour on working days.', 'yummy-treats' ), 'current' ),
		'promptpay-waiting' => array( __( 'Scan to pay', 'yummy-treats' ), __( 'This page updates when it lands.', 'yummy-treats' ), 'current' ),
		/* translators: %s: order total */
		'cod'               => array( __( 'Pay the courier', 'yummy-treats' ), sprintf( __( '%s in cash when it arrives.', 'yummy-treats' ), $total ), 'todo' ),
	);
	$paid       = ! isset( $pay[ $state ] );
	$payment    = $pay[ $state ] ?? array( __( 'Payment received', 'yummy-treats' ), $total, 'done' );
	$pack_state = 'shipped' === $state ? 'done' : ( ( $paid || 'cod' === $state ) ? 'current' : 'todo' );

	$steps = array(
		array( __( 'Order received', 'yummy-treats' ), '#' . $order->get_order_number(), 'done' ),
		$payment,
		array( __( 'Packed and handed to the courier', 'yummy-treats' ), __( 'Same day for orders before 14:00, Mon to Sat.', 'yummy-treats' ), $pack_state ),
		array( __( 'Delivered', 'yummy-treats' ), __( 'Bangkok usually next day, the rest of Thailand 2 to 3 days.', 'yummy-treats' ), 'shipped' === $state ? 'current' : 'todo' ),
	);
	if ( 'cod' === $state ) {
		// Cash on delivery: payment happens at the door, so it comes after packing.
		$steps = array( $steps[0], $steps[2], $steps[3], $payment );
	}
	return array_map(
		static fn( $s ) => array(
			'title' => $s[0],
			'sub'   => $s[1],
			'state' => $s[2],
		),
		$steps
	);
}

/**
 * Pre-order lines in the order, with their ship text ("Ships 12 Nov").
 *
 * @param WC_Order $order Order.
 * @return array<int,array{name:string,ships:string}>
 */
function yt_order_preorders( $order ) {
	$out = array();
	foreach ( $order->get_items() as $item ) {
		$ships = yt_preorder_ships( $item->get_product() );
		if ( $ships ) {
			$out[] = array(
				'name'  => wp_strip_all_tags( $item->get_name() ),
				'ships' => $ships,
			);
		}
	}
	return $out;
}

/**
 * The care guide that fits what was bought: cats → switching food; dog meals → portions; treats → training.
 *
 * @param WC_Order $order Order.
 * @return WP_Post|null
 */
function yt_order_guide( $order ) {
	$cats = array();
	foreach ( $order->get_items() as $item ) {
		$product = $item->get_product();
		$id      = $product ? ( $product->get_parent_id() ?: $product->get_id() ) : 0;
		foreach ( wp_get_post_terms( $id, 'product_cat', array( 'fields' => 'slugs' ) ) as $slug ) {
			$cats[ $slug ] = true;
		}
	}
	if ( isset( $cats['cats'] ) ) {
		$slug = 'switching-your-cat-to-a-new-food';
	} elseif ( isset( $cats['meals'] ) ) {
		$slug = 'how-much-should-a-condo-dog-eat';
	} else {
		$slug = 'treats-and-training-how-many-is-too-many';
	}
	$post = get_page_by_path( $slug, OBJECT, 'post' );
	return ( $post && 'publish' === $post->post_status ) ? $post : null;
}

/**
 * Reading time in minutes (200 words a minute, at least 1).
 *
 * @param WP_Post $post Post.
 * @return int
 */
function yt_reading_minutes( $post ) {
	return max( 1, (int) round( str_word_count( wp_strip_all_tags( $post->post_content ) ) / 200 ) );
}

/**
 * Shop staff can open a customer's order-received page from the admin (with its key) to see what the customer sees.
 * WooCommerce otherwise asks a logged-in admin to verify the guest's email first.
 */
add_filter(
	'woocommerce_order_email_verification_required',
	function ( $required ) {
		return current_user_can( 'edit_shop_orders' ) ? false : $required;
	}
);
add_filter(
	'woocommerce_order_received_verify_known_shoppers',
	function ( $verify ) {
		return current_user_can( 'edit_shop_orders' ) ? false : $verify;
	}
);

/**
 * Slip box: point to LINE as the second route, and state the checking hours.
 */
add_filter(
	'yt_slip_upload_alt_text',
	function ( $text, $order ) {
		return sprintf(
			/* translators: 1: LINE account, 2: order number */
			esc_html__( 'Or send it on LINE %1$s with your order number, #%2$s.', 'yummy-treats' ),
			'<a href="https://line.me/R/ti/p/[@yummytreats]" rel="noopener">[@yummytreats]</a>',
			esc_html( $order->get_order_number() )
		);
	},
	10,
	2
);

/**
 * Once a slip has arrived, the customer no longer needs the transfer instructions and bank details:
 * drop WooCommerce's bank-transfer block from the thank-you page for that order (the slip box stays).
 *
 * @param int $order_id Order ID.
 */
function yt_hide_bank_details_after_slip( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order || 'transfer-waiting' === yt_order_state( $order ) ) {
		return;
	}
	$gateways = WC()->payment_gateways() ? WC()->payment_gateways()->payment_gateways() : array();
	if ( isset( $gateways['bacs'] ) ) {
		remove_action( 'woocommerce_thankyou_bacs', array( $gateways['bacs'], 'thankyou_page' ) );
	}
}
add_action( 'woocommerce_thankyou_bacs', 'yt_hide_bank_details_after_slip', 1 );
