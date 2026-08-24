<?php
/**
 * Unit tests for the `Language_Fallback` class.
 *
 * @package language-fallback
 */

namespace LanguageFallback\Tests\Unit;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Language_Fallback;
use Mockery;
use NOOP_Translations;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

/**
 * Class LanguageFallbackTest.
 */
final class LanguageFallbackTest extends TestCase {

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
	const DOMAIN = 'my-plugin';

	/**
	 * Set up the stubs for the translation and escaping functions.
	 *
	 * @return void
	 */
	protected function set_up() {
		parent::set_up();

		$this->stubTranslationFunctions();
		$this->stubEscapeFunctions();

		$this->clean_language_directories();
	}

	/**
	 * Remove the translation files created by a test.
	 *
	 * @return void
	 */
	protected function tear_down() {
		$this->clean_language_directories();

		parent::tear_down();
	}

	/**
	 * The constructor registers all hooks and loads the translation of the plugin itself.
	 *
	 * @return void
	 */
	public function test_constructor_registers_the_hooks() {
		Functions\when( 'get_locale' )->justReturn( self::LOCALE );
		Functions\when( 'get_option' )->justReturn( self::FALLBACK_LOCALE );
		Functions\when( 'plugin_basename' )->justReturn( 'language-fallback/language-fallback.php' );

		Actions\expectAdded( 'override_load_textdomain' )->once();
		Filters\expectAdded( 'gettext' )->once();
		Actions\expectAdded( 'admin_init' )->once();

		Functions\expect( 'load_plugin_textdomain' )
			->once()
			->with( 'language-fallback', false, 'language-fallback/languages' );

		$this->assertInstanceOf( Language_Fallback::class, new Language_Fallback() );
	}

	/**
	 * An existing translation file for the locale of the site is loaded by WordPress itself.
	 *
	 * @return void
	 */
	public function test_fallback_load_textdomain_keeps_an_existing_translation_file() {
		$plugin = $this->create_plugin();

		$mofile = $this->create_translation_file( 'plugins', self::DOMAIN . '-' . self::LOCALE . '.mo' );

		Functions\expect( 'load_textdomain' )->never();

		$this->assertFalse( $plugin->fallback_load_textdomain( false, self::DOMAIN, $mofile ) );
	}

	/**
	 * A missing translation file is replaced by the one of the fallback locale.
	 *
	 * @return void
	 */
	public function test_fallback_load_textdomain_loads_the_fallback_translation_file() {
		$plugin = $this->create_plugin();

		$mofile          = WP_LANG_DIR . '/plugins/' . self::DOMAIN . '-' . self::LOCALE . '.mo';
		$fallback_mofile = $this->create_translation_file(
			'plugins',
			self::DOMAIN . '-' . self::FALLBACK_LOCALE . '.mo'
		);

		Functions\expect( 'load_textdomain' )
			->once()
			->with( self::DOMAIN, $fallback_mofile )
			->andReturn( true );

		$this->assertTrue( $plugin->fallback_load_textdomain( false, self::DOMAIN, $mofile ) );
	}

	/**
	 * Without a translation file for the fallback locale, nothing is overridden.
	 *
	 * @return void
	 */
	public function test_fallback_load_textdomain_without_any_translation_file() {
		$plugin = $this->create_plugin();

		$mofile = WP_LANG_DIR . '/plugins/' . self::DOMAIN . '-' . self::LOCALE . '.mo';

		Functions\expect( 'load_textdomain' )->never();

		$this->assertFalse( $plugin->fallback_load_textdomain( false, self::DOMAIN, $mofile ) );
	}

	/**
	 * The translations of WordPress itself are never touched.
	 *
	 * @return void
	 */
	public function test_just_in_time_fallback_skips_the_default_domain() {
		$plugin = $this->create_plugin();

		Functions\expect( 'get_translations_for_domain' )->never();

		$this->assertSame(
			'Übersetzung',
			$plugin->just_in_time_fallback( 'Übersetzung', 'Translation', 'default' )
		);
	}

