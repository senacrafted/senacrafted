<?php
/**
 * The one-page storefront: hero → shop → custom orders → about → events → contact.
 *
 * @package Sena_Crafted
 */

defined( 'ABSPATH' ) || exit;

get_header();

$sena_has_wc  = class_exists( 'WooCommerce' );
$sena_tagline = sena_get_option( 'tagline' );
$sena_email   = sena_get_option( 'email' );
$sena_ig      = sena_get_option( 'instagram' );
$sena_tiktok  = sena_get_option( 'tiktok' );
?>

<main id="top" class="site-main">

	<section class="hero stripes" aria-label="<?php esc_attr_e( 'Welcome', 'sena-crafted' ); ?>">
		<div class="hero-mark">
			<?php sena_doodle( 'star-1.webp', 'star-a', true ); ?>
			<?php sena_doodle( 'star-3.webp', 'star-b', true ); ?>
			<h1>
				<img src="<?php echo esc_url( sena_img( 'drawings/logo-arched.webp' ) ); ?>" alt="" width="900" height="718" loading="eager" fetchpriority="high" decoding="async">
				<span class="visually-hidden"><?php bloginfo( 'name' ); ?><?php echo '' !== $sena_tagline ? ' — ' . esc_html( $sena_tagline ) : ''; ?></span>
			</h1>
		</div>
	</section>

	<section id="work" class="section">
		<div class="section-head">
			<h2 class="visually-hidden"><?php esc_html_e( 'Shop', 'sena-crafted' ); ?></h2>
			<?php
			if ( $sena_has_wc ) {
				sena_product_filters();
			}
			?>
		</div>
		<?php if ( $sena_has_wc ) : ?>
			<?php wc_print_notices(); ?>
			<div id="gallery" class="gallery woocommerce">
				<?php
				$sena_products = sena_front_page_products();
				if ( $sena_products ) {
					foreach ( $sena_products as $sena_product ) {
						sena_product_card( $sena_product );
					}
				} else {
					echo '<p class="muted empty-note">' . esc_html__( 'New pieces are on the workbench. Check back soon!', 'sena-crafted' ) . '</p>';
				}
				?>
			</div>
		<?php else : ?>
			<p class="muted empty-note"><?php esc_html_e( 'The shop is being set up. In the meantime, say hello below!', 'sena-crafted' ); ?></p>
		<?php endif; ?>
	</section>

	<section id="custom" class="gingham">
		<div class="wavy">
			<svg class="wave" aria-hidden="true" focusable="false"></svg>
			<?php sena_doodle( 'leaf-1.webp', 'leaf-a' ); ?>
			<?php sena_doodle( 'leaf-3.webp', 'leaf-b' ); ?>
			<?php sena_doodle( 'leaf-3.webp', 'leaf-c' ); ?>
			<h2><?php esc_html_e( 'Custom orders', 'sena-crafted' ); ?></h2>
			<?php sena_the_paragraphs( sena_get_option( 'custom' ) ); ?>
			<a class="btn" id="custom-btn" href="<?php echo esc_url( sena_custom_order_url() ); ?>"><?php esc_html_e( 'Start a custom order', 'sena-crafted' ); ?></a>
		</div>
	</section>

	<section id="about" class="section about">
		<div class="about-photos">
			<?php
			sena_about_photo( 'about_img_1', __( 'Sena smiling with a sandwich at a café', 'sena-crafted' ), 'me' );
			sena_about_photo( 'about_img_2', __( "Sena's workbench with leather, tools, and a cutting mat", 'sena-crafted' ) );
			sena_about_photo( 'about_img_3', __( 'Leather crow eyes cut out and waiting to be stitched', 'sena-crafted' ) );
			?>
		</div>
		<div class="about-text">
			<h2><i class="spark" aria-hidden="true"></i><?php echo esc_html( sena_get_option( 'about_title' ) ); ?></h2>
			<div id="about-copy">
				<?php sena_the_paragraphs( sena_get_option( 'about' ) ); ?>
			</div>
		</div>
	</section>

	<section id="events" class="section events-section">
		<div class="events-head">
			<h2><?php esc_html_e( 'Find me at a festival', 'sena-crafted' ); ?></h2>
			<img class="car" src="<?php echo esc_url( sena_img( 'drawings/car.webp' ) ); ?>" alt="" aria-hidden="true" loading="lazy" decoding="async">
		</div>
		<ul id="event-list" class="events">
			<?php
			$sena_events = sena_get_upcoming_events();
			if ( $sena_events ) :
				foreach ( $sena_events as $sena_event ) :
					$sena_date  = (string) get_post_meta( $sena_event->ID, 'event_date', true );
					$sena_place = (string) get_post_meta( $sena_event->ID, 'event_place', true );
					$sena_link  = (string) get_post_meta( $sena_event->ID, 'event_link', true );
					?>
					<li>
						<span class="date"><time datetime="<?php echo esc_attr( $sena_date ); ?>"><?php echo esc_html( sena_event_short_date( $sena_date ) ); ?></time></span>
						<div>
							<strong><?php echo esc_html( get_the_title( $sena_event ) ); ?></strong>
							<?php if ( '' !== $sena_place ) : ?>
								<div class="place"><?php echo esc_html( $sena_place ); ?></div>
							<?php endif; ?>
						</div>
						<?php if ( '' !== $sena_link ) : ?>
							<a class="btn small ghost" href="<?php echo esc_url( $sena_link ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Details', 'sena-crafted' ); ?></a>
						<?php endif; ?>
					</li>
					<?php
				endforeach;
			else :
				?>
				<li class="empty"><span class="muted">
					<?php
					if ( '' !== $sena_ig ) {
						printf(
							/* translators: %s: link to Instagram */
							esc_html__( 'No festivals scheduled yet, follow %s for dates.', 'sena-crafted' ),
							'<a href="' . esc_url( 'https://www.instagram.com/' . $sena_ig . '/' ) . '" target="_blank" rel="noopener">' . esc_html__( 'Instagram', 'sena-crafted' ) . '</a>'
						);
					} else {
						esc_html_e( 'No festivals scheduled yet, follow Instagram for dates.', 'sena-crafted' );
					}
					?>
				</span></li>
			<?php endif; ?>
		</ul>
	</section>

	<section id="contact" class="contact stripes khaki">
		<div class="contact-card">
			<?php sena_doodle( 'star-4.webp', 'star-a' ); ?>
			<?php sena_doodle( 'star-2.webp', 'star-b' ); ?>
			<h2><?php esc_html_e( 'Say hello!', 'sena-crafted' ); ?></h2>
			<p class="muted hello"><?php echo nl2br( esc_html( sena_get_option( 'hello' ) ) ); ?></p>
			<div class="contact-links">
				<?php if ( '' !== $sena_email ) : ?>
					<a class="btn" id="email-link" href="<?php echo esc_url( sena_mailto( __( 'Hello!', 'sena-crafted' ) ) ); ?>"><?php esc_html_e( 'Email me', 'sena-crafted' ); ?></a>
				<?php endif; ?>
				<?php if ( '' !== $sena_ig ) : ?>
					<a class="btn ghost" id="ig-link" href="<?php echo esc_url( 'https://www.instagram.com/' . $sena_ig . '/' ); ?>" target="_blank" rel="noopener">Instagram</a>
				<?php endif; ?>
				<?php if ( '' !== $sena_tiktok ) : ?>
					<a class="btn ghost" id="tiktok-link" href="<?php echo esc_url( 'https://www.tiktok.com/@' . $sena_tiktok ); ?>" target="_blank" rel="noopener">TikTok</a>
				<?php endif; ?>
			</div>
		</div>
	</section>

</main>

<?php
get_footer();
