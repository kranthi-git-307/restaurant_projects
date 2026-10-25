<?php
if(!defined('ABSPATH')){
    exit;
}
class Pinaka_Reservation_Api_controller
{
    protected $namespace = 'pinaka-restaurant-pos/v1';
    protected $rest_base = 'reservation';

    public function register_routes(){
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/create-reservation',
            array(
                'methods'     => 'POST',
                'callback'    => array($this, 'create_reservation_method'),
                'permission_callback'  => array($this, 'check_user_role_permission'),
            )
        );
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/get-reservation-by-id',
            array(
                'methods'   => 'GET',
                'callback'  => array($this, 'get_reservation_by_id'),
                'permission_callback' => array($this, 'check_user_role_permission'),
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/update-reservation',
            array(
                'methods'     => 'POST',
                'callback'    => array($this, 'update_reservation_method'),
                'permission_callback'  => array($this, 'check_user_role_permission'),
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/get-all-reservations',
            array(
                'methods'     => 'GET',
                'callback'    => array($this, 'get_all_reservations'),
                'permission_callback'  => array($this, 'check_user_role_permission'),
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/cancel-reservation',
            array(
                'methods'     => 'POST',
                'callback'    => array($this, 'cancel_reservation_method'),
                'permission_callback'  => array($this, 'check_user_role_permission'),
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/reservation-date-range',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_reservation_date_range'),
                'permission_callback' => array($this, 'check_user_role_permission'),
            )
        );
        
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/reservation-status-counts',
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_reservation_status_counts'],
                'permission_callback' => [$this, 'check_user_role_permission'],
            ]
        );
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/reservation-report',
            array(
                'methods'  => 'GET',
                'callback' => array($this, 'get_reservation_report'),
                'permission_callback' => array($this, 'check_user_role_permission'),
            )
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
    
    public function get_reservation_status_counts($request)
    {
      $restaurant_id = absint($request->get_param('restaurant_id'));
        
        $args = [
                    'post_type'      => 'reservation_list',
                    'post_status'    => 'publish',
                    'posts_per_page' => -1,
                ];

                if (!empty($restaurant_id)) {

                    $args['meta_query'] = [
                        [
                            'key'     => '_restaurant_id',
                            'value'   => $restaurant_id,
                            'compare' => '='
                        ]
                    ];

                }
        $query = new WP_Query($args);
    
        $seated = 0;
        $cancelled = 0;
        $expired = 0;
        $upcoming = 0;
    
        $wp_timezone = wp_timezone();
        // $today = new DateTime('today', $wp_timezone);
        // $end_date = clone $today;
        // $end_date->modify('+6 days');
    
        foreach ($query->posts as $post) {

            $reservation_id = $post->ID;
            $meta = get_post_meta($reservation_id);
        
            $table_status = $meta['_table_status'][0] ?? '';
            $reservation_time_raw = $meta['_reservation_time'][0] ?? '';
            $reservation_date_raw = $meta['_reservation_date'][0] ?? '';
            $force_cancellation = get_post_meta($reservation_id, '_force_cancellation', true);
        
            if (empty($reservation_date_raw) || empty($reservation_time_raw)) {
                continue;
            }
        
            try {
        
                $reservation_date = new DateTime($reservation_date_raw, $wp_timezone);
        
                // Only count today + next 6 days
                // if ($reservation_date < $today || $reservation_date > $end_date) {
                //     continue;
                // }
        
                $start_dt = new DateTime(
                    $reservation_date_raw . ' ' . $reservation_time_raw,
                    $wp_timezone
                );
        
                $end_dt = clone $start_dt;
                $end_dt->modify('+30 minutes');
        
                $now_dt = new DateTime('now', $wp_timezone);
        
                // Determine reservation status
                if ($force_cancellation == 1) {
        
                    $reservation_status = 'Cancelled';
        
                } elseif (in_array($table_status, ['Dine in', 'Ready to Pay'])) {
        
                    $reservation_status = 'Seated';
        
                } elseif ($table_status === 'Cancelled') {
        
                    $reservation_status = 'Cancelled';
        
                } elseif ($now_dt > $end_dt) {
        
                    $reservation_status = 'Expired';
        
                } else {
        
                    $reservation_status = 'Upcoming';
        
                }
        
                switch ($reservation_status) {
        
                    case 'Upcoming':
                        $upcoming++;
                        break;
        
                    case 'Seated':
                        $seated++;
                        break;
        
                    case 'Cancelled':
                        $cancelled++;
                        break;
        
                    case 'Expired':
                        $expired++;
                        break;
                }
        
            } catch (Exception $e) {
                continue;
            }
        }
    
        return new WP_REST_Response([
            'success' => true,
            'restaurant_id' => $restaurant_id,
            'reservation_status_counts' => [
                'upcoming' => $upcoming,
                'seated' => $seated,
                'cancelled' => $cancelled,
                'expired' => $expired,
                'total_reservations' => $upcoming + $seated + $cancelled + $expired
            ]
        ], 200);
    }
    public function create_reservation_method(WP_REST_Request $request) {
        $params = $request->get_json_params();
        $user_id = get_current_user_id();

        // Define valid table statuses (shared with table system)
        $statuses = ['Available', 'Dine in', 'Reserve', 'Ready to Pay'];

        // Required fields
        $required_fields = [
            'no_of_people',
            'customer_name',
            'customer_phone',
            'reservation_date',
            'reservation_time',
            'table_no',
            'restaurant_id',
            'table_status'
        ];

        foreach ($required_fields as $field) {
            if (empty($params[$field])) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required.',
                ], 422);
            }
        }

        // Sanitize inputs
        $customer_name     = sanitize_text_field($params['customer_name']);
        $customer_phone    = sanitize_text_field($params['customer_phone']);
        $reservation_date  = sanitize_text_field($params['reservation_date']);
        $reservation_time  = sanitize_text_field($params['reservation_time']);
        $table_no          = sanitize_text_field($params['table_no']);
        $slot_type         = sanitize_text_field($params['slot_type']);
        $restaurant_id     = sanitize_text_field($params['restaurant_id']);
        $restaurant_name   = sanitize_text_field($params['restaurant_name'] ?? '');
        $priority          = sanitize_text_field($params['priority_category'] ?? '');
        $zone_name         = sanitize_text_field($params['zone_name'] ?? '');
        $table_status      = sanitize_text_field($params['table_status']);
        $no_of_people      = intval($params['no_of_people']);
        // Validate table status
        if (!in_array($table_status, $statuses)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Invalid table status. Allowed statuses: ' . implode(', ', $statuses),
            ], 422);
        }

        if (empty($zone_name)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Zone name is required.',
            ], 422);
        }

        // $today_date = date('Y-m-d');
        // $current_time_24 = strtotime(date('h:i A')); // Current time in seconds
        // $reservation_date_timestamp = strtotime($reservation_date); // For date comparison

        // $slot_time_24 = strtotime($reservation_time);

        // // CASE 1: Reservation date is in the past (e.g., yesterday)
        // if ($reservation_date_timestamp < strtotime($today_date)) {
        //     return new WP_REST_Response([
        //         'success' => false,
        //         'message' => 'Cannot reserve for a past date.',
        //     ], 422);
        // }

        // // CASE 2: Reservation is for today, and slot time has already passed
        // if ($reservation_date === $today_date && $slot_time_24 <= $current_time_24) {
        //     return new WP_REST_Response([
        //         'success' => false,
        //         'message' => 'Selected slot time has already passed. Please choose a future time slot.',
        //     ], 422);
        // }
        $timezone = wp_timezone();

        $current_datetime = new DateTime('now', $timezone);
        
        $reservation_datetime = new DateTime(
            $reservation_date . ' ' . $reservation_time,
            $timezone
        );
        
        // Reservation date is in the past
        if ($reservation_datetime->format('Y-m-d') < $current_datetime->format('Y-m-d')) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Cannot reserve for a past date.',
            ], 422);
        }
        
        // Reservation date is today and time has passed
        if ($reservation_datetime <= $current_datetime) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Selected slot time has already passed. Please choose a future time slot.',
            ], 422);
        }
        
        $all_slots = get_option('pinaka_pos_slots', []);
        $all_slots = maybe_unserialize($all_slots);
        $slot_found = false;
        $slot_times_for_type = [];

        foreach ($all_slots as $slot) {
            if (
                intval($slot['restaurant_id']) === intval($restaurant_id) &&
                strtolower($slot['type']) === strtolower($slot_type)
            ) {
                $formatted_slot_time = date('H:i', strtotime($slot['time']));

                $slot_times_for_type[] = $formatted_slot_time;
                if ($formatted_slot_time === date('H:i', strtotime($reservation_time))) {
                    $slot_found = true;
                }
            }
        }
        if (!$slot_found) {
            return new WP_REST_Response([
                'success' => false,
                'message' => sprintf(
                    'No available slot found for %s at %s in the selected restaurant.',
                    ucfirst($slot_type),
                    date('h:i A', strtotime($reservation_time))
                ),
            ], 422);
        }

        $zone_query = new WP_Query([
            'post_type'      => 'zones',
            'post_status'    => 'publish',
            'title'          => $zone_name,
            'posts_per_page' => 1,
            'fields'         => 'ids'
        ]);

        if (empty($zone_query->posts)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Zone name does not exist. Please provide a valid zone.',
            ], 404);
        }

        $zone_id = $zone_query->posts[0];
        // echo $zone_id;die;
        $table_id = null;
        $tables_in_zone = get_posts([
            'post_type'      => 'tables',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'     => '_table_zone_id',
                    'value'   => $zone_id,
                    'compare' => '='
                ]
            ]
        ]);

        foreach ($tables_in_zone as $table_post) {
            if (strtolower(trim($table_post->post_title)) === strtolower(trim($table_no))) {
                $table_id = $table_post->ID;
                break;
            }
        }
  
        if (!$table_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'The selected table does not belong to the specified zone.',
            ], 422);
        }

        $people_count = $no_of_people;
        // if ($people_count < 1 || $people_count > 12) {
        //     return new WP_REST_Response([
        //         'success' => false,
        //         'message' => 'People count must be between 1 and 12.',
        //     ], 422);
        // }
        $table_capacity = intval(get_post_meta($table_id, '_table_capacity', true));
        
        $current_time = date('H:i', strtotime($reservation_time));

        if (in_array($current_time, $slot_times_for_type)) {
            $existing_reservations = get_posts([
                'post_type'       => 'reservation_list',
                'post_status'     => 'publish',
                'posts_per_page'  => -1,
                'meta_query'      => [
                    'relation' => 'AND',
                    [
                        'key'   => '_table_no',
                        'value' => $table_no,
                    ],
                    [
                        'key'   => '_reservation_date',
                        'value' => $reservation_date,
                    ],
                    [
                        'key'   => '_slot_type',
                        'value' => $slot_type,
                    ]
                ]
            ]);
            
            foreach ($existing_reservations as $reservation) {
                // $orders = wc_get_orders( [
                //     'limit'      => 1,
                //     'meta_key'   => '_reservation_id',
                //     'meta_value' => $reservation->ID,
                //     'return'     => 'objects',
                // ] );
        
                // if ( ! empty( $orders ) ) {
        
                //     $order = $orders[0];
        
                //     // If parent order is cancelled, update reservation
                //     if ( $order->get_parent_id() == 0 && $order->get_status() === 'cancelled' ) {
        
                //         update_post_meta( $reservation->ID, '_reservation_status', 'Cancelled' );
                //         update_post_meta( $reservation->ID, '_table_status', 'Available' );
                //     }
                // }
                $existing_status = get_post_meta($reservation->ID, '_table_status', true);
                if (in_array($existing_status, ['Reserve', 'Dine in'])) {
                    return new WP_REST_Response([
                        'success' => false,
                        'message' => "$table_no is currently marked as \"$existing_status\" and cannot be reserved in this $slot_type slot on $reservation_date. Please choose another table in $slot_type slot.",
                    ], 409);
                }
            }
        }
        $active_orders = wc_get_orders([
            'status'    => [ 'processing', 'pending' ],
            'limit'     => 1,
            'meta_query'=> [
                'relation' => 'AND',
                [ 'key' => '_restaurant_id', 'value' => $restaurant_id ],
                [ 'key' => '_zone_id', 'value' => $zone_id ],
                [ 'key' => '_table_id', 'value' => $table_id ],
                [ 'key' => '_table_status', 'value' => 'Dine in' ],
                [ 'key' => '_parent_order', 'value' => 'yes' ]
            ],
            // 'post_status' => array_keys( wc_get_order_statuses() ),
        ]);

        if ( ! empty( $active_orders ) ) 
        {
           return new WP_REST_Response([
                'success'       => false,
                'message'       => 'This table is currently marked as Dine In. Please select another table.'
            ], 409);
        }
        $restaurants = get_option('pinaka_pos_restaurants', []);
        $restaurant_exists = false;
        foreach ($restaurants as $r) {
            if ($r['id'] == $restaurant_id) {
                $restaurant_exists = true;
                break;
            }
        }

        if (!$restaurant_exists) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Restaurant does not exist.',
            ], 409);
        }


        $user_restaurant_id = intval(get_user_meta($user_id, 'user_restaurant_id', true));
        if ($user_restaurant_id !== intval($restaurant_id)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'User not assigned to the selected restaurant.',
            ], 403);
        }


        $post_id = wp_insert_post([
            'post_type'   => 'reservation_list',
            'post_status' => 'publish',
            'post_title'  => $customer_name,
            'post_author' => $user_id,
        ]);

        if (is_wp_error($post_id)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Failed to create reservation.',
            ], 500);
        }
    
        update_post_meta($post_id, '_customer_name', $customer_name);
        update_post_meta($post_id, '_customer_phone', $customer_phone);
        update_post_meta($post_id, '_people_count', $people_count);
        update_post_meta($post_id, '_table_no', $table_no);
        update_post_meta($post_id, '_table_no_id', $table_id);
        update_post_meta($post_id, '_reservation_date', $reservation_date);
        update_post_meta($post_id, '_reservation_time', $reservation_time);
        update_post_meta($post_id, '_slot_type', $slot_type);
        update_post_meta($post_id, '_area', $zone_name);
        update_post_meta($post_id, '_zone_id', $zone_id);
        update_post_meta($post_id, '_restaurant_id', $restaurant_id);
        update_post_meta($post_id, '_restaurant_name', $restaurant_name);
        update_post_meta($post_id, '_priority_category', $priority);
        update_post_meta($post_id, '_table_status', $table_status);

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Reservation created successfully.',
            'reservation_id' => $post_id,
            'customer_name' => $customer_name,
            'customer_phone' => $customer_phone,
            'people_count' => $people_count,
            'reservation_date' => $reservation_date,
            'reservation_time' => $reservation_time,
            'slot_type' => $slot_type,
            'table_no' => $table_no,
            'table_id' => $table_id,
            'restaurant_id' => $restaurant_id,
            'restaurant_name' => $restaurant_name,
            'zone_name' => $zone_name,
            'zone_id' => $zone_id,
            'priority_category' => $priority,
            'table_status' => $table_status,
        ], 200);
    }

    public function update_reservation_method(WP_REST_Request $request) 
    {
        $params = $request->get_json_params();
        if(!isset($params['reservation_id']))
        {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Reservation ID is required.',
            ], 400);
        }
        $reservation_id = absint($params['reservation_id'] ?? 0);

        if (!$reservation_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Reservation ID is required.',
            ], 400);
        }
        $reservation_post = get_post($reservation_id);

        if (!$reservation_post || $reservation_post->post_type !== 'reservation_list' || $reservation_post->post_status !== 'publish') {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Reservation does not exist or is invalid.',
            ], 404);
        }

        $user_id = get_current_user_id();
        // Define valid table statuses (shared with table system)
        $statuses = ['Available', 'Dine in', 'Reserve', 'Ready to Pay'];
        
        // Required fields
        $required_fields = [
            'no_of_people',
            'customer_name',
            'customer_phone',
            'reservation_date',
            'reservation_time',
            'zone_name',
            'table_no',
            'slot_type',
            'restaurant_id',
            'table_status'
        ];

        foreach ($required_fields as $field) {
            if (empty($params[$field])) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required.',
                ], 422);
            }
        }
        // Sanitize inputs
        $customer_name     = sanitize_text_field($params['customer_name']);
        $customer_phone    = sanitize_text_field($params['customer_phone']);
        $reservation_date  = sanitize_text_field($params['reservation_date']);
        $reservation_time  = sanitize_text_field($params['reservation_time']);
        $table_no          = sanitize_text_field($params['table_no']);
        $slot_type         = sanitize_text_field($params['slot_type']);
        $restaurant_id     = intval($params['restaurant_id']);
        $restaurant_name   = sanitize_text_field($params['restaurant_name'] ?? '');
        $priority          = sanitize_text_field($params['priority_category'] ?? '');
        $zone_name         = sanitize_text_field($params['zone_name'] ?? '');
        $table_status      = sanitize_text_field($params['table_status']);
        $no_of_people      = intval($params['no_of_people']);
        // Validate table status
        if (!in_array($table_status, $statuses)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Invalid table status. Allowed statuses: ' . implode(', ', $statuses),
            ], 422);
        }

        $timezone = wp_timezone();

        $current_datetime = new DateTime('now', $timezone);
        
        $reservation_datetime = new DateTime(
            $reservation_date . ' ' . $reservation_time,
            $timezone
        );
        
        $reserv_status = get_post_meta($reservation_id, '_reservation_status', true);
        
        if (in_array($reserv_status, ['Cancelled', 'Expired'], true)) {
            return new WP_Error(
                'reservation_locked',
                __('You cannot edit a cancelled or expired reservation.', 'pinaka-pos'),
                ['status' => 403]
            );
        }
        
        // CASE 1: Reservation is in the past
        if ($reservation_datetime < $current_datetime) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Cannot edit reservation to past time.',
            ], 422);
        }
        
        // CASE 2: Reservation is today
        if ($reservation_datetime->format('Y-m-d') === $current_datetime->format('Y-m-d')) {
        
            $time_difference = $reservation_datetime->getTimestamp() - $current_datetime->getTimestamp();
        
            // Less than 10 minutes left — don't allow edit
            if ($time_difference < 600) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Cannot edit reservation within 10 minutes of the reservation time.',
                ], 422);
            }
        }
        
        $all_slots = get_option('pinaka_pos_slots', []);
        $all_slots = maybe_unserialize($all_slots);
        $slot_found = false;
        $slot_times_for_type = [];
    
        foreach ($all_slots as $slot) {
            if (
                intval($slot['restaurant_id']) === intval($restaurant_id) &&
                strtolower($slot['type']) === strtolower($slot_type)
            ) {
                $formatted_slot_time = date('H:i', strtotime($slot['time']));

                $slot_times_for_type[] = $formatted_slot_time;
             
                if ($formatted_slot_time === date('H:i', strtotime($reservation_time))) 
                {
                    $slot_found = true;
                }
            }
        }

        if (!$slot_found) {
            return new WP_REST_Response([
                'success' => false,
                'message' => sprintf(
                    'No available slot found for %s at %s in the selected restaurant.',
                    ucfirst($slot_type),
                    date('h:i A', strtotime($reservation_time))
                ),
            ], 422);
        }

        $zone_query = new WP_Query([
            'post_type'      => 'zones',
            'post_status'    => 'publish',
            'title'          => $zone_name,
            'posts_per_page' => 1,
            'fields'         => 'ids'
        ]);

        if (empty($zone_query->posts)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Zone name does not exist. Please provide a valid zone.',
            ], 404);
        }

        $zone_id = $zone_query->posts[0];

        $table_id = null;
        $tables_in_zone = get_posts([
            'post_type'      => 'tables',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'     => '_table_zone_id',
                    'value'   => $zone_id,
                    'compare' => '='
                ]
            ]
        ]);

        foreach ($tables_in_zone as $table_post) {
            if (strtolower(trim($table_post->post_title)) === strtolower(trim($table_no))) {
                $table_id = $table_post->ID;
                break;
            }
        }
  
        if (!$table_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'The selected table does not belong to the specified zone.',
            ], 422);
        }

        $people_count = $no_of_people;
        // if ($people_count < 1 || $people_count > 12) {
        //     return new WP_REST_Response([
        //         'success' => false,
        //         'message' => 'People count must be between 1 and 12.',
        //     ], 422);
        // }
        $table_capacity = intval(get_post_meta($table_id, '_table_capacity', true));
        
        $current_time = date('H:i', strtotime($reservation_time));

        if (in_array($current_time, $slot_times_for_type)) 
        {
            $existing_reservations = get_posts([
                'post_type'       => 'reservation_list',
                'post_status'     => 'publish',
                'posts_per_page'  => -1,
                'meta_query'      => [
                    'relation' => 'AND',
                    [
                        'key'   => '_table_no',
                        'value' => $table_no,
                    ],
                    [
                        'key'   => '_reservation_date',
                        'value' => $reservation_date,
                    ],
                    [
                        'key'   => '_slot_type',
                        'value' => $slot_type,
                    ],
                    [
                        'key'   => '_zone_id',
                        'value' => $zone_id,
                    ],
                    [
                        'key'   => '_restaurant_id',
                        'value' => $restaurant_id,
                    ],
                    
                ]
            ]);

            foreach ($existing_reservations as $reservation) 
            {
                if ($reservation_id && $reservation->ID == $reservation_id) {
                    continue;
                }
                // $orders = wc_get_orders( [
                //     'limit'      => 1,
                //     'meta_key'   => '_reservation_id',
                //     'meta_value' => $reservation->ID,
                //     'return'     => 'objects',
                // ] );
        
                // if ( ! empty( $orders ) ) {
        
                //     $order = $orders[0];
        
                //     // If parent order is cancelled, update reservation
                //     if ( $order->get_parent_id() == 0 && $order->get_status() === 'cancelled' ) {
        
                //         update_post_meta( $reservation->ID, '_reservation_status', 'Cancelled' );
                //         update_post_meta( $reservation->ID, '_table_status', 'Available' );
                //     }
                // }
                $existing_status = get_post_meta($reservation->ID, '_table_status', true);

                if (in_array($existing_status, ['Reserve', 'Dine in'])) 
                {
                    return new WP_REST_Response([
                        'success' => false,
                        'message' => "$table_no is currently marked as \"$existing_status\" and cannot be reserved in this $slot_type slot on $reservation_date. Please choose another table in $slot_type slot.",
                    ], 409);
                }
            }
        }
        $active_orders = wc_get_orders([
            'status'    => [ 'processing', 'pending' ],
            'limit'     => 1,
            'meta_query'=> [
                'relation' => 'AND',
                [ 'key' => '_restaurant_id', 'value' => $restaurant_id ],
                [ 'key' => '_zone_id', 'value' => $zone_id ],
                [ 'key' => '_table_id', 'value' => $table_id ],
                [ 'key' => '_table_status', 'value' => 'Dine in' ],
                [ 'key' => '_parent_order', 'value' => 'yes' ]
            ],
            // 'post_status' => array_keys( wc_get_order_statuses() ),
        ]);

        if ( ! empty( $active_orders ) ) 
        {
           return new WP_REST_Response([
                'success'       => false,
                'message'       => 'This table is currently marked as Dine In. Please select another table.'
            ], 409);
        }
        $restaurants = get_option('pinaka_pos_restaurants', []);
        $restaurant_exists = false;
        foreach ($restaurants as $r) {
            if ($r['id'] == $restaurant_id) {
                $restaurant_exists = true;
                break;
            }
        }

        if (!$restaurant_exists) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Restaurant does not exist.',
            ], 409);
        }

        $user_restaurant_id = intval(get_user_meta($user_id, 'user_restaurant_id', true));
        if ($user_restaurant_id !== intval($restaurant_id)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'User not assigned to the selected restaurant.',
            ], 403);
        }

        // $ex_post = get_post($reservation_id);
        // if ($ex_post->post_author != $user_id) {
        //     return new WP_REST_Response([
        //         'success' => false,
        //         'message' => 'You are not authorized to update this reservation.',
        //     ], 403);
        // }
        
        // Update the post title
        $post_id = wp_update_post([
            'ID'         => $reservation_id,
            'post_title' => sanitize_text_field($params['customer_name'] ?? $post->post_title),
            'post_author'=> $user_id,
        ], true);

        if (is_wp_error($post_id)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Failed to update reservation.',
            ], 500);
        }

        update_post_meta($post_id, '_customer_name', $customer_name);
        update_post_meta($post_id, '_customer_phone', $customer_phone);
        update_post_meta($post_id, '_people_count', $people_count);
        update_post_meta($post_id, '_table_no', $table_no);
        update_post_meta($post_id, '_reservation_date', $reservation_date);
        update_post_meta($post_id, '_reservation_time', $reservation_time);
        update_post_meta($post_id, '_slot_type', $slot_type);
        update_post_meta($post_id, '_area', $zone_name);
        update_post_meta($post_id, '_zone_id', $zone_id);
        update_post_meta($post_id, '_restaurant_id', $restaurant_id);
        update_post_meta($post_id, '_restaurant_name', $restaurant_name);
        update_post_meta($post_id, '_priority_category', $priority);
        update_post_meta($post_id, '_table_status', $table_status);
        update_post_meta($post_id, '_table_no_id', $table_id);

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Reservation updated successfully.',
            'reservation_id' => $post_id,
            'customer_name' => $customer_name,
            'customer_phone' => $customer_phone,
            'people_count' => $people_count,
            'reservation_date' => $reservation_date,
            'reservation_time' => $reservation_time,
            'slot_type' => $slot_type,
            'table_no' => $table_no,
            'table_id' => $table_id,
            'restaurant_id' => $restaurant_id,
            'restaurant_name' => $restaurant_name,
            'zone_name' => $zone_name,
            'zone_id' => $zone_id,
            'priority_category' => $priority,
            'table_status' => $table_status,
        ], 200);
    }
    //functionality for reservation date range
    public function get_reservation_date_range(WP_REST_Request $request){
        $today = current_time('Y-m-d'); //Wordpress-safe current date
        $end_date = date('Y-m-d', strtotime($today . ' +6 days')); //total 7 days including today

        return new WP_REST_Response([
            'success'     => true,
            'start_date'  => $today,
            'end_date'    => $end_date,
            'message'     => 'Reservation allowed only between these dates.',
        ], 200);
    }
    public function cancel_reservation_method(WP_REST_Request $request) 
    {
        $params = $request->get_json_params();

        // Validate reservation_id
        if (!isset($params['reservation_id']) || empty($params['reservation_id'])) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Reservation ID is required.',
            ], 400);
        }
        // Validate reservation_id
        if (!isset($params['restaurant_id'])) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Restaurant ID is required.',
            ], 400);
        }
        $restaurant_id = intval($params['restaurant_id']);
        if (empty($restaurant_id)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Restaurant ID is required.',
            ], 400);
        }
        $user_id = get_current_user_id();
        
        $user_restaurant_id = intval(get_user_meta($user_id, 'user_restaurant_id', true));
       
        if ($user_restaurant_id !== intval($restaurant_id)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'User not assigned to the selected restaurant.',
            ], 403);
        }
        $reservation_id = absint($params['reservation_id']);
        $reservation_post = get_post($reservation_id);

        // Validate reservation existence
        if (!$reservation_post || $reservation_post->post_type !== 'reservation_list' || $reservation_post->post_status !== 'publish') {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Reservation does not exist or is invalid.',
            ], 404);
        }

        // Fetch required meta values
        $reservation_restaurant  = get_post_meta($reservation_id, '_restaurant_id', true);
        
        if (intval($reservation_restaurant) !== intval($restaurant_id)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Restaurant id not matching with reservation.',
            ], 403);
        }
        $reservation_date  = get_post_meta($reservation_id, '_reservation_date', true);
        $reservation_time  = get_post_meta($reservation_id, '_reservation_time', true);

        // Check if reservation is in the past or within 10 minutes
        $current_datetime      = strtotime(date('Y-m-d h:i A'));
        $reservation_timestamp = strtotime($reservation_date . ' ' . $reservation_time);

        if ($reservation_timestamp < $current_datetime) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Cannot cancel a past reservation.',
            ], 422);
        }

        if (date('Y-m-d', $reservation_timestamp) === date('Y-m-d', $current_datetime)) 
        {
            $time_difference = $reservation_timestamp - $current_datetime;
            if ($time_difference < 600) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Cannot cancel reservation within 10 minutes of the reservation time.',
                ], 422);
            }
        }

        // Update status for old and current tables if needed
        $table_id     = get_post_meta($reservation_id, '_table_no_id', true);
        $table_status = get_post_meta($reservation_id, '_table_status', true);
        $reserve_stat = get_post_meta($reservation_id, '_reservation_status', true);
        if ( ! in_array($reserve_stat, ['Cancelled', 'Expired'], true) ) {
            update_post_meta($reservation_id, '_table_status', 'Available');
            update_post_meta($reservation_id, '_reservation_status', 'Cancelled');
            update_post_meta($reservation_id, '_force_cancellation', 1);

            $meta = get_post_meta($reservation_id);

            // Build response array
            $response = [
                'success'            => true,
                'message'            => 'Reservation cancelled successfully.',
                'reservation_id'     => $reservation_id,
                'reservation_title'  => $reservation_post->post_title,
                'customer_name'      => $meta['_customer_name'][0] ?? '',
                'customer_phone'     => $meta['_customer_phone'][0] ?? '',
                'people_count'       => intval($meta['_people_count'][0] ?? 0),
                'priority_category'  => $meta['_priority_category'][0] ?? '',
                'zone_id'            => intval($meta['_zone_id'][0] ?? 0),
                'zone_name'          => $meta['_area'][0] ?? '',
                'table_no'           => $meta['_table_no'][0] ?? '',
                'table_id'           => $meta['_table_no_id'][0] ?? '',
                'restaurant_id'      => intval($meta['_restaurant_id'][0] ?? 0),
                'restaurant_name'    => $meta['_restaurant_name'][0] ?? '',
                'slot_type'          => $meta['_slot_type'][0] ?? '',
                'reservation_time'   => $meta['_reservation_time'][0] ?? '',
                'reservation_date'   => $meta['_reservation_date'][0] ?? '',
                'table_status'       => $meta['_table_status'][0] ?? '',
                'reservation_status' => $meta['_reservation_status'][0] ?? '',
            ];
            return new WP_REST_Response($response, 200);
        } else {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Reservation is already cancelled or expired.',
            ], 409);
        }
    }
    public function get_reservation_by_id($request) {
        $reservation_id = $request->get_param('id');

        // Check if reservation exists
        $post = get_post($reservation_id);
        if (!$post || $post->post_type !== 'reservation_list') {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Reservation not found.',
            ], 404);
        }

        // Get meta fields
        $meta = get_post_meta($reservation_id);

        $response = [
            'success'            => true,
            'reservation_id'     => $reservation_id,
            'reservation_title'  => $post->post_title,
            'customer_name'      => $meta['_customer_name'][0] ?? '',
            'customer_phone'     => $meta['_customer_phone'][0] ?? '',
            'people_count'       => intval($meta['_people_count'][0] ?? 0),
            'priority_category'  => $meta['_priority_category'][0] ?? '',
            'zone_id'            => intval($meta['_zone_id'][0] ?? 0),
            'zone_name'          => $meta['_area'][0] ?? '',
            'table_no'           => $meta['_table_no'][0] ?? '',
            'restaurant_id'      => intval($meta['_restaurant_id'][0] ?? 0),
            'restaurant_name'    => $meta['_restaurant_name'][0] ?? '',
            'slot_type'          => $meta['_slot_type'][0] ?? '',
            'reservation_time'   => $meta['_reservation_time'][0] ?? '',
            'reservation_date'   => $meta['_reservation_date'][0] ?? '',
            'table_status'       => $meta['_table_status'][0] ?? '',
        ];

        return new WP_REST_Response($response, 200); 
    }

    public function get_all_reservations($request) {
        $args = [
            'post_type'      => 'reservation_list',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
        ];

        $query = new WP_Query($args);
        $reservations = [];

        $wp_timezone = wp_timezone(); // Get WP timezone
        // $today = new DateTime('today', $wp_timezone);
        // $end_date = clone $today;
        // $end_date->modify('+6 days'); // 7-day window

        foreach ($query->posts as $post) {
            $reservation_id = $post->ID;
            $meta = get_post_meta($reservation_id);
            
            $raw_status = $meta['_table_status'][0] ?? '';
            $reservation_time_raw = $meta['_reservation_time'][0] ?? '';
            $reservation_date_raw = $meta['_reservation_date'][0] ?? '';
            $reservation_status = '';
            $buffer_time = '';

            if (empty($reservation_date_raw)) continue;

            try {
                $reservation_date = new DateTime($reservation_date_raw, $wp_timezone);
            } catch (Exception $e) {
                continue;
            }

            // if ($reservation_date < $today || $reservation_date > $end_date) {
            //     continue;
            // }

            // Time & status logic
            if (!empty($reservation_time_raw)) {
                try {
                    $datetime_str = $reservation_date_raw . ' ' . $reservation_time_raw;
                    $start_dt = new DateTime($datetime_str, $wp_timezone);
                    $end_dt = clone $start_dt;
                    $end_dt->modify('+30 minutes');

                    $start_time = $start_dt->format('h:i A');
                    $end_time = $end_dt->format('h:i A');
                    $buffer_time = $start_time . ' - ' . $end_time;

                    $now_dt = new DateTime('now', $wp_timezone);

                    if (in_array($raw_status, ['Dine in', 'Ready to Pay'])) {
                        $reservation_status = 'Seated';
                    } elseif ($raw_status === 'Cancelled' && $now_dt < $end_dt) {
                        $reservation_status = 'Cancelled';
                    } elseif ($now_dt > $end_dt && !in_array($raw_status, ['Dine in', 'Ready to Pay', 'Cancelled'])) {
                        $reservation_status = 'Expired';
                    }
                  

                } catch (Exception $e) {
                    $buffer_time = 'Invalid time';
                }
            }

            // Auto-update _table_status to Available if reservation is Cancelled or Expired and was previously Reserve
            if (in_array($reservation_status, ['Expired', 'Cancelled']) && $raw_status === 'Reserve') {
                update_post_meta($reservation_id, '_table_status', 'Available');
                $table_id = get_post_meta($reservation_id, '_table_no_id', true);
                update_post_meta($table_id, '_table_status', 'Available');
                if($reservation_status=="Expired")
                {
                    update_post_meta($reservation_id, '_reservation_status', 'Expired');
                }
                else
                {
                    update_post_meta($reservation_id, '_reservation_status', 'Cancelled');
                }
                
                $raw_status = 'Available';
            }

            $table_status = $raw_status; 
      
            $edit_cutoff_timestamp = strtotime($reservation_date_raw . ' ' . $reservation_time_raw) - 600;
            $edit_cutoff_time = date('h:i A', $edit_cutoff_timestamp); 

            //Pre-buffer time
            $pre_buffer_timestamp = strtotime($reservation_date_raw . '' . $reservation_time_raw) - 1800;
            $pre_buffer_time = date('h:i:A', $pre_buffer_timestamp);

            $force_cancellation = get_post_meta($reservation_id,'_force_cancellation',true);
            if($force_cancellation && $force_cancellation == 1)
            {
                $reservation_status = "Cancelled";
            }

            $reservations[] = [
                'reservation_id'     => $reservation_id,
                'reservation_title'  => $post->post_title,
                'customer_name'      => $meta['_customer_name'][0] ?? '',
                'customer_phone'     => $meta['_customer_phone'][0] ?? '',
                'people_count'       => intval($meta['_people_count'][0] ?? 0),
                'priority_category'  => $meta['_priority_category'][0] ?? '',
                'zone_id'            => intval($meta['_zone_id'][0] ?? 0),
                'zone_name'          => $meta['_area'][0] ?? '',
                'table_no'           => $meta['_table_no'][0] ?? '',
                'restaurant_id'      => intval($meta['_restaurant_id'][0] ?? 0),
                'restaurant_name'    => $meta['_restaurant_name'][0] ?? '',
                'slot_type'          => $meta['_slot_type'][0] ?? '',
                'reservation_time'   => $reservation_time_raw,
                'reservation_date'   => $reservation_date_raw,
                'cutoff_time'        => $edit_cutoff_time,
                'table_status'       => $raw_status,
                'reservation_status' => $reservation_status,
                'buffer_time'        => $buffer_time,
                'pre_buffer_time'    => $pre_buffer_time,
            ];
        }
        return new WP_REST_Response([
            'success'      => true,
            'reservations' => $reservations,
        ],200);
    }


    public function get_reservation_report(WP_REST_Request $request)
{
    $restaurant_id = absint($request->get_param('restaurant_id'));



    $period = sanitize_text_field($request->get_param('period'));
    $from   = sanitize_text_field($request->get_param('from'));
    $to     = sanitize_text_field($request->get_param('to'));

    $wp_timezone = wp_timezone();

    // If from/to are not provided, calculate from period
    if (empty($from) || empty($to)) {

        switch ($period) {

            case 'today':
                $from = current_time('Y-m-d');
                $to   = $from;
                break;

            case 'yesterday':
                $from = date('Y-m-d', strtotime(current_time('Y-m-d') . ' -1 day'));
                $to   = $from;
                break;

            case 'this_week':
                $from = date('Y-m-d', strtotime('monday this week'));
                $to   = current_time('Y-m-d');
                break;
            
            case 'tomorrow':
                $from = date('Y-m-d', strtotime(current_time('Y-m-d') . ' +1 day'));
                $to   = $from;
                break;

            case 'next_6_days':
                $from = current_time('Y-m-d');
                $to   = date('Y-m-d', strtotime(current_time('Y-m-d') . ' +6 days'));
                break;

            case 'this_month':
                $from = date('Y-m-01');
                $to   = current_time('Y-m-d');
                break;

            case 'last_month':
                $from = date('Y-m-01', strtotime('first day of last month'));
                $to   = date('Y-m-t', strtotime('last day of last month'));
                break;

            default:
                $from = current_time('Y-m-d');
                $to   = $from;
        }
    }

    $from_date = new DateTime($from, $wp_timezone);
    $to_date   = new DateTime($to, $wp_timezone);
    $to_date->setTime(23, 59, 59);

    $args = [
    'post_type'      => 'reservation_list',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
        ];

        if (!empty($restaurant_id)) {

            $args['meta_query'] = [
                [
                    'key'     => '_restaurant_id',
                    'value'   => $restaurant_id,
                    'compare' => '='
                ]
            ];

        }

    $query = new WP_Query($args);

    $reservations = [];

    foreach ($query->posts as $post) {

        $meta = get_post_meta($post->ID);

        $reservation_date_raw = $meta['_reservation_date'][0] ?? '';

        if (empty($reservation_date_raw)) {
            continue;
        }

        try {
            $reservation_date = new DateTime($reservation_date_raw, $wp_timezone);
        } catch (Exception $e) {
            continue;
        }

        // Date range filter
        if ($reservation_date < $from_date || $reservation_date > $to_date) {
            continue;
        }

        $reservation_date = $meta['_reservation_date'][0] ?? '';
        $raw_status = $meta['_table_status'][0] ?? '';
            $reservation_time_raw = $meta['_reservation_time'][0] ?? '';

            $reservation_status = 'Upcoming';

            $start_dt = new DateTime(
                $reservation_date_raw . ' ' . $reservation_time_raw,
                $wp_timezone
            );

            $end_dt = clone $start_dt;
            $end_dt->modify('+30 minutes');

            $now_dt = new DateTime('now', $wp_timezone);

            if (in_array($raw_status, ['Dine in', 'Ready to Pay'])) {

                $reservation_status = 'Seated';

            } elseif ($raw_status === 'Cancelled' && $now_dt < $end_dt) {

                $reservation_status = 'Cancelled';

            } elseif (
                $now_dt > $end_dt &&
                !in_array($raw_status, ['Dine in', 'Ready to Pay', 'Cancelled'])
            ) {

                $reservation_status = 'Expired';

            }

        $reservations[] = [
            'reservation_id'   => $post->ID,
            'customer_name'    => $meta['_customer_name'][0] ?? '',
            'customer_phone'   => $meta['_customer_phone'][0] ?? '',
            'people_count'     => intval($meta['_people_count'][0] ?? 0),
            'reservation_date' => $reservation_date_raw,
            'reservation_time' => $meta['_reservation_time'][0] ?? '',
            'slot_type'        => $meta['_slot_type'][0] ?? '',
            'table_no'         => $meta['_table_no'][0] ?? '',
            'zone_name'        => $meta['_area'][0] ?? '',
            'table_status'     => $raw_status,
            'reservation_status' => $reservation_status,
            'restaurant_id'    => intval($meta['_restaurant_id'][0] ?? 0),
        ];
    }

    return new WP_REST_Response([
        'success' => true,
        'period'  => $period,
        'from'    => $from,
        'to'      => $to,
        'count'   => count($reservations),
        'reservations' => $reservations
    ], 200);
}
}