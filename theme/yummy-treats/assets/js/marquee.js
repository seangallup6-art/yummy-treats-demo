/**
 * Marquee pause control (WCAG 2.2.2: anything that moves for > 5 s needs a pause).
 * The track is duplicated once so the -50% translate loops seamlessly.
 */
(function () {
	var i18n = window.ytI18n || { pause: 'Pause', play: 'Play' };
	var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	document.querySelectorAll('.yt-marquee').forEach(function (m) {
		var track = m.querySelector('.yt-marquee__track');
		var btn = m.querySelector('.yt-marquee__pause');
		if (!track) { return; }

		if (!track.dataset.cloned) {
			track.innerHTML += track.innerHTML;
			track.dataset.cloned = '1';
		}

		if (!btn) { return; }
		var paused = reduce;
		var render = function () {
			m.classList.toggle('is-paused', paused);
			btn.textContent = paused ? i18n.play : i18n.pause;
			btn.setAttribute('aria-pressed', paused ? 'true' : 'false');
		};
		btn.addEventListener('click', function () { paused = !paused; render(); });
		render();
	});
})();
