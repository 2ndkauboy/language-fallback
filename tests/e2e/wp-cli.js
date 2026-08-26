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
 * Run a list of shell commands in the WP-CLI container of the given environment.
 *
 * All commands are chained with `&&` and run in a single container call, so a failing
 * command aborts the rest.
 *
 * @param {string[]} commands    The shell commands to run.
 * @param {string}   environment The `wp-env` environment, either `tests` or `development`.
 *
 * @return {void}
 */
function runWpCli( commands, environment = 'tests' ) {
	execFileSync(
		'npx',
		[ 'wp-env', 'run', `${ environment }-cli`, '--', 'sh', '-c', commands.join( ' && ' ) ],
		{ cwd: PLUGIN_ROOT, stdio: 'inherit' }
	);
}

module.exports = { runWpCli };
