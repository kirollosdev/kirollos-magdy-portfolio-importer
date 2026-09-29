<?php
/**
 * Plugin Name:       Kirollos Magdy Portfolio Importer
 * Plugin URI:        https://wa.me/+201016324429
 * Description:       One-click content for kirollosmagdy.com, in the Kirollos Magdy admin menu. Portfolio Import creates every portfolio project against the portfolios post type and its Category, Service and Industry taxonomies. Blog Importer publishes the bundled SEO blog posts with covers, dates and Yoast SEO fields. A helper for Kirollos Magdy Portfolio Builder.
 * Version:           1.9.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Kirollos Magdy - WordPress Developer
 * Author URI:        https://wa.me/+201016324429
 * Text Domain:       kirollos-magdy-portfolio-importer
 * License:           Proprietary. All rights reserved.
 * License URI:       https://github.com/kirollosdev/kirollos-magdy-portfolio-importer/blob/main/LICENSE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

define( 'KMPFI_VERSION', '1.9.0' );
define( 'KMPFI_FILE', __FILE__ );
define( 'KMPFI_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Bootstrap.
 *
 * Admin only, and deliberately separate from the builder: it writes posts once
 * and then has nothing left to do, so it can be deactivated and deleted after
 * the import without affecting the portfolio.
 *
 * Everything is prefixed KMPFI_ rather than the older importer's KMPI_, so both
 * plugins can sit in the same install without colliding.
 */
final class Kirollos_Magdy_Portfolio_Importer {

	/** @var self|null */
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'boot' ) );
	}

	public function boot() {

		if ( ! is_admin() ) {
			return;
		}

		require_once KMPFI_PATH . 'includes/class-projects-data.php';
		require_once KMPFI_PATH . 'includes/class-importer.php';
		require_once KMPFI_PATH . 'includes/class-media-assigner.php';
		require_once KMPFI_PATH . 'includes/class-work-dates.php';
		require_once KMPFI_PATH . 'includes/class-gallery-zip.php';
		require_once KMPFI_PATH . 'includes/class-admin-page.php';

		new KMPFI_Admin_Page();

		$this->load_blog_importer();
	}

	/**
	 * Blog Importer module, formerly the standalone "Kirollos Blog Importer" plugin.
	 * If that plugin is still active its classes are already loaded, and loading a second
	 * copy would fatal on the duplicate class names, so this copy steps aside and asks for
	 * the standalone one to be deactivated.
	 */
	private function load_blog_importer() {
		if ( class_exists( 'KMBI_Importer', false ) || defined( 'KMBI_PATH' ) ) {
			add_action( 'admin_notices', array( $this, 'notice_duplicate_blog_importer' ) );
			return;
		}

		require_once KMPFI_PATH . 'modules/blog-importer/blog-importer.php';
		KMBI_Admin::init();
	}

	public function notice_duplicate_blog_importer() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		printf(
			'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
			wp_kses(
				sprintf(
					/* translators: 1: This plugin 2: The standalone plugin */
					__( '%1$s now includes the Blog Importer. Deactivate and delete the separate %2$s plugin so the built-in version can take over.', 'kirollos-magdy-portfolio-importer' ),
					'<strong>Kirollos Magdy Portfolio Importer</strong>',
					'<strong>Kirollos Blog Importer</strong>'
				),
				array( 'strong' => array() )
			)
		);
	}
}

Kirollos_Magdy_Portfolio_Importer::instance();
