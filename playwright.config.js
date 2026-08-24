/**
 * Playwright configuration for the end-to-end tests.
 *
 * @package language-fallback
 */

/**
 * External dependencies
 */
const { defineConfig } = require( '@playwright/test' );

/**
 * WordPress dependencies
 */
const baseConfig = require( '@wordpress/scripts/config/playwright.config' );

module.exports = defineConfig( {
	...baseConfig,
	testDir: './tests/e2e/specs',
	globalSetup: require.resolve( './tests/e2e/global-setup.js' ),
} );
