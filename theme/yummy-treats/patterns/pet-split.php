<?php
/**
 * Title: Dogs / Cats split
 * Slug: yummy-treats/pet-split
 * Categories: yummy-treats
 * Description: Two big tappable cards, raspberry for dogs and blue for cats.
 *
 * @package YummyTreats
 */

?>
<!-- wp:group {"align":"wide","className":"yt-petsplit yt-reveal-stagger","style":{"spacing":{"padding":{"bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide yt-petsplit yt-reveal-stagger" style="padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|40"}}}} -->
	<div class="wp-block-columns alignwide">
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"yt-pet-card","style":{"spacing":{"padding":{"top":"32px","bottom":"32px","left":"32px","right":"32px"}},"color":{"background":"#F26BC6","text":"#000000"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"space-between","verticalAlignment":"space-between"}} -->
			<div class="wp-block-group yt-pet-card has-text-color has-background" style="color:#000000;background-color:#F26BC6;padding:32px">
				<!-- wp:heading {"level":2,"style":{"color":{"text":"#000000"}}} -->
				<h2 class="wp-block-heading has-text-color" style="color:#000000"><?php esc_html_e( 'For dogs', 'yummy-treats' ); ?></h2>
				<!-- /wp:heading -->
				<!-- wp:paragraph {"style":{"color":{"text":"#000000"},"typography":{"fontWeight":"600"}}} -->
				<p class="has-text-color" style="color:#000000;font-weight:600"><?php esc_html_e( 'Training treats, chews and meal toppers, from toy breeds to big softies.', 'yummy-treats' ); ?></p>
				<!-- /wp:paragraph -->
				<!-- wp:buttons -->
				<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/product-category/dogs/"><?php esc_html_e( 'Shop dogs →', 'yummy-treats' ); ?></a></div><!-- /wp:button --></div>
				<!-- /wp:buttons -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"yt-pet-card","style":{"spacing":{"padding":{"top":"32px","bottom":"32px","left":"32px","right":"32px"}},"color":{"background":"#5EC1EC","text":"#000000"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"space-between","verticalAlignment":"space-between"}} -->
			<div class="wp-block-group yt-pet-card has-text-color has-background" style="color:#000000;background-color:#5EC1EC;padding:32px">
				<!-- wp:heading {"level":2,"style":{"color":{"text":"#000000"}}} -->
				<h2 class="wp-block-heading has-text-color" style="color:#000000"><?php esc_html_e( 'For cats', 'yummy-treats' ); ?></h2>
				<!-- /wp:heading -->
				<!-- wp:paragraph {"style":{"color":{"text":"#000000"},"typography":{"fontWeight":"600"}}} -->
				<p class="has-text-color" style="color:#000000;font-weight:600"><?php esc_html_e( 'Freeze-dried bites, lickable treats and wet food for picky eaters.', 'yummy-treats' ); ?></p>
				<!-- /wp:paragraph -->
				<!-- wp:buttons -->
				<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/product-category/cats/"><?php esc_html_e( 'Shop cats →', 'yummy-treats' ); ?></a></div><!-- /wp:button --></div>
				<!-- /wp:buttons -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
