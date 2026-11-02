<?php
/**
 * Title: Featured products
 * Slug: yummy-treats/featured-products
 * Categories: yummy-treats
 * Description: Heading row with a "See all" link, then eight best sellers as Yummy Treats! cards (card markup: yt_product_cards() in inc/woocommerce.php).
 *
 * @package YummyTreats
 */

?>
<!-- wp:group {"align":"wide","className":"yt-reveal","style":{"spacing":{"padding":{"bottom":"var:preset|spacing|70"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide yt-reveal" style="padding-bottom:var(--wp--preset--spacing--70)">
	<!-- wp:group {"align":"wide","layout":{"type":"flex","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:heading {"level":2} -->
		<h2 class="wp-block-heading"><?php esc_html_e( 'Bangkok favourites', 'yummy-treats' ); ?></h2>
		<!-- /wp:heading -->
		<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700"}}} -->
		<p style="font-weight:700"><a href="/shop/"><?php esc_html_e( 'See all 24 products →', 'yummy-treats' ); ?></a></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<?php echo function_exists( 'yt_product_cards' ) ? yt_product_cards() : ''; // phpcs:ignore WordPress.Security.EscapeOutput -- block markup. ?>
</div>
<!-- /wp:group -->
