<?php
/**
 * Metadata backwards compatibility layer tests.
 *
 * @package WooCommerce\Tests\WC_Tests_Backwards_Compatibility
 */

/**
 * Tests the backwards-compatibility layer.
 *
 * @since 1.0.0
 */
class WC_Tests_Backwards_Compatibility extends WC_Unit_Test_Case {

	/**
	 * Get meta values directly from the postmeta table.
	 *
	 * @since 1.0.0
	 * @param int    $id Post id.
	 * @param string $key Meta key.
	 */
	protected function get_from_meta_table( $id, $key ) {
		global $wpdb;

		return $wpdb->get_col( $wpdb->prepare( 'SELECT meta_value FROM ' . $wpdb->prefix . 'postmeta where meta_key=%s and post_id=%d', $key, $id ) );
	}

	/**
	 * Test the sku metadata mapping.
	 *
	 * @since 1.0.0
	 */
	public function test_sku_mapping() {
		$product = new WC_Product_Simple();
		$product->set_sku( 'testsku' );
		$product->save();

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_sku' ) );
		$this->assertEquals( 'testsku', get_post_meta( $product->get_id(), '_sku', true ) );

		update_post_meta( $product->get_id(), '_sku', 'newsku' );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_sku' ) );
		$this->assertEquals( 'newsku', get_post_meta( $product->get_id(), '_sku', true ) );

		// @todo this fails right now. Probably data-store related.
		// $product = new WC_Product_Simple( $product->get_id() );
		// $this->assertEquals( 'newsku', $product->get_sku() );
		delete_post_meta( $product->get_id(), '_sku' );
		$this->assertEquals( '', get_post_meta( $product->get_id(), '_sku', true ) );

		add_post_meta( $product->get_id(), '_sku', 'newestsku' );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_sku' ) );
		$this->assertEquals( 'newestsku', get_post_meta( $product->get_id(), '_sku', true ) );
	}

	/**
	 * Test the regular price metadata mapping.
	 *
	 * @since 1.0.0
	 */
	public function test_regular_price_mapping() {
		$product = new WC_Product_Simple();
		$product->set_regular_price( 11.0 );
		$product->save();

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_regular_price' ) );
		$this->assertEquals( 11.0, get_post_meta( $product->get_id(), '_regular_price', true ) );

		update_post_meta( $product->get_id(), '_regular_price', 11.50 );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_regular_price' ) );
		$this->assertEquals( 11.50, get_post_meta( $product->get_id(), '_regular_price', true ) );

		// @todo Instantiate a product object and check it got updated.
		delete_post_meta( $product->get_id(), '_regular_price' );
		$this->assertEquals( 0, get_post_meta( $product->get_id(), '_regular_price', true ) );

		add_post_meta( $product->get_id(), '_regular_price', 2.12 );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_regular_price' ) );
		$this->assertEquals( 2.12, get_post_meta( $product->get_id(), '_regular_price', true ) );
	}

	/**
	 * Test the sale price metadata mapping.
	 *
	 * @since 1.0.0
	 */
	public function test_sale_price_mapping() {
		$product = new WC_Product_Simple();
		$product->set_regular_price( 11.0 );
		$product->set_sale_price( 9.0 );
		$product->save();

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_sale_price' ) );
		$this->assertEquals( 9.0, get_post_meta( $product->get_id(), '_sale_price', true ) );

		update_post_meta( $product->get_id(), '_sale_price', 1.63 );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_sale_price' ) );
		$this->assertEquals( 1.63, get_post_meta( $product->get_id(), '_sale_price', true ) );

		// @todo Instantiate a product object and check it got updated.
		delete_post_meta( $product->get_id(), '_sale_price' );
		$this->assertEquals( 0, get_post_meta( $product->get_id(), '_sale_price', true ) );

		add_post_meta( $product->get_id(), '_sale_price', 10.50 );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_sale_price' ) );
		$this->assertEquals( 10.50, get_post_meta( $product->get_id(), '_sale_price', true ) );
	}

