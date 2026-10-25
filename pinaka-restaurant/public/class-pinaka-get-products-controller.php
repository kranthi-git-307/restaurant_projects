<?php

/**
 * REST API Products Controller
 *
 * Handles requests to the /products endpoints.
 *
 * @package Pinaka Restaurant POS
 */

defined('ABSPATH') || exit;

/**
 * REST API Products Controller class.
 */
class Pinaka_Get_Products_Controller
{
    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'pinaka-restaurant-pos/v1';

    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'products';


    /**
     * Register REST API routes.
     */
    public function register_routes()
    {
        /*
         * =========================================================
         * GET ALL PRODUCTS
         * =========================================================
         *
         * GET:
         *
         * /wp-json/pinaka-restaurant-pos/v1/products
         *
         * Returns all published products.
         *
         * Frontend will handle lazy loading and display
         * 30 products at a time.
         */

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array($this, 'get_custom_products'),
                    'permission_callback' => array($this, 'check_user_role_permission'),
                ),
            )
        );


        /*
         * =========================================================
         * UPDATE PRODUCT STATUS
         * =========================================================
         *
         * POST:
         *
         * /wp-json/pinaka-restaurant-pos/v1/products/status
         *
         * Request:
         *
         * {
         *     "product_id": 1234,
         *     "status": "outofstock",
         *     "pin": "123456"
         * }
         */

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/status',
            array(
                array(
                    'methods'             => 'POST',
                    'callback'            => array($this, 'update_product_status'),
                    'permission_callback' => array($this, 'check_user_role_permission'),
                ),
            )
        );
    }


    /**
     * Check whether the current user has permission.
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_Error|boolean
     */
    public function check_user_role_permission($request)
    {
        $user_id = get_current_user_id();
        $user    = get_userdata($user_id);

        if (!$user) {
            return new WP_Error(
                'pinakapos_rest_cannot_view',
                esc_html__(
                    'Sorry, you cannot give permission.',
                    'pinaka-pos'
                ),
                array(
                    'status' => rest_authorization_required_code(),
                )
            );
        }


        /*
         * Get all registered WordPress roles.
         */
        global $wp_roles;

        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }

        $all_roles = array_keys($wp_roles->get_names());


        /*
         * Allow if user has any registered role.
         */
        foreach ($user->roles as $role) {

            if (in_array($role, $all_roles, true)) {
                return true;
            }
        }


        /*
         * User does not have a valid role.
         */
        return new WP_Error(
            'pinakapos_rest_cannot_view',
            esc_html__(
                'Sorry, you cannot give permission.',
                'pinaka-pos'
            ),
            array(
                'status' => rest_authorization_required_code(),
            )
        );
    }


    /**
     * Get all products.
     *
     * Returns ALL published WooCommerce products.
     *
     * Frontend handles lazy loading.
     *
     * Example:
     *
     * GET /wp-json/pinaka-restaurant-pos/v1/products
     *
     * Search:
     *
     * GET /wp-json/pinaka-restaurant-pos/v1/products?search=chicken
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     */
    public function get_custom_products($request)
    {
        /*
         * =========================================================
         * SEARCH PARAMETER
         * =========================================================
         */
        
        $search = $request->get_param('search');

        if ($search === null) {
            $search = '';
        }

        $search = sanitize_text_field($search);


        /*
         * =========================================================
         * PRODUCT QUERY
         * =========================================================
         *
         * Get ALL published products.
         *
         * No pagination.
         *
         * Frontend will display 30 at a time.
         */

        $args = array(
            'post_type'              => 'product',
            'post_status'            => 'publish',
            'posts_per_page'         => -1,
            'orderby'                => 'ID',
            'order'                  => 'ASC',
            'ignore_sticky_posts'    => true,
            'no_found_rows'          => true,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => true,
        );


        /*
         * =========================================================
         * SEARCH BY PRODUCT NAME
         * =========================================================
         */

        if (!empty($search)) {
            $args['s'] = $search;
        }


        /*
         * =========================================================
         * GET PRODUCTS
         * =========================================================
         */

        $query = new WP_Query($args);

        $products = array();

       
        /*
         * =========================================================
         * PREPARE PRODUCT DATA
         * =========================================================
         */
        // echo json_encode($query->have_posts());die;
        while ( $query->have_posts() ) {

            $query->the_post();
        
            $product_id = get_the_ID();
        
            $product = wc_get_product( $product_id );
        
            if ( ! $product ) {
                continue;
            }
        
            /*
             * Search by Product Name or SKU
             */
            if ( ! empty( $search ) ) {
        
                $product_name = $product->get_name();
                $sku          = $product->get_sku();
        
                $name_match = stripos( $product_name, $search ) !== false;
        
                $sku_match = ! empty( $sku )
                    && stripos( $sku, $search ) !== false;
        
                if ( ! $name_match && ! $sku_match ) {
                    continue;
                }
            }
        
            /*
             * Get Product Categories
             */
            /*
             * Get Product Category
             */
            /*
             * Get Absolute Top-Most Parent Category
             */
            $category_id   = null;
            $category_name = '';
            
            $categories = get_the_terms(
                $product_id,
                'product_cat'
            );
            
            if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
            
                $category = reset( $categories );
            
                $top_category = $category;
            
                while ( ! empty( $top_category->parent ) ) {
            
                    $parent = get_term(
                        $top_category->parent,
                        'product_cat'
                    );
            
                    if ( ! $parent || is_wp_error( $parent ) ) {
                        break;
                    }
            
                    $top_category = $parent;
                }
            
                $category_id   = (int) $top_category->term_id;
                $category_name = $top_category->name;
            }
            
            
            /*
             * Veg / Non-Veg
             */
            $is_veg = false;
            
            $product_tags = wp_get_post_terms(
                $product_id,
                'product_tag',
                array(
                    'fields' => 'slugs',
                )
            );
            
            if ( ! is_wp_error( $product_tags ) ) {
            
                $is_veg =
                    in_array( 'vegetarian', $product_tags, true ) ||
                    in_array( 'veg', $product_tags, true );
            }
            
            
            /*
             * Prepare Product Data
             */
            $products[] = array(
                'id'             => $product->get_id(),
                'name'           => $product->get_name(),
                'stock_quantity' => $product->get_stock_quantity(),
                'stock_status'   => $product->get_stock_status(),
                'category_id'    => $category_id,
                'category'       => $category_name,
                'is_veg'         => $is_veg,
            );
        }
        

        /*
         * =========================================================
         * RESPONSE
         * =========================================================
         */

        return rest_ensure_response(
            array(
                'success'  => true,
                'total'    => count($products),
                'products' => $products,
            )
        );
    }


    /**
 * Update product / variation stock status.
 *
 * Supports:
 * - Single product
 * - Multiple products
 * - Simple products
 * - Variations
 * - Variable parent products
 *
 * Request format:
 *
 * {
 *     "products": [
 *         {
 *             "product_id": 428,
 *             "old_status": "instock",
 *             "new_status": "outofstock"
 *         }
 *     ],
 *     "pin": "123456"
 * }
 *
 * @param WP_REST_Request $request
 *
 * @return WP_REST_Response|WP_Error
 */

