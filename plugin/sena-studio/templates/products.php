<?php
/**
 * Pieces tab.
 *
 * @var WC_Product[] $products
 * @var string       $filter
 * @var string       $search
 * @var bool         $show_add
 * @var array        $statuses
 * @var WP_Term[]    $categories
 */
defined( 'ABSPATH' ) || exit;
$filters = array( 'all' => __( 'All', 'sena-studio' ) ) + $statuses + array( 'low' => __( 'Low stock', 'sena-studio' ) );
$back    = array( 'filter' => $filter, 's' => $search );
?>
<div class="sena-title-row">
	<h1 class="sena-title"><?php esc_html_e( 'Pieces', 'sena-studio' ); ?></h1>
	<button type="button" class="sena-btn sena-btn--big" data-open="#sena-add-piece"><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span> <?php esc_html_e( 'Add a piece', 'sena-studio' ); ?></button>
</div>

<div class="sena-toolbar">
	<div class="sena-segment sena-segment--scroll">
		<?php foreach ( $filters as $key => $label ) : ?>
			<a class="sena-segment__item<?php echo $key === $filter ? ' is-active' : ''; ?>" href="<?php echo esc_url( Sena_Studio::url( 'products', array( 'filter' => $key ) ) ); ?>"><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</div>
	<form class="sena-search" method="get" action="<?php echo esc_url( Sena_Studio::form_action( 'products' ) ); ?>" role="search">
		<?php if ( ! Sena_Studio::is_portal() ) : ?>
			<input type="hidden" name="page" value="<?php echo esc_attr( Sena_Studio::PAGE ); ?>">
			<input type="hidden" name="tab" value="products">
		<?php endif; ?>
		<label class="screen-reader-text" for="sena-product-search"><?php esc_html_e( 'Search pieces', 'sena-studio' ); ?></label>
		<input id="sena-product-search" type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search pieces', 'sena-studio' ); ?>">
		<button class="sena-btn sena-btn--small" type="submit"><?php esc_html_e( 'Search', 'sena-studio' ); ?></button>
	</form>
</div>

<?php if ( empty( $products ) ) : ?>
	<section class="sena-card sena-card--empty"><p><?php esc_html_e( 'No pieces here yet.', 'sena-studio' ); ?></p></section>
<?php endif; ?>

