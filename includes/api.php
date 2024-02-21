<?php
/**
 * Provide functions for interacting with shadow terms.
 *
 * @package shadow-terms
 */

namespace ShadowTerms\API;

/**
 * Determine if a taxonomy is a shadow taxonomy.
 *
 * @param  string|\WP_Taxonomy $taxonomy Name of taxonomy, or the taxonomy object.
 * @return bool
 */
function is_shadow_taxonomy( $taxonomy ) {

	if ( $taxonomy instanceof \WP_Taxonomy ) {
		$taxonomy = $taxonomy->name;
	}

	$is_shadow_taxonomy = strcasecmp( substr( $taxonomy, -8 ), '_connect' ) === 0;

	return $is_shadow_taxonomy;
}

/**
 * Retrieve a post's shadow taxonomy slug.
 *
 * @since 1.0.0
 *
 * @param int $post_id The ID of the post.
 * @return string The shadow taxonomy slug. Empty if not found.
 */
function get_taxonomy_slug( int $post_id ): string {
	$post = get_post( $post_id );

	if ( ! $post ) {
		return '';
	}

	$taxonomy = $post->post_type . '_connect';

	if ( ! taxonomy_exists( $taxonomy ) ) {
		return '';
	}

	return $taxonomy;
}

/**
 * Retrieve a post's shadow term ID.
 *
 * @since 1.0.0
 *
 * @param int $post_id The post ID.
 * @return int The term ID. 0 if not available.
 */
function get_term_id( int $post_id ): int {
	$post = get_post( $post_id );

	if ( ! $post ) {
		return 0;
	}

	$taxonomy = get_taxonomy_slug( $post_id );

	if ( '' === $taxonomy ) {
		return 0;
	}

	$term = get_term_by( 'name', $post->post_title, $taxonomy );

	if ( ! $term ) {
		return 0;
	}

	return (int) $term->term_id;
}

/**
 * Retrieve a shadow term's associated post ID.
 *
 * @since 1.0.0
 *
 * @param int $term_id The shadow term ID.
 * @return int The post ID. 0 if not found.
 */
function get_post_id( int $term_id ): int {
	$term = get_term( $term_id );

	if ( ! $term ) {
		return 0;
	}

	$post_type = str_replace( '_connect', '', $term->taxonomy );

	// If `_connect` was not part of the taxonomy or the taxonomy is
	// not registered, there will be no associated post.
	if ( $post_type === $term->taxonomy || ! post_type_exists( $post_type ) ) {
		return 0;
	}

	$query = new \WP_Query(
		[
			'post_type'              => $post_type,
			'title'                  => $term->name,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_term_meta_cache' => false,
		]
	);

	return (int) array_pop( $query->posts );
}

/**
 * Retrieve a list of post types that a shadow taxonomy supports.
 *
 * @since 1.0.0
 *
 * @param string $post_type The post type connected with the shadow taxonomy.
 * @return string[] A list of post types that support assignment of terms in
 *               the shadow taxonomy.
 */
function get_connected_post_types( string $post_type ): array {
	$supports = get_all_post_type_supports( $post_type );

	if ( is_array( $supports['shadow-terms'] ) && is_array( $supports['shadow-terms'][0] ) ) {
		return $supports['shadow-terms'][0];
	}

	return array();
}