	/**
	 * A text domain with loaded translations is left alone.
	 *
	 * @return void
	 */
	public function test_just_in_time_fallback_skips_loaded_translations() {
		$plugin = $this->create_plugin();

		$translations = Mockery::mock( 'Translations' );
		$translations->shouldReceive( 'translate' )->never();

		Functions\expect( 'get_translations_for_domain' )
			->once()
			->with( self::DOMAIN )
			->andReturn( $translations );

		Functions\expect( 'load_textdomain' )->never();

		$this->assertSame(
			'Übersetzung',
			$plugin->just_in_time_fallback( 'Übersetzung', 'Translation', self::DOMAIN )
		);
	}

	/**
	 * A text domain without translations is translated with the fallback language.
	 *
	 * @return void
	 */
	public function test_just_in_time_fallback_loads_the_fallback_translation() {
		$plugin = $this->create_plugin();

		$fallback_mofile = $this->create_translation_file(
			'plugins',
			self::DOMAIN . '-' . self::FALLBACK_LOCALE . '.mo'
		);

		$translations = Mockery::mock( 'Translations' );
		$translations->shouldReceive( 'translate' )->once()->with( 'Translation' )->andReturn( 'Übersetzung' );

		Functions\expect( 'get_translations_for_domain' )
			->twice()
			->with( self::DOMAIN )
			->andReturn( new NOOP_Translations(), $translations );

		Functions\expect( 'load_textdomain' )
			->once()
			->with( self::DOMAIN, $this->pointing_to( $fallback_mofile ) )
			->andReturn( true );

		$this->assertSame(
			'Übersetzung',
			$plugin->just_in_time_fallback( 'Translation', 'Translation', self::DOMAIN )
		);
	}

	/**
	 * A translation file in the themes directory is found as well.
	 *
	 * @return void
	 */
	public function test_just_in_time_fallback_also_looks_into_the_themes_directory() {
		$plugin = $this->create_plugin();

		$fallback_mofile = $this->create_translation_file(
			'themes',
			self::DOMAIN . '-' . self::FALLBACK_LOCALE . '.mo'
		);

		$translations = Mockery::mock( 'Translations' );
		$translations->shouldReceive( 'translate' )->once()->andReturn( 'Übersetzung' );

		Functions\expect( 'get_translations_for_domain' )
			->twice()
			->andReturn( new NOOP_Translations(), $translations );

		Functions\expect( 'load_textdomain' )
			->once()
			->with( self::DOMAIN, $this->pointing_to( $fallback_mofile ) )
			->andReturn( true );

		$this->assertSame(
			'Übersetzung',
			$plugin->just_in_time_fallback( 'Translation', 'Translation', self::DOMAIN )
		);
	}

	/**
	 * Without a translation file for the fallback locale, the original string is returned.
	 *
	 * @return void
	 */
	public function test_just_in_time_fallback_without_a_fallback_translation() {
		$plugin = $this->create_plugin();

		Functions\expect( 'get_translations_for_domain' )
			->once()
			->with( self::DOMAIN )
			->andReturn( new NOOP_Translations() );

		Functions\expect( 'load_textdomain' )->never();

		$this->assertSame(
			'Translation',
			$plugin->just_in_time_fallback( 'Translation', 'Translation', self::DOMAIN )
		);
	}

	/**
	 * The setting is registered on the general settings page.
	 *
	 * @return void
	 */
	public function test_general_settings_registers_the_setting() {
		$plugin = $this->create_plugin();

		Functions\expect( 'add_settings_section' )
			->once()
			->with( 'language_fallback', 'Language Fallback Settings', Mockery::type( 'array' ), 'general' );

		Functions\expect( 'add_settings_field' )
			->once()
			->with( 'fallback_locale', 'Site Fallback Language', Mockery::type( 'array' ), 'general', 'language_fallback' );

		Functions\expect( 'register_setting' )
			->once()
			->with( 'general', 'fallback_locale', array( 'sanitize_callback' => 'sanitize_locale_name' ) );

		$plugin->general_settings();
	}

	/**
	 * The section of the setting does not render anything on its own.
	 *
	 * @return void
	 */
	public function test_fallback_locale_section_renders_nothing() {
		$plugin = $this->create_plugin();

		$this->expectOutputString( '' );

		$plugin->fallback_locale_section();
	}

