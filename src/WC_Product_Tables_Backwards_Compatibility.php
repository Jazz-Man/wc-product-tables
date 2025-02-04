<?php
/**
 * File for the JazzMan\WCProductTables\WC_Product_Tables_Backwards_Compatibility class.
 */

namespace JazzMan\WCProductTables;

use WC_Product;

/**
 * Backwards compatibility layer for metadata access.
 */
class WC_Product_Tables_Backwards_Compatibility
{
    /**
     * Field mapping.
     *
     * @var array
     */
    protected static $mapping;

    /**
     * Hook into WP meta filters.
     */
    public static function hook()
    {
        if (!apply_filters('woocommerce_product_tables_enable_backward_compatibility',
                true) || \defined('WC_PRODUCT_TABLES_DISABLE_BW_COMPAT')) {
            return;
        }
        add_filter('get_post_metadata', [__CLASS__, 'get_metadata_from_tables'], 99, 4);
        add_filter('add_post_metadata', [__CLASS__, 'add_metadata_to_tables'], 99, 5);
        add_filter('update_post_metadata', [__CLASS__, 'update_metadata_in_tables'], 99, 5);
        add_filter('delete_post_metadata', [__CLASS__, 'delete_metadata_from_tables'], 99, 5);
    }

    /**
     * Unhook WP meta filters.
     */
    public static function unhook()
    {
        remove_filter('get_post_metadata', [__CLASS__, 'get_metadata_from_tables'], 99, 4);
        remove_filter('add_post_metadata', [__CLASS__, 'add_metadata_to_tables'], 99, 5);
        remove_filter('update_post_metadata', [__CLASS__, 'update_metadata_in_tables'], 99, 5);
        remove_filter('delete_post_metadata', [__CLASS__, 'delete_metadata_from_tables'], 99, 5);
    }

    /**
     * Get product data from the custom tables instead of the post meta table.
     *
     * @param array|null $result   query result
     * @param int        $post_id  post ID
     * @param string     $meta_key the meta key to retrieve
     * @param bool       $single   whether to return a single value
     *
     * @return string|array
     */
    public static function get_metadata_from_tables($result, $post_id, $meta_key, $single)
    {
        $mapping = self::get_mapping();

        if (!isset($mapping[$meta_key])) {
            return $result;
        }

        $mapped_func = $mapping[$meta_key]['get']['function'];
        $args = $mapping[$meta_key]['get']['args'];
        $args['product_id'] = $post_id;

        $query_results = \call_user_func($mapped_func, $args);

        if ($single && $query_results) {
            return $query_results[0];
        }

        if ($single && empty($query_results)) {
            return '';
        }

        return $query_results;
    }

    /**
     * Add product data to the custom tables instead of the post meta table.
     *
     * @param array|null $result     query result
     * @param int        $post_id    post ID
     * @param string     $meta_key   metadata key
     * @param mixed      $meta_value Metadata value. Must be serializable if non-scalar.
     * @param bool       $unique     whether the same key should not be added
     *
     * @return array|bool
     */
    public static function add_metadata_to_tables($result, $post_id, $meta_key, $meta_value, $unique)
    {
        $mapping = self::get_mapping();

        if (!isset($mapping[$meta_key])) {
            return $result;
        }

        if ($unique) {
            $existing = self::get_metadata_from_tables(null, $post_id, $meta_key, false);
            if ($existing) {
                return false;
            }
        }

        $mapped_func = $mapping[$meta_key]['add']['function'];
        $args = $mapping[$meta_key]['add']['args'];
        $args['product_id'] = $post_id;
        $args['value'] = $meta_value;

        return (bool) \call_user_func($mapped_func, $args);
    }

    /**
     * Update product data in the custom tables instead of the post meta table.
     *
     * @param array|null $result     query result
     * @param int        $post_id    post ID
     * @param string     $meta_key   metadata key
     * @param mixed      $meta_value Metadata value. Must be serializable if non-scalar.
     * @param mixed      $prev_value previous value to check before removing
     *
     * @return array|bool
     */
    public static function update_metadata_in_tables($result, $post_id, $meta_key, $meta_value, $prev_value)
    {
        $mapping = self::get_mapping();

        if (!isset($mapping[$meta_key])) {
            return $result;
        }

        $mapped_func = $mapping[$meta_key]['update']['function'];
        $args = $mapping[$meta_key]['update']['args'];
        $args['product_id'] = $post_id;
        $args['value'] = $meta_value;
        $args['prev_value'] = maybe_serialize($prev_value);

        return (bool) \call_user_func($mapped_func, $args);
    }

