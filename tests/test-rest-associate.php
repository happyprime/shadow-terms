<?php
/**
 * Class TestRestAssociate
 *
 * Tests request validation and permissions for the associate endpoint.
 *
 * @package shadow-terms
 */

/**
 * Test request validation and permissions for the associate endpoint.
 */
class TestRestAssociate extends WP_UnitTestCase {

	/**
	 * REST server used to dispatch requests.
	 *
	 * @var WP_REST_Server
	 */
	protected $rest_server;

	/**
	 * Sets up a REST server so the associate route can be dispatched.
	 */
	public function set_up(): void {
		parent::set_up();

		global $wp_rest_server;
		$wp_rest_server    = new WP_REST_Server();
		$this->rest_server = $wp_rest_server;
		do_action( 'rest_api_init' );
	}

	/**
	 * Removes the REST server created for each test.
	 */
	public function tear_down(): void {
		global $wp_rest_server;
		$wp_rest_server = null;

		parent::tear_down();
	}

	/**
	 * Creates a user with a role and returns the ID.
	 *
	 * @param string $role The user's role.
	 * @return int The user ID.
	 */
	private function create_user( string $role ): int {
		$user_id = $this->factory()->user->create( array( 'role' => $role ) );

		if ( is_wp_error( $user_id ) ) {
			$this->fail( "Failed to create {$role} user." );
		}

		return $user_id;
	}

	/**
	 * Creates a published post and returns the ID.
	 *
	 * @param string $post_type  The post type.
	 * @param string $post_title The post title.
	 * @param int    $author     The post author ID.
	 * @return int The post ID.
	 */
	private function create_post( string $post_type, string $post_title, int $author = 0 ): int {
		$post_id = $this->factory()->post->create(
			array(
				'post_type'   => $post_type,
				'post_title'  => $post_title,
				'post_status' => 'publish',
				'post_author' => $author,
			)
		);

		if ( is_wp_error( $post_id ) ) {
			$this->fail( "Failed to create {$post_title} post." );
		}

		return $post_id;
	}

	/**
	 * Dispatches an associate request.
	 *
	 * @param array<string, mixed> $params The request parameters.
	 * @return WP_REST_Response The response.
	 */
	private function associate( array $params ): WP_REST_Response {
		$request = new WP_REST_Request( 'POST', '/shadow-terms/v1/associate' );

		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		return $this->rest_server->dispatch( $request );
	}

	/**
	 * Returns the slugs of a post's example_connect terms.
	 *
	 * @param int $post_id The post ID.
	 * @return string[] The term slugs.
	 */
	private function term_slugs( int $post_id ): array {
		$terms = wp_get_object_terms( $post_id, 'example_connect', array( 'fields' => 'slugs' ) );

		if ( is_wp_error( $terms ) ) {
			$this->fail( 'Failed to read terms.' );
		}

		return array_map( 'strval', $terms );
	}

	/**
	 * A request missing either ID is rejected before it reaches the handler.
	 */
	public function test_missing_ids_are_rejected(): void {
		wp_set_current_user( $this->create_user( 'editor' ) );

		$this->assertSame( 400, $this->associate( array() )->get_status() );
		$this->assertSame(
			400,
			$this->associate(
				array(
					'postId'           => 0,
					'associatedPostId' => 0,
				)
			)->get_status()
		);
	}

	/**
	 * A contributor cannot attach a shadow term to a post they cannot edit.
	 */
	public function test_contributor_cannot_associate_another_users_post(): void {
		$acme_id    = $this->create_post( 'example', 'Acme' );
		$article_id = $this->create_post( 'post', 'Article', $this->create_user( 'editor' ) );

		wp_set_current_user( $this->create_user( 'contributor' ) );

		$response = $this->associate(
			array(
				'postId'           => $acme_id,
				'associatedPostId' => $article_id,
			)
		);

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( array(), $this->term_slugs( $article_id ) );
	}

	/**
	 * An author can attach a shadow term to their own post.
	 */
	public function test_author_can_associate_own_post(): void {
		$author_id  = $this->create_user( 'author' );
		$acme_id    = $this->create_post( 'example', 'Acme' );
		$article_id = $this->create_post( 'post', 'Article', $author_id );

		wp_set_current_user( $author_id );

		$response = $this->associate(
			array(
				'postId'           => $acme_id,
				'associatedPostId' => $article_id,
			)
		);

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( array( 'acme' ), $this->term_slugs( $article_id ) );
	}

	/**
	 * A post that does not exist cannot be associated.
	 */
	public function test_missing_associated_post_is_rejected(): void {
		$acme_id = $this->create_post( 'example', 'Acme' );

		wp_set_current_user( $this->create_user( 'editor' ) );

		$response = $this->associate(
			array(
				'postId'           => $acme_id,
				'associatedPostId' => PHP_INT_MAX,
			)
		);

		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * A shadow post that does not exist returns a failure instead of an error.
	 */
	public function test_missing_shadow_post_reports_failure(): void {
		$article_id = $this->create_post( 'post', 'Article' );

		wp_set_current_user( $this->create_user( 'editor' ) );

		$response = $this->associate(
			array(
				'postId'           => PHP_INT_MAX,
				'associatedPostId' => $article_id,
			)
		);

		$data = (array) $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertFalse( $data['success'] );
	}

	/**
	 * A post type that is not connected to the shadow taxonomy is not given a term.
	 */
	public function test_unconnected_post_type_is_not_associated(): void {
		$acme_id = $this->create_post( 'example', 'Acme' );
		$page_id = $this->create_post( 'page', 'About' );

		wp_set_current_user( $this->create_user( 'editor' ) );

		$response = $this->associate(
			array(
				'postId'           => $acme_id,
				'associatedPostId' => $page_id,
			)
		);

		$data = (array) $response->get_data();

		$this->assertFalse( $data['success'] );
		$this->assertSame( array(), $this->term_slugs( $page_id ) );
	}
}
