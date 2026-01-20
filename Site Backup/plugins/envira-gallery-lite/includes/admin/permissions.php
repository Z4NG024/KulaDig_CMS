<?php
/**
 * Permissions class.
 *
 * @since 1.8.9
 *
 * @package Envira_Gallery Lite
 * @author  Envira Gallery Team <support@enviragallery.com>
 */

namespace Envira\Admin;

use Envira\Admin\Envira_Capabilities;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Permissions class.
 *
 * @since 1.8.9
 *
 * @package Envira_Gallery Lite
 * @author  Envira Gallery Team <support@enviragallery.com>
 */
class Envira_Permissions {

	/**
	 * Permissions constructor.
	 *
	 * @since 1.8.9
	 */
	public function __construct() {
		// Actions.
		add_action( 'envira_permissions_update', [ $this, 'envira_permissions_update' ], 10, 1 );

		$this->set_default_permissions(); // Set default permissions.
	}

	/**
	 * Localize the permissions.
	 *
	 * @since 1.8.9
	 */
	public function envira_permissions_localize() {
		$permissions = $this->get_fields();
		$map         = [];
		foreach ( $permissions as $permission ) {
			switch ( $permission ) {
				case 'envira_permissions_edit_others':
					$map[ $permission ] = [ 'envira_permissions_edit_others' ];
					break;
				case 'envira_permissions_delete':
					$map[ $permission ] = [ 'envira_permissions_create', 'envira_permissions_edit', 'envira_permissions_delete' ];
					break;
				case 'envira_permissions_edit':
					$map[ $permission ] = [ 'envira_permissions_create', 'envira_permissions_edit' ];
					break;
				default:
					$map[ $permission ] = [ 'envira_permissions_create' ];
			}
		}

		$permissions_labels = [
			'envira_permissions_create'      => esc_html__( 'Create Galleries', 'envira-gallery-lite' ),
			'envira_permissions_edit'        => esc_html__( 'Edit Galleries', 'envira-gallery-lite' ),
			'envira_permissions_delete'      => esc_html__( 'Delete Galleries', 'envira-gallery-lite' ),
			'envira_permissions_edit_others' => esc_html__( 'Edit Others Galleries', 'envira-gallery-lite' ),
		];

		$map                = apply_filters( 'envira_permissions_fields_mapping', $map );
		$permissions_labels = apply_filters( 'envira_permissions_labels', $permissions_labels );

		wp_localize_script( ENVIRA_LITE_SLUG . '-permissions-check', 'enviraPermissions', $map );
		wp_localize_script( ENVIRA_LITE_SLUG . '-permissions-check', 'enviraPermissionsLabels', $permissions_labels );
	}

	/**
	 * Map the permissions and capabilities for user roles.
	 *
	 * @since 1.8.9
	 * @return array
	 */
	public function map_permissions(): array {
		// Map the permissions and capabilities and user roles.

		$permissions = [
			'envira_permissions_create'      => [ 'create_envira_galleries', 'read_envira_gallery', 'publish_envira_galleries' ],
			'envira_permissions_edit'        => [ 'edit_envira_gallery', 'edit_envira_galleries', 'edit_published_envira_galleries' ],
			'envira_permissions_delete'      => [ 'delete_envira_galleries', 'delete_envira_gallery', 'delete_published_envira_galleries' ],
			'envira_permissions_edit_others' => [
				'edit_other_envira_galleries',
				'edit_others_envira_galleries',
				'edit_private_envira_galleries',
				'read_private_envira_galleries',
			],
		];

		$permissions = apply_filters( 'envira_permissions_map', $permissions );

		return $permissions;
	}

	/**
	 * Update the permissions and capabilities for user roles.
	 * Also used to set permissions on plugin activation of addons.
	 *
	 * @since 1.8.9
	 *
	 * @param array $permissions The permissions.
	 */
	public function envira_permissions_update( $permissions ) {
		if ( empty( $permissions ) ) {
			return;
		}

		foreach ( $permissions as $permission => $capabilities ) {
			$roles = get_option( $permission, [] );
			$roles = maybe_unserialize( $roles );
			$this->add_capabilities_by_permissions( $permission, $roles, $permissions );
		}
	}

	/**
	 * Set the default permissions for user roles.
	 *
	 * @since 1.8.9
	 */
	public function set_default_permissions() {

		$has_default_permissions = get_option( 'envira_permissions_default' );

		if ( ! empty( $has_default_permissions ) ) {
			return;
		}

		$all    = [ 'administrator', 'editor', 'author' ];
		$editor = [ 'administrator', 'editor' ];

		$permissions = [
			'envira_permissions_create'      => $all,
			'envira_permissions_edit'        => $all,
			'envira_permissions_delete'      => $all,
			'envira_permissions_edit_others' => $editor,
		];

		$permissions = apply_filters( 'envira_permissions_default_roles', $permissions );

		foreach ( $permissions as $permission => $roles ) {
			update_option( $permission, maybe_serialize( $roles ) );
			$this->add_capabilities_by_permissions( $permission, $roles );
		}

		update_option( 'envira_permissions_default', 'yes' );
	}

