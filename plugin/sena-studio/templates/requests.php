<?php
/**
 * Requests tab: list + detail panel.
 *
 * @var WP_Post[] $requests
 * @var string    $status
 * @var array     $statuses
 * @var array     $budgets
 * @var array     $counts
 * @var array     $detail
 * @var array     $settings
 * @var bool      $has_wc
 */
defined( 'ABSPATH' ) || exit;
$filters = array( 'open' => __( 'Open', 'sena-studio' ), 'new' => __( 'New', 'sena-studio' ), 'quoted' => __( 'Quoted', 'sena-studio' ), 'deposit_paid' => __( 'Deposit paid', 'sena-studio' ), 'in_progress' => __( 'In progress', 'sena-studio' ), 'done' => __( 'Done', 'sena-studio' ), 'declined' => __( 'Declined', 'sena-studio' ), 'all' => __( 'All', 'sena-studio' ) );
?>
<h1 class="sena-title"><?php esc_html_e( 'Custom requests', 'sena-studio' ); ?></h1>

<div class="sena-toolbar">
	<div class="sena-segment sena-segment--scroll">
		<?php foreach ( $filters as $key => $label ) : ?>
			<a class="sena-segment__item<?php echo $key === $status ? ' is-active' : ''; ?>" href="<?php echo esc_url( Sena_Studio::url( 'requests', array( 'status' => $key ) ) ); ?>"><?php echo esc_html( $label ); ?><?php if ( isset( $counts[ $key ] ) && $counts[ $key ] ) : ?> <span class="sena-count"><?php echo esc_html( number_format_i18n( $counts[ $key ] ) ); ?></span><?php endif; ?></a>
		<?php endforeach; ?>
	</div>
</div>