	/**
	 * Test the price metadata mapping.
	 *
	 * @since 1.0.0
	 */
	public function test_price_mapping() {
		$product = new WC_Product_Simple();
		$product->set_regular_price( 10.0 );
		$product->set_price( 10.0 );
		$product->save();

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_price' ) );
		$this->assertEquals( 10.0, get_post_meta( $product->get_id(), '_price', true ) );

		update_post_meta( $product->get_id(), '_price', 12.0 );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_price' ) );
		$this->assertEquals( 12.0, get_post_meta( $product->get_id(), '_price', true ) );

		// @todo Instantiate a product object and check it got updated.
		delete_post_meta( $product->get_id(), '_price' );
		$this->assertEquals( 0, get_post_meta( $product->get_id(), '_price', true ) );

		add_post_meta( $product->get_id(), '_price', 5.50 );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_price' ) );
		$this->assertEquals( 5.50, get_post_meta( $product->get_id(), '_price', true ) );
	}

	/*
	@todo Tables throw errors saving products with datetime objects so this cant be tested yet.
	Seems to be from the main data store and not the mapping.
	public function test_sale_price_dates_from_mapping() {
		$sale_time_from = time();

		$product = new WC_Product_Simple();
		$product->set_regular_price( 5 );
		$product->set_sale_price( 4 );
		$product->set_date_on_sale_from( $sale_time_from );
		$product->save();

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_sale_price_dates_from' ) );
		$meta_date = get_post_meta( $product->get_id(), '_sale_price_dates_from', true );
		$this->assertEquals( $sale_time_from, strtotime( $meta_date ) );

	}

	public function test_sale_price_dates_to_mapping() {

	}
	@todo see above
	*/

