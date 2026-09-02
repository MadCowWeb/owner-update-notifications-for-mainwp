<?php
/**
 * Asset enqueue on the MainWP Manage Sites screen.
 *
 * @package OwnerUpdateNotificationsForMainWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue admin CSS + JS on MainWP's Manage Sites screen only.
 *
 * @param string $hook_suffix Current admin page hook suffix.
 * @return void
 */
function mcw_ounm_enqueue_admin_assets( $hook_suffix ) {
	// MainWP registers Manage Sites as a submenu under mainwp_tab. The hook
	// suffix contains "managesites" in current and legacy MainWP builds; match
	// loosely so this survives menu renames.
	if ( false === strpos( (string) $hook_suffix, 'managesites' ) ) {
		return;
	}

	$css_rel = 'assets/css/admin.css';
	$js_rel  = 'assets/js/admin.js';

	$css_ver = file_exists( MCW_OUNM_DIR . $css_rel )
		? (string) filemtime( MCW_OUNM_DIR . $css_rel )
		: MCW_OUNM_VERSION;
	$js_ver  = file_exists( MCW_OUNM_DIR . $js_rel )
		? (string) filemtime( MCW_OUNM_DIR . $js_rel )
		: MCW_OUNM_VERSION;

	wp_enqueue_style( 'mcw-ounm-admin', MCW_OUNM_URL . $css_rel, array(), $css_ver );
	wp_enqueue_script( 'mcw-ounm-admin', MCW_OUNM_URL . $js_rel, array(), $js_ver, true );

	wp_localize_script(
		'mcw-ounm-admin',
		'mcwNotify',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'i18n'    => array(
				'confirm'       => __( 'Send the update notification email to this site owner now?', 'owner-update-notifications-for-mainwp' ),
				'sending'       => __( 'Sending…', 'owner-update-notifications-for-mainwp' ),
				'network'       => __( 'Network error. Please try again.', 'owner-update-notifications-for-mainwp' ),
				'dismiss'       => __( 'Dismiss this notice.', 'owner-update-notifications-for-mainwp' ),
				'batchBtn'      => __( 'Send to Selected Owners', 'owner-update-notifications-for-mainwp' ),
				/* translators: %d: number of selected rows. */
				'batchCount'    => __( '%d selected', 'owner-update-notifications-for-mainwp' ),
				/* translators: 1: current index, 2: total count. */
				'batchProgress' => __( 'Sending %1$d of %2$d…', 'owner-update-notifications-for-mainwp' ),
				/* translators: %d: number of sites. */
				'batchConfirm'  => __( 'Send the update notification email to %d selected site owner(s)? Sites with no owner email set will be skipped.', 'owner-update-notifications-for-mainwp' ),
				'batchNone'     => __( 'No sites are selected.', 'owner-update-notifications-for-mainwp' ),
				/* translators: 1: sent count, 2: skipped count, 3: failed count. */
				'batchSummary'  => __( 'Batch complete: %1$d sent • %2$d skipped (no owner email) • %3$d failed.', 'owner-update-notifications-for-mainwp' ),
			),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'mcw_ounm_enqueue_admin_assets' );
