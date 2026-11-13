<?php
/**
 * [sena_track_order] front-end template.
 *
 * @var ?array $result
 * @var string $error
 * @var string $number
 * @var string $email
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="sena-track">
	<form class="sena-track__form" method="post">
		<?php wp_nonce_field( 'sena_track_order', 'sena_track_nonce' ); ?>
		<p>
			<label for="sena-track-number"><?php esc_html_e( 'Order number', 'sena-studio' ); ?></label>
			<input id="sena-track-number" type="text" name="order_number" value="<?php echo esc_attr( $number ); ?>" required inputmode="numeric" autocomplete="off">
		</p>
		<p>
			<label for="sena-track-email"><?php esc_html_e( 'Email used at checkout', 'sena-studio' ); ?></label>
			<input id="sena-track-email" type="email" name="order_email" value="<?php echo esc_attr( $email ); ?>" required autocomplete="email">
		</p>
		<p><button type="submit" class="button sena-track__btn"><?php esc_html_e( 'Track my order', 'sena-studio' ); ?></button></p>
	</form>

	<?php if ( $error ) : ?>
		<p class="sena-track__error" role="alert"><?php echo esc_html( $error ); ?></p>
	<?php endif; ?>

	<?php if ( $result ) : ?>
		<?php $t = $result['tracking']; ?>
		<div class="sena-track__result" role="status">
			<h3><?php echo esc_html( sprintf( /* translators: %s: order number */ __( 'Order #%s', 'sena-studio' ), $result['order']->get_order_number() ) ); ?></h3>
			<p><strong><?php esc_html_e( 'Status:', 'sena-studio' ); ?></strong> <?php echo esc_html( $result['status'] ); ?></p>
			<?php if ( $t['number'] ) : ?>
				<p><strong><?php esc_html_e( 'Tracking:', 'sena-studio' ); ?></strong> <?php echo esc_html( $t['carrier_label'] ); ?>
					<?php if ( $t['url'] ) : ?>
						<a href="<?php echo esc_url( $t['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $t['number'] ); ?></a>
					<?php else : ?>
						<?php echo esc_html( $t['number'] ); ?>
					<?php endif; ?>
				</p>
			<?php else : ?>
				<p><?php esc_html_e( 'Tracking will appear here as soon as the package ships.', 'sena-studio' ); ?></p>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>
