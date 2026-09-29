<?php
/**
 * Blog Importer module (formerly the standalone "Kirollos Blog Importer" plugin, v1.1.0).
 *
 * Imports the bundled, SEO-ready blog posts with categories, tags, excerpt, Yoast SEO title,
 * meta description, focus keyphrase and a branded cover image. Tools > Blog Importer.
 * Loaded by the main plugin file, which calls KMBI_Admin::init().
 *
 * Posts are written as Markdown in source/, then built into posts/ with:
 *   node build-posts.js && python make-covers.py <fonts-dir>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Version of the bundled posts, not of the plugin. Each imported post stores it, and posts
 * imported from an older bundle are pre-selected for re-import. Bump it only when posts/ changes.
 */
define( 'KMBI_VERSION', '1.1.0' );
define( 'KMBI_PATH', plugin_dir_path( __FILE__ ) );

require_once KMBI_PATH . 'includes/class-kmbi-importer.php';
require_once KMBI_PATH . 'includes/class-kmbi-admin.php';
