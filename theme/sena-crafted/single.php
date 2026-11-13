<?php
/**
 * Single blog post.
 *
 * @package Sena_Crafted
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="top" class="site-main">
	<?php while ( have_posts() ) : the_post(); ?>
		<article <?php post_class( 'section prose' ); ?>>
			<header class="entry-header">
				<h1 class="page-title"><?php the_title(); ?></h1>
				<p class="muted entry-meta">
					<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
				</p>
			</header>
			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="page-thumb"><?php the_post_thumbnail( 'large', array( 'loading' => 'eager' ) ); ?></figure>
			<?php endif; ?>
			<div class="entry-content">
				<?php
				the_content();
				wp_link_pages( array( 'before' => '<nav class="page-links">', 'after' => '</nav>' ) );
				?>
			</div>
			<nav class="post-nav" aria-label="<?php esc_attr_e( 'Posts', 'sena-crafted' ); ?>">
				<?php previous_post_link( '%link', '&lsaquo; %title' ); ?>
				<?php next_post_link( '%link', '%title &rsaquo;' ); ?>
			</nav>
		</article>
		<?php
		if ( comments_open() || get_comments_number() ) {
			echo '<div class="section prose">';
			comments_template();
			echo '</div>';
		}
	endwhile;
	?>
</main>
<?php
get_footer();
