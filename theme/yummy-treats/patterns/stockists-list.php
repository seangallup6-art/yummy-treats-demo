<?php
/**
 * Title: Stockists list
 * Slug: yummy-treats/stockists-list
 * Categories: yummy-treats
 * Inserter: no
 * Description: The grouped stockist list with type chips (same output as the [yt_stockists] shortcode, without the shortcode block's auto-paragraphs).
 *
 * @package YummyTreats
 */

if ( ! function_exists( 'yt_stockists_shortcode' ) ) {
	return;
}
?>
<!-- wp:html -->
<?php echo yt_stockists_shortcode(); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped where built. ?>
<!-- /wp:html -->
