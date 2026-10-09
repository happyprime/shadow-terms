# Shadow Terms

Use terms from generated taxonomies to associate related content.

## Description

Shadow Terms registers custom (shadow) taxonomies for supported post types. These taxonomies can be used to associate related content from a variety of post types.

When a new post of a supported post type is created, a term mirroring that post is also created. When editing another post type that supports this taxonomy, this term can be assigned to associate the posts.

Shadow Terms does not register support for itself on any post types by default. Custom code must be added to a plugin or theme.

Support can be added to a custom post type with code like:

```php
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
```

With the example above, whenever an `organization` is created, a term with the same name will be created under the `organization_connect` taxonomy. When a person or press release is edited, that term will be available for assignment through standard WordPress taxonomy interfaces.

Code can then be written to query and display all people or press releases related to an organization.

## Development

Requires Docker, Node 20 or later, and Composer.

```sh
composer install
npm install
npm run env:start
```

The site runs at http://localhost:8940 (`admin` / `password`) on WordPress 7.1 with Twenty Twenty-Five. `.dev/mu-plugins/shadow-terms-demo.php` registers `organization` and `person` post types and adds Shadow Terms support so posts and people can be assigned an organization. `.dev/seed.php` creates four organizations (Umbrella Labs is a draft), three people, and three posts associated with them. Each organization page lists its related posts and people.

Checks:

```sh
composer phpcs
composer phpstan
npm run lint:package
npm run env:test:start
npm run test:php
```

PHPUnit runs in a second environment on port 8941 so it does not reset the demo site. `npm run env:stop` and `npm run env:test:stop` stop the environments.

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

* Do not show "Add New" term option for shadow taxonomies, which are automatically managed. Thanks [@s3rgiosan](https://github.com/s3rgiosan)!
* Do not show shadow terms in REST API to unauthenticated users if their original post type is not publicly available via REST endpoint.

### 1.1.0

* Add filtering to shadow taxonomy taxonomy arguments.
* Update development tooling.

### 1.0.1

* Fix: Ensure term and post slugs sync properly on post update.

### 1.0.0

Initial release.
