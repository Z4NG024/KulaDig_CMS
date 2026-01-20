<?php
/**
 * Convert Gallery Main Class.
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
 * Convert Gallery Main Class.
 */
class Convert_Gallery_Main {
	/**
	 * Register hooks.
	 */
	public function hooks() {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			( new Convert_Gallery_CLI() )->register_command();
		}

		add_action(
			'rest_api_init',
			function () {
				$rest_converter = new Convert_Gallery_REST();
				$rest_converter->register_rest_routes();
			}
		);
	}
}
