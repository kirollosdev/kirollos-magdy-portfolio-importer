<?php
/**
 * Assigns Media Library images to projects by filename.
 *
 * WHY BY FILENAME RATHER THAN FROM GOOGLE DRIVE
 *
 * A Drive folder URL containing /u/3/ is a per-account link, not a public one,
 * so nothing outside that signed-in browser can read it. Making the whole
 * folder public just so a site can fetch it is a worse trade than uploading the
 * images, which WordPress has to do anyway to serve them.
 *
 * So the flow is: upload the images to the Media Library, then this matches
 * them to projects by name.
 *
 * MATCHING
 *
 * Aliases come from each project's domain and client name, matched against the
 * attachment title, slug and stored file path. All aliases across all projects
 * are sorted longest first, so "almatjarhome" is tested before "almatjar" and
 * the two stores never collect each other's screenshots.
 *
 * WHAT IT WRITES
 *
 * The first matched image becomes the featured image. An image whose filename
 * says "logo" becomes the secondary image, which is what the portfolio cards
 * show as the client logo; without one, the featured image is used there too.
 * The rest are stored as a comma separated gallery meta.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMPFI_Media_Assigner {

	const GALLERY_META = 'gallery';

	/**
	 * The module's secondary image key, asked for by name so a change there
	 * carries over.
	 *
	 * @return string
	 */
	public static function logo_meta_key() {
		return function_exists( 'pw_secondary_image_meta_key' )
			? pw_secondary_image_meta_key()
			: '_secondary_featured_image';
	}

	/**
	 * Builds the alias list for one project.
	 *
	 * @param array $project
	 * @return string[]
	 */
	public static function aliases( $project ) {

		$aliases = array();

		$url  = isset( $project['meta']['live_url'] ) ? $project['meta']['live_url'] : '';
		$host = $url ? wp_parse_url( $url, PHP_URL_HOST ) : '';

		if ( $host ) {
			$host = preg_replace( '/^www\./', '', $host );

			// Drop the TLD: "almatjarhome.ly" -> "almatjarhome".
			$name = preg_replace( '/\.[a-z.]+$/i', '', $host );

			if ( $name ) {
				$aliases[] = $name;
				$aliases[] = str_replace( '-', '', $name );
			}
		}

		$client = isset( $project['meta']['client'] ) ? $project['meta']['client'] : '';

		if ( $client ) {
			$slug = sanitize_title( $client );
			if ( $slug ) {
				$aliases[] = $slug;
				$aliases[] = str_replace( '-', '', $slug );
			}
		}

		$aliases = array_values( array_unique( array_filter( $aliases, 'strlen' ) ) );

		// Two or three characters would match almost anything.
		return array_values(
			array_filter(
				$aliases,
				function ( $alias ) {
					return strlen( $alias ) >= 4;
				}
			)
		);
	}

	/**
	 * Normalised haystack for an attachment: title, slug and file path.
	 *
	 * @param WP_Post $attachment
	 * @return string
	 */
	private static function haystack( $attachment ) {

		$file = get_post_meta( $attachment->ID, '_wp_attached_file', true );

		$text = strtolower(
			implode(
				' ',
				array(
					(string) $attachment->post_title,
					(string) $attachment->post_name,
					(string) $file,
				)
			)
		);

		// Collapse anything that is not a letter or digit, so "Al Jawdah",
		// "al_jawdah" and "al-jawdah.png" all reduce to the same shape.
		return preg_replace( '/[^a-z0-9]+/', '', $text );
	}

	/**
	 * Every alias across every project, longest first.
	 *
	 * @return array[]
	 */
	private static function alias_index() {

		$index = array();

		foreach ( KMPFI_Projects_Data::all() as $project ) {

			$key = isset( $project['meta']['live_url'] ) ? $project['meta']['live_url'] : $project['title'];

			foreach ( self::aliases( $project ) as $alias ) {
				$index[] = array(
					'alias' => preg_replace( '/[^a-z0-9]+/', '', strtolower( $alias ) ),
					'key'   => $key,
					'title' => $project['title'],
				);
			}
		}

		usort(
			$index,
			function ( $a, $b ) {
				return strlen( $b['alias'] ) - strlen( $a['alias'] );
			}
		);

		return $index;
	}

	/**
	 * Works out which images belong to which project.
	 *
	 * @return array{projects:array,unmatched:array}
	 */
	public static function plan() {

		$index = self::alias_index();
		$plan  = array();

		foreach ( KMPFI_Projects_Data::all() as $project ) {
			$key          = isset( $project['meta']['live_url'] ) ? $project['meta']['live_url'] : $project['title'];
			$plan[ $key ] = array(
				'title'   => $project['title'],
				'post_id' => KMPFI_Importer::find_existing( $project ),
				'images'  => array(),
			);
		}

		$attachments = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'post_status'    => 'inherit',
				'posts_per_page' => 500,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$unmatched = array();

		foreach ( $attachments as $attachment ) {

			$haystack = self::haystack( $attachment );
			$matched  = false;

			foreach ( $index as $entry ) {

				if ( '' === $entry['alias'] || false === strpos( $haystack, $entry['alias'] ) ) {
					continue;
				}

				$name = basename( (string) get_post_meta( $attachment->ID, '_wp_attached_file', true ) );

				$plan[ $entry['key'] ]['images'][] = array(
					'id'   => $attachment->ID,
					'name' => $name,
					'logo' => ( false !== stripos( $name, 'logo' ) ),
				);

				$matched = true;
				break; // Longest alias wins; never assign one image twice.
			}

			if ( ! $matched ) {
				$unmatched[] = array(
					'id'   => $attachment->ID,
					'name' => basename( (string) get_post_meta( $attachment->ID, '_wp_attached_file', true ) ),
				);
			}
		}

		// Natural order, so image2 comes before image10.
		foreach ( $plan as $key => $row ) {
			usort(
				$plan[ $key ]['images'],
				function ( $a, $b ) {
					return strnatcasecmp( $a['name'], $b['name'] );
				}
			);
		}

		return array(
			'projects'  => $plan,
			'unmatched' => $unmatched,
		);
	}

	/**
	 * Writes the matched images onto each project.
	 *
	 * @return array{updated:int,images:int,logos:int,missing:int}
	 */
	public static function run() {

		$result = array(
			'updated' => 0,
			'images'  => 0,
			'logos'   => 0,
			'missing' => 0,
		);

		$plan = self::plan();

		foreach ( $plan['projects'] as $row ) {

			if ( ! $row['post_id'] ) {
				// The project has not been created yet.
				if ( $row['images'] ) {
					$result['missing']++;
				}
				continue;
			}

			if ( ! $row['images'] ) {
				continue;
			}

			$ids = wp_list_pluck( $row['images'], 'id' );

			// Merge with whatever is already there rather than replacing it.
			$current = get_post_meta( $row['post_id'], self::GALLERY_META, true );
			$current = $current ? array_filter( array_map( 'absint', explode( ',', (string) $current ) ) ) : array();
			$merged  = array_values( array_unique( array_merge( $current, $ids ) ) );

			update_post_meta( $row['post_id'], self::GALLERY_META, implode( ',', $merged ) );

			if ( ! has_post_thumbnail( $row['post_id'] ) ) {
				set_post_thumbnail( $row['post_id'], (int) $ids[0] );
			}

			// The card logo: an image named "logo" if one came through.
			$logo_key = self::logo_meta_key();

			if ( ! get_post_meta( $row['post_id'], $logo_key, true ) ) {

				$logo_id = 0;

				foreach ( $row['images'] as $image ) {
					if ( ! empty( $image['logo'] ) ) {
						$logo_id = (int) $image['id'];
						break;
					}
				}

				if ( $logo_id ) {
					update_post_meta( $row['post_id'], $logo_key, $logo_id );
					$result['logos']++;
				}
			}

			$result['updated']++;
			$result['images'] += count( $ids );
		}

		return $result;
	}
}
