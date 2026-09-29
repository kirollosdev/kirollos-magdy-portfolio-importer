<?php
/**
 * Imports project screenshots from a folder zip.
 *
 * THE SHAPE IT EXPECTS
 *
 * One zip containing one subfolder per project, each holding that project's
 * screenshots. That is exactly what comes out of the "My Projects" folder when
 * it is downloaded, so it can go straight in with no renaming and no tidying.
 *
 *   My Projects.zip
 *     Seaqui Real Estate Marketing/  Home.pdf  Properties.pdf  FAQ.pdf ...
 *     Travoya Tours/                 Home.pdf  Tours.pdf  Cruises.pdf ...
 *     Queen Delivery/                Home.pdf  Services.pdf ...
 *
 * Nothing here talks to Google or to any other service. The zip is read off
 * disk, unpacked into the uploads folder, imported, and the unpacked copy is
 * deleted again.
 *
 * MATCHING
 *
 * Subfolder name to project, through the names in FOLDERS plus the same alias
 * rules the Media Library matcher uses. A folder with no project is reported
 * rather than guessed at.
 *
 * THE FILE NAMING IT READS
 *
 * A file called logo becomes the project's logo: it is set as the featured
 * image and as the module's secondary image, which is what the cards show in
 * the strip along the top. It is deliberately kept out of the gallery, since a
 * logo among the screenshots would look like a mistake.
 *
 * Everything else is the gallery, in the order its filename sorts: 1, 2, 3, 4.
 * Only the first four are used, because that is what a project can hold.
 *
 * FILE NAMES IN THE LIBRARY
 *
 * Every project has a 1.webp and a logo, so each file is stored under the
 * project's slug: travoyatours-1.webp, travoyatours-logo.webp. Without that the
 * Media Library fills up with 1, 1-1, 1-2.
 *
 * PDFs
 *
 * Most of these screenshots are PDFs. WordPress renders a PDF attachment
 * through the JPEG previews it generates on upload, so they show in a gallery
 * like images, but only where the server has Imagick with Ghostscript.
 * pdf_ready() reports whether this one does, before anything is imported.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMPFI_Gallery_Zip {

	/**
	 * Folder name => the project's live URL.
	 *
	 * These are the real folder names, read off the actual folder, not guesses
	 * at what they might be called.
	 *
	 * @var array
	 */
	const FOLDERS = array(
		'seaqui'                       => 'https://seaqui.com',
		'seaqui real estate marketing' => 'https://seaqui.com',
		'al sanowber'                  => 'https://sanowber.ly',
		'alhqol alkhadra'              => 'https://alhqolalkhadra.com',
		'jc clayations'                => 'https://jcclayations.ca',
		'hope express'                 => 'https://hope-express.com',
		'queen delivery'               => 'https://queendelivery.ca',
		'travoya tours'                => 'https://travoyatours.com',
		'bait alasaad'                 => 'https://baitalasaad.com',
		'pharaohkids'                  => 'https://pharaohkids.de',
		'almatjarhome'                 => 'https://almatjarhome.ly',
		'champollion hostel'           => 'https://champollion-hostel.com',
	);

	/**
	 * What gets imported. Anything else in the zip is left alone.
	 *
	 * @var array
	 */
	const TYPES = array( 'jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf' );

	/**
	 * Where the half-finished import lives between requests.
	 */
	const JOB = 'kmpfi_zip_job';

	/**
	 * How many gallery photos a project can hold, matching the builder.
	 */
	const LIMIT = 4;

	/**
	 * The meta key the module stores the card logo under.
	 *
	 * Asked for by name where the module is loaded, so a change there carries
	 * over rather than being duplicated as a literal.
	 *
	 * @return string
	 */
	public static function logo_meta_key() {
		return function_exists( 'pw_secondary_image_meta_key' )
			? pw_secondary_image_meta_key()
			: '_secondary_featured_image';
	}

	/**
	 * @param string $path
	 * @return bool Whether this file is the project's logo.
	 */
	private static function is_logo( $path ) {
		return (bool) preg_match( '/^logo\b/i', pathinfo( $path, PATHINFO_FILENAME ) );
	}

	/**
	 * @return bool Whether this server can render a PDF as a picture.
	 */
	public static function pdf_ready() {
		return function_exists( 'wp_image_editor_supports' )
			&& wp_image_editor_supports( array( 'mime_type' => 'application/pdf' ) );
	}

	/**
	 * Matches a folder name to a project.
	 *
	 * @param string $name
	 * @return array|null
	 */
	public static function match_project( $name ) {

		$key = strtolower( trim( $name ) );

		if ( isset( self::FOLDERS[ $key ] ) ) {
			foreach ( KMPFI_Projects_Data::all() as $project ) {
				if ( isset( $project['meta']['live_url'] ) && self::FOLDERS[ $key ] === $project['meta']['live_url'] ) {
					return $project;
				}
			}
		}

		$flat = preg_replace( '/[^a-z0-9]+/', '', $key );

		if ( '' === $flat ) {
			return null;
		}

		$best     = null;
		$best_len = 0;

		foreach ( KMPFI_Projects_Data::all() as $project ) {
			foreach ( KMPFI_Media_Assigner::aliases( $project ) as $alias ) {

				$alias = preg_replace( '/[^a-z0-9]+/', '', strtolower( $alias ) );

				if ( '' === $alias || false === strpos( $flat, $alias ) ) {
					continue;
				}

				// Longest alias wins, so "almatjarhome" beats "almatjar".
				if ( strlen( $alias ) > $best_len ) {
					$best     = $project;
					$best_len = strlen( $alias );
				}
			}
		}

		return $best;
	}

	/**
	 * Unpacks the zip and builds the job, importing nothing yet.
	 *
	 * WHY IT IS SPLIT IN TWO
	 *
	 * Importing forty screenshots means forty sideloads, and every PDF among
	 * them has its preview images generated on the way in. Done in the single
	 * request that uploaded the zip, that runs past max_execution_time on most
	 * hosts and dies halfway with nothing to show for it. So the upload only
	 * unpacks and plans; the browser then walks the plan a couple of files at a
	 * time, which also gives it something honest to draw a progress bar from.
	 *
	 * @param array $file       One entry from $_FILES.
	 * @param bool  $replace    Clear each project's existing photos first.
	 * @param bool  $delete_old Also delete those old files from the library.
	 * @return array{total:int,projects:int,unmatched:string[],errors:string[]}
	 */
	public static function prepare( $file, $replace = true, $delete_old = false ) {

		$out = array(
			'total'     => 0,
			'projects'  => 0,
			'unmatched' => array(),
			'errors'    => array(),
		);

		if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			$out['errors'][] = __( 'No zip was uploaded. Check that the file is not larger than this server allows.', 'kirollos-magdy-portfolio-importer' );
			return $out;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';

		WP_Filesystem();

		// A job left behind by an abandoned run would otherwise sit in the
		// uploads folder forever.
		self::finish();

		$uploads = wp_upload_dir();
		$target  = trailingslashit( $uploads['basedir'] ) . 'kmpfi-zip-' . wp_generate_password( 8, false );

		$unzipped = unzip_file( $file['tmp_name'], $target );

		if ( is_wp_error( $unzipped ) ) {
			$out['errors'][] = sprintf(
				/* translators: %s: Error message. */
				__( 'Could not unpack the zip: %s', 'kirollos-magdy-portfolio-importer' ),
				$unzipped->get_error_message()
			);
			return $out;
		}

		$items    = array();
		$projects = array();

		foreach ( self::subfolders( self::root( $target ) ) as $path ) {

			$name    = basename( $path );
			$project = self::match_project( $name );

			if ( ! $project ) {
				$out['unmatched'][] = $name;
				continue;
			}

			$post_id = KMPFI_Importer::find_existing( $project );

			if ( ! $post_id ) {
				$out['errors'][] = sprintf(
					/* translators: %s: Project title. */
					__( '"%s" is not on the site yet. Run the project import above first, then this.', 'kirollos-magdy-portfolio-importer' ),
					$project['title']
				);
				continue;
			}

			$slug    = sanitize_title( isset( $project['meta']['client'] ) ? $project['meta']['client'] : $project['title'] );
			$entries = glob( trailingslashit( $path ) . '*' );
			$entries = is_array( $entries ) ? $entries : array();

			$files = array();

			foreach ( $entries as $entry ) {
				if ( ! is_dir( $entry ) ) {
					$files[] = $entry;
				}
			}

			// The logo goes first so the featured image is set before anything
			// else lands, and it never counts against the gallery limit.
			$logos = array();
			$shots = array();

			foreach ( self::sort_files( $files ) as $file_path ) {
				if ( self::is_logo( $file_path ) ) {
					$logos[] = $file_path;
				} else {
					$shots[] = $file_path;
				}
			}

			$shots   = array_slice( $shots, 0, self::LIMIT );
			$ordered = array_merge( array_slice( $logos, 0, 1 ), $shots );
			$counted = false;

			foreach ( $ordered as $file_path ) {
				$items[] = array(
					'path'    => $file_path,
					'name'    => basename( $file_path ),
					'post_id' => $post_id,
					'slug'    => $slug,
					'project' => $project['title'],
					'logo'    => self::is_logo( $file_path ),
				);
				$counted = true;
			}

			if ( $counted ) {
				$projects[] = $post_id;
			}
		}

		$out['total']    = count( $items );
		$out['projects'] = count( $projects );

		if ( ! $items ) {
			self::remove_dir( $target );
			return $out;
		}

		update_option(
			self::JOB,
			array(
				'dir'        => $target,
				'replace'    => (bool) $replace,
				'delete_old' => (bool) $delete_old,
				'cleared'    => false,
				'items'    => $items,
				'index'    => 0,
				'files'    => 0,
				'skipped'  => 0,
				'projects' => $projects,
				'errors'   => $out['errors'],
			),
			false
		);

		return $out;
	}

	/**
	 * Imports the next few files and reports where it got to.
	 *
	 * @param int $batch How many files to take this time.
	 * @return array{done:bool,index:int,total:int,files:int,skipped:int,log:string[],errors:string[]}
	 */
	public static function step( $batch = 2 ) {

		$job = get_option( self::JOB );

		if ( ! is_array( $job ) || empty( $job['items'] ) ) {
			return array(
				'done'    => true,
				'index'   => 0,
				'total'   => 0,
				'files'   => 0,
				'skipped' => 0,
				'log'     => array(),
				'errors'  => array( __( 'Nothing to import. Upload the zip again.', 'kirollos-magdy-portfolio-importer' ) ),
			);
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$total = count( $job['items'] );
		$log   = array();

		// Replacing happens once, on the first batch, and only for the projects
		// this zip actually has photos for. A project not in the zip keeps
		// everything it has.
		if ( ! empty( $job['replace'] ) && empty( $job['cleared'] ) ) {

			$cleared = self::clear_galleries( $job['projects'], ! empty( $job['delete_old'] ) );

			$job['cleared'] = true;

			if ( $cleared ) {
				$log[] = sprintf(
					/* translators: 1: number of photos, 2: number of projects. */
					__( 'Cleared %1$d old photos from %2$d projects', 'kirollos-magdy-portfolio-importer' ),
					$cleared,
					count( $job['projects'] )
				);
			}
		}

		for ( $i = 0; $i < max( 1, (int) $batch ); $i++ ) {

			if ( $job['index'] >= $total ) {
				break;
			}

			$item = $job['items'][ $job['index'] ];
			$job['index']++;

			$done = self::import_one( $item );

			if ( $done['ok'] ) {
				$job['files']++;
				$log[] = sprintf( '%s — %s', $item['project'], $item['name'] );
			} else {
				$job['skipped']++;
				if ( $done['error'] ) {
					$job['errors'][] = $done['error'];
					$log[]           = sprintf( '%s — %s (skipped)', $item['project'], $item['name'] );
				}
			}
		}

		$finished = ( $job['index'] >= $total );

		if ( $finished ) {
			$result = array(
				'done'    => true,
				'index'   => $total,
				'total'   => $total,
				'files'   => (int) $job['files'],
				'skipped' => (int) $job['skipped'],
				'log'     => $log,
				'errors'  => array_values( array_unique( $job['errors'] ) ),
			);

			self::finish();

			return $result;
		}

		update_option( self::JOB, $job, false );

		return array(
			'done'    => false,
			'index'   => (int) $job['index'],
			'total'   => $total,
			'files'   => (int) $job['files'],
			'skipped' => (int) $job['skipped'],
			'log'     => $log,
			'errors'  => array(),
		);
	}

	/**
	 * Empties the galleries of the projects about to be re-imported.
	 *
	 * The featured image goes too, so the new home page screenshot takes it
	 * rather than the old one staying put. Deleting the files themselves is
	 * off by default: detaching them is reversible, deleting is not, and an
	 * old screenshot may be in use somewhere else on the site.
	 *
	 * @param int[] $post_ids
	 * @param bool  $delete_files
	 * @return int How many photos were cleared.
	 */
	private static function clear_galleries( $post_ids, $delete_files ) {

		$cleared = 0;

		foreach ( array_unique( array_map( 'absint', (array) $post_ids ) ) as $post_id ) {

			if ( ! $post_id ) {
				continue;
			}

			$current = get_post_meta( $post_id, KMPFI_Media_Assigner::GALLERY_META, true );
			$current = $current ? array_filter( array_map( 'absint', explode( ',', (string) $current ) ) ) : array();

			$thumb = (int) get_post_thumbnail_id( $post_id );
			$logo  = (int) get_post_meta( $post_id, self::logo_meta_key(), true );

			delete_post_meta( $post_id, KMPFI_Media_Assigner::GALLERY_META );
			delete_post_meta( $post_id, self::logo_meta_key() );
			delete_post_thumbnail( $post_id );

			$cleared += count( $current );

			if ( ! $delete_files ) {
				continue;
			}

			$ids = $current;

			// The featured image is usually one of the gallery files anyway,
			// but not always.
			if ( $thumb && ! in_array( $thumb, $ids, true ) ) {
				$ids[] = $thumb;
			}

			if ( $logo && ! in_array( $logo, $ids, true ) ) {
				$ids[] = $logo;
			}

			foreach ( $ids as $attachment_id ) {
				if ( 'attachment' === get_post_type( $attachment_id ) ) {
					wp_delete_attachment( $attachment_id, true );
				}
			}
		}

		return $cleared;
	}

	/**
	 * Clears the job and the unpacked copy.
	 */
	public static function finish() {

		$job = get_option( self::JOB );

		if ( is_array( $job ) && ! empty( $job['dir'] ) ) {
			self::remove_dir( $job['dir'] );
		}

		delete_option( self::JOB );
	}

	/**
	 * A downloaded folder arrives wrapped in one folder named after itself.
	 * Step into that when it is all there is.
	 *
	 * @param string $dir
	 * @return string
	 */
	private static function root( $dir ) {

		$subs = self::subfolders( $dir );

		if ( 1 !== count( $subs ) ) {
			return $dir;
		}

		// It only counts as a wrapper if it holds folders of its own.
		return self::subfolders( $subs[0] ) ? $subs[0] : $dir;
	}

	/**
	 * @param string $dir
	 * @return string[]
	 */
	private static function subfolders( $dir ) {

		$found = glob( trailingslashit( $dir ) . '*', GLOB_ONLYDIR );

		return is_array( $found ) ? $found : array();
	}

	/**
	 * Natural order, so 2 comes before 10 rather than after it.
	 *
	 * @param string[] $paths
	 * @return string[]
	 */
	private static function sort_files( $paths ) {

		usort(
			$paths,
			function ( $a, $b ) {
				return strnatcasecmp( basename( $a ), basename( $b ) );
			}
		);

		return $paths;
	}

	/**
	 * Sideloads one screenshot onto its project.
	 *
	 * The gallery meta is written as each file lands rather than at the end, so
	 * a run that is interrupted still leaves everything it managed to import
	 * attached where it belongs.
	 *
	 * @param array $item path, name, post_id, slug, project
	 * @return array{ok:bool,error:string}
	 */
	private static function import_one( $item ) {

		$extension = strtolower( pathinfo( $item['path'], PATHINFO_EXTENSION ) );

		if ( ! in_array( $extension, self::TYPES, true ) ) {
			return array( 'ok' => false, 'error' => '' );
		}

		$name = $item['slug'] . '-' . sanitize_file_name( $item['name'] );
		$copy = wp_tempnam( $name );

		if ( ! $copy || ! copy( $item['path'], $copy ) ) {
			return array(
				'ok'    => false,
				'error' => sprintf(
					/* translators: %s: File name. */
					__( 'Could not read "%s" out of the zip.', 'kirollos-magdy-portfolio-importer' ),
					$item['name']
				),
			);
		}

		$attachment_id = media_handle_sideload(
			array(
				'name'     => $name,
				'tmp_name' => $copy,
			),
			(int) $item['post_id'],
			pathinfo( $item['path'], PATHINFO_FILENAME )
		);

		if ( is_wp_error( $attachment_id ) ) {
			wp_delete_file( $copy );
			return array(
				'ok'    => false,
				'error' => sprintf(
					/* translators: 1: File name, 2: Error message. */
					__( 'Could not import "%1$s": %2$s', 'kirollos-magdy-portfolio-importer' ),
					$item['name'],
					$attachment_id->get_error_message()
				),
			);
		}

		$post_id = (int) $item['post_id'];

		// The logo is the featured image and the card logo, and stays out of the
		// gallery: among the screenshots it would read as a mistake.
		if ( ! empty( $item['logo'] ) ) {

			set_post_thumbnail( $post_id, (int) $attachment_id );
			update_post_meta( $post_id, self::logo_meta_key(), (int) $attachment_id );

			return array( 'ok' => true, 'error' => '' );
		}

		$current = get_post_meta( $post_id, KMPFI_Media_Assigner::GALLERY_META, true );
		$current = $current ? array_filter( array_map( 'absint', explode( ',', (string) $current ) ) ) : array();

		$current[] = (int) $attachment_id;

		$current = array_slice( array_values( array_unique( $current ) ), 0, self::LIMIT );

		update_post_meta(
			$post_id,
			KMPFI_Media_Assigner::GALLERY_META,
			implode( ',', $current )
		);

		if ( ! has_post_thumbnail( $post_id ) ) {
			set_post_thumbnail( $post_id, (int) $attachment_id );
		}

		return array( 'ok' => true, 'error' => '' );
	}

	/**
	 * @param string $dir
	 */
	private static function remove_dir( $dir ) {

		if ( ! is_dir( $dir ) ) {
			return;
		}

		$entries = glob( trailingslashit( $dir ) . '*' );
		$entries = is_array( $entries ) ? $entries : array();

		foreach ( $entries as $entry ) {
			if ( is_dir( $entry ) ) {
				self::remove_dir( $entry );
			} else {
				wp_delete_file( $entry );
			}
		}

		if ( function_exists( 'rmdir' ) ) {
			rmdir( $dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
	}
}
