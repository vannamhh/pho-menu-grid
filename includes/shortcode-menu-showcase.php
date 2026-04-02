<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_shortcode( 'pho_menu_showcase', 'pho_menu_showcase_render_shortcode' );

function pho_menu_showcase_render_shortcode( $atts ) {
	wp_enqueue_style( 'pho-menu-showcase' );
	wp_enqueue_script( 'pho-menu-showcase' );

	// Parse attributes
	$atts = shortcode_atts(
		array(
			'categories'         => '',
			'posts_per_category' => 10,
			'default_tab'        => 1,
			'order_btn_text'     => 'ORDER',
			'primary_color'      => '#0e603b',
			'accent_color'       => '#f39c12',
		),
		$atts,
		'pho_menu_showcase'
	);

	// Get categories
	$category_ids = array();
	if ( ! empty( $atts['categories'] ) ) {
		$category_ids = explode( ',', $atts['categories'] );
		$category_ids = array_map( 'intval', $category_ids );
	}

	if ( empty( $category_ids ) ) {
		$terms = get_terms( array(
			'taxonomy'   => 'pho_menu_category',
			'hide_empty' => true,
		) );
	} else {
		$terms = get_terms( array(
			'taxonomy'   => 'pho_menu_category',
			'include'    => $category_ids,
			'hide_empty' => true,
			'orderby'    => 'include',
		) );
	}

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return '<p>' . esc_html__( 'No menu categories found.', 'pho-menu-grid' ) . '</p>';
	}

	$default_tab_index = intval( $atts['default_tab'] ) - 1;
	$default_tab_index = max( 0, min( $default_tab_index, count( $terms ) - 1 ) );

	$inline_styles = sprintf(
		'--menu-primary-color: %1$s; --menu-accent-color: %2$s;',
		esc_attr( $atts['primary_color'] ),
		esc_attr( $atts['accent_color'] )
	);

	ob_start();
	?>
	<div class="pho-menu-showcase-wrapper" style="<?php echo $inline_styles; ?>" data-default-tab="<?php echo esc_attr( $default_tab_index ); ?>">
		
		<!-- NAVIGATION TABS -->
		<div class="menu-nav-container">
			<div class="menu-nav">
				<?php foreach ( $terms as $index => $term ) : ?>
					<?php
					$target_id    = 'showcase-tab-' . esc_attr( $term->slug ) . '-' . wp_rand(100, 999);
					$active_class = ( $index === $default_tab_index ) ? 'active' : '';
					$term->target_id = $target_id; // Store for later
					
					$svg_code = get_term_meta( $term->term_id, 'svg_code', true );
					
					printf(
						'<button type="button" class="nav-item %1$s" data-target="%2$s">',
						esc_attr( $active_class ),
						esc_attr( $target_id )
					);
					echo '<span>' . esc_html( $term->name ) . '</span>';
					if ( ! empty( $svg_code ) ) {
						echo wp_kses( $svg_code, array(
							'svg'  => array('class' => true, 'viewbox' => true, 'xmlns' => true, 'width' => true, 'height' => true, 'fill' => true),
							'path' => array('d' => true, 'fill' => true),
							'g'    => array('fill' => true, 'stroke' => true),
						) );
					}
					echo '</button>';
					?>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- CONTENT PANELS -->
		<div class="tab-panels-wrapper">
			<?php foreach ( $terms as $index => $term ) : ?>
				<?php
				$display_style = ( $index === $default_tab_index ) ? 'display: block;' : 'display: none;';
				?>
				<div class="tab-panel" id="<?php echo esc_attr( $term->target_id ); ?>" style="<?php echo esc_attr( $display_style ); ?>">
					<div class="showcase-list">
						<?php
						$loop_args = array(
							'post_type'      => 'pho_menu_item',
							'posts_per_page' => intval( $atts['posts_per_category'] ),
							'tax_query'      => array(
								array(
									'taxonomy' => 'pho_menu_category',
									'field'    => 'term_id',
									'terms'    => $term->term_id,
								),
							),
						);

						$query = new WP_Query( $loop_args );

						if ( $query->have_posts() ) {
							while ( $query->have_posts() ) {
								$query->the_post();
								pho_menu_showcase_render_single_item( $atts['order_btn_text'] );
							}
							wp_reset_postdata();
						} else {
							echo '<p>' . esc_html__( 'No items in this category.', 'pho-menu-grid' ) . '</p>';
						}
						?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Render a single showcase item (Zig-Zag Row)
 */
