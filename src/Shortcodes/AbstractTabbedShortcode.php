<?php
/**
 * Shared behaviour for the tabbed menu shortcodes.
 *
 * @package PhoMenuGrid
 */

namespace PhoMenuGrid\Shortcodes;

use PhoMenuGrid\PostType;
use PhoMenuGrid\Support\Icon;
use PhoMenuGrid\Support\Sanitizer;

defined( 'ABSPATH' ) || exit;

/**
 * Base class for `[pho_menu_grid]` and `[pho_menu_showcase]`.
 *
 * Owns everything the two shortcodes had in common: attribute parsing, term
 * lookup, wrapper attributes, DOM id generation and the tab navigation markup.
 */
abstract class AbstractTabbedShortcode {

	/**
	 * Hard ceiling on items rendered per tab, guarding against an unbounded query.
	 */
	protected const MAX_ITEMS = 100;

	/**
	 * Default primary colour.
	 */
	protected const DEFAULT_PRIMARY = '#0e603b';

	/**
	 * Default accent colour.
	 */
	protected const DEFAULT_ACCENT = '#f39c12';

	/**
	 * Number of star glyphs a rating is drawn with.
	 */
	protected const STAR_COUNT = 5;

	/**
	 * List glyph drawn on the floating picker's toggle.
	 */
	private const PICKER_LIST_SVG = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" /></svg>';

	/**
	 * Chevron drawn on the floating picker's toggle.
	 *
	 * Points up: the toggle sits at the foot of the viewport and its sheet opens
	 * upwards. The stylesheet flips it while the sheet is open.
	 */
	private const PICKER_CARET_SVG = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m18 15-6-6-6 6" /></svg>';

	/**
	 * Cross drawn on the floating picker's close button.
	 */
	private const PICKER_CLOSE_SVG = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M18 6 6 18M6 6l12 12" /></svg>';

	/**
	 * Per-request instance counter, used to build collision-free DOM ids.
	 *
	 * @var int
	 */
	private static $instances = 0;

	/**
	 * The shortcode tag.
	 *
	 * @return string
	 */
	abstract protected function tag(): string;

	/**
	 * Extra attribute defaults for the concrete shortcode.
	 *
	 * @return array<string, mixed>
	 */
	protected function extra_defaults(): array {
		return array();
	}

	/**
	 * Render the shortcode body.
	 *
	 * @param array<string, mixed> $atts    Parsed attributes.
	 * @param \WP_Term[]           $terms   Categories to render as tabs.
	 * @param int                  $active  Zero-based index of the default tab.
	 * @param string               $uid     Unique id fragment for this instance.
	 * @return string
	 */
	abstract protected function render( array $atts, array $terms, int $active, string $uid ): string;

	/**
	 * Register the shortcode.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_shortcode( $this->tag(), array( $this, 'handle' ) );
	}

	/**
	 * Shortcode callback.
	 *
	 * @param array<string, mixed>|string $atts Raw attributes.
	 * @return string
	 */
	public function handle( $atts ): string {
		$atts = shortcode_atts( $this->defaults(), (array) $atts, $this->tag() );

		$this->enqueue();

		$terms = $this->get_categories( $atts['categories'] );

		if ( empty( $terms ) ) {
			return sprintf(
				'<div class="%1$s"><p>%2$s</p></div>',
				esc_attr( $this->wrapper_base_class() ),
				esc_html__( 'Please select at least one Menu Category in the element settings.', 'pho-menu-grid' )
			);
		}

		$active = (int) $atts['default_tab'] - 1;
		$active = max( 0, min( $active, count( $terms ) - 1 ) );

		++self::$instances;
		$uid = $this->tag() . '-' . self::$instances;

		return $this->render( $atts, $terms, $active, $uid );
	}

	/**
	 * Attribute defaults shared by both shortcodes.
	 *
	 * @return array<string, mixed>
	 */
	protected function defaults(): array {
		return array_merge(
			array(
				'categories'         => '',
				'posts_per_category' => 10,
				'default_tab'        => 1,
				'show_icon'          => 'true',
				'primary_color'      => self::DEFAULT_PRIMARY,
				'accent_color'       => self::DEFAULT_ACCENT,
				'class'              => '',
				'visibility'         => '',
			),
			$this->extra_defaults()
		);
	}

