<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_shortcode( 'pho_menu_grid', 'pho_menu_grid_render_shortcode' );

function pho_menu_grid_render_shortcode( $atts ) {

	// 1. Phân tích attributes từ UX Builder
	$atts = shortcode_atts( array(
		'categories'         => '',
		'posts_per_category' => -1,
		'default_tab'        => 1,
		'auto_play_tabs'     => 3000,
		'primary_color'      => '#0e603b',
		'accent_color'       => '#f39c12',
		'class'              => '',
		'visibility'         => '',
	), $atts );

	$el_id = 'pho-menu-grid-' . uniqid();

	// Load third-party resources if enabled
	$options = get_option( 'pho_menu_grid_settings' );
	if ( ! empty( $options['load_gsap'] ) ) {
		wp_enqueue_script( 'pho-gsap' );
	}
	if ( ! empty( $options['load_flickity'] ) ) {
		wp_enqueue_style( 'pho-flickity' );
		wp_enqueue_script( 'pho-flickity' );
	}
	
	// Enqueue Plugin Assets
	wp_enqueue_style( 'pho-menu-grid' );
	wp_enqueue_script( 'pho-menu-grid' );

	ob_start();

	// Parse categories array
	$categories_arr = array();
	if ( ! empty( $atts['categories'] ) ) {
		$cat_ids = array_map( 'intval', explode( ',', $atts['categories'] ) );
		$categories = get_terms( array(
			'taxonomy'   => 'pho_menu_category',
			'include'    => $cat_ids,
			'orderby'    => 'include',
			'hide_empty' => false,
		) );
		
		if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
			$categories_arr = $categories;
		}
	}

	// Early return if no valid categories
	if ( empty( $categories_arr ) ) {
		echo '<div class="pho-menu-grid-wrapper"><p>' . esc_html__( 'Please select at least one Menu Category in the settings.', 'pho-menu-grid' ) . '</p></div>';
		return ob_get_clean();
	}

	// Normalize default_tab index
	$default_tab = (int) $atts['default_tab'];
	if ( $default_tab < 1 || $default_tab > count( $categories_arr ) ) {
		$default_tab = 1;
	}
	$default_index = $default_tab - 1; // 0-based index

	// Prepare Inline CSS properties for scope
	$inline_style = sprintf(
		'--primary-color: %1$s; --accent-color: %2$s;',
		esc_attr( $atts['primary_color'] ),
		esc_attr( $atts['accent_color'] )
	);

	// Advanced Classes
	$wrapper_classes = 'pho-menu-grid-wrapper';
	if ( ! empty( $atts['class'] ) ) {
		$wrapper_classes .= ' ' . esc_attr( $atts['class'] );
	}
	if ( ! empty( $atts['visibility'] ) ) {
		$wrapper_classes .= ' ' . esc_attr( $atts['visibility'] );
	}

	// Start Wrapper with data attributes for JS
	printf(
		'<div id="%1$s" class="%2$s" style="%3$s" data-auto-play="%4$d" data-default-tab="%5$d">',
		esc_attr( $el_id ),
		esc_attr( $wrapper_classes ),
		$inline_style,
		(int) $atts['auto_play_tabs'],
		$default_index
	);

	// ==========================================
	// 2. RENDER NAVIGATION TABS
	// ==========================================
	echo '<div class="menu-nav-container"><div class="menu-nav">';

	foreach ( $categories_arr as $index => $cat ) {
		$active_class = ( $index === $default_index ) ? 'active' : '';
		$target_id    = 'tab-' . esc_attr( $cat->slug ) . '-' . esc_attr( $el_id );
		
		// Term Meta
		$term_meta = get_option( "taxonomy_" . $cat->term_id );
		$use_svg   = ! empty( $term_meta['use_svg'] ) ? $term_meta['use_svg'] : '0';
		$tab_icon  = ! empty( $term_meta['tab_icon'] ) ? $term_meta['tab_icon'] : '';
		$svg_code  = ! empty( $term_meta['svg_code'] ) ? trim( $term_meta['svg_code'] ) : '';

		printf(
			'<button type="button" class="nav-item %1$s" data-target="%2$s">',
			esc_attr( $active_class ),
			esc_attr( $target_id )
		);
		echo '<span>' . esc_html( $cat->name ) . '</span>';
		echo '<div class="nav-icon">';

		if ( $use_svg === '1' && ! empty( $svg_code ) ) {
			echo $svg_code; // SVG requires raw output
		} elseif ( ! empty( $tab_icon ) ) {
			echo '<i class="' . esc_attr( $tab_icon ) . '"></i>'; 
		} else {
			echo '<svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z" /></svg>';
		}

		echo '</div></button>';
	}
	echo '</div></div>';

	// ==========================================
	// 3. RENDER TAB PANELS & CAROUSELS
	// ==========================================
	echo '<div class="tab-panels-wrapper">';

	foreach ( $categories_arr as $index => $cat ) {
		$display_style = ( $index === $default_index ) ? 'display: block;' : 'display: none;';
		$target_id     = 'tab-' . esc_attr( $cat->slug ) . '-' . esc_attr( $el_id );
		$carousel_class = 'carousel-' . esc_attr( $cat->slug ) . '-' . esc_attr( $el_id );

		printf(
			'<div class="tab-panel" id="%1$s" style="%2$s">',
			esc_attr( $target_id ),
			esc_attr( $display_style )
		);
		echo '<div class="carousel ' . esc_attr( $carousel_class ) . '">';

		$loop_args = array(
			'post_type'      => 'pho_menu_item',
			'posts_per_page' => (int) $atts['posts_per_category'],
			'tax_query'      => array(
				array(
					'taxonomy' => 'pho_menu_category',
					'field'    => 'term_id',
					'terms'    => $cat->term_id,
				),
			),
			'no_found_rows'  => true, // optimization
		);
		$query = new WP_Query( $loop_args );

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				pho_menu_grid_render_single_item();
			}
			wp_reset_postdata();
		} else {
			echo '<div class="carousel-cell"><p>' . esc_html__( 'No items found.', 'pho-menu-grid' ) . '</p></div>';
		}

		echo '</div></div>'; // End carousel & tab-panel
	}
	echo '</div>'; // End tab-panels-wrapper

	echo '</div>'; // End main wrapper

	return ob_get_clean();
}

