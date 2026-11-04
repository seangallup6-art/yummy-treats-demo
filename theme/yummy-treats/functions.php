<?php
/**
 * Yummy Treats! theme bootstrap.
 *
 * Keep this file tiny: each concern lives in inc/.
 *
 * @package YummyTreats
 */

defined( 'ABSPATH' ) || exit;

define( 'YT_VERSION', '0.1.0' );
define( 'YT_DIR', get_template_directory() );
define( 'YT_URI', get_template_directory_uri() );

require YT_DIR . '/inc/setup.php';
require YT_DIR . '/inc/assets.php';
require YT_DIR . '/inc/patterns.php';
require YT_DIR . '/inc/stockists.php';
require YT_DIR . '/inc/debug.php';
require YT_DIR . '/inc/playground.php';

if ( class_exists( 'WooCommerce' ) ) {
	require YT_DIR . '/inc/woocommerce.php';
	require YT_DIR . '/inc/product-page.php';
	require YT_DIR . '/inc/cart-feedback.php';
	require YT_DIR . '/inc/checkout.php';
	require YT_DIR . '/inc/account.php';
	require YT_DIR . '/inc/orders.php';
	require YT_DIR . '/inc/back-in-stock.php';
}
