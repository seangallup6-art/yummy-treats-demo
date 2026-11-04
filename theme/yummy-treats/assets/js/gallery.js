/**
 * Product gallery read-outs (boards P1-B and P3-B).
 *
 * WooCommerce's Product Gallery block keeps the gallery state in its own context ("woocommerce/product-gallery":
 * imageData, selectedImageId, isDialogOpen). Its store is private, so this store only reads that context and
 * offers the few values the theme's added markup needs (see inc/product-page.php):
 *   state.count      "1 / 2" for the strip under the picture and the zoom view's header
 *   state.isCurrent  whether the element's data-image-id is the picture on show
 * plus two small behaviours: close the zoom view from the dark area around the picture, and put focus back on
 * the zoom button when the view closes.
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

const gallery = () => getContext( 'woocommerce/product-gallery' );
const wasOpen = new WeakMap();

store( 'yummy-treats/gallery', {
	state: {
		get count() {
			const { imageData = [], selectedImageId } = gallery();
			const index = Math.max( 0, imageData.indexOf( selectedImageId ) );
			return `${ index + 1 } / ${ imageData.length }`;
		},
		get isCurrent() {
			const { attributes } = getElement();
			return Number( attributes[ 'data-image-id' ] ) === gallery().selectedImageId;
		},
	},
	actions: {
		closeOnBackdrop( event ) {
			if ( event.target !== event.currentTarget ) {
				return;
			}
			gallery().isDialogOpen = false;
			document.body.classList.remove( 'wc-block-product-gallery-dialog-open' );
		},
	},
	callbacks: {
		returnFocus() {
			const { ref } = getElement();
			const open = gallery().isDialogOpen;
			if ( ref && wasOpen.get( ref ) && ! open ) {
				ref.focus( { preventScroll: true } );
			}
			if ( ref ) {
				wasOpen.set( ref, open );
			}
		},
	},
} );