	/**
	 * Enqueue the assets this shortcode needs.
	 *
	 * @return void
	 */
	abstract protected function enqueue(): void;

	/**
	 * Base CSS class for the outer wrapper.
	 *
	 * @return string
	 */
	abstract protected function wrapper_base_class(): string;

	/**
	 * Resolve the categories to render.
	 *
	 * An explicit selection is honoured in full, empty categories included — the
	 * editor asked for that tab, so it renders with its empty state rather than
	 * silently disappearing. With no selection, every non-empty category is used
	 * so the element is useful the moment it is dropped into the builder.
	 *
	 * @param string $raw Comma separated term ids.
	 * @return \WP_Term[]
	 */
	protected function get_categories( string $raw ): array {
		$args = array(
			'taxonomy'   => PostType::TAXONOMY,
			'hide_empty' => true,
		);

		$ids = array_filter( array_map( 'absint', explode( ',', $raw ) ) );

		if ( ! empty( $ids ) ) {
			$args['include']    = $ids;
			$args['orderby']    = 'include';
			$args['hide_empty'] = false;
		}

		$terms = get_terms( $args );

		return is_wp_error( $terms ) ? array() : $terms;
	}

	/**
	 * Build the inline custom property declaration for the wrapper.
	 *
	 * @param array<string, mixed> $atts Parsed attributes.
	 * @return string
	 */
	protected function inline_style( array $atts ): string {
		return sprintf(
			'--pho-primary-color:%1$s;--pho-accent-color:%2$s;',
			Sanitizer::color( (string) $atts['primary_color'], self::DEFAULT_PRIMARY ),
			Sanitizer::color( (string) $atts['accent_color'], self::DEFAULT_ACCENT )
		);
	}

	/**
	 * Build the wrapper class attribute value.
	 *
	 * @param array<string, mixed> $atts Parsed attributes.
	 * @return string
	 */
	protected function wrapper_classes( array $atts ): string {
		return Sanitizer::class_list(
			$this->wrapper_base_class(),
			(string) $atts['class'],
			(string) $atts['visibility']
		);
	}

	/**
	 * Number of items to query for one tab.
	 *
	 * @param array<string, mixed> $atts Parsed attributes.
	 * @return int
	 */
	protected function items_per_tab( array $atts ): int {
		$count = (int) $atts['posts_per_category'];

		if ( $count < 1 || $count > self::MAX_ITEMS ) {
			return self::MAX_ITEMS;
		}

		return $count;
	}