    /**
     * Delete product data from the custom tables instead of the post meta table.
     *
     * @param array|null $result     query result
     * @param int        $post_id    post ID
     * @param string     $meta_key   metadata key
     * @param mixed      $prev_value Metadata value. Must be serializable if non-scalar.
     * @param bool       $delete_all delete all metadata
     *
     * @return array|bool
     */
    public static function delete_metadata_from_tables($result, $post_id, $meta_key, $prev_value, $delete_all)
    {
        $mapping = self::get_mapping();

        if (!isset($mapping[$meta_key])) {
            return $result;
        }

        $mapped_func = $mapping[$meta_key]['delete']['function'];
        $args = $mapping[$meta_key]['delete']['args'];
        $args['product_id'] = $post_id;
        $args['delete_all'] = $delete_all;
        $args['prev_value'] = '';

        $prev_value = maybe_serialize($prev_value);
        if ('' !== $prev_value && null !== $prev_value && false !== $prev_value) {
            $args['prev_value'] = $prev_value;
        }

        return (bool) \call_user_func($mapped_func, $args);
    }

    /**
     * Get from product table.
     *
     * @param array $args {
     *                    Array of arguments
     *
     * @var int    $product_id product ID
     * @var string $column     Column name.
     *             }
     *
     * @return array
     */
    public static function get_from_product_table($args)
    {
        global $wpdb;

        $defaults = [
            'product_id' => 0,
            'column' => '',
        ];
        $args = wp_parse_args($args, $defaults);

        if (!$args['column'] || !$args['product_id']) {
            return [];
        }

        // Look in cache for table.
        $cached_data = (array) wp_cache_get('woocommerce_product_'.$args['product_id'], 'product');

        if (isset($cached_data[$args['column']])) {
            return $cached_data[$args['column']];
        }

        // Look in cache for bw compat table.
        $data = wp_cache_get('woocommerce_product_backwards_compatibility_'.$args['product_id'], 'product');

        if (false === $data) {
            $data = [];
        }

        if (empty($data[$args['column']])) {
            $escaped_column = '`'.esc_sql($args['column']).'`';
            $data[$args['column']] = $wpdb->get_col($wpdb->prepare("SELECT {$escaped_column} FROM {$wpdb->prefix}wc_products WHERE product_id = %d",
                // phpcs:ignore
                $args['product_id']));

            wp_cache_set('woocommerce_product_backwards_compatibility_'.$args['product_id'], $data, 'product');
        }

        return $data[$args['column']];
    }

    /**
     * Update from product table.
     *
     * @param array $args {
     *                    Array of arguments
     *
     * @var int    $product_id product ID
     * @var string $column     column name
     * @var string $format     format to be mapped to the value
     * @var string $value      Value save on the database.
     *             }
     *
     * @return bool
     */
    public static function update_in_product_table($args)
    {
        global $wpdb;

        $defaults = [
            'product_id' => 0,
            'column' => '',
            'format' => '%s',
            'value' => '',
        ];
        $args = wp_parse_args($args, $defaults);

        if (!$args['column'] || !$args['product_id']) {
            return false;
        }

        $format = $args['format'] ? [$args['format']] : null;
        $where = [
            'product_id' => $args['product_id'],
        ];

        if (!empty($args['delete_all'])) {
            // Properly convert null values to mysql.
            $delete_all_value = null === $args['value'] ? 'NULL' : "'".esc_sql($args['value'])."'";

            // Update all values.
            $query = "UPDATE {$wpdb->prefix}wc_products";
            $query .= ' SET '.esc_sql($args['column']).' = '.$delete_all_value;

            if (!empty($args['prev_value'])) {
                $query .= ' WHERE '.esc_sql($args['column']).' = '."'".esc_sql($args['prev_value'])."'";
            }

            $update_success = (bool) $wpdb->query($query); // WPCS: unprepared SQL ok.
        } else {
            // Support for prev value while deleting or updating.
            if (!empty($args['prev_value'])) {
                $where[$args['column']] = $args['prev_value'];
            }

            $update_success = (bool) $wpdb->update($wpdb->prefix.'wc_products', [
                $args['column'] => $args['value'],
            ], $where, $format); // WPCS: db call ok, cache ok.
        }

        if ($update_success) {
            wp_cache_delete('woocommerce_product_backwards_compatibility_'.$args['product_id'], 'product');
            wp_cache_delete('woocommerce_product_'.$args['product_id'], 'product');
        }

        return $update_success;
    }

