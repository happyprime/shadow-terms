<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package shadow-terms
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
}

// Forward custom PHPUnit Polyfills configuration to PHPUnit bootstrap file.
$_phpunit_polyfills_path = getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
if ( false !== $_phpunit_polyfills_path ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_phpunit_polyfills_path ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
}

if ( ! file_exists( "{$_tests_dir}/includes/functions.php" ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once "{$_tests_dir}/includes/functions.php";

tests_add_filter( 'init', '_register_test_post_types' );
/**
 * Setup example post types that support or do not
 * support shadow terms.
 */
function _register_test_post_types(): void { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	register_post_type(
		'example',
		array(
			'label'    => 'Example',
			'public'   => true,
			'supports' => array(
				'title',
				'editor',
				'shadow-terms' => array( 'post' ),
			),
		)
	);

	register_post_type(
		'unexample',
		array(
			'label'    => 'Example',
			'public'   => true,
			'supports' => array(
				'title',
				'editor',
			),
		)
	);

	register_post_type(
		'another-example',
		array(
			'label'    => 'Another Example',
			'public'   => true,
			'supports' => array(
				'title',
				'editor',
			),
		)
	);

	add_post_type_support( 'another-example', 'shadow-terms', array( 'post' ) );
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );
/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin(): void { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	require dirname( __DIR__ ) . '/plugin.php';
}

// Start up the WP testing environment.
require "{$_tests_dir}/includes/bootstrap.php";
