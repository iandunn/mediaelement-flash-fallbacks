<?php

defined( 'WPINC' ) or die();

class Test_MediaElement_Flash_Fallbacks extends WP_UnitTestCase {
	/**
	 * Test that the redirect is only triggered when it should be.
	 *
	 * @param string $url             The requested URL.
	 * @param string $expected_result 'preempt' if the input should result in an early return;
	 *                                'redirect' if it should result in a redirect.
	 *
	 * @covers       meff_redirect_files()
	 * @dataProvider data_meff_redirect_files
	 */
	public function test_meff_redirect_files( $url, $expected_result ) {
		$_SERVER['REQUEST_URI'] = $url;

		// wp_safe_redirect() is mocked in tests/bootstrap.php to throw an exception.
		if ( 'redirect' === $expected_result ) {
			$this->expectException( 'Exception' );
		}

		$actual_preempt = meff_redirect_files( false );

		if ( 'preempt' === $expected_result ) {
			$this->assertSame( false, $actual_preempt );
		}
	}

	/**
	 * Data provider for test_meff_redirect_files()
	 *
	 * @return array
	 */
	public function data_meff_redirect_files() {
		return array(
			// All of the MediaElement 4.x SWF files bundled in Core should redirect.
			array(
				'http://example.org/wp-includes/js/mediaelement/mediaelement-flash-audio.swf',
				'redirect'
			),

			array(
				'http://example.org/wp-includes/js/mediaelement/mediaelement-flash-audio-ogg.swf',
				'redirect'
			),

			array(
				'http://example.org/wp-includes/js/mediaelement/mediaelement-flash-video.swf',
				'redirect'
			),

			array(
				'http://example.org/wp-includes/js/mediaelement/mediaelement-flash-video-hls.swf',
				'redirect'
			),

			array(
				'http://example.org/wp-includes/js/mediaelement/mediaelement-flash-video-mdash.swf',
				'redirect'
			),

			// The MediaElement 2.x SWF file should not redirect.
			array(
				'http://example.org/wp-includes/js/mediaelement/flashmediaelement.swf',
				'preempt'
			),

			// MediaElement 4.x SWF files outside of wp-includes should not redirect.
			array(
				'http://example.org/wp-content/plugins/mediaelement/mediaelement-flash-audio.swf',
				'preempt'
			),

			// MediaElement's 4.x non-SWF files should not redirect.
			array(
				'http://example.org/wp-includes/js/mediaelement/mediaelementjs',
				'preempt'
			),
		);
	}

	/**
	 * Test that the redirect URL is built correctly.
	 *
	 * This doesn't test the validation specifically, since that's covered in test_meff_validate_query().
	 *
	 * @param string $url             The requested URL.
	 * @param bool   $validate        Whether the URL's query should be validated or not.
	 * @param string $expected_result The expected URL.
	 *
	 * @covers       meff_get_redirect_url()
	 * @dataProvider data_meff_get_redirect_url
	 */
	public function test_meff_get_redirect_url( $url, $validate, $expected_result ) {
		$validation_callback = $validate ? '__return_true' : '__return_false';

		remove_all_filters( 'meff_validate_query' );

		add_filter( 'meff_validate_query', $validation_callback );
		$this->assertSame( $expected_result, meff_get_redirect_url( $url ) );
		remove_filter( 'meff_validate_query', $validation_callback );
	}

	/**
	 * Data provider for test_meff_get_redirect_url()
	 *
	 * @return array
	 */
	public function data_meff_get_redirect_url() {
		return array(
			// The redirect URL should be to the version of the file bundled in the plugin, rather than Core.
			// Also, a `?` shouldn't be added to the redirect URL if there wasn't a query in the request URL.
			array(
				'http://example.org/wp-includes/js/mediaelement/mediaelement-flash-audio.swf',
				false,
				'http://example.org/wp-content/plugins/mediaelement-flash-fallbacks/mediaelement/build/mediaelement-flash-audio.swf'
			),

			// The `?` should be added to the redirect URL when validation is off.
			array(
				'http://example.org/wp-includes/js/mediaelement/mediaelement-flash-video.swf?uid=homestar_runner',
				false,
				'http://example.org/wp-content/plugins/mediaelement-flash-fallbacks/mediaelement/build/mediaelement-flash-video.swf?uid=homestar_runner'
			),

			// The `?` should be added to the redirect URL when validation is on.
			array(
				'http://example.org/wp-includes/js/mediaelement/mediaelement-flash-audio-ogg.swf?preload=1',
				true,
				'http://example.org/wp-content/plugins/mediaelement-flash-fallbacks/mediaelement/build/mediaelement-flash-audio-ogg.swf?preload=1'
			),
		);
	}

	/**
	 * Test that the URL parameters for the SWF redirect URL are validated.
	 *
	 * @param string $query           The raw query before validation.
	 * @param string $expected_result The expected query after validation.
	 *
	 * @covers       meff_validate_query()
	 * @dataProvider data_meff_validate_query
	 */
	public function test_meff_validate_query( $query, $expected_result ) {
		$parsed_url    = parse_url( $query );
		$query         = isset( $parsed_url['query'] ) ? $parsed_url['query'] : '';
		$actual_result = meff_validate_query( $query );

		$this->assertSame( $expected_result, $actual_result );
	}

	/**
	 * Data provider for test_meff_validate_query()
	 *
	 * Note: Fragments are included in the test URLs here, but they won't be in real-world usage. See the note
	 * in `meff_redirect_files()` for details.
	 *
	 * @return array
	 */
	public function data_meff_validate_query() {
		global $meff_private_test_cases;

		$public_test_cases = array(
			// No params.
			array(
				'',
				'',
			),

	        // Parameters that equal `0` or '' will be removed, because of `add_query_arg()`.
			array(
				'?allowScriptAccess=true&autoplay=0',
				'?allowScriptAccess=1',
			),
			array(
				'?allowScriptAccess=true&autoplay',
				'?allowScriptAccess=1',
			),

			// Valid with all params.
			array(
				'?allowScriptAccess=true&autoplay=0&timerrate=300&src=https//example.org/files/foo.mp4&uid=test1&preload=foo&flashstreamer=bar&pseudostreamstart=bax&psuedostreamtype=quix&proxytype=baz&streamdelimiter=qax',
				'?allowScriptAccess=1&timerrate=300&src=http%3A%2F%2Fhttps%2F%2Fexample.org%2Ffiles%2Ffoo.mp4&uid=test1&preload=foo&flashstreamer=bar&pseudostreamstart=bax&proxytype=baz&streamdelimiter=qax',
			),

			// Valid with 1 param.
			array(
				'?autoplay=true&uid=0',
				'?autoplay=1&uid=0'
			),

			// Valid with a few params.
			array(
				'?autoplay=true&timerrate=50&uid=baz&preload=sure',
				'?autoplay=1&timerrate=50&uid=baz&preload=sure'
			),

			// Parameters that aren't whitelisted should be removed.
			array(
				'?autoplay=true&foo=bar',
				'?autoplay=1'
			),

			// Malicious payload with a hash, trying to bypass ME's validation.
			// @see https://hackerone.com/reports/134546.
			array(
				'?%#ui%d=alert%601%60',
				''
			),

			// Above payload with the hash removed, so that the fragment becomes part of the query.
			array(
				'?ui%d=alert%601%60',
				''
			),
		);

		// Include malicious payloads that haven't been made public yet.
		if ( ! is_array( $meff_private_test_cases ) ) {
			$meff_private_test_cases = array();
		}

		return array_merge( $public_test_cases, $meff_private_test_cases );
	}
}
