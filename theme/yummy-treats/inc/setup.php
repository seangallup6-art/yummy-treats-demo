<?php
/**
 * Theme supports and basics.
 *
 * @package YummyTreats
 */

defined( 'ABSPATH' ) || exit;

/**
 * Theme supports. Block themes get most things for free; WooCommerce still wants to be told.
 */
function yt_setup() {
	load_theme_textdomain( 'yummy-treats', YT_DIR . '/languages' );
	add_action( 'init', 'yt_load_translation_overrides', 20 );

	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/theme.css' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );

	// WooCommerce: product images are square packshots; the gallery blocks handle zoom and lightbox.
	add_theme_support(
		'woocommerce',
		array(
			'thumbnail_image_width' => 600,
			'single_image_width'    => 1200,
		)
	);
}
add_action( 'after_setup_theme', 'yt_setup' );

/**
 * A `js` class on <html> as early as possible. Anything hidden for a reveal animation is gated on it,
 * so nothing stays invisible without JavaScript.
 */
function yt_js_class() {
	echo "<script>document.documentElement.classList.add('js');</script>\n";
}
add_action( 'wp_head', 'yt_js_class', 0 );

/**
 * Body classes that the CSS keys off: the current language and whether backdrop shapes are on.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function yt_body_classes( $classes ) {
	$lang      = function_exists( 'trp_get_current_language' ) ? trp_get_current_language() : get_locale();
	$classes[] = 'yt-lang-' . sanitize_html_class( substr( (string) $lang, 0, 2 ) );
	$classes[] = 'yt-shapes-' . sanitize_html_class( get_theme_mod( 'yt_backdrop_shapes', 'off' ) );
	return $classes;
}
add_filter( 'body_class', 'yt_body_classes' );

/**
 * Register the two block styles the designs use on buttons: raspberry and blue fills.
 */
function yt_block_styles() {
	register_block_style(
		'core/button',
		array(
			'name'  => 'accent',
			'label' => _x( 'Raspberry', 'block style', 'yummy-treats' ),
		)
	);
	register_block_style(
		'core/button',
		array(
			'name'  => 'blue',
			'label' => _x( 'Blue', 'block style', 'yummy-treats' ),
		)
	);
	register_block_style(
		'core/button',
		array(
			'name'  => 'mustard',
			'label' => _x( 'Mustard', 'block style', 'yummy-treats' ),
		)
	);
	register_block_style(
		'core/group',
		array(
			'name'  => 'card',
			'label' => _x( 'Card (white, border, hard shadow)', 'block style', 'yummy-treats' ),
		)
	);
	register_block_style(
		'core/group',
		array(
			'name'  => 'card-flat',
			'label' => _x( 'Card, no shadow', 'block style', 'yummy-treats' ),
		)
	);
	register_block_style(
		'core/group',
		array(
			'name'  => 'band-ink',
			'label' => _x( 'Ink band', 'block style', 'yummy-treats' ),
		)
	);
}
add_action( 'init', 'yt_block_styles' );

/**
 * Colour the "!" in the site title (header and footer) so the name reads as the logo in the designs.
 *
 * @param string $content Rendered Site Title block.
 * @return string
 */
function yt_site_title_bang( $content ) {
	return preg_replace( '/!(<\/a>)/', '<span class="yt-bang">!</span>$1', $content, 1 );
}
add_filter( 'render_block_core/site-title', 'yt_site_title_bang' );

/**
 * Thai for strings the theme does not own: WooCommerce's (buttons, checkout, order confirmation) and WordPress's
 * (pagination, skip link, search title). Shipped as small .mo files in /languages and merged with any language pack
 * already installed; built by tools/make-mo.py from seed/translations-th.php.
 */
function yt_load_translation_overrides() {
	foreach ( array( 'woocommerce', 'default' ) as $domain ) {
		$file = YT_DIR . '/languages/' . $domain . '-' . determine_locale() . '.mo';
		if ( file_exists( $file ) ) {
			load_textdomain( $domain, $file );
			if ( 'default' === $domain && isset( $GLOBALS['wp_locale'] ) ) {
				// WordPress builds its month and weekday names once, early; rebuild them so dates pick up the Thai.
				$GLOBALS['wp_locale'] = new WP_Locale(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride -- same as switch_to_locale().
			}
		}
	}
}

/**
 * The same overrides for WooCommerce's JavaScript (the block cart and checkout render their labels in the browser,
 * where .mo files never reach). wp.i18n.setLocaleData merges, so a real language pack still wins where it has a string.
 */
function yt_script_translation_overrides() {
	$file = YT_DIR . '/languages/woocommerce-' . determine_locale() . '.json';
	if ( ! file_exists( $file ) ) {
		return;
	}
	$json = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- local theme file.
	if ( $json ) {
		wp_add_inline_script( 'wp-i18n', 'wp.i18n.setLocaleData(' . $json . ',"woocommerce");', 'after' );
	}
}
add_action( 'wp_enqueue_scripts', 'yt_script_translation_overrides', 20 );