	/**
	 * An installed fallback language is preselected without downloading anything.
	 *
	 * @return void
	 */
	public function test_fallback_locale_field_uses_an_installed_language() {
		$plugin = $this->create_plugin();

		$translations = array( self::FALLBACK_LOCALE => array( 'native_name' => 'Deutsch' ) );

		Functions\expect( 'get_available_languages' )->once()->andReturn( array( self::FALLBACK_LOCALE ) );
		Functions\expect( 'wp_get_available_translations' )->once()->andReturn( $translations );
		Functions\when( 'is_multisite' )->justReturn( false );
		Functions\when( 'wp_can_install_language_pack' )->justReturn( true );

		Functions\expect( 'wp_download_language_pack' )->never();

		Functions\expect( 'wp_dropdown_languages' )
			->once()
			->with(
				array(
					'name'                        => 'fallback_locale',
					'id'                          => 'fallback_locale',
					'selected'                    => self::FALLBACK_LOCALE,
					'languages'                   => array( self::FALLBACK_LOCALE ),
					'translations'                => $translations,
					'show_available_translations' => true,
				)
			);

		$plugin->fallback_locale_field();
	}

	/**
	 * A fallback language that is not installed yet is downloaded.
	 *
	 * @return void
	 */
	public function test_fallback_locale_field_downloads_a_missing_language_pack() {
		$plugin = $this->create_plugin();

		Functions\expect( 'get_available_languages' )->once()->andReturn( array() );
		Functions\expect( 'wp_get_available_translations' )->once()->andReturn( array() );
		Functions\when( 'is_multisite' )->justReturn( false );
		Functions\when( 'wp_can_install_language_pack' )->justReturn( true );

		Functions\expect( 'wp_download_language_pack' )
			->once()
			->with( self::FALLBACK_LOCALE )
			->andReturn( self::FALLBACK_LOCALE );

		Functions\expect( 'wp_dropdown_languages' )
			->once()
			->with(
				array(
					'name'                        => 'fallback_locale',
					'id'                          => 'fallback_locale',
					'selected'                    => self::FALLBACK_LOCALE,
					'languages'                   => array(),
					'translations'                => array(),
					'show_available_translations' => true,
				)
			);

		$plugin->fallback_locale_field();
	}

	/**
	 * Create an instance of the plugin with the given fallback locale.
	 *
	 * @param string $fallback_locale The fallback locale stored in the options.
	 *
	 * @return Language_Fallback
	 */
	private function create_plugin( $fallback_locale = self::FALLBACK_LOCALE ) {
		Functions\when( 'get_locale' )->justReturn( self::LOCALE );
		Functions\when( 'get_option' )->justReturn( $fallback_locale );
		Functions\when( 'plugin_basename' )->justReturn( 'language-fallback/language-fallback.php' );
		Functions\when( 'load_plugin_textdomain' )->justReturn( true );

		return new Language_Fallback();
	}

	/**
	 * Create a translation file in the language directory.
	 *
	 * @param string $type     The type of the language directory, `plugins` or `themes`.
	 * @param string $filename The name of the translation file.
	 *
	 * @return string The full path of the created file.
	 */
	private function create_translation_file( $type, $filename ) {
		$path = WP_LANG_DIR . '/' . $type . '/' . $filename;

		file_put_contents( $path, '' );

		return $path;
	}

	/**
	 * Matcher for a path that resolves to the given file.
	 *
	 * The plugin builds the paths of the "just in time" translations with an additional
	 * slash, so the paths have to be normalized before they are compared.
	 *
	 * @param string $file The full path of the expected file.
	 *
	 * @return \Mockery\Matcher\Closure The matcher for the path.
	 */
	private function pointing_to( $file ) {
		return Mockery::on(
			static function ( $path ) use ( $file ) {
				return realpath( $path ) === realpath( $file );
			}
		);
	}

	/**
	 * Remove all translation files created by a test.
	 *
	 * @return void
	 */
	private function clean_language_directories() {
		foreach ( array( 'plugins', 'themes' ) as $type ) {
			foreach ( (array) glob( WP_LANG_DIR . '/' . $type . '/*' ) as $file ) {
				unlink( $file );
			}
		}
	}
}
