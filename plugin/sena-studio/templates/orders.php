<?php
/**
 * Orders tab.
 *
 * @var WC_Order[] $orders
 * @var string     $filter
 * @var string     $search
 * @var int        $focus_id
 * @var array      $carriers
 * @var array      $settings
 */
defined( 'ABSPATH' ) || exit;

$filters = array(
	'pack'    => __( 'Needs packing', 'sena-studio' ),
	'shipped' => __( 'Shipped', 'sena-studio' ),
	'all'     => __( 'All', 'sena-studio' ),
);
?>
<h1 class="sena-title"><?php esc_html_e( 'Orders', 'sena-studio' ); ?></h1>

<div class="sena-toolbar">
	<div class="sena-segment" role="tablist">
		<?php foreach ( $filters as $key => $label ) : ?>
			<a class="sena-segment__item<?php echo $key === $filter ? ' is-active' : ''; ?>" href="<?php echo esc_url( Sena_Studio::url( 'orders', array( 'filter' => $key ) ) ); ?>"><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</div>
	<form class="sena-search" method="get" action="<?php echo esc_url( Sena_Studio::form_action( 'orders' ) ); ?>" role="search">
		<?php if ( ! Sena_Studio::is_portal() ) : ?>
			<input type="hidden" name="page" value="<?php echo esc_attr( Sena_Studio::PAGE ); ?>">
			<input type="hidden" name="tab" value="orders">
		<?php endif; ?>
		<input type="hidden" name="filter" value="<?php echo esc_attr( $filter ); ?>">
		<label class="screen-reader-text" for="sena-order-search"><?php esc_html_e( 'Search orders', 'sena-studio' ); ?></label>
		<input id="sena-order-search" type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Name or order number', 'sena-studio' ); ?>" inputmode="search">
		<button class="sena-btn sena-btn--small" type="submit"><?php esc_html_e( 'Search', 'sena-studio' ); ?></button>
	</form>
</div>

<?php if ( empty( $orders ) ) : ?>
	<section class="sena-card sena-card--empty">
		<p><?php echo $search ? esc_html__( 'No orders match that search.', 'sena-studio' ) : esc_html__( 'No orders here right now.', 'sena-studio' ); ?></p>
	</section>
<?php endif; ?>

