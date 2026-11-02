<?php
/**
 * Title: Product reassurance list
 * Slug: yummy-treats/pdp-reassure
 * Categories: yummy-treats
 * Inserter: no
 * Description: The four reassurance lines under the Add to cart button, plus the "also in shops" row.
 *
 * @package YummyTreats
 */

$yt_check = '<svg viewBox="0 0 20 20" aria-hidden="true"><circle cx="10" cy="10" r="9" fill="#DFF2DE" stroke="#000000" stroke-width="2"/><path d="M6 10.5l2.5 2.5L14 7.5" fill="none" stroke="#000000" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$yt_lines = array(
	__( 'Free Bangkok delivery on orders over ฿1,000, otherwise ฿50. Elsewhere in Thailand ฿80, free over ฿1,500.', 'yummy-treats' ),
	__( 'Order before 14:00 and we hand it to the courier the same day (Mon–Sat).', 'yummy-treats' ),
	__( 'PromptPay, card, bank transfer or cash on delivery.', 'yummy-treats' ),
	__( 'Not a hit with your pet? Message us on LINE within 7 days and we will sort it out.', 'yummy-treats' ),
);
?>
<!-- wp:html -->
<ul class="yt-reassure">
	<?php foreach ( $yt_lines as $yt_line ) : ?>
	<li><?php echo $yt_check; // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( $yt_line ); ?></span></li>
	<?php endforeach; ?>
</ul>
<div class="yt-marketrow">
	<span><?php esc_html_e( 'Also on the shelf at pet shops and vet clinics:', 'yummy-treats' ); ?></span>
	<a class="wp-block-button__link is-style-outline" style="background:#fff;color:#000000" href="<?php echo esc_url( home_url( '/stockists/' ) ); ?>"><?php esc_html_e( 'Find a stockist', 'yummy-treats' ); ?></a>
</div>
<!-- /wp:html -->