    /**
     * Get from relationship table.
     *
     * @param array $args {
     *                    Array of arguments
     *
     * @var int    $product_id product ID
     * @var string $type       Type of relationship.
     *             }
     *
     * @return array
     */
    public static function get_from_relationship_table($args)
    {
        global $wpdb;

        $defaults = [
            'product_id' => 0,
            'type' => '',
        ];
        $args = wp_parse_args($args, $defaults);

        if (!$args['type'] || !$args['product_id']) {
            return [];
        }

        $data = wp_cache_get('woocommerce_product_backwards_compatibility_'.$args['type'].'_relationship_'.$args['product_id'],
            'product');

        if (empty($data)) {
            $data = [
                [
                    $wpdb->get_col($wpdb->prepare("SELECT DISTINCT object_id from {$wpdb->prefix}wc_product_relationships WHERE product_id = %d AND type = %s",
                        $args['product_id'], $args['type'])),
                ],
            ];

            wp_cache_set('woocommerce_product_backwards_compatibility_'.$args['type'].'_relationship_'.$args['product_id'],
                $data, 'product');
        }

        return $data;
    }

    /**
     * Update from relationship table.
     *
     * @param array $args {
     *                    Array of arguments
     *
     * @var int    $product_id product ID
     * @var string $type       type of relationship
     * @var string $value      Value to save on database.
     *             }
     *
     * @return bool
     */
    public static function update_relationship_table($args)
    {
        global $wpdb;

        $defaults = [
            'product_id' => 0,
            'type' => '',
            'value' => [],
        ];
        $args = wp_parse_args($args, $defaults);

        if (!$args['type'] || !$args['product_id'] || !\is_array($args['value'])) {
            return false;
        }

        $new_values = $args['value'];

        $existing_relationship_data = $wpdb->get_results($wpdb->prepare("SELECT `object_id`, `type` FROM {$wpdb->prefix}wc_product_relationships WHERE `product_id` = %d AND `type` = %s ORDER BY `priority` ASC",
            $args['product_id'], $args['type'])); // WPCS: db call ok, cache ok.
        $old_values = wp_list_pluck($existing_relationship_data, 'object_id');
        $missing = array_diff($old_values, $new_values);

        // Delete from database missing values.
        foreach ($missing as $object_id) {
            $wpdb->delete($wpdb->prefix.'wc_product_relationships', [
                'object_id' => $object_id,
                'product_id' => $args['product_id'],
            ], [
                '%d',
                '%d',
            ]); // WPCS: db call ok, cache ok.
        }

        // Insert or update relationship.
        foreach ($new_values as $key => $value) {
            $relationship = [
                'type' => $args['type'],
                'product_id' => $args['product_id'],
                'object_id' => $value,
                'priority' => $key,
            ];

            $wpdb->replace("{$wpdb->prefix}wc_product_relationships", $relationship, [
                '%s',
                '%d',
                '%d',
                '%d',
            ]); // WPCS: db call ok, cache ok.
        }

        wp_cache_delete('woocommerce_product_backwards_compatibility_'.$args['type'].'_relationship_'.$args['product_id'],
            'product');
        wp_cache_delete('woocommerce_product_relationships_'.$args['product_id'], 'product');

        return true;
    }

    /**
     * Get the variation description.
     *
     * @param array $args {
     *                    Array of arguments
     *
     * @var int $product_id Product ID.
     *          }
     *
     * @return array
     */
    public static function get_variation_description($args)
    {
        $defaults = [
            'product_id' => 0,
        ];
        $args = wp_parse_args($args, $defaults);

        if (!$args['product_id']) {
            return [];
        }

        return [get_post_field('post_content', $args['product_id'], 'raw')];
    }

    /**
     * Set the variation description.
     *
     * @param array $args {
     *                    Array of arguments
     *
     * @var int    $product_id product ID
     * @var string $value      Value to save on database.
     *             }
     *
     * @return bool
     */
    public static function set_variation_description($args)
    {
        global $wpdb;

        $defaults = [
            'product_id' => 0,
            'value' => '',
        ];
        $args = wp_parse_args($args, $defaults);

        if (!$args['product_id']) {
            return false;
        }

        // Support delete all and check for meta value.
        if (!empty($args['delete_all'])) {
            $prev_value = '';
            $update = "UPDATE {$wpdb->posts} SET post_content = '' WHERE post_type = 'product_variation'";
            $current = "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product_variation'";

            if (!empty($args['prev_value'])) {
                $prev_value = " AND post_content = '".esc_sql($args['prev_value'])."'";
            }

            $id_list = $wpdb->get_results($current.$prev_value); // WPCS: unprepared SQL ok.
            $results = (bool) $wpdb->query($update.$prev_value); // WPCS: unprepared SQL ok.

            // Clear post cache if successfully.
            if ($results) {
                foreach ($id_list as $variation) {
                    clean_post_cache($variation->ID);
                }
            }

            return $results;
        }

        // Check for previous value while deleting or updating.
        if (!empty($args['prev_value'])) {
            $description = self::get_variation_description($args);

            if ($args['prev_value'] !== $description[0]) {
                return false;
            }
        }

        // Regular update.
        return wp_update_post([
            'ID' => $args['product_id'],
            'post_content' => $args['value'],
        ]);
    }