<div class="sena-grid sena-grid--products">
<?php foreach ( $products as $product ) : ?>
	<?php
	if ( ! $product instanceof WC_Product ) {
		continue;
	}
	$id     = $product->get_id();
	$status = Sena_Studio_Products::status( $product );
	$stock  = $product->managing_stock() ? (string) $product->get_stock_quantity() : ( $product->is_in_stock() ? '∞' : '0' );
	$etsy   = (string) $product->get_meta( Sena_Studio_Products::META_ETSY );
	?>
	<article class="sena-card sena-product" id="product-<?php echo esc_attr( (string) $id ); ?>">
		<div class="sena-product__thumb"><?php echo wp_kses_post( $product->get_image( 'woocommerce_thumbnail' ) ); ?></div>
		<div class="sena-product__body">
			<div class="sena-product__head">
				<h3 class="sena-product__name"><?php echo esc_html( $product->get_name() ); ?></h3>
				<span class="sena-chip sena-chip--<?php echo esc_attr( $status ); ?>"><?php echo esc_html( $statuses[ $status ] ); ?></span>
			</div>
			<div class="sena-product__facts">
				<span><strong><?php echo esc_html( Sena_Studio::money( $product->get_price() ) ); ?></strong></span>
				<span class="sena-muted"><?php esc_html_e( 'Stock', 'sena-studio' ); ?> <?php echo esc_html( $stock ); ?></span>
				<?php if ( 'publish' !== $product->get_status() ) : ?><span class="sena-chip sena-chip--soft"><?php echo esc_html( $product->get_status() ); ?></span><?php endif; ?>
			</div>

			<div class="sena-product__actions">
				<button type="button" class="sena-btn sena-btn--small sena-btn--ghost" data-toggle="#price-<?php echo esc_attr( (string) $id ); ?>"><?php esc_html_e( 'Price', 'sena-studio' ); ?></button>
				<button type="button" class="sena-btn sena-btn--small sena-btn--ghost" data-toggle="#stock-<?php echo esc_attr( (string) $id ); ?>"><?php esc_html_e( 'Stock', 'sena-studio' ); ?></button>
				<button type="button" class="sena-btn sena-btn--small sena-btn--ghost" data-toggle="#status-<?php echo esc_attr( (string) $id ); ?>"><?php esc_html_e( 'Status', 'sena-studio' ); ?></button>
				<?php if ( 'sold' !== $status ) : ?>
					<a class="sena-btn sena-btn--small" data-confirm="<?php esc_attr_e( 'Mark this piece as sold?', 'sena-studio' ); ?>" href="<?php echo esc_url( Sena_Studio::action_url( 'sena_studio_product_quick', array_merge( array( 'product_id' => $id, 'do' => 'sold' ), $back ) ) ); ?>"><?php esc_html_e( 'Mark sold', 'sena-studio' ); ?></a>
				<?php endif; ?>
			</div>

			<form class="sena-inline-form is-hidden" id="price-<?php echo esc_attr( (string) $id ); ?>" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'sena_studio_product_quick' ); Sena_Studio::return_field(); ?>
				<input type="hidden" name="action" value="sena_studio_product_quick"><input type="hidden" name="do" value="price">
				<input type="hidden" name="product_id" value="<?php echo esc_attr( (string) $id ); ?>">
				<input type="hidden" name="filter" value="<?php echo esc_attr( $filter ); ?>"><input type="hidden" name="s" value="<?php echo esc_attr( $search ); ?>">
				<label class="sena-grow"><span><?php esc_html_e( 'New price', 'sena-studio' ); ?></span><input type="text" inputmode="decimal" name="price" value="<?php echo esc_attr( $product->get_regular_price() ); ?>"></label>
				<button class="sena-btn sena-btn--small" type="submit"><?php esc_html_e( 'Save', 'sena-studio' ); ?></button>
			</form>

			<form class="sena-inline-form is-hidden" id="stock-<?php echo esc_attr( (string) $id ); ?>" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'sena_studio_product_quick' ); Sena_Studio::return_field(); ?>
				<input type="hidden" name="action" value="sena_studio_product_quick"><input type="hidden" name="do" value="stock">
				<input type="hidden" name="product_id" value="<?php echo esc_attr( (string) $id ); ?>">
				<input type="hidden" name="filter" value="<?php echo esc_attr( $filter ); ?>"><input type="hidden" name="s" value="<?php echo esc_attr( $search ); ?>">
				<label class="sena-grow"><span><?php esc_html_e( 'Stock', 'sena-studio' ); ?></span><input type="number" min="0" step="1" name="stock" value="<?php echo esc_attr( (string) ( $product->get_stock_quantity() ?? 0 ) ); ?>"></label>
				<button class="sena-btn sena-btn--small" type="submit"><?php esc_html_e( 'Save', 'sena-studio' ); ?></button>
			</form>

			<form class="sena-inline-form is-hidden" id="status-<?php echo esc_attr( (string) $id ); ?>" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'sena_studio_product_quick' ); Sena_Studio::return_field(); ?>
				<input type="hidden" name="action" value="sena_studio_product_quick"><input type="hidden" name="do" value="status">
				<input type="hidden" name="product_id" value="<?php echo esc_attr( (string) $id ); ?>">
				<input type="hidden" name="filter" value="<?php echo esc_attr( $filter ); ?>"><input type="hidden" name="s" value="<?php echo esc_attr( $search ); ?>">
				<label class="sena-grow"><span><?php esc_html_e( 'Status', 'sena-studio' ); ?></span>
					<select name="status">
						<?php foreach ( $statuses as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<button class="sena-btn sena-btn--small" type="submit"><?php esc_html_e( 'Save', 'sena-studio' ); ?></button>
			</form>

			<div class="sena-product__links">
				<a href="<?php echo esc_url( get_edit_post_link( $id ) ?: '' ); ?>"><?php esc_html_e( 'Full edit', 'sena-studio' ); ?></a>
				<a href="<?php echo esc_url( $product->get_permalink() ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View', 'sena-studio' ); ?></a>
				<?php if ( $etsy ) : ?><a href="<?php echo esc_url( $etsy ); ?>" target="_blank" rel="noopener">Etsy</a><?php endif; ?>
			</div>
		</div>
	</article>
<?php endforeach; ?>
</div>

<dialog class="sena-dialog" id="sena-add-piece" <?php echo $show_add ? 'data-auto-open' : ''; ?>>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" class="sena-form">
		<div class="sena-dialog__head">
			<h2><?php esc_html_e( 'Add a piece', 'sena-studio' ); ?></h2>
			<button type="button" class="sena-dialog__close" data-close aria-label="<?php esc_attr_e( 'Close', 'sena-studio' ); ?>">&times;</button>
		</div>
		<?php wp_nonce_field( 'sena_studio_add_piece' ); Sena_Studio::return_field(); ?>
		<input type="hidden" name="action" value="sena_studio_add_piece">
		<input type="hidden" name="image_ids" id="sena-image-ids" value="">

		<div class="sena-field">
			<span class="sena-label"><?php esc_html_e( 'Photos', 'sena-studio' ); ?></span>
			<div class="sena-photos" id="sena-photo-preview" aria-live="polite"></div>
			<div class="sena-row">
				<button type="button" class="sena-btn sena-btn--ghost" id="sena-pick-photos"><span class="dashicons dashicons-format-gallery" aria-hidden="true"></span> <?php esc_html_e( 'From library', 'sena-studio' ); ?></button>
				<label class="sena-btn sena-btn--ghost sena-file">
					<span class="dashicons dashicons-camera" aria-hidden="true"></span> <?php esc_html_e( 'Camera / upload', 'sena-studio' ); ?>
					<input type="file" name="photos[]" accept="image/*" capture="environment" multiple>
				</label>
			</div>
			<p class="sena-help"><?php esc_html_e( 'First photo becomes the main image.', 'sena-studio' ); ?></p>
		</div>

		<label class="sena-field"><span class="sena-label"><?php esc_html_e( 'Name', 'sena-studio' ); ?></span><input type="text" name="name" required maxlength="120" autocomplete="off"></label>
		<div class="sena-row">
			<label class="sena-field sena-grow"><span class="sena-label"><?php esc_html_e( 'Price', 'sena-studio' ); ?></span><input type="text" inputmode="decimal" name="price" required placeholder="0.00"></label>
			<label class="sena-field sena-grow"><span class="sena-label"><?php esc_html_e( 'Category', 'sena-studio' ); ?></span>
				<select name="category">
					<option value="0"><?php esc_html_e( 'None', 'sena-studio' ); ?></option>
					<?php foreach ( $categories as $term ) : ?>
						<option value="<?php echo esc_attr( (string) $term->term_id ); ?>"><?php echo esc_html( $term->name ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
		</div>

		<fieldset class="sena-field">
			<legend class="sena-label"><?php esc_html_e( 'Kind', 'sena-studio' ); ?></legend>
			<div class="sena-radios">
				<label class="sena-radio"><input type="radio" name="kind" value="one_of_a_kind" checked><span><strong><?php esc_html_e( 'One of a kind', 'sena-studio' ); ?></strong><small><?php esc_html_e( 'Stock 1, sold individually', 'sena-studio' ); ?></small></span></label>
				<label class="sena-radio"><input type="radio" name="kind" value="made_to_order"><span><strong><?php esc_html_e( 'Made to order', 'sena-studio' ); ?></strong><small><?php esc_html_e( 'No stock limit', 'sena-studio' ); ?></small></span></label>
			</div>
		</fieldset>

		<label class="sena-field"><span class="sena-label"><?php esc_html_e( 'Short story', 'sena-studio' ); ?></span><textarea name="story" rows="4" placeholder="<?php esc_attr_e( 'Materials, inspiration, size…', 'sena-studio' ); ?>"></textarea></label>
		<label class="sena-field"><span class="sena-label"><?php esc_html_e( 'Etsy link (optional)', 'sena-studio' ); ?></span><input type="url" name="etsy" placeholder="https://www.etsy.com/listing/…" inputmode="url"></label>

		<div class="sena-dialog__foot">
			<button type="button" class="sena-btn sena-btn--ghost" data-close><?php esc_html_e( 'Cancel', 'sena-studio' ); ?></button>
			<button type="submit" class="sena-btn sena-btn--big"><?php esc_html_e( 'Publish piece', 'sena-studio' ); ?></button>
		</div>
	</form>
</dialog>
