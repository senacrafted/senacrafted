<?php
/**
 * 404.
 *
 * @package Sena_Crafted
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="top" class="site-main">
	<div class="section prose not-found">
		<img class="car" src="<?php echo esc_url( sena_img( 'drawings/car.webp' ) ); ?>" alt="" aria-hidden="true" width="130">
		<h1 class="page-title"><?php esc_html_e( 'Oops, wrong turn!', 'sena-crafted' ); ?></h1>
		<p class="muted"><?php esc_html_e( "That page isn't here. Let's head back to the shop.", 'sena-crafted' ); ?></p>
		<a class="btn" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back home', 'sena-crafted' ); ?></a>
	</div>
</main>
<?php
get_footer();