    /**
     * Get whether stock is managed.
     *
     * @param array $args {
     *                    Array of arguments
     *
     * @var int $product_id Product ID.
     *          }
     *
     * @return array
     */
    public static function get_manage_stock($args)
    {
        $defaults = [
            'product_id' => 0,
        ];
        $args = wp_parse_args($args, $defaults);

        if (!$args['product_id']) {
            return [];
        }

        $args['column'] = 'stock_quantity';
        $stock = self::get_from_product_table($args);
        if (!empty($stock) && is_numeric($stock[0])) {
            return [true];
        }

        return [false];
    }

    /**
     * Set whether stock is managed.
     *
     * @param array $args {
     *                    Array of arguments
     *
     * @var int  $product_id product ID
     * @var bool $value      Value to save on database.
     *           }
     *
     * @return bool
     */
    public static function set_manage_stock($args)
    {
        $defaults = [
            'product_id' => 0,
            'value' => false,
        ];
        $args = wp_parse_args($args, $defaults);

        if (!$args['product_id']) {
            return false;
        }

        // Set stock_quantity to 0 if managing stock.
        $args['column'] = 'stock_quantity';
        if ($args['value']) {
            $args['value'] = 0;
            $args['format'] = '%d';

            return self::update_in_product_table($args);
        }

        // Set stock_quantity to NULL if not managing stock.
        $args['value'] = null;
        $args['format'] = '';

        return self::update_in_product_table($args);
    }

    /**
     * Get downloadable files in legacy meta format from downloads table.
     *
     * @param array $args {
     *                    Array of arguments
     *
     * @var int $product_id Product ID.
     *          }
     *
     * @return array
     */
    public static function get_downloadable_files($args)
    {
        global $wpdb;

        $defaults = [
            'product_id' => 0,
        ];
        $args = wp_parse_args($args, $defaults);

        if (!$args['product_id']) {
            return [];
        }

        $query_results = wp_cache_get('woocommerce_product_backwards_compatibility_downloadable_files_'.$args['product_id'],
            'product');

        if (empty($query_results)) {
            $query_results = $wpdb->get_results($wpdb->prepare("SELECT `download_id`, `name`, `file` from {$wpdb->prefix}wc_product_downloads WHERE `product_id` = %d ORDER by `priority` ASC",
                $args['product_id']));

            wp_cache_set('woocommerce_product_backwards_compatibility_downloadable_files_'.$args['product_id'],
                $query_results, 'product');
        }

        $mapped_results = [];
        foreach ($query_results as $result) {
            $mapped_results[$result->download_id] = [
                'id' => $result->download_id,
                'name' => $result->name,
                'file' => $result->file,
                'previous_hash' => '',
            ];
        }

        return [[$mapped_results]];
    }

    /**
     * Update downloadable files from legacy meta format .
     *
     * @param array $args {
     *                    Array of arguments
     *
     * @var int   $product_id product ID
     * @var array $value      Array of legacy meta format downloads info.
     *            }
     *
     * @return bool
     */
    public static function update_downloadable_files($args)
    {
        global $wpdb;

        $defaults = [
            'product_id' => 0,
            'value' => [],
        ];
        $args = wp_parse_args($args, $defaults);

        if (!$args['product_id'] || !\is_array($args['value'])) {
            return false;
        }

        $new_values = $args['value'];
        $new_ids = array_keys($new_values);

        $existing_file_data = $wpdb->get_results($wpdb->prepare("SELECT `download_id` FROM {$wpdb->prefix}wc_product_downloads WHERE `product_id` = %d ORDER BY `priority` ASC",
            $args['product_id'])); // WPCS: db call ok, cache ok.
        $existing_file_data_by_key = [];
        foreach ($existing_file_data as $data) {
            $existing_file_data_by_key[$data->download_id] = $data;
        }
        $old_ids = wp_list_pluck($existing_file_data, 'download_id');
        $missing = array_diff($old_ids, $new_ids);

        // Delete from database missing values.
        foreach ($missing as $download_id) {
            $wpdb->delete($wpdb->prefix.'wc_product_downloads', [
                'download_id' => $download_id,
            ], [
                '%d',
            ]); // WPCS: db call ok, cache ok.
        }

        // Insert or update relationship.
        $priority = 1;
        foreach ($new_values as $id => $download_info) {
            $download = [
                'download_id' => $id,
                'product_id' => $args['product_id'],
                'name' => isset($download_info['name']) ? $download_info['name'] : '',
                'file' => isset($download_info['file']) ? $download_info['file'] : '',
                'priority' => $priority,
            ];

            $wpdb->replace("{$wpdb->prefix}wc_product_downloads", $download, [
                '%d',
                '%d',
                '%s',
                '%s',
                '%d',
                '%d',
            ]); // WPCS: db call ok, cache ok.

            ++$priority;
        }

        wp_cache_delete('woocommerce_product_backwards_compatibility_downloadable_files_'.$args['product_id'],
            'product');
        wp_cache_delete('woocommerce_product_downloads_'.$args['product_id'], 'product');

        return true;
    }

