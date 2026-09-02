<?php
/**
 * Uninstall script for Owner Update Notifications for MainWP.
 * Removes all plugin options on plugin delete.
 *
 * @package OwnerUpdateNotificationsForMainWP
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$mcw_ounm_options = array(
	'mcw_ounm_recipients',
	'mcw_ounm_template',
	'mcw_ounm_last_sent',
	'mcw_ounm_migrated_v1',
);

foreach ( $mcw_ounm_options as $mcw_ounm_option ) {
	delete_option( $mcw_ounm_option );
}

// Multisite: also remove from each site.
if ( is_multisite() ) {
	$mcw_ounm_sites = get_sites( array( 'number' => 0 ) );
	foreach ( $mcw_ounm_sites as $mcw_ounm_site ) {
		switch_to_blog( (int) $mcw_ounm_site->blog_id );
		foreach ( $mcw_ounm_options as $mcw_ounm_option ) {
			delete_option( $mcw_ounm_option );
		}
		restore_current_blog();
	}
}
