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
 *
 * Registration fires at priority 9999 so that we have the best opportunity to
 * catch all post types with registered support without going overboard. If a
 * post type registers itself after priority 9999, it should manually register
 * its shadow taxonomy with `ShadowTerms\Taxonomy\register_taxonomy()`.
 *
 * @since 1.0.0
 */
function register(): void {
	$post_types = get_post_types_by_support( 'shadow-terms' );

	foreach ( $post_types as $post_type ) {
		register_taxonomy( $post_type );
	}
}

/**
 * Register Shadow Term REST routes.
 *
 * @since 1.0.0
 */
function register_route(): void {
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
 * Register a single post type's shadow taxonomy.
 *
 * @since 1.0.0
 *
 * @param string $post_type The post type from which a shadow taxonomy should
 *                          be registered.
 */
function register_taxonomy( string $post_type ): void {
	$post_type_object = get_post_type_object( $post_type );

	$args = array(
		'label'              => $post_type_object->label,
		'labels'             => $post_type_object->labels,
		'description'        => $post_type_object->description,
		'public'             => false,
		'publicly_queryable' => false,
		'rewrite'            => false,
		'hierarchical'       => true,
		'capabilities'       => array(
			'manage_terms' => 'override_shadow_terms',
			'edit_terms'   => 'override_shadow_terms',
			'delete_terms' => 'override_shadow_terms',
		),
		'show_ui'            => true,
		'show_in_menu'       => false,
		'show_in_nav_menus'  => false,
		'show_in_rest'       => true,
		'show_admin_column'  => true,
	);

	// If a post type is not publicly queryable and not visible in the REST API,
	// we should not expose that post type's shadow terms to unauthorized users.
	if ( false === $post_type_object->publicly_queryable && false === $post_type_object->show_in_rest && ! is_user_logged_in() ) {
		$args['show_in_rest'] = false;
	}

	/**
	 * Filter the arguments used to register a shadow taxonomy.
	 *
	 * @since 1.1.0
	 *
	 * @param array  $args      The arguments used to register the taxonomy.
	 * @param string $post_type The post type this taxonomy is shadowing.
	 */
	$args = apply_filters( 'shadow_terms_register_taxonomy_args', $args, $post_type );

	\register_taxonomy(
		$post_type . '_connect',
		API\get_connected_post_types( $post_type ),
		$args
	);
}

/**
 * Determine whether the current user can associated shadow terms with posts.
 *
 * @since 1.0.0
 *
 * @return bool True if capable. False if not.
 */
function can_associate_posts(): bool {
	return current_user_can( 'edit_posts' );
}

/**
 * Handle the submission of a post association via REST request.
 *
 * @since 1.0.0
 *
 * @param \WP_REST_Request $request The request to associate posts.
 * @return \WP_REST_Response The response data.
 */
function handle_rest_associate( \WP_REST_Request $request ): \WP_REST_Response {
	$post_id            = (int) $request->get_param( 'postId' );
	$associated_post_id = (int) $request->get_param( 'associatedPostId' );
	$taxonomy_slug      = API\get_taxonomy_slug( $post_id );

	if ( ! $taxonomy_slug ) {
		return rest_ensure_response(
			[
				'success' => false,
				'message' => 'This post type is not associated with a shadow taxonomy.',
				'posts'   => [],
			]
		);
	}

	$post = get_post( $post_id );

	if ( 'publish' !== $post->post_status ) {
		$associated_posts = get_post_meta( $post_id, $taxonomy_slug . '_associated_posts', true );

		if ( ! is_array( $associated_posts ) ) {
			$associated_posts = [];
		}

		$associated_posts = array_map( 'intval', $associated_posts );

		if ( ! in_array( $associated_post_id, $associated_posts, true ) ) {
			$associated_posts[] = $associated_post_id;
			update_post_meta( $post_id, $taxonomy_slug . '_associated_posts', $associated_posts );
		}

		return rest_ensure_response(
			[
				'success' => true,
				'message' => '',
				'posts'   => $associated_posts,
			]
		);
	}

	wp_set_object_terms( $associated_post_id, API\get_term_id( $post_id ), API\get_taxonomy_slug( $post_id ) );

	$associated_post  = get_post( $associated_post_id );
	$associated_posts = new \WP_Query(
		[
			'fields'                 => 'ids',
			'post_type'              => $associated_post->post_type,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_term_meta_cache' => false,
			'tax_query'              => [
				[
					'taxonomy' => API\get_taxonomy_slug( $post_id ),
					'field'    => 'term_id',
					'terms'    => [ API\get_term_id( $post_id ) ],
				],
			],
			'post_status'            => [ 'publish', 'draft' ],
		]
	);

	return rest_ensure_response(
		[
			'success' => true,
			'message' => '',
			'posts'   => $associated_posts->posts,
		]
	);
}
