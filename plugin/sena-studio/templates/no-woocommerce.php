<?php defined( 'ABSPATH' ) || exit; ?>
<section class="sena-card sena-card--empty">
	<h2><?php esc_html_e( 'WooCommerce needed', 'sena-studio' ); ?></h2>
	<p><?php esc_html_e( 'This section reads orders and products from WooCommerce. Install and activate WooCommerce to use it.', 'sena-studio' ); ?></p>
	<?php if ( current_user_can( 'install_plugins' ) ) : ?>
		<a class="sena-btn" href="<?php echo esc_url( admin_url( 'plugin-install.php?s=woocommerce&tab=search&type=term' ) ); ?>"><?php esc_html_e( 'Install WooCommerce', 'sena-studio' ); ?></a>
	<?php endif; ?>
</section>
