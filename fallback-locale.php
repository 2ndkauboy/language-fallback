<?php

/*
 * Plugin Name: Fallback Locale
 * Description: Set a locale as a fallback for the chosen locale (e.g. de_DE as a fallback for de_DE-formal)
 * Version: 0.1
 * Author: Bernhard Kau
 * Author URI: http://kau-boys.de
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0
 */

class Fallback_Locale {

	// used to store the current locale e.g. "de_DE"
	private $locale;

	// used to store the fallback locale
	private $fallback_locale;

	function __construct() {

		// get current locale
		$this->locale = get_locale();

		// set folder for overwrites
		$this->fallback_locale = apply_filters( 'fallback_locale', 'de_DE' );

		// register action that is triggered, whenever a textdomain is loaded
		add_action( 'override_load_textdomain', array( $this, 'fallback_load_textdomain' ), 10, 3 );
	}

	/*
	 * A function to check if the requested mofile exists and if not, it checks if a mofile for the fallback locale exists
	 */
	function fallback_load_textdomain( $override, $domain, $mofile ) {

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

}

new Fallback_Locale;