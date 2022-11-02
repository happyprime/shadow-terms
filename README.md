# Shadow Terms

## Description

Automatically create taxonomies for post types and use terms to associate related content.

## Usage

* First, add a shadow taxonomy by adding support to a post type. Here, we're creating a post type called Example. Every time an Example post is published, it will automatically generate a Shadow Term in the `example_connect` taxonomy.

```
<?php
// Add 'shadow-terms' to your 'supports' array.
$args = array(
	'supports' => array( 'title', 'editor', 'thumbnail', 'revisions', 'shadow-terms' ),
);
register_post_type( 'example', $args );
?>
```

* Then, enable the new taxonomy on other post types. Here, we're enabling Shadow Terms on the default Post and Page post types.

```
<?php
add_post_type_support( 'example', 'shadow-terms', array( 'post', 'page' ) );
?>
```

* Now, you can add a few Example posts. Once those are published, edit a Post or Page, and you'll be able to select any of the Example post titles to link your Post or Page to the Example.

## Installation

1. Upload `shadow-terms.zip` to the `/wp-content/plugins/` directory.
2. Activate the plugin through WordPress's 'Plugins' menu.

## Changelog

### 0.0.2

* Added usage instructions.

### 0.0.1

* Initial release.
