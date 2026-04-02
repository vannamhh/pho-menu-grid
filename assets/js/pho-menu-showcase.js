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
	});

});
