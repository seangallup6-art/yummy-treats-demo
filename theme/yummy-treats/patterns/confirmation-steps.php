<?php
/**
 * Title: What happens next (order confirmation)
 * Slug: yummy-treats/confirmation-steps
 * Categories: yummy-treats
 * Inserter: no
 * Description: Four steps after ordering. Step 2 changes wording per payment method via the slip-upload plugin when present.
 *
 * @package YummyTreats
 */

?>
<!-- wp:group {"align":"wide","style":{"spacing":{"margin":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide" style="margin-top:var(--wp--preset--spacing--40);margin-bottom:var(--wp--preset--spacing--40)">
	<!-- wp:heading {"level":2} --><h2 class="wp-block-heading"><?php esc_html_e( 'What happens next', 'yummy-treats' ); ?></h2><!-- /wp:heading -->
	<!-- wp:html -->
	<ol class="yt-steps">
		<li class="is-done"><strong>1</strong><?php esc_html_e( 'Order received. You will get an email (and a LINE message if you opted in).', 'yummy-treats' ); ?></li>
		<li><strong>2</strong><?php esc_html_e( 'Payment confirmed. For bank transfer, upload your slip below; PromptPay and card confirm automatically.', 'yummy-treats' ); ?></li>
		<li><strong>3</strong><?php esc_html_e( 'Packed and handed to the courier, same day for orders before 14:00.', 'yummy-treats' ); ?></li>
		<li><strong>4</strong><?php esc_html_e( 'Delivered. Bangkok usually next day; the rest of Thailand 2–3 days.', 'yummy-treats' ); ?></li>
	</ol>
	<!-- /wp:html -->
</div>
<!-- /wp:group -->
