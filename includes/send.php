<?php
/**
 * Send logic — resolves site info, builds the message, and calls wp_mail().
 *
 * @package OwnerUpdateNotificationsForMainWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Replace template placeholders.
 *
 * When $as_html is true, `{site_url}` is substituted as a pre-built `<a>` tag,
 * so downstream `make_clickable()` cannot grab trailing punctuation into the href.
 *
 * @param string $text       Template text.
 * @param string $first_name Owner first name.
 * @param string $site_name  Site name.
 * @param string $site_url   Site URL.
 * @param bool   $as_html    Whether the output will be rendered as HTML.
 * @return string
 */
function mcw_ounm_apply_placeholders( $text, $first_name, $site_name, $site_url, $as_html = false ) {
	$site_url_clean = untrailingslashit( trim( (string) $site_url ) );

	if ( $as_html ) {
		$site_url_token = '' !== $site_url_clean
			? '<a href="' . esc_url( $site_url_clean ) . '">' . esc_html( $site_url_clean ) . '</a>'
			: '';

		return strtr(
			$text,
			array(
				'{first_name}' => esc_html( $first_name ),
				'{site_name}'  => esc_html( $site_name ),
				'{site_url}'   => $site_url_token,
			)
		);
	}

	return strtr(
		$text,
		array(
			'{first_name}' => $first_name,
			'{site_name}'  => $site_name,
			'{site_url}'   => $site_url_clean,
		)
	);
}

/**
 * Retrieve all child sites from the MainWP dashboard database.
 *
 * @return array<int,array{id:int,name:string,url:string}>
 */
function mcw_ounm_get_all_sites() {
	$sites = array();

	if ( ! mcw_ounm_mainwp_active() ) {
		return $sites;
	}

	$db       = \MainWP\Dashboard\MainWP_DB::instance();
	$websites = $db->query( $db->get_sql_websites_for_current_user( false, null, 'wp.name' ) );

	while ( $websites && ( $website = \MainWP\Dashboard\MainWP_DB::fetch_object( $websites ) ) ) {
		$sites[] = array(
			'id'   => (int) $website->id,
			'name' => (string) $website->name,
			'url'  => (string) $website->url,
		);
	}

	return $sites;
}

/**
 * Perform the actual send for a given site.
 *
 * Assumes capability + nonce were verified by the caller.
 *
 * @param int $id MainWP child site ID.
 * @return string One of: 'sent', 'failed', 'no_email'.
 */
function mcw_ounm_do_send( $id ) {
	$id = (int) $id;
	if ( $id <= 0 ) {
		return 'failed';
	}

	$recipients = mcw_ounm_get_recipients();
	$rec        = isset( $recipients[ $id ] ) ? $recipients[ $id ] : array();
	$email      = isset( $rec['email'] ) ? mcw_ounm_sanitize_emails( $rec['email'] ) : '';

	if ( '' === $email ) {
		return 'no_email';
	}

	$site_name = '';
	$site_url  = '';
	foreach ( mcw_ounm_get_all_sites() as $site ) {
		if ( (int) $site['id'] === $id ) {
			$site_name = $site['name'];
			$site_url  = $site['url'];
			break;
		}
	}

	$template   = mcw_ounm_get_template();
	$first_name = ( isset( $rec['first_name'] ) && '' !== $rec['first_name'] )
		? (string) $rec['first_name']
		: __( 'there', 'owner-update-notifications-for-mainwp' );

	/**
	 * Filter the resolved template just before send.
	 *
	 * @param array  $template Keys: from_email, subject, body.
	 * @param int    $id       Site ID.
	 * @param string $email    Comma-separated recipient email(s).
	 */
	$template = apply_filters( 'mcw_ounm_template_before_send', $template, $id, $email );

	$subject = mcw_ounm_apply_placeholders( $template['subject'], $first_name, $site_name, $site_url, false );
	$body    = mcw_ounm_apply_placeholders( $template['body'], $first_name, $site_name, $site_url, true );

	// {site_url} was substituted as a full <a> tag above, so make_clickable() only
	// linkifies any additional bare URLs the operator typed into the template.
	$html_body = wpautop( make_clickable( $body ) );

	$from_email = ( isset( $template['from_email'] ) && is_email( $template['from_email'] ) )
		? $template['from_email']
		: get_option( 'admin_email' );

	/**
	 * Filter the outbound wp_mail() headers.
	 *
	 * @param array  $headers Default Content-Type, From, Reply-To.
	 * @param int    $id      Site ID.
	 * @param string $email   Recipient email(s).
	 */
	$headers = apply_filters(
		'mcw_ounm_mail_headers',
		array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . $from_email,
			'Reply-To: ' . $from_email,
		),
		$id,
		$email
	);

	/**
	 * Fires just before wp_mail() is invoked.
	 *
	 * @param int    $id
	 * @param string $email
	 * @param string $subject
	 */
	do_action( 'mcw_ounm_before_send', $id, $email, $subject );

	$sent = wp_mail( $email, $subject, $html_body, $headers );

	/**
	 * Fires after wp_mail() returns.
	 *
	 * @param int    $id
	 * @param string $email
	 * @param bool   $success
	 */
	do_action( 'mcw_ounm_after_send', $id, $email, (bool) $sent );

	if ( $sent ) {
		mcw_ounm_record_send( $id );
		return 'sent';
	}
	return 'failed';
}
