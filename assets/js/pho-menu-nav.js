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
	 * Whether the reader has asked for reduced motion.
	 *
	 * @return {boolean} True when animation should be skipped.
	 */
	function prefersReducedMotion() {
		return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	}

	/**
	 * Focus an element without letting the browser scroll to it.
	 *
	 * A browser that does not honour `preventScroll` simply behaves as it did
	 * before.
	 *
	 * @param {HTMLElement} el Element to focus.
	 */
	function focusQuietly(el) {
		if (el) {
			el.focus({ preventScroll: true });
		}
	}

	/**
	 * Bring the top of the panel area back into view after a tab switch.
	 *
	 * Panels differ in length, so swapping one for another from halfway down the
	 * page drops the reader into the middle of a list they have not seen the
	 * start of — or, when the incoming panel is the shorter one, at its very end
	 * because the browser clamps the scroll position.
	 *
	 * Only runs once the nav bar has scrolled past the top of the viewport:
	 * pressing a tab while it is still on screen already leaves the reader
	 * looking at the start of the list, and scrolling then would be a pointless
	 * jolt.
	 *
	 * The allowance for a sticky site header comes from `scroll-margin-top` on
	 * the panel wrapper rather than from arithmetic here, so this knows nothing
	 * about the active theme's header — including whether it is on screen at
	 * this instant.
	 *
	 * @param {HTMLElement} wrapper Element wrapper.
	 */
	function scrollPanelsIntoView(wrapper) {
		var nav = wrapper.querySelector('.menu-nav-container');
		var panels = wrapper.querySelector('.tab-panels-wrapper');

		if (!nav || !panels || nav.getBoundingClientRect().top >= 0) {
			return;
		}

		panels.scrollIntoView({
			behavior: prefersReducedMotion() ? 'auto' : 'smooth',
			block: 'start'
		});
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

		// Subscribers are told about every switch, whichever control caused it,
		// so a second face on these tabs never has to guess the current state.
		var listeners = [];

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
			scrollPanelsIntoView(wrapper);

			listeners.forEach(function (fn) {
				fn(next);
			});
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

				// Quietly: activate() has already placed the page — centring the
				// tab in its own strip, and scrolling the panels up when the bar
				// is off screen — and the browser's own scroll-into-view for the
				// newly focused button would pull against that.
				focusQuietly(next);
			});
		});

		initDragScroll(wrapper.querySelector('.menu-nav'));

		return {
			buttons: buttons,
			activate: activate,
			onChange: function (fn) {
				listeners.push(fn);
			},
			next: function () {
				activate((index + 1) % buttons.length);
			},
			current: function () {
				return index;
			}
		};
	}

	/**
	 * Build the floating category picker for one element instance.
	 *
	 * The nav bar only exists at the top of the element, so a reader partway
	 * down a long panel cannot change category without scrolling all of it back.
	 * The picker is a second face on the same tab controller — it never decides
	 * which panel is visible, it only calls `activate()` and reflects what comes
	 * back through `onChange`.
	 *
	 * @param {HTMLElement} wrapper Element wrapper.
	 * @param {Object}      tabs    Controller returned by createTabs().
	 * @return {Object|null} Controller, or null when the markup is absent.
	 */
	function createCategoryPicker(wrapper, tabs) {
		var picker = wrapper.querySelector('.menu-picker');

		if (!picker || !tabs) {
			return null;
		}

		var toggle = picker.querySelector('.menu-picker-toggle');
		var sheet = picker.querySelector('.menu-picker-sheet');
		var backdrop = picker.querySelector('.menu-picker-backdrop');
		var label = picker.querySelector('.menu-picker-toggle-label');
		var items = Array.prototype.slice.call(picker.querySelectorAll('.menu-picker-item'));
		var navContainer = wrapper.querySelector('.menu-nav-container');

		if (!toggle || !sheet || !backdrop || !navContainer || !items.length) {
			return null;
		}

		var isOpen = false;
		var savedOverflow = '';
		var scrollLocked = false;

		/**
		 * Stop the page scrolling behind the open sheet — where that is free.
		 *
		 * Taking a classic scrollbar away is never free. Let the viewport grow
		 * into the strip it frees and every `position: fixed` thing on the page
		 * lurches sideways by its width. Reserve the strip with
		 * `scrollbar-gutter: stable` instead and the content box moves by a
		 * fraction of a pixel — measured at 1px here, which is enough to re-wrap
		 * this element's container-query typography and heave the whole article
		 * several pixels up the screen. Neither is a fair price for a lock.
		 *
		 * So the lock is taken only where the scrollbar costs no layout space to
		 * begin with: overlay scrollbars, which is every touch device — and that
		 * is exactly where the sheet covers the screen and a lock earns its
		 * keep. Everywhere else the sheet is a popover hung off a button, and it
		 * dismisses on scroll the way any other popover does.
		 */
		function lockScroll() {
			var root = document.documentElement;

			scrollLocked = window.innerWidth === root.clientWidth;

			if (!scrollLocked) {
				window.addEventListener('scroll', close, { passive: true });
				return;
			}

			savedOverflow = root.style.overflow;
			root.style.overflow = 'hidden';
		}

		function unlockScroll() {
			if (!scrollLocked) {
				window.removeEventListener('scroll', close);
				return;
			}

			document.documentElement.style.overflow = savedOverflow;
			scrollLocked = false;
		}

		function open() {
			if (isOpen) {
				return;
			}

			isOpen = true;

			backdrop.removeAttribute('hidden');
			sheet.removeAttribute('hidden');

			// Same reason as reveal(): a transition cannot run on an element
			// that was `display: none` in this frame.
			void sheet.offsetWidth;

			picker.classList.add('is-open');
			toggle.setAttribute('aria-expanded', 'true');
			lockScroll();

			var current = picker.querySelector('.menu-picker-item.is-active');

			// Focused quietly throughout: every target here is `position:
			// fixed` and already fully on screen, so there is nothing the
			// browser's scroll-into-view could usefully do — and the sheet's
			// list scrolls on a short viewport, where it could do something
			// unwanted.
			focusQuietly(current || items[0]);
		}

		function close() {
			if (!isOpen) {
				return;
			}

			isOpen = false;

			picker.classList.remove('is-open');
			toggle.setAttribute('aria-expanded', 'false');
			backdrop.setAttribute('hidden', '');
			sheet.setAttribute('hidden', '');
			unlockScroll();

			focusQuietly(toggle);
		}

		/**
		 * Keep Tab inside the sheet while it is open.
		 *
		 * `aria-modal` tells assistive technology the rest of the page is inert
		 * but does nothing to the tab order itself. Every focusable node in the
		 * sheet is a button, so they are the whole cycle.
		 *
		 * @param {KeyboardEvent} e Key event.
		 */
		function trapFocus(e) {
			var focusable = sheet.querySelectorAll('button:not([disabled])');

			if (!focusable.length) {
				return;
			}

			var first = focusable[0];
			var last = focusable[focusable.length - 1];

			if (e.shiftKey && document.activeElement === first) {
				e.preventDefault();
				focusQuietly(last);
			} else if (!e.shiftKey && document.activeElement === last) {
				e.preventDefault();
				focusQuietly(first);
			}
		}

		toggle.addEventListener('click', function () {
			if (isOpen) {
				close();
			} else {
				open();
			}
		});

		backdrop.addEventListener('click', close);

		var closeButton = picker.querySelector('.menu-picker-close');

		if (closeButton) {
			closeButton.addEventListener('click', close);
		}

		sheet.addEventListener('keydown', function (e) {
			if (e.key === 'Escape') {
				e.preventDefault();
				close();
			} else if (e.key === 'Tab') {
				trapFocus(e);
			}
		});

		items.forEach(function (item) {
			item.addEventListener('click', function () {
				var target = item.getAttribute('data-target');
				var btn = wrapper.querySelector('.nav-item[data-target="' + target + '"]');

				// Closed before the switch, not after: the smooth scroll
				// activate() starts cannot run while the body is still locked.
				close();

				if (btn) {
					tabs.activate(btn);
				}
			});
		});

		tabs.onChange(function (btn) {
			var target = btn.getAttribute('data-target');

			items.forEach(function (item) {
				var active = item.getAttribute('data-target') === target;
				var name = item.querySelector('.menu-picker-name');

				item.classList.toggle('is-active', active);

				if (!active) {
					item.removeAttribute('aria-current');
					return;
				}

				item.setAttribute('aria-current', 'true');

				if (label && name) {
					label.textContent = name.textContent;
				}
			});
		});

		/**
		 * Decide whether the toggle belongs on screen, and act on it.
		 *
		 * The toggle earns its place only in the stretch where the nav bar has
		 * been scrolled off the top and there is still showcase left to read.
		 *
		 * Measured from geometry rather than read off the observer's entries, so
		 * the same answer can be seeded synchronously below: observer callbacks
		 * are delivered as part of updating the rendering, which a background
		 * tab never does, and a page restored partway down the element would
		 * otherwise carry no state at all until someone looked at it. Same
		 * reasoning as the first applyMask() call.
		 */
		function syncVisibility() {
			var nav = navContainer.getBoundingClientRect();
			var showcase = wrapper.getBoundingClientRect();

			// The bar counts as present while it is on screen *or* still below
			// the fold, so scrolling down towards the element never raises the
			// toggle ahead of it.
			var navPresent = nav.bottom > 0;
			var showcasePresent = showcase.top < window.innerHeight && showcase.bottom > 0;
			var visible = !navPresent && showcasePresent;

			picker.classList.toggle('is-visible', visible);

			if (!visible) {
				close();
			}
		}

		// Both edges that matter — the nav bar leaving the top, the showcase
		// leaving the bottom — are threshold crossings, which is exactly what a
		// default-threshold observer reports. It is only the trigger here; the
		// answer itself comes from syncVisibility().
		var observer = new IntersectionObserver(syncVisibility);

		observer.observe(navContainer);
		observer.observe(wrapper);
		syncVisibility();

		// Rendered hidden so a reader without JavaScript is never shown a button
		// that cannot do anything.
		picker.removeAttribute('hidden');

		var lastWidth = window.innerWidth;

		window.addEventListener(
			'resize',
			rafThrottle(function () {
				// Dismiss the sheet on a real reflow only. A height-only resize
				// is the mobile browser's own chrome sliding in and out, which
				// must not yank the sheet out from under a reader's thumb.
				if (window.innerWidth !== lastWidth) {
					lastWidth = window.innerWidth;
					close();
				}

				syncVisibility();
			})
		);

		return {
			open: open,
			close: close,
			sync: syncVisibility,
			items: items
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
		createCategoryPicker: createCategoryPicker,
		initDragScroll: initDragScroll,
		scrollTabIntoView: scrollTabIntoView,
		rafThrottle: rafThrottle
	};
})();
