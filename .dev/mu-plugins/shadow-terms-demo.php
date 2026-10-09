<?php
/**
 * Plugin Name: Shadow Terms demo
 * Description: Registers demo post types with Shadow Terms support for local development.
 *
 * @package shadow-terms
 */

namespace ShadowTerms\Demo;

add_action( 'init', __NAMESPACE__ . '\register_post_types' );
add_action( 'init', __NAMESPACE__ . '\register_templates' );
add_filter( 'shadow_terms_register_taxonomy_args', __NAMESPACE__ . '\filter_taxonomy_args', 10, 2 );
add_filter( 'pre_render_block', __NAMESPACE__ . '\filter_related_query', 15, 2 );

/**
 * Registers the organization and person post types.
 */
function register_post_types(): void {
	register_post_type(
		'organization',
		array(
			'label'        => 'Organizations',
			'labels'       => array(
				'name'          => 'Organizations',
				'singular_name' => 'Organization',
			),
			'public'       => true,
			'show_in_rest' => true,
			'has_archive'  => 'organizations',
			'menu_icon'    => 'dashicons-building',
			'supports'     => array( 'title', 'editor', 'excerpt' ),
		)
	);

	register_post_type(
		'person',
		array(
			'label'        => 'People',
			'labels'       => array(
				'name'          => 'People',
				'singular_name' => 'Person',
			),
			'public'       => true,
			'show_in_rest' => true,
			'has_archive'  => 'people',
			'menu_icon'    => 'dashicons-groups',
			'supports'     => array( 'title', 'editor', 'excerpt' ),
		)
	);

	// Posts and people can be assigned an organization's shadow term.
	add_post_type_support( 'organization', 'shadow-terms', array( 'post', 'person' ) );
}

/**
 * Makes the organization shadow taxonomy available to the Query block's taxonomy filter.
 *
 * @param array<string, mixed> $args      Shadow taxonomy arguments.
 * @param string               $post_type The shadowed post type.
 * @return array<string, mixed> Modified arguments.
 */
function filter_taxonomy_args( array $args, string $post_type ): array {
	if ( 'organization' === $post_type ) {
		$args['publicly_queryable'] = true;
	}

	return $args;
}

/**
 * Registers a single organization template that lists related posts and people.
 */
function register_templates(): void {
	if ( ! function_exists( 'register_block_template' ) ) {
		return;
	}

	$related_query = static function ( string $post_type, string $heading ): string {
		return '<!-- wp:heading -->
<h2 class="wp-block-heading">' . $heading . '</h2>
<!-- /wp:heading -->

<!-- wp:query {"query":{"perPage":10,"pages":0,"offset":0,"postType":"' . $post_type . '","order":"desc","orderBy":"date","inherit":false},"namespace":"shadow-terms/related"} -->
<div class="wp-block-query"><!-- wp:post-template -->
<!-- wp:post-title {"level":3,"isLink":true} /-->
<!-- /wp:post-template -->

<!-- wp:query-no-results -->
<!-- wp:paragraph -->
<p>Nothing is associated with this organization yet.</p>
<!-- /wp:paragraph -->
<!-- /wp:query-no-results --></div>
<!-- /wp:query -->';
	};

	register_block_template(
		'shadow-terms-demo//single-organization',
		array(
			'title'       => 'Single Organization',
			'description' => 'Shows an organization with the posts and people associated with it.',
			'post_types'  => array( 'organization' ),
			'content'     => '<!-- wp:template-part {"slug":"header"} /-->

<!-- wp:group {"tagName":"main","layout":{"type":"constrained"}} -->
<main class="wp-block-group"><!-- wp:post-title {"level":1} /-->

<!-- wp:post-content {"layout":{"type":"constrained"}} /-->

' . $related_query( 'post', 'Related posts' ) . '

' . $related_query( 'person', 'People' ) . '</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer"} /-->',
		)
	);
}

/**
 * Limits a Query block with the shadow-terms/related namespace to posts that
 * share the current post's shadow term.
 *
 * This is the example from the plugin README.
 *
 * @param string|null          $pre_render   The pre-rendered content.
 * @param array<string, mixed> $parsed_block The parsed block.
 * @return string|null The unchanged pre-rendered content.
 */
function filter_related_query( ?string $pre_render, array $parsed_block ): ?string {
	if ( 'shadow-terms/related' !== ( $parsed_block['attrs']['namespace'] ?? false ) ) {
		return $pre_render;
	}

	if ( ! function_exists( 'ShadowTerms\API\get_taxonomy_slug' ) ) {
		return $pre_render;
	}

	$post_id = get_queried_object_id();

	$callback = function ( array $query ) use ( $post_id ): array {
		$taxonomy = \ShadowTerms\API\get_taxonomy_slug( $post_id );
		$term_id  = $taxonomy ? \ShadowTerms\API\get_term_id( $post_id ) : 0;

		if ( ! $term_id ) {
			$query['post__in'] = array( 0 );

			return $query;
		}

		$query['tax_query'][] = array(
			'taxonomy' => $taxonomy,
			'field'    => 'term_id',
			'terms'    => $term_id,
		);

		return $query;
	};

	add_filter( 'query_loop_block_query_vars', $callback );

	// Remove the query filter once this Query block and its inner blocks render.
	add_filter(
		'render_block_core/query',
		function ( string $block_content, array $block ) use ( $callback ): string {
			if ( 'shadow-terms/related' === ( $block['attrs']['namespace'] ?? false ) ) {
				remove_filter( 'query_loop_block_query_vars', $callback );
			}

			return $block_content;
		},
		10,
		2
	);

	return $pre_render;
}
