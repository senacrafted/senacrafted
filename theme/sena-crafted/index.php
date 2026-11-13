<?php
/**
 * Blog index / archives / search fallback.
 *
 * @package Sena_Crafted
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="top" class="site-main">
	<div class="section">
		<header class="section-head">
			<h1 class="page-title">
				<?php
				if ( is_home() && ! is_front_page() ) {
					single_post_title();
				} elseif ( is_search() ) {
					/* translators: %s: search query */
					printf( esc_html__( 'Search results for “%s”', 'sena-crafted' ), esc_html( get_search_query() ) );
				} elseif ( is_archive() ) {
					the_archive_title();
				} else {
					esc_html_e( 'Journal', 'sena-crafted' );
				}
				?>
			</h1>
		</header>

		<?php if ( have_posts() ) : ?>
			<div class="gallery post-grid">
				<?php while ( have_posts() ) : the_post(); ?>
					<article <?php post_class( 'card' ); ?>>
						<a class="media" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
							<?php
							if ( has_post_thumbnail() ) {
								the_post_thumbnail( 'sena-card', array( 'loading' => 'lazy' ) );
							} else {
								echo '<div class="placeholder"><div>' . esc_html( get_the_title() ) . '</div></div>';
							}
							?>
						</a>
						<div class="info">
							<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
							<p class="desc"><span><?php echo esc_html( get_the_excerpt() ); ?></span></p>
							<div class="row">
								<span class="muted"><?php echo esc_html( get_the_date() ); ?></span>
								<a class="btn small ghost" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Read', 'sena-crafted' ); ?></a>
							</div>
						</div>
					</article>
				<?php endwhile; ?>
			</div>
			<?php
			the_posts_pagination(
				array(
					'prev_text' => '&lsaquo; ' . __( 'Newer', 'sena-crafted' ),
					'next_text' => __( 'Older', 'sena-crafted' ) . ' &rsaquo;',
				)
			);
			?>
		<?php else : ?>
			<p class="muted"><?php esc_html_e( 'Nothing here yet.', 'sena-crafted' ); ?></p>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
