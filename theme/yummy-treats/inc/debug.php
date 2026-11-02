<?php
/**
 * Debug switch for the demo: add ?yt-debug=1 to any URL.
 * Prints the request's routing facts and, if anything tries to redirect, stops and shows the backtrace instead.
 * Read-only, admin-free, no secrets; harmless to leave in a demo theme.
 *
 * @package YummyTreats
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $_GET['yt-debug'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
	return;
}

/**
 * Intercept any redirect and show where it came from.
 */
add_filter(
	'wp_redirect',
	function ( $location, $status ) {
		header( 'Content-Type: text/plain; charset=utf-8' );
		echo "REDIRECT INTERCEPTED\nto: {$location}\nstatus: {$status}\n\n";
		echo yt_debug_facts();
		echo "\nbacktrace:\n" . implode( "\n", (array) wp_debug_backtrace_summary( null, 0, false ) ); // phpcs:ignore
		exit;
	},
	0,
	2
);

/**
 * If nothing redirected, print the facts at the top of the page.
 */
add_action(
	'template_redirect',
	function () {
		header( 'Content-Type: text/plain; charset=utf-8' );
		echo "NO REDIRECT (template_redirect reached)\n\n" . yt_debug_facts();
		exit;
	},
	PHP_INT_MAX
);

/**
 * Routing facts.
 *
 * @return string
 */
function yt_debug_facts() {
	global $wp, $wp_query, $wp_rewrite;
	$rules = get_option( 'rewrite_rules' );
	$lines = array(
		'wp version'          => get_bloginfo( 'version' ),
		'php'                 => PHP_VERSION,
		'home / siteurl'      => home_url() . ' / ' . site_url(),
		'request'             => $_SERVER['REQUEST_URI'] ?? '',
		'wp->request'         => $wp->request ?? '',
		'matched_rule'        => $wp->matched_rule ?? '',
		'matched_query'       => $wp->matched_query ?? '',
		'permalink_structure' => get_option( 'permalink_structure' ),
		'rewrite_rules count' => is_array( $rules ) ? count( $rules ) : gettype( $rules ),
		'post rule present'   => is_array( $rules ) && isset( $rules['([^/]+)(?:/([0-9]+))?/?$'] ) ? 'yes' : 'no',
		'is_404/single/page/home/front' => implode( '/', array( (int) is_404(), (int) is_single(), (int) is_page(), (int) is_home(), (int) is_front_page() ) ),
		'queried object'      => ( $o = get_queried_object() ) ? ( ( $o->post_type ?? $o->taxonomy ?? get_class( $o ) ) . ' #' . ( $o->ID ?? $o->term_id ?? '' ) ) : 'none',
		'show_on_front / page_on_front / page_for_posts' => get_option( 'show_on_front' ) . ' / ' . get_option( 'page_on_front' ) . ' / ' . get_option( 'page_for_posts' ),
		'posts (publish)'     => wp_count_posts( 'post' )->publish ?? '?',
		'locale'              => determine_locale(),
		'active plugins'      => implode( ', ', (array) get_option( 'active_plugins' ) ),
		'TRP_LANGUAGE'        => defined( 'TRP_LANGUAGE' ) ? TRP_LANGUAGE : '-',
	);
	$out = '';
	foreach ( $lines as $k => $v ) {
		$out .= str_pad( $k, 46 ) . $v . "\n";
	}
	return $out;
}
