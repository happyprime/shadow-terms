<?php
/**
 * Seeds demo content for local Shadow Terms testing.
 *
 * Run with `npm run env:seed`. Safe to run more than once: posts are matched
 * by title and only created when missing.
 *
 * @package shadow-terms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns the ID of a post with a title, creating the post if it does not exist.
 *
 * @param string $post_type The post type.
 * @param string $title     The post title.
 * @param string $content   The post content.
 * @param string $status    The post status for a new post.
 * @return int The post ID, or 0 on failure.
 */
function shadow_terms_seed_post( string $post_type, string $title, string $content, string $status = 'publish' ): int {
	$existing = get_posts(
		array(
			'post_type'      => $post_type,
			'post_status'    => 'any',
			'title'          => $title,
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);

	if ( $existing ) {
		return (int) $existing[0];
	}

	$post_id = wp_insert_post(
		wp_slash(
			array(
				'post_type'    => $post_type,
				'post_title'   => $title,
				'post_content' => $content,
				'post_status'  => $status,
				'post_author'  => 1,
			)
		)
	);

	return is_wp_error( $post_id ) ? 0 : (int) $post_id;
}

/**
 * Wraps text in a paragraph block.
 *
 * @param string $text The paragraph text.
 * @return string Block markup.
 */
function shadow_terms_seed_paragraph( string $text ): string {
	return "<!-- wp:paragraph -->\n<p>" . esc_html( $text ) . "</p>\n<!-- /wp:paragraph -->";
}

/**
 * Associates a post with organizations through the plugin's REST endpoint.
 *
 * The endpoint stores the association on a draft organization, so it is
 * restored when that organization is published.
 *
 * @param int   $post_id          The post or person ID.
 * @param int[] $organization_ids The organization IDs.
 */
function shadow_terms_seed_associate( int $post_id, array $organization_ids ): void {
	foreach ( $organization_ids as $organization_id ) {
		$request = new WP_REST_Request( 'POST', '/shadow-terms/v1/associate' );
		$request->set_param( 'postId', $organization_id );
		$request->set_param( 'associatedPostId', $post_id );

		$response = rest_do_request( $request );

		if ( $response->is_error() ) {
			WP_CLI::warning( "Associating {$post_id} with {$organization_id} failed." );
		}
	}
}

wp_set_current_user( 1 );

switch_theme( 'twentytwentyfive' );
update_option( 'permalink_structure', '/%postname%/' );
flush_rewrite_rules( false );

$shadow_terms_orgs = array(
	'acme'     => shadow_terms_seed_post( 'organization', 'Acme Corporation', shadow_terms_seed_paragraph( 'Acme makes everything. Its shadow term is created when this post is published.' ) ),
	'globex'   => shadow_terms_seed_post( 'organization', 'Globex', shadow_terms_seed_paragraph( 'Globex is a published organization with several associated posts and people.' ) ),
	'initech'  => shadow_terms_seed_post( 'organization', 'Initech', shadow_terms_seed_paragraph( 'Initech is a published organization with one associated post.' ) ),
	'umbrella' => shadow_terms_seed_post( 'organization', 'Umbrella Labs', shadow_terms_seed_paragraph( 'Umbrella Labs is a draft. It has no shadow term until it is published, but posts can already be associated with it.' ), 'draft' ),
);

$shadow_terms_people = array(
	'ada'   => shadow_terms_seed_post( 'person', 'Ada Lovelace', shadow_terms_seed_paragraph( 'Associated with Acme Corporation.' ) ),
	'grace' => shadow_terms_seed_post( 'person', 'Grace Hopper', shadow_terms_seed_paragraph( 'Associated with Acme Corporation and Globex.' ) ),
	'alan'  => shadow_terms_seed_post( 'person', 'Alan Turing', shadow_terms_seed_paragraph( 'Not associated with any organization.' ) ),
);

$shadow_terms_posts = array(
	'office'      => shadow_terms_seed_post( 'post', 'Acme opens a new office', shadow_terms_seed_paragraph( 'Associated with Acme Corporation.' ) ),
	'partnership' => shadow_terms_seed_post( 'post', 'Globex and Initech announce a partnership', shadow_terms_seed_paragraph( 'Associated with Globex and Initech.' ) ),
	'roundup'     => shadow_terms_seed_post( 'post', 'Quarterly roundup', shadow_terms_seed_paragraph( 'Associated with Acme Corporation, Globex, and the draft Umbrella Labs.' ) ),
);

shadow_terms_seed_associate( $shadow_terms_people['ada'], array( $shadow_terms_orgs['acme'] ) );
shadow_terms_seed_associate( $shadow_terms_people['grace'], array( $shadow_terms_orgs['acme'], $shadow_terms_orgs['globex'] ) );
shadow_terms_seed_associate( $shadow_terms_posts['office'], array( $shadow_terms_orgs['acme'] ) );
shadow_terms_seed_associate( $shadow_terms_posts['partnership'], array( $shadow_terms_orgs['globex'], $shadow_terms_orgs['initech'] ) );
shadow_terms_seed_associate( $shadow_terms_posts['roundup'], array( $shadow_terms_orgs['acme'], $shadow_terms_orgs['globex'], $shadow_terms_orgs['umbrella'] ) );

WP_CLI::success( 'Seeded organizations, people, and posts. Visit ' . get_permalink( $shadow_terms_orgs['acme'] ) );
