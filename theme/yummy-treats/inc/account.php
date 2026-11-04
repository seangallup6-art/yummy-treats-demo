<?php
/**
 * My account (board M3, option A): a black "signed in as" card over a column of pill links, a dashboard that leads
 * with the latest order and what to do next, then tinted tiles (address, details, order again); orders with a
 * plain-words state pill; and, logged out, a "no account needed" card beside the log-in form.
 *
 * Everything hooks into WooCommerce's own account pages (no template copies), so WooCommerce updates keep working.
 * The look is in assets/css/theme.css, section 24.
 *
 * @package YummyTreats
 */

defined( 'ABSPATH' ) || exit;

/**
 * The shop sells nothing to download, so the Downloads page goes from the menu.
 *
 * @param array $items Menu items.
 * @return array
 */
function yt_account_menu( $items ) {
	unset( $items['downloads'] );
	return $items;
}
add_filter( 'woocommerce_account_menu_items', 'yt_account_menu' );

/**
 * An order's state in plain words, with the tint for its pill. Uses the same states as the order-received page.
 *
 * @param WC_Order $order Order.
 * @return array{0:string,1:string} Label and tint slug.
 */
function yt_order_state_label( $order ) {
	switch ( yt_order_state( $order ) ) {
		case 'transfer-waiting':
			return array( __( 'Waiting for your slip', 'yummy-treats' ), 'base' );
		case 'transfer-checking':
			return array( __( 'Slip received, checking', 'yummy-treats' ), 'tint-raspberry' );
		case 'promptpay-waiting':
			return array( __( 'Waiting for payment', 'yummy-treats' ), 'base' );
		case 'cod':
			return array( __( 'Packing, pay on delivery', 'yummy-treats' ), 'tint-blue' );
		case 'shipped':
			return array( __( 'On its way', 'yummy-treats' ), 'tint-teal' );
		case 'failed':
			return array( __( 'Payment failed', 'yummy-treats' ), 'tint-raspberry' );
		case 'cancelled':
			return array( __( 'Cancelled', 'yummy-treats' ), 'line' );
	}
	return array( __( 'Paid, packing', 'yummy-treats' ), 'tint-green' );
}

/**
 * The state pill.
 *
 * @param WC_Order $order Order.
 * @return string
 */
function yt_order_state_pill( $order ) {
	list( $label, $tint ) = yt_order_state_label( $order );
	return '<span class="yt-state has-' . esc_attr( $tint ) . '-background-color">' . esc_html( $label ) . '</span>';
}

/**
 * Orders list: the state pill in place of WooCommerce's status word.
 *
 * @param WC_Order $order Order.
 */
