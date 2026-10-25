<?php
/**
 * REST API Orders controller
 *
 * Handles requests to the /orders endpoint.
 *
 * @package WooCommerce\RestApi
 * @since    2.6.0
 */

use Automattic\WooCommerce\Utilities\OrderUtil;

defined( 'ABSPATH' ) || exit;

/**
 * REST API Orders controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Orders_V2_Controller
 */
class MP_REST_Orders_Controller extends WC_REST_Orders_V2_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'pinaka-restaurant-pos/v1';
	/**
	 * Register the routes for orders.
	 */
	public function register_routes() {
		
		parent::register_routes();
		
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/get-line-items',
			array(
				array(
					// 'methods'             => WP_REST_Server::READABLE,
					'methods'             => 'GET',
					'callback'            => array( $this, 'pinaka_get_line_items' ),
					'permission_callback' => array( $this, 'check_user_role_permission' )
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/void-item-selection',
			array(
				array(
					// 'methods'             => WP_REST_Server::EDITABLE,
					'methods'             => 'POST',
					'callback'            => array( $this, 'pinaka_void_item_selection' ),
					'permission_callback' => array( $this, 'check_user_role_permission' )
				),
			)
		);

        register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/order-kot-edit',
			array(
				array(
					// 'methods'             => WP_REST_Server::EDITABLE,
					'methods'             => 'POST',
					'callback'            => array( $this, 'pinaka_order_kot_edit' ),
					'permission_callback' => array( $this, 'check_user_role_permission' )
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/order-kot-edit-reasons',
			array(
				array(
					// 'methods'             => WP_REST_Server::EDITABLE,
					'methods'             => 'GET',
					'callback'            => array( $this, 'pinaka_order_kot_edit_reasons' ),
					'permission_callback' => array( $this, 'check_user_role_permission' )
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/order-kot-list',
			array(
				array(
					// 'methods'             => WP_REST_Server::EDITABLE,
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_order_kot_list' ),
					'permission_callback' => array( $this, 'check_user_role_permission' )
				),
			)
		);

// 		register_rest_route(
// 			$this->namespace,
// 			'/' . $this->rest_base . '/order-kot-update',
// 			array(
// 				array(
// 					// 'methods'             => WP_REST_Server::EDITABLE,
// 					'methods'             => 'POST',
// 					'callback'            => array( $this, 'pinaka_order_kot_update' ),
// 					'permission_callback' => array( $this, 'check_user_role_permission' )
// 				),
// 			)
// 		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/repeat-kot-order',
			array(
				array(
					// 'methods'             => WP_REST_Server::EDITABLE,
					'methods'             => 'POST',
					'callback'            => array( $this, 'pinaka_repeat_kot_order' ),
					'permission_callback' => array( $this, 'check_user_role_permission' )
				),
			)
		);
		register_rest_route(
			$this->namespace,
            '/' . $this->rest_base . '/get-order-items',
			[
				// 'methods'              => WP_REST_Server::READABLE,
				'methods'             => 'GET',
				'callback'             => [$this, 'get_all_order_items'],
				'permission_callback'  => [$this, 'check_user_role_permission'],
            ]
		);
		
		register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/update-service-charge',
            [
                'methods' => 'POST',
                'callback' => [$this, 'update_service_charge'],
                'permission_callback' => [$this, 'check_user_role_permission'],
            ]
        );
        
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/delete-service-charge',
            [
                'methods' => 'POST',
                'callback' => [$this, 'delete_service_charge'],
                'permission_callback' => [$this, 'check_user_role_permission'],
            ]
        );
		register_rest_route(
			$this->namespace,
            '/' . $this->rest_base . '/get-order-percentage',
			[
				// 'methods'              => WP_REST_Server::READABLE,
				'methods'             => 'GET',
				'callback'             => [$this, 'pinaka_discount_calculate'],
				'permission_callback'  => [$this, 'check_user_role_permission'],
            ]
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/items',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'order_items_callback' ),
					// 'permission_callback' => 'my_custom_permission_callback',
					'permission_callback' => array( $this, '[]' ),
					// 'args'                => $this->get_collection_params(),
				),
				// 'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/gst-report',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'order_items_gst_report_callback' ),
					// 'permission_callback' => 'my_custom_permission_callback',
					'permission_callback' => array( $this, '[]' ),
					// 'args'                => $this->get_collection_params(),
				),
				// 'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/total-orders',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'pinaka_get_total_orders' ),
					// 'permission_callback' => 'my_custom_permission_callback',
					'permission_callback' => array( $this, 'get_items_permissions_check' )
				),
				// 'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route( 
			$this->namespace,
			'/' . $this->rest_base . '/discount-reasons',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'pinaka_get_discount_reasons' ),
					// 'permission_callback' => 'my_custom_permission_callback',
					'permission_callback' => array( $this, 'check_user_role_permission' )
				),
				// 'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route( 
			$this->namespace,
			'/' . $this->rest_base . '/add-discount',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'pinaka_pos_add_discount' ),
					// 'permission_callback' => 'my_custom_permission_callback',
					'permission_callback' => array( $this, 'check_user_role_permission' )
				),
				// 'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route( 
			$this->namespace,
			'/' . $this->rest_base . '/remove-discount',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'pinaka_pos_remove_discount' ),
					// 'permission_callback' => 'my_custom_permission_callback',
					'permission_callback' => array( $this, 'check_user_role_permission' )
				),
				// 'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route( 
			$this->namespace,
			'/' . $this->rest_base . '/add-payout',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'pinaka_pos_add_payout' ),
					// 'permission_callback' => 'my_custom_permission_callback',
					'permission_callback' => array( $this, 'get_items_permissions_check' )
				),
				// 'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route( 
			$this->namespace,
			'/' . $this->rest_base . '/add-tip',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'pinaka_pos_add_tip' ),
					// 'permission_callback' => 'my_custom_permission_callback',
					'permission_callback' => array( $this, 'check_user_role_permission' )
				),
				// 'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route( 
			$this->namespace,
			'/' . $this->rest_base . '/remove-tip',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'pinaka_pos_remove_tip' ),
					// 'permission_callback' => 'my_custom_permission_callback',
					'permission_callback' => array( $this, 'check_user_role_permission' )
				),
				// 'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
        register_rest_route( 
			$this->namespace,
			'/' . $this->rest_base . '/view-order-info',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'view_order_information' ),
					// 'permission_callback' => 'my_custom_permission_callback',
					'permission_callback' => array( $this, 'check_user_role_permission' )
				),
				// 'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/voided-items',
			[
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'pinaka_get_voided_items_by_kot' ],
					'permission_callback' => [ $this, 'check_user_role_permission' ],
					'args' => [
						'order_id' => [
							'required' => true,
							'type'     => 'integer',
						],
					],
				],
			]
		);

		add_action(
			'woocommerce_rest_insert_shop_order_object',
			[$this, 'mp_store_kot_snapshot'],
			20,
			3
		);

		add_action(
            'woocommerce_rest_pre_insert_shop_order_object',
            array( $this, 'pos_pre_insert_order_validation' ),
            10,
            3
        );

        add_action(
            'woocommerce_rest_insert_shop_order_object',
            array( $this, 'pos_after_insert_order_meta' ),
            10,
            3
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

	public function pinaka_get_voided_items_by_kot( WP_REST_Request $request ) {
		global $wpdb;

		$order_id = absint( $request->get_param( 'order_id' ) );

		if ( ! $order_id ) {
			return new WP_Error(
				'invalid_order_id',
				'Invalid order ID',
				[ 'status' => 400 ]
			);
		}

		$table = $wpdb->prefix . 'order_void_logs';

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT id, action, user_id, data, created_at
				FROM {$table}
				WHERE post_id = %d
				AND post_type = %s
				AND action = %s
				ORDER BY created_at DESC
				",
				$order_id,
				'shop_order',
				'item_voided'
			),
			ARRAY_A
		);

		if ( empty( $results ) ) {
			return rest_ensure_response( [
				'order_id' => $order_id,
				'items'    => [],
			] );
		}

		$items = [];

		foreach ( $results as $row ) {
			$data = json_decode( $row['data'], true );

			$items[] = [
				//'log_id'     => (int) $row['id'],
				'item_id'    => $data['item_id'] ?? null,
				'product'    => $data['product'] ?? '',
				'orig_qty'   => (int) ( $data['orig_qty'] ?? 0 ),
				'new_qty'    => (int) ( $data['new_qty'] ?? 0 ),
				'voided_qty' => (int) ( $data['voided_qty'] ?? 0 ),
				'item_total' => $data['item_total'] ?? '0.00',
				'remarks'    => $data['remarks'] ?? '',
				'voided_by'  => (int) $row['user_id'],
				'voided_at'  => $row['created_at'],
			];
		}

		return rest_ensure_response( [
			'kot_id' => $order_id,
			'count'    => count( $items ),
			'items'    => $items,
		] );
	}

	public function mp_store_kot_snapshot($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) return;
    
        $parent_id = $order->get_parent_id();
    
        // run only for KOT child orders
        if (!$parent_id) return;
    
        $parent_order = wc_get_order($parent_id);
        if (!$parent_order) return;
        
        // New KOT means the previous bill is no longer valid.
        // Reset generated bill status.
        $parent_order->update_meta_data('is_generated_bill', '0');
        $parent_order->save();
    
        // ✅ get existing snapshot
        $existing_snapshot = $parent_order->get_meta('_kot_initial_items', true);
    
        if (!is_array($existing_snapshot)) {
            $existing_snapshot = [];
        }
    
        // collect existing line item ids to prevent duplicates
        $existing_ids = array_column($existing_snapshot, 'line_item_id');
    
        foreach ($order->get_items() as $item_id => $item) {
    
            // skip if already captured
            if (in_array($item_id, $existing_ids)) {
                continue;
            }
    
            $product = $item->get_product();
    
            /* -------- MODIFIERS -------- */
            $modifiers = [];
            $modifier_amount = 0;
    
            foreach ($item->get_meta_data() as $meta) {
    
                if (in_array($meta->key, ['_addons','_modifiers'], true) && is_array($meta->value)) {
                    foreach ($meta->value as $mod) {
                        if (!empty($mod['name'])) {
                            $modifiers[] = $mod['name'];
                        }
                    }
                }
    
                if ($meta->key === '_extra_modifier_amount') {
                    $modifier_amount += (float) $meta->value;
                }
            }
    
            /* -------- COMBO ITEMS -------- */
            $combo_items = [];
    
            if ($product && $product->is_type('combo_bundle')) {
    
                $combo_meta = json_decode(
                    get_post_meta($product->get_id(), '_combo_bundle_items', true),
                    true
                );
    
                if (is_array($combo_meta)) {
                    foreach ($combo_meta as $combo_item) {
    
                        $child_product = wc_get_product($combo_item['id']);
    
                        if ($child_product) {
                            $combo_items[] = [
                                'id'       => (int) $child_product->get_id(),
                                'name'     => $child_product->get_name(),
                                'quantity' => (int) $combo_item['qty'] * (float) $item->get_quantity(),
                            ];
                        }
                    }
                }
            }
    
            /* -------- TOTALS -------- */
            $subtotal  = (float) $item->get_subtotal();
            $tax_total = (float) $item->get_subtotal_tax();
    
            $total_wo_tax   = $subtotal + $modifier_amount;
            $total_with_tax = $total_wo_tax + $tax_total;
    		$kot_order_id = $order->get_id();
    
            $existing_snapshot[] = [
    			'kot_order_id'  => $kot_order_id,
                'line_item_id'   => $item_id,
                'product_id'     => $item->get_product_id(),
                'name'           => $item->get_name(),
                'quantity'       => (float) $item->get_quantity(),
    
                'modifier_amount'=> round($modifier_amount, 2),
                'modifiers'      => $modifiers,
                'combo_items'    => $combo_items,
    
                'total_wo_tax'   => round($total_wo_tax, 2),
                'tax_total'      => round($tax_total, 2),
                'total_with_tax' => round($total_with_tax, 2),
            ];
        }
    
        // save merged snapshot
        $parent_order->update_meta_data('_kot_initial_items', $existing_snapshot);
        $parent_order->save();
    }


    public function pos_pre_insert_order_validation( $order, $request, $creating ) {
        if ( $request->get_route() && strpos( $request->get_route(), $this->namespace . '/orders' ) === false ) {
            return $order;
        }
        // $payload = (array) $request->get_json_params();
        
        // /*
        //  * Route only orders received from OrderOut.
        //  */
        // $source_name = strtolower(
        // 	sanitize_text_field(
        // 		$payload['source']['ods']['name'] ?? ''
        // 	)
        // );
        
        // if ( $creating && 'orderout' === $source_name ) {
        
        // 	$routes = get_option(
        // 		'pinakaonlineorders_restaurant_destinations',
        // 		array()
        // 	);
        
        // 	/*
        // 	 * If mappings exist, this website works as the central router.
        // 	 */
        // 	if ( is_array( $routes ) && ! empty( $routes ) ) {
        
        // 		/*
        // 		 * OrderOut restaurant details are inside destination.
        // 		 *
        // 		 * Example:
        // 		 * destination.restaurantId
        // 		 * destination.externalRestaurantId
        // 		 */
        // 		$restaurant_id = sanitize_text_field(
        // 			(string) (
        // 				$payload['destination']['restaurantId']
        // 				?? ''
        // 			)
        // 		);
        
        // 		/*
        // 		 * Optional fallback to externalRestaurantId.
        // 		 */
        // 		if (
        // 			'' === $restaurant_id &&
        // 			! empty( $payload['destination']['externalRestaurantId'] )
        // 		) {
        // 			$restaurant_id = sanitize_text_field(
        // 				(string) $payload['destination']['externalRestaurantId']
        // 			);
        // 		}
        
        // 		if ( '' === $restaurant_id ) {
        // 			return new WP_Error(
        // 				'restaurant_id_missing',
        // 				'The OrderOut payload does not contain a restaurant ID.',
        // 				array(
        // 					'status' => 400,
        // 				)
        // 			);
        // 		}
        
        // 		/*
        // 		 * Find destination configuration.
        // 		 */
        // 		if ( ! isset( $routes[ $restaurant_id ] ) ) {
        
        // 			return new WP_Error(
        // 				'restaurant_route_not_found',
        // 				sprintf(
        // 					'No destination website is configured for restaurant %s.',
        // 					$restaurant_id
        // 				),
        // 				array(
        // 					'status' => 404,
        // 				)
        // 			);
        // 		}
        
        // 		$configured_route = $routes[ $restaurant_id ];
        
        // 		/*
        // 		 * Support:
        // 		 *
        // 		 * [
        // 		 *     'website'   => 'https://restaurant.example.com',
        // 		 *     'jwt_token' => 'xxxx'
        // 		 * ]
        // 		 *
        // 		 * OR old format:
        // 		 *
        // 		 * 'https://restaurant.example.com'
        // 		 */
        // 		if ( is_array( $configured_route ) ) {
        
        // 			$website = isset( $configured_route['website'] )
        // 				? $configured_route['website']
        // 				: '';
        
        // 			$jwt_token = isset( $configured_route['jwt_token'] )
        // 				? $configured_route['jwt_token']
        // 				: '';
        
        // 		} else {
        
        // 			$website   = $configured_route;
        // 			$jwt_token = '';
        // 		}
        
        // 		$website = untrailingslashit(
        // 			esc_url_raw( $website )
        // 		);
        
        // 		if (
        // 			empty( $website ) ||
        // 			! wp_http_validate_url( $website )
        // 		) {
        // 			return new WP_Error(
        // 				'restaurant_route_invalid',
        // 				'The configured restaurant destination is invalid.',
        // 				array(
        // 					'status' => 500,
        // 				)
        // 			);
        // 		}
        
        // 		/*
        // 		 * Forward the ORIGINAL OrderOut payload
        // 		 * to the mapped restaurant website.
        // 		 */
        // 		$destination_url =
        // 			$website .
        // 			'/wp-json/pinaka-restaurant-pos/v1/orders';
        
        // 		$headers = array(
        // 			'Content-Type' => 'application/json',
        // 			'Accept'       => 'application/json',
        // 		);
        
        // 		if ( ! empty( $jwt_token ) ) {
        
        // 			$jwt_token = preg_replace(
        // 				'/^Bearer\s+/i',
        // 				'',
        // 				trim( $jwt_token )
        // 			);
        
        // 			$headers['Authorization'] =
        // 				'Bearer ' . $jwt_token;
        // 		}
        
        // 		$response = wp_remote_post(
        // 			$destination_url,
        // 			array(
        // 				'timeout'     => 30,
        // 				'redirection' => 3,
        // 				'headers'     => $headers,
        // 				'body'        => wp_json_encode( $payload ),
        // 				'data_format' => 'body',
        // 			)
        // 		);
        
        // 		if ( is_wp_error( $response ) ) {
        
        // 			return new WP_Error(
        // 				'restaurant_forward_failed',
        // 				$response->get_error_message(),
        // 				array(
        // 					'status' => 502,
        // 				)
        // 			);
        // 		}
        
        // 		$status = wp_remote_retrieve_response_code(
        // 			$response
        // 		);
        
        // 		$response_body = wp_remote_retrieve_body(
        // 			$response
        // 		);
        
        // 		$response_data = json_decode(
        // 			$response_body,
        // 			true
        // 		);
        
        // 		if ( JSON_ERROR_NONE !== json_last_error() ) {
        // 			$response_data = $response_body;
        // 		}
        
        // 		/*
        // 		 * Restaurant rejected the order.
        // 		 */
        // 		if ( $status < 200 || $status >= 300 ) {
        
        // 			return new WP_Error(
        // 				'restaurant_forward_rejected',
        // 				'The restaurant website rejected the OrderOut order.',
        // 				array(
        // 					'status' => $status ?: 502,
        
        // 					'restaurant_id' =>
        // 						$restaurant_id,
        
        // 					'external_restaurant_id' =>
        // 						$payload['destination']['externalRestaurantId']
        // 						?? '',
        
        // 					'destination_response' =>
        // 						$response_data,
        // 				)
        // 			);
        // 		}
        
        // 		/*
        // 		 * Important:
        // 		 *
        // 		 * Stop WooCommerce from creating the same order
        // 		 * on the central/router website.
        // 		 *
        // 		 * The order has already been forwarded to the
        // 		 * actual restaurant website.
        // 		 */
        // 		return new WP_Error(
        // 			'pinaka_order_routed',
        // 			'OrderOut order routed successfully; no central order was created.',
        // 			array(
        // 				'status' => 200,
        
        // 				'routed' => true,
        
        // 				'restaurant_id' =>
        // 					$restaurant_id,
        
        // 				'external_restaurant_id' =>
        // 					$payload['destination']['externalRestaurantId']
        // 					?? '',
        
        // 				'orderout_order_id' =>
        // 					$payload['source']['externalReferenceId']
        // 					?? '',
        
        // 				'orderout_order_number' =>
        // 					$payload['source']['orderNumber']
        // 					?? '',
        
        // 				'destination_response' =>
        // 					$response_data,
        // 			)
        // 		);
        // 	}
        // }
		$meta_data = (array) $request->get_param( 'meta_data' );
		$is_void_req = false;
		$kot_remarks_val = '';
		foreach ( $meta_data as $meta ) {
			if ( isset( $meta['key'], $meta['value'] ) && $meta['key'] === 'kot_remarks' ) {
				$is_void_req = true;
				$kot_remarks_val = $meta['value'];
				break;
			}
		}
		$line_items = (array) $request->get_param( 'line_items' );
		
		if($is_void_req && !empty($line_items))
		{
			$line_item_ids = [];
			foreach ( $line_items as $item ) {

				$item_id = absint( $item['id'] ?? 0 );
				$qty     = isset( $item['quantity'] ) ? (int) $item['quantity'] : null;

				if ( ! $item_id ) {
					continue;
				}

				if ( $qty === 0 ) {
					$line_item_ids[] = $item_id;
				}
			}

			if ( ! empty( $line_item_ids ) ) {
				foreach ( $order->get_fees() as $fee_item_id => $fee_item ) {

					$linked_line_item_id = absint( $fee_item->get_meta( '_linked_line_item_id' ) );

					if ( $linked_line_item_id && in_array( $linked_line_item_id, $line_item_ids, true ) ) {
						$order->remove_item( $fee_item_id );
					}
				}
			}

			if ( $kot_remarks_val ) {
    $order->update_meta_data(
        'order_edit_remarks',
        $kot_remarks_val
    );
}

/*
 * Store previous net payable before marking order as updated.
 * Save only once so the original amount is preserved.
 */
$parent_order_id = $order->get_parent_id();

if ( $parent_order_id ) {

    $parent_order = wc_get_order( $parent_order_id );

    if ( $parent_order ) {

        $existing_prev_total = $parent_order->get_meta(
            '_order_prev_total',
            true
        );

        if ( $existing_prev_total === '' ) {

            $current_net_payable = $parent_order->get_meta(
                '_net_payable',
                true
            );

            if ( $current_net_payable !== '' ) {

                $parent_order->update_meta_data(
                    '_order_prev_total',
                    (float) $current_net_payable
                );

                $parent_order->save();
            }
        }
    }
}

$order->update_meta_data( 'is_voided', 'yes' );
			global $wpdb;

			$table      = $wpdb->prefix . 'order_void_logs';
			$charset_collate = $wpdb->get_charset_collate();

			$sql_table = "
			CREATE TABLE IF NOT EXISTS {$table} (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				post_id BIGINT(20) UNSIGNED NOT NULL,
				post_type VARCHAR(100) NOT NULL,
				action VARCHAR(50) NOT NULL,
				user_id BIGINT(20) UNSIGNED NOT NULL,
				data LONGTEXT NOT NULL,
				created_at DATETIME NOT NULL,
				PRIMARY KEY (id)
			) {$charset_collate};
			";

			$wpdb->query( $sql_table );

			$table_exists = $wpdb->get_var(
				$wpdb->prepare( "SHOW TABLES LIKE %s", $table )
			);

			if ( ! $table_exists ) {
				return;
			}

			// Original item data captured before WooCommerce modified the order.
            $void_original_items = (array) $request->get_param(
                '_void_original_items'
            );
            
            foreach ( $line_items as $item ) {
            
                $item_id = absint( $item['id'] ?? 0 );
                $new_qty = isset( $item['quantity'] )
                    ? (int) $item['quantity']
                    : null;
            
                if ( ! $item_id || $new_qty === null ) {
                    continue;
                }
            
                // Get ORIGINAL quantity captured before order update.
                $original_data = $void_original_items[ $item_id ] ?? null;
            
                if ( ! $original_data ) {
                    continue;
                }
            
                $orig_qty = (int) $original_data['orig_qty'];
            
                if ( $orig_qty <= 0 ) {
                    continue;
                }
            
                // No void if quantity is unchanged/increased.
                if ( $new_qty >= $orig_qty ) {
                    continue;
                }
            
                $product   = $order_item = null;
            
                // Product/name from original data first.
                $prod_name = $original_data['product'] ?? '';
            
                if ( empty( $prod_name ) ) {
                    $current_item = $order->get_item( $item_id );
            
                    if ( $current_item ) {
                        $product   = $current_item->get_product();
                        $prod_name = $product
                            ? $product->get_name()
                            : $current_item->get_name();
                    }
                }
            
                // Number of units voided.
                $voided_qty = $orig_qty - $new_qty;
            
                // Original amount before quantity reduction.
                $original_item_total =
                    (float) ( $original_data['total'] ?? 0 ) +
                    (float) ( $original_data['tax'] ?? 0 );
            
                // Amount belonging to one unit.
                $unit_total = $orig_qty > 0
                    ? $original_item_total / $orig_qty
                    : 0;
            
                // Amount actually voided.
                $voided_item_total = $unit_total * $voided_qty;
            
                $wpdb->insert(
                    $table,
                    [
                        'post_id'   => $order->get_id(),
                        'post_type' => 'shop_order',
                        'action'    => 'item_voided',
                        'user_id'   => get_current_user_id(),
            
                        'data'      => wp_json_encode( [
                            'item_id'     => $item_id,
                            'product'     => $prod_name,
                            'orig_qty'    => $orig_qty,
                            'new_qty'     => $new_qty,
                            'voided_qty'  => $voided_qty,
                            'item_total'  => wc_format_decimal(
                                $voided_item_total,
                                2
                            ),
                            'remarks'     => $kot_remarks_val,
                        ] ),
            
                        'created_at' => current_time( 'mysql' ),
                    ],
                    [ '%d', '%s', '%s', '%d', '%s', '%s' ]
                );
            }
			$request->set_param( 'line_items', [] );
		}
        $params  = $request->get_json_params();	
        $user_id = get_current_user_id();
        
        // $payload = $request->get_json_params();
        // $source_name = strtolower(
        //     $payload['source']['ods']['name'] ?? ''
        // );
        
        // $connector_id = $source_name === 'orderout'
        //     ? 'orderout'
        //     : 'woocommerce';
        
        // $connector_base_url = 'https://retrial-preformed-employer.ngrok-free.dev';
        
        // $connector_webhook_url =
        //     $connector_base_url .
        //     '/api/v1/connectors/' .
        //     $connector_id .
        //     '/webhook';
        
        // $correlation_id = wp_generate_uuid4();
        
        // $forward_result = wp_remote_post(
        //     $connector_webhook_url,
        //     [
        //         'headers' => [
        //             'Content-Type'     => 'application/json',
        //             'Accept'           => 'application/json',
        //             'x-correlation-id' => $correlation_id,
        //         ],
        //         'body'     => wp_json_encode($payload),
        //         'timeout'  => 10,
        //         'blocking' => false,
        //     ]
        // );

		if(isset($params['flag_type']) && !in_array($params['flag_type'], ['parent_order', 'kot_order','update_kot_order','cancel_parent_order','complete_parent_order','update_kot_status','parent_takeaway_order','parent_online_order','void_items_update','add_discount','cancel_takeaway_order','complete_takeaway_order','cancel_completed_order','update_kot_order_status'])) 
		{
			return new WP_Error(
				'invalid_flag_type',
				"Invalid flag_type value. only allowed values are: ".implode(", ", ['parent_order', 'kot_order','update_kot_order','cancel_parent_order','complete_parent_order','update_kot_status','parent_takeaway_order','parent_online_order','void_items_update','add_discount','cancel_takeaway_order','complete_takeaway_order','cancel_completed_order','update_kot_order_status']),
				array( 'status' => 400 )
			);
		}

		if(isset($params['flag_type']) && $params['flag_type'] === 'parent_order')
		{
			$required_fields = ['table_id', 'zone_id'];
			$data = [];

			foreach ( $required_fields as $field ) {
				$value = $params[$field] ?? '';
				if ( $value === '' || intval( $value ) === 0 ) {
					return new WP_Error(
						'missing_field',
						"Missing or invalid required field: {$field}",
						array( 'status' => 400 )
					);
				}
				$data[$field] = intval( $value );
			}
			$meta_restaurant_id = get_user_meta( $user_id, 'user_restaurant_id', true );
			$restaurant_id = ! empty( $params['restaurant_id'] ) ? intval( $params['restaurant_id'] ) : ( ! empty( $meta_restaurant_id ) ? intval( $meta_restaurant_id ) : 0 );

			if ( empty( $restaurant_id ) ) {
				return new WP_Error( 'missing_restaurant', 'Restaurant id is required.', [ 'status' => 422 ] );
			}
			
			if (isset($params['guest_count']) && empty($params['guest_count'])) {
				return new WP_Error(
					'guest_empty',
					"Guest count cannot be empty if provided.",
					array( 'status' => 400 )
				);
			}
			$restaurants = get_option('pinaka_pos_restaurants', []);
			$ids   = array_column($restaurants, 'id');
			$found = in_array($restaurant_id, array_map('intval', $ids), true);

			if ( ! $found ) {
				return new WP_Error(
					'restaurant_not_exists',
					"Restaurant not exists.",
					array( 'status' => 400 )
				);
			}
			$user_restaurant_id = intval( $meta_restaurant_id );
			if ( $user_restaurant_id !== $restaurant_id ) {
				return new WP_Error(
					'user_not_assigned',
					"User not assigned to Restaurant.",
					array( 'status' => 400 )
				);
			}
			
			$table_id   = $data['table_id'];
			$table_post = get_post( $table_id );
			if ( ! $table_post || $table_post->post_type !== 'tables' || $table_post->post_status !== 'publish' ) {
				return new WP_Error(
					'invalid_table',
					"Invalid table_id. Table not found or not published.",
					array( 'status' => 400 )
				);
			}
			$table_name = $table_post->post_title;
			$existing = new WP_Query([
				'post_type'      => 'tables',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'meta_query'     => [
					'relation' => 'AND',
					[ 'key' => '_table_name', 'value' => $table_name ],
					[ 'key' => '_table_zone_id', 'value' => $data['zone_id'] ],
					[ 'key' => '_restaurant_id', 'value' => $restaurant_id ]
				]
			]);
			if ( ! $existing->have_posts() ) {
				return new WP_Error(
					'invalid_table',
					"This table does not belong to the given zone/restaurant.",
					array( 'status' => 400 )
				);
			}
			
			$active_orders = wc_get_orders([
				'status'    => [ 'processing', 'pending' ],
				'limit'     => 1,
				'meta_query'=> [
					'relation' => 'AND',
					[ 'key' => '_restaurant_id', 'value' => $restaurant_id ],
					[ 'key' => '_zone_id', 'value' => $data['zone_id'] ],
					[ 'key' => '_table_id', 'value' => $table_id ],
					[
                        'key'     => '_table_status',
                        'value'   => [ 'Dine in', 'Occupied' ],
                        'compare' => 'IN',
                    ],
					[ 'key' => '_parent_order', 'value' => 'yes' ]
				]
			]);

			if ( ! empty( $active_orders ) ) 
			{
				$table_order = reset( $active_orders );
				$table_post = get_post( $table_order->get_meta('_table_id') );
				$zone_post  = get_post( $table_order->get_meta('_zone_id') );
				$restaurant_id = $table_order->get_meta('_restaurant_id');
				return new WP_Error(
					'active_order_exists',
					__( 'This table already has an active parent order.', 'pinaka-pos' ),
					[
						'status' => 409,
						'order_id'   => $table_order->get_id(),
						'status'     => $table_order->get_status(),
						'table_name' => $table_post ? $table_post->post_title : '',
						'zone_name'  => $zone_post ? $zone_post->post_title : '',
						'restaurant_id' => intval($restaurant_id)
					]
				);
			}
			if ( isset($params['reservation_id']) && ! empty($params['reservation_id']) ) {
			
				$reservation_id   = absint( $params['reservation_id'] );
				$reservation_post = get_post( $reservation_id );
				
				if ( ! $reservation_post || $reservation_post->post_type !== 'reservation_list' || $reservation_post->post_status !== 'publish' ) {
					return new WP_Error(
						'no_reservation',
						"Reservation does not exist or is invalid.",
						array( 'status' => 400 )
					);
				}

				// Validate reservation matches
				$res_table_id = intval( get_post_meta($reservation_id, '_table_no_id', true) );
				$res_zone_id  = intval( get_post_meta($reservation_id, '_zone_id', true) );
				$res_rest_id  = intval( get_post_meta($reservation_id, '_restaurant_id', true) );
				
				if ( $res_table_id !== $table_id || $res_zone_id !== $data['zone_id'] || $res_rest_id !== $restaurant_id ) {
					return new WP_Error(
						'no_reservation_match',
						"Reservation does not match the given table/zone/restaurant.",
						array( 'status' => 400 )
					);
				}
				$res_stat = get_post_meta($reservation_id, '_reservation_status', true);
				if($res_stat == "Expired" || $res_stat == "Cancelled")
				{
					return new WP_Error(
						'invalid_reservation',
						"Please enter valid reservation ID.",
						array( 'status' => 400 )
					);
				}
			} 
		}
		else if(isset($params['flag_type']) && $params['flag_type'] === 'kot_order')
		{
			$parent_order_id = absint($params['parent_order_id'] ?? 0);
			$parent_order = wc_get_order($parent_order_id);

			if (!$parent_order_id || !$parent_order) {
				$error_message = 'Invalid parent order ID.';
				return new WP_Error('invalid_parent', $error_message, ['status' => 400]);
			}
			if ( $parent_order->get_status() === 'trash') {
				return new WP_Error(
					'trashed_parent',
					__( 'Parent order is deleted.', 'pinaka-pos' ),
					[ 'status' => 400 ]
				);
			}
			$order_restaurant_id = (int) $parent_order->get_meta( '_restaurant_id' );
			$order_zone_id       = (int) $parent_order->get_meta( '_zone_id' );
			
			$req_restaurant_id = isset( $params['restaurant_id'] ) ? (int) $params['restaurant_id'] : 0;
			$req_zone_id       = isset( $params['zone_id'] ) ? (int) $params['zone_id'] : 0;
			if ( $order_restaurant_id !== $req_restaurant_id || $order_zone_id !== $req_zone_id ) {
				return new WP_Error(
					'mismatched_ids',
					__( 'Restaurant or Zone ID does not match with order Restaurant or Zone ID.', 'pinaka-pos' ),
					[ 'status' => 403 ]
				);
			}
			$captain_id = isset( $params['captain_id'] ) ? (int) $params['captain_id'] : 0;
			if(empty($captain_id))
			{
				return new WP_Error( 'missing_captain_id', 'Captain id is required.', [ 'status' => 422 ] );
			}
			if ($parent_order->get_status() === 'cancelled') 
			{
				$error_message = 'Cannot create KOT order because the parent order is cancelled.';
				return new WP_Error( 'parent_cancelled', $error_message, [ 'status' => 403 ] );
			}
			if ($parent_order->get_status() === 'completed') 
			{
				return new WP_Error('parent_completed', 'Cannot create KOT. Parent order is already completed.', ['status' => 400]);
			}
		}
		else if(isset($params['flag_type']) && $params['flag_type'] === 'update_kot_order')
		{
			$parent_order_id = absint($params['parent_order_id'] ?? 0);
			$parent_order = wc_get_order($parent_order_id);

			if (!$parent_order_id || !$parent_order) {
				$error_message = 'Invalid parent order ID.';
				return new WP_Error('invalid_parent', $error_message, ['status' => 400]);
			}
			if ( $parent_order->get_status() === 'trash') {
				return new WP_Error(
					'trashed_parent',
					__( 'Parent order is deleted.', 'pinaka-pos' ),
					[ 'status' => 400 ]
				);
			}
			$order_restaurant_id = (int) $parent_order->get_meta( '_restaurant_id' );
			$order_zone_id       = (int) $parent_order->get_meta( '_zone_id' );
			
			$req_restaurant_id = isset( $params['restaurant_id'] ) ? (int) $params['restaurant_id'] : 0;
			$req_zone_id       = isset( $params['zone_id'] ) ? (int) $params['zone_id'] : 0;
			if ( $order_restaurant_id !== $req_restaurant_id || $order_zone_id !== $req_zone_id ) {
				return new WP_Error(
					'mismatched_ids',
					__( 'Restaurant or Zone ID does not match with order Restaurant or Zone ID.', 'pinaka-pos' ),
					[ 'status' => 403 ]
				);
			}
			$captain_id = isset( $params['captain_id'] ) ? (int) $params['captain_id'] : 0;
			if(empty($captain_id))
			{
				return new WP_Error( 'missing_captain_id', 'Captain id is required.', [ 'status' => 422 ] );
			}
			if ($parent_order->get_status() === 'cancelled') 
			{
				$error_message = 'Cannot create KOT order because the parent order is cancelled.';
				return new WP_Error( 'parent_cancelled', $error_message, [ 'status' => 403 ] );
			}
			if ($parent_order->get_status() === 'completed') 
			{
				return new WP_Error('parent_completed', 'Cannot create KOT. Parent order is already completed.', ['status' => 400]);
			}
			foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) {
                $order->remove_item( $item_id );
            }
    
            // Remove previous fee items
            foreach ( $order->get_items( 'fee' ) as $fee_id => $fee ) {
                $order->remove_item( $fee_id );
            }
            $order->save();
		}
		else if(isset($params['flag_type']) && $params['flag_type'] === "cancel_parent_order")
		{			
			$order_id = $order->get_id();
			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				return new WP_Error( 'invalid_id', 'Order does not exist', array( 'status' => 404 ) );
			}
			if ( $order->get_status() === 'trash' ) {
				return new WP_Error(
					'trashed_parent',
					__( 'Parent order is deleted.', 'pinaka-pos' ),
					[ 'status' => 400 ]
				);
			}
			$is_parent_order = $order->get_meta( '_parent_order' );
			if ( $is_parent_order !== 'yes') {
				return new WP_Error(
					'cannot_cancel_order',
					__( 'Please select valid parent order id.', 'pinaka-pos' ),
					array( 'status' => 403 )
				);
			}
			$order_restaurant_id = (int) $order->get_meta( '_restaurant_id' );
			$order_zone_id       = (int) $order->get_meta( '_zone_id' );
			
			$req_restaurant_id = isset( $params['restaurant_id'] ) ? (int) $params['restaurant_id'] : 0;
			$req_zone_id       = isset( $params['zone_id'] ) ? (int) $params['zone_id'] : 0;

			if ( $order_restaurant_id !== $req_restaurant_id || $order_zone_id !== $req_zone_id ) {
				return new WP_Error(
					'mismatched_ids',
					__( 'Restaurant or Zone ID does not match with order Restaurant or Zone ID.', 'pinaka-pos' ),
					[ 'status' => 403 ]
				);
			}
			$get_reser_id = intval( $order->get_meta( '_reservation_id', true ) );
			$children = $this->get_kot_child_orders_by_parent_id( $order_id );
