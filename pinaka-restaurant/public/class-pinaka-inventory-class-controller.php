<?php
if (!defined('ABSPATH')) {
    exit;
}

class Pinaka_Inventories_API_Controller 
{
    protected $namespace = 'pinaka-restaurant-pos/v1';
    protected $rest_base = 'inventories';
    private $bar_categories = [ 'alcohol', 'bewerages','beverages' ];
    public function register_routes() 
    {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/get-sort-types',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_sort_types' ],
                'permission_callback' => [ $this, 'check_user_role_permission' ],
            ]
        );
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/get-all-categories',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_all_categories' ],
                'permission_callback' => [ $this, 'check_user_role_permission' ],
            ]
        );
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/get-products-by-name-sku',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_products_by_name_sku' ],
                'permission_callback' => [ $this, 'check_user_role_permission' ],
            ]
        );
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/single-stock-details',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_single_stock_details' ],
                'permission_callback' => [ $this, 'check_user_role_permission' ],
            ]
        );
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/update-stock-details',
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'update_single_stock_details' ],
                'permission_callback' => [ $this, 'check_user_role_permission' ],
            ]
        );
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/add-update-item-details',
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'add_update_item_details' ],
                'permission_callback' => [ $this, 'check_user_role_permission' ],
            ]
        );
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/show-category-sublist',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'show_category_sublist' ],
                'permission_callback' => [ $this, 'check_user_role_permission' ],
            ]
        );
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/get-search-categories',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_all_search_categories' ],
                'permission_callback' => [ $this, 'check_user_role_permission' ],
            ]
        );
        add_filter('posts_where', [$this, 'search_by_exact_product_name'], 10, 2);
    }
    public function search_by_exact_product_name($where, $query) {
        global $wpdb;

        // Apply only to our intended query
        if ($query->get('exact_title_equal')) {
            $search_term = esc_sql($query->get('s'));
            $where .= " AND {$wpdb->posts}.post_title = '{$search_term}' ";
        }

        return $where;
    }
    public function check_user_role_permission( $request ) {

		$user_id = get_current_user_id();
		$user    = get_userdata( $user_id );

		if ( ! $user ) {
			return new WP_Error(
				'pinakapos_rest_cannot_view',
				esc_html__( 'Sorry, you cannot give permission.', 'pinaka-pos' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		// Get all registered roles
		global $wp_roles;
		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}
		$all_roles = array_keys( $wp_roles->get_names() );

		// Allow if user has any of the registered roles
		foreach ( $user->roles as $role ) {
			if ( in_array( $role, $all_roles, true ) ) {
				return true;
			}
		}

		return new WP_Error(
			'pinakapos_rest_cannot_view',
			esc_html__( 'Sorry, you cannot give permission.', 'pinaka-pos' ),
			[ 'status' => rest_authorization_required_code() ]
		);
	}
    public function get_sort_types($request)
    {
        $ar_list = ['Lowest Stock First', 'Highest Stock First', 'Out Of Stock', 'Alphabetical (A-Z)', 'Most Popular'];
        return new WP_REST_Response(['sort_types' => $ar_list], 200);
    }
    // public function get_all_categories($request)
    // {
    //     // Fetch all product categories
    //     $categories = get_terms([
    //         'taxonomy'   => 'product_cat',
    //         'hide_empty' => false,
    //     ]);

    //     $result = [];

    //     if (!is_wp_error($categories)) {
    //         // === Optional: Filter only bar categories ===
    //         if (!empty($this->bar_categories)) {
    //             $categories = array_filter($categories, function ($cat) {
    //                 return in_array(strtolower($cat->slug), array_map('strtolower', $this->bar_categories), true);
    //             });
    //         }

    //         // === Build Response ===
    //         foreach ($categories as $category) {
    //             $result[] = [
    //                 'id'   => $category->term_id,
    //                 'name' => $category->name,
    //                 'slug' => $category->slug,
    //                 'children' => $this->get_child_categories($category->term_id),
    //             ];
    //         }
    //     }

    //     return rest_ensure_response($result);
    // }
    public function get_all_categories( $request ) {

        $categories = get_terms( [
            'taxonomy'   => 'product_cat',
            'parent'     => 0,
            'hide_empty' => false,
            'orderby'    => 'menu_order',
            'order'      => 'ASC',
        ] );

        $result = [];

        if ( ! is_wp_error( $categories ) ) {

            // Optional: Filter bar categories
            if ( ! empty( $this->bar_categories ) ) {
                $categories = array_filter( $categories, function ( $cat ) {
                    return in_array(
                        strtolower( $cat->slug ),
                        array_map( 'strtolower', $this->bar_categories ),
                        true
                    );
                } );
            }

            foreach ( $categories as $category ) {
                $result[] = [
                    'id'   => $category->term_id,
                    'name' => html_entity_decode( $category->name, ENT_QUOTES, 'UTF-8' ),
                ];
            }
        }

        return rest_ensure_response( $result );
    }

    private function get_child_categories($parent_id)
    {
        $children = get_terms([
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'parent'     => $parent_id,
        ]);

        $result = [];

        if (!is_wp_error($children) && !empty($children)) {
            foreach ($children as $child) {
                $result[] = [
                    'id'       => $child->term_id,
                    'name'     => $child->name,
                    'slug'     => $child->slug,
                    'children' => $this->get_child_categories($child->term_id),
                ];
            }
        }

        return $result;
    }
    // public function get_products_by_name_sku($request)
    // {
    //     $params = $request->get_params();

    //     if (empty($params['barcode']) && empty($params['search'])) {
    //         return new WP_Error('missing_search', 'Please enter product name or sku or barcode.', ['status' => 422]);
    //     }

    //     $search_term = !empty($params['barcode']) ? trim($params['barcode']) : trim($params['search']);
    //     if (empty($search_term)) {
    //         return new WP_REST_Response([], 200);
    //     }

    //     $user_id = get_current_user_id();
    //     $restaurant_id = isset($params['restaurant_id']) ? intval($params['restaurant_id']) : intval(get_user_meta($user_id, 'user_restaurant_id', true));
    //     $category_id = isset($params['category_id']) ? intval($params['category_id']) : 0;
        
    //     // if (empty($category_id)) {
    //     //     return new WP_Error('missing_categoryid', 'Please select category id.', ['status' => 422]);
    //     // }

    //     $search_term_lower = strtolower($search_term);
    //     // if(isset($params['barcode']) && $params['barcode']!=='')
    //     // {
    //     //     $args = [
    //     //         'post_type'      => ['product', 'product_variation'],
    //     //         'posts_per_page' => -1,
    //     //         'post_status'    => 'publish',
    //     //         'meta_query'     => [
    //     //             [
    //     //                 'key'     => '_sku',
    //     //                 'value'   => $search_term_lower,
    //     //                 'compare' => '=', // changed '=' to LIKE for flexible search
    //     //             ],
    //     //         ],
    //     //     ];
    //     //     $query = new WP_Query($args);
    //     // }
    //     // else
    //     // {
    //     //     if($search_term_lower==='-')
    //     //     {
    //     //         return new WP_REST_Response([
    //     //             // 'sort_types' => $ar_list,
    //     //             // 'categories' => $result,
    //     //             'products'   => [],
    //     //         ], 200);
    //     //     }
    //     //     // $args = [
    //     //     //     'post_type'      => ['product', 'product_variation'],
    //     //     //     'posts_per_page' => -1,
    //     //     //     'post_status'    => 'publish',
    //     //     //     's'              => $search_term_lower,
    //     //     // ];
    //     //     // $query = new WP_Query($args);
    //     //     // if(!$query->posts)
    //     //     // {
    //     //     //     $args = [
    //     //     //         'post_type'      => ['product', 'product_variation'],
    //     //     //         'posts_per_page' => -1,
    //     //     //         'post_status'    => 'publish',
    //     //     //         'meta_query'     => [
    //     //     //             [
    //     //     //                 'key'     => '_sku',
    //     //     //                 'value'   => $search_term_lower,
    //     //     //                 'compare' => '=',
    //     //     //             ],
    //     //     //         ],
    //     //     //     ];
    //     //     //     $query = new WP_Query($args);
    //     //     // }
    //     //     $args = [
    //     //         'post_type'      => ['product', 'product_variation'],
    //     //         'posts_per_page' => -1,
    //     //         'post_status'    => 'publish',
    //     //         's'              => $search_term_lower,
    //     //         'meta_query'    => [
    //     //             'relation' => 'OR',
    //     //             [
    //     //                 'key'     => '_sku',
    //     //                 'value'   => $search_term_lower,
    //     //                 'compare' => 'LIKE',
    //     //             ],
    //     //         ],
    //     //     ];
    //     //     $query = new WP_Query($args);
    //     // }
    //     $limit  = isset($params['limit']) ? intval($params['limit']) : 100;
    //     $offset = isset($params['offset']) ? intval($params['offset']) : 0;
    //     global $wpdb;
    //     $like = '%' . $wpdb->esc_like($search_term_lower) . '%';

    //     // --------------------------------------------------
    //     // SQL SEARCH (FAST + TRUE OR LOGIC)
    //     // --------------------------------------------------
        
    //     if (!empty($params['barcode'])) {

    //         $sku = trim($params['barcode']);

    //         // IMPORTANT: build LIKE safely
    //         $like = '%' . $wpdb->esc_like($sku) . '%';

    //         $sql = $wpdb->prepare(
    //             "
    //             SELECT DISTINCT
    //                 CASE 
    //                     WHEN p.post_type = 'product_variation'
    //                     THEN p.post_parent
    //                     ELSE p.ID
    //                 END AS product_id
    //             FROM {$wpdb->posts} p
    //             INNER JOIN {$wpdb->postmeta} pm
    //                 ON p.ID = pm.post_id
    //             WHERE p.post_type IN ('product', 'product_variation')
    //             AND p.post_status = 'publish'
    //             AND pm.meta_key = '_sku'
    //             AND pm.meta_value LIKE %s
    //             LIMIT %d OFFSET %d
    //             ",
    //             $like,
    //             (int) $limit,
    //             (int) $offset
    //         );
    //         // DO NOT echo $sql
    //         $product_ids = $wpdb->get_col($sql);
    //     }
    //     else {
    //         // NORMAL MODE → NAME OR SKU
    //         $sql = $wpdb->prepare("
    //             SELECT DISTINCT p.ID
    //             FROM {$wpdb->posts} p
    //             LEFT JOIN {$wpdb->postmeta} pm 
    //                 ON p.ID = pm.post_id
    //             WHERE p.post_type IN ('product', 'product_variation')
    //             AND p.post_status = 'publish'
    //             AND (
    //                 p.post_title LIKE %s
    //                 OR (pm.meta_key = '_sku' AND pm.meta_value LIKE %s)
    //             )
    //             ORDER BY p.ID DESC
    //             LIMIT %d OFFSET %d
    //         ", $like, $like, $limit, $offset);
    //     }

    //     $product_ids = $wpdb->get_col($sql);
        
    //     $products = [];
    //     $get_all_ids = $this->get_category_ids();
    //     if ( ! empty( $category_id ) ) {

    //         $category_id = (int) $category_id;
    //         if ( ! in_array( $category_id, $get_all_ids, true ) ) {
    //             $get_all_ids[] = $category_id;
    //         }
    //     }
    //     foreach ($product_ids as $post) {
    //         $product = wc_get_product($post);
    //         if (!$product) continue;
            
    //         $item_name = strtolower($product->get_name());
    //         $sku       = strtolower($product->get_sku());
    //         // if(!$sku)
    //         // {
    //         //     continue;
    //         // }
    //         $all_ids = $this->pinaka_get_product_all_category_ids($product);
    //         // if($category_id)
    //         // {
    //         //     if(!in_array($category_id,$get_all_ids))
    //         //     {
    //         //         continue;
    //         //     }
    //         //     $is_in_category = false;
    //         //     if ($product->is_type('variation')) {
    //         //         $parent_id = $product->get_parent_id();
    //         //         if (has_term($category_id, 'product_cat', $parent_id)) {
    //         //             $is_in_category = true;
    //         //         }
    //         //     } else {
    //         //         if (has_term($category_id, 'product_cat', $product->get_id())) {
    //         //             $is_in_category = true;
    //         //         }
    //         //     }
    //         // }
    //         // else
    //         // {
                
    //         // }
    //         // if ($category_id && !$is_in_category) continue;
    //         // if ( $product->is_type( 'variation' ) ) {
    //         //     $parent_id   = $product->get_parent_id();
    //         //     $product_cats = wc_get_product_term_ids( $parent_id, 'product_cat' );
    //         // } else {
    //         //     $product_cats = wc_get_product_term_ids( $product->get_id(), 'product_cat' );
    //         // }

    //         // if ( empty( $product_cats ) || ! is_array( $product_cats ) ) {
    //         //     continue;
    //         // }

    //         // if ( ! empty( $category_id ) ) {

    //         //     if ( ! in_array( (int) $category_id, $get_all_ids, true ) ) {
    //         //         continue;
    //         //     }

    //         //     if ( ! in_array( (int) $category_id, $product_cats, true ) ) {
    //         //         continue;
    //         //     }

    //         // } else {

    //         //     if ( empty( array_intersect( $product_cats, $get_all_ids ) ) ) {
    //         //         continue;
    //         //     }
    //         // }
    //         if ( empty( array_intersect( $all_ids, $get_all_ids ) ) ) {
    //             continue; // product not allowed
    //         }
    //         // if (strpos($item_name, $search_term_lower) === false && strpos($sku, $search_term_lower) === false) continue;

    //         // if ($product->is_type('variable')) {
    //         //     foreach ($product->get_children() as $variation_id) {
    //         //         $variation = wc_get_product($variation_id);
    //         //         if (!$variation) continue;

    //         //         $variation_name = strtolower($variation->get_name());
    //         //         $variation_sku  = strtolower($variation->get_sku());

    //         //         if (strpos($variation_name, $search_term_lower) === false && strpos($variation_sku, $search_term_lower) === false) continue;

    //         //         $products[] = $this->format_inventory_product($variation, $product->get_name());
    //         //     }
    //         // } else {
    //         //     $products[] = $this->format_inventory_product($product, '');
    //         // }
    //         // if ($product->is_type('variation')) {
    //         //     $parent = wc_get_product($product->get_parent_id());
    //         //     $products[] = $this->format_inventory_product(
    //         //         $product,
    //         //         $parent ? $parent->get_name() : ''
    //         //     );
    //         // } 
    //         // elseif ($product->is_type('simple')) {
    //         //     $products[] = $this->format_inventory_product($product, '');
    //         // }
    //         if ($product->is_type('simple')) {

    //             $products[] = $this->format_inventory_product($product, '');

    //         }
    //         elseif ($product->is_type('variation')) {

    //             $parent = wc_get_product($product->get_parent_id());
    //             $products[] = $this->format_inventory_product(
    //                 $product,
    //                 $parent ? $parent->get_name() : ''
    //             );

    //         }
    //         elseif ($product->is_type('variable')) {
    //             foreach ($product->get_children() as $variation_id) {

    //                 $variation = wc_get_product($variation_id);
    //                 if (!$variation || !$variation->is_purchasable()) {
    //                     continue;
    //                 }

    //                 $products[] = $this->format_inventory_product(
    //                     $variation,
    //                     $product->get_name()
    //                 );
    //             }
    //         }
    //     }
     
    //     $filter = isset($params['filter']) ? $params['filter'] : 'Alphabetical (A-Z)';
    //     if ($filter !== '') {
    //         usort($products, function ($a, $b) use ($filter) {
    //             $stockA = intval(filter_var($a['remaining'], FILTER_SANITIZE_NUMBER_INT));
    //             $stockB = intval(filter_var($b['remaining'], FILTER_SANITIZE_NUMBER_INT));

    //             $outOfStockA = $stockA <= 0 ? 0 : 1;
    //             $outOfStockB = $stockB <= 0 ? 0 : 1;

    //             if ($filter === 'Out Of Stock') {
    //                 if ($outOfStockA !== $outOfStockB) {
    //                     return $outOfStockA - $outOfStockB;
    //                 }
    //                 return $stockA <=> $stockB;
    //             }

    //             switch ($filter) {
    //                 case 'Lowest Stock First':
    //                     return $stockA <=> $stockB;
    //                 case 'Highest Stock First':
    //                     return $stockB <=> $stockA;
    //                 case 'Alphabetical (A-Z)':
    //                     return strcmp($a['item_name'], $b['item_name']);
    //                 case 'Most Popular':
    //                     return $b['sold_total'] <=> $a['sold_total'];
    //                 default:
    //                     return 0;
    //             }
    //         });
    //     }

    //     $products = array_filter($products);
    //     $products = array_values($products);

    //     // $categories = get_terms([
    //     //     'taxonomy'   => 'product_cat',
    //     //     'hide_empty' => false,
    //     // ]);

    //     // $result = [];
    //     // if (!is_wp_error($categories)) {
    //     //     if (!empty($this->bar_categories)) {
    //     //         $categories = array_filter($categories, function ($cat) {
    //     //             return in_array(strtolower($cat->slug), array_map('strtolower', $this->bar_categories), true);
    //     //         });
    //     //     }

    //     //     foreach ($categories as $category) {
    //     //         $result[] = [
    //     //             'id'       => $category->term_id,
    //     //             'name'     => $category->name,
    //     //             'slug'     => $category->slug,
    //     //             'children' => $this->get_child_categories($category->term_id),
    //     //         ];
    //     //     }
    //     // }

    //     // $ar_list = ['Lowest Stock First', 'Highest Stock First', 'Out Of Stock', 'Alphabetical (A-Z)', 'Most Popular'];

    //     return new WP_REST_Response([
    //         // 'sort_types' => $ar_list,
    //         // 'categories' => $result,
    //         'products'   => $products,
    //     ], 200);
    // }
    public function get_products_by_name_sku($request)
    {
        $params = $request->get_params();

        if (empty($params['barcode']) && empty($params['search'])) {
            return new WP_Error('missing_search', 'Please enter product name or sku or barcode.', ['status' => 422]);
        }

        $search_term = !empty($params['barcode']) ? trim($params['barcode']) : trim($params['search']);
        if (empty($search_term)) {
            return new WP_REST_Response([], 200);
        }

        $user_id = get_current_user_id();
        $restaurant_id = isset($params['restaurant_id']) ? intval($params['restaurant_id']) : intval(get_user_meta($user_id, 'user_restaurant_id', true));
        $category_id = isset($params['category_id']) ? intval($params['category_id']) : 0;

        $search_term_lower = strtolower($search_term);
        $limit  = isset($params['limit']) ? intval($params['limit']) : 100;
        $offset = isset($params['offset']) ? intval($params['offset']) : 0;
        global $wpdb;
        $like = '%' . $wpdb->esc_like($search_term_lower) . '%';

        if (!empty($params['barcode'])) {

            $sku = trim($params['barcode']);

            // IMPORTANT: build LIKE safely
            $like = '%' . $wpdb->esc_like($sku) . '%';

            $sql = $wpdb->prepare(
                "
                SELECT DISTINCT
                    CASE 
                        WHEN p.post_type = 'product_variation'
                        THEN p.post_parent
                        ELSE p.ID
                    END AS product_id
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm
                    ON p.ID = pm.post_id
                WHERE p.post_type IN ('product', 'product_variation')
                AND p.post_status = 'publish'
                AND pm.meta_key = '_sku'
                AND pm.meta_value LIKE %s
                LIMIT %d OFFSET %d
                ",
                $like,
                (int) $limit,
                (int) $offset
            );
            // DO NOT echo $sql
            $product_ids = $wpdb->get_col($sql);
        }
        else {
            // NORMAL MODE → NAME OR SKU
            $sql = $wpdb->prepare("
                SELECT DISTINCT p.ID
                FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->postmeta} pm 
                    ON p.ID = pm.post_id
                WHERE p.post_type IN ('product', 'product_variation')
                AND p.post_status = 'publish'
                AND (
                    p.post_title LIKE %s
                    OR (pm.meta_key = '_sku' AND pm.meta_value LIKE %s)
                )
                ORDER BY p.ID DESC
                LIMIT %d OFFSET %d
            ", $like, $like, $limit, $offset);
        }

        $product_ids = $wpdb->get_col($sql);
        
        $products = [];
        $get_all_ids = $this->get_category_ids();
        if ( ! empty( $category_id ) ) {

            $category_id = (int) $category_id;
            if ( ! in_array( $category_id, $get_all_ids, true ) ) {
                $get_all_ids[] = $category_id;
            }
        }
        $temp_products = [];
        foreach ($product_ids as $post) {
            $product = wc_get_product($post);
            if (!$product) continue;
            
            $item_name = strtolower($product->get_name());
            $sku       = strtolower($product->get_sku());
            $all_ids = $this->pinaka_get_product_all_category_ids($product);
            
            if ( empty( array_intersect( $all_ids, $get_all_ids ) ) ) {
                continue; // product not allowed
            }
                
            // Simple product
            if ($product->is_type('simple')) {
                $temp_products[$product->get_id()] = $product;
            }

            // Single variation (when SQL directly returns variation)
            elseif ($product->is_type('variation')) {
                $temp_products[$product->get_id()] = $product;
            }

            // Variable product → collect children ONLY (not parent)
            elseif ($product->is_type('variable')) {

                foreach ($product->get_children() as $variation_id) {

                    $variation = wc_get_product($variation_id);

                    if (!$variation || !$variation->is_purchasable()) {
                        continue;
                    }

                    $temp_products[$variation_id] = $variation;
                }
            }
        }
        $products = [];

        foreach ($temp_products as $product) {

            if ($product->is_type('variation')) {
                $parent = wc_get_product($product->get_parent_id());

                $products[] = $this->format_inventory_product(
                    $product,
                    $parent ? $parent->get_name() : ''
                );
            } else {
                $products[] = $this->format_inventory_product($product, '');
            }
        }
        $filter = isset($params['filter']) ? $params['filter'] : 'Alphabetical (A-Z)';
        if ($filter !== '') {
            usort($products, function ($a, $b) use ($filter) {
                $stockA = intval(filter_var($a['remaining'], FILTER_SANITIZE_NUMBER_INT));
                $stockB = intval(filter_var($b['remaining'], FILTER_SANITIZE_NUMBER_INT));

                $outOfStockA = $stockA <= 0 ? 0 : 1;
                $outOfStockB = $stockB <= 0 ? 0 : 1;

                if ($filter === 'Out Of Stock') {
                    if ($outOfStockA !== $outOfStockB) {
                        return $outOfStockA - $outOfStockB;
                    }
                    return $stockA <=> $stockB;
                }

                switch ($filter) {
                    case 'Lowest Stock First':
                        return $stockA <=> $stockB;
                    case 'Highest Stock First':
                        return $stockB <=> $stockA;
                    case 'Alphabetical (A-Z)':
                        return strcmp($a['item_name'], $b['item_name']);
                    case 'Most Popular':
                        return $b['sold_total'] <=> $a['sold_total'];
                    default:
                        return 0;
                }
            });
        }

        $products = array_filter($products);
        $products = array_values($products);

        // $categories = get_terms([
        //     'taxonomy'   => 'product_cat',
        //     'hide_empty' => false,
        // ]);

        // $result = [];
        // if (!is_wp_error($categories)) {
        //     if (!empty($this->bar_categories)) {
        //         $categories = array_filter($categories, function ($cat) {
        //             return in_array(strtolower($cat->slug), array_map('strtolower', $this->bar_categories), true);
        //         });
        //     }

        //     foreach ($categories as $category) {
        //         $result[] = [
        //             'id'       => $category->term_id,
        //             'name'     => $category->name,
        //             'slug'     => $category->slug,
        //             'children' => $this->get_child_categories($category->term_id),
        //         ];
        //     }
        // }

        // $ar_list = ['Lowest Stock First', 'Highest Stock First', 'Out Of Stock', 'Alphabetical (A-Z)', 'Most Popular'];

        return new WP_REST_Response([
            // 'sort_types' => $ar_list,
            // 'categories' => $result,
            'products'   => $products,
        ], 200);
    }
    public function format_inventory_product($product, $parent_name = '') 
    {
        // if (!$product || !$product->managing_stock()) return null;
        $stock = (int)$product->get_stock_quantity();
        $threshold = $product ? intval(get_post_meta($product->get_id(), '_low_stock_amount', true)) : 0;
        $total_sold = (int)$product->get_total_sales();
        if ($stock <= 0) 
        {
            // $status = 'out_of_stock';
            $label = 'Out of Stock';
            $color = '#FF3B30';
        } 
        elseif ($stock <= $threshold) {
            // $status = 'low_stock';
            $label = 'Low Stock';
            $color = '#FFA500';
        } 
        else {
            // $status = 'normal';
            $label = 'Normal';
            $color = '#4CAF50';
        }
        $attribute = '';
        if ($product->is_type('variation')) 
        {
            $attribute = implode(', ', $product->get_attributes());
        }
        $full_name = $parent_name ? "{$parent_name} ({$attribute})" : $product->get_name();
        // $stock_percentage = $threshold > 0 ? round(($stock / $threshold) * 100, 2) : 0;
        
        return [
            'id'              => $product->get_id(),
            'item_name'       => $full_name,
            'threshold'       => $threshold,
            // 'stock_percentage'=> $stock_percentage,
            'remaining'       => "⚠️ {$stock} Left!",
            'sold_total'      => $total_sold,
            'status_label'    => $label,
            'status_color'    => $color,
            'image'           => wp_get_attachment_url($product->get_image_id())
        ];
    }
    public function get_single_stock_details($request)
    {
        $params = $request->get_params();
        $product_id = $params['item_id'];
        $product = wc_get_product($product_id);
        if (!$product) {
            return new WP_Error('invalid_product', 'Please enter valid item_id.', ['status' => 400]);
        }
        // $user_id = get_current_user_id();
        // $restaurant_id = isset($params['restaurant_id']) ? intval($params['restaurant_id']) : intval( get_user_meta( $user_id, 'user_restaurant_id', true ) );
        // if(!empty($restaurant_id))
        // {
        //     $rest_id = $product->get_meta('_restaurant_id');
        //     if($rest_id!==$restaurant_id)
        //     {
        //         return new WP_Error('invalid_product_restaurant', 'Please enter valid product restaurant id.', ['status' => 400]);
        //     }
        // }
        $parent_name = '';
        if ($product->is_type('variation')) 
        {
            $parent = wc_get_product($product->get_parent_id());
            if ($parent) 
            {
                $parent_name = $parent->get_name();
            }
        }
        $result = $this->single_format_inventory_product($product, $parent_name);
        return rest_ensure_response($result);
    }
    public function single_format_inventory_product($product, $parent_name = '')
    {
        if ( $product->is_type('variation') ) {
            $parent_id   = $product->get_parent_id();
            $parent_name = get_the_title($parent_id);
            $attributes  = $product->get_attributes();
            $attr_values = array_map(function($a) {
                return is_object($a) ? wc_attribute_label($a->get_name()) : $a;
            }, $attributes);

            if ( is_array($attributes) && ! empty($attributes) ) {
                $attr_values = array_values( $attributes );
            }
            $full_name = trim( $parent_name . ' ' . implode(' ', $attr_values) );
        } else {
            $full_name = $product->get_name();
        }
        // $full_name = $parent_name ? "{$parent_name}" . ($attribute ? " ({$attribute})" : "") : $product->get_name();
        $image_id = $product->get_image_id();
        $image_url = $image_id ? wp_get_attachment_url($image_id) : null;
        return [
            'id'        => $product->get_id(),
            'item_name' => $full_name,
            'image'     => $image_url,
            'Reduce Stock' => [
                'Damage', 'Expiry', 'Adjustment Entry', 'Manual Sale'
            ],
            'Add Stock' => [
                'Restock', 'Transfer', 'Supplier', 'Return' , 'Promotional'
            ]
        ];
    }
    public function update_single_stock_details( $request ) 
    {
        $params  = $request->get_params();
        $user_id = get_current_user_id();

        $product_id = isset( $params['item_id'] ) ? intval( $params['item_id'] ) : 0;
        $flag_type  = isset( $params['flag_type'] ) ? sanitize_text_field( $params['flag_type'] ) : '';
        $qty        = isset( $params['qty'] ) ? floatval( $params['qty'] ) : 0;
        $reason     = isset( $params['reason'] ) ? sanitize_textarea_field( $params['reason'] ) : '';

        // === Basic Validations ===
        if ( ! $product_id || ! $flag_type || empty( $reason ) ) {
            return new WP_Error( 'invalid_params', 'Required parameter missing', [ 'status' => 400 ] );
        }

        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return new WP_Error( 'invalid_product', 'Invalid product ID.', [ 'status' => 400 ] );
        }

        // === Prevent updating parent variable product ===
        if ( $product->is_type( 'variable' ) ) {
            return new WP_Error( 'parent_product', 'Cannot update stock for parent variable product. Use a specific variation.', [ 'status' => 400 ] );
        }

        // === Fetch Current Stock ===
        $current_stock = $product->get_stock_quantity();
        if ( $current_stock === null || $current_stock < 0 ) {
            $current_stock = 0;
        }

        // === Determine Action ===
        if ( $flag_type === 'Reduce Stock' ) {

            // Validation for reduction
            if ( $qty <= 0 ) {
                return new WP_Error( 'invalid_qty', 'Enter quantity greater than zero for reducing stock.', [ 'status' => 400 ] );
            }
            if ( $current_stock <= 0 ) {
                return new WP_Error( 'no_stock', 'Stock is zero, cannot reduce.', [ 'status' => 400 ] );
            }
            if ( $qty > $current_stock ) {
                return new WP_Error( 'insufficient_stock', "Entered quantity ($qty) exceeds current stock ($current_stock).", [ 'status' => 400 ] );
            }

            $new_stock = max( 0, $current_stock - $qty );

        } elseif ( $flag_type === 'Add Stock' ) {

            if ( $qty <= 0 ) {
                return new WP_Error( 'invalid_qty', 'Enter quantity greater than zero for adding stock.', [ 'status' => 400 ] );
            }

            $new_stock = $current_stock + $qty;

        } else {
            return new WP_Error( 'invalid_flag', 'Invalid flag type. Must be Add Stock or Reduce Stock.', [ 'status' => 400 ] );
        }

        $product->set_manage_stock( true );

        $product->set_stock_quantity( $new_stock );

        if ( $new_stock > 0 ) {
            $product->set_stock_status( 'instock' );
        } else {
            $product->set_stock_status( 'outofstock' );
        }
        $product->save();
        if ( method_exists( $this, 'insert_post_full_log' ) ) {
            $this->insert_post_full_log( $product_id, $flag_type, [
                'flag_type'   => $flag_type,
                'qty'         => $qty,
                'reason'      => $reason,
                'new_stock'   => $new_stock,
                'old_stock'   => $current_stock,
                'updated_by'  => $user_id,
                'updated_at'  => current_time( 'mysql' ),
            ]);
        }

        // === Build Product Display Name (handles variation) ===
        if ( $product->is_type( 'variation' ) ) {
            $parent_id   = $product->get_parent_id();
            $parent_name = get_the_title( $parent_id );

            // Get readable variation attributes
            $variation_data = [];
            foreach ( $product->get_attributes() as $attr_name => $attr_value ) {
                $label = wc_attribute_label( str_replace( 'attribute_', '', $attr_name ) );
                $variation_data[] = "$label: $attr_value";
            }

            $full_name = $parent_name . ( ! empty( $variation_data ) ? ' (' . implode( ', ', $variation_data ) . ')' : '' );

        } else {
            $full_name = $product->get_name();
        }

        // === Return Response ===
        return rest_ensure_response( [
            'success'       => true,
            'product_id'    => $product_id,
            'product_type'  => $product->get_type(),
            'flag_type'     => $flag_type,
            'reason'        => $reason,
            'qty_changed'   => $qty,
            'old_stock'     => $current_stock,
            'new_stock'     => $new_stock,
            'stock_status'  => $product->get_stock_status(),
            'product_name'  => $full_name,
        ] );
    }

    public function insert_post_full_log($post_id, $action, $data = []) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'post_full_logs';
        $post = get_post($post_id);
        $post_type = $post ? $post->post_type : '';
        $user_id = get_current_user_id();
        $json_data = !empty($data) ? wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : null;
        $wpdb->insert($table_name, [
            'post_id'    => $post_id,
            'post_type'  => $post_type,
            'action'     => $action,
            'user_id'    => $user_id,
            'data'       => $json_data,
            'created_at' => current_time('mysql'),
        ]);
    }
    // public function add_update_item_details($request)
    // {
    //     $params = $request->get_params();
    //     if (!isset($params['item_name']) || empty(trim($params['item_name']))) {
    //         return new WP_Error('missing_itemname', 'Please enter beverage name.', ['status' => 422]);
    //     }
    //     $item_sku = isset($params['item_sku']) ? intval($params['item_sku']) : '';
    //     if (empty($item_sku)) {
    //         return new WP_Error('missing_sku', 'Please enter sku.', ['status' => 422]);
    //     }
    //     $search_term = !empty($params['item_sku']) ? trim($params['item_sku']) : trim($params['item_name']);
    //     $search_term_lower = strtolower($search_term);

    //     $user_id = get_current_user_id();
    //     $restaurant_id = isset($params['restaurant_id'])
    //         ? intval($params['restaurant_id'])
    //         : intval(get_user_meta($user_id, 'user_restaurant_id', true));

    //     $category_id = isset($params['category_id']) ? intval($params['category_id']) : 0;
    //     if (empty($category_id)) {
    //         return new WP_Error('missing_category', 'Please select category id.', ['status' => 422]);
    //     }

    //     $item_qty   = isset($params['item_qty']) ? floatval($params['item_qty']) : 0;
    //     $item_price = isset($params['item_price']) ? floatval($params['item_price']) : 0;
    //     $item_note  = isset($params['item_note']) ? sanitize_text_field($params['item_note']) : '';

    //     if ($item_qty <= 0) {
    //         return new WP_Error('invalid_qty', 'Please enter valid item quantity.', ['status' => 422]);
    //     }
    //     if ($item_price <= 0) {
    //         return new WP_Error('invalid_price', 'Please enter valid item price.', ['status' => 422]);
    //     }

    //     $isfound = false;
    //     $product_id = 0;
    //     $args = [
    //         'post_type'      => ['product', 'product_variation'],
    //         'posts_per_page' => -1,
    //         'post_status'    => 'publish',
    //         'meta_query'     => [
    //             [
    //                 'key'     => '_sku',
    //                 'value'   => $search_term_lower,
    //                 'compare' => '='
    //             ]
    //         ],
    //         'tax_query'      => [
    //             [
    //                 'taxonomy' => 'product_cat',
    //                 'field'    => 'term_id',
    //                 'terms'    => $category_id,
    //             ]
    //         ],
    //     ];

    //     $query = new WP_Query($args);
    //     if (!empty($query->posts)) 
    //     {
    //         $isfound = true;
    //         $product_id = $query->posts[0]->ID;
    //     }
    //     if (empty($query->posts)) 
    //     {
    //         global $wpdb;

    //         $search_term_lower = strtolower(trim($params['item_name']));

    //         $filter = function ($where) use ($search_term_lower, $wpdb) {
    //             return $where . $wpdb->prepare(" AND LOWER({$wpdb->posts}.post_title) = %s", $search_term_lower);
    //         };

    //         add_filter('posts_where', $filter, 10, 2);

    //         $args = [
    //             'post_type'      => ['product', 'product_variation'],
    //             'posts_per_page' => 1,
    //             'post_status'    => 'publish',
    //             's'              => '',
    //             'tax_query'      => [
    //                 [
    //                     'taxonomy' => 'product_cat',
    //                     'field'    => 'term_id',
    //                     'terms'    => $category_id,
    //                 ]
    //             ],
    //             'no_found_rows'  => true,
    //         ];

    //         $query = new WP_Query($args);

    //         remove_filter('posts_where', $filter, 10);

    //         if (!empty($query->posts)) {
    //             $isfound   = true;
    //             $product_id = $query->posts[0]->ID;
    //         }
    //     }
    //     if (!$isfound) 
    //     {
    //         $new_product = new WC_Product_Simple();
    //         $new_product->set_name(ucwords($params['item_name']));
    //         $new_product->set_status('publish');
    //         $new_product->set_catalog_visibility('visible');
    //         $new_product->set_regular_price($item_price);
    //         $new_product->set_stock_quantity($item_qty);
    //         $new_product->set_manage_stock(true);
    //         $new_product->set_stock_status('instock');
    //         $new_product->set_category_ids([$category_id]);
    //         $new_product->set_sku($params['item_sku']);
    //         $product_id = $new_product->save();
    //         if (!empty($item_note)) 
    //         {
    //             update_post_meta($product_id, '_item_note', $item_note);
    //         }
    //         return [
    //             'status'  => 'created',
    //             'message' => "New product '{$search_term}' created successfully.",
    //             'id'      => $product_id,
    //             'price'   => $item_price,
    //             'qty'     => $item_qty,
    //             'note'    => $item_note,
    //         ];
    //     }

    //     $product = wc_get_product($product_id);
    //     if ($product) 
    //     {
    //         $current_stock = (float) $product->get_stock_quantity();
    //         $new_stock = $current_stock + $item_qty;
    //         $product->set_manage_stock(true);
    //         $product->set_stock_quantity($new_stock);
    //         // $product->set_regular_price($item_price);
    //         $product->save();
    //         if (!empty($item_note)) 
    //         {
    //             update_post_meta($product_id, '_item_note', $item_note);
    //         }
    //         return [
    //             'status'   => 'updated',
    //             'message'  => "Product '{$product->get_name()}' updated successfully.",
    //             'id'       => $product_id,
    //             'new_stock'=> $new_stock,
    //             // 'price'    => $item_price,
    //             'note'    => $item_note,
    //         ];
    //     }
    //     return new WP_Error('not_processed', 'Unable to process request.', ['status' => 500]);
    // }
    // public function add_update_item_details($request)
    // {
    //     $params = $request->get_params();
    //     // $logger = wc_get_logger();

    //     // $logger->info(
    //     //     'add_update_item_details params: ' . wc_print_r( $params, true ),
    //     //     [ 'source' => 'pk-pos-api' ]
    //     // );
    //     // === Validate required fields ===
    //     if (empty(trim($params['item_name'] ?? ''))) {
    //         return new WP_Error('missing_itemname', 'Please enter beverage name.', ['status' => 422]);
    //     }

    //     $item_sku = isset($params['item_sku']) ? sanitize_text_field(trim($params['item_sku'])) : '';
    //     if (empty($item_sku)) {
    //         return new WP_Error('missing_sku', 'Please enter SKU.', ['status' => 422]);
    //     }

    //     $user_id = get_current_user_id();
    //     $restaurant_id = isset($params['restaurant_id'])
    //         ? intval($params['restaurant_id'])
    //         : intval(get_user_meta($user_id, 'user_restaurant_id', true));

    //     $category_id = isset($params['category_id']) ? intval($params['category_id']) : get_term_by('slug', 'uncategorized', 'product_cat')->term_id;
    //     if (empty($category_id)) {
    //         return new WP_Error('missing_category', 'Please select category ID.', ['status' => 422]);
    //     }
    //     $mini_category_id = isset($params['mini_category_id']) ? intval($params['mini_category_id']) : $category_id;
    //     $item_qty   = isset($params['item_qty']) ? floatval($params['item_qty']) : 0;
    //     $item_price = isset($params['item_price']) ? floatval($params['item_price']) : 0;
    //     $item_note  = isset($params['item_note']) ? sanitize_text_field($params['item_note']) : '';

    //     if ($item_qty <= 0) {
    //         return new WP_Error('invalid_qty', 'Please enter valid item quantity.', ['status' => 422]);
    //     }
    //     if ($item_price <= 0) {
    //         return new WP_Error('invalid_price', 'Please enter valid item price.', ['status' => 422]);
    //     }
    //     $item_sku = trim( $item_sku );

    //     if ( empty( $item_sku ) ) {
    //         wp_send_json_error([
    //             'message' => 'SKU is required'
    //         ]);
    //     }

    //     // Check if SKU already exists
    //     $existing_id = wc_get_product_id_by_sku( $item_sku );
    //     if ( $existing_id ) {
    //         wp_send_json_error([
    //             'message'    => 'SKU already exists',
    //             'product_id'=> $existing_id
    //         ]);
    //     }

    //     global $wpdb;

    //     $isfound    = false;
    //     $product_id = 0;

    //     /**
    //      * === STEP 1: Find by SKU (no category restriction) ===
    //      */
        
    //     $args = [
    //         'post_type'      => ['product', 'product_variation'],
    //         'posts_per_page' => 1,
    //         'post_status'    => 'publish',
    //         'meta_query'     => [
    //             [
    //                 'key'     => '_sku',
    //                 'value'   => $item_sku,
    //                 'compare' => '=',
    //             ],
    //         ],
    //     ];
    //     $query = new WP_Query($args);
    //     if (!empty($query->posts)) {
    //         $isfound    = true;
    //         $product_id = $query->posts[0]->ID;
    //     }
    //     /**
    //      * === STEP 2: If not found by SKU, try exact name match (no category restriction) ===
    //      */
    //     // if (!$isfound) 
    //     // {
    //     //     $search_term_lower = strtolower(trim($params['item_name']));

    //     //     $filter = function ($where) use ($search_term_lower, $wpdb) {
    //     //         return $where . $wpdb->prepare(" AND LOWER({$wpdb->posts}.post_title) = %s", $search_term_lower);
    //     //     };

    //     //     add_filter('posts_where', $filter, 10, 2);

    //     //     $args = [
    //     //         'post_type'      => ['product', 'product_variation'],
    //     //         'posts_per_page' => 1,
    //     //         'post_status'    => 'publish',
    //     //         'no_found_rows'  => true,
    //     //     ];

    //     //     $query = new WP_Query($args);
    //     //     remove_filter('posts_where', $filter, 10);

    //     //     if (!empty($query->posts)) {
    //     //         $isfound    = true;
    //     //         $product_id = $query->posts[0]->ID;
    //     //     }
    //     // }
    //     /**
    //      * === STEP 3: If found, check category match ===
    //      */
    //     if ($isfound && $product_id > 0) {
    //         $product = wc_get_product($product_id);
    //     }
        
    //     /**
    //      * === STEP 4: If still not found, create new product ===
    //      */
    //     if (!$isfound) 
    //     {
    //         $new_product = new WC_Product_Simple();
    //         $new_product->set_name(ucwords(trim($params['item_name'])));
    //         $new_product->set_status('publish');
    //         $new_product->set_catalog_visibility('visible');
    //         $new_product->set_regular_price($item_price);
    //         $new_product->set_manage_stock(true);
    //         $new_product->set_stock_quantity($item_qty);
    //         $new_product->set_stock_status('instock');
    //         $new_product->set_category_ids([$mini_category_id]);
    //         // $new_product->set_sku($item_sku);
            
    //         // $logger  = wc_get_logger();
    //         // $context = [ 'source' => 'pos-tax' ];
    //         // echo json_encode([$params,$mini_category_id]);die;
    //         // $logger->info( 'Raw params received: ' . print_r( $params, true ), $context );
    //         if ( isset( $params['tax_class'] ) && $params['tax_class'] !== null && $params['tax_class'] !== '' ) {
                
    //             // Treat tax_id as tax class slug
    //             $tax_class = sanitize_title( $params['tax_class'] );

    //             // $logger->info( 'Applying tax class slug: ' . $tax_class, $context );

    //             $new_product->set_tax_status( 'taxable' );
    //             $new_product->set_tax_class( $tax_class );

    //             // $logger->info( 'Tax class applied successfully', $context );
    //         }
            
    //         $product_id = $new_product->save();
    //         if (!empty($item_note)) {
    //             update_post_meta($product_id, '_item_note', $item_note);
    //         }

    //         return new WP_REST_Response([
    //             'status'  => 'created',
    //             'message' => "New product created successfully.",
    //             'item_id'      => $product_id,
    //             'item_price'   => $item_price,
    //             'item_qty'     => $item_qty,
    //             'item_note'    => $item_note,
    //         ], 200);
    //     }
    //     else
    //     {
            
    //         $current_stock = (float) $product->get_stock_quantity();
    //         $new_stock     = $current_stock + $item_qty;

    //         $product->set_manage_stock(true);
    //         $product->set_stock_quantity($new_stock);
    //         // $logger  = wc_get_logger();
    //         // $context = [ 'source' => 'pos-tax' ];

    //         // $logger->info( 'Raw params received: ' . print_r( $params, true ), $context );
    //         if ( isset( $params['tax_class'] ) && $params['tax_class'] !== null && $params['tax_class'] !== '' ) {

    //             // Treat tax_id as tax class slug
    //             $tax_class = sanitize_title( $params['tax_class'] );

    //             // $logger->info( 'Applying tax class slug: ' . $tax_class, $context );

    //             $product->set_tax_status( 'taxable' );
    //             $product->set_tax_class( $tax_class );

    //             // $logger->info( 'Tax class applied successfully', $context );
    //         }
    //         $product->save();

    //         if (!empty($item_note)) {
    //             update_post_meta($product_id, '_item_note', $item_note);
    //         }

    //         return new WP_REST_Response([
    //             'status'     => 'updated',
    //             'message'    => "Product '{$product->get_name()}' updated successfully.",
    //             'item_id'         => $product_id,
    //             'new_stock'  => $new_stock,
    //             'item_note'       => $item_note,
    //         ], 200);
    //     }
    //     return new WP_Error('not_processed', 'Unable to process request.', ['status' => 500]);
    // }
    public function add_update_item_details($request)
    {
        $params = $request->get_params();
        // $logger = wc_get_logger();

        // $logger->info(
        //     'add_update_item_details params: ' . wc_print_r( $params, true ),
        //     [ 'source' => 'pk-pos-api' ]
        // );
        // === Validate required fields ===
        if (empty(trim($params['item_name'] ?? ''))) {
            return new WP_Error('missing_itemname', 'Please enter beverage name.', ['status' => 422]);
        }

        $item_sku = isset($params['item_sku']) ? sanitize_text_field(trim($params['item_sku'])) : '';
        if (empty($item_sku)) {
            return new WP_Error('missing_sku', 'Please enter SKU.', ['status' => 422]);
        }

        $user_id = get_current_user_id();
        $restaurant_id = isset($params['restaurant_id'])
            ? intval($params['restaurant_id'])
            : intval(get_user_meta($user_id, 'user_restaurant_id', true));

        $category_id = isset($params['category_id']) ? intval($params['category_id']) : get_term_by('slug', 'uncategorized', 'product_cat')->term_id;
        if (empty($category_id)) {
            return new WP_Error('missing_category', 'Please select category ID.', ['status' => 422]);
        }
        $mini_category_id = isset($params['mini_category_id']) ? intval($params['mini_category_id']) : $category_id;
        $item_qty   = isset($params['item_qty']) ? floatval($params['item_qty']) : 0;
        $item_price = isset($params['item_price']) ? floatval($params['item_price']) : 0;
        $item_note  = isset($params['item_note']) ? sanitize_text_field($params['item_note']) : '';

        if ($item_qty <= 0) {
            return new WP_Error('invalid_qty', 'Please enter valid item quantity.', ['status' => 422]);
        }
        if ($item_price <= 0) {
            return new WP_Error('invalid_price', 'Please enter valid item price.', ['status' => 422]);
        }

        // === IMAGE ID SUPPORT ===
        $image_id = isset($params['image_id']) ? intval($params['image_id']) : 0;

        if (!empty($image_id)) {
            $attachment = get_post($image_id);

            if (!$attachment || $attachment->post_type !== 'attachment') {
                return new WP_Error(
                    'invalid_image',
                    'Invalid image_id. Attachment not found.',
                    ['status' => 422]
                );
            }
        }

        global $wpdb;

        $isfound    = false;
        $product_id = 0;

        /**
         * === STEP 1: Find by SKU (no category restriction) ===
         */
        $args = [
            'post_type'      => ['product', 'product_variation'],
            'posts_per_page' => 1,
            'post_status'    => 'publish',
            'meta_query'     => [
                [
                    'key'     => '_sku',
                    'value'   => $item_sku,
                    'compare' => '=',
                ],
            ],
        ];
        $query = new WP_Query($args);
        if (!empty($query->posts)) {
            $isfound    = true;
            $product_id = $query->posts[0]->ID;
        }

        /**
         * === STEP 2: If not found by SKU, try exact name match (no category restriction) ===
         */
        // if (!$isfound) 
        // {
        //     $search_term_lower = strtolower(trim($params['item_name']));

        //     $filter = function ($where) use ($search_term_lower, $wpdb) {
        //         return $where . $wpdb->prepare(" AND LOWER({$wpdb->posts}.post_title) = %s", $search_term_lower);
        //     };

        //     add_filter('posts_where', $filter, 10, 2);

        //     $args = [
        //         'post_type'      => ['product', 'product_variation'],
        //         'posts_per_page' => 1,
        //         'post_status'    => 'publish',
        //         'no_found_rows'  => true,
        //     ];

        //     $query = new WP_Query($args);
        //     remove_filter('posts_where', $filter, 10);

        //     if (!empty($query->posts)) {
        //         $isfound    = true;
        //         $product_id = $query->posts[0]->ID;
        //     }
        // }
        /**
         * === STEP 3: If found, check category match ===
         */
        if ($isfound && $product_id > 0) {
            $product = wc_get_product($product_id);
        }

        /**
         * === STEP 4: If still not found, create new product ===
         */
        if (!$isfound) 
        {
            $new_product = new WC_Product_Simple();
            $new_product->set_name(ucwords(trim($params['item_name'])));
            $new_product->set_status('publish');
            $new_product->set_catalog_visibility('visible');
            $new_product->set_regular_price($item_price);
            $new_product->set_manage_stock(true);
            $new_product->set_stock_quantity($item_qty);
            $new_product->set_stock_status('instock');
            $new_product->set_category_ids([$mini_category_id]);
            $new_product->set_sku($item_sku);
            // $logger  = wc_get_logger();
            // $context = [ 'source' => 'pos-tax' ];

            // $logger->info( 'Raw params received: ' . print_r( $params, true ), $context );
            if ( isset( $params['tax_class'] ) && $params['tax_class'] !== null && $params['tax_class'] !== '' ) {

                // Treat tax_id as tax class slug
                $tax_class = sanitize_title( $params['tax_class'] );

                // $logger->info( 'Applying tax class slug: ' . $tax_class, $context );

                $new_product->set_tax_status( 'taxable' );
                $new_product->set_tax_class( $tax_class );

                // $logger->info( 'Tax class applied successfully', $context );
            }
            $product_id = $new_product->save();

            if (!empty($item_note)) {
                update_post_meta($product_id, '_item_note', $item_note);
            }

            // Attach image
            if (!empty($image_id)) {
                set_post_thumbnail($product_id, $image_id);
            }

            return new WP_REST_Response([
                'status'  => 'created',
                'message' => "New product '{$params['item_name']}' created successfully.",
                'item_id'      => $product_id,
                'item_price'   => $item_price,
                'item_qty'     => $item_qty,
                'item_note'    => $item_note,
                'image_id'   => $image_id,
                'image_url'  => $image_id ? wp_get_attachment_url($image_id) : '',
            ], 200);
        }
        else
        {
            $current_stock = (float) $product->get_stock_quantity();
            $new_stock     = $current_stock + $item_qty;

            $product->set_manage_stock(true);
            $product->set_stock_quantity($new_stock);
            // $logger  = wc_get_logger();
            // $context = [ 'source' => 'pos-tax' ];

            // $logger->info( 'Raw params received: ' . print_r( $params, true ), $context );
            if ( isset( $params['tax_class'] ) && $params['tax_class'] !== null && $params['tax_class'] !== '' ) {

                // Treat tax_id as tax class slug
                $tax_class = sanitize_title( $params['tax_class'] );

                // $logger->info( 'Applying tax class slug: ' . $tax_class, $context );

                $product->set_tax_status( 'taxable' );
                $product->set_tax_class( $tax_class );

                // $logger->info( 'Tax class applied successfully', $context );
            }
            $product->save();

            if (!empty($item_note)) {
                update_post_meta($product_id, '_item_note', $item_note);
            }

            // Replace image
            if (!empty($image_id)) {
                set_post_thumbnail($product_id, $image_id);
            }

            return new WP_REST_Response([
                'status'     => 'updated',
                'message'    => "Product '{$product->get_name()}' updated successfully.",
                'item_id'         => $product_id,
                'new_stock'  => $new_stock,
                'item_note'       => $item_note,
                'image_id'   => $image_id,
                'image_url' => $image_id ? wp_get_attachment_url($image_id) : '',
            ], 200);
        }
        return new WP_Error('not_processed', 'Unable to process request.', ['status' => 500]);
    }
    public function show_category_sublist($request)
    {
        // $parent_id = absint( $request->get_param( 'category_id' ) );

        // if ( ! $parent_id ) {
        //     return new WP_Error(
        //         'missing_category',
        //         'category_id is required',
        //         [ 'status' => 400 ]
        //     );
        // }

        // // Validate category
        // $parent_term = get_term( $parent_id, 'product_cat' );
        // if ( ! $parent_term || is_wp_error( $parent_term ) ) {
        //     return new WP_Error(
        //         'invalid_category',
        //         'Invalid category ID',
        //         [ 'status' => 404 ]
        //     );
        // }

        // // Fetch direct children ONLY (no deep recursion here)
        // $terms = get_terms( [
        //     'taxonomy'   => 'product_cat',
        //     'parent'     => $parent_id,
        //     'hide_empty' => false,
        //     'orderby'    => 'menu_order',
        //     'order'      => 'ASC',
        // ] );

        // if ( is_wp_error( $terms ) ) {
        //     return $terms;
        // }

        // $categories = [];

        // foreach ( $terms as $term ) {

        //     // Lightweight check: does this category have children?
        //     $child_check = get_terms( [
        //         'taxonomy'   => 'product_cat',
        //         'parent'     => $term->term_id,
        //         'hide_empty' => false,
        //         'number'     => 1,
        //         'fields'     => 'ids',
        //     ] );

        //     $categories[] = [
        //         'id'           => $term->term_id,
        //         'name'         => $term->name,
        //         'slug'         => $term->slug,
        //         'has_children' => ! empty( $child_check ),
        //     ];
        // }

        // return [
        //     'parent_id'   => $parent_id,
        //     'parent_name' => $parent_term->name,
        //     'level'       => $this->calculate_level( $parent_term ),
        //     'categories'  => $categories,
        // ];
        $parent_id = absint( $request->get_param( 'category_id' ) );

        if ( ! $parent_id ) {
            return new WP_Error(
                'missing_category',
                'category_id is required',
                [ 'status' => 400 ]
            );
        }

        $parent_term = get_term( $parent_id, 'product_cat' );
        if ( ! $parent_term || is_wp_error( $parent_term ) ) {
            return new WP_Error(
                'invalid_category',
                'Invalid category ID',
                [ 'status' => 404 ]
            );
        }

        $terms = get_terms( [
            'taxonomy'   => 'product_cat',
            'parent'     => $parent_id,
            'hide_empty' => false,
            'orderby'    => 'menu_order',
            'order'      => 'ASC',
        ] );

        $categories = [];

        foreach ( $terms as $term ) {
            $children_terms = get_terms( [
                'taxonomy'   => 'product_cat',
                'parent'     => $term->term_id,
                'hide_empty' => false,
                'orderby'    => 'menu_order',
                'order'      => 'ASC',
            ] );

            $children = [];

            foreach ( $children_terms as $child ) {
                $children[] = [
                    'id'   => $child->term_id,
                    'name' => $child->name,
                ];
            }

            $categories[] = [
                'id'       => $term->term_id,
                'name'     => $term->name,
                'children' => $children,
            ];
        }

        return [
            'parent_id'   => $parent_id,
            'parent_name' => $parent_term->name,
            'categories'  => $categories,
        ];
    }
    public function get_all_search_categories() {

        $parent_slugs = [ 'alcohol', 'bewerages','beverages' ];
        $parent_ids   = [];

        // Get parent category IDs from slugs
        foreach ( $parent_slugs as $slug ) {
            $term = get_term_by( 'slug', $slug, 'product_cat' );
            if ( $term && ! is_wp_error( $term ) ) {
                $parent_ids[] = (int) $term->term_id;
            }
        }

        if ( empty( $parent_ids ) ) {
            return rest_ensure_response( [] );
        }

        $categories = [];
        foreach ( $parent_ids as $parent_id ) {

            $terms = get_terms( [
                'taxonomy'   => 'product_cat',
                'parent'     => (int) $parent_id,
                'hide_empty' => false,
                'orderby'    => 'menu_order',
                'order'      => 'ASC',
            ] );

            if ( is_wp_error( $terms ) || empty( $terms ) ) {
                continue;
            }

            foreach ( $terms as $term ) {
                $categories[] = [
                    'id'   => (int) $term->term_id,
                    'name' => $term->name,
                ];
            }
        }
        return rest_ensure_response( $categories );
    }
    public function get_category_ids() {
        $parent_slugs = [ 'alcohol', 'bewerages','beverages' ];
        $parent_ids   = [];

        // Get parent category IDs from slugs
        foreach ( $parent_slugs as $slug ) {
            $term = get_term_by( 'slug', $slug, 'product_cat' );
            if ( $term && ! is_wp_error( $term ) ) {
                $parent_ids[] = (int) $term->term_id;
            }
        }

        if ( empty( $parent_ids ) ) {
            return rest_ensure_response( [] );
        }

        $category_ids = [];

        foreach ( $parent_ids as $parent_id ) {

            $terms = get_terms( [
                'taxonomy'   => 'product_cat',
                'parent'     => (int) $parent_id,
                'hide_empty' => false,
                'orderby'    => 'menu_order',
                'order'      => 'ASC',
                'fields'     => 'ids',
            ] );

            if ( is_wp_error( $terms ) || empty( $terms ) ) {
                continue;
            }

            $category_ids = array_merge( $category_ids, $terms );
        }

        return array_values( array_unique( $category_ids ) );
    }
    public function pinaka_get_product_all_category_ids( $product ) {
        if ( is_numeric( $product ) ) {
            $product = wc_get_product( $product );
        }

        if ( ! $product instanceof WC_Product ) {
            return [];
        }

        // Handle variations → use parent product
        if ( $product->is_type( 'variation' ) ) {
            $product_id = $product->get_parent_id();
        } else {
            $product_id = $product->get_id();
        }

        // Get directly assigned categories
        $cat_ids = wc_get_product_term_ids( $product_id, 'product_cat' );

        if ( empty( $cat_ids ) ) {
            return [];
        }

        $all_ids = $cat_ids;

        // Add parent (ancestor) categories
        foreach ( $cat_ids as $cat_id ) {
            $ancestors = get_ancestors( $cat_id, 'product_cat' );
            if ( ! empty( $ancestors ) ) {
                $all_ids = array_merge( $all_ids, $ancestors );
            }
        }

        return array_values( array_unique( array_map( 'intval', $all_ids ) ) );
    }
}
?>