<?php
/**
 * Today tab: greeting, monthly goal, numbers, charts, best sellers,
 * next steps, seasonal ideas and quick actions.
 *
 * @var array $data
 */
defined( 'ABSPATH' ) || exit;

$goal      = $data['goal'];
$stats     = $data['stats'];
$visitors  = $data['visitors'];
$has_wc    = $data['has_wc'];
$has_sales = $data['has_sales'];
$labels    = Sena_Studio_Dashboard::channel_labels();
?>
<header class="sena-hello">
	<p class="sena-hello__date"><?php echo esc_html( $data['date_label'] ); ?></p>
	<h1 class="sena-title"><?php echo esc_html( $data['greeting'] ); ?>, <?php echo esc_html( $data['first_name'] ); ?></h1>
	<p class="sena-pulse"><?php echo esc_html( $data['pulse'] ); ?></p>
</header>

<?php /* ---- 2. Monthly goal ---- */ ?>
<section class="sena-card sena-goal" aria-labelledby="sena-goal-title">
	<div class="sena-goal__head">
		<h2 id="sena-goal-title"><?php echo esc_html( sprintf( /* translators: %s: month name */ __( '%s goal', 'sena-studio' ), $goal['month_label'] ) ); ?></h2>
		<button type="button" class="sena-link" data-toggle="#sena-goal-form" aria-controls="sena-goal-form"><span class="dashicons dashicons-edit" aria-hidden="true"></span> <?php esc_html_e( 'Edit', 'sena-studio' ); ?></button>
	</div>

	<?php if ( $goal['amount'] > 0 ) : ?>
		<p class="sena-goal__big">
			<strong><?php echo esc_html( Sena_Studio::money( $goal['sales'] ) ); ?></strong>
			<span class="sena-muted"><?php echo esc_html( sprintf( /* translators: %s: goal amount */ __( 'of %s', 'sena-studio' ), Sena_Studio::money( $goal['amount'] ) ) ); ?></span>
		</p>
		<div class="sena-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( (string) $goal['percent'] ); ?>" aria-label="<?php esc_attr_e( 'Progress towards the monthly goal', 'sena-studio' ); ?>">
			<span class="sena-progress__bar<?php echo $goal['percent'] >= 100 ? ' is-done' : ''; ?>" style="width: <?php echo esc_attr( (string) $goal['percent'] ); ?>%"></span>
		</div>
		<p class="sena-goal__meta">
			<span class="sena-chip sena-chip--soft"><?php echo esc_html( $goal['percent'] . '%' ); ?></span>
			<span><?php echo esc_html( sprintf( /* translators: %d: days */ _n( '%d day left', '%d days left', $goal['days_left'], 'sena-studio' ), $goal['days_left'] ) ); ?></span>
			<?php if ( ! $has_wc ) : ?>
				<span class="sena-muted"><?php esc_html_e( 'Sales appear once WooCommerce is active.', 'sena-studio' ); ?></span>
			<?php elseif ( $goal['percent'] >= 100 ) : ?>
				<span class="sena-goal__cheer"><?php esc_html_e( 'Goal reached. Treat yourself to good coffee.', 'sena-studio' ); ?></span>
			<?php else : ?>
				<span><?php echo esc_html( sprintf( /* translators: %s: amount */ __( 'you need about %s per day', 'sena-studio' ), Sena_Studio::money( $goal['per_day'] ) ) ); ?></span>
			<?php endif; ?>
		</p>
	<?php else : ?>
		<p class="sena-muted"><?php esc_html_e( 'Set a monthly sales goal. Even a small one makes the month feel like a game you can win.', 'sena-studio' ); ?></p>
	<?php endif; ?>

	<form id="sena-goal-form" class="sena-inline-form is-hidden" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'sena_studio_save_goal' ); Sena_Studio::return_field(); ?>
		<input type="hidden" name="action" value="sena_studio_save_goal">
		<label class="sena-grow"><span><?php esc_html_e( 'Monthly goal', 'sena-studio' ); ?></span>
			<input type="number" name="monthly_goal" min="0" step="1" inputmode="decimal" value="<?php echo esc_attr( (string) round( $goal['amount'] ) ); ?>">
		</label>
		<button type="submit" class="sena-btn sena-btn--small"><?php esc_html_e( 'Save goal', 'sena-studio' ); ?></button>
	</form>
</section>

