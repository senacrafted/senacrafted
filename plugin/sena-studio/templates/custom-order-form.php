<?php
/**
 * [sena_custom_order_form] front-end template.
 *
 * @var bool  $sent
 * @var array $errors
 * @var array $old
 * @var array $budgets
 */
defined( 'ABSPATH' ) || exit;
$v = static fn( string $k ): string => isset( $old[ $k ] ) ? (string) $old[ $k ] : '';
?>
<div class="sena-custom-order" id="sena-custom-order">
	<?php if ( $sent ) : ?>
		<div class="sena-custom-order__success" role="status">
			<h3><?php esc_html_e( 'Thank you! Your request is in.', 'sena-studio' ); ?></h3>
			<p><?php esc_html_e( 'I read every request personally and will reply by email, usually within a couple of days.', 'sena-studio' ); ?></p>
		</div>
	<?php else : ?>
		<?php if ( $errors ) : ?>
			<div class="sena-custom-order__errors" role="alert">
				<ul>
					<?php foreach ( $errors as $e ) : ?><li><?php echo esc_html( $e ); ?></li><?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
		<form method="post" enctype="multipart/form-data" class="sena-custom-order__form" novalidate>
			<?php wp_nonce_field( 'sena_custom_order', 'sena_request_nonce', true ); ?>
			<input type="hidden" name="sena_custom_order_submit" value="1">
			<p class="sena-hp" aria-hidden="true" style="position:absolute;left:-9999px;top:-9999px;"><label>Website <input type="text" name="sena_website" tabindex="-1" autocomplete="off"></label></p>

			<p>
				<label for="sena-req-name"><?php esc_html_e( 'Your name', 'sena-studio' ); ?> <span aria-hidden="true">*</span></label>
				<input id="sena-req-name" type="text" name="sena_name" value="<?php echo esc_attr( $v( 'name' ) ); ?>" required autocomplete="name" maxlength="100">
			</p>
			<p>
				<label for="sena-req-email"><?php esc_html_e( 'Email', 'sena-studio' ); ?> <span aria-hidden="true">*</span></label>
				<input id="sena-req-email" type="email" name="sena_email" value="<?php echo esc_attr( $v( 'email' ) ); ?>" required autocomplete="email" inputmode="email">
			</p>
			<p>
				<label for="sena-req-details"><?php esc_html_e( 'What would you like me to make?', 'sena-studio' ); ?> <span aria-hidden="true">*</span></label>
				<textarea id="sena-req-details" name="sena_details" rows="5" required maxlength="3000" aria-describedby="sena-req-details-help"><?php echo esc_textarea( $v( 'details' ) ); ?></textarea>
				<small id="sena-req-details-help"><?php esc_html_e( 'Size, colours, leather or paper, who it is for, any deadline.', 'sena-studio' ); ?></small>
			</p>
			<p>
				<label for="sena-req-budget"><?php esc_html_e( 'Budget', 'sena-studio' ); ?></label>
				<select id="sena-req-budget" name="sena_budget">
					<?php foreach ( $budgets as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $v( 'budget' ), $key ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
			<p>
				<label for="sena-req-image"><?php esc_html_e( 'Reference image (optional, up to 5 MB)', 'sena-studio' ); ?></label>
				<input id="sena-req-image" type="file" name="sena_image" accept="image/jpeg,image/png,image/webp,image/gif,image/heic">
			</p>
			<p><button type="submit" class="button sena-custom-order__btn"><?php esc_html_e( 'Send request', 'sena-studio' ); ?></button></p>
		</form>
	<?php endif; ?>
</div>
