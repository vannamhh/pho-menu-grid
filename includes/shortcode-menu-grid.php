<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_shortcode( 'pho_menu_grid', 'pho_menu_grid_render_shortcode' );

function pho_menu_grid_render_shortcode( $atts ) {

	// 1. Phân tích attributes từ UX Builder
	$atts = shortcode_atts( array(
		'categories'         => '',      // Chuỗi ID các menu_category cách nhau dấu phẩy
		'posts_per_category' => -1,
		'auto_play_tabs'     => 3000,
		'primary_color'      => '#0e603b',
		'accent_color'       => '#f39c12',
	), $atts );

	// Prepare classes / unique ID cho block
	$el_id = 'pho-menu-grid-' . uniqid();

	ob_start();

	// Load resources conditionally if enabled in admin settings
	$options = get_option( 'pho_menu_grid_settings' );
	if ( ! empty( $options['load_gsap'] ) ) {
		wp_enqueue_script( 'pho-gsap' );
	}
	if ( ! empty( $options['load_flickity'] ) ) {
		wp_enqueue_style( 'pho-flickity' );
		wp_enqueue_script( 'pho-flickity' );
	}

	// Dynamic Inline Styles
	?>
	<style>
		/* Scoped variables for this instance */
		#<?php echo esc_attr( $el_id ); ?> {
			--primary-color: <?php echo esc_attr( $atts['primary_color'] ); ?>;
			--accent-color: <?php echo esc_attr( $atts['accent_color'] ); ?>;
		}

		#<?php echo esc_attr( $el_id ); ?> .menu-nav-container {
			width: 100%;
			max-width: 1200px;
			margin: 3rem auto 1rem;
			padding: 0 1rem;
		}

		#<?php echo esc_attr( $el_id ); ?> .menu-nav {
			display: flex;
			justify-content: flex-start;
			gap: 2rem;
			overflow-x: auto;
			padding-bottom: 1.5rem;
			border-bottom: 2px dashed #e0e0e0;
			-ms-overflow-style: none;
			scrollbar-width: none;
		}
		#<?php echo esc_attr( $el_id ); ?> .menu-nav::-webkit-scrollbar {
			display: none;
		}
		@media (min-width: 1024px) {
			#<?php echo esc_attr( $el_id ); ?> .menu-nav {
				justify-content: center;
			}
		}

		#<?php echo esc_attr( $el_id ); ?> .nav-item {
			display: flex;
			flex-direction: column;
			align-items: center;
			cursor: pointer;
			color: var(--primary-color);
			font-weight: 800;
			text-transform: uppercase;
			font-size: 0.9rem;
			letter-spacing: 0.05em;
			white-space: nowrap;
			transition: all 0.3s ease;
			opacity: 0.7;
			background: none;
			border: none;
			outline: none;
		}
		#<?php echo esc_attr( $el_id ); ?> .nav-item:hover {
			opacity: 1;
		}
		#<?php echo esc_attr( $el_id ); ?> .nav-item.active {
			color: var(--accent-color);
			opacity: 1;
		}
		#<?php echo esc_attr( $el_id ); ?> .nav-icon {
			margin-top: 0.75rem;
			width: 48px;
			height: 48px;
			fill: currentColor;
			transition: transform 0.3s ease;
			display: inline-flex;
			justify-content: center;
			align-items: center;
			font-size: 40px; /* if icon class */
		}
		#<?php echo esc_attr( $el_id ); ?> .nav-icon svg {
			width: 100%;
			height: 100%;
		}
		#<?php echo esc_attr( $el_id ); ?> .nav-item.active .nav-icon {
			transform: scale(1.1);
		}

		/* Tab Panels */
		#<?php echo esc_attr( $el_id ); ?> .tab-panels-wrapper {
			width: 100%;
			max-width: 1200px;
			margin: 0 auto 3rem;
			position: relative;
		}
		#<?php echo esc_attr( $el_id ); ?> .tab-panel {
			display: none;
			width: 100%;
			padding: 2rem 0;
		}
		#<?php echo esc_attr( $el_id ); ?> .carousel {
			background: transparent;
		}
		#<?php echo esc_attr( $el_id ); ?> .flickity-viewport {
			overflow: visible !important;
		}
		#<?php echo esc_attr( $el_id ); ?> .carousel-cell {
			width: 100%; /* Mobile */
			display: flex;
			flex-direction: column;
			align-items: center;
			padding: 1rem;
		}
		@media (min-width: 768px) {
			#<?php echo esc_attr( $el_id ); ?> .carousel-cell { width: 50%; }
		}
		@media (min-width: 1024px) {
			#<?php echo esc_attr( $el_id ); ?> .carousel-cell { width: 33.333%; }
		}

		#<?php echo esc_attr( $el_id ); ?> .plate-bg {
			width: 280px;
			height: 280px;
			background-color: #ffffff;
			border-radius: 50%;
			box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
			display: flex;
			justify-content: center;
			align-items: center;
			margin-bottom: 1.5rem;
			transition: transform 0.3s ease;
		}
		#<?php echo esc_attr( $el_id ); ?> .carousel-cell:hover .plate-bg {
			transform: translateY(-10px);
		}
		#<?php echo esc_attr( $el_id ); ?> .dish-img {
			width: 250px;
			height: 250px;
			object-fit: cover;
			border-radius: 50%;
			border: 6px solid #1c1c1c;
		}

		#<?php echo esc_attr( $el_id ); ?> .dish-title {
			color: var(--primary-color);
			font-size: 1.1rem;
			font-weight: 800;
			text-transform: uppercase;
			text-align: center;
			margin: 0 0 0.5rem 0;
			letter-spacing: 0.02em;
		}
		#<?php echo esc_attr( $el_id ); ?> .dish-rating {
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 0.5rem;
			margin-bottom: 1rem;
		}
		#<?php echo esc_attr( $el_id ); ?> .stars {
			color: var(--accent-color);
			font-size: 0.9rem;
			letter-spacing: 2px;
		}
		#<?php echo esc_attr( $el_id ); ?> .review-text {
			color: #777;
			font-size: 0.75rem;
			font-weight: 600;
			font-style: italic;
		}
		#<?php echo esc_attr( $el_id ); ?> .item-separator {
			width: 50%;
			height: 2px;
			border-bottom: 2px dashed #e0e0e0;
			margin-top: 0.5rem;
		}
		
		#<?php echo esc_attr( $el_id ); ?> .item-url {
			text-decoration: none;
			display: flex;
			flex-direction: column;
			align-items: center;
			color: inherit;
		}

		/* Flickity Prev/Next Buttons Customization */
		#<?php echo esc_attr( $el_id ); ?> .flickity-prev-next-button {
			background: #ffffff;
			box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
			color: var(--primary-color);
			width: 44px;
			height: 44px;
		}
		#<?php echo esc_attr( $el_id ); ?> .flickity-prev-next-button:hover {
			background: var(--accent-color);
			color: white;
		}
	</style>
	<?php

	echo '<div id="' . esc_attr( $el_id ) . '" class="pho-menu-grid-wrapper">';

	// Process categories
	$categories_arr = array();
	if ( ! empty( $atts['categories'] ) ) {
		$cat_ids = explode( ',', $atts['categories'] );
		$categories = get_terms( array(
			'taxonomy'   => 'pho_menu_category',
			'include'    => $cat_ids,
			'orderby'    => 'include', // Maintain order from multiselect.
			'hide_empty' => false,
		) );
		
		if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
			$categories_arr = $categories;
		}
	}

	if ( empty( $categories_arr ) ) {
		echo '<p>Please select at least one Menu Category in the settings.</p></div>';
		return ob_get_clean();
	}

	// ==========================================
	// 2. RENDER NAVIGATION TABS
	// ==========================================
	echo '<div class="menu-nav-container"><div class="menu-nav">';
	$first_tab_active = true;

	foreach ( $categories_arr as $index => $cat ) {
		$active_class = $first_tab_active ? 'active' : '';
		$target_id    = 'tab-' . esc_attr( $cat->slug ) . '-' . esc_attr( $el_id );
		
		// Get term meta
		$term_meta = get_option( "taxonomy_" . $cat->term_id );
		$use_svg   = ! empty( $term_meta['use_svg'] ) ? $term_meta['use_svg'] : '0';
		$tab_icon  = ! empty( $term_meta['tab_icon'] ) ? $term_meta['tab_icon'] : '';
		$svg_code  = ! empty( $term_meta['svg_code'] ) ? $term_meta['svg_code'] : '';

		echo '<button class="nav-item ' . esc_attr( $active_class ) . '" data-target="' . esc_attr( $target_id ) . '">';
		echo '<span>' . esc_html( $cat->name ) . '</span>';
		
		echo '<div class="nav-icon">';
		if ( $use_svg === '1' && ! empty( $svg_code ) ) {
			echo $svg_code; // Output raw SVG 
		} elseif ( ! empty( $tab_icon ) ) {
			echo '<i class="' . esc_attr( $tab_icon ) . '"></i>'; // Flatsome icon format
		} else {
			// Fallback SVG if nothing provided
			echo '<svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z" /></svg>';
		}
		echo '</div>';
		
		echo '</button>';
		
		$first_tab_active = false;
	}
	echo '</div></div>';

	// ==========================================
	// 3. RENDER TAB PANELS & CAROUSELS
	// ==========================================
	echo '<div class="tab-panels-wrapper">';
	$first_panel_active = true;

	foreach ( $categories_arr as $index => $cat ) {
		$display_style = $first_panel_active ? 'display: block;' : 'display: none;';
		$target_id     = 'tab-' . esc_attr( $cat->slug ) . '-' . esc_attr( $el_id );
		$carousel_class = 'carousel-' . esc_attr( $cat->slug ) . '-' . esc_attr( $el_id );

		echo '<div class="tab-panel" id="' . esc_attr( $target_id ) . '" style="' . esc_attr( $display_style ) . '">';
		echo '<div class="carousel ' . esc_attr( $carousel_class ) . '">';

		// Query Items in this category
		$loop_args = array(
			'post_type'      => 'pho_menu_item',
			'posts_per_page' => (int) $atts['posts_per_category'],
			'tax_query'      => array(
				array(
					'taxonomy' => 'pho_menu_category',
					'field'    => 'term_id',
					'terms'    => $cat->term_id,
				)
			)
		);
		$query = new WP_Query( $loop_args );

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post_id = get_the_ID();

				// Retrieve Meta
				$item_link   = get_post_meta( $post_id, 'pho_item_link', true );
				$star_rating = get_post_meta( $post_id, 'pho_star_rating', true );
				$review_text = get_post_meta( $post_id, 'pho_review_text', true );

				// Fallback to permalink if empty link
				if ( empty( $item_link ) ) {
					$item_link = get_permalink();
				}

				// Generate Stars securely (convert number to visual stars or display number)
				// E.g., if "4.5" was submitted, we might just display "★★★★½" statically, but here we just process the text if it is already star emojis, or if it is a number like 4.5, we output "★★★★½".
				// For simplicity since the text mentions "ví dụ 4.5", maybe we display the number alongside a star icon, or if they input '★★★★★'. I'll just output what they wrote inside the star block, so they can paste '★★★★★'.
				// If they put '4.5', it will render `4.5` but styled yellow. 
				// To be safe, let's prefix a star if it's purely numeric.
				if(is_numeric($star_rating)) {
					$star_display = '★ ' . $star_rating;
				} else {
					$star_display = $star_rating; // User pastes '★★★★★'
				}

				// Thumbnail
				$img_url = get_the_post_thumbnail_url( $post_id, 'large' );
				if ( ! $img_url ) {
					// Fallback placeholder image
					$img_url = 'https://images.unsplash.com/photo-1582878826629-29b7ad1cb438?auto=format&fit=crop&q=80&w=400';
				}

				echo '<div class="carousel-cell">';
				
				// Output Link wrapper
				echo '<a href="' . esc_url( $item_link ) . '" class="item-url">';
				
				// Plate + Image
				echo '<div class="plate-bg">';
				echo '<img src="' . esc_url( $img_url ) . '" alt="' . esc_attr( get_the_title() ) . '" class="dish-img">';
				echo '</div>';
				
				// Title
				echo '<h3 class="dish-title">' . esc_html( get_the_title() ) . '</h3>';
				
				echo '</a>'; // End link wrapper

				// Rating area
				if ( ! empty( $star_rating ) || ! empty( $review_text ) ) {
					echo '<div class="dish-rating">';
					if ( ! empty( $star_rating ) ) {
						echo '<span class="stars">' . esc_html( $star_display ) . '</span>';
					}
					if ( ! empty( $review_text ) ) {
						echo '<span class="review-text">' . esc_html( $review_text ) . '</span>';
					}
					echo '</div>';
				}

				echo '<div class="item-separator"></div>';
				echo '</div>'; // End carousel-cell
			}
			wp_reset_postdata();
		} else {
			echo '<div class="carousel-cell"><p>No items found.</p></div>';
		}

		echo '</div></div>'; // End carousel & tab-panel
		$first_panel_active = false;
	}
	echo '</div>'; // End tab-panels-wrapper

	// ==========================================
	// 4. INLINE JAVASCRIPT FOR LOGIC
	// ==========================================
	$auto_play = (int) $atts['auto_play_tabs'];
	?>
	<script>
	document.addEventListener("DOMContentLoaded", () => {
		
		const wrapper = document.getElementById('<?php echo esc_js($el_id); ?>');
		if(!wrapper) return;

		// 1. Initialize Carousels
		const flickityOptions = {
			cellAlign: 'center',
			wrapAround: false,
			pageDots: false,
			prevNextButtons: true,
			groupCells: true
		};

		const carouselsObjects = {};
		// Check if Flickity is available globally (Flatsome provides it or we enqueued it)
		let checkFlickityInterval = setInterval(() => {
			if (typeof Flickity !== 'undefined') {
				clearInterval(checkFlickityInterval);
				initCarousels();
			}
		}, 100);

		// Stop checking after 5s if not found
		setTimeout(() => { clearInterval(checkFlickityInterval); }, 5000);

		function initCarousels() {
			<?php foreach ( $categories_arr as $index => $cat ) : ?>
			<?php $carousel_class = '.carousel-' . esc_attr( $cat->slug ) . '-' . esc_attr( $el_id ); ?>
			<?php $target_id    = 'tab-' . esc_attr( $cat->slug ) . '-' . esc_attr( $el_id ); ?>
			
			carouselsObjects['<?php echo esc_js($target_id); ?>'] = new Flickity(wrapper.querySelector('<?php echo esc_js($carousel_class); ?>'), flickityOptions);
			<?php endforeach; ?>
		}

		// 2. Tab Switching Logic
		const tabButtons = Array.from(wrapper.querySelectorAll('.nav-item'));
		let currentTabIndex = 0;
		let autoPlayTimer;

		function switchTab(btn) {
			const targetId = btn.getAttribute('data-target');
			const currentActiveBtn = wrapper.querySelector('.nav-item.active');
			const currentActivePanel = wrapper.querySelector('.tab-panel[style*="display: block"]');

			if (currentActiveBtn === btn) return;

			currentTabIndex = tabButtons.indexOf(btn);
			
			if(currentActiveBtn) currentActiveBtn.classList.remove('active');
			btn.classList.add('active');

			// Fallback animation if GSAP isn't available
			const hasGSAP = typeof gsap !== 'undefined';

			const onCompleteAnim = () => {
				if(currentActivePanel) currentActivePanel.style.display = 'none';
				const newPanel = document.getElementById(targetId);
				if(newPanel) {
					newPanel.style.display = 'block';
					if (carouselsObjects[targetId]) {
						carouselsObjects[targetId].resize();
					}
					
					if(hasGSAP) {
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

			if(hasGSAP && currentActivePanel) {
				gsap.to(currentActivePanel, {
					opacity: 0,
					y: 15,
					duration: 0.2,
					onComplete: onCompleteAnim
				});
			} else {
				if(currentActivePanel) {
					currentActivePanel.style.opacity = 0;
				}
				onCompleteAnim();
			}
		}

		function startAutoPlay() {
			const autoPlayDuration = <?php echo (int) $auto_play; ?>;
			if(autoPlayDuration <= 0) return;
			
			clearInterval(autoPlayTimer);
			autoPlayTimer = setInterval(() => {
				if(tabButtons.length > 0) {
					let nextIndex = (currentTabIndex + 1) % tabButtons.length;
					switchTab(tabButtons[nextIndex]);
				}
			}, autoPlayDuration);
		}

		function stopAutoPlay() {
			clearInterval(autoPlayTimer);
		}

		tabButtons.forEach(btn => {
			btn.addEventListener('click', () => {
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
				// Also pause on touch events
				el.addEventListener('touchstart', stopAutoPlay, {passive: true});
				el.addEventListener('touchend', startAutoPlay, {passive: true});
			}
		});

		// Trigger initially
		startAutoPlay();
	});
	</script>
	<?php
	
	echo '</div>'; // End wrapper

	return ob_get_clean();
}