function pho_menu_showcase_render_single_item( $order_btn_text ) {
	$post_id = get_the_ID();
	
	// Data extraction
	$en_name     = get_the_title();
	$vn_name     = get_post_meta( $post_id, 'pho_vn_name', true );
	$description = get_post_meta( $post_id, 'pho_description', true );
	$price       = get_post_meta( $post_id, 'pho_price', true );
	$sale_price  = get_post_meta( $post_id, 'pho_sale_price', true );
	$sticker     = get_post_meta( $post_id, 'pho_sticker', true );
	$dietary_json= get_post_meta( $post_id, 'pho_dietary_guide_json', true );
	
	$item_link   = get_post_meta( $post_id, 'pho_item_link', true );
	$item_link   = empty( $item_link ) ? get_permalink() : $item_link;
	$star_rating = get_post_meta( $post_id, 'pho_star_rating', true );
	$review_text = get_post_meta( $post_id, 'pho_review_text', true );

	$image_id  = get_post_thumbnail_id();
	$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'large' ) : '';

	// Decode repeater
	$dietary = [];
	if(!empty($dietary_json)) {
		$parsed = json_decode($dietary_json, true);
		if(is_array($parsed)) $dietary = $parsed;
	}

	?>
	<div class="showcase-item">
		<!-- TEXT COLUMN -->
		<div class="showcase-col-text">
			<h2 class="dish-title-en"><?php echo esc_html( $en_name ); ?></h2>
			<?php if ( ! empty( $vn_name ) ) : ?>
				<h3 class="dish-title-vn"><?php echo esc_html( $vn_name ); ?></h3>
			<?php endif; ?>

			<?php if ( ! empty( $star_rating ) ) : ?>
				<div class="dish-rating">
					<span class="stars"><?php echo esc_html( $star_rating ); ?> ★</span>
					<?php if ( ! empty( $review_text ) ) : ?>
						<span class="review-text"><?php echo esc_html( $review_text ); ?></span>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="item-separator"></div>

			<?php if ( ! empty( $description ) ) : ?>
				<div class="dish-description">
					<?php echo wp_kses_post( wpautop($description) ); ?>
				</div>
			<?php endif; ?>

			<!-- Price Wrapper -->
			<div class="price-wrapper">
				<?php if ( ! empty( $sale_price ) ) : ?>
					<span class="regular-price strike"><?php echo esc_html( $price ); ?></span>
					<span class="sale-price pill"><?php echo esc_html( $sale_price ); ?></span>
				<?php elseif ( ! empty( $price ) ) : ?>
					<span class="regular-price pill"><?php echo esc_html( $price ); ?></span>
				<?php endif; ?>
			</div>

			<!-- Accordions (Dietary Guide) -->
			<?php if ( ! empty( $dietary ) ) : ?>
				<div class="accordion-group dropdown-style">
					<div class="accordion-item">
						<button class="accordion-header" type="button">
							<span class="accordion-title">DIETARY GUIDE</span>
							<span class="accordion-icon">+</span>
						</button>
						<div class="accordion-content">
							<div class="accordion-content-inner">
								<ul class="dietary-list">
									<?php foreach($dietary as $row): ?>
										<?php if(!empty($row['label']) || !empty($row['text'])): ?>
											<li>
												<strong><?php echo esc_html($row['label']); ?>:</strong>
												<?php echo esc_html($row['text']); ?>
											</li>
										<?php endif; ?>
									<?php endforeach; ?>
								</ul>
							</div>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<!-- Buttons -->
			<div class="action-buttons">
				<a href="<?php echo esc_url( $item_link ); ?>" class="btn-order"><?php echo esc_html( $order_btn_text ); ?></a>
			</div>
		</div>

		<!-- IMAGE COLUMN -->
		<div class="showcase-col-image">
			<!-- Vòng xoáy bên dưới -->
			<div class="rings-wrapper"></div>
			
			<!-- Hình sản phẩm nằm đè lên trên -->
			<div class="product-image-wrapper">
				<?php if ( $image_url ) : ?>
					<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $en_name ); ?>" class="dish-product-img">
				<?php endif; ?>
				
				<?php if ( ! empty( $sticker ) ) : ?>
					<div class="dish-sticker sticker-<?php echo esc_attr( $sticker ); ?>">
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php
}
