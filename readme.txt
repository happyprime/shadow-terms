# Plugin Name
Contributors: happyprime, jeremyfelt, slocker, philcable, wpgirl369
Tags: terms, related, content
Requires at least: 5.9
Tested up to: 6.2
Stable tag: 1.0.0
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

## Changelog

### 1.0.0

Initial release.
