<?php
/**
 * Title: Guide for what was bought (order confirmation)
 * Slug: yummy-treats/confirmation-guide
 * Categories: yummy-treats
 * Inserter: no
 * Description: One care guide picked from the order's categories. Renders nothing without a valid order key.
 *
 * @package YummyTreats
 */

$yt_order = function_exists( 'yt_received_order' ) ? yt_received_order() : null;
$yt_guide = $yt_order ? yt_order_guide( $yt_order ) : null;
if ( ! $yt_guide ) {
	return;
}
?>
<!-- wp:group {"align":"wide","className":"yt-guide-card is-style-card-flat","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|50","right":"var:preset|spacing|50"},"margin":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
<div class="wp-block-group alignwide yt-guide-card is-style-card-flat" style="margin-top:var(--wp--preset--spacing--40);margin-bottom:var(--wp--preset--spacing--40);padding:var(--wp--preset--spacing--40) var(--wp--preset--spacing--50)">
	<!-- wp:html -->
	<div>
		<p class="yt-eyebrow"><?php esc_html_e( 'For what you just bought', 'yummy-treats' ); ?></p>
		<p class="yt-guide-card__title"><?php echo esc_html( get_the_title( $yt_guide ) ); ?></p>
	</div>
	<a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( get_permalink( $yt_guide ) ); ?>">
		<?php
		/* translators: %d: minutes */
		printf( esc_html__( 'Read it (%d min)', 'yummy-treats' ), (int) yt_reading_minutes( $yt_guide ) );
		?>
	</a>
	<!-- /wp:html -->
</div>
<!-- /wp:group -->
