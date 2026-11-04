<?php
/**
 * Checkout (board P7, option A): the block checkout keeps working as WooCommerce built it; this file only
 * renames its steps to the board's plainer headings and shows the total on the Place order button. The look
 * (tinted step cards with number stickers, black order summary) is in assets/css/theme.css, section 20.
 *
 * @package YummyTreats
 */

defined( 'ABSPATH' ) || exit;

/**
 * Step headings and the price on the button. The checkout's inner blocks read their settings from data-*
 * attributes on their placeholders, so the titles are set there; "is-style-with-price" is WooCommerce's own
 * style for the actions block that adds " · ฿790" to the button.
 *
 * @param string $html Rendered Checkout block.
 * @return string
 */
function yt_checkout_steps( $html ) {
	$titles = array(
		'woocommerce/checkout-contact-information-block' => __( 'Who’s ordering', 'yummy-treats' ),
		'woocommerce/checkout-shipping-address-block'    => __( 'Where it goes', 'yummy-treats' ),
		'woocommerce/checkout-shipping-methods-block'    => __( 'Delivery', 'yummy-treats' ),
		'woocommerce/checkout-payment-block'             => __( 'How you pay', 'yummy-treats' ),
	);
	$tags = new WP_HTML_Tag_Processor( $html );
	while ( $tags->next_tag( 'div' ) ) {
		$name = $tags->get_attribute( 'data-block-name' );
		if ( isset( $titles[ $name ] ) && null === $tags->get_attribute( 'data-title' ) ) {
			$tags->set_attribute( 'data-title', $titles[ $name ] );
		} elseif ( 'woocommerce/checkout-actions-block' === $name ) {
			$tags->add_class( 'is-style-with-price' );
		}
	}
	return $tags->get_updated_html();
}
add_filter( 'render_block_woocommerce/checkout', 'yt_checkout_steps' );
