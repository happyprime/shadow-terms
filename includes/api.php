<?php
/**
 * Provide functions for interacting with shadow terms.
 *
 * @package shadow-terms
 */

namespace ShadowTerms\API;

/**
 * Retrieve a post's shadow taxonomy slug.
 *
 * @param int $post_id The ID of the post.
 * @return string The shadow taxonomy slug. Empty if not found.
 */
function get_taxonomy_slug( int $post_id ) : string {
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
 * @param int $post_id The post ID.
 * @return int The term ID. 0 if not available.
 */
function get_term_id( int $post_id ) : int {
	$post = get_post( $post_id );

	if ( ! $post ) {
		return 0;
	}

	$taxonomy = get_taxonomy_slug( $post_id );

	if ( '' === $taxonomy ) {
		return 0;
	}

	$term = get_term_by( 'slug', $post->post_name, $taxonomy );

	if ( ! $term ) {
		return 0;
	}

	return (int) $term->term_id;
}

/**
 * Retrieve a list of associated posts stored when a post is
 * in a non-published state.
 *
 * @param int $post_id The post ID.
 * @return array A list of associated posts.
 */
function get_associated_posts( int $post_id ) : array {
	$taxonomy_slug = get_taxonomy_slug( $post_id );

	if ( '' === $taxonomy_slug ) {
		return [];
	}

	$posts = get_post_meta( $post_id, $taxonomy_slug . '_associated_posts', true );

	if ( ! $posts ) {
		return [];
	}

	$posts = array_map( 'intval', $posts );

	return (array) $posts;
}

/**
 * Update a list of a post's associated posts.
 *
 * @param int        $post_id The original post.
 * @param array[int] $posts   A list of associated post IDs.
 */
function update_associated_posts( int $post_id, array $posts ) : void {
	$taxonomy_slug = get_taxonomy_slug( $post_id );

	if ( '' === $taxonomy_slug ) {
		return;
	}

	update_post_meta( $post_id, $taxonomy_slug . '_associated_posts', $posts );
}