	/**
	 * Add capabilities by permissions.
	 * This is used to add capabilities to user roles based on the permissions.
	 * Addons can use this to add capabilities to user roles on activation.
	 *
	 * @param string $new_permission The new permission to be added.
	 * @param array  $role_names        The role names.
	 * @param array  $permissions   The permissions array that contains mapping.
	 *
	 * @return void
	 */
	public function add_capabilities_by_permissions( string $new_permission, array $role_names, array $permissions = [] ) {

		if ( empty( $permissions ) ) {
			$permissions = $this->map_permissions();
		}

		// remove administrator role from the roles array.
		$role_names = array_diff( $role_names, [ 'administrator' ] );

		// Roles to remove permissions.
		$all_roles = array_keys( $this->get_user_roles() );
		// remove administrator role from the roles array.
		$all_roles = array_diff( $all_roles, [ 'administrator' ] );

		$roles_to_remove = [];
		foreach ( $all_roles as $role_name ) {
			if ( ! in_array( $role_name, $role_names, true ) ) {
				$roles_to_remove[] = $role_name;
			}
		}

		// Add the capabilities to the user roles if they are in the new permission.

		foreach ( $role_names as $role_name ) {
			$role = get_role( $role_name );
			if ( ! is_object( $role ) ) {
				continue;
			}
			$cap = $permissions[ $new_permission ];

			foreach ( $cap as $capability ) {
				if ( empty( $role->has_cap( $capability ) ) ) {
					$role->add_cap( $capability );
				}
			}
		}

		// Remove the capabilities from the user roles if they are not in the new permission.

		foreach ( $roles_to_remove as $role_name ) {
			$role = get_role( $role_name );
			// Skip if role is not found or if it's an administrator role.
			if ( ! is_object( $role ) ) {
				continue;
			}
			// Get the capabilities for the new permission.
			$cap = $permissions[ $new_permission ];

			foreach ( $cap as $capability ) {
				$check = $role->has_cap( $capability );
				if ( ! empty( $check ) ) {
					$role->remove_cap( $capability );
				}
			}
		}
	}

	/**
	 * Get the user roles.
	 * This is used to get the user roles to display in the permissions settings.
	 * Only roles with edit_posts capability are shown.
	 *
	 * @since 1.8.9
	 * @return array
	 */
	public function get_user_roles(): array {
		global $wp_roles;
		$roles_array  = [];
		$envira_roles = $wp_roles;
		if ( ! is_object( $envira_roles ) ) {
			// Don't assign this to the global because otherwise WordPress won't override it.
			$envira_roles = new \WP_Roles();
		}

		$role_names = $envira_roles->get_names();
		asort( $role_names );
		foreach ( $role_names as $role_name => $role_label ) {
			$role = get_role( $role_name );
			if ( ! is_object( $role ) ) {
				continue;
			}

			// Only show roles that can edit posts. To avoid showing roles that can't edit.
			if ( ! $role->has_cap( 'edit_posts' ) ) {
				continue;
			}
			$roles_array[ $role_name ] = $role_label;
		}
		return $roles_array;
	}

	/**
	 * Sanitize the field data.
	 *
	 * @since 1.8.9
	 *
	 * @param array $field_value The field value.
	 *
	 * @return array
	 */
	public function sanitize_field_data( array $field_value ): array {
		return array_map( 'sanitize_text_field', array_values( $field_value ) );
	}

	/**
	 * Get the fields.
	 *
	 * @since 1.8.9
	 *
	 * @return array
	 */
	public function get_fields(): array {
		$fields = [ 'envira_permissions_create', 'envira_permissions_edit', 'envira_permissions_delete', 'envira_permissions_edit_others' ];
		$fields = apply_filters( 'envira_permissions_fields', $fields );
		return $fields;
	}

	/**
	 * Render the permissions field.
	 *
	 * @since 1.8.9
	 *
	 * @param string $field The field name.
	 *
	 * @return void
	 */
	public function render_permissions_field( string $field ) {

		$allowed_html = [
			'select' => [
				'name'     => [],
				'id'       => [],
				'class'    => [],
				'multiple' => 'multiple',
			],
			'option' => [
				'value'    => [],
				'selected' => [],
			],
			'div'    => [
				'class' => [],
			],
		];

		$field_value = get_option( $field, [] );
		$field_value = maybe_unserialize( $field_value );
		$data        = '<div class="envira-permissions-field">';
		$data       .= '<select name="' . esc_attr( $field ) . '[]" id="' . esc_attr( $field ) . '" multiple class="envira-permissions-select-field">';
		$selected    = '';
		$role_names  = $this->get_user_roles();
		foreach ( $role_names as $role_name => $role_label ) {
			if ( isset( $field_value ) && is_array( $field_value ) ) {
				$selected = in_array( $role_name, $field_value, true ) ? 'selected' : '';
			}
			$data .= '<option value="' . esc_attr( $role_name ) . '" ' . esc_attr( $selected ) . '>' . esc_html( $role_label ) . '</option>';
		}
		$data .= '</select>';
		$data .= '</div>';
		echo wp_kses( $data, $allowed_html );
	}
}
