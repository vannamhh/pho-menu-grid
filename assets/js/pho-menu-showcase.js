document.addEventListener("DOMContentLoaded", () => {
	
	const wrappers = document.querySelectorAll('.pho-menu-showcase-wrapper');

	wrappers.forEach(wrapper => {
		
		// 1. Tab Switching
		const tabButtons = wrapper.querySelectorAll('.nav-item');
		const panels = wrapper.querySelectorAll('.tab-panel');

		tabButtons.forEach(btn => {
			btn.addEventListener('click', (e) => {
				e.preventDefault();

				if (btn.classList.contains('active')) return;

				const targetId = btn.getAttribute('data-target');
				
				// Remove active class
				tabButtons.forEach(b => b.classList.remove('active'));
				btn.classList.add('active');

				// Scroll active tab into view (smooth snap to center)
				btn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });

				// Hide all panels
				panels.forEach(p => p.style.display = 'none');

				// Show target panel
				const targetPanel = wrapper.querySelector('#' + targetId);
				if (targetPanel) {
					targetPanel.style.display = 'block';

					// Trigger reflow/lazy-load for any images inside
					setTimeout(() => {
						window.dispatchEvent(new Event('resize'));
						window.dispatchEvent(new Event('scroll'));
					}, 50);
				}
			});
		});

		// 2. Accordions (Dietary Guide)
		const accordions = wrapper.querySelectorAll('.accordion-header');
		accordions.forEach(acc => {
			acc.addEventListener('click', (e) => {
				e.preventDefault();
				const item = acc.closest('.accordion-item');
				const content = item.querySelector('.accordion-content');
				
				// Toggle logic
				if (item.classList.contains('is-open')) {
					item.classList.remove('is-open');
					content.style.maxHeight = null;
				} else {
					item.classList.add('is-open');
					// Animate max height smoothly based on actual height
					content.style.maxHeight = content.scrollHeight + 40 + "px"; // +40px for safety padding buffer
				}
			});
		});

		// 3. Desktop Drag to Scroll & Overflow Mask (Visual Cue)
		const navWrapper = wrapper.querySelector('.menu-nav');
		function initDragScrollAndMask(navEl) {
			if (!navEl) return;
			let isDown = false;
			let startX;
			let scrollLeft;
			let isDragging = false;

			const updateMask = () => {
				const maxScroll = navEl.scrollWidth - navEl.clientWidth;
				if (maxScroll <= 5) {
					navEl.classList.remove('is-start', 'is-end', 'is-middle');
					navEl.classList.add('no-scroll');
					return;
				}
				const current = navEl.scrollLeft;
				navEl.classList.remove('is-start', 'is-end', 'is-middle', 'no-scroll');
				if (current <= 2) {
					navEl.classList.add('is-start');
				} else if (current >= maxScroll - 2) {
					navEl.classList.add('is-end');
				} else {
					navEl.classList.add('is-middle');
				}
			};

			navEl.addEventListener('scroll', updateMask, {passive: true});
			setTimeout(updateMask, 100);
			window.addEventListener('resize', updateMask);

			navEl.addEventListener('mousedown', (e) => {
				isDown = true;
				isDragging = false;
				navEl.classList.add('mouse-dragging');
				startX = e.pageX - navEl.offsetLeft;
				scrollLeft = navEl.scrollLeft;
			});

			navEl.addEventListener('mouseleave', () => {
				isDown = false;
				navEl.classList.remove('mouse-dragging');
			});

			navEl.addEventListener('mouseup', () => {
				isDown = false;
				navEl.classList.remove('mouse-dragging');
			});

			navEl.addEventListener('mousemove', (e) => {
				if (!isDown) return;
				e.preventDefault();
				const x = e.pageX - navEl.offsetLeft;
				const walk = (x - startX) * 2;
				if (Math.abs(walk) > 5) isDragging = true;
				navEl.scrollLeft = scrollLeft - walk;
			});

			navEl.addEventListener('click', (e) => {
				if (isDragging) {
					e.preventDefault();
					e.stopPropagation();
				}
			}, true);
		}

		if (navWrapper) {
			initDragScrollAndMask(navWrapper);
		}
	});

});
