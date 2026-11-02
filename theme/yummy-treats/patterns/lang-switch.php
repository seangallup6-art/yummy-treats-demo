<?php
/**
 * Title: Language switch (ไทย / EN)
 * Slug: yummy-treats/lang-switch
 * Categories: yummy-treats
 * Inserter: no
 * Description: Two plain links, Thai first. Reads the current language from TranslatePress when it is active; without it the links still work as URLs.
 *
 * @package YummyTreats
 */

$yt_current = defined( 'TRP_LANGUAGE' ) ? TRP_LANGUAGE : '';
if ( ! $yt_current ) {
	$yt_path    = (string) wp_parse_url( home_url( add_query_arg( array() ) ), PHP_URL_PATH );
	$yt_current = str_starts_with( $yt_path, '/th' ) ? 'th' : 'en_US';
}
$yt_is_th = 'th' === $yt_current;
$yt_url_th = home_url( '/th/' );
$yt_url_en = home_url( '/' );
if ( class_exists( 'TRP_Translate_Press' ) ) {
	// Same page in the other language, with TranslatePress's own URL rules (slugs, trailing /th/).
	$yt_conv   = TRP_Translate_Press::get_trp_instance()->get_component( 'url_converter' );
	$yt_url_th = $yt_conv->get_url_for_language( 'th', null, '' );
	$yt_url_en = $yt_conv->get_url_for_language( 'en_US', null, '' );
}
?>
<!-- wp:group {"className":"yt-lang-switch","layout":{"type":"flex","flexWrap":"nowrap"},"style":{"spacing":{"blockGap":"8px"}}} -->
<div class="wp-block-group yt-lang-switch">
	<!-- wp:paragraph {"fontSize":"small"} -->
	<p class="has-small-font-size" data-no-translation data-no-dynamic-translation><a href="<?php echo esc_url( $yt_url_th ); ?>" lang="th" hreflang="th"<?php echo $yt_is_th ? ' class="is-current" aria-current="true"' : ''; ?>>ไทย</a> / <a href="<?php echo esc_url( $yt_url_en ); ?>" lang="en" hreflang="en"<?php echo $yt_is_th ? '' : ' class="is-current" aria-current="true"'; ?>>EN</a></p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
