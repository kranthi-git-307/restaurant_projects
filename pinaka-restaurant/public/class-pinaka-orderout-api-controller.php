<?php
if (!defined('ABSPATH')) {
    exit;
}

class Pinaka_Orderout_API_Controller 
{
    protected $namespace = 'pinaka-restaurant-pos/v1';
    protected $rest_base = 'orderout';
    public function register_routes() 
    {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/create-menu',
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'create_menu' ],
                'permission_callback' => [ $this, 'check_user_role_permission' ],
            ]
        );
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/get-menu',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'import_orderout_menu' ],
                'permission_callback' => [ $this, 'check_user_role_permission' ],
            ]
        );
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/product-modifiers-mapping',
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'product_modifiers_mapping' ],
                'permission_callback' => [ $this, 'check_user_role_permission' ],
            ]
        );
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/upload-menu',
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'upload_menu' ],
                'permission_callback' => [ $this, 'check_user_role_permission' ],
            ]
        );
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/create-order',
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'create_order_from_orderout' ],
                'permission_callback' => [ $this, 'check_user_role_permission' ],
            ]
        );
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/oauth-callback',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'oauth_callback' ],
                'permission_callback' => '__return_true',
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
	public function create_order_from_orderout( $request ) {

    	$logger  = wc_get_logger();
    	$context = array(
    		'source' => 'orderout-router',
    	);
    
    	$payload = (array) $request->get_json_params();
    
    	$logger->info(
    		'OrderOut routing request received.',
    		$context
    	);
    
    	$logger->debug(
    		'OrderOut request payload: ' . wp_json_encode( $payload ),
    		$context
    	);
    
    	/*
    	 * Route only orders received from OrderOut.
    	 */
    	$source_name = strtolower(
    		sanitize_text_field(
    			$payload['source']['ods']['name'] ?? ''
    		)
    	);
    
    	$logger->info(
    		sprintf(
    			'Detected source: %s | External Reference ID: %s | Order Number: %s',
    			$source_name,
    			$payload['source']['externalReferenceId'] ?? '',
    			$payload['source']['orderNumber'] ?? ''
    		),
    		$context
    	);
    
    	/*
    	 * Remove $creating if this function itself is only called
    	 * when a new OrderOut order is being processed.
    	 */
    	if ( 'orderout' === $source_name ) {
    
    		$routes = get_option(
    			'pinakaonlineorders_restaurant_destinations',
    			array()
    		);
    
    		$logger->debug(
    			'Configured restaurant routes: ' . wp_json_encode( $routes ),
    			$context
    		);
    
    		if ( is_array( $routes ) && ! empty( $routes ) ) {
    
    			$restaurant_id = sanitize_text_field(
    				(string) (
    					$payload['destination']['restaurantId']
    					?? ''
    				)
    			);
    
    // 			if (
    // 				'' === $restaurant_id &&
    // 				! empty( $payload['destination']['externalRestaurantId'] )
    // 			) {
    				$restaurant_id = sanitize_text_field(
    					(string) $payload['destination']['externalRestaurantId']
    				);
    
    				$logger->info(
    					sprintf(
    						'Using externalRestaurantId as fallback restaurant ID: %s',
    						$restaurant_id
    					),
    					$context
    				);
    // 			}
    
    			$logger->info(
    				sprintf(
    					'Restaurant routing ID detected: %s',
    					$restaurant_id
    				),
    				$context
    			);
    
    			if ( '' === $restaurant_id ) {
    
    				$logger->error(
    					'Restaurant ID is missing from OrderOut payload.',
    					$context
    				);
    
    				return new WP_Error(
    					'restaurant_id_missing',
    					'The OrderOut payload does not contain a restaurant ID.',
    					array(
    						'status' => 400,
    					)
    				);
    			}
    
    			if ( ! isset( $routes[ $restaurant_id ] ) ) {
    
    				$logger->error(
    					sprintf(
    						'No restaurant destination mapping found for restaurant ID: %s',
    						$restaurant_id
    					),
    					$context
    				);
    
    				return new WP_Error(
    					'restaurant_route_not_found',
    					sprintf(
    						'No destination website is configured for restaurant %s.',
    						$restaurant_id
    					),
    					array(
    						'status' => 404,
    					)
    				);
    			}
    
    			$configured_route = $routes[ $restaurant_id ];
    
    			if ( is_array( $configured_route ) ) {
    
    				$website = isset( $configured_route['website'] )
    					? $configured_route['website']
    					: '';
    
    				$jwt_token = isset( $configured_route['jwt_token'] )
    					? $configured_route['jwt_token']
    					: '';
    
    			} else {
    
    				$website   = $configured_route;
    				$jwt_token = '';
    			}
    
    			$website = untrailingslashit(
    				esc_url_raw( $website )
    			);
    
    			$logger->info(
    				sprintf(
    					'Restaurant destination resolved. Restaurant ID: %s | Website: %s',
    					$restaurant_id,
    					$website
    				),
    				$context
    			);
    
    			if (
    				empty( $website ) ||
    				! wp_http_validate_url( $website )
    			) {
    
    				$logger->error(
    					sprintf(
    						'Invalid restaurant website configured for restaurant ID %s. Website: %s',
    						$restaurant_id,
    						$website
    					),
    					$context
    				);
    
    				return new WP_Error(
    					'restaurant_route_invalid',
    					'The configured restaurant destination is invalid.',
    					array(
    						'status' => 500,
    					)
    				);
    			}
    
    			$destination_url =
    				$website .
    				'/wp-json/pinaka-restaurant-pos/v1/orders';
                
    			$headers = array(
    				'Content-Type' => 'application/json',
    				'Accept'       => 'application/json',
    			);
    
    			if ( ! empty( $jwt_token ) ) {
    
    				$jwt_token = preg_replace(
    					'/^Bearer\s+/i',
    					'',
    					trim( $jwt_token )
    				);
    
    				$headers['Authorization'] =
    					'Bearer ' . $jwt_token;
    
    				/*
    				 * Don't log the actual JWT token.
    				 */
    				$logger->debug(
    					'JWT Authorization header added for restaurant request.',
    					$context
    				);
    			}
    
    			$logger->info(
    				sprintf(
    					'Forwarding OrderOut order. Order: %s | Restaurant: %s | Destination: %s',
    					$payload['source']['orderNumber'] ?? '',
    					$restaurant_id,
    					$destination_url
    				),
    				$context
    			);
    
    // 			$response = wp_remote_post(
    // 				$destination_url,
    // 				array(
    // 					'timeout'     => 30,
    // 					'redirection' => 3,
    // 					'headers'     => $headers,
    // 					'body'        => wp_json_encode( $payload ),
    // 					'data_format' => 'body',
    // 				)
    // 			);
                $destination_url = 'https://pch.alektasolutions.com/connector/api/v1/connectors/orderout/webhook';

                $response = wp_remote_post(
                	$destination_url,
                	array(
                		'timeout'     => 30,
                		'redirection' => 3,
                		'headers'     => array(
                			'Content-Type' => 'application/json',
                		),
                		'body'        => wp_json_encode( $payload ),
                		'data_format' => 'body',
                	)
                );
    			if ( is_wp_error( $response ) ) {
    
    				$logger->error(
    					sprintf(
    						'OrderOut forwarding failed. Restaurant: %s | Error: %s',
    						$restaurant_id,
    						$response->get_error_message()
    					),
    					$context
    				);
    
    				return new WP_Error(
    					'restaurant_forward_failed',
    					$response->get_error_message(),
    					array(
    						'status' => 502,
    					)
    				);
    			}
    
    			$status = wp_remote_retrieve_response_code(
    				$response
    			);
    
    			$response_body = wp_remote_retrieve_body(
    				$response
    			);
    
    			$response_data = json_decode(
    				$response_body,
    				true
    			);
    
    			if ( JSON_ERROR_NONE !== json_last_error() ) {
    				$response_data = $response_body;
    			}
    
    			$logger->info(
    				sprintf(
    					'Restaurant response received. Restaurant: %s | HTTP Status: %s',
    					$restaurant_id,
    					$status
    				),
    				$context
    			);
    
    			$logger->debug(
    				'Restaurant response body: ' .
    				(
    					is_array( $response_data )
    						? wp_json_encode( $response_data )
    						: $response_data
    				),
    				$context
    			);
    
    			if ( $status < 200 || $status >= 300 ) {
    
    				$logger->error(
    					sprintf(
    						'Restaurant rejected OrderOut order. Order: %s | Restaurant: %s | HTTP Status: %s | Response: %s',
    						$payload['source']['orderNumber'] ?? '',
    						$restaurant_id,
    						$status,
    						is_array( $response_data )
    							? wp_json_encode( $response_data )
    							: $response_data
    					),
    					$context
    				);
    
    				return new WP_Error(
    					'restaurant_forward_rejected',
    					'The restaurant website rejected the OrderOut order.',
    					array(
    						'status' => $status ?: 502,
    
    						'restaurant_id' =>
    							$restaurant_id,
    
    						'external_restaurant_id' =>
    							$payload['destination']['externalRestaurantId']
    							?? '',
    
    						'destination_response' =>
    							$response_data,
    					)
    				);
    			}
    
    			$logger->info(
    				sprintf(
    					'OrderOut order routed successfully. External Reference: %s | Order Number: %s | Restaurant: %s',
    					$payload['source']['externalReferenceId'] ?? '',
    					$payload['source']['orderNumber'] ?? '',
    					$restaurant_id
    				),
    				$context
    			);
    
    			return new WP_Error(
    				'pinaka_order_routed',
    				'OrderOut order routed successfully; no central order was created.',
    				array(
    					'status' => 200,
    
    					'routed' => true,
    
    					'restaurant_id' =>
    						$restaurant_id,
    
    					'external_restaurant_id' =>
    						$payload['destination']['externalRestaurantId']
    						?? '',
    
    					'orderout_order_id' =>
    						$payload['source']['externalReferenceId']
    						?? '',
    
    					'orderout_order_number' =>
    						$payload['source']['orderNumber']
    						?? '',
    
    					'destination_response' =>
    						$response_data,
    				)
    			);
    		}
    
    		$logger->info(
    			'No restaurant destination mappings configured. Order will continue with local WooCommerce processing.',
    			$context
    		);
    	}
    
    	$logger->debug(
    		'Request is not routed through OrderOut restaurant routing.',
    		$context
    	);
    }
	public function oauth_callback($request)
	{
        $code = $request->get_param( 'code' );

        update_option(
            'uber_auth_code',
            $code
        );

        return [
            'success' => true,
            'code'    => $code,
        ];
	}
    public function create_menu( $request ) 
    {  
        $logger = wc_get_logger();

        $logger->info(
            'OrderOut Create Menu Request: ' . wp_json_encode(
                [
                    'method'      => $request->get_method(),
                    'route'       => $request->get_route(),
                    'params'      => $request->get_params(),
                    'json_params' => $request->get_json_params(),
                    'headers'     => $request->get_headers(),
                ],
                JSON_PRETTY_PRINT
            ),
            [ 'source' => 'orderout-menu' ]
        );
    }
    public function product_modifiers_mapping($request)
    {
        $params = $request->get_params();

        $restaurant_id = sanitize_text_field( $params['restaurant_id'] );
        $menu_id       = sanitize_text_field( $params['menu_id'] );
        $api_key = 's11pfICCvVLnNsQujYu60TuAo6OLqyhF';
        if ( empty( $restaurant_id ) ) {
            return new WP_Error(
                'missing_restaurant_id',
                'restaurant_id is required.',
                [ 'status' => 400 ]
            );
        }
    
        if ( empty( $menu_id ) ) {
            return new WP_Error(
                'missing_menu_id',
                'menu_id is required.',
                [ 'status' => 400 ]
            );
        }
        $endpoint = "https://api.orderout.co/api/pos/restaurant/{$restaurant_id}/menu/{$menu_id}";

        $response = wp_remote_get(
            $endpoint,
            [
                'timeout' => 15,
                'headers' => [
                    'accept'  => 'application/json',
                    'api-key' => $api_key,
                ],
            ]
        );
    
        if ( is_wp_error( $response ) ) {
            return $response;
        }
    
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
    
        if ( empty( $body['items'] ) ) {
            return new WP_Error( 'invalid_menu', 'No items found in OrderOut menu.' );
        }
    
        /**
         * Get all synced WooCommerce products
         */
        $products = get_posts(
            [
                'post_type'      => 'product',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'meta_query'     => [
                    [
                        'key'     => '_orderout_item_id',
                        'compare' => 'EXISTS',
                    ],
                ],
            ]
        );
    
        foreach ( $products as $product ) {
    
            $product_id = $product->ID;
    
            $orderout_item_id = get_post_meta(
                $product_id,
                '_orderout_item_id',
                true
            );
    
            if ( empty( $orderout_item_id ) ) {
                continue;
            }
    
            /**
             * Find modifiers assigned to this product
             */
            $modifier_posts = get_posts(
                [
                    'post_type'      => 'modifier',
                    'post_status'    => 'publish',
                    'posts_per_page' => -1,
                    'meta_query'     => [
                        [
                            'key'     => 'product_ids',
                            'value'   => '"' . $product_id . '"',
                            'compare' => 'LIKE',
                        ],
                    ],
                ]
            );
    
            $modifier_group_ids = [];
    
            foreach ( $modifier_posts as $modifier ) {
    
                $group_id = get_post_meta(
                    $modifier->ID,
                    '_orderout_modifier_group_id',
                    true
                );
    
                if ( ! empty( $group_id ) ) {
                    $modifier_group_ids[] = $group_id;
                }
            }
    
            /**
             * Update matching OrderOut item
             */
            foreach ( $body['items'] as &$item ) {
    
                if ( $item['id'] != $orderout_item_id ) {
                    continue;
                }
    
                $item['modifier_group_ids'] = [
                    'ids'       => array_values( array_unique( $modifier_group_ids ) ),
                    'overrides' => [],
                ];
    
                break;
            }
    
            unset( $item );
        }
    
        /**
         * Update Menu
         */
        $response = wp_remote_request(
            $endpoint,
            [
                'method'  => 'PUT',
                'timeout' => 15,
                'headers' => [
                    'accept'       => 'application/json',
                    'api-key'      => $api_key,
                    'content-type' => 'application/json',
                ],
                'body' => wp_json_encode( $body ),
            ]
        );
    
        if ( is_wp_error( $response ) ) {
            return $response;
        }
    
        return json_decode(
            wp_remote_retrieve_body( $response ),
            true
        );
    }
    public function upload_menu( $request ) 
    {  
        $params = $request->get_params();
        
        $restaurant_id = $params['restaurant_id'];
        if ( empty( $restaurant_id ) ) {
            return new WP_Error(
                'missing_restaurant_id',
                'restaurant_id is required.',
                [ 'status' => 400 ]
            );
        }
        
        // $restaurant_id = "5189587907510272";
    
        $menu = $this->build_orderout_menu();
    
        $response = $this->send_to_orderout(
            $restaurant_id,
            $menu
        );
    
        return rest_ensure_response(
            $response
        );
    }
    public function build_orderout_menu() {

        $menu = [
            'menus'      => [],
            'categories' => [],
            'items'      => [],
            'modifier_groups'  => [],
            'modifiers'        => [],
        ];
    
        /**
         * Categories
         */
        $categories = get_terms(
            [
                'taxonomy'   => 'product_cat',
                'hide_empty' => true,
            ]
        );
    
        $category_map = [];
    
        foreach ( $categories as $category ) {
    
            $category_map[ $category->term_id ] = [
                'id' => (string) $category->term_id,
    
                'title' => [
                    'translations' => [
                        'en_us' => $category->name,
                    ],
                ],
    
                'entities' => [],
            ];
        }
    
        /**
         * Products
         */
        $product_ids = wc_get_products(
            [
                'return' => 'ids',
                'limit'  => -1,
            ]
        );
    
        foreach ( $product_ids as $product_id ) {
    
            $product = wc_get_product( $product_id );
    
            if ( ! $product ) {
                continue;
            }
            $tax_class = $product->get_tax_class();
            $tax_rates = WC_Tax::get_rates($tax_class);
            
            $total_tax_rate = 0;
            
            foreach ($tax_rates as $tax_rate_data) {
                $total_tax_rate += (float) $tax_rate_data['rate'];
            }
            $menu['items'][] = [
    
                'id' => (string) $product->get_id(),
    
                'title' => [
                    'translations' => [
                        'en_us' => $product->get_name(),
                    ],
                ],
    
                'description' => [
                    'translations' => [
                        'en_us' => wp_strip_all_tags(
                            $product->get_description()
                        ),
                    ],
                ],
    
                'quantity_info' => new stdClass(),
    
                'external_data' => 'WooCommerce Product #' . $product->get_id(),
    
                'price_info' => [
                    'price' => (int) (
                        floatval( $product->get_price() ) * 100
                    ),
                ],
                'tax_info' => [
                    'tax_rate' => $total_tax_rate,
                    'vat_rate_percentage' => 0,
                ],
            ];
           
            /**
             * Link product to categories
             */
            $product_categories = wp_get_post_terms(
                $product_id,
                'product_cat'
            );
    
            foreach ( $product_categories as $product_category ) {
    
                if ( isset( $category_map[ $product_category->term_id ] ) ) {
    
                    $category_map[ $product_category->term_id ]['entities'][] = [
                        'type' => 'ITEM',
                        'id'   => (string) $product->get_id(),
                    ];
                }
            }
        }
                /**
         * Modifiers
         */
        $modifier_posts = get_posts(
            [
                'post_type'      => 'modifier',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
            ]
        );
        
        foreach ( $modifier_posts as $modifier_post ) {
        
            $modifier_id = $modifier_post->ID;
        
            $modifier_type = get_post_meta(
                $modifier_id,
                'modifier_type',
                true
            );
        
            $price = (float) get_post_meta(
                $modifier_id,
                'modifier_price',
                true
            );
        
            $modifier_product_ids = get_post_meta(
                $modifier_id,
                'product_ids',
                true
            );
        
            if ( ! is_array( $modifier_product_ids ) ) {
                $modifier_product_ids = array_filter(
                    array_map(
                        'trim',
                        explode( ',', $modifier_product_ids )
                    )
                );
            }
        
            // Modifier
            $menu['modifiers'][] = [
                'id' => (string) $modifier_id,
            
                'title' => [
                    'translations' => [
                        'en_us' => get_the_title( $modifier_id ),
                    ],
                ],
            
                'external_data' => 'WooCommerce Modifier #' . $modifier_id,
            
                'price_info' => [
                    'price' => ( 'add-on' === $modifier_type )
                        ? (int) ( $price * 100 )
                        : 0,
                ],
            ];
        
            // Modifier Group
            $menu['modifier_groups'][] = [
                'id' => 'group-' . $modifier_id,
            
                'title' => [
                    'translations' => [
                        'en_us' => get_the_title( $modifier_id ),
                    ],
                ],
            
                'external_data' => 'WooCommerce Modifier Group #' . $modifier_id,
            
                'quantity_info' => [
                    'minimum_quantity' => 0,
                    'maximum_quantity' => 10,
                ],
            
                'modifier_options' => [
                    [
                        'id'   => (string) $modifier_id,
                        'type' => 'MODIFIER',
                    ],
                ],
            ];
        
        }
        /**
         * Categories
         */
        $menu['categories'] = array_values(
            $category_map
        );
    
        /**
         * Menu
         */
        $menu['menus'][] = [
    
            'id' => 'main-menu',
    
            'title' => [
                'translations' => [
                    'en_us' => 'Main Menu',
                ],
            ],
    
            'service_availability' => [
    
                [
                    'day_of_week' => 'monday',
                    'time_periods' => [
                        [
                            'start_time' => '00:00',
                            'end_time'   => '23:59',
                        ],
                    ],
                ],
                [
                    'day_of_week' => 'tuesday',
                    'time_periods' => [
                        [
                            'start_time' => '00:00',
                            'end_time'   => '23:59',
                        ],
                    ],
                ],
                [
                    'day_of_week' => 'wednesday',
                    'time_periods' => [
                        [
                            'start_time' => '00:00',
                            'end_time'   => '23:59',
                        ],
                    ],
                ],
                [
                    'day_of_week' => 'thursday',
                    'time_periods' => [
                        [
                            'start_time' => '00:00',
                            'end_time'   => '23:59',
                        ],
                    ],
                ],
                [
                    'day_of_week' => 'friday',
                    'time_periods' => [
                        [
                            'start_time' => '00:00',
                            'end_time'   => '23:59',
                        ],
                    ],
                ],
                [
                    'day_of_week' => 'saturday',
                    'time_periods' => [
                        [
                            'start_time' => '00:00',
                            'end_time'   => '23:59',
                        ],
                    ],
                ],
                [
                    'day_of_week' => 'sunday',
                    'time_periods' => [
                        [
                            'start_time' => '00:00',
                            'end_time'   => '23:59',
                        ],
                    ],
                ],
            ],
    
            'category_ids' => array_values(
                array_map(
                    function ( $category ) {
                        return (string) $category->term_id;
                    },
                    $categories
                )
            ),
        ];
    
        return $menu;
    }
    public function send_to_orderout(
        $restaurant_id,
        $menu
    ) {
    
        $api_key = 's11pfICCvVLnNsQujYu60TuAo6OLqyhF';
    
        $endpoint =
            "https://api.orderout.co/api/pos/restaurant/{$restaurant_id}/menu";
    
        $response = wp_remote_post(
            $endpoint,
            [
                'timeout' => 60,
    
                'headers' => [
    
                    'accept'       => 'application/json',
    
                    'api-key'      => $api_key,
    
                    'content-type' => 'application/json',
                ],
    
                'body' => wp_json_encode(
                    $menu
                ),
            ]
        );
    
        if ( is_wp_error( $response ) ) {
    
            return [
                'success' => false,
                'message' => $response->get_error_message(),
            ];
        }
    
        $status_code =
            wp_remote_retrieve_response_code(
                $response
            );
    
        $body =
            json_decode(
                wp_remote_retrieve_body(
                    $response
                ),
                true
            );
        if (
            isset( $body['menu']['items'] ) &&
            is_array( $body['menu']['items'] )
        ) {
    
            foreach ( $body['menu']['items'] as $item ) {
    
                if (
                    empty( $item['external_data'] ) ||
                    empty( $item['id'] )
                ) {
                    continue;
                }
    
                if ( preg_match( '/#(\d+)$/', $item['external_data'], $matches ) ) {
    
                    $woo_product_id  = (int) $matches[1];
                    $orderout_item_id = sanitize_text_field( $item['id'] );
    
                    update_post_meta(
                        $woo_product_id,
                        '_orderout_item_id',
                        $orderout_item_id
                    );
                }
            }
        
        }
        return [
    
            'success'     => $status_code >= 200 && $status_code < 300,
    
            'status_code' => $status_code,
    
            'response'    => $body,
        ];
    }
    public function import_orderout_menu( WP_REST_Request $request ) {

        $restaurant_id = sanitize_text_field( $request->get_param( 'restaurant_id' ) );
        $menu_id       = sanitize_text_field( $request->get_param( 'menu_id' ) );
    
        if ( empty( $restaurant_id ) ) {
            return new WP_Error(
                'missing_restaurant_id',
                'restaurant_id is required.',
                [ 'status' => 400 ]
            );
        }
    
        if ( empty( $menu_id ) ) {
            return new WP_Error(
                'missing_menu_id',
                'menu_id is required.',
                [ 'status' => 400 ]
            );
        }
    
        $api_key = get_option( 'orderout_api_key' );
        $api_key = 's11pfICCvVLnNsQujYu60TuAo6OLqyhF';
    
        $endpoint = sprintf(
            'https://api.orderout.co/api/pos/restaurant/%s/menu/%s',
            rawurlencode( $restaurant_id ),
            rawurlencode( $menu_id )
        );
    
        $response = wp_remote_get(
            $endpoint,
            [
                'timeout' => 15,
                'headers' => [
                    'accept'  => 'application/json',
                    'api-key' => $api_key,
                ],
            ]
        );
    
        $logger = wc_get_logger();
    
        if ( is_wp_error( $response ) ) {
    
            $logger->error(
                'OrderOut Get Menu Error: ' . $response->get_error_message(),
                [ 'source' => 'orderout-menu' ]
            );
    
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => $response->get_error_message(),
                ],
                500
            );
        }
    
        $body = wp_remote_retrieve_body( $response );
        
        $body =
            json_decode(
                wp_remote_retrieve_body(
                    $response
                ),
                true
            );
        
        if ( ! empty( $body['items'] ) ) {

            foreach ( $body['items'] as $item ) {
        
                if (
                    ! empty( $item['external_data'] ) &&
                    preg_match( '/#(\d+)$/', $item['external_data'], $matches )
                ) {
                    $product_id = (int) $matches[1];
        
                    update_post_meta(
                        $product_id,
                        '_orderout_item_id',
                        $item['id']
                    );
                }
            }
        }
        if ( ! empty( $body['modifier_groups'] ) ) {

            foreach ( $body['modifier_groups'] as $modifier_group ) {
        
                $modifier_name = $modifier_group['title']['translations']['en_us'];
        
                $modifier_post = get_page_by_title(
                    $modifier_name,
                    OBJECT,
                    'modifier'
                );
        
                if ( $modifier_post ) {
        
                    update_post_meta(
                        $modifier_post->ID,
                        '_orderout_modifier_group_id',
                        $modifier_group['id']
                    );
                }
            }
        }
        return new WP_REST_Response(
            [
                'success' => true,
                'message' => 'menu import successfull',
                // 'data' => $body
            ],
            200
        );
    }
}