// 			$has_items = false;
            $all_cancelled = true;
			foreach ( $children as $child ) {
                if ( $child->get_status() !== 'cancelled' ) {
                    $all_cancelled = false;
                }
				// foreach ( $child->get_items() as $item ) {
				// 	if ( $item->get_quantity() > 0 ) {
				// 		$has_items = true;
				// 		break 2;
				// 	}
				// }
			}
			if (!$all_cancelled) {
				return new WP_Error(
					'cannot_cancel_with_children',
					__( 'This Order contains KOT child orders and cannot be canceled.', 'pinaka-pos' ),
					array( 'status' => 403 )
				);
			}
		}
		else if(isset($params['flag_type']) && $params['flag_type'] === "cancel_takeaway_order")
		{			
			$order_id = $order->get_id();
			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				return new WP_Error( 'invalid_id', 'Order does not exist', array( 'status' => 404 ) );
			}
			if ( $order->get_status() === 'trash' ) {
				return new WP_Error(
					'trashed_parent',
					__( 'Parent order is deleted.', 'pinaka-pos' ),
					[ 'status' => 400 ]
				);
			}
			$is_parent_order = $order->get_meta( '_takeaway_order' );
			if ( $is_parent_order !== 'yes') {
				return new WP_Error(
					'cannot_cancel_order',
					__( 'Please select valid parent order id.', 'pinaka-pos' ),
					array( 'status' => 403 )
				);
			}
			$order_restaurant_id = (int) $order->get_meta( '_restaurant_id' );
			
			$req_restaurant_id = isset( $params['restaurant_id'] ) ? (int) $params['restaurant_id'] : 0;

			if ( $order_restaurant_id !== $req_restaurant_id) {
				return new WP_Error(
					'mismatched_ids',
					__( 'Restaurant id does not match with order Restaurant id.', 'pinaka-pos' ),
					[ 'status' => 403 ]
				);
			}
// 			$children = $this->get_kot_child_orders_by_parent_id( $order_id );
// 			$children = array_filter( $children, function ( $child ) {
// 				return $child->get_meta( '_kot_number', true );
// 			} );
		