    /**
     * Get attributes in legacy meta format from attributes tables.
     *
     * @param array $args {
     *                    Array of arguments
     *
     * @var int $product_id Product ID.
     *          }
     *
     * @return array
     */
    public static function get_product_attributes($args)
    {
        $defaults = [
            'product_id' => 0,
        ];
        $args = wp_parse_args($args, $defaults);

        if (!$args['product_id']) {
            return [];
        }
        $product = self::get_product($args['product_id']);
        if (!$product) {
            return [];
        }

        $raw_attributes = $product->get_attributes();
        $attributes = [];
        foreach ($raw_attributes as $raw_attribute) {
            $attribute = [
                'name' => $raw_attribute->get_name(),
                'position' => $raw_attribute->get_position(),
                'is_visible' => (int) $raw_attribute->get_visible(),
                'is_variation' => (int) $raw_attribute->get_variation(),
                'is_taxonomy' => (int) $raw_attribute->is_taxonomy(),
                'value' => implode(' | ', $raw_attribute->get_options()),
            ];
            $attributes[sanitize_title($raw_attribute->get_name())] = $attribute;
        }

        return [[$attributes]];
    }

    /**
     * Update product attributes from legacy meta format .
     *
     * @param array $args {
     *                    Array of arguments
     *
     * @var int   $product_id product ID
     * @var array $value      Array of legacy meta format attribute info.
     *            }
     *
     * @return bool
     */
    public static function update_product_attributes($args)
    {
        $defaults = [
            'product_id' => 0,
            'value' => [],
        ];
        $args = wp_parse_args($args, $defaults);

        if (!$args['product_id'] || !\is_array($args['value'])) {
            return false;
        }

        $product_id = $args['product_id'];
        $attributes = $args['value'];

        $product = self::get_product($product_id);
        if (!$product) {
            return false;
        }

        $new_attributes = [];
        foreach ($attributes as $attribute) {
            $new_attribute = new WC_Product_Attribute();
            $new_attribute->set_name($attribute['name']);
            $new_attribute->set_position($attribute['position']);
            $new_attribute->set_visible($attribute['is_visible']);
            $new_attribute->set_variation($attribute['is_variation']);
            $new_attribute->set_options(array_map('trim', explode('|', $attribute['value'])));
            $new_attributes[sanitize_title($attribute['name'])] = $new_attribute;
        }

        $product->set_attributes($new_attributes);
        $product->save();

        wp_cache_delete('woocommerce_product_backwards_compatibility_attributes_'.$args['product_id'], 'product');

        return true;
    }

    /**
     * Get default attributes in legacy meta format from attributes tables.
     *
     * @param array $args {
     *                    Array of arguments
     *
     * @var int $product_id Product ID.
     *          }
     *
     * @return array
     */
    public static function get_product_default_attributes($args)
    {
        $defaults = [
            'product_id' => 0,
        ];
        $args = wp_parse_args($args, $defaults);

        if (!$args['product_id']) {
            return [];
        }

        $product = self::get_product($args['product_id']);
        if ($product) {
            return [[$product->get_default_attributes('edit')]];
        }

        return [];
    }

    /**
     * Update product default attributes from legacy meta format .
     *
     * @param array $args {
     *                    Array of arguments
     *
     * @var int   $product_id product ID
     * @var array $value      Array of legacy meta format attribute info.
     *            }
     *
     * @return bool
     */
    public static function update_product_default_attributes($args)
    {
        $defaults = [
            'product_id' => 0,
            'value' => [],
        ];
        $args = wp_parse_args($args, $defaults);

        if (!$args['product_id'] || !\is_array($args['value'])) {
            return false;
        }

        $product = self::get_product($args['product_id']);
        if ($product) {
            $product->set_default_attributes($args['value']);
            $product->save();

            return true;
        }

        return false;
    }

