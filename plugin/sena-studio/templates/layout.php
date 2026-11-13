<?php
/**
 * Studio page shell: header, tab bar, notice, tab body.
 *
 * @var string $tab
 * @var array  $tabs
 * @var ?array $notice
 * @var bool   $has_wc
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="sena-studio" id="sena-studio">
	<header class="sena-topbar">
		<div class="sena-topbar__inner">
			<a class="sena-brand" href="<?php echo esc_url( Sena_Studio::url( 'today' ) ); ?>">
				<span class="dashicons dashicons-art" aria-hidden="true"></span>
				<span><?php esc_html_e( 'Sena Studio', 'sena-studio' ); ?></span>
			</a>
			<div class="sena-topbar__meta">
				<span class="sena-topbar__date"><?php echo esc_html( wp_date( get_option( 'date_format' ) ) ); ?></span>
				<a class="sena-topbar__site" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'View shop', 'sena-studio' ); ?></a>
			</div>
		</div>
	</header>

	<nav class="sena-tabs" aria-label="<?php esc_attr_e( 'Studio sections', 'sena-studio' ); ?>">
		<?php foreach ( $tabs as $slug => $label ) : ?>
			<a class="sena-tab<?php echo $slug === $tab ? ' is-active' : ''; ?>" href="<?php echo esc_url( Sena_Studio::url( $slug ) ); ?>" <?php echo $slug === $tab ? 'aria-current="page"' : ''; ?>>
				<span class="dashicons <?php echo esc_attr( Sena_Studio::tab_icon( $slug ) ); ?>" aria-hidden="true"></span>
				<span class="sena-tab__label"><?php echo esc_html( $label ); ?></span>
			</a>
		<?php endforeach; ?>
	</nav>

	<main class="sena-main" id="sena-main">
		<?php if ( $notice ) : ?>
			<div class="sena-notice sena-notice--<?php echo esc_attr( $notice['type'] ); ?>" role="status">
				<?php echo esc_html( $notice['message'] ); ?>
				<button type="button" class="sena-notice__close" data-dismiss aria-label="<?php esc_attr_e( 'Dismiss', 'sena-studio' ); ?>">&times;</button>
			</div>
		<?php endif; ?>

		<?php if ( ! $has_wc && in_array( $tab, array( 'today', 'orders', 'products' ), true ) ) : ?>
			<div class="sena-notice sena-notice--warning">
				<?php esc_html_e( 'WooCommerce is not active. Orders and pieces will appear here once it is installed and activated.', 'sena-studio' ); ?>
			</div>
		<?php endif; ?>

		<?php Sena_Studio::render_tab( $tab ); ?>
	</main>
</div>
