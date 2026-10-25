<?php
if (!defined('ABSPATH')) {
    exit;
}

class Pinaka_Combo_Bundles_API_Controller {

    protected $namespace = 'pinaka-restaurant-pos/v1';
    protected $rest_base = 'combos';

    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/get-all-combos',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_all_combos' ],
                'permission_callback' => [ $this, 'check_user_role_permission' ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/search-combos',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'search_combos' ],
                'permission_callback' => [ $this, 'check_user_role_permission' ],
            ]
        );
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

    private function prepare_combo_product_for_response($product_id) 
    {
        $product = wc_get_product($product_id);
        if (!$product) {
            return new WP_Error(
                'invalid_product',
                __('Product not found.', 'pinaka-pos'),
                ['status' => 404]
            );
        }
        // Get combo bundle items from post meta
        $combo_items = json_decode(get_post_meta($product_id, '_combo_bundle_items', true), true);

        $children = [];

        if (!empty($combo_items) && is_array($combo_items)) 
        {
            foreach ($combo_items as $item) 
            {
                $child_product = wc_get_product($item['id']);
                if ($child_product) 
                {
                    $children[] = [
                        'id'       => $child_product->get_id(),
                        'name'     => $child_product->get_name(),
                        'price'    => wc_get_price_to_display($child_product),
                        'quantity' => intval($item['qty']),
                    ];
                }
            }
        }

        // Build combo product data
        $data = [
            'item id'       => $product->get_id(),
            'item name'     => $product->get_name(),
            'item sku'      => $product->get_sku(),
            'item image'    => $product->get_image_id() ? wp_get_attachment_url($product->get_image_id()) : false,
            'item price'    => wc_get_price_to_display($product),
            'sub-items' => $children,
        ];

        return $data;
    }

    public function get_all_combos() 
    {
        $products = get_posts([
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'     => '_combo_bundle_items',
                    'compare' => 'EXISTS',
                ],
            ],
        ]);
        if (empty($products))
        {
            return rest_ensure_response([
                'status'  => 'success',
                'data'    => [],
                'message' => __('No Combo products found.', 'pinaka-pos'),
            ]);
        }

        // Prepare response data
        $data = [];
        foreach ($products as $product) 
        {
            $prepared = $this->prepare_combo_product_for_response($product->ID);
            if ($prepared) {
                $data[] = $prepared;
            }
        }

        return rest_ensure_response([ 'status' => 'success', 'data' => $data ]);
    }

    public function search_combos($request) 
    {
        global $wpdb;

        if (!isset($request['product_id']) || empty($request['product_id'])) 
        {
            return new WP_Error(
                'invalid_product_id',
                __('Search Product Id is required.', 'pinaka-pos'),
                ['status' => 400]
            );
        }

        $product_id = sanitize_text_field($request['product_id']);
        
        $product_ids = $wpdb->get_col($wpdb->prepare("
            SELECT p.ID
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'product'
            AND p.post_status = 'publish'
            AND p.ID = %d
            AND pm.meta_key = '_combo_bundle_items'
        ", $product_id));
     
        if (empty($product_ids)) 
        {
            return rest_ensure_response([
                'status'  => 'success',
                'data'    => [],
                'message' => __('No Combo product found.', 'pinaka-pos'),
            ]);
        }

        $data = [];
        foreach ($product_ids as $product_id) 
        {
            $prepared = $this->prepare_combo_product_for_response($product_id);
            if ($prepared) {
                $data[] = $prepared;
            }
        }
        return rest_ensure_response(['status' => 'success', 'data' => $data]);
    }
}
