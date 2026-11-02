/**
 * Scroll reveals. Elements with .yt-reveal / .yt-reveal-stagger fade up once when ~15% visible.
 * Reduced-motion users get everything visible immediately (CSS handles that too).
 */
(function () {
	var els = document.querySelectorAll('.yt-reveal, .yt-reveal-stagger');
	if (!els.length) { return; }
	var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	if (reduce || !('IntersectionObserver' in window)) {
		els.forEach(function (el) { el.classList.add('is-in'); });
		return;
	}
	var io = new IntersectionObserver(function (entries) {
		entries.forEach(function (e) {
			if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); }
		});
	}, { rootMargin: '0px 0px -10% 0px', threshold: 0.15 });
	els.forEach(function (el) { io.observe(el); });
})();
