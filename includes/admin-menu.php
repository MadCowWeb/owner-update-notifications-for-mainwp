<?php
/**
 * Admin menu registration + plugin action links.
 *
 * @package OwnerUpdateNotificationsForMainWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the admin page (as a MainWP submenu when available, else under Tools).
 *
 * @return void
 */
function mcw_ounm_admin_menu() {
	$cap = 'manage_options';

	if ( mcw_ounm_mainwp_active() ) {
		add_submenu_page(
			'mainwp_tab',
			esc_html__( 'Update Notifications', 'owner-update-notifications-for-mainwp' ),
			esc_html__( 'Update Notifications', 'owner-update-notifications-for-mainwp' ),
			$cap,
			MCW_OUNM_SLUG,
			'mcw_ounm_render_admin_page'
		);
	} else {
		add_management_page(
			esc_html__( 'MainWP Update Notifications', 'owner-update-notifications-for-mainwp' ),
			esc_html__( 'MainWP Notifications', 'owner-update-notifications-for-mainwp' ),
			$cap,
			MCW_OUNM_SLUG,
			'mcw_ounm_render_admin_page'
		);
	}
}
add_action( 'admin_menu', 'mcw_ounm_admin_menu', 20 );

/**
 * Add a Settings link on the Plugins screen.
 *
 * @param array $links Existing action links.
 * @return array
 */
function mcw_ounm_plugin_action_links( $links ) {
	$url  = admin_url( 'admin.php?page=' . MCW_OUNM_SLUG );
	$link = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'owner-update-notifications-for-mainwp' ) . '</a>';
	array_unshift( $links, $link );
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( MCW_OUNM_FILE ), 'mcw_ounm_plugin_action_links' );
