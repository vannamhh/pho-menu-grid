/**
 * Pho Menu Grid — tab autoplay.
 *
 * Tab switching, drag-scroll and the fade transition live in PhoMenuNav and
 * CSS respectively; this file only adds the autoplay timer.
 */
(function (Nav) {
	'use strict';

	if (!Nav) {
		return;
	}

	var instances = [];

	/**
	 * Attach autoplay to one grid instance.
	 *
	 * @param {HTMLElement} wrapper Element wrapper.
	 */
	function setup(wrapper) {
		var tabs = Nav.createTabs(wrapper);

		if (!tabs) {
			return;
		}

		var duration = parseInt(wrapper.getAttribute('data-auto-play'), 10) || 0;

		if (duration <= 0 || tabs.buttons.length < 2) {
			return;
		}

		var timer = null;

		function stop() {
			window.clearInterval(timer);
			timer = null;
		}

		function start() {
			stop();

			timer = window.setInterval(function () {
				if (!document.hidden) {
					tabs.next();
				}
			}, duration);
		}

		// Pause while the visitor is interacting with the element.
		['mouseenter', 'focusin'].forEach(function (type) {
			wrapper.addEventListener(type, stop);
		});
		['mouseleave', 'focusout'].forEach(function (type) {
			wrapper.addEventListener(type, start);
		});
		wrapper.addEventListener('touchstart', stop, { passive: true });
		wrapper.addEventListener('touchend', start, { passive: true });

		instances.push({ start: start, stop: stop });
		start();
	}

	// Registered once for the whole document rather than once per instance.
	document.addEventListener('visibilitychange', function () {
		instances.forEach(function (instance) {
			if (document.hidden) {
				instance.stop();
			} else {
				instance.start();
			}
		});
	});

	Nav.register('.pho-menu-grid-wrapper', setup);
})(window.PhoMenuNav);
