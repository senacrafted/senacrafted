<?php
/**
 * Static pages (Custom order, FAQ, Shipping & returns…).
 *
 * @package Sena_Crafted
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="top" class="site-main">
	<?php while ( have_posts() ) : the_post(); ?>
		<article <?php post_class( 'section prose' ); ?>>
			<h1 class="page-title"><?php the_title(); ?></h1>
			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="page-thumb"><?php the_post_thumbnail( 'large', array( 'loading' => 'eager' ) ); ?></figure>
			<?php endif; ?>
			<div class="entry-content">
				<?php
				the_content();
				wp_link_pages( array( 'before' => '<nav class="page-links">', 'after' => '</nav>' ) );
				?>
			</div>
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
