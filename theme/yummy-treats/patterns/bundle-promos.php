<?php
/**
 * Title: Bundle promos (shop page only)
 * Slug: yummy-treats/bundle-promos
 * Categories: yummy-treats
 * Inserter: no
 * Description: Bundle cards with a "bundle" sticker at the top of the main shop page. Renders nothing on category and search pages.
 *
 * @package YummyTreats
 */

if ( ! function_exists( 'is_shop' ) || ! is_shop() ) {
	return;
}
?>
<!-- wp:group {"align":"wide","className":"yt-bundles","style":{"spacing":{"margin":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide yt-bundles" style="margin-top:var(--wp--preset--spacing--40);margin-bottom:var(--wp--preset--spacing--50)">
	<!-- wp:group {"align":"wide","layout":{"type":"flex","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:heading {"level":2,"style":{"typography":{"fontSize":"28px"}}} -->
		<h2 class="wp-block-heading" style="font-size:28px"><?php esc_html_e( 'start with a bundle', 'yummy-treats' ); ?></h2>
		<!-- /wp:heading -->
		<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"14px"}}} -->
		<p style="font-size:14px;font-weight:700"><?php esc_html_e( 'a little cheaper than buying the parts, and the quickest way to find a favourite', 'yummy-treats' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
	<!-- wp:woocommerce/product-collection {"align":"wide","queryId":4,"query":{"perPage":4,"pages":1,"offset":0,"postType":"product","order":"desc","orderBy":"popularity","search":"","exclude":[],"inherit":false,"taxQuery":{"product_cat":[<?php echo (int) ( get_term_by( 'slug', 'bundles', 'product_cat' )->term_id ?? 0 ); ?>]},"isProductCollectionBlock":true,"featured":false,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":[],"filterable":false},"tagName":"div","displayLayout":{"type":"flex","columns":4,"shrinkColumns":true},"dimensions":{"widthType":"fill"},"collection":"woocommerce/product-collection/product-catalog","hideControls":["inherit","filterable"],"queryContextIncludes":["collection"],"className":"yt-cards yt-cards--promo"} -->
	<div class="wp-block-woocommerce-product-collection alignwide yt-cards yt-cards--promo">
		<!-- wp:woocommerce/product-template -->
		<!-- wp:woocommerce/product-image {"showSaleBadge":false,"imageSizing":"thumbnail","isDescendentOfQueryLoop":true} -->
			<!-- wp:woocommerce/product-sale-badge {"isDescendentOfQueryLoop":true,"align":"left"} /-->
		<!-- /wp:woocommerce/product-image -->
		<!-- wp:post-title {"level":3,"isLink":true,"__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->
		<!-- wp:post-excerpt {"__woocommerceNamespace":"woocommerce/product-collection/product-summary","excerptLength":16,"showMoreOnNewLine":false,"style":{"typography":{"fontSize":"13px"}}} /-->
		<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true} /-->
		<!-- wp:woocommerce/product-button {"isDescendentOfQueryLoop":true} /-->
		<!-- /wp:woocommerce/product-template -->
	</div>
	<!-- /wp:woocommerce/product-collection -->
</div>
<!-- /wp:group -->
