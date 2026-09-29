<?php
/**
 * Work dates for the portfolio projects.
 *
 * The builder shows a work date on each card, opposite the button, reading the
 * `work_date` meta. This is where that gets filled in for all fourteen projects
 * at once instead of opening fourteen edit screens.
 *
 * NOTHING IS PREFILLED
 *
 * There is no record of when most of these builds were delivered, and a date on
 * a portfolio is a claim to a client, so none are invented here. The screen
 * starts empty and whatever is typed in is what gets written. The one figure
 * that is on record, Travoya Tours in 2025, is shown as a hint next to its row
 * rather than filled in, because the month is not recorded either.
 *
 * Dates are stored as Y-m, month and year only, which is the shape the
 * builder's month field uses. A day would be false precision on work that ran
 * for weeks.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMPFI_Work_Dates {

	const OPTION = 'kmpfi_work_dates';
	const META   = 'work_date';

	/**
	 * What is on record, shown as a hint. Not written anywhere.
	 *
	 * @var array
	 */
	const KNOWN = array(
		'https://travoyatours.com' => '2025',
	);

	/**
	 * live_url => Y-m
	 *
	 * @return array
	 */
	public static function all() {

		$saved = get_option( self::OPTION, array() );

		return is_array( $saved ) ? $saved : array();
	}

	/**
	 * @param string $url
	 * @return string '' when nothing is set.
	 */
	public static function get( $url ) {

		$all = self::all();

		return isset( $all[ $url ] ) ? (string) $all[ $url ] : '';
	}

	/**
	 * Saves the typed dates and writes them onto the projects that exist.
	 *
	 * @param array $raw url => date, straight off the form.
	 * @return array{saved:int,written:int,cleared:int}
	 */
	public static function save( $raw ) {

		$result = array(
			'saved'   => 0,
			'written' => 0,
			'cleared' => 0,
		);

		$clean = array();

		foreach ( KMPFI_Projects_Data::all() as $project ) {

			$url = isset( $project['meta']['live_url'] ) ? $project['meta']['live_url'] : '';

			if ( '' === $url ) {
				continue;
			}

			$value = isset( $raw[ $url ] ) ? sanitize_text_field( wp_unslash( $raw[ $url ] ) ) : '';

			// The month input hands back Y-m. A Y-m-d from an older build is
			// trimmed to its month; anything else is dropped rather than guessed
			// at.
			if ( preg_match( '/^(\d{4}-\d{2})-\d{2}$/', $value, $m ) ) {
				$value = $m[1];
			}

			if ( '' !== $value && ! preg_match( '/^\d{4}-\d{2}$/', $value ) ) {
				$value = '';
			}

			if ( '' !== $value ) {
				$clean[ $url ] = $value;
				$result['saved']++;
			}

			$post_id = KMPFI_Importer::find_existing( $project );

			if ( ! $post_id ) {
				continue;
			}

			if ( '' === $value ) {
				if ( get_post_meta( $post_id, self::META, true ) ) {
					delete_post_meta( $post_id, self::META );
					$result['cleared']++;
				}
				continue;
			}

			update_post_meta( $post_id, self::META, $value );
			$result['written']++;
		}

		update_option( self::OPTION, $clean, false );

		return $result;
	}

	/**
	 * Writes the saved date onto one project during an import.
	 *
	 * @param int   $post_id
	 * @param array $project
	 * @param bool  $overwrite
	 */
	public static function apply( $post_id, $project, $overwrite ) {

		$url = isset( $project['meta']['live_url'] ) ? $project['meta']['live_url'] : '';

		if ( '' === $url ) {
			return;
		}

		$date = self::get( $url );

		if ( '' === $date ) {
			return;
		}

		if ( ! $overwrite && get_post_meta( $post_id, self::META, true ) ) {
			return;
		}

		update_post_meta( $post_id, self::META, $date );
	}
}