<div class="sena-stack">
<?php
foreach ( $orders as $order ) :
	if ( ! $order instanceof WC_Order ) {
		continue;
	}
	$id       = $order->get_id();
	$status   = $order->get_status();
	$tracking = Sena_Studio_Orders::tracking( $order );
	$is_open  = $focus_id === $id;
	$back     = array( 'filter' => $filter, 's' => $search );
	$ship_to  = $order->get_formatted_shipping_address() ?: $order->get_formatted_billing_address();
	?>
	<details class="sena-card sena-order<?php echo $is_open ? ' is-focus' : ''; ?>" id="order-<?php echo esc_attr( (string) $id ); ?>" <?php echo $is_open ? 'open' : ''; ?>>
		<summary class="sena-order__summary">
			<div class="sena-order__head">
				<span class="sena-order__num">#<?php echo esc_html( $order->get_order_number() ); ?></span>
				<span class="sena-badge sena-badge--<?php echo esc_attr( $status ); ?>"><?php echo esc_html( Sena_Studio_Orders::status_label( $order ) ); ?></span>
				<span class="sena-order__total"><?php echo esc_html( Sena_Studio::money( $order->get_total() ) ); ?></span>
			</div>
			<div class="sena-order__sub">
				<strong><?php echo esc_html( $order->get_formatted_billing_full_name() ?: $order->get_billing_email() ); ?></strong>
				<span class="sena-muted"><?php echo esc_html( $order->get_date_created() ? $order->get_date_created()->date_i18n( get_option( 'date_format' ) ) : '' ); ?> &middot; <?php echo esc_html( number_format_i18n( $order->get_item_count() ) ); ?> <?php esc_html_e( 'items', 'sena-studio' ); ?></span>
			</div>
			<?php if ( $tracking['number'] ) : ?>
				<div class="sena-order__tracking"><span class="dashicons dashicons-location" aria-hidden="true"></span> <?php echo esc_html( $tracking['carrier_label'] . ' ' . $tracking['number'] ); ?></div>
			<?php endif; ?>
		</summary>

		<div class="sena-order__body">
			<ul class="sena-items">
				<?php foreach ( $order->get_items() as $item ) : ?>
					<?php
					$product = $item->get_product();
					$thumb   = $product ? $product->get_image( array( 64, 64 ) ) : '';
					?>
					<li class="sena-item">
						<span class="sena-item__thumb"><?php echo wp_kses_post( $thumb ); ?></span>
						<span class="sena-item__name"><?php echo esc_html( $item->get_name() ); ?> <span class="sena-muted">&times; <?php echo esc_html( (string) $item->get_quantity() ); ?></span></span>
						<span class="sena-item__total"><?php echo esc_html( Sena_Studio::money( $item->get_total() ) ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>

			<div class="sena-order__meta">
				<div>
					<h4><?php esc_html_e( 'Ship to', 'sena-studio' ); ?></h4>
					<address><?php echo wp_kses_post( $ship_to ); ?></address>
					<?php if ( $order->get_billing_email() ) : ?>
						<a href="mailto:<?php echo esc_attr( $order->get_billing_email() ); ?>"><?php echo esc_html( $order->get_billing_email() ); ?></a>
					<?php endif; ?>
				</div>
				<?php if ( $order->get_customer_note() ) : ?>
					<div>
						<h4><?php esc_html_e( 'Customer note', 'sena-studio' ); ?></h4>
						<p class="sena-note"><?php echo esc_html( $order->get_customer_note() ); ?></p>
					</div>
				<?php endif; ?>
				<div>
					<h4><?php esc_html_e( 'Shipping', 'sena-studio' ); ?></h4>
					<p><?php echo esc_html( $order->get_shipping_method() ?: __( 'None', 'sena-studio' ) ); ?> &middot; <?php echo esc_html( Sena_Studio::money( $order->get_shipping_total() ) ); ?></p>
				</div>
			</div>

			<div class="sena-actions">
				<?php if ( in_array( $status, array( 'processing', 'on-hold' ), true ) ) : ?>
					<a class="sena-btn" href="<?php echo esc_url( Sena_Studio::action_url( 'sena_studio_order_packed', array_merge( array( 'order_id' => $id ), $back ) ) ); ?>"><span class="dashicons dashicons-archive" aria-hidden="true"></span> <?php esc_html_e( 'Mark packed', 'sena-studio' ); ?></a>
				<?php endif; ?>
				<a class="sena-btn sena-btn--ghost" target="_blank" rel="noopener" href="<?php echo esc_url( Sena_Studio::action_url( 'sena_studio_packing_slip', array( 'order_id' => $id ) ) ); ?>"><span class="dashicons dashicons-printer" aria-hidden="true"></span> <?php esc_html_e( 'Packing slip', 'sena-studio' ); ?></a>
				<?php if ( ! empty( $settings['pirate_ship_link'] ) && 'completed' !== $status ) : ?>
					<a class="sena-btn sena-btn--ghost" target="_blank" rel="noopener" href="https://ship.pirateship.com/ship/single"><span class="dashicons dashicons-tag" aria-hidden="true"></span> <?php esc_html_e( 'Buy label', 'sena-studio' ); ?></a>
				<?php endif; ?>
				<button type="button" class="sena-btn sena-btn--ghost" data-toggle="#tracking-<?php echo esc_attr( (string) $id ); ?>"><span class="dashicons dashicons-location" aria-hidden="true"></span> <?php echo $tracking['number'] ? esc_html__( 'Edit tracking', 'sena-studio' ) : esc_html__( 'Add tracking', 'sena-studio' ); ?></button>
				<?php if ( 'completed' !== $status && 'cancelled' !== $status && 'refunded' !== $status ) : ?>
					<a class="sena-btn sena-btn--dark" data-confirm="<?php esc_attr_e( 'Mark this order shipped and email the customer?', 'sena-studio' ); ?>" href="<?php echo esc_url( Sena_Studio::action_url( 'sena_studio_order_shipped', array_merge( array( 'order_id' => $id ), $back ) ) ); ?>"><span class="dashicons dashicons-airplane" aria-hidden="true"></span> <?php esc_html_e( 'Mark shipped', 'sena-studio' ); ?></a>
				<?php endif; ?>
				<a class="sena-link" href="<?php echo esc_url( $order->get_edit_order_url() ); ?>"><?php esc_html_e( 'Open in WooCommerce', 'sena-studio' ); ?></a>
			</div>

			<form class="sena-inline-form<?php echo $tracking['number'] ? '' : ' is-hidden'; ?>" id="tracking-<?php echo esc_attr( (string) $id ); ?>" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'sena_studio_order_tracking' ); Sena_Studio::return_field(); ?>
				<input type="hidden" name="action" value="sena_studio_order_tracking">
				<input type="hidden" name="order_id" value="<?php echo esc_attr( (string) $id ); ?>">
				<input type="hidden" name="filter" value="<?php echo esc_attr( $filter ); ?>">
				<input type="hidden" name="s" value="<?php echo esc_attr( $search ); ?>">
				<label>
					<span><?php esc_html_e( 'Carrier', 'sena-studio' ); ?></span>
					<select name="carrier">
						<?php foreach ( $carriers as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $tracking['carrier'], $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label class="sena-grow">
					<span><?php esc_html_e( 'Tracking number', 'sena-studio' ); ?></span>
					<input type="text" name="tracking_number" value="<?php echo esc_attr( $tracking['number'] ); ?>" autocomplete="off" inputmode="text">
				</label>
				<label class="sena-check">
					<input type="checkbox" name="mark_shipped" value="1" <?php checked( 'completed' !== $status ); ?>>
					<span><?php esc_html_e( 'Mark shipped and email customer', 'sena-studio' ); ?></span>
				</label>
				<button class="sena-btn" type="submit"><?php esc_html_e( 'Save tracking', 'sena-studio' ); ?></button>
			</form>
		</div>
	</details>
<?php endforeach; ?>
</div>
