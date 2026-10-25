<?php
if (!defined('ABSPATH')) {
    exit;
}

class Pinaka_Merchant_Dashboard_API_Controller {

    protected $namespace = 'pinaka-restaurant-pos/v1';
    protected $rest_base = 'merchant-dashboard';
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/get-revenue-by-filters',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'merchant_get_revenue_by_filter' ],
                'permission_callback' => [ $this, 'check_user_role_permission' ],
            ]
        );
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/top-products-sold',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'merchant_get_top_products' ],
                'permission_callback' => [ $this, 'check_user_role_permission' ],
            ]
        );
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/top-categories-sold',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'merchant_get_top_categories' ],
                'permission_callback' => [ $this, 'check_user_role_permission' ],
            ]
        );
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/get-inventory-alerts',
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'get_inventory_alerts'],
                'permission_callback' => [$this, 'check_user_role_permission'],
            ]
        );
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/get-chart-revenue',
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'get_chart_revenue'],
                'permission_callback' => [$this, 'check_user_role_permission'],
            ]
        );
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/get-payment-modes-revenue',
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'get_payment_modes_revenue'],
                'permission_callback' => [$this, 'check_user_role_permission'],
            ]
        );
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
    public function merchant_get_revenue_by_filter($request)
    {
        $params     = $request->get_params();
        $user_id    = get_current_user_id();
        $restaurant_id   = isset($params['restaurant_id']) ? $params['restaurant_id'] : 0;
        if(empty($restaurant_id))
        {
            return new WP_Error( 'missing_restaurant_id', 'Restaurant ID is required', [ 'status' => 422 ] );
        }
        $timezone = new DateTimeZone(wp_timezone_string());
        $filters = ["Daily", "Weekly", "Monthly"];
   
        $zones = get_posts([
            'post_type'      => 'zones',
            'post_status'    => 'publish',
            'numberposts'    => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'meta_query'     => [
                [
                    'key'     => '_restaurant_id',
                    'value'   => $restaurant_id,
                    'compare' => '='
                ]
            ]
        ]);
        $zone_list = [];
        foreach ( $zones as $zone ) {
            $zone_list[] = [
                'id'   => $zone->ID,
                'name' => $zone->post_title,
            ];
        }
        if(empty($zone_list))
        {
            return new WP_Error( 'missing_zone_id', 'Zone ids are missing for the restaurant.', [ 'status' => 422 ] );
        }
        $range           = isset($params['range']) ? strtolower($params['range']) : 'daily';
        $zone_id         = isset($params['zone_id']) ? $params['zone_id'] : intval($zone_list[0]['id']);
        $zone_ids        = array_column($zone_list, 'id');
        $order_type      = null;
     
        if (!in_array($zone_id, $zone_ids)) 
        {
            return new WP_Error(
                'mismatch_zone_id',
                'Zone ID does not match any of the restaurant zones.',
                ['status' => 422]
            );
        }
        $today = new DateTime('now', wp_timezone());
        switch ($range) 
        {
            case 'monthly':
                $current_end   = clone $today;
                $current_start = (clone $today)->sub(new DateInterval('P1M'));

                $prev_end      = (clone $current_start)->sub(new DateInterval('P1D'));
                $prev_start    = (clone $prev_end)->sub(new DateInterval('P1M'));
                $comparison_text = 'from last month';
                break;
            case 'weekly':
                $current_end   = clone $today;
                $current_start = (clone $today)->modify('-7 days');

                $prev_end      = (clone $current_start)->modify('-1 day');
                $prev_start    = (clone $prev_end)->modify('-7 days');
                $comparison_text = 'from last week';
                break;
            case 'daily':
            default:
                $current_start = (clone $today)->setTime(0, 0, 0);
                $current_end = (clone $today)->setTime(23, 59, 59);

                $prev_start = (clone $current_start)->modify('-1 day')->setTime(0, 0, 0);
                $prev_end = (clone $current_start)->modify('-1 day')->setTime(23, 59, 59);
                $comparison_text = 'from yesterday';
                break;
        }
        
        $current_data  = $this->pinaka_calculate_sales($current_start->format('Y-m-d'), $current_end->format('Y-m-d'), $zone_id, $order_type);
        $previous_data = $this->pinaka_calculate_sales($prev_start->format('Y-m-d'), $prev_end->format('Y-m-d'), $zone_id, $order_type);
   
        $current_gross_total  = (float)$current_data['gross_sales'];
        $previous_gross_total = (float)$previous_data['gross_sales'];
       
        $current_order_count  = isset($current_data['orders']) ? (int)$current_data['orders'] : 0;
        $previous_order_count = isset($previous_data['orders']) ? (int)$previous_data['orders'] : 0;

        if ($previous_gross_total == 0) 
        {
            $percentage_deviation = 0;
            $revenue_trend = 'no_change';
        } 
        else 
        {
            $raw_deviation = (($current_gross_total - $previous_gross_total) / $previous_gross_total) * 100;
            $percentage_deviation = max(-100, min(100, $raw_deviation));
            $revenue_trend = $current_gross_total > $previous_gross_total ? 'increased' : ($current_gross_total < $previous_gross_total ? 'decreased' : 'no_change');
        }

        if ($previous_order_count == 0) 
        {
            $order_deviation = 0;
            $orders_trend = 'no_change';
        } 
        else 
        {
            $raw_order_deviation = (($current_order_count - $previous_order_count) / $previous_order_count) * 100;
            $order_deviation = max(-100, min(100, $raw_order_deviation));
            $orders_trend = $current_order_count > $previous_order_count ? 'increased' : ($current_order_count < $previous_order_count ? 'decreased' : 'no_change');
        }
        if ($revenue_trend === 'increased') {
            $revenue_trend_text = '↑ ' . abs(round($percentage_deviation, 2)) . '% ' . $comparison_text;
        } elseif ($revenue_trend === 'decreased') {
            $revenue_trend_text = '↓ ' . abs(round($percentage_deviation, 2)) . '% ' . $comparison_text;
        } else {
            $revenue_trend_text = '';
        }
        
        if ($orders_trend === 'increased') 
        {
            $order_trend_text = '↑ ' . abs(round($order_deviation, 2)) . '% ' . $comparison_text;
        } 
        elseif ($orders_trend === 'decreased') 
        {
            $order_trend_text = '↓ ' . abs(round($order_deviation, 2)) . '% ' . $comparison_text;
        } 
        else 
        {
            $order_trend_text = '';
        }

        $today = new DateTime('now', wp_timezone());
        $start = (clone $today)->setTime(0, 0, 0);

        $end = (clone $today)->setTime(23, 59, 59);
        $today_start = $start->format('Y-m-d H:i:s');
        $today_end = $end->format('Y-m-d H:i:s');
        
        $orders = wc_get_orders( [
            'status'       => [ 'processing', 'on-hold', 'pending' ],
            'date_created' => $today_start . '...' . $today_end,
            'return'       => 'ids',
            'meta_query'   => [
                'relation' => 'AND',
                [
                    'key'     => '_zone_id',
                    'value'   => $zone_id,
                    'compare' => '='
                ],
                [
                    'key'     => '_restaurant_id',
                    'value'   => $restaurant_id,
                    'compare' => '='
                ],
            ],
        ] );
        $parent_orders = [];
        foreach ($orders as $order_id) 
        {
            $order = wc_get_order($order_id);
            if (!$order) {
                continue;
            }
            if ((int) $order->get_parent_id() === 0) {
                $parent_orders[] = $order_id;
            }
        }
        $active_orders_count = count( $parent_orders );
        $dine_in_orders = wc_get_orders( [
            'status'       => [ 'processing', 'on-hold', 'pending' ],
            'date_created' => $today_start . '...' . $today_end,
            'return'       => 'ids',
            'meta_query'   => [
                'relation' => 'AND',
                [
                    'key'     => '_table_status',
                    'value'   => 'Dine in',
                    'compare' => '='
                ],
                [
                    'key'     => '_zone_id',
                    'value'   => $zone_id,
                    'compare' => '='
                ],
                [
                    'key'     => '_restaurant_id',
                    'value'   => $restaurant_id,
                    'compare' => '='
                ],
            ],
        ] );
        
        $dinein_orders_count = count( $dine_in_orders );
        return rest_ensure_response([
            'restaurant_id' => intval($restaurant_id),
            'zone_id'       => intval($zone_id),
            'all_ranges'   => $filters,
            'restaurant_zones' => $zone_list,
            'total_revenue' => [
                'title'   => 'Total Revenue',
                // 'value'   => wc_format_decimal($current_gross_total, 2),
                'value' => round(floatval($current_gross_total), 2),
                'trend'   => $revenue_trend_text,
            ],
            'total_orders' => [
                'title' => 'Total Orders',
                'value' => intval($current_order_count),
                'trend' => $order_trend_text,
            ],
            'active_orders'   => $active_orders_count,
            'running_tables'  => $dinein_orders_count,
        ]);
    }
    public function get_payment_method_totals(
        $start_date,
        $end_date,
        $ord_type,
        $restaurant_id,
        $zone_id
    ) {
        // echo json_encode($start_date.'--'.$end_date.'--'.$ord_type.'--'.$restaurant_id.'--'.$zone_id)."<br>\n";
        $payments = get_posts([
            'post_type'      => 'payments',
            'post_status'    => 'publish',
            'numberposts'    => -1,
            'meta_query'     => [
                [
                    'key'     => '_payment_datetime',
                    'value'   => [$start_date, $end_date],
                    'compare' => 'BETWEEN',
                    'type'    => 'DATETIME',
                ],
            ],
        ]);
        
        $totals = [];

        foreach ($payments as $payment) {
            $is_void = get_post_meta($payment->ID, '_payment_void', true);
            if ($is_void && $is_void !== 'false') {
                continue;
            }
            $method = sanitize_text_field(
                get_post_meta($payment->ID, '_payment_method', true)
            );

            $amount = (float) get_post_meta($payment->ID, '_payment_amount', true);
            if (empty($method) || $amount <= 0) {
                continue;
            }
            $order_id = (int) get_post_meta($payment->ID, '_payment_order_id', true);
            $order    = wc_get_order($order_id);

            if (!$order) {
                continue;
            }
            if ( $order->get_parent_id() != 0 ) {
                continue;
            }
            if ($order->get_created_via() !== $ord_type) {
                continue;
            }
            $order_restaurant_id = (int) $order->get_meta('_restaurant_id');
            if ($order_restaurant_id !== (int) $restaurant_id) {
                continue;
            }
            if ($ord_type === 'rest-api') {
                $order_zone_id = (int) $order->get_meta('_zone_id');
                if ($order_zone_id !== (int) $zone_id) {
                    continue;
                }
            }

            if (!isset($totals[$method])) {
                $totals[$method] = 0;
            }
            $amount = $order->get_total();
            $totals[$method] += $amount;
        }
        $response = [];
        foreach ($totals as $method => $amount) {
            $response[] = [
                'payment_method' => $method,
                'total'          => $amount,
            ];
        }

        return $response;
    }
    public function merchant_get_top_products($request)
    {
        $params = $request->get_params();
        $user_id = get_current_user_id();
        if (!isset($params['restaurant_id'])) {
			$restaurant_id = 0;
		} else {
			$restaurant_id = sanitize_text_field($params['restaurant_id']);
		}

		if (empty($restaurant_id)) {

			return new WP_REST_Response([
				'success' => false,
				'message' => 'Restaurant id is required.'
			], 422);
		}
   
        $today = new DateTime('now', wp_timezone());
        $start = (clone $today)->setTime(0, 0, 0);

        $end = (clone $today)->setTime(23, 59, 59);
        $today_start = $start->format('Y-m-d H:i:s');
        $today_end = $end->format('Y-m-d H:i:s');
        $top_five_products = $this->pinaka_get_top_products_from_kots($today_start,$today_end,$restaurant_id);
        return rest_ensure_response([
            'restaurant_id' => intval($restaurant_id),
            'top_products_sold'        => $top_five_products['top_products'],
        ]);
    }
    public function merchant_get_top_categories($request)
    {
        $params = $request->get_params();
        $user_id = get_current_user_id();
        if (!isset($params['restaurant_id'])) {
			$restaurant_id = 0;
		} else {
			$restaurant_id = sanitize_text_field($params['restaurant_id']);
		}

		if (empty($restaurant_id)) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Restaurant id is required.'
			], 422);
		}
 
        $today = new DateTime('now', wp_timezone());
        $start = (clone $today)->setTime(0, 0, 0);

        $end = (clone $today)->setTime(23, 59, 59);
        $today_start = $start->format('Y-m-d H:i:s');
        $today_end = $end->format('Y-m-d H:i:s');
        
        $top_five_products = $this->pinaka_get_top_products_from_kots($today_start,$today_end,$restaurant_id);
        return rest_ensure_response([
            'restaurant_id' => intval($restaurant_id),
            'top_categories_sold'      => $top_five_products['top_categories'],
        ]);
    }
    public function pinaka_get_top_products_from_kots($start_date, $end_date, $restaurant_id) 
    {
        global $wpdb;
        $kot_orders = wc_get_orders([
            'date_created' => $start_date . '...' . $end_date,
            'limit'        => -1,
            'return'       => 'ids',
        ]);
        $child_order_ids = [];
        if (!empty($kot_orders)) 
        {
            $placeholders = implode(',', array_fill(0, count($kot_orders), '%d'));
            $allowed_statuses = [
                'yet-to-prepare',
                'preparing',
                'ready',
                'served',
                'recalled'
            ];

            $status_placeholders = implode(',', array_fill(0, count($allowed_statuses), '%s'));
            $args = array_merge($kot_orders, $allowed_statuses);
            $query = $wpdb->prepare(
                "SELECT o.id 
                FROM {$wpdb->prefix}wc_orders o
                WHERE o.parent_order_id IN ($placeholders)
                AND o.status IN ($status_placeholders)",
                ...$args
            );
            $child_order_ids = $wpdb->get_col($query);
        }
        $products   = [];
        $categories = [];
    
        foreach ($child_order_ids as $order_id) 
        {
            $order = wc_get_order($order_id);
            if (!$order || (int) $order->get_meta('_restaurant_id') !== (int) $restaurant_id) {
                continue;
            }
            foreach ($order->get_items() as $item) {
                $product_id   = $item->get_product_id();
                $product_name = $item->get_name();
                $qty          = $item->get_quantity();
                $line_total   = $item->get_total();

                if (!isset($products[$product_id])) {
                    $products[$product_id] = [
                        // 'id'         => $product_id,
                        'name'       => $product_name,
                        'items_sold' => 0,
                        'net_sales'  => 0,
                    ];
                }
                $products[$product_id]['items_sold'] += $qty;
                $products[$product_id]['net_sales']  += $line_total;

                $terms = get_the_terms($product_id, 'product_cat');
                $top_category = $this->get_last_category_before_product($product_id);

                if ($top_category) {
                    $cat_id = $top_category->term_id;
                    if (!isset($categories[$cat_id])) {
                        $categories[$cat_id] = [
                            // 'id'         => $cat_id,
                            'name'       => $top_category->name,
                            'items_sold' => 0,
                            'net_sales'  => 0,
                        ];
                    }

                    $categories[$cat_id]['items_sold'] += $qty;
                    $categories[$cat_id]['net_sales']  += $line_total;
                }
            }
        }

        $products   = array_values($products);
        $categories = array_values($categories);

        usort($products, function($a, $b) {
            return $b['items_sold'] <=> $a['items_sold'];
        });
        usort($categories, function($a, $b) {
            return $b['items_sold'] <=> $a['items_sold'];
        });

        $products   = array_slice($products, 0, 5);
        $categories = array_slice($categories, 0, 5);
        return [
            'top_products'   => $products,
            'top_categories' => $categories,
        ];
    }
    private function get_last_category_before_product($product_id) 
    {
        $terms = get_the_terms($product_id, 'product_cat');
        if (!$terms || is_wp_error($terms)) {
            return null;
        }
        usort($terms, function($a, $b) {
            return $a->term_id <=> $b->term_id;
        });
        $last_category = end($terms);
        return $last_category;
    }
    public function get_inventory_alerts(WP_REST_Request $request)
    {
        $args = [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'   => '_stock_status',
                    'value' => 'outofstock',
                ],
            ],
            'fields' => 'ids',
        ];

        $query  = new WP_Query($args);
        $alerts = [];

        foreach ($query->posts as $product_id) {

            $product = wc_get_product($product_id);
            if (!$product) {
                continue;
            }

            $alerts[] = [
                'id'        => $product_id,
                'name'      => $product->get_name(),
                'quantity'  => $product->get_stock_quantity(),
                'status'    => 'Out of Stock',
                'type'      => $product->get_type(),
            ];
        }

        return rest_ensure_response($alerts);
    }
    public function pinaka_calculate_sales($start_date, $end_date, $zone_id = null, $order_type = null)
    {
        $start = new DateTime($start_date, wp_timezone());
        $end   = new DateTime($end_date, wp_timezone());
        $start->setTime(0, 0, 0);
        $end->setTime(23, 59, 59);
        // echo json_encode($start_date . '--' . $end_date . '--' . $zone_id . '--' . $order_type) . "<br>\n";
        $args = [
            'status'       => ['wc-completed'],
            'date_created' => $start->format('Y-m-d H:i:s') . '...' . $end->format('Y-m-d H:i:s'),
            'limit'        => -1,
        ];
        
        $orders = wc_get_orders($args);
        $top_level_orders = [];
        foreach ( $orders as $order ) 
        {
            if ( intval( $order->get_parent_id() ) === 0 ) 
            {
                if ($order_type !== null) 
                {
                    $created_via = $order->get_created_via();

                    if (strtolower($order_type) === 'rest-api' && $created_via !== 'rest-api') {
                        continue;
                    }
                    if (strtolower($order_type) === 'takeaway' && $created_via !== 'takeaway') {
                        continue;
                    }
                }
                $top_level_orders[] = $order;
            }
        }
        $gross    = 0;
        $count    = 0;
        foreach ($top_level_orders as $order) 
        {
            // if ( empty( $zone_id ) ) {
            //     continue;
            // }
            $created_via = $order->get_created_via();
            if($created_via === 'rest-api')
            {
                $zone_id       = intval($zone_id);
                $order_zone_id = intval($order->get_meta('_zone_id'));
                if ($order_zone_id !== $zone_id) {
                    continue;
                }
            }
            $count++;
            $gross    += (float) $order->get_total();
        }
        return [
            'orders'      => $count,
            'gross_sales' => wc_format_decimal($gross, 2),
        ];
    }
    public function get_chart_revenue($request)
    {
        $params = $request->get_params();
        $restaurant_id = isset($params['restaurant_id']) ? $params['restaurant_id'] : 0;
        if (empty($restaurant_id)) {
            return new WP_Error('missing_restaurant_id', 'Restaurant ID is required', ['status' => 422]);
        }

        $zones = get_posts([
            'post_type'   => 'zones',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby'     => 'title',
            'order'       => 'ASC',
            'meta_query'  => [
                [
                    'key'     => '_restaurant_id',
                    'value'   => $restaurant_id,
                    'compare' => '='
                ]
            ]
        ]);

        $zone_list = [];
        foreach ($zones as $zone) {
            $zone_list[] = [
                'id'   => $zone->ID,
                'name' => $zone->post_title,
            ];
        }
        if (empty($zone_list)) {
            return new WP_Error('missing_zone_id', 'Zone ids are missing for the restaurant.', ['status' => 422]);
        }
        $range    = isset($params['range']) ? strtolower($params['range']) : 'daily';
        $zone_id  = isset($params['zone_id']) ? $params['zone_id'] : intval($zone_list[0]['id']);
        $zone_ids = array_column($zone_list, 'id');
        if (!in_array($zone_id, $zone_ids)) {
            return new WP_Error('mismatch_zone_id', 'Zone ID does not match any of the restaurant zones.', ['status' => 422]);
        }
        $revenue_data = [];
        $timezone = wp_timezone();
        $today    = new DateTime('now', $timezone);
        
        $currency   = get_woocommerce_currency_symbol();
        $format_amount = function ($amount) use ($currency) {
            $currency_code = get_woocommerce_currency();

            if ($currency_code === 'INR') {
                if ($amount >= 10000000) { // 1 Crore
                    return round($amount / 10000000, 2) . ' Cr';
                } elseif ($amount >= 100000) { // 1 Lakh
                    return round($amount / 100000, 2) . ' L';
                } elseif ($amount >= 1000) {
                    return round($amount / 1000, 1) . ' K';
                }
                return number_format($amount, 0);
            }

            if ($amount >= 1000000000) {
                return round($amount / 1000000000, 2) . ' B';
            } elseif ($amount >= 1000000) {
                return round($amount / 1000000, 2) . ' M';
            } elseif ($amount >= 1000) {
                return round($amount / 1000, 1) . ' K';
            }
            return number_format($amount, 0);
        };
        // === DAILY ===
        if ($range === 'daily') 
        {
            $day_start = (clone $today)->setTime(0, 0, 0);
            $day_end   = (clone $today)->setTime(23, 59, 59);
            
            $dine_in  = $this->pinaka_calculate_sales($day_start->format('Y-m-d'), $day_end->format('Y-m-d'), $zone_id, 'rest-api');
            $takeaway = $this->pinaka_calculate_sales($day_start->format('Y-m-d'), $day_end->format('Y-m-d'), $zone_id, 'takeaway');

            $dine_in_sales  = isset($dine_in['gross_sales']) ? (float) $dine_in['gross_sales'] : 0;
            $takeaway_sales = isset($takeaway['gross_sales']) ? (float) $takeaway['gross_sales'] : 0;

            $revenue_data[] = [
                'label'           => $day_start->format('D d M'),
                'dine_in'         => $format_amount($dine_in_sales),
                'takeaway'        => $format_amount($takeaway_sales),
                'dine_in_amount'  => $dine_in_sales,
                'takeaway_amount' => $takeaway_sales,
            ];
        }
        elseif ($range === 'weekly') 
        {
            $start_day = (clone $today)->modify('-6 days')->setTime(0, 0, 0);
            for ($i = 0; $i < 7; $i++) {
                $day_start = (clone $start_day)->modify("+{$i} days")->setTime(0, 0, 0);
                $day_end   = (clone $day_start)->setTime(23, 59, 59);

                $dine_in  = $this->pinaka_calculate_sales($day_start->format('Y-m-d'), $day_end->format('Y-m-d'), $zone_id, 'rest-api');
                $takeaway = $this->pinaka_calculate_sales($day_start->format('Y-m-d'), $day_end->format('Y-m-d'), $zone_id, 'takeaway');

                $dine_in_sales  = isset($dine_in['gross_sales']) ? (float) $dine_in['gross_sales'] : 0;
                $takeaway_sales = isset($takeaway['gross_sales']) ? (float) $takeaway['gross_sales'] : 0;

                $revenue_data[] = [
                    'label'           => $day_start->format('D d M'),
                    'dine_in'         => $format_amount($dine_in_sales),
                    'takeaway'        => $format_amount($takeaway_sales),
                    'dine_in_amount'  => $dine_in_sales,
                    'takeaway_amount' => $takeaway_sales,
                ];
            }
        }
        elseif ($range === 'monthly') 
        {
            $week_4_end = (clone $today)->setTime(23, 59, 59);
            $week_4_start = (clone $week_4_end)->modify('-6 days')->setTime(0, 0, 0); // 7 day week, inclusive

            for ($i = 0; $i < 4; $i++) 
            {
                $week_end = (clone $week_4_end)->modify('-' . (7 * (3 - $i)) . ' days')->setTime(23, 59, 59);
                $week_start = (clone $week_end)->modify('-6 days')->setTime(0, 0, 0);
                $dine_in  = $this->pinaka_calculate_sales($week_start->format('Y-m-d'), $week_end->format('Y-m-d'), $zone_id, 'rest-api');
                $takeaway = $this->pinaka_calculate_sales($week_start->format('Y-m-d'), $week_end->format('Y-m-d'), $zone_id, 'takeaway');

                $dine_in_sales  = isset($dine_in['gross_sales']) ? (float) $dine_in['gross_sales'] : 0;
                $takeaway_sales = isset($takeaway['gross_sales']) ? (float) $takeaway['gross_sales'] : 0;

                $revenue_data[] = [
                    'label'           => "Week " . ($i + 1),
                    'dine_in'         => $format_amount($dine_in_sales),
                    'takeaway'        => $format_amount($takeaway_sales),
                    'dine_in_amount'  => $dine_in_sales,
                    'takeaway_amount' => $takeaway_sales,
                ];
            }
        }
        // elseif ($range === 'yearly') 
        // {
        //     // Start from current month
        //     $current_month_start = (clone $today)->modify('first day of this month')->setTime(0, 0, 0);
        //     $current_month_end   = (clone $today)->modify('last day of this month')->setTime(23, 59, 59);

        //     for ($i = 0; $i < 12; $i++) 
        //     {
        //         $month_start = (clone $current_month_start)->modify("-$i months");
        //         $month_end   = (clone $current_month_end)->modify("-$i months");
        //         $dine_in  = $this->pinaka_calculate_sales($month_start->format('Y-m-d'), $month_end->format('Y-m-d'), $zone_id, 'rest-api');
        //         $takeaway = $this->pinaka_calculate_sales($month_start->format('Y-m-d'), $month_end->format('Y-m-d'), $zone_id, 'takeaway');

        //         $dine_in_sales  = isset($dine_in['gross_sales']) ? (float) $dine_in['gross_sales'] : 0;
        //         $takeaway_sales = isset($takeaway['gross_sales']) ? (float) $takeaway['gross_sales'] : 0;

        //         $revenue_data[] = [
        //             'label'           => $month_start->format('M Y'),
        //             'dine_in'         => $format_amount($dine_in_sales),
        //             'takeaway'        => $format_amount($takeaway_sales),
        //             'dine_in_amount'  => $dine_in_sales,
        //             'takeaway_amount' => $takeaway_sales,
        //         ];
        //     }
        // }

        $all_sales = [];
        $dine_in_total   = 0;
        $takeaway_total  = 0;
        foreach ($revenue_data as $row) {
            $all_sales[] = $row['dine_in_amount'];
            $all_sales[] = $row['takeaway_amount'];
            $dine_in_total  += $row['dine_in_amount'];
            $takeaway_total += $row['takeaway_amount'];
        }
        $max = !empty($all_sales) ? max($all_sales) : 0;
        $step = 1;
        if ($max > 10000000) {
            $step = 2000000;
        } elseif ($max > 1000000) {
            $step = 200000;
        } elseif ($max > 100000) {
            $step = 25000;
        } elseif ($max > 10000) {
            $step = 5000;
        } elseif ($max > 1000) {
            $step = 1000;
        } elseif ($max > 100) {
            $step = 100;
        } else {
            $step = 1;
        }
        
        $y_axis_ticks = [];
        for ($tick = 0; $tick <= $max; $tick += $step) {
            $y_axis_ticks[] = $format_amount($tick);
        }
        if ($tick - $step < $max) {
            $y_axis_ticks[] = $format_amount($max);
        }
        $y_axis_ticks = array_unique($y_axis_ticks);
        $overall_total = $dine_in_total + $takeaway_total;

        return rest_ensure_response([
            'restaurant_id'  => intval($restaurant_id),
            'zone_id'        => intval($zone_id),
            'order_types'    => ['Dine In', 'Takeaways'],
            'y_axis_values'  => $y_axis_ticks,
            // 'dine_in_total'  => $dine_in_total,
            // 'takeaway_total' => $takeaway_total,
            'revenue'        => "Total ($overall_total) = Dine In ($dine_in_total) + Takeaways ($takeaway_total)",
            'revenue_chart'  => $revenue_data,
        ]);
    }
    public function get_payment_modes_revenue($request)
    {
        $timezone = wp_timezone();
        $today    = new DateTime('now', $timezone);
        $params   = $request->get_params();

        /* ---------------------------
        * Validate restaurant
        * --------------------------- */
        $restaurant_id = isset($params['restaurant_id']) ? (int) $params['restaurant_id'] : 0;

        if (!$restaurant_id) {
            return new WP_Error(
                'missing_restaurant_id',
                'Restaurant ID is required',
                ['status' => 422]
            );
        }

        /* ---------------------------
        * Fetch zones
        * --------------------------- */
        $zones = get_posts([
            'post_type'   => 'zones',
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query'  => [
                [
                    'key'   => '_restaurant_id',
                    'value' => $restaurant_id,
                ],
            ],
        ]);

        if (empty($zones)) {
            return new WP_Error(
                'missing_zone_id',
                'No zones found for this restaurant',
                ['status' => 422]
            );
        }

        $zone_ids = wp_list_pluck($zones, 'ID');
        $zone_id  = isset($params['zone_id']) ? (int) $params['zone_id'] : (int) $zone_ids[0];

        if (!in_array($zone_id, $zone_ids)) {
            return new WP_Error(
                'invalid_zone',
                'Zone does not belong to this restaurant',
                ['status' => 422]
            );
        }

        /* ---------------------------
        * Date range
        * --------------------------- */
        $range = isset($params['range']) ? strtolower($params['range']) : 'daily';

        if ($range === 'weekly') {
            $start = (clone $today)->modify('-6 days')->setTime(0, 0, 0);
        } elseif ($range === 'monthly') {
            $start = (clone $today)->modify('-27 days')->setTime(0, 0, 0);
        } else {
            $start = (clone $today)->setTime(0, 0, 0);
        }

        $end = (clone $today)->setTime(23, 59, 59);

        /* ---------------------------
        * Order type mapping
        * --------------------------- */
        $order_type_ui = isset($params['order_type']) ? $params['order_type'] : 'Dine In';

        $order_type_map = [
            'Dine In'  => 'rest-api',
            'Takeaways' => 'takeaway',
        ];

        $ord_type = $order_type_map[$order_type_ui] ?? 'rest-api';

        /* --------------------------------------------------
        * STEP 1: Get TOTAL using same logic as chart revenue
        * -------------------------------------------------- */
        $sales = $this->pinaka_calculate_sales(
            $start->format('Y-m-d H:i:s'),
            $end->format('Y-m-d H:i:s'),
            $zone_id,
            $ord_type
        );

        $expected_total = isset($sales['gross_sales'])
            ? (float) $sales['gross_sales']
            : 0;

        /* --------------------------------------------------
        * STEP 2: Get payment method totals
        * -------------------------------------------------- */
        $results = $this->get_payment_method_totals(
            $start->format('Y-m-d H:i:s'),
            $end->format('Y-m-d H:i:s'),
            $ord_type,
            $restaurant_id,
            $zone_id
        );

        /* --------------------------------------------------
        * STEP 3: Sum payment totals
        * -------------------------------------------------- */
        $payment_sum = 0;
        foreach ($results as $row) {
            $payment_sum += (float) $row['total'];
        }

        /* --------------------------------------------------
        * STEP 4: Fix rounding / minor mismatch
        * -------------------------------------------------- */
        $diff = round($expected_total - $payment_sum, 2);

        /* --------------------------------------------------
        * STEP 5: Build response
        * -------------------------------------------------- */
        $final_revenue = [
            'order_type'    => $order_type_ui,
            'payment_types' => [],
        ];

        if (!empty($results)) {

            foreach ($results as $index => $row) {

                $amount = (float) $row['total'];

                // adjust last row to fix rounding mismatch
                if ($diff != 0 && $index === array_key_last($results)) {
                    $amount += $diff;
                }

                $final_revenue['payment_types'][] = [
                    'mode'   => $row['payment_method'],
                    'amount' => round($amount, 2),
                ];
            }

        } else {
            // if no payment modes found, still return total
            $final_revenue['payment_types'][] = [
                'mode'   => 'Unknown',
                'amount' => round($expected_total, 2),
            ];
        }

        return rest_ensure_response([
            'restaurant_id'   => $restaurant_id,
            'zone_id'         => $zone_id,
            'order_types'     => ['Dine In', 'Takeaways'],
            'payment_revenue' => $final_revenue,
        ]);
    }

}
?>