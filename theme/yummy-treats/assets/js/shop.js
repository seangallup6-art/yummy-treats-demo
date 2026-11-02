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
	var bar = document.querySelector('.yt-freebar');
	if (bar && window.fetch) {
		var threshold = parseFloat(bar.dataset.threshold || '0');
		var fill = bar.querySelector('.yt-freebar__fill');
		var msg = bar.querySelector('.yt-freebar__msg');
		var fmt = function (n) { return '฿' + Math.ceil(n).toLocaleString('en-US'); };
		var update = function () {
			fetch((window.wpApiSettings && window.wpApiSettings.root || '/wp-json/') + 'wc/store/v1/cart', { credentials: 'same-origin' })
				.then(function (r) { return r.json(); })
				.then(function (cart) {
					var minor = typeof cart.totals.currency_minor_unit === 'number' ? cart.totals.currency_minor_unit : parseInt(cart.totals.currency_minor_unit || '0', 10);
					var items = parseInt(cart.totals.total_items || '0', 10) / Math.pow(10, minor);
					var pct = threshold > 0 ? Math.min(100, Math.round(items / threshold * 100)) : 100;
					if (fill) { fill.style.width = pct + '%'; }
					if (msg) {
						msg.textContent = items >= threshold
							? (i18n.freeReached || 'You have free delivery!')
							: (i18n.freeLeft || 'Add %s more for free Bangkok delivery').replace('%s', fmt(threshold - items));
					}
					bar.hidden = false;
				})
				.catch(function () { bar.hidden = true; });
		};
		update();
		// The cart block re-renders after quantity changes; poll lightly while the page is visible.
		setInterval(function () { if (!document.hidden) { update(); } }, 4000);
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
