<?php

namespace Tarosky\MakePostmetaFaster;


use Tarosky\MakePostmetaFaster\Pattern\SingletonPattern;

/**
 * Add setting screen.
 */
class Setting extends SingletonPattern {

	/**
	 * {@inheritDoc}
	 */
	protected function init() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register menu.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_submenu_page( 'tools.php', __( 'Database Index Optimization', 'mpmf' ), __( 'DB Index', 'mpmf' ), 'manage_options', 'mpmf', array( $this, 'render_menu' ) );
	}

	/**
	 * Render menu page.
	 *
	 * @return void
	 */
	public function render_menu() {
		?>
		<style>
			.mpmf-label {
				display: inline-block;
				margin: 0 1em 1em 0;
			}
		</style>
		<div class="wrap">
			<h1><?php esc_html_e( 'Database Index Optimizer', 'mpmf' ); ?></h1>
			<h2><?php esc_html_e( 'Performance Check', 'mpmf' ); ?></h2>
			<p><?php esc_html_e( 'This feature is "work in progress".', 'mpmf' ); ?></p>
			<hr />
			<form action="<?php echo admin_url( 'options.php' ); ?>" method="post">
				<?php
				settings_fields( 'mpmf' );
				do_settings_sections( 'mpmf' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Register settings for option page.
	 *
	 * @return void
	 */
	public function register_settings() {
		// Add section postmeta.
		add_settings_section( 'mpmf-postmeta', __( 'Postmeta Index', 'mpmf' ), function () {
			printf(
				'<p class="desc">%s</p>',
				wp_kses_post( __( 'This option affects the query performance of <code>wp_postmeta</code>.', 'mpfm' ) )
			);
		}, 'mpmf' );
		// Key-length.
		add_settings_field( 'mpmf-postmeta-key-length', __( 'Key Length', 'mpmf' ), function () {
			$length = get_option( 'mpmf-postmeta-key-length', array( 255, 64 ) );
			?>
			<label class="mpmf-label">
				<?php esc_html_e( 'Meta Key', 'mpmf' ); ?><br />
				<input placeholder="255" type="number" min="32" max="255" name="mpmf-postmeta-key-length[]" value="<?php echo esc_attr( $length[0] ); ?>" />
			</label>
			<label class="mpmf-label">
				<?php esc_html_e( 'Meta Value', 'mpmf' ); ?><br />
				<input placeholder="64" type="number" min="32" name="mpmf-postmeta-key-length[]" value="<?php echo esc_attr( $length[1] ); ?>" />
			</label>
			<?php
		}, 'mpmf', 'mpmf-postmeta' );
		register_setting( 'mpmf', 'mpmf-postmeta-key-length' );
	}
}
