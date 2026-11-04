<?php
/**
 * Product page: the picture gallery (board P1, option B) and the zoom view (board P3, "dark room").
 *
 * WooCommerce's Product Gallery block does the real work (which picture is showing, swiping, the dialog, focus
 * trapping, Esc). This file only adds markup to its output: picture names, a zoom button, a count, and the dark
 * room's title, arrows and picture pills. The added buttons call WooCommerce's own gallery actions through
 * Interactivity API directives, and assets/js/gallery.js adds the two read-outs WooCommerce has no state for
 * (the "1 / 2" count and which picture is current).
 *
 * @package YummyTreats
 */

defined( 'ABSPATH' ) || exit;

/**
 * Load the gallery store on product pages only.
 */
function yt_gallery_module() {
	wp_register_script_module( 'yummy-treats/gallery', YT_URI . '/assets/js/gallery.js', array( '@wordpress/interactivity' ), yt_asset_version( 'assets/js/gallery.js' ) );
	if ( is_product() ) {
		wp_enqueue_script_module( 'yummy-treats/gallery' );
	}
}
add_action( 'wp_enqueue_scripts', 'yt_gallery_module' );

/**
 * The same two read-outs on the server, so the page arrives with the right count and the first picture marked
 * (WordPress renders Interactivity API directives on the server as well as in the browser).
 */
function yt_gallery_server_state() {
	if ( ! is_product() || ! function_exists( 'wp_interactivity_state' ) ) {
		return;
	}
	wp_interactivity_state(
		'yummy-treats/gallery',
		array(
			'count'     => static function () {
				$context = wp_interactivity_get_context( 'woocommerce/product-gallery' );
				$ids     = array_map( 'intval', (array) ( $context['imageData'] ?? array() ) );
				$index   = array_search( (int) ( $context['selectedImageId'] ?? 0 ), $ids, true );
				return ( false === $index ? 1 : $index + 1 ) . ' / ' . count( $ids );
			},
			'isCurrent' => static function () {
				$context = wp_interactivity_get_context( 'woocommerce/product-gallery' );
				$element = wp_interactivity_get_element();
				return (int) ( $element['attributes']['data-image-id'] ?? 0 ) === (int) ( $context['selectedImageId'] ?? 0 );
			},
		)
	);
}
add_action( 'template_redirect', 'yt_gallery_server_state' );

/**
 * The product's pictures with a short name for each: the attachment caption if it has one ("Front",
 * "Back of the bag"), otherwise "Picture 2". Keyed by attachment ID, in gallery order.
 *
 * @param int $product_id Product ID.
 * @return array<int,string>
 */
function yt_gallery_pictures( $product_id ) {
	$product = wc_get_product( $product_id );
	if ( ! $product ) {
		return array();
	}
	$ids      = array_values( array_unique( array_filter( array_merge( array( (int) $product->get_image_id() ), array_map( 'intval', $product->get_gallery_image_ids() ) ) ) ) );
	$pictures = array();
	foreach ( $ids as $n => $id ) {
		$caption = trim( (string) wp_get_attachment_caption( $id ) );
		/* translators: %d: picture number, 1 for the first picture */
		$pictures[ $id ] = '' !== $caption ? yt_translate_stored_label( $caption ) : sprintf( __( 'Picture %d', 'yummy-treats' ), $n + 1 );
	}
	return $pictures;
}

/**
 * One <span> per picture name; only the current one shows. The first is current when the page loads.
 *
 * @param array<int,string> $pictures From yt_gallery_pictures().
 * @return string
 */
function yt_gallery_name_spans( $pictures ) {
	$out   = '';
	$first = true;
	foreach ( $pictures as $id => $name ) {
		$out  .= sprintf(
			'<span data-image-id="%1$d" data-wp-bind--hidden="yummy-treats/gallery::!state.isCurrent"%2$s>%3$s</span>',
			$id,
			$first ? '' : ' hidden',
			esc_html( $name )
		);
		$first = false;
	}
	return $out;
}

