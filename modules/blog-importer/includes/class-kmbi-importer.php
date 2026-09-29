<?php
/**
 * Reads the bundled posts and creates or updates them in WordPress.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMBI_Importer {

	/** Post meta key that links a WordPress post to its bundled source id. */
	const SOURCE_META = '_kmbi_source_id';

	/**
	 * Bundled posts from posts/manifest.json.
	 *
	 * @return array[]
	 */
	public static function get_manifest() {
		$file = KMBI_PATH . 'posts/manifest.json';
		if ( ! is_readable( $file ) ) {
			return array();
		}
		$data = json_decode( file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		return is_array( $data ) ? $data : array();
	}

	/**
	 * Finds the WordPress post created from a bundled post, by source id, then by slug.
	 *
	 * @param array $item Manifest entry.
	 * @return WP_Post|null
	 */
	public static function find_existing( $item ) {
		$found = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'meta_key'       => self::SOURCE_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $item['id'], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
		if ( $found ) {
			return $found[0];
		}
		$by_slug = get_page_by_path( $item['slug'], OBJECT, 'post' );
		return $by_slug instanceof WP_Post ? $by_slug : null;
	}

	/**
	 * Creates or updates one post.
	 *
	 * @param array  $item   Manifest entry.
	 * @param string $status 'publish' or 'draft'. Existing published posts are never unpublished.
	 * @return int|WP_Error Post ID.
	 */
	public static function import( $item, $status ) {
		$file = KMBI_PATH . 'posts/' . basename( $item['file'] );
		if ( ! is_readable( $file ) ) {
			return new WP_Error( 'kmbi_missing_file', sprintf( 'Content file missing: %s', $item['file'] ) );
		}

		$content = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		// Internal links are stored root-relative; point them at this site's home URL.
		$home    = untrailingslashit( home_url() );
		$content = str_replace( 'href="/', 'href="' . esc_url( $home ) . '/', $content );

		$existing = self::find_existing( $item );
		if ( $existing && 'publish' === $existing->post_status ) {
			$status = 'publish';
		}

		$postarr = array(
			'post_type'     => 'post',
			'post_title'    => $item['title'],
			'post_name'     => $item['slug'],
			'post_content'  => $content,
			'post_excerpt'  => $item['excerpt'],
			'post_status'   => $status,
			'post_category' => array( self::get_category_id( $item['category'] ) ),
			'tags_input'    => $item['tags'],
		);

		if ( $existing ) {
			$postarr['ID'] = $existing->ID;
			$post_id       = wp_update_post( wp_slash( $postarr ), true );
		} else {
			$postarr['post_author'] = get_current_user_id();
			$post_id                = wp_insert_post( wp_slash( $postarr ), true );
		}

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		update_post_meta( $post_id, self::SOURCE_META, $item['id'] );
		update_post_meta( $post_id, '_yoast_wpseo_title', $item['seo_title'] );
		update_post_meta( $post_id, '_yoast_wpseo_metadesc', $item['meta_description'] );
		update_post_meta( $post_id, '_yoast_wpseo_focuskw', $item['focus_keyword'] );
		update_post_meta( $post_id, '_kmbi_version', KMBI_VERSION );

		$cover = self::attach_cover( $post_id, $item );
		if ( is_wp_error( $cover ) ) {
			return $cover;
		}

		return $post_id;
	}

	/**
	 * Whether a post needs importing: new, imported by an older plugin version, or missing its cover.
	 *
	 * @param WP_Post|null $existing Existing post.
	 * @return bool
	 */
	public static function needs_import( $existing ) {
		if ( ! $existing ) {
			return true;
		}
		return KMBI_VERSION !== get_post_meta( $existing->ID, '_kmbi_version', true ) || ! has_post_thumbnail( $existing->ID );
	}

	/**
	 * Sets the bundled cover as the featured image. A featured image you chose yourself is never replaced.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $item    Manifest entry.
	 * @return int|WP_Error|null Attachment ID, error, or null when skipped.
	 */
	private static function attach_cover( $post_id, $item ) {
		if ( empty( $item['cover'] ) || has_post_thumbnail( $post_id ) ) {
			return null;
		}
		$src = KMBI_PATH . 'posts/' . $item['cover'];
		if ( ! is_readable( $src ) ) {
			return null;
		}

		$reuse = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_kmbi_cover_for', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $item['id'], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		if ( $reuse ) {
			$attachment_id = (int) $reuse[0];
		} else {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';

			$tmp = wp_tempnam( basename( $src ) );
			if ( ! $tmp || ! copy( $src, $tmp ) ) {
				return new WP_Error( 'kmbi_cover_copy', 'Could not prepare the cover image for upload.' );
			}
			$file          = array(
				'name'     => basename( $src ),
				'tmp_name' => $tmp,
			);
			$attachment_id = media_handle_sideload( $file, $post_id, $item['cover_alt'] );
			if ( is_wp_error( $attachment_id ) ) {
				wp_delete_file( $tmp );
				return $attachment_id;
			}
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', $item['cover_alt'] );
			update_post_meta( $attachment_id, '_kmbi_cover_for', $item['id'] );
		}

		set_post_thumbnail( $post_id, $attachment_id );
		return $attachment_id;
	}

	/**
	 * Returns the category ID for a name, creating the category if needed.
	 *
	 * @param string $name Category name.
	 * @return int
	 */
	private static function get_category_id( $name ) {
		$term = term_exists( $name, 'category' );
		if ( ! $term ) {
			$term = wp_insert_term( $name, 'category' );
		}
		if ( is_wp_error( $term ) ) {
			return (int) get_option( 'default_category' );
		}
		return (int) ( is_array( $term ) ? $term['term_id'] : $term );
	}
}
