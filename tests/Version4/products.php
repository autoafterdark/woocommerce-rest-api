<?php
/**
 * Products REST API tests.
 *
 * @package WooCommerce/RestApi/Tests
 */

namespace WooCommerce\RestApi\Tests\Version4;

defined( 'ABSPATH' ) || exit;

use \WooCommerce\RestApi\Tests\AbstractRestApiTest;

/**
 * Abstract Rest API Test Class
 *
 * @extends AbstractRestApiTest
 */
class Products extends AbstractRestApiTest {
	/**
	 * Routes that this endpoint creates.
	 *
	 * @var array
	 */
	protected $routes = [
		'/wc/v4/products',
		'/wc/v4/products/(?P<id>[\d]+)',
		'/wc/v4/products/batch',
	];

	/**
	 * Test creation using this method.
	 * If read-only, test to confirm this.
	 */
	public function test_create() {

	}

	/**
	 * Test get/read using this method.
	 */
	public function test_read() {

	}

	/**
	 * Test updates using this method.
	 * If read-only, test to confirm this.
	 */
	public function test_update() {

	}

	/**
	 * Test delete using this method.
	 * If read-only, test to confirm this.
	 */
	public function test_delete() {

	}

	/**
	 * Test getting products.
	 *
	 * @since 3.5.0
	 */
	public function test_get_products() {
		wp_set_current_user( $this->user );
		WC_Helper_Product::create_external_product();
		sleep( 1 ); // So both products have different timestamps.
		WC_Helper_Product::create_simple_product();
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v4/products' ) );
		$products = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$this->assertEquals( 2, count( $products ) );
		$this->assertEquals( 'Dummy Product', $products[0]['name'] );
		$this->assertEquals( 'DUMMY SKU', $products[0]['sku'] );
		$this->assertEquals( 'Dummy External Product', $products[1]['name'] );
		$this->assertEquals( 'DUMMY EXTERNAL SKU', $products[1]['sku'] );
	}