/**
 * Helper function to render a single menu item to keep code DRY and clean.
 */
function pho_menu_grid_render_single_item() {
	$post_id = get_the_ID();

	// Retrieve Meta
	$item_link   = get_post_meta( $post_id, 'pho_item_link', true );
	$star_rating = get_post_meta( $post_id, 'pho_star_rating', true );
	$review_text = get_post_meta( $post_id, 'pho_review_text', true );

	$item_link   = empty( $item_link ) ? get_permalink() : $item_link;

	// Star Generator
	$star_display = is_numeric( $star_rating ) ? '★ ' . $star_rating : $star_rating;

	// Thumbnail
	$img_url = get_the_post_thumbnail_url( $post_id, 'large' );
	if ( ! $img_url ) {
		$img_url = 'https://images.unsplash.com/photo-1582878826629-29b7ad1cb438?auto=format&fit=crop&q=80&w=400';
	}

	echo '<div class="carousel-cell">';
	
	echo '<a href="' . esc_url( $item_link ) . '" class="item-url">';
	echo '<div class="plate-bg">';
	echo '<img src="' . esc_url( $img_url ) . '" alt="' . esc_attr( get_the_title() ) . '" class="dish-img">';
	echo '</div>';
	echo '<h3 class="dish-title">' . esc_html( get_the_title() ) . '</h3>';
	echo '</a>'; 

	// Ratings
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
	echo '</div>'; // End cell
}