// 			if ( ! empty( $children ) ) {
// 				return new WP_Error(
// 					'cannot_cancel_with_children',
// 					__( 'This Order contains KOT child orders and cannot be canceled.', 'pinaka-pos' ),
// 					array( 'status' => 403 )
// 				);
// 			}
		}
		else if(isset($params['flag_type']) && $params['flag_type'] === 'complete_parent_order')
		{
			$main_order_id = $order->get_id();
			$parent_id = (int) $order->get_parent_id();
			if($parent_id!==0)
			{
				return new WP_Error(
					'invalid_parent_id',
					"Invalid Parent Order ID",
					array( 'status' => 400 )
				);
			}
			$orderid = wc_get_order( $main_order_id );
			if ( ! $orderid ) {
				return new WP_Error( 'order_not_found', 'Order not found', array( 'status' => 404 ) );
			}
			if ( $orderid->get_status() === 'trash' ) {
				return new WP_Error(
					'trashed_parent',
					__( 'Parent order is deleted.', 'pinaka-pos' ),
					[ 'status' => 400 ]
				);
			}
			$order_restaurant_id = (int) $orderid->get_meta( '_restaurant_id' );
			$order_zone_id       = (int) $orderid->get_meta( '_zone_id' );
			
			$req_restaurant_id = isset( $params['restaurant_id'] ) ? (int) $params['restaurant_id'] : 0;
			$req_zone_id       = isset( $params['zone_id'] ) ? (int) $params['zone_id'] : 0;

			if ( $order_restaurant_id !== $req_restaurant_id || $order_zone_id !== $req_zone_id ) {
				return new WP_Error(
					'mismatched_ids',
					__( 'Restaurant or Zone ID does not match with order Restaurant or Zone ID.', 'pinaka-pos' ),
					[ 'status' => 403 ]
				);
			}
			$children = $this->get_kot_child_orders_by_parent_id( $main_order_id );
			$children = array_filter( $children, function ( $child ) {
				return $child->get_meta( '_kot_number', true );
			} );
			$all_done = true;
			foreach ( $children as $child ) 
			{
				$status = $child->get_status();
				if ( ! in_array( $status, [ 'completed', 'cancelled' ], true ) ) {
					$all_done = false;
					break;
				}
			}
			if ( !$all_done ) 
			{
				return new WP_Error(
					'invalid_status_request',
					"Cannot complete parent order. All KOT Orders not completed",
					array( 'status' => 400 )
				);
			}
		}
		else if(isset($params['flag_type']) && $params['flag_type'] === 'complete_takeaway_order')
		{
			$main_order_id = $order->get_id();
			$parent_id = (int) $order->get_parent_id();
			if($parent_id!==0)
			{
				return new WP_Error(
					'invalid_parent_id',
					"Invalid Parent Order ID",
					array( 'status' => 400 )
				);
			}
			$orderid = wc_get_order( $main_order_id );
			if ( ! $orderid ) {
				return new WP_Error( 'order_not_found', 'Order not found', array( 'status' => 404 ) );
			}
			if ( $orderid->get_status() === 'trash' ) {
				return new WP_Error(
					'trashed_parent',
					__( 'Parent order is deleted.', 'pinaka-pos' ),
					[ 'status' => 400 ]
				);
			}
			$order_restaurant_id = (int) $orderid->get_meta( '_restaurant_id' );
			
			$req_restaurant_id = isset( $params['restaurant_id'] ) ? (int) $params['restaurant_id'] : 0;

			if ( $order_restaurant_id !== $req_restaurant_id) {
				return new WP_Error(
					'mismatched_ids',
					__( 'Restaurant id does not match with order Restaurant id.', 'pinaka-pos' ),
					[ 'status' => 403 ]
				);
			}
			$children = $this->get_kot_child_orders_by_parent_id( $main_order_id );
		
			$children = array_filter( $children, function ( $child ) {
				return $child->get_meta( '_kot_number', true );
			} );
			$all_done = true;
			foreach ( $children as $child ) 
			{
				$status = $child->get_status();
				if ( ! in_array( $status, [ 'completed', 'cancelled' ], true ) ) {
					$all_done = false;
					break;
				}
			}
			if ( !$all_done ) 
			{
				return new WP_Error(
					'invalid_status_request',
					"Cannot complete parent order. All KOT Orders not completed",
					array( 'status' => 400 )
				);
			}
		}
		else if(isset($params['flag_type']) && $params['flag_type'] === 'update_kot_status')
		{
			$ord_id = $order->get_parent_id();
			if ( $ord_id === 0 ) {
				return new WP_Error(
					'invalid_kot_id',
					__( 'Invalid KOT ID.', 'pinaka-pos' ),
					[ 'status' => 400 ]
				);
			}
			if ( $order->get_status() === 'trash' ) {
				return new WP_Error(
					'trashed_parent',
					__( 'Parent order is deleted.', 'pinaka-pos' ),
					[ 'status' => 400 ]
				);
			}
			$order_restaurant_id = (int) $order->get_meta( '_restaurant_id' );
			$order_zone_id       = (int) $order->get_meta( '_zone_id' );
			
			$req_restaurant_id = isset( $params['restaurant_id'] ) ? (int) $params['restaurant_id'] : 0;
			$req_zone_id       = isset( $params['zone_id'] ) ? (int) $params['zone_id'] : 0;

			$is_parnt_order = $order->get_meta('_parent_order');
			$is_takeaway_order = $order->get_meta('_takeaway_order');
			$is_online_order = $order->get_meta('_online_order');
			if($is_takeaway_order === 'yes' || $is_online_order === 'yes')
			{
			     $order_zone_id = $req_zone_id = 0;
			}
			if ($is_parnt_order == 'yes' && $order_restaurant_id !== $req_restaurant_id && $order_zone_id !== $req_zone_id ) {
				return new WP_Error(
					'mismatched_ids',
					__( 'Restaurant or Zone ID does not match with order Restaurant or Zone ID.', 'pinaka-pos' ),
					[ 'status' => 403 ]
				);
			}
			else
			{
				if($order_restaurant_id !== $req_restaurant_id)
				{
					return new WP_Error(
						'mismatched_ids',
						__( 'Restaurant id does not match with order Restaurant id.', 'pinaka-pos' ),
						[ 'status' => 403 ]
					);
				}
			}
		}
		else if(isset($params['flag_type']) && $params['flag_type'] === 'update_kot_order_status')
		{
			$ord_id = $order->get_parent_id();
			if ( (int) $ord_id !== (int) $params['parent_id'] ) {
            	return new WP_Error(
            		'invalid_kot_parent',
            		__( 'The KOT does not belong to the specified parent order.', 'pinaka-pos' ),
            		[ 'status' => 400 ]
            	);
            }
			if ( $order->get_status() === 'trash' ) {
				return new WP_Error(
					'trashed_parent',
					__( 'Parent order is deleted.', 'pinaka-pos' ),
					[ 'status' => 400 ]
				);
			}
			$order_restaurant_id = (int) $order->get_meta( '_restaurant_id' );
			$order_zone_id       = (int) $order->get_meta( '_zone_id' );
			
			$req_restaurant_id = isset( $params['restaurant_id'] ) ? (int) $params['restaurant_id'] : 0;
			$req_zone_id       = isset( $params['zone_id'] ) ? (int) $params['zone_id'] : 0;

			$is_parnt_order = $order->get_meta('_parent_order');
			$is_takeaway_order = $order->get_meta('_takeaway_order');
			$is_online_order = $order->get_meta('_online_order');
			if($is_takeaway_order === 'yes' || $is_online_order === 'yes')
			{
			     $order_zone_id = $req_zone_id = 0;
			}
			if ($is_parnt_order === 'yes' && $order_restaurant_id !== $req_restaurant_id && $order_zone_id !== $req_zone_id ) {
				return new WP_Error(
					'mismatched_ids',
					__( 'Restaurant or Zone ID does not match with order Restaurant or Zone ID.', 'pinaka-pos' ),
					[ 'status' => 403 ]
				);
			}
			else
			{
				if($order_restaurant_id !== $req_restaurant_id)
				{
					return new WP_Error(
						'mismatched_ids',
						__( 'Restaurant id does not match with order Restaurant id.', 'pinaka-pos' ),
						[ 'status' => 403 ]
					);
				}
			}
		}
		else if(isset($params['flag_type']) && $params['flag_type'] === 'parent_takeaway_order')
		{
			if(!isset($params['created_via']))
			{
				return new WP_Error( 'missing_created_via', 'Created via is required.', [ 'status' => 422 ] );
			}
			if($params['created_via'] !== 'takeaway')
			{
				return new WP_Error( 'invalid_created_via', 'Invalid created via. take created_via as takeaway', [ 'status' => 422 ] );
			}
			$restaurant_id = isset( $params['restaurant_id'] ) ? intval( $params['restaurant_id'] ) : intval( get_user_meta( $user_id, 'user_restaurant_id', true ) );
			if(empty($restaurant_id))
			{
				return new WP_Error( 'missing_restaurant_id', 'Restaurant ID is required.', [ 'status' => 422 ] );
			}
		}
		else if(isset($params['flag_type']) && $params['flag_type'] === 'parent_online_order')
		{
			if(!isset($params['created_via']))
			{
				return new WP_Error( 'missing_created_via', 'Created via is required.', [ 'status' => 422 ] );
			}
			if($params['created_via'] !== 'online')
			{
				return new WP_Error( 'invalid_created_via', 'Invalid created via. take created_via online', [ 'status' => 422 ] );
			}
			$restaurant_id = isset( $params['restaurant_id'] ) ? intval( $params['restaurant_id'] ) : intval( get_user_meta( $user_id, 'user_restaurant_id', true ) );
			if(empty($restaurant_id))
			{
				return new WP_Error( 'missing_restaurant_id', 'Restaurant ID is required.', [ 'status' => 422 ] );
			}
		}
		else if ( isset( $params['flag_type'] ) && $params['flag_type'] === 'cancel_completed_order' ) {

			$main_order_id = (int) $order->get_id();
			$parent_id     = (int) $order->get_parent_id();

			// Block if this is a child order
			if ( $parent_id !== 0 ) {
				return new WP_Error(
					'invalid_parent_id',
					'Invalid Parent Order ID',
					[ 'status' => 400 ]
				);
			}

			$parent_order = wc_get_order( $main_order_id );

			if ( ! $parent_order ) {
				return new WP_Error(
					'order_not_found',
					'Order not found',
					[ 'status' => 404 ]
				);
			}

			if ( $parent_order->get_status() === 'trash' ) {
				return new WP_Error(
					'trashed_parent',
					__( 'Parent order is deleted.', 'pinaka-pos' ),
					[ 'status' => 400 ]
				);
			}

			// Restaurant validation
			$order_restaurant_id = (int) $parent_order->get_meta( '_restaurant_id' );
			$req_restaurant_id  = (int) $request->get_param( 'restaurant_id' );

			// Order type detection
			$map = [
				'_parent_order'   => 'Dine In',
				'_takeaway_order' => 'Takeaways',
				'_online_order'   => 'Online',
			];

			$or_type = 'Dine In';

			foreach ( $map as $meta_key => $label ) {
				if ( $parent_order->get_meta( $meta_key ) === 'yes' ) {
					$or_type = $label;
					break;
				}
			}

			// Validation rules by order type
			if ( $or_type === 'Dine In' ) {

				$order_zone_id = (int) $parent_order->get_meta( '_zone_id' );
				$req_zone_id   = (int) $request->get_param( 'zone_id' );

				if (
					$order_restaurant_id !== $req_restaurant_id ||
					$order_zone_id !== $req_zone_id
				) {
					return new WP_Error(
						'mismatched_ids',
						__( 'Restaurant or Zone ID does not match with order Restaurant or Zone ID.', 'pinaka-pos' ),
						[ 'status' => 403 ]
					);
				}

			} else {

				if ( $order_restaurant_id !== $req_restaurant_id ) {
					return new WP_Error(
						'mismatched_ids',
						__( 'Restaurant ID does not match with order Restaurant ID.', 'pinaka-pos' ),
						[ 'status' => 403 ]
					);
				}
			}
		}
        else if ( isset( $params['event'] ) && 'cancelled' === $params['event'] ) {

            $orders = [];
        
            if ( ! empty( $params['source']['externalReferenceId'] ) ) {
        
                $orders = wc_get_orders(
                    [
                        'limit'      => 1,
                        'return'     => 'objects',
                        'meta_key'   => 'orderout_order_id',
                        'meta_value' => $params['source']['externalReferenceId'],
                    ]
                );
            }
        
            if ( empty( $orders ) && ! empty( $params['source']['orderNumber'] ) ) {
        
                $orders = wc_get_orders(
                    [
                        'limit'      => 1,
                        'return'     => 'objects',
                        'meta_key'   => 'orderout_order_no',
                        'meta_value' => $params['source']['orderNumber'],
                    ]
                );
            }
        
            if ( ! empty( $orders ) ) {
        
                $order = $orders[0];
        
                $order->update_meta_data(
                    '_orderout_cancel_payload',
                    wp_json_encode(
                        $params,
                        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                    )
                );
        
                $order->add_order_note(
                    sprintf(
                        'Order cancelled by %s via OrderOut.',
                        $params['source']['deliveryCompany']['name'] ?? 'Unknown'
                    )
                );
                
                // if ( $order->get_status() !== 'cancelled' ) {
                //     echo json_encode($order->get_status());
                    $order->update_status( 'cancelled' );
                    // $order->save();
                // }
        
                $child_orders = $this->get_kot_child_orders_by_parent_id(
                    $order->get_id()
                );
        
                if ( ! empty( $child_orders ) ) {
        
                    foreach ( $child_orders as $child_order ) {
        
                        if ( $child_order->get_status() !== 'cancelled' ) {
        
                            $child_order->add_order_note(
                                'Cancelled because parent OrderOut order was cancelled.'
                            );
        
                            $child_order->set_status( 'cancelled' );
                            $child_order->save();
                        }
                    }
                }
            }
            return new WP_Error(
                'orderout_cancelled_event',
                'Order cancellation processed.',
                [ 'status' => 200 ]
            );
        }
        return $order;
    }

    public function pos_after_insert_order_meta( $order, $request, $creating ) {

        if ( $request->get_route() && strpos( $request->get_route(), $this->namespace . '/orders' ) === false ) {
            return;
        }
        // if ( ! $creating ) return;

        $params  = $request->get_json_params();
	    $logger  = wc_get_logger();
        $context = [ 'source' => 'pinaka-pos-order-details' ];
        $logger->info(
                'Request Params: ' . wp_json_encode( $request->get_json_params() ),
                $context
            );
        $user_id = get_current_user_id();
	    if (
            ! empty( $params['source']['ods']['name'] ) &&
            $params['source']['ods']['name'] === 'OrderOut'
        ) {
            $params['flag_type'] = 'orderout_order';
        }
        if ( isset( $params['flag_type'] ) && 'orderout_order' === $params['flag_type'] ) {
            
            $order->set_created_via( 'online' );
            $order->set_status( 'processing' );
            $order->update_meta_data( 'orderout_order_id', $params['source']['externalReferenceId'] );
            $order->update_meta_data( 'orderout_order_no', $params['source']['orderNumber'] );
            // $order->update_meta_data( '_restaurant_id', $params['destination']['externalRestaurantId'] );
            $order->update_meta_data( '_restaurant_id', 1 );
            if ( isset( $params['source']['deliveryCompany']['name'] ) ) {
                $order->update_meta_data(
                    'order_from',
                    sanitize_text_field( $params['source']['deliveryCompany']['name'] )
                );
            }
            $order->update_meta_data(
                '_orderout_request_payload',
                wp_json_encode( $params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
            );
            
            $order->update_meta_data( '_online_order', 'yes' );
            
            // Service Fee
            if ( ! empty( $params['payload']['order']['surcharge'] ) ) {
                $fee = new WC_Order_Item_Fee();
                $fee->set_name( 'Service Fee' );
                $fee->set_total( (float) $params['payload']['order']['surcharge'] );
                $order->add_item( $fee );
            }
            
            // Tip
            if ( ! empty( $params['payload']['order']['gratuity'] ) ) {
                $fee = new WC_Order_Item_Fee();
                $fee->set_name( 'Tip' );
                $fee->set_total( (float) $params['payload']['order']['gratuity'] );
                $order->add_item( $fee );
            }
            
            // Discount
            if ( ! empty( $params['payload']['order']['discount'] ) ) {
                $fee = new WC_Order_Item_Fee();
                $fee->set_name( 'Discount' );
                $fee->set_total( -(float) $params['payload']['order']['discount'] ); // negative amount
                $order->add_item( $fee );
            }
            
            $child_order = wc_create_order();

            $child_order->set_parent_id( $order->get_id() );
    
            $child_order->set_status( 'wc-preparing', 'No email', true );
        
            $child_order->update_meta_data( '_kot_order', 'yes' );
            // $child_order->update_meta_data(
            //     '_restaurant_id',
            //     $params['destination']['externalRestaurantId']
            // );
            $child_order->update_meta_data(
                '_restaurant_id',
                1
            );
            $child_order->update_meta_data(
                '_kot_created_time',
                current_time( 'mysql' )
            );
            global $wpdb;
            /*
             * Products
             */
            
            $option_name = 'pinaka_last_kot_number';

            $wpdb->query(
                $wpdb->prepare(
                    "INSERT IGNORE INTO {$wpdb->options}
                    (option_name, option_value, autoload)
                    VALUES (%s,%d,'yes')",
                    $option_name,
                    0
                )
            );
        
            $wpdb->query("
                UPDATE {$wpdb->options}
                SET option_value = option_value + 1
                WHERE option_name = '{$option_name}'
            ");
        
            $next_number = intval(
                $wpdb->get_var("
                    SELECT option_value
                    FROM {$wpdb->options}
                    WHERE option_name='{$option_name}'
                ")
            );
        
            $kot_number = 'KOT#' . str_pad(
                $next_number,
                4,
                '0',
                STR_PAD_LEFT
            );
        
            $child_order->update_meta_data(
                '_kot_number',
                $kot_number
            );
        
            /**
             * ------------------------------------------------------
             * Products
             * ------------------------------------------------------
             */
        
            foreach ( $params['payload']['order']['items'] as $item ) {
        
                $product = null;
        
                $external_id = ! empty( $item['external_id'] )
                    ? (int) preg_replace( '/\D/', '', $item['external_id'] )
                    : 0;
        
                if ( $external_id ) {
        
                    $product = wc_get_product( $external_id );
        
                }
	
                if ( ! $product ) {
        
                    $products = get_posts(
                        [
                            'post_type'      => [
                                'product',
                                'product_variation'
                            ],
                            'posts_per_page' => 1,
                            'fields'         => 'ids',
                            'meta_key'       => '_orderout_item_id',
                            'meta_value'     => $item['id'],
                        ]
                    );
        
                    if ( ! empty( $products ) ) {
        
                        $product = wc_get_product(
                            $products[0]
                        );
        
                    }
                }
        
                if ( ! $product ) {
                    continue;
                }
        
                $child_order->add_product(
                    $product,
                    (int) $item['quantity'],
                    [
                        'subtotal' =>
                            (float) $item['price'] *
                            (int) $item['quantity'],
        
                        'total' =>
                            (float) $item['total'],
                    ]
                );
				$child_order->update_meta_data(
					'_item_notes', $item['note']
				);
                /**
                 * Modifiers
                 */

                if ( ! empty( $item['modifiers'] ) ) {
        
                    $line_items = $child_order->get_items(
                        'line_item'
                    );
        
                    $line_item = end( $line_items );
					if ( $line_item ) 
					{
						$line_item_id = $line_item
							? $line_item->get_id()
							: 0;
						$addons               = [];
						$extra_modifier_total = 0;
						foreach ( $item['modifiers'] as $modifier ) {
			
							$modifier_post_id = (int) preg_replace(
								'/\D/',
								'',
								$modifier['external_id']
							);
			
							if ( ! get_post(
								$modifier_post_id
							) ) {
			
								continue;
			
							}
			
							$fee = new WC_Order_Item_Fee();
			
							$fee->set_name(
								$product->get_name()
								. ' - '
								. get_the_title(
									$modifier_post_id
								)
							);

							$amount =
								(float) $modifier['price']
								*
								(int) $modifier['quantity'];

							

							$fee->set_amount(
								$amount
							);
			
							$fee->set_total(
								$amount
							);
			
							$fee->set_tax_status(
								'none'
							);
			
							$fee->set_tax_class(
								''
							);
			
							$fee->set_taxes(
								[
									'total' => [],
									'subtotal' => [],
								]
							);
			
							$fee->add_meta_data(
								'_linked_line_item_id',
								$line_item_id
							);
			
							$fee->add_meta_data(
								'_linked_orderout_item_id',
								$external_id
							);
			
							$fee->add_meta_data(
								'_modifier_id',
								$modifier_post_id
							);
			
							$fee->add_meta_data(
								'_orderout_modifier_id',
								$modifier['id']
							);
			
							$child_order->add_item(
								$fee
							);

							$qty   = isset( $modifier['quantity'] ) ? (int) $modifier['quantity'] : 1;
							$price = (float) $modifier['price'];
							$total = $price * $qty;

							$addons[] = [
								'name'       => get_the_title( $modifier_post_id ),
								'value'      => get_the_title( $modifier_post_id ),
								'price'      => $price,
								'quantity'   => $qty,
								'total'      => $total,
								'modifier_id'=> $modifier_post_id,
								'orderout_id'=> $modifier['id'],
							];

							$extra_modifier_total += $total;
						}

						if ( ! empty( $addons ) ) {

							$line_item->update_meta_data( '_addons', $addons );
							$line_item->update_meta_data( '_modifier_notes', '' );
							$line_item->update_meta_data( '_extra_modifier_amount', $extra_modifier_total );

							$line_item->save();
						}
					}
                }
            }
        
            $child_order->calculate_totals( true );
            $child_order->save();
            
            $this->sync_parent_totals_and_status($child_order->get_parent_id());
        }
		else if(isset($params['flag_type']) && $params['flag_type'] === 'parent_order') 
		{
			// $order->set_customer_id( $user_id );
			$order->update_meta_data( '_parent_order', 'yes' );
			$order->update_meta_data( '_table_id', intval( $params['table_id'] ) );
			$order->update_meta_data( '_zone_id', intval( $params['zone_id'] ) );
			$order->update_meta_data( '_restaurant_id', intval( $params['restaurant_id'] ?? get_user_meta( $user_id, 'user_restaurant_id', true ) ) );
			$order->update_meta_data( '_guest_count', intval( $params['guest_count'] ?? get_post_meta( $params['table_id'], '_table_capacity', true ) ) );
			$order->update_meta_data( '_order_time', current_time( 'Y-m-d H:i:s' ) );
			$order->update_meta_data( '_order_datetime', $params['order_datetime'] );
			$order->update_meta_data( '_table_status', 'Occupied' );
            $current_user_id = get_current_user_id();

            $device_id = get_user_meta( $current_user_id, 'user_device_id', true );
            
            if ( ! empty( $device_id ) ) {
                $order->update_meta_data( 'pos_device_id', $device_id );
            }
			$settings_tax = get_option('pinaka_restaurant_tax_settings', []);
			$restaurant_ids = absint($params['restaurant_id'] ?? 0);
			$restaurant_taxable = $settings_tax[$restaurant_ids] ?? 'no';
			$order_id = $order->get_id();

			update_post_meta( $order_id, '_modifiers_tax', $restaurant_taxable );

			$order->update_meta_data('_dine_in_start_time', current_time('mysql'));

            if (isset($params['reservation_id']) && !empty($params['reservation_id'])) {
            
                $reservation_id = intval($params['reservation_id']);
            
                $order->update_meta_data('_reservation_id', $reservation_id);
            
                update_post_meta($reservation_id, '_table_status', 'Dine in');
                update_post_meta($reservation_id, '_reservation_status', 'Seated');
                update_post_meta($reservation_id, '_dine_in_start_time', current_time('mysql'));
            }
			// Get today's open shift
            $shift_date = current_time( 'Y-m-d' );
        
            $open_shift = get_posts( [
                'post_type'      => 'shifts',
                'post_status'    => 'publish',
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'meta_query'     => [
                    'relation' => 'AND',
                    [
                        'key'     => '_shift_date',
                        'value'   => $shift_date,
                        'compare' => '='
                    ],
                    [
                        'key'     => '_shift_status',
                        'value'   => 'open',
                        'compare' => '='
                    ]
                ]
            ] );
        
            // Save shift ID if found
            if ( ! empty( $open_shift ) ) {
                $order->update_meta_data( 'shift_id', (int) $open_shift[0] );
            }
			$order->set_status( 'processing' );
		}
		else if ( isset($params['flag_type']) && $params['flag_type'] === 'kot_order' ) 
		{
			global $wpdb;
			$captain_id = intval($params['captain_id']);
			// $status_slug = 'wc-yet-to-prepare';
// 			$status_slug = 'wc-yet-to-prepare';
			$status_slug = 'wc-preparing';
			$order->set_parent_id( $params['parent_order_id'] );
			
			$order->set_status( $status_slug, 'No email', true );
			$option_name = 'pinaka_last_kot_number';
			$wpdb->query(
				$wpdb->prepare(
					"INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %d, 'yes')",
					$option_name,
					0
				)
			);
			$wpdb->query("
				UPDATE {$wpdb->options}
				SET option_value = option_value + 1
				WHERE option_name = '{$option_name}'
			");
			$next_number = intval($wpdb->get_var("
				SELECT option_value FROM {$wpdb->options} WHERE option_name = '{$option_name}'
			"));
			$formatted   = str_pad($next_number, 4, '0', STR_PAD_LEFT);
			$kot_number  = 'KOT#' . $formatted;
			$order->set_customer_id( $captain_id );
			$order->update_meta_data( '_kot_number', $kot_number );
			$order->update_meta_data( '_kot_order', 'yes' );
			$order->update_meta_data( '_restaurant_id', $params['restaurant_id'] );
			$order->update_meta_data(
                '_kot_created_time',
                current_time( 'mysql' )
            );

			$parent_orders = wc_get_order( $params['parent_order_id'] );
			if($parent_orders->get_meta('_takeaway_order') !== 'yes')
			{
				$order->update_meta_data( '_zone_id', $params['zone_id'] );
			}
			if($parent_orders->get_meta('_takeaway_order') === 'yes')
			{
			    $status_slug = 'wc-on-hold';
			    $order->update_status( $status_slug, 'No email', true );
			    update_post_meta($params['parent_order_id'],'_child_order',$order->get_id());
			}
			$parent_order_id = absint( $params['parent_order_id'] );
            $current_kot_id  = $order->get_id();
            
            $existing_kot_count = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "
                    SELECT COUNT(id)
                    FROM {$wpdb->prefix}wc_orders
                    WHERE parent_order_id = %d
                    AND id != %d
                    ",
                    $parent_order_id,
                    $current_kot_id
                )
            );
            
            if ( $existing_kot_count === 0 ) {
            
                // First KOT
                $order->update_meta_data( '_kot_order_status', 'new' );
            
            } else {
            
                // Second KOT or later
                $order->update_meta_data( '_kot_order_status', 'running' );
            }

			$rest_taxable = get_post_meta($order->get_parent_id(),'_modifiers_tax',true);

			foreach ( $order->get_items() as $item_id => $item ) {
				$addons_total = floatval( $item->get_meta( '_extra_modifier_amount', true ) );
				if ( $addons_total > 0 ) {

					$fee = new WC_Order_Item_Fee();
					$fee->set_name( $item->get_name() . ' - Modifier' );
					$fee->set_amount( $addons_total );
					$fee->set_total( $addons_total );
					if ( $rest_taxable === 'yes' ) {
						$gst_tax_class = $item->get_tax_class();
						if ( $gst_tax_class === '' ) {
							$product = $item->get_product();
							$gst_tax_class = $product ? $product->get_tax_class() : '';
						}
						$fee->set_tax_status( 'taxable' );
						$fee->set_tax_class( $gst_tax_class );
					} else {
						$fee->set_tax_status( 'none' );
						$fee->set_tax_class( '' );
						$fee->set_taxes( [ 'total' => [], 'subtotal' => [] ] );
					}
					$fee->add_meta_data( '_linked_line_item_id', $item_id );

					$order->add_item( $fee );
				}
				$item->save();
			}
			$order->calculate_totals( true );
		}
		else if ( isset($params['flag_type']) && $params['flag_type'] === 'update_kot_order' ) 
		{
			$rest_taxable = get_post_meta($order->get_parent_id(),'_modifiers_tax',true);

			foreach ( $order->get_items() as $item_id => $item ) {
				$addons_total = floatval( $item->get_meta( '_extra_modifier_amount', true ) );
				if ( $addons_total > 0 ) {

					$fee = new WC_Order_Item_Fee();
					$fee->set_name( $item->get_name() . ' - Modifier' );
					$fee->set_amount( $addons_total );
					$fee->set_total( $addons_total );
					if ( $rest_taxable === 'yes' ) {
						$gst_tax_class = $item->get_tax_class();
						if ( $gst_tax_class === '' ) {
							$product = $item->get_product();
							$gst_tax_class = $product ? $product->get_tax_class() : '';
						}
						$fee->set_tax_status( 'taxable' );
						$fee->set_tax_class( $gst_tax_class );
					} else {
						$fee->set_tax_status( 'none' );
						$fee->set_tax_class( '' );
						$fee->set_taxes( [ 'total' => [], 'subtotal' => [] ] );
					}
					$fee->add_meta_data( '_linked_line_item_id', $item_id );

					$order->add_item( $fee );
				}
				$item->save();
			}
			$order->calculate_totals( true );
		}
		else if (isset($params['flag_type']) && $params['flag_type'] === 'cancel_parent_order' ) 
		{
			$order->update_meta_data( '_table_status', 'Available' );
			$order->update_status( 'cancelled', 'Order cancelled.', true );
			$get_reser_id = intval( $order->get_meta( '_reservation_id', true ) );
		}
		else if (isset($params['flag_type']) && $params['flag_type'] === 'cancel_takeaway_order' ) 
		{
			$order->set_status( 'cancelled', 'Order cancelled.', true );
		}
		else if(isset($params['flag_type']) && $params['flag_type'] === 'complete_parent_order')
		{
			$orderid = $order->get_id();
			$this->sync_parent_totals_and_status( $orderid );
			$parent_status = $order->get_status();
			$order->update_meta_data('_all_done', 'yes');
			if (in_array($parent_status, ['processing', 'pending'], true)) 
			{
				$order->set_status('completed', __('Auto-completed after all KOTs done.', 'pinaka-pos'), true);
				$order->add_order_note(__('All KOTs have been completed.', 'pinaka-pos'));

				$order->update_meta_data( '_table_status', 'Available' );
				$parent_reser_id = intval($order->get_meta( '_reservation_id', true ));
				if($parent_reser_id!=='')
				{
					update_post_meta( $parent_reser_id, '_table_status', 'Ready to Pay' );
					update_post_meta( $parent_reser_id, '_reservation_status', 'Ready to Pay' );
				}
			}
		}
		else if(isset($params['flag_type']) && $params['flag_type'] === 'complete_takeaway_order')
		{
			$orderid = $order->get_id();
			$this->sync_parent_totals_and_status( $orderid );
			$parent_status = $order->get_status();
			$order->update_meta_data('_all_done', 'yes');
			if (in_array($parent_status, ['processing', 'pending'], true)) 
			{
				$order->set_status('completed', __('Auto-completed after all KOTs done.', 'pinaka-pos'), true);
				$order->add_order_note(__('All KOTs have been completed.', 'pinaka-pos'));
			}
		}		
		else if(isset($params['flag_type']) && $params['flag_type'] === 'update_kot_status')
		{
			$this->sync_parent_totals_and_status($order->get_parent_id());
		}
		else if(isset($params['flag_type']) && $params['flag_type'] === 'update_kot_order_status')
		{
		    if($params['status'] === 'served')
		    {
		        $order->update_meta_data(
                    '_served_time',
                    current_time( 'mysql' )
                );
		    }
			$this->sync_parent_totals_and_status($order->get_parent_id());
		}
		else if(isset($params['flag_type']) && $params['flag_type'] === 'cancel_completed_order')
		{
			$order->set_status( 'cancelled', 'Order cancelled.', true );
			$this->update_kot_statuses($order->get_id());
		}
		else if(isset($params['flag_type']) && $params['flag_type'] === 'parent_takeaway_order' || $params['flag_type'] === 'parent_online_order')
		{
			$order->set_created_via(sanitize_text_field( $params['created_via']));
			if($params['flag_type'] === 'parent_takeaway_order')
			{
				$order->update_meta_data('_takeaway_order', 'yes');
			}
			if($params['flag_type'] === 'parent_online_order')
			{
				$order->update_meta_data('_online_order', 'yes');
			}
			$order->update_meta_data( '_order_datetime', $params['order_datetime'] );
			$current_user_id = get_current_user_id();

            $device_id = get_user_meta( $current_user_id, 'user_device_id', true );
            
            if ( ! empty( $device_id ) ) {
                $order->update_meta_data( 'pos_device_id', $device_id );
            }
			$order->update_meta_data( '_restaurant_id', intval( $params['restaurant_id'] ?? get_user_meta( $user_id, 'user_restaurant_id', true ) ) );
			
			$shift_date = current_time('Y-m-d');
            //mobile app
            $open_shift = get_posts([
                'post_type'      => 'shifts',
                'post_status'    => 'publish',
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'meta_query'     => [
                    'relation' => 'AND',
                    [
                        'key'     => '_shift_date',
                        'value'   => $shift_date,
                        'compare' => '='
                    ],
                    [
                        'key'     => '_shift_status',
                        'value'   => 'open',
                        'compare' => '='
                    ]
                ]
            ]);
            
            if (!empty($open_shift)) {
                $order->update_meta_data('shift_id', (int)$open_shift[0]);
            }
            ////
			
			
			$order->set_status( 'processing' );
		}
        $order->save();
		if ( $order->get_parent_id() > 0 ) 
		{
			$this->sync_parent_totals_and_status($order->get_parent_id());
		}
		if (isset($params['flag_type']) && isset( $params['flag_type'], $params['captain_id'] ) && $params['flag_type'] === 'kot_order' && intval( $params['captain_id'] ) > 0 ) 
		{
			$this->sync_kot_user_to_parent($order->get_parent_id(),intval( $params['captain_id'] ));
		}
		$meta_data = (array) $request->get_param( 'meta_data' );
		$is_void_req = false;
		foreach ( $meta_data as $meta ) {
			if ( isset( $meta['key'], $meta['value'] ) && $meta['key'] === 'kot_remarks' ) {
				$is_void_req = true;
				break;
			}
		}
		// if($is_void_req)
		// {
		// 	$this->update_order_totals($order->get_parent_id());
		// }
		if ( $is_void_req ) 
		{
			foreach ( $order->get_items() as $item ) {
				$product = $item->get_product();
				if ( $product ) {
					// error_log('sub total ='.$item->get_subtotal());
					// Reset unit price to current product price
					$item->set_subtotal( $product->get_price() * $item->get_quantity() );
					$item->set_total( $product->get_price() * $item->get_quantity() );
				}
				// $product = $item->get_product();
				// $qty     = $item->get_quantity();

				// if ( $product && $qty > 0 ) {

				// 	// Get current line subtotal (before tax)
				// 	$line_subtotal = (float) $item->get_subtotal();
				// 	error_log('$line_subtotal ='.$line_subtotal);
				// 	// Derive unit price from existing subtotal
				// 	$unit_price = $line_subtotal / $qty;
				// 	error_log('$unit_price ='.$unit_price);
				// 	error_log('$final ='.$unit_price * $qty);
				// 	// Re-apply clean totals
				// 	$item->set_subtotal( $unit_price * $qty );
				// 	$item->set_total( $unit_price * $qty );
				// }
			}
			$order->calculate_totals( true );
			$order->save();
			$this->update_order_totals( $order->get_parent_id() );
			
		}
    }
	private function update_kot_statuses($ordid)
	{
		$children = $this->get_kot_child_orders_by_parent_id($ordid);
		$children = array_filter( $children, function ( $child ) {
			return $child->get_meta( '_kot_number', true );
		} );
		$all_done = true;
		foreach ( $children as $child ) 
		{
			$child->set_status( 'cancelled' );
        	$child->save();
		}
		$order = wc_get_order( $ordid );
		if($order)
		{
    		$tip_amt = 0.0;
            $tip_product_id = absint( get_option( 'pinaka_tip_product_id', 0 ) );
            foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) 
            {
            	$product_id = $item->get_product_id();
            	if (absint($product_id) === $tip_product_id) 
            	{
            		$tip_amt = (float) $item->get_total();
            		$order->remove_item( $item_id );
            	}
            }
            if($tip_amt)
            {
            	$order->delete_meta_data( '_order_tip');
            	$order->calculate_totals(true);
            	$order->save();
            	
            	$order_total = $order->get_total();
            	$meta_rounded_payable = round($order_total);
                $meta_round_off = round($meta_rounded_payable - $order_total, 2);
                $order_total = round($order_total + $meta_round_off, 2);
                update_post_meta($ordid,'_pay_final_total',floatval($order_total));
            }
		}
	}

    public function view_order_information($request)
    {
        $parent_order_id = absint($request->get_param('order_id'));
        if (!$parent_order_id) 
        {
            return new WP_REST_Response(['error' => 'Missing order_id'], 400);
        }
        $parent_order = wc_get_order($parent_order_id);
        if (!$parent_order) 
        {
            return new WP_REST_Response(['error' => 'Invalid order ID'], 404);
        }
        $status = $parent_order->get_status();
		if ( !in_array( $status, ['completed'], true ) ) 
        {
            return new WP_REST_Response([ 'error' => "Order not completed.cannot be edited" ],400);
        }
        if ( in_array( $status, ['trash'], true ) ) 
        {
            return new WP_REST_Response([ 'error' => "Order is in {$status}" ],410);
        }
        if ( $parent_order->get_parent_id() !== 0 ) 
        {
            return new WP_REST_Response(
                [ 'error' => 'Only parent orders are allowed.' ],
                403
            );
        }
        $order_restaurant_id = (int) $parent_order->get_meta( '_restaurant_id' );
        $req_restaurant_id = (int) $request->get_param( 'restaurant_id' );

        $map = [
            '_parent_order'   => 'Dine In',
            '_takeaway_order' => 'Takeaways',
            '_online_order'   => 'Online',
        ];

        $or_type = 'Dine In';
        foreach ( $map as $meta_key => $label ) {
            if ( $parent_order->get_meta( $meta_key ) === 'yes' ) {
                $or_type = $label;
                break;
            }
        }

        if($or_type === 'Dine In')
        {
            $order_zone_id     = (int) $parent_order->get_meta( '_zone_id' );
            $req_zone_id       = (int) $request->get_param( 'zone_id' );
            if ( $order_restaurant_id !== $req_restaurant_id || $order_zone_id !== $req_zone_id ) {
                return new WP_Error(
                    'mismatched_ids',
                    __( 'Restaurant or Zone ID does not match with order Restaurant or Zone ID.', 'pinaka-pos' ),
                    [ 'status' => 403 ]
                );
            }
        }
        else
        {
            if ( $order_restaurant_id !== $req_restaurant_id) {
                return new WP_Error(
                    'mismatched_ids',
                    __( 'Restaurant ID does not match with order Restaurant ID.', 'pinaka-pos' ),
                    [ 'status' => 403 ]
                );
            }
        }
		$table_id = intval( $parent_order->get_meta('_table_id') );
		$zone_id  = intval( $parent_order->get_meta('_zone_id') );

		$table_post = get_post( $table_id );
		$zone_post  = get_post( $zone_id );

		$table_name = $table_post ? $table_post->post_title : '';
		$zone_name  = $zone_post ? $zone_post->post_title : '';
		$created_via = $parent_order->get_created_via();

		if($created_via == "rest-api")
		{
			$created_via = "Dine In";
		}
		else if($created_via == "takeaway")
		{
			$created_via = "Takeaways";
		}
		else if($created_via == "online")
		{
			$created_via = "Online Orders";
		}
		$reservation_id = $parent_order->get_meta('_reservation_id');
		$order_created_time = $parent_order->get_date_created() ? $parent_order->get_date_created()->date( 'Y-m-d h:i A' ) : '';
		
		global $wpdb;
        $use_hpos = class_exists('\Automattic\WooCommerce\Utilities\OrderUtil') &&
                    method_exists('\Automattic\WooCommerce\Utilities\OrderUtil', 'custom_orders_table_usage_is_enabled') &&
                    \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();

        $orders_table  = $use_hpos ? $wpdb->prefix . 'wc_orders' : $wpdb->prefix . 'posts';
        $id_field      = $use_hpos ? 'id' : 'ID';
        $parent_field  = $use_hpos ? 'parent_order_id' : 'post_parent';
        $status_field  = $use_hpos ? 'status' : 'post_status';

        $children = $wpdb->get_col($wpdb->prepare("
            SELECT $id_field FROM {$orders_table}
            WHERE $parent_field = %d
        ", $parent_order_id));

        $kot_orders = [];
		$items_merged = [
			'line_items' => [],
			'fees'       => [],
			'coupons'    => []
		];
		$gst_summary = [];
		$tax_groups = [];
		$grand_total   = 0;
		$tax_total     = 0;
		$fee_total     = 0;
		$fee_tax_total = 0;
		$coupon_total  = 0;
		$global_modifiers = [];
        foreach ( $children as $child_id ) 
        {
            $child_order = wc_get_order( $child_id );
            if ( ! $child_order ) {
                continue;
            }

            $line_items = [];

            foreach ( $child_order->get_items() as $item_id => $item ) 
            {
                if ( 'cancelled' === $item->get_meta( '_item_status', true ) ) {
                    continue; // Skip cancelled items
                }
				$product_id   = (int) $item->get_product_id();
				$variation_id = (int) $item->get_variation_id();
				$resolved_product_id = $variation_id ?: $product_id;
				$product = wc_get_product( $resolved_product_id );
				if ( ! $product ) {
					continue;
				}
				
                $modifier_amount  = 0;
                $gst_amount      = 0;
                $meta_data = $item->get_meta_data();
                foreach ( $meta_data as $meta ) 
                {
                    $meta_key   = $meta->key;
                    $meta_value = $meta->value;
                    if ( in_array( $meta_key, [ '_addons', '_modifiers' ] ) && is_array( $meta_value ) ) 
                    {
                        foreach ( $meta_value as $mod ) 
                        {
                            if ( ! empty( $mod['id'] ) ) 
                            {
                                $mod_post = get_post( $mod['id'] );
                                if ( $mod_post && $mod_post->post_type === 'modifier' ) 
                                {
                                    $global_modifiers[] = $mod_post->post_title;
                                }
                            }
                            else
                            {
                                $global_modifiers[] = $mod['name'];
                            }
                        }
                    }
                }
                $combo_items = [];
                if ( $product && $product->is_type( 'combo_bundle' ) ) 
                {
                    $combo_meta = get_post_meta( $product->get_id(), '_combo_bundle_items', true );
                    $combo_meta = json_decode( $combo_meta, true );

                    if ( ! empty( $combo_meta ) ) {
                        foreach ( $combo_meta as $combo_item ) {
                            $child_product = wc_get_product( $combo_item['id'] );
                            if ( $child_product ) {
                                $combo_items[] = [
                                    'id'       => $child_product->get_id(),
                                    'name'     => $child_product->get_name(),
                                    'price'    => wc_get_price_to_display( $child_product ),
                                    'quantity' => intval( $combo_item['qty'] ),
                                ];
                            }
                        }
                    }
                }
				$item_key = $resolved_product_id;
				if ( $product ) 
                {
                    $price = wc_get_price_to_display( $product );
                } 
                else 
                {
                    $price = 0;
                }
				$qty        = (float) $item->get_quantity();
				$line_total = (float) $item->get_total();
				$line_tax   = (float) $item->get_total_tax();

				$tax_class = $item->get_tax_class() ?: $product->get_tax_class() ?: '';
				if ( $tax_class !== '' ) {
					$taxes = $item->get_taxes();
					if ( ! empty( $taxes['total'] ) ) {
						if ( ! isset( $tax_groups[ $tax_class ] ) ) {
							$tax_groups[ $tax_class ] = [
								'tax_class'   => $tax_class,
								'rate'        => 0,
								'taxable_sum' => 0,
								'tax_amount'  => 0,
							];
						}
						$tax_groups[ $tax_class ]['taxable_sum'] += (float) $line_total;
						$tax_groups[ $tax_class ]['rate'] = (float) $rate['tax_rate'];
						foreach ( $taxes['total'] as $tax_rate_id => $tax_amount ) {
							if ( $tax_rate_id <= 0 || $tax_amount <= 0 ) {
								continue;
							}
							$rate = WC_Tax::_get_tax_rate( (int) $tax_rate_id );
							if ( empty( $rate ) ) {
								continue;
							}
							$tax_groups[ $tax_class ]['tax_amount'] += (float) $tax_amount;
						}
					}
				}

				$grand_total += $line_total;
				$tax_total   += $line_tax;
				
				$name      = $item->get_name();
                $qty       = $item->get_quantity();
                $total     = $item->get_total();

				$line_items[] = [
                    'id'         => $item_id,
                    'product_id' => $item_key,
                    'item_name'  => $name,
                    'quantity'   => $qty,
                    'price'      => floatval($price),
                    'amount'     => floatval( $total ),
					'modifier_amount' => $modifier_amount,
                    'combos'     => $combo_items,
                ];
            }

            $customer_id = $child_order->get_customer_id();
            if ( $customer_id ) {
                $user       = get_userdata( $customer_id );
                $created_by = $user ? $user->display_name : 'Guest';
            } else {
                $created_by = 'Guest';
            }

            $child_data = [
                'id'                   => $child_order->get_id(),
                'time'                 => $child_order->get_date_created() ? $child_order->get_date_created()->date('Y-m-d h:i A') : '',
                'status'               => wc_get_order_status_name( $child_order->get_data()['status'] ),
                'total'                => floatval($child_order->get_total()),
                'kot_number'           => $child_order->get_meta('_kot_number',true),
                'order_by'             => $created_by,
                'line_items'           => $line_items,
            ];

            $kot_orders[] = $child_data;
        }
		$gst_summary = [];
		foreach ( $tax_groups as $group ) {
			$tax_amount = round(
				( $group['taxable_sum'] * $group['rate'] ) / 100,
				2
			);
			$gst_summary[] = [
				'tax_class'   => $group['tax_class'],
				'rate'        => $group['rate'],
				'taxable'     => $group['taxable_sum'],
				'tax_amount'  => $tax_amount,
			];
		}
		foreach ( $parent_order->get_items( 'fee' ) as $fee ) {

			$fee_amount = (float) $fee->get_total();
			$fee_tax    = (float) $fee->get_total_tax();

			$fee_total     += $fee_amount;
			$fee_tax_total += $fee_tax;

			$items_merged['fees'][] = [
				'id'    => $fee->get_id(),
				'name'  => $fee->get_name(),
				'total' => $fee_amount,
				'tax'   => $fee_tax
			];
		}

		foreach ( $parent_order->get_items( 'coupon' ) as $coupon ) 
		{
			$coupon_code = $coupon->get_code();
			$wc_coupon = new WC_Coupon( $coupon_code );
			$discount_type = $wc_coupon->get_discount_type();
			$coupon_amount = 0;

			if ( $discount_type === 'fixed_cart' ) {
				$coupon_amount = $wc_coupon->get_amount();
			} elseif ( $discount_type === 'percent' ) {
				$discount_percentage = $wc_coupon->get_amount();
				$coupon_amount = ( $discount_percentage / 100 ) * $grand_total;
			} elseif ( $discount_type === 'fixed_product' ) {
		
				$product_ids = $wc_coupon->get_product_ids();
				$coupon_amount_per_item = $wc_coupon->get_amount();
				
				foreach ( $kot_orders as $kot ) {
					foreach ( $kot->get_items() as $item ) {
						$product_id = $item->get_product_id();
						
						if ( empty( $product_ids ) || in_array( $product_id, $product_ids ) ) {
							$coupon_amount += $coupon_amount_per_item * $item->get_quantity();
						}
					}
				}
			}

			$coupon_tax = $coupon->get_discount_tax();

			$coupon_total += $coupon_amount;
			$tax_total    -= $coupon_tax;
			$items_merged['coupons'][] = [
				'code'         => $coupon_code,
				'value'        => $coupon_amount,
				'tax'          => $coupon_tax,
				'discount_type' => $discount_type
			];
		}

		$sub_total = max( 0, $grand_total - $coupon_total );
		$merchant_discount = $parent_order->meta_exists( '_order_discount' ) ? (float) $parent_order->get_meta( '_order_discount' ) : 0.0;
		$net_total   = $grand_total + $tax_total + $fee_total + $fee_tax_total;
		$final_total = max( 0, $net_total - $coupon_total );
		if ( $merchant_discount ) {
			$final_total = $final_total + (float) $merchant_discount;
		}

		$reservation_id = absint($parent_order->get_meta('_reservation_id'));
		$cust_name = $reservation_id ? get_post_meta($reservation_id, '_customer_name', true) : 'Guest';
		$cust_phone = $reservation_id ? get_post_meta($reservation_id, '_customer_phone', true) : '-';
		$global_modifiers = array_unique($global_modifiers);
		$actual_total = round( $net_total, 2 );
		$final_total  = round( $final_total, 2 );
		$round_off = round( $final_total - $actual_total, 2 );
		$data = [
			'restaurant_id' => intval($parent_order->get_meta('_restaurant_id')),
			'zone_id'    => intval($parent_order->get_meta('_zone_id')),
			'order_id'   => $parent_order->get_id(),
			'order_created_at' => $order_created_time,
			'status'     => $parent_order->get_status(),
			'table_name' => $table_name,
			'zone_name'  => $zone_name,
			'table_status' => $parent_order->get_meta('_table_status'),
			'order_type'   => $created_via,
			'additional_info' => $global_modifiers,
			'customer_name'  => $cust_name,
			'customer_phone' => $cust_phone,
			'prev_order_total' => $parent_order->get_meta('main_order_total') ?: 0,
			'is_updated' => $parent_order->get_meta('is_updated') ?: '',
			'edit_remarks' => $parent_order->get_meta('order_edit_remarks') ?: '',
 			'kot_orders'  => $kot_orders,
			'payment_details' => [
				'gross_total'        => round( $grand_total, 2 ),
				'coupons_discounts'   => round($coupon_total,2),
				'sub_total'          => round($sub_total,2),
				'gst_summary'        => array_values( $gst_summary ),
				'tax'                => round( $tax_total + $fee_tax_total, 2 ),
				'fees'               => round( $fee_total, 2 ),
				'coupons'            => round( $coupon_total, 2 ),
				'tip'                => 0,
				'net_total'          => round( $net_total, 2 ),
				'merchant_discount'  => $merchant_discount,
				'round_off'          => $round_off,
				'net_payable'        => round( $final_total, 2 )  
			],
		];
		return rest_ensure_response( $data );
    }
	// private function get_request_meta_value( $request, $meta_key ) {
	// 	$meta_data = (array) $request->get_param( 'meta_data' );

	// 	foreach ( $meta_data as $meta ) {
	// 		if ( isset( $meta['key'], $meta['value'] ) && $meta['key'] === $meta_key ) {
	// 			return sanitize_text_field( $meta['value'] );
	// 		}
	// 	}

	// 	return '';
	// }
    public function pinaka_order_kot_edit( WP_REST_Request $request )
	{
		$params = $request->get_json_params();

		$order_id      = absint( $params['order_id'] ?? 0 );
		$restaurant_id = absint( $params['restaurant_id'] ?? 0 );
		$zone_id       = absint( $params['zone_id'] ?? 0 );
		$kot_ids       = array_map( 'absint', (array) ( $params['kot_ids'] ?? [] ) );
		$items_by_kot = (array) ( $params['items'] ?? [] );
		$remarks = isset($params['remarks']) ? $params['remarks'] : '';

		if ( ! $order_id || empty( $kot_ids ) ) {
			return new WP_Error( 'missing_data', 'Order ID or KOT IDs missing', [ 'status' => 400 ] );
		}

		$parent_order = wc_get_order( $order_id );
		if ( ! $parent_order ) {
			return new WP_Error( 'invalid_order', 'Parent order not found', [ 'status' => 404 ] );
		}
		$order_restaurant_id = (int) $parent_order->get_meta( '_restaurant_id' );
        $req_restaurant_id = $restaurant_id;

        $map = [
            '_parent_order'   => 'Dine In',
            '_takeaway_order' => 'Takeaways',
            '_online_order'   => 'Online',
        ];

        $or_type = 'Dine In';

        foreach ( $map as $meta_key => $label ) {
            if ( $parent_order->get_meta( $meta_key ) === 'yes' ) {
                $or_type = $label;
                break;
            }
        }

        if($or_type === 'Dine In')
        {
            $order_zone_id     = (int) $parent_order->get_meta( '_zone_id' );
            $req_zone_id       = $zone_id;
            if ( $order_restaurant_id !== $req_restaurant_id || $order_zone_id !== $req_zone_id ) {
                return new WP_Error(
                    'mismatched_ids',
                    __( 'Restaurant or Zone ID does not match with order Restaurant or Zone ID.', 'pinaka-pos' ),
                    [ 'status' => 403 ]
                );
            }
        }
        else
        {
            if ( $order_restaurant_id !== $req_restaurant_id) {
                return new WP_Error(
                    'mismatched_ids',
                    __( 'Restaurant ID does not match with order Restaurant ID.', 'pinaka-pos' ),
                    [ 'status' => 403 ]
                );
            }
        }
		$global_diff        = 0;
		$global_summary    = [];
		$all_changed_items = [];

		foreach ( $kot_ids as $kot_id ) {

			$order = wc_get_order( $kot_id );
			if ( ! $order ) {
				continue;
			}

			if ( (int) $order->get_parent_id() !== $order_id ) {
				continue;
			}

			$request_map = [];
			foreach ( (array) ( $items_by_kot[ (string) $kot_id ] ?? [] ) as $req_item ) {
				$request_map[ absint( $req_item['item_id'] ) ] = [
					'quantity' => isset( $req_item['quantity'] ) ? (int) $req_item['quantity'] : null,
					'void'     => ! empty( $req_item['void'] ),
				];
			}

			foreach ( $order->get_items() as $item_id => $item ) {

				if ( ! isset( $request_map[ $item_id ] ) ) {
					continue;
				}
				$variation_id = (int) $item->get_variation_id();
				$product_id   = (int) $item->get_product_id();

				$item_key = $variation_id ?: $product_id;
				$product = wc_get_product( $item_key );
				// $product      = $item->get_product();
				$product_name = $item->get_name();
				$orig_qty     = (int) $item->get_quantity();

				$req     = $request_map[ $item_id ];
				$new_qty = $req['void'] ? 0 : ( $req['quantity'] ?? $orig_qty );

				if ( $new_qty > $orig_qty ) {
					return new WP_Error(
						'invalid_quantity',
						'Invalid quantity for ' . $product_name . ' (Item ID ' . $item_id . '). Max qty is ' . $orig_qty,
						[ 'status' => 400 ]
					);
				}

				$orig_line = (float) $item->get_total();
				$orig_tax  = 0;
				$tax_data  = $item->get_taxes();
				if ( isset( $tax_data['total'] ) ) {
					$orig_tax = array_sum( array_map( 'floatval', $tax_data['total'] ) );
				}

				$orig_mod   = (float) $item->get_meta( '_extra_modifier_amount' );
				$orig_total = $orig_line + $orig_tax + $orig_mod;

				$price    = $product ? wc_get_price_to_display( $product ) : 0;
				$new_total = $price * $new_qty;

				if ( wc_tax_enabled() ) 
				{
					$tax_rates = WC_Tax::get_rates( $item->get_tax_class() );
					if ( $tax_rates ) 
					{
						$taxes = WC_Tax::calc_tax( $new_total, $tax_rates, false );
						$new_total += array_sum( $taxes );
					}
				}
				$new_total += $orig_mod;
				if ( $new_qty < $orig_qty ) 
				{
					$diff      = $orig_total - $new_total;
					$diff_qty = $orig_qty - $new_qty;

					$global_diff += $diff;

					if ( ! isset( $global_summary[ $product_name ] ) ) {
						$global_summary[ $product_name ] = 0;
					}
					$global_summary[ $product_name ] += $diff_qty;

					$all_changed_items[] = [
						'kot_id'  => $kot_id,
						'item_id'=> $item_id,
						'name'   => $product_name,
						'qty'    => $diff_qty,
						'amount'=> wc_format_decimal( $diff, 2 )
					];
				}
			}
		}
		$grouped_items = [];

		foreach ( $all_changed_items as $row ) {
			$name = $row['name'];

			if ( ! isset( $grouped_items[ $name ] ) ) {
				$grouped_items[ $name ] = [
					'name'   => $name,
					'qty'    => 0,
					'amount'=> 0,
				];
			}

			$grouped_items[ $name ]['qty'] += (int) $row['qty'];
			$grouped_items[ $name ]['amount'] += (float) $row['amount'];
		}
		$order_total = round( (float) $parent_order->get_total(), 2 );
		$final_total = round( $order_total - $global_diff, 2 );

		return [
			'order_id' => $order_id,
			'net_payable' => wc_format_decimal( $order_total, 2 ),
			'difference'  => wc_format_decimal( -1 * $global_diff, 2 ),
			'updated_net_payable' => wc_format_decimal( $final_total, 2 ),
			// 'changed_items_text' => implode(
			// 	', ',
			// 	array_map(
			// 		function( $name, $qty ) {
			// 			return "{$name} ({$qty})";
			// 		},
			// 		array_keys( $global_summary ),
			// 		$global_summary
			// 	)
			// ),
			// 'changed_items' => $grouped_items,
			"remarks" => $remarks,
			'payload' => $params
		];
	}
    public function pinaka_order_kot_edit_reasons()
	{
		$edit_reasons = [
			'Wrong Item Added',
			'Customer requested item change',
			'Quantity Correction',
			'Item Preparation Error',
			'Unavailable Item Replacement',
			'Wrong Table or Order Mapped',
			'Manual Entry Mistakes'
		];
		$reasons_list = [
			'reasons' => $edit_reasons
		];
		return rest_ensure_response( $reasons_list );
	}
    public function get_order_kot_list($request)
	{
		$order_id = absint( $request->get_param( 'order_id' ) ?? 0 );
		$restaurant_id = absint( $request->get_param( 'restaurant_id' ) ?? 0 );
		$zone_id = absint( $request->get_param( 'zone_id' ) ?? 0 );

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return new WP_Error( 'invalid_order', 'Order not found', [ 'status' => 404 ] );
		}
		$order_restaurant_id = (int) $order->get_meta( '_restaurant_id' );
        $req_restaurant_id = $restaurant_id;

        $map = [
            '_parent_order'   => 'Dine In',
            '_takeaway_order' => 'Takeaways',
            '_online_order'   => 'Online',
        ];

        $or_type = 'Dine In';

        foreach ( $map as $meta_key => $label ) {
            if ( $order->get_meta( $meta_key ) === 'yes' ) {
                $or_type = $label;
                break;
            }
        }

        if($or_type === 'Dine In')
        {
            $order_zone_id     = (int) $order->get_meta( '_zone_id' );
            $req_zone_id       = $zone_id;
            if ( $order_restaurant_id !== $req_restaurant_id || $order_zone_id !== $req_zone_id ) {
                return new WP_Error(
                    'mismatched_ids',
                    __( 'Restaurant or Zone ID does not match with order Restaurant or Zone ID.', 'pinaka-pos' ),
                    [ 'status' => 403 ]
                );
            }
        }
        else
        {
            if ( $order_restaurant_id !== $req_restaurant_id) {
                return new WP_Error(
                    'mismatched_ids',
                    __( 'Restaurant ID does not match with order Restaurant ID.', 'pinaka-pos' ),
                    [ 'status' => 403 ]
                );
            }
        }
		$kot_orders = $this->get_kot_child_orders_by_parent_id( $order_id );
		if(empty($kot_orders))
		{
			return new WP_Error(
				'mismatched_ids',
				__( 'The Order has no kots.', 'pinaka-pos' ),
				[ 'status' => 403 ]
			);
		}
		$list_kots = [];
		foreach ( $kot_orders as $kot ) {
			$kot_id = $kot->get_id();
			$kot_nos = $kot->get_meta( '_kot_number', true );
			$list_kots[] = [
				'kot_id' => $kot_id,
				'kot_no' => $kot_nos
			];
		}
		$list_of_kots = [
			'kot_details' => $list_kots
		];
		return rest_ensure_response( $list_of_kots );
	}
    // public function pinaka_order_kot_update($request)
	// {
	// 	$params = $request->get_json_params();

	// 	$order_id      = absint( $params['order_id'] ?? 0 );
	// 	$restaurant_id = absint( $params['restaurant_id'] ?? 0 );
	// 	$zone_id       = absint( $params['zone_id'] ?? 0 );
	// 	$kot_ids       = array_map( 'absint', (array) ( $params['kot_ids'] ?? [] ) );
	// 	$items_by_kot = (array) ( $params['items'] ?? [] );
	// 	$remarks = isset($params['remarks']) ? $params['remarks'] : '';

	// 	if ( ! $order_id || empty( $kot_ids ) ) {
	// 		return new WP_Error( 'missing_data', 'Order ID or KOT IDs missing', [ 'status' => 400 ] );
	// 	}

	// 	$parent_order = wc_get_order( $order_id );
	// 	if ( ! $parent_order ) {
	// 		return new WP_Error( 'invalid_order', 'Parent order not found', [ 'status' => 404 ] );
	// 	}
	// 	$main_order_total = floatval($parent_order->get_total());
	// 	$order_restaurant_id = (int) $parent_order->get_meta( '_restaurant_id' );
    //     $req_restaurant_id = $restaurant_id;

    //     $map = [
    //         '_parent_order'   => 'Dine In',
    //         '_takeaway_order' => 'Takeaways',
    //         '_online_order'   => 'Online',
    //     ];

    //     $or_type = 'Dine In';

    //     foreach ( $map as $meta_key => $label ) {
    //         if ( $parent_order->get_meta( $meta_key ) === 'yes' ) {
    //             $or_type = $label;
    //             break;
    //         }
    //     }

    //     if($or_type === 'Dine In')
    //     {
    //         $order_zone_id     = (int) $parent_order->get_meta( '_zone_id' );
    //         $req_zone_id       = $zone_id;
    //         if ( $order_restaurant_id !== $req_restaurant_id || $order_zone_id !== $req_zone_id ) {
    //             return new WP_Error(
    //                 'mismatched_ids',
    //                 __( 'Restaurant or Zone ID does not match with order Restaurant or Zone ID.', 'pinaka-pos' ),
    //                 [ 'status' => 403 ]
    //             );
    //         }
    //     }
    //     else
    //     {
    //         if ( $order_restaurant_id !== $req_restaurant_id) {
    //             return new WP_Error(
    //                 'mismatched_ids',
    //                 __( 'Restaurant ID does not match with order Restaurant ID.', 'pinaka-pos' ),
    //                 [ 'status' => 403 ]
    //             );
    //         }
    //     }

	// 	$global_summary = [];
	// 	$updated_kots  = [];
		
	// 	foreach ( $kot_ids as $kot_id ) {

	// 		$order = wc_get_order( $kot_id );
	// 		if ( ! $order ) {
	// 			continue;
	// 		}

	// 		if ( (int) $order->get_parent_id() !== $order_id ) {
	// 			continue;
	// 		}

	// 		$request_map = [];
	// 		foreach ( (array) ( $items_by_kot[ (string) $kot_id ] ?? [] ) as $req_item ) {
	// 			$request_map[ absint( $req_item['item_id'] ) ] = [
	// 				'quantity' => isset( $req_item['quantity'] ) ? (int) $req_item['quantity'] : null,
	// 				'void'     => ! empty( $req_item['void'] ),
	// 			];
	// 		}

	// 		$allQtyZero    = true;
	// 		$line_item_ids = [];
	// 		$order_item_ids = array_keys( $order->get_items() );
	// 		foreach ( $request_map as $posted_item_id => $data ) 
	// 		{
	// 			if ( ! in_array( $posted_item_id, $order_item_ids, true ) ) {
	// 				return new WP_Error(
	// 					'invalid_line_item',
	// 					'Line item ID ' . $posted_item_id . ' does not exist in KOT ' . $kot_id,
	// 					[ 'status' => 400 ]
	// 				);
	// 			}
	// 		}
	// 		foreach ( $order->get_items() as $item_id => $item ) 
	// 		{
	// 			if ( ! isset( $request_map[ $item_id ] ) ) {
	// 				$allQtyZero = false;
	// 				continue;
	// 			}
	// 			$product_name = $item->get_name();
	// 			$orig_qty     = (int) $item->get_quantity();

	// 			$req     = $request_map[ $item_id ];
	// 			$new_qty = $req['void'] ? 0 : ( $req['quantity'] ?? $orig_qty );

	// 			if ( $new_qty > $orig_qty ) {
	// 				return new WP_Error(
	// 					'invalid_quantity',
	// 					'Invalid quantity for ' . $product_name . ' (Item ID ' . $item_id . '). Max qty is ' . $orig_qty,
	// 					[ 'status' => 400 ]
	// 				);
	// 			}

	// 			$line_item_ids[] = $item_id;
	// 			$diff = $orig_qty - $new_qty;

	// 			if ( $diff > 0 ) {
	// 				$variation_id = (int) $item->get_variation_id();
	// 				$product_id   = (int) $item->get_product_id();
	// 				$item_key = $variation_id ?: $product_id;
	// 				$product = wc_get_product( $item_key );

	// 				// $product = $item->get_product();

	// 				if ( $product && $product->managing_stock() ) {
	// 					wc_update_product_stock(
	// 						$product,
	// 						$diff,
	// 						'decrease'
	// 					);

	// 					// Optional POS audit trail
	// 					$order->add_order_note(
	// 						sprintf(
	// 							'Stock reduced by %d for %s (KOT %d)',
	// 							$diff,
	// 							$product->get_name(),
	// 							$kot_id
	// 						)
	// 					);
	// 				}
	// 			}
	// 			if ( $new_qty === 0 ) {
	// 				$order->remove_item( $item_id );
	// 			} else {
	// 				$variation_id = (int) $item->get_variation_id();
	// 				$product_id   = (int) $item->get_product_id();
	// 				$item_key = $variation_id ?: $product_id;
	// 				$product = wc_get_product( $item_key );
	// 				// $product = $item->get_product();
	// 				$price = $product ? wc_get_price_to_display( $product ) : 0;
	// 				$line_subtotal = $price * $new_qty;
	// 				$item->set_quantity( $new_qty );
	// 				$item->set_subtotal( wc_format_decimal( $line_subtotal ) );
	// 				$item->set_total( wc_format_decimal( $line_subtotal ) );
	// 				if ( wc_tax_enabled() ) {
	// 					$tax_rates = WC_Tax::get_rates( $item->get_tax_class() );
	// 					if ( ! empty( $tax_rates ) ) {
	// 						$taxes = WC_Tax::calc_tax( $line_subtotal, $tax_rates, false );
	// 						$item->set_taxes( [
	// 							'total'    => array_map( 'wc_format_decimal', $taxes ),
	// 							'subtotal' => array_map( 'wc_format_decimal', $taxes ),
	// 						] );
	// 					}
	// 				}

	// 				$item->save();
	// 				$allQtyZero = false;
	// 			}
	// 		}
	// 		if ( $allQtyZero ) 
	// 		{
	// 			$order->update_status( 'cancelled' );
	// 			foreach ( $order->get_fees() as $fee_item_id => $fee_item ) 
	// 			{
	// 				$linked_line_item_id = $fee_item->get_meta( '_linked_line_item_id' );
	// 				if ( $linked_line_item_id && in_array( $linked_line_item_id, $line_item_ids, true ) ) 
	// 				{
	// 					$order->remove_item( $fee_item_id );
	// 				}
	// 			}
	// 		}
	// 		$order->calculate_totals();
	// 		$order->save();
	// 		$updated_kots[] = $kot_id;
	// 	}
	// 	$parent_order->update_meta_data('is_updated', 'Yes');
	// 	$parent_order->update_meta_data('order_edit_remarks', $remarks);
	// 	$parent_order->update_meta_data('main_order_total', $main_order_total);
	// 	$parent_order->save();
		
	// 	$this->update_order_totals($order_id);
	// 	return [
	// 		'status' => 'success',
	// 		'message' => 'Order items updated successfully',
	// 		'order_id' => $order_id,
	// 		'zone_id' => $zone_id,
	// 		'restaurant_id' => $restaurant_id,
	// 	];
	// }
	// public function update_order_totals($parent_id)
	// {
	// 	$parent = wc_get_order($parent_id);
	// 	if (!$parent) {
	// 		return;
	// 	}
	// 	$total = (float) $parent->get_total();
	// 	$existing = $parent->get_meta( 'order_prev_total', true ) ?: '';
	// 	if ( $existing === '' ) {
	// 		$parent->update_meta_data( 'order_prev_total', $total );
	// 	}
	// 	$children = $this->get_kot_child_orders_by_parent_id($parent_id);
	// 	if (empty($children)) 
	// 	{
	// 		return;
	// 	}
	// 	$total = 0;		
	// 	$parent_rmks = '';$parent_voided = '';
	// 	foreach ($children as $child) 
	// 	{
	// 		$status = $child->get_status();
	// 		if ($status !== 'cancelled') {
	// 			$total += floatval($child->get_total());
	// 		}

	// 		$last_remarks = $child->get_meta('order_edit_remarks') ?: '';
	// 		$is_voided = $child->get_meta('is_voided') ?: '';
	// 		if($last_remarks)
	// 		{
	// 			$parent_rmks = $last_remarks;
	// 		}
	// 		if($is_voided)
	// 		{
	// 			$parent_voided = $is_voided;
	// 		}
	// 	}
	// 	if(!empty($parent_rmks))
	// 	{
	// 		$parent->update_meta_data('order_edit_remarks',$parent_rmks);
	// 	}
	// 	if(!empty($parent_voided))
	// 	{
	// 		$parent->update_meta_data('is_voided',$parent_voided);
	// 	}
	// 	if($total <= 0)
	// 	{
	// 		$tip_amount = $parent->meta_exists( '_order_tip' )
	// 		? (float) $parent->get_meta( '_order_tip' )
	// 		: '';
	// 		// if($tip_amount)
	// 		// {
	// 		// 	$parent->delete_meta_data('_order_tip');
	// 		// }
	// 		$merchant_discount = $parent->meta_exists( '_order_discount' ) ? (float) $parent->get_meta( '_order_discount' ) : '';
	// 		if($merchant_discount)
	// 		{
	// 			$parent->delete_meta_data('_order_discount');
	// 		}
	// 		$is_nc = $parent->meta_exists( '_is_nc_order' )
	// 		? $parent->get_meta( '_is_nc_order' )
	// 		: 'no';
	// 		if($is_nc === 'yes')
	// 		{
	// 			$parent->delete_meta_data('_is_nc_order');
	// 		}
	// 		$discount_reason = $parent->meta_exists( '_discount_reason' )
	// 		? $parent->get_meta( '_discount_reason' )
	// 		: '';
	// 		if($discount_reason)
	// 		{
	// 			$parent->delete_meta_data('_discount_reason');
	// 		}
	// 	}
	// 	$parent->set_total($total);
	// 	$parent->save();
	// }
	public function update_order_totals($parent_id)
	{
		$parent = wc_get_order($parent_id);
		if (!$parent) {
			return;
		}

		// Store original total once
		// $existing = $parent->get_meta('order_prev_total');
		// if ($existing === '') {
		// 	$parent->update_meta_data('order_prev_total', (float) $parent->get_total());
		// }
		$children = $this->get_kot_child_orders_by_parent_id($parent_id);
		if (empty($children)) {
			return;
		}

		$total = 0;
		$parent_rmks = '';
		$parent_voided = '';
        $all_children_cancelled = true;
		foreach ($children as $child) {
			$status = $child->get_status();
            $child_all_items_zero = true;

            // Check all items in this child order
            foreach ($child->get_items() as $item) {
                if ((float) $item->get_quantity() > 0) {
                    $child_all_items_zero = false;
                    break;
                }
            }
            if ($child_all_items_zero) {
                $child->update_status('cancelled', 'All child order items have quantity 0.');
                $child->save();
            }
			if ($status !== 'cancelled') {
				$total += (float) $child->get_total();
			}

			$last_remarks = $child->get_meta('order_edit_remarks');
			$is_voided   = $child->get_meta('is_voided');

			if ($last_remarks !== '') {
				$parent_rmks = $last_remarks;
				
			}

			if ($is_voided !== '') {
				$parent_voided = $is_voided;
			}
		}

		// Update parent meta
		if ($parent_rmks !== '') {
			$parent->update_meta_data('order_edit_remarks', $parent_rmks);
		}
		
		if ($parent_voided !== '') {
			$parent->update_meta_data('is_voided', $parent_voided);
		}

		// Cleanup if total is zero
		if ($total <= 0) {

			if ($parent->get_meta('_order_tip') !== '') {
				$parent->delete_meta_data('_order_tip');
			}

			if ($parent->get_meta('_order_discount') !== '') {
				$parent->delete_meta_data('_order_discount');
			}

			if ($parent->get_meta('_is_nc_order') === 'yes') {
				$parent->delete_meta_data('_is_nc_order');
			}

			if ($parent->get_meta('_discount_reason') !== '') {
				$parent->delete_meta_data('_discount_reason');
			}
		}

		// Update total
		$parent->set_total($total);
        $parent->calculate_totals(true);
		// Persist everything
		$parent->save();
	}

	public function sync_kot_user_to_parent( $order_id, $captain_id ) 
	{
		if ( empty( $order_id ) || empty( $captain_id ) ) {
			return;
		}
		$kot_orders = $this->get_kot_child_orders_by_parent_id( $order_id );
		if ( ! empty( $kot_orders ) && count( $kot_orders ) === 1 ) 
		{
			$parent_order = wc_get_order( $order_id );
			if ( $parent_order && $parent_order instanceof WC_Order ) {
				$parent_order->set_customer_id( intval( $captain_id ) );
				$parent_order->save();
			}
		}
	}
	public function sync_parent_totals_and_status($parent_id) 
	{
		$parent = wc_get_order($parent_id);
		if (!$parent) {
			return;
		}
		$children = $this->get_kot_child_orders_by_parent_id($parent_id);
		if (empty($children)) 
		{
			return;
		}
		$child_count = count( $children );
		$parcel_amt = 0;
		
		$total = 0;
		$all_done = true;
		foreach ($children as $child) 
		{
			$status = $child->get_status();
			if ($status !== 'cancelled') {
				$total += floatval($child->get_total());
			}

			if (!in_array($status, ['completed','cancelled'], true)) {
				$all_done = false;
			}
		}
		// if ( $child_count === 1 && $parent->get_meta( '_takeaway_order', true ) ) {
		// 	foreach ($parent->get_fees() as $fee) {
		// 		$total += floatval($fee->get_total());
		// 	}
		// }
		// foreach ($parent->get_fees() as $fee) {
		// 	$total += floatval($fee->get_total());
		// }
// 		if($parent->get_status() !== 'completed')
// 		{
// 			update_post_meta($parent->get_id(),'_order_prev_total', $total);
// 		}
		$net_payable_product_id = get_option( 'pinaka_order_amount_product_id', 0 );
		$net_payable_item = null;

        foreach ( $parent->get_items() as $item_id => $item ) {
        
            if ( $item->get_product_id() == $net_payable_product_id ) {
                $net_payable_item = $item;
                break;
            }
        }
        if ( $net_payable_item ) {

            $net_payable_item->set_quantity( 1 );
            $net_payable_item->set_subtotal( $total );
            $net_payable_item->set_total( $total );
            $net_payable_item->save();
        
        } else {
        
            $product = wc_get_product( $net_payable_product_id );
        
            $item = new WC_Order_Item_Product();
        
            $item->set_product( $product );
            $item->set_quantity( 1 );
            $item->set_subtotal( $total );
            $item->set_total( $total );
        
            $parent->add_item( $item );
        }
        if ( floatval( $total ) <= 0 ) {
            foreach ( $parent->get_items( 'fee' ) as $item_id => $fee ) {
                if ( strpos( $fee->get_name(), 'Service Charge' ) !== false ) {
                    $parent->remove_item( $item_id );
                }
            }
        }
        $parent->update_meta_data( '_table_status', 'Dine in' );
		$parent->calculate_totals( false );
        $parent->save();
	}

	private function get_kot_child_orders_by_parent_id($parent_id) 
	{
        global $wpdb;

        $use_hpos = false;
        if (class_exists('\Automattic\WooCommerce\Utilities\OrderUtil') &&
            method_exists('\Automattic\WooCommerce\Utilities\OrderUtil', 'custom_orders_table_usage_is_enabled')) {
            $use_hpos = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
        }
	
        $orders_table  = $use_hpos ? $wpdb->prefix . 'wc_orders' : $wpdb->prefix . 'posts';
        $id_field      = $use_hpos ? 'id' : 'ID';
        $parent_field  = $use_hpos ? 'parent_order_id' : 'post_parent';
        $status_field  = $use_hpos ? 'status' : 'post_status';
		
		$sql = "
            SELECT $id_field FROM {$orders_table}
            WHERE $parent_field = %d
        ";
        $child_ids = $wpdb->get_col($wpdb->prepare($sql, $parent_id));
        $orders = array_filter(array_map('wc_get_order', $child_ids));
        return $orders;
    }
    private function get_kot_child_orders_by_parent_id_without_cancel( $parent_id ) {
        global $wpdb;
    
        $use_hpos = false;
        if (
            class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) &&
            method_exists( '\Automattic\WooCommerce\Utilities\OrderUtil', 'custom_orders_table_usage_is_enabled' )
        ) {
            $use_hpos = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
        }
    
        $orders_table = $use_hpos ? $wpdb->prefix . 'wc_orders' : $wpdb->prefix . 'posts';
        $id_field     = $use_hpos ? 'id' : 'ID';
        $parent_field = $use_hpos ? 'parent_order_id' : 'post_parent';
        $status_field = $use_hpos ? 'status' : 'post_status';
    
        $statuses = array(
            'yet-to-prepare',
            'preparing',
            'ready',
            'served',
            'wc-on-hold'
        );
    
        $placeholders = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );
    
        $sql = "
            SELECT {$id_field}
            FROM {$orders_table}
            WHERE {$parent_field} = %d
            AND {$status_field} IN ($placeholders)
        ";
    
        $child_ids = $wpdb->get_col(
            $wpdb->prepare(
                $sql,
                $parent_id,
                ...$statuses
            )
        );
    
        $orders = array_filter( array_map( 'wc_get_order', $child_ids ) );
    
        return $orders;
    }
	public function pinaka_get_line_items($request) 
	{
		$params     = $request->get_params();
		$order_id   = isset($params['kot_id']) ? intval($params['kot_id']) : 0;
		if (!$order_id) 
		{
			return new WP_Error('no_order', 'KOT Order ID missing', ['status' => 400]);
		}
		$order = wc_get_order($order_id);
		if (!$order) 
		{
			return new WP_Error('invalid_order', 'Invalid KOT Order ID', ['status' => 404]);
		}
		
		// Get parent order
        $parent_order_id = (int) $order->get_parent_id();
        $parent_order = $parent_order_id ? wc_get_order($parent_order_id) : false;
        
        // Get user details from parent order
        $placed_by_user_id = $parent_order ? $parent_order->get_user_id() : 0;
        
        $placed_by_first_name = '';
        $placed_by_last_name  = '';
        $placed_by_role       = '';
        $emp_login_pin        = '';
        
        if ($placed_by_user_id) {
        
            $placed_by_user = get_userdata($placed_by_user_id);
        
            if ($placed_by_user) {
                $placed_by_first_name = $placed_by_user->first_name;
                $placed_by_last_name  = $placed_by_user->last_name;
        
                $placed_by_role = !empty($placed_by_user->roles)
                    ? $placed_by_user->roles[0]
                    : '';
            }
        
            $emp_login_pin = get_user_meta(
                $placed_by_user_id,
                'emp_login_pin',
                true
            );
        }
        
        // Get all initial KOT items saved before the order was updated/voided
$all_initial_kot_items = [];

if ($parent_order) {

    $all_initial_kot_items = $parent_order->get_meta(
        '_kot_initial_items',
        true
    );

    if (!is_array($all_initial_kot_items)) {
        $all_initial_kot_items = [];
    }
}

// Get only initial items belonging to the requested KOT
$initial_kot_items = [];

foreach ($all_initial_kot_items as $initial_item) {

    if (
        isset($initial_item['kot_order_id']) &&
        (int) $initial_item['kot_order_id'] === $order_id
    ) {
        $initial_kot_items[] = $initial_item;
    }
}

// Calculate previous KOT total from filtered KOT items
$kot_prev_total = 0;

foreach ($initial_kot_items as $initial_item) {

    if (isset($initial_item['total_wo_tax'])) {
        $kot_prev_total += (float) $initial_item['total_wo_tax'];
    }
}

$kot_prev_total = round($kot_prev_total, 2);
        
        
		$order_restaurant_id = (int) $order->get_meta( '_restaurant_id' );
		$order_zone_id       = (int) $order->get_meta( '_zone_id' );
		
		$req_restaurant_id = isset( $params['restaurant_id'] ) ? (int) $params['restaurant_id'] : 0;
		$req_zone_id       = isset( $params['zone_id'] ) ? (int) $params['zone_id'] : 0;

		if ( $order_restaurant_id !== $req_restaurant_id || $order_zone_id !== $req_zone_id ) 
		{
			return new WP_Error(
				'mismatched_ids',
				__( 'Restaurant or Zone ID does not match with order Restaurant or Zone ID.', 'pinaka-pos' ),
				[ 'status' => 403 ]
			);
		}
		$is_kot_order = $order->get_meta('_kot_order', true);
		if (!$is_kot_order) 
		{
			return new WP_Error('invalid_order', 'Invalid KOT Order ID', ['status' => 404]);
		}
		$response = [];
		$kot_total = 0;
		foreach ($order->get_items() as $item_id => $item) 
		{
		  //  if ( 'cancelled' === wc_get_order_item_meta( $item_id, '_item_status', true ) ) {
    //             continue;
    //         }
			$variation_id = (int) $item->get_variation_id();
			$product_id   = (int) $item->get_product_id();

			$item_key = $variation_id ?: $product_id;
			$product = wc_get_product( $item_key );

			$product_name  = $item->get_name();
			$qty   = (float) $item->get_quantity();
            $total = (float) $item->get_total();
            
            // Find original quantity from initial KOT items
            $original_quantity = 0;
            
            foreach ($initial_kot_items as $initial_item) {
                if (
                    isset($initial_item['line_item_id']) &&
                    (int) $initial_item['line_item_id'] === (int) $item_id
                ) {
                    $original_quantity = (float) $initial_item['quantity'];
                    break;
                }
            }
            
            
			if ( $product ) 
			{
				$price = wc_get_price_to_display( $product );
			} 
			else 
			{
				$price = 0;
			}
			
			$voided_quantity = max(
                0,
                $original_quantity - $qty
            );
            
            $voided_amount = round(
                $price * $voided_quantity,
                2
            );

			$attributes = [];
			if ($product && $product->is_type('variation')) {
				$variation_data = wc_get_product_variation_attributes($product->get_id());
				foreach ($variation_data as $attr_value) {
					$attributes[] = $attr_value;
				}
			}

			$all_modifiers    = [];
			$modifier_amount  = 0;
			$gst_amount      = 0;
			$meta_data = $item->get_meta_data();
			// echo json_encode($meta_data);
			foreach ($meta_data as $meta) 
			{
				$meta_key   = $meta->key;
				$meta_value = $meta->value;

				if (in_array($meta_key, ['_addons', '_modifiers']) && is_array($meta_value)) 
				{
					foreach ($meta_value as $mod) 
					{
						if (!empty($mod['id'])) 
						{
							$mod_post = get_post($mod['id']);
							if ($mod_post && $mod_post->post_type === 'modifier') 
							{
								$all_modifiers[] = $mod_post->post_title;
							}
						}
						else
						{
							$all_modifiers[] = $mod['name'];
						}
					}
				}
				if ($meta_key === '_extra_modifier_amount') 
				{
					$modifier_amount += floatval($meta_value);
				}
			}
			$combo_items = [];
			if ( $product && $product->is_type( 'combo_bundle' ) ) 
			{
				$combo_meta = get_post_meta( $product->get_id(), '_combo_bundle_items', true );
				$combo_meta = json_decode( $combo_meta, true );

				if ( ! empty( $combo_meta ) ) {
					foreach ( $combo_meta as $combo_item ) {
						$child_product = wc_get_product( $combo_item['id'] );
						if ( $child_product ) {
							$combo_items[] = [
								'id'       => $child_product->get_id(),
								'name'     => $child_product->get_name(),
								'price'    => wc_get_price_to_display( $child_product ),
								'quantity' => intval( $combo_item['qty'] ),
							];
						}
					}
				}
			}
			// $tax_data = $item->get_taxes();
            // if (isset($tax_data['total'])) 
			// {
            //     $tax_totals = $tax_data['total'];
            //     foreach ($tax_totals as $tax_amount) 
			// 	{
            //         $gst_amount += floatval($tax_amount);
            //     }
            //     $total += $gst_amount;
            // }
			$total += $modifier_amount;
			$item_status = wc_get_order_item_meta( $item_id, '_item_status', true );

            $is_cancelled = ( strtolower( $item_status ) === 'cancelled' ) ? 'yes' : 'no';
			$response[] = [
                'id'                => $item_id,
                'product_id'        => $product ? $product->get_id() : 0,
                'product_name'      => $product_name,
                'attributes'        => $attributes,
                'quantity'          => $qty,
                'original_quantity' => $original_quantity,
                'voided_quantity'   => $voided_quantity,
                'voided_amount' => floatval($voided_amount),
                'price'             => ( strtolower( $item_status ) === 'cancelled' ) ? 0 : floatval($price),
                'amount'            => floatval($total),
                'is_cancelled'      => $is_cancelled,
                'modifiers'         => $all_modifiers,
                'combos'            => $combo_items,
            ];
			$kot_total += floatval($total);
		}
		
		$diff_in_total = round($kot_prev_total - $kot_total, 2);
		
		// Fetch voided items for this KOT
        $voided_items = [];
        $final_void_remarks = '';
        $final_voided_date  = '';
        
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'order_void_logs';
        
        $table_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $table_name
            )
        );
        
        if ($table_exists === $table_name) {
        
            $void_logs = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT data, created_at
                     FROM {$table_name}
                     WHERE post_id = %d
                     ORDER BY id DESC",
                    $order_id
                ),
                ARRAY_A
            );
        
            foreach ((array) $void_logs as $log) {

                $log_data = json_decode($log['data'], true);
            
                if (!is_array($log_data)) {
                    continue;
                }
            
                // Latest void record
                if (empty($final_void_remarks)) {
                    $final_void_remarks = $log_data['remarks'] ?? '';
                    $final_voided_date  = $log['created_at'] ?? '';
                }
            
                $voided_items[] = [
                    'item_id'   => (int) ($log_data['item_id'] ?? 0),
                    'product'   => $log_data['product'] ?? '',
                    'new_qty'   => (int) ($log_data['new_qty'] ?? 0),
                    'remarks'   => $log_data['remarks'] ?? '',
                    'voided_by' => (int) ($log_data['voided_by'] ?? 0),
                    'voided_at' => $log['created_at'],
                ];
            }
        }
		return [
			'restaurant_id' => $order_restaurant_id,
			'zone_id'    => $order_zone_id,
			'kot_id'     => $order_id,
			'kot_prev_total'    => $kot_prev_total,
			'kot_total'  => floatval($kot_total),
			'diff_in_total'     => $diff_in_total,
			'kot_number' => $order->get_meta('_kot_number', true),
			'placed_by_user_id'    => (int) $placed_by_user_id,
            'placed_by_first_name' => $placed_by_first_name,
            'placed_by_last_name'  => $placed_by_last_name,
            'placed_by_role'       => $placed_by_role,
            'emp_login_pin'        => $emp_login_pin,
            'final_void_remarks' => $final_void_remarks,
            'final_voided_date'  => $final_voided_date,
			'initial_kot_items' => $initial_kot_items,
			'voided_items'       => $voided_items,
			'items'      => $response
		];
	}
	public function pinaka_void_item_selection($request)
	{
		$params   = $request->get_json_params();
		$order_id = isset($params['kot_id']) ? absint($params['kot_id']) : 0;
		$items    = isset($params['items']) ? (array) $params['items'] : [];

		// Map request items
		$request_map = [];
		foreach ($items as $req_item) {
			if (empty($req_item['item_id'])) {
				continue;
			}
			$request_map[absint($req_item['item_id'])] = [
				'quantity' => isset($req_item['quantity']) ? absint($req_item['quantity']) : null,
				'void'     => !empty($req_item['void']),
			];
		}

		if (!$order_id || empty($request_map)) {
			return new WP_Error('missing_data', 'Order ID or items missing', ['status' => 400]);
		}

		$order = wc_get_order($order_id);
		if (!$order) {
			return new WP_Error('invalid_order', 'Order not found', ['status' => 404]);
		}
		$order->calculate_taxes();
		$order->calculate_totals();
		$order->save();

		$order_restaurant_id = (int) $order->get_meta('_restaurant_id');
		$order_zone_id       = (int) $order->get_meta('_zone_id');

		$req_restaurant_id = isset($params['restaurant_id']) ? (int) $params['restaurant_id'] : 0;
		$req_zone_id       = isset($params['zone_id']) ? (int) $params['zone_id'] : 0;

		if ($order_restaurant_id !== $req_restaurant_id || $order_zone_id !== $req_zone_id) {
			return new WP_Error(
				'mismatched_ids',
				__('Restaurant or Zone ID does not match order', 'pinaka-pos'),
				['status' => 403]
			);
		}

		if ($order->get_parent_id() <= 0) {
			return new WP_Error('invalid_order', 'Order is not a KOT order', ['status' => 404]);
		}

		$response_items  = [];
		$kot_total      = 0;
		$pos_line_items = [];

		foreach ( $order->get_items() as $item_id => $item ) {
			$orig_qty = (int) $item->get_quantity();
			if ( isset( $request_map[ $item_id ] ) ) {

				if ( ! empty( $request_map[ $item_id ]['void'] )) {
					$qty = 0;
				} else {
					$qty = $request_map[ $item_id ]['quantity'] !== null
						? (int) $request_map[ $item_id ]['quantity']
						: $orig_qty;
				}
				$pos_line_items[] = [
					'id'       => $item_id,
					'quantity'=> $qty,
				];
			} else {
				$qty = $orig_qty;
			}
			if ( $qty > $orig_qty ) {
				return new WP_Error(
					'invalid_quantity',
					'Invalid quantity for item ID ' . $item_id . '. Max qty is ' . $orig_qty,
					[ 'status' => 400 ]
				);
			}
			if ( $qty === 0 ) {
				continue;
			}
			$stored_subtotal = (float) $item->get_total();
			$stored_tax      = (float) $item->get_total_tax();
			$stored_mod      = (float) $item->get_meta( '_extra_modifier_amount', true );

			if ( $orig_qty > 0 && $qty !== $orig_qty ) {

				$per_unit_sub = $stored_subtotal / max( 1, $orig_qty );
				$per_unit_tax = $stored_tax / max( 1, $orig_qty );
				$per_unit_mod = $stored_mod / max( 1, $orig_qty );

				$line_subtotal = $per_unit_sub * $qty;
				$line_tax      = $per_unit_tax * $qty;
				$line_mod      = $per_unit_mod * $qty;

			} else {
				$line_subtotal = $stored_subtotal;
				$line_tax      = $stored_tax;
				$line_mod      = $stored_mod;
			}
			$line_total = $line_subtotal + $line_tax + $line_mod;
			$kot_total += $line_total;
		}
		return [
			'restaurant_id' => $order_restaurant_id,
			'zone_id'       => $order_zone_id,
			'kot_id'        => $order_id,
			'kot_number'   => $order->get_meta('_kot_number', true),
			'new_kot_total'=> wc_format_decimal($kot_total, 2),
			'payload'      => [
				'flag_type'    => 'void_items_update',
				'restaurant_id'=> $order_restaurant_id,
				'zone_id'      => $order_zone_id,
				'line_items'  => $pos_line_items,
				'remarks'      => $params['remarks'] ?? '',
			],
		];
	}
	public function pinaka_repeat_kot_order($request)
	{
		$params = $request->get_json_params();
		$existing_order_id = isset( $params['order_id'] ) ? absint( $params['order_id'] ) : 0;
		if ( ! $existing_order_id ) {
			return new WP_Error( 'missing_id', 'Missing order ID', [ 'status' => 400 ] );
		}
		
		$existing_order = wc_get_order( $existing_order_id );
		if ( ! $existing_order ) {
			return new WP_Error( 'invalid_order', 'Invalid order ID', [ 'status' => 404 ] );
		}
		if($existing_order->get_parent_id() !== 0)
		{
			return new WP_Error( 'invalid_order', 'Please enter Valid Parent Order ID.', [ 'status' => 400 ] );
		}
		if ( 'cancelled' === $existing_order->get_status() ) {
			$error_message = 'Cannot repeat KOT order because the parent order is cancelled.';
			return new WP_Error( 'parent_cancelled', $error_message, [ 'status' => 403 ] );
		}
		if ($existing_order->get_status() === 'completed') 
		{
			return new WP_Error('parent_completed', 'Cannot repeat KOT. Parent order is already completed.', ['status' => 400]);
		}
		$order_restaurant_id = (int) $existing_order->get_meta( '_restaurant_id' );
		$order_zone_id       = (int) $existing_order->get_meta( '_zone_id' );
		
		$req_restaurant_id = isset( $params['restaurant_id'] ) ? (int) $params['restaurant_id'] : 0;
		$req_zone_id       = isset( $params['zone_id'] ) ? (int) $params['zone_id'] : 0;

		if ( $order_restaurant_id !== $req_restaurant_id || $order_zone_id !== $req_zone_id ) {
			return new WP_Error(
				'mismatched_ids',
				__( 'Restaurant or Zone ID does not match with order Restaurant or Zone ID.', 'pinaka-pos' ),
				[ 'status' => 403 ]
			);
		}
		$parent_id = absint( $params['order_id'] );
		global $wpdb;

		$use_hpos = false;
		if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) &&
			method_exists( '\Automattic\WooCommerce\Utilities\OrderUtil', 'custom_orders_table_usage_is_enabled' ) ) {
			$use_hpos = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
		}

		$orders_table = $use_hpos ? $wpdb->prefix . 'wc_orders' : $wpdb->prefix . 'posts';
		$id_field     = $use_hpos ? 'id' : 'ID';
		$parent_field = $use_hpos ? 'parent_order_id' : 'post_parent';
		$status_field = $use_hpos ? 'status' : 'post_status';
		$max_kot_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT MAX($id_field) 
			FROM $orders_table 
			WHERE $parent_field = %d 
			AND ( $status_field NOT IN ('trash') )",
			$parent_id
		));

		$repeat_order_id = $max_kot_id ? absint( $max_kot_id ) : 0;
		if ( ! $repeat_order_id ) {
			return new WP_Error('no_kots', 'Cannot repeat KOT. Parent order has no kots.', ['status' => 400]);
		}
		$original_order = wc_get_order( $repeat_order_id );
		if ( ! $original_order ) {
			return;
		}
		$captain_id = $original_order->get_customer_id();
		$line_items = [];
		foreach ($original_order->get_items() as $item_id => $item) 
		{
			$variation_id = (int) $item->get_variation_id();
			$product_id   = (int) $item->get_product_id();

			$item_key = $variation_id ?: $product_id;
			$product = wc_get_product( $item_key );
			if (!$product) continue;

			$product_id   = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
			$quantity     = $item->get_quantity();
			$meta_data = [];
			foreach ($item->get_meta_data() as $meta) 
			{
				$key   = $meta->key;
				$value = $meta->value;

				if (in_array($key, ['_addons','_modifiers'])) {
					$meta_data[] = [
						'key'   => $key,
						'value' => $value,
					];
				} elseif (in_array($key, ['_modifier_notes','_extra_modifier_amount'])) {
					$meta_data[] = [
						'key'   => $key,
						'value' => $value,
					];
				}
			}
			$active_unit_price = wc_format_decimal(
				wc_get_price_to_display( $product )
			);
			$item = [
				'product_id' => $product_id,
				'product_name' => $product->get_name(),
				'product_price' => $active_unit_price,
				'quantity'   => $quantity,
			];
			
			if ( ! empty( $meta_data ) ) {
				$item['meta_data'] = $meta_data;
			}

			$line_items[] = $item;
		}
		$response = [
			'flag_type'      => 'kot_order',
			'parent_order_id'=> $existing_order_id,
			"restaurant_id"  => $order_restaurant_id,
    		"zone_id"        => $order_zone_id,
			"captain_id"     => $captain_id,
			'line_items'     => $line_items,
		];
		return rest_ensure_response( $response );
	}
	
	// public function get_all_order_items( $request ) 
	// {
	// 	$params = $request->get_params();
	// 	$parent_order_id = intval( $params['order_id'] ?? 0 );
	// 	$parent_order    = wc_get_order( $parent_order_id );

	// 	if ( ! $parent_order ) 
	// 	{
	// 		return new WP_Error( 'not_found', 'Parent order not found', [ 'status' => 404 ] );
	// 	}
	// 	$status = $parent_order->get_status();
    //     if ( in_array( $status, ['trash'], true ) ) {
    //         return new WP_REST_Response(
    //             [ 'error' => "Parent order is in {$status}" ],
    //             410
    //         );
    //     }
    //     if ( $parent_order->get_parent_id() !== 0 ) {
    //         return new WP_REST_Response(
    //             [ 'error' => 'Only parent orders are allowed.' ],
    //             403
    //         );
    //     }
	// 	$order_restaurant_id = (int) $parent_order->get_meta( '_restaurant_id' );
    //     $req_restaurant_id = isset( $params['restaurant_id'] ) ? (int) $params['restaurant_id'] : 0;

    //     if($params['order_type'] === 'Dine In')
    //     {
    //         $order_zone_id     = (int) $parent_order->get_meta( '_zone_id' );
    //         $req_zone_id       = isset( $params['zone_id'] ) ? (int) $params['zone_id'] : 0;
    //         if ( $order_restaurant_id !== $req_restaurant_id || $order_zone_id !== $req_zone_id ) {
    //             return new WP_Error(
    //                 'mismatched_ids',
    //                 __( 'Restaurant or Zone ID does not match with order Restaurant or Zone ID.', 'pinaka-pos' ),
    //                 [ 'status' => 403 ]
    //             );
    //         }
    //     }
    //     else
    //     {
    //         if ( $order_restaurant_id !== $req_restaurant_id) {
    //             return new WP_Error(
    //                 'mismatched_ids',
    //                 __( 'Restaurant ID does not match with order Restaurant ID.', 'pinaka-pos' ),
    //                 [ 'status' => 403 ]
    //             );
    //         }
    //     }
	// 	$kot_orders   = $this->get_kot_child_orders_by_parent_id( $parent_order_id );
	// 	$items_merged = [
	// 		'line_items' => [],
	// 		'fees'       => [],
	// 		'coupons'    => []
	// 	];
	// 	$grand_total  = 0;
	// 	$tax_total    = 0;
	// 	$fee_total    = 0;
	// 	$fee_tax_total = 0;
	// 	$coupon_total = 0;

	// 	// foreach ( $kot_orders as $kot ) {
	// 	// 	$global_modifiers = [];
	// 	// 	$modifier_amount  = 0;
	// 	// 	foreach ( $kot->get_items() as $item ) {

	// 	// 		$variation_id = (int) $item->get_variation_id();
	// 	// 		$product_id   = (int) $item->get_product_id();

	// 	// 		$item_key = $variation_id ?: $product_id;
    //     //         $product = wc_get_product( $item_key );
    //     //         if ( ! $product ) {
    //     //             continue;
    //     //         }
	// 	// 		$qty        = (float) $item->get_quantity();
	// 	// 		$line_total = (float) $item->get_total();
	// 	// 		$line_tax   = (float) $item->get_total_tax();
	// 	// 		$tax_class = $item->get_tax_class();
	// 	// 		$meta_data = $item->get_meta_data();
    //     //         foreach ( $meta_data as $meta ) 
    //     //         {
    //     //             $meta_key   = $meta->key;
    //     //             $meta_value = $meta->value;
    //     //             if ( in_array( $meta_key, [ '_addons', '_modifiers' ] ) && is_array( $meta_value ) ) 
    //     //             {
    //     //                 foreach ( $meta_value as $mod ) 
    //     //                 {
    //     //                     if ( ! empty( $mod['id'] ) ) 
    //     //                     {
    //     //                         $mod_post = get_post( $mod['id'] );
    //     //                         if ( $mod_post && $mod_post->post_type === 'modifier' ) 
    //     //                         {
    //     //                             $global_modifiers[] = $mod_post->post_title;
    //     //                         }
    //     //                     }
    //     //                     else
    //     //                     {
    //     //                         $global_modifiers[] = $mod['name'];
    //     //                     }
    //     //                 }
    //     //             }
	// 	// 			if ($meta_key === '_extra_modifier_amount') 
	// 	// 			{
	// 	// 				$modifier_amount += floatval($meta_value);
	// 	// 			}
    //     //         }
    //     //         $combo_items = [];
    //     //         if ( $product && $product->is_type( 'combo_bundle' ) ) 
    //     //         {
    //     //             $combo_meta = get_post_meta( $product->get_id(), '_combo_bundle_items', true );
    //     //             $combo_meta = json_decode( $combo_meta, true );

    //     //             if ( ! empty( $combo_meta ) ) {
    //     //                 foreach ( $combo_meta as $combo_item ) {
    //     //                     $child_product = wc_get_product( $combo_item['id'] );
    //     //                     if ( $child_product ) {
    //     //                         $combo_items[] = [
    //     //                             'id'       => $child_product->get_id(),
    //     //                             'name'     => $child_product->get_name(),
    //     //                             'price'    => wc_get_price_to_display( $child_product ),
    //     //                             'quantity' => intval( $combo_item['qty'] ),
    //     //                         ];
    //     //                     }
    //     //                 }
    //     //             }
    //     //         }
	// 	// 		if ( ! isset( $items_merged['line_items'][ $item_key ] ) ) {
	// 	// 			$items_merged['line_items'][ $item_key ] = [
	// 	// 				'product_id'   => $product_id,
	// 	// 				'variation_id' => $variation_id,
	// 	// 				'name'         => $item->get_name(),
	// 	// 				'qty'          => 0,
	// 	// 				'total'        => 0,
	// 	// 				'tax'          => 0,
	// 	// 				'tax_class'      => $tax_class,
	// 	// 				'modifiers'    => $global_modifiers,
	// 	// 				'modifier_amount' => $modifier_amount,
						
	// 	// 				'combo_items' => $combo_items
	// 	// 			];
	// 	// 		}
	// 	// 		$line_total = $line_total + $modifier_amount;
	// 	// 		$items_merged['line_items'][ $item_key ]['qty']   += $qty;
	// 	// 		$items_merged['line_items'][ $item_key ]['total'] += $line_total;
	// 	// 		$items_merged['line_items'][ $item_key ]['tax']   += $line_tax;

	// 	// 		$grand_total += $line_total;
	// 	// 		$tax_total   += $line_tax;
	// 	// 	}
	// 	// }
	// 	foreach ( $kot_orders as $kot ) {

	// 		foreach ( $kot->get_items() as $item ) {

	// 			/* ---------- RESET PER ITEM ---------- */
	// 			$global_modifiers = [];
	// 			$modifier_amount = 0;

	// 			$variation_id = (int) $item->get_variation_id();
	// 			$product_id   = (int) $item->get_product_id();

	// 			// MERGE KEY = PRODUCT ONLY
	// 			$item_key = $variation_id ?: $product_id;

	// 			$product = wc_get_product( $item_key );
	// 			if ( ! $product ) {
	// 				continue;
	// 			}

	// 			$qty        = (float) $item->get_quantity();
	// 			$line_total = (float) $item->get_total();      // after coupons, excl tax
	// 			$line_tax   = (float) $item->get_total_tax(); // tax amount
	// 			$tax_class  = $item->get_tax_class();

	// 			/* ---------- READ META ---------- */
	// 			foreach ( $item->get_meta_data() as $meta ) {

	// 				$meta_key   = $meta->key;
	// 				$meta_value = $meta->value;

	// 				// Collect modifiers
	// 				if ( in_array( $meta_key, [ '_addons', '_modifiers' ], true ) && is_array( $meta_value ) ) {
	// 					foreach ( $meta_value as $mod ) {
	// 						if ( ! empty( $mod['id'] ) ) {
	// 							$mod_post = get_post( $mod['id'] );
	// 							if ( $mod_post && $mod_post->post_type === 'modifier' ) {
	// 								$global_modifiers[] = $mod_post->post_title;
	// 							}
	// 						} elseif ( ! empty( $mod['name'] ) ) {
	// 							$global_modifiers[] = $mod['name'];
	// 						}
	// 					}
	// 				}

	// 				// Modifier price
	// 				if ( $meta_key === '_extra_modifier_amount' ) {
	// 					$modifier_amount += (float) $meta_value;
	// 				}
	// 			}

	// 			/* ---------- COMBO ITEMS ---------- */
	// 			$combo_items = [];
	// 			if ( $product->is_type( 'combo_bundle' ) ) {
	// 				$combo_meta = json_decode(
	// 					get_post_meta( $product->get_id(), '_combo_bundle_items', true ),
	// 					true
	// 				);

	// 				if ( is_array( $combo_meta ) ) {
	// 					foreach ( $combo_meta as $combo_item ) {
	// 						$child_product = wc_get_product( $combo_item['id'] );
	// 						if ( $child_product ) {
	// 							$combo_items[] = [
	// 								'id'       => $child_product->get_id(),
	// 								'name'     => $child_product->get_name(),
	// 								'price'    => wc_get_price_to_display( $child_product ),
	// 								'quantity' => (int) $combo_item['qty'],
	// 							];
	// 						}
	// 					}
	// 				}
	// 			}

	// 			/* ---------- INIT / MERGE ---------- */
	// 			if ( ! isset( $items_merged['line_items'][ $item_key ] ) ) {
	// 				$items_merged['line_items'][ $item_key ] = [
	// 					'product_id'       => $product_id,
	// 					'variation_id'    => $variation_id,
	// 					'name'            => $item->get_name(),
	// 					'qty'             => 0,
	// 					'total'           => 0,
	// 					'tax'             => 0,
	// 					'tax_class'      => $tax_class,
	// 					'modifiers'      => [],
	// 					'modifier_amount'=> 0,
	// 					'combo_items'    => $combo_items,
	// 				];
	// 			}

	// 			/* ---------- TOTALS ---------- */
	// 			$line_total += $modifier_amount;

	// 			$items_merged['line_items'][ $item_key ]['qty'] += $qty;
	// 			$items_merged['line_items'][ $item_key ]['total'] += $line_total;
	// 			$items_merged['line_items'][ $item_key ]['tax'] += $line_tax;
	// 			$items_merged['line_items'][ $item_key ]['modifier_amount'] += $modifier_amount;

	// 			// MERGE MODIFIER NAMES (no duplicates)
	// 			$items_merged['line_items'][ $item_key ]['modifiers'] = array_values(
	// 				array_unique(
	// 					array_merge(
	// 						$items_merged['line_items'][ $item_key ]['modifiers'],
	// 						$global_modifiers
	// 					)
	// 				)
	// 			);

	// 			$grand_total += $line_total;
	// 			$tax_total   += $line_tax;
	// 		}
	// 	}

	// 	foreach ( $parent_order->get_items( 'fee' ) as $fee ) {
	// 		$fee_amount = $fee->get_total();
	// 		$fee_tax    = $fee->get_total_tax();

	// 		$fee_total    += $fee_amount;
	// 		$fee_tax_total += $fee_tax;

	// 		// Add fee details to response
	// 		$items_merged['fees'][] = [
	// 			'id' 	=> $fee->get_id(),
	// 			'name'  => $fee->get_name(),
	// 			'total' => $fee_amount,
	// 			'tax'   => $fee_tax
	// 		];
	// 	}
	// 	foreach ( $parent_order->get_items( 'coupon' ) as $coupon ) 
	// 	{
	// 		$coupon_code = $coupon->get_code();
	// 		$wc_coupon = new WC_Coupon( $coupon_code );
	// 		$discount_type = $wc_coupon->get_discount_type();
	// 		$coupon_amount = 0;

	// 		if ( $discount_type === 'fixed_cart' ) {
	// 			$coupon_amount = $wc_coupon->get_amount();
	// 		} elseif ( $discount_type === 'percent' ) {
	// 			$discount_percentage = $wc_coupon->get_amount();
	// 			$coupon_amount = ( $discount_percentage / 100 ) * $grand_total;
	// 		} elseif ( $discount_type === 'fixed_product' ) {
		
	// 			$product_ids = $wc_coupon->get_product_ids();
	// 			$coupon_amount_per_item = $wc_coupon->get_amount();
				
	// 			foreach ( $kot_orders as $kot ) {
	// 				foreach ( $kot->get_items() as $item ) {
	// 					$product_id = $item->get_product_id();
						
	// 					if ( empty( $product_ids ) || in_array( $product_id, $product_ids ) ) {
	// 						$coupon_amount += $coupon_amount_per_item * $item->get_quantity();
	// 					}
	// 				}
	// 			}
	// 		}

	// 		$coupon_tax = $coupon->get_discount_tax();

	// 		$coupon_total += $coupon_amount;
	// 		$tax_total    -= $coupon_tax;
	// 		$items_merged['coupons'][] = [
	// 			'code'         => $coupon_code,
	// 			'value'        => $coupon_amount,
	// 			'tax'          => $coupon_tax,
	// 			'discount_type' => $discount_type
	// 		];
	// 	}
	// 	$net_total   = $grand_total + $tax_total + $fee_total + $fee_tax_total;
	// 	$final_total = max( 0, $net_total - $coupon_total );

	// 	if ($fee_total < 0) {
    //         $fee_label = 'Discount';
    //     } else {
    //         $fee_label = 'Fees';
    //     }
	// 	$tip_amount = $parent_order->meta_exists( '_order_tip' )
	// 		? (float) $parent_order->get_meta( '_order_tip' )
	// 		: 0.0;

	// 	$merchant_discount = $parent_order->meta_exists( '_order_discount' ) ? (float) $parent_order->get_meta( '_order_discount' ) : 0.0;
	// 	if ( $merchant_discount ) {
	// 		$final_total = $final_total + (float) $merchant_discount;
	// 	}

	// 	if ( $tip_amount > 0 ) {
	// 		$final_total += $tip_amount;
	// 	}
	// 	$parent_order->set_total( $final_total );
	// 	$parent_order->save();
	// 	$response = [
	// 		'restaurant_id' => intval( $parent_order->get_meta( '_restaurant_id' ) ),
	// 		'order_id'      => $parent_order_id,
	// 		'gross_total'   => round($grand_total,2),
	// 		'tax'           => round($tax_total + $fee_tax_total,2),
	// 		$fee_label          => round($fee_total,2),
	// 		'coupons'       => round($coupon_total,2),
	// 		'merchant_discount' => $merchant_discount,
	// 		'tip'                => round( $tip_amount, 2 ),
	// 		'net_total'     => round($final_total,2),
	// 		'line_items'    => array_values( $items_merged['line_items'] ),
	// 		'fee_details'   => $items_merged['fees'],
	// 		'coupon_details'=> $items_merged['coupons'],
	// 	];

	// 	if ( $parent_order->get_created_via() === 'rest-api' ) {
	// 		$response['table_id'] = intval( $parent_order->get_meta( '_table_id' ) );
	// 		$response['zone_id'] = intval( $parent_order->get_meta( '_zone_id' ) );
	// 	}
		
	// 	return rest_ensure_response( $response );
	// }
	public function get_all_order_items( $request ) 
	{
		$params = $request->get_params();
		$parent_order_id = intval( $params['order_id'] ?? 0 );
		$parent_order    = wc_get_order( $parent_order_id );
		$is_takeaway_order = ($parent_order->get_created_via() === 'takeaway');

		if ( ! $parent_order ) 
		{
			return new WP_Error( 'not_found', 'Parent order not found', [ 'status' => 404 ] );
		}
		$status = $parent_order->get_status();
        if ( in_array( $status, ['trash'], true ) ) {
            return new WP_REST_Response(
                [ 'error' => "Parent order is in {$status}" ],
                410
            );
        }
        if ( $parent_order->get_parent_id() !== 0 ) {
            return new WP_REST_Response(
                [ 'error' => 'Only parent orders are allowed.' ],
                403
            );
        }
		$order_restaurant_id = (int) $parent_order->get_meta( '_restaurant_id' );
        $req_restaurant_id = isset( $params['restaurant_id'] ) ? (int) $params['restaurant_id'] : 0;

        if($params['order_type'] === 'Dine In')
        {
            $order_zone_id     = (int) $parent_order->get_meta( '_zone_id' );
            $req_zone_id       = isset( $params['zone_id'] ) ? (int) $params['zone_id'] : 0;
            if ( $order_restaurant_id !== $req_restaurant_id || $order_zone_id !== $req_zone_id ) {
                return new WP_Error(
                    'mismatched_ids',
                    __( 'Restaurant or Zone ID does not match with order Restaurant or Zone ID.', 'pinaka-pos' ),
                    [ 'status' => 403 ]
                );
            }
        }
        else
        {
            if ( $order_restaurant_id !== $req_restaurant_id) {
                return new WP_Error(
                    'mismatched_ids',
                    __( 'Restaurant ID does not match with order Restaurant ID.', 'pinaka-pos' ),
                    [ 'status' => 403 ]
                );
            }
        }
		// $settings_tax_s = get_option('pinaka_restaurant_tax_settings', []);
		// $restaurant_ids_s = absint($order_restaurant_id ?? 0);
		// $restaurant_taxable_s = $settings_tax_s[$restaurant_ids_s] ?? 'no';
		$kot_orders   = $this->get_kot_child_orders_by_parent_id_without_cancel( $parent_order_id );
		$items_merged = [
			'line_items' => [],
			'fees'       => [],
			'coupons'    => []
		];
		$grand_total  = 0;
		$tax_total    = 0;
		$fee_total    = 0;
		$fee_tax_total = 0;
		$coupon_total = 0;
		$coupon_tax_total = 0;
		$modifier_tax_total = 0;
	
		foreach ( $kot_orders as $kot ) {

			foreach ( $kot->get_items() as $item_id => $item ) {
                if ( 'cancelled' === wc_get_order_item_meta( $item_id, '_item_status', true ) ) {
                    continue;
                }
				/* ---------- RESET PER ITEM ---------- */
				$global_modifiers = [];
				$modifier_amount = 0;

				$variation_id = (int) $item->get_variation_id();
				$product_id   = (int) $item->get_product_id();

				// MERGE KEY = PRODUCT ONLY
				$item_key = $variation_id ?: $product_id;

				$product = wc_get_product( $item_key );
				if ( ! $product ) {
					continue;
				}

				$qty        = (float) $item->get_quantity();
				// $line_total = (float) $item->get_total();      // after coupons, excl tax
				// $line_tax   = (float) $item->get_total_tax(); // tax amount
				$line_total = (float) $item->get_subtotal(); // RAW price
				$line_new_total = $qty > 0 ? $line_total / $qty : 0;
        		$line_tax   = (float) $item->get_subtotal_tax();
				$tax_class  = $item->get_tax_class();
				
				/* ---------- READ META ---------- */
				foreach ( $item->get_meta_data() as $meta ) {

					$meta_key   = $meta->key;
					$meta_value = $meta->value;

					// Collect modifiers
					if ( in_array( $meta_key, [ '_addons', '_modifiers' ], true ) && is_array( $meta_value ) ) {
						foreach ( $meta_value as $mod ) {
							if ( ! empty( $mod['id'] ) ) {
								$mod_post = get_post( $mod['id'] );
								if ( $mod_post && $mod_post->post_type === 'modifier' ) {
									$global_modifiers[] = $mod_post->post_title;
								}
							} elseif ( ! empty( $mod['name'] ) ) {
								$global_modifiers[] = $mod['name'];
							}
						}
					}

					// Modifier price
					if ( $meta_key === '_extra_modifier_amount' ) {
						$modifier_amount += (float) $meta_value;
					}
				}

				/* ---------- COMBO ITEMS ---------- */
				$combo_items = [];
				if ( $product->is_type( 'combo_bundle' ) ) {
					$combo_meta = json_decode(
						get_post_meta( $product->get_id(), '_combo_bundle_items', true ),
						true
					);

					if ( is_array( $combo_meta ) ) {
						foreach ( $combo_meta as $combo_item ) {
							$child_product = wc_get_product( $combo_item['id'] );
							if ( $child_product ) {
								$combo_items[] = [
									'id'       => $child_product->get_id(),
									'name'     => $child_product->get_name(),
									'price'    => wc_get_price_to_display( $child_product ),
									'quantity' => (int) $combo_item['qty'],
								];
							}
						}
					}
				}
                $product = $item->get_product();

                $item_price = $product
                    ? (float) $product->get_price()
                    : 0;
				/* ---------- INIT / MERGE ---------- */
				if ( ! isset( $items_merged['line_items'][ $item_key ] ) ) {
					$items_merged['line_items'][ $item_key ] = [
						'product_id'       => $product_id,
						'variation_id'    => $variation_id,
						'name'            => $item->get_name(),
						'qty'             => 0,
						'total'           => 0,
						'price'           => 0,
						'original_price'  => $item_price,
						'tax'             => 0,
						'tax_class'      => $tax_class,
						'modifiers'      => [],
						'modifier_amount'=> 0,
						'combo_items'    => $combo_items,
					];
				}

				/* ---------- TOTALS ---------- */
				$line_total += $modifier_amount;
				// if($restaurant_taxable_s === 'yes')
				// {
				// 	$line_new_total += $modifier_amount;
				// }

				$items_merged['line_items'][ $item_key ]['qty'] += $qty;
				$items_merged['line_items'][ $item_key ]['total'] += $line_total;
				$items_merged['line_items'][ $item_key ]['price'] += $line_new_total;
				$items_merged['line_items'][ $item_key ]['tax'] += $line_tax;
				$items_merged['line_items'][ $item_key ]['modifier_amount'] += $modifier_amount;

				// MERGE MODIFIER NAMES (no duplicates)
				$items_merged['line_items'][ $item_key ]['modifiers'] = array_values(
					array_unique(
						array_merge(
							$items_merged['line_items'][ $item_key ]['modifiers'],
							$global_modifiers
						)
					)
				);

				$grand_total += $line_total;
				$tax_total   += $line_tax;
			}
		}
		foreach ( $kot_orders as $kot ) {
			foreach ( $kot->get_items( 'fee' ) as $fee_item ) {
				$linked_line_item_id = absint( $fee_item->get_meta( '_linked_line_item_id', true ) );
				if ( ! $linked_line_item_id ) {
					continue;
				}
				$modifier_tax_total += (float) $fee_item->get_total_tax();
			}
		}
		if($modifier_tax_total > 0)
		{
			$restaurant_taxable_s = 'yes';
		}
		else
		{
			$restaurant_taxable_s = 'no';
		}
		$tax_total += $modifier_tax_total;
		foreach ( $parent_order->get_items( 'fee' ) as $fee ) {
			$fee_amount = $fee->get_total();
			$fee_tax    = $fee->get_total_tax();

			$fee_total    += $fee_amount;
			$fee_tax_total += $fee_tax;

			// Add fee details to response
			$items_merged['fees'][] = [
				'id' 	=> $fee->get_id(),
				'name'  => $fee->get_name(),
				'total' => $fee_amount,
				'tax'   => $fee_tax
			];
		}
		update_post_meta(
            $parent_order->get_id(),
            '_service_grand_total',
            $grand_total
        );
		// Service Charge
        $service_charge_percentage = 0;
        $service_charge_value = 0;
        
        // Always remove existing Service Charge fee
        foreach ($parent_order->get_items('fee') as $fee_item_id => $fee_item) {
            if (strpos($fee_item->get_name(), 'Service Charge') !== false) {
                $parent_order->remove_item($fee_item_id);
            }
        }
        
        if (!$is_takeaway_order) {
        
            $service_charge_percentage = $parent_order->get_meta(
                '_service_charge_percentage',
                true
            );
        
            if ($service_charge_percentage === '') {
                $service_charge_percentage = 5;
            }
        
            $service_charge_percentage = (float) $service_charge_percentage;
        
            $service_charge_value = round(
                ($grand_total * $service_charge_percentage) / 100,
                2
            );
        
            if ($service_charge_value > 0) {
        
                $fee = new WC_Order_Item_Fee();
                $fee->set_name('Service Charge - ' . $service_charge_percentage . '%');
                $fee->set_amount($service_charge_value);
                $fee->set_total($service_charge_value);
                $fee->set_tax_status('none');
                $fee->set_tax_class('');
                $fee->set_taxes([
                    'total' => [],
                    'subtotal' => []
                ]);
        
                $parent_order->add_item($fee);
            }
        }
        
        $parent_order->calculate_totals(true);
        $parent_order->save();
        
        update_post_meta(
            $parent_order->get_id(),
            '_service_charge_value',
            $service_charge_value
        );

		$coupon_total = 0;
		$coupon_tax_total = 0;

		foreach ( $parent_order->get_items( 'coupon' ) as $coupon ) {

			// Coupon code
			$coupon_code = $coupon->get_code();

			// Totals calculated by WooCommerce (safe for all coupon types & plugins)
			$discount_amount = (float) $coupon->get_discount();
			$discount_tax    = (float) $coupon->get_discount_tax();

			// Optional: Get coupon type for UI/debugging
			$wc_coupon = new WC_Coupon( $coupon_code );
			$discount_type = $wc_coupon->get_discount_type();

			// Accumulate totals
			$coupon_total     += $discount_amount;
			$coupon_tax_total -= $discount_tax;

			// Store structured output for POS / API
			$items_merged['coupons'][] = [
				'code'          => $coupon_code,
				'value'         => $discount_amount,
				'tax'           => $discount_tax,
				'discount_type'=> $discount_type
			];
		}

		// foreach ( $parent_order->get_items('coupon') as $coupon ) {
		// 	$coupon_total += (float) $coupon->get_discount();
		// 	$coupon_tax_total += (float) $coupon->get_discount_tax();
		// }
		// $net_total   = $grand_total + $tax_total + $fee_total + $fee_tax_total;
		$net_total   = round($grand_total,2);
		$net_total = round($net_total - $coupon_total,2);
		$taxs = round(($tax_total + $fee_tax_total) - $coupon_tax_total,2);
		$final_total = round($net_total + $taxs,2);
        update_post_meta(
            $parent_order->get_id(),
            '_order_pre_net_total',
            $final_total
        );
		$tip_amount = $parent_order->meta_exists( '_order_tip' )
			? (float) $parent_order->get_meta( '_order_tip' )
			: 0.0;

		$merchant_discount = $parent_order->meta_exists( '_order_discount' ) ? (float) $parent_order->get_meta( '_order_discount' ) : 0.0;
		
		// echo $merchant_discount;
		if ( $merchant_discount ) {
			$final_total = round($final_total + (float) $merchant_discount,2);
		}

		// Add service charge and tip first
        $net_payable = round(
            $final_total +
            ($is_takeaway_order ? 0 : $service_charge_value) +
            $tip_amount,
            2
        );
        
        // $net_payable = round(
        //     $final_total + $service_charge_value + $tip_amount,
        //     2
        // );
        $service_charge_val = get_post_meta(
            $parent_order->get_id(),
            '_service_charge_value',
            true
        );
        $meta_net_payable = $parent_order->get_total() + $service_charge_val;

        // Calculate round off on final payable
        $rounded_payable = round($net_payable, 0);
        $round_off = round($rounded_payable - $net_payable, 2);
        
        // Final payable after round off
        $net_payable = round($net_payable + $round_off, 2);
        
        // Ensure net payable is not negative
        if ($net_payable < 0) {
            $net_payable = 0;
        }
        
        //Save net payable
        update_post_meta(
            $parent_order->get_id(),
            '_net_payable',
            (float) $meta_net_payable
        );
		$is_nc_order = $parent_order->get_meta('_is_nc_order', true) ?: 'no';

		$response = [
			'restaurant_id' => intval( $parent_order->get_meta( '_restaurant_id' ) ),
			'table_name'    => get_the_title(intval($parent_order->get_meta( '_table_id' ))),
			'order_id'      => $parent_order_id,
			'gross_total'   => round($grand_total,2),
			'service_charge_percentage' => ($is_nc_order === 'yes' || $is_takeaway_order)
                ? 0
                : $service_charge_percentage,
            
            'service_charge_value' => ($is_nc_order === 'yes' || $is_takeaway_order)
                ? 0
                : $service_charge_value,
			'coupon_total'  => $coupon_total,
			'modifiers_taxable' => $restaurant_taxable_s,
			'tax'           => $taxs,
			'coupons'       => round($coupon_total,2),
			'is_nc'   => $is_nc_order === 'yes' ? 'yes' : 'no', 
			'merchant_discount' => $is_nc_order === 'yes'
                ? round( $net_payable - $service_charge_value - $tip_amount, 2 )
                : $merchant_discount,
			'round_off'     => $round_off,
			'net_total' => $is_nc_order === 'yes'
                ? round($tip_amount, 2)
                : $net_payable,
			'tip'                => round( $tip_amount, 2 ),
			'line_items'    => array_values( $items_merged['line_items'] ),
			'fee_details'   => $items_merged['fees'],
			'coupon_details'=> $items_merged['coupons'],
		];

		if ( $parent_order->get_created_via() === 'rest-api' ) {
			$response['table_id'] = intval( $parent_order->get_meta( '_table_id' ) );
			$response['zone_id'] = intval( $parent_order->get_meta( '_zone_id' ) );
		}
		$reservation_id = (int) $parent_order->get_meta('_reservation_id' ,true);
		if($reservation_id)
		{
		    update_post_meta($reservation_id, '_table_status', 'Ready to Pay');
            update_post_meta($reservation_id, '_reservation_status', 'Ready to Pay');
            $parent_order->update_meta_data( '_table_status', 'Available' );
            $parent_order->save();
		}
		return rest_ensure_response( $response );
	}
	
	public function update_service_charge( $request ) {

        $order_id = absint($request['order_id']);
        $service_charge_percentage = floatval(
            $request['service_charge_percentage']
        );
    
        if ($service_charge_percentage < 1 || $service_charge_percentage > 5) {
            return new WP_Error(
                'invalid_percentage',
                'Service charge must be between 1 and 5',
                ['status' => 400]
            );
        }
    
        $order = wc_get_order($order_id);
    
        if (!$order) {
            return new WP_Error(
                'invalid_order',
                'Order not found',
                ['status' => 404]
            );
        }
    
        $order->update_meta_data(
            '_service_charge_percentage',
            $service_charge_percentage
        );
        foreach ( $order->get_items( 'fee' ) as $fee_item_id => $fee_item ) {
            if ( strpos( $fee_item->get_name(), 'Service Charge' ) !== false ) {
                $order->remove_item( $fee_item_id );
            }
        }
        $subtotal = (float) get_post_meta($order->get_id(),'_service_grand_total',true) ?: 0;
        $service_charge_value = round(
            ( $subtotal * $service_charge_percentage ) / 100,
            2
        );
        $addons_total = floatval( $service_charge_value );
    	if ( $addons_total > 0 && !empty($service_charge_percentage)) {
    
    		$fee = new WC_Order_Item_Fee();
    		$fee->set_name( 'Service Charge - ' . $service_charge_percentage . '%' );
    		$fee->set_amount( $addons_total );
    		$fee->set_total( $addons_total );
    
    		$fee->set_tax_status( 'none' );
    		$fee->set_tax_class( '' );
    		$fee->set_taxes( [ 'total' => [], 'subtotal' => [] ] );
    
    		$order->add_item( $fee );
    		$order->calculate_totals( true );
    		$order->save();
    	}
        // $order->save();
    
        return rest_ensure_response([
            'success' => true,
            'order_id' => $order_id,
            'service_charge_percentage' => $service_charge_percentage
        ]);
    }

    public function delete_service_charge( $request ) {
    
        $order_id = absint($request['order_id']);
    
        $order = wc_get_order($order_id);
    
        if (!$order) {
            return new WP_Error(
                'invalid_order',
                'Order not found',
                ['status' => 404]
            );
        }
    
        $order->update_meta_data(
            '_service_charge_percentage',
            0
        );
        foreach ( $order->get_items( 'fee' ) as $item_id => $fee ) {

            if ( strpos( $fee->get_name(), 'Service Charge' ) !== false ) {
                $order->remove_item( $item_id );
            }
        }
    
        // Recalculate totals
        $order->calculate_totals( true );
        $order->save();
    
        return rest_ensure_response([
            'success' => true,
            'order_id' => $order_id,
            'service_charge_percentage' => 0
        ]);
    }
	/**
	 * Get total orders callback.
	 *
	 * @param WP_REST_Request $request .
	 * @return array|WP_Error
	 */
	function pinaka_get_total_orders(WP_REST_Request $request) {
		$params = $request->get_params();

		$page     = isset($params['page']) ? max(1, intval($params['page'])) : 1;
		$per_page = isset($params['per_page']) ? max(1, intval($params['per_page'])) : 10;

		$statuses = isset($params['status']) ? explode(',', str_replace(' ', '', $params['status'])) : ['any'];

		$query_args = [
			'post_type'           => 'shop_order',
			'post_status'         => [],
			'posts_per_page'      => $per_page,
			'paged'               => $page,
			'orderby'             => 'date ID',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
		];

		// Statuses
		foreach ($statuses as $status) {
			$query_args['post_status'][] = strpos($status, 'wc-') === 0 ? $status : 'wc-' . $status;
		}

		$author = sanitize_text_field($params['author']) ?: get_current_user_id();

		// Author filter
		$query_args['meta_query'][] = [
			'key'     => '_wc_order_created_by',
			'value'   => $author,
			'compare' => '=',
		];

		// Created via filter
		if (!empty($params['created_via'])) {
			$query_args['meta_query'][] = [
				'key'     => '_created_via',
				'value'   => sanitize_text_field($params['created_via']),
				'compare' => '='
			];
		}

		// Date query
		$date_query = [];
		$site_timezone = new DateTimeZone(wp_timezone_string());
		$utc_timezone  = new DateTimeZone('UTC');

		if (!empty($params['after']) && is_string($params['after'])) {
			try {
				if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $params['after'])) {
					$after = new DateTime($params['after'] . ' 00:00:00', $site_timezone);
				} else {
					$after = new DateTime($params['after'], $site_timezone);
				}
				// Convert to UTC
				$after->setTimezone($utc_timezone);

				$date_query[] = [
					'column'    => 'post_date_gmt',
					'after'     => $after->format('Y-m-d H:i:s'),
					'inclusive' => true,
				];
			} catch (Exception $e) {}
		}

		if (!empty($params['before']) && is_string($params['before'])) {
			try {
				if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $params['before'])) {
					$before = new DateTime($params['before'] . ' 23:59:59', $site_timezone);
				} else {
					$before = new DateTime($params['before'], $site_timezone);
				}
				// Convert to UTC
				$before->setTimezone($utc_timezone);

				$date_query[] = [
					'column'    => 'post_date_gmt',
					'before'    => $before->format('Y-m-d H:i:s'),
					'inclusive' => true,
				];
			} catch (Exception $e) {}
		}

		if (!empty($date_query)) {
			$query_args['date_query'] = $date_query;
		}

		// Get total matching orders without pagination
		$total_query_args = $query_args;
		// echo json_encode($total_query_args);
		// die;
		$total_query_args['posts_per_page'] = -1;
		$total_query_args['fields'] = 'ids'; // Only get IDs for performance

		$total_query = new \WC_Order_Query($total_query_args);
		$total_orders = $total_query->get_orders();
		$total_count = count($total_orders);

		// Get paginated orders
		$query  = new \WC_Order_Query($query_args);
		$orders = $query->get_orders();

		// Setup controllers
		$api_product = new WC_REST_Products_Controller();
		$api_var     = new WC_REST_Product_Variations_Controller();

		$formatted_orders = [];

		foreach ($orders as $order) {
			if (!$order instanceof WC_Order) {
				continue;
			}

			$order_id = $order->get_id();

			// Process line items
			$line_items = [];
			foreach ($order->get_items() as $item) {
				$product = wc_get_product($item->get_product_id());
				$image_id = $product ? $product->get_image_id() : null;
				$image_src = $image_id ? wp_get_attachment_url($image_id) : '';

				$line = [
					'id'           => $item->get_id(),
					'name'         => $item->get_name(),
					'product_id'   => $item->get_product_id(),
					'variation_id' => $item->get_variation_id(),
					'quantity'     => $item->get_quantity(),
					'tax_class'    => $item->get_tax_class(),
					'subtotal'     => $item->get_subtotal(),
					'subtotal_tax' => $item->get_subtotal_tax(),
					'total'        => $item->get_total(),
					'total_tax'    => $item->get_total_tax(),
					'taxes'        => $item->get_taxes(),
					'image'		   => [
						'id'  => $image_id,
						'src' => $image_src,
					],
					'meta_data'    => array_map(function ($meta) {
						return [
							'id'            => $meta->id,
							'key'           => $meta->key,
							'value'         => $meta->value,
							'display_key'   => $meta->key,
							'display_value' => $meta->value,
						];
					}, $item->get_meta_data()),
				];

				if (!empty($line['variation_id'])) {
					$req_var = new WP_REST_Request('GET');
					$req_var->set_query_params(['id' => $line['variation_id']]);
					$res_var = $api_var->get_item($req_var);
					$line['product_variation_data'] = is_wp_error($res_var) ? null : $res_var->get_data();
				}

				$req_prod = new WP_REST_Request('GET');
				$req_prod->set_query_params(['id' => $line['product_id']]);
				$res_prod = $api_product->get_item($req_prod);
				$line['product_data'] = is_wp_error($res_prod) ? null : $res_prod->get_data();

				$line_items[] = $line;
			}

			// Process fee lines
			$fee_lines = [];
			foreach ($order->get_fees() as $fee) {
				$taxes = [];
				foreach ($fee->get_taxes()['total'] ?? [] as $tax_id => $total_tax) {
					$taxes[] = [
						'id'       => (int) $tax_id,
						'total'    => wc_format_decimal($total_tax),
						'subtotal' => '',
					];
				}

				$fee_lines[] = [
					'id'         => $fee->get_id(),
					'name'       => $fee->get_name(),
					'tax_class'  => $fee->get_tax_class(),
					'tax_status' => $fee->get_tax_status(),
					'amount'     => '',
					'total'      => wc_format_decimal($fee->get_total()),
					'total_tax'  => wc_format_decimal($fee->get_total_tax()),
					'taxes'      => $taxes,
					'meta_data'  => array_map(function ($meta) {
						return [
							'id'            => $meta->id,
							'key'           => $meta->key,
							'value'         => $meta->value,
							'display_key'   => $meta->key,
							'display_value' => $meta->value,
						];
					}, $fee->get_meta_data()),
				];
			}

			$formatted_order = [
				'id'                   => $order_id,
				'status'               => $order->get_status(),
				"date_created" 		   => $order->get_date_created()->date('Y-m-d H:i:s'),
				"date_modified"		   => $order->get_date_modified()->date('Y-m-d H:i:s'),
				'currency'             => $order->get_currency(),
				'prices_include_tax'   => $order->get_prices_include_tax(),
				'discount_total'       => $order->get_discount_total(),
				'discount_tax'         => $order->get_discount_tax(),
				'shipping_total'       => $order->get_shipping_total(),
				'shipping_tax'         => $order->get_shipping_tax(),
				'cart_tax'             => $order->get_cart_tax(),
				'total'                => $order->get_total(),
				'total_tax'            => $order->get_total_tax(),
				'order_key'            => $order->get_order_key(),
				'payment_method'       => $order->get_payment_method(),
				'payment_method_title' => $order->get_payment_method_title(),
				'transaction_id'       => $order->get_transaction_id(),
				'created_via'          => $order->get_created_via(),
				'author'			   => $order->get_meta( '_wc_order_created_by' ),
				'currency'                 => esc_attr( get_woocommerce_currency() ),
				'currency_symbol'          => get_option("currency_symbol"), // e.g., '₹'
				'date_completed'       => $order->get_date_completed() ? $order->get_date_completed()->date('c') : null,
				'date_paid'            => $order->get_date_paid() ? $order->get_date_paid()->date('c') : null,
				'number'               => $order->get_order_number(),
				'coupon_lines' => array_values(array_map(function ($item) {
					$coupon_code = $item->get_code();
					$coupon      = new WC_Coupon($coupon_code);

					return [
						'id'            => $item->get_id(),
						'code'          => $coupon_code,
						'discount'      => wc_format_decimal($item->get_discount(), 2),
						'discount_tax'  => wc_format_decimal($item->get_discount_tax(), 2),
						'meta_data'     => array_map(function ($meta) {
							return [
								'id'            => $meta->id,
								'key'           => $meta->key,
								'value'         => $meta->value,
								'display_key'   => $meta->key,
								'display_value' => $meta->value,
							];
						}, $item->get_meta_data()),
						'discount_type' => $coupon->get_discount_type(),
						'nominal_amount'=> floatval($item->get_discount()),
						'free_shipping' => (bool) $coupon->get_free_shipping(),
					];
				}, $order->get_items('coupon'))),
				'line_items'           => $line_items,
				'fee_lines'            => $fee_lines,
				'refunds'              => $order->get_refunds(),
			];

			if ($order_type = get_post_meta($order_id, '_order_type', true)) {
				$formatted_order['order_type'] = $order_type;
			}

			$formatted_orders[] = $formatted_order;
		}

		return new WP_REST_Response([
			'order_total_count' => $total_count,
			'page'              => $page,
			'per_page'          => $per_page,
			'orders_data'       => $formatted_orders,
		]);
	}
	function pinaka_get_discount_reasons()
	{
		$reasons_list = ['Corporate Discount','Premium Customer', 'Anniversary Discount', 'Special Discount'];
		return new WP_REST_Response( [
			'status'  => 'success',
			'message' => 'Discount Reasons list fetched successfully',
			'reason_list' => $reasons_list
		], 200 );
	}
	function pinaka_pos_add_discount( WP_REST_Request $request ) 
	{
		$params     = $request->get_json_params();
		$order_id   = isset( $params['order_id'] ) ? absint( $params['order_id'] ) : 0;
		$raw_amount = isset( $params['amount'] ) ? trim( $params['amount'] ) : '';
		$is_nc = isset($params['is_nc']) ? $params['is_nc'] : 'no';
		$discount_reason = isset($params['reason']) ? $params['reason'] : '';

		if ( ! $order_id) {
			return new WP_Error(
				'pinaka_invalid_request',
				__( 'Order ID is required.', 'pinaka-pos' ),
				[ 'status' => 400 ]
			);
		}
		if( $raw_amount === '' && $is_nc === 'no')
		{
			return new WP_Error(
				'pinaka_invalid_request',
				__( 'discount percentage (or) amount is required.', 'pinaka-pos' ),
				[ 'status' => 400 ]
			);
		}
		if($is_nc === 'yes' && $discount_reason === "")
		{
			return new WP_Error(
				'pinaka_invalid_request',
				__( 'Please mention the discount reason.', 'pinaka-pos' ),
				[ 'status' => 400 ]
			);
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) 
		{
			return new WP_REST_Response( [ 'error' => 'Order not found' ], 404 );
		}
		$children = $this->get_kot_child_orders_by_parent_id( $order_id );
		$children = array_filter( $children, function ( $child ) {
			return $child->get_meta( '_kot_number', true );
		} );

		if ( empty( $children )) {
			return new WP_Error(
				'pinaka_empty_order_no_discount_only',
				__( 'Merchant discount cannot be applied to an empty cart.', 'pinaka-pos' ),
				[ 'status' => 400 ]
			);
		}

		$order_total     = (float) $order->get_total();
        $order_total     = (float) get_post_meta( $order_id, '_order_pre_net_total', true ) ?: $order_total;
		$discount_amount = 0.0;
		if($is_nc === 'no')
		{
			if ( strpos( $raw_amount, '%' ) !== false ) {

				$percent = (float) wc_format_decimal( str_replace( '%', '', $raw_amount ) );

				if ( $percent <= 0 || $percent > 100 ) {
					return new WP_Error(
						'pinaka_invalid_discount_percentage',
						__( 'Invalid discount percentage.', 'pinaka-pos' ),
						[ 'status' => 400 ]
					);
				}

				$discount_amount = - round(
					( $order_total * $percent ) / 100,
					wc_get_price_decimals()
				);
				
				update_post_meta( $order_id, '_merchant_discount_type', 'percentage' );
                update_post_meta( $order_id, '_merchant_discount_value', $percent );

			} else {
				$fixed = (float) wc_format_decimal( $raw_amount );
				if ( $fixed <= 0 ) {
					return new WP_Error(
						'pinaka_invalid_discount_amount',
						__( 'Invalid discount amount.', 'pinaka-pos' ),
						[ 'status' => 400 ]
					);
				}
                update_post_meta( $order_id, '_merchant_discount_type', 'fixed' );
                update_post_meta( $order_id, '_merchant_discount_value', $fixed );
				$discount_amount = - abs( $fixed );
			}

			if ( $order_total + $discount_amount <= 0 ) {
				return new WP_Error(
					'pinaka_discount_exceeds_total',
					__( 'Discount amount cannot be greater than order total.', 'pk-pos' ),
					[ 'status' => 400 ]
				);
			}

			$discount_product_id = absint( get_option( 'pinaka_discount_product_id', 0 ) );
			if ( ! $discount_product_id ) {
				return new WP_REST_Response( [ 'error' => 'Discount product not found' ], 404 );
			}

			foreach ( $order->get_items( 'line_item' ) as $item ) {
				if ( (int) $item->get_product_id() === $discount_product_id ) {
					return new WP_Error(
						'pinaka_discount_already_applied',
						__( 'Merchant discount has already been applied to this order.', 'pk-pos' ),
						[ 'status' => 400 ]
					);
				}
			}

			$discount_product = wc_get_product( $discount_product_id );

			$item_id = $order->add_product( $discount_product, 1, [
				'subtotal' => $discount_amount,
				'total'    => $discount_amount,
			] );

			if ( $item_id ) {
				$order->update_meta_data( '_order_discount', $discount_amount );
			}
			// $order->update_meta_data( '_is_nc_order', 'no' );
		}
		else
		{
			$order->update_meta_data( '_is_nc_order', 'yes' );
		}
		if(!empty($discount_reason))
		{
			$order->update_meta_data( '_discount_reason', $discount_reason );
		}
		$order->calculate_totals(true);
		$order->save();
		if($is_nc === 'no')
		{
			return new WP_REST_Response( [
				'status'  => 'success',
				'message' => 'Discount applied successfully',
				'data'    => [
					'order_id'      => $order_id,
					'discount_amt'  => (float) $discount_amount,
					'is_nocharge'   => 'no',
					'discount_reason' => $discount_reason
				],
			], 200 );
		}
		else
		{
			return new WP_REST_Response( [
				'status'  => 'success',
				'message' => 'No charges applied successfully',
				'data'    => [
					'order_id'      => $order_id,
					'is_nocharge'   => 'yes',
					'discount_reason' => $discount_reason
				],
			], 200 );
		}
	}
	function pinaka_pos_remove_discount( WP_REST_Request $request )
	{
		$params   = $request->get_json_params();
		$order_id = isset( $params['order_id'] ) ? absint( $params['order_id'] ) : 0;
		$is_nc = isset($params['is_nc']) ? $params['is_nc'] : 'no';

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return new WP_REST_Response( [ 'error' => 'Order not found' ], 404 );
		}
		if($is_nc === 'no')
		{
			$discount_amt = 0.0;
			$discount_product_id = absint( get_option( 'pinaka_discount_product_id', 0 ) );
			foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) {

                $product = $item->get_product();
            
                if ( ! $product ) {
                    continue;
                }
            
                $product_name = trim( $product->get_name() );
            
                if ( strtolower( $product_name ) === 'discount' ) {
            
                    $discount_product_id = $product->get_id();
            
                    $discount_amt = (float) $item->get_total();
            
                    $order->remove_item( $item_id );
                }
            }
            
            $order->save();
			if($discount_amt)
			{
				if ( $order->get_meta( '_order_discount' ) !== '' ) 
				{
					$order->delete_meta_data( '_order_discount');
				}
				// $order->delete_meta_data( '_is_nc_order');
				if ( $order->get_meta( '_discount_reason' ) !== '' ) 
				{
					$order->delete_meta_data( '_discount_reason' );
				}
				$order->calculate_totals(true);
				$order->save();
			}
			return new WP_REST_Response( [
				'status'  => 'success',
				'message' => 'Discount removed successfully',
				'data'    => [
					'order_id' => $order_id,
					'is_nocharge' => 'no'
				]
			], 200 );
		}
		else
		{
			// $order->delete_meta_data( '_is_nc_order');
			if ( $order->get_meta( '_is_nc_order' ) !== '' ) 
			{
				$order->delete_meta_data( '_is_nc_order' );
			}
			if ( $order->get_meta( '_discount_reason' ) !== '' ) 
			{
				$order->delete_meta_data( '_discount_reason' );
			}
			$order->calculate_totals(true);
			$order->save();
			return new WP_REST_Response( [
				'status'  => 'success',
				'message' => 'No charges removed successfully',
				'data'    => [
					'order_id' => $order_id,
					'is_nocharge' => 'no'
				]
			], 200 );
		}
	}

	function pinaka_pos_add_tip( WP_REST_Request $request ) {
		$params   = $request->get_json_params();
		$order_id = isset( $params['order_id'] ) ? absint( $params['order_id'] ) : 0;
		$amount   = isset( $params['amount'] ) ? wc_format_decimal( $params['amount'] ) : 0;

		if ( ! $order_id || ! $amount ) {
			return new WP_REST_Response(
				[ 'error' => 'Order ID and amount are required' ],
				400
			);
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return new WP_REST_Response(
				[ 'error' => 'Order not found' ],
				404
			);
		}

		// Tip product
		$tip_product_id = get_option( 'pinaka_tip_product_id', 0 );
		if ( ! $tip_product_id ) {
			return new WP_REST_Response(
				[ 'error' => 'Tip product not found' ],
				404
			);
		}

		// Prevent duplicate tip
		foreach ( $order->get_items( 'line_item' ) as $item ) {
			if ( (int) $item->get_product_id() === (int) $tip_product_id ) {
				return new WP_Error(
					'woocommerce_rest_invalid_tip',
					__( 'Tip already exists for this order.', 'woocommerce' ),
					[ 'status' => 400 ]
				);
			}
		}

		$children = $this->get_kot_child_orders_by_parent_id( $order_id );
			$children = array_filter( $children, function ( $child ) {
				return $child->get_meta( '_kot_number', true );
			} );

			if ( empty( $children )) {
				return new WP_Error(
					'pinaka_empty_order_no_discount_only',
					__( 'Tip cannot be applied to an empty cart.', 'pinaka-pos' ),
					[ 'status' => 400 ]
				);
			}

		$tip_product = wc_get_product( $tip_product_id );

		// Remove existing tip fee if any
		foreach ( $order->get_fees() as $fee_id => $fee_item ) {
			if ( strtolower( $fee_item->get_name() ) === 'tip' ) {
				$order->remove_item( $fee_id );
			}
		}

		// Add tip as product
		$item_id = $order->add_product( $tip_product, 1, [
			'subtotal' => $amount,
			'total'    => $amount,
		] );

		if ( $item_id ) {
			$order->update_meta_data( '_order_tip', $amount );
		}

		$order->calculate_totals(true);
		$order->save();

		$data['message']    = 'Tip was added successfully';
		$data['order_id'] = $order_id;
		$data['tip_amount'] = (float) $amount;

		return rest_ensure_response( $data );
	}

	function pinaka_pos_remove_tip( WP_REST_Request $request )
	{
		$params   = $request->get_json_params();
		$order_id = isset( $params['order_id'] ) ? absint( $params['order_id'] ) : 0;
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return new WP_REST_Response( [ 'error' => 'Order not found' ], 404 );
		}
		$tip_amt = 0.0;
		$tip_product_id = absint( get_option( 'pinaka_tip_product_id', 0 ) );
		foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) 
		{
			$product_id = $item->get_product_id();
			if (absint($product_id) === $tip_product_id) 
			{
				$tip_amt = (float) $item->get_total();
				$order->remove_item( $item_id );
			}
		}
		if($tip_amt)
		{
			$order->delete_meta_data( '_order_tip');
			$order->calculate_totals(true);
			$order->save();
		}
		return new WP_REST_Response( [
			'status'  => 'success',
			'message' => 'Tip item removed successfully',
			'data'    => [
				'order_id' => $order_id,
				'discount_amount' => (float) $discount_amt
			]
		], 200 );
	}

	function pinaka_pos_add_payout( WP_REST_Request $request ) {
		$params   = $request->get_json_params();
		$order_id = isset( $params['order_id'] ) ? absint( $params['order_id'] ) : 0;
		$amount   = isset( $params['amount'] ) ? wc_format_decimal( $params['amount'] ) : 0;

		if ( ! $order_id || ! $amount ) {
			return new WP_REST_Response( [ 'error' => 'Order ID and amount are required' ], 400 );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return new WP_REST_Response( [ 'error' => 'Order not found' ], 404 );
		}

		// Find "Payout" product
		$payout_product_id = get_option( 'pinaka_payout_product_id', 0 );
		if ( ! $payout_product_id ) {
			return new WP_REST_Response( [ 'error' => 'Payout product not found' ], 404 );
		}

		foreach ( $order->get_items( 'line_item' ) as $item ) {
			if ( (int) $item->get_product_id() === (int) $payout_product_id ) {
				return new WP_Error(
					'woocommerce_rest_invalid_discount',
					__( 'Payout already exists for this order.', 'woocommerce' ),
					array( 'status' => 400 )
				);
			}
		}

		$payout_product = wc_get_product( $payout_product_id );
		foreach ( $order->get_fees() as $fee_id => $fee_item ) {
			if ( strtolower( $fee_item->get_name() ) === 'payout' ) {
				$order->remove_item( $fee_id );
			}
		}

		$item_id = $order->add_product( $payout_product, 1, [
			'subtotal' => $amount,
			'total'    => $amount,
		] );

		if ( $item_id ) {
			$order->update_meta_data( '_order_payout', $amount );
		}

		$order->calculate_totals();
		$order->save();

		$controller = new WC_REST_Orders_V2_Controller();
		$rest_request = new WP_REST_Request( 'GET' );
		$rest_request->set_param( 'context', 'view' );

		$response = $controller->prepare_object_for_response( $order, $rest_request );
		return rest_ensure_response( $response->get_data() );
	}

	function pinaka_get_product_by_title( $title ) {
		$query = new WP_Query( [
			'post_type'      => 'product',
			'title'          => $title,
			'posts_per_page' => 1,
			'post_status'    => 'any',
			'fields'         => 'ids',
		] );

		if ( $query->have_posts() ) {
			return $query->posts[0]; // product ID
		}

		return false;
	}

	/**
	 * Get products/attributes/terms  callback.
	 *
	 * @param WP_REST_Request $request .
	 * @return array|WP_Error
	 */
	public function order_items_callback( WP_REST_Request $request ) {

		$params     = $request->get_params();
		$product_id = $params['product_id'];
		$page       = isset( $params['page'] ) ? $params['page'] : 1;
		$per_page   = isset( $params['per_page'] ) ? $params['per_page'] : 10;

		global $wpdb;

		// Calculate offset and limit based on page and per_page values
		$offset = ( $page - 1 ) * $per_page;
		$limit  = $per_page;

		// Modify the SQL query to include offset and limit clauses
		$query = $wpdb->prepare(
			"
        SELECT
            oi.order_id,
            o.post_date,
            o.post_date_gmt,
            oim2.meta_key,
            oim2.meta_value
        FROM {$wpdb->prefix}woocommerce_order_items AS oi
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oim
        ON oi.order_item_id = oim.order_item_id
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oim2
        ON oim.order_item_id = oim2.order_item_id
        JOIN {$wpdb->prefix}posts AS o
        ON oi.order_id = o.ID
        WHERE oi.order_item_type = 'line_item'
        AND (
            oim.meta_key = '_product_id'
            OR oim.meta_key = '_variation_id'
        )
        AND oim.meta_value = %d
        AND (
            oim2.meta_key = '_reduced_stock'
            OR oim2.meta_key = '_increased_stock'
        )
        GROUP BY oi.order_id, oim2.meta_key, oim2.meta_value
        ORDER BY o.post_date_gmt DESC
        LIMIT %d OFFSET %d
        ",
			$product_id,
			$limit,
			$offset
		);

		$order_stock_changes = $wpdb->get_results( $query );

		return new WP_REST_Response(
			$order_stock_changes,
			200
		);
	}

	/**
	 * Get order items for gst report
	 *
	 * @param WP_REST_Request $request .
	 * @return array|WP_Error
	 */
	public function order_items_gst_report_callback( WP_REST_Request $request ) {

		$params = $request->get_params();

		$page       = isset( $params['page'] ) ? $params['page'] : 1;
		$per_page   = isset( $params['per_page'] ) ? $params['per_page'] : 10;
		$start_date = isset( $params['start_date'] ) ? $params['start_date'] : 10;
		$end_date   = isset( $params['end_date'] ) ? $params['end_date'] : 10;

		global $wpdb;

		// Calculate offset and limit based on page and per_page values
		$offset = ( $page - 1 ) * $per_page;
		$limit  = $per_page;

		// Modify the SQL query to include offset and limit clauses
		$query = $wpdb->prepare(
			"
            SELECT
    oi.*,
    p.ID,
    p.post_date,
    customer_meta.meta_value AS user_id,
    first_name_meta.meta_value AS first_name,
    last_name_meta.meta_value AS last_name,
    state_meta.meta_value AS billing_state,
    user_meta.meta_value AS gst_number,
    item_qty_meta.meta_value AS quantity,
    item_subtotal_meta.meta_value AS subtotal,
    item_total_meta.meta_value AS total,
    item_product_meta.meta_value AS product_id,
    item_variation_meta.meta_value AS variation_id,
    hsn_meta.meta_value AS hsn_sac,
    cgst_meta.meta_value AS mp_tax_cgst,
    sgst_meta.meta_value AS mp_tax_sgst,
    cess_meta.meta_value AS mp_tax_cess
FROM
{$wpdb->prefix}posts AS p
JOIN {$wpdb->prefix}woocommerce_order_items AS oi
ON
    oi.order_id = p.ID
LEFT JOIN {$wpdb->prefix}postmeta AS customer_meta
ON
    p.ID = customer_meta.post_id AND customer_meta.meta_key = '_customer_user'
LEFT JOIN {$wpdb->prefix}postmeta AS first_name_meta
ON
    p.ID = first_name_meta.post_id AND first_name_meta.meta_key = '_billing_first_name'
LEFT JOIN {$wpdb->prefix}postmeta AS last_name_meta
ON
    p.ID = last_name_meta.post_id AND last_name_meta.meta_key = '_billing_last_name'
LEFT JOIN {$wpdb->prefix}postmeta AS state_meta
ON
    p.ID = state_meta.post_id AND state_meta.meta_key = '_billing_state'
LEFT JOIN {$wpdb->prefix}usermeta AS user_meta
ON
    customer_meta.meta_value = user_meta.user_id AND user_meta.meta_key = 'gstin_number'
LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS item_qty_meta
ON
    oi.order_item_id = item_qty_meta.order_item_id AND item_qty_meta.meta_key = '_qty'
LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS item_subtotal_meta
ON
    oi.order_item_id = item_subtotal_meta.order_item_id AND item_subtotal_meta.meta_key = '_line_subtotal'
LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS item_total_meta
ON
    oi.order_item_id = item_total_meta.order_item_id AND item_total_meta.meta_key = '_line_total'
LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS item_product_meta
ON
    oi.order_item_id = item_product_meta.order_item_id AND item_product_meta.meta_key = '_product_id'
LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS item_variation_meta
ON
    oi.order_item_id = item_variation_meta.order_item_id AND item_variation_meta.meta_key = '_variation_id'
LEFT JOIN {$wpdb->prefix}postmeta AS hsn_meta
ON
    (
        CASE WHEN item_variation_meta.meta_value = 0 THEN item_product_meta.meta_value ELSE item_variation_meta.meta_value
    END
) = hsn_meta.post_id AND hsn_meta.meta_key = 'hsn_sac'
LEFT JOIN {$wpdb->prefix}postmeta AS cgst_meta
ON
    item_product_meta.meta_value = cgst_meta.post_id AND cgst_meta.meta_key = 'mp_tax_cgst'
LEFT JOIN {$wpdb->prefix}postmeta AS sgst_meta
ON
    item_product_meta.meta_value = sgst_meta.post_id AND sgst_meta.meta_key = 'mp_tax_sgst'
LEFT JOIN {$wpdb->prefix}postmeta AS cess_meta
ON
    item_product_meta.meta_value = cess_meta.post_id AND cess_meta.meta_key = 'mp_tax_cess'
WHERE
    oi.order_item_type = 'line_item' AND p.post_status = 'wc-completed' AND p.post_type = 'shop_order'
    AND p.post_date BETWEEN %s AND %s
    LIMIT %d OFFSET %d;
        ",
			$start_date,
			$end_date,
			$limit,
			$offset
		);

		$order_stock_changes = $wpdb->get_results( $query );

		return new WP_REST_Response(
			$order_stock_changes,
			200
		);
	}

	protected function calculate_coupons( $request, $order ) {
		do_action( 'woocommerce_apply_coupon_order_rest', $order, $request );

		$discounts = new WC_Discounts( $order );

		$apply_codes  = [];
		$remove_codes = [];
		$remove_all   = false;

		// --- 1) Parse coupon_lines (adds & removes) ---
		if ( isset( $request['coupon_lines'] ) && is_array( $request['coupon_lines'] ) ) {
			if ( count( $order->get_items( 'line_item' ) ) === 0 ) {
				throw new WC_REST_Exception(
					'woocommerce_rest_no_products_in_order',
					__( 'You cannot apply a coupon because the order has no products.', 'woocommerce' ),
					400
				);
			}

			foreach ( $request['coupon_lines'] as $item ) {
				// explicit removals
				if ( ! empty( $item['remove'] ) ) {
					if ( ! empty( $item['code'] ) ) {
						$remove_codes[] = wc_format_coupon_code( wc_clean( $item['code'] ) );
					} else {
						// allow "remove all" when code is omitted
						$remove_all = true;
					}
					continue;
				}

				// additions
				if ( ! empty( $item['id'] ) ) {
					throw new WC_REST_Exception(
						'woocommerce_rest_coupon_item_id_readonly',
						__( 'Coupon item ID is readonly.', 'woocommerce' ),
						400
					);
				}
				if ( empty( $item['code'] ) ) {
					throw new WC_REST_Exception(
						'woocommerce_rest_invalid_coupon',
						__( 'Coupon code is required.', 'woocommerce' ),
						400
					);
				}

				$coupon_code = wc_format_coupon_code( wc_clean( $item['code'] ) );
				$check       = $discounts->is_coupon_valid( new WC_Coupon( $coupon_code ) );
				if ( is_wp_error( $check ) ) {
					throw new WC_REST_Exception(
						'woocommerce_rest_' . $check->get_error_code(),
						$check->get_error_message(),
						400
					);
				}
				$apply_codes[] = $coupon_code;
			}
		}

		// --- 2) Remove requested coupons (or all) ---
		if ( $remove_all || ! empty( $remove_codes ) ) {
			foreach ( $order->get_items( 'coupon' ) as $existing_coupon ) {
				$code = wc_format_coupon_code( $existing_coupon->get_code() );

				if ( $remove_all || in_array( $code, $remove_codes, true ) ) {
					$order->remove_coupon( $code );
				}
			}
			// Make sure item-level metadata is cleaned after removals
			$this->clear_coupon_metadata_from_items( $order );
		}

		// --- 3) Remove any auto-applied coupons before recalculating ---
		foreach ( $order->get_items( 'coupon' ) as $existing_coupon ) {
			$code   = $existing_coupon->get_code();
			$coupon = new WC_Coupon( $code );

			$required_ids = get_post_meta( $coupon->get_id(), 'required_product_ids', true );
			if ( ! empty( $required_ids ) ) {
				$order->remove_coupon( $code );
			}
		}
		$this->clear_coupon_metadata_from_items( $order );

		// --- 4) Collect product IDs present in the order ---
		$product_ids_in_order = [];
		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}
			$pid = absint( $item->get_product_id() );
			$vid = absint( $item->get_variation_id() );
			if ( $pid ) $product_ids_in_order[] = $pid;
			if ( $vid ) $product_ids_in_order[] = $vid;
		}
		$product_ids_in_order = array_values( array_unique( $product_ids_in_order ) );

		// --- 5) Find coupons to auto-apply based on required_product_ids ---
		$coupon_ids = get_posts( [
			'post_type'      => 'shop_coupon',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => [
				[
					'key'     => 'required_product_ids',
					'compare' => 'EXISTS',
				],
			],
		] );

		foreach ( $coupon_ids as $coupon_id ) {
			$required_ids = get_post_meta( $coupon_id, 'required_product_ids', true );
			if ( is_string( $required_ids ) ) {
				$required_ids = array_filter( array_map( 'absint', array_map( 'trim', explode( ',', $required_ids ) ) ) );
			} elseif ( is_array( $required_ids ) ) {
				$required_ids = array_filter( array_map( 'absint', $required_ids ) );
			} else {
				$required_ids = [];
			}
			if ( empty( $required_ids ) ) {
				continue;
			}

			// only if ALL required products are in the order
			if ( empty( array_diff( $required_ids, $product_ids_in_order ) ) ) {
				$code  = wc_format_coupon_code( get_the_title( $coupon_id ) );
				$check = $discounts->is_coupon_valid( new WC_Coupon( $code ) );
				if ( ! is_wp_error( $check ) ) {
					$apply_codes[] = $code;
				}
			}
		}

		// --- 6) Finalize list to apply (unique) and ensure we don't re-add removed ones ---
		$apply_codes = array_values( array_unique( $apply_codes ) );
		if ( $remove_all ) {
			$apply_codes = []; // user asked to remove all coupons
		} elseif ( ! empty( $remove_codes ) ) {
			$apply_codes = array_values( array_diff( $apply_codes, $remove_codes ) );
		}

		// --- 7) Apply coupons ---
		foreach ( $apply_codes as $code ) {
			$result = $order->apply_coupon( $code );
			if ( is_wp_error( $result ) ) {
				throw new WC_REST_Exception(
					'woocommerce_rest_' . $result->get_error_code(),
					$result->get_error_message(),
					400
				);
			}
			$this->add_coupon_metadata_to_discounted_items( $order, $code );
		}

		// --- 8) Totals ---
		$order->calculate_totals();

		return true;
	}


	protected function clear_coupon_metadata_from_items( $order ) {
		foreach ( $order->get_items() as $item ) {
			$item->delete_meta_data( 'Discount Type' );
			$item->delete_meta_data( 'Discount Applied' );
			$item->delete_meta_data( 'Discounted Unit Price' );
			$item->save();
		}
	}

	protected function add_coupon_metadata_to_discounted_items( $order, $coupon_code ) {
		$coupon = new WC_Coupon( $coupon_code );
		$required_ids = (array) get_post_meta( $coupon->get_id(), 'required_product_ids', true );

		// Only run if required products are set
		if ( empty( $required_ids ) ) {
			return;
		}

		// Check if all required products exist in the order
		$found_product_ids = [];

		foreach ( $order->get_items() as $item ) {
			$found_product_ids[] = $item->get_product_id();
		}

		// If not all required products are in the order, do nothing
		foreach ( $required_ids as $required_id ) {
			if ( ! in_array( $required_id, $found_product_ids ) ) {
				return;
			}
		}

		// Apply metadata only to non-required discounted products
		foreach ( $order->get_items() as $item ) {
			// $product_id = $item->get_product_id();
			$variation_id = (int) $item->get_variation_id();
			$product_id   = (int) $item->get_product_id();

			$item_key = $variation_id ?: $product_id;
			$product = wc_get_product( $item_key );
			// Skip required items
			if ( in_array( $item_key, $required_ids ) ) {
				continue;
			}

			$qty = $item->get_quantity();

			// $product = $item->get_product();

			if ( ! $product ) {
				continue;
			}

			$original_price  = floatval( $product->get_price() );
			$total_before    = $original_price * $qty;
			$total_after     = floatval( $item->get_total() );
			$discount_amount = $total_before - $total_after;

			if ( $discount_amount > 0 ) {
				$new_unit_price = $total_after / $qty;

				$item->add_meta_data( 'Discount Type', 'Combo Discount', true );
				$item->add_meta_data( 'Discount Applied', wc_format_decimal( $discount_amount ), true );
				$item->add_meta_data( 'Discounted Unit Price', wc_format_decimal( $new_unit_price ), true );
				$item->save();
			}
		}
	}
	
	/**
	 * Prepare a single order for create or update.
	 *
	 * @throws WC_REST_Exception When fails to set any item.
	 * @param  WP_REST_Request $request Request object.
	 * @param  bool            $creating If is creating a new object.
	 * @return WP_Error|WC_Data
	 */
	protected function prepare_object_for_database( $request, $creating = false ) {

		$id        = isset( $request['id'] ) ? absint( $request['id'] ) : 0;
		$order     = new WC_Order( $id );
		$schema    = $this->get_item_schema();
		$data_keys = array_keys( array_filter( $schema['properties'], array( $this, 'filter_writable_props' ) ) );

		// Handle all writable props.
		foreach ( $data_keys as $key ) {
			$value = $request[ $key ];

			if ( ! is_null( $value ) ) {
				switch ( $key ) {
					case 'coupon_lines':
					case 'status':
						// Change should be done later so transitions have new data.
						break;
					case 'billing':
					case 'shipping':
						$this->update_address( $order, $value, $key );
						break;
					case 'line_items':
					case 'shipping_lines':
					case 'fee_lines':
						if ( is_array( $value ) ) {
							foreach ( $value as $item ) {
								if ( is_array( $item ) ) {
									if ( $this->item_is_null( $item ) || ( isset( $item['quantity'] ) && 0 === $item['quantity'] ) ) {
										$order->remove_item( $item['id'] );
										do_action( 'mp_order_item_removed', $item['id'], $order );
									} else {
										$this->set_item( $order, $key, $item );
									}
								}
							}
						}
						break;
					case 'meta_data':
						if ( is_array( $value ) ) {
							foreach ( $value as $meta ) {
								$order->update_meta_data( $meta['key'], $meta['value'], isset( $meta['id'] ) ? $meta['id'] : '' );
							}
						}
						$order->update_meta_data( '_wc_order_created_by', get_current_user_id() );

						break;
					default:
						if ( is_callable( array( $order, "set_{$key}" ) ) ) {
							$order->{"set_{$key}"}( $value );
						}
						break;
				}
			}
		}

		/**
		 * Filters an object before it is inserted via the REST API.
		 *
		 * The dynamic portion of the hook name, `$this->post_type`,
		 * refers to the object type slug.
		 *
		 * @param WC_Data         $order    Object object.
		 * @param WP_REST_Request $request  Request object.
		 * @param bool            $creating If is creating a new object.
		 */
		return apply_filters( "woocommerce_rest_pre_insert_{$this->post_type}_object", $order, $request, $creating );
	}

	/**
	 * Save an object data.
	 *
	 * @since  3.0.0
	 * @throws WC_REST_Exception But all errors are validated before returning any data.
	 * @param  WP_REST_Request $request  Full details about the request.
	 * @param  bool            $creating If is creating a new object.
	 * @return WC_Data|WP_Error
	 */
	protected function save_object( $request, $creating = false ) {
        // First launch
		try {
			// Load the existing order early (for delta calculations), but do not mutate it
			$existing_order = null;
			if ( ! $creating && ! empty( $request['id'] ) ) {
				$existing_order = wc_get_order( absint( $request['id'] ) );
			}
			
			// Capture original quantities BEFORE order modification
            $void_original_items = [];
    
            if ( ! $creating && $existing_order && ! empty( $request['line_items'] ) ) {
    
                foreach ( $request['line_items'] as $item_update ) {
    
                    $item_id = absint( $item_update['id'] ?? 0 );
    
                    if ( ! $item_id ) {
                        continue;
                    }
    
                    $existing_item = $existing_order->get_item( $item_id );
    
                    if ( ! $existing_item ) {
                        continue;
                    }
    
                    $void_original_items[ $item_id ] = [
                        'orig_qty' => (int) $existing_item->get_quantity(),
                        'product'  => $existing_item->get_name(),
                        'total'    => (float) $existing_item->get_total(),
                        'tax'      => (float) $existing_item->get_total_tax(),
                    ];
                }
            }
    
            $request->set_param(
                '_void_original_items',
                $void_original_items
            );


			// Validate stock using raw request payload and existing order (this does NOT change any order)
			$stock_check = $this->validate_line_items_stock_request( $request, $existing_order );
			if ( is_wp_error( $stock_check ) ) {
				// IMPORTANT: return the error immediately so nothing is saved.
				return $stock_check;
			}
			
			
			
			$object = $this->prepare_object_for_database( $request, $creating );

			if ( is_wp_error( $object ) ) {
				return $object;
			}

			// Make sure gateways are loaded so hooks from gateways fire on save/create.
			WC()->payment_gateways();

			if ( ! is_null( $request['customer_id'] ) && 0 !== $request['customer_id'] ) {
				// Make sure customer exists.
				if ( false === get_user_by( 'id', $request['customer_id'] ) ) {
					throw new WC_REST_Exception( 'woocommerce_rest_invalid_customer_id', __( 'Customer ID is invalid.', 'woocommerce' ), 400 );
				}

				// Make sure customer is part of blog.
				if ( is_multisite() && ! is_user_member_of_blog( $request['customer_id'] ) ) {
					add_user_to_blog( get_current_blog_id(), $request['customer_id'], 'customer' );
				}
			}

			if ( $creating ) {
				$object->set_created_via( 'rest-api' );
				$object->set_prices_include_tax( 'yes' === get_option( 'woocommerce_prices_include_tax' ) );
				// Add mix-and-match discounts before totals
    			// $this->apply_mix_and_match_discounts( $object );
				$object->calculate_totals();

			} else {
				if ( ! empty( $request['fee_lines'] ) ) {
					$order_id       = $object->get_id(); // Or use $request['id'] if necessary
					$existing_order = wc_get_order( $order_id );
					// Count new payout fees being added
					foreach ( $request['fee_lines'] as $new_fee ) {
						// ✅ Check for discount with empty order
						if ( isset( $new_fee['name'] ) && strtolower( $new_fee['name'] ) === 'discount' ) {
							if ( count( $existing_order->get_items( 'line_item' ) ) === 0 ) {
								return new WP_Error(
									'woocommerce_rest_invalid_discount',
									__( 'You cannot apply a discount because the order has no products.', 'woocommerce' ),
									array( 'status' => 400 )
								);
							}

							if ( isset( $new_fee['total'] ) ) {
								$discount_amount = floatval( $new_fee['total'] );
								$order_total     = floatval( $existing_order->get_total() );

								// Important: discount is usually negative in WooCommerce (e.g., -50)
								// So compare absolute value
								if ( abs( $discount_amount ) > $order_total ) {
									return new WP_Error(
										'woocommerce_rest_discount_exceeds_total',
										__( 'Discount amount cannot be greater than the order total.', 'woocommerce' ),
										array( 'status' => 400 )
									);
								}
							}

							// Order total is zero or below
							if ( $existing_order->get_total() <= 0 ) {
								return new WP_Error(
									'woocommerce_rest_invalid_discount_total',
									__( 'You cannot apply a discount because the order total is zero or negative.', 'woocommerce' ),
									array( 'status' => 400 )
								);
							}
						}
					}
				}

				// If items have changed, recalculate order totals.
				if ( isset( $request['billing'] ) || isset( $request['shipping'] ) || isset( $request['line_items'] ) || isset( $request['shipping_lines'] ) || isset( $request['fee_lines'] ) || isset( $request['coupon_lines'] ) ) {
    				// $this->apply_mix_and_match_discounts( $object );

					$data = $request->get_json_params();

					// === LINE ITEMS ===
					if ( isset( $data['line_items'] ) && is_array( $data['line_items'] ) ) {
						foreach ( $data['line_items'] as $item_update ) {
							$item_id = $item_update['id'] ?? null;
							$new_qty = absint( $item_update['quantity'] ?? 0 );

							if ( ! $item_id || $new_qty <= 0 ) {
								continue;
							}

							$item = $object->get_item( $item_id );
							if ( ! $item ) {
								continue;
							}

							$product = $item->get_product();
							if ( ! $product ) {
								continue;
							}

							// Remove old and re-add
							$object->remove_item( $item_id );
							$object->add_product( $product, $new_qty );
						}
						// $this->maybe_apply_combo_coupon( $object );
						// Recalculate and save only if line_items changed
						$object->calculate_totals();
						$object->save();
					}else{
						$object->calculate_totals(true);
					}
				}
			}
			
			// 2) Remove any existing payout lines (product or fee) from the order so coupons won't touch them
			//    Also, if no payout_amount in request, try to recover it from existing items/meta.
			$recovered_amount = $this->pinaka_remove_existing_payout_lines( $object );
			$recovered_amount_discount = $this->pinaka_remove_existing_discount_lines( $object );
			$recovered_tip_discount = $this->pinaka_remove_existing_tip_lines( $object );
			// Set coupons.
			$this->calculate_coupons( $request, $object );
			// die;
			
			if($recovered_amount){
				// Find "Payout" product
				$payout_product_id = get_option( 'pinaka_payout_product_id', 0 );
				$payout_product = wc_get_product( $payout_product_id );
				// Add payout product line (only once)
				$item_id = $object->add_product( $payout_product, 1, [
					'subtotal' => $recovered_amount,
					'total'    => $recovered_amount,
				] );
				// $object->calculate_totals(true);
			}
			if ( $recovered_amount_discount ) {
			    $discount_product_id = (int) get_option( 'pinaka_discount_product_id', 0 );
				if ( $discount_product_id && $p = wc_get_product( $discount_product_id ) ) {
					$object->add_product( $p, 1, [
						'subtotal' => $recovered_amount_discount,
						'total'    => $recovered_amount_discount,
					] );
				}
			}
			if ( $recovered_tip_discount ) {
			    $tip_product_id = (int) get_option( 'pinaka_tip_product_id', 0 );
				if ( $tip_product_id && $p = wc_get_product( $tip_product_id ) ) {
					$object->add_product( $p, 1, [
						'subtotal' => $recovered_tip_discount,
						'total'    => $recovered_tip_discount,
					] );
				}
			}
			$object->calculate_totals(true);
			/**
			 * Adds custom order statuses to the WooCommerce order status dropdown menu.
			 *
			 * This function adds three custom order statuses to the existing array of
			 * order statuses that is used to populate the order status dropdown menu
			 * on the WooCommerce admin pages. The custom statuses are "Pending Purchase",
			 * "Ordered Purchase", and "Received Purchase".
			 *
			 * @param array $statuses The existing array of order statuses.
			 * @return array The updated array of order statuses.
			 */
			function add_custom_order_statuses( $statuses ) {
				// Add your custom order statuses to the existing array of statuses.
				$custom_statuses = array(
					'wc-pending-purchase'  => _x( 'Pending Purchase', 'Order status', 'pinaka-pos' ),
					'wc-ordered-purchase'  => _x( 'Ordered Purchase', 'Order status', 'pinaka-pos' ),
					'wc-received-purchase' => _x( 'Received Purchase', 'Order status', 'pinaka-pos' ),
				);
				return array_merge( $custom_statuses, $statuses );
			}
			// Add the callback function to the filter hook.
			add_filter( 'wc_order_statuses', 'add_custom_order_statuses', 10, 1 );
			// Set status.
			if ( ! empty( $request['status'] ) ) {
				$object->set_status( $request['status'] );
			}
			// 5) Add payout as a negative fee AFTER coupons so coupons never reduce it
			// if ( $payout_amount !== 0.0 ) {
			// 	$this->pinaka_add_payout_fee( $object, $payout_amount );
			// }
			$object->save();

			// Actions for after the order is saved.
			if ( true === $request['set_paid'] ) {
				if ( $creating || $object->needs_payment() ) {
					$object->payment_complete( $request['transaction_id'] );
				}
			}
			remove_filter( 'wc_order_statuses', 'add_custom_order_statuses', 10, 1 );

			return $this->get_object( $object->get_id() );
		} catch ( WC_Data_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
		} catch ( WC_REST_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Validate line_items in the incoming request against stock without mutating order.
	 *
	 * @param WP_REST_Request $request
	 * @param WC_Order|null   $existing_order  Existing order (when updating), or null when creating
	 * @return null|WP_Error
	 */
	private function validate_line_items_stock_request( $request, $existing_order = null ) {
		$data = $request->get_json_params();
		if ( empty( $data['line_items'] ) || ! is_array( $data['line_items'] ) ) {
			return null;
		}

		foreach ( $data['line_items'] as $item ) {
			$requested_qty = isset( $item['quantity'] ) ? intval( $item['quantity'] ) : 1;
			if ( $requested_qty <= 0 ) {
				// treat as removal or skip
				continue;
			}

			// Prefer variation_id then product_id
			$prod_id = 0;
			if ( ! empty( $item['variation_id'] ) ) {
				$prod_id = absint( $item['variation_id'] );
			} elseif ( ! empty( $item['product_id'] ) ) {
				$prod_id = absint( $item['product_id'] );
			}

			// If this is an update to an existing order item, we can derive product and existing qty from existing_order
			$existing_qty = 0;
			if ( ! empty( $item['id'] ) && $existing_order instanceof WC_Order ) {
				$existing_item = $existing_order->get_item( $item['id'] );
				if ( $existing_item ) {
					$existing_qty = (int) $existing_item->get_quantity();
					// if product id not provided in payload, derive from existing order item
					if ( ! $prod_id ) {
						$existing_product = $existing_item->get_product();
						if ( $existing_product ) {
							// prefer variation id
							$prod_id = ( $existing_item->get_variation_id() ) ? $existing_item->get_variation_id() : $existing_product->get_id();
						}
					}
				}
			}

			if ( ! $prod_id ) {
				return new WP_Error(
					'woocommerce_rest_invalid_product',
					__( 'Line item missing product_id/variation_id and unable to detect product from existing order.', 'woocommerce' ),
					array( 'status' => 400 )
				);
			}

			$product = wc_get_product( $prod_id );
			if ( ! $product ) {
				return new WP_Error(
					'woocommerce_rest_invalid_product',
					__( 'Invalid product/variation ID in line_items.', 'woocommerce' ),
					array( 'status' => 400 )
				);
			}

			// If product does not manage stock, allow by default (change if you want stricter behavior).
			if ( ! $product->managing_stock() ) {
				continue;
			}

			// Disallow if backorders are not permitted by your policy for API changes.
			if ( $product->backorders_allowed() ) {
				// if you want to *reject* backorders for API, replace continue; with return error.
				continue;
			}

			$delta = $requested_qty - $existing_qty;
			if ( $delta <= 0 ) {
				continue; // reducing qty or same qty -> safe
			}

			$available_stock = (int) $product->get_stock_quantity();
			if ( $available_stock < $delta ) {
				return new WP_Error(
					'woocommerce_rest_stock_error',
					sprintf(
						__( 'This item is out of stock. Please check.' ),
						$product->get_name(),
						$available_stock,
					),
					array( 'status' => 400 )
				);
			}
		}

		return null;
	}



	/**
	 * Remove any existing payout representations from the order (product or fee).
	 * Returns a recovered payout amount if we find one; otherwise 0.0.
	 */
	private function pinaka_remove_existing_payout_lines( $order ): float {
		$recovered = 0.0;
		// 1) Get Order meta _order_payout (if any)
		$meta_payout = $order->get_meta( '_order_payout', true );
		if ( is_numeric( $meta_payout ) ) {
			$recovered = floatval( $meta_payout );
		}

		// 2) Remove payout product lines (legacy representation)
		// If you used a fixed product ID, set it here; else rely on _is_payout meta.
		$payout_product_id = get_option( 'pinaka_payout_product_id', 0 );; // e.g. 9999 if you had one; else 0
		foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) {
			if ( ( $payout_product_id && $item->get_product_id() == $payout_product_id ) ) {
				$order->remove_item( $item_id );
			}
		}
		return $recovered;
	}
    private function pinaka_remove_existing_discount_lines( $order ): float {
		$recovered = 0.0;

		// 1) Get Order meta _order_payout (if any)
		$meta_discount = $order->get_meta( '_order_discount', true );
		if ( is_numeric( $meta_discount ) ) {
			$recovered = floatval( $meta_discount );
		}
		if($recovered == 0.0)
		{
			foreach ( $order->get_items( 'line_item' ) as $item ) {
				if($item->get_name() === 'Discount')
				{
					$recovered = $item->get_total();
					break;
				}
			}
			// return $recovered;
		}
		// 2) Remove payout product lines (legacy representation)
		// If you used a fixed product ID, set it here; else rely on _is_payout meta.
		$discount_product_id = get_option( 'pinaka_discount_product_id', 0 );; // e.g. 9999 if you had one; else 0
		foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) {
			if ( ( $discount_product_id && $item->get_product_id() == $discount_product_id ) ) {
				$recovered = $item->get_total();
				$order->remove_item( $item_id );
			}
		}
		return $recovered;
	}
	private function pinaka_remove_existing_tip_lines( $order ): float {
		$recovered = 0.0;

		// 1) Get Order meta _order_payout (if any)
		$meta_discount = $order->get_meta( '_order_tip', true );
		if ( is_numeric( $meta_discount ) ) {
			$recovered = floatval( $meta_discount );
		}
		if($recovered == 0.0)
		{
			foreach ( $order->get_items( 'line_item' ) as $item ) {
				if($item->get_name() === 'Tip')
				{
					$recovered = $item->get_total();
					break;
				}
			}
			// return $recovered;
		}
		// 2) Remove payout product lines (legacy representation)
		// If you used a fixed product ID, set it here; else rely on _is_payout meta.
		$discount_product_id = get_option( 'pinaka_tip_product_id', 0 );; // e.g. 9999 if you had one; else 0
		foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) {
			if ( ( $discount_product_id && $item->get_product_id() == $discount_product_id ) ) {
				$recovered = $item->get_total();
				$order->remove_item( $item_id );
			}
		}
		return $recovered;
	}
	/**
	 * Add payout as a negative fee, preserving your _order_payout meta.
	 * Coupons never apply to fees, so this is safe.
	 */
	private function pinaka_add_payout_fee( WC_Order $order, float $payout_amount ): void {
		if ( $payout_amount === 0.0 ) {
			return;
		}

		// Ensure negative
		$fee_total = 0 - abs( $payout_amount );

		$fee = new WC_Order_Item_Fee();
		$fee->set_name( 'Payout' );
		$fee->set_amount( $fee_total );
		$fee->set_total( $fee_total );
		$fee->set_tax_status( 'none' ); // change if payout should be taxable
		$fee->add_meta_data( '_order_payout', (string) $payout_amount, true );

		$order->add_item( $fee );
	}


	/**
	 * Get formatted item data.
	 *
	 * @param WC_Order $order WC_Data instance.
	 * @return array
	 */
	protected function get_formatted_item_data( $order ) {
        // Fourth Step
		$item_data       = parent::get_formatted_item_data( $order );
		$cpt_hidden_keys = array();

		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$cpt_hidden_keys = ( new \WC_Order_Data_Store_CPT() )->get_internal_meta_keys();
		}

		// XXX: This might be removed once we finalize the design for internal keys vs meta vs props in COT.
		if ( ! empty( $item_data['meta_data'] ) ) {
			$item_data['meta_data'] = array_filter(
				$item_data['meta_data'],
				function ( $meta ) use ( $cpt_hidden_keys ) {
					return ! in_array( $meta->key, $cpt_hidden_keys, true );
				}
			);
		}

		return $item_data;
	}
    
	/**
	 * Prepare objects query.
	 *
	 * @since  3.0.0
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		
		// echo "seven";
		// This is needed to get around an array to string notice in WC_REST_Orders_V2_Controller::prepare_objects_query.
		$statuses = $request['status'];
		unset( $request['status'] );

		// Prevents WC_REST_Orders_V2_Controller::prepare_objects_query() from generating a meta_query for 'customer'.
		// which COT can handle as a native field.
		$cot_customer =
			( OrderUtil::custom_orders_table_usage_is_enabled() && isset( $request['customer'] ) )
			? $request['customer']
			: null;

		if ( $cot_customer ) {
			unset( $request['customer'] );
		}


		$args = parent::prepare_objects_query( $request );

		$args['post_status'] = array();
		foreach ( $statuses as $status ) {
			if ( in_array( $status, $this->get_order_statuses(), true ) ) {
				$args['post_status'][] = 'wc-' . $status;
			} elseif ( 'any' === $status ) {

				foreach ( $this->get_order_statuses() as $status_order ) {
					$args['post_status'][] = 'wc-' . $status_order;
				}
				break;
			} else {
				$args['post_status'][] = $status;
			}
		}

		// Put the statuses back for further processing (next/prev links, etc).
		$request['status'] = $statuses;
		
		if ( isset( $request['created_via'] ) && $request['created_via'] === 'doordash' ) {
			$args['created_via'] = 'doordash';
		} else {
			$args['created_via'] = 'rest-api';
		}

		// Add back 'customer' to args and request.
		if ( ! is_null( $cot_customer ) ) {
			$args['customer']    = $cot_customer;
			$request['customer'] = $cot_customer;
		}

		$args['author'] = intval( $request['author'] ) ?? get_current_user_id();
		return $args;
	}

	/**
	 * Get objects.
	 *
	 * @param  array $query_args Query args.
	 * @return array
	 */
	protected function get_objects( $query_args ) {
		// Do not use WC_Order_Query for the CPT datastore.
		if ( ! OrderUtil::custom_orders_table_usage_is_enabled() ) {
			return parent::get_objects( $query_args );
		}
		
		if($query_args['author'] == 0){
			unset( $query_args['author'] );
			$query_args['author'] = get_current_user_id();
		}
		if(	isset( $query_args['created_via'] ) && $query_args['created_via'] == 'doordash' ){

			return array(
				'objects' => array(),
				'total'   => 0,
				'pages'   => 0,
			);
		}else{
			$query   = new \WC_Order_Query(
				array_merge(
					$query_args,
					array(
						'paginate' => true,
						'meta_query' => array(
							array(
								'key'     => '_wc_order_created_by',
								'value'   => $query_args['author'],
								'compare' => '=', // or 'LIKE', 'IN', '!=' etc.
							),
						),
					)
				)
			);
			

			$results = $query->get_orders();
			
			return array(
				'objects' => $results->orders,
				'total'   => $results->total,
				'pages'   => $results->max_num_pages,
			);
		}

	}

	/**
	 * Get the Order's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = parent::get_item_schema();

		$schema['properties']['coupon_lines']['items']['properties']['discount']['readonly'] = true;
		$schema['properties']['status']['enum'] = array_merge( array( 'pending-purchase', 'ordered-purchase', 'received-purchase' ), $this->get_order_statuses() );

		return $schema;
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
 
		$params = parent::get_collection_params();

		$params['status'] = array(
			'default'           => 'any',
			'description'       => __( 'Limit result set to orders which have specific statuses.', 'woocommerce' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'string',
				'enum' => array_merge( array( 'any', 'trash', 'wc-pending-purchase', 'wc-ordered-purchase', 'wc-received-purchase' ), $this->get_order_statuses() ),
			),
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}
	/**
	 * Calculate discount amount and final total.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function pinaka_discount_calculate( WP_REST_Request $request ) {
		$params = $request->get_params();
		if ( ! isset( $params['order_id'] ) ) {
			return new WP_REST_Response( array(
				'error' => 'Order ID is required'
			), 400 );
		}
		$order_id = absint( $params['order_id'] );
		$order    = wc_get_order( $order_id );
		if ( ! $order ) {
			return new WP_REST_Response( array(
				'error' => 'Invalid order ID'
			), 404 );
		}
		// $order_restaurant_id = (int) $order->get_meta( '_restaurant_id' );
        // $order_zone_id       = (int) $order->get_meta( '_zone_id' );

        // $req_restaurant_id = (int) $request->get_param( 'restaurant_id' );
        // $req_zone_id       = (int) $request->get_param( 'zone_id' );

        // if ( $order_restaurant_id !== $req_restaurant_id || $order_zone_id !== $req_zone_id ) {
        //     return new WP_Error(
        //         'mismatched_ids',
        //         __( 'Restaurant or Zone ID does not match with order Restaurant or Zone ID.', 'pinaka-pos' ),
        //         [ 'status' => 403 ]
        //     );
        // }
		$order_restaurant_id = (int) $order->get_meta( '_restaurant_id' );
        $req_restaurant_id = isset( $params['restaurant_id'] ) ? (int) $params['restaurant_id'] : 0;
        if($params['order_type'] === 'Dine In')
        {
            $order_zone_id       = (int) $order->get_meta( '_zone_id' );
            $req_zone_id       = isset( $params['zone_id'] ) ? (int) $params['zone_id'] : 0;
            if ( $order_restaurant_id !== $req_restaurant_id || $order_zone_id !== $req_zone_id ) {
                return new WP_Error(
                    'mismatched_ids',
                    __( 'Restaurant or Zone ID does not match with order Restaurant or Zone ID.', 'pinaka-pos' ),
                    [ 'status' => 403 ]
                );
            }
        }
        else
        {
            if ( $order_restaurant_id !== $req_restaurant_id) {
                return new WP_Error(
                    'mismatched_ids',
                    __( 'Restaurant ID does not match with order Restaurant ID.', 'pinaka-pos' ),
                    [ 'status' => 403 ]
                );
            }
        }
		if ( ! isset( $params['order_amount'] ) || ! isset( $params['percent'] ) ) {
			return new WP_REST_Response( array(
				'error' => 'Missing parameters. Required: order_amount, percent'
			), 400 );
		}

		$order_amount   = floatval( $params['order_amount'] );
		$percent        = floatval( $params['percent'] );
		$discount_name  = isset( $params['discount_name'] ) ? sanitize_text_field( $params['discount_name'] ) : 'Others';

		if ( $order_amount < 0 || $percent < 0 ) {
			return new WP_REST_Response( array(
				'error' => 'order_amount and percent must be non-negative numbers.'
			), 400 );
		}

		$actual_total = floatval( $order->get_total() );
		if ( round( $order_amount, 2 ) !== round( $actual_total, 2 ) ) {
			return new WP_REST_Response( array(
				'error'        => 'Order amount mismatch',
				'passed_total' => $order_amount,
				'order_total'  => $actual_total,
			), 400 );
		}
		$discount_amount = ( $order_amount * $percent ) / 100.0;
		$discount_amount = round( $discount_amount, 2 );

		$response = array(
			'restaurant_id'   => $req_restaurant_id,
			'zone_id'		  => $req_zone_id,
			'order_id'        => $order_id,
			'fee_lines'       => array(
				array(
					'name'  => ucfirst($discount_name) . ' (' . $percent . '%)',
					'total' => '-' . $discount_amount,
				)
			)
		);
		return new WP_REST_Response( $response, 200 );
	}
}