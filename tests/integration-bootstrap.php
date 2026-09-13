<?php
/**
 * Bootstrap for the integration tests.
 *
 * The tests run against a real WordPress installation inside the `wp-env` environment.
 * `wp-env` ships the PHPUnit test files of the installed WordPress version and points the
 * environment variable `WP_TESTS_DIR` to them, which is what `yoast/wp-test-utils` picks
 * up here.
 *
 * @package language-fallback
 */

use Yoast\WPTestUtils\WPIntegration;

$language_fallback_autoload = dirname( __DIR__ ) . '/vendor/autoload.php';

if ( ! file_exists( $language_fallback_autoload ) ) {
	echo 'The Composer dependencies are missing. Run `npm run composer install` first.' . PHP_EOL;
	exit( 1 );
}

require_once $language_fallback_autoload;
require_once dirname( __DIR__ ) . '/vendor/yoast/wp-test-utils/src/WPIntegration/bootstrap-functions.php';

$language_fallback_tests_dir = WPIntegration\get_path_to_wp_test_dir();

if ( false === $language_fallback_tests_dir ) {
	echo 'The WordPress test suite could not be found. Run the tests with `npm run test:integration`.' . PHP_EOL;
	exit( 1 );
}

require_once $language_fallback_tests_dir . 'includes/functions.php';

/*
 * Load the plugin as a mu-plugin, so it is active for every test without going through
 * the activation hooks.
 */
tests_add_filter(
	'muplugins_loaded',
	static function () {
		require dirname( __DIR__ ) . '/language-fallback.php';
	}
);

WPIntegration\bootstrap_it();