public function update_product_status($request)
{
    /*
     * =========================================================
     * GET REQUEST DATA
     * =========================================================
     */

    $products = $request->get_param('products');

    $pin = sanitize_text_field(
        $request->get_param('pin')
    );


    /*
     * =========================================================
     * VALIDATE PRODUCTS ARRAY
     * =========================================================
     */

    if (
        empty($products) ||
        !is_array($products)
    ) {

        return new WP_Error(
            'invalid_products',
            'Products array is required.',
            array(
                'status' => 400,
            )
        );
    }


    /*
     * =========================================================
     * VALIDATE PIN
     * =========================================================
     */

    if (empty($pin)) {

        return new WP_Error(
            'missing_pin',
            'Employee PIN is required.',
            array(
                'status' => 400,
            )
        );
    }


    /*
     * =========================================================
     * FIND USER USING EMPLOYEE PIN
     * =========================================================
     */

    $users = get_users(
        array(
            'meta_key'    => 'emp_login_pin',
            'meta_value'  => $pin,
            'number'      => 1,
            'count_total' => false,
            'fields'      => 'ID',
        )
    );


    /*
     * =========================================================
     * INVALID PIN
     * =========================================================
     */

    if (empty($users)) {

        return new WP_Error(
            'invalid_pin',
            'Invalid employee PIN.',
            array(
                'status' => 401,
            )
        );
    }


    /*
     * =========================================================
     * USER ID
     * =========================================================
     */

    $user_id = (int) $users[0];


    /*
     * =========================================================
     * ALLOWED STATUS VALUES
     * =========================================================
     */

    $allowed_statuses = array(
        'instock',
        'outofstock',
    );


    /*
     * =========================================================
     * RESULT ARRAYS
     * =========================================================
     */

    $results = array();

    $updated_count = 0;

    $failed_count = 0;


    /*
     * =========================================================
     * PROCESS EACH PRODUCT
     * =========================================================
     */

    foreach ($products as $item) {

        /*
         * -----------------------------------------------------
         * Validate item structure
         * -----------------------------------------------------
         */

        if (!is_array($item)) {

            $failed_count++;

            $results[] = array(
                'status' => 'failed',
                'message' => 'Invalid product data.',
            );

            continue;
        }


        /*
         * -----------------------------------------------------
         * Get product ID
         * -----------------------------------------------------
         */

        $product_id = isset($item['product_id'])
            ? absint($item['product_id'])
            : 0;


        /*
         * -----------------------------------------------------
         * Get frontend old status
         * -----------------------------------------------------
         */

        $old_status = isset($item['old_status'])
            ? sanitize_text_field($item['old_status'])
            : '';


        /*
         * -----------------------------------------------------
         * Get new status
         * -----------------------------------------------------
         */

        $new_status = isset($item['new_status'])
            ? sanitize_text_field($item['new_status'])
            : '';


        /*
         * -----------------------------------------------------
         * Validate product ID
         * -----------------------------------------------------
         */

        if (!$product_id) {

            $failed_count++;

            $results[] = array(
                'product_id' => $product_id,
                'old_status' => $old_status,
                'new_status' => $new_status,
                'status'     => 'failed',
                'message'    => 'Product ID is required.',
            );

            continue;
        }


        /*
         * -----------------------------------------------------
         * Validate new status
         * -----------------------------------------------------
         */

        if (!in_array($new_status, $allowed_statuses, true)) {

            $failed_count++;

            $results[] = array(
                'product_id' => $product_id,
                'old_status' => $old_status,
                'new_status' => $new_status,
                'status'     => 'failed',
                'message'    => 'New status must be instock or outofstock.',
            );

            continue;
        }


        /*
         * -----------------------------------------------------
         * Get WooCommerce product
         * -----------------------------------------------------
         */

        $product = wc_get_product($product_id);


        if (!$product) {

            $failed_count++;

            $results[] = array(
                'product_id' => $product_id,
                'old_status' => $old_status,
                'new_status' => $new_status,
                'status'     => 'failed',
                'message'    => 'Product or variation not found.',
            );

            continue;
        }


        /*
         * -----------------------------------------------------
         * Get ACTUAL status from WooCommerce
         * -----------------------------------------------------
         *
         * Do NOT blindly trust frontend old_status.
         */

        $actual_old_status = $product->get_stock_status();


        /*
         * =====================================================
         * CASE 1:
         * FRONTEND OLD STATUS DOES NOT MATCH
         * =====================================================
         *
         * We still return the actual status.
         *
         * This prevents stale frontend data from being
         * silently treated as correct.
         */

        if (
            !empty($old_status) &&
            $old_status !== $actual_old_status
        ) {

            $failed_count++;

            $results[] = array(
                'product_id'   => $product_id,
                'old_status'   => $old_status,
                'actual_status' => $actual_old_status,
                'new_status'   => $new_status,
                'status'       => 'failed',
                'message'      => 'Old status does not match the current product status.',
            );

            continue;
        }


        /*
         * =====================================================
         * CASE 2:
         * NEW STATUS IS SAME AS CURRENT STATUS
         * =====================================================
         */

        if ($actual_old_status === $new_status) {

            $results[] = array(
                'product_id'    => $product_id,
                'old_status'    => $actual_old_status,
                'new_status'    => $new_status,
                'actual_status' => $actual_old_status,
                'status'        => 'already_updated',
                'message'       => 'Product already has this status.',
            );

            continue;
        }


        /*
         * =====================================================
         * CASE 3:
         * SIMPLE PRODUCT
         * =====================================================
         */

        if ($product->is_type('simple')) {

            /*
             * Update ONLY status.
             *
             * Quantity is NOT changed.
             */

            $product->set_stock_status($new_status);

            $product->save();


            /*
             * Clear cache.
             */

            clean_post_cache($product_id);

            wc_delete_product_transients($product_id);


            /*
             * Verify updated status.
             */

            $updated_product = wc_get_product($product_id);

            $updated_status = $updated_product
                ? $updated_product->get_stock_status()
                : $new_status;


            $updated_count++;


            $results[] = array(
                'product_id'    => $product_id,
                'old_status'    => $actual_old_status,
                'new_status'    => $new_status,
                'actual_status' => $updated_status,
                'product_type'  => 'simple',
                'status'        => 'updated',
            );

            continue;
        }


        /*
         * =====================================================
         * CASE 4:
         * PRODUCT VARIATION
         * =====================================================
         *
         * If frontend sends variation ID,
         * update only that variation.
         */

        if ($product->is_type('variation')) {

            /*
             * Update ONLY variation status.
             *
             * Quantity remains unchanged.
             */

            $product->set_stock_status($new_status);

            $product->save();


            /*
             * Clear cache.
             */

            clean_post_cache($product_id);

            wc_delete_product_transients($product_id);


            /*
             * Verify updated status.
             */

            $updated_product = wc_get_product($product_id);

            $updated_status = $updated_product
                ? $updated_product->get_stock_status()
                : $new_status;


            $updated_count++;


            $results[] = array(
                'product_id'    => $product_id,
                'old_status'    => $actual_old_status,
                'new_status'    => $new_status,
                'actual_status' => $updated_status,
                'product_type'  => 'variation',
                'status'        => 'updated',
            );

            continue;
        }


        /*
         * =====================================================
         * CASE 5:
         * VARIABLE PRODUCT
         * =====================================================
         *
         * If frontend sends parent variable product ID,
         * update all its variations.
         */

        if ($product->is_type('variable')) {

            $variation_ids = $product->get_children();


            /*
             * No variations found.
             */

            if (empty($variation_ids)) {

                $failed_count++;

                $results[] = array(
                    'product_id' => $product_id,
                    'old_status' => $actual_old_status,
                    'new_status' => $new_status,
                    'status'     => 'failed',
                    'message'    => 'No variations found for this variable product.',
                );

                continue;
            }


            $variation_results = array();

            $variation_updated_count = 0;


            /*
             * Update each variation.
             */

            foreach ($variation_ids as $variation_id) {

                $variation = wc_get_product($variation_id);


                if (!$variation) {

                    $variation_results[] = array(
                        'variation_id' => $variation_id,
                        'status'       => 'failed',
                        'message'      => 'Variation not found.',
                    );

                    continue;
                }


                /*
                 * Get actual variation status.
                 */

                $variation_old_status = $variation->get_stock_status();


                /*
                 * If already same status.
                 */

                if ($variation_old_status === $new_status) {

                    $variation_results[] = array(
                        'variation_id' => $variation_id,
                        'old_status'   => $variation_old_status,
                        'new_status'   => $new_status,
                        'status'       => 'already_updated',
                    );

                    continue;
                }


                /*
                 * Update variation status.
                 *
                 * Quantity is NOT changed.
                 */

                $variation->set_stock_status($new_status);

                $variation->save();


                /*
                 * Clear cache.
                 */

                clean_post_cache($variation_id);

                wc_delete_product_transients($variation_id);


                /*
                 * Verify.
                 */

                $updated_variation = wc_get_product(
                    $variation_id
                );


                $updated_variation_status = $updated_variation
                    ? $updated_variation->get_stock_status()
                    : $new_status;


                $variation_updated_count++;


                $variation_results[] = array(
                    'variation_id' => $variation_id,
                    'old_status'   => $variation_old_status,
                    'new_status'   => $new_status,
                    'status'       => 'updated',
                );
            }


            /*
             * Clear parent cache.
             */

            clean_post_cache($product_id);

            wc_delete_product_transients($product_id);


            /*
             * Refresh parent.
             */

            $updated_parent = wc_get_product($product_id);


            $parent_status = $updated_parent
                ? $updated_parent->get_stock_status()
                : $new_status;


            if ($variation_updated_count > 0) {
                $updated_count++;
            }


            $results[] = array(
                'product_id'         => $product_id,
                'old_status'         => $actual_old_status,
                'new_status'         => $new_status,
                'actual_status'      => $parent_status,
                'product_type'       => 'variable',
                'status'             => $variation_updated_count > 0
                    ? 'updated'
                    : 'already_updated',
                'variations'         => $variation_results,
            );

            continue;
        }


        /*
         * =====================================================
         * UNSUPPORTED PRODUCT TYPE
         * =====================================================
         */

        $failed_count++;

        $results[] = array(
            'product_id' => $product_id,
            'old_status' => $actual_old_status,
            'new_status' => $new_status,
            'status'     => 'failed',
            'message'    => 'Unsupported product type.',
        );
    }


    /*
     * =========================================================
     * FINAL RESPONSE
     * =========================================================
     */

    return new WP_REST_Response(
        array(
            'success'       => $failed_count === 0,
            'message'       => $failed_count === 0
                ? 'Product statuses processed successfully.'
                : 'Product status processing completed with some failures.',
            'user_id'       => $user_id,
            'total_count'   => count($products),
            'updated_count' => $updated_count,
            'failed_count'  => $failed_count,
            'results'       => $results,
        ),
        200
    );
}
}