<?php /* ---- 2b. Milestones ---- */ ?>
<?php $ms = $data['milestones']; ?>
<section class="sena-card sena-milestones" aria-labelledby="sena-ms-title">
	<div class="sena-goal__head">
		<h2 id="sena-ms-title"><?php esc_html_e( 'Milestones', 'sena-studio' ); ?></h2>
		<span class="sena-chip sena-chip--soft"><?php echo esc_html( sprintf( /* translators: 1: done, 2: total */ __( '%1$d of %2$d', 'sena-studio' ), $ms['done'], $ms['total'] ) ); ?></span>
	</div>
	<?php if ( $ms['total'] ) : ?>
		<div class="sena-strip" aria-hidden="true">
			<?php foreach ( $ms['items'] as $m ) : ?>
				<span class="sena-strip__seg<?php echo $m['done'] ? ' is-done' : ( $m['current'] ? ' is-current' : '' ); ?>"></span>
			<?php endforeach; ?>
		</div>
		<ol class="sena-ladder">
			<?php foreach ( $ms['items'] as $i => $m ) : ?>
				<li class="sena-rung<?php echo $m['done'] ? ' is-done' : ''; ?><?php echo $m['current'] ? ' is-current' : ''; ?>" <?php echo $m['current'] ? 'aria-current="step"' : ''; ?>>
					<span class="sena-rung__mark" aria-hidden="true"><?php echo $m['done'] ? '<span class="dashicons dashicons-yes"></span>' : esc_html( (string) ( $i + 1 ) ); ?></span>
					<div class="sena-rung__body">
						<strong class="sena-rung__title">
							<?php echo esc_html( $m['title'] ?? '' ); ?>
							<?php if ( $m['done'] ) : ?><span class="screen-reader-text"><?php esc_html_e( '(done)', 'sena-studio' ); ?></span><?php endif; ?>
							<?php if ( ! $m['done'] && ! empty( $m['progress'] ) ) : ?><span class="sena-chip sena-chip--soft"><?php echo esc_html( $m['progress'] ); ?></span><?php endif; ?>
						</strong>
						<?php if ( $m['current'] ) : ?>
							<span class="sena-rung__why"><?php echo esc_html( $m['why'] ?? '' ); ?></span>
							<span class="sena-rung__cheer"><?php echo esc_html( $ms['cheer'] ); ?></span>
							<div class="sena-rung__actions">
								<?php if ( ! empty( $m['url'] ) && ! empty( $m['button'] ) ) : ?>
									<a class="sena-btn sena-btn--small" href="<?php echo esc_url( $m['url'] ); ?>"><?php echo esc_html( $m['button'] ); ?></a>
								<?php endif; ?>
								<a class="sena-link sena-link--danger" href="<?php echo esc_url( Sena_Studio::action_url( 'sena_studio_milestone', array( 'milestone' => $m['id'] ) ) ); ?>"><?php esc_html_e( 'Tick by hand', 'sena-studio' ); ?></a>
							</div>
						<?php elseif ( $m['done'] && $m['manual'] ) : ?>
							<a class="sena-link sena-link--danger sena-rung__undo" href="<?php echo esc_url( Sena_Studio::action_url( 'sena_studio_milestone', array( 'milestone' => $m['id'], 'undo' => 1 ) ) ); ?>"><?php esc_html_e( 'Untick', 'sena-studio' ); ?></a>
						<?php elseif ( ! $m['done'] ) : ?>
							<span class="sena-rung__why sena-rung__why--soft"><?php echo esc_html( $m['why'] ?? '' ); ?></span>
						<?php endif; ?>
					</div>
				</li>
			<?php endforeach; ?>
		</ol>
	<?php else : ?>
		<p class="sena-muted"><?php esc_html_e( 'No milestones defined.', 'sena-studio' ); ?></p>
	<?php endif; ?>
</section>

