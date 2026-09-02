<?php
/**
 * The [pho_menu_grid] shortcode.
 *
 * @package PhoMenuGrid
 */

namespace PhoMenuGrid\Shortcodes;

defined( 'ABSPATH' ) || exit;

/**
 * Circular-plate grid of menu items, one tab per category.
 */
class MenuGrid extends AbstractTabbedShortcode {

	/**
	 * The shortcode tag.
	 *
	 * @return string
	 */
	protected function tag(): string {
		return 'pho_menu_grid';
	}

	/**
	 * Base wrapper class.
	 *
	 * @return string
	 */
	protected function wrapper_base_class(): string {
		return 'pho-menu-grid-wrapper';
	}

	/**
	 * Attributes unique to this shortcode.
	 *
	 * @return array<string, mixed>
	 */
	protected function extra_defaults(): array {
		return array( 'auto_play_tabs' => 3000 );
	}

	/**
	 * Enqueue assets.
	 *
	 * @return void
	 */
	protected function enqueue(): void {
		wp_enqueue_style( 'pho-menu-grid' );
		wp_enqueue_script( 'pho-menu-grid' );
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
		$limit = $this->items_per_tab( $atts );

		ob_start();

		printf(
			'<div id="%1$s" class="%2$s" style="%3$s" data-auto-play="%4$d">',
			esc_attr( $uid ),
			esc_attr( $this->wrapper_classes( $atts ) ),
			esc_attr( $this->inline_style( $atts ) ),
			absint( $atts['auto_play_tabs'] )
		);

		// phpcs:ignore WordPress.Security.EscapeOutputOutputNotEscaped, WordPress.Security.EscapeOutput.OutputNotEscaped -- render_nav() escapes every dynamic part and filters icon SVG through wp_kses.
		echo $this->render_nav( $terms, $active, $uid, $atts );

		echo '<div class="tab-panels-wrapper">';

		foreach ( array_values( $terms ) as $index => $term ) {
			$panel_id = $this->panel_id( $uid, (int) $term->term_id );

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- open_panel() escapes its own attributes.
			echo $this->open_panel( $panel_id, $index === $active );
			echo '<div class="carousel">';

			$query = new \WP_Query( $this->query_args( (int) $term->term_id, $limit ) );

			if ( $query->have_posts() ) {
				while ( $query->have_posts() ) {
					$query->the_post();
					$this->render_item( get_the_ID() );
				}

				wp_reset_postdata();
			} else {
				echo '<p class="carousel-empty">' . esc_html__( 'No items found.', 'pho-menu-grid' ) . '</p>';
			}

			echo '</div></div>';
		}

		echo '</div></div>';

		return (string) ob_get_clean();
	}

	/**
	 * Render one grid cell.
	 *
	 * @param int $post_id Post id.
	 * @return void
	 */
	private function render_item( int $post_id ): void {
		$title       = get_the_title( $post_id );
		$link        = $this->item_link( $post_id );
		$star_rating = (string) get_post_meta( $post_id, 'pho_star_rating', true );
		$review_text = (string) get_post_meta( $post_id, 'pho_review_text', true );
		$thumb_id    = get_post_thumbnail_id( $post_id );

		echo '<div class="carousel-cell">';

		if ( '' !== $link ) {
			printf( '<a href="%s" class="item-url">', esc_url( $link ) );
		} else {
			echo '<div class="item-url">';
		}

		echo '<div class="plate-bg">';

		if ( $thumb_id ) {
			echo wp_get_attachment_image(
				$thumb_id,
				'large',
				false,
				$this->image_attr( 'grid', esc_attr( $title ), 'dish-img' )
			);
		} else {
			echo '<div class="dish-img dish-img--placeholder" aria-hidden="true"></div>';
		}

		echo '</div>';
		echo '<h3 class="dish-title">' . esc_html( $title ) . '</h3>';
		echo ( '' !== $link ) ? '</a>' : '</div>';

		if ( '' !== $star_rating || '' !== $review_text ) {
			echo '<div class="dish-rating">';

			if ( '' !== $star_rating ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_stars() escapes its own output.
				echo $this->render_stars( $star_rating );
			}

			if ( '' !== $review_text ) {
				echo '<span class="review-text">' . esc_html( $review_text ) . '</span>';
			}

			echo '</div>';
		}

		echo '<div class="item-separator"></div>';
		echo '</div>';
	}
}
