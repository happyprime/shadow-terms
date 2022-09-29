<?php
/**
 * Manage the automated taxonomies created to hold shadow terms.
 *
 * @package shadow-terms
 */

namespace ShadowTerms\Taxonomy;

use ShadowTerms\API;

add_action( 'init', __NAMESPACE__ . '\register', 9999 );
add_action( 'rest_api_init', __NAMESPACE__ . '\register_route' );

/**
 * Register all shadow taxonomies.
 */
function register() {
	$post_types = get_post_types_by_support( 'shadow-terms' );

	foreach ( $post_types as $post_type ) {
		register_taxonomy( $post_type );
		register_rest_fields( $post_type );
	}
}

/**
 * Register Shadow Term REST routes.
 */
function register_route() {
	// Register a REST route used to associate posts with a post's shadow term.
	register_rest_route(
		'shadow-terms/v1',
		'associate',
		array(
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => __NAMESPACE__ . '\handle_rest_associate',
			'permission_callback' => __NAMESPACE__ . '\can_associate_posts',
		)
	);
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

/**
 * Determine whether a user can associated shadow terms with posts.
 *
 * @return bool True if capable. False if not.
 */
function can_associate_posts() : bool {
	return current_user_can( 'edit_posts' );
}

/**
 * Handle the submission of a post association via REST request.
 *
 * @param \WP_REST_Request $request The vote submission request.
 * @return \WP_REST_Response The response data.
 */
function handle_rest_associate( \WP_REST_Request $request ) : \WP_REST_Response {
	$post_id            = (int) $request->get_param( 'postId' );
	$associated_post_id = (int) $request->get_param( 'associatedPostId' );

	if ( ! API\get_taxonomy_slug( $post_id ) ) {
		return rest_ensure_response(
			[
				'success' => false,
				'message' => 'This post type is not associated with a shadow taxonomy.',
			]
		);
	}

	$post = get_post( $post_id );

	if ( 'publish' !== $post->post_status ) {
		$associated_posts = API\get_associated_posts( $post_id );

		if ( ! in_array( $associated_post_id, $associated_posts, true ) ) {
			$associated_posts[] = $associated_post_id;
			API\update_associated_posts( $post_id, $associated_posts );
		}

		return rest_ensure_response( [] );
	}

	wp_set_object_terms( $associated_post_id, API\get_term_id( $post_id ), API\get_taxonomy_slug( $post_id ) );

	return rest_ensure_response( [] );
}

/**
 * Register a field on the post type's REST response for the shadow term ID.
 *
 * @param string $post_type The post type.
 */
function register_rest_fields( string $post_type ) {

	// Register a field on each post type to provide a list of associated posts
	// when the original post is in a non-published state.
	register_rest_field(
		$post_type,
		'shadowTermPosts',
		[
			'get_callback' => __NAMESPACE__ . '\populate_associated_posts',
		]
	);

	// Register a field on each post type to provide the post's corresponding
	// shadow term ID when it is in a published state.
	register_rest_field(
		$post_type,
		'shadowTermId',
		[
			'get_callback' => __NAMESPACE__ . '\populate_shadow_term_id',
		]
	);

	// Register a field on each post type to provide its shadow taxonomy slug.
	register_rest_field(
		$post_type,
		'shadowTermSlug',
		[
			'get_callback' => __NAMESPACE__ . '\populate_shadow_term_slug',
		]
	);
}

/**
 * Populate the post's associated posts as part of the REST response.
 *
 * @param array $post The post data as built for the response.
 * @return array A list of associated posts.
 */
function populate_associated_posts( array $post ) : array {
	$posts = get_post_meta( $post['id'], API\get_taxonomy_slug( $post['id'] ) . '_associated_posts', true );

	if ( $posts ) {
		return (array) $posts;
	}

	return [];
}

/**
 * Populate the post's shadow term ID as part of the REST response.
 *
 * @param array $post The post data as built for the response.
 * @return int The shadow term ID.
 */
function populate_shadow_term_id( array $post ) : int {
	return API\get_term_id( $post['id'] );
}

/**
 * Populate the post type's shadow taxonomy slug as part of the REST response.
 *
 * @param array $post The post data as built for the response.
 * @return string The shadow taxonomy slug.
 */
function populate_shadow_term_slug( array $post ) : string {
	return API\get_taxonomy_slug( $post['id'] );
}
