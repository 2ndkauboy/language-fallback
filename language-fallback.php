<?php
/**
 * Language Fallback
 *
 * @package language-fallback
 * @author  Bernhard Kau
 * @license GPLv3
 *
 * @wordpress-plugin
 * Plugin Name: Language Fallback
 * Plugin URI: https://github.com/2ndkauboy/language-fallback
 * Description: Set a language as a fallback for the chosen language (e.g. "Deutsch" as a fallback for "Deutsch (Sie)")
 * Version: 2.0.0
 * Author: Bernhard Kau
 * Author URI: https://kau-boys.com
 * Text Domain: language-fallback
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 */

/**
 * Class Language_Fallback.
 */
class Language_Fallback {

	/**
	 * Used to store the current locale e.g. "de_DE"
	 *
	 * @var string
	 */
	private $locale;

	/**
	 * Used to store the fallback locale
	 *
	 * @var string
	 */
	private $fallback_locale;

	/**
	 * Used for the found "just in time" translations"
	 *
	 * @var array
	 */
	private $just_in_time_paths = [];

	/**
	 * Used to store all cached mo files.
	 *
	 * @var null|array
	 */
	private $cached_mo_files;

	/**
	 * Constructor.
	 */
	public function __construct() {

		// Get current locale.
		$this->locale = get_locale();

		// Set folder for overwrites.
		$this->fallback_locale = get_option( 'fallback_locale' );

		// Register action that is triggered, whenever a textdomain is loaded.
		add_action( 'override_load_textdomain', [ $this, 'fallback_load_textdomain' ], 10, 3 );

		// Hook into `gettext` to load the `mofile` the first time a translation is needed for a textdomain.
		add_filter( 'gettext', [ $this, 'just_in_time_fallback' ], 10, 3 );

		// Adding the settings fields.
		add_action( 'admin_init', [ $this, 'general_settings' ] );

		/**
		 * Load plugin's translation.
		 *
		 * Do this after the callback is set, so even loading the translations for this plugin will benefit from the fallback!
		 */
		load_plugin_textdomain( 'language-fallback', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * A function to check if the requested mofile exists and if not, it checks if a mofile for the fallback locale exists.
	 *
	 * @param bool   $override Whether to override the text domain. Default false.
	 * @param string $domain Text domain. Unique identifier for retrieving translated strings.
	 * @param string $mofile Path to the MO file.
	 *
	 * @return bool
	 */
	public function fallback_load_textdomain( $override, $domain, $mofile ) {

		/**
		 * Filter the fallback locale
		 *
		 * @param string $domain Text domain. Unique identifier for retrieving translated strings.
		 * @param string $locale The locale of the blog.
		 */
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$fallback_locales = apply_filters( 'fallback_locale', [ $this->fallback_locale ], $domain, $this->locale );

		/**
		 * Filters MO file path for loading translations for a specific text domain.
		 */
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$mofile = apply_filters( 'load_textdomain_mofile', $mofile, $domain );

		if ( ! is_readable( $mofile ) ) {

			// Try to get a fallback for the locale.
			foreach ( $fallback_locales as $fallback_locale ) {
				$fallback_mofile = str_replace( $this->locale . '.mo', $fallback_locale . '.mo', $mofile );

				if ( is_readable( $fallback_mofile ) ) {
					// Load fallback mofile.
					load_textdomain( $domain, $fallback_mofile );

					// Return true to skip the loading of the originally requested file.
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Fixing missing translation files in the `_load_textdomain_just_in_time`.
	 *
	 * @param string $translation Translated text.
	 * @param string $text Text to translate.
	 * @param string $domain Text domain. Unique identifier for retrieving translated strings.
	 *
	 * @return string Translated text.
	 */
	public function just_in_time_fallback( $translation, $text, $domain ) {
		if ( 'default' === $domain ) {
			return $translation;
		}

		$translations = get_translations_for_domain( $domain );

		if ( $translations instanceof NOOP_Translations ) {
			// If we have already searched for translations but couldn't find any, return the original translation string.
			if ( array_key_exists( $domain, $this->just_in_time_paths ) && empty( $this->just_in_time_paths[ $domain ] ) ) {
				return $translation;
			}

			// @phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			$fallback_locales = apply_filters( 'fallback_locale', [ $this->fallback_locale ], $domain, $this->locale );

			$path = $this->get_path_for_mofile( $domain, $fallback_locales );

			if ( ! $path ) {
				return $translation;
			}

			foreach ( $fallback_locales as $locale ) {
				$mofile = "{$path}/{$domain}-{$locale}.mo";

				if ( load_textdomain( $domain, $mofile ) ) {
					$this->just_in_time_paths[ $domain ] = $mofile;

					$translations = get_translations_for_domain( $domain );
					$translation  = $translations->translate( $text );

					break;
				}
			}

			// No translations for this domain could be found.
			$this->just_in_time_paths[ $domain ] = '';
		}

		return $translation;
	}

	/**
	 * Get the path for the domain by checking in the `themes` and `plugins` language folders.
	 *
	 * Similar implementation than in the `preferred-languages` plugin version 1.8.0 on `/inc/class-preferred-languages-textdomain-registry.php:84`
	 *
	 * @param string $domain The current textdomain.
	 * @param array  $fallback_locales The list of fallback languages.
	 *
	 * @return false|string
	 */
	private function get_path_for_mofile( $domain, $fallback_locales ) {
		if ( null === $this->cached_mo_files ) {
			$this->cached_mo_files = [];
			$this->set_cached_mo_files();
		}
		foreach ( $fallback_locales as $locale ) {
			$mo_file = "{$domain}-{$locale}.mo";

			$path = WP_LANG_DIR . '/plugins/' . $mo_file;
			if ( in_array( $path, $this->cached_mo_files, true ) ) {
				$path = WP_LANG_DIR . '/plugins/';

				$this->just_in_time_paths[ $domain ] = $path;

				return $path;
			}

			$path = WP_LANG_DIR . '/themes/' . $mo_file;
			if ( in_array( $path, $this->cached_mo_files, true ) ) {
				$path = WP_LANG_DIR . '/themes/';

				$this->just_in_time_paths[ $domain ] = $path;

				return $path;
			}
		}

		$this->just_in_time_paths[ $domain ] = '';

		return false;
	}

	/**
	 * Reads and caches all available MO files from the plugins and themes language directories.
	 *
	 * Similar implementation than in the `preferred-languages` plugin version 1.8.0 on `/inc/class-preferred-languages-textdomain-registry.php:123`
	 */
	protected function set_cached_mo_files() {
		$locations = [
			WP_LANG_DIR . '/plugins',
			WP_LANG_DIR . '/themes',
		];

		foreach ( $locations as $location ) {
			$mo_files = glob( $location . '/*.mo' );

			if ( $mo_files ) {
				array_push( $this->cached_mo_files, ...$mo_files );
			}
		}
	}

	/**
	 * Register the settings for the language fallback on the general settings page
	 */
	public function general_settings() {
		add_settings_section(
			'language_fallback',
			__( 'Language Fallback Settings', 'language-fallback' ),
			[ $this, 'fallback_locale_section' ],
			'general'
		);
		add_settings_field(
			'fallback_locale',
			__( 'Site Fallback Language', 'language-fallback' ),
			[ $this, 'fallback_locale_field' ],
			'general',
			'language_fallback'
		);
		register_setting(
			'general',
			'fallback_locale'
		);
	}

	/**
	 * Empty callback for the section, as no extra content/headline is needed
	 */
	public function fallback_locale_section() {
		// Nothing to do here.
	}

	/**
	 * Download the chosen fallback language on save and create the language dropdown similar to the default language dropdown
	 */
	public function fallback_locale_field() {
		$languages       = get_available_languages();
		$translations    = wp_get_available_translations();
		$fallback_locale = $this->fallback_locale;

		// Handle translation install.
		if ( ! empty( $fallback_locale ) && ! in_array( $fallback_locale, $languages, true ) && ( ! is_multisite() || is_super_admin() ) ) {
			require_once ABSPATH . 'wp-admin/includes/translation-install.php';

			if ( wp_can_install_language_pack() ) {
				$language = wp_download_language_pack( $fallback_locale );
				if ( $language ) {
					$fallback_locale = $language;
				}
			}
		}

		wp_dropdown_languages(
			[
				'name'                        => 'fallback_locale',
				'id'                          => 'fallback_locale',
				'selected'                    => $fallback_locale,
				'languages'                   => $languages,
				'translations'                => $translations,
				'show_available_translations' => ( ! is_multisite() || is_super_admin() ) && wp_can_install_language_pack(),
			]
		);
	}

}

new Language_Fallback();
