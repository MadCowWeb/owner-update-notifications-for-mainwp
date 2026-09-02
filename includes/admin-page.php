<?php
/**
 * Settings page renderer.
 *
 * @package OwnerUpdateNotificationsForMainWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the "Owner Update Notifications" settings page.
 *
 * @return void
 */
function mcw_ounm_render_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$template   = mcw_ounm_get_template();
	$recipients = mcw_ounm_get_recipients();
	$sites      = mcw_ounm_get_all_sites();
	$updated    = isset( $_GET['updated'] ) && '1' === $_GET['updated']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Owner Update Notifications', 'owner-update-notifications-for-mainwp' ); ?></h1>

	<?php if ( mcw_ounm_mainwp_active() ) : ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=managesites' ) ); ?>" class="page-title-action">
			<?php esc_html_e( '&larr; Back to Manage Sites', 'owner-update-notifications-for-mainwp' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=mainwp_tab' ) ); ?>" class="page-title-action">
			<?php esc_html_e( 'MainWP Dashboard', 'owner-update-notifications-for-mainwp' ); ?>
		</a>
	<?php endif; ?>

	<hr class="wp-header-end" />

	<?php if ( $updated ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Settings saved.', 'owner-update-notifications-for-mainwp' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( ! mcw_ounm_mainwp_active() ) : ?>
		<div class="notice notice-warning">
			<p><?php esc_html_e( 'The MainWP Dashboard plugin is not active, so no sites can be listed. The message template can still be edited below.', 'owner-update-notifications-for-mainwp' ); ?></p>
		</div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="mcw_ounm_save" />
		<?php wp_nonce_field( 'mcw_ounm_save', 'mcw_ounm_nonce' ); ?>

		<h2><?php esc_html_e( 'Message Template', 'owner-update-notifications-for-mainwp' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Available placeholders:', 'owner-update-notifications-for-mainwp' ); ?>
			<code>{first_name}</code> <code>{site_name}</code> <code>{site_url}</code>
		</p>
		<p class="description">
			<?php esc_html_e( 'Basic HTML is allowed in the body. Add a link like this:', 'owner-update-notifications-for-mainwp' ); ?>
			<code>&lt;a href="https://example.com"&gt;click here&lt;/a&gt;</code>.
			<?php esc_html_e( 'A plain URL on its own line becomes a clickable link automatically.', 'owner-update-notifications-for-mainwp' ); ?>
		</p>
		<p class="description">
			<?php esc_html_e( 'Add a logo or image with a publicly hosted URL:', 'owner-update-notifications-for-mainwp' ); ?>
			<code>&lt;img src="https://yourwebsite.com/logo.png" alt="Site Logo" width="200" /&gt;</code>.
			<?php esc_html_e( 'Tip: upload the image to Media, copy its file URL, and set a width so it scales well in email.', 'owner-update-notifications-for-mainwp' ); ?>
		</p>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="mcw_from_email"><?php esc_html_e( 'From Email Address', 'owner-update-notifications-for-mainwp' ); ?> <span class="description">(<?php esc_html_e( 'required', 'owner-update-notifications-for-mainwp' ); ?>)</span></label>
				</th>
				<td>
					<input name="mcw_from_email" id="mcw_from_email" type="email" class="regular-text mcw-ounm-input" required value="<?php echo esc_attr( $template['from_email'] ); ?>" />
					<p class="description"><?php esc_html_e( 'Notifications are sent from this address. Use an address on a domain you control for best deliverability.', 'owner-update-notifications-for-mainwp' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="mcw_subject"><?php esc_html_e( 'Subject', 'owner-update-notifications-for-mainwp' ); ?></label>
				</th>
				<td>
					<input name="mcw_subject" id="mcw_subject" type="text" class="large-text" value="<?php echo esc_attr( $template['subject'] ); ?>" />
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="mcw_body"><?php esc_html_e( 'Body', 'owner-update-notifications-for-mainwp' ); ?></label>
				</th>
				<td>
					<textarea name="mcw_body" id="mcw_body" rows="18" class="large-text code"><?php echo esc_textarea( $template['body'] ); ?></textarea>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Site Owners', 'owner-update-notifications-for-mainwp' ); ?></h2>
		<?php if ( empty( $sites ) ) : ?>
			<p><?php esc_html_e( 'No child sites found.', 'owner-update-notifications-for-mainwp' ); ?></p>
		<?php else : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Site', 'owner-update-notifications-for-mainwp' ); ?></th>
						<th><?php esc_html_e( 'Owner First Name', 'owner-update-notifications-for-mainwp' ); ?></th>
						<th>
							<?php esc_html_e( 'Owner Email(s)', 'owner-update-notifications-for-mainwp' ); ?>
							<span class="description" style="font-weight:400;">&mdash; <?php esc_html_e( 'separate multiple with commas', 'owner-update-notifications-for-mainwp' ); ?></span>
						</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $sites as $site ) : ?>
						<?php
						$sid   = (int) $site['id'];
						$rec   = isset( $recipients[ $sid ] ) ? $recipients[ $sid ] : array();
						$first = isset( $rec['first_name'] ) ? $rec['first_name'] : '';
						$mail  = isset( $rec['email'] ) ? $rec['email'] : '';
						?>
						<tr>
							<td>
								<strong><?php echo esc_html( $site['name'] ); ?></strong><br />
								<a href="<?php echo esc_url( $site['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $site['url'] ); ?></a>
							</td>
							<td>
								<input type="text" class="regular-text" name="mcw_first[<?php echo esc_attr( $sid ); ?>]" value="<?php echo esc_attr( $first ); ?>" />
							</td>
							<td>
								<input type="text" class="regular-text mcw-ounm-input" name="mcw_email[<?php echo esc_attr( $sid ); ?>]" value="<?php echo esc_attr( $mail ); ?>" />
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<?php submit_button( esc_html__( 'Save Changes', 'owner-update-notifications-for-mainwp' ) ); ?>
	</form>
</div>
	<?php
}
