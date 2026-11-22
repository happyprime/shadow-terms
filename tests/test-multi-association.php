<?php
/**
 * Class TestMultiAssociation
 *
 * Regression tests covering the three call sites where `wp_set_object_terms`
 * was invoked without `$append = true`, which silently destroyed existing
 * shadow-term associations on the connected post.
 *
 * Each test exercises one of the three call sites:
 *   - includes/sync.php:73     (restore loop on draft → publish)
 *   - includes/sync.php:110    (recovery loop when the shadow term is missing)
 *   - includes/taxonomy.php:170 (REST `associate` endpoint for a published shadow post)
 *
 * @package shadow-terms
 */

/**
 * Test that managing one shadow term preserves a connected post's other
 * shadow-term associations in the same taxonomy.
 */
class TestMultiAssociation extends WP_UnitTestCase {

	/**
	 * REST server used to dispatch requests in REST-level tests.
	 *
	 * @var WP_REST_Server|null
	 */
	protected $rest_server;

	/**
	 * Spin up a REST server so the `associate` route can be dispatched.
	 */
	public function set_up(): void {
		parent::set_up();

		global $wp_rest_server;
		$wp_rest_server    = new WP_REST_Server();
		$this->rest_server = $wp_rest_server;
		do_action( 'rest_api_init' );
	}

	/**
	 * Tear down the REST server created for each test.
	 */
	public function tear_down(): void {
		global $wp_rest_server;
		$wp_rest_server    = null;
		$this->rest_server = null;

		parent::tear_down();
	}

	/**
	 * Create a post and fail the test if creation returns an error.
	 *
	 * @param string $post_type   The post type to create.
	 * @param string $post_title  The post title.
	 * @param string $post_status The post status.
	 * @return int The new post ID.
	 */
	private function create_post( string $post_type, string $post_title, string $post_status = 'publish' ): int {
		$post_id = $this->factory()->post->create(
			array(
				'post_type'   => $post_type,
				'post_title'  => $post_title,
				'post_status' => $post_status,
			)
		);

		if ( is_wp_error( $post_id ) ) {
			$this->fail( "Failed to create {$post_title} post." );
		}

		return $post_id;
	}

	/**
	 * Return a connected post's `example_connect` term slugs, sorted.
	 *
	 * @param int $post_id The connected post ID.
	 * @return string[] The associated term slugs.
	 */
	private function associated_slugs( int $post_id ): array {
		$terms = wp_get_object_terms( $post_id, 'example_connect' );

		if ( is_wp_error( $terms ) ) {
			$this->fail( 'Failed to read associated terms.' );
		}

		$slugs = wp_list_pluck( $terms, 'slug' );
		sort( $slugs );

		return $slugs;
	}

	/**
	 * Restore loop on draft → publish should preserve other associations
	 * (sync.php:73).
	 *
	 * When a shadow post returns to published, the plugin re-attaches the new
	 * shadow term to every previously associated post. That loop must append the
	 * term so it does not replace the connected post's other `example_connect`
	 * terms.
	 */
	public function test_restore_on_publish_preserves_other_associations(): void {
		$acme_id = $this->create_post( 'example', 'Acme' );
		$this->create_post( 'example', 'Globex' );

		$acme_term   = get_term_by( 'slug', 'acme', 'example_connect' );
		$globex_term = get_term_by( 'slug', 'globex', 'example_connect' );

		if ( ! $acme_term || ! $globex_term ) {
			$this->fail( 'Expected shadow terms for both example posts.' );
		}

		$article_id = $this->create_post( 'post', 'Article' );

		wp_set_object_terms(
			$article_id,
			array( (int) $acme_term->term_id, (int) $globex_term->term_id ),
			'example_connect'
		);

		$this->assertSame(
			array( 'acme', 'globex' ),
			$this->associated_slugs( $article_id ),
			'Sanity: article should start with both shadow terms attached.'
		);

		// Acme → draft. The plugin deletes the acme shadow term and stores the
		// list of previously-associated posts in postmeta on the acme post.
		$acme              = get_post( $acme_id );
		$acme->post_status = 'draft';
		wp_update_post( $acme );

		// Acme → publish. The restore loop in sync.php should re-attach the new
		// acme term to the article WITHOUT wiping its globex association.
		$acme->post_status = 'publish';
		wp_update_post( $acme );

		$this->assertSame(
			array( 'acme', 'globex' ),
			$this->associated_slugs( $article_id ),
			'Restoring a shadow post should not wipe the connected post\'s other shadow-term associations.'
		);
	}

