<?php
/**
 * Class TestTaxonomyRegistration
 *
 * Tests the registration of shadow taxonomies for post types.
 *
 * @package shadow-terms
 */

/**
 * Test the registration of shadow taxonomies for post types.
 */
class TestTaxonomyRegistration extends WP_UnitTestCase {
	/**
	 * Test a post type that has declared support for shadow terms in
	 * the register_post_type() arguments.
	 */
	public function test_post_type_with_register_post_type_support_registers_shadow_taxonomy(): void {
		$this->assertTrue( taxonomy_exists( 'example_connect' ) );
	}

	/**
	 * Test a post type that has declared support for shadow terms in
	 * add_post_type_support().
	 */
	public function test_post_type_with_add_post_type_support_registers_shadow_taxonomy(): void {
		$this->assertTrue( taxonomy_exists( 'another-example_connect' ) );
	}

	/**
	 * Test a post type that has not declared support for shadow terms.
	 */
	public function test_post_type_with_no_support_has_no_shadow_taxonomy(): void {
		$this->assertFalse( taxonomy_exists( 'unexample_connect' ) );
	}
}
