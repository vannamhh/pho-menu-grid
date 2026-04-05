/**
 * Pho Menu Grid Javascript
 * Handles Flatsome UX Builder element logic: Flickity Carousels, GSAP Transitions, AutoPlay.
 */
window.initPhoMenuGrid = function() {
	const wrappers = document.querySelectorAll('.pho-menu-grid-wrapper:not(.initialized)');
	if (wrappers.length === 0) return;

	wrappers.forEach(wrapper => {
		wrapper.classList.add('initialized');
		// Parse settings from data attributes
		const autoPlayDuration = parseInt(wrapper.getAttribute('data-auto-play')) || 0;
		const defaultTabIndex = parseInt(wrapper.getAttribute('data-default-tab')) || 0;
		const wrapperId = wrapper.id || '';

		// 1. Initialize Carousels
		const flickityOptions = {
			cellAlign: 'center',
			wrapAround: false,
			pageDots: false,
			prevNextButtons: true,
			groupCells: true
		};

		const carouselsObjects = {};
		
		// Check if Flickity is available globally
		let checkFlickityInterval = setInterval(() => {
			if (typeof Flickity !== 'undefined') {
				clearInterval(checkFlickityInterval);
				initCarousels();
			}
		}, 100);

		// Stop checking after 5s if not found to prevent memory leak
		setTimeout(() => { clearInterval(checkFlickityInterval); }, 5000);

		function initCarousels() {
			const carousels = wrapper.querySelectorAll('.carousel');
			carousels.forEach(carousel => {
				const idMatch = carousel.className.match(/carousel-([a-zA-Z0-9_-]+)/);
				if (idMatch && idMatch[0]) {
					// The target tab ID matches the suffix of the carousel class pattern
                    // but we can just map it directly via data attributes or by parent tab-panel ID
                    const parentPanel = carousel.closest('.tab-panel');
                    if (parentPanel) {
                        carouselsObjects[parentPanel.id] = new Flickity(carousel, flickityOptions);
                    }
				}
			});
		}

		// 2. Tab Switching Logic
		const tabButtons = Array.from(wrapper.querySelectorAll('.nav-item'));
		let currentTabIndex = defaultTabIndex;
		let autoPlayTimer;

		function switchTab(btn) {
			const targetId = btn.getAttribute('data-target');
			const currentActiveBtn = wrapper.querySelector('.nav-item.active');
			const currentActivePanel = wrapper.querySelector('.tab-panel[style*="display: block"]');

			if (currentActiveBtn === btn) return;

			currentTabIndex = tabButtons.indexOf(btn);
			
			if (currentActiveBtn) currentActiveBtn.classList.remove('active');
			btn.classList.add('active');

			// Scroll active tab into view (smooth snap to center)
			btn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });

			const hasGSAP = typeof gsap !== 'undefined';
			const newPanel = document.getElementById(targetId);

			if (hasGSAP) {
				// Kill any ongoing animations within this wrapper to prevent overlapping glitches
				gsap.killTweensOf(wrapper.querySelectorAll('.tab-panel'));
			}

			// Clean up any stray panels that might have gotten stuck
			wrapper.querySelectorAll('.tab-panel').forEach(p => {
				if (currentActivePanel && p === currentActivePanel) return;
				if (newPanel && p === newPanel) return;
				p.style.display = 'none';
				p.style.opacity = 0;
			});

			const onCompleteAnim = () => {
				if (currentActivePanel) {
					currentActivePanel.style.display = 'none';
					currentActivePanel.style.opacity = 0;
				}
				
				if (newPanel) {
					newPanel.style.display = 'block';
					if (carouselsObjects[targetId]) {
						carouselsObjects[targetId].resize();
					}
					
					if (hasGSAP) {
						gsap.fromTo(newPanel,
							{ opacity: 0, y: 15 },
							{ opacity: 1, y: 0, duration: 0.3, ease: "power2.out" }
						);
					} else {
						newPanel.style.opacity = 1;
						newPanel.style.transform = 'translateY(0)';
					}
				}
			};

			if (hasGSAP && currentActivePanel) {
				gsap.to(currentActivePanel, {
					opacity: 0,
					y: 15,
					duration: 0.2,
					onComplete: onCompleteAnim
				});
			} else {
				if (currentActivePanel) {
					currentActivePanel.style.opacity = 0;
				}
				onCompleteAnim();
			}
		}

		function startAutoPlay() {
			if (autoPlayDuration <= 0) return;
			
			clearInterval(autoPlayTimer);
			autoPlayTimer = setInterval(() => {
				if (document.hidden) return; // Prevent firing if browser tab is hidden
				if (tabButtons.length > 0) {
					let nextIndex = (currentTabIndex + 1) % tabButtons.length;
					switchTab(tabButtons[nextIndex]);
				}
			}, autoPlayDuration);
		}

		function stopAutoPlay() {
			clearInterval(autoPlayTimer);
		}

		// Bind events
		tabButtons.forEach(btn => {
			btn.addEventListener('click', (e) => {
				e.preventDefault();
				switchTab(btn);
				startAutoPlay();
			});
		});

		const panelsWrapper = wrapper.querySelector('.tab-panels-wrapper');
		const navWrapper = wrapper.querySelector('.menu-nav');

		[panelsWrapper, navWrapper].forEach(el => {
			if (el) {
				el.addEventListener('mouseenter', stopAutoPlay);
				el.addEventListener('mouseleave', startAutoPlay);
				el.addEventListener('touchstart', stopAutoPlay, {passive: true});
				el.addEventListener('touchend', startAutoPlay, {passive: true});
			}
		});

		// Fix glitch when switching browser tabs natively
		document.addEventListener("visibilitychange", () => {
			if (document.hidden) {
				stopAutoPlay();
			} else {
				startAutoPlay();
			}
		});

		// 3. Desktop Drag to Scroll & Overflow Mask (Visual Cue)
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

		// Trigger initially
		startAutoPlay();
	});
};

document.addEventListener("DOMContentLoaded", window.initPhoMenuGrid);

// Observe DOM for elements added via UX Builder or AJAX
const observer = new MutationObserver((mutations) => {
	let shouldInit = false;
	for (let m of mutations) {
		if (m.addedNodes.length > 0) {
			shouldInit = true;
			break;
		}
	}
	if (shouldInit) {
		window.initPhoMenuGrid();
	}
});
observer.observe(document.body, { childList: true, subtree: true });
