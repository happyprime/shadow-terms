<?php
/**
 * Manage the automated taxonomies created to hold shadow terms.
 *
 * @package shadow-terms
 */

namespace ShadowTerms\Taxonomy;

add_action( 'init', __NAMESPACE__ . '\register', 9999 );

/**
 * Register all shadow taxonomies.
 */
function register() {
	$post_types = get_post_types_by_support( 'shadow-terms' );

	foreach ( $post_types as $post_type ) {
		register_taxonomy( $post_type );
	}
}

/**
 * Retrieve a list of post types that a shadow taxonomy supports.
 *
 * @param string $post_type The post type connected with the shadow taxonomy.
 * @return string[] A list of post types that support assignment of terms in
 *               the shadow taxonomy.
 */
function get_connected_post_types( string $post_type ) : array {
	$supports = get_all_post_type_supports( $post_type );

	if ( is_array( $supports['shadow-terms'] ) && is_array( $supports['shadow-terms'][0] ) ) {
		return $supports['shadow-terms'][0];
	}

	return array();
}

/**
 * Register a single post type's shadow taxonomy.
 *
 * @param string $post_type The post type from which a shadow taxonomy should
 *                          be registered.
 */
function register_taxonomy( string $post_type ) {
	$post_type_object = get_post_type_object( $post_type );

	$args = array(
		'label'              => $post_type_object->label,
		'labels'             => $post_type_object->labels,
		'description'        => $post_type_object->description,
		'public'             => false,
		'publicly_queryable' => false,
		'rewrite'            => false,
		'hierarchical'       => true,
		'show_ui'            => true,
		'show_in_menu'       => false,
		'show_in_nav_menus'  => false,
		'show_in_rest'       => true,
		'show_admin_column'  => true,
	);

	\register_taxonomy(
		$post_type . '_connect',
		get_connected_post_types( $post_type ),
		$args
	);
}