<div class="sena-split<?php echo $detail ? ' has-detail' : ''; ?>">
	<div class="sena-split__list">
		<?php if ( empty( $requests ) ) : ?>
			<section class="sena-card sena-card--empty"><p><?php esc_html_e( 'No requests in this view.', 'sena-studio' ); ?></p><p class="sena-help"><?php esc_html_e( 'Add the [sena_custom_order_form] shortcode to a page to start receiving them.', 'sena-studio' ); ?></p></section>
		<?php endif; ?>
		<?php foreach ( $requests as $post ) : ?>
			<?php $r = Sena_Studio_Requests::get( $post->ID ); ?>
			<a class="sena-card sena-request<?php echo $detail && $detail['id'] === $r['id'] ? ' is-active' : ''; ?>" href="<?php echo esc_url( Sena_Studio::url( 'requests', array( 'status' => $status, 'request' => $r['id'] ) ) ); ?>#sena-request-detail">
				<div class="sena-request__head">
					<strong><?php echo esc_html( $r['name'] ); ?></strong>
					<span class="sena-chip sena-chip--<?php echo esc_attr( $r['status'] ); ?>"><?php echo esc_html( $statuses[ $r['status'] ] ?? $r['status'] ); ?></span>
				</div>
				<p class="sena-request__excerpt"><?php echo esc_html( wp_trim_words( $r['details'], 18 ) ); ?></p>
				<div class="sena-muted sena-request__meta"><?php echo esc_html( $r['date'] ); ?> &middot; <?php echo esc_html( $budgets[ $r['budget'] ] ?? $r['budget'] ); ?><?php echo $r['image_id'] ? ' &middot; <span class="dashicons dashicons-format-image" aria-hidden="true"></span>' : ''; ?></div>
			</a>
		<?php endforeach; ?>
	</div>

	<?php if ( $detail ) : ?>
		<?php $r = $detail; ?>
		<section class="sena-card sena-detail" id="sena-request-detail">
			<div class="sena-detail__head">
				<div>
					<h2><?php echo esc_html( $r['name'] ); ?></h2>
					<a href="mailto:<?php echo esc_attr( $r['email'] ); ?>?subject=<?php echo esc_attr( rawurlencode( __( 'Your custom order request', 'sena-studio' ) ) ); ?>"><?php echo esc_html( $r['email'] ); ?></a>
					<div class="sena-muted"><?php echo esc_html( $r['date'] ); ?> &middot; <?php echo esc_html( $budgets[ $r['budget'] ] ?? $r['budget'] ); ?></div>
				</div>
				<a class="sena-dialog__close" href="<?php echo esc_url( Sena_Studio::url( 'requests', array( 'status' => $status ) ) ); ?>" aria-label="<?php esc_attr_e( 'Close', 'sena-studio' ); ?>">&times;</a>
			</div>

			<p class="sena-detail__text"><?php echo nl2br( esc_html( $r['details'] ) ); ?></p>

			<?php if ( $r['image_id'] ) : ?>
				<a class="sena-detail__image" href="<?php echo esc_url( wp_get_attachment_url( $r['image_id'] ) ?: '' ); ?>" target="_blank" rel="noopener"><?php echo wp_get_attachment_image( $r['image_id'], 'medium' ); ?></a>
			<?php endif; ?>

			<?php if ( $r['order_id'] && $has_wc ) : ?>
				<?php $o = wc_get_order( $r['order_id'] ); ?>
				<?php if ( $o ) : ?>
					<div class="sena-notice sena-notice--info">
						<?php echo esc_html( sprintf( /* translators: 1: order number 2: status 3: total */ __( 'Order #%1$s – %2$s – %3$s', 'sena-studio' ), $o->get_order_number(), wc_get_order_status_name( $o->get_status() ), Sena_Studio::money( $o->get_total() ) ) ); ?>
						<a href="<?php echo esc_url( $o->get_edit_order_url() ); ?>"><?php esc_html_e( 'Open order', 'sena-studio' ); ?></a>
						<?php if ( $o->needs_payment() ) : ?>
							&middot; <button type="button" class="sena-link" data-copy="<?php echo esc_attr( $o->get_checkout_payment_url() ); ?>"><?php esc_html_e( 'Copy pay link', 'sena-studio' ); ?></button>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			<?php endif; ?>

			<form class="sena-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'sena_studio_request_update' ); Sena_Studio::return_field(); ?>
				<input type="hidden" name="action" value="sena_studio_request_update">
				<input type="hidden" name="request_id" value="<?php echo esc_attr( (string) $r['id'] ); ?>">
				<div class="sena-row">
					<label class="sena-field sena-grow"><span class="sena-label"><?php esc_html_e( 'Status', 'sena-studio' ); ?></span>
						<select name="status">
							<?php foreach ( $statuses as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $r['status'], $key ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<label class="sena-field sena-grow"><span class="sena-label"><?php esc_html_e( 'Quote amount', 'sena-studio' ); ?></span><input type="text" inputmode="decimal" name="quote" value="<?php echo esc_attr( $r['quote'] ); ?>" placeholder="0.00"></label>
				</div>
				<label class="sena-field"><span class="sena-label"><?php esc_html_e( 'Private notes', 'sena-studio' ); ?></span><textarea name="notes" rows="3"><?php echo esc_textarea( $r['notes'] ); ?></textarea></label>
				<div class="sena-actions">
					<button type="submit" class="sena-btn"><?php esc_html_e( 'Save', 'sena-studio' ); ?></button>
				</div>
			</form>

			<?php if ( $has_wc ) : ?>
				<form class="sena-form sena-form--quote" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'sena_studio_request_quote' ); Sena_Studio::return_field(); ?>
					<input type="hidden" name="action" value="sena_studio_request_quote">
					<input type="hidden" name="request_id" value="<?php echo esc_attr( (string) $r['id'] ); ?>">
					<h3><?php esc_html_e( 'Send quote', 'sena-studio' ); ?></h3>
					<p class="sena-help"><?php esc_html_e( 'Creates a WooCommerce order and emails the customer a secure pay link.', 'sena-studio' ); ?></p>
					<div class="sena-row">
						<label class="sena-field sena-grow"><span class="sena-label"><?php esc_html_e( 'Quote total', 'sena-studio' ); ?></span><input type="text" inputmode="decimal" name="quote" value="<?php echo esc_attr( $r['quote'] ); ?>" placeholder="0.00" required></label>
					</div>
					<div class="sena-radios">
						<label class="sena-radio"><input type="radio" name="mode" value="deposit" checked><span><strong><?php echo esc_html( sprintf( /* translators: %d: percent */ __( 'Deposit (%d%%)', 'sena-studio' ), (int) $settings['deposit_percent'] ) ); ?></strong><small><?php esc_html_e( 'Balance due at the end', 'sena-studio' ); ?></small></span></label>
						<label class="sena-radio"><input type="radio" name="mode" value="full"><span><strong><?php esc_html_e( 'Full amount', 'sena-studio' ); ?></strong><small><?php esc_html_e( 'One payment', 'sena-studio' ); ?></small></span></label>
					</div>
					<div class="sena-actions">
						<button type="submit" class="sena-btn sena-btn--dark" data-confirm="<?php esc_attr_e( 'Create the order and email the pay link now?', 'sena-studio' ); ?>"><span class="dashicons dashicons-email" aria-hidden="true"></span> <?php esc_html_e( 'Send quote', 'sena-studio' ); ?></button>
					</div>
				</form>
			<?php endif; ?>

			<div class="sena-detail__danger">
				<a class="sena-link sena-link--danger" data-confirm="<?php esc_attr_e( 'Move this request to the trash?', 'sena-studio' ); ?>" href="<?php echo esc_url( Sena_Studio::action_url( 'sena_studio_request_delete', array( 'request_id' => $r['id'] ) ) ); ?>"><?php esc_html_e( 'Delete request', 'sena-studio' ); ?></a>
			</div>
		</section>
	<?php endif; ?>
</div>
