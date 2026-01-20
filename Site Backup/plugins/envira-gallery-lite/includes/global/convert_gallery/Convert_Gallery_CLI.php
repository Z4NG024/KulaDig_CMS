<?php
/**
 * Convert Gallery CLI Class.
 *
 * @since 1.9.1
 *
 * @package Envira Gallery
 * @author  Envira Gallery Team <support@enviragallery.com>
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Convert Gallery CLI Class.
 */
class Convert_Gallery_CLI extends Convert_Gallery_Common {
	/**
	 * Register command.
	 *
	 * @return void
	 */
	public function register_command() {
		WP_CLI::add_command( 'envira convert-galleries', [ $this, 'envira_bulk_cli_convert_galleries' ] );
	}

	/**
	 * WP-CLI: Convert galleries to Envira format.
	 *
	 * @param array $args CLI arguments.
	 * @param array $assoc_args CLI associative arguments.
	 * @return void
	 */
	public function envira_bulk_cli_convert_galleries( $args, $assoc_args ) {
		// Check if 'post-type' is provided in the named arguments.
		if ( ! isset( $assoc_args['post-type'] ) || empty( $assoc_args['post-type'] ) ) {
			WP_CLI::error( 'You must provide a post type using the --post-type parameter. Example: --post-type=post' );
		}

		$selected_posttype = $assoc_args['post-type'];

		if ( ! post_type_exists( $selected_posttype ) ) {
			WP_CLI::error( "The post type '{$selected_posttype}' does not exist. Please provide a valid post type." );
		}

		// Fetch posts with the specified post type.
		$query_args = [
			'post_type'   => $selected_posttype,
			'post_status' => 'any',
			'numberposts' => -1,
		];
		$posts      = get_posts( $query_args );

		if ( empty( $posts ) ) {
			WP_CLI::error( "No posts found for the selected post type '{$selected_posttype}'. Please ensure there are posts of this type." );
		}

		$found_posts = [];
		foreach ( $posts as $post ) {
			if ( has_block( 'gallery', $post->post_content ) || has_shortcode( $post->post_content, 'gallery' ) ) {
				$found_posts[] = $post;
			}
		}

		if ( empty( $found_posts ) ) {
			WP_CLI::warning( 'No galleries found for conversion for the specified post type.' );
			return;
		}

		WP_CLI::log( sprintf( 'Found %d posts containing wordpress galleries.', count( $found_posts ) ) );

		$progress   = \WP_CLI\Utils\make_progress_bar( 'Converting galleries...', count( $found_posts ) );
		$converted  = 0;
		$failed     = 0;
		$logs_array = [];

		foreach ( $found_posts as $post ) {
			$post_id          = $post->ID;
			$original_content = $post->post_content;
			$updated_content  = $original_content; // Start with the current content.
			$needs_update     = false;

			$post_edit_url = admin_url( 'post.php?post=' . $post_id . '&action=edit' );

			// Processing gallery shortcodes.
			$this->process_gallery_shortcodes( $updated_content, $post_id, $needs_update );

			// Processing gallery blocks.
			$blocks = parse_blocks( $updated_content );
			$this->process_gallery_blocks( $blocks, $post_id, $needs_update );

			// Update post content if changes were made.
			if ( $needs_update ) {
				$updated_content = serialize_blocks( $blocks );

				wp_update_post(
					[
						'ID'           => $post_id,
						'post_content' => $updated_content,
					]
				);
				++$converted;
			} else {
				++$failed;
				$logs_array[] = "Post ID {$post_id}: No convertible gallery content found. [Edit Post]({$post_edit_url})";
			}
			$progress->tick();
		}
		$progress->finish();
		WP_CLI::log( "\033[32mGallery conversion process completed.\033[0m" );

		// After processing posts, prepare the data to display.
		$table_data = [
			[
				'Status' => 'Converted',
				'Count'  => $converted,
			],
			[
				'Status' => 'Failed',
				'Count'  => $failed,
			],
		];

		// Display the results as a table using format_items.
		\WP_CLI\Utils\format_items( 'table', $table_data, [ 'Status', 'Count' ] );

		if ( $converted > 0 ) {
			$post_text = $converted > 1 ? 'posts' : 'post';
			WP_CLI::log( "Conversion successfully for {$converted} {$post_text}." );
		}

		if ( $failed > 0 ) {
			$post_text = $failed > 1 ? 'posts' : 'post';
			WP_CLI::log( "Conversion failed for {$failed} {$post_text}." );
		}

		if ( ! empty( $logs_array ) ) {
			foreach ( $logs_array as $log ) {
				WP_CLI::log( $log );
			}
		}
	}
}
