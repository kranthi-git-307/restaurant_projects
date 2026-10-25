<?php
if (!defined('ABSPATH')) {
    exit;
}

class Pinaka_Kot_Orders_API_Controller 
{
    protected $namespace = 'pinaka-restaurant-pos/v1';
    protected $rest_base = 'kot';
    private $bar_categories = [ 'alcohol', 'bewerages','beverages' ];
    // private $customer_printer = 'front_desk';
    public function register_routes() 
    {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/get-parent-kot-orders',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_parent_kot_orders' ],
                'permission_callback' => [ $this, 'check_user_role_permission' ],
            ]
        );
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/kot-transfer',
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'handle_kot_transfer' ],
                'permission_callback' => [ $this, 'check_user_role_permission' ],
            ]
        );
        register_rest_route(
			$this->namespace,
            '/' . $this->rest_base . '/get-all-orders',
			[
				'methods'              => 'GET',
				'callback'             => [$this, 'get_all_orders'],
				'permission_callback'  => [$this, 'check_user_role_permission'],
            ]
		);
		register_rest_route(
			$this->namespace,
            '/' . $this->rest_base . '/get-all-tip-orders',
			[
				'methods'              => 'GET',
				'callback'             => [$this, 'get_all_tip_orders'],
				'permission_callback'  => [$this, 'check_user_role_permission'],
            ]
		);
        register_rest_route(
			$this->namespace,
            '/' . $this->rest_base . '/get-all-order-types',
			[
				'methods'              => 'GET',
				'callback'             => [$this, 'get_all_order_types'],
				'permission_callback'  => [$this, 'check_user_role_permission'],
            ]
		);
        register_rest_route(
			$this->namespace,
            '/' . $this->rest_base . '/order-filters-api',
			[
				'methods'              => 'GET',
				'callback'             => [$this, 'get_order_filters'],
				'permission_callback'  => [$this, 'check_user_role_permission'],
            ]
		);
		register_rest_route(
			$this->namespace,
            '/' . $this->rest_base . '/get-kot-status-count',
			[
				'methods'              => 'GET',
				'callback'             => [$this, 'get_kot_status_count'],
				'permission_callback'  => [$this, 'check_user_role_permission'],
            ]
		);
		register_rest_route(
			$this->namespace,
            '/' . $this->rest_base . '/get-status-wise-count',
			[
				'methods'              => 'GET',
				'callback'             => [$this, 'get_status_wise_count'],
				'permission_callback'  => [$this, 'check_user_role_permission'],
            ]
		);
		register_rest_route(
			$this->namespace,
            '/' . $this->rest_base . '/kitchen-display-orders',
			[
				'methods'              => 'GET',
				'callback'             => [$this, 'get_kitchen_display_orders'],
				'permission_callback'  => [$this, 'check_user_role_permission'],
            ]
		);
		register_rest_route(
			$this->namespace,
            '/' . $this->rest_base . '/get-kitchen-items-count',
			[
				'methods'              => 'GET',
				'callback'             => [$this, 'get_kitchen_items_count'],
				'permission_callback'  => [$this, 'check_user_role_permission'],
            ]
		);
		
        register_rest_route(
			$this->namespace,
            '/' . $this->rest_base . '/category-wise-kot-print',
			[
				'methods'              => 'GET',
				'callback'             => [$this, 'category_wise_kot_print'],
				'permission_callback'  => [$this, 'check_user_role_permission'],
            ]
		);
        register_rest_route(
			$this->namespace,
            '/' . $this->rest_base . '/get-order-by-table',
			[
				'methods'              => 'GET',
				'callback'             => [$this, 'get_order_by_table'],
				'permission_callback'  => [$this, 'check_user_role_permission'],
            ]
		);
        register_rest_route(
			$this->namespace,
            '/' . $this->rest_base . '/generate-email-template',
			[
				'methods'              => 'GET',
				'callback'             => [$this, 'get_dinein_table_order_kots'],
				'permission_callback'  => [$this, 'check_user_role_permission'],
            ]
		);
		
		register_rest_route(
			$this->namespace,
            '/' . $this->rest_base . '/get-completed-orders',
			[
				'methods'              => 'GET',
				'callback'             => [$this, 'get_all_completed_orders'],
				'permission_callback'  => [$this, 'check_user_role_permission'],
            ]
		);
		
		register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/order-status-counts',
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_active_order_count'],
                'permission_callback' => [$this, 'check_user_role_permission'],
            ]
        );
        register_rest_route(
			$this->namespace,
            '/' . $this->rest_base . '/get-order-types',
			[
				'methods'              => 'GET',
				'callback'             => [$this, 'get_order_types'],
				'permission_callback'  => [$this, 'check_user_role_permission'],
            ]
		);
		register_rest_route(
			$this->namespace,
            '/' . $this->rest_base . '/update-order-type',
			[
				'methods'              => 'POST',
				'callback'             => [$this, 'update_order_type'],
				'permission_callback'  => [$this, 'check_user_role_permission'],
            ]
		);
		register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/count',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'get_orders_count' ),
                    'permission_callback' => array( $this, 'check_user_role_permission'),
                ),
            )
        );
		register_rest_route(
			$this->namespace,
            '/' . $this->rest_base . '/update-cancel-item-status',
			[
				'methods'              => 'PUT',
				'callback'             => [$this, 'update_cancel_item_status'],
				'permission_callback'  => [$this, 'check_user_role_permission'],
            ]
		);
		register_rest_route(
			$this->namespace,
            '/' . $this->rest_base . '/update-kot-item-status',
			[
				'methods'              => 'PUT',
				'callback'             => [$this, 'update_kot_item_status'],
				'permission_callback'  => [$this, 'check_user_role_permission'],
            ]
		);
		register_rest_route(
			$this->namespace,
            '/' . $this->rest_base . '/update-generate-bill-status',
			[
				'methods'              => 'PUT',
				'callback'             => [$this, 'update_generate_bill_status'],
				'permission_callback'  => [$this, 'check_user_role_permission'],
            ]
		);
