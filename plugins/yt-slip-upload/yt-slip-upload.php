<?php
/**
 * Plugin Name:       Bank Slip Upload for WooCommerce
 * Description:       Customers who pay by bank transfer upload their payment slip on the order page; shop staff see it on the order and mark it paid in one click. Built for Thai shops, where "transfer and send the slip" is the normal way to pay.
 * Version:           0.1.0
 * Requires at least: 6.6
 * Requires PHP:      8.1
 * Requires Plugins:  woocommerce
 * Author:            [Your name]
 * License:           GPL-2.0-or-later
 * Text Domain:       yt-slip-upload
 * Domain Path:       /languages
 * WC requires at least: 9.0
 *
 * @package YTSlipUpload
 */

defined( 'ABSPATH' ) || exit;

define( 'YT_SLIP_UPLOAD_VERSION', '0.1.0' );
define( 'YT_SLIP_UPLOAD_FILE', __FILE__ );
define( 'YT_SLIP_UPLOAD_DIR', __DIR__ );

// Works with WooCommerce's order tables (HPOS) and the block cart/checkout.
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);

add_action(
	'plugins_loaded',
	static function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		require_once __DIR__ . '/includes/class-yt-slip-upload.php';
		YT_Slip_Upload::init();
	}
);

/**
 * Public API: the slip stored on an order, or null.
 *
 * @param WC_Order $order Order.
 * @return array{file:string,type:string,size:int,uploaded:int,name:string}|null
 */
function yt_slip_upload_get( $order ) {
	return class_exists( 'YT_Slip_Upload' ) ? YT_Slip_Upload::get_slip( $order ) : null;
}

/**
 * Public API: attach a file already on disk as the order's slip (seeding, imports, tests).
 *
 * @param WC_Order $order Order.
 * @param string   $path  Absolute path to a JPG, PNG, WebP or PDF.
 * @return bool
 */
function yt_slip_upload_attach( $order, $path ) {
	return class_exists( 'YT_Slip_Upload' ) ? YT_Slip_Upload::attach_file( $order, $path ) : false;
}
