<?php
/**
 * Class TestTermSync
 *
 * Tests the synchronization of shadow terms with posts.
 *
 * @package shadow-terms
 */

/**
 * Test the registration of shadow taxonomies for post types.
 */
class TestTermSync extends WP_UnitTestCase {

	/**
	 * A newly created post with an initial status of publish should generate a shadow term.
	 */
	public function test_post_new_to_publish_creates_term(): void {
		$this->factory()->post->create(
			array(
				'post_type'   => 'example',
				'post_title'  => 'Apple',
				'post_status' => 'publish',
			)
		);

		$term      = get_term_by( 'slug', 'apple', 'example_connect', 'OBJECT' );
		$term_name = ! $term ? '' : $term->name;

		$this->assertEquals( 'Apple', $term_name, 'A newly created post with an initial status of publish should generate a shadow term.' );
	}

	/**
	 * An existing draft post that is published should generate a shadow term.
	 */
	public function test_post_draft_to_publish_creates_term(): void {
		$post = $this->factory()->post->create(
			array(
				'post_type'   => 'example',
				'post_title'  => 'Bean',
				'post_status' => 'draft',
			)
		);
		$post = get_post( $post );

		$post->post_status = 'publish';
		wp_update_post( $post );

		$term      = get_term_by( 'slug', 'bean', 'example_connect', 'OBJECT' );
		$term_name = ! $term ? '' : $term->name;

		$this->assertEquals( 'Bean', $term_name, 'An existing draft post that is published should generate a shadow term.' );
	}

	/**
	 * A shadow term should be deleted when its connected post is deleted.
	 */
	public function test_post_publish_to_delete_removes_term(): void {
		$post = $this->factory()->post->create(
			array(
				'post_type'   => 'example',
				'post_title'  => 'Corn',
				'post_status' => 'publish',
			)
		);
		$post = get_post( $post );
		$term = get_term_by( 'slug', 'corn', 'example_connect', 'OBJECT' );

		wp_delete_post( $post->ID, true );

		$term = get_term( $term->term_id );

		$this->assertEquals( null, $term, 'A shadow term should be deleted when its connected post is deleted.' );
	}

	/**
	 * A shadow term should be deleted when its connected post is moved from publish to draft.
	 */
	public function test_post_publish_to_draft_removes_term(): void {
		$post = $this->factory()->post->create(
			array(
				'post_type'   => 'example',
				'post_title'  => 'Daikon',
				'post_status' => 'publish',
			)
		);
		$post = get_post( $post );
		$term = get_term_by( 'slug', 'daikon', 'example_connect', 'OBJECT' );

		$post->post_status = 'draft';
		wp_update_post( $post );

		$term = get_term( $term->term_id );

		$this->assertNull( $term, 'A shadow term should be deleted when its connected post is moved from publish to draft.' );
	}

	/**
	 * Existing shadow term relationships should be stored when its connected post is moved from publish to draft.
	 */
	public function test_post_publish_to_draft_preserves_relationships(): void {
		$post = $this->factory()->post->create(
			array(
				'post_type'   => 'example',
				'post_title'  => 'Zebra',
				'post_status' => 'publish',
			)
		);
		$post = get_post( $post );
		$term = get_term_by( 'slug', 'zebra', 'example_connect', 'OBJECT' );

		$associated_post = $this->factory()->post->create(
			array(
				'post_type'    => 'post',
				'post_title'   => 'ABC Test 129',
				'post_content' => 'Everything and nothing.',
			)
		);

		wp_set_object_terms( $associated_post, $term->term_id, 'example_connect' );

		$post->post_status = 'draft';
		wp_update_post( $post );

		$associated_posts = (array) get_post_meta( $post->ID, 'example_connect_associated_posts', true );

		$this->assertEquals( array( $associated_post ), $associated_posts, 'Existing shadow term relationships should be stored when its conncted post is moved from publish to draft.' );
	}

	/**
	 * A shadow term should be deleted when its connected post is moved from publish to pending.
	 */
	public function test_post_publish_to_pending_removes_term(): void {
		$post = $this->factory()->post->create(
			array(
				'post_type'   => 'example',
				'post_title'  => 'Elderberry',
				'post_status' => 'publish',
			)
		);
		$post = get_post( $post );
		$term = get_term_by( 'slug', 'elderberry', 'example_connect', 'OBJECT' );

		$post->post_status = 'pending';
		wp_update_post( $post );

		$term = get_term( $term->term_id );

		$this->assertNull( $term, 'A shadow term should be deleted when its connected post is moved from publish to pending.' );
	}

