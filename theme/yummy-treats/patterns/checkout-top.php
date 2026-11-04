<?php
/**
 * Title: Checkout top (Back to cart, title, steps)
 * Slug: yummy-treats/checkout-top
 * Categories: yummy-treats
 * Inserter: no
 * Description: Board P7-A. A "Back to cart" pill over the big title, and three step pills: Cart (done), Checkout (here), Pay.
 *
 * @package YummyTreats
 */

$yt_cart_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' );
?>
<!-- wp:html -->
<div class="yt-co-top alignwide">
	<div class="yt-co-top__lead">
		<a class="yt-backlink" href="<?php echo esc_url( $yt_cart_url ); ?>"><svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/></svg><?php esc_html_e( 'Back to cart', 'yummy-treats' ); ?></a>
		<h1 class="yt-co-top__title"><?php esc_html_e( 'Checkout', 'yummy-treats' ); ?></h1>
	</div>
	<ol class="yt-co-steps" aria-label="<?php esc_attr_e( 'Steps', 'yummy-treats' ); ?>">
		<li class="is-done"><span class="yt-co-steps__n" aria-hidden="true">✓</span><?php esc_html_e( 'Cart', 'yummy-treats' ); ?><span class="screen-reader-text"> <?php esc_html_e( '(done)', 'yummy-treats' ); ?></span></li>
		<li class="is-current" aria-current="step"><span class="yt-co-steps__n" aria-hidden="true">2</span><?php esc_html_e( 'Checkout', 'yummy-treats' ); ?></li>
		<li><span class="yt-co-steps__n" aria-hidden="true">3</span><?php esc_html_e( 'Pay', 'yummy-treats' ); ?></li>
	</ol>
</div>
<!-- /wp:html -->
