<?php
/**
 * Admin form handlers (save settings, no-JS send fallback, result notices).
 *
 * @package OwnerUpdateNotificationsForMainWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle the settings form submission.
 *
 * @return void
 */
function mcw_ounm_handle_save() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to do this.', 'owner-update-notifications-for-mainwp' ) );
	}
	check_admin_referer( 'mcw_ounm_save', 'mcw_ounm_nonce' );

	$from_email = isset( $_POST['mcw_from_email'] )
		? sanitize_email( wp_unslash( $_POST['mcw_from_email'] ) )
		: '';
	if ( '' === $from_email || ! is_email( $from_email ) ) {
		// Required field — fall back to the site admin address if missing/invalid.
		$from_email = get_option( 'admin_email' );
	}

	$template = array(
		'from_email' => $from_email,
		'subject'    => isset( $_POST['mcw_subject'] )
			? sanitize_text_field( wp_unslash( $_POST['mcw_subject'] ) )
			: '',
		'body'       => isset( $_POST['mcw_body'] )
			? wp_kses( wp_unslash( $_POST['mcw_body'] ), mcw_ounm_allowed_html() )
			: '',
	);
	update_option( MCW_OUNM_OPT_TEMPLATE, $template );

	$emails = ( isset( $_POST['mcw_email'] ) && is_array( $_POST['mcw_email'] ) )
		? map_deep( wp_unslash( $_POST['mcw_email'] ), 'sanitize_text_field' )
		: array();
	$firsts = ( isset( $_POST['mcw_first'] ) && is_array( $_POST['mcw_first'] ) )
		? map_deep( wp_unslash( $_POST['mcw_first'] ), 'sanitize_text_field' )
		: array();

	$recipients = array();
	foreach ( $emails as $id => $email ) {
		$id          = (int) $id;
		$clean_email = mcw_ounm_sanitize_emails( $email );
		$first_name  = isset( $firsts[ $id ] ) ? sanitize_text_field( $firsts[ $id ] ) : '';

		if ( '' === $clean_email && '' === $first_name ) {
			continue;
		}
		$recipients[ $id ] = array(
			'email'      => $clean_email,
			'first_name' => $first_name,
		);
	}
	update_option( MCW_OUNM_OPT_RECIPIENTS, $recipients );

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'    => MCW_OUNM_SLUG,
				'updated' => '1',
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}
add_action( 'admin_post_mcw_ounm_save', 'mcw_ounm_handle_save' );

/**
 * No-JS fallback: same URL the row anchor points at. Delegates to do_send()
 * and redirects back to Manage Sites with a result flag.
 *
 * @return void
 */
function mcw_ounm_handle_send_fallback() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to do this.', 'owner-update-notifications-for-mainwp' ) );
	}

	$id = isset( $_GET['site_id'] ) ? (int) $_GET['site_id'] : 0;
	check_admin_referer( 'mcw_ounm_send_' . $id, 'mcw_nonce' );

	$result = mcw_ounm_do_send( $id );

	$redirect = wp_get_referer();
	if ( ! $redirect ) {
		$redirect = admin_url( 'admin.php?page=managesites' );
	}
	$redirect = add_query_arg( 'mcw_notify', $result, $redirect );

	wp_safe_redirect( $redirect );
	exit;
}
add_action( 'admin_post_mcw_ounm_send', 'mcw_ounm_handle_send_fallback' );

/**
 * Show a result notice after a no-JS send redirect.
 *
 * @return void
 */
function mcw_ounm_admin_notices() {
	if ( ! current_user_can( 'manage_options' ) || ! isset( $_GET['mcw_notify'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	$status = sanitize_key( wp_unslash( $_GET['mcw_notify'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( 'sent' === $status ) {
		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html__( 'Notification email sent to the site owner.', 'owner-update-notifications-for-mainwp' )
		);
	} elseif ( 'no_email' === $status ) {
		printf(
			'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
			esc_html__( 'No owner email is set for this site.', 'owner-update-notifications-for-mainwp' )
		);
	} elseif ( 'failed' === $status ) {
		printf(
			'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
			esc_html__( 'The notification email could not be sent. Check the owner email address and your site\'s mail configuration.', 'owner-update-notifications-for-mainwp' )
		);
	}
}
add_action( 'admin_notices', 'mcw_ounm_admin_notices' );
