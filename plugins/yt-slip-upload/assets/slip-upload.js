/**
 * Slip form: show the chosen file (thumbnail for images), stop files that are too big before they upload,
 * and disable the button while sending so it is not pressed twice.
 */
(function () {
	var form = document.querySelector('.yt-slip__form');
	if (!form) { return; }
	var input = form.querySelector('.yt-slip__file');
	var button = form.querySelector('.yt-slip__submit');
	var hint = form.querySelector('.yt-slip__hint');
	var max = parseInt(form.getAttribute('data-max-bytes') || '0', 10);
	var t = window.ytSlip || {};
	var hintText = hint ? hint.textContent : '';
	var preview = document.createElement('div');
	preview.className = 'yt-slip__preview';
	preview.hidden = true;
	input.insertAdjacentElement('afterend', preview);

	input.addEventListener('change', function () {
		var file = input.files && input.files[0];
		preview.hidden = true;
		preview.innerHTML = '';
		input.setCustomValidity('');
		if (hint) { hint.textContent = hintText; hint.classList.remove('is-error'); }
		if (!file) { return; }
		if (max && file.size > max) {
			input.setCustomValidity(t.tooBig || 'File too large');
			if (hint) { hint.textContent = t.tooBig || 'File too large'; hint.classList.add('is-error'); }
			input.reportValidity();
			return;
		}
		var name = document.createElement('span');
		name.textContent = file.name;
		if (/^image\//.test(file.type) && window.URL) {
			var img = document.createElement('img');
			img.alt = '';
			img.src = URL.createObjectURL(file);
			img.onload = function () { URL.revokeObjectURL(img.src); };
			preview.appendChild(img);
		}
		preview.appendChild(name);
		preview.hidden = false;
	});

	form.addEventListener('submit', function () {
		if (button) {
			button.disabled = true;
			button.setAttribute('aria-busy', 'true');
			button.textContent = t.sending || 'Sending…';
		}
	});
})();
