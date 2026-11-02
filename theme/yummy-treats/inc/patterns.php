<?php
/**
 * Block pattern categories. The patterns themselves are the PHP files in /patterns, which WordPress
 * registers from their file headers (Title, Slug, Categories).
 *
 * @package YummyTreats
 */

defined( 'ABSPATH' ) || exit;

/**
 * One category for everything the theme ships, plus the WooCommerce one it already knows.
 */
function yt_pattern_categories() {
	register_block_pattern_category(
		'yummy-treats',
		array(
			'label'       => __( 'Yummy Treats!', 'yummy-treats' ),
			'description' => __( 'Sections from the Yummy Treats! designs: hero, pet split, featured products, read the bag, guides, stockists, LINE band.', 'yummy-treats' ),
		)
	);
}
add_action( 'init', 'yt_pattern_categories', 9 );

/**
 * Hide the core and remote patterns in the inserter so the client only sees ours and WooCommerce's.
 * Keeps the editor honest to the design system.
 */
add_filter( 'should_load_remote_block_patterns', '__return_false' );
function yt_remove_core_patterns() {
	remove_theme_support( 'core-block-patterns' );
}
add_action( 'after_setup_theme', 'yt_remove_core_patterns', 20 );
