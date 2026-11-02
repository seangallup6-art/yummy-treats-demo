<?php
/**
 * Title: LINE band
 * Slug: yummy-treats/line-band
 * Categories: yummy-treats
 * Description: Raspberry band inviting people to add the shop on LINE. The link is a placeholder until the client supplies the official account.
 *
 * @package YummyTreats
 */

?>
<!-- wp:group {"align":"wide","className":"yt-line-band yt-reveal","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|60","right":"var:preset|spacing|60"},"margin":{"bottom":"var:preset|spacing|60"}},"color":{"background":"#F26BC6","text":"#000000"},"border":{"radius":"28px","width":"3px","color":"#000000"}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
<div class="wp-block-group alignwide yt-line-band yt-reveal has-text-color has-background has-border-color" style="border-color:#000000;border-width:3px;border-radius:28px;color:#000000;background-color:#F26BC6;margin-bottom:var(--wp--preset--spacing--60);padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--60)">
	<!-- wp:group {"layout":{"type":"constrained"}} -->
	<div class="wp-block-group">
		<!-- wp:heading {"level":2,"style":{"color":{"text":"#000000"}}} -->
		<h2 class="wp-block-heading has-text-color" style="color:#000000"><?php esc_html_e( 'Questions? We answer on LINE.', 'yummy-treats' ); ?></h2>
		<!-- /wp:heading -->
		<!-- wp:paragraph {"style":{"color":{"text":"#000000"},"typography":{"fontWeight":"600"}}} -->
		<p class="has-text-color" style="color:#000000;font-weight:600"><?php esc_html_e( 'Order help, feeding questions, or just a photo of your dog. Weekdays 9–18.', 'yummy-treats' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
	<!-- wp:buttons -->
	<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="https://line.me/R/ti/p/[@yummytreats]" rel="noopener"><?php esc_html_e( 'Add [@yummytreats] on LINE', 'yummy-treats' ); ?></a></div><!-- /wp:button --></div>
	<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