<?php /* ---- 3. Numbers that matter ---- */ ?>
<section class="sena-section" aria-labelledby="sena-numbers-title">
	<h2 id="sena-numbers-title" class="sena-section__title"><?php esc_html_e( 'Numbers that matter', 'sena-studio' ); ?></h2>
	<div class="sena-tiles">
		<?php if ( $has_wc ) : ?>
			<a class="sena-tile" href="<?php echo esc_url( Sena_Studio::url( 'orders', array( 'filter' => 'all' ) ) ); ?>">
				<span class="sena-tile__num"><?php echo esc_html( Sena_Studio::money( $stats['week'] ) ); ?></span>
				<span class="sena-tile__label"><?php esc_html_e( 'sales this week', 'sena-studio' ); ?></span>
				<span class="sena-tile__trend sena-tile__trend--<?php echo esc_attr( $stats['trend'] ); ?>">
					<?php if ( 'up' === $stats['trend'] ) : ?>
						<span class="dashicons dashicons-arrow-up-alt" aria-hidden="true"></span>
					<?php elseif ( 'down' === $stats['trend'] ) : ?>
						<span class="dashicons dashicons-arrow-down-alt" aria-hidden="true"></span>
					<?php endif; ?>
					<?php echo esc_html( sprintf( /* translators: %s: amount */ __( 'last week %s', 'sena-studio' ), Sena_Studio::money( $stats['last_week'] ) ) ); ?>
				</span>
			</a>
			<a class="sena-tile" href="<?php echo esc_url( Sena_Studio::url( 'orders', array( 'filter' => 'all' ) ) ); ?>">
				<span class="sena-tile__num"><?php echo esc_html( number_format_i18n( $stats['orders'] ) ); ?></span>
				<span class="sena-tile__label"><?php esc_html_e( 'orders this month', 'sena-studio' ); ?></span>
			</a>
			<div class="sena-tile">
				<span class="sena-tile__num"><?php echo esc_html( $stats['orders'] ? Sena_Studio::money( $stats['aov'] ) : '–' ); ?></span>
				<span class="sena-tile__label"><?php esc_html_e( 'average order', 'sena-studio' ); ?></span>
			</div>
			<div class="sena-tile">
				<span class="sena-tile__num"><?php echo esc_html( $stats['customers'] ? round( $stats['repeat'] * 100 ) . '%' : '–' ); ?></span>
				<span class="sena-tile__label"><?php esc_html_e( 'repeat customers', 'sena-studio' ); ?></span>
				<?php if ( $stats['customers'] ) : ?>
					<span class="sena-tile__trend"><?php echo esc_html( sprintf( /* translators: %d: customers */ _n( 'of %d customer', 'of %d customers', $stats['customers'], 'sena-studio' ), $stats['customers'] ) ); ?></span>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		<a class="sena-tile" href="<?php echo esc_url( Sena_Studio::url( 'requests', array( 'status' => 'new' ) ) ); ?>">
			<span class="sena-tile__num"><?php echo esc_html( number_format_i18n( $data['requests_new'] ) ); ?></span>
			<span class="sena-tile__label"><?php esc_html_e( 'custom requests open', 'sena-studio' ); ?></span>
		</a>
		<?php if ( $has_wc ) : ?>
			<a class="sena-tile" href="<?php echo esc_url( Sena_Studio::url( 'products', array( 'filter' => 'available' ) ) ); ?>">
				<span class="sena-tile__num"><?php echo esc_html( number_format_i18n( $stats['available'] ) ); ?></span>
				<span class="sena-tile__label"><?php esc_html_e( 'pieces for sale', 'sena-studio' ); ?></span>
			</a>
		<?php endif; ?>
		<?php if ( $visitors['available'] ) : ?>
			<div class="sena-tile">
				<span class="sena-tile__num"><?php echo esc_html( number_format_i18n( $visitors['value'] ) ); ?></span>
				<span class="sena-tile__label"><?php esc_html_e( 'visitors, last 7 days', 'sena-studio' ); ?></span>
			</div>
		<?php else : ?>
			<a class="sena-tile sena-tile--ghost" href="<?php echo esc_url( $visitors['url'] ); ?>">
				<span class="sena-tile__num"><span class="dashicons dashicons-chart-area" aria-hidden="true"></span></span>
				<span class="sena-tile__label"><?php esc_html_e( 'Connect Google Analytics', 'sena-studio' ); ?></span>
				<span class="sena-tile__trend"><?php echo $visitors['installed'] ? esc_html__( 'via MonsterInsights settings', 'sena-studio' ) : esc_html__( 'install MonsterInsights', 'sena-studio' ); ?></span>
			</a>
		<?php endif; ?>
	</div>
</section>

