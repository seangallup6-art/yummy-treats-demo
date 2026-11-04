<?php
/**
 * After "Add to cart" (board P6, option C): a floating cart pill, bottom right.
 *
 * The pill is a second view of WooCommerce's mini-cart: it uses the mini-cart's own Interactivity API state
 * (item count, subtotal, whether there is anything in the cart) and its own openDrawer action, so it always
 * agrees with the header's cart button and opens the same drawer. The drawer no longer opens by itself
 * (parts/header.html, addToCartBehaviour "none"); the toast in assets/js/shop.js says what was added.
 *
 * @package YummyTreats
 */

defined( 'ABSPATH' ) || exit;

/**
 * Note that the page has a mini-cart (the pill needs its store and drawer).
 *
 * @param string $html Rendered mini-cart block.
 * @return string
 */
function yt_note_mini_cart( $html ) {
	$GLOBALS['yt_has_mini_cart'] = true;
	return $html;
}
add_filter( 'render_block_woocommerce/mini-cart', 'yt_note_mini_cart' );

/**
 * Print the pill. Not on the cart and checkout pages, which are the cart already. The directives are processed
 * on the server too, so the first paint already shows the right count, or no pill for an empty cart.
 */
function yt_print_cart_pill() {
	if ( empty( $GLOBALS['yt_has_mini_cart'] ) || is_cart() || is_checkout() || ! function_exists( 'wp_interactivity_process_directives' ) ) {
		return;
	}
	$html = '<div class="yt-cartpill" data-wp-interactive="woocommerce/mini-cart" data-wp-context=\'{"productCountVisibility":"greater_than_zero"}\'>'
		. '<button type="button" class="yt-cartpill__btn" hidden data-wp-bind--hidden="!state.badgeIsVisible" data-wp-on--click="actions.openDrawer">'
		. '<span class="yt-cartpill__count" data-wp-text="state.totalItemsInCart">0</span>'
		. '<span class="screen-reader-text">' . esc_html__( 'items in the cart,', 'yummy-treats' ) . ' </span>'
		. '<span class="yt-cartpill__text"><span class="yt-cartpill__total" data-wp-text="state.formattedSubtotal"></span>'
		. '<span class="yt-cartpill__open">' . esc_html__( 'Open cart', 'yummy-treats' ) . '</span></span>'
		. '</button></div>';
	echo wp_interactivity_process_directives( $html ); // phpcs:ignore WordPress.Security.EscapeOutput -- built above from escaped parts.
}
add_action( 'wp_footer', 'yt_print_cart_pill', 5 );

/**
 * The drawer's close button: WooCommerce prints aria-label="Close" as fixed English; give it the theme's word.
 *
 * @param string $html Rendered mini-cart contents.
 * @return string
 */
function yt_mini_cart_close_label( $html ) {
	return str_replace( 'aria-label="Close"', 'aria-label="' . esc_attr__( 'Close', 'yummy-treats' ) . '"', $html );
}
add_filter( 'render_block_woocommerce/mini-cart-contents', 'yt_mini_cart_close_label' );
