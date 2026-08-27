<?php
/**
 * The [pho_menu_showcase] shortcode.
 *
 * @package PhoMenuGrid
 */

namespace PhoMenuGrid\Shortcodes;

use PhoMenuGrid\Fields;

defined( 'ABSPATH' ) || exit;

/**
 * Zig-zag showcase rows, one tab per category.
 */
class MenuShowcase extends AbstractTabbedShortcode {

	/**
	 * The shortcode tag.
	 *
	 * @return string
	 */
	protected function tag(): string {
		return 'pho_menu_showcase';
	}

	/**
	 * Base wrapper class.
	 *
	 * @return string
	 */
	protected function wrapper_base_class(): string {
		return 'pho-menu-showcase-wrapper';
	}

	/**
	 * Attributes unique to this shortcode.
	 *
	 * @return array<string, mixed>
	 */
	protected function extra_defaults(): array {
		return array( 'order_btn_text' => __( 'ORDER', 'pho-menu-grid' ) );
	}

	/**
	 * Enqueue assets.
	 *
	 * @return void
	 */
	protected function enqueue(): void {
		wp_enqueue_style( 'pho-menu-showcase' );
		wp_enqueue_script( 'pho-menu-showcase' );
	}

	/**
	 * Render the element.
	 *
	 * @param array<string, mixed> $atts   Parsed attributes.
	 * @param \WP_Term[]           $terms  Categories.
	 * @param int                  $active Active tab index.
	 * @param string               $uid    Instance id fragment.
	 * @return string
	 */
	protected function render( array $atts, array $terms, int $active, string $uid ): string {
		$limit     = $this->items_per_tab( $atts );
		$btn_label = (string) $atts['order_btn_text'];
		$counts    = array();

		ob_start();

		printf(
			'<div id="%1$s" class="%2$s" style="%3$s">',
			esc_attr( $uid ),
			esc_attr( $this->wrapper_classes( $atts ) ),
			esc_attr( $this->inline_style( $atts ) )
		);

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_nav() escapes every dynamic part and filters icon SVG through wp_kses.
		echo $this->render_nav( $terms, $active, $uid, $atts );

		echo '<div class="tab-panels-wrapper">';

		foreach ( array_values( $terms ) as $index => $term ) {
			$panel_id = $this->panel_id( $uid, (int) $term->term_id );

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- open_panel() escapes its own attributes.
			echo $this->open_panel( $panel_id, $index === $active );
			echo '<div class="showcase-list">';

			$query = new \WP_Query( $this->query_args( (int) $term->term_id, $limit ) );

			// The rendered count rather than $term->count, which ignores
			// posts_per_category and counts non-published items too.
			$counts[ $panel_id ] = (int) $query->post_count;

			if ( $query->have_posts() ) {
				while ( $query->have_posts() ) {
					$query->the_post();
					$this->render_item( get_the_ID(), $btn_label, $panel_id );
				}

				wp_reset_postdata();
			} else {
				echo '<p class="showcase-empty">' . esc_html__( 'No items in this category.', 'pho-menu-grid' ) . '</p>';
			}

			echo '</div></div>';
		}

		echo '</div>';

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_category_picker() escapes every dynamic part and filters icon SVG through wp_kses.
		echo $this->render_category_picker( $terms, $active, $uid, $atts, $counts );

		echo '</div>';

		return (string) ob_get_clean();
	}

