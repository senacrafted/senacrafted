<?php
/**
 * Posts tab (approval queue).
 *
 * @var array[]      $posts
 * @var string       $status
 * @var array        $statuses
 * @var array        $platforms
 * @var array        $counts
 * @var WC_Product[] $products
 */
defined( 'ABSPATH' ) || exit;
$filters = $statuses + array( 'all' => __( 'All', 'sena-studio' ) );
?>
<div class="sena-title-row">
	<h1 class="sena-title"><?php esc_html_e( 'Posts', 'sena-studio' ); ?></h1>
	<button type="button" class="sena-btn sena-btn--ghost" data-open="#sena-add-post"><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span> <?php esc_html_e( 'New post', 'sena-studio' ); ?></button>
</div>

<div class="sena-toolbar">
	<div class="sena-segment sena-segment--scroll">
		<?php foreach ( $filters as $key => $label ) : ?>
			<a class="sena-segment__item<?php echo $key === $status ? ' is-active' : ''; ?>" href="<?php echo esc_url( Sena_Studio::url( 'posts', array( 'status' => $key ) ) ); ?>"><?php echo esc_html( $label ); ?><?php if ( ! empty( $counts[ $key ] ) ) : ?> <span class="sena-count"><?php echo esc_html( number_format_i18n( $counts[ $key ] ) ); ?></span><?php endif; ?></a>
		<?php endforeach; ?>
	</div>
</div>

<?php if ( empty( $posts ) ) : ?>
	<section class="sena-card sena-card--empty">
		<p><?php esc_html_e( 'The queue is empty.', 'sena-studio' ); ?></p>
		<p class="sena-help"><?php esc_html_e( 'Automations can push drafts here via the REST API (POST /sena-studio/v1/posts).', 'sena-studio' ); ?></p>
	</section>
<?php endif; ?>

