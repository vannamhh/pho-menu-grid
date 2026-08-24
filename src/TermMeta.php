<?php
/**
 * Menu category term meta (tab icons).
 *
 * @package PhoMenuGrid
 */

namespace PhoMenuGrid;

use PhoMenuGrid\Support\Icon;
use PhoMenuGrid\Support\Sanitizer;

defined( 'ABSPATH' ) || exit;

/**
 * Adds the tab icon fields to the `pho_menu_category` taxonomy screens.
 */
class TermMeta {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( PostType::TAXONOMY . '_add_form_fields', array( $this, 'render_add_fields' ) );
		add_action( PostType::TAXONOMY . '_edit_form_fields', array( $this, 'render_edit_fields' ) );
		add_action( 'created_' . PostType::TAXONOMY, array( $this, 'save' ) );
		add_action( 'edited_' . PostType::TAXONOMY, array( $this, 'save' ) );
	}

	/**
	 * Fields on the "Add New Category" form.
	 *
	 * @return void
	 */
	public function render_add_fields(): void {
		?>
		<div class="form-field">
			<label for="term_meta_tab_icon"><?php esc_html_e( 'Tab Icon (Class)', 'pho-menu-grid' ); ?></label>
			<input type="text" name="term_meta[tab_icon]" id="term_meta_tab_icon" value="">
			<p class="description"><?php esc_html_e( 'If you use Flatsome icon classes, enter here (e.g. icon-star). Ignored if Use Inline SVG is checked.', 'pho-menu-grid' ); ?></p>
		</div>
		<div class="form-field">
			<label for="term_meta_use_svg">
				<input type="checkbox" name="term_meta[use_svg]" id="term_meta_use_svg" value="1">
				<?php esc_html_e( 'Use Inline SVG for Tab Icon?', 'pho-menu-grid' ); ?>
			</label>
		</div>
		<div class="form-field">
			<label for="term_meta_svg_code"><?php esc_html_e( 'SVG Code', 'pho-menu-grid' ); ?></label>
			<textarea name="term_meta[svg_code]" id="term_meta_svg_code" rows="5" cols="50"></textarea>
			<p class="description"><?php esc_html_e( 'Paste your raw <svg>...</svg> code here. Scripts and event handlers are stripped on save.', 'pho-menu-grid' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Fields on the "Edit Category" form.
	 *
	 * @param \WP_Term $term Term being edited.
	 * @return void
	 */
	public function render_edit_fields( \WP_Term $term ): void {
		$icon = Icon::for_term( $term->term_id );
		?>
		<tr class="form-field">
			<th scope="row"><label for="term_meta_tab_icon"><?php esc_html_e( 'Tab Icon (Class)', 'pho-menu-grid' ); ?></label></th>
			<td>
				<input type="text" name="term_meta[tab_icon]" id="term_meta_tab_icon" value="<?php echo esc_attr( $icon['tab_icon'] ); ?>">
				<p class="description"><?php esc_html_e( 'If you use Flatsome icon classes, enter here (e.g. icon-star). Ignored if Use Inline SVG is checked.', 'pho-menu-grid' ); ?></p>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row"><label for="term_meta_use_svg"><?php esc_html_e( 'Use Inline SVG for Tab Icon?', 'pho-menu-grid' ); ?></label></th>
			<td>
				<input type="checkbox" name="term_meta[use_svg]" id="term_meta_use_svg" value="1" <?php checked( $icon['use_svg'] ); ?>>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row"><label for="term_meta_svg_code"><?php esc_html_e( 'SVG Code', 'pho-menu-grid' ); ?></label></th>
			<td>
				<textarea name="term_meta[svg_code]" id="term_meta_svg_code" rows="5" cols="50"><?php echo esc_textarea( $icon['svg_code'] ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Paste your raw <svg>...</svg> code here. Scripts and event handlers are stripped on save.', 'pho-menu-grid' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Persist the term meta.
	 *
	 * @param int $term_id Term ID.
	 * @return void
	 */
	public function save( int $term_id ): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- verify_referer() below re-checks the term form nonce.
		if ( ! isset( $_POST['term_meta'] ) || ! is_array( $_POST['term_meta'] ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_term', $term_id ) ) {
			return;
		}

		if ( ! $this->verify_referer( $term_id ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- each member is sanitized individually below.
		$meta = wp_unslash( $_POST['term_meta'] );

		$tab_icon = isset( $meta['tab_icon'] ) ? sanitize_text_field( (string) $meta['tab_icon'] ) : '';
		$use_svg  = empty( $meta['use_svg'] ) ? '0' : '1';

		update_term_meta( $term_id, 'tab_icon', $tab_icon );
		update_term_meta( $term_id, 'use_svg', $use_svg );

		if ( isset( $meta['svg_code'] ) ) {
			/*
			 * Filtered through a strict SVG allowlist rather than gated behind a
			 * capability: `manage_options` used to bypass the check entirely,
			 * which on multisite grants raw markup to users WordPress
			 * deliberately denies `unfiltered_html`.
			 */
			$svg_code = Sanitizer::kses_svg( (string) $meta['svg_code'] );

			if ( '' === $svg_code ) {
				delete_term_meta( $term_id, 'svg_code' );
			} else {
				update_term_meta( $term_id, 'svg_code', wp_slash( $svg_code ) );
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Re-verify the nonce WordPress used for the term form.
	 *
	 * Core already checks this before firing `created_*`/`edited_*`; verifying
	 * again keeps the meta write safe if the hook is ever reached another way.
	 * Verification is non-fatal on purpose so a programmatic term insert that
	 * happens to carry a `term_meta` payload cannot hard-fail the request.
	 *
	 * @param int $term_id Term ID.
	 * @return bool
	 */
	private function verify_referer( int $term_id ): bool {
		$action = isset( $_POST['action'] ) ? sanitize_key( wp_unslash( $_POST['action'] ) ) : '';

		// The "Add New" form (and its AJAX counterpart) uses a dedicated field.
		if ( 'add-tag' === $action ) {
			$nonce = isset( $_POST['_wpnonce_add-tag'] )
				? sanitize_text_field( wp_unslash( $_POST['_wpnonce_add-tag'] ) )
				: '';

			return (bool) wp_verify_nonce( $nonce, 'add-tag' );
		}

		$nonce = isset( $_POST['_wpnonce'] )
			? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) )
			: '';

		return (bool) wp_verify_nonce( $nonce, 'update-tag_' . $term_id );
	}
}
