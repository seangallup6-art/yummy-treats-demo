/**
 * Shop behaviours.
 * 1. Variation buttons (R4): the classic variable-product <select> stays in the DOM (WooCommerce's own
 *    variation script keeps driving price/stock from it); we draw a row of buttons and proxy clicks to it.
 * 2. Buy box (board P4-A): one price line that follows the size, a stock pill, "Pick a size first" until a size
 *    is chosen, and Add to cart without leaving the page.
 * 2b. After Add to cart (board P6-C): one toast, bottom left, that counts repeated adds instead of stacking.
 * 3. Free-shipping progress bar on the cart page (reads the cart via the Store API).
 * 4 to 9: stockist chips, phone sticky bar, checkout keypad, drawer inert, Copy button, product card button labels.
 */
(function () {
	var i18n = window.ytShop || {};

	/* 1. Variation buttons ------------------------------------------------- */
	function perKgLabel(select, value) {
		// Variation data is on the form; find the matching variation's per-kg text.
		var form = select.closest('form.variations_form');
		if (!form) { return ''; }
		var attr = select.getAttribute('name');
		var data;
		try { data = JSON.parse(form.getAttribute('data-product_variations') || 'null'); } catch (e) { data = null; }
		if (!data) { return ''; }
		for (var i = 0; i < data.length; i++) {
			if (data[i].attributes && data[i].attributes[attr] === value) {
				var tmp = document.createElement('div');
				tmp.innerHTML = data[i].price_html || '';
				var pk = tmp.querySelector('.yt-perkg');
				return pk ? pk.textContent : '';
			}
		}
		return '';
	}

	document.querySelectorAll('form.variations_form .variations select').forEach(function (select) {
		var row = select.closest('tr') || select.parentNode;
		var wrap = document.createElement('div');
		wrap.className = 'yt-variations';
		wrap.setAttribute('role', 'group');
		var labelEl = row.querySelector('label');
		if (labelEl) { wrap.setAttribute('aria-label', labelEl.textContent); }

		Array.prototype.forEach.call(select.options, function (opt) {
			if (!opt.value) { return; }
			var b = document.createElement('button');
			b.type = 'button';
			b.className = 'yt-variations__btn';
			b.dataset.value = opt.value;
			var sub = perKgLabel(select, opt.value);
			b.innerHTML = '<span>' + opt.text + '</span>' + (sub ? '<small>' + sub + '</small>' : '');
			// Tapping the chosen size again unpicks it (there is no separate Clear link).
			b.addEventListener('click', function () {
				select.value = select.value === opt.value ? '' : opt.value;
				select.dispatchEvent(new Event('change', { bubbles: true }));
			});
			wrap.appendChild(b);
		});

		var sync = function () {
			wrap.querySelectorAll('.yt-variations__btn').forEach(function (b) {
				var opt = select.querySelector('option[value="' + b.dataset.value.replace(/"/g, '\\"') + '"]');
				b.setAttribute('aria-pressed', select.value === b.dataset.value ? 'true' : 'false');
				b.classList.toggle('is-unavailable', !!(opt && opt.disabled));
				b.disabled = !!(opt && opt.disabled);
			});
		};
		select.addEventListener('change', sync);
		var form = select.closest('form.variations_form');
		if (form) { form.addEventListener('woocommerce_update_variation_values', sync); }
		select.classList.add('screen-reader-text');
		select.setAttribute('tabindex', '-1');
		select.parentNode.insertBefore(wrap, select.nextSibling);
		sync();
	});

	/* 2. Buy box --------------------------------------------------------------- */
	var form = document.querySelector('.yt-buybox form.cart');
	var buyBtn = form && form.querySelector('.single_add_to_cart_button');
	var $ = window.jQuery;

	// The button says what to do: "Pick a size first" until a size is chosen (WooCommerce marks that with a
	// .disabled class), then "Add to cart". It is also truly disabled until then, so a press does nothing instead
	// of WooCommerce's "Please select some product options" browser alert.
	if (buyBtn && form.classList.contains('variations_form')) {
		var label = function () {
			var waiting = buyBtn.classList.contains('disabled');
			var text = waiting ? i18n.pickSize : i18n.addToCart;
			if (text && buyBtn.textContent !== text) { buyBtn.textContent = text; }
			if (buyBtn.disabled !== waiting) { buyBtn.disabled = waiting; }
		};
		new MutationObserver(label).observe(buyBtn, { attributes: true, attributeFilter: ['class'] });
		label();
	}

	// One price line: the price block under the title shows the chosen size's price, and goes back to the
	// range when the size is unpicked. WooCommerce's own variation price under the sizes is hidden (CSS).
	var priceBox = document.querySelector('.yt-buybox .wp-block-woocommerce-product-price .wc-block-components-product-price');
	if (form && priceBox && $ && form.classList.contains('variations_form')) {
		var range = priceBox.innerHTML;
		$(form).on('found_variation', function (e, variation) {
			if (!variation || !variation.price_html) { return; }
			var tmp = document.createElement('div');
			tmp.innerHTML = variation.price_html;
			var inner = tmp.querySelector('.price');
			priceBox.innerHTML = (inner || tmp).innerHTML;
		}).on('reset_data hide_variation', function () {
			if (priceBox.innerHTML !== range) { priceBox.innerHTML = range; }
		});
	}

	// Stock pill before a size is chosen: "Every size in stock" when that is true. After a choice WooCommerce
	// prints the size's own stock line, styled as the same pill.
	if (form && form.classList.contains('variations_form')) {
		var variations;
		try { variations = JSON.parse(form.getAttribute('data-product_variations') || 'null'); } catch (e) { variations = null; }
		var wrapEl = form.querySelector('.single_variation_wrap');
		if (variations && variations.length && wrapEl && i18n.allInStock && variations.every(function (v) { return v.is_in_stock; })) {
			var pill = document.createElement('p');
			pill.className = 'stock in-stock yt-stockall';
			pill.textContent = i18n.allInStock;
			wrapEl.insertBefore(pill, wrapEl.firstChild);
			var syncPill = function () { pill.hidden = !!(form.querySelector('input.variation_id') || {}).value; };
			if ($) { $(form).on('found_variation reset_data hide_variation show_variation', function () { setTimeout(syncPill, 0); }); }
			syncPill();
		}
	}

	// Add to cart without a page load: WooCommerce's own AJAX endpoint (the one its shop buttons use), then the
	// same "added_to_cart" event, which the mini-cart turns into a refresh. If anything goes wrong, the form is
	// sent the normal way so WooCommerce can show its message.
	if (form && buyBtn && i18n.addUrl && window.fetch && !form.classList.contains('grouped_form')) {
		var sendNormally = false;
		form.addEventListener('submit', function (e) {
			if (sendNormally) { return; }
			var idField = form.querySelector('input.variation_id');
			var id = idField ? idField.value : (buyBtn.value || (form.querySelector('[name="add-to-cart"]') || {}).value);
			if (!id || id === '0' || buyBtn.classList.contains('disabled')) { return; }
			e.preventDefault();
			if (buyBtn.classList.contains('is-busy')) { return; }
			// Name the item now: the shopper may pick another size before the answer comes back.
			var title = document.querySelector('.yt-buybox .wp-block-post-title');
			var size = form.querySelector('.yt-variations__btn[aria-pressed="true"] span');
			var item = (title ? title.textContent.trim() : '') + (size ? ', ' + size.textContent.trim() : '');
			holdToast();
			var qtyEl = form.querySelector('input.qty');
			var data = new FormData();
			data.append('product_id', id);
			data.append('quantity', qtyEl ? qtyEl.value : '1');
			buyBtn.classList.add('is-busy');
			buyBtn.setAttribute('aria-disabled', 'true');
			var done = function () { buyBtn.classList.remove('is-busy'); buyBtn.removeAttribute('aria-disabled'); };
			fetch(i18n.addUrl, { method: 'POST', body: data, credentials: 'same-origin' })
				.then(function (r) { return r.json(); })
				.then(function (res) {
					done();
					if (!res || res.error) { throw new Error('add failed'); }
					if ($) {
						$(document.body).trigger('added_to_cart', [res.fragments, res.cart_hash, $(buyBtn)]);
					} else {
						document.body.dispatchEvent(new CustomEvent('wc-blocks_added_to_cart', { bubbles: true }));
					}
					addedToast(item);
				})
				.catch(function () {
					done();
					armToast();
					sendNormally = true;
					if (form.requestSubmit) { form.requestSubmit(buyBtn); } else { buyBtn.click(); }
				});
		});
	}

	/* 2b. Toast ----------------------------------------------------------------- */
	// One toast at a time. Each add while it is up bumps the count ("Added 3 items · ฿740"), names the latest
	// item and restarts the timer; it stays while the pointer or focus is on it.
	var toastEl, toastTimer, toastCount = 0;
	function cartTotal() {
		var t = document.querySelector('.yt-cartpill__total');
		return t ? t.textContent.trim() : '';
	}
	function toastTitle() {
		if (!toastEl) { return; }
		var head = toastCount > 1 ? (i18n.addedMany || 'Added %d items').replace('%d', toastCount) : (i18n.added || 'Added to cart');
		var total = toastCount > 1 ? cartTotal() : '';
		toastEl.querySelector('.yt-toast__title').textContent = head + (total ? ' · ' + total : '');
	}
	function hideToast() {
		if (toastEl && !toastEl.matches(':hover, :focus-within')) { toastEl.hidden = true; toastCount = 0; } else { armToast(); }
	}
	function armToast() { clearTimeout(toastTimer); if (toastEl && !toastEl.hidden) { toastTimer = setTimeout(hideToast, 5000); } }
	// While another add is on its way, keep the toast up so the count carries on.
	function holdToast() { clearTimeout(toastTimer); }
	function addedToast(item) {
		if (!toastEl) {
			toastEl = document.createElement('div');
			toastEl.className = 'yt-toast';
			toastEl.hidden = true;
			toastEl.setAttribute('data-no-dynamic-translation', ''); // text arrives already translated (ytShop)
			toastEl.setAttribute('role', 'status');
			toastEl.innerHTML = '<p class="yt-toast__title"></p><p class="yt-toast__item"></p>';
			if (i18n.checkoutUrl) {
				var go = document.createElement('a');
				go.className = 'yt-toast__go';
				go.href = i18n.checkoutUrl;
				go.textContent = i18n.checkout || 'Checkout';
				toastEl.appendChild(go);
			}
			document.body.appendChild(toastEl);
			var totalEl = document.querySelector('.yt-cartpill__total');
			if (totalEl) { new MutationObserver(toastTitle).observe(totalEl, { childList: true, characterData: true, subtree: true }); }
		}
		toastCount = toastEl.hidden ? 1 : toastCount + 1;
		toastEl.querySelector('.yt-toast__item').textContent = item || '';
		toastTitle();
		toastEl.hidden = false;
		armToast();
	}

	// Product cards (shop, home, "goes well with"): note which card was pressed, then show the toast when
	// WooCommerce reports the add. Without a recent press the event is ignored (quantity changes in the drawer).
	var pending = null;
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('.wc-block-components-product-button .wp-block-button__link, .wc-block-components-product-button__button, .add_to_cart_button');
		if (!btn || btn.getAttribute('aria-disabled') === 'true' || btn.tagName === 'A' && !btn.classList.contains('ajax_add_to_cart')) { return; }
		var card = btn.closest('.wc-block-product, li.product, .wp-block-post');
		var title = card && card.querySelector('.wp-block-post-title, .wc-block-components-product-name, h2, h3');
		pending = { name: title ? title.textContent.trim() : '', at: Date.now() };
	});
	document.body.addEventListener('wc-blocks_added_to_cart', function () {
		if (pending && Date.now() - pending.at < 10000) { addedToast(pending.name); pending = null; }
	});

	/* 3. Free shipping bar ------------------------------------------------------ */
	// Reads WooCommerce's own cart store (wc/store/cart), which the cart block keeps current, so the bar follows
	// quantity changes instantly and costs no extra requests. Without the store it asks the Store API once.
	var bar = document.querySelector('.yt-freebar');
	if (bar) {
		var threshold = parseFloat(bar.dataset.threshold || '0');
		var fill = bar.querySelector('.yt-freebar__fill');
		var msg = bar.querySelector('.yt-freebar__msg');
		var fmt = function (n) { return '฿' + Math.ceil(n).toLocaleString('en-US'); };
		var render = function (totals) {
			var minor = typeof totals.currency_minor_unit === 'number' ? totals.currency_minor_unit : parseInt(totals.currency_minor_unit || '0', 10);
			var items = parseInt(totals.total_items || '0', 10) / Math.pow(10, minor);
			var pct = threshold > 0 ? Math.min(100, Math.round(items / threshold * 100)) : 100;
			if (fill) { fill.style.width = pct + '%'; }
			if (msg) {
				msg.textContent = items >= threshold
					? (i18n.freeReached || 'You have free delivery!')
					: (i18n.freeLeft || 'Add %s more for free Bangkok delivery').replace('%s', fmt(threshold - items));
			}
			bar.hidden = false;
		};
		var data = window.wp && window.wp.data;
		var cartStore = data && data.select ? data.select('wc/store/cart') : null;
		if (cartStore && cartStore.getCartTotals) {
			var last = '';
			var sync = function () {
				var totals = cartStore.getCartTotals();
				var key = totals ? totals.total_items + '|' + totals.currency_minor_unit : '';
				if (totals && key !== last) { last = key; render(totals); }
			};
			data.subscribe(sync, 'wc/store/cart');
			sync();
		} else if (window.fetch) {
			fetch((window.wpApiSettings && window.wpApiSettings.root || '/wp-json/') + 'wc/store/v1/cart', { credentials: 'same-origin' })
				.then(function (r) { return r.json(); })
				.then(function (cart) { render(cart.totals); })
				.catch(function () { bar.hidden = true; });
		}
	}
})();

