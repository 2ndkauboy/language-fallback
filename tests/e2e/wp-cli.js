/**
 * Helper to run WP-CLI commands inside the `wp-env` containers.
 *
 * @package language-fallback
 */

/**
 * External dependencies
 */
const { execFileSync } = require( 'node:child_process' );
const path = require( 'node:path' );

const PLUGIN_ROOT = path.join( __dirname, '..', '..' );

/**
 * Run a list of shell commands in the WP-CLI container of the test environment.
 *
 * All commands are chained with `&&` and run in a single container call, so a failing
 * command aborts the rest.
 *
 * The test environment is a separate `wp-env` configuration with its own containers
 * and database, so the container is the plain `cli` service of that configuration —
 * not the `tests-cli` service that the deprecated two-environments-in-one-file setup
 * used to provide.
 *
 * @param {string[]} commands   The shell commands to run.
 * @param {string}   configFile The `wp-env` configuration file of the environment.
 *
 * @return {void}
 */
function runWpCli( commands, configFile = '.wp-env.test.json' ) {
	execFileSync(
		'npx',
		[
			'wp-env',
			'run',
			'cli',
			`--config=${ configFile }`,
			'--',
			'sh',
			'-c',
			commands.join( ' && ' ),
		],
		{ cwd: PLUGIN_ROOT, stdio: 'inherit' }
	);
}

module.exports = { runWpCli };