	/**
	 * Test getting products without permission.
	 *
	 * @since 3.5.0
	 */
	public function test_get_products_without_permission() {
		wp_set_current_user( 0 );
		WC_Helper_Product::create_simple_product();
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v4/products' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test getting a single product.
	 *
	 * @since 3.5.0
	 */
	public function test_get_product() {
		wp_set_current_user( $this->user );
		$simple   = WC_Helper_Product::create_external_product();
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v4/products/' . $simple->get_id() ) );
		$product  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertContains(
			array(
				'id'            => $simple->get_id(),
				'name'          => 'Dummy External Product',
				'type'          => 'simple',
				'status'        => 'publish',
				'sku'           => 'DUMMY EXTERNAL SKU',
				'regular_price' => 10,
			),
			$product
		);
	}

	/**
	 * Test getting single product without permission.
	 *
	 * @since 3.5.0
	 */
	public function test_get_product_without_permission() {
		wp_set_current_user( 0 );
		$product  = WC_Helper_Product::create_simple_product();
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v4/products/' . $product->get_id() ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test deleting a single product.
	 *
	 * @since 3.5.0
	 */
	public function test_delete_product() {
		wp_set_current_user( $this->user );
		$product = WC_Helper_Product::create_simple_product();

		$request = new WP_REST_Request( 'DELETE', '/wc/v4/products/' . $product->get_id() );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$response   = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v4/products' ) );
		$variations = $response->get_data();
		$this->assertEquals( 0, count( $variations ) );
	}

	/**
	 * Test deleting a single product without permission.
	 *
	 * @since 3.5.0
	 */
	public function test_delete_product_without_permission() {
		wp_set_current_user( 0 );
		$product = WC_Helper_Product::create_simple_product();
		$request = new WP_REST_Request( 'DELETE', '/wc/v4/products/' . $product->get_id() );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test deleting a single product with an invalid ID.
	 *
	 * @since 3.5.0
	 */
	public function test_delete_product_with_invalid_id() {
		wp_set_current_user( 0 );
		$request = new WP_REST_Request( 'DELETE', '/wc/v4/products/0' );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test editing a single product. Tests multiple product types.
	 *
	 * @since 3.5.0
	 */
	public function test_update_product() {
		wp_set_current_user( $this->user );

		// test simple products.
		$product      = WC_Helper_Product::create_simple_product();
		$response     = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v4/products/' . $product->get_id() ) );
		$data         = $response->get_data();
		$date_created = date( 'Y-m-d\TH:i:s', current_time( 'timestamp' ) );

		$this->assertEquals( 'DUMMY SKU', $data['sku'] );
		$this->assertEquals( 10, $data['regular_price'] );
		$this->assertEmpty( $data['sale_price'] );

		$request = new WP_REST_Request( 'PUT', '/wc/v4/products/' . $product->get_id() );
		$request->set_body_params(
			array(
				'sku'          => 'FIXED-SKU',
				'sale_price'   => '8',
				'description'  => 'Testing',
				'date_created' => $date_created,
				'images'       => array(
					array(
						'position' => 0,
						'src'      => 'http://cldup.com/Dr1Bczxq4q.png',
						'alt'      => 'test upload image',
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertContains( 'Testing', $data['description'] );
		$this->assertEquals( '8', $data['price'] );
		$this->assertEquals( '8', $data['sale_price'] );
		$this->assertEquals( '10', $data['regular_price'] );
		$this->assertEquals( 'FIXED-SKU', $data['sku'] );
		$this->assertEquals( $date_created, $data['date_created'] );
		$this->assertContains( 'Dr1Bczxq4q', $data['images'][0]['src'] );
		$this->assertContains( 'test upload image', $data['images'][0]['alt'] );
		$product->delete( true );

		// test variable product (variations are tested in product-variations.php).
		$product  = WC_Helper_Product::create_variation_product();
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v4/products/' . $product->get_id() ) );
		$data     = $response->get_data();

		foreach ( array( 'small', 'large' ) as $term_name ) {
			$this->assertContains( $term_name, $data['attributes'][0]['options'] );
		}

		$request = new WP_REST_Request( 'PUT', '/wc/v4/products/' . $product->get_id() );
		$request->set_body_params(
			array(
				'attributes' => array(
					array(
						'id'        => 0,
						'name'      => 'pa_color',
						'options'   => array(
							'red',
							'yellow',
						),
						'visible'   => false,
						'variation' => 1,
					),
					array(
						'id'        => 0,
						'name'      => 'pa_size',
						'options'   => array(
							'small',
						),
						'visible'   => false,
						'variation' => 1,
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( array( 'small' ), $data['attributes'][0]['options'] );

		foreach ( array( 'red', 'yellow' ) as $term_name ) {
			$this->assertContains( $term_name, $data['attributes'][1]['options'] );
		}

		$product->delete( true );

		// test external product.
		$product  = WC_Helper_Product::create_external_product();
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v4/products/' . $product->get_id() ) );
		$data     = $response->get_data();

		$this->assertEquals( 'Buy external product', $data['button_text'] );
		$this->assertEquals( 'http://woocommerce.com', $data['external_url'] );

		$request = new WP_REST_Request( 'PUT', '/wc/v4/products/' . $product->get_id() );
		$request->set_body_params(
			array(
				'button_text'  => 'Test API Update',
				'external_url' => 'http://automattic.com',
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 'Test API Update', $data['button_text'] );
		$this->assertEquals( 'http://automattic.com', $data['external_url'] );
	}

	/**
	 * Test updating a single product without permission.
	 *
	 * @since 3.5.0
	 */
	public function test_update_product_without_permission() {
		wp_set_current_user( 0 );
		$product = WC_Helper_Product::create_simple_product();
		$request = new WP_REST_Request( 'PUT', '/wc/v4/products/' . $product->get_id() );
		$request->set_body_params(
			array(
				'sku' => 'FIXED-SKU-NO-PERMISSION',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test updating a single product with an invalid ID.
	 *
	 * @since 3.5.0
	 */
	public function test_update_product_with_invalid_id() {
		wp_set_current_user( $this->user );
		$request = new WP_REST_Request( 'PUT', '/wc/v2/products/0' );
		$request->set_body_params(
			array(
				'sku' => 'FIXED-SKU-INVALID-ID',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test creating a single product.
	 *
	 * @since 3.5.0
	 */
	public function test_create_product() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', '/wc/v4/products/shipping_classes' );
		$request->set_body_params(
			array(
				'name' => 'Test',
			)
		);
		$response          = $this->server->dispatch( $request );
		$data              = $response->get_data();
		$shipping_class_id = $data['id'];

		// Create simple.
		$request = new WP_REST_Request( 'POST', '/wc/v4/products' );
		$request->set_body_params(
			array(
				'type'           => 'simple',
				'name'           => 'Test Simple Product',
				'sku'            => 'DUMMY SKU SIMPLE API',
				'regular_price'  => '10',
				'shipping_class' => 'test',
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( '10', $data['price'] );
		$this->assertEquals( '10', $data['regular_price'] );
		$this->assertTrue( $data['purchasable'] );
		$this->assertEquals( 'DUMMY SKU SIMPLE API', $data['sku'] );
		$this->assertEquals( 'Test Simple Product', $data['name'] );
		$this->assertEquals( 'simple', $data['type'] );
		$this->assertEquals( $shipping_class_id, $data['shipping_class_id'] );

		// Create external.
		$request = new WP_REST_Request( 'POST', '/wc/v4/products' );
		$request->set_body_params(
			array(
				'type'          => 'external',
				'name'          => 'Test External Product',
				'sku'           => 'DUMMY SKU EXTERNAL API',
				'regular_price' => '10',
				'button_text'   => 'Test Button',
				'external_url'  => 'https://wordpress.org',
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( '10', $data['price'] );
		$this->assertEquals( '10', $data['regular_price'] );
		$this->assertFalse( $data['purchasable'] );
		$this->assertEquals( 'DUMMY SKU EXTERNAL API', $data['sku'] );
		$this->assertEquals( 'Test External Product', $data['name'] );
		$this->assertEquals( 'external', $data['type'] );
		$this->assertEquals( 'Test Button', $data['button_text'] );
		$this->assertEquals( 'https://wordpress.org', $data['external_url'] );

		// Create variable.
		$request = new WP_REST_Request( 'POST', '/wc/v4/products' );
		$request->set_body_params(
			array(
				'type'       => 'variable',
				'name'       => 'Test Variable Product',
				'sku'        => 'DUMMY SKU VARIABLE API',
				'attributes' => array(
					array(
						'id'        => 0,
						'name'      => 'pa_size',
						'options'   => array(
							'small',
							'medium',
						),
						'visible'   => false,
						'variation' => 1,
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 'DUMMY SKU VARIABLE API', $data['sku'] );
		$this->assertEquals( 'Test Variable Product', $data['name'] );
		$this->assertEquals( 'variable', $data['type'] );
		$this->assertEquals( array( 'small', 'medium' ), $data['attributes'][0]['options'] );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v4/products' ) );
		$products = $response->get_data();
		$this->assertEquals( 3, count( $products ) );
	}

	/**
	 * Test creating a single product without permission.
	 *
	 * @since 3.5.0
	 */
	public function test_create_product_without_permission() {
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'POST', '/wc/v4/products' );
		$request->set_body_params(
			array(
				'name'          => 'Test Product',
				'regular_price' => '12',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test batch managing products.
	 *
	 * @since 3.5.0
	 */
	public function test_products_batch() {
		wp_set_current_user( $this->user );
		$product   = WC_Helper_Product::create_simple_product();
		$product_2 = WC_Helper_Product::create_simple_product();
		$request   = new WP_REST_Request( 'POST', '/wc/v4/products/batch' );
		$request->set_body_params(
			array(
				'update' => array(
					array(
						'id'          => $product->get_id(),
						'description' => 'Updated description.',
					),
				),
				'delete' => array(
					$product_2->get_id(),
				),
				'create' => array(
					array(
						'sku'           => 'DUMMY SKU BATCH TEST 1',
						'regular_price' => '10',
						'name'          => 'Test Batch Create 1',
						'type'          => 'external',
						'button_text'   => 'Test Button',
					),
					array(
						'sku'           => 'DUMMY SKU BATCH TEST 2',
						'regular_price' => '20',
						'name'          => 'Test Batch Create 2',
						'type'          => 'simple',
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertContains( 'Updated description.', $data['update'][0]['description'] );
		$this->assertEquals( 'DUMMY SKU BATCH TEST 1', $data['create'][0]['sku'] );
		$this->assertEquals( 'DUMMY SKU BATCH TEST 2', $data['create'][1]['sku'] );
		$this->assertEquals( 'Test Button', $data['create'][0]['button_text'] );
		$this->assertEquals( 'external', $data['create'][0]['type'] );
		$this->assertEquals( 'simple', $data['create'][1]['type'] );
		$this->assertEquals( $product_2->get_id(), $data['delete'][0]['id'] );

		$request  = new WP_REST_Request( 'GET', '/wc/v4/products' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 3, count( $data ) );
	}

	/**
	 * Tests to make sure you can filter products post statuses by both
	 * the status query arg and WP_Query.
	 *
	 * @since 3.5.0
	 */
	public function test_products_filter_post_status() {
		wp_set_current_user( $this->user );
		for ( $i = 0; $i < 8; $i++ ) {
			$product = WC_Helper_Product::create_simple_product();
			if ( 0 === $i % 2 ) {
				wp_update_post(
					array(
						'ID'          => $product->get_id(),
						'post_status' => 'draft',
					)
				);
			}
		}

		// Test filtering with status=publish.
		$request = new WP_REST_Request( 'GET', '/wc/v4/products' );
		$request->set_param( 'status', 'publish' );
		$response = $this->server->dispatch( $request );
		$products = $response->get_data();

		$this->assertEquals( 4, count( $products ) );
		foreach ( $products as $product ) {
			$this->assertEquals( 'publish', $product['status'] );
		}

		// Test filtering with status=draft.
		$request = new WP_REST_Request( 'GET', '/wc/v4/products' );
		$request->set_param( 'status', 'draft' );
		$response = $this->server->dispatch( $request );
		$products = $response->get_data();

		$this->assertEquals( 4, count( $products ) );
		foreach ( $products as $product ) {
			$this->assertEquals( 'draft', $product['status'] );
		}

		// Test filtering with no filters - which should return 'any' (all 8).
		$request  = new WP_REST_Request( 'GET', '/wc/v4/products' );
		$response = $this->server->dispatch( $request );
		$products = $response->get_data();

		$this->assertEquals( 8, count( $products ) );
	}

	/**
	 * Test product category.
	 *
	 * @since 3.5.0
	 */
	public function test_get_products_by_category() {
		wp_set_current_user( $this->user );

		// Create one product with a category.
		$category = wp_insert_term( 'Some Category', 'product_cat' );

		$product = new WC_Product_Simple();
		$product->set_category_ids( array( $category['term_id'] ) );
		$product->save();

		// Create one product without category, i.e. Uncategorized.
		$product_2 = new WC_Product_Simple();
		$product_2->save();

		// Test product assigned to a single category.
		$query_params = array(
			'category' => (string) $category['term_id'],
		);
		$request      = new WP_REST_Request( 'GET', '/wc/v2/products' );
		$request->set_query_params( $query_params );
		$response          = $this->server->dispatch( $request );
		$response_products = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		foreach ( $response_products as $response_product ) {
			$this->assertEquals( $product->get_id(), $response_product['id'] );
			$this->assertEquals( $product->get_category_ids(), wp_list_pluck( $response_product['categories'], 'id' ) );
		}

		// Test product without categories.
		$request          = new WP_REST_Request( 'GET', '/wc/v2/products/' . $product_2->get_id() );
		$response         = $this->server->dispatch( $request );
		$response_product = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 1, $response_product['categories'], print_r( $response_product, true ) );
		$this->assertEquals( 'uncategorized', $response_product['categories'][0]['slug'] );

	}

	/**
	 * Test getting products by product type.
	 *
	 * @since 3.5.0
	 */
	public function test_get_products_by_type() {
		wp_set_current_user( $this->user );

		$simple   = WC_Helper_Product::create_simple_product();
		$external = WC_Helper_Product::create_external_product();
		$grouped  = WC_Helper_Product::create_grouped_product();
		$variable = WC_Helper_Product::create_variation_product();

		$product_ids_for_type = array(
			'simple'   => array( $simple->get_id() ),
			'external' => array( $external->get_id() ),
			'grouped'  => array( $grouped->get_id() ),
			'variable' => array( $variable->get_id() ),
		);

		foreach ( $grouped->get_children() as $additional_product ) {
			$product_ids_for_type['simple'][] = $additional_product;
		}

		foreach ( $product_ids_for_type as $product_type => $product_ids ) {
			$query_params = array(
				'type' => $product_type,
			);
			$request      = new WP_REST_Request( 'GET', '/wc/v2/products' );
			$request->set_query_params( $query_params );
			$response          = $this->server->dispatch( $request );
			$response_products = $response->get_data();

			$this->assertEquals( 200, $response->get_status() );
			$this->assertEquals( count( $product_ids ), count( $response_products ) );
			foreach ( $response_products as $response_product ) {
				$this->assertContains( $response_product['id'], $product_ids_for_type[ $product_type ], 'REST API: ' . $product_type . ' not found correctly' );
			}
		}
	}

	/**
	 * Test getting products by featured property.
	 *
	 * @since 3.5.0
	 */
	public function test_get_featured_products() {
		wp_set_current_user( $this->user );

		// Create a featured product.
		$feat_product = WC_Helper_Product::create_simple_product();
		$feat_product->set_featured( true );
		$feat_product->save();

		// Create a non-featured product.
		$nonfeat_product = WC_Helper_Product::create_simple_product();
		$nonfeat_product->save();

		$query_params = array(
			'featured' => 'true',
		);
		$request      = new WP_REST_Request( 'GET', '/wc/v2/products' );
		$request->set_query_params( $query_params );
		$response          = $this->server->dispatch( $request );
		$response_products = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		foreach ( $response_products as $response_product ) {
			$this->assertEquals( $feat_product->get_id(), $response_product['id'], 'REST API: Featured product not found correctly' );
		}

		$query_params = array(
			'featured' => 'false',
		);
		$request      = new WP_REST_Request( 'GET', '/wc/v2/products' );
		$request->set_query_params( $query_params );
		$response          = $this->server->dispatch( $request );
		$response_products = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		foreach ( $response_products as $response_product ) {
			$this->assertEquals( $nonfeat_product->get_id(), $response_product['id'], 'REST API: Featured product not found correctly' );
		}
	}

	/**
	 * Test getting products by shipping class property.
	 *
	 * @since 3.5.0
	 */
	public function test_get_products_by_shipping_class() {
		wp_set_current_user( $this->user );

		$shipping_class_1 = wp_insert_term( 'Bulky', 'product_shipping_class' );

		$product_1 = new WC_Product_Simple();
		$product_1->set_shipping_class_id( $shipping_class_1['term_id'] );
		$product_1->save();

		$query_params = array(
			'shipping_class' => (string) $shipping_class_1['term_id'],
		);
		$request      = new WP_REST_Request( 'GET', '/wc/v2/products' );
		$request->set_query_params( $query_params );
		$response          = $this->server->dispatch( $request );
		$response_products = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		foreach ( $response_products as $response_product ) {
			$this->assertEquals( $product_1->get_id(), $response_product['id'] );
		}
	}

	/**
	 * Test getting products by tag.
	 *
	 * @since 3.5.0
	 */
	public function test_get_products_by_tag() {
		wp_set_current_user( $this->user );

		$test_tag_1 = wp_insert_term( 'Tag 1', 'product_tag' );

		// Product with a tag.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_tag_ids( array( $test_tag_1['term_id'] ) );
		$product->save();

		// Product without a tag.
		$product_2 = WC_Helper_Product::create_simple_product();

		$query_params = array(
			'tag' => (string) $test_tag_1['term_id'],
		);
		$request      = new WP_REST_Request( 'GET', '/wc/v2/products' );
		$request->set_query_params( $query_params );
		$response          = $this->server->dispatch( $request );
		$response_products = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		foreach ( $response_products as $response_product ) {
			$this->assertEquals( $product->get_id(), $response_product['id'] );
		}
	}

	/**
	 * Test getting products by global attribute.
	 *
	 * @since 3.5.0
	 */
	public function test_get_products_by_attribute() {
		global $wpdb;
		wp_set_current_user( $this->user );

		// Variable product with 2 different variations.
		$variable_product = WC_Helper_Product::create_variation_product();

		// Terms created by variable product.
		$term_large = get_term_by( 'slug', 'large', 'pa_size' );
		$term_small = get_term_by( 'slug', 'small', 'pa_size' );

		// Simple product without attribute.
		$product_1 = WC_Helper_Product::create_simple_product();

		// Simple product with attribute size = large.
		$product_2 = WC_Helper_Product::create_simple_product();
		$product_2->set_attributes( array( 'pa_size' => 'large' ) );
		$product_2->save();

		// Link the product to the term.
		$wpdb->insert(
			$wpdb->prefix . 'term_relationships',
			array(
				'object_id'        => $product_2->get_id(),
				'term_taxonomy_id' => $term_large->term_id,
				'term_order'       => 0,
			)
		);

		// Products with attribute size == large.
		$expected_product_ids = array(
			$variable_product->get_id(),
			$product_2->get_id(),
		);
		$query_params         = array(
			'attribute'      => 'pa_size',
			'attribute_term' => (string) $term_large->term_id,
		);
		$request              = new WP_REST_Request( 'GET', '/wc/v2/products' );
		$request->set_query_params( $query_params );
		$response          = $this->server->dispatch( $request );
		$response_products = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( count( $expected_product_ids ), count( $response_products ) );
		foreach ( $response_products as $response_product ) {
			$this->assertContains( $response_product['id'], $expected_product_ids );
		}

		// Products with attribute size == small.
		$expected_product_ids = array(
			$variable_product->get_id(),
		);
		$query_params         = array(
			'attribute'      => 'pa_size',
			'attribute_term' => (string) $term_small->term_id,
		);
		$request              = new WP_REST_Request( 'GET', '/wc/v2/products' );
		$request->set_query_params( $query_params );
		$response          = $this->server->dispatch( $request );
		$response_products = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( count( $expected_product_ids ), count( $response_products ) );
		foreach ( $response_products as $response_product ) {
			$this->assertContains( $response_product['id'], $expected_product_ids );
		}
	}

	/**
	 * Test product schema contains embed fields.
	 */
	public function test_product_schema() {
		wp_set_current_user( $this->user );
		$product    = WC_Helper_Product::create_simple_product();
		$request    = new WP_REST_Request( 'OPTIONS', '/wc/v4/products/' . $product->get_id() );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$properties_to_embed = array(
			'id',
			'name',
			'slug',
			'permalink',
			'images',
			'description',
			'short_description',
		);

		foreach ( $properties as $property_key => $property ) {
			if ( in_array( $property_key, $properties_to_embed, true ) ) {
				$this->assertEquals( array( 'view', 'edit', 'embed' ), $property['context'] );
			}
		}

		$product->delete( true );
	}
}
