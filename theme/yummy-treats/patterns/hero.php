<?php
/**
 * Title: Home hero
 * Slug: yummy-treats/hero
 * Categories: yummy-treats
 * Description: Big display headline on the mustard ground, two buttons, one sticker, and a product photo placeholder.
 *
 * @package YummyTreats
 */

?>
<!-- wp:group {"align":"wide","className":"yt-hero yt-reveal","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide yt-hero yt-reveal" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:columns {"align":"wide","verticalAlignment":"center","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|60"}}}} -->
	<div class="wp-block-columns alignwide are-vertically-aligned-center">
		<!-- wp:column {"verticalAlignment":"center","width":"58%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:58%">
			<!-- wp:paragraph {"className":"yt-eyebrow"} -->
			<p class="yt-eyebrow"><?php esc_html_e( 'Small-batch treats and meals · Made in Thailand', 'yummy-treats' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:heading {"level":1} -->
			<h1 class="wp-block-heading"><?php esc_html_e( 'Good food for Bangkok dogs and cats.', 'yummy-treats' ); ?></h1>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"fontSize":"large","style":{"typography":{"fontWeight":"500"}}} -->
			<p class="has-large-font-size" style="font-weight:500"><?php esc_html_e( 'Real meat, no fillers, and sizes that make sense for a city apartment. Delivered across Bangkok, or pick it up at a pet shop near you.', 'yummy-treats' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:buttons {"style":{"spacing":{"blockGap":"12px"}}} -->
			<div class="wp-block-buttons">
				<!-- wp:button -->
				<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/shop/"><?php esc_html_e( 'Shop all treats', 'yummy-treats' ); ?></a></div>
				<!-- /wp:button -->
				<!-- wp:button {"className":"is-style-outline"} -->
				<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/stockists/"><?php esc_html_e( 'Find a stockist', 'yummy-treats' ); ?></a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center","width":"42%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:42%">
			<!-- wp:group {"className":"yt-card","style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"}},"color":{"background":"#FBDDF1"}},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group yt-card has-background" style="background-color:#FBDDF1;padding:0">
				<!-- wp:image {"sizeSlug":"large","className":"yt-hero__img"} -->
				<figure class="wp-block-image size-large yt-hero__img"><img src="<?php echo esc_url( YT_URI . '/assets/img/placeholder-hero.svg' ); ?>" alt="<?php esc_attr_e( '[Hero photo: a dog and a cat with a Yummy Treats! bag]', 'yummy-treats' ); ?>"/></figure>
				<!-- /wp:image -->
				<!-- wp:html -->
				<span class="yt-sticker" style="position:absolute;right:-18px;top:-22px"><?php esc_html_e( 'Free delivery over ฿1,000', 'yummy-treats' ); ?></span>
				<!-- /wp:html -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