function yt_account_orders_state( $order ) {
	echo yt_order_state_pill( $order ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in yt_order_state_pill().
}
add_action( 'woocommerce_my_account_my_orders_column_order-status', 'yt_account_orders_state' );

/**
 * "Order again" for orders still on their way too, not only delivered ones (WooCommerce's default), so a shopper can
 * repeat last week's order straight away. The button on the order page and the dashboard tile both use it.
 *
 * @param string[] $statuses Statuses that allow ordering again.
 * @return string[]
 */
function yt_order_again_statuses( $statuses ) {
	return array_unique( array_merge( (array) $statuses, array( 'completed', 'processing', 'on-hold' ) ) );
}
add_filter( 'woocommerce_valid_order_statuses_for_order_again', 'yt_order_again_statuses' );

/**
 * Black "signed in as" card above the menu.
 */
function yt_account_hello() {
	$user = wp_get_current_user();
	if ( ! $user->exists() ) {
		return;
	}
	$name = trim( $user->first_name . ' ' . $user->last_name );
	echo '<div class="yt-acct-hi"><p class="yt-acct-hi__label">' . esc_html__( 'Signed in as', 'yummy-treats' ) . '</p>'
		. '<p class="yt-acct-hi__name">' . esc_html( '' !== $name ? $name : $user->display_name ) . '</p>'
		. '<p class="yt-acct-hi__help">' . sprintf(
			/* translators: %s: LINE account link */
			esc_html__( 'Questions? LINE %s', 'yummy-treats' ),
			'<a href="https://line.me/R/ti/p/[@yummytreats]" rel="noopener">[@yummytreats]</a>'
		) . '</p></div>';
}
add_action( 'woocommerce_before_account_navigation', 'yt_account_hello' );

/**
 * Dashboard: the latest order and what to do next, then three tiles. WooCommerce's own two lines of greeting are
 * hidden by the stylesheet once this is on the page (the name is in the black card, Log out in the menu).
 */
function yt_account_dashboard() {
	$orders = wc_get_orders(
		array(
			'customer_id' => get_current_user_id(),
			'limit'       => 1,
			'orderby'     => 'date',
			'order'       => 'DESC',
		)
	);
	$order  = $orders ? $orders[0] : null;
	$shop   = wc_get_page_permalink( 'shop' );

	echo '<div class="yt-acct-dash">';

	// 1. Latest order.
	echo '<section class="yt-acct-card is-order"><h2 class="yt-acct-card__title"><span aria-hidden="true">1</span>' . esc_html__( 'Your latest order', 'yummy-treats' ) . '</h2>';
	if ( $order ) {
		$items = $order->get_item_count();
		$slip  = 'transfer-waiting' === yt_order_state( $order );
		echo '<div class="yt-acct-order"><div><p class="yt-acct-order__head"><span class="yt-acct-order__no">#' . esc_html( $order->get_order_number() ) . '</span>' . yt_order_state_pill( $order ) . '</p>' // phpcs:ignore WordPress.Security.EscapeOutput
			. '<p class="yt-acct-order__meta">' . esc_html(
				implode(
					' · ',
					array_filter(
						array(
							$order->get_date_created() ? wc_format_datetime( $order->get_date_created() ) : '',
							/* translators: %d: number of items */
							sprintf( _n( '%d item', '%d items', $items, 'yummy-treats' ), $items ),
							wp_strip_all_tags( $order->get_formatted_order_total() ),
							yt_translate_stored_label( $order->get_payment_method_title() ),
						)
					)
				)
			) . '</p></div>'
			. '<p class="yt-acct-actions"><a class="yt-btn" href="' . esc_url( $order->get_view_order_url() ) . '">' . esc_html__( 'View order', 'yummy-treats' ) . '</a>'
			. ( $slip ? '<a class="yt-btn is-yellow" href="' . esc_url( $order->get_view_order_url() . '#yt-slip' ) . '">' . esc_html__( 'Send the slip', 'yummy-treats' ) . '</a>' : '' )
			. '</p></div>';
	} else {
		echo '<p class="yt-acct-empty">' . esc_html__( 'No orders yet. When you order while signed in, it shows up here with its slip and delivery status.', 'yummy-treats' ) . '</p>'
			. '<p class="yt-acct-actions"><a class="yt-btn is-yellow" href="' . esc_url( $shop ) . '">' . esc_html__( 'Go shopping', 'yummy-treats' ) . '</a></p>';
	}
	echo '</section>';

	echo '<div class="yt-acct-tiles">';

	// 2. Address.
	$address = wc_get_account_formatted_address( 'shipping' );
	if ( ! $address ) {
		$address = wc_get_account_formatted_address( 'billing' );
	}
	echo '<section class="yt-acct-card is-address"><h2 class="yt-acct-card__title"><span aria-hidden="true">2</span>' . esc_html__( 'Address', 'yummy-treats' ) . '</h2>'
		. '<p class="yt-acct-label">' . esc_html__( 'Delivers to', 'yummy-treats' ) . '</p>'
		. '<p>' . ( $address ? wp_kses_post( $address ) : esc_html__( 'No address saved yet.', 'yummy-treats' ) ) . '</p>'
		. '<p class="yt-acct-actions"><a class="yt-btn" href="' . esc_url( wc_get_endpoint_url( 'edit-address' ) ) . '">' . esc_html__( 'Edit', 'yummy-treats' ) . '</a></p></section>';

	// 3. Details.
	$user = wp_get_current_user();
	echo '<section class="yt-acct-card is-details"><h2 class="yt-acct-card__title"><span aria-hidden="true">3</span>' . esc_html__( 'Your details', 'yummy-treats' ) . '</h2>'
		. '<p class="yt-acct-label">' . esc_html__( 'Email', 'yummy-treats' ) . '</p><p class="yt-acct-email">' . esc_html( $user->user_email ) . '</p>'
		. '<p class="yt-acct-label">' . esc_html__( 'Password', 'yummy-treats' ) . '</p><p>' . esc_html__( 'Change it any time.', 'yummy-treats' ) . '</p>'
		. '<p class="yt-acct-actions"><a class="yt-btn" href="' . esc_url( wc_get_endpoint_url( 'edit-account' ) ) . '">' . esc_html__( 'Change', 'yummy-treats' ) . '</a></p></section>';

	// 4. Order again.
	echo '<section class="yt-acct-card is-again"><h2 class="yt-acct-card__title"><span aria-hidden="true">4</span>' . esc_html__( 'Order again', 'yummy-treats' ) . '</h2>';
	$valid = $order && in_array( $order->get_status(), (array) apply_filters( 'woocommerce_valid_order_statuses_for_order_again', array( 'completed' ) ), true );
	if ( $valid && $order->get_items() ) {
		echo '<ul class="yt-acct-items">';
		foreach ( array_slice( $order->get_items(), 0, 3 ) as $item ) {
			echo '<li>' . esc_html( $item->get_name() ) . ' <span>× ' . esc_html( $item->get_quantity() ) . '</span></li>';
		}
		echo '</ul><p class="yt-acct-actions"><a class="yt-btn is-dark" href="' . esc_url( wp_nonce_url( add_query_arg( 'order_again', $order->get_id(), wc_get_cart_url() ), 'woocommerce-order_again' ) ) . '">' . esc_html__( 'Put these in the cart', 'yummy-treats' ) . '</a></p>';
	} else {
		echo '<p>' . esc_html__( 'Your last order will be here, ready to repeat in one tap.', 'yummy-treats' ) . '</p>'
			. '<p class="yt-acct-actions"><a class="yt-btn is-dark" href="' . esc_url( $shop ) . '">' . esc_html__( 'See the shop', 'yummy-treats' ) . '</a></p>';
	}
	echo '</section>';

	echo '</div></div>';
}
add_action( 'woocommerce_account_dashboard', 'yt_account_dashboard' );

/**
 * Logged out: beside the log-in form, a card saying an account is not needed to order.
 */
function yt_account_no_account_card() {
	if ( is_user_logged_in() || ! is_account_page() ) {
		return;
	}
	echo '<section class="yt-acct-new"><h2 class="yt-acct-card__title">' . esc_html__( 'New here?', 'yummy-treats' ) . '</h2>'
		. '<p class="yt-acct-new__big">' . esc_html__( 'No account needed to order', 'yummy-treats' ) . '</p>'
		. '<p>' . esc_html__( 'Check out as a guest. After your first order, one password on the confirmation page turns it into an account: order status, slips and your address in one place.', 'yummy-treats' ) . '</p>'
		. '<p class="yt-acct-actions"><a class="yt-btn is-yellow" href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '">' . esc_html__( 'Go shopping', 'yummy-treats' ) . '</a></p></section>';
}
add_action( 'woocommerce_after_customer_login_form', 'yt_account_no_account_card' );