/**
 * Large picture: a picture-name sticker and a zoom button on the picture, and the count and name in the strip
 * under it, beside WooCommerce's previous/next buttons (which the stylesheet moves into that strip).
 *
 * @param string    $html     Rendered block.
 * @param array     $block    Parsed block.
 * @param \WP_Block $instance Block instance.
 * @return string
 */
function yt_gallery_large_image_extras( $html, $block, $instance = null ) {
	$pictures = yt_gallery_pictures( yt_block_post_id( $block, $instance ) );
	$end      = strrpos( $html, '</div>' );
	if ( ! $pictures || false === $end ) {
		return $html;
	}
	$names = yt_gallery_name_spans( $pictures );
	$zoom  = '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><circle cx="10.5" cy="10.5" r="6.5" fill="none" stroke="currentColor" stroke-width="3"/><path d="M15.5 15.5L21 21M10.5 7.5v6M7.5 10.5h6" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>';
	$extra = '<span class="yt-gal__sticker" aria-hidden="true">' . $names . '</span>'
		. '<button type="button" class="yt-gal__zoom" aria-label="' . esc_attr__( 'Zoom in', 'yummy-treats' ) . '"'
		. ' data-wp-on--click="woocommerce/product-gallery::actions.openDialog"'
		. ' data-wp-watch="yummy-treats/gallery::callbacks.returnFocus">' . $zoom . '</button>';
	if ( count( $pictures ) > 1 ) {
		$extra .= '<p class="yt-gal__meta"><span class="yt-gal__count" data-wp-text="yummy-treats/gallery::state.count">1 / ' . count( $pictures ) . '</span>'
			. '<span class="yt-gal__name">' . $names . '</span></p>';
	}
	return substr_replace( $html, $extra, $end, 0 );
}
add_filter( 'render_block_woocommerce/product-gallery-large-image', 'yt_gallery_large_image_extras', 200, 3 );

/**
 * The zoom dialog, dark room style: product name and count in the header, a visible "Close" on the close button,
 * one framed picture at a time (its name as a sticker), round arrows, and a pill per picture at the bottom.
 * Arrow keys change the picture; a click on the dark area around the picture closes it.
 *
 * WordPress then renders the added directives on the server too (yt_gallery_server_state() supplies the values).
 *
 * @param string    $html     Rendered block.
 * @param array     $block    Parsed block.
 * @param \WP_Block $instance Block instance.
 * @return string
 */
