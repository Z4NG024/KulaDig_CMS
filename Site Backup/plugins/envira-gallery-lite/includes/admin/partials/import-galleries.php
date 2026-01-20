<?php
/**
 * Envira Importer Template
 *
 * @package Envira Gallery Lite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>
<div class="envira-admin-content">
	<div class="envira-admin-modal">
		<div class="envira-admin-modal-content">
			<h2>
				<?php esc_html_e( 'Importing Galleries is a Pro Feature', 'envira-gallery-lite' ); ?>
			</h2>
			<p>
				<?php
				esc_html_e(
					'We\'re sorry, importing your Foo Gallery or Modula Gallery is not available in your plan. Please upgrade to the Pro plan to unlock this awesome feature.
',
					'envira-gallery-lite'
				);
				?>
			</p>
		</div>
		<div class="envira-admin-modal-bonus">
			<svg class="check" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="28px" height="28px" fill="#00ac53">
				<path d="M256 48a208 208 0 110 416 208 208 0 110-416zm0 464a256 256 0 100-512 256 256 0 100 512zm113-303c9.4-9.4 9.4-24.6 0-33.9s-24.6-9.4-33.9 0l-111 111-47-47c-9.4-9.4-24.6-9.4-33.9 0s-9.4 24.6 0 33.9l64 64c9.4 9.4 24.6 9.4 33.9 0L369 209z"></path>
			</svg>
			<p>
				<?php
				printf(
					// Translators: %1$s - Opening strong tag, do not translate. %2$s - Closing strong tag, do not translate. %3$s - Opening span tag, do not translate. %4$s - Closing strong tag, do not translate.
					esc_html__( '%1$sBonus%2$s: Envira Gallery Lite users get %3$s 50%% off%4$s regular price, automatically applied at checkout', 'envira-gallery-lite' ),
					'<strong>',
					'</strong>',
					'<span class="envira-green"><strong>',
					'</strong></span>'
				);
				?>
			</p>
		</div>
		<div class="envira-admin-modal-button">
			<a href="<?php echo esc_url( Envira_Gallery_Common_Admin::get_instance()->get_upgrade_link( 'https://enviragallery.com/pricing', 'importerPage', 'upgrade' ) ); ?>" class="button envira-button envira-primary-button" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'Upgrade to Envira Gallery Pro Now', 'envira-gallery-lite' ); ?>
			</a>
		</div>
		<a class="envira-admin-modal-text-link" href="<?php echo esc_url( admin_url( 'edit.php?post_type=envira&page=envira-gallery-settings' ) ); ?>"><?php esc_html_e( 'Already Purchased?', 'envira-gallery-lite' ); ?></a>
	</div>
	<div class="wrap">
		<div id="envira-settings-importer">

			<table class="wp-list-table widefat fixed striped table-view-excerpt modula-galleries">
				<thead>
					<tr>
						<td id="cb" class="manage-column column-cb check-column"><input id="cb-select-all-1" type="checkbox">
							<label for="cb-select-all-1"><span class="screen-reader-text">Select All</span></label>
						</td>
						<th scope="col" id="id" class="manage-column column-id column-primary">Modula Gallery ID</th>
						<th scope="col" id="title" class="manage-column column-title">Title</th>
						<th scope="col" id="count" class="manage-column column-count">Images</th>
						<th scope="col" id="status" class="manage-column column-status">Status</th>
						<th scope="col" id="date" class="manage-column column-date">Imported</th>
						<th scope="col" id="envira_id" class="manage-column column-envira_id">Envira Gallery ID</th>
					</tr>
				</thead>

				<tbody id="the-list" data-wp-lists="list:modula-gallery">
					<tr>
						<th scope="row" class="check-column"><input type="checkbox" name="modula_gallery_ids[]" value="863"></th>
						<td class="id column-id has-row-actions column-primary" data-colname="Modula Gallery ID">863<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button></td>
						<td class="title column-title" data-colname="Title"><a href="" target="_blank">Modula 1</a></td>
						<td class="count column-count" data-colname="Images">6</td>
						<td class="status column-status" data-colname="Status"><span style="color:gray;">Pending</span></td>
						<td class="date column-date" data-colname="Imported"></td>
						<td class="envira_id column-envira_id" data-colname="Envira Gallery ID"></td>
					</tr>
					<tr>
						<th scope="row" class="check-column"><input type="checkbox" name="modula_gallery_ids[]" value="800"></th>
						<td class="id column-id has-row-actions column-primary" data-colname="Modula Gallery ID">800<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button></td>
						<td class="title column-title" data-colname="Title"><a href="" target="_blank">Test Modula Gallery 10</a></td>
						<td class="count column-count" data-colname="Images">5</td>
						<td class="status column-status" data-colname="Status"><span style="color:gray;">Pending</span></td>
						<td class="date column-date" data-colname="Imported"></td>
						<td class="envira_id column-envira_id" data-colname="Envira Gallery ID"></td>
					</tr>
					<tr>
						<th scope="row" class="check-column"><input type="checkbox" name="modula_gallery_ids[]" value="799"></th>
						<td class="id column-id has-row-actions column-primary" data-colname="Modula Gallery ID">799<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button></td>
						<td class="title column-title" data-colname="Title"><a href="" target="_blank">Test Modula Gallery 9</a></td>
						<td class="count column-count" data-colname="Images">5</td>
						<td class="status column-status" data-colname="Status"><span style="color:gray;">Pending</span></td>
						<td class="date column-date" data-colname="Imported"></td>
						<td class="envira_id column-envira_id" data-colname="Envira Gallery ID"></td>
					</tr>
					<tr>
						<th scope="row" class="check-column"><input type="checkbox" name="modula_gallery_ids[]" value="798"></th>
						<td class="id column-id has-row-actions column-primary" data-colname="Modula Gallery ID">798<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button></td>
						<td class="title column-title" data-colname="Title"><a href="" target="_blank">Test Modula Gallery 8</a></td>
						<td class="count column-count" data-colname="Images">5</td>
						<td class="status column-status" data-colname="Status"><span style="color:gray;">Pending</span></td>
						<td class="date column-date" data-colname="Imported"></td>
						<td class="envira_id column-envira_id" data-colname="Envira Gallery ID"></td>
					</tr>
					<tr>
						<th scope="row" class="check-column"><input type="checkbox" name="modula_gallery_ids[]" value="797"></th>
						<td class="id column-id has-row-actions column-primary" data-colname="Modula Gallery ID">797<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button></td>
						<td class="title column-title" data-colname="Title"><a href="" target="_blank">Test Modula Gallery 7</a></td>
						<td class="count column-count" data-colname="Images">5</td>
						<td class="status column-status" data-colname="Status"><span style="color:gray;">Pending</span></td>
						<td class="date column-date" data-colname="Imported"></td>
						<td class="envira_id column-envira_id" data-colname="Envira Gallery ID"></td>
					</tr>
					<tr>
						<th scope="row" class="check-column"><input type="checkbox" name="modula_gallery_ids[]" value="796"></th>
						<td class="id column-id has-row-actions column-primary" data-colname="Modula Gallery ID">796<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button></td>
						<td class="title column-title" data-colname="Title"><a href="" target="_blank">Test Modula Gallery 6</a></td>
						<td class="count column-count" data-colname="Images">5</td>
						<td class="status column-status" data-colname="Status"><span style="color:gray;">Pending</span></td>
						<td class="date column-date" data-colname="Imported"></td>
						<td class="envira_id column-envira_id" data-colname="Envira Gallery ID"></td>
					</tr>
					<tr>
						<th scope="row" class="check-column"><input type="checkbox" name="modula_gallery_ids[]" value="795"></th>
						<td class="id column-id has-row-actions column-primary" data-colname="Modula Gallery ID">795<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button></td>
						<td class="title column-title" data-colname="Title"><a href="" target="_blank">Test Modula Gallery 5</a></td>
						<td class="count column-count" data-colname="Images">5</td>
						<td class="status column-status" data-colname="Status"><span style="color:gray;">Pending</span></td>
						<td class="date column-date" data-colname="Imported"></td>
						<td class="envira_id column-envira_id" data-colname="Envira Gallery ID"></td>
					</tr>
					<tr>
						<th scope="row" class="check-column"><input type="checkbox" name="modula_gallery_ids[]" value="794"></th>
						<td class="id column-id has-row-actions column-primary" data-colname="Modula Gallery ID">794<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button></td>
						<td class="title column-title" data-colname="Title"><a href="" target="_blank">Test Modula Gallery 4</a></td>
						<td class="count column-count" data-colname="Images">5</td>
						<td class="status column-status" data-colname="Status"><span style="color:gray;">Pending</span></td>
						<td class="date column-date" data-colname="Imported"></td>
						<td class="envira_id column-envira_id" data-colname="Envira Gallery ID"></td>
					</tr>
					<tr>
						<th scope="row" class="check-column"><input type="checkbox" name="modula_gallery_ids[]" value="793"></th>
						<td class="id column-id has-row-actions column-primary" data-colname="Modula Gallery ID">793<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button></td>
						<td class="title column-title" data-colname="Title"><a href="" target="_blank">Test Modula Gallery 3</a></td>
						<td class="count column-count" data-colname="Images">5</td>
						<td class="status column-status" data-colname="Status"><span style="color:gray;">Pending</span></td>
						<td class="date column-date" data-colname="Imported"></td>
						<td class="envira_id column-envira_id" data-colname="Envira Gallery ID"></td>
					</tr>
					<tr>
						<th scope="row" class="check-column"><input type="checkbox" name="modula_gallery_ids[]" value="791"></th>
						<td class="id column-id has-row-actions column-primary" data-colname="Modula Gallery ID">791<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button></td>
						<td class="title column-title" data-colname="Title"><a href="" target="_blank">Test Modula Gallery 2</a></td>
						<td class="count column-count" data-colname="Images">5</td>
						<td class="status column-status" data-colname="Status"><span style="color:gray;">Pending</span></td>
						<td class="date column-date" data-colname="Imported"></td>
						<td class="envira_id column-envira_id" data-colname="Envira Gallery ID"></td>
					</tr>
					<tr>
						<th scope="row" class="check-column"><input type="checkbox" name="modula_gallery_ids[]" value="790"></th>
						<td class="id column-id has-row-actions column-primary" data-colname="Modula Gallery ID">790<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button></td>
						<td class="title column-title" data-colname="Title"><a href="" target="_blank">Test Modula Gallery 1</a></td>
						<td class="count column-count" data-colname="Images">5</td>
						<td class="status column-status" data-colname="Status"><span style="color:gray;">Pending</span></td>
						<td class="date column-date" data-colname="Imported"></td>
						<td class="envira_id column-envira_id" data-colname="Envira Gallery ID"></td>
					</tr>
				</tbody>
				<tfoot>
					<tr>
						<td class="manage-column column-cb check-column"><input id="cb-select-all-2" type="checkbox">
							<label for="cb-select-all-2"><span class="screen-reader-text">Select All</span></label>
						</td>
						<th scope="col" class="manage-column column-id column-primary">Modula Gallery ID</th>
						<th scope="col" class="manage-column column-title">Title</th>
						<th scope="col" class="manage-column column-count">Images</th>
						<th scope="col" class="manage-column column-status">Status</th>
						<th scope="col" class="manage-column column-date">Imported</th>
						<th scope="col" class="manage-column column-envira_id">Envira Gallery ID</th>
					</tr>
				</tfoot>
			</table>
		</div>
	</div>
</div>
