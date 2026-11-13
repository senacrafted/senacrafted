<?php
/**
 * Packing slip body, shared by the single slip and the "print all" page.
 *
 * @var WC_Order $order
 * @var array    $settings
 * @var array    $tracking
 */
defined( 'ABSPATH' ) || exit;
$shop_name = get_bloginfo( 'name' );
$ship_to   = $order->get_formatted_shipping_address() ?: $order->get_formatted_billing_address();
?>
<div class="head">
	<div>
		<h1><?php echo esc_html( $shop_name ); ?></h1>
		<div class="muted"><?php echo esc_html( home_url() ); ?></div>
	</div>
	<div style="text-align:right">
		<strong><?php echo esc_html( sprintf( /* translators: %s: order number */ __( 'Order #%s', 'sena-studio' ), $order->get_order_number() ) ); ?></strong><br>
		<span class="muted"><?php echo esc_html( $order->get_date_created() ? $order->get_date_created()->date_i18n( get_option( 'date_format' ) ) : '' ); ?></span>
	</div>
</div>

<h2><?php esc_html_e( 'Ship to', 'sena-studio' ); ?></h2>
<address><?php echo wp_kses_post( $ship_to ); ?></address>
<?php if ( $order->get_shipping_method() ) : ?>
	<div class="muted"><?php echo esc_html( $order->get_shipping_method() ); ?></div>
<?php endif; ?>
<?php if ( $tracking['number'] ) : ?>
	<div class="muted"><?php echo esc_html( $tracking['carrier_label'] . ' ' . $tracking['number'] ); ?></div>
<?php endif; ?>

<h2><?php esc_html_e( 'Items', 'sena-studio' ); ?></h2>
<table>
	<thead><tr><th><?php esc_html_e( 'Piece', 'sena-studio' ); ?></th><th class="qty"><?php esc_html_e( 'Qty', 'sena-studio' ); ?></th></tr></thead>
	<tbody>
	<?php foreach ( $order->get_items() as $item ) : ?>
		<tr>
			<td>
				<?php echo esc_html( $item->get_name() ); ?>
				<?php
				$product = $item->get_product();
				if ( $product && $product->get_sku() ) {
					echo '<div class="muted">' . esc_html( $product->get_sku() ) . '</div>';
				}
				$meta = wc_display_item_meta( $item, array( 'echo' => false, 'before' => '<div class="muted">', 'after' => '</div>', 'separator' => ', ' ) );
				echo wp_kses_post( $meta );
				?>
			</td>
			<td class="qty"><?php echo esc_html( (string) $item->get_quantity() ); ?></td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>

<?php if ( $order->get_customer_note() ) : ?>
	<h2><?php esc_html_e( 'Customer note', 'sena-studio' ); ?></h2>
	<p><?php echo esc_html( $order->get_customer_note() ); ?></p>
<?php endif; ?>

<?php if ( ! empty( $settings['thank_you_note'] ) ) : ?>
	<div class="note"><?php echo esc_html( $settings['thank_you_note'] ); ?></div>
<?php endif; ?>
