<?php
/**
 * The one screen this plugin has: Portfolio Import, under Tools.
 *
 * It shows exactly what the import would do before it does it, then one button
 * runs the lot. Both actions are POSTs behind a nonce and a capability check,
 * and both are re-runnable: nothing here creates a duplicate or overwrites work
 * done by hand.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMPFI_Admin_Page {

	const SLUG   = 'kmpfi-portfolio-import';
	const ACTION = 'kmpfi_run';
	const NONCE  = 'kmpfi_nonce';

	/** @var array|null Result of the run just performed, for the notice. */
	private $result = null;

	/** @var array|null */
	private $media_result = null;

	/** @var array|null */
	private $terms_result = null;

	/** @var array|null */
	private $zip_result = null;

	/** @var array|null */
	private $dates_result = null;


	public function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'maybe_run' ) );
		add_action( 'wp_ajax_kmpfi_step', array( $this, 'ajax_step' ) );
	}

	/**
	 * One batch of the photo import, called over and over by the progress bar
	 * until it reports done.
	 */
	public function ajax_step() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Not allowed.', 'kirollos-magdy-portfolio-importer' ) ), 403 );
		}

		check_ajax_referer( self::ACTION, 'nonce' );

		$batch = isset( $_POST['batch'] ) ? absint( $_POST['batch'] ) : 2;

		wp_send_json_success( KMPFI_Gallery_Zip::step( $batch ) );
	}

	/**
	 * Under the "Kirollos Magdy" menu when Portfolio Builder is active, under Tools otherwise.
	 */
	public function menu() {
		add_submenu_page(
			defined( 'KMPB_ADMIN_MENU' ) ? KMPB_ADMIN_MENU : 'tools.php',
			__( 'Portfolio Import', 'kirollos-magdy-portfolio-importer' ),
			__( 'Portfolio Import', 'kirollos-magdy-portfolio-importer' ),
			'manage_options',
			self::SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Handles the POST before the page renders, so the screen shows the state
	 * after the import rather than before it.
	 */
	public function maybe_run() {

		if ( ! isset( $_POST[ self::ACTION ] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ self::NONCE ] ) ), self::ACTION ) ) {
			return;
		}

		$what = sanitize_key( wp_unslash( $_POST[ self::ACTION ] ) );

		if ( 'media' === $what ) {
			$this->media_result = KMPFI_Media_Assigner::run();
			return;
		}

		if ( 'terms' === $what ) {
			$this->terms_result = KMPFI_Importer::remove_empty_terms();
			return;
		}

		if ( 'zip' === $what ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$file = isset( $_FILES['kmpfi_zip'] ) ? $_FILES['kmpfi_zip'] : array();

			$this->zip_result = KMPFI_Gallery_Zip::prepare(
				$file,
				! empty( $_POST['kmpfi_replace'] ),
				! empty( $_POST['kmpfi_delete_old'] )
			);
			return;
		}

		if ( 'zip_cancel' === $what ) {
			KMPFI_Gallery_Zip::finish();
			return;
		}

		if ( 'dates' === $what ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$raw                = isset( $_POST['kmpfi_date'] ) ? (array) $_POST['kmpfi_date'] : array();
			$this->dates_result = KMPFI_Work_Dates::save( $raw );
			return;
		}


		$status = ( isset( $_POST['kmpfi_status'] ) && 'draft' === $_POST['kmpfi_status'] ) ? 'draft' : 'publish';

		$this->result = KMPFI_Importer::run( $status );
	}

	public function render() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$check = KMPFI_Importer::preflight();
		$plan  = $check['ok'] ? KMPFI_Importer::plan() : array();

		$to_create = 0;
		$to_update = 0;

		foreach ( $plan as $row ) {
			if ( 'create' === $row['action'] ) {
				$to_create++;
			} else {
				$to_update++;
			}
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Portfolio Import', 'kirollos-magdy-portfolio-importer' ); ?></h1>

			<p class="description">
				<?php
				printf(
					/* translators: %s: Post type slug. */
					esc_html__( 'Creates every portfolio project in the "%s" post type, with its Categories, Services and Industries, and the live site link the portfolio cards use.', 'kirollos-magdy-portfolio-importer' ),
					esc_html( KMPFI_Importer::post_type() )
				);
				?>
			</p>

			<?php $this->notices( $check ); ?>

			<?php if ( $check['ok'] ) : ?>

				<h2><?php esc_html_e( 'Import all projects', 'kirollos-magdy-portfolio-importer' ); ?></h2>

				<form method="post">
					<?php wp_nonce_field( self::ACTION, self::NONCE ); ?>

					<p>
						<label>
							<input type="radio" name="kmpfi_status" value="publish" checked>
							<?php esc_html_e( 'Publish straight away', 'kirollos-magdy-portfolio-importer' ); ?>
						</label>
						&nbsp;&nbsp;
						<label>
							<input type="radio" name="kmpfi_status" value="draft">
							<?php esc_html_e( 'Create as drafts', 'kirollos-magdy-portfolio-importer' ); ?>
						</label>
					</p>

					<p>
						<button type="submit" name="<?php echo esc_attr( self::ACTION ); ?>" value="import" class="button button-primary button-hero">
							<?php
							printf(
								/* translators: 1: number to create, 2: number to update. */
								esc_html__( 'Import everything now (%1$d new, %2$d existing)', 'kirollos-magdy-portfolio-importer' ),
								(int) $to_create,
								(int) $to_update
							);
							?>
						</button>
					</p>

					<p class="description">
						<?php esc_html_e( 'Safe to run more than once. Existing projects are matched on their live URL and then their title; only fields that are still empty get filled, so anything you typed by hand survives.', 'kirollos-magdy-portfolio-importer' ); ?>
					</p>
				</form>

				<h2><?php esc_html_e( 'The filter vocabulary', 'kirollos-magdy-portfolio-importer' ); ?></h2>

				<p class="description">
					<?php esc_html_e( 'These are the terms the import assigns. They are deliberately broad: a filter where every term matches one project is not a filter, and it splits the same search term across several near-duplicate archives. The detailed wording stays on each project, in the fields the project page reads.', 'kirollos-magdy-portfolio-importer' ); ?>
				</p>

				<div style="display:flex;flex-wrap:wrap;gap:24px;margin:16px 0">
					<?php
					$this->vocabulary( __( 'Services', 'kirollos-magdy-portfolio-importer' ), 'service' );
					$this->vocabulary( __( 'Industries', 'kirollos-magdy-portfolio-importer' ), 'industry' );
					$this->vocabulary( __( 'Categories', 'kirollos-magdy-portfolio-importer' ), 'category' );
					?>
				</div>

				<form method="post">
					<?php wp_nonce_field( self::ACTION, self::NONCE ); ?>
					<p>
						<button type="submit" name="<?php echo esc_attr( self::ACTION ); ?>" value="terms" class="button button-secondary">
							<?php esc_html_e( 'Remove portfolio terms with no projects', 'kirollos-magdy-portfolio-importer' ); ?>
						</button>
					</p>
					<p class="description">
						<?php esc_html_e( 'Run this after a re-import to clear out the old one-project-per-term list. It only deletes terms that have nothing on them.', 'kirollos-magdy-portfolio-importer' ); ?>
					</p>
				</form>

				<h2><?php esc_html_e( 'What it will do', 'kirollos-magdy-portfolio-importer' ); ?></h2>

				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Project', 'kirollos-magdy-portfolio-importer' ); ?></th>
							<th><?php esc_html_e( 'Industry', 'kirollos-magdy-portfolio-importer' ); ?></th>
							<th><?php esc_html_e( 'Services', 'kirollos-magdy-portfolio-importer' ); ?></th>
							<th><?php esc_html_e( 'Live site', 'kirollos-magdy-portfolio-importer' ); ?></th>
							<th><?php esc_html_e( 'Action', 'kirollos-magdy-portfolio-importer' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $plan as $row ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $row['title'] ); ?></strong></td>
								<td><?php echo esc_html( $row['industry'] ); ?></td>
								<td style="font-size:12px;color:#646970"><?php echo esc_html( $row['services'] ); ?></td>
								<td>
									<?php if ( $row['url'] ) : ?>
										<a href="<?php echo esc_url( $row['url'] ); ?>" target="_blank" rel="noopener noreferrer">
											<?php echo esc_html( preg_replace( '#^https?://(www\.)?#i', '', $row['url'] ) ); ?>
										</a>
									<?php endif; ?>
								</td>
								<td>
									<?php if ( 'create' === $row['action'] ) : ?>
										<span style="color:#1a7f37;font-weight:600"><?php esc_html_e( 'Create', 'kirollos-magdy-portfolio-importer' ); ?></span>
									<?php else : ?>
										<span style="color:#8a6d00;font-weight:600"><?php esc_html_e( 'Already there, fill gaps', 'kirollos-magdy-portfolio-importer' ); ?></span>
										<a href="<?php echo esc_url( (string) get_edit_post_link( $row['existing'] ) ); ?>"><?php esc_html_e( 'edit', 'kirollos-magdy-portfolio-importer' ); ?></a>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>


				<h2><?php esc_html_e( 'Work dates', 'kirollos-magdy-portfolio-importer' ); ?></h2>

				<p class="description">
					<?php esc_html_e( 'The month each project was delivered. The builder shows it on the card opposite the button. Saving writes straight to the projects that already exist, and the import fills in the rest.', 'kirollos-magdy-portfolio-importer' ); ?>
				</p>

				<p class="description">
					<?php esc_html_e( 'Nothing is prefilled. There is no record of when most of these were delivered, and a date on a portfolio is a claim to a client, so none are guessed at here.', 'kirollos-magdy-portfolio-importer' ); ?>
				</p>

				<form method="post">
					<?php wp_nonce_field( self::ACTION, self::NONCE ); ?>

					<table class="widefat striped" style="max-width:760px;margin:14px 0">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Project', 'kirollos-magdy-portfolio-importer' ); ?></th>
								<th style="width:220px"><?php esc_html_e( 'Month and year', 'kirollos-magdy-portfolio-importer' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( KMPFI_Projects_Data::all() as $project ) : ?>
								<?php
								$url = isset( $project['meta']['live_url'] ) ? $project['meta']['live_url'] : '';

								if ( '' === $url ) {
									continue;
								}

								$known = isset( KMPFI_Work_Dates::KNOWN[ $url ] ) ? KMPFI_Work_Dates::KNOWN[ $url ] : '';
								?>
								<tr>
									<td>
										<strong><?php echo esc_html( $project['title'] ); ?></strong>
										<?php if ( $known ) : ?>
											<span style="color:#646970">
												<?php
												printf(
													/* translators: %s: the year on record. */
													esc_html__( '(on record: %s, month not recorded)', 'kirollos-magdy-portfolio-importer' ),
													esc_html( $known )
												);
												?>
											</span>
										<?php endif; ?>
									</td>
									<td>
										<input type="month" name="kmpfi_date[<?php echo esc_attr( $url ); ?>]"
											value="<?php echo esc_attr( KMPFI_Work_Dates::get( $url ) ); ?>" style="width:100%">
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>

					<p>
						<button type="submit" name="<?php echo esc_attr( self::ACTION ); ?>" value="dates" class="button button-primary">
							<?php esc_html_e( 'Save dates and apply them now', 'kirollos-magdy-portfolio-importer' ); ?>
						</button>
					</p>

					<p class="description">
						<?php esc_html_e( 'Clearing a date and saving removes it from the project too.', 'kirollos-magdy-portfolio-importer' ); ?>
					</p>
				</form>

				<h2><?php esc_html_e( 'Import all the screenshots', 'kirollos-magdy-portfolio-importer' ); ?></h2>

				<p class="description">
					<?php esc_html_e( 'Upload one zip holding a folder per project. Inside each folder, the file called "logo" becomes the featured image and the logo on the card; the rest go into the gallery in number order, up to four. Nothing is sent anywhere: the zip is unpacked on your own server and the unpacked copy is deleted straight after.', 'kirollos-magdy-portfolio-importer' ); ?>
				</p>

				<?php if ( ! KMPFI_Gallery_Zip::pdf_ready() ) : ?>
					<div class="notice notice-warning inline">
						<p><?php esc_html_e( 'This server cannot render PDFs, so it is missing Imagick with Ghostscript. PDF screenshots will still import and attach to the right projects, but they will not display as pictures on the cards until that is available. PNG and JPG are unaffected.', 'kirollos-magdy-portfolio-importer' ); ?></p>
					</div>
				<?php endif; ?>

				<?php $this->progress_panel(); ?>

				<form method="post" enctype="multipart/form-data" id="kmpfi-zip-form">
					<?php wp_nonce_field( self::ACTION, self::NONCE ); ?>
					<p><input type="file" name="kmpfi_zip" accept=".zip" required></p>

					<p>
						<label>
							<input type="checkbox" name="kmpfi_replace" value="1" checked>
							<?php esc_html_e( 'Replace the photos already on these projects', 'kirollos-magdy-portfolio-importer' ); ?>
						</label>
						<br>
						<span class="description" style="margin-left:24px">
							<?php esc_html_e( 'Empties each gallery and its featured image before importing, so the new screenshots stand alone instead of piling up behind the old ones. Only the projects in the zip are touched.', 'kirollos-magdy-portfolio-importer' ); ?>
						</span>
					</p>

					<p>
						<label>
							<input type="checkbox" name="kmpfi_delete_old" value="1">
							<?php esc_html_e( 'Also delete those old files from the Media Library', 'kirollos-magdy-portfolio-importer' ); ?>
						</label>
						<br>
						<span class="description" style="margin-left:24px">
							<?php esc_html_e( 'Off by default. Detaching a photo can be undone; deleting it cannot, and an old screenshot may be used somewhere else on the site.', 'kirollos-magdy-portfolio-importer' ); ?>
						</span>
					</p>

					<p>
						<button type="submit" name="<?php echo esc_attr( self::ACTION ); ?>" value="zip" class="button button-primary button-hero">
							<?php esc_html_e( 'Import all the photos now', 'kirollos-magdy-portfolio-importer' ); ?>
						</button>
					</p>
					<p class="description">
						<?php
						printf(
							/* translators: %s: the folder names. */
							esc_html__( 'Folders it knows: %s. Anything else in the zip is reported back, not guessed at.', 'kirollos-magdy-portfolio-importer' ),
							esc_html( implode( ', ', array_map( 'ucwords', array_keys( KMPFI_Gallery_Zip::FOLDERS ) ) ) )
						);
						?>
					</p>
				</form>

				<h2><?php esc_html_e( 'Or assign images already in the Media Library', 'kirollos-magdy-portfolio-importer' ); ?></h2>

				<p class="description">
					<?php esc_html_e( 'Upload the project screenshots and logos to the Media Library first. This matches them to projects by filename, sets the featured image, and uses any file with "logo" in its name as the card logo.', 'kirollos-magdy-portfolio-importer' ); ?>
				</p>

				<?php $this->media_summary(); ?>

				<form method="post">
					<?php wp_nonce_field( self::ACTION, self::NONCE ); ?>
					<p>
						<button type="submit" name="<?php echo esc_attr( self::ACTION ); ?>" value="media" class="button button-secondary">
							<?php esc_html_e( 'Match Media Library images to projects', 'kirollos-magdy-portfolio-importer' ); ?>
						</button>
					</p>
				</form>

				<h2><?php esc_html_e( 'Left blank on purpose', 'kirollos-magdy-portfolio-importer' ); ?></h2>

				<p class="description">
					<?php esc_html_e( 'These are real figures and named quotes. There is no record of genuine ones for these builds, so they are not filled in:', 'kirollos-magdy-portfolio-importer' ); ?>
					<code><?php echo esc_html( implode( ', ', KMPFI_Projects_Data::blank_fields() ) ); ?></code>
				</p>

			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * @param array $check Preflight result.
	 */
	private function notices( $check ) {

		foreach ( $check['messages'] as $message ) {
			printf(
				'<div class="notice notice-%s"><p>%s</p></div>',
				$check['ok'] ? 'warning' : 'error',
				esc_html( $message )
			);
		}

		if ( null !== $this->result ) {

			$result = $this->result;

			if ( $result['errors'] ) {
				foreach ( $result['errors'] as $error ) {
					printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $error ) );
				}
			}

			printf(
				'<div class="notice notice-success"><p>%s</p></div>',
				esc_html(
					sprintf(
						/* translators: 1: created, 2: updated, 3: skipped. */
						__( 'Done. %1$d created, %2$d already there and filled in, %3$d skipped.', 'kirollos-magdy-portfolio-importer' ),
						(int) $result['created'],
						(int) $result['updated'],
						(int) $result['skipped']
					)
				)
			);
		}

		if ( null !== $this->terms_result ) {

			$terms = $this->terms_result;

			$message = $terms['deleted']
				? sprintf(
					/* translators: 1: number of terms, 2: the term names. */
					__( 'Removed %1$d empty terms: %2$s', 'kirollos-magdy-portfolio-importer' ),
					(int) $terms['deleted'],
					implode( ', ', $terms['names'] )
				)
				: __( 'Nothing to remove. Every portfolio term has at least one project on it.', 'kirollos-magdy-portfolio-importer' );

			printf( '<div class="notice notice-success"><p>%s</p></div>', esc_html( $message ) );
		}

		if ( null !== $this->dates_result ) {

			$dates = $this->dates_result;

			printf(
				'<div class="notice notice-success"><p>%s</p></div>',
				esc_html(
					sprintf(
						/* translators: 1: dates saved, 2: projects written to, 3: dates removed. */
						__( '%1$d dates saved, written onto %2$d projects, %3$d cleared.', 'kirollos-magdy-portfolio-importer' ),
						(int) $dates['saved'],
						(int) $dates['written'],
						(int) $dates['cleared']
					)
				)
			);
		}

		if ( null !== $this->zip_result ) {

			$zip = $this->zip_result;

			foreach ( $zip['errors'] as $error ) {
				printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $error ) );
			}

			if ( $zip['unmatched'] ) {
				printf(
					'<div class="notice notice-warning"><p>%s</p></div>',
					esc_html(
						sprintf(
							/* translators: %s: folder names. */
							__( 'No project matches these folders, so they were left out: %s', 'kirollos-magdy-portfolio-importer' ),
							implode( ', ', $zip['unmatched'] )
						)
					)
				);
			}

			if ( empty( $zip['total'] ) && ! $zip['errors'] ) {
				printf(
					'<div class="notice notice-warning"><p>%s</p></div>',
					esc_html__( 'Nothing in that zip matched a project.', 'kirollos-magdy-portfolio-importer' )
				);
			}
		}

		if ( null !== $this->media_result ) {

			$media = $this->media_result;

			printf(
				'<div class="notice notice-success"><p>%s</p></div>',
				esc_html(
					sprintf(
						/* translators: 1: projects updated, 2: images, 3: logos, 4: projects not yet created. */
						__( 'Images assigned. %1$d projects updated, %2$d images matched, %3$d logos set, %4$d projects had images waiting but do not exist yet.', 'kirollos-magdy-portfolio-importer' ),
						(int) $media['updated'],
						(int) $media['images'],
						(int) $media['logos'],
						(int) $media['missing']
					)
				)
			);
		}
	}


	/**
	 * The live progress panel.
	 *
	 * It only draws itself when a zip has just been unpacked and there is a job
	 * waiting, then walks that job two files at a time and reports each one as
	 * it lands. Two at a time because a PDF's previews are slow to generate and
	 * a bigger batch starts risking the same timeout the split was meant to
	 * avoid.
	 */
	private function progress_panel() {

		if ( null === $this->zip_result || empty( $this->zip_result['total'] ) ) {
			return;
		}

		$total = (int) $this->zip_result['total'];
		?>
		<div id="kmpfi-progress" style="max-width:780px;margin:18px 0;padding:18px 20px;background:#fff;border:1px solid #c3c4c7;border-left:4px solid #2271b1;border-radius:3px">

			<p style="margin:0 0 10px;font-weight:600">
				<span id="kmpfi-progress-title"><?php esc_html_e( 'Importing photos', 'kirollos-magdy-portfolio-importer' ); ?></span>
				<span id="kmpfi-progress-count" style="font-weight:400;color:#646970">
					<?php
					printf(
						/* translators: %d: number of photos. */
						esc_html__( '0 of %d', 'kirollos-magdy-portfolio-importer' ),
						(int) $total
					);
					?>
				</span>
			</p>

			<div style="height:18px;background:#f0f0f1;border-radius:9px;overflow:hidden">
				<div id="kmpfi-progress-bar" style="height:100%;width:0;background:#2271b1;transition:width .25s ease"></div>
			</div>

			<p id="kmpfi-progress-now" style="margin:10px 0 0;color:#646970;font-size:13px">
				<?php esc_html_e( 'Starting...', 'kirollos-magdy-portfolio-importer' ); ?>
			</p>

			<ul id="kmpfi-progress-log" style="margin:12px 0 0;max-height:220px;overflow:auto;font-size:12px;font-family:Consolas,Monaco,monospace;color:#3c434a"></ul>

			<p style="margin:14px 0 0">
				<button type="button" id="kmpfi-progress-stop" class="button button-secondary">
					<?php esc_html_e( 'Stop', 'kirollos-magdy-portfolio-importer' ); ?>
				</button>
			</p>
		</div>

		<script>
		( function () {
			var total   = <?php echo (int) $total; ?>;
			var ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
			var nonce   = <?php echo wp_json_encode( wp_create_nonce( self::ACTION ) ); ?>;

			var bar   = document.getElementById( 'kmpfi-progress-bar' );
			var count = document.getElementById( 'kmpfi-progress-count' );
			var now   = document.getElementById( 'kmpfi-progress-now' );
			var log   = document.getElementById( 'kmpfi-progress-log' );
			var stop  = document.getElementById( 'kmpfi-progress-stop' );
			var title = document.getElementById( 'kmpfi-progress-title' );

			var stopped = false;

			stop.addEventListener( 'click', function () {
				stopped = true;
				now.textContent = <?php echo wp_json_encode( __( 'Stopped. Everything imported so far is already attached; upload the zip again to carry on.', 'kirollos-magdy-portfolio-importer' ) ); ?>;
				stop.disabled = true;
			} );

			function line( text ) {
				var li = document.createElement( 'li' );
				li.textContent = text;
				li.style.margin = '0 0 3px';
				log.appendChild( li );
				log.scrollTop = log.scrollHeight;
			}

			function step() {
				if ( stopped ) {
					return;
				}

				var body = new FormData();
				body.append( 'action', 'kmpfi_step' );
				body.append( 'nonce', nonce );
				body.append( 'batch', '2' );

				fetch( ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body } )
					.then( function ( r ) { return r.json(); } )
					.then( function ( res ) {

						if ( ! res || ! res.success ) {
							now.textContent = <?php echo wp_json_encode( __( 'The import stopped with an error. Reload and try again.', 'kirollos-magdy-portfolio-importer' ) ); ?>;
							stop.disabled = true;
							return;
						}

						var d = res.data;

						( d.log || [] ).forEach( line );

						var pct = d.total ? Math.round( ( d.index / d.total ) * 100 ) : 100;

						bar.style.width  = pct + '%';
						count.textContent = d.index + ' of ' + d.total;

						if ( d.done ) {
							title.textContent = <?php echo wp_json_encode( __( 'Finished', 'kirollos-magdy-portfolio-importer' ) ); ?>;
							now.textContent   = d.files + <?php echo wp_json_encode( __( ' photos imported, ', 'kirollos-magdy-portfolio-importer' ) ); ?> + d.skipped + <?php echo wp_json_encode( __( ' skipped.', 'kirollos-magdy-portfolio-importer' ) ); ?>;
							bar.style.background = '#00a32a';
							stop.disabled = true;

							( d.errors || [] ).forEach( function ( e ) { line( 'Error: ' + e ); } );
							return;
						}

						now.textContent = <?php echo wp_json_encode( __( 'Working...', 'kirollos-magdy-portfolio-importer' ) ); ?>;
						step();
					} )
					.catch( function () {
						now.textContent = <?php echo wp_json_encode( __( 'Lost contact with the server. Reload and upload the zip again to carry on.', 'kirollos-magdy-portfolio-importer' ) ); ?>;
						stop.disabled = true;
					} );
			}

			step();
		}() );
		</script>
		<?php
	}

	/**
	 * One column of the vocabulary preview: each term and how many projects
	 * land on it, so a term that would come out at one is visible here rather
	 * than on the live filter.
	 *
	 * @param string $label
	 * @param string $which category|service|industry
	 */
	private function vocabulary( $label, $which ) {

		$counts = KMPFI_Projects_Data::term_counts( $which );

		if ( ! $counts ) {
			return;
		}

		echo '<div style="min-width:230px">';
		printf( '<h3 style="margin:0 0 8px">%s</h3>', esc_html( $label ) );
		echo '<ul style="margin:0">';

		foreach ( $counts as $name => $count ) {
			printf(
				'<li>%s <span style="color:#646970">(%d)</span></li>',
				esc_html( $name ),
				(int) $count
			);
		}

		echo '</ul></div>';
	}

	/**
	 * A short read on what the matcher currently sees, so the result is
	 * predictable before the button is pressed.
	 */
	private function media_summary() {

		$plan    = KMPFI_Media_Assigner::plan();
		$matched = 0;

		foreach ( $plan['projects'] as $row ) {
			$matched += count( $row['images'] );
		}

		printf(
			'<p><strong>%s</strong></p>',
			esc_html(
				sprintf(
					/* translators: 1: matched images, 2: unmatched images. */
					__( '%1$d images in the Media Library match a project. %2$d match nothing.', 'kirollos-magdy-portfolio-importer' ),
					(int) $matched,
					count( $plan['unmatched'] )
				)
			)
		);
	}
}
