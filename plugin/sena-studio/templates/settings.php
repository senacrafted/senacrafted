<?php
/**
 * Settings tab.
 *
 * @var array $settings
 */
defined( 'ABSPATH' ) || exit;
?>
<h1 class="sena-title"><?php esc_html_e( 'Settings', 'sena-studio' ); ?></h1>

<section class="sena-card sena-form">
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'sena_studio_save_settings' ); Sena_Studio::return_field(); ?>
		<input type="hidden" name="action" value="sena_studio_save_settings">

		<label class="sena-field">
			<span class="sena-label"><?php esc_html_e( 'Thank-you note on packing slips', 'sena-studio' ); ?></span>
			<textarea name="thank_you_note" rows="4"><?php echo esc_textarea( $settings['thank_you_note'] ); ?></textarea>
		</label>

		<div class="sena-row">
			<label class="sena-field sena-grow">
				<span class="sena-label"><?php esc_html_e( 'Deposit percentage for custom orders', 'sena-studio' ); ?></span>
				<input type="number" name="deposit_percent" min="1" max="100" step="1" value="<?php echo esc_attr( (string) $settings['deposit_percent'] ); ?>" inputmode="numeric">
			</label>
			<label class="sena-field sena-grow">
				<span class="sena-label"><?php esc_html_e( 'Notification email', 'sena-studio' ); ?></span>
				<input type="email" name="notification_email" value="<?php echo esc_attr( $settings['notification_email'] ); ?>" inputmode="email">
			</label>
		</div>

		<label class="sena-field">
			<span class="sena-label"><?php esc_html_e( 'Monthly sales goal', 'sena-studio' ); ?></span>
			<input type="number" name="monthly_goal" min="0" step="1" value="<?php echo esc_attr( (string) round( (float) ( $settings['monthly_goal'] ?? 500 ) ) ); ?>" inputmode="decimal">
			<span class="sena-help"><?php esc_html_e( 'Net sales you aim for each month. Shown with a progress bar on Today. 0 hides the goal.', 'sena-studio' ); ?></span>
		</label>

		<label class="sena-check sena-field">
			<input type="checkbox" name="pirate_ship_link" value="1" <?php checked( ! empty( $settings['pirate_ship_link'] ) ); ?>>
			<span><?php esc_html_e( 'Show a "Buy label" link to Pirate Ship on orders', 'sena-studio' ); ?></span>
		</label>

		<div class="sena-actions">
			<button type="submit" class="sena-btn"><?php esc_html_e( 'Save settings', 'sena-studio' ); ?></button>
		</div>
	</form>
</section>

<section class="sena-card">
	<h2><?php esc_html_e( 'Shortcodes', 'sena-studio' ); ?></h2>
	<ul class="sena-codes">
		<li><code>[sena_custom_order_form]</code> &ndash; <?php esc_html_e( 'custom order request form', 'sena-studio' ); ?></li>
		<li><code>[sena_track_order]</code> &ndash; <?php esc_html_e( 'order status and tracking lookup', 'sena-studio' ); ?></li>
	</ul>
	<h2><?php esc_html_e( 'Automation (REST API)', 'sena-studio' ); ?></h2>
	<p class="sena-help"><?php esc_html_e( 'Use an application password for a user who can manage WooCommerce.', 'sena-studio' ); ?></p>
	<ul class="sena-codes">
		<li><code>GET <?php echo esc_html( rest_url( 'sena-studio/v1/posts?status=approved' ) ); ?></code></li>
		<li><code>POST <?php echo esc_html( rest_url( 'sena-studio/v1/posts' ) ); ?></code></li>
		<li><code>POST <?php echo esc_html( rest_url( 'sena-studio/v1/posts/{id}/approve' ) ); ?></code></li>
		<li><code>POST <?php echo esc_html( rest_url( 'sena-studio/v1/posts/{id}/skip' ) ); ?></code></li>
		<li><code>GET <?php echo esc_html( rest_url( 'sena-studio/v1/products/recent' ) ); ?></code></li>
	</ul>
	<?php if ( current_user_can( 'edit_user', get_current_user_id() ) ) : ?>
		<a class="sena-link" href="<?php echo esc_url( get_edit_profile_url() . '#application-passwords-section' ); ?>"><?php esc_html_e( 'Create an application password', 'sena-studio' ); ?></a>
	<?php endif; ?>
</section>
