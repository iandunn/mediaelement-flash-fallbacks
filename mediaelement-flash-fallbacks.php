<?php

/*
Plugin Name: MediaElement Flash Fallbacks
Plugin URI:  https://wordpress.org/plugins/mediaelement-flash-fallbacks
Description: Flash fallbacks for MediaElement.js, to support DASH and HLS for old browsers.
Version:     0.2
Author:      Ian Dunn
Author URI:  https://iandunn.name
Text Domain: mediaelement-flash-fallbacks
*/

defined( 'WPINC' ) or die();

/**
 * Redirect requests to Core's removed Flash files to the ones bundled in this plugin.
 *
 * @param bool $preempt Whether to short-circuit default header status handling.
 *
 * @return bool
 */
function meff_redirect_files( $preempt ) {
	// Target the ME 4.x files specifically, since those are the ones that are bundled in this plugin.
	$mejs_flash_pattern = '~wp-includes/js/mediaelement/mediaelement-flash-(video|audio)(-hls|-mdash|-ogg)?.swf~i';

	if ( 0 === preg_match( $mejs_flash_pattern, $_SERVER['REQUEST_URI'] ) ) {
		return $preempt;
	}

	wp_safe_redirect( meff_get_redirect_url( $_SERVER['REQUEST_URI'] ) );
	die();
}
add_action( 'pre_handle_404', 'meff_redirect_files' );

/**
 * Build the URL that the request should be redirected to.
 *
 * SECURITY NOTE: If the URL has a fragment, that browser never sends that to the server.
 * Instead, `$_SERVER['REQUEST_URI']` will only have the part of the URL preceding the `#`,
 * and then the browser will add the fragment to the URL in the `Location` header. Some
 * malicious payloads attempt to set `flashVars` via the fragment, rather than the query.
 * In those cases, the `$query` that gets validated will not affect the fragment. Although,
 * in some cases, it will break the payload by removing necessary characters from the query.
 *
 * @param string $url
 *
 * @return string
 */
function meff_get_redirect_url( $url ) {
	$parsed_url = parse_url( $url );
	$query      = isset( $parsed_url['query'] ) ? $parsed_url['query'] : '';

	if ( apply_filters( 'meff_validate_query', false ) ) {
		$query = meff_validate_query( $query );
	} elseif ( $query ) {
		/*
		 * meff_validate_query() returns the query with a preceding '?'.
		 * It should have that if validation is turned off, too.
		 */
		$query = '?' . $query;
	}

	return plugins_url(
		'mediaelement/build/' . basename( $parsed_url['path'] ) . $query,
		__FILE__
	);
}

/**
 * Validate the query parameters for MediaElement SWF files.
 *
 * This isn't effective against targeted attacks, because a phishing payload could just contain a
 * link directly to the bundled file, instead of being redirected. It'll still help against bots,
 * though.
 *
 * SECURITY NOTE: This won't affect the URL fragment, only the query (the part before the `#`). See
 * the note in `meff_redirect_files()`.
 *
 * @param string $unsafe_query_raw
 *
 * @return string
 */
function meff_validate_query( $unsafe_query_raw ) {
	$safe_query = '';

	parse_str( $unsafe_query_raw, $unsafe_query );

	foreach ( $unsafe_query as $unsafe_key => $unsafe_value ) {
		// If it matches a case, then $unsafe_key is safe.
		switch ( $unsafe_key ) {
			case 'allowScriptAccess':
			case 'autoplay':
				$safe_query = add_query_arg( $unsafe_key, (bool) $unsafe_value, $safe_query );
				break;

			case 'timerrate':
				$safe_query = add_query_arg( $unsafe_key, (int) $unsafe_value, $safe_query );
				break;

			case 'src':
				$safe_query = add_query_arg( $unsafe_key, esc_url_raw( $unsafe_value ), $safe_query );
				break;

			case 'uid':
			case 'preload':
			case 'flashstreamer':
			case 'pseudostreamstart':
			case 'pseudostreamtype':
			case 'proxytype':
			case 'streamdelimiter':
				// The pattern should not use shorthands like \w, or support Unicode, just to be strict/safe.
				$safe_value = preg_replace( '/[^a-z0-9]/i', '', $unsafe_value );
				$safe_query = add_query_arg( $unsafe_key, $safe_value, $safe_query );
		        break;

			default:
				// It's not a whitelisted parameter, so ignore it.
		};
	}

	return $safe_query;
}