/* 4. Stockist type chips --------------------------------------------------- */
(function () {
	var root = document.querySelector('[data-yt-stockists]');
	if (!root) { return; }
	root.addEventListener('click', function (e) {
		var chip = e.target.closest('.yt-chip');
		if (!chip) { return; }
		var type = chip.dataset.type || '';
		root.querySelectorAll('.yt-chip').forEach(function (c) { c.setAttribute('aria-pressed', c === chip ? 'true' : 'false'); });
		root.querySelectorAll('.wp-block-post').forEach(function (card) {
			card.hidden = !!(type && card.dataset.type !== type);
		});
		root.querySelectorAll('.yt-stockists__group').forEach(function (g) {
			g.hidden = !g.querySelector('.wp-block-post:not([hidden])');
		});
	});
})();

/* 5. Sticky add-to-cart bar on phones (board B32) ------------------------------------------------
 * Shows the product name, the current price and the same action as the buy button once that button
 * has scrolled out of view. Tapping it scrolls back to the size buttons when a size is still needed,
 * otherwise it presses the real button, so there is only one add-to-cart code path. */
(function () {
	var buy = document.querySelector('.yt-buybox form.cart .single_add_to_cart_button');
	var title = document.querySelector('.yt-buybox .wp-block-post-title');
	if (!buy || !title) { return; }
	var priceEl = document.querySelector('.yt-buybox .wc-block-components-product-price, .yt-buybox .wp-block-woocommerce-product-price');
	var bar = document.createElement('div');
	bar.className = 'yt-stickybar';
	bar.setAttribute('data-no-dynamic-translation', ''); // copies text that is already translated
	bar.setAttribute('aria-hidden', 'true');
	bar.innerHTML = '<div class="yt-stickybar__info"><span class="yt-stickybar__name"></span><span class="yt-stickybar__price"></span></div><button type="button" class="wp-element-button" tabindex="-1"></button>';
	bar.querySelector('.yt-stickybar__name').textContent = title.textContent.trim();
	var btn = bar.querySelector('button');
	var price = bar.querySelector('.yt-stickybar__price');
	function sync() {
		btn.textContent = buy.textContent.trim();
		var varPrice = document.querySelector('.yt-buybox .woocommerce-variation-price .price');
		var src = (varPrice && varPrice.textContent.trim()) ? varPrice : priceEl;
		var amount = src ? src.cloneNode(true) : null;
		if (amount) { amount.querySelectorAll('.yt-perkg, .screen-reader-text').forEach(function (n) { n.remove(); }); }
		price.textContent = amount ? amount.textContent.replace(/\s+/g, ' ').trim() : '';
	}
	btn.addEventListener('click', function () {
		if (buy.classList.contains('disabled') || buy.disabled) {
			var first = document.querySelector('.yt-variations__btn:not([disabled])') || buy;
			first.scrollIntoView({ behavior: 'smooth', block: 'center' });
			setTimeout(function () { first.focus({ preventScroll: true }); }, 400);
		} else {
			buy.click();
		}
	});
	var form = buy.closest('form');
	if (form) {
		form.addEventListener('change', function () { setTimeout(sync, 50); });
		if (window.jQuery) { window.jQuery(form).on('found_variation reset_data', function () { setTimeout(sync, 50); }); }
	}
	sync();
	document.body.appendChild(bar);
	// A scroll check rather than IntersectionObserver: a fast fling or an anchor jump can move the button from
	// below the screen to above it without it ever "intersecting", and the observer would never fire.
	var ticking = false;
	function check() {
		ticking = false;
		var shown = buy.getBoundingClientRect().bottom < 0;
		bar.classList.toggle('is-shown', shown);
		document.body.classList.toggle('yt-stickybar-on', shown && window.innerWidth < 782);
	}
	window.addEventListener('scroll', function () {
		if (!ticking) { ticking = true; window.requestAnimationFrame(check); }
	}, { passive: true });
	check();
})();

