/**
 * Pho Menu — shared navigation behaviour.
 *
 * Owns everything the grid and showcase elements have in common: the tab
 * controller, horizontal drag-to-scroll, the overflow mask state classes and
 * the initialisation hook used by the UX Builder preview.
 */
window.PhoMenuNav = (function () {
	'use strict';

	/**
	 * Run a callback at most once per animation frame.
	 *
	 * @param {Function} fn Callback.
	 * @return {Function} Throttled callback.
	 */
	function rafThrottle(fn) {
		var pending = false;

		return function () {
			if (pending) {
				return;
			}

			// The flag is raised before scheduling rather than storing the frame
			// id afterwards, so a synchronously-invoked callback cannot leave a
			// stale id behind and wedge the throttle shut.
			pending = true;

			window.requestAnimationFrame(function () {
				pending = false;
				fn();
			});
		};
	}

	/**
	 * Centre a tab button within its scroll container.
	 *
	 * Uses scrollTo on the nav itself rather than scrollIntoView, which would
	 * also scroll the page vertically.
	 *
	 * @param {HTMLElement} btn Tab button.
	 */
	function scrollTabIntoView(btn) {
		var nav = btn.closest('.menu-nav');

		if (!nav) {
			return;
		}

		var navRect = nav.getBoundingClientRect();
		var btnRect = btn.getBoundingClientRect();
		var left = nav.scrollLeft + (btnRect.left - navRect.left) - navRect.width / 2 + btnRect.width / 2;

		nav.scrollTo({ left: left, behavior: 'smooth' });
	}

	/**
	 * Wire drag-to-scroll and the fade-mask state classes onto a nav element.
	 *
	 * @param {HTMLElement} nav Scroll container.
	 */
	function initDragScroll(nav) {
		if (!nav) {
			return;
		}

		var isDown = false;
		var isDragging = false;
		var startX = 0;
		var startScroll = 0;

		function applyMask() {
			var maxScroll = nav.scrollWidth - nav.clientWidth;

			nav.classList.remove('is-start', 'is-end', 'is-middle', 'no-scroll');

			if (maxScroll <= 5) {
				nav.classList.add('no-scroll');
				return;
			}

			if (nav.scrollLeft <= 2) {
				nav.classList.add('is-start');
			} else if (nav.scrollLeft >= maxScroll - 2) {
				nav.classList.add('is-end');
			} else {
				nav.classList.add('is-middle');
			}
		}

		// Throttle the high-frequency sources, but seed the initial state
		// synchronously: requestAnimationFrame never fires in a hidden tab, so a
		// throttled first call would leave the mask unset until the first scroll.
		var updateMask = rafThrottle(applyMask);

		nav.addEventListener('scroll', updateMask, { passive: true });
		window.addEventListener('resize', updateMask);
		applyMask();

		nav.addEventListener('mousedown', function (e) {
			isDown = true;
			isDragging = false;
			nav.classList.add('mouse-dragging');
			startX = e.pageX - nav.offsetLeft;
			startScroll = nav.scrollLeft;
		});

		['mouseleave', 'mouseup'].forEach(function (type) {
			nav.addEventListener(type, function () {
				isDown = false;
				nav.classList.remove('mouse-dragging');
			});
		});

		nav.addEventListener('mousemove', function (e) {
			if (!isDown) {
				return;
			}

			e.preventDefault();

			var walk = (e.pageX - nav.offsetLeft - startX) * 2;

			if (Math.abs(walk) > 5) {
				isDragging = true;
			}

			nav.scrollLeft = startScroll - walk;
		});

		// Swallow the click that ends a drag so it does not switch tabs.
		nav.addEventListener(
			'click',
			function (e) {
				if (isDragging) {
					e.preventDefault();
					e.stopPropagation();
				}
			},
			true
		);
	}

	/**
	 * Show a panel with its fade transition.
	 *
	 * A transition cannot run on an element that was `display: none` in the same
	 * frame, so the `hidden` attribute is dropped first and a reflow forced
	 * before the class that animates opacity is added. Reading `offsetWidth` is
	 * used rather than requestAnimationFrame because rAF does not fire while the
	 * tab is in the background.
	 *
	 * @param {HTMLElement} panel Tab panel.
	 */
	function reveal(panel) {
		panel.removeAttribute('hidden');
		void panel.offsetWidth;
		panel.classList.add('is-active');
	}

	/**
	 * Take a panel out of the layout and the accessibility tree.
	 *
	 * Hidden immediately rather than faded out: two panels in flow at once would
	 * stretch the page for the length of the transition and then snap it back.
	 *
	 * @param {HTMLElement} panel Tab panel.
	 */
	function conceal(panel) {
		panel.classList.remove('is-active');
		panel.setAttribute('hidden', '');
	}

	/**
	 * Build a tab controller for one element instance.
	 *
	 * Panels are toggled with the `is-active` class and the `hidden` attribute
	 * rather than inline `display`, so the fade is a plain CSS transition and
	 * the active panel can be found without parsing a style string.
	 *
	 * @param {HTMLElement} wrapper Element wrapper.
	 * @return {Object|null} Controller, or null when there are no tabs.
	 */
	function createTabs(wrapper) {
		var buttons = Array.prototype.slice.call(wrapper.querySelectorAll('.nav-item'));

		if (!buttons.length) {
			return null;
		}

		var index = Math.max(
			0,
			buttons.findIndex(function (btn) {
				return btn.classList.contains('active');
			})
		);

		function activate(target) {
			var next = typeof target === 'number' ? buttons[target] : target;

			if (!next || next.classList.contains('active')) {
				return;
			}

			index = buttons.indexOf(next);

			buttons.forEach(function (btn) {
				var active = btn === next;
				var panel = document.getElementById(btn.getAttribute('data-target'));

				btn.classList.toggle('active', active);
				btn.setAttribute('aria-selected', active ? 'true' : 'false');
				btn.setAttribute('tabindex', active ? '0' : '-1');

				if (!panel) {
					return;
				}

				if (active) {
					reveal(panel);
				} else {
					conceal(panel);
				}
			});

			scrollTabIntoView(next);
		}

		buttons.forEach(function (btn) {
			btn.addEventListener('click', function (e) {
				e.preventDefault();
				activate(btn);
			});

			// Roving tabindex: arrow keys move between tabs.
			btn.addEventListener('keydown', function (e) {
				var offset = 0;

				if (e.key === 'ArrowRight') {
					offset = 1;
				} else if (e.key === 'ArrowLeft') {
					offset = -1;
				} else {
					return;
				}

				e.preventDefault();

				var next = buttons[(buttons.indexOf(btn) + offset + buttons.length) % buttons.length];
				activate(next);
				next.focus();
			});
		});

		initDragScroll(wrapper.querySelector('.menu-nav'));

		return {
			buttons: buttons,
			activate: activate,
			next: function () {
				activate((index + 1) % buttons.length);
			},
			current: function () {
				return index;
			}
		};
	}

	/**
	 * Initialise every not-yet-initialised wrapper matching a selector.
	 *
	 * @param {string}   selector Wrapper selector.
	 * @param {Function} setup    Called once per wrapper.
	 */
	function each(selector, setup) {
		document.querySelectorAll(selector + ':not(.pho-initialized)').forEach(function (wrapper) {
			wrapper.classList.add('pho-initialized');
			setup(wrapper);
		});
	}

	/**
	 * Register an element type.
	 *
	 * Runs once on DOM ready. The MutationObserver is only attached inside the
	 * UX Builder preview, where elements arrive over AJAX — on the front end it
	 * would fire on every DOM insertion made by any script on the page.
	 *
	 * @param {string}   selector Wrapper selector.
	 * @param {Function} setup    Called once per wrapper.
	 */
	function register(selector, setup) {
		function run() {
			each(selector, setup);
		}

		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', run);
		} else {
			run();
		}

		if (!isBuilderPreview()) {
			return;
		}

		new MutationObserver(rafThrottle(run)).observe(document.body, {
			childList: true,
			subtree: true
		});
	}

	/**
	 * Whether the page is being rendered inside the UX Builder preview iframe.
	 *
	 * @return {boolean}
	 */
	function isBuilderPreview() {
		var query = window.location.search;

		// Matched on the editor's own query flags rather than "am I in an
		// iframe": any third-party embed of the site would otherwise pay for the
		// observer on every DOM insertion.
		return query.indexOf('uxb_iframe') !== -1 || query.indexOf('customize_changeset_uuid') !== -1;
	}

	return {
		register: register,
		createTabs: createTabs,
		initDragScroll: initDragScroll,
		scrollTabIntoView: scrollTabIntoView,
		rafThrottle: rafThrottle
	};
})();
