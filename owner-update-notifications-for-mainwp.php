<?php
/**
 * Plugin Name:       Owner Update Notifications for MainWP
 * Plugin URI:        https://madcowweb.com/
 * Description:       Adds a "Notify Owner" button (and a batch "Send to Selected Owners" toolbar) to the MainWP Manage Sites table. Stores each site's owner name + email, lets you customize the message template, and sends via wp_mail().
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Mad Cow Web
 * Author URI:        https://madcowweb.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       owner-update-notifications-for-mainwp
 * Domain Path:       /languages
 *
 * @package OwnerUpdateNotificationsForMainWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ── Constants ───────────────────────────────────────────────────────── */
define( 'MCW_OUNM_VERSION', '1.0.0' );
define( 'MCW_OUNM_FILE', __FILE__ );
define( 'MCW_OUNM_DIR', plugin_dir_path( __FILE__ ) );
define( 'MCW_OUNM_URL', plugin_dir_url( __FILE__ ) );
define( 'MCW_OUNM_SLUG', 'owner-update-notifications' );
define( 'MCW_OUNM_OPT_RECIPIENTS', 'mcw_ounm_recipients' );
define( 'MCW_OUNM_OPT_TEMPLATE', 'mcw_ounm_template' );
define( 'MCW_OUNM_OPT_LAST_SENT', 'mcw_ounm_last_sent' );

/* ── PHP version guard ───────────────────────────────────────────────── */
/**
 * Admin notice shown when the site's PHP version is below the plugin's requirement.
 *
 * Registered conditionally below so it only appears when needed.
 *
 * @return void
 */
function mcw_ounm_php_version_notice() {
	echo '<div class="notice notice-error"><p>';
	echo esc_html(
		sprintf(
			/* translators: %s: minimum required PHP version. */
			__( 'Owner Update Notifications for MainWP requires PHP %s or higher and has been disabled.', 'owner-update-notifications-for-mainwp' ),
			'8.0'
		)
	);
	echo '</p></div>';
}

if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
	add_action( 'admin_notices', 'mcw_ounm_php_version_notice' );
	return;
}

/* ── Includes ────────────────────────────────────────────────────────── */
require_once MCW_OUNM_DIR . 'includes/options.php';
require_once MCW_OUNM_DIR . 'includes/send.php';
require_once MCW_OUNM_DIR . 'includes/render.php';
require_once MCW_OUNM_DIR . 'includes/enqueue.php';
require_once MCW_OUNM_DIR . 'includes/admin-menu.php';
require_once MCW_OUNM_DIR . 'includes/admin-handlers.php';
require_once MCW_OUNM_DIR . 'includes/ajax.php';
require_once MCW_OUNM_DIR . 'includes/admin-page.php';

// Translations are loaded automatically by WordPress from the wp.org language pack
// (WP 4.6+) and just-in-time (WP 6.7+). No load_plugin_textdomain() call needed.
