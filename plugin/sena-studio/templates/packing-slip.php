<?php
/**
 * Printable packing slip (stand-alone HTML document).
 *
 * @var WC_Order $order
 * @var array    $settings
 * @var array    $tracking
 */
defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php echo esc_attr( get_option( 'blog_charset' ) ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?php echo esc_html( sprintf( /* translators: %s: order number */ __( 'Packing slip #%s', 'sena-studio' ), $order->get_order_number() ) ); ?></title>
<style>
	*{box-sizing:border-box}
	body{font-family:Figtree,-apple-system,BlinkMacSystemFont,"Segoe UI",Helvetica,Arial,sans-serif;color:#222;background:#fff;margin:0;padding:32px;max-width:720px;margin-inline:auto;line-height:1.45}
	h1{font-size:22px;margin:0 0 4px}
	h2{font-size:14px;letter-spacing:.06em;text-transform:uppercase;color:#777;margin:24px 0 8px}
	.head{display:flex;justify-content:space-between;align-items:flex-start;gap:24px;border-bottom:2px solid #c8371f;padding-bottom:16px}
	.muted{color:#777}
	table{width:100%;border-collapse:collapse;margin-top:8px}
	th,td{text-align:left;padding:10px 6px;border-bottom:1px solid #e8e0d2;vertical-align:top}
	th{font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#777}
	td.qty,th.qty{text-align:center;width:60px}
	.note{margin-top:28px;padding:18px 20px;background:#f6efe3;border-radius:14px;font-size:15px;white-space:pre-line}
	address{font-style:normal}
	.print{position:fixed;top:12px;right:12px;background:#c8371f;color:#fff;border:0;border-radius:999px;padding:12px 20px;font:inherit;font-weight:600;cursor:pointer}
	@media print{.print{display:none}body{padding:0}}
</style>
</head>
<body>
<button class="print" type="button" onclick="window.print()"><?php esc_html_e( 'Print', 'sena-studio' ); ?></button>
<?php Sena_Studio::template( 'parts/packing-slip-body', array( 'order' => $order, 'settings' => $settings, 'tracking' => $tracking ) ); ?>
</body>
</html>
