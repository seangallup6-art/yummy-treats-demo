<?php
/**
 * Styles and scripts.
 *
 * @package YummyTreats
 */

defined( 'ABSPATH' ) || exit;

/**
 * Version string from the file's mtime, so the browser cache clears on every change during development.
 *
 * @param string $rel Path relative to the theme root.
 * @return string
 */
function yt_asset_version( $rel ) {
	$path = YT_DIR . '/' . ltrim( $rel, '/' );
	return file_exists( $path ) ? (string) filemtime( $path ) : YT_VERSION;
}

/**
 * Front-end assets. Two small vanilla scripts, deferred; one stylesheet of components.
 */
function yt_enqueue_assets() {
	wp_enqueue_style( 'yummy-treats', get_stylesheet_uri(), array(), yt_asset_version( 'style.css' ) );
	wp_enqueue_style( 'yummy-treats-components', YT_URI . '/assets/css/theme.css', array( 'yummy-treats' ), yt_asset_version( 'assets/css/theme.css' ) );

	wp_enqueue_script( 'yummy-treats-marquee', YT_URI . '/assets/js/marquee.js', array(), yt_asset_version( 'assets/js/marquee.js' ), array( 'strategy' => 'defer' ) );
	wp_enqueue_script( 'yummy-treats-motion', YT_URI . '/assets/js/motion.js', array(), yt_asset_version( 'assets/js/motion.js' ), array( 'strategy' => 'defer' ) );
	// Deferred, so it runs after every footer script: on the cart page WooCommerce's cart data store already exists.
	// (Do not list WooCommerce block scripts as dependencies here: that pulls them into <head> before their settings.)
	wp_enqueue_script( 'yummy-treats-shop', YT_URI . '/assets/js/shop.js', array(), yt_asset_version( 'assets/js/shop.js' ), array( 'strategy' => 'defer' ) );

	wp_localize_script(
		'yummy-treats-marquee',
		'ytI18n',
		array(
			'pause' => __( 'Pause', 'yummy-treats' ),
			'play'  => __( 'Play', 'yummy-treats' ),
		)
	);
	wp_localize_script(
		'yummy-treats-shop',
		'ytShop',
		array(
			'added'       => __( 'Added!', 'yummy-treats' ),
			'freeReached' => __( 'You have free Bangkok delivery!', 'yummy-treats' ),
			/* translators: %s: amount still needed, e.g. ฿250 */
			'freeLeft'    => __( 'Add %s more for free Bangkok delivery', 'yummy-treats' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'yt_enqueue_assets' );

/**
 * Block editor: the same component styles, so patterns look right while editing.
 */
function yt_editor_assets() {
	wp_enqueue_style( 'yummy-treats-components-editor', YT_URI . '/assets/css/theme.css', array(), yt_asset_version( 'assets/css/theme.css' ) );
}
add_action( 'enqueue_block_editor_assets', 'yt_editor_assets' );
