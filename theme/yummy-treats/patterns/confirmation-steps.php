<?php
/**
 * Title: What happens next (order confirmation)
 * Slug: yummy-treats/confirmation-steps
 * Categories: yummy-treats
 * Inserter: no
 * Description: Four steps that follow the order's state (transfer, slip checking, PromptPay, cash on delivery, paid, shipped), plus a line for any pre-order items. Renders nothing without a valid order key.
 *
 * @package YummyTreats
 */

$yt_order = function_exists( 'yt_received_order' ) ? yt_received_order() : null;
if ( ! $yt_order || in_array( yt_order_state( $yt_order ), array( 'failed', 'cancelled' ), true ) ) {
	return;
}
$yt_labels = array(
	'done'    => __( 'Done', 'yummy-treats' ),
	'current' => __( 'Now', 'yummy-treats' ),
	'todo'    => __( 'Next', 'yummy-treats' ),
);
?>
<!-- wp:group {"align":"wide","className":"yt-next","style":{"spacing":{"margin":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide yt-next" style="margin-top:var(--wp--preset--spacing--40);margin-bottom:var(--wp--preset--spacing--40)">
	<!-- wp:heading {"level":2} --><h2 class="wp-block-heading"><?php esc_html_e( 'What happens next', 'yummy-treats' ); ?></h2><!-- /wp:heading -->
	<!-- wp:html -->
	<ol class="yt-steps">
		<?php foreach ( yt_order_steps( $yt_order ) as $yt_i => $yt_step ) : ?>
		<li class="is-<?php echo esc_attr( $yt_step['state'] ); ?>"<?php echo 'current' === $yt_step['state'] ? ' aria-current="step"' : ''; ?>>
			<strong><?php echo esc_html( $yt_i + 1 ); ?></strong>
			<span class="yt-steps__state"><?php echo esc_html( $yt_labels[ $yt_step['state'] ] ); ?></span>
			<span class="yt-steps__title"><?php echo esc_html( $yt_step['title'] ); ?></span>
			<span class="yt-steps__sub"><?php echo esc_html( $yt_step['sub'] ); ?></span>
		</li>
		<?php endforeach; ?>
	</ol>
	<?php foreach ( yt_order_preorders( $yt_order ) as $yt_pre ) : ?>
	<p class="yt-next__preorder">
		<?php
		/* translators: 1: product name, 2: date, e.g. 12 Nov */
		printf( esc_html__( '%1$s is a pre-order and travels separately (ships %2$s), at no extra delivery cost. We message you when it leaves.', 'yummy-treats' ), '<strong>' . esc_html( $yt_pre['name'] ) . '</strong>', esc_html( $yt_pre['ships'] ) );
		?>
	</p>
	<?php endforeach; ?>
	<!-- /wp:html -->
</div>
<!-- /wp:group -->