	/**
	 * Test the total sales metadata mapping.
	 *
	 * @since 1.0.0
	 */
	public function test_total_sales_mapping() {
		$product = new WC_Product_Simple();
		$product->set_total_sales( 5 );
		$product->save();

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), 'total_sales' ) );
		$this->assertEquals( 5, get_post_meta( $product->get_id(), 'total_sales', true ) );

		update_post_meta( $product->get_id(), 'total_sales', 12 );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), 'total_sales' ) );
		$this->assertEquals( 12, get_post_meta( $product->get_id(), 'total_sales', true ) );

		// @todo Instantiate a product object and check it got updated should pass.
		// $retrieved_product = new WC_Product_Simple( $product->get_id() );
		// $this->assertEquals( 12, $retrieved_product->get_total_sales() );
		delete_post_meta( $product->get_id(), 'total_sales' );
		$this->assertEquals( 0, get_post_meta( $product->get_id(), 'total_sales', true ) );

		add_post_meta( $product->get_id(), 'total_sales', 2 );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), 'total_sales' ) );
		$this->assertEquals( 2, get_post_meta( $product->get_id(), 'total_sales', true ) );
	}

	/**
	 * Test the tax status metadata mapping.
	 *
	 * @since 1.0.0
	 */
	public function test_tax_status_mapping() {
		$product = new WC_Product_Simple();
		$product->set_tax_status( 'shipping' );
		$product->save();

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_tax_status' ) );
		$this->assertEquals( 'shipping', get_post_meta( $product->get_id(), '_tax_status', true ) );

		update_post_meta( $product->get_id(), '_tax_status', 'taxable' );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_tax_status' ) );
		$this->assertEquals( 'taxable', get_post_meta( $product->get_id(), '_tax_status', true ) );

		// @todo Instantiate a product object and check it got updated should pass.
		delete_post_meta( $product->get_id(), '_tax_status' );
		$this->assertEquals( 'taxable', get_post_meta( $product->get_id(), '_tax_status', true ) );

		add_post_meta( $product->get_id(), '_tax_status', 'shipping' );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_tax_status' ) );
		$this->assertEquals( 'shipping', get_post_meta( $product->get_id(), '_tax_status', true ) );
	}

	/**
	 * Test the tax class metadata mapping.
	 *
	 * @since 1.0.0
	 */
	public function test_tax_class_mapping() {
		$product = new WC_Product_Simple();
		$product->set_tax_class( 'reduced-rate' );
		$product->save();

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_tax_class' ) );
		$this->assertEquals( 'reduced-rate', get_post_meta( $product->get_id(), '_tax_class', true ) );

		update_post_meta( $product->get_id(), '_tax_class', 'zero-rate' );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_tax_class' ) );
		$this->assertEquals( 'zero-rate', get_post_meta( $product->get_id(), '_tax_class', true ) );

		// @todo Instantiate a product object and check it got updated should pass.
		delete_post_meta( $product->get_id(), '_tax_class' );
		$this->assertEquals( '', get_post_meta( $product->get_id(), '_tax_class', true ) );

		add_post_meta( $product->get_id(), '_tax_class', 'zero-rate' );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_tax_class' ) );
		$this->assertEquals( 'zero-rate', get_post_meta( $product->get_id(), '_tax_class', true ) );
	}

	/**
	 * Test the stock quantity metadata mapping.
	 *
	 * @since 1.0.0
	 */
	public function test_stock_mapping() {
		$product = new WC_Product_Simple();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 5 );
		$product->save();

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_stock' ) );
		$this->assertEquals( 5, get_post_meta( $product->get_id(), '_stock', true ) );

		update_post_meta( $product->get_id(), '_stock', 10 );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_stock' ) );
		$this->assertEquals( 10, get_post_meta( $product->get_id(), '_stock', true ) );

		// @todo Instantiate a product object and check it got updated should pass.
		delete_post_meta( $product->get_id(), '_stock' );
		$this->assertEquals( 0, get_post_meta( $product->get_id(), '_stock', true ) );

		add_post_meta( $product->get_id(), '_stock', 2 );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_stock' ) );
		$this->assertEquals( 2, get_post_meta( $product->get_id(), '_stock', true ) );
	}

	/**
	 * Test the stock status metadata mapping.
	 *
	 * @since 1.0.0
	 */
	public function test_stock_status_mapping() {
		$product = new WC_Product_Simple();
		$product->set_stock_status( 'outofstock' );
		$product->save();

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_stock_status' ) );
		$this->assertEquals( 'outofstock', get_post_meta( $product->get_id(), '_stock_status', true ) );

		update_post_meta( $product->get_id(), '_stock_status', 'onbackorder' );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_stock_status' ) );
		$this->assertEquals( 'onbackorder', get_post_meta( $product->get_id(), '_stock_status', true ) );

		// @todo Instantiate a product object and check it got updated should pass.
		delete_post_meta( $product->get_id(), '_stock_status' );
		$this->assertEquals( 'instock', get_post_meta( $product->get_id(), '_stock_status', true ) );

		add_post_meta( $product->get_id(), '_stock_status', 'outofstock' );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_stock_status' ) );
		$this->assertEquals( 'outofstock', get_post_meta( $product->get_id(), '_stock_status', true ) );
	}

	/**
	 * Test the width metadata mapping.
	 *
	 * @since 1.0.0
	 */
	public function test_width_mapping() {
		$product = new WC_Product_Simple();
		$product->set_width( 50 );
		$product->save();

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_width' ) );
		$this->assertEquals( 50, get_post_meta( $product->get_id(), '_width', true ) );

		update_post_meta( $product->get_id(), '_width', 30 );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_width' ) );
		$this->assertEquals( 30, get_post_meta( $product->get_id(), '_width', true ) );

		// @todo Instantiate a product object and check it got updated should pass.
		delete_post_meta( $product->get_id(), '_width' );
		$this->assertEquals( 0, get_post_meta( $product->get_id(), '_width', true ) );

		add_post_meta( $product->get_id(), '_width', 10 );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_width' ) );
		$this->assertEquals( 10, get_post_meta( $product->get_id(), '_width', true ) );
	}

	/**
	 * Test the length metadata mapping.
	 *
	 * @since 1.0.0
	 */
	public function test_length_mapping() {
		$product = new WC_Product_Simple();
		$product->set_length( 50 );
		$product->save();

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_length' ) );
		$this->assertEquals( 50, get_post_meta( $product->get_id(), '_length', true ) );

		update_post_meta( $product->get_id(), '_length', 30 );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_length' ) );
		$this->assertEquals( 30, get_post_meta( $product->get_id(), '_length', true ) );

		// @todo Instantiate a product object and check it got updated should pass.
		delete_post_meta( $product->get_id(), '_length' );
		$this->assertEquals( 0, get_post_meta( $product->get_id(), '_length', true ) );

		add_post_meta( $product->get_id(), '_length', 10 );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_length' ) );
		$this->assertEquals( 10, get_post_meta( $product->get_id(), '_length', true ) );
	}

	/**
	 * Test the height metadata mapping.
	 *
	 * @since 1.0.0
	 */
	public function test_height_mapping() {
		$product = new WC_Product_Simple();
		$product->set_height( 50 );
		$product->save();

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_height' ) );
		$this->assertEquals( 50, get_post_meta( $product->get_id(), '_height', true ) );

		update_post_meta( $product->get_id(), '_height', 30 );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_height' ) );
		$this->assertEquals( 30, get_post_meta( $product->get_id(), '_height', true ) );

		// @todo Instantiate a product object and check it got updated should pass.
		delete_post_meta( $product->get_id(), '_height' );
		$this->assertEquals( 0, get_post_meta( $product->get_id(), '_height', true ) );

		add_post_meta( $product->get_id(), '_height', 10 );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_height' ) );
		$this->assertEquals( 10, get_post_meta( $product->get_id(), '_height', true ) );
	}

	/**
	 * Test the weight metadata mapping.
	 *
	 * @since 1.0.0
	 */
	public function test_weight_mapping() {
		$product = new WC_Product_Simple();
		$product->set_weight( 50 );
		$product->save();

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_weight' ) );
		$this->assertEquals( 50, get_post_meta( $product->get_id(), '_weight', true ) );

		update_post_meta( $product->get_id(), '_weight', 30 );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_weight' ) );
		$this->assertEquals( 30, get_post_meta( $product->get_id(), '_weight', true ) );

		// @todo Instantiate a product object and check it got updated should pass.
		delete_post_meta( $product->get_id(), '_weight' );
		$this->assertEquals( 0, get_post_meta( $product->get_id(), '_weight', true ) );

		add_post_meta( $product->get_id(), '_weight', 10 );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_weight' ) );
		$this->assertEquals( 10, get_post_meta( $product->get_id(), '_weight', true ) );
	}

	/**
	 * Test the virtual metadata mapping.
	 *
	 * @since 1.0.0
	 */
	public function test_virtual_mapping() {
		$product = new WC_Product_Simple();
		$product->set_virtual( true );
		$product->save();

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_virtual' ) );
		$this->assertEquals( true, get_post_meta( $product->get_id(), '_virtual', true ) );

		update_post_meta( $product->get_id(), '_virtual', false );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_virtual' ) );
		$this->assertEquals( false, (bool) get_post_meta( $product->get_id(), '_virtual', true ) );

		// @todo Instantiate a product object and check it got updated should pass.
		update_post_meta( $product->get_id(), '_virtual', true );
		delete_post_meta( $product->get_id(), '_virtual' );
		$this->assertEquals( false, (bool) get_post_meta( $product->get_id(), '_virtual', true ) );

		add_post_meta( $product->get_id(), '_virtual', true );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_virtual' ) );
		$this->assertEquals( true, (bool) get_post_meta( $product->get_id(), '_virtual', true ) );
	}

	/**
	 * Test the downloadable metadata mapping.
	 *
	 * @since 1.0.0
	 */
	public function test_downloadable_mapping() {
		$product = new WC_Product_Simple();
		$product->set_downloadable( true );
		$product->save();

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_downloadable' ) );
		$this->assertEquals( true, get_post_meta( $product->get_id(), '_downloadable', true ) );

		update_post_meta( $product->get_id(), '_downloadable', false );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_downloadable' ) );
		$this->assertEquals( false, (bool) get_post_meta( $product->get_id(), '_downloadable', true ) );

		// @todo Instantiate a product object and check it got updated should pass.
		update_post_meta( $product->get_id(), '_downloadable', true );
		delete_post_meta( $product->get_id(), '_downloadable' );
		$this->assertEquals( false, (bool) get_post_meta( $product->get_id(), '_downloadable', true ) );

		add_post_meta( $product->get_id(), '_downloadable', true );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_downloadable' ) );
		$this->assertEquals( true, (bool) get_post_meta( $product->get_id(), '_downloadable', true ) );
	}

	/**
	 * Test the average rating metadata mapping.
	 *
	 * @since 1.0.0
	 */
	public function test_wc_average_rating_mapping() {
		$product = new WC_Product_Simple();
		$product->set_average_rating( 3.5 );
		$product->save();

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_wc_average_rating' ) );
		$this->assertEquals( 3.5, get_post_meta( $product->get_id(), '_wc_average_rating', true ) );

		update_post_meta( $product->get_id(), '_wc_average_rating', 5 );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_wc_average_rating' ) );
		$this->assertEquals( 5, get_post_meta( $product->get_id(), '_wc_average_rating', true ) );

		// @todo Instantiate a product object and check it got updated should pass.
		delete_post_meta( $product->get_id(), '_wc_average_rating' );
		$this->assertEquals( 0, get_post_meta( $product->get_id(), '_wc_average_rating', true ) );

		add_post_meta( $product->get_id(), '_wc_average_rating', 3 );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_wc_average_rating' ) );
		$this->assertEquals( 3, get_post_meta( $product->get_id(), '_wc_average_rating', true ) );
	}

	/**
	 * Test the thumbnail ID metadata mapping.
	 *
	 * @since 1.0.0
	 */
	public function test_thumbnail_id_mapping() {
		$product = new WC_Product_Simple();
		$product->set_image_id( 125 );
		$product->save();

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_thumbnail_id' ) );
		$this->assertEquals( 125, get_post_meta( $product->get_id(), '_thumbnail_id', true ) );

		update_post_meta( $product->get_id(), '_thumbnail_id', 100 );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_thumbnail_id' ) );
		$this->assertEquals( 100, get_post_meta( $product->get_id(), '_thumbnail_id', true ) );

		// @todo Instantiate a product object and check it got updated should pass.
		delete_post_meta( $product->get_id(), '_thumbnail_id' );
		$this->assertEquals( 0, get_post_meta( $product->get_id(), '_thumbnail_id', true ) );

		add_post_meta( $product->get_id(), '_thumbnail_id', 126 );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_thumbnail_id' ) );
		$this->assertEquals( 126, get_post_meta( $product->get_id(), '_thumbnail_id', true ) );
	}

	/**
	 * Test the upsell ids metadata mapping.
	 *
	 * @since 1.0.0
	 */
	public function test_upsell_ids_mapping() {
		$product = new WC_Product_Simple();
		$product->set_upsell_ids( array( 20, 30 ) );
		$product->save();

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_upsell_ids' ) );
		$this->assertEquals( array( 20, 30 ), get_post_meta( $product->get_id(), '_upsell_ids', true ) );

		update_post_meta( $product->get_id(), '_upsell_ids', array( 40, 50 ) );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_upsell_ids' ) );
		$this->assertEquals( array( 40, 50 ), get_post_meta( $product->get_id(), '_upsell_ids', true ) );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_upsell_ids' ) );
		$this->assertEquals( array( 40, 50 ), get_post_meta( $product->get_id(), '_upsell_ids', true ) );

		// @todo Instantiate a product object and check it got updated should pass.
		delete_post_meta( $product->get_id(), '_upsell_ids' );
		$this->assertEquals( array(), get_post_meta( $product->get_id(), '_upsell_ids', true ) );

		add_post_meta( $product->get_id(), '_upsell_ids', array( 20, 30 ) );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_upsell_ids' ) );
		$this->assertEquals( array( 20, 30 ), get_post_meta( $product->get_id(), '_upsell_ids', true ) );
	}

	/**
	 * Test the cross sell ids metadata mapping.
	 *
	 * @since 1.0.0
	 */
	public function test_crosssell_ids_mapping() {
		$product = new WC_Product_Simple();
		$product->set_cross_sell_ids( array( 20, 30 ) );
		$product->save();

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_crosssell_ids' ) );
		$this->assertEquals( array( 20, 30 ), get_post_meta( $product->get_id(), '_crosssell_ids', true ) );

		update_post_meta( $product->get_id(), '_crosssell_ids', array( 40, 50 ) );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_crosssell_ids' ) );
		$this->assertEquals( array( 40, 50 ), get_post_meta( $product->get_id(), '_crosssell_ids', true ) );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_crosssell_ids' ) );
		$this->assertEquals( array( 40, 50 ), get_post_meta( $product->get_id(), '_crosssell_ids', true ) );

		// @todo Instantiate a product object and check it got updated should pass.
		delete_post_meta( $product->get_id(), '_crosssell_ids' );
		$this->assertEquals( array(), get_post_meta( $product->get_id(), '_crosssell_ids', true ) );

		add_post_meta( $product->get_id(), '_crosssell_ids', array( 20, 30 ) );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_crosssell_ids' ) );
		$this->assertEquals( array( 20, 30 ), get_post_meta( $product->get_id(), '_crosssell_ids', true ) );
	}

	/**
	 * Test the product image gallery ids metadata mapping.
	 *
	 * @since 1.0.0
	 */
	public function test_product_image_gallery_mapping() {
		$product = new WC_Product_Simple();
		$product->save();

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_product_image_gallery' ) );
		$this->assertEquals( array(), get_post_meta( $product->get_id(), '_product_image_gallery', true ) );

		update_post_meta( $product->get_id(), '_product_image_gallery', array( 40, 50 ) );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_product_image_gallery' ) );
		$this->assertEquals( array( 40, 50 ), get_post_meta( $product->get_id(), '_product_image_gallery', true ) );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_product_image_gallery' ) );
		$this->assertEquals( array( 40, 50 ), get_post_meta( $product->get_id(), '_product_image_gallery', true ) );

		// @todo Instantiate a product object and check it got updated should pass.
		delete_post_meta( $product->get_id(), '_product_image_gallery' );
		$this->assertEquals( array(), get_post_meta( $product->get_id(), '_product_image_gallery', true ) );

		add_post_meta( $product->get_id(), '_product_image_gallery', array( 20, 30 ) );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_product_image_gallery' ) );
		$this->assertEquals( array( 20, 30 ), get_post_meta( $product->get_id(), '_product_image_gallery', true ) );
	}

	/**
	 * Test the product children ids metadata mapping.
	 *
	 * @since 1.0.0
	 */
	public function test_children_mapping() {
		$product = new WC_Product_Grouped();
		$product->set_children( array( 20, 30 ) );
		$product->save();

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_children' ) );
		$this->assertEquals( array( 20, 30 ), get_post_meta( $product->get_id(), '_children', true ) );

		update_post_meta( $product->get_id(), '_children', array( 40, 50 ) );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_children' ) );
		$this->assertEquals( array( 40, 50 ), get_post_meta( $product->get_id(), '_children', true ) );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_children' ) );
		$this->assertEquals( array( 40, 50 ), get_post_meta( $product->get_id(), '_children', true ) );

		// @todo Instantiate a product object and check it got updated should pass.
		delete_post_meta( $product->get_id(), '_children' );
		$this->assertEquals( array(), get_post_meta( $product->get_id(), '_children', true ) );

		add_post_meta( $product->get_id(), '_children', array( 20, 30 ) );

		$this->assertEquals( array(), $this->get_from_meta_table( $product->get_id(), '_children' ) );
		$this->assertEquals( array( 20, 30 ), get_post_meta( $product->get_id(), '_children', true ) );
	}

	/**
	 * Test the download limit metadata mapping.
	 *
	 * @since 1.0.0
	 */
	public function test_download_limit_mapping() {

	}

	// test_download_expiry_mapping
	// test_downloadable_files_mapping
	// test_variation_description_mapping
	// test_manage_stock_mapping
	// test_default_attributes_mapping
	// test_product_attributes_mapping
	// test_downloadable_files_mapping.
}
