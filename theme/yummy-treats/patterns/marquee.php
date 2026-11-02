<?php
/**
 * Title: Marquee strip
 * Slug: yummy-treats/marquee
 * Categories: yummy-treats
 * Inserter: no
 * Description: The scrolling ink strip at the very top. Pauses on hover, has a Pause button, and stops entirely for reduced-motion users.
 *
 * @package YummyTreats
 */

$yt_marquee_items = array(
	__( 'Free Bangkok delivery over ฿1,000', 'yummy-treats' ),
	__( 'Small-batch treats, made in Thailand', 'yummy-treats' ),
	__( 'Pay by PromptPay, card, bank transfer or cash on delivery', 'yummy-treats' ),
	__( 'Also stocked at pet shops and vet clinics near you', 'yummy-treats' ),
);
?>
<!-- wp:html -->
<div class="yt-marquee" aria-label="<?php esc_attr_e( 'Shop news', 'yummy-treats' ); ?>">
	<div class="yt-marquee__viewport">
		<div class="yt-marquee__track" aria-hidden="true">
			<?php foreach ( $yt_marquee_items as $yt_item ) : ?>
				<span><?php echo esc_html( $yt_item ); ?> <span aria-hidden="true">✦</span></span>
			<?php endforeach; ?>
		</div>
	</div>
	<p class="screen-reader-text"><?php echo esc_html( implode( '. ', $yt_marquee_items ) ); ?>.</p>
	<button type="button" class="yt-marquee__pause" aria-pressed="false"><?php esc_html_e( 'Pause', 'yummy-treats' ); ?></button>
</div>
<!-- /wp:html -->
