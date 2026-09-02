<?php
/**
 * Option accessors, defaults, and one-time legacy migration.
 *
 * @package OwnerUpdateNotificationsForMainWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the MainWP Dashboard plugin is active.
 *
 * @return bool
 */
function mcw_ounm_mainwp_active() {
	return class_exists( '\MainWP\Dashboard\MainWP_DB' );
}

/**
 * Default message template.
 *
 * @return array{from_email:string,subject:string,body:string}
 */
function mcw_ounm_default_template() {
	return array(
		'from_email' => get_option( 'admin_email' ),
		'subject'    => __( 'Your website {site_name} has been updated', 'owner-update-notifications-for-mainwp' ),
		'body'       => __( "Hi {first_name},\n\nJust a quick note to let you know we've completed maintenance and updates on your website ({site_url}). Everything is running smoothly.\n\nIf you notice anything unusual, reply to this email and we'll take a look.\n\nBest regards,", 'owner-update-notifications-for-mainwp' ),
	);
}

/**
 * Allowed HTML tags for the email body template. Keeps the plain textarea simple
 * while permitting links, images, and basic formatting.
 *
 * @return array<string,array<string,bool>>
 */
function mcw_ounm_allowed_html() {
	return array(
		'a'      => array(
			'href'   => true,
			'title'  => true,
			'target' => true,
			'rel'    => true,
		),
		'img'    => array(
			'src'    => true,
			'alt'    => true,
			'width'  => true,
			'height' => true,
			'style'  => true,
		),
		'div'    => array( 'style' => true ),
		'p'      => array( 'style' => true ),
		'strong' => array(),
		'b'      => array(),
		'br'     => array(),
	);
}

/**
 * Get the saved template merged with defaults.
 *
 * @return array{from_email:string,subject:string,body:string}
 */
function mcw_ounm_get_template() {
	$saved = get_option( MCW_OUNM_OPT_TEMPLATE, array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	return wp_parse_args( $saved, mcw_ounm_default_template() );
}

/**
 * Get the saved recipients map: [ site_id => [ email, first_name ] ].
 *
 * @return array<int,array{email:string,first_name:string}>
 */
function mcw_ounm_get_recipients() {
	$saved = get_option( MCW_OUNM_OPT_RECIPIENTS, array() );
	return is_array( $saved ) ? $saved : array();
}

/**
 * Get "last sent" timestamps map: [ site_id => unix_ts ].
 *
 * @return array<int,int>
 */
function mcw_ounm_get_last_sent() {
	$saved = get_option( MCW_OUNM_OPT_LAST_SENT, array() );
	return is_array( $saved ) ? $saved : array();
}

/**
 * Record the current time as the "last sent" moment for a site.
 *
 * @param int $site_id MainWP child site ID.
 * @return void
 */
function mcw_ounm_record_send( $site_id ) {
	$site_id = (int) $site_id;
	if ( $site_id <= 0 ) {
		return;
	}
	$all             = mcw_ounm_get_last_sent();
	$all[ $site_id ] = time();
	update_option( MCW_OUNM_OPT_LAST_SENT, $all, false );
}

/**
 * Window (in seconds) during which a recently-sent button stays highlighted.
 *
 * @return int
 */
function mcw_ounm_recent_window() {
	/**
	 * Filter the "recently sent" highlight window in seconds.
	 *
	 * @param int $seconds Default 7 days.
	 */
	return (int) apply_filters( 'mcw_ounm_recent_window', 7 * DAY_IN_SECONDS );
}

/**
 * Sanitize a comma-separated list of email addresses.
 *
 * @param string $raw Raw input.
 * @return string Comma-separated list of valid emails, or empty string.
 */
function mcw_ounm_sanitize_emails( $raw ) {
	$emails = array();
	foreach ( explode( ',', (string) $raw ) as $email ) {
		$clean = sanitize_email( trim( $email ) );
		if ( '' !== $clean && is_email( $clean ) ) {
			$emails[] = $clean;
		}
	}
	return implode( ', ', array_unique( $emails ) );
}

/**
 * One-time migration from the legacy `mcw-mainwp-notify` plugin's option names.
 * Runs once, flags itself as complete. Safe to leave in place forever.
 *
 * @return void
 */
function mcw_ounm_maybe_migrate_legacy_options() {
	if ( get_option( 'mcw_ounm_migrated_v1', 0 ) ) {
		return;
	}

	$legacy_recipients = get_option( 'mcw_mainwp_notify_recipients', null );
	$legacy_template   = get_option( 'mcw_mainwp_notify_template', null );
	$legacy_last_sent  = get_option( 'mcw_mainwp_notify_last_sent', null );

	// Only migrate into slots that don't already hold user data.
	if ( is_array( $legacy_recipients ) && ! get_option( MCW_OUNM_OPT_RECIPIENTS ) ) {
		update_option( MCW_OUNM_OPT_RECIPIENTS, $legacy_recipients );
	}
	if ( is_array( $legacy_template ) && ! get_option( MCW_OUNM_OPT_TEMPLATE ) ) {
		update_option( MCW_OUNM_OPT_TEMPLATE, $legacy_template );
	}
	if ( is_array( $legacy_last_sent ) && ! get_option( MCW_OUNM_OPT_LAST_SENT ) ) {
		update_option( MCW_OUNM_OPT_LAST_SENT, $legacy_last_sent );
	}

	update_option( 'mcw_ounm_migrated_v1', 1, false );
}
add_action( 'admin_init', 'mcw_ounm_maybe_migrate_legacy_options' );
