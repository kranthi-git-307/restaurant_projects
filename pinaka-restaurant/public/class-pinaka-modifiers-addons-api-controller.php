<?php
/**
 * REST API Zones API controller
 *
 * Handles requests to the Zones API endpoint.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Zones Controller class.
 */
class Pinaka_Modifier_Addons_Api_Controller {

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
	protected $rest_base = 'modifiers-addons';

	/**
	 * Register the routes for Zones.
	 */
	public function register_routes() {
 
		// register_rest_route(
		// 	$this->namespace,
		// 	'/' . $this->rest_base . '/get-all-modifiers',
		// 	array(
		// 		'methods'             => 'GET',
		// 		'callback'            => array( $this, 'get_all_modifiers' ),
		// 		'permission_callback' => array($this, 'check_user_role_permission'),
		// 	)
		// );

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/get-modifiers',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_modifiers' ),
				'permission_callback' => array($this, 'check_user_role_permission'),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/get-addons',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_addons' ),
				'permission_callback' => array($this, 'check_user_role_permission'),
			)
		);

        register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/get-modifiers-by-product-id',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_modifiers_by_product_id' ),
				'permission_callback' => array($this, 'check_user_role_permission'),
			)
		);
        register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/get-modifiers-by-subcategory-id',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_modifiers_by_subcategory_id' ),
				'permission_callback' => array($this, 'check_user_role_permission'),
			)
		);
	}
	/**
	 * Check whether a given request has permission to view system status.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	// public function check_user_role_permission( $request ) 
	// {
	// 	$user_id = get_current_user_id();
	// 	$user    = get_userdata( $user_id );
	// 	if ( $user == null ) {
	// 		return new WP_Error( 'pinakapos_rest_cannot_view', esc_html__( 'Sorry, you cannot give permission.', 'pinaka-pos' ), array( 'status' => rest_authorization_required_code() ) );
	// 	}
	// 	if ( in_array( 'administrator', (array) $user->roles ) ) {
	// 		return true;

	// 	} elseif ( in_array( 'shop_manager', (array) $user->roles ) ) {
	// 		return true;

	// 	} elseif ( in_array( 'employee', (array) $user->roles ) ) {
	// 		return true;

	// 	} else {
	// 		return new WP_Error( 'pinakapos_rest_cannot_view', esc_html__( 'Sorry, you cannot give permission.', 'pinaka-pos' ), array( 'status' => rest_authorization_required_code() ) );
	// 	}
	// }
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
    /**
     * Prepare a single category for API response
     */
    private function prepare_modifier_for_response($modifier) {

        return [
            'id'          => $modifier->ID,
            'restaurant_id' => intval(get_post_meta($modifier->ID, 'restaurant_id', true)),
            'name'        => $modifier->post_title,
            'price'       => floatval(get_post_meta($modifier->ID, 'modifier_price', true)),
            'type'       => get_post_meta($modifier->ID, 'modifier_type', true),
        ];
    }
	public function get_modifiers() 
    {
        // Load only modifiers for this product
        $modifiers = get_posts([
            'post_type'      => 'modifier',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => [
                [
                    'key'     => 'modifier_type',
                    'value'   => 'modifier',
                    'compare' => '='
                ]
            ]
        ]);
        $data = [];
        foreach ($modifiers as $modifier) 
        {
            $prepared = $this->prepare_modifier_for_response($modifier);
            $data[] = $prepared;
        }
        return rest_ensure_response(['status' => "success", 'data' => $data]);
    }
	public function get_addons() 
    {
        // Load only modifiers for this product
        $modifiers = get_posts([
            'post_type'      => 'modifier',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => [
                [
                    'key'     => 'modifier_type',
                    'value'   => 'add-on',
                    'compare' => '='
                ]
            ]
        ]);
        $data = [];
        foreach ($modifiers as $modifier) 
        {
            $prepared = $this->prepare_modifier_for_response($modifier);
            $data[] = $prepared;
        }
        return rest_ensure_response(['status' => "success", 'data' => $data]);
    }
    // public function get_modifiers_by_product_id($request) 
    // {
    //     $product_id = isset($request['product_id']) ? intval($request['product_id']) : 0;
      
    //     if (get_post_type($product_id) !== 'product') {
    //         return new WP_Error(
    //             'pinakapos_rest_invalid_product_id',
    //             esc_html__('Invalid or non-existent product ID.', 'pinaka-pos'),
    //             array('status' => 400)
    //         );
    //     }
    //     // // Load only modifiers for this product
    //     // $modifiers = get_posts([
    //     //     'post_type'      => 'modifier',
    //     //     'posts_per_page' => -1,
    //     //     'post_status'    => 'publish',
    //     //     'meta_query'     => [
    //     //         [
    //     //             'key'     => 'product_ids',
    //     //             'value'   => 'i:' . intval($product_id) . ';',
    //     //             'compare' => 'REGEXP',
    //     //         ],
    //     //     ]
    //     // ]);
    //     // $categories = wp_get_post_terms($product_id, 'product_cat', ['orderby' => 'term_id', 'order' => 'ASC']);
    //     // $current_category = !empty($categories) ? $categories[0] : null;

    //     // if ($current_category) {
    //     //     echo "Current Category: " . $current_category->name . " (ID: " . $current_category->term_id . ")";
    //     // }
    //     // die;
    //     $term_ids = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);
    //     if (empty($term_ids)) {
    //         $term_ids = [];
    //     }
    //     $category_modifier_map = get_option('pinaka_category_modifier_map', []);
    //     $category_addon_map    = get_option('pinaka_category_addon_map', []);

    //     $modifier_ids = [];
    //     $addon_ids    = [];

    //     foreach ($term_ids as $term_id) {
    //         if (!empty($category_modifier_map[$term_id])) {
    //             $modifier_ids = array_merge($modifier_ids, $category_modifier_map[$term_id]);
    //         }
    //         if (!empty($category_addon_map[$term_id])) {
    //             $addon_ids = array_merge($addon_ids, $category_addon_map[$term_id]);
    //         }
    //     }

    //     $direct_modifiers = get_posts([
    //         'post_type'      => 'modifier',
    //         'posts_per_page' => -1,
    //         'post_status'    => 'publish',
    //         'meta_query'     => [
    //             [
    //                 'key'     => 'product_ids',
    //                 'value'   => 'i:' . intval($product_id) . ';',
    //                 'compare' => 'REGEXP',
    //             ],
    //         ],
    //         'fields' => 'ids'
    //     ]);

    //     // Merge all IDs
    //     $all_modifier_ids = array_unique(array_merge($modifier_ids, $addon_ids, $direct_modifiers));

    //     if (empty($all_modifier_ids)) {
    //         return [];
    //     }

    //     $modifiers = get_posts([
    //         'post_type'      => 'modifier',
    //         'posts_per_page' => -1,
    //         'post_status'    => 'publish',
    //         'post__in'       => $all_modifier_ids,
    //         'orderby'        => 'title',
    //         'order'          => 'ASC',
    //     ]);
    //     $data = [];
    //     foreach ($modifiers as $modifier) 
    //     {
    //         $prepared = $this->prepare_modifier_for_response($modifier);
    //         $data[] = $prepared;
    //     }
    //     return rest_ensure_response(['status' => "success", 'data' => $data]);
    // }
    // public function get_modifiers_by_product_id($request)
    // {
    //     $product_id = isset($request['product_id']) ? intval($request['product_id']) : 0;

    //     if (get_post_type($product_id) !== 'product') {
    //         return new WP_Error(
    //             'pinakapos_rest_invalid_product_id',
    //             esc_html__('Invalid or non-existent product ID.', 'pinaka-pos'),
    //             array('status' => 400)
    //         );
    //     }

    //     $term_ids = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);
    //     if (empty($term_ids)) {
    //         $term_ids = [];
    //     }

    //     $all_related_term_ids = [];

    //     foreach ($term_ids as $term_id) {
    //         $all_related_term_ids[] = $term_id; // include current category

    //         $parent_id = wp_get_term_taxonomy_parent_id($term_id, 'product_cat');
    //         while ($parent_id && $parent_id != 0) {
    //             $all_related_term_ids[] = $parent_id;
    //             $parent_id = wp_get_term_taxonomy_parent_id($parent_id, 'product_cat');
    //         }
    //     }

    //     $all_related_term_ids = array_unique($all_related_term_ids);

    //     $category_modifier_map = get_option('pinaka_category_modifier_map', []);
    //     $category_addon_map    = get_option('pinaka_category_addon_map', []);

    //     $modifier_ids = [];
    //     $addon_ids    = [];

    //     foreach ($all_related_term_ids as $term_id) {
    //         if (!empty($category_modifier_map[$term_id])) {
    //             $modifier_ids = array_merge($modifier_ids, $category_modifier_map[$term_id]);
    //         }
    //         if (!empty($category_addon_map[$term_id])) {
    //             $addon_ids = array_merge($addon_ids, $category_addon_map[$term_id]);
    //         }
    //     }

    //     $direct_modifiers = get_posts([
    //         'post_type'      => 'modifier',
    //         'posts_per_page' => -1,
    //         'post_status'    => 'publish',
    //         'meta_query'     => [
    //             [
    //                 'key'     => 'product_ids',
    //                 'value'   => 'i:' . intval($product_id) . ';',
    //                 'compare' => 'REGEXP',
    //             ],
    //         ],
    //         'fields' => 'ids',
    //     ]);

    //     $all_modifier_ids = array_unique(array_merge($modifier_ids, $addon_ids, $direct_modifiers));

    //     if (empty($all_modifier_ids)) {
    //         return rest_ensure_response(['status' => 'success', 'data' => []]);
    //     }

    //     $modifiers = get_posts([
    //         'post_type'      => 'modifier',
    //         'posts_per_page' => -1,
    //         'post_status'    => 'publish',
    //         'post__in'       => $all_modifier_ids,
    //         'orderby'        => 'title',
    //         'order'          => 'ASC',
    //     ]);

    //     $data = [];
    //     $seen = [];
        
    //     foreach ($modifiers as $modifier) {
    //         $restaurant_id = (int) get_post_meta($modifier->ID, '_restaurant_id', true);
    //         // Normalize name to avoid case/space duplicates
    //         $name = strtolower(trim($modifier->post_title));

    //         if (isset($seen[$restaurant_id][$name])) {
    //             continue; // Skip duplicate for this restaurant
    //         }

    //         $seen[$restaurant_id][$name] = true;
    //         $prepared = $this->prepare_modifier_for_response($modifier);
    //         $data[] = $prepared;
    //     }

    //     return rest_ensure_response(['status' => 'success', 'data' => $data]);
    // }
    public function get_modifiers_by_product_id($request)
    {
        $product_id = isset($request['product_id']) ? intval($request['product_id']) : 0;
        $user_id = get_current_user_id();
        $restaurant_id = isset($request['restaurant_id']) ? intval($request['restaurant_id']) : intval(get_user_meta($user_id, 'user_restaurant_id', true));
        if(empty($restaurant_id))
        {
            return new WP_Error(
                'pinakapos_rest_invalid_rest_id',
                esc_html__('Invalid or non-existent Restaurant ID.', 'pinaka-pos'),
                array('status' => 400)
            );
        }
        if (get_post_type($product_id) !== 'product') {
            return new WP_Error(
                'pinakapos_rest_invalid_product_id',
                esc_html__('Invalid or non-existent product ID.', 'pinaka-pos'),
                array('status' => 400)
            );
        }

        // $term_ids = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);
        // if (empty($term_ids)) {
        //     $term_ids = [];
        // }

        // $all_related_term_ids = [];

        // foreach ($term_ids as $term_id) {
        //     $all_related_term_ids[] = $term_id;

        //     $parent_id = wp_get_term_taxonomy_parent_id($term_id, 'product_cat');
        //     while ($parent_id && $parent_id != 0) {
        //         $all_related_term_ids[] = $parent_id;
        //         $parent_id = wp_get_term_taxonomy_parent_id($parent_id, 'product_cat');
        //     }
        // }

        // $all_related_term_ids = array_unique($all_related_term_ids);

        // $category_modifier_map = get_option('pinaka_category_modifier_map', []);
        // $category_addon_map    = get_option('pinaka_category_addon_map', []);

        // $modifier_ids = [];
        // $addon_ids    = [];

        // foreach ($all_related_term_ids as $term_id) {
        //     if (!empty($category_modifier_map[$term_id])) {
        //         $modifier_ids = array_merge($modifier_ids, $category_modifier_map[$term_id]);
        //     }
        //     if (!empty($category_addon_map[$term_id])) {
        //         $addon_ids = array_merge($addon_ids, $category_addon_map[$term_id]);
        //     }
        // }

        $direct_modifiers = get_posts([
            'post_type'      => 'modifier',
            'posts_per_page' => -1,
            'post_status'   => 'publish',
            'meta_query'    => [
                [
                    'key'     => 'product_ids',
                    'value'   => 'i:' . $product_id . ';',
                    'compare' => 'REGEXP',
                ],
                [
                    'key'     => 'restaurant_id',
                    'value'   => $restaurant_id,
                    'compare' => '=',
                ]
            ],
            'fields' => 'ids',
        ]);

        // $all_modifier_ids = array_unique(array_merge($modifier_ids, $addon_ids, $direct_modifiers));
        if (empty($direct_modifiers)) {
            return rest_ensure_response(['status' => 'success', 'data' => []]);
        }
        // $modifiers = get_posts([
        //     'post_type'      => 'modifier',
        //     'posts_per_page' => -1,
        //     'post_status'    => 'publish',
        //     'post__in'       => $all_modifier_ids,
        //     'orderby'        => 'title',
        //     'order'          => 'ASC',
        // ]);
        $modifiers = get_posts([
            'post_type'      => 'modifier',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'post__in'       => $direct_modifiers,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);

        $data = [];
        $seen = [];
        
        foreach ($modifiers as $modifier) {
            $restaurant_id = (int) get_post_meta($modifier->ID, 'restaurant_id', true);
            $modfier_type = get_post_meta($modifier->ID, 'modifier_type', true);
            $name = strtolower(trim($modifier->post_title));

            if (isset($seen[$restaurant_id][$modfier_type][$name])) {
                continue;
            }

            $seen[$restaurant_id][$modfier_type][$name] = true;
            $prepared = $this->prepare_modifier_for_response($modifier);
            $data[] = $prepared;
        }

        return rest_ensure_response(['status' => 'success', 'data' => $data]);
    }
    /**
     * Helper to get parent term ID (WordPress doesn’t have direct function)
     */
    private function wp_get_term_taxonomy_parent_id($term_id, $taxonomy) {
        $term = get_term($term_id, $taxonomy);
        return ($term && !is_wp_error($term)) ? (int) $term->parent : 0;
    }

    public function get_modifiers_by_subcategory_id($request) 
    {
        $subcategory_id = isset($request['subcategory_id']) ? intval($request['subcategory_id']) : 0;
        $term = get_term($subcategory_id);
        if (!$term || is_wp_error($term)) {
            return new WP_Error(
                'pinakapos_rest_invalid_subcategory_id',
                esc_html__('Invalid or non-existent subcategory ID.', 'pinaka-pos'),
                array('status' => 400)
            );
        }
        // Load only modifiers for this product
        $modifiers = get_posts([
            'post_type'      => 'modifier',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => [
                [
                    'key'     => 'subcategory_ids',
                    'value'   => 'i:' . intval($subcategory_id) . ';',
                    'compare' => 'REGEXP',
                ],
            ]
        ]);
        $data = [];
        foreach ($modifiers as $modifier) 
        {
            $prepared = $this->prepare_modifier_for_response($modifier);
            $data[] = $prepared;
        }
        return rest_ensure_response(['status' => "success", 'data' => $data]);
    }
}