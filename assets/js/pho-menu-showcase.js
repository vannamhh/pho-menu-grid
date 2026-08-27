/**
 * Pho Menu Showcase — dietary guide accordion.
 *
 * Tab switching and drag-scroll live in PhoMenuNav.
 */
(function (Nav) {
	'use strict';

	if (!Nav) {
		return;
	}

	/**
	 * Attach the accordion behaviour to one showcase instance.
	 *
	 * @param {HTMLElement} wrapper Element wrapper.
	 */
	function setup(wrapper) {
		var tabs = Nav.createTabs(wrapper);

		if (tabs) {
			Nav.createCategoryPicker(wrapper, tabs);
		}

		wrapper.querySelectorAll('.accordion-header').forEach(function (header) {
			header.addEventListener('click', function (e) {
				e.preventDefault();

				var item = header.closest('.accordion-item');
				var content = item && item.querySelector('.accordion-content');

				if (!content) {
					return;
				}

				var isOpen = item.classList.toggle('is-open');

				header.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
				// Animating to a pixel height is the only way to transition
				// from a content-sized box; cleared again on close.
				content.style.maxHeight = isOpen ? content.scrollHeight + 'px' : '';
			});
		});
	}

	Nav.register('.pho-menu-showcase-wrapper', setup);
})(window.PhoMenuNav);