	/**
	 * Render one showcase row.
	 *
	 * @param int    $post_id   Post id.
	 * @param string $btn_label Order button label.
	 * @param string $panel_id  Owning panel id, used to build unique DOM ids.
	 * @return void
	 */
	private function render_item( int $post_id, string $btn_label, string $panel_id ): void {
		$en_name     = get_the_title( $post_id );
		$vn_name     = (string) get_post_meta( $post_id, 'pho_vn_name', true );
		$description = (string) get_post_meta( $post_id, 'pho_description', true );
		$price       = (string) get_post_meta( $post_id, 'pho_price', true );
		$sale_price  = (string) get_post_meta( $post_id, 'pho_sale_price', true );
		$sticker     = (string) get_post_meta( $post_id, 'pho_sticker', true );
		$star_rating = (string) get_post_meta( $post_id, 'pho_star_rating', true );
		$review_text = (string) get_post_meta( $post_id, 'pho_review_text', true );
		$second_lbl  = (string) get_post_meta( $post_id, 'pho_secondary_btn_label', true );
		$second_link = (string) get_post_meta( $post_id, 'pho_secondary_btn_link', true );

		$dietary   = Fields::get_dietary( $post_id );
		$link      = $this->item_link( $post_id );
		$image_id  = get_post_thumbnail_id( $post_id );
		$accord_id = $panel_id . '-diet-' . $post_id;
		?>
		<div class="showcase-item">
			<div class="showcase-col-image">
				<!-- Decorative ring behind the dish; the active theme supplies its artwork. -->
				<div class="rings-wrapper" aria-hidden="true"></div>

				<div class="product-image-wrapper">
					<?php
					if ( $image_id ) {
						echo wp_get_attachment_image(
							$image_id,
							'large',
							false,
							array(
								'class' => 'dish-product-img',
								'alt'   => esc_attr( $en_name ),
							)
						);
					}
					?>
					<?php if ( '' !== $sticker ) : ?>
						<div class="dish-sticker sticker-<?php echo esc_attr( $sticker ); ?>" aria-hidden="true"></div>
					<?php endif; ?>
				</div>
			</div>

			<div class="showcase-col-text">
				<h2 class="dish-title-en"><?php echo esc_html( $en_name ); ?></h2>

				<?php if ( '' !== $vn_name ) : ?>
					<h3 class="dish-title-vn"><?php echo esc_html( $vn_name ); ?></h3>
				<?php endif; ?>

				<?php if ( '' !== $star_rating ) : ?>
					<div class="dish-rating">
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_stars() escapes its own output.
						echo $this->render_stars( $star_rating );
						?>
						<?php if ( '' !== $review_text ) : ?>
							<span class="review-text"><?php echo esc_html( $review_text ); ?></span>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<div class="item-separator"></div>

				<?php
				$has_price = ( '' !== $sale_price || '' !== $price );
				?>
				<?php if ( '' !== $description || $has_price ) : ?>
					<?php
					/*
					 * The price stays inside .dish-description on purpose: themes
					 * lay that element out as a flex row so the pill sits beside
					 * the copy rather than under it. The wrapper is emitted for a
					 * price-only item too, which previously hid the price whenever
					 * the description was blank.
					 */
					?>
					<div class="dish-description">
						<?php
						/*
						 * The copy is wrapped rather than emitted loose: themes lay
						 * .dish-description out as a flex row, and wpautop() returns
						 * one <p> per paragraph, so a two-paragraph description
						 * would put each paragraph in its own column beside the
						 * price instead of stacking them.
						 */
						?>
						<div class="dish-description-text">
							<?php echo wp_kses_post( wpautop( $description ) ); ?>
						</div>

						<?php if ( $has_price ) : ?>
							<div class="price-wrapper">
								<?php if ( '' !== $sale_price ) : ?>
									<span class="regular-price strike"><?php echo esc_html( $price ); ?></span>
									<span class="sale-price pill">
										<?php
										// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- format_price() escapes both captured parts.
										echo $this->format_price( $sale_price );
										?>
									</span>
								<?php else : ?>
									<span class="regular-price pill">
										<?php
										// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- format_price() escapes both captured parts.
										echo $this->format_price( $price );
										?>
									</span>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $dietary ) ) : ?>
					<div class="accordion-group dropdown-style">
						<div class="accordion-item">
							<button
								class="accordion-header"
								type="button"
								aria-expanded="false"
								aria-controls="<?php echo esc_attr( $accord_id ); ?>"
							>
								<span class="accordion-title"><?php esc_html_e( 'DIETARY GUIDE', 'pho-menu-grid' ); ?></span>
								<span class="accordion-icon" aria-hidden="true"></span>
							</button>
							<div class="accordion-content" id="<?php echo esc_attr( $accord_id ); ?>">
								<div class="accordion-content-inner">
									<ul class="dietary-list">
										<?php foreach ( $dietary as $row ) : ?>
											<li>
												<?php if ( '' !== $row['label'] ) : ?>
													<strong><?php echo esc_html( $row['label'] ); ?>:</strong>
												<?php endif; ?>
												<?php echo esc_html( $row['text'] ); ?>
											</li>
										<?php endforeach; ?>
									</ul>
								</div>
							</div>
						</div>
					</div>
				<?php endif; ?>

				<div class="action-buttons">
					<?php if ( '' !== $link ) : ?>
						<a href="<?php echo esc_url( $link ); ?>" class="btn-order"><?php echo esc_html( $btn_label ); ?></a>
					<?php endif; ?>
					<?php if ( '' !== $second_lbl && '' !== $second_link ) : ?>
						<a href="<?php echo esc_url( $second_link ); ?>" class="btn-secondary-action"><?php echo esc_html( $second_lbl ); ?></a>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Split a price into its currency symbol and amount.
	 *
	 * @param string $price Raw price string, e.g. "$18.00".
	 * @return string Safe HTML.
	 */
	private function format_price( string $price ): string {
		$price = trim( $price );

		if ( preg_match( '/^([^\d\s]+)\s*(.*)$/u', $price, $matches ) ) {
			return '<span class="currency-symbol">' . esc_html( trim( $matches[1] ) ) . '</span>'
				. '<span class="amount">' . esc_html( trim( $matches[2] ) ) . '</span>';
		}

		return esc_html( $price );
	}
}
