<?php
/**
 * Title: Stockists teaser
 * Slug: yummy-treats/stockists-teaser
 * Categories: yummy-treats
 * Description: "Also in a shop near you" — a mustard-on-white card with a button to the stockists page.
 *
 * @package YummyTreats
 */

?>
<!-- wp:group {"align":"wide","className":"yt-reveal","style":{"spacing":{"padding":{"bottom":"var:preset|spacing|70"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide yt-reveal" style="padding-bottom:var(--wp--preset--spacing--70)">
	<!-- wp:group {"align":"wide","className":"is-style-card","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|60","right":"var:preset|spacing|60"}}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
	<div class="wp-block-group alignwide is-style-card" style="padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--60)">
		<!-- wp:group {"layout":{"type":"constrained"}} -->
		<div class="wp-block-group">
			<!-- wp:heading {"level":2} -->
			<h2 class="wp-block-heading"><?php esc_html_e( 'Prefer to pick it up?', 'yummy-treats' ); ?></h2>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"500"}}} -->
			<p style="font-weight:500"><?php esc_html_e( 'Yummy Treats! is on the shelf at [number] pet shops and vet clinics in Bangkok and Nonthaburi. Same treats, no delivery wait.', 'yummy-treats' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
		<!-- wp:buttons -->
		<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-blue"} --><div class="wp-block-button is-style-blue"><a class="wp-block-button__link wp-element-button" href="/stockists/"><?php esc_html_e( 'Find a stockist near you', 'yummy-treats' ); ?></a></div><!-- /wp:button --></div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
