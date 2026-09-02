=== Owner Update Notifications for MainWP ===
Contributors: madcowweb
Tags: mainwp, notifications, email, client, updates
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Send owner notification emails to your MainWP child site owners with one click. Batch send, per-site email storage, customizable template.

== Description ==

Owner Update Notifications for MainWP adds an "Email owner" button to every row in your MainWP Manage Sites table. After running updates or maintenance, click one button and the site's owner receives a templated email letting them know their site is up to date. Need to notify a dozen owners at once? Check the rows and hit **Send to Selected Owners** — a sticky toolbar shows live progress and a summary at the end.

**Key features**

* **One-click send** — an "Email owner" button appears in every row of MainWP's Manage Sites table.
* **Batch send** — check multiple rows and send to all owners at once, with per-row progress and a summary at the end.
* **Persistent "recently sent" state** — green button shows how long ago each owner was last notified.
* **Per-site owner name + email** — stored server-side; edit them all from one settings screen.
* **Customizable template** — subject, body, and from-address. Placeholders: `{first_name}`, `{site_name}`, `{site_url}`.
* **HTML body support** — basic HTML allowed for images, links, and formatting.
* **No page reloads** — everything is AJAX; scroll position, filters, and table state are preserved.
* **Sticky batch toolbar** — stays visible under the MainWP header while you scroll long site lists.
* **Developer-friendly** — clean hooks and filters for extension. Nothing "phones home."

**Requirements**

* The free [MainWP Dashboard](https://wordpress.org/plugins/mainwp/) plugin, for the Manage Sites integration.
* PHP 8.0 or higher.
* WordPress 6.0 or higher.

== Installation ==

1. Upload the plugin folder to `wp-content/plugins/` or install through the WordPress admin.
2. Activate the plugin through the "Plugins" screen.
3. Ensure the free MainWP Dashboard plugin is also installed and active.
4. Go to **MainWP &rarr; Update Notifications** to configure the message template and enter each site's owner name + email.
5. Visit **MainWP &rarr; Manage Sites** and use the "Email owner" (per row) or "Send to Selected Owners" (batch) buttons.

== Frequently Asked Questions ==

= Do I need the MainWP Dashboard plugin installed? =

Yes. The row buttons and batch send integrate with MainWP's Manage Sites table. Without MainWP active, only the settings page is accessible for editing the message template.

= Does it work with any SMTP plugin? =

Yes. All emails go through WordPress's standard `wp_mail()` function, so any SMTP plugin, transactional mail service (Mailgun, SendGrid, Postmark, SES, etc.), or default PHP mail configuration works.

= What happens if a site has no owner email set? =

The row shows a "Set email" link instead of the button. In batch mode, such rows are skipped and counted in the summary at the end.

= Can I customize the message per site? =

The current version uses one global template with placeholders for the first name, site name, and site URL. Per-site templates are on the roadmap.

= Does this plugin send any data to a third party? =

No. All processing happens on your own site. Emails go through your own `wp_mail()` pipeline. No telemetry, no external calls.

= Does it work on multisite? =

The plugin activates per-site on multisite installs. Options are per-site (single-blog `wp_options`), matching how MainWP itself is typically installed.

== Screenshots ==

1. The "Email owner" and "Sent X ago" buttons in the MainWP Manage Sites table.
2. Batch send toolbar with live selection counter, pinned at the top while scrolling.
3. Settings page for editing the message template and per-site owner details.

== Changelog ==

= 1.0.0 =
* Initial public release.

== Upgrade Notice ==

= 1.0.0 =
Initial public release.