<?php if ( $has_wc ) : ?>
<?php /* ---- 4. Sales chart ---- */ ?>
<section class="sena-card sena-chart" aria-labelledby="sena-chart-title">
	<h2 id="sena-chart-title"><?php esc_html_e( 'Last 12 weeks', 'sena-studio' ); ?></h2>
	<?php if ( $has_sales ) : ?>
		<?php echo Sena_Studio_Dashboard::bar_chart( $data['weeks'], 'sena-weeks', __( 'Net sales per week, last 12 weeks', 'sena-studio' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with escaped parts. ?>
		<h3 class="sena-chart__sub"><?php esc_html_e( 'Where sales came from (90 days)', 'sena-studio' ); ?></h3>
		<?php echo Sena_Studio_Dashboard::channel_chart( $data['channels'], __( 'Net sales by channel, last 90 days', 'sena-studio' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with escaped parts. ?>
	<?php else : ?>
		<p class="sena-muted"><?php esc_html_e( 'Your first sale will draw the first bar here. Every maker starts at zero.', 'sena-studio' ); ?></p>
	<?php endif; ?>
</section>

<?php /* ---- 5. Best sellers & unseen pieces ---- */ ?>
<div class="sena-grid sena-grid--two">
	<section class="sena-card" aria-labelledby="sena-best-title">
		<h2 id="sena-best-title"><?php esc_html_e( 'Best sellers, 90 days', 'sena-studio' ); ?></h2>
		<?php if ( $data['best'] ) : ?>
			<ol class="sena-ranked">
				<?php foreach ( $data['best'] as $i => $row ) : ?>
					<li>
						<a href="<?php echo esc_url( $row['url'] ); ?>">
							<span class="sena-ranked__pos"><?php echo esc_html( (string) ( $i + 1 ) ); ?></span>
							<span class="sena-ranked__thumb"><?php if ( $row['thumb'] ) : ?><img src="<?php echo esc_url( $row['thumb'] ); ?>" alt="" loading="lazy"><?php endif; ?></span>
							<span class="sena-ranked__body">
								<strong><?php echo esc_html( $row['name'] ); ?></strong>
								<span class="sena-muted"><?php echo esc_html( sprintf( /* translators: %d: units */ _n( '%d sold', '%d sold', $row['units'], 'sena-studio' ), $row['units'] ) ); ?> &middot; <?php echo esc_html( Sena_Studio::money( $row['revenue'] ) ); ?></span>
							</span>
						</a>
					</li>
				<?php endforeach; ?>
			</ol>
		<?php else : ?>
			<p class="sena-muted"><?php esc_html_e( 'No sales in the last 90 days yet. The first one always feels the best.', 'sena-studio' ); ?></p>
		<?php endif; ?>
	</section>

	<section class="sena-card" aria-labelledby="sena-unseen-title">
		<h2 id="sena-unseen-title"><?php esc_html_e( 'Pieces nobody has seen', 'sena-studio' ); ?></h2>
		<?php if ( $data['unseen'] ) : ?>
			<p class="sena-help"><?php esc_html_e( 'Listed, in stock, no sale in 90 days. A post is often all they need.', 'sena-studio' ); ?></p>
			<ul class="sena-ranked sena-ranked--plain">
				<?php foreach ( $data['unseen'] as $row ) : ?>
					<li>
						<a href="<?php echo esc_url( $row['url'] ); ?>">
							<span class="sena-ranked__thumb"><?php if ( $row['thumb'] ) : ?><img src="<?php echo esc_url( $row['thumb'] ); ?>" alt="" loading="lazy"><?php endif; ?></span>
							<span class="sena-ranked__body">
								<strong><?php echo esc_html( $row['name'] ); ?></strong>
								<?php if ( '' !== (string) $row['price'] ) : ?><span class="sena-muted"><?php echo esc_html( Sena_Studio::money( (float) $row['price'] ) ); ?></span><?php endif; ?>
							</span>
						</a>
						<a class="sena-btn sena-btn--ghost sena-btn--small" href="<?php echo esc_url( Sena_Studio::action_url( 'sena_studio_draft_post', array( 'product_id' => $row['id'] ) ) ); ?>"><span class="dashicons dashicons-share" aria-hidden="true"></span> <?php esc_html_e( 'Draft a post', 'sena-studio' ); ?></a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php elseif ( $stats['available'] > 0 ) : ?>
			<p class="sena-muted"><?php esc_html_e( 'Every piece for sale has found a buyer recently. That is a very good sign.', 'sena-studio' ); ?></p>
		<?php else : ?>
			<p class="sena-muted"><?php esc_html_e( 'Add a piece and it will show up here until it sells.', 'sena-studio' ); ?></p>
		<?php endif; ?>
	</section>
</div>
<?php endif; ?>

<?php /* ---- 6. Next steps ---- */ ?>
<section class="sena-card sena-steps" aria-labelledby="sena-steps-title">
	<h2 id="sena-steps-title"><?php esc_html_e( 'Next steps', 'sena-studio' ); ?></h2>
	<?php if ( $data['steps'] ) : ?>
		<ol class="sena-steps__list">
			<?php foreach ( $data['steps'] as $step ) : ?>
				<li class="sena-step">
					<div class="sena-step__body">
						<strong class="sena-step__title"><?php echo esc_html( $step['title'] ?? '' ); ?></strong>
						<?php if ( ! empty( $step['why'] ) ) : ?><span class="sena-step__why"><?php echo esc_html( $step['why'] ); ?></span><?php endif; ?>
					</div>
					<div class="sena-step__actions">
						<a class="sena-btn sena-btn--small" href="<?php echo esc_url( $step['url'] ?? Sena_Studio::url( 'today' ) ); ?>"><?php echo esc_html( $step['button'] ?? __( 'Open', 'sena-studio' ) ); ?></a>
						<a class="sena-link sena-link--danger" href="<?php echo esc_url( Sena_Studio::action_url( 'sena_studio_dismiss_step', array( 'step' => $step['id'] ) ) ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: step title */ __( 'Hide "%s" for a week', 'sena-studio' ), $step['title'] ) ); ?>"><?php esc_html_e( 'Later', 'sena-studio' ); ?></a>
					</div>
				</li>
			<?php endforeach; ?>
		</ol>
	<?php else : ?>
		<p class="sena-muted"><?php esc_html_e( 'All clear. Nothing on the list, so the bench is yours.', 'sena-studio' ); ?></p>
	<?php endif; ?>
</section>

<?php /* ---- 7. This season ---- */ ?>
<section class="sena-section" aria-labelledby="sena-season-title">
	<h2 id="sena-season-title" class="sena-section__title"><?php esc_html_e( 'This season', 'sena-studio' ); ?></h2>
	<div class="sena-grid sena-grid--ideas">
		<?php foreach ( array_slice( (array) $data['season'], 0, 3 ) as $idea ) : ?>
			<article class="sena-card sena-idea">
				<h3><?php echo esc_html( $idea['title'] ?? '' ); ?></h3>
				<p><?php echo esc_html( $idea['text'] ?? '' ); ?></p>
				<?php if ( ! empty( $idea['url'] ) && ! empty( $idea['cta'] ) ) : ?>
					<a class="sena-link" href="<?php echo esc_url( $idea['url'] ); ?>"><?php echo esc_html( $idea['cta'] ); ?> &rarr;</a>
				<?php endif; ?>
			</article>
		<?php endforeach; ?>
	</div>
</section>

<?php /* ---- 8. Quick actions ---- */ ?>
<div class="sena-quick sena-quick--today">
	<a class="sena-btn sena-btn--big" href="<?php echo esc_url( Sena_Studio::url( 'products', array( 'add' => 1 ) ) ); ?>"><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span> <?php esc_html_e( 'Add a piece', 'sena-studio' ); ?></a>
	<a class="sena-btn sena-btn--ghost" href="<?php echo esc_url( Sena_Studio::url( 'events', array( 'add' => 1 ) ) ); ?>"><span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span> <?php esc_html_e( 'Add an event', 'sena-studio' ); ?></a>
	<a class="sena-btn sena-btn--ghost" href="<?php echo esc_url( Sena_Studio::url( 'posts', array( 'add' => 1 ) ) ); ?>"><span class="dashicons dashicons-share" aria-hidden="true"></span> <?php esc_html_e( 'New post', 'sena-studio' ); ?></a>
	<?php if ( $has_wc && $data['to_pack_count'] > 0 ) : ?>
		<a class="sena-btn sena-btn--dark" href="<?php echo esc_url( Sena_Studio::action_url( 'sena_studio_packing_slips' ) ); ?>" target="_blank" rel="noopener"><span class="dashicons dashicons-printer" aria-hidden="true"></span> <?php echo esc_html( sprintf( /* translators: %d: orders */ _n( 'Print %d packing slip', 'Print %d packing slips', $data['to_pack_count'], 'sena-studio' ), $data['to_pack_count'] ) ); ?></a>
	<?php endif; ?>
</div>
