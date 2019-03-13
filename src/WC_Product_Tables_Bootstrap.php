<?php
/**
 * Bootstrap file.
 *
 * Loads everything needed for the plugin to function.
 *
 * @package WooCommerce Product Tables Feature Plugin
 * @author  Automattic
 */

namespace JazzMan\WCProductTables;

use JazzMan\WCProductTables\DataStores\WC_Product_Data_Store_Custom_Table;
use JazzMan\WCProductTables\DataStores\WC_Product_Grouped_Data_Store_Custom_Table;
use JazzMan\WCProductTables\DataStores\WC_Product_Variable_Data_Store_Custom_Table;
use JazzMan\WCProductTables\DataStores\WC_Product_Variation_Data_Store_Custom_Table;
use WP_CLI;


/**
 * JazzMan\WCProductTables\WC_Product_Tables_Bootstrap.
 */
class WC_Product_Tables_Bootstrap
{

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->includes();
        add_filter('woocommerce_data_stores', [$this, 'replace_core_data_stores']);
    }

    /**
     * Include classes
     */
    public function includes()
    {
        include_once __DIR__ . '/compatibility/hacks.php';

        if (defined('WP_CLI') && WP_CLI) {
            WP_CLI::add_command('wc-product-tables', 'JazzMan\WCProductTables\WC_Product_Tables_Cli');
        }

        $this->query = new WC_Product_Tables_Query();
    }

    /**
     * Replace the core data store for products.
     *
     * @param array $stores List of data stores.
     *
     * @return array
     */
    public function replace_core_data_stores($stores)
    {

        $stores['product']           = WC_Product_Data_Store_Custom_Table::class;
        $stores['product-grouped']   = WC_Product_Grouped_Data_Store_Custom_Table::class;
        $stores['product-variable']  = WC_Product_Variable_Data_Store_Custom_Table::class;
        $stores['product-variation'] = WC_Product_Variation_Data_Store_Custom_Table::class;

        return $stores;
    }
}

