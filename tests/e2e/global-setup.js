/**
 * Global setup for the end-to-end tests.
 *
 * @package language-fallback
 */

/**
 * WordPress dependencies
 */
const baseGlobalSetup = require( '@wordpress/scripts/config/playwright/global-setup' );

/**
 * Internal dependencies
 */
const { runWpCli } = require( './wp-cli' );

/**
 * @param {import('@playwright/test').FullConfig} config The Playwright configuration.
 *
 * @return {Promise<void>}
 */
module.exports = async function globalSetup( config ) {
	await baseGlobalSetup( config );

	runWpCli( [
		/*
		 * The plugin is activated by `wp-env` on start, but the integration test suite
		 * reinstalls WordPress in the very same database, which resets the active
		 * plugins. Activating it here keeps the E2E setup independent of that.
		 */
		'( wp plugin is-active language-fallback || wp plugin activate language-fallback )',

		// Both locales are needed: the formal one for the site, the informal one as fallback.
		'wp language core install de_DE de_DE_formal',

		/*
		 * Reinstall the translations of this plugin, as WP-CLI would consider a locale
		 * to be installed even if a previous test run has deleted its files.
		 */
		'( wp language plugin uninstall language-fallback de_DE de_DE_formal || true )',
		'wp language plugin install language-fallback de_DE de_DE_formal',

		// Reset the settings the tests are changing, so runs stay reproducible.
		"wp option update WPLANG ''",
		'( wp option delete fallback_locale || true )',
	] );
};