	/**
	 * Existing shadow term relationships should be stored when its connected post is moved from publish to pending.
	 */
	public function test_post_publish_to_pending_preserves_relationships(): void {
		$post = $this->factory()->post->create(
			array(
				'post_type'   => 'example',
				'post_title'  => 'Yellow',
				'post_status' => 'publish',
			)
		);
		$post = get_post( $post );
		$term = get_term_by( 'slug', 'yellow', 'example_connect', 'OBJECT' );

		$associated_post = $this->factory()->post->create(
			array(
				'post_type'    => 'post',
				'post_title'   => 'ABC Test 128',
				'post_content' => 'Everything and nothing.',
			)
		);

		wp_set_object_terms( $associated_post, $term->term_id, 'example_connect' );

		$post->post_status = 'pending';
		wp_update_post( $post );

		$associated_posts = (array) get_post_meta( $post->ID, 'example_connect_associated_posts', true );

		$this->assertEquals( array( $associated_post ), $associated_posts, 'Existing shadow term relationships should be stored when its conncted post is moved from publish to pending.' );
	}

	/**
	 * A shadow term should be deleted when its connected post is moved from publish to private.
	 */
	public function test_post_publish_to_private_removes_term(): void {
		$post = $this->factory()->post->create(
			array(
				'post_type'   => 'example',
				'post_title'  => 'Xylophone',
				'post_status' => 'publish',
			)
		);
		$post = get_post( $post );
		$term = get_term_by( 'slug', 'xylophone', 'example_connect', 'OBJECT' );

		$post->post_status = 'private';
		wp_update_post( $post );

		$term = get_term( $term->term_id );

		$this->assertNull( $term, 'A shadow term should be deleted when its connected post is moved from publish to private.' );
	}

	/**
	 * Existing shadow term relationships should be stored when its conncted post is moved from publish to private.
	 */
	public function test_post_publish_to_private_preserves_relationships(): void {
		$post = $this->factory()->post->create(
			array(
				'post_type'   => 'example',
				'post_title'  => 'French Fry',
				'post_status' => 'publish',
			)
		);
		$post = get_post( $post );
		$term = get_term_by( 'slug', 'french-fry', 'example_connect', 'OBJECT' );

		$associated_post = $this->factory()->post->create(
			array(
				'post_type'    => 'post',
				'post_title'   => 'ABC Test 127',
				'post_content' => 'Everything and nothing.',
			)
		);

		wp_set_object_terms( $associated_post, $term->term_id, 'example_connect' );

		$post->post_status = 'private';
		wp_update_post( $post );

		$associated_posts = (array) get_post_meta( $post->ID, 'example_connect_associated_posts', true );

		$this->assertEquals( array( $associated_post ), $associated_posts, 'Existing shadow term relationships should be stored when its conncted post is moved from publish to private.' );
	}

	/**
	 * Existing shadow term relationships should be restored when its connected post is moved from draft to publish.
	 */
	public function test_post_draft_to_publish_creates_term_and_restores_relationships(): void {
		$post = $this->factory()->post->create(
			array(
				'post_type'   => 'example',
				'post_title'  => 'Wrapper',
				'post_status' => 'publish',
			)
		);
		$post = get_post( $post );
		$term = get_term_by( 'slug', 'wrapper', 'example_connect', 'OBJECT' );

		$associated_post = $this->factory()->post->create(
			array(
				'post_type'    => 'post',
				'post_title'   => 'ABC Test 126',
				'post_content' => 'Everything and nothing.',
			)
		);

		wp_set_object_terms( $associated_post, $term->term_id, 'example_connect' );

		$post->post_status = 'draft';
		wp_update_post( $post );

		$associated_posts = (array) get_post_meta( $post->ID, 'example_connect_associated_posts', true );

		$post->post_status = 'publish';
		wp_update_post( $post );

		$associated_terms = wp_get_object_terms( $associated_posts, 'example_connect' );
		$associated_terms = wp_list_pluck( $associated_terms, 'slug' );

		$this->assertEquals( array( $term->slug ), $associated_terms, 'Existing shadow term relationships should be restored when its connected post is moved from draft to publish.' );
	}

	/**
	 * Existing shadow term relationships should be restored when its connected post is moved from pending to publish.
	 */
	public function test_post_pending_to_publish_creates_term_and_restores_relationships(): void {
		$post = $this->factory()->post->create(
			array(
				'post_type'   => 'example',
				'post_title'  => 'Viola',
				'post_status' => 'publish',
			)
		);
		$post = get_post( $post );
		$term = get_term_by( 'slug', 'viola', 'example_connect', 'OBJECT' );

		$associated_post = $this->factory()->post->create(
			array(
				'post_type'    => 'post',
				'post_title'   => 'ABC Test 125',
				'post_content' => 'Everything and nothing.',
			)
		);

		wp_set_object_terms( $associated_post, $term->term_id, 'example_connect' );

		$post->post_status = 'pending';
		wp_update_post( $post );

		$associated_posts = (array) get_post_meta( $post->ID, 'example_connect_associated_posts', true );

		$post->post_status = 'publish';
		wp_update_post( $post );

		$associated_terms = wp_get_object_terms( $associated_posts, 'example_connect' );
		$associated_terms = wp_list_pluck( $associated_terms, 'slug' );

		$this->assertEquals( array( $term->slug ), $associated_terms, 'Existing shadow term relationships should be restored when its connected post is moved from pending to publish.' );
	}

