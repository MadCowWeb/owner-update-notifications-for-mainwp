<?php
/**
 * Rendering: adds the "Notify Owner" column to MainWP's Manage Sites table
 * and builds each row's button HTML.
 *
 * @package OwnerUpdateNotificationsForMainWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the custom column header on the Manage Sites table.
 *
 * @param array $cols Existing columns.
 * @return array
 */
function mcw_ounm_sitestable_columns( $cols ) {
	$cols['mcw_ounm_notify'] = esc_html__( 'Notify Owner', 'owner-update-notifications-for-mainwp' );
	return $cols;
}
add_filter( 'mainwp_sitestable_getcolumns', 'mcw_ounm_sitestable_columns', 10 );

/**
 * Populate the custom column for each site row.
 *
 * @param array $item Row data (id, name, url, ...).
 * @return array
 */
function mcw_ounm_sitestable_item( $item ) {
	$id         = isset( $item['id'] ) ? (int) $item['id'] : 0;
	$recipients = mcw_ounm_get_recipients();
	$rec        = isset( $recipients[ $id ] ) ? $recipients[ $id ] : array();
	$email      = isset( $rec['email'] ) ? (string) $rec['email'] : '';

	$item['mcw_ounm_notify'] = mcw_ounm_render_button( $id, $email );

	return $item;
}
add_filter( 'mainwp_sitestable_item', 'mcw_ounm_sitestable_item', 10 );

/**
 * Build the HTML for a site row's notify button.
 *
 * Shared by the initial column render and the AJAX response.
 *
 * @param int    $id    MainWP child site ID.
 * @param string $email Owner email string (may be empty or comma-separated).
 * @return string
 */
function mcw_ounm_render_button( $id, $email ) {
	$id = (int) $id;

	if ( $id <= 0 || '' === trim( (string) $email ) ) {
		$settings_url = admin_url( 'admin.php?page=' . MCW_OUNM_SLUG );
		return '<a href="' . esc_url( $settings_url ) . '" title="'
			. esc_attr__( 'No owner email set for this site — click to add one', 'owner-update-notifications-for-mainwp' ) . '">'
			. esc_html__( 'Set email', 'owner-update-notifications-for-mainwp' ) . '</a>';
	}

	$nonce    = wp_create_nonce( 'mcw_ounm_send_' . $id );
	$send_url = wp_nonce_url(
		add_query_arg(
			array(
				'action'  => 'mcw_ounm_send',
				'site_id' => $id,
			),
			admin_url( 'admin-post.php' )
		),
		'mcw_ounm_send_' . $id,
		'mcw_nonce'
	);

	$data_attrs = ' data-site-id="' . esc_attr( (string) $id ) . '" data-nonce="' . esc_attr( $nonce ) . '"';

	$last_sent_map = mcw_ounm_get_last_sent();
	$last_ts       = isset( $last_sent_map[ $id ] ) ? (int) $last_sent_map[ $id ] : 0;
	$is_recent     = $last_ts > 0 && ( time() - $last_ts ) < mcw_ounm_recent_window();

	if ( $is_recent ) {
		$ago   = human_time_diff( $last_ts, time() );
		$stamp = wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $last_ts );
		/* translators: %s: human-readable time difference, e.g. "2 hours". */
		$btn_label = sprintf( esc_html__( 'Sent %s ago', 'owner-update-notifications-for-mainwp' ), $ago );
		/* translators: %s: formatted date/time the last notification was sent. */
		$title = sprintf( esc_attr__( 'Last notification sent: %s. Click to resend.', 'owner-update-notifications-for-mainwp' ), $stamp );

		return '<a class="button button-small mcw-notify-btn mcw-notify-sent" '
			. 'href="' . esc_url( $send_url ) . '" title="' . $title . '"' . $data_attrs . '>'
			. '<span class="dashicons dashicons-yes" aria-hidden="true"></span> '
			. $btn_label . '</a>';
	}

	return '<a class="button button-small mcw-notify-btn" href="' . esc_url( $send_url ) . '"' . $data_attrs . '>'
		. esc_html__( 'Email owner', 'owner-update-notifications-for-mainwp' ) . '</a>';
}