	/**
	 * Build the WP_Query arguments for one tab.
	 *
	 * @param int $term_id Category term id.
	 * @param int $limit   Items to fetch.
	 * @return array<string, mixed>
	 */
	protected function query_args( int $term_id, int $limit ): array {
		return array(
			'post_type'              => PostType::POST_TYPE,
			'post_status'            => 'publish',
			'posts_per_page'         => $limit,
			'no_found_rows'          => true,
			'ignore_sticky_posts'    => true,
			'update_post_term_cache' => false,
			'tax_query'              => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- the taxonomy is the whole point of the element.
				array(
					'taxonomy' => PostType::TAXONOMY,
					'field'    => 'term_id',
					'terms'    => $term_id,
				),
			),
		);
	}

	/**
	 * DOM id for a tab panel.
	 *
	 * Derived from the term id rather than a random number, so two panels can
	 * never collide within an instance.
	 *
	 * @param string $uid     Instance id fragment.
	 * @param int    $term_id Term id.
	 * @return string
	 */
	protected function panel_id( string $uid, int $term_id ): string {
		return sprintf( '%s-panel-%d', $uid, $term_id );
	}

	/**
	 * Render the full tab navigation.
	 *
	 * @param \WP_Term[]           $terms  Categories.
	 * @param int                  $active Active index.
	 * @param string               $uid    Instance id fragment.
	 * @param array<string, mixed> $atts   Parsed attributes.
	 * @return string Safe HTML.
	 */
	protected function render_nav( array $terms, int $active, string $uid, array $atts ): string {
		$show_icon = Sanitizer::bool( $atts['show_icon'] );
		$buttons   = '';

		foreach ( array_values( $terms ) as $index => $term ) {
			$panel_id  = $this->panel_id( $uid, (int) $term->term_id );
			$is_active = ( $index === $active );

			$buttons .= sprintf(
				'<button type="button" class="nav-item%1$s" role="tab" id="%2$s-tab" aria-controls="%3$s" aria-selected="%4$s" tabindex="%5$s" data-target="%3$s">',
				$is_active ? ' active' : '',
				esc_attr( $panel_id ),
				esc_attr( $panel_id ),
				$is_active ? 'true' : 'false',
				$is_active ? '0' : '-1'
			);
			$buttons .= '<span>' . esc_html( $term->name ) . '</span>';

			if ( $show_icon ) {
				// Scoped so two instances of the element cannot emit the same
				// internal SVG ids or have their embedded styles collide.
				$buttons .= '<div class="nav-icon">' . Icon::render( (int) $term->term_id, $panel_id ) . '</div>';
			}

			$buttons .= '</button>';
		}

		return sprintf(
			'<div class="menu-nav-container"><div class="menu-nav" role="tablist" aria-label="%1$s">%2$s</div></div>',
			esc_attr__( 'Menu categories', 'pho-menu-grid' ),
			$buttons
		);
	}


	/**
	 * Render the floating category picker.
	 *
	 * A second control over the same tabs, for a reader already deep inside a
	 * long panel: the nav bar only exists at the very top of the element, so
	 * switching category otherwise means scrolling the whole list back up. The
	 * markup starts `hidden` and is revealed by the script, so a reader without
	 * JavaScript is never shown a button that cannot do anything.
	 *
	 * Each entry carries the same `data-target` as its nav button, which is all
	 * the script needs to hand the click to the existing tab controller rather
	 * than run a second one.
	 *
	 * @param \WP_Term[]           $terms  Categories.
	 * @param int                  $active Active index.
	 * @param string               $uid    Instance id fragment.
	 * @param array<string, mixed> $atts   Parsed attributes.
	 * @param array<string, int>   $counts Item count keyed by panel id.
	 * @return string Safe HTML.
	 */
	protected function render_category_picker( array $terms, int $active, string $uid, array $atts, array $counts = array() ): string {
		$show_icon = Sanitizer::bool( $atts['show_icon'] );
		$sheet_id  = $uid . '-picker';
		$items     = '';
		$label     = '';

		foreach ( array_values( $terms ) as $index => $term ) {
			$panel_id  = $this->panel_id( $uid, (int) $term->term_id );
			$is_active = ( $index === $active );

			if ( $is_active ) {
				$label = $term->name;
			}

			$items .= sprintf(
				'<li><button type="button" class="menu-picker-item%1$s" data-target="%2$s"%3$s>',
				$is_active ? ' is-active' : '',
				esc_attr( $panel_id ),
				$is_active ? ' aria-current="true"' : ''
			);

			if ( $show_icon ) {
				// Scoped differently from the nav's copy of the same icon:
				// Icon::render() prefixes the SVG's ids and class names with the
				// scope, and two copies sharing one prefix would emit duplicate
				// ids and let one icon's embedded stylesheet repaint the other.
				$items .= '<span class="menu-picker-icon">' . Icon::render( (int) $term->term_id, $panel_id . '-picker' ) . '</span>';
			}

			$items .= '<span class="menu-picker-name">' . esc_html( $term->name ) . '</span>';

			if ( isset( $counts[ $panel_id ] ) ) {
				$items .= '<span class="menu-picker-count">' . esc_html( number_format_i18n( $counts[ $panel_id ] ) ) . '</span>';
			}

			$items .= '</button></li>';
		}

		return sprintf(
			'<div class="menu-picker" hidden>'
				. '<button type="button" class="menu-picker-toggle" aria-expanded="false" aria-haspopup="dialog" aria-controls="%1$s">'
					. '<span class="menu-picker-toggle-icon">%2$s</span>'
					. '<span class="menu-picker-toggle-label">%3$s</span>'
					. '<span class="menu-picker-toggle-caret">%4$s</span>'
				. '</button>'
				. '<div class="menu-picker-backdrop" hidden></div>'
				. '<div class="menu-picker-sheet" id="%1$s" role="dialog" aria-modal="true" aria-label="%5$s" hidden>'
					. '<div class="menu-picker-head">'
						. '<span class="menu-picker-title">%5$s</span>'
						. '<button type="button" class="menu-picker-close" aria-label="%6$s">%7$s</button>'
					. '</div>'
					. '<ul class="menu-picker-list">%8$s</ul>'
				. '</div>'
			. '</div>',
			esc_attr( $sheet_id ),
			self::PICKER_LIST_SVG,
			esc_html( $label ),
			self::PICKER_CARET_SVG,
			esc_attr__( 'Menu categories', 'pho-menu-grid' ),
			esc_attr__( 'Close', 'pho-menu-grid' ),
			self::PICKER_CLOSE_SVG,
			$items
		);
	}

	/**
	 * Opening tag for a tab panel.
	 *
	 * @param string $panel_id  Panel DOM id.
	 * @param bool   $is_active Whether the panel starts visible.
	 * @return string
	 */
	protected function open_panel( string $panel_id, bool $is_active ): string {
		return sprintf(
			'<div class="tab-panel%1$s" id="%2$s" role="tabpanel" aria-labelledby="%2$s-tab"%3$s>',
			$is_active ? ' is-active' : '',
			esc_attr( $panel_id ),
			$is_active ? '' : ' hidden'
		);
	}

	/**
	 * Render a rating as five star glyphs.
	 *
	 * Drawn as two stacked layers — a grey row of five stars with a coloured
	 * copy clipped to the score's width on top — so a fractional score shows a
	 * partly filled star. Doing it this way needs only the U+2605 glyph, which
	 * every font ships, rather than a half-star character that most do not.
	 *
	 * A non-numeric rating is passed through untouched, so an editor can still
	 * type free text into the field.
	 *
	 * @param string $rating Raw rating meta value.
	 * @return string Safe HTML.
	 */
	protected function render_stars( string $rating ): string {
		$rating = trim( $rating );

		if ( '' === $rating ) {
			return '';
		}

		if ( ! is_numeric( $rating ) ) {
			return '<span class="stars">' . esc_html( $rating ) . '</span>';
		}

		$score   = (float) min( self::STAR_COUNT, max( 0, (float) $rating ) );
		$percent = ( $score / self::STAR_COUNT ) * 100;
		$glyphs  = str_repeat( '★', self::STAR_COUNT );

		return sprintf(
			'<span class="stars" role="img" aria-label="%1$s">'
				. '<span class="stars-base" aria-hidden="true">%2$s</span>'
				. '<span class="stars-fill" style="width:%3$s%%" aria-hidden="true">%2$s</span>'
				. '</span>',
			esc_attr(
				sprintf(
					/* translators: 1: rating score, 2: maximum score. */
					__( 'Rated %1$s out of %2$s', 'pho-menu-grid' ),
					number_format_i18n( $score, ( 0.0 === fmod( $score, 1.0 ) ) ? 0 : 1 ),
					self::STAR_COUNT
				)
			),
			$glyphs,
			esc_attr( (string) round( $percent, 2 ) )
		);
	}

	/**
	 * Resolve the outbound link for an item.
	 *
	 * The post type is not publicly queryable, so `get_permalink()` would hand
	 * back a URL that 404s. An item with no explicit link is therefore not
	 * linked at all: the grid renders its cell unwrapped and the showcase omits
	 * its order button.
	 *
	 * @param int $post_id Post id.
	 * @return string Empty string when the item should not be a link.
	 */
	protected function item_link( int $post_id ): string {
		return trim( (string) get_post_meta( $post_id, 'pho_item_link', true ) );
	}
}