	/**
	 * Recovery loop when the shadow term is missing should preserve other
	 * associations (sync.php:110).
	 *
	 * If a published shadow post has lost its term (e.g., manually deleted in
	 * admin) and is saved again, the plugin recreates the term and restores
	 * known associations. That restore loop must append rather than replace.
	 */
	public function test_missing_term_recovery_preserves_other_associations(): void {
		$acme_id = $this->create_post( 'example', 'Acme' );
		$this->create_post( 'example', 'Globex' );

		$acme_term   = get_term_by( 'slug', 'acme', 'example_connect' );
		$globex_term = get_term_by( 'slug', 'globex', 'example_connect' );

		if ( ! $acme_term || ! $globex_term ) {
			$this->fail( 'Expected shadow terms for both example posts.' );
		}

		$article_id = $this->create_post( 'post', 'Article' );

		wp_set_object_terms(
			$article_id,
			array( (int) $acme_term->term_id, (int) $globex_term->term_id ),
			'example_connect'
		);

		// Simulate the missing-term precondition. Deleting the term also
		// detaches it from the article, so after this call the article has
		// only the globex term — that's the state the recovery loop must
		// not destroy.
		wp_delete_term( $acme_term->term_id, 'example_connect' );

		// Seed the prior-association meta on the acme post so the recovery
		// loop in sync.php has something to restore.
		update_post_meta( $acme_id, 'example_connect_associated_posts', array( $article_id ) );

		// publish → publish with the term missing triggers the recovery branch.
		wp_update_post( get_post( $acme_id ) );

		$this->assertSame(
			array( 'acme', 'globex' ),
			$this->associated_slugs( $article_id ),
			'Recovering a missing shadow term should not wipe other shadow-term associations on the connected post.'
		);
	}

	/**
	 * REST `associate` endpoint should preserve prior associations
	 * (taxonomy.php:170).
	 *
	 * Associating a connected post with a second published shadow post via the
	 * REST endpoint must be additive rather than replacing the first
	 * association.
	 */
	public function test_rest_associate_preserves_prior_associations(): void {
		$editor_id = $this->factory()->user->create( array( 'role' => 'editor' ) );

		if ( is_wp_error( $editor_id ) ) {
			$this->fail( 'Failed to create editor user.' );
		}

		wp_set_current_user( $editor_id );

		$acme_id    = $this->create_post( 'example', 'Acme' );
		$globex_id  = $this->create_post( 'example', 'Globex' );
		$article_id = $this->create_post( 'post', 'Article' );

		// First association: Article ↔ Acme.
		$request = new WP_REST_Request( 'POST', '/shadow-terms/v1/associate' );
		$request->set_param( 'postId', $acme_id );
		$request->set_param( 'associatedPostId', $article_id );

		$response = $this->rest_server->dispatch( $request );
		$this->assertSame( 200, $response->get_status(), 'First associate call should succeed.' );

		// Second association: Article ↔ Globex. This should be additive.
		$request = new WP_REST_Request( 'POST', '/shadow-terms/v1/associate' );
		$request->set_param( 'postId', $globex_id );
		$request->set_param( 'associatedPostId', $article_id );

		$response = $this->rest_server->dispatch( $request );
		$this->assertSame( 200, $response->get_status(), 'Second associate call should succeed.' );

		$this->assertSame(
			array( 'acme', 'globex' ),
			$this->associated_slugs( $article_id ),
			'Associating a connected post with a second shadow term via REST should not wipe the prior association.'
		);
	}

	/**
	 * REST `associate` endpoint should recreate a missing term rather than
	 * silently succeeding (taxonomy.php).
	 *
	 * If a published shadow post's term has been deleted and the post has not
	 * been re-saved, the term cannot be resolved. The endpoint must recreate it
	 * and attach it instead of reporting success while attaching nothing.
	 */
	public function test_rest_associate_recreates_missing_published_term(): void {
		$editor_id = $this->factory()->user->create( array( 'role' => 'editor' ) );

		if ( is_wp_error( $editor_id ) ) {
			$this->fail( 'Failed to create editor user.' );
		}

		wp_set_current_user( $editor_id );

		$acme_id    = $this->create_post( 'example', 'Acme' );
		$article_id = $this->create_post( 'post', 'Article' );

		// Delete the term but leave the post published and un-saved, so the sync
		// recovery branch never runs and the term stays missing.
		$acme_term = get_term_by( 'slug', 'acme', 'example_connect' );

		if ( ! $acme_term ) {
			$this->fail( 'Expected an acme shadow term.' );
		}

		wp_delete_term( $acme_term->term_id, 'example_connect' );

		$request = new WP_REST_Request( 'POST', '/shadow-terms/v1/associate' );
		$request->set_param( 'postId', $acme_id );
		$request->set_param( 'associatedPostId', $article_id );

		$response = $this->rest_server->dispatch( $request );
		$this->assertSame( 200, $response->get_status(), 'Associate call should return 200.' );

		$data = $response->get_data();
		$this->assertTrue(
			is_array( $data ) && true === $data['success'],
			'Associate should report success only after the missing term is recreated.'
		);

		$this->assertSame(
			array( 'acme' ),
			$this->associated_slugs( $article_id ),
			'A recreated shadow term should actually be attached to the connected post.'
		);
	}
}