/* 6. Checkout keyboards: numeric keypad for postcodes (the block checkout re-renders, so keep re-applying). */
(function () {
	if (!document.querySelector('.wp-block-woocommerce-checkout')) { return; }
	function apply() {
		document.querySelectorAll('#shipping-postcode, #billing-postcode').forEach(function (el) {
			if (el.getAttribute('inputmode') !== 'numeric') { el.setAttribute('inputmode', 'numeric'); }
		});
	}
	apply();
	new MutationObserver(apply).observe(document.body, { childList: true, subtree: true });
})();

/* 7. Mini-cart drawer: WooCommerce hides it with aria-hidden but leaves its buttons focusable. Mirror aria-hidden as
 * `inert` so keyboard users cannot tab into the closed drawer (axe: aria-hidden-focus). */
(function () {
	var drawer = document.querySelector('.wc-block-mini-cart__drawer');
	if (!drawer) { return; }
	function sync() {
		if (drawer.getAttribute('aria-hidden') === 'true') { drawer.setAttribute('inert', ''); } else { drawer.removeAttribute('inert'); }
	}
	sync();
	new MutationObserver(sync).observe(drawer, { attributes: true, attributeFilter: ['aria-hidden'] });
})();

/* 8. Order confirmation (board P9-A): a Copy button on the bank account number. */
(function () {
	var i18n = window.ytShop || {};
	var number = document.querySelector('.yt-paybox .account_number strong');
	if (!number || !navigator.clipboard) { return; }
	var btn = document.createElement('button');
	btn.type = 'button';
	btn.className = 'yt-copy';
	btn.setAttribute('data-no-dynamic-translation', '');
	btn.innerHTML = '<span class="yt-copy__text"></span><span class="screen-reader-text"></span>';
	var text = btn.querySelector('.yt-copy__text');
	text.textContent = i18n.copy || 'Copy';
	btn.querySelector('.screen-reader-text').textContent = ' ' + (i18n.copyWhat || 'account number');
	var live = document.createElement('span');
	live.className = 'screen-reader-text';
	live.setAttribute('role', 'status');
	btn.addEventListener('click', function () {
		navigator.clipboard.writeText(number.textContent.trim()).then(function () {
			text.textContent = i18n.copied || 'Copied';
			live.textContent = i18n.copied || 'Copied';
			setTimeout(function () { text.textContent = i18n.copy || 'Copy'; live.textContent = ''; }, 2000);
		});
	});
	number.parentNode.appendChild(btn);
	number.parentNode.appendChild(live);
})();

