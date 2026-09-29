<?php
/**
 * Creates the portfolio projects.
 *
 * Written against the current portfolio system: the `portfolios` post type from
 * the portfolio-widgets module, its portfolio_category / portfolio_service /
 * portfolio_industry taxonomies, and the project_url meta the archive cards
 * read. The older importer wrote to the `project` post type, which no longer
 * exists.
 *
 * Two rules govern this class:
 *
 *   1. It never creates a duplicate. Every project is matched on its live URL
 *      meta and then on its title, so running the import twice, or after adding
 *      a project by hand, updates or skips rather than doubling up.
 *   2. It never overwrites work. On an existing project only fields that are
 *      currently empty are filled, so anything typed in by hand survives.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMPFI_Importer {

	const TAX_CATEGORY = 'portfolio_category';
	const TAX_SERVICE  = 'portfolio_service';
	const TAX_INDUSTRY = 'portfolio_industry';

	/**
	 * The post type to write to, read from the module so a renamed post type
	 * stays in step.
	 *
	 * @return string
	 */
	public static function post_type() {

		if ( class_exists( '\KirollosMagdy\PortfolioWidgets\Module' ) ) {
			$module = \KirollosMagdy\PortfolioWidgets\Module::instance();
			if ( $module && method_exists( $module, 'post_type' ) ) {
				return $module->post_type();
			}
		}

		return 'portfolios';
	}

	/**
	 * Whether the environment can actually take the import.
	 *
	 * @return array{ok:bool,messages:string[]}
	 */
	public static function preflight() {

		$messages = array();
		$ok       = true;

		if ( ! post_type_exists( self::post_type() ) ) {
			$ok         = false;
			$messages[] = sprintf(
				/* translators: %s: Post type slug. */
				__( 'The "%s" post type does not exist. Activate Kirollos Magdy Portfolio Builder first.', 'kirollos-magdy-portfolio-importer' ),
				self::post_type()
			);
		}

		foreach ( array( self::TAX_CATEGORY, self::TAX_SERVICE, self::TAX_INDUSTRY ) as $taxonomy ) {
			if ( ! taxonomy_exists( $taxonomy ) ) {
				$messages[] = sprintf(
					/* translators: %s: Taxonomy slug. */
					__( 'The "%s" taxonomy is not registered, so those terms will be skipped. Everything else still imports.', 'kirollos-magdy-portfolio-importer' ),
					$taxonomy
				);
			}
		}

		return array(
			'ok'       => $ok,
			'messages' => $messages,
		);
	}

	/**
	 * Finds an existing project for this entry.
	 *
	 * @param array $project
	 * @return int 0 when there is no match.
	 */
	public static function find_existing( $project ) {

		$url = isset( $project['meta']['live_url'] ) ? $project['meta']['live_url'] : '';

		if ( $url ) {
			$found = get_posts(
				array(
					'post_type'      => self::post_type(),
					'post_status'    => 'any',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						'relation' => 'OR',
						array(
							'key'   => 'project_url',
							'value' => $url,
						),
						array(
							'key'   => 'live_url',
							'value' => $url,
						),
					),
				)
			);

			if ( $found ) {
				return (int) $found[0];
			}
		}

		// Falls back to the title, catching projects added by hand before this ran.
		$by_title = get_posts(
			array(
				'post_type'              => self::post_type(),
				'post_status'            => 'any',
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'title'                  => $project['title'],
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		return $by_title ? (int) $by_title[0] : 0;
	}

	/**
	 * Builds a preview of what the import would do, writing nothing.
	 *
	 * @return array[]
	 */
	public static function plan() {

		$plan = array();

		foreach ( KMPFI_Projects_Data::all() as $project ) {

			$existing = self::find_existing( $project );

			$terms = KMPFI_Projects_Data::terms_for( $project );

			$plan[] = array(
				'title'    => $project['title'],
				'url'      => isset( $project['meta']['live_url'] ) ? $project['meta']['live_url'] : '',
				'industry' => implode( ', ', (array) $terms['industry'] ),
				'services' => implode( ', ', (array) $terms['service'] ),
				'existing' => $existing,
				'action'   => $existing ? 'update' : 'create',
			);
		}

		return $plan;
	}

	/**
	 * Runs the import.
	 *
	 * @param string $status Post status for newly created projects.
	 * @return array{created:int,updated:int,skipped:int,errors:string[]}
	 */
	public static function run( $status = 'publish' ) {

		$result = array(
			'created' => 0,
			'updated' => 0,
			'skipped' => 0,
			'errors'  => array(),
		);

		if ( ! in_array( $status, array( 'draft', 'publish' ), true ) ) {
			$status = 'publish';
		}

		$check = self::preflight();

		if ( ! $check['ok'] ) {
			$result['errors'] = $check['messages'];
			return $result;
		}

		foreach ( KMPFI_Projects_Data::all() as $project ) {

			$existing = self::find_existing( $project );

			if ( $existing ) {
				self::fill_gaps( $existing, $project );
				$result['updated']++;
				continue;
			}

			$post_id = wp_insert_post(
				array(
					'post_type'    => self::post_type(),
					'post_title'   => $project['title'],
					'post_excerpt' => isset( $project['excerpt'] ) ? $project['excerpt'] : '',
					'post_content' => self::build_content( $project ),
					'post_status'  => $status,
				),
				true
			);

			if ( is_wp_error( $post_id ) || ! $post_id ) {
				$result['skipped']++;
				$result['errors'][] = sprintf(
					/* translators: 1: Project title, 2: Error message. */
					__( 'Could not create "%1$s": %2$s', 'kirollos-magdy-portfolio-importer' ),
					$project['title'],
					is_wp_error( $post_id ) ? $post_id->get_error_message() : __( 'unknown error', 'kirollos-magdy-portfolio-importer' )
				);
				continue;
			}

			self::write_meta( $post_id, $project, true );
			self::assign_terms( $post_id, $project );

			if ( class_exists( 'KMPFI_Work_Dates' ) ) {
				KMPFI_Work_Dates::apply( $post_id, $project, true );
			}

			$result['created']++;
		}

		return $result;
	}

	/**
	 * The post body, built from the challenge / solution / results copy.
	 *
	 * The current portfolio system has no fixed detail fields, so this is where
	 * that writing has to live for it to appear on the project page. It is only
	 * ever written into an empty body.
	 *
	 * @param array $project
	 * @return string
	 */
	private static function build_content( $project ) {

		$sections = array(
			__( 'The Challenge', 'kirollos-magdy-portfolio-importer' ) => isset( $project['meta']['challenge'] ) ? $project['meta']['challenge'] : '',
			__( 'The Solution', 'kirollos-magdy-portfolio-importer' )  => isset( $project['meta']['solution'] ) ? $project['meta']['solution'] : '',
			__( 'The Result', 'kirollos-magdy-portfolio-importer' )    => isset( $project['meta']['results'] ) ? $project['meta']['results'] : '',
		);

		$out = '';

		foreach ( $sections as $heading => $body ) {
			if ( '' === trim( (string) $body ) ) {
				continue;
			}
			$out .= '<h2>' . esc_html( $heading ) . '</h2>' . "\n";
			$out .= '<p>' . wp_kses_post( $body ) . '</p>' . "\n\n";
		}

		return $out;
	}

	/**
	 * Fills only what is currently empty on an existing project.
	 *
	 * @param int   $post_id
	 * @param array $project
	 */
	private static function fill_gaps( $post_id, $project ) {

		self::write_meta( $post_id, $project, false );
		self::assign_terms( $post_id, $project );

		if ( class_exists( 'KMPFI_Work_Dates' ) ) {
			KMPFI_Work_Dates::apply( $post_id, $project, false );
		}

		$post = get_post( $post_id );

		if ( ! $post ) {
			return;
		}

		$update = array();

		if ( '' === trim( (string) $post->post_excerpt ) && ! empty( $project['excerpt'] ) ) {
			$update['post_excerpt'] = $project['excerpt'];
		}

		if ( '' === trim( (string) $post->post_content ) ) {
			$content = self::build_content( $project );
			if ( '' !== $content ) {
				$update['post_content'] = $content;
			}
		}

		if ( $update ) {
			$update['ID'] = $post_id;
			wp_update_post( $update );
		}
	}

	/**
	 * @param int   $post_id
	 * @param array $project
	 * @param bool  $overwrite Whether to write over a value that already exists.
	 */
	private static function write_meta( $post_id, $project, $overwrite ) {

		$meta = isset( $project['meta'] ) ? $project['meta'] : array();

		// project_url is the one the archive cards and the Project Link box read.
		if ( ! empty( $meta['live_url'] ) ) {
			$meta['project_url'] = $meta['live_url'];
		}

		foreach ( $meta as $key => $value ) {

			if ( '' === $value || null === $value ) {
				continue;
			}

			if ( ! $overwrite ) {
				$current = get_post_meta( $post_id, $key, true );
				if ( '' !== $current && null !== $current && false !== $current ) {
					continue;
				}
			}

			if ( 'live_url' === $key || 'project_url' === $key ) {
				$clean = esc_url_raw( $value );
			} elseif ( in_array( $key, array( 'challenge', 'solution', 'results' ), true ) ) {
				$clean = wp_kses_post( $value );
			} else {
				$clean = sanitize_text_field( $value );
			}

			update_post_meta( $post_id, $key, $clean );
		}
	}

	/**
	 * Categories, services and industries in one pass.
	 *
	 * The terms come from the curated vocabulary in the data class, not from the
	 * prose fields. They REPLACE what is on the project rather than being added
	 * to it, which is the point: a re-run is how the old one-project-per-term
	 * list gets tidied into the shorter one.
	 *
	 * @param int   $post_id
	 * @param array $project
	 */
	private static function assign_terms( $post_id, $project ) {

		$terms = KMPFI_Projects_Data::terms_for( $project );

		$map = array(
			self::TAX_CATEGORY => isset( $terms['category'] ) ? (array) $terms['category'] : array(),
			self::TAX_SERVICE  => isset( $terms['service'] ) ? (array) $terms['service'] : array(),
			self::TAX_INDUSTRY => isset( $terms['industry'] ) ? (array) $terms['industry'] : array(),
		);

		foreach ( $map as $taxonomy => $names ) {

			$names = array_filter( array_map( 'trim', $names ), 'strlen' );

			if ( ! $names || ! taxonomy_exists( $taxonomy ) ) {
				continue;
			}

			$term_ids = array();

			foreach ( $names as $name ) {

				$term = get_term_by( 'name', $name, $taxonomy );

				if ( $term ) {
					$term_ids[] = (int) $term->term_id;
					continue;
				}

				$created = wp_insert_term( $name, $taxonomy );

				if ( is_wp_error( $created ) ) {
					// A term with the same slug already exists: its id rides along
					// in the error, as an int or inside an array depending on
					// which check tripped.
					$data = $created->get_error_data();

					if ( is_array( $data ) && isset( $data['term_id'] ) ) {
						$term_ids[] = (int) $data['term_id'];
					} elseif ( is_numeric( $data ) ) {
						$term_ids[] = (int) $data;
					}

					continue;
				}

				$term_ids[] = (int) $created['term_id'];
			}

			if ( $term_ids ) {
				wp_set_object_terms( $post_id, $term_ids, $taxonomy, false );
			}
		}
	}

	/**
	 * Deletes portfolio terms that no longer have a project on them.
	 *
	 * Re-running the import moves every project onto the short vocabulary, which
	 * leaves the old specific terms behind at zero. This clears those out. It
	 * only ever touches empty terms, so a term you created and used by hand is
	 * never at risk.
	 *
	 * @return array{deleted:int,names:string[]}
	 */
	public static function remove_empty_terms() {

		$result = array(
			'deleted' => 0,
			'names'   => array(),
		);

		foreach ( array( self::TAX_CATEGORY, self::TAX_SERVICE, self::TAX_INDUSTRY ) as $taxonomy ) {

			if ( ! taxonomy_exists( $taxonomy ) ) {
				continue;
			}

			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
				)
			);

			if ( is_wp_error( $terms ) ) {
				continue;
			}

			foreach ( $terms as $term ) {

				if ( (int) $term->count > 0 ) {
					continue;
				}

				$deleted = wp_delete_term( $term->term_id, $taxonomy );

				if ( true === $deleted ) {
					$result['deleted']++;
					$result['names'][] = $term->name;
				}
			}
		}

		return $result;
	}
}
