/**
 * Shop behaviours.
 * 1. Variation buttons (R4): the classic variable-product <select> stays in the DOM (WooCommerce's own
 *    variation script keeps driving price/stock from it); we draw a row of buttons and proxy clicks to it.
 * 2. Added-to-cart toast for the Product Collection "Add to cart" buttons.
 * 3. Free-shipping progress bar on the cart page (reads the cart via the Store API).
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
			b.addEventListener('click', function () {
				select.value = opt.value;
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

	/* 2. Toast ----------------------------------------------------------------- */
	var toastTimer;
	function toast(text) {
		var t = document.querySelector('.yt-toast');
		if (!t) {
			t = document.createElement('div');
			t.className = 'yt-toast';
			t.setAttribute('data-no-dynamic-translation', ''); // text arrives already translated (ytShop)
			t.setAttribute('role', 'status');
			document.body.appendChild(t);
		}
		t.textContent = text;
		t.hidden = false;
		clearTimeout(toastTimer);
		toastTimer = setTimeout(function () { t.hidden = true; }, 2400);
	}
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('.wc-block-components-product-button .wp-block-button__link, .add_to_cart_button');
		if (!btn || btn.getAttribute('aria-disabled') === 'true') { return; }
		if (btn.classList.contains('product_type_variable') || btn.getAttribute('href') && !btn.hasAttribute('data-product_id') && !btn.closest('[data-wp-interactive]')) { return; }
		var name = i18n.added || 'Added!';
		setTimeout(function () { toast(name); }, 350);
	});
	document.body.addEventListener('added_to_cart', function () { toast(i18n.added || 'Added!'); });

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
		bar.classList.toggle('is-shown', buy.getBoundingClientRect().bottom < 0);
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
