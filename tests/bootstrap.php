<?php
/**
 * Bootstrap for the unit tests.
 *
 * The plugin is tested in isolation, WordPress itself is never loaded. All WordPress
 * functions are mocked with Brain Monkey, the classes the plugin touches are replaced
 * by the stubs in `_stubs.php`.
 *
 * As the plugin reads translation files from the file system, `WP_LANG_DIR` and `ABSPATH`
 * point to a fixture directory that is created here and filled by the single tests.
 *
 * @package language-fallback
 */

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

require_once __DIR__ . '/_stubs.php';

$language_fallback_fixtures = sys_get_temp_dir() . '/language-fallback-tests';

define( 'WP_LANG_DIR', $language_fallback_fixtures . '/languages' );
define( 'ABSPATH', $language_fallback_fixtures . '/wordpress/' );

foreach (
	array(
		WP_LANG_DIR . '/plugins',
		WP_LANG_DIR . '/themes',
		ABSPATH . 'wp-admin/includes',
	) as $language_fallback_directory
) {
	if ( ! is_dir( $language_fallback_directory ) ) {
		mkdir( $language_fallback_directory, 0777, true );
	}
}

// The plugin requires this file before downloading a language pack.
file_put_contents( ABSPATH . 'wp-admin/includes/translation-install.php', "<?php\n" );

/*
 * The plugin file creates an instance while being loaded, so Brain Monkey has to be
 * active and the functions used by the constructor have to be stubbed.
 */
Brain\Monkey\setUp();
Brain\Monkey\Functions\when( 'get_locale' )->justReturn( 'de_DE_formal' );
Brain\Monkey\Functions\when( 'get_option' )->justReturn( '' );
Brain\Monkey\Functions\when( 'plugin_basename' )->justReturn( 'language-fallback/language-fallback.php' );
Brain\Monkey\Functions\when( 'load_plugin_textdomain' )->justReturn( true );
require_once dirname( __DIR__ ) . '/language-fallback.php';
Brain\Monkey\tearDown();
