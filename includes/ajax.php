<?php
/**
 * AJAX endpoint for the row-level "Email owner" button.
 *
 * Returns fresh button HTML (in its new "sent" state) plus a message so the
 * JS can update the row in-place without a page reload.
 *
 * @package OwnerUpdateNotificationsForMainWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX handler: send a notification for a single site.
 *
 * @return void
 */
function mcw_ounm_ajax_send() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error(
			array( 'message' => __( 'You do not have permission to do this.', 'owner-update-notifications-for-mainwp' ) ),
			403
		);
	}

	$id    = isset( $_POST['site_id'] ) ? (int) $_POST['site_id'] : 0;
	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'mcw_ounm_send_' . $id ) ) {
		wp_send_json_error(
			array( 'message' => __( 'Security check failed. Please reload the page and try again.', 'owner-update-notifications-for-mainwp' ) ),
			403
		);
	}

	$result = mcw_ounm_do_send( $id );

	$recipients  = mcw_ounm_get_recipients();
	$email       = isset( $recipients[ $id ]['email'] ) ? (string) $recipients[ $id ]['email'] : '';
	$button_html = mcw_ounm_render_button( $id, $email );

	if ( 'sent' === $result ) {
		wp_send_json_success(
			array(
				'button_html' => $button_html,
				'message'     => __( 'Notification email sent to the site owner.', 'owner-update-notifications-for-mainwp' ),
			)
		);
	}

	$msg = ( 'no_email' === $result )
		? __( 'No owner email is set for this site.', 'owner-update-notifications-for-mainwp' )
		: __( 'The notification email could not be sent. Check the owner email address and your site\'s mail configuration.', 'owner-update-notifications-for-mainwp' );

	wp_send_json_error(
		array(
			'button_html' => $button_html,
			'message'     => $msg,
		)
	);
}
add_action( 'wp_ajax_mcw_ounm_send', 'mcw_ounm_ajax_send' );
