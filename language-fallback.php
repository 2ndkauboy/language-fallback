<?php
/*
 * Plugin Name: Language Fallback
 * Description: Set a language as a fallback for the chosen language (e.g. "Deutsch" as a fallback for "Deutsch (Sie)")
 * Version: 1.0
 * Author: Bernhard Kau
 * Author URI: http://kau-boys.de
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0
 */

class Language_Fallback {

	// used to store the current locale e.g. "de_DE"
	private $locale;

	// used to store the fallback locale
	private $fallback_locale;

	function __construct() {

		// get current locale
		$this->locale = get_locale();

		// set folder for overwrites
		$this->fallback_locale = apply_filters( 'fallback_locale', get_option( 'fallback_locale' ) );

		// register action that is triggered, whenever a textdomain is loaded
		add_action( 'override_load_textdomain', array( $this, 'fallback_load_textdomain' ), 10, 3 );

		// adding the options page
		#add_action( 'admin_menu', array( $this, 'options_page' ) );

		// adding the settings fields
		add_action( 'admin_init', array( $this, 'general_settings' ) );

		/**
		 * load plugin's translation
		 * Do this after the callback is set, so even loading the translations for this plugin will benefit from the fallback!
		 */
		load_plugin_textdomain( 'language-fallback', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * A function to check if the requested mofile exists and if not, it checks if a mofile for the fallback locale exists
	 *
	 * @param $override
	 * @param $domain
	 * @param $mofile
	 *
	 * @return bool
	 */
	public function fallback_load_textdomain( $override, $domain, $mofile ) {

		$mofile = apply_filters( 'load_textdomain_mofile', $mofile, $domain );

		if ( ! is_readable( $mofile ) ) {
			// try to get a fallback for the locale
			$mofile = str_replace( $this->locale . '.mo', $this->fallback_locale . '.mo', $mofile );

			if ( ! is_readable( $mofile ) ) {
				// fallback mofile not found
				return false;
			} else {
				// load fallback mofile
				load_textdomain( $domain, $mofile );
				// return true to skip the loading of the originally requested file
				return true;
			}
		}

		return false;
	}

	public function general_settings() {
		add_settings_section( 'language_fallback',  __( 'Language Fallback Settings', 'language-fallback' ), array( $this, 'fallback_locale_section' ), 'general' );
		add_settings_field( 'fallback_locale', __( 'Site Fallback Language', 'language-fallback' ), array( $this, 'fallback_locale_field' ), 'general', 'language_fallback' );
		register_setting( 'general', 'fallback_locale' );
	}

	public function fallback_locale_section() {
		// No extra content/headline needed
	}

	public function fallback_locale_field() {

		$languages = get_available_languages();
		$translations = wp_get_available_translations();
		$fallback_locale = $this->fallback_locale;

		// Handle translation install.
		if ( ! empty( $fallback_locale ) && ! in_array( $fallback_locale, $languages ) && ( ! is_multisite() || is_super_admin() ) ) {
			require_once( ABSPATH . 'wp-admin/includes/translation-install.php' );

			if ( wp_can_install_language_pack() ) {
				$language = wp_download_language_pack( $fallback_locale );
				if ( $language ) {
					$fallback_locale = $language;
				}
			}
		}

		wp_dropdown_languages( array(
			'name'         => 'fallback_locale',
			'id'           => 'fallback_locale',
			'selected'     => $fallback_locale,
			'languages'    => $languages,
			'translations' => $translations,
			'show_available_translations' => ( ! is_multisite() || is_super_admin() ) && wp_can_install_language_pack(),
		) );
	}

}

new Language_Fallback;