<div class="sena-grid sena-grid--posts">
<?php foreach ( $posts as $p ) : ?>
	<article class="sena-card sena-post" id="post-<?php echo esc_attr( (string) $p['id'] ); ?>">
		<div class="sena-post__media">
			<?php if ( $p['image_url'] ) : ?>
				<img src="<?php echo esc_url( $p['image_url'] ); ?>" alt="" loading="lazy">
			<?php else : ?>
				<div class="sena-post__placeholder"><span class="dashicons dashicons-format-image" aria-hidden="true"></span></div>
			<?php endif; ?>
			<span class="sena-chip sena-chip--platform sena-chip--<?php echo esc_attr( $p['platform'] ); ?>"><?php echo esc_html( $platforms[ $p['platform'] ] ?? $p['platform'] ); ?></span>
			<span class="sena-chip sena-chip--<?php echo esc_attr( $p['status'] ); ?> sena-post__status"><?php echo esc_html( $statuses[ $p['status'] ] ?? $p['status'] ); ?></span>
		</div>
		<form class="sena-post__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'sena_studio_post_save' ); Sena_Studio::return_field(); ?>
			<input type="hidden" name="action" value="sena_studio_post_save">
			<input type="hidden" name="post_id" value="<?php echo esc_attr( (string) $p['id'] ); ?>">
			<input type="hidden" name="status" value="<?php echo esc_attr( $status ); ?>">

			<?php if ( $p['product_name'] ) : ?>
				<a class="sena-post__product" href="<?php echo esc_url( $p['product_url'] ); ?>" target="_blank" rel="noopener"><span class="dashicons dashicons-art" aria-hidden="true"></span> <?php echo esc_html( $p['product_name'] ); ?></a>
			<?php endif; ?>

			<label class="sena-field">
				<span class="screen-reader-text"><?php esc_html_e( 'Caption', 'sena-studio' ); ?></span>
				<textarea name="caption" rows="5" data-autogrow class="sena-post__caption"><?php echo esc_textarea( $p['caption'] ); ?></textarea>
			</label>
			<label class="sena-field">
				<span class="sena-label"><?php esc_html_e( 'Hashtags', 'sena-studio' ); ?></span>
				<input type="text" name="hashtags" value="<?php echo esc_attr( $p['hashtags'] ); ?>" class="sena-post__tags">
			</label>
			<div class="sena-row">
				<label class="sena-field sena-grow">
					<span class="sena-label"><?php esc_html_e( 'Post on', 'sena-studio' ); ?></span>
					<input type="datetime-local" name="scheduled" value="<?php echo esc_attr( $p['scheduled'] ); ?>">
				</label>
				<label class="sena-field">
					<span class="sena-label"><?php esc_html_e( 'Platform', 'sena-studio' ); ?></span>
					<select name="platform">
						<?php foreach ( $platforms as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $p['platform'], $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			</div>

			<div class="sena-actions sena-post__actions">
				<?php if ( 'approved' !== $p['status'] && 'published' !== $p['status'] ) : ?>
					<button class="sena-btn" type="submit" name="do" value="approve"><span class="dashicons dashicons-yes" aria-hidden="true"></span> <?php esc_html_e( 'Approve', 'sena-studio' ); ?></button>
				<?php endif; ?>
				<?php if ( 'skipped' !== $p['status'] ) : ?>
					<button class="sena-btn sena-btn--ghost" type="submit" name="do" value="skip"><?php esc_html_e( 'Skip', 'sena-studio' ); ?></button>
				<?php endif; ?>
				<button class="sena-btn sena-btn--ghost" type="submit" name="do" value="save"><?php esc_html_e( 'Save edits', 'sena-studio' ); ?></button>
				<button class="sena-btn sena-btn--ghost" type="button" data-copy-caption><span class="dashicons dashicons-clipboard" aria-hidden="true"></span> <?php esc_html_e( 'Copy caption', 'sena-studio' ); ?></button>
				<?php if ( 'approved' === $p['status'] ) : ?>
					<button class="sena-btn sena-btn--dark" type="submit" name="do" value="publish"><?php esc_html_e( 'Mark published', 'sena-studio' ); ?></button>
				<?php endif; ?>
				<?php if ( 'draft' !== $p['status'] ) : ?>
					<button class="sena-link" type="submit" name="do" value="draft"><?php esc_html_e( 'Back to queue', 'sena-studio' ); ?></button>
				<?php endif; ?>
				<a class="sena-link sena-link--danger" data-confirm="<?php esc_attr_e( 'Delete this post?', 'sena-studio' ); ?>" href="<?php echo esc_url( Sena_Studio::action_url( 'sena_studio_post_delete', array( 'post_id' => $p['id'], 'status' => $status ) ) ); ?>"><?php esc_html_e( 'Delete', 'sena-studio' ); ?></a>
			</div>
		</form>
	</article>
<?php endforeach; ?>
</div>

<dialog class="sena-dialog" id="sena-add-post" <?php echo ! empty( $show_add ) ? 'data-auto-open' : ''; ?>>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sena-form">
		<div class="sena-dialog__head">
			<h2><?php esc_html_e( 'New post', 'sena-studio' ); ?></h2>
			<button type="button" class="sena-dialog__close" data-close aria-label="<?php esc_attr_e( 'Close', 'sena-studio' ); ?>">&times;</button>
		</div>
		<?php wp_nonce_field( 'sena_studio_post_create' ); Sena_Studio::return_field(); ?>
		<input type="hidden" name="action" value="sena_studio_post_create">
		<input type="hidden" name="image_id" id="sena-post-image-id" value="">
		<div class="sena-row">
			<label class="sena-field sena-grow"><span class="sena-label"><?php esc_html_e( 'Platform', 'sena-studio' ); ?></span>
				<select name="platform">
					<?php foreach ( $platforms as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?>
				</select>
			</label>
			<label class="sena-field sena-grow"><span class="sena-label"><?php esc_html_e( 'Piece', 'sena-studio' ); ?></span>
				<select name="product_id">
					<option value="0"><?php esc_html_e( 'None', 'sena-studio' ); ?></option>
					<?php foreach ( $products as $prod ) : ?><option value="<?php echo esc_attr( (string) $prod->get_id() ); ?>"><?php echo esc_html( $prod->get_name() ); ?></option><?php endforeach; ?>
				</select>
			</label>
		</div>
		<div class="sena-field">
			<span class="sena-label"><?php esc_html_e( 'Image (optional, defaults to the piece photo)', 'sena-studio' ); ?></span>
			<div class="sena-photos" id="sena-post-image-preview"></div>
			<button type="button" class="sena-btn sena-btn--ghost sena-btn--small" id="sena-pick-post-image"><?php esc_html_e( 'Choose image', 'sena-studio' ); ?></button>
		</div>
		<label class="sena-field"><span class="sena-label"><?php esc_html_e( 'Caption', 'sena-studio' ); ?></span><textarea name="caption" rows="5" required></textarea></label>
		<label class="sena-field"><span class="sena-label"><?php esc_html_e( 'Hashtags', 'sena-studio' ); ?></span><input type="text" name="hashtags" placeholder="#handmade #leather"></label>
		<label class="sena-field"><span class="sena-label"><?php esc_html_e( 'Post on', 'sena-studio' ); ?></span><input type="datetime-local" name="scheduled"></label>
		<div class="sena-dialog__foot">
			<button type="button" class="sena-btn sena-btn--ghost" data-close><?php esc_html_e( 'Cancel', 'sena-studio' ); ?></button>
			<button type="submit" class="sena-btn"><?php esc_html_e( 'Add to queue', 'sena-studio' ); ?></button>
		</div>
	</form>
</dialog>
