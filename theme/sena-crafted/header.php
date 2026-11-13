<?php
/**
 * Document head, announcement bar and sticky nav.
 *
 * @package Sena_Crafted
 */

defined( 'ABSPATH' ) || exit;

$sena_announce = sena_get_option( 'announcement' );
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<!-- Hand-painted wobble for the wavy frame -->
<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
	<filter id="painted">
		<feTurbulence type="fractalNoise" baseFrequency="0.035" numOctaves="2" seed="3"/>
		<feDisplacementMap in="SourceGraphic" scale="6"/>
	</filter>
</svg>

<a class="skip-link visually-hidden-focusable" href="#top"><?php esc_html_e( 'Skip to content', 'sena-crafted' ); ?></a>

<?php if ( '' !== $sena_announce ) : ?>
	<div class="announce" role="region" aria-label="Announcement"><?php echo esc_html( $sena_announce ); ?></div>
<?php endif; ?>

<header class="nav" id="site-header">
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="brand" rel="home"><?php bloginfo( 'name' ); ?></a>
	<nav aria-label="<?php esc_attr_e( 'Primary', 'sena-crafted' ); ?>">
		<?php
		wp_nav_menu(
			array(
				'theme_location' => 'primary',
				'container'      => false,
				'items_wrap'     => '%3$s',
				'depth'          => 1,
				'fallback_cb'    => 'sena_fallback_menu',
				'walker'         => new Sena_Flat_Walker(),
			)
		);
		if ( function_exists( 'sena_cart_link' ) ) {
			sena_cart_link();
		}
		?>
	</nav>
</header>