	/**
	 * Existing shadow term relationships should be restored when its connected post is moved from private to publish.
	 */
	public function test_post_private_to_publish_creates_term_and_restores_relationships(): void {
		$post = $this->factory()->post->create(
			array(
				'post_type'   => 'example',
				'post_title'  => 'Umbrella',
				'post_status' => 'publish',
			)
		);
		$post = get_post( $post );
		$term = get_term_by( 'slug', 'umbrella', 'example_connect', 'OBJECT' );

		$associated_post = $this->factory()->post->create(
			array(
				'post_type'    => 'post',
				'post_title'   => 'ABC Test 124',
				'post_content' => 'Everything and nothing.',
			)
		);

		wp_set_object_terms( $associated_post, $term->term_id, 'example_connect' );

		$post->post_status = 'private';
		wp_update_post( $post );

		$associated_posts = (array) get_post_meta( $post->ID, 'example_connect_associated_posts', true );

		$post->post_status = 'publish';
		wp_update_post( $post );

		$associated_terms = wp_get_object_terms( $associated_posts, 'example_connect' );
		$associated_terms = wp_list_pluck( $associated_terms, 'slug' );

		$this->assertEquals( array( $term->slug ), $associated_terms, 'Existing shadow term relationships should be restored when its connected post is moved from private to publish.' );
	}

	/**
	 * Existing shadow term relationships should be restored when its connected post is moved from trash to publish.
	 */
	public function test_post_trash_to_publish_creates_term_and_restores_relationships(): void {
		$post = $this->factory()->post->create(
			array(
				'post_type'   => 'example',
				'post_title'  => 'Tasty',
				'post_status' => 'publish',
			)
		);
		$post = get_post( $post );
		$term = get_term_by( 'slug', 'tasty', 'example_connect', 'OBJECT' );

		$associated_post = $this->factory()->post->create(
			array(
				'post_type'    => 'post',
				'post_title'   => 'ABC Test 123',
				'post_content' => 'Everything and nothing.',
			)
		);

		wp_set_object_terms( $associated_post, $term->term_id, 'example_connect' );

		$post->post_status = 'trash';
		wp_update_post( $post );

		$associated_posts = (array) get_post_meta( $post->ID, 'example_connect_associated_posts', true );

		$post->post_status = 'publish';
		wp_update_post( $post );

		$associated_terms = wp_get_object_terms( $associated_posts, 'example_connect' );
		$associated_terms = wp_list_pluck( $associated_terms, 'slug' );

		$this->assertEquals( array( $term->slug ), $associated_terms, 'Existing shadow term relationships should be restored when its connected post is moved from trash to publish.' );
	}

	/**
	 * An existing published post that has its title changed should change the
	 * title of its shadow term.
	 */
	public function test_post_publish_to_publish_modified_title_updates_term(): void {
		$post = $this->factory()->post->create(
			array(
				'post_type'   => 'example',
				'post_title'  => 'Garbanzo Bean',
				'post_status' => 'publish',
			)
		);
		$post = get_post( $post );

		$post->post_title = 'Chickpea';
		wp_update_post( $post );

		// A term should exist for the original slug.
		$term      = get_term_by( 'slug', 'garbanzo-bean', 'example_connect', 'OBJECT' );
		$term_name = ! $term ? '' : $term->name;

		// And that term's name should match the updated post title.
		$this->assertEquals( 'Chickpea', $term_name, 'A connected term name should update when a post title is updated, but the term slug should remain the same.' );
	}

	/**
	 * An existing published post that has its slug changed should change the
	 * slug of its shadow term.
	 */
	public function test_post_publish_to_publish_modified_slug_updates_term(): void {
		$post = $this->factory()->post->create(
			array(
				'post_type'   => 'example',
				'post_title'  => 'Garbanzo Bean',
				'post_status' => 'publish',
			)
		);
		$post = get_post( $post );

		$post->post_name = 'chickpea';
		wp_update_post( $post );

		// A term should exist for the new slug.
		$term      = get_term_by( 'slug', 'chickpea', 'example_connect', 'OBJECT' );
		$term_name = ! $term ? '' : $term->name;

		// And that term's name should still match the unmodified post title.
		$this->assertEquals( 'Garbanzo Bean', $term_name, 'A connected term slug should update when a post slug is updated.' );
	}

	/**
	 * An existing published post that has its title changed should change the
	 * title of its shadow term.
	 */
	public function test_post_publish_to_publish_modified_title_and_slug_updates_term(): void {
		$post = $this->factory()->post->create(
			array(
				'post_type'   => 'example',
				'post_title'  => 'Garbanzo Bean',
				'post_status' => 'publish',
			)
		);
		$post = get_post( $post );

		$post->post_title = 'Chickpea';
		$post->post_name  = 'chickpea';
		wp_update_post( $post );

		// A term should exist for the new slug.
		$term      = get_term_by( 'slug', 'chickpea', 'example_connect', 'OBJECT' );
		$term_name = ! $term ? '' : $term->name;

		// And that term's name should match the updated post's title.
		$this->assertEquals( 'Chickpea', $term_name, 'A connected term slug and term name should update when its post title and slug is updated.' );
	}
}
