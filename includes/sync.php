<?php
/**
 * Manage the synchronization of shadow terms and posts.
 *
 * @package shadow-terms
 */

namespace ShadowTerms\Sync;

add_action( 'wp_after_insert_post', __NAMESPACE__ . '\sync_shadow_taxonomies', 10, 4 );
add_action( 'deleted_post', __NAMESPACE__ . '\delete_term', 10, 2 );

/**
 * Associate terms from registered "shadow" taxonomies with the titles of
 * published posts with support for those taxonomies.
 *
 * @since 1.0.0
 *
 * @param int           $post_id     The post ID.
 * @param \WP_Post      $post_after  The post object after the update.
 * @param bool          $update      Whether this is an update of an existing post.
 * @param null|\WP_Post $post_before The post object before the update.
 */
function sync_shadow_taxonomies( int $post_id, \WP_Post $post_after, bool $update, $post_before ): void {
	// The post before object may be null for new posts, so juggle that
	// possibility to capture status and title before doing anything else.
	$status_before = null === $post_before ? '' : $post_before->post_status;
	$status_after  = $post_after->post_status;
	$title_before  = null === $post_before ? '' : $post_before->post_title;
	$title_after   = $post_after->post_title;
	$slug_before   = null === $post_before ? '' : $post_before->post_name;
	$slug_after    = $post_after->post_name;

	// One version of the post must be published for us to make a change.
	if ( ! in_array( 'publish', array( $status_before, $status_after ), true ) ) {
		return;
	}

	if ( ! post_type_supports( $post_after->post_type, 'shadow-terms' ) ) {
		return;
	}

	// Assume any taxonomy with a `_connect` suffix is a shadow taxonomy.
	$taxonomy = "{$post_after->post_type}_connect";

	// Bail if this post type's shadow taxonomy has not been registered.
	if ( ! taxonomy_exists( $taxonomy ) ) {
		return;
	}

	// Collect any existing terms associated with the post's title(s).
	$term_before = '' === $title_before ? false : get_term_by( 'name', $title_before, $taxonomy );
	$term_after  = '' === $title_after ? false : get_term_by( 'name', $title_after, $taxonomy );

	// If a post transitioned from not-published to published, associate a term with the new title.
	if ( 'publish' !== $status_before && 'publish' === $status_after && false === $term_after ) {
		// Determine if any previous connections can be restored.
		$existing_associations = (array) get_post_meta( $post_id, "{$taxonomy}_associated_posts", true );
		$existing_associations = array_filter( $existing_associations );

		$new_term = wp_insert_term( $title_after, $taxonomy );

		foreach ( $existing_associations as $association ) {
			wp_set_object_terms( $association, $new_term['term_id'], $taxonomy );
		}

		return;
	}

	$changed = $title_before !== $title_after || $slug_before !== $slug_after;

	// If a post is to remain published, but the title or slug has changed, update the term.
	if ( $term_before && 'publish' === $post_after->post_status && $changed ) {
		wp_update_term(
			$term_before->term_id,
			$taxonomy,
			array(
				'name' => $title_after,
				'slug' => $slug_after,
			)
		);

		return;
	}

	// If the post transitioned from published to not published, remove the associated term.
	if ( 'publish' !== $status_after && $term_before ) {
		$associated_posts = get_objects_in_term( $term_before->term_id, $taxonomy );

		update_post_meta( $post_id, "{$taxonomy}_associated_posts", $associated_posts );
		wp_delete_term( $term_before->term_id, $taxonomy );

		return;
	}
}

/**
 * Delete a post's shadow term when the post is deleted.
 *
 * @since 1.0.0
 *
 * @param int      $post_id The post ID.
 * @param \WP_Post $post    The post object.
 */
function delete_term( int $post_id, \WP_Post $post ): void {
	if ( ! post_type_supports( $post->post_type, 'shadow-terms' ) ) {
		return;
	}

	$taxonomy = "{$post->post_type}_connect";

	// Bail if this post type's shadow taxonomy has not been registered.
	if ( ! taxonomy_exists( $taxonomy ) ) {
		return;
	}

	$term = get_term_by( 'slug', $post->post_name, $taxonomy );

	if ( $term ) {
		wp_delete_term( $term->term_id, $taxonomy );
	}
}