// 		register_rest_route(
// 			$this->namespace,
//             '/' . $this->rest_base . '/get-coupons-list',
// 			[
// 				'methods'              => 'GET',
// 				'callback'             => [$this, 'get_all_coupons_list'],
// 				'permission_callback'  => [$this, 'check_user_role_permission'],
//             ]
// 		);
    }
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
	// 	}
    //     elseif ( in_array( 'chef', (array) $user->roles ) ) {
	// 		return true;
	// 	}  
    //     else {
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
		global $wp_roles;
		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}
		$all_roles = array_keys( $wp_roles->get_names() );
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
	
	public function get_active_order_count(WP_REST_Request $request)
    {
        global $wpdb;
    
        $restaurant_id = absint($request->get_param('restaurant_id'));
    
        if (empty($restaurant_id)) {
            return new WP_Error(
                'missing_restaurant_id',
                'Restaurant ID is required',
                ['status' => 422]
            );
        }
    
       $timezone = wp_timezone();

        $today_start = new DateTime( 'today', $timezone );
        $today_end   = new DateTime( 'tomorrow', $timezone );
        
        $start_gmt = $today_start
            ->setTimezone( new DateTimeZone( 'UTC' ) )
            ->format( 'Y-m-d H:i:s' );
        
        $end_gmt = $today_end
            ->setTimezone( new DateTimeZone( 'UTC' ) )
            ->format( 'Y-m-d H:i:s' );
        
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "
                SELECT COUNT(DISTINCT o.id)
                FROM {$wpdb->prefix}wc_orders o
                INNER JOIN {$wpdb->prefix}wc_orders_meta om
                    ON o.id = om.order_id
                WHERE (o.parent_order_id = 0 OR o.parent_order_id IS NULL)
                AND o.status IN (
                    'wc-processing',
                    'wc-pending'
                )
                AND om.meta_key = '_restaurant_id'
                AND om.meta_value = %d
                AND o.date_created_gmt >= %s
                AND o.date_created_gmt < %s
                ",
                $restaurant_id,
                $start_gmt,
                $end_gmt
            )
        );
    
        return new WP_REST_Response([
            'success' => true,
            'message' => 'Active order count fetched successfully.',
            'restaurant_id' => $restaurant_id,
            'active_orders_count' => (int) $count
        ], 200);
    }
    public function get_all_order_types( $request ) 
    {
        $ordertypes = ['Dine In','Takeaways','Online Orders'];
        return new WP_REST_Response([
            'success'     => true,
            'order_types' => $ordertypes
        ]);
    }
    public function get_order_types( $request ) 
    {
        $ordertypes = ['Dine In','Swiggy Dine In','Zomato Dine In','Ubereats Dine In','Takeaways','Swiggy Takeaways','Zomato Takeaways','Ubereats Takeaways'];
        return new WP_REST_Response([
            'success'     => true,
            'order_types' => $ordertypes
        ]);
    }
    public function update_order_type( $request )
    {
        $order_id   = absint( $request->get_param( 'order_id' ) );
        $order_type = sanitize_text_field( $request->get_param( 'order_type' ) );
    
        if ( ! $order_id || ! $order_type ) {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'Order ID and Order Type are required.'
                ],
                400
            );
        }
    
        $order = wc_get_order( $order_id );
    
        if ( ! $order ) {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'Order not found.'
                ],
                404
            );
        }
    
        $allowed_types = ['Dine In','Swiggy Dine In','Zomato Dine In','Ubereats Dine In','Takeaways','Swiggy Takeaways','Zomato Takeaways','Ubereats Takeaways'];
    
        if ( ! in_array( $order_type, $allowed_types, true ) ) {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'Invalid order type.'
                ],
                400
            );
        }
    
        $order->update_meta_data(
            '_pinaka_order_type',
            $order_type
        );
    
        $order->save();
    
        return new WP_REST_Response(
            [
                'success'    => true,
                'message'    => 'Order type updated successfully.',
                'order_id'   => $order_id,
                'order_type' => $order_type,
            ]
        );
    }
    public function get_orders_count( WP_REST_Request $request ) {

        global $wpdb;
    
        $total_count = (int) $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->prefix}wc_orders
            WHERE type = 'shop_order'
            AND (parent_order_id = 0 OR parent_order_id IS NULL)
        ");
    
        return rest_ensure_response([
            'success' => true,
            'total_order_count' => $total_count,
        ]);
    }

    public function get_order_filters( $request ) 
    {
        global $wpdb;
        // === Get Parameters ===
        $params        = $request->get_params();
        $order_type    = $params['order_type'] ?? 'Dine In';
        $zone_id       = isset($params['zone_id']) ? intval($params['zone_id']) : 0;
 
        $captain_id    = isset($params['captain_id']) ? intval($params['captain_id']) : get_current_user_id();
        $restaurant_id = isset($params['restaurant_id']) ? intval($params['restaurant_id']) : (int) get_user_meta($captain_id, 'user_restaurant_id', true);

        // === Validations ===
        if ( empty($order_type) ) {
            return new WP_Error( 'missing_order_type', 'Order type is required.', [ 'status' => 422 ] );
        }

        // if ( empty($zone_id) && $order_type === 'Dine In' ) {
        //     return new WP_Error( 'missing_zone_id', 'Zone ID is required.', [ 'status' => 422 ] );
        // }

        if ( empty($restaurant_id) ) {
            return new WP_Error( 'missing_restaurant_id', 'Restaurant ID is required.', [ 'status' => 422 ] );
        }

        if ( empty($captain_id) ) {
            return new WP_Error( 'missing_captain_id', 'Captain ID is required.', [ 'status' => 422 ] );
        }

        // === Determine User Role Access ===
        $table_owner_name = '';
        $is_manager = true; // default
        
        $user = get_userdata( $captain_id );
        
        if ( $user && isset( $user->roles ) ) {
            $restricted_roles = [ 'captain', 'chef' ];
            foreach ( $restricted_roles as $role ) {
                if ( in_array( $role, (array) $user->roles, true ) ) {
                    $is_manager = false;
                    break;
                }
            }
        }
        // === Query Order IDs ===
        if ( $is_manager ) {
            // Manager: get all parent orders
            $parent_order_ids = $wpdb->get_col("
                SELECT id
                FROM {$wpdb->prefix}wc_orders
                WHERE parent_order_id = 0
            ");
            $child_parent_ids = [];
        } else {
            // Captain/Chef: get only their orders
            $parent_order_ids = $wpdb->get_col( $wpdb->prepare("
                SELECT id
                FROM {$wpdb->prefix}wc_orders
                WHERE customer_id = %d
                AND parent_order_id = 0
            ", $captain_id ) );

            $child_parent_ids = $wpdb->get_col( $wpdb->prepare("
                SELECT DISTINCT parent_order_id
                FROM {$wpdb->prefix}wc_orders
                WHERE customer_id = %d
                AND parent_order_id > 0
            ", $captain_id ) );

            $user_obj = get_user_by( 'id', $captain_id );
            if ( $user_obj ) 
            {
                $table_owner_name = trim( $user_obj->first_name . ' ' . $user_obj->last_name );
                if ( empty( $table_owner_name ) ) 
                {
                    $table_owner_name = $user_obj->display_name;
                }
            }
        }
        $all_parent_ids = array_unique( array_merge( $parent_order_ids, $child_parent_ids ) );
        $orders = [];
        //$order_ids = [];
        foreach ( $all_parent_ids as $pid ) {
            $order = wc_get_order( $pid );
            if ( $order && ! in_array( $order->get_status(), ['cancelled', 'failed', 'trash' ], true ) ) {
                $orders[] = $order;
                // $order_ids[] = $pid;
            }
        }

        $normalized_order_type = strtolower( $order_type );
        switch ( $normalized_order_type ) {
            case 'takeaways':
            case 'takeaway':
                $normalized_order_type = 'takeaway';
                break;
            case 'online orders':
            case 'online-order':
            case 'online order':
            case 'online':
                $normalized_order_type = 'online';
                break;
            case 'dine in':
            case 'dine-in':
            default:
                $normalized_order_type = 'dine in';
                break;
        }
        // === Filter by Type ===
        $orders = array_filter( $orders, function( $order ) use ( $normalized_order_type ) {
            if ( $normalized_order_type === 'takeaway' ) {
                return $order->get_created_via() === 'takeaway';
            }
            if ( $normalized_order_type === 'online' ) {
                return $order->get_created_via() === 'online';
            }
            return $order->get_created_via() === 'rest-api';
        });
        // === Filter by Zone (Only for Dine In) ===
        if ( $order_type === 'Dine In' && !empty($zone_id)) {
            $orders = array_filter( $orders, function( $order ) use ( $zone_id ) {
                return intval( $order->get_meta( '_zone_id' ) ) === $zone_id;
            });
        }
        
        // === Filter by Restaurant ===
        if ( $restaurant_id ) {
            $orders = array_filter( $orders, function( $order ) use ( $restaurant_id ) {
                return intval( $order->get_meta( '_restaurant_id' ) ) === $restaurant_id;
            });
        }
        
        // === Keep only today's active orders ===
        // $active_statuses = [ 'pending', 'processing','completed' ];
        // $orders = array_filter( $orders, function( $order ) use ( $restaurant_id, $active_statuses ) {
        $today = wp_date( 'Y-m-d', null, wp_timezone() );

        $orders = array_filter(
            $orders,
            function ( $order ) use ( $today_start, $today_end ) {
        
                $order_date = $order->get_date_created();
                if ( ! $order_date ) {
                    return false;
                }
                
                return $order_date->format( 'Y-m-d' ) === date('Y-m-d');
            }
        );
        
        $orders = array_values( $orders );

        // === Prepare Response ===
        $response = [];
        foreach ( $orders as $order ) {
            $order_id    = $order->get_id();
            
            $order_time  = $order->get_date_created() ? $order->get_date_created()->date( 'h:i A' ) : '';
            $zone_id     = $order_type === 'Dine In' ? (int) $order->get_meta( '_zone_id', true ) : 0;
            $zone_name   = $zone_id ? get_the_title( $zone_id ) : '';
            $restaurant  = (int) $order->get_meta( '_restaurant_id', true );
            if (
                'completed' === $order->get_status() &&
                'rest-api' === $order->get_created_via()
            ) {
                continue;
            }
            // Identify order type label
            if ( $order->get_created_via() === 'rest-api' ) {
                $order_types = 'Dine In';
            } elseif ( $order->get_created_via() === 'takeaway' ) {
                $order_types = 'Takeaways';
            } 
            elseif ( $order->get_created_via() === 'online' ) {
                $order_types = 'Online';
            }
            else {
                $order_types = '';
            }

            // === Get KOT info if needed ===
            if ( in_array( $normalized_order_type, [ 'dine in', 'takeaway', 'online' ], true ) ) {
                $kot_orders = $this->get_kot_child_orders_by_parent_id( $order_id );
                $min_kot_number = null;
                $customer_id = 0;

                if ( $kot_orders ) {
                    foreach ( $kot_orders as $kot_order ) {
                        $kot_number_str = $kot_order->get_meta( '_kot_number', true );
                        $kot_number = (int) preg_replace( '/\D/', '', $kot_number_str );

                        if ( $min_kot_number === null || $kot_number < $min_kot_number ) {
                            $customer_id = $kot_order->get_customer_id();
                            $min_kot_number = $kot_number;
                        }
                    }
                }

                $min_kot_number_formatted = $min_kot_number !== null ? 'KOT#' . str_pad( $min_kot_number, 4, '0', STR_PAD_LEFT ) : null;

                $table_id   = (int) $order->get_meta( '_table_id', true );
                $table_name = $table_id ? get_the_title( $table_id ) : '';

                if ( $customer_id > 0 ) {
                    $user = get_user_by( 'id', $customer_id );
                    if ( $user ) {
                        $table_owner_name = trim( $user->first_name . ' ' . $user->last_name );
                        if ( empty( $table_owner_name ) ) {
                            $table_owner_name = $user->display_name;
                        }
                    }
                }

                $order_data = [
                    'order_id'        => $order_id,
                    'order_time'      => $order_time,
                    'order_type'      => $order_types,
                    'status'          => $order->get_status(),
                    'total'           => (float) $order->get_total(),
                    'restaurant_id'   => $restaurant,
                    'remaining_count' => $kot_orders ? count($kot_orders) - 1 : 0,
                    'kot_number'      => $min_kot_number_formatted,
                    'table_owner'     => $table_owner_name,
                ];

                if ( $normalized_order_type === 'dine in' ) {
                    $order_data['table_id']   = $table_id;
                    $order_data['table_name'] = $table_name;
                    $order_data['zone_id']    = $zone_id;
                    $order_data['zone_name']  = $zone_name;
                }

                $response[] = $order_data;
            } else {
                $response[] = [
                    'order_id'      => $order_id,
                    'order_type'    => $order_types,
                    'order_time'    => $order_time,
                    'status'        => $order->get_status(),
                    'total'         => (float) $order->get_total(),
                    'zone_id'       => $zone_id,
                    'zone_name'     => $zone_name,
                    'restaurant_id' => $restaurant,
                ];
            }
        }

        return rest_ensure_response( $response );
    }
    // public function get_kot_status_count($request)
    // {
    //     global $wpdb;
    //     // === Get Parameters ===
    //     $params        = $request->get_params();
    //     $restaurant_id = isset($params['restaurant_id']) ? intval($params['restaurant_id']) : (int) get_user_meta(get_current_user_id(), 'user_restaurant_id', true);
    //     if ( empty($restaurant_id) ) {
    //         return new WP_Error( 'missing_restaurant_id', 'Restaurant ID is required.', [ 'status' => 422 ] );
    //     }

    //     $parent_order_ids = $wpdb->get_col("
    //         SELECT id
    //         FROM {$wpdb->prefix}wc_orders
    //         WHERE parent_order_id = 0
    //     ");
    //     $orders = [];
    //     foreach ( $parent_order_ids as $pid ) {
    //         $order = wc_get_order( $pid );
    //         if ( $order && in_array( $order->get_status(), [ 'processing','pending','completed' ], true ) ) {
    //             $orders[] = $order;
    //         }
    //     }
    //     if ( $restaurant_id ) {
    //         $orders = array_filter( $orders, function( $order ) use ( $restaurant_id ) {
    //             return intval( $order->get_meta( '_restaurant_id' ) ) === $restaurant_id;
    //         });
    //     }
    //     // $response = [];
    //     $status_count=0;
    //     foreach ( $orders as $order ) {

            // if ( $order->get_meta( '_parent_order' ) === 'yes' ) {
            //     continue;
            // }
    //         $kot_orders = $this->get_kot_child_orders_by_parent_id( $order->get_id() );

    //         if ( ! empty( $kot_orders ) ) {

    //             foreach ( $kot_orders as $kot_order ) {
    //                 $status = $kot_order->get_status();
    //                 if($status !== 'cancelled' || $status !== 'on-hold')
    //                 {
    //                     $status_count++;
    //                 }
    //             }
    //         }
            
    //     }
    //     $response = [
    //         'message' => 'kot status count fetched successfully',
    //         'restaurant_id' => $restaurant_id,
    //         'status_count'  => 0,
    //         'customer_count'  => 0,
    //     ];

    //     $customer_count = count(
    //         get_users(
    //             [
    //                 'role'       => 'customer',
    //                 'meta_key'   => 'user_restaurant_id',
    //                 'meta_value' => $restaurant_id,
    //                 'fields'     => 'ids',
    //             ]
    //         )
    //     );
    //     if($status_count > 0 || $customer_count > 0)
    //     {
    //         $response = [
    //             'message' => 'kot status count fetched successfully',
    //             'restaurant_id' => $restaurant_id,
    //             'status_count'  => $status_count,
    //             'customer_count'  => $customer_count,
    //         ];
    //     }
    //     return rest_ensure_response($response);
        
    // }
    public function get_kot_status_count( $request ) {
        global $wpdb;
    
        // =========================================================
        // Get Parameters
        // =========================================================
    
        $params = $request->get_params();
    
        $captain_id = isset( $params['captain_id'] )
            ? absint( $params['captain_id'] )
            : get_current_user_id();
    
        $restaurant_id = isset( $params['restaurant_id'] )
            ? absint( $params['restaurant_id'] )
            : absint(
                get_user_meta(
                    $captain_id,
                    'user_restaurant_id',
                    true
                )
            );
    
        // =========================================================
        // Validations
        // =========================================================
    
        if ( empty( $captain_id ) ) {
            return new WP_Error(
                'missing_captain_id',
                'Captain ID is required.',
                [ 'status' => 422 ]
            );
        }
    
        if ( empty( $restaurant_id ) ) {
            return new WP_Error(
                'missing_restaurant_id',
                'Restaurant ID is required.',
                [ 'status' => 422 ]
            );
        }
    
        // =========================================================
        // Manager Check
        // =========================================================
    
        $user = get_userdata( $captain_id );
        $is_manager = true;
        if ( $user && isset( $user->roles ) ) {
            $restricted_roles = [ 'captain', 'chef' ];
            foreach ( $restricted_roles as $role ) {
                if ( in_array( $role, (array) $user->roles, true ) ) {
                    $is_manager = false;
                    break;
                }
            }
        }
    
        // =========================================================
        // Today's Date Range
        // =========================================================
    
        /*
         * wc_orders.date_created_gmt is stored in GMT.
         *
         * Generate today's start/end using WordPress timezone
         * and convert them to GMT.
         */
        $timezone = wp_timezone();
    
        $start_local = new DateTimeImmutable(
            current_time( 'Y-m-d' ) . ' 00:00:00',
            $timezone
        );
    
        $end_local = $start_local->modify( '+1 day' );
    
        $start_gmt = $start_local
            ->setTimezone( new DateTimeZone( 'UTC' ) )
            ->format( 'Y-m-d H:i:s' );
    
        $end_gmt = $end_local
            ->setTimezone( new DateTimeZone( 'UTC' ) )
            ->format( 'Y-m-d H:i:s' );
    
        // =========================================================
        // Get Today's Parent Orders
        // =========================================================
    
        $parent_order_ids = $wpdb->get_col(
            $wpdb->prepare(
                "
                SELECT id
                FROM {$wpdb->prefix}wc_orders
                WHERE parent_order_id = 0
                AND date_created_gmt >= %s
                AND date_created_gmt < %s
                ORDER BY date_created_gmt DESC
                ",
                $start_gmt,
                $end_gmt
            )
        );
    
        // =========================================================
        // Get Valid Orders
        // =========================================================
    
        $orders = [];
        ;
        foreach ( $parent_order_ids as $parent_id ) {
    
            $order = wc_get_order( $parent_id );
    
            /*
             * IMPORTANT:
             * Check order exists BEFORE calling methods.
             */
            if ( ! $order ) {
                continue;
            }
            if (
                'completed' === $order->get_status() &&
                'rest-api' === $order->get_created_via()
            ) {
                continue;
            }
            // Skip orders created via REST API.
            // Allowed parent order statuses.
            if (
                ! in_array(
                    $order->get_status(),
                    [
                        'processing',
                        'pending',
                        'completed',
                    ],
                    true
                )
            ) {
                continue;
            }
    
            // Restaurant validation.
            if (
                absint( $order->get_meta( '_restaurant_id' ) )
                !== $restaurant_id
            ) {
                continue;
            }
    
            $orders[] = $parent_id;
        }

        // =========================================================
        // Calculate KOT Status Count
        // =========================================================
    
        $status_count = 0;
    
        foreach ( $orders as $order ) {
    
            $kot_orders = $this->get_kot_child_orders_by_parent_id(
                $order
            );
    
            if ( empty( $kot_orders ) ) {
                continue;
            }
            
            foreach ( $kot_orders as $kot_order ) {
    
                if ( ! $kot_order instanceof WC_Order ) {
                    continue;
                }
    
                $status = $kot_order->get_status();

                // Ignore cancelled and on-hold KOTs.
                if (
                    in_array(
                        $status,
                        [
                            'cancelled',
                            'on-hold',
                        ],
                        true
                    )
                ) {
                    continue;
                }
    
                $status_count++;
            }
        }
    
        // =========================================================
        // Customer Count
        // =========================================================
    
        $customer_ids = get_users(
            [
                'role'       => 'customer',
                'meta_key'   => 'user_restaurant_id',
                'meta_value' => $restaurant_id,
                'fields'     => 'ids',
            ]
        );
    
        $customer_count = count( $customer_ids );
    
        // =========================================================
        // Response
        // =========================================================
    
        return rest_ensure_response(
            [
                'message'        => 'KOT status count fetched successfully.',
                'restaurant_id'  => $restaurant_id,
                'captain_id'     => $captain_id,
                'is_manager'     => $is_manager,
                'status_count'   => $status_count,
                'customer_count' => $customer_count,
            ]
        );
    }
    // public function get_status_wise_count($request)
    // {
    //     global $wpdb;
    //     // === Get Parameters ===
    //     $params        = $request->get_params();
    //     $restaurant_id = isset($params['restaurant_id']) ? intval($params['restaurant_id']) : (int) get_user_meta(get_current_user_id(), 'user_restaurant_id', true);
    //     if ( empty($restaurant_id) ) {
    //         return new WP_Error( 'missing_restaurant_id', 'Restaurant ID is required.', [ 'status' => 422 ] );
    //     }

    //     $timezone = wp_timezone();

    //     $today_start = new DateTime( 'today', $timezone );
    //     $today_end   = new DateTime( 'tomorrow', $timezone );
        
    //     $start_gmt = $today_start->setTimezone( new DateTimeZone( 'UTC' ) )
    //                              ->format( 'Y-m-d H:i:s' );
        
    //     $end_gmt = $today_end->setTimezone( new DateTimeZone( 'UTC' ) )
    //                          ->format( 'Y-m-d H:i:s' );
        
    //     $parent_order_ids = $wpdb->get_col(
    //         $wpdb->prepare(
    //             "
    //             SELECT id
    //             FROM {$wpdb->prefix}wc_orders
    //             WHERE parent_order_id = 0
    //             AND date_created_gmt >= %s
    //             AND date_created_gmt < %s
    //             ",
    //             $start_gmt,
    //             $end_gmt
    //         )
    //     );
    //     $status_counts = [
    //         'message' => 'kot status count fetched successfully',
    //         'restaurant_id' => $restaurant_id,
    //         'queue'                => 0,
    //         'kitchen_preparing'    => 0,
    //         'kot_ready'            => 0,
    //         'served'               => 0,
    //         'cancelled'            => 0,
    //         'total_dinein_count'   => 0,
    //         'total_online_count'   => 0,
    //         'total_takeaway_count' => 0
    //     ];
    //     $orders = [];

    //     foreach ( $parent_order_ids as $pid ) {
        
    //         $order = wc_get_order( $pid );
        
    //         if ( ! $order ) {
    //             continue;
    //         }
        
    //         $status = $order->get_status();
        
    //         $is_takeaway = $order->get_meta( '_takeaway_order', true );
    //         $is_dinein   = $order->get_meta( '_parent_order', true );
        
    //         // Dine In: only processing and pending
    //         if ( $is_dinein ) {
        
    //             if ( ! in_array( $status, [ 'processing', 'pending' ], true ) ) {
    //                 continue;
    //             }
        
    //             $orders[] = $order;
    //             $status_counts['total_dinein_count']++;
    //         }
        
    //         // Takeaway: processing, pending and completed
    //         elseif ( $is_takeaway ) {
        
    //             if ( ! in_array( $status, [ 'processing', 'pending', 'completed' ], true ) ) {
    //                 continue;
    //             }
        
    //             $orders[] = $order;
    //             $status_counts['total_takeaway_count']++;
    //         }
        
    //         // Online: processing, pending and completed
    //         else {
        
    //             if ( ! in_array( $status, [ 'processing', 'pending', 'completed' ], true ) ) {
    //                 continue;
    //             }
        
    //             $orders[] = $order;
    //             $status_counts['total_online_count']++;
    //         }
    //     }
    //     if ( $restaurant_id ) {
    //         $orders = array_filter( $orders, function( $order ) use ( $restaurant_id ) {
    //             return intval( $order->get_meta( '_restaurant_id' ) ) === $restaurant_id;
    //         });
    //     }
        
        
        
    //     foreach ( $orders as $order ) {
        
    //         $kot_orders = $this->get_kot_child_orders_by_parent_id( $order->get_id() );
        
    //         if ( ! empty( $kot_orders ) ) {

    //             $allowed_statuses = [
    //                 'preparing',
    //                 'served',
    //                 'cancelled',
    //             ];
            
    //             $all_valid = true;
            
    //             // Check whether ALL KOTs have allowed statuses.
    //             foreach ( $kot_orders as $kot_order ) {
            
    //                 if ( ! in_array( $kot_order->get_status(), $allowed_statuses, true ) ) {
    //                     $all_valid = false;
    //                     break;
    //                 }
    //             }
            
    //             // Count only if ALL KOTs have allowed statuses.
    //             if ( $all_valid ) {
            
    //                 foreach ( $kot_orders as $kot_order ) {
            
    //                     switch ( $kot_order->get_status() ) {
            
    //                         case 'preparing':
    //                             $status_counts['kitchen_preparing']++;
    //                             break;
            
    //                         case 'served':
    //                             $status_counts['served']++;
    //                             break;
            
    //                         case 'cancelled':
    //                             $status_counts['cancelled']++;
    //                             break;
    //                     }
    //                 }
    //             }
    //         }
    //     }
        
    //     return $status_counts;
    // }
    public function get_status_wise_count( $request ) {

        global $wpdb;
    
        $params = $request->get_params();
    
        $restaurant_id = isset( $params['restaurant_id'] )
            ? intval( $params['restaurant_id'] )
            : (int) get_user_meta(
                get_current_user_id(),
                'user_restaurant_id',
                true
            );
    
        if ( empty( $restaurant_id ) ) {
            return new WP_Error(
                'missing_restaurant_id',
                'Restaurant ID is required.',
                [ 'status' => 422 ]
            );
        }
    
        /*
         * Today's date range in GMT.
         */
        $timezone = wp_timezone();
    
        $today_start = new DateTime( 'today', $timezone );
        $today_end   = new DateTime( 'tomorrow', $timezone );
    
        $start_gmt = $today_start
            ->setTimezone( new DateTimeZone( 'UTC' ) )
            ->format( 'Y-m-d H:i:s' );
    
        $end_gmt = $today_end
            ->setTimezone( new DateTimeZone( 'UTC' ) )
            ->format( 'Y-m-d H:i:s' );
    
    
        /*
         * Get today's parent orders.
         */
        $parent_order_ids = $wpdb->get_col(
            $wpdb->prepare(
                "
                SELECT id
                FROM {$wpdb->prefix}wc_orders
                WHERE parent_order_id = 0
                AND date_created_gmt >= %s
                AND date_created_gmt < %s
                ",
                $start_gmt,
                $end_gmt
            )
        );
    
    
        $status_counts = [
            'message'                => 'KOT status count fetched successfully',
            'restaurant_id'          => $restaurant_id,
            'queue'                  => 0,
            'kitchen_preparing'      => 0,
            'kot_ready'              => 0,
            'served'                 => 0,
            'cancelled'              => 0,
            'total_dinein_count'     => 0,
            'total_online_count'     => 0,
            'total_takeaway_count'   => 0,
        ];
    
    
        foreach ( $parent_order_ids as $parent_order_id ) {
    
            $order = wc_get_order( $parent_order_id );
    
            if ( ! $order ) {
                continue;
            }
    
            /*
             * Restaurant check first.
             */
            if (
                (int) $order->get_meta( '_restaurant_id' ) !== $restaurant_id
            ) {
                continue;
            }
    
            $status = $order->get_status();
    
            $is_dinein = ! empty(
                $order->get_meta( '_parent_order', true )
            );
    
            $is_takeaway = ! empty(
                $order->get_meta( '_takeaway_order', true )
            );
    
    
            /*
             * Validate parent order status.
             */
            if ( $is_dinein ) {
    
                if (
                    ! in_array(
                        $status,
                        [ 'processing', 'pending' ],
                        true
                    )
                ) {
                    continue;
                }
    
            } else {
    
                /*
                 * Takeaway / Online
                 */
                if (
                    ! in_array(
                        $status,
                        [ 'processing', 'pending', 'completed' ],
                        true
                    )
                ) {
                    continue;
                }
            }
    
    
            /*
             * Get child KOT orders.
             */
            $kot_orders = $this->get_kot_child_orders_by_parent_id(
                $parent_order_id
            );
    
    
            /*
             * Check whether all KOTs are served.
             *
             * If all KOTs are served:
             * - Don't count takeaway
             * - Don't count online
             * - Don't count dine-in
             * - Don't count KOT statuses
             */
            $all_served = ! empty( $kot_orders );
    
            foreach ( $kot_orders as $kot_order ) {
    
                if ( $kot_order->get_status() !== 'served' ) {
                    $all_served = false;
                    break;
                }
            }
    
            if ( $all_served ) {
                continue;
            }
    
    
            /*
             * Order type count.
             */
            if ( $is_dinein ) {
    
                $status_counts['total_dinein_count']++;
    
            } elseif ( $is_takeaway ) {
    
                $status_counts['total_takeaway_count']++;
    
            } else {
    
                $status_counts['total_online_count']++;
            }
    
    
            /*
             * KOT status counts.
             */
            if ( empty( $kot_orders ) ) {
                continue;
            }
    
    
            $allowed_statuses = [
                'preparing',
                'served',
                'cancelled',
            ];
    
    
            /*
             * Only process when ALL KOTs
             * contain allowed statuses.
             */
            $all_valid = true;
    
            foreach ( $kot_orders as $kot_order ) {
    
                if (
                    ! in_array(
                        $kot_order->get_status(),
                        $allowed_statuses,
                        true
                    )
                ) {
                    $all_valid = false;
                    break;
                }
            }
    
            if ( ! $all_valid ) {
                continue;
            }
    
    
            /*
             * Count KOT statuses.
             */
            foreach ( $kot_orders as $kot_order ) {
    
                switch ( $kot_order->get_status() ) {
    
                    case 'preparing':
                        $status_counts['kitchen_preparing']++;
                        break;
    
                    case 'served':
                        $status_counts['served']++;
                        break;
    
                    case 'cancelled':
                        $status_counts['cancelled']++;
                        break;
                }
            }
        }
    
    
        return $status_counts;
    }
    public function get_all_completed_orders( $request ) {

        global $wpdb;
    
        $params = $request->get_params();
    
        $page     = isset( $params['page'] ) ? max( 1, intval( $params['page'] ) ) : 1;
        $per_page = isset( $params['per_page'] ) ? max( 1, intval( $params['per_page'] ) ) : 20;
    
        $restaurant_id = isset( $params['restaurant_id'] )
            ? intval( $params['restaurant_id'] )
            : 0;
    
        $search_order_id = isset( $params['order_id'] )
            ? intval( $params['order_id'] )
            : 0;
    
        $search_order_type = isset( $params['order_type'] )
            ? sanitize_text_field( $params['order_type'] )
            : '';
    
        $from_date = isset( $params['from_date'] )
            ? sanitize_text_field( $params['from_date'] )
            : '';
    
        $to_date = isset( $params['to_date'] )
            ? sanitize_text_field( $params['to_date'] )
            : '';
        $status = isset( $params['status'] )
            ? sanitize_text_field( $params['status'] )
            : '';
        $status = strtolower($status);
        if($status === "completed")
        {
            $status = "served";
        }
        $prep_filter = isset( $params['prep_time'] )
            ? sanitize_text_field( $params['prep_time'] )
            : '';
        
        if ( empty( $from_date ) && empty( $to_date ) ) {

            $to_date = current_time( 'Y-m-d' );
            $from_date = current_time( 'Y-m-d' );
        }
        $offset = ( $page - 1 ) * $per_page;
    
        $where = " WHERE o.parent_order_id = 0 ";
        $join  = '';
        
        if ( $search_order_id > 0 ) {
        
            $where .= $wpdb->prepare(
                " AND o.id = %d",
                $search_order_id
            );
        }
        
        if ( ! empty( $from_date ) ) {
        
            $where .= $wpdb->prepare(
                " AND o.date_created_gmt >= %s",
                gmdate( 'Y-m-d 00:00:00', strtotime( $from_date ) )
            );
        }
        
        if ( ! empty( $to_date ) ) {
        
            $where .= $wpdb->prepare(
                " AND o.date_created_gmt <= %s",
                gmdate('Y-m-d 23:59:59', strtotime($to_date))
            );
        }
        
        /**
         * Restaurant filter in SQL
         */
        if ( $restaurant_id > 0 ) {
        
            $join .= $wpdb->prepare(
                "
                INNER JOIN {$wpdb->prefix}wc_orders_meta rm
                    ON rm.order_id = o.id
                    AND rm.meta_key = '_restaurant_id'
                    AND rm.meta_value = %d
                ",
                $restaurant_id
            );
        }
        $join .= "
            LEFT JOIN {$wpdb->prefix}wc_orders_meta created_meta
                ON created_meta.order_id = kot.id
                AND created_meta.meta_key = '_kot_created_time'

            LEFT JOIN {$wpdb->prefix}wc_orders_meta served_meta
                ON served_meta.order_id = kot.id
                AND served_meta.meta_key = '_served_time'
            ";
        $prep_where = '';

        if (!empty($prep_filter) && is_numeric($prep_filter)) {
            
            if($prep_filter === "0.5" || $prep_filter == "0.5")
            {
                $minutes_val = 30;
            }
            else
            {
                $minutes_val = ((int) $prep_filter) * 60;
            }
        
            $prep_where = $wpdb->prepare(
                "
                AND TIMESTAMPDIFF(
                    MINUTE,
                    created_meta.meta_value,
                    served_meta.meta_value
                ) <= %d
                ",
                $minutes_val
            );
        }
        if ( ! empty( $search_order_type ) ) {

            switch ( strtolower( trim( $search_order_type ) ) ) {
        
                case 'dine in':
                case 'dinein':
                case 'dine-in':
                    $where .= "
                        AND EXISTS (
                            SELECT 1
                            FROM {$wpdb->prefix}wc_orders_meta om
                            WHERE om.order_id = o.id
                            AND om.meta_key = '_parent_order'
                        )
                    ";
                    break;
        
                case 'takeaway':
                case 'takeaways':
                case 'take-aways':
                case 'take-away':
                    $where .= "
                        AND EXISTS (
                            SELECT 1
                            FROM {$wpdb->prefix}wc_orders_meta om
                            WHERE om.order_id = o.id
                            AND om.meta_key = '_takeaway_order'
                        )
                    ";
                    break;
        
                case 'online':
        
                    $where .= "
                        AND EXISTS (
                            SELECT 1
                            FROM {$wpdb->prefix}wc_orders_meta om
                            WHERE om.order_id = o.id
                            AND om.meta_key = '_online_order'
                        )
                    ";
                    break;
            }
        }

        $status_condition = '';

        if ($status === 'served') {
        
            $status_condition = " AND kot.status = 'served' ";
        
        } elseif ($status === 'cancelled') {
        
            $status_condition = " AND kot.status = 'wc-cancelled' ";
        
        } else {
        
            $status_condition = " AND kot.status IN ('served','wc-cancelled') ";
        }
        // $count_where = $where;
        // $kot_exists = "
        //     AND EXISTS (
        //         SELECT 1
        //         FROM {$wpdb->prefix}wc_orders kot
        //         WHERE kot.parent_order_id = o.id
        //         {$status_condition}
        // ";
        
        // if ( ! empty( $from_date ) ) {
        
        //     $kot_exists .= $wpdb->prepare(
        //         " AND kot.date_created_gmt >= %s",
        //         gmdate( 'Y-m-d 00:00:00', strtotime( $from_date ) )
        //     );
        // }
        
        // if ( ! empty( $to_date ) ) {
        
        //     $kot_exists .= $wpdb->prepare(
        //         " AND kot.date_created_gmt <= %s",
        //         gmdate('Y-m-d 23:59:59', strtotime($to_date))
        //     );
        // }
        
        // $kot_exists .= ")";
        
        // $where .= $kot_exists;

        $total_items = $wpdb->get_var("
            SELECT COUNT(DISTINCT kot.id)

            FROM {$wpdb->prefix}wc_orders kot

            INNER JOIN {$wpdb->prefix}wc_orders o
                ON o.id = kot.parent_order_id

            {$join}

            {$where}

            AND kot.status IN ('served','wc-cancelled')

            {$prep_where}
        ");
        $completed_count = (int) $wpdb->get_var("
            SELECT COUNT(DISTINCT kot.id)
            FROM {$wpdb->prefix}wc_orders kot
            INNER JOIN {$wpdb->prefix}wc_orders o
                ON o.id = kot.parent_order_id
            {$join}
            {$where}
            AND kot.status IN ('served')
            {$prep_where}
        ");
        
        $cancelled_count = (int) $wpdb->get_var("
            SELECT COUNT(DISTINCT kot.id)
            FROM {$wpdb->prefix}wc_orders kot
            INNER JOIN {$wpdb->prefix}wc_orders o
                ON o.id = kot.parent_order_id
            {$join}
            {$where}
            AND kot.status IN ('wc-cancelled')
            {$prep_where}
        ");
        if ($status === 'cancelled') {
            $total_items = $cancelled_count;
            $completed_count = 0;
        }
        
        if ($status === 'served') {
            $total_items = $completed_count;
            $cancelled_count = 0;
        }
        $total_pages = max(1, ceil($total_items / $per_page));

        if ($page > $total_pages) {
            $page = $total_pages;
        }
        
        $offset = ($page - 1) * $per_page;
        $served_kots = $wpdb->get_results(
            $wpdb->prepare(
                "
                SELECT
                    kot.id,
                    kot.parent_order_id

                FROM {$wpdb->prefix}wc_orders kot

                INNER JOIN {$wpdb->prefix}wc_orders o
                    ON o.id = kot.parent_order_id

                {$join}

                {$where}

                {$status_condition}

                {$prep_where}

                ORDER BY kot.date_updated_gmt DESC

                LIMIT %d OFFSET %d
                ",
                $per_page,
                $offset
            )
        );
        
        $response = [];
        // $completed_count = 0;
        // $cancelled_count = 0;
        $order_types = [
          'rest-api' => 'Dine In',
          'takeaway' => 'Takeaways',
          'online'   => 'Online'
        ];
        foreach ( $served_kots as $kot_row ) {
        
            $kot_order = wc_get_order( $kot_row->id );
        
            if ( ! $kot_order ) {
                continue;
            }
        
            $parent_order = wc_get_order(
                $kot_row->parent_order_id
            );
        
            if ( ! $parent_order ) {
                continue;
            }
            $status = $kot_order->get_status();

            // if ($status === 'served') {
            
            //     $completed_count++;
            
            // } elseif (
            //     $status === 'wc-cancelled' ||
            //     $status === 'cancelled'
            // ) {
            
            //     $cancelled_count++;
            // }
            // Order Type
            if (isset($order_types[$parent_order->get_created_via()]))
            {
        
                $order_type = $order_types[$parent_order->get_created_via()];
            }
            else
            {
                $order_type = 'NA';
            }
        
            // Table
            $table_id = (int) $parent_order->get_meta(
                '_table_id',
                true
            );
        
            $table_name = $table_id
                ? get_the_title( $table_id )
                : '-';
        
            $kot_number = $kot_order->get_meta(
                '_kot_number',
                true
            );
        
            $created_time = $kot_order->get_meta(
                '_kot_created_time',
                true
            );
        
            $served_time = $kot_order->get_meta(
                '_served_time',
                true
            );
            // Finished Time
            $finished_time = '';
        
            if ($kot_order->get_status() === 'cancelled') {

                $datetime = new DateTime(
                    $created_time,
                    wp_timezone()
                );
                
                $finished_time = $datetime->format(
                    get_option('date_format') . ' ' . get_option('time_format')
                );

            } else {

                $finished_time =
                    !empty($served_time)
                    ? $served_time
                    : '';
                if ( ! empty( $finished_time ) ) {
                    $datetime = new DateTime(
                        $finished_time,
                        wp_timezone()
                    );
                    
                    $finished_time = $datetime->format(
                        get_option('date_format') . ' ' . get_option('time_format')
                    );
                }
            }
            // Prep Time
            $prep_time = '';
        
            if (
                ! empty( $created_time ) &&
                ! empty( $served_time )
            ) {
        
                $seconds =
                    strtotime( $served_time )
                    -
                    strtotime( $created_time );
        
                $minutes = max(
                    1,
                    floor( $seconds / 60 )
                );
        
                $prep_time = $minutes . ' Min';
            }
            $response[] = [
                'order_id'       => $parent_order->get_id(),
                'order_type'     => $order_type,
                'table_id'       => $table_id,
                'table_name'     => $table_name,
        
                'kot_order_id'   => $kot_order->get_id(),
                'kot_number'     => $kot_number,
        
                'finished_time'  => $finished_time,
                'prep_time'      => $prep_time,
        
                'status' => $status === 'served'
                ? 'Completed'
                : ucfirst($status),
        
                'restaurant_id'  => (int) $parent_order->get_meta(
                    '_restaurant_id',
                    true
                ),
                'zone_id'  => (int) $parent_order->get_meta(
                    '_zone_id',
                    true
                ),
        
        
                'can_recall'     => $status === 'served' ? true : false,
            ];
            
        }
        $total_kots = $total_items;
        return rest_ensure_response( [
            'success'       => true,
            'current_page'  => $page,
            'per_page'      => $per_page,
            'total_items'   => $total_items,
            'total_pages'   => ceil( $total_items / $per_page ),
            'total_kots'       => (int)$total_kots,
            'completed_count'     => (int)$completed_count,
            'cancelled_count'  => (int)$cancelled_count,
            'data'          => $response,
        ] );
    }
    public function get_kitchen_display_orders( $request ) 
    {
        global $wpdb;
        // === Get Parameters ===
        $params        = $request->get_params();
        $restaurant_id = isset($params['restaurant_id']) ? intval($params['restaurant_id']) : (int) get_user_meta($captain_id, 'user_restaurant_id', true);
    
        if ( empty($restaurant_id) ) {
            return new WP_Error( 'missing_restaurant_id', 'Restaurant ID is required.', [ 'status' => 422 ] );
        }
    
        // Manager: get all parent orders
        $parent_order_ids = $wpdb->get_col("
            SELECT id
            FROM {$wpdb->prefix}wc_orders
            WHERE parent_order_id = 0 order by id desc
        ");
        $orders = [];

        $allowed_statuses = [ 'pending', 'processing', 'completed' ];

        foreach ( $parent_order_ids as $pid ) {
            $order = wc_get_order( $pid );
        
            if ( $order && in_array( $order->get_status(), $allowed_statuses, true ) ) {
                $orders[] = $order;
            }
        }
        // === Keep only today's active orders ===
        $orders = array_filter( $orders, function( $order ) use ( $restaurant_id ) {
            $matches_restaurant = intval( $order->get_meta( '_restaurant_id' ) ) === $restaurant_id;
            return $matches_restaurant;
        });

        // die;
        // $statuses = array( 'yet-to-prepare','preparing', 'ready', 'served' );
        $statuses = array( 'preparing' );
        $placeholders = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );
        
        $child_rows = $wpdb->get_results(
            $wpdb->prepare(
                "
                SELECT id, parent_order_id
                FROM {$wpdb->prefix}wc_orders
                WHERE parent_order_id > 0
                AND status IN ($placeholders)
                ",
                ...$statuses
            ),
            ARRAY_A
        );
        
        $children_by_parent = [];
        
        foreach ( $child_rows as $row ) {
            $children_by_parent[ (int) $row['parent_order_id'] ][] = (int) $row['id'];
        }
        
        // === Prepare Response ===
        $response = [];
        foreach ( $orders as $order ) {
            $order_id    = $order->get_id();
            
            $order_time  = $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d h:i A' ) : '';
            $zone_id     = (int) $order->get_meta( '_zone_id', true ) ?: 0;
            $zone_name   = $zone_id ? get_the_title( $zone_id ) : '';
            $restaurant  = (int) $order->get_meta( '_restaurant_id', true );
    
            // Identify order type label
            if ( $order->get_created_via() === 'rest-api' ) {
                $order_types = 'Dine In';
            } elseif ( $order->get_created_via() === 'takeaway' ) {
                $order_types = 'Takeaways';
            } elseif ( $order->get_created_via() === 'online' ) {
                $order_types = 'Online';
            }
            else{
                $order_types = 'N/A';
            }
            $created_via = $order->get_created_via();
            $kot_orders_data = [];
            if ( empty( $children_by_parent[ $order_id ] ) ) {
                continue;
            }
            
            $children = $children_by_parent[ $order_id ];
            
            foreach ( $children as $child_id ) {
            
                $child_order = wc_get_order( $child_id );
            
                if ( ! $child_order ) {
                    continue;
                }
                $items = $child_order->get_items();
            
                if ( empty( $items ) ) {
                    continue;
                }
            
                $line_items = [];
            
                foreach ( $items as $item_id => $item ) {
            
                    $product = $item->get_product();
            
                    $price = $product
                        ? wc_get_price_to_display( $product )
                        : 0;
            
                    $modifier_amount = 0;
                    $all_modifiers   = [];
                    $modifier_notes  = '';
                    $modifiers = [];
                    $addons    = [];
                    $is_veg = null;

                    if ( $product ) {
                        $tag_product_id = $product->is_type( 'variation' )
                            ? $product->get_parent_id()
                            : $product->get_id();

                        $product_tags = wp_get_post_terms(
                            $tag_product_id,
                            'product_tag',
                            [ 'fields' => 'names' ]
                        );

                        if ( ! is_wp_error( $product_tags ) ) {
                            foreach ( $product_tags as $tag_name ) {
                                $tag_name = strtolower( $tag_name );

                                if ( false !== strpos( $tag_name, 'non-vegetarian' ) ) {
                                    $is_veg = false;
                                    break;
                                }

                                if ( false !== strpos( $tag_name, 'vegetarian' ) ) {
                                    $is_veg = true;
                                    break;
                                }
                            }
                        }
                    }
                    foreach ( $item->get_meta_data() as $meta ) {
            
                        $meta_key   = $meta->key;
                        $meta_value = $meta->value;
                        
                        // if (
                        //     in_array( $created_via, [ 'orderout', 'online' ], true )
                        //     &&
                        //     '_addons' === $meta_key
                        //     &&
                        //     is_array( $meta_value )
                        // ) {
                    
                        //     foreach ( $meta_value as $mod ) {
                    
                        //         $modifier_id = ! empty( $mod['modifier_id'] )
                        //             ? absint( $mod['modifier_id'] )
                        //             : 0;
                    
                        //         /*
                        //          * First preference:
                        //          * Use saved modifier_id.
                        //          */
                        //         if ( $modifier_id ) {
                    
                        //             $modifier_post = get_post( $modifier_id );
                    
                        //             if (
                        //                 $modifier_post
                        //                 &&
                        //                 'modifiers' === $modifier_post->post_type
                        //             ) {
                        //                 $modifiers[] = $modifier_post->post_title;
                        //                 continue;
                        //             }
                        //         }
                    
                        //         /*
                        //          * Fallback:
                        //          * Search modifiers CPT by name.
                        //          */
                        //         if ( ! empty( $mod['name'] ) ) {
                    
                        //             $modify_name = trim( $mod['name'] );
                    
                        //             $modifier_id = $wpdb->get_var(
                        //                 $wpdb->prepare(
                        //                     "
                        //                     SELECT ID
                        //                     FROM {$wpdb->posts}
                        //                     WHERE post_type = %s
                        //                     AND post_title = %s
                        //                     LIMIT 1
                        //                     ",
                        //                     'modifiers',
                        //                     $modify_name
                        //                 )
                        //             );
                    
                        //             if ( $modifier_id ) {
                        //                 $modifiers[] = get_the_title( $modifier_id );
                        //             }
                        //         }
                        //     }
                        // }
                        if (
                            in_array(
                                $meta_key,
                                [ '_addons', '_modifiers' ],
                                true
                            ) &&
                            is_array( $meta_value )
                        ) {
                            
                            foreach ( $meta_value as $mod ) {

                                if ( ! empty( $mod['id'] ) ) {
                            
                                    $modifier = get_post( $mod['id'] );
                            
                                    if ( ! $modifier ) {
                                        continue;
                                    }
                            
                                    $modifier_type = get_post_meta( $modifier->ID, 'modifier_type', true );
                            
                                    if ( 'modifier' === $modifier_type ) {
                                        $modifiers[] = $modifier->post_title;
                                    } elseif ( 'add-on' === $modifier_type ) {
                                        $addons[] = $modifier->post_title;
                                    }
                            
                                } elseif ( ! empty( $mod['name'] ) ) {
                                    $modify_name = trim($mod['name']);
                                    // Lookup the modifier by its title.
                                    $modifier_id = $wpdb->get_var(
                                        $wpdb->prepare(
                                            "SELECT ID
                                             FROM {$wpdb->posts}
                                             WHERE post_type = %s
                                               AND post_title = %s
                                             LIMIT 1",
                                            'modifier',
                                            $modify_name
                                        )
                                    );
                                    
                                    if ( ! $modifier_id ) {
                                        continue;
                                    }
                            
                                    $modifier_type = get_post_meta( $modifier_id, 'modifier_type', true );
                            
                                    if ( 'modifier' === $modifier_type ) {
                                        $modifiers[] = $modify_name;
                                    } elseif ( 'add-on' === $modifier_type ) {
                                        $addons[] = $modify_name;
                                    }
                                }
                            }
                        }
            
                        if ( '_extra_modifier_amount' === $meta_key ) {
                            $modifier_amount += (float) $meta_value;
                        }
                        if ( '_modifier_notes' === $meta_key ) {
                            $modifier_notes = is_scalar( $meta_value )
                                ? (string) $meta_value
                                : $meta_value;
                        }
                    }
  
                    $combo_items = [];
            
                    if ( $product && $product->is_type( 'combo_bundle' ) ) {
            
                        $combo_meta = get_post_meta(
                            $product->get_id(),
                            '_combo_bundle_items',
                            true
                        );
            
                        $combo_meta = json_decode( $combo_meta, true );
            
                        if ( ! empty( $combo_meta ) ) {
            
                            foreach ( $combo_meta as $combo_item ) {
            
                                $combo_product = wc_get_product(
                                    $combo_item['id']
                                );
            
                                if ( $combo_product ) {
            
                                    $combo_items[] = [
                                        'id'       => $combo_product->get_id(),
                                        'name'     => $combo_product->get_name(),
                                        'price'    => wc_get_price_to_display(
                                            $combo_product
                                        ),
                                        'quantity' => (int) $combo_item['qty'],
                                    ];
                                }
                            }
                        }
                    }
            
                    $amount = (float) $item->get_total() + $modifier_amount;
            
                    $line_items[] = [
                        'id'         => $item_id,
                        'product_id' => $product ? $product->get_id() : 0,
                        'item_name'  => $item->get_name(),
                        'is_served'  => wc_get_order_item_meta( $item_id, '_kot_item_status', true ) ? true : false,
                        'is_veg'     => $is_veg,
                        'quantity'   => $item->get_quantity(),
                        'price'      => (float) $price,
                        'amount'     => $amount,
                        // 'modifiers'  => $all_modifiers,
                        'modifiers'  => $modifiers,
                        'modifier_notes' => $modifier_notes,
                        'addons'     => $addons,
                        'combos'     => $combo_items,
                    ];
                }
            
                $customer_id = $child_order->get_customer_id();
            
                $created_by = 'guest';
            
                if ( $customer_id ) {
            
                    $user = get_userdata( $customer_id );
            
                    if ( $user ) {
                        $created_by = $user->display_name;
                    }
                }
            
                $kot_orders_data[] = [
                    'id'         => $child_order->get_id(),
                    'time'       => $child_order->get_date_created()
                        ? $child_order->get_date_created()->date( 'Y-m-d h:i A' )
                        : '',
                    'status'     => wc_get_order_status_name(
                        $child_order->get_status()
                    ),
                    'total'      => (float) $child_order->get_total(),
                    'kot_number' => $child_order->get_meta(
                        '_kot_number',
                        true
                    ),
                    'kot_order_status' => $child_order->get_meta(
                        '_kot_order_status',
                        true
                    ),
                    'order_by'   => $created_by,
                    'line_items' => $line_items,
                ];
            }
            
            /**
             * Skip parent order completely if there are
             * no KOT Processed child orders.
             */
            if ( empty( $kot_orders_data ) ) {
                continue;
            }
            $table_id = intval( $order->get_meta('_table_id') );

            $table_post = get_post( $table_id );
    
            $table_name = $table_post ? $table_post->post_title : '';
            foreach ( $kot_orders_data as $index => $kot_data ) {
        
                $order_data = [
                    'order_id'      => $order_id,
                    'order_time'    => $order_time,
                    'order_type'    => $order_types,
                    'status'        => $order->get_status(),
                    'total'         => (float) $order->get_total(),
                    'restaurant_id' => $restaurant,
                ];
                
                if ( $order->get_created_via() === 'rest-api' ) {
                
                    $order_data['table_id']   = $table_id;
                    $order_data['table_name'] = $table_name;
                    $order_data['zone_id']    = $zone_id;
                    $order_data['zone_name']  = $zone_name;
                }
                
                /**
                 * Add KOT details after the condition
                 */
                $order_data['kot_id']        = $kot_data['id'];
                $order_data['kot_number']    = $kot_data['kot_number'];
                $order_data['kot_order_status'] = ucfirst($kot_data['kot_order_status']);
                $order_data['kot_time']      = $kot_data['time'];
                $order_data['kot_status']    = $kot_data['status'];
                $order_data['kot_total']     = $kot_data['total'];
                $order_data['kot_order_by']  = $kot_data['order_by'];
                $order_data['kot_items']     = $kot_data['line_items'];
               
                $response[] = $order_data;
            }
        }
        // =========================================================
        // 1. Aggregate pending products and quantities from yet-to-prepare KOTs.
        // =========================================================
        
        $pending_products = [];
        // $total_pending_items = 0;

        $statuses = [ 'preparing' ];
        
        $placeholders = implode(
            ', ',
            array_fill( 0, count( $statuses ), '%s' )
        );
        
        $child_rows = $wpdb->get_results(
            $wpdb->prepare(
                "
                SELECT id, parent_order_id
                FROM {$wpdb->prefix}wc_orders
                WHERE parent_order_id > 0
                AND status IN ($placeholders)
                ",
                ...$statuses
            ),
            ARRAY_A
        );
        
        foreach ( $child_rows as $child_row ) {
        
            $kot_order = wc_get_order( $child_row['id'] );
        
            if ( ! $kot_order ) {
                continue;
            }
        
            if (
                ! empty( $restaurant_id ) &&
                (int) $kot_order->get_meta( '_restaurant_id', true ) !== (int) $restaurant_id
            ) {
                continue;
            }
        
            foreach ( $kot_order->get_items() as $item_id => $item ) {

                $product = $item->get_product();
        
                if ( ! $product ) {
                    continue;
                }
        
                $product_id = $product->is_type( 'variation' )
                    ? $product->get_parent_id()
                    : $product->get_id();
        
                if ( ! $product_id ) {
                    continue;
                }

                $quantity = (int) $item->get_quantity();

                if ( $quantity <= 0 ) {
                    continue;
                }

                if ( ! isset( $pending_products[ $product_id ] ) ) {
                    $is_veg = null;
                    $tags   = wp_get_post_terms(
                        $product_id,
                        'product_tag',
                        [ 'fields' => 'names' ]
                    );

                    if ( ! is_wp_error( $tags ) ) {
                        foreach ( $tags as $tag_name ) {
                            $tag_name = strtolower( $tag_name );

                            if ( false !== strpos( $tag_name, 'non-vegetarian' ) ) {
                                $is_veg = false;
                                break;
                            }

                            if ( false !== strpos( $tag_name, 'vegetarian' ) ) {
                                $is_veg = true;
                                break;
                            }
                        }
                    }

                    $pending_products[ $product_id ] = [
                        'product_id' => (int) $product_id,
                        'item_name'  => $product->get_name(),
                        'quantity'   => 0,
                        'is_veg'     => $is_veg,
                    ];
                }

                $pending_products[ $product_id ]['quantity'] += $quantity;
                // $total_pending_items += $quantity;
            }
        }
        
        // =========================================================
        // 2. Group pending products by category for the item queue.
        // =========================================================
        
        // $category_products = [];
        
        // foreach ( $pending_products as $product_id => $product_data ) {
        
        //     $product_categories = wp_get_post_terms(
        //         $product_id,
        //         'product_cat',
        //         [
        //             'fields' => 'all',
        //         ]
        //     );
        
        //     if ( is_wp_error( $product_categories ) ) {
        //         continue;
        //     }
        
        //     $top_level_categories = [];

        //     foreach ( $product_categories as $category ) {

        //         while ( $category->parent > 0 ) {
        //             $parent_category = get_term( $category->parent, 'product_cat' );

        //             if ( is_wp_error( $parent_category ) || ! $parent_category ) {
        //                 break;
        //             }

        //             $category = $parent_category;
        //         }

        //         $top_level_categories[ $category->term_id ] = $category;
        //     }
        //     $top_level_category_slugs = wp_list_pluck(
        //         $top_level_categories,
        //         'slug'
        //     );

        //     // if ( array_intersect( [ 'alcohol', 'bewerages', 'beverages' ], $top_level_category_slugs ) ) {
        //     //     continue;
        //     // }
        //     foreach ( $top_level_categories as $category ) {
        
        //         if (
        //             in_array(
        //                 $category->name,
        //                 [ 'Uncategorized', 'Default' ],
        //                 true
        //             )
        //         ) {
        //             continue;
        //         }
        
        //         if ( ! isset( $category_products[ $category->term_id ] ) ) {
        
        //             $category_products[ $category->term_id ] = [
        //                 'category_id'   => (int) $category->term_id,
        //                 'category_name' => $category->name,
        //                 'quantity'      => 0,
        //                 'products'      => [],
        //             ];
        //         }
        
        //         $category_products[ $category->term_id ]['products'][] = $product_data;
        //         $category_products[ $category->term_id ]['quantity']  += $product_data['quantity'];
        //     }
        // }
        // =========================================================
        // 2. Group pending products by TOP-LEVEL category.
        // =========================================================
        
        $category_products = [];
        
        foreach ( $pending_products as $product_id => $product_data ) {
        
            $product_categories = wp_get_post_terms(
                $product_id,
                'product_cat',
                [
                    'fields' => 'all',
                ]
            );
        
            if ( is_wp_error( $product_categories ) || empty( $product_categories ) ) {
                continue;
            }
        
            $top_level_categories = [];
        
            foreach ( $product_categories as $category ) {
        
                $top_category = $category;
        
                /*
                 * Example:
                 *
                 * Food
                 *   └── Main Course
                 *        └── Biryani
                 *
                 * If product belongs to Biryani,
                 * get_ancestors() returns Main Course + Food.
                 * We take the highest parent = Food.
                 */
                $ancestors = get_ancestors(
                    $category->term_id,
                    'product_cat',
                    'taxonomy'
                );
        
                if ( ! empty( $ancestors ) ) {
        
                    // Last ancestor is the highest/top-level parent.
                    $top_parent_id = end( $ancestors );
        
                    $parent_term = get_term(
                        $top_parent_id,
                        'product_cat'
                    );
        
                    if (
                        ! is_wp_error( $parent_term ) &&
                        $parent_term
                    ) {
                        $top_category = $parent_term;
                    }
                }
        
                // Prevent same top category being added multiple times.
                $top_level_categories[ $top_category->term_id ] = $top_category;
            }
        
            /*
             * Add product under its top-level category.
             */
            foreach ( $top_level_categories as $category ) {
        
                if (
                    in_array(
                        strtolower( $category->name ),
                        [ 'uncategorized', 'default' ],
                        true
                    )
                ) {
                    continue;
                }
        
                if ( ! isset( $category_products[ $category->term_id ] ) ) {
        
                    $category_products[ $category->term_id ] = [
                        'category_id'   => (int) $category->term_id,
                        'category_name' => $category->name,
                        'quantity'      => 0,
                        'products'      => [],
                    ];
                }
        
                $category_products[ $category->term_id ]['products'][] =
                    $product_data;
        
                $category_products[ $category->term_id ]['quantity'] +=
                    (int) $product_data['quantity'];
            }
        }
        // =========================================================
        // 3. Sort categories and their products for a stable response.
        // =========================================================
        
        uasort(
            $category_products,
            function ( $a, $b ) {
                return strcmp(
                    $a['category_name'],
                    $b['category_name']
                );
            }
        );

        foreach ( $category_products as &$category_data ) {
            usort(
                $category_data['products'],
                function ( $a, $b ) {
                    return strcmp( $a['item_name'], $b['item_name'] );
                }
            );
        }
        unset( $category_data );

        $category_products = array_values( $category_products );
        return rest_ensure_response(
            [
                // 'total_pending_items' => $total_pending_items,
                'category_products'   => $category_products,
                'response'            => $response,
            ]
        );
    }
    public function get_kitchen_items_count( $request ) 
    {
        global $wpdb;
        // === Get Parameters ===
        $params        = $request->get_params();
        $restaurant_id = isset($params['restaurant_id']) ? intval($params['restaurant_id']) : (int) get_user_meta($captain_id, 'user_restaurant_id', true);
    
        if ( empty($restaurant_id) ) {
            return new WP_Error( 'missing_restaurant_id', 'Restaurant ID is required.', [ 'status' => 422 ] );
        }
    
        // Manager: get all parent orders
        $parent_order_ids = $wpdb->get_col("
            SELECT id
            FROM {$wpdb->prefix}wc_orders
            WHERE parent_order_id = 0 order by id desc
        ");
        $orders = [];
        foreach ( $parent_order_ids as $pid ) {
            $order = wc_get_order( $pid );
            if ( $order && ! in_array( $order->get_status(), [ 'cancelled', 'failed', 'trash' ], true ) ) {
                $orders[] = $order;
            }
        }
        // echo json_encode($orders);
        // === Keep only today's active orders ===
        $active_statuses = [ 'completed','pending', 'processing' ];
        $orders = array_filter( $orders, function( $order ) use ( $restaurant_id, $active_statuses ) {
            $matches_restaurant = intval( $order->get_meta( '_restaurant_id' ) ) === $restaurant_id;

            $status = $order->get_status();
            $is_active = in_array( $status, $active_statuses, true );
    
            // return $matches_restaurant && $is_today && $is_active;
            return $matches_restaurant && $is_active;
        });
    
        // === Prepare Response ===
        $response = [];
        $item_counts = [];
        
        foreach ( $orders as $order ) {
            $order_id    = $order->get_id();

            global $wpdb;
            $use_hpos = class_exists('\Automattic\WooCommerce\Utilities\OrderUtil') &&
                        method_exists('\Automattic\WooCommerce\Utilities\OrderUtil', 'custom_orders_table_usage_is_enabled') &&
                        \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
    
            $orders_table  = $use_hpos ? $wpdb->prefix . 'wc_orders' : $wpdb->prefix . 'posts';
            $id_field      = $use_hpos ? 'id' : 'ID';
            $parent_field  = $use_hpos ? 'parent_order_id' : 'post_parent';
            $status_field  = $use_hpos ? 'status' : 'post_status';
    
            $children = $wpdb->get_col(
                $wpdb->prepare(
                    "
                    SELECT {$id_field}
                    FROM {$orders_table}
                    WHERE {$parent_field} = %d
                    ",
                    $order_id
                )
            );
            
            
            foreach ( $children as $child_id ) {
    
                $child_order = wc_get_order( $child_id );
            
                if ( ! $child_order ) {
                    continue;
                }
                if($child_order->get_status() != 'preparing')
                {
                    continue;
                }

                foreach ( $child_order->get_items() as $item_id => $item ) {

                    if ( 'cancelled' === wc_get_order_item_meta( $item_id, '_item_status', true ) ) {
                        continue;
                    }
                
                    $product = $item->get_product();
                
                    $product_id = 0;
                    $is_veg     = null;
                
                    if ( $product ) {
                
                        $product_id = $product->is_type( 'variation' )
                            ? $product->get_parent_id()
                            : $product->get_id();
                
                        $categories = wp_get_post_terms( $product_id, 'product_cat' );

                        $top_level_categories = [];

                        foreach ( $categories as $category ) {
                        
                            while ( $category->parent > 0 ) {
                                $category = get_term( $category->parent, 'product_cat' );
                            }
                        
                            $top_level_categories[] = $category->slug;
                        }
                        
                        $top_level_categories = array_unique( $top_level_categories );
                        
                        if ( array_intersect( [ 'alcohol', 'bewerages', 'beverages' ], $top_level_categories ) ) {
                            continue;
                        }
                        $tags = wp_get_post_terms(
                            $product_id,
                            'product_tag',
                            [ 'fields' => 'names' ]
                        );
                
                        foreach ( $tags as $tag_name ) {
                
                            $tag_name = strtolower( $tag_name );
                
                            if ( false !== strpos( $tag_name, 'non-vegetarian' ) ) {
                                $is_veg = false;
                                break;
                            }
                
                            if ( false !== strpos( $tag_name, 'vegetarian' ) ) {
                                $is_veg = true;
                                break;
                            }
                        }
                    }
                
                    // Use parent product ID (or product ID for simple products) as the key.
                    $key = $product_id ?: md5( $item->get_name() );

                    if ( ! isset( $item_counts[ $key ] ) ) {
                
                        $item_counts[ $key ] = [
                            'product_id' => $product_id,
                            'item_name'  => $item->get_name(),
                            'quantity'   => 0,
                            'is_veg'     => $is_veg,
                        ];
                    }
                
                    $item_counts[ $key ]['quantity'] += (int) $item->get_quantity();
                }
            }
    
        }
        $line_items = [];

        // Only show items whose total quantity > 0
        foreach ( $item_counts as $item_data ) {
        
            if ( $item_data['quantity'] > 0 ) {
                $line_items[] = $item_data;
            }
        } 
        return rest_ensure_response( $line_items );
    }
    // public function get_all_coupons_list($request)
    // {
    //     $coupon_posts = get_posts(
    //         [
    //             'post_type'      => 'shop_coupon',
    //             'post_status'    => 'publish',
    //             'posts_per_page' => -1,
    //         ]
    //     );
    
    //     $coupons = [];
    
    //     foreach ( $coupon_posts as $coupon_post ) {
    
    //         $coupon = new WC_Coupon( $coupon_post->ID );
    
    //         // Skip invalid coupons
    //         if ( ! $coupon->get_code() ) {
    //             continue;
    //         }
    
    //         // Check expiry
    //         $expiry_date = $coupon->get_date_expires();
    
    //         if ( $expiry_date && $expiry_date->getTimestamp() < current_time( 'timestamp' ) ) {
    //             continue;
    //         }
    
    //         // Check usage limit
    //         $usage_limit = $coupon->get_usage_limit();
    
    //         if (
    //             $usage_limit > 0 &&
    //             $coupon->get_usage_count() >= $usage_limit
    //         ) {
    //             continue;
    //         }
    
    //         $coupons[] = [
    //             'id'                => $coupon->get_id(),
    //             'code'              => $coupon->get_code(),
    //             'description'       => $coupon->get_description(),
    //             'discount_type'     => $coupon->get_discount_type(),
    //             'amount'            => (float) $coupon->get_amount(),
    //         ];
    //     }
    
    //     return rest_ensure_response(
    //         [
    //             'success' => true,
    //             'count'   => count( $coupons ),
    //             'data'    => $coupons,
    //         ]
    //     );
    // }
    public function get_parent_kot_orders($request) 
    {
        $parent_order_id = absint($request->get_param('parent_order_id'));
        if (!$parent_order_id) 
        {
            return new WP_REST_Response(['error' => 'Missing parent_order_id'], 400);
        }
        // $orr_type = $request->get_param('order_type');
        // if(!$orr_type)
        // {
        //     return new WP_REST_Response(['error' => 'Missing order type'], 400);
        // }
        $parent_order = wc_get_order($parent_order_id);
        if (!$parent_order) 
        {
            return new WP_REST_Response(['error' => 'Invalid parent order ID'], 404);
        }
        $status = $parent_order->get_status();
        if ( in_array( $status, ['trash'], true ) ) 
        {
            return new WP_REST_Response([ 'error' => "Parent order is in {$status}" ],410);
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

        foreach ( $children as $child_id ) 
        {
            $child_order = wc_get_order( $child_id );
            if ( ! $child_order ) {
                continue;
            }

            if ( $child_order->get_status() === 'cancelled' ) {
                continue;
            }

            $line_items = [];
            $items = $child_order->get_items();
            if ( empty( $items ) ) {
                // Nothing to show → hide this child order
                continue;
            }
            foreach ( $child_order->get_items() as $item_id => $item ) 
            {
                $product   = $item->get_product();
                $name      = $item->get_name();
                $qty       = $item->get_quantity();
                $total     = $item->get_total();

                if ( $product ) 
                {
                    $price = wc_get_price_to_display( $product );
                } 
                else 
                {
                    $price = 0;
                }
                $all_modifiers = [];
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
                $tax_data = $item->get_taxes();
                // if (isset($tax_data['total'])) {
                //     $tax_totals = $tax_data['total'];
                //     foreach ($tax_totals as $tax_amount) {
                //         $gst_amount += floatval($tax_amount);
                //     }
                //     $total += $gst_amount;
                // }
                $total+= $modifier_amount;
                $item_status = wc_get_order_item_meta( $item_id, '_item_status', true );

                $is_cancelled = ( strtolower( $item_status ) === 'cancelled' ) ? 'yes' : 'no';
                $product = $item->get_product();

                $item_price = $product
                    ? (float) $product->get_price()
                    : 0;
                $line_items[] = [
                    'id'         => $item_id,
                    'product_id' => $product ? $product->get_id() : 0,
                    'item_name'  => $name,
                    'quantity'   => $qty,
                    'price'      => ( strtolower( $item_status ) === 'cancelled' ) ? 0 : floatval($price),
                    'original_price' => $item_price,
                    'amount'     => floatval( $total ),
                    'modifiers'  => $all_modifiers,
                    'combos'     => $combo_items,
                    'is_cancelled'  => $is_cancelled,
                ];
            }

            $customer_id = $child_order->get_customer_id();
            if ( $customer_id ) {
                $user       = get_userdata( $customer_id );
                $created_by = $user ? $user->display_name : 'guest';
            } else {
                $created_by = 'guest';
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

        $table_id = intval( $parent_order->get_meta('_table_id') );
        $zone_id  = intval( $parent_order->get_meta('_zone_id') );

        $table_post = get_post( $table_id );
        $zone_post  = get_post( $zone_id );

        $table_name = $table_post ? $table_post->post_title : '';
        $zone_name  = $zone_post ? $zone_post->post_title : '';
        $order_types = ['rest-api' => 'Dine In', 'takeaway' => 'Takeaways', 'online' => 'Online Orders'];
        $created_via = $parent_order->get_created_via();
        $order_type = isset($order_types[$created_via]) ? $order_types[$created_via] : '';
        $reservation_id = $parent_order->get_meta('_reservation_id');
        $response = [
            'parent_order' => [
                'id'         => $parent_order->get_id(),
                'status'     => $parent_order->get_status(),
                'order_type' => $order_type,
                'total'      => floatval($parent_order->get_total()),
                'zone_id'    => intval($parent_order->get_meta( '_zone_id' )),
                'zone_name'  => $zone_name,
                'restaurant_id' => intval($parent_order->get_meta('_restaurant_id')),
            ]
        ];
        if($parent_order->get_created_via() === 'rest-api')
        {
            $response['parent_order']['table_id'] = intval($parent_order->get_meta( '_table_id' ));
            $response['parent_order']['table_name'] = $table_name;
        }
        if($reservation_id!=='')
        {
            $response['parent_order']['reservation_id'] = $reservation_id;
        }

        $response['parent_order']['kot_orders'] = $kot_orders;
        return rest_ensure_response( $response );
    }
    public function update_cancel_item_status($request)
    {
        $params = $request->get_json_params();

        $parent_id = absint( $params['parent_id'] ?? 0 );
        $order_id  = absint( $params['order_id'] ?? 0 );
        
        if ( empty( $parent_id ) || empty( $order_id ) ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => 'Parent ID and Order ID are required.',
                ),
                400
            );
        }
        $items = isset( $params['items'] ) ? array_map( 'absint', (array) $params['items'] ) : array();

        if ( empty( $items ) ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => 'Items array is required.',
                ),
                400
            );
        }
        $parent_order = wc_get_order( $parent_id );
        $order        = wc_get_order( $order_id );
        
        if ( ! $parent_order || ! $order ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => 'Invalid Parent Order or Order.',
                ),
                404
            );
        }
        
        // Verify the child order belongs to the parent order.
        if ( (int) $order->get_parent_id() !== $parent_id ) {
            return new WP_Error(
                'invalid_parent_order',
                __( 'The order does not belong to the specified parent order.', 'pinaka-pos' ),
                array( 'status' => 403 )
            );
        }
        
        $order_restaurant_id = (int) $parent_order->get_meta( '_restaurant_id' );
        $req_restaurant_id   = (int) $request->get_param( 'restaurant_id' );
        
        $map = [
            '_parent_order'   => 'Dine In',
            '_takeaway_order' => 'Takeaways',
            '_online_order'   => 'Online',
        ];
        
        $order_type = 'Dine In';
        
        foreach ( $map as $meta_key => $label ) {
            if ( 'yes' === $parent_order->get_meta( $meta_key ) ) {
                $order_type = $label;
                break;
            }
        }
        
        if ( 'Dine In' === $order_type ) {
        
            $order_zone_id = (int) $parent_order->get_meta( '_zone_id' );
            $req_zone_id   = (int) $request->get_param( 'zone_id' );
        
            if ( $order_restaurant_id !== $req_restaurant_id || $order_zone_id !== $req_zone_id ) {
                return new WP_Error(
                    'mismatched_ids',
                    __( 'Restaurant or Zone ID does not match with the parent order.', 'pinaka-pos' ),
                    array( 'status' => 403 )
                );
            }
        
        } else {
        
            if ( $order_restaurant_id !== $req_restaurant_id ) {
                return new WP_Error(
                    'mismatched_ids',
                    __( 'Restaurant ID does not match with the parent order.', 'pinaka-pos' ),
                    array( 'status' => 403 )
                );
            }
        }
    
        $updated_items = array();
        $not_found     = array();

        // Cancel requested items.
        foreach ( $items as $item_id ) {
            
            $item = $order->get_item( $item_id );
            
            if ( ! $item ) {
                continue;
            }
            
            wc_update_order_item_meta( $item_id, '_item_status', 'cancelled' );
            $item->delete_meta_data( '_extra_modifier_amount' );
            $item->set_subtotal( 0 );
            $item->set_total( 0 );
            $item->set_taxes( [
                'subtotal' => [],
                'total'    => [],
            ] );
            
            $item->save();
            foreach ( $order->get_items( 'fee' ) as $fee_item_id => $fee_item ) {
                if ( (int) $fee_item->get_meta( '_linked_line_item_id', true ) === $item_id ) {
                    
                    $order->remove_item( $fee_item_id );
                }
            }
            
            $updated_items[] = $item_id;
        }
        
        // Remove existing tax items
        foreach ( $order->get_items( 'tax' ) as $tax_item_id => $tax_item ) {
            $order->remove_item( $tax_item_id );
        }
        
        
        $order->calculate_taxes();
        $order->calculate_totals();
        $order->save();
        
        // Check whether all order items are cancelled.
        $all_cancelled = true;
    
        foreach ( $order->get_items() as $order_item_id => $order_item ) {
    
            $status = wc_get_order_item_meta( $order_item_id, '_item_status', true );
            if ( strtolower( $status ) !== 'cancelled' ) {
                $all_cancelled = false;
                break;
            }
        }
        if ( $all_cancelled ) {

            // Cancel current/KOT order.
            $order->update_status(
                'cancelled',
                'All order items were cancelled via POS API.',
                true
            );
        
            // Cancel parent only when _parent_order meta does not exist.
            if ( $parent_order && ! $parent_order->meta_exists( '_parent_order' ) ) {
                $parent_order->update_status(
                    'cancelled',
                    'All child order items were cancelled via POS API.',
                    true
                );
            }
        }
        $this->sync_parent_totals_and_status($parent_id);
        return new WP_REST_Response(
            array(
                'success'          => true,
                'message'          => 'Order items updated successfully.',
                'updated_items'    => $updated_items,
                'not_found'        => $not_found,
                'order_cancelled'  => $all_cancelled,
                'order_status'     => $order->get_status(),
            ),
            200
        );
    }
    public function update_kot_item_status($request)
    {
        $params = $request->get_json_params();

        $parent_id = absint( $params['parent_id'] ?? 0 );
        $order_id  = absint( $params['order_id'] ?? 0 );
        // $request_type = $params['request_type'];
        if ( empty( $parent_id ) || empty( $order_id ) ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => 'Parent ID and Order ID are required.',
                ),
                400
            );
        }
        $items = isset( $params['items'] ) ? array_map( 'absint', (array) $params['items'] ) : array();

        if ( empty( $items ) ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => 'Items array is required.',
                ),
                400
            );
        }
        $parent_order = wc_get_order( $parent_id );
        $order        = wc_get_order( $order_id );
        
        if ( ! $parent_order || ! $order ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => 'Invalid Parent Order or Order.',
                ),
                404
            );
        }
        
        // Verify the child order belongs to the parent order.
        if ( (int) $order->get_parent_id() !== $parent_id ) {
            return new WP_Error(
                'invalid_parent_order',
                __( 'The order does not belong to the specified parent order.', 'pinaka-pos' ),
                array( 'status' => 403 )
            );
        }
        
        $order_restaurant_id = (int) $parent_order->get_meta( '_restaurant_id' );
        $req_restaurant_id   = (int) $request->get_param( 'restaurant_id' );
        
        $map = [
            '_parent_order'   => 'Dine In',
            '_takeaway_order' => 'Takeaways',
            '_online_order'   => 'Online',
        ];
        
        $order_type = 'Dine In';
        
        foreach ( $map as $meta_key => $label ) {
            if ( 'yes' === $parent_order->get_meta( $meta_key ) ) {
                $order_type = $label;
                break;
            }
        }
        
        if ( 'Dine In' === $order_type ) {
        
            $order_zone_id = (int) $parent_order->get_meta( '_zone_id' );
            $req_zone_id   = (int) $request->get_param( 'zone_id' );
        
            if ( $order_restaurant_id !== $req_restaurant_id || $order_zone_id !== $req_zone_id ) {
                return new WP_Error(
                    'mismatched_ids',
                    __( 'Restaurant or Zone ID does not match with the parent order.', 'pinaka-pos' ),
                    array( 'status' => 403 )
                );
            }
        
        } else {
        
            if ( $order_restaurant_id !== $req_restaurant_id ) {
                return new WP_Error(
                    'mismatched_ids',
                    __( 'Restaurant ID does not match with the parent order.', 'pinaka-pos' ),
                    array( 'status' => 403 )
                );
            }
        }
    
        $updated_items = array();
        $not_found     = array();
        if(!empty( $items )){
        
            // Cancel requested items.
            foreach ( $items as $item_id ) {
                
                $item = $order->get_item( $item_id );
                
                if ( ! $item ) {
                    continue;
                }
                
                wc_update_order_item_meta( $item_id, '_kot_item_status', 'served' );
                
                $updated_items[] = $item_id;
            }
        }
        // Check whether all order items are cancelled.
        $all_cancelled = true;
    
        foreach ( $order->get_items() as $order_item_id => $order_item ) {
    
            $status = wc_get_order_item_meta( $order_item_id, '_kot_item_status', true );
            if ( strtolower( $status ) !== 'served' ) {
                $all_cancelled = false;
                break;
            }
        }
    
        if ( $all_cancelled ) {
            $order->update_status(
                'served',
                'All order items were served via POS API.',
                true
            );
        }
        // $this->sync_parent_totals_and_status($parent_id);
        return new WP_REST_Response(
            array(
                'success'          => true,
                'message'          => 'Order items updated successfully.',
                'updated_items'    => $updated_items,
                'not_found'        => $not_found,
                'order_served'  => $all_cancelled,
                'order_status'     => $order->get_status(),
            ),
            200
        );
    }
    public function update_generate_bill_status( $request ) {

        $params = $request->get_json_params();

        $order_id = absint( $params['order_id'] ?? 0 );
    
        if ( empty( $order_id ) ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => 'Order ID is required.',
                ),
                400
            );
        }
    
        $order = wc_get_order( $order_id );
    
        if ( ! $order ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => 'Invalid Order.',
                ),
                404
            );
        }
    
        /*
         * Get restaurant ID.
         */
        $order_restaurant_id = (int) $order->get_meta( '_restaurant_id' );
        $req_restaurant_id   = (int) $params['restaurant_id'];
    
        /*
         * Identify order type.
         */
        $map = array(
            '_parent_order'   => 'Dine In',
            '_takeaway_order' => 'Takeaways',
            '_online_order'   => 'Online',
        );
    
        $order_type = '';
    
        foreach ( $map as $meta_key => $label ) {
    
            if ( 'yes' === $order->get_meta( $meta_key, true ) ) {
                $order_type = $label;
                break;
            }
        }
    
        /*
         * Dine In validation.
         */
        if ( 'Dine In' === $order_type ) {
    
            $order_zone_id = (int) $order->get_meta( '_zone_id' );
            $req_zone_id   = (int) $params['zone_id'];
    
            if (
                $order_restaurant_id !== $req_restaurant_id ||
                $order_zone_id !== $req_zone_id
            ) {
                return new WP_Error(
                    'mismatched_ids',
                    __(
                        'Restaurant or Zone ID does not match with the order.',
                        'pinaka-pos'
                    ),
                    array(
                        'status' => 403,
                    )
                );
            }
    
        } else {
    
            /*
             * Takeaway / Online validation.
             */
            if ( $order_restaurant_id !== $req_restaurant_id ) {
    
                return new WP_Error(
                    'mismatched_ids',
                    __(
                        'Restaurant ID does not match with the order.',
                        'pinaka-pos'
                    ),
                    array(
                        'status' => 403,
                    )
                );
            }
        }
    
        /*
         * Mark bill as generated.
         */
        $order->update_meta_data( 'is_generated_bill', "1" );
    
        /*
         * Store request payload.
         */
        $order->update_meta_data(
            'posted_data',
            wp_json_encode( $params )
        );
    
        /*
         * Important: save the order.
         */
        $order->save();
    
        return new WP_REST_Response(
            array(
                'success'           => true,
                'message'           => 'Order status updated to Ready To Pay successfully.',
                'order_id'          => $order_id,
                'order_type'        => $order_type,
                'is_generated_bill' => 1,
            ),
            200
        );
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
		if($parent->get_status() !== 'completed')
		{
			update_post_meta($parent->get_id(),'_order_prev_total', $total);
		}
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
		$parent->calculate_totals( false );
        $parent->save();
	}
    public function handle_kot_transfer($request)
    {
        $params = $request->get_json_params();
        $order_id       = absint(isset($params['order_id']) ? $params['order_id'] : 0);
        $kot_order_id   = absint(isset($params['kot_id']) ? $params['kot_id'] : 0);
        $from_table_id  = absint($params['from_table_id'] ?? 0);
        $to_table_id    = absint($params['to_table_id'] ?? 0);
        $user_id        = get_current_user_id();
        $zone_id        = isset($params['zone_id']) ? absint($params['zone_id']) : 0;
        $child_orders   = $this->get_kot_child_orders_by_parent_id($order_id);
        if(empty($order_id))
        {
            return new WP_Error( 'missing_order', 'Order ID is required.', [ 'status' => 422 ] );
        }
        if(empty($kot_order_id))
        {
            return new WP_Error( 'missing_kot', 'KOT ID is required.', [ 'status' => 422 ] );
        }
        $child_ids = [];
        if (!empty($child_orders) && is_array($child_orders)) {
            foreach ($child_orders as $child_order) {
                if (is_object($child_order) && method_exists($child_order, 'get_id')) {
                    $child_ids[] = $child_order->get_id();
                }
            }
        }

        if (!in_array($kot_order_id, $child_ids, true)) {
            return new WP_Error(
                'invalid_kot_order',
                'KOT ID does not exist within parent order.',
                ['status' => 422]
            );
        }

        $restaurant_id = isset( $params['restaurant_id'] )
            ? intval( $params['restaurant_id'] )
            : intval( get_user_meta( $user_id, 'user_restaurant_id', true ) );

        if ( empty( $restaurant_id ) ) {
            return new WP_Error( 'missing_restaurant', 'Restaurant id is required.', [ 'status' => 422 ] );
        }

        if(empty($zone_id))
        {
            return new WP_Error( 'missing_zone', 'Zone id is required.', [ 'status' => 422 ] );
        }

        $restaurants = get_option('pinaka_pos_restaurants', []);

        $found = false;
        foreach ( $restaurants as $restaurant ) {
            if ( intval($restaurant['id']) === $restaurant_id ) {
                $found = true;
                break;
            }
        }

        if ( ! $found ) {
            return new WP_Error(
                'restaurant_not_exists',
                "Restaurant not exists.",
                array( 'status' => 400 )
            );
        }

        // Validate user belongs to restaurant
        $user_restaurant_id = intval( get_user_meta( $user_id, 'user_restaurant_id', true ) );
        if ( $user_restaurant_id !== $restaurant_id ) {
            return new WP_Error(
                'user_not_assigned',
                "User not assigned to Restaurant.",
                array( 'status' => 400 )
            );
        }

        if (!$order_id || !$kot_order_id || !$from_table_id || !$to_table_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Missing or invalid parameters.',
            ], 400);
        }

        // Validate tables
        $valid_from_table = $this->check_valid_table($restaurant_id, $zone_id, $from_table_id);
        $valid_to_table   = $this->check_valid_table($restaurant_id, $zone_id, $to_table_id);

        if(!$valid_from_table) {
            return new WP_Error( 'invalid_from_table_zone_restaurant', 'From Table not belongs to the specified zone and restaurant.', [ 'status' => 422 ] );
        }
        if(!$valid_to_table) {
            return new WP_Error( 'invalid_to_table_zone_restaurant', 'To Table not belongs to the specified zone and restaurant.', [ 'status' => 422 ] );
        }

        $kot_order = wc_get_order($kot_order_id);
        if (!$kot_order) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Invalid KOT order ID.',
            ], 404);
        }

        // Get parent orders by table IDs
        // $from_parent = $this->get_parent_order_by_table($from_table_id);
        // $to_parent   = $this->get_parent_order_by_table($to_table_id);
      
        // if (!$from_parent['order_id'] || !$to_parent['order_id']) {
        //     return new WP_REST_Response([
        //         'success' => false,
        //         'message' => 'Parent order not found for one of the tables.',
        //     ], 404);
        // }
        $from_parent_id = $this->get_active_parent_order_id_by_table( $from_table_id );
        $to_parent_id   = $this->get_active_parent_order_id_by_table( $to_table_id );

        if ( ! $from_parent_id ) {
            return new WP_Error(
                'from_parent_missing',
                'No active order found for source table.',
                [ 'status' => 422 ]
            );
        }

        if ( ! $to_parent_id ) {
            return new WP_Error(
                'to_parent_missing',
                'Target table has no active order. KOT transfer not allowed.',
                [ 'status' => 422 ]
            );
        }
       
        // if(!$to_parent['order_id'])
        // {
        //     $order = wc_create_order();
        //     $order->update_meta_data( '_parent_order', 'yes' );
		// 	$order->update_meta_data( '_table_id', intval( $to_table_id ) );
		// 	$order->update_meta_data( '_zone_id', intval( $zone_id ) );
		// 	$order->update_meta_data( '_restaurant_id', intval( $restaurant_id ?? get_user_meta( $user_id, 'user_restaurant_id', true ) ) );
		// 	$order->update_meta_data( '_guest_count', intval( get_post_meta( $to_table_id, '_table_capacity', true ) ) );
		// 	$order->update_meta_data( '_order_time', current_time( 'Y-m-d H:i:s' ) );
		// 	$order->update_meta_data( '_table_status', 'Dine in' );
		// 	if (isset($params['reservation_id']) && ! empty( $params['reservation_id'] ) ) 
		// 	{
		// 		$order->update_meta_data( '_reservation_id', intval( $params['reservation_id'] ) );
		// 		update_post_meta(intval($params['reservation_id']), '_table_status', 'Dine in');
		// 		update_post_meta(intval($params['reservation_id']), '_reservation_status', 'Seated');
		// 	}
		// 	$order->set_status( 'processing' );
        //     $order->save();
        //     $to_parent   = $this->get_parent_order_by_table($to_table_id);
        // }
        // Ensure current parent is correct before transferring
        if ((int)$kot_order->get_parent_id() !== (int)$from_parent_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'KOT is not currently assigned to the from_table_id parent order.',
            ], 422);
        }
        if ( $kot_order->get_status() === 'cancelled' ) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'cannot transfer cancelled kots.',
            ], 422);
        }
        $from_parent_order = wc_get_order($from_parent_id);
        $to_parent_order   = wc_get_order($to_parent_id);

        $from_table_restaurant_id = intval( $from_parent_order->get_meta('_restaurant_id') );
        $to_table_restaurant_id   = intval( $to_parent_order->get_meta('_restaurant_id') );


        if($from_table_restaurant_id !== $restaurant_id || $to_table_restaurant_id !== $restaurant_id) 
        {
            return new WP_Error( 'table_restaurant_mismatch', 'from order or to order does not belong to the specified restaurant.', [ 'status' => 422 ] );
        }
        if ($from_parent_order->get_status() === 'completed' || $to_parent_order->get_status() === 'completed') {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Cannot transfer to or from a completed parent order.',
            ], 403);
        }
        
        // Transfer KOT
        $kot_order->set_parent_id($to_parent_id);
        $kot_order->save();

        // move snapshot items to new parent
        $this->transfer_kot_snapshot_items(
            $from_parent_id,
            $to_parent_id,
            $kot_order_id
        );


        // Optionally: recalculate totals if you have such a function
        if (method_exists($this, 'recalculate_parent_totals')) {
            $this->recalculate_parent_totals($from_parent_id);
            $this->recalculate_parent_totals($to_parent_id);

            // update stable snapshot AFTER transfer totals are finalized
            $from_updated = wc_get_order($from_parent_id);
            $to_updated   = wc_get_order($to_parent_id);

            update_post_meta($from_parent_id, '_order_prev_total', $from_updated->get_total());
            update_post_meta($to_parent_id, '_order_prev_total', $to_updated->get_total());

        }
        $this->sync_kot_user_to_parent( $to_parent_order, $kot_order->get_customer_id() );

        return new WP_REST_Response([
            'success'        => true,
            'message'        => 'KOT order transferred successfully.',
            'kot_order_id'   => $kot_order_id,
            'from_table_id'  => $from_table_id,
            'to_table_id'    => $to_table_id,
            'new_parent_id'  => $to_parent_id,
            'restaurant_id'  => $restaurant_id,
            'zone_id'        => $zone_id
        ]);
    }

    private function transfer_kot_snapshot_items($from_parent_id, $to_parent_id, $kot_order_id)
{
    $from_parent = wc_get_order($from_parent_id);
    $to_parent   = wc_get_order($to_parent_id);

    if (!$from_parent || !$to_parent) {
        return;
    }

    $from_snapshot = $from_parent->get_meta('_kot_initial_items', true);
    $to_snapshot   = $to_parent->get_meta('_kot_initial_items', true);

    if (!is_array($from_snapshot)) $from_snapshot = [];
    if (!is_array($to_snapshot))   $to_snapshot   = [];

    // existing IDs in destination to avoid duplicates
    $existing_ids = array_column($to_snapshot, 'line_item_id');

    foreach ($from_snapshot as $item) {

        if ((int)$item['kot_order_id'] !== (int)$kot_order_id) {
            continue;
        }

        if (in_array($item['line_item_id'], $existing_ids)) {
            continue;
        }

        $to_snapshot[] = $item;
    }

    // save to destination parent
    $to_parent->update_meta_data('_kot_initial_items', $to_snapshot);
    $to_parent->save();
}

    private function get_active_parent_order_id_by_table( $table_id ) {

        $parent = $this->get_parent_order_by_table( $table_id );

        if ( empty( $parent ) || empty( $parent['order_id'] ) ) {
            return 0;
        }

        $order = wc_get_order( $parent['order_id'] );
        if ( ! $order ) {
            return 0;
        }

        if ( in_array( $order->get_status(), [ 'completed', 'cancelled' ], true ) ) {
            return 0;
        }

        if ( $order->get_meta( '_parent_order' ) !== 'yes' ) {
            return 0;
        }

        return (int) $order->get_id();
    }
    private function get_parent_order_by_table($table_id)
    {
        global $wpdb;

        $use_hpos = false;
        if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) &&
             method_exists( '\Automattic\WooCommerce\Utilities\OrderUtil', 'custom_orders_table_usage_is_enabled' ) ) {
            $use_hpos = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
        }
        if ($use_hpos) {
            $orders_table = $wpdb->prefix . 'wc_orders';
            $meta_table   = $wpdb->prefix . 'wc_orders_meta';

            $result = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT o.id AS order_id, m2.meta_value AS table_id
                     FROM $orders_table o
                     INNER JOIN $meta_table m1 ON m1.order_id = o.id AND m1.meta_key = %s AND m1.meta_value = %s
                     INNER JOIN $meta_table m2 ON m2.order_id = o.id AND m2.meta_key = %s AND m2.meta_value = %s
                     ORDER BY o.id DESC
                     LIMIT 1",
                    '_parent_order', 'yes',
                    '_table_id', $table_id
                ),
                ARRAY_A
            );
            if ($result) {
                return [
                    'order_id' => absint($result['order_id']),
                    'table_id' => absint($result['table_id']),
                ];
            }

            return [
                'order_id' => 0,
                'table_id' => $table_id,
            ];

        } else {
            // Classic WP posts
            $args = [
                'post_type'   => 'shop_order',
                'post_status' => ['wc-pending', 'wc-processing'],
                'meta_query'  => [
                    [
                        'key'     => '_parent_order',
                        'value'   => 'yes',
                    ],
                    [
                        'key'     => '_table_id',
                        'value'   => $table_id,
                        'compare' => '=',
                    ],
                ],
                'numberposts' => 1,
            ];

            $orders = get_posts($args);

            if (!empty($orders)) {
                $order_id = $orders[0]->ID;
                $table_id_meta = get_post_meta($order_id, '_table_id', true);
                return [
                    'order_id' => absint($order_id),
                    'table_id' => absint($table_id_meta),
                ];
            }

            return [
                'order_id' => 0,
                'table_id' => $table_id,
            ];
        }
    }
    private function check_valid_table($restaurant_id, $zone_id, $table_id)
    {
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
                [ 'key' => '_table_zone_id', 'value' => $zone_id ],
                [ 'key' => '_restaurant_id', 'value' => $restaurant_id ]
            ]
        ]);
        if ( $existing->have_posts() ) {
            return 1;
        }
        else {
            return 0;
        }
    }
    // private function recalculate_parent_totals($parent_id)
    // {
    //     $parent = wc_get_order($parent_id);
    //     // if (!$parent || $parent->get_meta('_parent_order', true) !== 'yes') {
    //     //     return;
    //     // }
    //     $parent_order_id = $parent->get_id();
    //     $children = $this->get_kot_child_orders_by_parent_id($parent_order_id);
       
    //     $valid_children = array_filter($children, function($child) {
    //         return is_a($child, 'WC_Order') && $child->get_meta('_kot_number', true);
    //     });
    //     if(count($valid_children) > 0 && $parent->get_status() == 'processing')
    //     {
    //         $parent->set_status( 'pending' );
    //     }
    //     $new_total = 0;
    //     foreach ($valid_children as $child_id) {
    //         $child = wc_get_order($child_id);
    //         if ($child && $child->get_status() !== 'cancelled') 
    //         {
    //             $new_total += (float) $child->get_total();
    //         }
    //     }

    //     // if($parent->get_status() !== 'completed')
    //     // {
    //     //     update_post_meta($parent->get_id(),'_order_prev_total', $total);
    //     // }
        
    //     $parent->set_total($new_total);
    //     $parent->save();
    // }
    private function recalculate_parent_totals($parent_id)
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
		if($parent->get_status() !== 'completed')
		{
			update_post_meta($parent->get_id(),'_order_prev_total', $total);
		}
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
		$parent->calculate_totals( false );
        $parent->save();
	}
    public function sync_kot_user_to_parent( $parent_order, $captain_id ) 
    {
        if ( $parent_order && $parent_order instanceof WC_Order ) 
        {
            $parent_customer_id = intval( $parent_order->get_customer_id() );
            if ( $parent_customer_id === 0 ) {
                $parent_order->set_customer_id( intval( $captain_id ) );
                $parent_order->save();
            }
        }
    }
    private function get_kot_child_orders_by_parent_id($parent_id) {
        global $wpdb;

        $use_hpos = false;
        if (class_exists('\Automattic\WooCommerce\Utilities\OrderUtil') &&
            method_exists('\Automattic\WooCommerce\Utilities\OrderUtil', 'custom_orders_table_usage_is_enabled')) {
            $use_hpos = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
        }

        $orders_table  = $use_hpos ? $wpdb->prefix . 'wc_orders' : $wpdb->prefix . 'posts';
        $id_field      = $use_hpos ? 'id' : 'ID';
        $parent_field  = $use_hpos ? 'parent_order_id' : 'post_parent';

        $sql = "SELECT $id_field FROM {$orders_table} WHERE $parent_field = %d";
        $child_ids = $wpdb->get_col($wpdb->prepare($sql, $parent_id));

        $orders = [];
        foreach ($child_ids as $id) 
        {
            $order = wc_get_order($id);
            if ($order) 
            {
                $orders[] = $order;
            }
        }
        return $orders;
    }

     public function get_all_orders( WP_REST_Request $request ) {
        
        global $wpdb;

        $results = $wpdb->get_results("
            SELECT
                id as order_id,
                type as order_type,
                date_created_gmt as order_date,
                status
            FROM {$wpdb->prefix}wc_orders
            WHERE parent_order_id = 0 OR parent_order_id IS NULL
            ORDER BY id DESC
        ");
        $total_count = (int) $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->prefix}wc_orders
            WHERE parent_order_id = 0 OR parent_order_id IS NULL
        ");

        $orders = array();
        $order_types = [
            'rest-api' => 'Dine In',
            'takeaway' => 'Takeaways',
            'online'   => 'Online Orders'
        ];
        $allowed_dinein_types = [
            'Swiggy Dine In',
            'Zomato Dine In',
            'Ubereats Dine In',
        ];
        foreach ( $results as $row ) {

            $order = wc_get_order( $row->order_id );
            if ( ! $order ) {
                continue;
            }
            
            // Get the user who placed the order
            $placed_by_user_id = $order->get_user_id();
            
            $placed_by_first_name = '';
            $placed_by_last_name  = '';
            $placed_by_name       = '';
            $emp_login_pin = '';
            $placed_by_role = '';

            if ( $placed_by_user ) {
                $placed_by_role = ! empty( $placed_by_user->roles )
                    ? $placed_by_user->roles[0]
                    : '';
            }
            
            if ( $placed_by_user_id ) {
                $emp_login_pin = get_user_meta(
                    $placed_by_user_id,
                    'emp_login_pin',
                    true
                );
            }
            
            if ( $placed_by_user_id ) {
                $placed_by_user = get_userdata( $placed_by_user_id );
            
                if ( $placed_by_user ) {
                    $placed_by_first_name = $placed_by_user->first_name;
                    $placed_by_last_name  = $placed_by_user->last_name;
                    $placed_by_name       = trim(
                        $placed_by_first_name . ' ' . $placed_by_last_name
                    );
                }
            }

            $payment_method = $this->get_order_payment_method_from_payments( $order->get_id() );

            $restaurant_id = $order->get_meta('_restaurant_id', true);
            $zone_id       = $order->get_meta('_zone_id', true);
            $table_id      = $order->get_meta('_table_id', true);

            $total = (float) $order->get_total();

            /* ================================
            CUSTOMER FETCH LOGIC
            ================================= */

            $customer_name  = $order->get_meta('_customer_name', true);
            $customer_phone = $order->get_meta('_customer_phone', true);

            // Fallback to reservation
            if ( empty($customer_name) || empty($customer_phone) ) {

                $reservation_id = $order->get_meta('_reservation_id', true);

                if ( $reservation_id ) {
                    if ( empty($customer_name) ) {
                        $customer_name = get_post_meta($reservation_id, '_customer_name', true);
                    }

                    if ( empty($customer_phone) ) {
                        $customer_phone = get_post_meta($reservation_id, '_customer_phone', true);
                    }
                }
            }

            // Final fallback to billing (rare in POS)
            if ( empty($customer_name) ) {
                $customer_name = $order->get_formatted_billing_full_name();
            }

            if ( empty($customer_phone) ) {
                $customer_phone = $order->get_billing_phone();
            }
            
            $service_charge_percentage = $order->get_meta(
                '_service_charge_percentage',
                true
            );
            
            if ($service_charge_percentage === '') {
                $service_charge_percentage = 5;
            }

            // Merchant discount (force positive)
            $merchant_discount = (float) $order->get_meta('_order_discount', true);
            $merchant_discount = abs($merchant_discount);

            // Get child KOT order IDs
            $child_ids = $wpdb->get_col( $wpdb->prepare("
                SELECT id FROM {$wpdb->prefix}wc_orders
                WHERE parent_order_id = %d
            ", $order->get_id() ) );

            // Load child KOT orders
            $kot_orders = [];
            foreach ( $child_ids as $child_id ) {
                $child_order = wc_get_order( $child_id );
                if ( ! $child_order ) continue;
                if ( $child_order->has_status('cancelled') ) continue;
                $kot_orders[] = $child_order;
            }

            /* ===============================
            MODIFIER TAX DETECTION
            ================================ */
            $modifier_tax_total = 0;

            foreach ( $kot_orders as $kot ) {
                foreach ( $kot->get_items( 'fee' ) as $fee_item ) {

                    $linked_line_item_id = absint(
                        $fee_item->get_meta( '_linked_line_item_id', true )
                    );

                    if ( ! $linked_line_item_id ) {
                        continue;
                    }

                    $modifier_tax_total += (float) $fee_item->get_total_tax();
                }
            }

            $modifiers_taxable = $modifier_tax_total > 0 ? 'yes' : 'no';

            

            // grand_total + tax_total (from KOT items)
            $grand_total    = 0;
            $tax_total      = 0;
            $items_subtotal = 0; // subtotal + tax
            $gross_total    = 0; // total of all items amount (subtotal only)

            foreach ( $kot_orders as $kot ) {
                foreach ( $kot->get_items() as $item_id => $item ) {
                    if ( 'cancelled' === wc_get_order_item_meta( $item_id, '_item_status', true ) ) {
                        continue;
                    }
                    $item_subtotal     = (float) $item->get_subtotal();
                    $item_subtotal_tax = (float) $item->get_subtotal_tax();

                    // modifier amount from meta
                    $modifier_amount = 0;
                    $meta_data = $item->get_meta_data();

                    foreach ( $meta_data as $meta ) {
                        if ( $meta->key === '_extra_modifier_amount' ) {
                            $modifier_amount += (float) $meta->value;
                        }
                    }

                    // gross_total includes modifiers now
                    $gross_total    += ($item_subtotal + $modifier_amount);

                    $items_subtotal += ($item_subtotal + $modifier_amount) + $item_subtotal_tax;

                    $grand_total += (float) $item->get_total();
                    $tax_total   += (float) $item->get_total_tax();
                }
            }


            // Fee tax (from parent order fees)
            $fee_total     = 0;
            $fee_tax_total = 0;

            foreach ( $order->get_items('fee') as $fee ) {
                $fee_total     += (float) $fee->get_total();
                $fee_tax_total += (float) $fee->get_total_tax();
            }

            // Coupon Calculation
            $coupon_total      = 0;
            $coupon_tax_total  = 0;
            $coupon_data       = [];

            foreach ( $order->get_items('coupon') as $coupon ) {

                $coupon_code = $coupon->get_code();
                if ( empty($coupon_code) ) {
                    continue;
                }

                $wc_coupon     = new WC_Coupon( $coupon_code );
                $discount_type = $wc_coupon->get_discount_type();
                $coupon_amount = 0;

                if ( $discount_type === 'fixed_cart' ) {

                    $coupon_amount = (float) $wc_coupon->get_amount();

                } elseif ( $discount_type === 'percent' ) {

                    $discount_percentage = (float) $wc_coupon->get_amount();
                    $coupon_amount = ( $discount_percentage / 100 ) * $grand_total;

                } elseif ( $discount_type === 'fixed_product' ) {

                    $product_ids = $wc_coupon->get_product_ids();
                    $coupon_amount_per_item = (float) $wc_coupon->get_amount();

                    foreach ( $kot_orders as $kot ) {
                        foreach ( $kot->get_items() as $item ) {
                            $product_id = (int) $item->get_product_id();

                            if ( empty($product_ids) || in_array($product_id, $product_ids) ) {
                                $coupon_amount += $coupon_amount_per_item * (float) $item->get_quantity();
                            }
                        }
                    }
                }

                $coupon_tax = (float) $coupon->get_discount_tax();

                $coupon_total     += $coupon_amount;
                $coupon_tax_total += $coupon_tax;

                $coupon_data[] = [
                    'code'  => $coupon_code,
                    'value' => round($coupon_amount, 2),
                ];
            }

            // discount = coupon + merchant discount
            $discount = round( abs($coupon_total) + abs($merchant_discount), 2 );

            // Final tax = KOT item tax + fee tax - coupon tax
            $final_tax = round(
                ($tax_total + $fee_tax_total + $modifier_tax_total) - $coupon_tax_total,
                2
            );

            if ( $final_tax < 0 ) {
                $final_tax = 0;
            }

            // sub_total = gross_total - coupon discount
            $sub_total = round( $gross_total - abs($coupon_total), 2 );
            if ( $sub_total < 0 ) {
                $sub_total = 0;
            }

            // net_total = sub_total + final_tax
            $net_total = round( $sub_total + $final_tax, 2 );
            if ( $net_total < 0 ) {
                $net_total = 0;
            }

            // net_payable (before round off) = net_total - merchant_discount
            // net_payable before service charge
            $net_payable = round(($net_total - $merchant_discount), 2);
            if ($net_payable < 0) {
                $net_payable = 0;
            }
            
            $created_via = $order->get_created_via();

$is_takeaway_order = ($created_via === 'takeaway');
            
            $service_charge_percentage = $is_takeaway_order
                ? 0
                : (float) $service_charge_percentage;
            
            $service_charge_value = $is_takeaway_order
                ? 0
                : round(
                    ($gross_total * $service_charge_percentage) / 100,
                    2
                );
            
            $tip_amt = (float) $order->get_meta('_order_tip', true);
            
            // Add service charge & tip
            $net_payable = round(
                $net_payable + $service_charge_value + $tip_amt,
                2
            );
            
            // Calculate round off on final payable
            $rounded_payable = round($net_payable, 0);
            $round_off = round($rounded_payable - $net_payable, 2);
            
            // Final payable after round off
            $net_payable = round($net_payable + $round_off, 2);
            
            if ($net_payable < 0) {
                $net_payable = 0;
            }

            $is_nc_order = $order->get_meta('_is_nc_order', true);
            $order_prev_total = get_post_meta(
    $order->get_id(),
    '_order_prev_total',
    true
);

if ($order_prev_total === '') {
    $order_prev_total = $total;
}
            $initial_items = $order->get_meta('_kot_initial_items', true);

            // $tip_amt = (float) $order->get_meta('_order_tip', true);
            
            // $net_payable = round(
            //     $net_payable + $service_charge_value + $tip_amt,
            //     2
            // );
            
            // $created_via = $order->get_created_via();
            
            // $is_takeaway_order = ($created_via === 'takeaway');
            
            $order_type = isset($order_types[$created_via])
                ? $order_types[$created_via]
                : '';
            $order_type = $order->get_meta('_pinaka_order_type', true) ?: $order_type;
            $order_type = isset($allowed_dinein_types[$order_type]) ? $allowed_dinein_types[$order_type] : $order_type;
            $order_datetime = $order->get_meta('_order_datetime');

            // Parent order response data
            $order_data = array(
                'order_id'       => $order->get_id(),
                'order_type'     => $order_type,
                'date'           => $order->get_date_created() ? wp_date(
                    get_option( 'date_format' ),
                    $order->get_date_created()->getTimestamp()
                ) : '',
                'customer_name'  => $customer_name ?: '',
                'customer_phone' => $customer_phone ?: '',
                'placed_by_user_id' => (int) $placed_by_user_id,
                //'placed_by_first_name' => $placed_by_first_name,
                //'placed_by_last_name'  => $placed_by_last_name,
                'placed_by_name'       => $placed_by_name,
                'emp_login_pin'     => $emp_login_pin,
                'placed_by_role'    => $placed_by_role,
                'payment_type'   => $payment_method ?: 'Unpaid',
                'order_prev_total' => (float) $order_prev_total,
                'is_updated'   => $order->get_meta('is_voided', true) ?: 'no',
                'updated_remarks' => $order->get_meta('order_edit_remarks', true) ?: '',
                'gross_total'        => (float) number_format($gross_total, 2, '.', ''),
                'coupon_details'     => $coupon_data,
                'sub_total'          => (float) number_format($sub_total, 2, '.', ''),
                'modifiers_taxable' => $modifiers_taxable,
                
                'total_tax'          => (float) number_format($final_tax, 2, '.', ''),
                'net_total'          => (float) number_format($net_total, 2, '.', ''),
                'is_nc_order' => ($is_nc_order === 'yes') ? 'yes' : 'no',
                //'merchant_discount'  => (float) number_format($merchant_discount, 2, '.', ''),
                'merchant_discount' => ($is_nc_order === 'yes' )
                    ? $net_payable
                    : $merchant_discount,
                'tip_amt'            => floatval($tip_amt),
                'service_charge_percentage' => ($is_nc_order === 'yes' || $is_takeaway_order)
                    ? 0
                    : $service_charge_percentage,
                
                'service_charge_value' => ($is_nc_order === 'yes' || $is_takeaway_order)
                    ? 0
                    : $service_charge_value,
                'round_off' => (float) number_format($round_off, 2, '.', ''),
                //'net_payable'        => (float) number_format($net_payable, 2, '.', ''),
                'net_payable'        => $is_nc_order === 'yes' ? 0 : $net_payable,
                'diff_in_total' => round(
                    (float) $order_prev_total - (float) $net_payable,
                    2
                ),
                'restaurant_id'  => (int) $restaurant_id,
                'zone_id'        => (int) $zone_id,
                'table_id'       => (int) $table_id,
                'table_status'   => $order->get_meta('_table_status', true),

                'zone_name'      => $zone_id ? get_the_title($zone_id) : '',
                'table_name'     => $table_id ? get_the_title($table_id) : '',

                'status'         => ucfirst($order->get_status()),

                'is_parent'      => true,
                'initial_kot_items' => $initial_items ?: [],
                'kot_orders'     => array(),
            );

            // Add KOT order line items with modifiers/addons
            foreach ( $child_ids as $child_id ) {

                $child_order = wc_get_order( $child_id );
                if ( ! $child_order ) continue;
                
                $kot_remarks = $child_order->get_meta('kot_remarks', true);

                $kot_items = [];

                foreach ( $child_order->get_items() as $item_id => $item ) {
                //  if ( 'cancelled' === wc_get_order_item_meta( $item_id, '_item_status', true ) ) {
                //     continue;
                // }
                $product = $item->get_product();

                $quantity = (float) $item->get_quantity();
                
                $original_quantity = 0;
                
                // Find original quantity from initial_kot_items
                foreach ( $initial_items as $initial_item ) {
                
                    if (
                        isset($initial_item['line_item_id']) &&
                        (int) $initial_item['line_item_id'] === (int) $item_id
                    ) {
                        $original_quantity = (float) $initial_item['quantity'];
                        break;
                    }
                }
                
                $voided_quantity = max(
                    0,
                    $original_quantity - $quantity
                );
                
                $voided_at = '';

                if ( $voided_quantity > 0 ) {
                
                    $table_name = $wpdb->prefix . 'order_void_logs';
                
                    $voided_at = $wpdb->get_var(
                        $wpdb->prepare(
                            "
                            SELECT created_at
                            FROM {$table_name}
                            WHERE post_id = %d
                              AND action = 'item_voided'
                              AND JSON_EXTRACT(data, '$.item_id') = %d
                            ORDER BY id DESC
                            LIMIT 1
                            ",
                            $order->get_id(),
                            $item_id
                        )
                    );
                
                    if ( ! $voided_at ) {
                        $voided_at = '';
                    }
                }

                /* ===============================
                COMBO ITEMS LOGIC
                ================================ */
                $combo_items = [];

                if ( $product && $product->is_type( 'combo_bundle' ) ) {

                    $combo_meta = json_decode(
                        get_post_meta( $product->get_id(), '_combo_bundle_items', true ),
                        true
                    );

                    if ( is_array( $combo_meta ) ) {

                        foreach ( $combo_meta as $combo_item ) {

                            $child_product = wc_get_product( $combo_item['id'] );

                            if ( $child_product ) {
                                $combo_items[] = [
                                    'id'       => (int) $child_product->get_id(),
                                    'name'     => $child_product->get_name(),
                                    'quantity' => (int) $combo_item['qty'] * (float) $item->get_quantity(),
                                ];
                            }
                        }
                    }
                }

                    //$quantity    = (float) $item->get_quantity();
                    $subtotal    = (float) $item->get_subtotal();
                    $unit_price  = $quantity > 0 ? ($subtotal / $quantity) : 0;

                    $product = $item->get_product();
                    $tax     = (float) $item->get_subtotal_tax();
                    $amount  = (float) $item->get_subtotal();

                    // modifiers/addons + extra modifier amount
                    $global_modifiers = [];
                    $modifier_amount  = 0;

                    $meta_data = $item->get_meta_data();
                    
                    foreach ( $meta_data as $meta ) {

                        $meta_key   = $meta->key;
                        $meta_value = $meta->value;

                        if ( in_array( $meta_key, [ '_addons', '_modifiers' ], true ) && is_array( $meta_value ) ) {

                            foreach ( $meta_value as $mod ) {

                                if ( ! empty( $mod['id'] ) ) {

                                    $mod_post = get_post( $mod['id'] );
                                    if ( $mod_post && $mod_post->post_type === 'modifier' ) {
                                        $global_modifiers[] = $mod_post->post_title;
                                    }

                                } else {

                                    if ( ! empty( $mod['name'] ) ) {
                                        $global_modifiers[] = $mod['name'];
                                    }
                                }
                            }
                        }

                        if ( $meta_key === '_extra_modifier_amount' ) {
                            $modifier_amount += (float) $meta_value;
                        }
                    }

                    // total including modifier amount
                    $total_with_modifiers = ($amount + $modifier_amount) + $tax;
                    $total_wo_tax = $amount + $modifier_amount;
                    $item_status = wc_get_order_item_meta( $item_id, '_item_status', true );

                     $is_cancelled = ( strtolower( $item_status ) === 'cancelled' ) ? 'yes' : 'no';
                    $kot_items[] = [
                        'line_item_id'    => (int) $item_id,
                        'item_id'         => $product ? (int) $product->get_id() : 0,
                        'is_cancelled' => $is_cancelled,
                        'name'            => $item->get_name(),
                        'quantity'        => (float) $item->get_quantity(),
                        'original_quantity' => $original_quantity,
                        'voided_quantity'   => $voided_quantity,
                        'voided_amount'   => (float) number_format(
                            $unit_price * $voided_quantity,
                            2,
                            '.',
                            ''
                        ),
                        'kot_remarks' => ($voided_quantity > 0) ? $kot_remarks : '',
                        'voided_at' => $voided_at,
                        'tax'             => (float) number_format($tax, 2, '.', ''),
                        'item_price'      => (float) number_format($unit_price, 2, '.', ''),

                        'amount'          => (float) number_format($amount, 2, '.', ''),
                        'modifier_amount' => (float) number_format($modifier_amount, 2, '.', ''),
                        'modifiers'       => $global_modifiers,
                        'combo_items'     => $combo_items,
                        'total_wo_tax'    => (float) number_format($total_wo_tax, 2, '.', ''),
                        'total'           => (float) number_format($total_with_modifiers, 2, '.', ''),
                    ];
                }

                /* =========================
                FETCH VOIDED ITEMS FOR THIS KOT
                ========================= */

                $voided_items = [];
                $final_void_remarks = '';
                $final_voided_date = '';

                $table_name = $wpdb->prefix . 'order_void_logs';

                $table_exists = $wpdb->get_var(
                    $wpdb->prepare("SHOW TABLES LIKE %s", $table_name)
                );

                if ( $table_exists === $table_name ) {

                    $void_logs = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT data, created_at
                            FROM {$table_name}
                            WHERE post_id = %d
                            ORDER BY id DESC",
                            $child_id   // 🔥 IMPORTANT CHANGE
                        ),
                        ARRAY_A
                    );

                    foreach ( (array) $void_logs as $log ) {

                        $log_data = json_decode( $log['data'], true );
                    
                        if ( ! is_array( $log_data ) ) {
                            continue;
                        }
                    
                        $voided_items[] = [
                            'item_id'   => (int) ($log_data['item_id'] ?? 0),
                            'product'   => $log_data['product'] ?? '',
                            'new_qty'   => (int) ($log_data['new_qty'] ?? 0),
                            'remarks'   => $log_data['remarks'] ?? '',
                            'voided_by' => (int) ($log_data['voided_by'] ?? 0),
                            'voided_at' => $log['created_at'],
                        ];
                    
                        // Latest void record
                        if ( empty($final_void_remarks) ) {
                            $final_void_remarks = $log_data['remarks'] ?? '';
                            $final_voided_date  = $log['created_at'] ?? '';
                        }
                    }
                }

                $kot_number = $child_order->get_meta('_kot_number');
                $kot_order_id = str_replace('KOT#', '', $kot_number);
                $order_data['kot_orders'][] = [
                    'kot_order_id' => (int) $child_id,
                    'kot_number'   => $kot_number,
                    'status'       => $child_order->get_status(),
                    'total'        => (float) $child_order->get_total(),
                    'created_at'   => $child_order->get_date_created() ? wp_date(
                        get_option( 'date_format' ),
                        $child_order->get_date_created()->getTimestamp()
                    ) : '',
                    'is_parent'    => false,
                    'final_void_remarks'  => $final_void_remarks,
                    'final_voided_date'   => $final_voided_date,
                    'voided_items' => $voided_items,

                    'line_items'   => $kot_items,
                ];
            }

            $orders[] = $order_data;
        }

        return new WP_REST_Response(
            array(
                'success'           => true,
                'total_order_count' => $total_count,
                'orders'            => $orders,
            ),
            200
        );
    }



    private function get_order_payment_method_from_payments( $order_id ) {

        $payments = get_posts( [
            'post_type'   => 'payments',
            'numberposts' => 1,
            'orderby'     => 'date',
            'order'       => 'DESC',
            'meta_query'  => [
                [
                    'key'   => '_payment_order_id',
                    'value' => $order_id,
                ],
                [
                    'key'     => '_payment_void',
                    'value'   => '1',
                    'compare' => '!=',
                ],
            ],
        ] );

        if ( empty( $payments ) ) {
            return '';
        }

        return (string) get_post_meta( $payments[0]->ID, '_payment_method', true );
    }

    public function get_all_tip_orders( WP_REST_Request $request ) {

        global $wpdb;
    
        $date = $request->get_param('tip_date') ?: date('Y-m-d');
        $user_id = get_current_user_id();
		// Get restaurant ID
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "
                SELECT
                    id AS order_id
                FROM {$wpdb->prefix}wc_orders
                WHERE (parent_order_id = 0 OR parent_order_id IS NULL)
                AND DATE(date_created_gmt) = %s
                ORDER BY id DESC
                ",
                $date
            )
        );
        $orders = [];
        $total_tip = 0;
        $order_types = ['rest-api' => 'Dine In', 'takeaway' => 'Takeaway', 'online' => 'Online Order'];
        foreach ( $results as $row ) {
    
            $order = wc_get_order( $row->order_id );
    
            if ( ! $order ) {
                continue;
            }
            
            $tip_amt = (float) $order->get_meta( '_order_tip', true );
            if($tip_amt <= 0)
            {
                continue;
            }
            $created_via = $order->get_created_via();
            $order_type = isset($order_types[$created_via]) ? $order_types[$created_via] : '';
            $net_payable = get_post_meta($order->get_id(), '_pay_final_total', true);

            $order_amt = ($net_payable !== '')
                ? (float) $net_payable
                : (float) $order->get_total();
                
            $rounded_payable = round($order_amt, 0);
            $round_off = round($rounded_payable - $order_amt, 2);
            
            // Final payable
            $order_amt = round($order_amt + $round_off, 2);
            
            $orders[] = [
                'order_id'   => $order->get_id(),
                'order_date'       => wp_date(
                    get_option( 'date_format' ),
                    strtotime($date)
                ),
                'order_type' => $order_type,
                'order_amt'  => $order_amt,
                'order_tip_amt'    => $tip_amt,
            ];
    
            $total_tip += $tip_amt;
        }
    
        return rest_ensure_response([
            'date'       => $date,
            'total_tip_amt'  => round( $total_tip, 2 ),
            'orders'     => $orders,
        ]);
    }
    public function category_wise_kot_print($request)
    {
        $restaurant_id = absint($request->get_param('restaurant_id'));
        $zone_id       = absint($request->get_param('zone_id'));
        $order_id      = absint($request->get_param('order_id'));
        $kot_id        = absint($request->get_param('kot_id'));
        $order_type    = $request->get_param('order_type') ?: '';
        if($order_type === '')
        {
            return new WP_Error('invalid_order', 'Order type is required', ['status' => 404]);
        }
        if($order_type === 'Dine In')
        {
            $order_type = 'rest-api';
        }
        else if($order_type === 'Takeaways')
        {
            $order_type = 'takeaway';
        }
        $order = wc_get_order($order_id);
        if (!$order) 
        {
            return new WP_Error('invalid_order', 'Order not found', ['status' => 404]);
        }

        $order_restaurant_id = (int) $order->get_meta( '_restaurant_id' );
        $req_restaurant_id = (int) $request->get_param( 'restaurant_id' );
        if($order_type === 'rest-api')
        {
            $order_zone_id       = (int) $order->get_meta( '_zone_id' );
            $req_zone_id       = (int) $request->get_param( 'zone_id' );
            if ( $order_restaurant_id !== $req_restaurant_id || $order_zone_id !== $req_zone_id ) 
            {
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
        $kot_order = wc_get_order($kot_id);
        if (!$kot_order) {
            return new WP_Error('invalid_order', 'Kot Order not found', ['status' => 404]);
        }

        if($kot_order->get_parent_id()!==$order_id)
        {
            return new WP_Error('invalid_parent_order_for_kot', 'Order Id not matching with Kot order', ['status' => 404]);
        }
        $kot_no = $kot_order->get_meta('_kot_number');
        $items_by_printer = [];
        $all_terms = [];

        foreach ( $kot_order->get_items() as $item ) 
        {
            $product_id = $item->get_product_id();
            $terms      = wp_get_post_terms( $product_id, 'product_cat' );
            $all_slugs  = [];

            foreach ( $terms as $term ) {
                $all_slugs[] = $term->slug;
                $ancestors = get_ancestors( $term->term_id, 'product_cat' );
                foreach ( $ancestors as $ancestor_id ) {
                    $ancestor = get_term( $ancestor_id, 'product_cat' );
                    if ( ! is_wp_error( $ancestor ) ) {
                        $all_slugs[] = $ancestor->slug;
                    }
                }
            }

            $all_terms[] = $all_slugs;
            $is_bar_item = false;

            foreach ( $all_slugs as $slug ) 
            {
                if ( in_array( $slug, $this->bar_categories, true ) ) {
                    $items_by_printer['bar'][] = $item;
                    $is_bar_item = true;
                    break;
                }
            }

            if ( ! $is_bar_item ) {
                $items_by_printer['kitchen'][] = $item;
            }

            $all_items[] = $item;
        }

        $slips = [];
        foreach ( $items_by_printer as $printer_type => $items ) {
            $slip = $this->generate_slip($items, $kot_no, ucfirst($printer_type).' Staff Copy', false);
            // $this->send_to_printer($restaurant_id, $zone_id, $slip, $printer_type);
            $slips[ $printer_type . '_slip' ] = $slip;
        }

        $customer_slip = $this->generate_slip($all_items, $kot_no, 'Customer Copy', true);
        // $this->send_to_printer($restaurant_id, $zone_id, $customer_slip, $this->customer_printer);
        $slips['customer_slip'] = $customer_slip;
        // if($order_type === 'takeaway')
        // {
            $bill_slip = $this->generate_slip($all_items, $kot_no, 'Billing Copy', true);
            // $this->send_to_printer($restaurant_id, $zone_id, $customer_slip, $this->customer_printer);
            $slips['bill_slip'] = $bill_slip;
        // }
        $plugin_dir_path = plugin_dir_path(__FILE__) . 'slip-emails';        
        $plugin_url = plugin_dir_url(__FILE__) . 'slip-emails';
        if ( ! file_exists( $plugin_dir_path ) ) 
        {
            wp_mkdir_p( $plugin_dir_path );
        }
        $filename = $plugin_dir_path . '/slip-' . $order_id . '-' . time() . '.html';
        file_put_contents( $filename, $slips );

        return [
            'success' => true,
            'message' => 'KOT copies printed successfully',
            // 'slips'   => $slips,
        ];
    }
    // private function generate_slip( $items, $kot_no, $header, $show_tax = true ) 
    // {
    //     if ( empty( $items ) ) {
    //         return '';
    //     }

    //     // Header
    //     $slip  = "========= $header =========\n";
    //     $slip .= $kot_no."\n";
    //     $slip .= "---------------------------------------------\n";

    //     if ( $show_tax ) {
    //         $slip .= sprintf("%-20s %-5s %-10s %-8s %-10s\n", "Item", "Qty", "Price", "Tax", "Total");
    //     } else {
    //         $slip .= sprintf("%-20s %-5s %-10s %-10s\n", "Item", "Qty", "Price", "Total");
    //     }

    //     $slip .= "---------------------------------------------\n";

    //     $grand_total = 0;

    //     foreach ( $items as $item ) 
    //     {
    //         $product = $item->get_product();
    //         $qty     = $item->get_quantity();

    //         // if ( $product ) {
    //         //     $price = $product->get_sale_price()
    //         //         ? wc_get_price_to_display( $product, [ 'price' => $product->get_sale_price() ] )
    //         //         : wc_get_price_to_display( $product, [ 'price' => $product->get_regular_price() ] );
    //         // } else {
    //         //     $price = 0;
    //         // }
    //         if ( $product ) {
	// 			// WooCommerce automatically uses sale price if active, otherwise regular
	// 			$price = wc_get_price_to_display( $product );
	// 		} else {
	// 			$price = 0;
	// 		}
    //         $line_total = $item->get_total();
    //         $tax_total  = array_sum( $item->get_taxes()['total'] ?? [] );

    //         $grand_total += $line_total;

    //         if ( $show_tax ) {
    //             $slip .= sprintf(
    //                 "%-20s %-5s %-10s %-8s %-10s\n",
    //                 mb_strimwidth($item->get_name(), 0, 18, "..."),
    //                 $qty,
    //                 number_format($price, 2),
    //                 number_format($tax_total, 2),
    //                 number_format($line_total, 2)
    //             );
    //         } else {
    //             $slip .= sprintf(
    //                 "%-20s %-5s %-10s %-10s\n",
    //                 mb_strimwidth($item->get_name(), 0, 18, "..."),
    //                 $qty,
    //                 number_format($price, 2),
    //                 number_format($line_total, 2)
    //             );
    //         }
    //     }

    //     $slip .= "---------------------------------------------\n";
    //     if ( $show_tax ) {
    //         $slip .= sprintf("%-20s %-5s %-10s %-8s %-10s\n", "Total", "", "", "", number_format($grand_total, 2));
    //     } else {
    //         $slip .= sprintf("%-20s %-5s %-10s %-10s\n", "Total", "", "", number_format($grand_total, 2));
    //     }
    //     $slip .= "=============================================\n\n";

    //     return $slip;
    // }
    private function generate_slip($items, $kot_no, $header, $show_tax = true)
    {
        if (empty($items)) {
            return '';
        }

        $html  = '<div style="font-family: monospace, monospace, monospace; max-width: 600px; margin: 10px auto; border: 1px solid #ccc; padding: 10px;">';
        $html .= '<h2 style="text-align:center;">' . esc_html($header) . '</h2>';
        $html .= '<p style="text-align:center; font-weight: bold;">' . esc_html($kot_no) . '</p>';
        $html .= '<table style="width: 100%; border-collapse: collapse;">';
        $html .= '<thead><tr style="background:#eee;">';

        if ($show_tax) {
            $html .= '<th style="border:1px solid #ccc; padding:6px; text-align:left; min-width: 180px;">Item</th>';
            $html .= '<th style="border:1px solid #ccc; padding:6px; text-align:center; width:50px;">Qty</th>';
            $html .= '<th style="border:1px solid #ccc; padding:6px; text-align:right; width:80px;">Price</th>';
            $html .= '<th style="border:1px solid #ccc; padding:6px; text-align:right; width:80px;">Tax</th>';
            $html .= '<th style="border:1px solid #ccc; padding:6px; text-align:right; width:90px;">Total</th>';
        } else {
            $html .= '<th style="border:1px solid #ccc; padding:6px; text-align:left; min-width: 180px;">Item</th>';
            $html .= '<th style="border:1px solid #ccc; padding:6px; text-align:center; width:50px;">Qty</th>';
            $html .= '<th style="border:1px solid #ccc; padding:6px; text-align:right; width:80px;">Price</th>';
            $html .= '<th style="border:1px solid #ccc; padding:6px; text-align:right; width:90px;">Total</th>';
        }

        $html .= '</tr></thead><tbody>';

        $grand_total = 0;

        foreach ($items as $item) {
            $product = $item->get_product();
            $qty = $item->get_quantity();
            $price = $product ? wc_get_price_to_display($product) : 0;
            $line_total = $item->get_total();
            $tax_total = array_sum($item->get_taxes()['total'] ?? []);
            $grand_total += $line_total;

            $item_name = esc_html(mb_strimwidth($item->get_name(), 0, 30, "..."));

            $html .= '<tr>';
            $html .= '<td style="border:1px solid #ccc; padding:6px;">' . $item_name . '</td>';
            $html .= '<td style="border:1px solid #ccc; padding:6px; text-align:center;">' . esc_html($qty) . '</td>';
            $html .= '<td style="border:1px solid #ccc; padding:6px; text-align:right;">' . number_format($price, 2) . '</td>';

            if ($show_tax) {
                $html .= '<td style="border:1px solid #ccc; padding:6px; text-align:right;">' . number_format($tax_total, 2) . '</td>';
            }

            $html .= '<td style="border:1px solid #ccc; padding:6px; text-align:right;">' . number_format($line_total, 2) . '</td>';
            $html .= '</tr>';
        }

        // Total row
        $html .= '<tr style="font-weight:bold; background:#eee;">';
        if ($show_tax) {
            $html .= '<td style="border:1px solid #ccc; padding:6px;" colspan="4">Total</td>';
        } else {
            $html .= '<td style="border:1px solid #ccc; padding:6px;" colspan="3">Total</td>';
        }
        $html .= '<td style="border:1px solid #ccc; padding:6px; text-align:right;">' . number_format($grand_total, 2) . '</td>';
        $html .= '</tr>';

        $html .= '</tbody></table>';
        $html .= '</div>';

        return $html;
    }
    // private function send_to_printer($restaurant_id, $zone_id, $slip, $printer_type) {
    //     if (empty($slip)) return;
    //     $printer_api_url = "https://your-print-server.local/api/print";
    //     wp_remote_post($printer_api_url, [
    //         'timeout' => 15,
    //         'body' => [
    //             'restaurant_id' => $restaurant_id,
    //             'zone_id'       => $zone_id,
    //             'printer_type'  => $printer_type,
    //             'content'       => $slip,
    //         ]
    //     ]);
    // }
    public function get_order_by_table($request)
    {
        $params = $request->get_params();
        $restaurant_id = $params['restaurant_id'] ? intval($params['restaurant_id']) : 0;
        $zone_id       = $params['zone_id'] ? intval($params['zone_id']) : 0;
        $table_id      = $params['table_id'] ? intval($params['table_id']) : 0;
        // $logger  = wc_get_logger();
        // $context = [ 'source' => 'pinaka-pos-active-order' ];

        $active_orders = wc_get_orders([
            'status'    => [ 'processing', 'pending' ],
            'limit'     => 1,
            'meta_query'=> [
                'relation' => 'AND',
                [ 'key' => '_restaurant_id', 'value' => $restaurant_id ],
                [ 'key' => '_zone_id', 'value' => $zone_id ],
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
            $table_order   = reset( $active_orders );
            $table_post    = get_post( $table_order->get_meta('_table_id') );
            $zone_post     = get_post( $table_order->get_meta('_zone_id') );
            $restaurant_id = $table_order->get_meta('_restaurant_id');

            $parent_order_id = absint( $table_order->get_id() );
            
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
                AND ( $status_field NOT IN ('trash') )
            ", $parent_order_id));

            $kot_orders = [];

            foreach ( $children as $child_id ) 
            {
                $child_order = wc_get_order( $child_id );
                if ( ! $child_order ) {
                    continue;
                }
                if ( $child_order->get_status() === 'cancelled' ) {
                    continue;
                }
                $line_items = [];
                foreach ( $child_order->get_items() as $item_id => $item ) 
                {
                    if ( 'cancelled' === $item->get_meta( '_item_status', true ) ) {
                        continue; // Skip cancelled items
                    }
                    $product   = $item->get_product();
                    $name      = $item->get_name();
                    $qty       = $item->get_quantity();
                    $total     = $item->get_total();
                    $price     = $product ? wc_get_price_to_display( $product ) : 0;

                    $all_modifiers = [];
                    $modifier_amount  = 0;
                    $gst_amount      = 0;

                    foreach ( $item->get_meta_data() as $meta ) {
                        $meta_key   = $meta->key;
                        $meta_value = $meta->value;
                        if ( in_array( $meta_key, [ '_addons', '_modifiers' ] ) && is_array( $meta_value ) ) {
                            foreach ( $meta_value as $mod ) {
                                if ( ! empty( $mod['id'] ) ) {
                                    $mod_post = get_post( $mod['id'] );
                                    if ( $mod_post && $mod_post->post_type === 'modifier' ) {
                                        $all_modifiers[] = $mod_post->post_title;
                                    }
                                } else {
                                    $all_modifiers[] = $mod['name'];
                                }
                            }
                        }
                        if ($meta_key === '_extra_modifier_amount') {
                            $modifier_amount += floatval($meta_value);
                        }
                    }

                    $combo_items = [];
                    if ( $product && $product->is_type( 'combo_bundle' ) ) {
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

                    $tax_data = $item->get_taxes();
                    if ( isset($tax_data['total']) ) {
                        foreach ( $tax_data['total'] as $tax_amount ) {
                            $gst_amount += floatval($tax_amount);
                        }
                        $total += $gst_amount;
                    }
                    $total += $modifier_amount;

                    $line_items[] = [
                        'id'         => $item_id,
                        'product_id' => $product ? $product->get_id() : 0,
                        'item_name'  => $name,
                        'quantity'   => $qty,
                        'price'      => floatval($price),
                        'amount'     => floatval( $total ),
                        'modifiers'  => $all_modifiers,
                        'combos'     => $combo_items,
                    ];
                }

                // $customer_id = $child_order->get_customer_id();
                // if ( $customer_id ) {
                //     $user       = get_userdata( $customer_id );
                //     $created_by = $user ? $user->display_name : 'guest';
                // } else {
                //     $created_by = 'guest';
                // }

                $kot_orders[] = [
                    'id'                   => $child_order->get_id(),
                    'time'                 => $child_order->get_date_created() ? $child_order->get_date_created()->date('Y-m-d h:i A') : '',
                    'status'               => wc_get_order_status_name( $child_order->get_data()['status'] ),
                    'total'                => floatval($child_order->get_total()),
                    'kot_number'           => $child_order->get_meta('_kot_number',true),
                    // 'order_by'             => $created_by,
                    'line_items'           => $line_items,
                ];
            }

            $response = [
                "restaurant_id"  => intval($restaurant_id),
                'order_id'      => $table_order->get_id(),
                'order_type'    => $table_order->get_meta('_table_status'),
                'tableStatus'  => $table_order->get_meta('_table_status'),
                'table_name'    => $table_post ? $table_post->post_title : '',
                'guest_count'   => intval($table_order->get_meta('_guest_count')),
                'zone_id'       => $zone_post ? intval($table_order->get_meta('_zone_id')) : 0,
                'zone_name'     => $zone_post ? $zone_post->post_title : '',
                'kot_orders'     => $kot_orders,
            ];
            return rest_ensure_response( $response );
        }
        return [
            'success' => false,
            'message' => __( 'No active parent order for this table.', 'pinaka-pos' ),
        ];
    }
    public function get_dinein_table_order_kots( $request ) 
    {
        $params = $request->get_params();
        $restaurant_id = isset($params['restaurant_id']) ? intval($params['restaurant_id']) : 0;
        $zone_id       = isset($params['zone_id']) ? intval($params['zone_id']) : 0;
        $order_id      = isset($params['order_id']) ? intval($params['order_id']) : 0;

        $parent_order = wc_get_order($order_id);
        if ( ! $parent_order ) {
            return new WP_Error('invalid_order', 'Parent order not found.', ['status' => 404]);
        }

        global $wpdb;
        $use_hpos = class_exists('\Automattic\WooCommerce\Utilities\OrderUtil') &&
                    method_exists('\Automattic\WooCommerce\Utilities\OrderUtil','custom_orders_table_usage_is_enabled') &&
                    \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();

        $orders_table  = $use_hpos ? $wpdb->prefix . 'wc_orders' : $wpdb->prefix . 'posts';
        $id_field      = $use_hpos ? 'id' : 'ID';
        $parent_field  = $use_hpos ? 'parent_order_id' : 'post_parent';
        $status_field  = $use_hpos ? 'status' : 'post_status';

        $children = $wpdb->get_col($wpdb->prepare("
            SELECT $id_field FROM {$orders_table}
            WHERE $parent_field = %d
            AND ( $status_field NOT IN ('trash') )
        ", $order_id));

        if ( empty($children) ) 
        {
            return new WP_Error('no_kots', 'No KOTs found for this parent order.', ['status' => 400]);
        }

        $kot_orders = [];
        $grand_total    = 0;
        $tax_total      = 0;

        foreach ( $children as $child_id ) 
        {
            $child_order = wc_get_order( $child_id );
            if ( ! $child_order || $child_order->get_status() === 'cancelled' ) continue;

            $kot_grand_total = 0;
            $kot_tax_total   = 0;
            $line_items = [];

            foreach ( $child_order->get_items() as $item_id => $item ) 
            {
                if ( 'cancelled' === $item->get_meta( '_item_status', true ) ) {
                    continue; // Skip cancelled items
                }
                $product = $item->get_product();
                $name    = $item->get_name();
                $qty     = $item->get_quantity();
                $total   = $item->get_total();
                $price   = $product ? wc_get_price_to_display( $product ) : 0;
                $line_total = $item->get_total();
                $line_tax   = $item->get_total_tax();
                $all_modifiers = [];
                $modifier_amount = 0;
                $gst_amount = 0;

                foreach ( $item->get_meta_data() as $meta ) 
                {
                    $meta_key   = $meta->key;
                    $meta_value = $meta->value;

                    if ( in_array($meta_key, ['_addons','_modifiers']) && is_array($meta_value) ) 
                    {
                        foreach ($meta_value as $mod) 
                        {
                            if (!empty($mod['id'])) 
                            {
                                $mod_post = get_post($mod['id']);
                                if ($mod_post && $mod_post->post_type==='modifier') 
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
                if ( $product && $product->is_type('combo_bundle') ) 
                {
                    $combo_meta = get_post_meta( $product->get_id(), '_combo_bundle_items', true );
                    $combo_meta = json_decode( $combo_meta, true );
                    if ( ! empty($combo_meta) ) 
                    {
                        foreach ($combo_meta as $combo_item) 
                        {
                            $child_product = wc_get_product($combo_item['id']);
                            if ($child_product) 
                            {
                                $combo_items[] = [
                                    'id'       => $child_product->get_id(),
                                    'name'     => $child_product->get_name(),
                                    'price'    => wc_get_price_to_display($child_product),
                                    'quantity' => intval($combo_item['qty']),
                                ];
                            }
                        }
                    }
                }

                $tax_data = $item->get_taxes();
                if ( isset($tax_data['total']) ) 
                {
                    foreach ($tax_data['total'] as $tax_amount) 
                    {
                        $gst_amount += floatval($tax_amount);
                    }
                    $total += $gst_amount;
                }

                $total += $modifier_amount;
                
                $kot_grand_total += $line_total;
                $kot_tax_total   += $line_tax;

                $line_items[] = [
                    'id'         => $item_id,
                    'product_id' => $product ? $product->get_id() : 0,
                    'item_name'  => $name,
                    'quantity'   => $qty,
                    'price'      => floatval($price),
                    'amount'     => floatval($total),
                    'modifiers'  => $all_modifiers,
                    'combos'     => $combo_items,
                ];
            }

            $grand_total += $kot_grand_total;
            $tax_total   += $kot_tax_total;

            $kot_orders[] = [
                'id'         => $child_order->get_id(),
                'time'       => $child_order->get_date_created() ? $child_order->get_date_created()->date('Y-m-d h:i A') : '',
                'status'     => wc_get_order_status_name($child_order->get_data()['status']),
                'total'      => floatval($child_order->get_total()),
                'kot_number' => $child_order->get_meta('_kot_number', true),
                'line_items' => $line_items,
            ];
        }

        $fee_total     = 0;
        $fee_tax_total = 0;
        $coupon_total  = 0;
        foreach ($parent_order->get_items('fee') as $fee) {
            $fee_total      += $fee->get_total();
            $fee_tax_total  += $fee->get_total_tax();
        }
        foreach ($parent_order->get_items('coupon') as $coupon) 
        {
            $coupon_code = $coupon->get_code();
            $wc_coupon = new WC_Coupon($coupon_code);
            $discount_type = $wc_coupon->get_discount_type();
            $coupon_amount = 0;
            if ($discount_type === 'fixed_cart') {
                $coupon_amount = $wc_coupon->get_amount();
            } elseif ($discount_type === 'percent') {
                $discount_percentage = $wc_coupon->get_amount();
                $coupon_amount = ($discount_percentage / 100) * $grand_total;
            } elseif ($discount_type === 'fixed_product') {
                $product_ids = $wc_coupon->get_product_ids();
                $coupon_amount_per_item = $wc_coupon->get_amount();
                foreach ($kot_orders as $kot) 
                {
                    foreach ($kot['line_items'] as $item) 
                    {
                        $product_id = $item['product_id'];
                        if (empty($product_ids) || in_array($product_id, $product_ids)) 
                        {
                            $coupon_amount += $coupon_amount_per_item * $item['quantity'];
                        }
                    }
                }
            }
            $coupon_tax     = $coupon->get_discount_tax();
            $coupon_total  += $coupon_amount;
            $tax_total     -= $coupon_tax;
        }

        $net_total   = $grand_total + $tax_total + $fee_total + $fee_tax_total;
        $final_total = max( 0, $net_total - $coupon_total );

        $email_html = $this->render_kot_email_html_for_api( $parent_order, $kot_orders, 'Pinaka Restaurant', $grand_total, $tax_total, $fee_total, $fee_tax_total, $coupon_total, $final_total);
        $plugin_dir_path = plugin_dir_path(__FILE__) . 'kot-emails';        
        $plugin_url = plugin_dir_url(__FILE__) . 'kot-emails';
        if ( ! file_exists( $plugin_dir_path ) ) 
        {
            wp_mkdir_p( $plugin_dir_path );
        }
        $filename = $plugin_dir_path . '/kot-' . $parent_order->get_id() . '-' . time() . '.html';
        file_put_contents( $filename, $email_html );
        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail(
            'KranthiGodishala@AlekyaTechSolutions.com',
            'New KOT Order #' . $parent_order->get_order_number(),
            $email_html,
            $headers
        );
        return rest_ensure_response([
            'success'    => true,
            'message'    => 'email sent successfully'
        ]);
    }
    private function render_kot_email_html_for_api($parent_order, $kot_orders, $restaurant_name = 'Pinaka Restaurant', $grand_total = 0,
        $tax_total = 0, $fee_total = 0, $fee_tax_total = 0, $coupon_total = 0, $final_total = 0) {
        if ($fee_total < 0) {
            $fee_label = 'Discount';
        } else {
            $fee_label = 'Fees';
        }
        $billing_address  = $parent_order->get_address('billing');
        $shipping_address = $parent_order->get_address('shipping');
        $customer_name    = !empty($billing_address['first_name']) ? $billing_address['first_name'] . ' ' . ($billing_address['last_name'] ?? '') : '';

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>New KOT Order</title>';
        $html .= '<style>
            body {
                font-family: Arial, sans-serif;
                font-size: 14px;
                color: #515151;
                background-color: #f8f8f8;
                margin: 0;
                padding: 0;
            }
            .email-wrapper {
                max-width: 700px;
                margin: 20px auto;
                background: #fff;
                padding: 20px;
                border: 1px solid #eaeaea;
            }
            h1, h2, h3, h4 {
                color: #333;
                margin-bottom: 10px;
            }
            h2 {
                font-size: 18px;
            }
            p {
                margin: 5px 0;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
                table-layout: fixed; /* Fix table layout for columns */
                word-wrap: break-word;
            }
            table th, table td {
                border: 1px solid #eaeaea;
                padding: 10px;
                vertical-align: top;
            }
            table th {
                background-color: #f2f2f2;
                text-align: left;
            }
         
            table th:nth-child(1), table td:nth-child(1) {
                min-width: 200px;
                max-width: 300px;
            }
            table th:nth-child(2), table td:nth-child(2) {
                width: 50px;
                text-align: center;
            }
            table th:nth-child(3), table td:nth-child(3),
            table th:nth-child(4), table td:nth-child(4) {
                width: 80px;
                text-align: right;
            }
            .total-row td {
                font-weight: bold;
            }
            .kot-header {
                background-color: #f7f7f7;
                padding: 10px;
                font-weight: bold;
                margin-bottom: 10px;
                border: 1px solid #eaeaea;
            }
            .combo-items, .modifier-items {
                font-style: italic;
                font-size: 13px;
                color: #555;
            }
        </style></head><body>';

        $html .= '<div class="email-wrapper">';
        $html .= '<h1>' . esc_html($restaurant_name) . '</h1>';
        $html .= '<h2>New order: #' . esc_html($parent_order->get_order_number()) . '</h2>';
        $html .= '<p>You’ve received a new order from ' . esc_html($customer_name) . ':</p>';

        foreach ($kot_orders as $kot) 
        {
            $html .= '<div class="kot-header">' . esc_html($kot['kot_number']) .'</div>';
            $html .= '<p><strong>Time:</strong> ' . esc_html($kot['time']) . '</p>';

            $html .= '<table><thead><tr>';
            $html .= '<th>Product</th><th>Qty</th><th>Price</th><th>Amount</th>';
            $html .= '</tr></thead><tbody>';

            foreach ($kot['line_items'] as $item) {
                $html .= '<tr>';
                $html .= '<td>' . esc_html($item['item_name']);

                if (!empty($item['modifiers'])) {
                    $html .= '<br><span class="modifier-items">Modifiers: ' . esc_html(implode(', ', $item['modifiers'])) . '</span>';
                }
                if (!empty($item['combos'])) {
                    $combo_list = [];
                    foreach ($item['combos'] as $combo) {
                        $combo_list[] = $combo['name'] . ' x' . $combo['quantity'];
                    }
                    $html .= '<br><span class="combo-items">Combo Items: ' . esc_html(implode(', ', $combo_list)) . '</span>';
                }

                $html .= '</td>';
                $html .= '<td>' . esc_html($item['quantity']) . '</td>';
                $html .= '<td>' . wc_price($item['price']) . '</td>';
                $html .= '<td>' . wc_price($item['amount']) . '</td>';
                $html .= '</tr>';
            }

            $html .= '<tr class="total-row">';
            $html .= '<td colspan="3" style="text-align:right;background:#f7f7f7;padding:10px;">KOT Total</td>';
            $html .= '<td style="font-weight:bold;background:#f7f7f7;padding:10px;">' . wc_price($kot['total']) . '</td>';
            $html .= '</tr>';

            $html .= '</tbody></table>';
        }
        $payment_method_title = $parent_order->get_payment_method_title();
   
        $html .= '<table style="width:100%;">';
        $html .= '<tr><td style="text-align:right; padding:5px 0;"><strong>Gross Total:</strong> ' . wc_price($grand_total) . '</td></tr>';
        $html .= '<tr><td style="text-align:right; padding:5px 0;"><strong>Tax:</strong> ' . wc_price($tax_total + $fee_tax_total) . '</td></tr>';
        $html .= '<tr><td style="text-align:right; padding:5px 0;"><strong>' . $fee_label . ':</strong> ' . wc_price($fee_total) . '</td></tr>';
        $html .= '<tr><td style="text-align:right; padding:5px 0;"><strong>Coupon Discount:</strong> -' . wc_price($coupon_total) . '</td></tr>';
        $html .= '<tr><td style="text-align:right; padding:8px 0; font-size:16px; font-weight:bold; border-top:1px solid #eaeaea;"><strong>Final Total:</strong> ' . wc_price($final_total) . '</td></tr>';
        $html .= '<tr><td style="text-align:right; padding:5px 0;"><strong>Payment method:</strong> ' . esc_html($payment_method_title) . '</td></tr>';
        $html .= '</table>';

        if ($customer_note = $parent_order->get_customer_note()) {
            $html .= '<h3>Customer note</h3>';
            $html .= '<p>' . nl2br(esc_html($customer_note)) . '</p>';
        }

        $billing_html  = '';
        $shipping_html = '';

        if (!empty($billing_address)) 
        {
            $billing_html .= '<h3>Billing address</h3><p>';
            $billing_html .= (!empty($billing_address['first_name']) ? esc_html($billing_address['first_name']) . ' ' : '');
            $billing_html .= (!empty($billing_address['last_name']) ? esc_html($billing_address['last_name']) . '<br>' : '');
            $billing_html .= (!empty($billing_address['company']) ? esc_html($billing_address['company']) . '<br>' : '');
            $billing_html .= (!empty($billing_address['address_1']) ? esc_html($billing_address['address_1']) . '<br>' : '');
            $billing_html .= (!empty($billing_address['address_2']) ? esc_html($billing_address['address_2']) . '<br>' : '');
            $billing_html .= (!empty($billing_address['city']) ? esc_html($billing_address['city']) . ', ' : '');
            $billing_html .= (!empty($billing_address['state']) ? esc_html($billing_address['state']) . ' ' : '');
            $billing_html .= (!empty($billing_address['postcode']) ? esc_html($billing_address['postcode']) . '<br>' : '');
            $billing_html .= (!empty($billing_address['country']) ? esc_html($billing_address['country']) . '<br>' : '');
            $billing_html .= (!empty($billing_address['phone']) ? esc_html($billing_address['phone']) . '<br>' : '');
            $billing_html .= (!empty($billing_address['email']) ? esc_html($billing_address['email']) : '');
            $billing_html .= '</p>';
        }

        if (!empty($shipping_address)) 
        {
            $shipping_html .= '<h3>Shipping address</h3><p>';
            $shipping_html .= (!empty($shipping_address['first_name']) ? esc_html($shipping_address['first_name']) . ' ' : '');
            $shipping_html .= (!empty($shipping_address['last_name']) ? esc_html($shipping_address['last_name']) . '<br>' : '');
            $shipping_html .= (!empty($shipping_address['company']) ? esc_html($shipping_address['company']) . '<br>' : '');
            $shipping_html .= (!empty($shipping_address['address_1']) ? esc_html($shipping_address['address_1']) . '<br>' : '');
            $shipping_html .= (!empty($shipping_address['address_2']) ? esc_html($shipping_address['address_2']) . '<br>' : '');
            $shipping_html .= (!empty($shipping_address['city']) ? esc_html($shipping_address['city']) . ', ' : '');
            $shipping_html .= (!empty($shipping_address['state']) ? esc_html($shipping_address['state']) . ' ' : '');
            $shipping_html .= (!empty($shipping_address['postcode']) ? esc_html($shipping_address['postcode']) . '<br>' : '');
            $shipping_html .= (!empty($shipping_address['country']) ? esc_html($shipping_address['country']) . '<br>' : '');
            $shipping_html .= (!empty($shipping_address['phone']) ? esc_html($shipping_address['phone']) : '');
            $shipping_html .= '</p>';
        }

        $html .= '<div style="display:flex; justify-content:space-between; flex-wrap:wrap; margin-top:20px;">';
        $html .= '<div style="width:45%; vertical-align:top;">' . $billing_html . '</div>';
        $html .= '<div style="width:45%; vertical-align:top; text-align:right;">' . $shipping_html . '</div>';
        $html .= '</div>';

        $html .= '<hr>';
        $html .= '<p style="text-align:center; color:#777; font-size:12px;">Thanks for using ' . esc_html($restaurant_name) . '!<br>© ' . esc_html($restaurant_name) . '</p>';
        $html .= '</div></body></html>';

        return $html;
    }
}
?>