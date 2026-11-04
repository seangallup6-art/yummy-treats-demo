<?php
/**
 * Title: Shop grid with filter sidebar
 * Slug: yummy-treats/shop-grid
 * Categories: yummy-treats
 * Inserter: no
 * Description: The product list for the shop, category and search pages: a column of tinted filter tiles (category, price, availability) beside the product grid. Below 783 px WooCommerce folds the filters into a "Filter products" button and a panel, styled as a bottom sheet in theme.css (section 5).
 *
 * @package YummyTreats
 */
?>
<!-- wp:group {"align":"wide","className":"yt-shop","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide yt-shop">
	<!-- wp:group {"className":"yt-shop__side","layout":{"type":"default"}} -->
	<div class="wp-block-group yt-shop__side">
		<!-- wp:woocommerce/product-filters {"overlayMode":"mobile","className":"yt-filterbar"} -->
		<div class="wp-block-woocommerce-product-filters yt-filterbar">
			<!-- wp:heading {"className":"yt-filterbar__title"} --><h2 class="wp-block-heading yt-filterbar__title">Filters</h2><!-- /wp:heading -->
			<!-- wp:woocommerce/product-filter-active -->
			<div class="wp-block-woocommerce-product-filter-active">
				<!-- wp:woocommerce/product-filter-removable-chips -->
				<div class="wp-block-woocommerce-product-filter-removable-chips wc-block-product-filter-removable-chips"></div>
				<!-- /wp:woocommerce/product-filter-removable-chips -->
				<!-- wp:woocommerce/product-filter-clear-button -->
				<!-- wp:buttons {"layout":{"type":"flex"}} -->
				<div class="wp-block-buttons"><!-- wp:button {"className":"wc-block-product-filter-clear-button"} -->
				<div class="wp-block-button wc-block-product-filter-clear-button"><a class="wp-block-button__link wp-element-button">Clear all</a></div>
				<!-- /wp:button --></div>
				<!-- /wp:buttons -->
				<!-- /wp:woocommerce/product-filter-clear-button -->
			</div>
			<!-- /wp:woocommerce/product-filter-active -->
			<!-- wp:woocommerce/product-filter-taxonomy {"taxonomy":"product_cat","displayStyle":"woocommerce/product-filter-checkbox-list","showCounts":true,"sortOrder":"name-asc"} -->
			<div class="wp-block-woocommerce-product-filter-taxonomy">
				<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Category</h3><!-- /wp:heading -->
				<!-- wp:woocommerce/product-filter-checkbox-list -->
				<div class="wp-block-woocommerce-product-filter-checkbox-list wc-block-product-filter-checkbox-list"></div>
				<!-- /wp:woocommerce/product-filter-checkbox-list -->
			</div>
			<!-- /wp:woocommerce/product-filter-taxonomy -->
			<!-- wp:woocommerce/product-filter-price -->
			<div class="wp-block-woocommerce-product-filter-price">
				<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Price</h3><!-- /wp:heading -->
				<!-- wp:woocommerce/product-filter-price-slider {"inlineInput":false} /-->
			</div>
			<!-- /wp:woocommerce/product-filter-price -->
			<!-- wp:woocommerce/product-filter-status {"displayStyle":"woocommerce/product-filter-checkbox-list","showCounts":true} -->
			<div class="wp-block-woocommerce-product-filter-status">
				<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Availability</h3><!-- /wp:heading -->
				<!-- wp:woocommerce/product-filter-checkbox-list -->
				<div class="wp-block-woocommerce-product-filter-checkbox-list wc-block-product-filter-checkbox-list"></div>
				<!-- /wp:woocommerce/product-filter-checkbox-list -->
			</div>
			<!-- /wp:woocommerce/product-filter-status -->
		</div>
		<!-- /wp:woocommerce/product-filters -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"className":"yt-shop__main","layout":{"type":"default"}} -->
	<div class="wp-block-group yt-shop__main">
		<!-- wp:group {"className":"yt-shop__bar","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
		<div class="wp-block-group yt-shop__bar" style="margin-bottom:var(--wp--preset--spacing--30)">
			<!-- wp:woocommerce/product-results-count /-->
			<!-- wp:woocommerce/catalog-sorting /-->
		</div>
		<!-- /wp:group -->

		<!-- wp:woocommerce/product-collection {"queryId":0,"query":{"woocommerceAttributes":[],"woocommerceStockStatus":["instock","outofstock","onbackorder"],"taxQuery":{},"isProductCollectionBlock":true,"perPage":12,"pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"title","author":"","search":"","exclude":[],"sticky":"","inherit":true},"tagName":"div","dimensions":{"widthType":"fill","fixedWidth":""},"displayLayout":{"type":"flex","columns":3,"shrinkColumns":true},"convertedFromProducts":false,"queryContextIncludes":["collection"],"className":"yt-cards"} -->
		<div class="wp-block-woocommerce-product-collection yt-cards">
			<!-- wp:woocommerce/product-template -->
			<!-- wp:woocommerce/product-image {"showSaleBadge":false,"imageSizing":"thumbnail","isDescendentOfQueryLoop":true} -->
				<!-- wp:woocommerce/product-sale-badge {"isDescendentOfQueryLoop":true,"align":"left"} /-->
			<!-- /wp:woocommerce/product-image -->
			<!-- wp:post-terms {"term":"product_cat"} /-->
			<!-- wp:post-title {"level":2,"isLink":true,"__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->
			<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true} /-->
			<!-- wp:woocommerce/product-button {"isDescendentOfQueryLoop":true} /-->
			<!-- /wp:woocommerce/product-template -->

			<!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}}} -->
			<!-- wp:query-pagination-previous /-->
			<!-- wp:query-pagination-numbers /-->
			<!-- wp:query-pagination-next /-->
			<!-- /wp:query-pagination -->

			<!-- wp:woocommerce/product-collection-no-results -->
			<!-- wp:group {"className":"yt-empty is-style-card-flat","layout":{"type":"constrained"}} -->
			<div class="wp-block-group yt-empty is-style-card-flat">
				<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">No treats match that.</h2><!-- /wp:heading -->
				<!-- wp:paragraph --><p>Try another word or fewer filters, or browse everything we make.</p><!-- /wp:paragraph -->
				<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
				<div class="wp-block-buttons">
					<!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/shop/">See all 24 products</a></div><!-- /wp:button -->
					<!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="https://line.me/R/ti/p/[@yummytreats]" rel="noopener">Ask us on LINE</a></div><!-- /wp:button -->
				</div>
				<!-- /wp:buttons -->
			</div>
			<!-- /wp:group -->
			<!-- /wp:woocommerce/product-collection-no-results -->
		</div>
		<!-- /wp:woocommerce/product-collection -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
