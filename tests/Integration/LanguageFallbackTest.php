<?php
/**
 * Integration tests for the `Language_Fallback` class.
 *
 * @package language-fallback
 */

use Yoast\WPTestUtils\WPIntegration\TestCase;

/**
 * Class LanguageFallbackTest.
 */
class LanguageFallbackTest extends TestCase {

	/**
	 * The locale of the site used in the tests.
	 */
	const LOCALE = 'de_DE_formal';

	/**
	 * The fallback locale used in the tests.
	 */
	const FALLBACK_LOCALE = 'de_DE';

	/**
	 * The text domain used in the tests.
	 */
	const DOMAIN = 'language-fallback-test-domain';

	/**
	 * The instance of the plugin.
	 *
	 * @var Language_Fallback
	 */
	private $plugin;

	/**
	 * The translation files created by a test.
	 *
	 * @var string[]
	 */
	private $created_files = array();

	/**
	 * Create an instance using the formal German locale with informal German as fallback.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		update_option( 'fallback_locale', self::FALLBACK_LOCALE );
		add_filter( 'locale', array( $this, 'filter_locale' ) );

		if ( ! is_dir( WP_LANG_DIR . '/plugins' ) ) {
			mkdir( WP_LANG_DIR . '/plugins', 0777, true );
		}

		$this->plugin = new Language_Fallback();
	}

	/**
	 * Remove the created translation files and the loaded text domain.
	 *
	 * @return void
	 */
	public function tear_down() {
		unload_textdomain( self::DOMAIN );

		foreach ( $this->created_files as $file ) {
			if ( file_exists( $file ) ) {
				unlink( $file );
			}
		}

		$this->created_files = array();

		parent::tear_down();
	}

	/**
	 * Return the locale of the site used in the tests.
	 *
	 * @return string
	 */
	public function filter_locale() {
		return self::LOCALE;
	}

	/**
	 * A translation file for the locale of the site is loaded by WordPress itself.
	 *
	 * @return void
	 */
	public function test_the_translation_of_the_locale_wins() {
		$mofile = $this->create_mo_file( self::LOCALE, 'Hallo (Sie)' );
		$this->create_mo_file( self::FALLBACK_LOCALE, 'Hallo (Du)' );

		$this->assertTrue( load_textdomain( self::DOMAIN, $mofile ) );
		$this->assertSame( 'Hallo (Sie)', __( 'Hello', self::DOMAIN ) );
	}

	/**
	 * A missing translation file is replaced by the one of the fallback locale.
	 *
	 * @return void
	 */
	public function test_the_fallback_translation_is_loaded() {
		$this->create_mo_file( self::FALLBACK_LOCALE, 'Hallo (Du)' );

		$missing_mofile = WP_LANG_DIR . '/plugins/' . self::DOMAIN . '-' . self::LOCALE . '.mo';

		$this->assertTrue( load_textdomain( self::DOMAIN, $missing_mofile ) );
		$this->assertSame( 'Hallo (Du)', __( 'Hello', self::DOMAIN ) );
	}

	/**
	 * Without a translation for the fallback locale, nothing is loaded.
	 *
	 * @return void
	 */
	public function test_without_a_fallback_translation_nothing_is_loaded() {
		$missing_mofile = WP_LANG_DIR . '/plugins/' . self::DOMAIN . '-' . self::LOCALE . '.mo';

		$this->assertFalse( load_textdomain( self::DOMAIN, $missing_mofile ) );
		$this->assertSame( 'Hello', __( 'Hello', self::DOMAIN ) );
	}

	/**
	 * A text domain that was never loaded is translated "just in time".
	 *
	 * @return void
	 */
	public function test_the_fallback_translation_is_loaded_just_in_time() {
		$this->create_mo_file( self::FALLBACK_LOCALE, 'Hallo (Du)' );

		$this->assertSame( 'Hallo (Du)', __( 'Hello', self::DOMAIN ) );
	}

	/**
	 * Without any translation file the original string is returned.
	 *
	 * @return void
	 */
	public function test_the_original_string_is_returned_just_in_time() {
		$this->assertSame( 'Hello', __( 'Hello', self::DOMAIN ) );
	}

	/**
	 * A fallback for another locale than the one of the site is ignored.
	 *
	 * @return void
	 */
	public function test_the_fallback_locale_can_be_filtered() {
		$this->create_mo_file( 'fr_FR', 'Bonjour' );

		add_filter(
			'fallback_locale',
			static function () {
				return array( 'fr_FR' );
			}
		);

		$missing_mofile = WP_LANG_DIR . '/plugins/' . self::DOMAIN . '-' . self::LOCALE . '.mo';

		$this->assertTrue( load_textdomain( self::DOMAIN, $missing_mofile ) );
		$this->assertSame( 'Bonjour', __( 'Hello', self::DOMAIN ) );
	}

	/**
	 * The setting is registered on the general settings page.
	 *
	 * @return void
	 */
	public function test_general_settings_registers_the_setting() {
		global $wp_settings_sections, $wp_settings_fields, $wp_registered_settings;

		$this->plugin->general_settings();

		$this->assertArrayHasKey( 'language_fallback', $wp_settings_sections['general'] );
		$this->assertArrayHasKey( 'fallback_locale', $wp_settings_fields['general']['language_fallback'] );
		$this->assertSame(
			'sanitize_locale_name',
			$wp_registered_settings['fallback_locale']['sanitize_callback']
		);
	}

	/**
	 * The setting renders a language dropdown with the fallback locale selected.
	 *
	 * @return void
	 */
	public function test_fallback_locale_field_renders_the_language_dropdown() {
		// The admin loads this file on every admin page, the test suite does not.
		require_once ABSPATH . 'wp-admin/includes/translation-install.php';

		// Pretend that the fallback language is installed, so it shows up in the dropdown.
		$language_file = WP_LANG_DIR . '/' . self::FALLBACK_LOCALE . '.mo';

		$this->created_files[] = $language_file;
		file_put_contents( $language_file, '' );

		ob_start();
		$this->plugin->fallback_locale_field();
		$dropdown = ob_get_clean();

		$this->assertStringContainsString(
			'<select name="fallback_locale" id="fallback_locale">',
			$dropdown
		);
		$this->assertStringContainsString(
			'<option value="' . self::FALLBACK_LOCALE . '" lang="de" selected=\'selected\' data-installed="1">',
			$dropdown
		);
	}

	/**
	 * Create a translation file for the test text domain.
	 *
	 * @param string $locale      The locale of the translation file.
	 * @param string $translation The translation of the string `Hello`.
	 *
	 * @return string The full path of the created file.
	 */
	private function create_mo_file( $locale, $translation ) {
		$mofile = WP_LANG_DIR . '/plugins/' . self::DOMAIN . '-' . $locale . '.mo';

		$mo = new MO();
		$mo->add_entry(
			new Translation_Entry(
				array(
					'singular'     => 'Hello',
					'translations' => array( $translation ),
				)
			)
		);
		$mo->export_to_file( $mofile );

		$this->created_files[] = $mofile;

		return $mofile;
	}
}
