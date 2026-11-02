<?php
/**
 * Title: Products for this guide
 * Slug: yummy-treats/guide-products
 * Categories: yummy-treats
 * Inserter: no
 * Description: "From our shelf" cards under a guide. Which products: the guide's custom field yt_guide_products, a comma-separated list of SKUs (e.g. YT-D-005, YT-D-006). Renders nothing when the field is empty.
 *
 * @package YummyTreats
 */

$yt_post = get_queried_object();
if ( ! $yt_post instanceof WP_Post || 'post' !== $yt_post->post_type || ! function_exists( 'yt_product_cards' ) ) {
	return;
}
$yt_ids = yt_product_ids_from_skus( get_post_meta( $yt_post->ID, 'yt_guide_products', true ) );
if ( ! $yt_ids ) {
	return;
}
?>
<!-- wp:group {"align":"wide","className":"yt-guide-products","style":{"spacing":{"margin":{"top":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide yt-guide-products" style="margin-top:var(--wp--preset--spacing--60)">
	<!-- wp:group {"align":"wide","layout":{"type":"flex","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:heading {"level":2} -->
		<h2 class="wp-block-heading"><?php esc_html_e( 'From our shelf', 'yummy-treats' ); ?></h2>
		<!-- /wp:heading -->
		<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700"}}} -->
		<p style="font-weight:700"><a href="/shop/"><?php esc_html_e( 'See all 24 products →', 'yummy-treats' ); ?></a></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
	<?php
	// phpcs:ignore WordPress.Security.EscapeOutput -- block markup built from integers and fixed strings.
	echo yt_product_cards(
		array(
			'collection' => 'woocommerce/product-collection/hand-picked',
			'columns'    => min( 4, count( $yt_ids ) ),
			'query'      => array(
				'perPage'                       => count( $yt_ids ),
				'orderBy'                       => 'post__in',
				'order'                         => 'asc',
				'woocommerceHandPickedProducts' => array_map( 'strval', $yt_ids ),
			),
		)
	);
	?>
</div>
<!-- /wp:group -->
