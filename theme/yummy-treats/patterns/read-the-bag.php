<?php
/**
 * Title: Read the bag (why us)
 * Slug: yummy-treats/read-the-bag
 * Categories: yummy-treats
 * Description: Ink band with four plain-language promises. No invented statistics — only claims a small brand can stand behind.
 *
 * @package YummyTreats
 */

$yt_points = array(
	array( __( 'Real meat first', 'yummy-treats' ), __( 'Chicken, duck, fish or pork is the first ingredient on every bag. No fillers, no mystery meal.', 'yummy-treats' ) ),
	array( __( 'Made in Thailand', 'yummy-treats' ), __( 'Cooked in small batches in [province] and sold fresh, not shipped halfway around the world.', 'yummy-treats' ) ),
	array( __( 'City-sized bags', 'yummy-treats' ), __( '100 g to 1.5 kg, so a condo kitchen never has a stale half-bag at the back of the cupboard.', 'yummy-treats' ) ),
	array( __( 'Vet-friendly', 'yummy-treats' ), __( 'Stocked by [number] vet clinics across Bangkok. Ask yours, or find one on the stockists page.', 'yummy-treats' ) ),
);
?>
<!-- wp:group {"align":"wide","className":"is-style-band-ink yt-reveal","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|60","right":"var:preset|spacing|60"},"margin":{"bottom":"var:preset|spacing|70"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide is-style-band-ink yt-reveal" style="margin-bottom:var(--wp--preset--spacing--70);padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--60)">
	<!-- wp:heading {"level":2,"align":"wide","style":{"typography":{"fontSize":"var:preset|font-size|xx-large"}}} -->
	<h2 class="wp-block-heading alignwide" style="font-size:var(--wp--preset--font-size--xx-large)"><?php esc_html_e( 'Read the bag. We did.', 'yummy-treats' ); ?></h2>
	<!-- /wp:heading -->
	<!-- wp:columns {"align":"wide","className":"yt-tile-grid"} -->
	<div class="wp-block-columns alignwide yt-tile-grid">
		<?php foreach ( $yt_points as $yt_i => $yt_p ) : ?>
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"style":{"color":{"text":"#000000"}},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group has-text-color" style="color:#000000">
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"40px","fontWeight":"800","lineHeight":"1"},"color":{"text":"#000000"}},"fontFamily":"display"} -->
				<p class="has-text-color has-display-font-family" style="color:#000000;font-size:40px;font-weight:800;line-height:1"><?php echo esc_html( str_pad( (string) ( $yt_i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></p>
				<!-- /wp:paragraph -->
				<!-- wp:heading {"level":3,"style":{"color":{"text":"#000000"}}} -->
				<h3 class="wp-block-heading has-text-color" style="color:#000000"><?php echo esc_html( $yt_p[0] ); ?></h3>
				<!-- /wp:heading -->
				<!-- wp:paragraph {"style":{"color":{"text":"#3D3D3D"},"typography":{"fontSize":"15px","fontWeight":"500"}}} -->
				<p class="has-text-color" style="color:#3D3D3D;font-size:15px;font-weight:500"><?php echo esc_html( $yt_p[1] ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
		<?php endforeach; ?>
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
