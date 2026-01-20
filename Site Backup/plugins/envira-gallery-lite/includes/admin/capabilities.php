<?php
/**
 * Capabilities class.
 *
 * @since 1.3.7
 *
 * @package Envira_Gallery
 * @author  Envira Team
 */

namespace Envira\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

/**
 * Capabilities class.
 *
 * @since 1.8.9
 *
 * @package Envira_Gallery
 * @author  Envira Team
 */
class Envira_Capabilities {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Register capabilities.
		add_action( 'admin_init', [ $this, 'add_capabilities' ] );
		add_filter( 'pre_get_posts', [ $this, 'show_only_user_envira_galleries' ] );
		add_action( 'current_screen', [ $this,'restrict_envira_gallery_access' ] );
	}

	/**
	 * Restricts access to Envira Galleries based on user capabilities.
	 *
	 * This function is hooked into the current_screen action, and checks if the current user has the required capability to edit other users' galleries.
	 * If the user doesn't have the capability, they're redirected to the Envira Galleries page.
	 *
	 * @return void
	 */
	public function restrict_envira_gallery_access() {
		$screen = get_current_screen();
		if ( 'envira' !== $screen->post_type ) {
			return;
		}

		$gallery_id = isset( $_GET['post'] ) ? absint( sanitize_key( $_GET['post'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification

		if ( ! $gallery_id ) {
			return;
		}

		$gallery = get_post( $gallery_id );

		if ( get_current_user_id() === (int) $gallery->post_author ) {
			return;
		}

		// If the user doesn't have the capability to edit other users' galleries, redirect to listing.

		if ( ! current_user_can( 'edit_others_envira_galleries' ) ) {
			wp_safe_redirect( admin_url( 'edit.php?post_type=envira' ) );
			exit;
		}
	}

	/**
	 * Restricts access to Envira Galleries based on user capabilities.
	 *
	 * This function is hooked into the pre_get_posts action, and checks if the current user has the required capability to edit other users' galleries.
	 * If the user doesn't have the capability, they're only shown their own galleries.
	 *
	 * @param WP_Query $query The WP_Query object.
	 *
	 * @return WP_Query
	 */
	public function show_only_user_envira_galleries( $query ) {
		global $pagenow;

		if ( 'edit.php' !== $pagenow || ! $query->is_admin ) {
			return $query;
		}

		if ( 'envira' !== $query->get( 'post_type' ) ) {
			return $query;
		}

		if ( ! current_user_can( 'edit_others_envira_galleries' ) ) {
			global $user_ID;
			$query->set( 'author', $user_ID );
		}
		return $query;
	}

	/**
	 * Registers Envira Gallery capabilities for each Role, if they don't already exist.
	 *
	 * If capabilities don't exist, they're copied from Posts. This ensures users prior to 1.8.9
	 * get like-for-like behaviour in Envira and don't notice the new capabilities.
	 *
	 * @since 1.0.0
	 */
	public function add_capabilities() {

		// Bail out if it is settings page update action. - Ignore nonce check as we don't use post data here.
		// phpcs:ignore WordPress.Security.NonceVerification
		if ( isset( $_POST['envira-permissions-nonce'] ) && sanitize_text_field( wp_unslash( $_POST['envira-permissions-nonce'] ) ) !== null ) {
			return;
		}

		// Grab the administrator role, and if it already has an Envira capability key defined, bail
		// as we only need to register our capabilities once.
		$administrator = get_role( 'administrator' );
		if ( $administrator->has_cap( 'edit_other_envira_galleries' ) ) {
			return;
		}

		// If here, we need to assign capabilities
		// Define the roles we want to assign capabilities to.
		$roles = [
			'administrator',
			'editor',
			'author',
			'contributor',
			'subscriber',
		];

		// Iterate through roles.
		foreach ( $roles as $role_name ) {
			// Properly get the role as WP_Role object.
			$role = get_role( $role_name );
			if ( ! is_object( $role ) ) {
				continue;
			}

			// Map this Role's Post capabilities to our Envira Gallery capabilities.
			$caps = [
				'edit_envira_gallery'               => $role->has_cap( 'edit_posts' ),
				'read_envira_gallery'               => $role->has_cap( 'read' ),
				'delete_envira_gallery'             => $role->has_cap( 'delete_posts' ),

				'edit_envira_galleries'             => $role->has_cap( 'edit_posts' ),
				'edit_other_envira_galleries'       => $role->has_cap( 'edit_others_posts' ),
				'edit_others_envira_galleries'      => $role->has_cap( 'edit_others_posts' ),
				'publish_envira_galleries'          => $role->has_cap( 'publish_posts' ),
				'read_private_envira_galleries'     => $role->has_cap( 'read_private_posts' ),

				'delete_envira_galleries'           => $role->has_cap( 'delete_posts' ),
				'delete_private_envira_galleries'   => $role->has_cap( 'delete_private_posts' ),
				'delete_published_envira_galleries' => $role->has_cap( 'delete_published_posts' ),
				'delete_others_envira_galleries'    => $role->has_cap( 'delete_others_posts' ),
				'edit_private_envira_galleries'     => $role->has_cap( 'edit_private_posts' ),
				'edit_published_envira_galleries'   => $role->has_cap( 'edit_published_posts' ),
				'create_envira_galleries'           => $role->has_cap( 'edit_posts' ),
			];

			// Add the above Envira capabilities to this Role.
			foreach ( $caps as $envira_cap => $value ) {
				// Don't add if value is false.
				if ( ! $value ) {
					continue;
				}
				$role->add_cap( $envira_cap );
			}
		}
	}
}
