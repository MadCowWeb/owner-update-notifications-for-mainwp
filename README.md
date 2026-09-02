# Owner Update Notifications for MainWP

Send owner notification emails to your MainWP child site owners with one click. Includes batch send, per-row progress, a customizable template, and a MainWP-aware sticky toolbar.

Built for real agency workflows — no telemetry, no calls home, no upsells inside the plugin.

## Features

- One-click per-row **Email owner** button in MainWP's Manage Sites table
- **Send to Selected Owners** — batch send using MainWP's own checkboxes with live per-row progress
- Persistent "recently sent" state per site (green button with time-ago)
- Per-site owner name + email storage
- Customizable subject, body, and from address (placeholders: `{first_name}`, `{site_name}`, `{site_url}`)
- HTML body support with safe `wp_kses` filtering
- Everything is AJAX — no page reloads, no lost scroll or filter state
- Sticky batch toolbar that pins under the MainWP header while scrolling
- Clean uninstall (removes all options; multisite-aware)

## Requirements

- WordPress 6.0+
- PHP 8.0+
- [MainWP Dashboard](https://wordpress.org/plugins/mainwp/) (free) for the Manage Sites integration

## Installation

Clone into your `wp-content/plugins` directory:

```bash
cd wp-content/plugins
git clone git@github.com:MadCowWeb/owner-update-notifications-for-mainwp.git
```

Then activate through **Plugins** and configure at **MainWP &rarr; Update Notifications**.

## Developer Hooks

### Filters

- `mcw_ounm_recent_window` — seconds to keep the green "recently sent" state on a button. Default: `7 * DAY_IN_SECONDS`.
- `mcw_ounm_template_before_send` — modify the template right before send. Receives `array $template`, `int $site_id`, `string $email`.
- `mcw_ounm_mail_headers` — modify `wp_mail()` headers. Receives `array $headers`, `int $site_id`, `string $email`.

### Actions

- `mcw_ounm_before_send` — fires before `wp_mail()`. Receives `int $site_id`, `string $email`, `string $subject`.
- `mcw_ounm_after_send` — fires after `wp_mail()`. Receives `int $site_id`, `string $email`, `bool $success`.

## File Layout

```
owner-update-notifications-for-mainwp/
├── owner-update-notifications-for-mainwp.php   # bootstrap + constants + PHP guard
├── uninstall.php                                # option cleanup on delete
├── readme.txt                                   # wp.org format
├── README.md
├── LICENSE
├── assets/
│   ├── css/admin.css                            # Manage Sites styles
│   └── js/admin.js                              # single + batch send, sticky toolbar
├── includes/
│   ├── options.php                              # accessors, defaults, legacy migration
│   ├── send.php                                 # do_send() + placeholder handling
│   ├── render.php                               # column filter + button HTML
│   ├── enqueue.php                              # asset registration
│   ├── admin-menu.php                           # menu + settings link
│   ├── admin-handlers.php                       # save + no-JS send + notices
│   ├── ajax.php                                 # wp_ajax_ endpoint
│   └── admin-page.php                           # settings screen renderer
└── languages/
```

## License

GPL-2.0-or-later. See `LICENSE`.

## Trademarks

MainWP® is a trademark of MainWP LLC. This plugin is an independent, third-party integration and is not affiliated with, endorsed by, or sponsored by MainWP LLC.