/* 9. Product card buttons: once an item is in the cart WooCommerce changes the button text to "1 in cart" but keeps
 * aria-label="Add to cart: …", so the spoken name no longer starts with the words on screen (axe:
 * label-content-name-mismatch, WCAG 2.5.3). Keep the label as "<words on the button>: <product name>". Grids can be
 * redrawn by the shop filters, so this re-runs after DOM changes (batched to one pass per frame). */
(function () {
	function nameOf(btn) {
		if (btn.dataset.ytName) { return btn.dataset.ytName; }
		var card = btn.closest('.wc-block-product, li.product, .wp-block-post');
		var title = card && card.querySelector('.wp-block-post-title, .woocommerce-loop-product__title');
		var name = title ? title.textContent.replace(/\s+/g, ' ').trim() : '';
		if (!name) {
			var label = btn.getAttribute('aria-label') || '';
			var at = label.indexOf(':');
			name = at > -1 ? label.slice(at + 1).replace(/[“”"]/g, '').trim() : '';
		}
		btn.dataset.ytName = name;
		return name;
	}
	function sync() {
		document.querySelectorAll('.wc-block-components-product-button__button[aria-label]').forEach(function (btn) {
			var text = btn.textContent.replace(/\s+/g, ' ').trim();
			var name = nameOf(btn);
			if (!text || !name) { return; }
			var label = text + ': ' + name;
			if (btn.getAttribute('aria-label') !== label) { btn.setAttribute('aria-label', label); }
		});
	}
	var queued = false;
	function queue() {
		if (queued) { return; }
		queued = true;
		window.requestAnimationFrame(function () { queued = false; sync(); });
	}
	sync();
	new MutationObserver(queue).observe(document.body, { childList: true, subtree: true, characterData: true });
})();
