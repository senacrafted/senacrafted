<?php
/**
 * Events tab.
 *
 * @var array[] $events
 * @var string  $scope
 * @var array   $editing
 * @var bool    $show_add
 */
defined( 'ABSPATH' ) || exit;
$open_form = $show_add || ! empty( $editing );
$e         = $editing ?: array( 'id' => 0, 'title' => '', 'date' => '', 'place' => '', 'link' => '', 'notes' => '' );
?>
<div class="sena-title-row">
	<h1 class="sena-title"><?php esc_html_e( 'Events', 'sena-studio' ); ?></h1>
	<a class="sena-btn" href="<?php echo esc_url( Sena_Studio::url( 'events', array( 'add' => 1 ) ) ); ?>"><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span> <?php esc_html_e( 'Add event', 'sena-studio' ); ?></a>
</div>

<div class="sena-toolbar">
	<div class="sena-segment">
		<a class="sena-segment__item<?php echo 'upcoming' === $scope ? ' is-active' : ''; ?>" href="<?php echo esc_url( Sena_Studio::url( 'events' ) ); ?>"><?php esc_html_e( 'Upcoming', 'sena-studio' ); ?></a>
		<a class="sena-segment__item<?php echo 'past' === $scope ? ' is-active' : ''; ?>" href="<?php echo esc_url( Sena_Studio::url( 'events', array( 'scope' => 'past' ) ) ); ?>"><?php esc_html_e( 'Past', 'sena-studio' ); ?></a>
	</div>
</div>

<?php if ( $open_form ) : ?>
	<section class="sena-card sena-form" id="sena-event-form">
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'sena_studio_event_save' ); Sena_Studio::return_field(); ?>
			<input type="hidden" name="action" value="sena_studio_event_save">
			<input type="hidden" name="event_id" value="<?php echo esc_attr( (string) $e['id'] ); ?>">
			<h2><?php echo $e['id'] ? esc_html__( 'Edit event', 'sena-studio' ) : esc_html__( 'New event', 'sena-studio' ); ?></h2>
			<label class="sena-field"><span class="sena-label"><?php esc_html_e( 'Name', 'sena-studio' ); ?></span><input type="text" name="title" value="<?php echo esc_attr( $e['title'] ); ?>" required maxlength="140"></label>
			<div class="sena-row">
				<label class="sena-field sena-grow"><span class="sena-label"><?php esc_html_e( 'Date', 'sena-studio' ); ?></span><input type="date" name="event_date" value="<?php echo esc_attr( $e['date'] ); ?>" required></label>
				<label class="sena-field sena-grow"><span class="sena-label"><?php esc_html_e( 'Place', 'sena-studio' ); ?></span><input type="text" name="event_place" value="<?php echo esc_attr( $e['place'] ); ?>" placeholder="<?php esc_attr_e( 'Market, city…', 'sena-studio' ); ?>"></label>
			</div>
			<label class="sena-field"><span class="sena-label"><?php esc_html_e( 'Link (optional)', 'sena-studio' ); ?></span><input type="url" name="event_link" value="<?php echo esc_attr( $e['link'] ); ?>" placeholder="https://" inputmode="url"></label>
			<label class="sena-field"><span class="sena-label"><?php esc_html_e( 'Notes', 'sena-studio' ); ?></span><textarea name="notes" rows="3"><?php echo esc_textarea( $e['notes'] ); ?></textarea></label>
			<div class="sena-actions">
				<button type="submit" class="sena-btn"><?php echo $e['id'] ? esc_html__( 'Save event', 'sena-studio' ) : esc_html__( 'Add event', 'sena-studio' ); ?></button>
				<a class="sena-btn sena-btn--ghost" href="<?php echo esc_url( Sena_Studio::url( 'events' ) ); ?>"><?php esc_html_e( 'Cancel', 'sena-studio' ); ?></a>
			</div>
		</form>
	</section>
<?php endif; ?>

<?php if ( empty( $events ) ) : ?>
	<section class="sena-card sena-card--empty"><p><?php echo 'past' === $scope ? esc_html__( 'No past events yet.', 'sena-studio' ) : esc_html__( 'No upcoming events. Add your next market or festival.', 'sena-studio' ); ?></p></section>
<?php endif; ?>

<div class="sena-stack">
<?php foreach ( $events as $ev ) : ?>
	<article class="sena-card sena-event">
		<div class="sena-event__date">
			<span class="sena-event__day"><?php echo esc_html( $ev['ts'] ? wp_date( 'j', $ev['ts'] ) : '' ); ?></span>
			<span class="sena-event__month"><?php echo esc_html( $ev['ts'] ? wp_date( 'M', $ev['ts'] ) : '' ); ?></span>
		</div>
		<div class="sena-event__body">
			<h3><?php echo esc_html( $ev['title'] ); ?> <?php if ( 'publish' !== $ev['status'] ) : ?><span class="sena-chip sena-chip--soft"><?php echo esc_html( $ev['status'] ); ?></span><?php endif; ?></h3>
			<div class="sena-muted"><?php echo esc_html( $ev['date_label'] ); ?><?php echo $ev['place'] ? ' &middot; ' . esc_html( $ev['place'] ) : ''; ?> &middot; <?php echo esc_html( $ev['relative'] ); ?></div>
			<?php if ( $ev['notes'] ) : ?><p class="sena-event__notes"><?php echo esc_html( wp_trim_words( $ev['notes'], 30 ) ); ?></p><?php endif; ?>
			<div class="sena-event__links">
				<a class="sena-link" href="<?php echo esc_url( Sena_Studio::url( 'events', array( 'scope' => $scope, 'event' => $ev['id'] ) ) ); ?>#sena-event-form"><?php esc_html_e( 'Edit', 'sena-studio' ); ?></a>
				<?php if ( $ev['link'] ) : ?><a class="sena-link" href="<?php echo esc_url( $ev['link'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Event page', 'sena-studio' ); ?></a><?php endif; ?>
				<a class="sena-link" href="<?php echo esc_url( get_permalink( $ev['id'] ) ?: '' ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View', 'sena-studio' ); ?></a>
				<a class="sena-link sena-link--danger" data-confirm="<?php esc_attr_e( 'Remove this event?', 'sena-studio' ); ?>" href="<?php echo esc_url( Sena_Studio::action_url( 'sena_studio_event_delete', array( 'event_id' => $ev['id'] ) ) ); ?>"><?php esc_html_e( 'Remove', 'sena-studio' ); ?></a>
			</div>
		</div>
	</article>
<?php endforeach; ?>
</div>
