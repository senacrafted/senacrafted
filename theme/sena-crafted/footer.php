<?php
/**
 * Footer with the mushrooms, the lightbox dialog and wp_footer.
 *
 * @package Sena_Crafted
 */

defined( 'ABSPATH' ) || exit;
?>

<footer class="footer" id="colophon">
	<img class="mushrooms" src="<?php echo esc_url( sena_img( 'drawings/mushrooms.webp' ) ); ?>" alt="" aria-hidden="true" loading="lazy" decoding="async">
	<?php if ( has_nav_menu( 'footer' ) ) : ?>
		<nav class="footer-nav" aria-label="<?php esc_attr_e( 'Footer', 'sena-crafted' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'footer',
					'container'      => false,
					'items_wrap'     => '%3$s',
					'depth'          => 1,
					'fallback_cb'    => false,
					'walker'         => new Sena_Flat_Walker(),
				)
			);
			?>
		</nav>
	<?php endif; ?>
	<span>&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?><?php
	$sena_location = sena_get_option( 'location' );
	if ( '' !== $sena_location ) {
		echo ' · ' . esc_html( $sena_location );
	}
	?></span>
</footer>

<dialog id="lightbox" aria-label="<?php esc_attr_e( 'Product photos', 'sena-crafted' ); ?>">
	<button type="button" class="close" aria-label="<?php esc_attr_e( 'Close', 'sena-crafted' ); ?>">&times;</button>
	<div class="lb-media">
		<button type="button" class="lb-nav prev" aria-label="<?php esc_attr_e( 'Previous photo', 'sena-crafted' ); ?>">&lsaquo;</button>
		<div class="lb-frame"></div>
		<button type="button" class="lb-nav next" aria-label="<?php esc_attr_e( 'Next photo', 'sena-crafted' ); ?>">&rsaquo;</button>
	</div>
	<div class="lb-thumbs"></div>
	<div class="lb-body"></div>
</dialog>

<?php wp_footer(); ?>
</body>
</html>
