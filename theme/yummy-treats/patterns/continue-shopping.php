<?php
/**
 * Title: Continue shopping link
 * Slug: yummy-treats/continue-shopping
 * Categories: yummy-treats
 * Inserter: no
 * Description: A pill link back to the shop. Used above the cart and at the end of the order confirmation.
 *
 * @package YummyTreats
 */

$yt_shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
?>
<!-- wp:html -->
<p class="yt-backlink-row"><a class="yt-backlink" href="<?php echo esc_url( $yt_shop_url ); ?>"><svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/></svg><?php esc_html_e( 'Continue shopping', 'yummy-treats' ); ?></a></p>
<!-- /wp:html -->
