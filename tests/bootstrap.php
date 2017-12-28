<?php

if ( 'cli' !== php_sapi_name() ) {
	return;
}

$core_tests_directory = getenv( 'WP_TESTS_DIR' );

if ( ! $core_tests_directory ) {
	echo "\nPlease set the WP_TESTS_DIR environment variable to the folder where WordPress' PHPUnit tests live --";
	echo "\ne.g., export WP_TESTS_DIR=/srv/www/wordpress-develop/tests/phpunit\n";

	return;
}

require_once( $core_tests_directory . '/includes/functions.php' );
require_once( dirname( dirname( $core_tests_directory ) ) . '/src/wp-admin/includes/plugin.php' );

/**
 * Mock wp_safe_redirect() so that functions calling it can be tested without side-effects.
 */
function wp_safe_redirect( $location, $status = 302 ) {
	throw new Exception( 'Redirecting' );
}

/**
 * Load the plugins that we'll need to be active for the tests
 */
function meff_manually_load_plugin() {
	require_once( dirname( dirname( __FILE__ ) ) . '/mediaelement-flash-fallbacks.php' );
}
tests_add_filter( 'muplugins_loaded', 'meff_manually_load_plugin' );

require( $core_tests_directory . '/includes/bootstrap.php' );
