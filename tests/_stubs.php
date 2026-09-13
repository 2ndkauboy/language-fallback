<?php
/**
 * Stubs for the WordPress classes used by the plugin.
 *
 * @package language-fallback
 */

/**
 * Stub for the translations object of a text domain without any translations.
 */
class NOOP_Translations { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound

	/**
	 * Return the original string, as there is nothing to translate.
	 *
	 * @param string      $singular The string to translate.
	 * @param string|null $context  The context of the string.
	 *
	 * @return string
	 */
	public function translate( $singular, $context = null ) {
		return $singular;
	}
}

/**
 * Stub for the translations object of a text domain with translations.
 */
class Translations { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound

	/**
	 * Translate a string.
	 *
	 * @param string      $singular The string to translate.
	 * @param string|null $context  The context of the string.
	 *
	 * @return string
	 */
	public function translate( $singular, $context = null ) {
		return $singular;
	}
}