function yt_gallery_dialog_extras( $html, $block, $instance = null ) {
	if ( false === strpos( $html, 'wc-block-product-gallery-dialog' ) ) {
		return $html;
	}
	$product_id = yt_block_post_id( $block, $instance );
	$pictures   = yt_gallery_pictures( $product_id );
	if ( ! $pictures ) {
		return $html;
	}
	$many  = count( $pictures ) > 1;
	$names = yt_gallery_name_spans( $pictures );

	// Mark the dialog's pictures so only the current one shows, and let arrow keys move between them.
	$tags      = new WP_HTML_Tag_Processor( $html );
	$in_dialog = false;
	$first     = true;
	while ( $tags->next_tag() ) {
		$tag = $tags->get_tag();
		if ( 'DIALOG' === $tag && ! $tags->is_tag_closer() ) {
			$in_dialog = true;
			/* translators: %s: product name */
			$tags->set_attribute( 'aria-label', sprintf( __( '%s: pictures', 'yummy-treats' ), get_the_title( $product_id ) ) );
			if ( $many ) {
				$tags->set_attribute( 'data-wp-on--keydown---yt-arrows', 'woocommerce/product-gallery::actions.onArrowsKeyDown' );
			}
			continue;
		}
		if ( $in_dialog && 'DIV' === $tag && $tags->has_class( 'wc-block-product-gallery-dialog__content' ) ) {
			$tags->set_attribute( 'data-wp-on--click', 'yummy-treats/gallery::actions.closeOnBackdrop' );
			continue;
		}
		if ( $in_dialog && 'BUTTON' === $tag && $tags->has_class( 'wc-block-product-gallery-dialog__close-button' ) ) {
			// Its name is its visible word (WooCommerce's English "Close dialog" would not match the Thai word).
			$tags->set_attribute( 'aria-label', __( 'Close', 'yummy-treats' ) );
			continue;
		}
		if ( $in_dialog && 'IMG' === $tag ) {
			$tags->set_attribute( 'data-wp-class--yt-is-current', 'yummy-treats/gallery::state.isCurrent' );
			if ( $first ) {
				$tags->add_class( 'yt-is-current' );
				$first = false;
			}
		}
	}
	$html = $tags->get_updated_html();

	$arrow = static function ( $dir ) {
		$d = 'prev' === $dir ? 'M15 5l-7 7 7 7' : 'M9 5l7 7-7 7';
		return '<svg viewBox="0 0 24 24" width="26" height="26" aria-hidden="true" focusable="false"><path d="' . $d . '" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
	};

	// Header: name and count before WooCommerce's close button, and a visible word on that button.
	$title = '<p class="yt-dlg__title"><span class="yt-dlg__dot" aria-hidden="true"></span><span class="yt-dlg__name">' . esc_html( get_the_title( $product_id ) ) . '</span>'
		. ( $many ? '<span class="yt-dlg__count" data-wp-text="yummy-treats/gallery::state.count">1 / ' . count( $pictures ) . '</span>' : '' ) . '</p>';
	$html  = preg_replace( '#(<div[^>]*class="wc-block-product-gallery-dialog__header"[^>]*>)#', '$1' . $title, $html, 1 );
	$html  = preg_replace( '#(<button[^>]*class="wc-block-product-gallery-dialog__close-button"[^>]*>)#', '$1<span class="yt-dlg__close-text">' . esc_html__( 'Close', 'yummy-treats' ) . '</span>', $html, 1 );

	// The picture's name as a sticker on its frame.
	$html = preg_replace( '#(<div[^>]*class="wc-block-product-gallery-dialog__content"[^>]*>)#', '$1<span class="yt-dlg__sticker" aria-hidden="true">' . $names . '</span>', $html, 1 );

	// Arrows and picture pills, after the pictures.
	if ( $many ) {
		$tints = array( 'mustard', 'blue', 'raspberry', 'green' );
		$pills = '';
		$n     = 0;
		foreach ( $pictures as $id => $name ) {
			$pills .= sprintf(
				'<button type="button" class="yt-dlg__pill" data-image-id="%1$d" aria-pressed="%2$s" data-wp-bind--aria-pressed="yummy-treats/gallery::state.isCurrent" data-wp-on--click="woocommerce/product-gallery::actions.selectCurrentImage"><span class="yt-dlg__swatch has-tint-%3$s" aria-hidden="true"></span>%4$s</button>',
				$id,
				0 === $n ? 'true' : 'false',
				$tints[ $n % count( $tints ) ],
				esc_html( $name )
			);
			++$n;
		}
		$controls = '<button type="button" class="yt-dlg__arrow is-prev" aria-label="' . esc_attr__( 'Previous picture', 'yummy-treats' ) . '" aria-disabled="true" data-wp-bind--aria-disabled="woocommerce/product-gallery::context.isDisabledPrevious" data-wp-on--click="woocommerce/product-gallery::actions.selectPreviousImage">' . $arrow( 'prev' ) . '</button>'
			. '<button type="button" class="yt-dlg__arrow is-next" aria-label="' . esc_attr__( 'Next picture', 'yummy-treats' ) . '" aria-disabled="false" data-wp-bind--aria-disabled="woocommerce/product-gallery::context.isDisabledNext" data-wp-on--click="woocommerce/product-gallery::actions.selectNextImage">' . $arrow( 'next' ) . '</button>'
			. '<div class="yt-dlg__pills" role="group" aria-label="' . esc_attr__( 'Pictures', 'yummy-treats' ) . '">' . $pills . '</div>';
		$end      = strrpos( $html, '</dialog>' );
		if ( false !== $end ) {
			$html = substr_replace( $html, $controls, $end, 0 );
		}
	}
	return $html;
}
add_filter( 'render_block_woocommerce/product-gallery', 'yt_gallery_dialog_extras', 200, 3 );
