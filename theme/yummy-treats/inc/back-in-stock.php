<?php
/**
 * Back-in-stock requests (design rule R6: a sold-out product keeps a way forward, never a dead end).
 *
 * Top-down:
 *  - Product page: when a product is sold out, the add-to-cart area gets an email form ("Notify me").
 *  - The form posts to admin-post.php; the address is stored on the product with a consent flag (PDPA).
 *  - When the product comes back in stock, each address gets one email and the list is emptied.
 *  - Shop cards' "Notify me" button jumps straight to the form; the product's Inventory tab shows the request count.
 *
 * @package YummyTreats
 */

defined( 'ABSPATH' ) || exit;

const YT_NOTIFY_META = '_yt_notify_requests';

/**
 * Stored requests on a product: email => time.
 *
 * @param WC_Product|null $product Product.
 * @return array<string,int>
 */
function yt_notify_list( $product ) {
	$list = $product ? $product->get_meta( YT_NOTIFY_META, true ) : array();
	return is_array( $list ) ? $list : array();
}

/**
 * Append the form to the add-to-cart block of a sold-out product.
 *
 * @param string         $content  Rendered block.
 * @param array          $block    Parsed block.
 * @param \WP_Block|null $instance Block instance.
 * @return string
 */
function yt_notify_form_after_add_to_cart( $content, $block, $instance = null ) {
	$product = wc_get_product( yt_block_post_id( $block, $instance ) );
	if ( ! $product || $product->is_in_stock() ) {
		return $content;
	}
	$done = isset( $_GET['yt-notify'] ) && 'ok' === $_GET['yt-notify']; // phpcs:ignore WordPress.Security.NonceVerification
	ob_start();
	?>
	<div class="yt-notify" id="yt-notify">
		<p class="yt-notify__title"><?php esc_html_e( 'Sold out for now. Get one email when it is back.', 'yummy-treats' ); ?></p>
		<?php if ( $done ) : ?>
			<p class="yt-notify__done" role="status"><?php esc_html_e( 'Done. We will email you once when it is back, then delete your address.', 'yummy-treats' ); ?></p>
		<?php else : ?>
			<form class="yt-notify__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="yt_notify">
				<input type="hidden" name="product_id" value="<?php echo esc_attr( $product->get_id() ); ?>">
				<?php wp_nonce_field( 'yt_notify_' . $product->get_id(), '_ytnotify' ); ?>
				<label class="yt-notify__label" for="yt-notify-email"><?php esc_html_e( 'Email', 'yummy-treats' ); ?></label>
				<div class="yt-notify__row">
					<input type="email" id="yt-notify-email" name="email" required autocomplete="email" inputmode="email">
					<button type="submit" class="wp-element-button"><?php esc_html_e( 'Notify me', 'yummy-treats' ); ?></button>
				</div>
				<label class="yt-notify__consent">
					<input type="checkbox" name="consent" value="1" required>
					<span><?php esc_html_e( 'Use my email for this one message only (PDPA).', 'yummy-treats' ); ?></span>
				</label>
			</form>
		<?php endif; ?>
		<p class="yt-notify__alt">
			<?php
			printf(
				/* translators: %s: LINE account link */
				esc_html__( 'Or ask on LINE %s when the next batch is due.', 'yummy-treats' ),
				'<a href="https://line.me/R/ti/p/[@yummytreats]" rel="noopener">[@yummytreats]</a>'
			);
			?>
		</p>
	</div>
	<?php
	return $content . ob_get_clean();
}
add_filter( 'render_block_woocommerce/add-to-cart-form', 'yt_notify_form_after_add_to_cart', 10, 3 );

/**
 * admin-post.php?action=yt_notify
 */
function yt_notify_handle() {
	$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
	$product    = $product_id ? wc_get_product( $product_id ) : null;
	if ( ! $product ) {
		wp_safe_redirect( home_url( '/' ) );
		exit;
	}
	$back  = get_permalink( $product->get_id() );
	$nonce = isset( $_POST['_ytnotify'] ) ? sanitize_text_field( wp_unslash( $_POST['_ytnotify'] ) ) : '';
	$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'yt_notify_' . $product_id ) || ! is_email( $email ) || empty( $_POST['consent'] ) ) {
		wp_safe_redirect( add_query_arg( 'yt-notify', 'error', $back ) . '#yt-notify' );
		exit;
	}
	$list                         = yt_notify_list( $product );
	$list[ strtolower( $email ) ] = time();
	$product->update_meta_data( YT_NOTIFY_META, $list );
	$product->save_meta_data();
	wp_safe_redirect( add_query_arg( 'yt-notify', 'ok', $back ) . '#yt-notify' );
	exit;
}
add_action( 'admin_post_yt_notify', 'yt_notify_handle' );
add_action( 'admin_post_nopriv_yt_notify', 'yt_notify_handle' );

/**
 * Back in stock: one email to each address, then forget them.
 *
 * @param int    $product_id Product or variation ID.
 * @param string $status     New stock status.
 * @param object $product    Product.
 */
function yt_notify_on_restock( $product_id, $status, $product = null ) {
	if ( 'instock' !== $status ) {
		return;
	}
	$product = $product ?: wc_get_product( $product_id );
	$target  = ( $product && $product->get_parent_id() ) ? wc_get_product( $product->get_parent_id() ) : $product;
	$list    = yt_notify_list( $target );
	if ( ! $list ) {
		return;
	}
	$mailer = WC()->mailer();
	/* translators: %s: product name */
	$subject = sprintf( __( '%s is back in stock', 'yummy-treats' ), $target->get_name() );
	$body    = $mailer->wrap_message(
		$subject,
		'<p>' . sprintf(
			/* translators: %s: product link */
			esc_html__( 'Good news: %s is back. We are not keeping your address; this is the only message you will get.', 'yummy-treats' ),
			'<a href="' . esc_url( get_permalink( $target->get_id() ) ) . '">' . esc_html( $target->get_name() ) . '</a>'
		) . '</p>'
	);
	foreach ( array_keys( $list ) as $email ) {
		$mailer->send( $email, $subject, $body );
	}
	$target->delete_meta_data( YT_NOTIFY_META );
	$target->save_meta_data();
}
add_action( 'woocommerce_product_set_stock_status', 'yt_notify_on_restock', 10, 3 );
add_action( 'woocommerce_variation_set_stock_status', 'yt_notify_on_restock', 10, 3 );

/**
 * Shop cards: "Notify me" jumps to the form.
 *
 * @param string     $url     Button URL.
 * @param WC_Product $product Product.
 * @return string
 */
function yt_notify_card_url( $url, $product ) {
	return ( $product instanceof WC_Product && ! $product->is_in_stock() ) ? get_permalink( $product->get_id() ) . '#yt-notify' : $url;
}
add_filter( 'woocommerce_product_add_to_cart_url', 'yt_notify_card_url', 10, 2 );

/**
 * Admin: request count in the product's Inventory tab.
 */
function yt_notify_admin_count() {
	global $product_object;
	if ( ! $product_object instanceof WC_Product ) {
		return;
	}
	$n = count( yt_notify_list( $product_object ) );
	if ( $n ) {
		/* translators: %d: number of requests */
		echo '<p class="form-field"><strong>' . esc_html( sprintf( _n( '%d customer asked to be told when this is back.', '%d customers asked to be told when this is back.', $n, 'yummy-treats' ), $n ) ) . '</strong></p>';
	}
}
add_action( 'woocommerce_product_options_stock_status', 'yt_notify_admin_count' );
