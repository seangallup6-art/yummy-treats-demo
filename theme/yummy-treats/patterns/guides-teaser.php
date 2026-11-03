<?php
/**
 * Title: Guides teaser
 * Slug: yummy-treats/guides-teaser
 * Categories: yummy-treats
 * Description: The three latest care guides as cards.
 *
 * @package YummyTreats
 */

?>
<!-- wp:group {"align":"wide","className":"yt-reveal","style":{"spacing":{"padding":{"bottom":"var:preset|spacing|70"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide yt-reveal" style="padding-bottom:var(--wp--preset--spacing--70)">
	<!-- wp:group {"align":"wide","layout":{"type":"flex","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:heading {"level":2} -->
		<h2 class="wp-block-heading"><?php esc_html_e( 'Feeding guides', 'yummy-treats' ); ?></h2>
		<!-- /wp:heading -->
		<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700"}}} -->
		<p style="font-weight:700"><a href="/guides/"><?php esc_html_e( 'All guides →', 'yummy-treats' ); ?></a></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
	<!-- wp:query {"queryId":2,"query":{"perPage":3,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"align":"wide","className":"yt-guides","layout":{"type":"default"}} -->
	<div class="wp-block-query alignwide yt-guides">
		<!-- wp:post-template {"layout":{"type":"grid","columnCount":3}} -->
			<!-- wp:post-terms {"term":"category","className":"yt-eyebrow yt-eyebrow--pill"} /-->
			<!-- wp:post-title {"level":3,"isLink":true} /-->
			<!-- wp:post-excerpt {"excerptLength":40,"moreText":"Read →","style":{"typography":{"fontSize":"15px"}}} /-->
		<!-- /wp:post-template -->
	</div>
	<!-- /wp:query -->
</div>
<!-- /wp:group -->
