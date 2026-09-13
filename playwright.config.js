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
 * Internal dependencies
 */
const testEnvConfig = require( './.wp-env.test.json' );

/*
 * Point the tests at the `wp-env` environment defined by `.wp-env.test.json`.
 *
 * `wp-scripts test-playwright` derives `WP_BASE_URL` from `env.tests.port` of the
 * default `.wp-env.json`, which no longer describes a tests environment — so without
 * this, the base configuration would silently fall back to its own default of
 * `http://localhost:8889` and every test would fail to connect. Set before the base
 * configuration is required, because that reads the variable at load time. An
 * explicitly exported `WP_BASE_URL` still wins, which is what `--config` users of a
 * further parallel environment need.
 */
process.env.WP_BASE_URL =
	process.env.WP_BASE_URL || `http://localhost:${ testEnvConfig.port }`;

/**
 * WordPress dependencies
 */
const baseConfig = require( '@wordpress/scripts/config/playwright.config' );

module.exports = defineConfig( {
	...baseConfig,
	testDir: './tests/e2e/specs',
	globalSetup: require.resolve( './tests/e2e/global-setup.js' ),
} );
