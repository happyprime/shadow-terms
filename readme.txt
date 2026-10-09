# Shadow Terms
Contributors: happyprime, jeremyfelt, slocker, philcable, wpgirl369
Tags: terms, related, content
Requires at least: 5.9
Tested up to: 7.1
Stable tag: 1.2.3
License: GPLv2 or later
Requires PHP: 7.4

Use terms from generated taxonomies to associate related content.

## Description

Shadow Terms registers custom (shadow) taxonomies for supported post types. These taxonomies can be used to associate related content from a variety of post types.

When a new post of a supported post type is created, a term mirroring that post is also created. When editing another post type that supports this taxonomy, this term can be assigned to associate the posts.

Shadow Terms does not register support for itself on any post types by default. Custom code must be added to a plugin or theme.

Support can be added to a custom post type with code like:

	<?php
	// Register the organization post type normally.
	register_post_type( 'organization', $args );

	// Add support for Shadow Terms to the organization post type.
	add_post_type_support(
		'organization',
		'shadow-terms',
		array(
			// Add post types that support the organization_connect taxonomy.
			'person',
			'press-release',
		)
	);

With the example above, whenever an `organization` is created, a term with the same name will be created under the `organization_connect` taxonomy. When a person or press release is edited, that term will be available for assignment through standard WordPress taxonomy interfaces.

Code can then be written to query and display all people or press releases related to an organization.

## Frequently Asked Questions

### Why don't existing posts have shadow terms?

A term is created when a post of a supported type is published, or saved while published. Posts published before support was added get their term the next time they are saved. To create terms for many posts at once, select them on the post list screen, choose **Edit** from **Bulk actions**, and click **Update** without changing anything.

Draft, pending, and private posts have no term.

### Where do I manage shadow terms?

Assign them from the taxonomy panel when editing a connected post type. Shadow Terms creates, renames, and deletes the terms as their posts change, so the term management screen requires the `override_shadow_terms` capability, which no role has by default.

### How do I list the posts associated with a post?

`ShadowTerms\API\get_taxonomy_slug()` and `ShadowTerms\API\get_term_id()` take a post ID. In a classic theme's `single-organization.php`:

	$taxonomy = \ShadowTerms\API\get_taxonomy_slug( get_the_ID() );
	$term_id  = \ShadowTerms\API\get_term_id( get_the_ID() );

	if ( $term_id ) {
		$people = new WP_Query(
			array(
				'post_type' => 'person',
				'tax_query' => array(
					array(
						'taxonomy' => $taxonomy,
						'field'    => 'term_id',
						'terms'    => $term_id,
					),
				),
			)
		);
	}

### How do I filter a Query block by a shadow taxonomy?

The Query block only offers taxonomies that are publicly queryable, and shadow taxonomies are not. Change that with the `shadow_terms_register_taxonomy_args` filter:

	add_filter(
		'shadow_terms_register_taxonomy_args',
		function ( array $args, string $post_type ): array {
			if ( 'organization' === $post_type ) {
				$args['publicly_queryable'] = true;
			}

			return $args;
		},
		10,
		2
	);

This also lets front-end requests filter by the taxonomy, such as `?organization_connect=acme`.

### How do I show related posts in a block theme template?

A Query block cannot select the current post's shadow term by itself. In the template (for example `single-organization.html`), give the Query block a `namespace` attribute using the code editor:

	<!-- wp:query {"namespace":"shadow-terms/related","query":{"postType":"person","inherit":false}} -->

Then limit that block's query to posts with the current post's term:

	add_filter( 'pre_render_block', 'myplugin_filter_related_query', 10, 2 );

	function myplugin_filter_related_query( ?string $pre_render, array $parsed_block ): ?string {
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

The site editor previews the block unfiltered. The front end shows only related posts.

## Changelog

### 1.2.3

* In a case where a published post is missing its associated shadow term, create one on post update.
* Bail early on direct access to plugin files.
* Confirm WordPress 6.9 support.
* Update development dependencies.

### 1.2.2

* No functional changes.
* Bump phpstan to level 7.
* Update development dependencies.
* Confirm WordPress 6.8 support.

### 1.2.1

* No functional changes.
* Exclude phpstan config from distribution.
* Update development dependencies.
* Confirm WordPress 6.6 support.

### 1.2.0

* Do not show "Add New" term option for shadow taxonomies, which are automatically managed. Thanks [@s3rgiosan](https://profiles.wordpress.org/s3rgiosan/)!
* Do not show shadow terms in REST API to unauthenticated users if their original post type is not publicly available via REST endpoint.

### 1.1.0

* Add filtering to shadow taxonomy taxonomy arguments.
* Update development tooling.

### 1.0.1

* Fix: Ensure term and post slugs sync properly on post update.

### 1.0.0

Initial release.
