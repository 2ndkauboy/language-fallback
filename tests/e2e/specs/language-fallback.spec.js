/**
 * End-to-end test for the language fallback on the general settings page.
 *
 * @package language-fallback
 */

/**
 * WordPress dependencies
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

/**
 * Internal dependencies
 */
const { runWpCli } = require( '../wp-cli' );

const SITE_LOCALE = 'de_DE_formal';
const FALLBACK_LOCALE = 'de_DE';

const LABEL_ENGLISH = 'Site Fallback Language';
const LABEL_GERMAN = 'Ersatzsprache der Website';

/**
 * The table row of the fallback language setting, including its label.
 *
 * As the admin is switched to German during the test, the row is located by the ID of the
 * select field instead of by its (translated) label.
 *
 * @param {import('@playwright/test').Page} page The page object.
 *
 * @return {import('@playwright/test').Locator} The locator for the settings row.
 */
function fallbackSettingRow( page ) {
	return page.locator( 'tr' ).filter( { has: page.locator( '#fallback_locale' ) } );
}

/**
 * Submit the general settings form.
 *
 * @param {import('@playwright/test').Page} page The page object.
 *
 * @return {Promise<void>}
 */
async function saveSettings( page ) {
	await page.locator( '#submit' ).click();
	await page.waitForURL( /settings-updated=true/ );
}

test.describe( 'Language Fallback', () => {
	test( 'translates the settings page using the fallback language', async ( {
		admin,
		page,
	} ) => {
		// Switch the site to formal German.
		await admin.visitAdminPage( 'options-general.php' );
		await page.locator( '#WPLANG' ).selectOption( SITE_LOCALE );
		await saveSettings( page );

		// Remove the translation files for the site language, so only the fallback is left.
		runWpCli( [
			`rm -f /var/www/html/wp-content/languages/plugins/language-fallback-${ SITE_LOCALE }.*`,
		] );

		// Without a fallback, the untranslated string is shown.
		await admin.visitAdminPage( 'options-general.php' );
		await expect( fallbackSettingRow( page ) ).toContainText( LABEL_ENGLISH );

		// Choose informal German as the fallback language.
		await page.locator( '#fallback_locale' ).selectOption( FALLBACK_LOCALE );
		await saveSettings( page );

		// The very same setting is now translated through the fallback language.
		await expect( fallbackSettingRow( page ) ).toContainText( LABEL_GERMAN );
	} );
} );
