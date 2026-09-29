<?php
/**
 * Tools > Blog Importer admin screen.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMBI_Admin {

	const SLUG = 'kmbi-blog-importer';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_kmbi_import', array( __CLASS__, 'handle_import' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( KMPFI_FILE ), array( __CLASS__, 'action_link' ) );
	}

	public static function menu() {
		add_management_page( 'Blog Importer', 'Blog Importer', 'publish_posts', self::SLUG, array( __CLASS__, 'render' ) );
	}

	public static function action_link( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'tools.php?page=' . self::SLUG ) ) . '">Import blog posts</a>' );
		return $links;
	}

	public static function handle_import() {
		if ( ! current_user_can( 'publish_posts' ) ) {
			wp_die( 'You do not have permission to import posts.' );
		}
		check_admin_referer( 'kmbi_import' );

		// Uploading covers and generating image sizes can take a while for many posts.
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}

		$status   = ( isset( $_POST['kmbi_status'] ) && 'draft' === $_POST['kmbi_status'] ) ? 'draft' : 'publish';
		$selected = isset( $_POST['kmbi_posts'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['kmbi_posts'] ) ) : array();

		$done   = 0;
		$errors = array();
		foreach ( KMBI_Importer::get_manifest() as $item ) {
			if ( ! in_array( $item['id'], $selected, true ) ) {
				continue;
			}
			$result = KMBI_Importer::import( $item, $status );
			if ( is_wp_error( $result ) ) {
				$errors[] = $item['title'] . ': ' . $result->get_error_message();
			} else {
				++$done;
			}
		}

		set_transient( 'kmbi_notice_' . get_current_user_id(), array( 'done' => $done, 'errors' => $errors, 'status' => $status ), 60 );
		wp_safe_redirect( admin_url( 'tools.php?page=' . self::SLUG ) );
		exit;
	}

	public static function render() {
		$items  = KMBI_Importer::get_manifest();
		$notice = get_transient( 'kmbi_notice_' . get_current_user_id() );
		delete_transient( 'kmbi_notice_' . get_current_user_id() );
		$yoast = defined( 'WPSEO_VERSION' );
		?>
		<div class="wrap">
			<h1>Blog Importer</h1>
			<p><?php echo esc_html( count( $items ) ); ?> SEO-ready posts are bundled with this plugin. Each one is imported with its category, tags, excerpt, and Yoast SEO title, meta description, and focus keyphrase. Each post also gets its cover image as the featured image. Re-importing a post updates it instead of creating a duplicate, and a featured image you set yourself is never replaced. Posts that are new, changed in this version, or missing a cover are ticked for you.</p>

			<?php if ( ! $yoast ) : ?>
				<div class="notice notice-warning"><p>Yoast SEO is not active. Posts will still import, and the SEO fields will apply once Yoast is active.</p></div>
			<?php endif; ?>

			<?php if ( $notice ) : ?>
				<div class="notice notice-success is-dismissible"><p>
					<?php
					echo esc_html(
						sprintf(
							'%d post(s) imported as %s.',
							$notice['done'],
							'draft' === $notice['status'] ? 'drafts' : 'published'
						)
					);
					?>
				</p></div>
				<?php foreach ( $notice['errors'] as $error ) : ?>
					<div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div>
				<?php endforeach; ?>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="kmbi_import">
				<?php wp_nonce_field( 'kmbi_import' ); ?>

				<table class="widefat striped">
					<thead>
						<tr>
							<td class="check-column"><input type="checkbox" id="kmbi-all"></td>
							<th style="width:130px">Cover</th>
							<th>Post</th>
							<th>Focus keyphrase</th>
							<th>Category</th>
							<th>Status on this site</th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $items as $item ) : ?>
						<?php $existing = KMBI_Importer::find_existing( $item ); ?>
						<tr>
							<th class="check-column"><input type="checkbox" class="kmbi-item" name="kmbi_posts[]" value="<?php echo esc_attr( $item['id'] ); ?>" <?php checked( KMBI_Importer::needs_import( $existing ) ); ?>></th>
							<td>
								<?php if ( ! empty( $item['cover'] ) ) : ?>
									<img src="<?php echo esc_url( plugins_url( 'posts/' . $item['cover'], KMBI_PATH . 'blog-importer.php' ) ); ?>" alt="" width="120" style="border-radius:4px;display:block">
								<?php endif; ?>
							</td>
							<td><strong><?php echo esc_html( $item['title'] ); ?></strong><br><code>/<?php echo esc_html( $item['slug'] ); ?>/</code></td>
							<td><?php echo esc_html( $item['focus_keyword'] ); ?></td>
							<td><?php echo esc_html( $item['category'] ); ?></td>
							<td>
								<?php if ( $existing ) : ?>
									<?php echo esc_html( 'publish' === $existing->post_status ? 'Published' : ucfirst( $existing->post_status ) ); ?>
									&middot; <a href="<?php echo esc_url( get_edit_post_link( $existing->ID ) ); ?>">Edit</a>
									&middot; <a href="<?php echo esc_url( get_permalink( $existing->ID ) ); ?>" target="_blank" rel="noopener">View</a>
								<?php else : ?>
									Not imported
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>

				<p>
					<label><input type="radio" name="kmbi_status" value="publish" checked> Publish now</label>
					&nbsp;&nbsp;
					<label><input type="radio" name="kmbi_status" value="draft"> Import as drafts</label>
				</p>
				<p class="description">Posts that are already published stay published when you re-import them.</p>
				<?php submit_button( 'Import selected posts' ); ?>
			</form>
		</div>
		<script>
			document.getElementById('kmbi-all').addEventListener('change', function (e) {
				document.querySelectorAll('.kmbi-item').forEach(function (box) { box.checked = e.target.checked; });
			});
		</script>
		<?php
	}
}
