<?php
/**
 * Title: Claim row
 * Slug: yummy-treats/claim-row
 * Categories: yummy-treats
 * Description: One white strip of short promises with star bullets, under the hero. Only claims a small brand can stand behind — no numbers.
 *
 * @package YummyTreats
 */

$yt_claims = array(
	__( 'real meat first', 'yummy-treats' ),
	__( 'made in Thailand', 'yummy-treats' ),
	__( 'city-sized bags', 'yummy-treats' ),
	__( 'stocked by vets', 'yummy-treats' ),
	__( 'no fillers, ever', 'yummy-treats' ),
);
?>
<!-- wp:group {"align":"wide","className":"yt-reveal","style":{"spacing":{"padding":{"bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide yt-reveal" style="padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:html -->
	<ul class="yt-claims alignwide" aria-label="<?php esc_attr_e( 'What we promise', 'yummy-treats' ); ?>">
		<?php foreach ( $yt_claims as $yt_c ) : ?>
		<li><span class="yt-claims__star" aria-hidden="true">✦</span><?php echo esc_html( $yt_c ); ?></li>
		<?php endforeach; ?>
	</ul>
	<!-- /wp:html -->
</div>
<!-- /wp:group -->