    /**
     * Get mapping.
     *
     * @return array
     */
    protected static function get_mapping()
    {
        if (self::$mapping) {
            return self::$mapping;
        }
        self::$mapping = [
            /*
             * In product table.
             */
            '_thumbnail_id' => [
                'get' => [
                    'function' => [__CLASS__, 'get_from_product_table'],
                    'args' => [
                        'column' => 'image_id',
                    ],
                ],
                'add' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'image_id',
                        'format' => '%d',
                    ],
                ],
                'update' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'image_id',
                        'format' => '%d',
                    ],
                ],
                'delete' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'image_id',
                        'format' => '%d',
                        'value' => '',
                    ],
                ],
            ],
            '_sku' => [
                'get' => [
                    'function' => [__CLASS__, 'get_from_product_table'],
                    'args' => [
                        'column' => 'sku',
                    ],
                ],
                'add' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'sku',
                        'format' => '%s',
                    ],
                ],
                'update' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'sku',
                        'format' => '%s',
                    ],
                ],
                'delete' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'sku',
                        'format' => '%s',
                        'value' => '',
                    ],
                ],
            ],
            '_price' => [
                'get' => [
                    'function' => [__CLASS__, 'get_from_product_table'],
                    'args' => [
                        'column' => 'price',
                    ],
                ],
                'add' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'price',
                        'format' => '%f',
                    ],
                ],
                'update' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'price',
                        'format' => '%f',
                    ],
                ],
                'delete' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'price',
                        'format' => '',
                        'value' => null,
                    ],
                ],
            ],
            '_regular_price' => [
                'get' => [
                    'function' => [__CLASS__, 'get_from_product_table'],
                    'args' => [
                        'column' => 'regular_price',
                    ],
                ],
                'add' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'regular_price',
                        'format' => '%f',
                    ],
                ],
                'update' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'regular_price',
                        'format' => '%f',
                    ],
                ],
                'delete' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'regular_price',
                        'format' => '',
                        'value' => null,
                    ],
                ],
            ],
            '_sale_price' => [
                'get' => [
                    'function' => [__CLASS__, 'get_from_product_table'],
                    'args' => [
                        'column' => 'sale_price',
                    ],
                ],
                'add' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'sale_price',
                        'format' => '%f',
                    ],
                ],
                'update' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'sale_price',
                        'format' => '%f',
                    ],
                ],
                'delete' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'sale_price',
                        'format' => '',
                        'value' => null,
                    ],
                ],
            ],
            '_sale_price_dates_from' => [
                'get' => [
                    'function' => [__CLASS__, 'get_from_product_table'],
                    'args' => [
                        'column' => 'date_on_sale_from',
                    ],
                ],
                'add' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'date_on_sale_from',
                        'format' => '%s',
                    ],
                ],
                'update' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'date_on_sale_from',
                        'format' => '%s',
                    ],
                ],
                'delete' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'date_on_sale_from',
                        'format' => '',
                        'value' => null,
                    ],
                ],
            ],
            '_sale_price_dates_to' => [
                'get' => [
                    'function' => [__CLASS__, 'get_from_product_table'],
                    'args' => [
                        'column' => 'date_on_sale_to',
                    ],
                ],
                'add' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'date_on_sale_to',
                        'format' => '%s',
                    ],
                ],
                'update' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'date_on_sale_to',
                        'format' => '%s',
                    ],
                ],
                'delete' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'date_on_sale_to',
                        'format' => '',
                        'value' => null,
                    ],
                ],
            ],
            'total_sales' => [
                'get' => [
                    'function' => [__CLASS__, 'get_from_product_table'],
                    'args' => [
                        'column' => 'total_sales',
                    ],
                ],
                'add' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'total_sales',
                        'format' => '%d',
                    ],
                ],
                'update' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'total_sales',
                        'format' => '%d',
                    ],
                ],
                'delete' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'total_sales',
                        'format' => '%d',
                        'value' => 0,
                    ],
                ],
            ],
            '_tax_status' => [
                'get' => [
                    'function' => [__CLASS__, 'get_from_product_table'],
                    'args' => [
                        'column' => 'tax_status',
                    ],
                ],
                'add' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'tax_status',
                        'format' => '%s',
                    ],
                ],
                'update' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'tax_status',
                        'format' => '%s',
                    ],
                ],
                'delete' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'tax_status',
                        'format' => '%s',
                        'value' => 'taxable',
                    ],
                ],
            ],
            '_tax_class' => [
                'get' => [
                    'function' => [__CLASS__, 'get_from_product_table'],
                    'args' => [
                        'column' => 'tax_class',
                    ],
                ],
                'add' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'tax_class',
                        'format' => '%s',
                    ],
                ],
                'update' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'tax_class',
                        'format' => '%s',
                    ],
                ],
                'delete' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'tax_class',
                        'format' => '%s',
                        'value' => '',
                    ],
                ],
            ],
            '_stock' => [
                'get' => [
                    'function' => [__CLASS__, 'get_from_product_table'],
                    'args' => [
                        'column' => 'stock_quantity',
                    ],
                ],
                'add' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'stock_quantity',
                        'format' => '%d',
                    ],
                ],
                'update' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'stock_quantity',
                        'format' => '%d',
                    ],
                ],
                'delete' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'stock_quantity',
                        'format' => '',
                        'value' => null,
                    ],
                ],
            ],
            '_stock_status' => [
                'get' => [
                    'function' => [__CLASS__, 'get_from_product_table'],
                    'args' => [
                        'column' => 'stock_status',
                    ],
                ],
                'add' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'stock_status',
                        'format' => '%s',
                    ],
                ],
                'update' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'stock_status',
                        'format' => '%s',
                    ],
                ],
                'delete' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'stock_status',
                        'format' => '%s',
                        'value' => 'instock',
                    ],
                ],
            ],
            '_length' => [
                'get' => [
                    'function' => [__CLASS__, 'get_from_product_table'],
                    'args' => [
                        'column' => 'length',
                    ],
                ],
                'add' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'length',
                        'format' => '%f',
                    ],
                ],
                'update' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'length',
                        'format' => '%f',
                    ],
                ],
                'delete' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'length',
                        'format' => '',
                        'value' => null,
                    ],
                ],
            ],
            '_width' => [
                'get' => [
                    'function' => [__CLASS__, 'get_from_product_table'],
                    'args' => [
                        'column' => 'width',
                    ],
                ],
                'add' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'width',
                        'format' => '%f',
                    ],
                ],
                'update' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'width',
                        'format' => '%f',
                    ],
                ],
                'delete' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'width',
                        'format' => '',
                        'value' => null,
                    ],
                ],
            ],
            '_height' => [
                'get' => [
                    'function' => [__CLASS__, 'get_from_product_table'],
                    'args' => [
                        'column' => 'height',
                    ],
                ],
                'add' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'height',
                        'format' => '%f',
                    ],
                ],
                'update' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'height',
                        'format' => '%f',
                    ],
                ],
                'delete' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'height',
                        'format' => '',
                        'value' => null,
                    ],
                ],
            ],
            '_weight' => [
                'get' => [
                    'function' => [__CLASS__, 'get_from_product_table'],
                    'args' => [
                        'column' => 'weight',
                    ],
                ],
                'add' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'weight',
                        'format' => '%f',
                    ],
                ],
                'update' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'weight',
                        'format' => '%f',
                    ],
                ],
                'delete' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'weight',
                        'format' => '',
                        'value' => null,
                    ],
                ],
            ],
            '_virtual' => [
                'get' => [
                    'function' => [__CLASS__, 'get_from_product_table'],
                    'args' => [
                        'column' => 'virtual',
                    ],
                ],
                'add' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'virtual',
                        'format' => '%d',
                    ],
                ],
                'update' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'virtual',
                        'format' => '%d',
                    ],
                ],
                'delete' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'virtual',
                        'format' => '%d',
                        'value' => 0,
                    ],
                ],
            ],
            '_downloadable' => [
                'get' => [
                    'function' => [__CLASS__, 'get_from_product_table'],
                    'args' => [
                        'column' => 'downloadable',
                    ],
                ],
                'add' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'downloadable',
                        'format' => '%d',
                    ],
                ],
                'update' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'downloadable',
                        'format' => '%d',
                    ],
                ],
                'delete' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'downloadable',
                        'format' => '%d',
                        'value' => 0,
                    ],
                ],
            ],
            '_wc_average_rating' => [
                'get' => [
                    'function' => [__CLASS__, 'get_from_product_table'],
                    'args' => [
                        'column' => 'average_rating',
                    ],
                ],
                'add' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'average_rating',
                        'format' => '%f',
                    ],
                ],
                'update' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'average_rating',
                        'format' => '%f',
                    ],
                ],
                'delete' => [
                    'function' => [__CLASS__, 'update_in_product_table'],
                    'args' => [
                        'column' => 'average_rating',
                        'format' => '%f',
                        'value' => 0,
                    ],
                ],
            ],

            /*
             * In relationship table.
             */
            '_upsell_ids' => [
                'get' => [
                    'function' => [__CLASS__, 'get_from_relationship_table'],
                    'args' => [
                        'type' => 'upsell',
                    ],
                ],
                'add' => [
                    'function' => [__CLASS__, 'update_relationship_table'],
                    'args' => [
                        'type' => 'upsell',
                    ],
                ],
                'update' => [
                    'function' => [__CLASS__, 'update_relationship_table'],
                    'args' => [
                        'type' => 'upsell',
                    ],
                ],
                'delete' => [
                    'function' => [__CLASS__, 'update_relationship_table'],
                    'args' => [
                        'type' => 'upsell',
                        'value' => [],
                    ],
                ],
            ],
            '_crosssell_ids' => [
                'get' => [
                    'function' => [__CLASS__, 'get_from_relationship_table'],
                    'args' => [
                        'type' => 'cross_sell',
                    ],
                ],
                'add' => [
                    'function' => [__CLASS__, 'update_relationship_table'],
                    'args' => [
                        'type' => 'cross_sell',
                    ],
                ],
                'update' => [
                    'function' => [__CLASS__, 'update_relationship_table'],
                    'args' => [
                        'type' => 'cross_sell',
                    ],
                ],
                'delete' => [
                    'function' => [__CLASS__, 'update_relationship_table'],
                    'args' => [
                        'type' => 'cross_sell',
                        'value' => [],
                    ],
                ],
            ],
            '_product_image_gallery' => [
                'get' => [
                    'function' => [__CLASS__, 'get_from_relationship_table'],
                    'args' => [
                        'type' => 'image',
                    ],
                ],
                'add' => [
                    'function' => [__CLASS__, 'update_relationship_table'],
                    'args' => [
                        'type' => 'image',
                    ],
                ],
                'update' => [
                    'function' => [__CLASS__, 'update_relationship_table'],
                    'args' => [
                        'type' => 'image',
                    ],
                ],
                'delete' => [
                    'function' => [__CLASS__, 'update_relationship_table'],
                    'args' => [
                        'type' => 'image',
                        'value' => [],
                    ],
                ],
            ],
            '_children' => [
                'get' => [
                    'function' => [__CLASS__, 'get_from_relationship_table'],
                    'args' => [
                        'type' => 'grouped',
                    ],
                ],
                'add' => [
                    'function' => [__CLASS__, 'update_relationship_table'],
                    'args' => [
                        'type' => 'grouped',
                    ],
                ],
                'update' => [
                    'function' => [__CLASS__, 'update_relationship_table'],
                    'args' => [
                        'type' => 'grouped',
                    ],
                ],
                'delete' => [
                    'function' => [__CLASS__, 'update_relationship_table'],
                    'args' => [
                        'type' => 'grouped',
                        'value' => [],
                    ],
                ],
            ],

            /*
             * Super custom.
             */
            '_downloadable_files' => [
                'get' => [
                    'function' => [__CLASS__, 'get_downloadable_files'],
                    'args' => [],
                ],
                'add' => [
                    'function' => [__CLASS__, 'update_downloadable_files'],
                    'args' => [],
                ],
                'update' => [
                    'function' => [__CLASS__, 'update_downloadable_files'],
                    'args' => [],
                ],
                'delete' => [
                    'function' => [__CLASS__, 'update_downloadable_files'],
                    'args' => [
                        'value' => [],
                    ],
                ],
            ],
            '_variation_description' => [
                'get' => [
                    'function' => [__CLASS__, 'get_variation_description'],
                    'args' => [],
                ],
                'add' => [
                    'function' => [__CLASS__, 'set_variation_description'],
                    'args' => [],
                ],
                'update' => [
                    'function' => [__CLASS__, 'set_variation_description'],
                    'args' => [],
                ],
                'delete' => [
                    'function' => [__CLASS__, 'set_variation_description'],
                    'args' => [
                        'value' => '',
                    ],
                ],
            ],
            '_manage_stock' => [
                'get' => [
                    'function' => [__CLASS__, 'get_manage_stock'],
                    'args' => [],
                ],
                'add' => [
                    'function' => [__CLASS__, 'set_manage_stock'],
                    'args' => [],
                ],
                'update' => [
                    'function' => [__CLASS__, 'set_manage_stock'],
                    'args' => [],
                ],
                'delete' => [
                    'function' => [__CLASS__, 'set_manage_stock'],
                    'args' => [
                        'value' => false,
                    ],
                ],
            ],
            '_product_attributes' => [
                'get' => [
                    'function' => [__CLASS__, 'get_product_attributes'],
                    'args' => [],
                ],
                'add' => [
                    'function' => [__CLASS__, 'update_product_attributes'],
                    'args' => [],
                ],
                'update' => [
                    'function' => [__CLASS__, 'update_product_attributes'],
                    'args' => [],
                ],
                'delete' => [
                    'function' => [__CLASS__, 'update_product_attributes'],
                    'args' => [
                        'value' => [],
                    ],
                ],
            ],
            '_default_attributes' => [
                'get' => [
                    'function' => [__CLASS__, 'get_product_default_attributes'],
                    'args' => [],
                ],
                'add' => [
                    'function' => [__CLASS__, 'update_product_default_attributes'],
                    'args' => [],
                ],
                'update' => [
                    'function' => [__CLASS__, 'update_product_default_attributes'],
                    'args' => [],
                ],
                'delete' => [
                    'function' => [__CLASS__, 'update_product_default_attributes'],
                    'args' => [
                        'value' => [],
                    ],
                ],
            ],
        ];

        return self::$mapping;
    }

    /**
     * Helper method to prevent infinite recursion with meta filters.
     *
     * @param int $product_id product ID
     *
     * @return WC_Product
     */
    protected static function get_product($product_id)
    {
        self::unhook();
        $product = wc_get_product($product_id);
        self::hook();

        return $product;
    }
}

WC_Product_Tables_Backwards_Compatibility::hook();
