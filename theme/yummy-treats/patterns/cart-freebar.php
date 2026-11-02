<?php
/**
 * Title: Free delivery progress bar
 * Slug: yummy-treats/cart-freebar
 * Categories: yummy-treats
 * Inserter: no
 * Description: "Add ฿X more for free Bangkok delivery". Filled by shop.js from the Store API cart totals. Threshold comes from the Bangkok shipping zone's free-shipping minimum when one is set.
 *
 * @package YummyTreats
 */

$yt_threshold = function_exists( 'yt_free_shipping_threshold' ) ? yt_free_shipping_threshold() : 1000;
?>
<!-- wp:html -->
<div class="yt-freebar alignwide" data-threshold="<?php echo esc_attr( $yt_threshold ); ?>" hidden aria-live="polite">
	<span class="yt-freebar__msg" data-no-dynamic-translation></span>
	<div class="yt-freebar__track"><div class="yt-freebar__fill" style="width:0%"></div></div>
</div>
<!-- /wp:html -->
